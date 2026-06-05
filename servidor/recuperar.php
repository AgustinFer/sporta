<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Acceso denegado');
}

$email = trim($_POST['email'] ?? '');

if (empty($email)) {
    die('Debe ingresar un email');
}

try {

    $con = conexion();

    /*
    |--------------------------------------------------------------------------
    | BUSCAR USUARIO
    |--------------------------------------------------------------------------
    */

    $stmt = $con->prepare("
        SELECT
            usu_id,
            usu_usuario,
            usu_nombre,
            usu_email
        FROM usuarios
        WHERE usu_email = :email
        LIMIT 1
    ");

    $stmt->execute([
        ':email' => $email
    ]);

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    /*
    |--------------------------------------------------------------------------
    | RESPUESTA GENÉRICA POR SEGURIDAD
    |--------------------------------------------------------------------------
    */

    if (!$usuario) {
        echo 'Si el correo existe, se enviará una nueva contraseña.';
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | GENERAR CONTRASEÑA TEMPORAL
    |--------------------------------------------------------------------------
    */

    $nuevaPassword = bin2hex(random_bytes(4));

    $hash = password_hash(
        $nuevaPassword,
        PASSWORD_DEFAULT
    );

    /*
    |--------------------------------------------------------------------------
    | ACTUALIZAR HASH
    |--------------------------------------------------------------------------
    */

    $stmt = $con->prepare("
        UPDATE usuarios
        SET usu_contrasena = :hash
        WHERE usu_id = :id
    ");

    $stmt->execute([
        ':hash' => $hash,
        ':id'   => $usuario['usu_id']
    ]);

    /*
    |--------------------------------------------------------------------------
    | ENVIAR MAIL
    |--------------------------------------------------------------------------
    */

    var_dump(getenv('MAIL_FROM'));
    var_dump(getenv('MAIL_USER'));
    var_dump(getenv('MAIL_PASS'));
    exit;

    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host       = getenv('MAIL_HOST');
    $mail->SMTPAuth   = true;
    $mail->Username   = getenv('MAIL_USER');
    $mail->Password   = getenv('MAIL_PASS');
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = getenv('MAIL_PORT');

    $mail->CharSet = 'UTF-8';

    $mail->setFrom(
        getenv('MAIL_FROM'),
        'Sporta'
    );

    $mail->addAddress(
        $usuario['usu_email']
    );

    $mail->isHTML(true);

    $mail->Subject = 'Recuperación de contraseña - Sporta';

    $mail->Body = "
        <h2>Sporta</h2>

        <p>Hola {$usuario['usu_nombre']}.</p>

        <p>Se generó una nueva contraseña temporal para tu cuenta.</p>

        <p>
            <strong>{$nuevaPassword}</strong>
        </p>

        <p>
            Te recomendamos iniciar sesión y cambiarla cuanto antes.
        </p>
    ";

    $mail->send();

    echo 'Si el correo existe, se enviará una nueva contraseña.';

} catch (Exception $e) {

    echo "<pre>";
    echo $e->getMessage();
    echo "</pre>";

}