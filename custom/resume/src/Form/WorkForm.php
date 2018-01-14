<?php
/**
 * @file
 * Contains \Drupal\resume\Form\WorkForm.
 */
namespace Drupal\resume\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Html;


class workForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'resume_form';
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
      
   
    $form['nombre_usuario'] = array(
      '#type' => 'textfield',
      '#title' => t('<p><li>Inserte sus datos para recibir un email con los datos para cambiar su contraseña.</li></p><li>Nombre Usuario:</li>'),
      '#required' => TRUE,
      '#size' => 28,
      '#maxlength' => 9,
    );
    $form['email_usuario'] = array(
      '#type' => 'email',
      '#title' => t('<li>Dirección Email:</li>'),
      '#required' => TRUE,
        '#size' => 28,
        '#maxlength' => 60,
    );
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Enviar'),
      '#button_type' => 'primary',
    );
    return $form;
  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
   // drupal_set_message($this->t('@emp_name ,Your application is being submitted!', array('@emp_name' => $form_state->getValue('employee_name'))));
  
      $nombre_usuario = $form_state->getValue('nombre_usuario');
      $email_usuario = $form_state->getValue('email_usuario');
      
        $query = \Drupal::database()->select('users_field_data', 'ufd');

                 $query->fields('ufd', ['name']); 
                 $query->condition('mail',$email_usuario);
                 $result = $query->execute();
                 $check_nombre = $result->fetchField();
                // drupal_set_message("nombre usuario = ".$nombre_usuario." check_nombre = ".$check_nombre);
        $query2 = \Drupal::database()->select('users_field_data', 'ufd');

                 $query2->fields('ufd', ['mail']); 
                 $query2->condition('name',$nombre_usuario);
                 $result2 = $query2->execute();
                 $check_email = $result2->fetchField();         
                 //drupal_set_message("email usuario = ".$email_usuario." check_email = ".$check_email);
                
                 
                if($nombre_usuario==$check_nombre && $email_usuario == $check_email){
                    
                
                    //generar contraseña y cambiarla en Drupal y en a_activacion,
                   $password= $this->otroEnviaMail($email_usuario,$nombre_usuario);
                    
                    //si se dieron dos padres de alta, se le notifica también al que no ha solicitado el cambio de
                    //contraseña ya que también cambia para él/ella
                    
                    //recibe el nif del usuario que está gestionando el envío de nueva contraseña,
                    //para buscar al otro padre en a_hijos
                    $this->otroEnviaMail2($nombre_usuario,$password) ;
                            
                    
                    
                    
                }
                else{
                    drupal_set_message('Los datos no son correctos. Reviselos e intentelo de nuevo.','error');
                }
  }
  
  
  
            function otroEnviaMail($email_usuario,$nombre_usuario){
                
               /* $to = '	jlfp0002@red.ujaen.es';
                $subject = 'Cambio de contraseña';
                $message = 'Hola usuario '.$nombre_usuario.'. Has solicitado un cambio de contraseña. Tu nueva contraseña'; 
                $from = 'jluisfpp@gmail.com';
 
                // Sending email
                if(mail($to, $subject, $message)){
                        drupal_set_message('Your mail has been sent successfully.');
                }else{
                        drupal_set_message('Unable to send email. Please try again.');
                }
      */
                
            $to = $email_usuario;
            $subject = 'Cambio de Contraseña. Portal de Avisos y Mensajes.';
            $from = 'Solicitud cambio de contraseña';
 
            // To send HTML mail, the Content-type header must be set
            $headers  = 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
 
            // Create email headers
            $headers .= 'From: '.$from."\r\n".
            'Reply-To: '.$from."\r\n" .
            'X-Mailer: PHP/' . phpversion();
            
            //Generamos y cambiamos el password
            $password= $this->generaPass();
            $this->gestionaPassword($nombre_usuario,$password);
            
            // Compose a simple HTML email message
            $message = '<html><body>';
            $message .= '<p style="color:#595D5F;font-size:18px;">Hola usuario '.$nombre_usuario.', </p>';
            $message .= '<p style="color:#000000;font-size:15px;">Recientemente has solicitado un cambio de contraseña.</p>';
            $message .= '<p style="color:#000000;font-size:15px;">Recuerda que esta nueva contraseña es válida para el portal web y para la aplicación móvil,</p>';
            $message .= '<p style="color:#000000;font-size:15px;">y que además invalida cualquier otra constraseña previa. Guárdela en un lugar seguro.</p>';
            $message .= '<p style="color:#000000;font-size:15px;">Si lo prefieres, puedes cambiar esta contraseña en el portal web una vez te hayas identificado, yendo a la</p>';
            $message .= '<p style="color:#000000;font-size:15px;">sección "Reestablecer Contraseña" en el "Menú de cuenta de usuario".</p>';
            $message .= '<p style="color:#000000;font-size:15px;">Su nueva contraseña es: </p><p style="color:#1885BB;font-size:18px;">'.$password.'</p>';
            $message .= '</body></html>';
 
            // Sending email
            if(mail($to, $subject, $message, $headers)){
                    drupal_set_message('¡Los datos de usuario son correctos! Se ha enviado un correo a '.$email_usuario. "\nRevise su carpeta de Spam si no lo recibe en la bandeja principal.");
            } else{
                  drupal_set_message('No se ha podido enviar el email.');
            } 
            
            return $password;
  }
    
  
    //esta función hace lo mismo que enviaOtroMail() pero esta no incluye la ejecucion de gestionaPassword, ya que si la incluyese,
    //al cambiarse la contraseña de nuevo, sólo sería valida la segunda, y la de primer email quedaría invalidada
  
   function otroEnviaMail2($nombre_usuario,$password){
                
          //SELECT DISTINCT nif_padre FROM a_hijos WHERE nif_padre = $dni OR nif_madre = $dni
         
          $query =\Drupal::database()->select('a_hijos','ah');
                $query->distinct();
                $query->fields('ah',['nif_padre']);
                $condition = db_or()
                  ->condition('nif_padre',$nombre_usuario)
                  ->condition('nif_madre',$nombre_usuario);
                
                $query->condition($condition);
                
                $result=$query->execute();
                
                $nif_padre=$result->fetchField(); 
            
            //si coinciden, ya se ha enviado el email al padre, por tanto se lo enviamos a la madre. Si no coinciden, el email
            // se le envió a la madre, por lo tanto hay que enviárselo al padre
                
            if($nif_padre!=$nombre_usuario){//se ha introducido el de la madre, 
                
                $query2 = \Drupal::database()->select('users_field_data', 'ufd');

                    $query2->fields('ufd', ['mail']); 
                    $query2->condition('name',$nif_padre);
                    $result2 = $query2->execute();
                    $mail_destinatario = $result2->fetchField(); 
                    
                    $nombre = $nif_padre;
                
                
                    //$nif_destinatario = $nif_padre;
          
                }else{ //se ha introducido el del padre, por tanto hay que buscar el nif de la madre y su email
                    //$nif_destinatario = $nombre_usuario;
                     $query =\Drupal::database()->select('a_hijos','ah');
                     $query->distinct();
                     $query->fields('ah',['nif_madre']);
                     $condition = db_or()
                             ->condition('nif_padre',$nif_padre);
                
                     $query->condition($condition);
                
                     $result=$query->execute();
                
                     $nif_madre=$result->fetchField();
                    
                     //si se dió una madre de alta (caso contrario a que sólo un padre se dió de alta)
                     if($nif_madre!=NULL){
                             //buscamos su email
                            $query2 = \Drupal::database()->select('users_field_data', 'ufd');

                            $query2->fields('ufd', ['mail']); 
                            $query2->condition('name',$nif_madre);
                            $result2 = $query2->execute();
                            $mail_destinatario = $result2->fetchField(); 
                    
                            $nombre = $nif_madre;
                         }
                 }   
                
                 
                 $to = $mail_destinatario;
                 $subject = 'Cambio de Contraseña. Portal de Avisos y Mensajes.';
                 $from = 'Solicitud cambio de contraseña';
 
                 // To send HTML mail, the Content-type header must be set
                   $headers  = 'MIME-Version: 1.0' . "\r\n";
                  $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
 
                 // Create email headers
                 $headers .= 'From: '.$from."\r\n".
                 'Reply-To: '.$from."\r\n" .
                 'X-Mailer: PHP/' . phpversion();
            
            
            
            // Compose a simple HTML email message
            $message = '<html><body>';
            $message .= '<p style="color:#595D5F;font-size:18px;">Hola usuario '.$nombre.', </p>';
            $message .= '<p style="color:#000000;font-size:15px;">Recientemente has solicitado un cambio de contraseña.</p>';
            $message .= '<p style="color:#000000;font-size:15px;">Recuerda que esta nueva contraseña es válida para el portal web y para la aplicación móvil,</p>';
            $message .= '<p style="color:#000000;font-size:15px;">y que además invalida cualquier otra constraseña previa. Guárdela en un lugar seguro.</p>';
            $message .= '<p style="color:#000000;font-size:15px;">Si lo prefieres, puedes cambiar esta contraseña en el portal web una vez te hayas identificado, yendo a la</p>';
            $message .= '<p style="color:#000000;font-size:15px;">sección "Reestablecer Contraseña" en el "Menú de cuenta de usuario".</p>';
            $message .= '<p style="color:#000000;font-size:15px;">Su nueva contraseña es: </p><p style="color:#1885BB;font-size:18px;">'.$password.'</p>';
            $message .= '</body></html>';
 
            // Sending email
            if(mail($to, $subject, $message, $headers)){
                    drupal_set_message('¡Los datos de usuario son correctos! Se ha enviado un correo a '.$mail_destinatario. "\nRevise su carpeta de Spam si no lo recibe en la bandeja principal.");
            } else{
                  drupal_set_message('No se ha enviado un segundo mail, sólo un padre está dado de alta en el servicio.');
            }                                                                             
  }

  
  
  
  
  
  
  
  
  public function gestionaPassword($nombre_usuario,$pass_word){
      
      $password = $pass_word;
      
      //SELECT DISTINCT nif_padre FROM a_hijos WHERE nif_padre = $dni OR nif_madre = $dni
         
          $query =\Drupal::database()->select('a_hijos','ah');
                $query->distinct();
                $query->fields('ah',['nif_padre']);
                $condition = db_or()
                  ->condition('nif_padre',$nombre_usuario)
                  ->condition('nif_madre',$nombre_usuario);
                
                $query->condition($condition);
                
                $result=$query->execute();
                
                $nif_padre=$result->fetchField(); 
                
                
                
                //si es el padre2 quien ha introducido sus datos, entonces el resultado devuelto es el nif del padre1, si 
                //hubiese sido el padre1, el resultado sería su mismo nif por lo que se hace otra consulta para recoger el del padre2
                
                if($nif_padre!=$nombre_usuario){
                    $nif1 = $nombre_usuario;
                    $nif2 = $nif_padre;
                    drupal_set_message("if//nif_padre= ".$nif2);
                    drupal_set_message("if//nif_madre= ".$nif1);
                }else{
                    
                     $query =\Drupal::database()->select('a_hijos','ah');
                     $query->distinct();
                     $query->fields('ah',['nif_madre']);
                
                     $query->condition('nif_padre',$nombre_usuario);
                
                     $result=$query->execute();
                
                     $nif_madre=$result->fetchField(); 
                
                     $nif1=$nombre_usuario;
                     $nif2=$nif_madre;
                     drupal_set_message("else// nif_padre= ".$nif1);
                     drupal_set_message("else// nif_madre= ".$nif2);
                }
                
                //Recogemos la identidad del padre
      
                $query =\Drupal::database()->select('users_field_data','ufd');
                
                $query->fields('ufd',['uid']);
                
                $query->condition('name',$nif1);
              
                
                $result=$query->execute();
                
                $id_padre=$result->fetchField(); 
                drupal_set_message("id_padre ".$id_padre);
                //y cambiamos la contraseña
                //en Drupal
                $user = \Drupal\user\Entity\User::load($id_padre);
                $user->setPassword($password);
                $user->save();
                
                //y en a_activacion
                //Encriptado del password para su almacenamiento en la BBDD, concretamente en la tabla 'a_activacion' (nif,password)
                $password_encryptado = $this->better_crypt($password);
                \Drupal::database()->update('a_activacion')
                    ->condition('nif' , $nif1)
                    ->fields([
                        'password' => $password_encryptado 
                        ])
                        ->execute();
                
                
                //Si $nif2 tiene un valor, entonces es que se insertaron dos padres en la activación, por tanto se cambia también su pass para que sea
                //el mismo para los dos.
                
                if($nif2!=NULL){
                  drupal_set_message("estoy entrando??");  
                    ///Recogemos la identidad de la madre
                
                     $query =\Drupal::database()->select('users_field_data','ufd');
                
                     $query->fields('ufd',['uid']);
                
                     $query->condition('name',$nif2);
              
                
                     $result=$query->execute();
                
                    $id_madre=$result->fetchField();
                    drupal_set_message("id_madre= ".$id_madre);
                //
                
                     //y cambiamos la contraseña
                     //en Drupal
                     $user = \Drupal\user\Entity\User::load($id_madre);
                     $user->setPassword($password);
                     $user->save();
                
                    //y en a_activacion
                    //Encriptado del password para su almacenamiento en la BBDD, concretamente en la tabla 'a_activacion' (nif,password)
                    //$password_encryptado = $this->better_crypt($password);
                     \Drupal::database()->update('a_activacion')
                             ->condition('nif' , $nif2)
                             ->fields([
                                   'password' => $password_encryptado 
                              ])
                             ->execute();
              }  
                
  }
  
  
  /*
     * Genera un password de 10 dígitos de manera aleatoria
     * 
     * @return String $salt
     * conteniendo el password generado
     * 
     */ 
  public function generaPass(){
         $salt = "";
         $salt_chars = array_merge(range('A','Z'), range('a','z'), range(0,9));
         for($i=0; $i < 10; $i++) {
         $salt .= $salt_chars[array_rand($salt_chars)];
         }
         return $salt;
    }
    
     /*
     * Realiza el encriptado del password previamente generado
     * 
     * @param String $password
     * que contiene el password a encriptar
     * @param int $rounds
     * que indica cuantos rounds ejecutará el algoritmo de encriptación
     *
     * @return String
     * conteniendo el password encryptado
     * 
     */
    
   function better_crypt($password, $rounds = 7)
  {
    $salt = "";
    $salt_chars = array_merge(range('A','Z'), range('a','z'), range(0,9));
    for($i=0; $i < 22; $i++) {
      $salt .= $salt_chars[array_rand($salt_chars)];
    }
    
    return crypt($password, sprintf('$2y$%02d$', $rounds) . $salt);
  }  
  
  
  
  
  
  
            function enviaMail($email_usuario) {
                
            $mailManager = \Drupal::service('plugin.manager.mail');
             $module = 'resume';
             $key = 'envio_mail_pass'; // Replace with Your key
             $to = 'jluisfpp@gmail.com';//$email_usuario;
             $params['message'] = '<p>Hola amigo</p>';
             $params['title'] = 'Etiqueta mensaje';
             
             $langcode = 'es';
             $send = true;

            $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
            
            if ($result['result'] != true) {
              $message = t('There was a problem sending your email notification to @email.', array('@email' => $to));
              drupal_set_message($message, 'error');
             \Drupal::logger('mail-log')->error($message);
             return;
            }

            $message = t('An email notification has been sent to @email ', array('@email' => $to));
            drupal_set_message('message= '.$message);
            \Drupal::logger('mail-log')->notice($message);
            }
      
  /**
 * Implements hook_mail().
 */
function resume_mail($key, &$message, $params) {
  $options = array(
    'langcode' => $message['langcode'],
  );
  switch ($key) {
    case 'envio_mail_pass':
      $message['from'] = \Drupal::config('system.site')->get('mail');
      $message['subject'] = t('Your mail subject Here: @title', array('@title' => $params['title']), $options);
      $message['body'][] = Html::escape($params['message']);
      break;
  }
}
  
  
}//end of class
