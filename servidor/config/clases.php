<?php
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
        return $this->rol == "Administrador";
    }

    public static function iniciarSesion($email,$pass){

        if(session_status() == PHP_SESSION_NONE){
            session_start();
        }

        if(isset($_SESSION['usuario'])){
            session_unset();
            session_destroy();
        }

        $mensaje = self::login($email,$pass);

        if($mensaje === true){

            header("Location: " . BASE_URL . "/inicio");
            exit;

        }else{

            echo "<script>alert('$mensaje')</script>";

        }

    }

    private static function login($email, $pass){

        $con = conexion();

        $sql = "
        SELECT
            u.usu_id,
            u.usu_usuario,
            u.usu_contrasena,
            u.usu_nombre,
            u.usu_apellido,
            u.usu_email,
            u.usu_celular,
            u.usu_dni,
            u.usu_direccion,
            u.usu_fecha_alta,
            u.usu_estado,

            r.rol_nombre,

            l.localidad_nombre,
            p.provincia_nombre,
            pa.pais_nombre

        FROM usuarios u

        INNER JOIN roles r
            ON u.usu_rol = r.rol_id

        LEFT JOIN localidades l
            ON u.usu_localidad_id = l.localidad_id

        LEFT JOIN provincias p
            ON u.usu_provincia_id = p.provincia_id

        LEFT JOIN paises pa
            ON u.usu_pais_id = pa.pais_id

        WHERE u.usu_email = :email
        AND u.usu_estado = 1
        ";

        $stmt = $con->prepare($sql);

        $stmt->bindParam(
            ':email',
            $email,
            PDO::PARAM_STR
        );

        $stmt->execute();

        if($stmt->rowCount() != 1){
            return "Usuario no encontrado";
        }

        $datos = $stmt->fetch(PDO::FETCH_ASSOC);

        if(
            !password_verify(
                $pass,
                $datos['usu_contrasena']
            )
        ){
            return "Contraseña incorrecta";
        }

        if(session_status() == PHP_SESSION_NONE){
            session_start();
        }

        $_SESSION['usuario'] = new self(

            $datos['usu_id'],
            $datos['usu_nombre'],
            $datos['usu_apellido'],
            $datos['usu_email'],
            $datos['usu_celular'],
            $datos['usu_dni'],
            $datos['usu_usuario'],
            $datos['rol_nombre'],
            $datos['usu_direccion'],
            $datos['localidad_nombre']

        );

        return true;

    }

}
?>
