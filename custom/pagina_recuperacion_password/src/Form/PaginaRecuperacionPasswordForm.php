<?php
/*
*@file
*Contains \Drupal\pagina_recuperacion_password\Form\PaginaRecuperacionPasswordForm.
*/

namespace Drupal\pagina_recuperacion_password\Form;

use Drupal\Core\Form\Formbase;
use Drupal\Core\Form\FormStateInterface;


class PaginaRecuperacionPasswordForm extends FormBase{
   //me he pasado un poco con el nombre 
    
    
     public function getFormId(){
        
        return 'prueba_borrado_form';
    } 
    
    public function buildForm(array $form, FormStateInterface $form_state){
        
        $form['contrasena_actual']= [
        '#title'=>'Contraseña Actual',
        '#type'=>'password',
        '#size' => 30,
        '#maxlength' => 512,
        '#required'=>TRUE,
    ];
        
        
        $form['nueva_contrasena']= [
        '#title'=>'Inserte su Nueva Contraseña',
        '#type'=>'password_confirm',
        '#empty_option'=>'-Seleccione',
        '#required'=>TRUE,
        
    ];
        
      
      
          $form['actions']['submit'] = [
                 '#type' => 'submit',
                 '#value' => $this->t('Cambiar Contraseña'),
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
        //$this->pruebaABorrar($form,  $form_state);   
       
        
        //se recoge el id del padre o madre que va a cambiar la contraseña
        $uid = \Drupal::currentUser()->id();
        
        drupal_set_message('user id = '.$uid); 
       
        $contrasena_actual = $form_state->getValue('contrasena_actual');
        
        //se recoge el nombre del padre o madre que va a cambiar la contraseña
        $query = \Drupal::database()->select('users_field_data', 'ufd');

                 $query->fields('ufd', ['name']); 
                 $query->condition('uid',$uid);

                 $result = $query->execute();

                 $nif = $result->fetchField();
                 drupal_set_message('nombre = '.$nif);
                 
       //y se recoge el hash del password del padre o madre
                 
        $query = \Drupal::database()->select('a_activacion', 'aa');

                 $query->fields('aa', ['password']); 
                 $query->condition('nif',$nif);

                 $result = $query->execute();
                 
                 $hashed_password = $result->fetchField();
                
                 
        // comprobamos si la contraseña actual y la almacenada son las mismas        
         
         $chequeo = $this->checkHash($contrasena_actual,$hashed_password);
                 
         drupal_set_message('chequeo = '.$chequeo);       
         
         //si el hash de la contraseña insertada y la guardada coinciden
         if($chequeo==1){
             drupal_set_message("Procedemos.");
             
             //comprobamos se se activaron padre y madre
                $query =\Drupal::database()->select('a_hijos','ah');
                $query->distinct();
                $query->fields('ah',['nif_padre']);
                $condition = db_or()
                  ->condition('nif_padre',$nif)
                  ->condition('nif_madre',$nif);
                
                $query->condition($condition);
                
                $result=$query->execute();
                
                $nif_padre=$result->fetchField(); 
                
                 //si es el padre2 quien ha introducido sus datos, entonces el resultado devuelto es el nif del padre1, si 
                //hubiese sido el padre1, el resultado sería su mismo nif por lo que se hace otra consulta para recoger el del padre2
                
                if($nif_padre!=$nif){
                    $nif1 = $nif;
                    $nif2 = $nif_padre;
                }else{
                    
                     $query =\Drupal::database()->select('a_hijos','ah');
                     $query->distinct();
                     $query->fields('ah',['nif_madre']);
                
                     $query->condition('nif_padre',$nif_padre);
                
                     $result=$query->execute();
                
                     $nif_madre=$result->fetchField(); 
                
                     $nif1=$nif;
                     $nif2=$nif_madre;
                    
                }
                
                //recogemos uid
                //Recogemos la identidad del padre1
      
                $query =\Drupal::database()->select('users_field_data','ufd');
                
                $query->fields('ufd',['uid']);
                
                $query->condition('name',$nif1);
              
                
                $result=$query->execute();
                
                $id_padre=$result->fetchField(); 
                
                //y cambiamos la contraseña
                //en Drupal
                $nueva_contrasena = $form_state->getValue('nueva_contrasena');
                
                $user = \Drupal\user\Entity\User::load($id_padre);
                $user->setPassword($nueva_contrasena);
                $user->save();
                
                //y en a_activacion
                //Encriptado del password para su almacenamiento en la BBDD, concretamente en la tabla 'a_activacion' (nif,password)
                $password_encryptado = $this->better_crypt($nueva_contrasena);
                \Drupal::database()->update('a_activacion')
                    ->condition('nif' , $nif1)
                    ->fields([
                        'password' => $password_encryptado 
                        ])
                        ->execute();
                
                
                //Si $nif2 tiene un valor, entonces es que se insertaron dos padres en la activación, por tanto se cambia también su pass para que sea
                //el mismo para los dos.
                
                if($nif2!=NULL){
                    
                    ///Recogemos la identidad de la madre
                
                     $query =\Drupal::database()->select('users_field_data','ufd');
                
                     $query->fields('ufd',['uid']);
                
                     $query->condition('name',$nif2);
              
                
                     $result=$query->execute();
                
                    $id_madre=$result->fetchField();
                
                //
                
                     //y cambiamos la contraseña
                     //en Drupal
                     $user = \Drupal\user\Entity\User::load($id_madre);
                     $user->setPassword($nueva_contrasena);
                     $user->save();
                
                    //y en a_activacion
                    //Encriptado del password para su almacenamiento en la BBDD, concretamente en la tabla 'a_activacion' (nif,password)
                    //$password_encryptado = $this->better_crypt($nueva_contrasena);
                     \Drupal::database()->update('a_activacion')
                             ->condition('nif' , $nif2)
                             ->fields([
                                   'password' => $password_encryptado 
                              ])
                             ->execute();
              }  
            
              drupal_set_message("Se ha modificado la contraseña. Recuerde que esta es su nueva contraseña sirve tanto");
              drupal_set_message("para acceder al portal como para activarse en la aplicación del dispositivo móvil.");
              drupal_set_message("Recuerde conservar sus contraseñas de manera segura.");
         }else{
             drupal_set_message("La contraseña actual no es correcta.",'warning');
         }        
                 
        
        
        
        
    
        
        
    }
    
    
     function checkHash ($password_inserted,$password_hash){
  
  if(crypt($password_inserted, $password_hash) == $password_hash) {
      return 1;
    
  }else{
      return 0;     
}

  }
    

    
    
    
    function better_crypt($password, $rounds = 7){
  
        $salt = "";
        $salt_chars = array_merge(range('A','Z'), range('a','z'), range(0,9));
        for($i=0; $i < 22; $i++) {
          $salt .= $salt_chars[array_rand($salt_chars)];
        }
    
    return crypt($password, sprintf('$2y$%02d$', $rounds) . $salt);
  } 
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    public function pruebaABorrar($form,  $form_state){
        
        $array_nifs = $this->getDniPadresActivados();
        $nif_seleccionado = $form_state->getValue(['select_dni_padre']);
        $nif_a_borrar = $array_nifs[$nif_seleccionado];
        drupal_set_message("usuario a borrar = ".$nif_a_borrar); 
        //SELECT uid FROM users_fiel_data WHERE name IN ($nifs_a_borrar)
        
            $query =\Drupal::database()->select('users_field_data','ufd');
                 
                $query->fields('ufd',['uid']); 
                $query->condition('name',$nif_a_borrar);
                   
                $result=$query->execute();
                
                $id_a_borrar=$result->fetchField(); 
                drupal_set_message("id a borrar = ".$id_a_borrar);
                user_cancel(array(), $id_a_borrar, 'user_cancel_delete');
               /* $user = \Drupal\user\Entity\User::load($id_a_borrar);
                $user->delete($id_a_borrar);*/
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



