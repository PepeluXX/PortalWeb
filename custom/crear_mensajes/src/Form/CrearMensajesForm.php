<?php 


/*
*@file
*Contains \Drupal\crear_mensajes\Form\CrearMensajesForm.
*/

namespace Drupal\crear_mensajes\Form;

use Drupal\Core\Form\Formbase;
use Drupal\Core\Form\FormStateInterface;

class CrearMensajesForm extends FormBase{
    
   /**
    *{@inheritdoc}     
    */
    
    public function getFormId(){
        
        return 'crear_mensajes_form';
        
    }
    /**
    *{@inheritdoc}     
    */
       public function buildForm(array $form, FormStateInterface $form_state){
           
           $form['titulo_pagina']=[
              '#title'=>$this->t('Creación de mensajes: '),
              '#type'=>'page_title', 
           ];
           $form['curso_alumno']=[
              '#title'=>$this->t('Curso Alumno: '),
              '#type'=>'select',
              '#empty_option'=>$this->t("-Seleccione curso-"),
              '#options'=>$this->getCursosProfesor(\Drupal::currentUser()->id()),
              '#required'=>TRUE,
              '#ajax'=>[
                  'callback'=>'::updateNombresAlumnos',
                  'wrapper'=>'caja'
              ]
           ];
           
           $form['caja']=[
               '#type'=>'container',
               '#attributes'=>['id'=>'caja'],
           ];
           
            $form['titulo']=[
               '#type'=>'container',
               '#attributes'=>['id'=>'titulo'],
           ];
           $curso_seleccionado=$form_state->getValue('curso_alumno');
           
           
           
           if(!empty($curso_seleccionado)){
               
           $form['caja']['nombres']=[
               '#title'=>$this->t('Nombre Alumno: '),
               '#type'=>'select',
               '#empty_option'=>$this->t('-Selecciona alumno-'),
               '#options'=>$this->getNombresAlumnos($curso_seleccionado),
               '#required'=>TRUE,
               '#ajax'=>[
                   'callback'=>'::updateTituloMensaje',
                   'wrapper'=>'titulo',
               ],
           ];
           
           }
           
           $nombre_alumno= $form_state->getValue('nombres');
           //drupal_set_message("nombre alumno: ".$nombre_alumno);
           
           if(!empty($nombre_alumno)){
               $form['titulo']['titulito']=[
                  '#title'=>$this->t('Titulo: '),
                  '#type' =>'textfield',
                  '#required'=>TRUE,
                '#size' => 100,
                '#maxlength' =>100,
                   
               ];
               
               $form['titulo']['area_texto']=[
                  '#title'=>$this->t('Cuerpo Mensaje: '),
                  '#type' =>'textarea',
                  '#required'=>TRUE,
                '#size' => 1000,
                '#maxlength' =>1000,
                   
               ];
           }
           
             $form['actions'] = [
            '#type' => 'actions',
            'submit' => [
            '#type' => 'submit',
            '#value' => $this->t('Crear Mensaje'),
      ],
    ];
          
           return $form;
       }

       
       
       
       public function validateForm(array &$form, FormStateInterface $form_state){
           
          
       }
       
       public function submitForm(array &$form, FormStateInterface $form_state){
       
           $alumno_seleccionado=$form_state->getValue('nombres');
           
           $array_nombres=$this->getNombresAlumnos($form_state->getValue('curso_alumno'));
           
           $nombre_alumno = $array_nombres[$alumno_seleccionado]; 
           
           $id_hijo=strstr($nombre_alumno,".",TRUE );
           
          // drupal_set_message("id hijo-> ".$id_hijo);
           
           $user_id=\Drupal::currentUser()->id();
           
           $titulo_mensaje=$form_state->getValue('titulito');
           
           $texto_mensaje=$form_state->getValue('area_texto');
           
           try{
            $connection = \Drupal::database();
      
             $result = $connection->insert('a_mensajes_hijo')
                ->fields([
                    'id_hijo' => $id_hijo,
                    'id_profesor' => $user_id,
                    'titulo_mensaje'=>$titulo_mensaje,
                    'texto_mensaje' => $texto_mensaje,
                     ])
                    ->execute();
      }catch(SQLExcet $e){
            drupal_set_message($this->t("Se ha producido un error en la activación del servicio.".$e));
        }
        drupal_set_message("El mensaje para ".$nombre_alumno." ha sido creado, puede revisarlo y enviarlo"
                . " desde 'Enviar Avisos y Mensajes'");
       }
       
       
       
       protected function getIDHijo($nombre_hijo,$curso_seleccionado){
           
           $id_nom_ap=$this->getNombresAlumnos($curso_seleccionado);
            $query = \Drupal::database()->select('a_hijos', 'ah');

                 $query->fields('ah', ['id']);
                 
                 $query->condition('nombre',$nombre_hijo);

                 $result = $query->execute();

                 $id_hijo = $result->fetchField();
                 
                 return $id_hijo;
               
       }
       
       
        protected function getCursosProfesor($id_profesor){
        
       $query = \Drupal::database()->select('user__field_cursos_asignados', 'ufca');

                 $query->fields('ufca', ['field_cursos_asignados_target_id']);
                 
                 $query->condition('entity_id',$id_profesor);

                 $result = $query->execute();

                 $cursos = $result->fetchCol();
                 
                            
       $query = \Drupal::database()->select('taxonomy_term_field_data', 'ttfd');

                 $query->fields('ttfd', ['name']);
                 
                 $query->condition('tid',$cursos,'IN');

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
    
    protected function getNombresAlumnos($curso_seleccionado){
        
        $nombres_cursos = $this->getCursosProfesor(\Drupal::currentUser()->id());
        $curso = $nombres_cursos[$curso_seleccionado];
        
        $query = \Drupal::database()->select('a_hijos', 'ah');

                 $query->fields('ah', ['id_hijo']);
                 
                 
                 $query->condition('curso',$curso);

                 $result1 = $query->execute();
                 
                 $ids_alumnos = $result1->fetchCol();
                 
         $query = \Drupal::database()->select('a_hijos', 'ah');

                 $query->fields('ah', ['nombre']);
                 
                 
                 $query->condition('curso',$curso);

                 $result2 = $query->execute();
                 
                 $nombres_alumnos = $result2->fetchCol();
                 
         $query = \Drupal::database()->select('a_hijos', 'ah');     
                 
                 $query->fields('ah', ['apellidos']);
                 
                 $query->condition('curso',$curso);

                 $result3 = $query->execute();        
                 
                $apellidos_alumnos=$result3->fetchCol();
                 
                $completo=[];
                
                
                for($i=1;$i<sizeof($nombres_alumnos)+1;$i++){
                    $completo[$i]=$ids_alumnos[$i-1]." . ".$nombres_alumnos[$i-1]." ".$apellidos_alumnos[$i-1];
                }
                 
                
                
                 return $completo;
        
    }
    
    
    
    public function updateTituloMensaje(array $form, FormStateInterface $form_state){
        
        return $form['titulo'];
    }
    
    public function updateNombresAlumnos(array $form, FormStateInterface $form_state){
        
        return $form['caja'];
    }
}