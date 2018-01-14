<?php
/*
*@file
*Contains \Drupal\busca_alumnos\Form\BuscaAlumnosForm.
*/

namespace Drupal\busca_alumnos\Form;

use Drupal\Core\Form\Formbase;
use Drupal\Core\Form\FormStateInterface;

require_once 'EnviosManager.php';


/*
 * Formulario que filtra los mensajes y avisos para su envío
 */


class BuscaAlumnosForm extends FormBase{
   
//Clave del servidor FCM asociada a nuestro proyecto Android
    
 private $SERVER_KEY = '*';    
    
    
   /**
    *{@inheritdoc}     
    */
    
    public function getFormId(){
        
        return 'busca_alumnos_form';
        
    }
    
  /**
   * Construye el formulario indicando los elementos HTML 
   * que se incluirán.
   *
   * A build form method constructs an array that defines how markup and
   * other form elements are included in an HTML form.
   *
   * @param array $form
   *   Estructura por defecto del formulario.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Objeto conteniendo el estado actual del formulario.
   *
   * @return array
   *   El array renderizado conteniendo los elementos del formulario.
   */
    
    
    
    public function buildForm(array $form, FormStateInterface $form_state){

        //la propia id del usuario que se encuentra manejando el formulario y con sesión activa
        
        $uid = \Drupal::currentUser()->id();
        
            
        /*DEFINICIÓN DE FORMULARIOS Y CONTAINERS*/
        
       //formulario para filtrar por curso o alumno
        $form['filtro']=[
            '#title'=>$this->t('Filtrar Aviso por: '),
            '#type' =>'select',
            '#options'=>$this->getFiltro(),
            '#empty_option'=>$this->t('-Seleccione filtro-'),
            '#ajax'=>[
                'callback'=>'::updateCursosProfesor',
                'wrapper' =>'cursos-profesor',
            ],
        ];
        
        //formulario para mostrar los cursos del profesor
        
        $form['cursos_profesor']=[
            '#type'=>'container',
            '#attributes'=>['id'=>'cursos-profesor'],
        ];
        
        
        //formulario para mostrar los titulos de los avisos
        
        $form['titulos_avisos']=[
            '#type'=>'container',
            '#attributes'=>['id'=>'titulos-avisos'],
        ];
        
        //formulario para mostrar el texto de los avisos
        
        $form['texto_aviso']=[
            '#type'=>'container',
            '#attributes'=>['id'=>'texto-aviso'],
        ];
        
        //Formulario para mostrar los alumnos del curso seleccionado
        $form['alumnos_curso']=[
            '#type'=>'container',
            '#attributes'=>['id'=>'alumnos-curso'],
        ];
        
        //formulario para mostrar los titulos de avisos a un alumno
        
        $form['titulos_avisos_alumno']=[
            '#type'=>'container',
            '#attributes'=>['id'=>'titulos-avisos-alumno'],
            '#required'=>TRUE,
        ];
        
        //area de texto para mostrar el aviso dirigido al alumno
        
        $form['texto_aviso_alumno']=[
            '#type'=>'container',
            '#attributes'=>['id'=>'texto-aviso-alumno'],
            '#required'=>TRUE,
        ];
        
        
        //Comenzamos el flujo del formulario
        
        $filtro = $form_state -> getValue('filtro');
        
          
        if(!empty($filtro)){
                        
            //si el filtro es por 'Curso'
            
            if($filtro==1){
                     
                $form['cursos_profesor']['curso']=[
                '#type' => 'select',
                '#title'=>$this->t('Mis Cursos: '),
                '#options'=>$this->getCursosProfesor($uid),
                '#empty_option'=>'-Seleccione Curso-',
                '#ajax'=>[
                'callback'=>'::updateTitulosAvisos',
                'wrapper' =>'titulos-avisos',
            ],
                
            ];  
          
                  $curso_seleccionado = $form_state->getValue('curso');
 
                  if(!empty($curso_seleccionado)){
                    
                    $form['titulos_avisos']['titulo']=[
                    '#type' => 'select',
                    '#title'=>$this->t('Titulos Avisos: '),
                    '#options'=> $this->getTitulosAvisos($uid,$curso_seleccionado),
                    '#empty_option'=>'-Titulo de Aviso-', 
                     '#ajax'=>[
                        'callback'=>'::updateTextoAviso',
                        'wrapper' =>'texto-aviso',
                        
                              ],
                    ];
                   
                  }
                  
                  //Recoge el titulo del aviso seleccionado
                  
                   $aviso_seleccionado=$form_state->getValue('titulo');
            
                   
                   if(!empty($aviso_seleccionado)){
                      
                       
                        $texto_aviso=$this->getTextoAvisos($uid,$curso_seleccionado,$aviso_seleccionado);
                        
                        $form['texto_aviso']['texto']=[
                          '#type' => 'textarea',
                          '#title' => $this->t('Texto: '),
                          '#value' => $texto_aviso, 
                          //'#attributes'=>['readonly' => 'readonly'],
                          '#size' => 100,
                          '#maxlength' => 10000,      
                           ]; 
                       
                   }                            
            }
            
            //Si se ha seleccionado por 'alumno'
            
            if($filtro==2){
                
                 $form['cursos_profesor']['curso']=[
                '#type' => 'select',
                '#title'=>$this->t('Curso del alumno: '),
                '#options'=>$this->getCursosProfesor($uid),
                '#empty_option'=>'-Curso del Alumno-',
                '#ajax'=>[
                'callback'=>'::updateAlumnosCurso',
                'wrapper' =>'alumnos-curso',
            ],                
            ]; 
               
               //guarda la posición seleccionada el la select list       
               $curso_alumno_seleccionado = $form_state->getValue('curso');
             
               
               if(!empty($curso_alumno_seleccionado)){
                    
                    $form['alumnos_curso']['alumno']=[
                    '#type' => 'select',
                    '#title'=>$this->t('Alumnos: '),
                    '#options'=> $this->getNombresHijos($curso_alumno_seleccionado),
                    '#empty_option'=>'-Lista Alumnos-',  
                    '#ajax'=>[
                             'callback'=>'::updateTitulosAvisosAlumno',
                             'wrapper' =>'titulos-avisos-alumno',
                           ],  
                    ];
                    
                    
                   }
                   
                   //Se recoge el valor seleccionado en la select list. Corresponde al alumno seleccionado
                   $alumno_seleccionado = $form_state->getValue('alumno');
                  
                   if(!empty($alumno_seleccionado)){
                       
                       $form['titulos_avisos_alumno']['titulo_mensaje']=[
                           '#type'=>'select',
                           '#title'=>$this->t('Titulo Aviso'),
                           '#options'=>$this->getTitulosMensajes($alumno_seleccionado,$curso_alumno_seleccionado),
                           '#empty_option'=>'Titulo Aviso',
                           '#ajax'=>[
                               'callback'=>'::updateTextoAvisoAlumno',
                               'wrapper'=>'texto-aviso-alumno',
                           ],
                       ];
                       
                   }
                   
                   //Recoge el título del mensaje seleccionado, para posteriormente poder tomar el texto.
                   $aviso_alumno_seleccionado = $form_state->getValue('titulo_mensaje');
                         
                   
                   if(!empty($aviso_alumno_seleccionado)){
                       
                       $form['texto_aviso_alumno']['texto_alumno']=[
                        '#type'=>'textarea',
                        '#title' => $this->t('Texto'),
                          '#value' => $this->getTextoMensajes($aviso_alumno_seleccionado,$alumno_seleccionado,$curso_alumno_seleccionado),   
                          //'#attributes'=>['readonly' => 'readonly'],
                          '#size' => 1000,
                          //'#maxlength' => 10000,
                    ];
                       
                   }
            }
          
            
            //Salimos del primer y el segundo filtros
            
            
            
        }  
        
        $form['actions'] = [
            '#type' => 'actions',
            'submit' => [
            '#type' => 'submit',
            '#value' => $this->t('Enviar Mensajes'),
            ],
         ];
        
        
        return $form;
    }
    
    
  /*---------------------------------------------------------------------MÉTODOS------------------------------------------------------------------*/
    
 /*--MÉTODOS PARA FILTRAR ------------------------------------------------------------------------------------------------------------------------*/
    
    
    /*
    *Devuelve los filtros para el tipo de mensaje que se desea enviar: Curso, Alumno, General.
    * 
    * @return String
    * el filtro por el que se clasificarán los mensajes a enviar
    */ 
    
    
    protected function getFiltro(){
        $filtrarpor=array(
    
                '1' => $this->t('Curso'),                
                '2' => $this->t('Alumno'),
                   
                );
        return $filtrarpor;
    }
    
 /*--FIN MÉTODOS PARA FILTRAR ------------------------------------------------------------------------------------------------------------------------*/
   
    
 /*--MÉTODOS PARA CONECTAR Y EJECUTAR CONSULTAS EN LA BBDD -------------------------------------------------------------------------------------------*/   
  
   /*
    *Devuelve los cursos (terminos taxonómicos) asociados al profesor
    * 
    * @param int $id_profesor 
    * es la identidad del profesor con sesión activa en este momento.
    * 
    * @return array
    * devuelve un array [String] que contiene los cursos (términos taxonómicos) asociados al profesor con sesión activa en est momento.
    */ 
    
    protected function getCursosProfesor($id_profesor){
        
       //Selecciona la id de los términos taxonómicos asociados a la id del profesor con sesión activa en este momento (online)
       //SELECT field_cursos_asignados_target_id FROM user__field_cursos_asignados WHERE entity_id = $id_profesor 
        
       $query = \Drupal::database()->select('user__field_cursos_asignados', 'ufca');

                 $query->fields('ufca', ['field_cursos_asignados_target_id']); 
                 $query->condition('entity_id',$id_profesor);

                 $result = $query->execute();

                 $taxonomy_ids = $result->fetchCol();
                 
       //Selecciona el nombre de aquellos términos taxonómicos asociados al profesor online  
       //SELECT name FROM taxonomy_term_field_data WHERE tid IN ($taxonomy_ids)
                 
       $query = \Drupal::database()->select('taxonomy_term_field_data', 'ttfd');

                 $query->fields('ttfd', ['name']);   
                 $query->condition('tid',$taxonomy_ids,'IN');

                 $result = $query->execute();

                 $cursos_nombre_aux = $result->fetchCol();
                 
                 //Esto  lo hago por que al devolver un array, la primera posición = 0
                 //por lo que da problemas a la hora de que aparezca el segundo formulario, cuando
                 //hago if(!empty($curso_seleccionado)), ya que esta if hace que no muestre
                 //nada si se selecciona la primera opción del dropdown (que es = 0).
                 //$cursos_nombre[0] --> se queda vacia pero no influye ya que el dropdown lo ignora
                 //nisiquiera deja un hueco blanco al desplegar el menú
                    
                 $cursos_nombre=[];
                 
                 for($i=1;$i<=sizeof($cursos_nombre_aux);$i++){
                    
                     $cursos_nombre[$i]=$cursos_nombre_aux[$i-1];
                 } 
                 
                 return $cursos_nombre;
        
    }
    
    
   /*
    *Devuelve los titulos de los avisos creados por el profesor online. Cada uno de estos avisos se crea en el portal y es otro nodo más añadido a Drupal.
    * 
    * @param int $userid
    * es la identidad del profesor con sesión activa en este momento. Se usa únicamente para llamar al método getCursosProfesor($user_id)
    * 
    * @param int $curso_seleccionado
    * es la posición seleccionada en la select list
    *
    * @return array
    * devuelve un array [String] que contiene los titulos de los avisos que ha creado el profesor.
    */ 
    
        protected function getTitulosAvisos($user_id,$curso_seleccionado){
        
        
        $array_cursos= $this->getCursosProfesor($user_id); //ya que getCursosProfesor devuelve el array tal y como se muestra en el dropdown
        
        $tid_aux=$array_cursos[$curso_seleccionado];
        
        //Obtiene la id del termino taxonómico seleccionado en la select list
        
        $tid=$this->getIndiceNombreCurso($tid_aux);
         
       //Obtiene la id de los nodos que corresponden a este término taxonómico
       //SELECT nid FROM taxonomy_index WHERE tid = $tid
        
        $query = \Drupal::database()->select('taxonomy_index', 'ti');

                 $query->fields('ti', ['nid']); 
                 $query->condition('tid',$tid);
                 
                 // $stringquery1=(String)$query;
                             
                 $result = $query->execute();

                 $nid = $result->fetchCol();
           
        //otiene el titulo de los avisos asociados al término taxonómico y que además han sido creados por el profesor online
        //SELECT title FROM node_field_data WHERE nid IN($nid) AND uid = $user_id
                 
        $query = \Drupal::database()->select('node_field_data', 'nfd');

                 $query->fields('nfd', ['title']);       
                 $query->condition('nid',$nid,'IN')->condition('uid',$user_id);
                 
                 $result = $query->execute();

                 $titulos = $result->fetchCol();
                 
                 $titulos_def=[];
                 
                 $marka=1;
                
                 //Muevo una posición para que coincida con la selección en la select list
                 
                foreach($titulos as $titulito){
                    $titulos_def[$marka]=$titulito;
                    $marka=$marka+1;
                }
                 return $titulos_def;
    }
    
   /*
    * NOTA: ahora comentando el código, me he dado cuenta de que esta función podría ser mucho más simple. Se puede llamar desde aquí a
    * getTitulosAvisos($user_id,$curso_seleccionado) y luego buscar el cuerpo del aviso en node__body por el titulo.
    * 
    * !NOTA: resulta que está bien como está, ya que en la tabla node__body no hay un campo titulo, y el dato apropiado para obtener el cuerpo del 
    * aviso es la id del nodo.
    * 
    * 
    *Devuelve el texto del aviso destinado a un curso.
    * 
    * @param int $user_id
    * es la id del profesor online. Se usa para llamar al método getCursosProfesor($user_id) y para las condiciones de la consulta SQL.
    *
    * @param int $curso_seleccionaado
    * es la posición seleccionada en la select list. Corresponde al curso seleccionado.
    *
    * @param int $aviso_seleccionado
    *   
    * @return String
    * devuelve un String que es el texto del mensaje.
    */ 
    
    
      protected function getTextoAvisos($user_id,$curso_seleccionado,$aviso_seleccionado){
        
        $array_cursos= $this->getCursosProfesor($user_id); //ya que getCursosProfesor devuelve el array tal y como se muestra en el dropdown
        
        $tid_aux=$array_cursos[$curso_seleccionado];
        
        //Se asocia el nombre del curso con su id en la taxonomía
        $tid=$this->getIndiceNombreCurso($tid_aux);
         
        
        //Se obtienen las ids de todos aquellos nodos (Content type: Aviso) que tienen asociado dicho término taxonómico.
        //SELECT nid FROM taxonomy_index WHERE tid = $tid
        
        $query = \Drupal::database()->select('taxonomy_index', 'ti');

                 $query->fields('ti', ['nid']);                
                 $query->condition('tid',$tid);
                 
                  //$stringquery1=(String)$query;
                              
                 $result = $query->execute();

                 //Guarda las ids de los nodos correspondientes al término taxonómico correspondiente
                 $nids = $result->fetchCol();
       
        //Obtiene la id de aquellos nodos que se corresponde con los asociados a un termino taxonómico en concreto (obtenidos en la consulta anterior)
        //y que además también han sido creados por el profesor online.
        //SELECT nid FROM node_field_data WHERE nid IN ($nids)  AND uid = $user_id      
                 
        $query = \Drupal::database()->select('node_field_data', 'nfd');

                 $query->fields('nfd', ['nid']); 
                 $query->condition('nid',$nids,'IN')->condition('uid',$user_id);
                 
                 $result = $query->execute();

                 $id_nodos = $result->fetchCol();
                 
                 //de todos los nodos, se obtiene el seleccionado
                 $id_nodo=$id_nodos[$aviso_seleccionado-1];
         
                 
         //Obtiene el cuerpo del aviso
         //SELECT body_value FROM node__body WHERE entity_id = $id_nodo
                 
         $query =\Drupal::database()->select('node__body','nb');
         
                $query->fields('nb',['body_value']);  
                $query->condition('entity_id',$id_nodo);
                
                $result=$query->execute();
                
                $texto_aviso=$result->fetchField();
                
                return $texto_aviso;
        
    }
    
    /*
    *Devuelve los nombres de los alumnos del curso seleccionado del profesor online (un profesor puede tener más de un curso asignado)
    * 
    * @param int $curso_alumno_seleccionado
    * es la posición seleccionada en la select list. Se corresponde con el curso asociado al profesor y para el que el profesor ha creado
    * mensajes para algún alumno.
    * 
    * @return array
    * devuelve un array [String] que contiene los nombres de alumnos para los que el profesor ha creado algún mensaje en particular.
    */ 
       
    protected function getNombresHijos($curso_alumno_seleccionado){
        
        //recoge los valores tal y como se muestran en la select list
        $array_cursos = $this->getCursosProfesor(\Drupal::currentUser()->id());
        //recoge el valor de esa posición en el array. Es el nombre del curso elegido
        $curso_elegido=$array_cursos[$curso_alumno_seleccionado];
        
        
        //Recoge la id de los hijos/alumnos para los que ese profesor ha creado mensajes. tabla a_mensajes_hijo(id_hijo,id_profesor,titulo,mensaje,fecha)
        //SELECT id_hijo FROM a_mensajes_hijo WHERE id_profesor = \Drupal::currentUser()->id()
        
        $query = \Drupal::database()->select('a_mensajes_hijo', 'amh');

                 $query->fields('amh', ['id_hijo']);  
                 $query->condition('id_profesor',\Drupal::currentUser()->id());
                                 
                 $result1 = $query->execute();

                 $ids_hijos = $result1->fetchCol();
        
        //Recoge los nombres de los hijos para los que este profesor tiene mensajes creados. Tabla a_hijos(id,nombre,apellidos,nif_padre,nif_madre,curso)        
        //SELECT nombre FROM a_hijos WHERE id IN ( $ids_hijos) AND curso=$curso_elegido
                 
        $query = \Drupal::database()->select('a_hijos', 'ah');

                 $query->fields('ah', ['nombre']); 
                 $query->condition('id',$ids_hijos,'IN');
                 $query->condition('curso',$curso_elegido);
                                 
                 $result2 = $query->execute();

                 $nombres_hijos = $result2->fetchCol();
         
         //Recoge los apellidos de los hijos 
         //SELECT apellidos FROM a_hijos WHERE id IN ($ids_hijos) AND curso = $curso_elegido
         $query = \Drupal::database()->select('a_hijos', 'ah');

                 $query->fields('ah', ['apellidos']);                
                 $query->condition('id',$ids_hijos,'IN');                 
                 $query->condition('curso',$curso_elegido);
                                 
                 $result3 = $query->execute();

                 $apellidos_hijos = $result3->fetchCol();
                 
                // $nombre_completo = [];
        
        //Recoge las ids de los hijos
        //SELECT id FROM a_hijos WHERE id IN ($ids_hijos) AND curso = $curso_elegido
                 
        $query = \Drupal::database()->select('a_hijos', 'ah');

                 $query->fields('ah', ['id']);
                 
                 $query->condition('id',$ids_hijos,'IN');
                 
                 $query->condition('curso',$curso_elegido);
                                 
                 $result4 = $query->execute();

                 $ids_hijos = $result4->fetchCol();
                 
                 
              //Crea el nombre completo concatenado los resultado elegidos. El resultado es el array[String]nombre_completo, que almacena los nombres
              //de los hijos/alumnos para los que el profesor ha creado un mensaje
              //El hecho de ajustar las posiciones en el array es porque si se selecciona la primera opción de la select list se devuelve 1, y falla al 
              //hacerlo coincidir con el primer elemento de un array, es decir 0, cuando se relaciona el valor elegido en la select list con el correspondiente
              //al array que proporciona los valores en la select list.
        
                 for($i=1;$i<sizeof($apellidos_hijos)+1;$i++){
                     
                     $nombre_completo[$i] =$ids_hijos[$i-1].". ". $nombres_hijos[$i-1]." ".$apellidos_hijos[$i-1]; 
                 }
                 
                 return $nombre_completo;
    }
  
    
    /*
    *Devuelve los titulos de los mensajes de un alumno en particular para los que el profesor online ha creado mensajes.
    * 
    * @param int $alumno_seleccionado
    * es la posición en la select list que corresponde al alumno seleccionado y para el que se obtiene los titulos de los mensajes a él destinados.
    *
    * @param int $curso_alumno_seleccionado
    * es la posición seleccionada en la select list. Se corresponde con el curso asociado al profesor y para el que el profesor ha creado
    * mensajes para algún alumno. Se usa para llamar al método getNombresHijos($curso_alumno_seleccionado)
    * 
    * @return array
    * devuelve un array [String] que contiene los titulos de los mensajes de un alumno en concreto.
    */ 
      
    protected function getTitulosMensajes($alumno_seleccionado,$curso_alumno_seleccionado){
            
            $nombres = $this->getNombresHijos($curso_alumno_seleccionado);
            
            $nombre_alumno = $nombres[$alumno_seleccionado];
            
            //recoge la id del alumno/hijo haciendo un subString hasta el caracter '.'
            $id_hijo = strstr($nombre_alumno,".",TRUE);
            
            //Recoge los titulos de los mensajes destinados a ese alumno/hijo
            //SELECT titulo_mensaje FROM a_mensajes_hijo WHERE id_hijo = $id_hijo AND id_profesor = $id_profesor_online
            $query = \Drupal::database()->select('a_mensajes_hijo', 'amh');

                 $query->fields('amh', ['titulo_mensaje']);     
                 $query->condition('id_hijo',$id_hijo);
                 $query->condition('id_profesor',\Drupal::currentUser()->id());
                                 
                 $result = $query->execute();

                 $titulos = $result->fetchCol();  
                 
                 $titulos_def=[];
                 
                 //ajusta los valores en el array para hacerlos coincidir con la manera en que se muestran en la select list
                 for($i=1;$i<sizeof($titulos)+1;$i++){
                     $titulos_def[$i]=$titulos[$i-1];
                 }            
                 return $titulos_def;
    }
    
   /*
    *Devuelve el texto del mensaje destinado a un alumno en particular para el que el profesor online ha creado mensajes.
    * 
    * @param int $titulo_seleccionado
    * es la posición seleccionada en la select list. Se corresponde con el titulo del mensaje del que se obtendrá el texto.
    *
    * @param int $alumno_seleccionado
    * es la posición en la select list que corresponde al alumno seleccionado y para el que se obtiene los titulos de los mensajes a él destinados.
    * Se usa para llamar al método getTitulosMensajes($alumno_seleccionado,$curso_alumno_seleccionado) y en este a su vez para llamar a getNombresHijos($alumno_seleccionado).
    *
    * @param int $curso_alumno_seleccionado
    * es la posición seleccionada en la select list. Se corresponde con el curso asociado al profesor y para el que el profesor ha creado
    * mensajes para algún alumno. Se usa para llamar al método getNombresHijos($curso_alumno_seleccionado)
    * 
    * @return String
    * devuelve un String que es el texto del mensaje.
    */ 
    
     protected function getTextoMensajes($titulo_seleccionado,$alumno_seleccionado,$curso_alumno_seleccionado){
            
            $titulos = $this->getTitulosMensajes($alumno_seleccionado,$curso_alumno_seleccionado);
            
            $titulo = $titulos[$titulo_seleccionado];
            
            //Devuelve el texto del mensaje que se corresponda al titulo proporcionado
            //SELECT texto_mensaje FROM a_mensajes_hijo WHERE titulo_mensaje = $titulo
            
            $query = \Drupal::database()->select('a_mensajes_hijo', 'amh');

                 $query->fields('amh', ['texto_mensaje']); 
                 $query->condition('titulo_mensaje',$titulo);
                 
                  //$stringquery1=(String)$query;    
                 
                 $result = $query->execute();

                 $texto = $result->fetchField();
        
                 return $texto;
    }
    
  /*--FIN MÉTODOS PARA CONECTAR Y EJECUTAR CONSULTAS EN LA BBDD --------------------------------------------------------------------------------------*/     
    
    
  

    
 /*--MÉTODOS LLAMADOS CON AJAX --------------------------------------------------------------------------------------------------------------*/     

    /*Actualiza la  select list que contiene los cursos asociados al profesor online.
     * 
     * @param array $form
     * Estructura del formulario.
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   Objeto que contiene el estado actual del formulario.
     * 
     * @return array
     * el formulario con los cambios aplicados (se llena la caja de texto el con el texto del aviso)
     */
    public function updateCursosProfesor(array $form, FormStateInterface $form_state){
        
        return $form['cursos_profesor'];
    }
    
    /*
     * Actualiza la select list que contiene los titulos de los avisos destinados a un curso
     * 
     *  @param array $form
     *   Estructura del formulario.
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   Objeto que contiene el estado actual del formulario.
     * 
     * @return array
     * el formulario con los cambios aplicados (se llena la select list con los títulos de los avisos para un curso)
     */
     public function updateTitulosAvisos(array $form, FormStateInterface $form_state){
        
        return $form['titulos_avisos'];
    }
    
     /*Actualiza la caja de texto que contiene el aviso destinado a un curso.
     * 
     *  @param array $form
     *   Estructura del formulario.
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   Objeto que contiene el estado actual del formulario.
     * 
     * @return array
     * el formulario con los cambios aplicados (se llena la caja de texto el con el texto del aviso)
     */
    
    public function updateTextoAviso(array $form, FormStateInterface $form_state){
        
        return $form['texto_aviso'];
    }
    
     
    /*Actualiza la select list que contiene los alumnos asociados a un curso y para los que el profesor online ha creado  mensajes.
     * 
     *  @param array $form
     *   Estructura del formulario.
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   Objeto que contiene el estado actual del formulario.
     * 
     * @return array
     * el formulario con los cambios aplicados 
     */
     
     public function updateAlumnosCurso(array $form, FormStateInterface $form_state){
        
        return $form['alumnos_curso'];
    }
    
    /*Actualiza la select list que contiene los titulos de los mensajes destinados a un alumno en particular.
     * 
     * @param array $form
     *   Estructura del formulario.
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   Objeto que contiene el estado actual del formulario.
     * 
     * @return array
     * el formulario con los cambios aplicados (se llena la select list con los titulos de los avisos para un alumno)
     */
    
      public function updateTitulosAvisosAlumno(array $form, FormStateInterface $form_state){
        
        return $form['titulos_avisos_alumno'];
    }
    
    
    
    /*Actualiza la caja de texto que contiene el mensaje destinado a un alumno en particular.
     * 
     *  @param array $form
     *  Estructura del formulario.
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     * Objeto que contiene el estado actual del formulario.
     * 
     * @return array
     * el formulario con los cambios aplicados (se llena la caja de texto con el mensaje)
     */
    
     public function updateTextoAvisoAlumno(array $form, FormStateInterface $form_state){
        
        return $form['texto_aviso_alumno'];
    }
       
 /*--FIN MÉTODOS LLAMADOS CON AJAX --------------------------------------------------------------------------------------------------------------*/   
    
    
    
/*--MÉTODO DE ASOCIACIÓN DE ELEMENTOS DE LA SELECT LIST CON ID´S DE TÉRMINOS TAXONÓMICOS----------------------------------------------------------*/    
    
    
    //recupera el tid, es decir, el índice asociado a cada taxonomy_term.
    //Nota para mí: el fallo que tuve al hacerla al principio fue que devolvía varios índices, por lo que
    // luego me aparecían todas las noticias de ese profesor, sin filtrar por curso, debido a que aquí
    //devolvía un array con todos los índices de todos los cursos y posteriormente lo utilizaba
    //en la consulta, por lo que devolvía, correctamente para mi pesar, todas las noticias correspondientes a todos lo índices.
    //Ahora sólo le paso un parámetro $tid, que es un String que representa lo elegido en el dropdown,
    //lo transforma y devuelve un único índice,su id en la taxonomía, que además es el único curso del que queremos mostrar los avisos.
     
    
   /*
    * Asocia el id de los terminos taxonómicos con su nombre. Este id de los términos taxonómicos es el que Drupal les dió al crearlos y, al ser
    * los primeros elementos creados del primer vocabulario creado, ha coincidido que son 1,2,3,4....8. En caso de que al hacer una nueva versión
    * con más vocabulario y se crearan en orden distinto, sólo habría que ajustar aquí la correspondencia entre el termino y su id.
    * 
    * @param
    */ 
   protected function getIndiceNombreCurso($tid){
    
       $my_array=[];
                  
           switch($tid){
               
               case 'Primero Infantil':
                   $my_array[]=1;
                   break;
               case 'Segundo Infantil':
                   $my_array[]=2;
                   break;
               case 'Primero Primaria':
                   $my_array[]=3;
                   break;
               case 'Segundo Primaria':
                   $my_array[]=4;
                   break;
               case 'Tercero Primaria':
                   $my_array[]=5;
                   break;
               case 'Cuarto Primaria':
                   $my_array[]=6;
                   break;
               case 'Quinto Primaria':
                   $my_array[]=7;
                   break;
               case 'Sexto Primaria':
                   $my_array[]=8;
                   break;
               case 'General':
                   $my_array[]=9;
                   break;
               
           }
           $tid_def=$my_array[0];
       
       return $tid_def;
   }
   
/*--FIN MÉTODO DE ASOCIACIÓN DE ELEMENTOS DE LA SELECT LIST CON ID´S DE TÉRMINOS TAXONÓMICOS----------------------------------------------------------*/     
   





     /**
    *{@inheritdoc}     
    */
    
    public function validateForm(array &$form, FormStateInterface $form_state) {
        
       /* $check_texto=$form_state->getValue('texto_alumno');
        if(empty($check_texto)){
            
            $form_state->setErrorByName('texto_alumno',$this->t("Aún no has terminado de configurar el envío."));
        }*/
     
    }
    
    
    
/*--MÉTODOS PARA CONECTAR CON EL SERVIDOR DE FIREBASE CLOUD MESSAGING Y PARA OBTENER OTROS DATOS NECESARIOS------------------------------------------------*/

/*
 * Obtiene el nombre del profesor online
 * 
 * @param int $id
 * es la id del usuario conectado
 * 
 * @return String
 * un string que contiene el nombre del usuario conectado
 */   
   public function getNombreUsuario($id){
       
       
        $query = \Drupal::database()->select('users_field_data', 'ufd');

                 $query->fields('ufd', ['name']);
                 
                 $query->condition('uid',$id);
                                 
                 $result = $query->execute();

                 $nombre = $result->fetchField();
                 
                 return $nombre;
   }
   
   //devuelve el nombre del hijo, le paso el título (aprovechando que lo tengo de consultas anteriores)
   // y con el título cojo su id
   
  /*
   * Devuelve el nombre del hijo al que corresponde el mensaje
   * 
   * @param String
   * el titulo del mensaje que se usará para obtener la id del alumno al que va destinado el mensaje.
   * 
   * @return String
   * el nombre del hijo. Si el nombre es compuesto se añade '_' en los espacios entre nombres.
   *
   */
   public function getNombreHijo($titulo){
       
       //SELECT id FROM a_mensajes_hijo WHERE titulo_mensaje = $titulo
       
       $query = \Drupal::database()->select('a_mensajes_hijo', 'amh');

                 $query->fields('amh', ['id_hijo']);
                 
                 $query->condition('titulo_mensaje',$titulo);
                                 
                 $result = $query->execute();

                 $id_hijo = $result->fetchField();
        
        //SELECT nombre FROM a_hijos WHERE id = $id_hijo
                 
        $query = \Drupal::database()->select('a_hijos', 'ah');

                 $query->fields('ah', ['nombre']);
                 
                 $query->condition('id',$id_hijo);
                                 
                 $result = $query->execute();

                 $nombre_hijo = $result->fetchField();
                 
                 $nombre_hijo= str_replace(" ", "_", $nombre_hijo);
                 
                 return $nombre_hijo;
   }
     /**
    *
    * Realiza las peticiones POST al servidor de FCM. Se ejecuta cuando se pulsa en el botón 'Enviar mensaje' del formulario.
    * 
    * @param array $form
    * Default form array structure.
    * @param \Drupal\Core\Form\FormStateInterface $form_state
    *   Object containing current form state.  
    */
    
    public function submitForm(array &$form, FormStateInterface $form_state) {
        
        $filtro = $form_state -> getValue('filtro');
         
        //si el mensaje va destinado a un curso completo
        
        if($filtro == 1){
            
            //Se obtienen el curso,el título y el texto para enviarlos como datos en la petición
            
               $curso_seleccionado = $form_state->getValue('curso');
               drupal_set_message("curso seleccionado =".$curso_seleccionado);
               $cursos = $this->getCursosProfesor(\Drupal::currentUser()->id());
               $curso=$cursos[$curso_seleccionado];
               drupal_set_message("curso =".$curso);
               $titulos_avisos=$this->getTitulosAvisos(\Drupal::currentUser()->id(), $curso_seleccionado);
               $titulo_seleccionado = $form_state->getValue('titulo');
               $titulo_aviso = $titulos_avisos[$titulo_seleccionado];
               drupal_set_message("titulo aviso =".$titulo_aviso);
               $texto_aviso = $form_state->getValue('texto');
               drupal_set_message("texto aviso =".$texto_aviso);
               
               //Se añaden campos extra al mensaje
              
               //Se seleccionan los dni´s de los padres con alumnos en el curso seleccionado
               //SELECT nif_padre,nif_madre FROM a_hijos WHERE curso = $curso
               
                 $query = \Drupal::database()->select('a_hijos', 'ah');
                 $query->fields('ah', ['nif_padre']);
                 $query->fields('ah', ['nif_madre']);
                 $query->condition('curso',$curso);

                 $result = $query->execute();

                 $nifs = $result->fetchCol();
                 
                 foreach($nifs as $key=>$value){
                     drupal_set_message("nifs = ".$value);
                 }
               
             //selección del token de todos los padres seleccionados previamente.
             //SELECT token FROM a_padres WHERE nif IN($nifs)
                
              $query = \Drupal::database()->select('a_padres', 'ap');
              $query->fields('ap', ['token']);
              $query->condition('nif',$nifs,'IN');
           
              $result = $query->execute();

              $tokens = $result->fetchCol();
                 
                 foreach($tokens as $key=>$value){
                     drupal_set_message("tamaño tokens = ".sizeof($tokens)." Token = ".$value);
                 }
                 
           //creación del resto de campos necesarios para el mensaje
                 $curso = str_replace(" ","_",$curso);
                 $autor = $this->getNombreUsuario(\Drupal::currentUser()->id());
                 $fecha = date('m/d/Y h:i:s a', time());
                 
                 
           //Conexión con Firebase Cloud Messaging
            
                 $message = $_POST['message'];
                 $title = $_POST['title'];
                 $path_to_fcm = 'https://fcm.googleapis.com/fcm/send';
                 $server_key='*';
                 
                 $headers = array(
                        'Authorization:key=' .$server_key,
                        'Content-Type:application/json'
                 );
                 
                 
                 //envio del aviso
                 
                 foreach($tokens as $key=>$value){
                     
                 
                 $fields = array(
                         'to'=>$value,
                         //Estos datos se han quitado para provocar el envío de un mensaje de datos desde el servidor FCM, si se añade la notificación,
                         //posteriormente al recibir el mensaje en la aplicación, sólo notificaba pero no ejecutaba la inserción en la base de datos
                         //ya que los datos titulo y texto se recogían como nulos. Al pasarlo todo como data, los reconoce y funciona bien.
                     
                        /*'notification'=>array('title'=>$titulo_aviso,
                                                'body'=>$texto_aviso),*/
                         'data'=>array('destinatario'=>"curso-".$curso."!",
                                        'autor'=>$autor,
                                        'fecha'=>$fecha,
                                        'titulo'=>$titulo_aviso,
                                        'texto'=>$texto_aviso,)
                 
                 );
                 
                 $payload = json_encode($fields);
                 
                 //Creación de conexión, se añaden la URL, el método,las cabeceras,se indica que se devuelva el resultado de la transferencia
                 //como un string del valor de curl_exec(), se indica que  cURL no verifique el peer del certificado, se selecciona el tipo de 
                 //dirección IP y se añade el payload codificado en JSON.
                 
                 $curl_session= curl_init();
                 curl_setopt($curl_session, CURLOPT_URL, $path_to_fcm);
                 curl_setopt($curl_session, CURLOPT_POST, true);
                 curl_setopt($curl_session, CURLOPT_HTTPHEADER, $headers);
                 curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, true);
                 curl_setopt($curl_session, CURLOPT_SSL_VERIFYPEER, false);
                 curl_setopt($curl_session, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
                 curl_setopt($curl_session, CURLOPT_POSTFIELDS, $payload);
                 
                 $result = curl_exec($curl_session);
                 curl_close($curl_session);
                 }
    }
    
    
    // Si el mensaje va destinado a un alumno en concreto
    
    else{
        
        //Obtención de datos necesarios como los cursos del profesor, el alumno seleccionado y el título del mensaje seleccionado
        
        $cursos = $this->getCursosProfesor(\Drupal::currentUser()->id());
        $curso_seleccionado = $form_state->getValue('curso');
        $curso = $cursos[$curso_seleccionado];
        
        $alumnos = $this->getNombresHijos($curso_seleccionado);
        $alumno_seleccionado = $form_state->getValue('alumno');
        $alumno = $alumnos[$alumno_seleccionado];
        
        $titulos= $this->getTitulosMensajes($alumno_seleccionado,$curso_seleccionado);
        $titulo_seleccionado = $form_state->getValue('titulo_mensaje');
        $titulo =$titulos[$titulo_seleccionado];
        
        $texto = $this->getTextoMensajes($titulo_seleccionado, $alumno_seleccionado, $curso_seleccionado);
        
        //Obtención de la id del alumno ya que sabemos que hasta el caracter '.' es su id
        
        $id_hijo = strstr($alumno,".",TRUE);
        
        //Obtención de los dni´s asociados a ese hijo/alumno
        //SELECT nif_padre,nif_madre FROM a_hijos WHERE id_hijo = $id_hijo
        $query = \Drupal::database()->select('a_hijos', 'ah');

                 $query->fields('ah', ['nif_padre']);
                 $query->fields('ah', ['nif_madre']);
                 $query->condition('id',$id_hijo);

                 $result = $query->execute();

                 $nifs = $result->fetchCol();
                 
        //Obtención de los token asociados a los dni´s recogidos previamente
        //SELECT token FROM a_padres WHERE nif IN ($nifs
        //)
        $query = \Drupal::database()->select('a_padres', 'ap');

                 $query->fields('ap', ['token']);
                 
                 $query->condition('nif',$nifs,'IN');

                 $result = $query->execute();

                 $tokens = $result->fetchCol();
                 
                 //creamos el resto de campos para el mensaje
                 
                 $nombre_hijo=$this->getNombreHijo($titulo);
                 $autor = $this->getNombreUsuario(\Drupal::currentUser()->id());
                 $fecha = date('m/d/Y h:i:s a'/*, time()*/);
                 
                 
                 $message = $_POST['message'];
                 $title = $_POST['title'];
                 $path_to_fcm = 'https://fcm.googleapis.com/fcm/send';
                 $server_key='*';   ;
                 
                 $headers = array(
                        'Authorization:key=' .$server_key,
                        'Content-Type:application/json'
                 );
                 
                 foreach($tokens as $key=>$value){
                 $fields = array('to'=>$value,
                        /* 'notification'=>array('title'=>$titulo,'body'=>$texto),*/
                         'data'=>array('destinatario'=>"hijo-".$nombre_hijo."!",
                                        'autor'=>$autor,
                                        'fecha'=>$fecha,
                                        'titulo'=>$titulo,
                                        'texto'=>$texto)
                 
                 );
                 
                 $payload = json_encode($fields); 
                 
                 //Creación de conexión, se añaden la URL, el método,las cabeceras,se indica que se devuelva el resultado de la transferencia
                 //como un string del valor de curl_exec(), se indica que  cURL no verifique el peer del certificado, se selecciona el tipo de 
                 //dirección IP y se añade el payload codificado en JSON.
                 
                 $curl_session= curl_init();
                 curl_setopt($curl_session, CURLOPT_URL, $path_to_fcm);
                 curl_setopt($curl_session, CURLOPT_POST, true);
                 curl_setopt($curl_session, CURLOPT_HTTPHEADER, $headers);
                 curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, true);
                 curl_setopt($curl_session, CURLOPT_SSL_VERIFYPEER, false);
                 curl_setopt($curl_session, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
                 curl_setopt($curl_session, CURLOPT_POSTFIELDS, $payload);
                 
                 $result = curl_exec($curl_session);
                 curl_close($curl_session);
                 }
        
    }
    

    
    
   }
   
}//end of class
   
   
    
    

