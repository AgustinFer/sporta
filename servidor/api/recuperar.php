<?php

require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/mailer.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido']);
    exit;
}

$email = trim($_POST['email'] ?? '');

if (empty($email)) {
    echo json_encode(['ok' => false, 'mensaje' => 'Debe ingresar un email']);
    exit;
}

// Respuesta genérica tanto si el correo existe como si no (seguridad)
$mensajeGenerico = 'Si el correo existe en el sistema, se ha enviado una nueva contraseña temporal.';

try {

    $con = conexion();

    $stmt = $con->prepare("
        SELECT usu_id, usu_usuario, usu_nombre, usu_email
        FROM usuarios
        WHERE usu_email = :email
        LIMIT 1
    ");
    $stmt->execute([':email' => $email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        echo json_encode(['ok' => true, 'mensaje' => $mensajeGenerico]);
        exit;
    }

    // Generar contraseña temporal
    $nuevaPassword = bin2hex(random_bytes(4));
    $hash = password_hash($nuevaPassword, PASSWORD_DEFAULT);

    // Actualizar hash
    $stmt = $con->prepare("UPDATE usuarios SET usu_contrasena = :hash WHERE usu_id = :id");
    $stmt->execute([
        ':hash' => $hash,
        ':id'   => $usuario['usu_id']
    ]);

    // Enviar mail
    $mail = crearMailer();
    $mail->addAddress($usuario['usu_email']);
    $mail->Subject = 'Recuperación de contraseña - Sporta';
    $mail->Body = "
        <h2>Sporta</h2>

        <p>Hola {$usuario['usu_nombre']}.</p>

        <p>Se generó una nueva contraseña temporal para tu cuenta:</p>

        <p style=\"font-size:20px;font-weight:bold;\">{$nuevaPassword}</p>

        <p>Te recomendamos iniciar sesión y cambiarla cuanto antes.</p>
    ";

    $mail->send();

    echo json_encode(['ok' => true, 'mensaje' => $mensajeGenerico]);

} catch (\PHPMailer\PHPMailer\Exception $e) {
    error_log('Mail recuperar: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'mensaje' => 'No se pudo enviar el correo. Intente nuevamente más tarde.']);
    exit;
} catch (Exception $e) {
    error_log('Recuperar error: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'mensaje' => 'Error interno del servidor']);
}
