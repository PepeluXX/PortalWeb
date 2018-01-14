<?php

class DBConexion{
    
    private $con;
    
    function __construct() {
        
    }
    function connect(){
        include_once 'Constants.php';
    
        $this->con = new mysqli(DB_HOST,DB_USER,DB_PASSWORD,DB_NAME);
        
        if(mysqli_connect_errno()){
            echo "Fallo en la conexion con la base de datos".mysqli_connect_err();
        }
        return $this->con;
    }
}

