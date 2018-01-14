<?php
//Register User

    require_once 'DBOperations.php';
    $response = array();
    
    /*$username=$_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];*/
    ////////////////////////////////////////                            ///////////////////////////////              //////////////////////////////
    
    if($_SERVER['REQUEST_METHOD']=='POST'){
        
        if(isset($_POST['token']) && isset ($_POST['dni']) && isset($_POST['password'])){
            
            
            $db=new DBOperations();
            
            
            $result = $db->compruebaExistenciaPadre($_POST['dni'],$_POST['password']); //comprobar contraseña
            
            
            
            if($result==1){
                
                
                $result2=$db->insertaToken($_POST['dni'],$_POST['token']);
                
                
                if($result2==1){
                     $response['error']=false;
                     $response['message'] = "OK";
                     $response['nombres_hijos']= $db->getNombresHijos($_POST['dni']);
                     $response['cursos_hijos']=$db->getCursosHijos($_POST['dni']);
                }else{
                     $response['error']=true;
                     $response['message'] = "No se ha registrado el token";
                }
            }
            else{
                $response['error']=true;
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
        else{
            $response['error'] = true;
            $response['message'] = 'Los campos requeridos no se encuentra';
        }
    }else{
        $response['error']=true;
        $response['message'] = 'El servidor no ha respondido...';
    }

echo json_encode($response);
