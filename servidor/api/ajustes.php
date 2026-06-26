<?php

require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/conexion.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
    exit;
}

$pdo = conexion();
$usuario = $_SESSION['usuario'];
$userId = $usuario->getId();

$input = json_decode(file_get_contents('php://input'), true);
$accion = $input['accion'] ?? '';

try {

    switch ($accion) {

        case 'check_usuario':
            $check = trim($input['usuario'] ?? '');
            if ($check === '') {
                echo json_encode(['disponible' => false]);
                exit;
            }
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE usu_usuario = :usuario AND usu_id != :id");
            $stmt->execute([':usuario' => $check, ':id' => $userId]);
            echo json_encode(['disponible' => $stmt->fetchColumn() == 0]);
            break;

        case 'cambio_usuario':
            $nuevoUsuario = trim($input['nuevo_usuario'] ?? '');
            if ($nuevoUsuario === '') {
                echo json_encode(['ok' => false, 'mensaje' => 'El nombre de usuario no puede estar vacío']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE usu_usuario = :usuario AND usu_id != :id");
            $stmt->execute([':usuario' => $nuevoUsuario, ':id' => $userId]);

            if ($stmt->fetchColumn() > 0) {
                echo json_encode(['ok' => false, 'mensaje' => 'El nombre de usuario ya está en uso']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE usuarios SET usu_usuario = :usuario WHERE usu_id = :id");
            $stmt->execute([':usuario' => $nuevoUsuario, ':id' => $userId]);

            $_SESSION['usuario']->setUsuario($nuevoUsuario);

            echo json_encode(['ok' => true, 'mensaje' => 'Nombre de usuario actualizado']);
            break;

        case 'cambio_contrasena':
            $passActual = $input['pass_actual'] ?? '';
            $passNueva = $input['pass_nueva'] ?? '';
            $passConfirmar = $input['pass_confirmar'] ?? '';

            $stmt = $pdo->prepare("SELECT usu_contrasena FROM usuarios WHERE usu_id = :id");
            $stmt->execute([':id' => $userId]);
            $hashActual = $stmt->fetchColumn();

            if (!password_verify($passActual, $hashActual)) {
                echo json_encode(['ok' => false, 'mensaje' => 'La contraseña actual no es correcta']);
                exit;
            }

            if ($passNueva !== $passConfirmar) {
                echo json_encode(['ok' => false, 'mensaje' => 'Las contraseñas no coinciden']);
                exit;
            }

            if (strlen($passNueva) < 6) {
                echo json_encode(['ok' => false, 'mensaje' => 'La contraseña debe tener al menos 6 caracteres']);
                exit;
            }

            if (!preg_match('/[A-Z]/', $passNueva)) {
                echo json_encode(['ok' => false, 'mensaje' => 'Debe contener al menos una mayúscula']);
                exit;
            }

            if (!preg_match('/[^a-zA-Z0-9]/', $passNueva)) {
                echo json_encode(['ok' => false, 'mensaje' => 'Debe contener al menos un caracter especial']);
                exit;
            }

            $hash = password_hash($passNueva, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE usuarios SET usu_contrasena = :hash WHERE usu_id = :id");
            $stmt->execute([':hash' => $hash, ':id' => $userId]);

            echo json_encode(['ok' => true, 'mensaje' => 'Contraseña actualizada correctamente']);
            break;

        case 'cambio_contrasena_default':
            $hash = password_hash("1234", PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE usuarios SET usu_contrasena = :hash WHERE usu_id = :id");
            $stmt->execute([':hash' => $hash, ':id' => $userId]);
            echo json_encode(['ok' => true, 'mensaje' => 'Contraseña restablecida a 1234']);
            break;

        case 'cambio_nombre':
            $nombre = trim($input['nombre'] ?? '');
            $apellido = trim($input['apellido'] ?? '');
            if ($nombre === '' || $apellido === '') {
                echo json_encode(['ok' => false, 'mensaje' => 'Nombre y apellido no pueden estar vacíos']);
                exit;
            }
            $stmt = $pdo->prepare("UPDATE usuarios SET usu_nombre = :nombre, usu_apellido = :apellido WHERE usu_id = :id");
            $stmt->execute([':nombre' => $nombre, ':apellido' => $apellido, ':id' => $userId]);
            $_SESSION['usuario']->setNombre($nombre);
            $_SESSION['usuario']->setApellido($apellido);
            echo json_encode(['ok' => true, 'mensaje' => 'Nombre y apellido actualizados']);
            break;

        case 'cambio_direccion':
            $direccion = trim($input['direccion'] ?? '');
            if ($direccion === '') {
                echo json_encode(['ok' => false, 'mensaje' => 'La dirección no puede estar vacía']);
                exit;
            }
            $stmt = $pdo->prepare("UPDATE usuarios SET usu_direccion = :direccion WHERE usu_id = :id");
            $stmt->execute([':direccion' => $direccion, ':id' => $userId]);
            $_SESSION['usuario']->setDireccion($direccion);
            echo json_encode(['ok' => true, 'mensaje' => 'Dirección actualizada']);
            break;

        case 'cambio_email':
            $email = trim($input['email'] ?? '');
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['ok' => false, 'mensaje' => 'Ingrese un email válido']);
                exit;
            }
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE usu_email = :email AND usu_id != :id");
            $stmt->execute([':email' => $email, ':id' => $userId]);
            if ($stmt->fetchColumn() > 0) {
                echo json_encode(['ok' => false, 'mensaje' => 'El email ya está en uso']);
                exit;
            }
            $stmt = $pdo->prepare("UPDATE usuarios SET usu_email = :email WHERE usu_id = :id");
            $stmt->execute([':email' => $email, ':id' => $userId]);
            $_SESSION['usuario']->setEmail($email);
            echo json_encode(['ok' => true, 'mensaje' => 'Email actualizado']);
            break;

        case 'cambio_celular':
            $celular = trim($input['celular'] ?? '');
            if ($celular === '') {
                echo json_encode(['ok' => false, 'mensaje' => 'El celular no puede estar vacío']);
                exit;
            }
            if (!preg_match('/^\d+$/', $celular)) {
                echo json_encode(['ok' => false, 'mensaje' => 'El celular solo debe contener números']);
                exit;
            }
            if (strlen($celular) > 10) {
                echo json_encode(['ok' => false, 'mensaje' => 'El celular no puede tener más de 10 dígitos']);
                exit;
            }
            $stmt = $pdo->prepare("UPDATE usuarios SET usu_celular = :celular WHERE usu_id = :id");
            $stmt->execute([':celular' => $celular, ':id' => $userId]);
            $_SESSION['usuario']->setCelular($celular);
            echo json_encode(['ok' => true, 'mensaje' => 'Celular actualizado']);
            break;

        case 'cambio_dni':
            $dni = trim($input['dni'] ?? '');
            if ($dni === '') {
                echo json_encode(['ok' => false, 'mensaje' => 'El DNI no puede estar vacío']);
                exit;
            }
            if (!preg_match('/^\d+$/', $dni)) {
                echo json_encode(['ok' => false, 'mensaje' => 'El DNI solo debe contener números']);
                exit;
            }
            $stmt = $pdo->prepare("UPDATE usuarios SET usu_dni = :dni WHERE usu_id = :id");
            $stmt->execute([':dni' => $dni, ':id' => $userId]);
            $_SESSION['usuario']->setDni($dni);
            echo json_encode(['ok' => true, 'mensaje' => 'DNI actualizado']);
            break;

        default:
            echo json_encode(['ok' => false, 'mensaje' => 'Acción inválida']);
            break;
    }

} catch (Exception $e) {
    echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
}
