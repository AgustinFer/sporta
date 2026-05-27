<?php

require_once __DIR__ . '/conexion.php';

function login($email, $pass){

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

    /* ========================= */
    /* 🔐 PASSWORD */
    /* ========================= */

    if(
        !password_verify(
            $pass,
            $datos['usu_contrasena']
        )
    ){

        return "Contraseña incorrecta";

    }

    /* ========================= */
    /* 🧠 SESSION */
    /* ========================= */

    if(session_status() == PHP_SESSION_NONE){

        session_start();

    }

    $_SESSION['usuario'] = new Usuario(

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

?>