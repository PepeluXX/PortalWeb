<?php
//Register User

    require_once 'DBOperations.php';
    $response = array();
    
    /*$username=$_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];*/
    ////////////////////////////////////////                            ///////////////////////////////              //////////////////////////////
    
	
    //Poner el servidor a la escucha de peticiones POST en la dirección https://www.portaldedesarrollo.com/TokenRegistration.php
	
    if($_SERVER['REQUEST_METHOD']=='POST'){
        
	//Si la petición POST recibida contiene los parámetros solicitados (token,dni,password)
		
        if(isset($_POST['token']) && isset ($_POST['dni']) && isset($_POST['password'])){
            
            //Para conectar y ejecutar operaciones en la base de datos
            $db=new DBOperations();
            
            //Ejecutar método para comprobar que el padre/madre está dado de alta en el portal web
            $result = $db->compruebaExistenciaPadre($_POST['dni'],$_POST['password']); 
            
            
            //Si la comprobación tiene éxito
            if($result==1){
                
                //Se inserta el token en la BBDD
                $result2=$db->insertaToken($_POST['dni'],$_POST['token']);
                
                //Si la inserción del token en la BBDD tiene éxito, se configuran los parámetros de éxito de respuesta a la petición
                if($result2==1){
		     //Configurar error como false, es decir, no hay error
                     $response['error']=false;
		     //Configurar el mensaje como OK
                     $response['message'] = "OK";
		     //Tomar los nombres de los hijos para enviarlos y que la aplicación móvil los use para crear tablas
                     $response['nombres_hijos']= $db->getNombresHijos($_POST['dni']);
		     //Tomar los nombres de los cursos para enviarlos y que la aplicación móvil los use para crear tablas
                     $response['cursos_hijos']=$db->getCursosHijos($_POST['dni']);
					 
                }//Si la comprobación de los datos no tiene éxito, se configuran los parámetros de respuesta a la petición como de sin éxito.
		 // Por todas las comprobaciones realizadas, rara o ninguna vez se llega a este mensaje de error.
		else{
		     //Configurar error es cierto
                     $response['error']=true;
		     //Configurar mensaje indicando que el token no se ha registrado
                     $response['message'] = "No se ha registrado el token";
                }
            }
	    //Si el padre/madre no está registrado en el portal web, configurar parámetros de respuesta a la petición como no exitosos.
	    //Este mensaje de error es más común de recibir en la aplicación ya que se recibe tanto si el usuario no está dado de alta en el 
	    //portal web como si se introducen datos erróneos en el formulario de la aplicación móvil
            else{
		//Ha habido un error
                $response['error']=true;
		//Configurar mensaje informando de lo ocurrido
                $response['message']= "El usuario no está dado de alta en el servicio.";
            }
            
           /* if ($result==1)
            {
              $response['error']=false;
              $response['message'] = "Token registrado oki doki";
            }elseif($result==2){
                $response['error']=true;
                $response['message']= "Algo se ha pinchao compae";
            }elseif($result==0){
                $response['error']=true;
                $response['message']= "El usuario ya existe. O creo qe es eso";
            }*/
        }
	//Si la petición POST no contiene los datos esperados
        else{
            $response['error'] = true;
            $response['message'] = 'Los campos requeridos no se encuentra';
        }
    }//Si el servidor está completamente fuera de servicio
	else{
        $response['error']=true;
        $response['message'] = 'El servidor no ha respondido...';
    }
//Codificar la respuesta en json
echo json_encode($response);
