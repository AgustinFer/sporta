<?php
require_once __DIR__ . '/init.php';
include_once __DIR__ . '/functions.php';


abstract class Persona{
    
    protected $nombre,$apellido,$email,$celular,$dni;

    #region Getters
    public function getNombre(){
        return $this->nombre;
    }
    public function getApellido(){
        return $this->apellido;
    }
    public function getEmail(){
        return $this->email;
    }
    public function getCelular(){
        return $this->celular;
    }
    public function getDni(){
        return $this->dni;
    }
    #endregion
    #region Setters
    public function setNombre($nombre){
        $this->nombre = $nombre;
    }
    public function setApellido($apellido){
        $this->apellido = $apellido;
    }
    public function setCelular($celular){
        $this->celular = $celular;
    }
    public function setEmail($email){
        $this->email = $email;
    }
    public function setDni($dni){
        $this->dni = $dni;
    }
    #endregion

    }
class Cliente extends Persona{
    private $id;

    public function __construct($id,$nombre,$apellido,$email,$celular,$dni){
        $this->id=$id;
        $this->nombre=$nombre;
        $this->apellido=$apellido;
        $this->email=$email;
        $this->celular=$celular;
        $this->dni=$dni;
    }

}

class Usuario extends Persona{
    
    private $id, $usuario, $rol, $direccion, $localidad;

    public function __construct($id,$nombre,$apellido,$email,$celular,$dni,$usuario,$rol,$direccion,$localidad){
        $this->id = $id;
        $this->nombre = $nombre;
        $this->apellido = $apellido;
        $this->email = $email;
        $this->celular = $celular;
        $this->dni = $dni;
        $this->usuario = $usuario;
        $this->rol = $rol;
        $this->direccion = $direccion;
        $this->localidad = $localidad;
    }
    #region GETTERS
    public function getRol(){
        return $this->rol;
    }
    public function getUsuario(){
        return $this->usuario;
    }
    public function getDireccion(){
        return $this->direccion;
    }
    public function getLocalidad(){
        return $this->localidad;
    }
    #endregion
    
    public function isAdmin(){
        if($this->rol == "Administrador"){
            return true;
        }else{
            return false;
        }
    }

    public static function iniciarSesion($email,$pass){

        if(session_status() == PHP_SESSION_NONE){
            session_start();
        }

        if(isset($_SESSION['usuario'])){
            session_unset();
            session_destroy();
        }

        $mensaje = login($email,$pass);

        if($mensaje === true){
            header("Location: " . BASE_URL . "/inicio");
            exit;    
        }else{
            echo "<script>alert('$mensaje')</script>";
        }
    }

}
?>
