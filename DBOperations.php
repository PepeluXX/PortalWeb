<?php

//DBOperations

class DBOperations{
    
    private $con;
    
    
    function __construct(){
        
        require_once 'DBConexion.php';
        require_once 'PruebaBlowfish.php';
        
        $db = new DBConexion();
        
        
        
        $this->con = $db->connect();
        
        
    }
    
    function insertaToken($dni,$token){
               
             $stmt = $this->con->prepare("UPDATE `a_padres` SET `token` = ?  WHERE `a_padres`.`nif` = ?");
             
             $stmt->bind_param("ss",$token,$dni);
             
             $stmt->execute();  
             
             
             $stmt_aux=$this->con->prepare("SELECT token FROM a_padres WHERE nif= ? ");
             
             $stmt_aux->bind_param("s",$dni);
             
             $stmt_aux->execute();
             
             $valor = $stmt_aux->get_result()->fetch_row();
             
             if($valor[0] == $token){
                 //echo "el token se ha insertado correctamente!!!\n";
                 return 1;
             }
             else{
                // echo "El token NO se ha registrado\n";
                 return 2;
             }
            
    }
    
    //comprueba que el padre está dado de alta a la espera de activar el servicio (tabla: a_activacion)
    //y comprueba también los hash de las contraseñas
    
    public function compruebaExistenciaPadre($dni,$password){
        
        //echo "hola desde comprueba existencia padre!\n";
        $bf = new PruebaBlowfish();
        //Comprobamos que el padre está en la base de datos (dado de alta en el servicio, pendiente de registro
        //)
         $stmt = $this->con->prepare("SELECT nif FROM a_padres WHERE nif= ? ");
         $stmt->bind_param("s",$dni);
         $stmt->execute();
         $valorcito = $stmt->get_result()->fetch_row();
         $valorcito = $valorcito[0];
         
         //echo "valorcito =".$valorcito."\n";
         
         //Comprobamos que los hash de las contraseñas coinciden
         $stmt = $this->con->prepare("SELECT password FROM a_activacion WHERE nif= ? ");
         $stmt->bind_param("s",$dni);
         $stmt->execute();
         $hashed_password= $stmt->get_result()->fetch_row(); //recogemos el hash del pass de la BD
         $hashed_password= $hashed_password[0];
         //echo "hashed password1 = ".$hashed_password."\n";
       
         $password_inserted = $bf->better_crypt($password); // hasheamos el password proporcionado desde android
         
         //echo "hash password inserted = ".$password_inserted."\n";
         
         $chequeo = $bf->checkHash($password,$hashed_password); //devuelve uno si todo correcto
         
        if(!empty($valorcito) && $chequeo == 1){
             //echo "el padre existe\n";
             return 1;
         }
         else{
             //echo "el padre no existe\n";
             return 0;
         }    
    }
    
    public function getNombresHijos($dni){
        
         $stmt = $this->con->prepare("SELECT nombre FROM a_hijos WHERE nif_padre = ? OR nif_madre = ?");
         $stmt->bind_param("ss",$dni,$dni);
         $stmt->execute();
         $array_nombres=[];
         $nombres_hijos="";
         
         
             
         foreach($stmt->get_result() as $row){
             $array_nombres[]=$row['nombre'];
             $nombres_hijos = $nombres_hijos.",".$row['nombre'];
             
         }
         //print_r($array_nombres);
         
        
         $nombres_hijos= substr($nombres_hijos,1); //para eliminar la ',' inicial
         
         
         return $nombres_hijos;
    }
    
    public function getCursosHijos($dni){
        
         //distinct por si hay varios hijos en el mismo curso,
        //lo hago así porque sólo me interesan los cursos para la creación de tablas para cada curso
        
         $stmt = $this->con->prepare("SELECT DISTINCT curso FROM a_hijos WHERE nif_padre = ? OR nif_madre = ?");
         $stmt->bind_param("ss",$dni,$dni);
         $stmt->execute();
         $array_cursos=[];
         $cursos_hijos="";
         
             
         foreach($stmt->get_result() as $row){
             $array_cursos[]=$row['curso'];
             $cursos_hijos = $cursos_hijos.",".$row['curso'];
         }
         //print_r($array_nombres);
         
         $cursos_hijos= substr($cursos_hijos,1);
         
         return $cursos_hijos;
    }
    
    
    public function getUserByUsername($username){
        $stmt = $this->con->prepare("SELECT * FROM users WHERE username =?");
        $stmt->bind_param("s",$username);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function userLogin($username, $pass){
        $password=md5($pass);
        $stmt = $this->con->prepare("SELECT id FROM users WHERE username =? AND password =?");
        $stmt->bind_param("ss",$username,$password);
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows > 0;
    }
    
    
    
    private function isUserExists($username,$email){
        $stmt = $this->con->prepare("SELECT id FROM users WHERE username = ? OR email =? " );
        $stmt->bind_param("ss",$username,$email);
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows > 0;
    }
}
