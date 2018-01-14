<?php
/*
*@file
*Contains \Drupal\eliminar_usuarios\Form\EliminarUsuariosForm.
*/

namespace Drupal\eliminar_usuarios\Form;

use Drupal\Core\Form\Formbase;
use Drupal\Core\Form\FormStateInterface;


class EliminarUsuariosForm extends FormBase{
    
     public function getFormId(){
        
        return 'eliminar_usuarios_form';
    } 

     public function buildForm(array $form, FormStateInterface $form_state){
         
         $form['select_dni_padre']= [
        '#title'=>'DNI Padre/Madre',
        '#type'=>'select',
        '#empty_option'=>'-Seleccione',
        '#required'=>TRUE,
        '#options'=>$this->getDniPadresActivados(),
         ];
           
        $form['actions']['submit'] = [
             '#type' => 'submit',
             '#value' => $this->t('Eliminar Usuarios'),
         ];
                
        return $form;  
           
     }
     
      /**
   * valida los elementos del formulario que se consideren necesarios
   *
   * @param array $form
   *   Estructura por defecto del formulario.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Objeto conteniendo el estado actual del formulario.
   */
    public function validateForm(array &$form, FormStateInterface $form_state){
       
    }
    
  /* Llama al método que Realiza las acciones necesarias para la activación del servicio cuando se pulsa sobre el botón 'Activar Servicio' (submit)
   * @param array $form
   *  Estructura por defecto del formulario.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Objeto conteniendo el estado actual del formulario.
   */  
    
    public function submitForm(array &$form, FormStateInterface $form_state){
        $this->eliminaUsuarios($form,  $form_state);         
    }
    
    
    public function eliminaUsuarios($form,  $form_state){
        
        $array_nifs = $this->getDniPadresActivados();
        $nif_seleccionado = $form_state->getValue(['select_dni_padre']);
        $nif_a_borrar = $array_nifs[$nif_seleccionado];
        drupal_set_message("nif_a_borrar-->".$nif_a_borrar);
        
        //Como sólo se selecciona el dni del padre o de la madre, buscamos el dni del padre o madre
         //que no se ha elegido
         //SELECT DISTINCT nif_padre FROM a_hijos WHERE nif_padre = $dni OR nif_madre = $dni
         
          $query =\Drupal::database()->select('a_hijos','ah');
                $query->distinct();
                $query->fields('ah',['nif_padre']);
                $condition = db_or()
                  ->condition('nif_padre',$nif_a_borrar)
                  ->condition('nif_madre',$nif_a_borrar);
                
                $query->condition($condition);
                
                $result=$query->execute();
                
                $nif_padre=$result->fetchField(); 
                
                drupal_set_message("nif padre-->".$nif_padre);
                
          $query =\Drupal::database()->select('a_hijos','ah');
                $query->distinct();
                $query->fields('ah',['nif_madre']);
                $condition = db_or()
                  ->condition('nif_padre',$nif_a_borrar)
                  ->condition('nif_madre',$nif_a_borrar);
                
                $query->condition($condition);
                
                $result=$query->execute();
                
                $nif_madre=$result->fetchField(); 
        
                
        $nifs_a_borrar=[];
        $nifs_a_borrar[]= $nif_padre;
        $nifs_a_borrar[]=$nif_madre;
        drupal_set_message("nifs a borrar = ".$nifs_a_borrar[0]." ".$nifs_a_borrar[1]);
        
        //borramos usuarios de Drupal, de la tabla a_padres,a_activacion y a los hijos correspondientes
       
         //De Drupal
         //SELECT uid FROM users_fiel_data WHERE name IN ($nifs_a_borrar)
        
            $query =\Drupal::database()->select('users_field_data','ufd');
                 
                $query->fields('ufd',['uid']); 
                $query->condition('name',$nifs_a_borrar,'IN');
                   
                $result=$query->execute();
                
                $ids_a_borrar=$result->fetchCol(); 
                
                
            
        foreach($ids_a_borrar as $key=>$value){
            user_cancel(array(), $value, 'user_cancel_delete');
        
        }
        
        //De la tabla a_padres
        
        $query = \Drupal::database()->delete('a_padres')
                ->condition('nif',$nifs_a_borrar,'IN')
                ->execute();
            
       //de la tabla a_activacion
         $query = \Drupal::database()->delete('a_activacion')
                ->condition('nif',$nifs_a_borrar,'IN')
                ->execute();
            
       //de la tabla a_hijos
          $query = \Drupal::database()->delete('a_hijos')
                  ->condition('nif_padre',$nif_padre)
                  ->execute();
          $query = \Drupal::database()->delete('a_hijos')
                  ->condition('nif_madre',$nif_madre)
                  ->execute();
    }
     
    
     
     
       /*
     * Devuelve los nombres de los cursos, tal y cómo se muestran el en dropdown.
     * 
     * @return array $cursos
     * un array[$key,$value] que asocia enteros con String y que se usará para llenar la select list y conocer lo correspodiente a las posiciones 
     * seleccionadas
     */
        protected function getCursos(){
        $cursos=array(
    
                '1' => $this->t('Primero Infantil'),                
                '2' => $this->t('Segundo Infantil'),
                '3' => $this->t('Primero Primaria'),                
                '4' => $this->t('Segundo Primaria'),
                '5' => $this->t('Tercero Primaria'),                
                '6' => $this->t('Cuarto Primaria'),
                '7' => $this->t('Quinto Primaria'),                
                '8' => $this->t('Sexto Primaria'),
                   
                );
        return $cursos;
    }
     
     
     
     
     
     
     
     
     
     
      /*
     * Devuelve los DNI´s de todos los padres que están dados de alta en el servicio de mensajería.
     * @return array $lista_nifs
     * que contiene la lista de los dni´s de todos aquellos padres que dados de alta en el portal y que aún no están dados de alta en el servicio
     * de mensajería
     * 
     */
    
       public function getDniPadresActivados(){ 
        
        
        try{
        //selecciona las ids de usuario (entity_id) de todos aquellos usuarios con el rol 'padre_madre_tutor'
        //SELECT nif FROM a_activacion'
            
        $query =\Drupal::database()->select('a_activacion','aa');
         
                $query->fields('aa',['nif']);  
               
                
                $result=$query->execute();
                
                $lista_nifs=$result->fetchCol();  
              
        
             }catch(SQLExcet $e){
                 
            drupal_set_message($this->t("Se ha producido un error en el modulo Añadir hijos.".$e));
        }   
        
        return $lista_nifs;  
    }
    
    
}