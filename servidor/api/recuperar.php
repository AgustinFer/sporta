<?php

require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/conexion.php';

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

try {

    $con = conexion();

    $stmt = $con->prepare("SELECT usu_id FROM usuarios WHERE usu_email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        loggear('recuperar_email_no_encontrado', ['email' => $email]);
        echo json_encode(['ok' => false, 'mensaje' => 'El correo no está registrado en el sistema']);
        exit;
    }

    loggear('recuperar_solicitado', ['email' => $email, 'usu_id' => (int)$usuario['usu_id']]);
    echo json_encode(['ok' => true, 'mensaje' => 'Se ha enviado un correo con las instrucciones para recuperar tu contraseña']);

} catch (Exception $e) {
    echo json_encode(['ok' => false, 'mensaje' => 'Error interno del servidor']);
}
