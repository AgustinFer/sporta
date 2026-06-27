<?php

require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/conexion.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario']) || !$_SESSION['usuario']->isAdmin()) {
    echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
    exit;
}

$pdo = conexion();
$input = json_decode(file_get_contents('php://input'), true);
$accion = $input['accion'] ?? ($_GET['accion'] ?? '');

try {

    switch ($accion) {

        case 'listar':
            listar($pdo);
            break;

        case 'toggle_estado':
            toggleEstado($pdo, $input);
            break;

        case 'crear':
            crear($pdo, $input);
            break;

        case 'editar':
            editar($pdo, $input);
            break;

        default:
            echo json_encode(['ok' => false, 'mensaje' => 'Acción inválida']);
            break;
    }

} catch (Exception $e) {
    loggear('error_excepcion', ['archivo' => 'empleados.php', 'mensaje' => $e->getMessage()]);
    echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
}

function listar(PDO $pdo): void
{
    $stmt = $pdo->query("SELECT u.*, r.rol_nombre FROM usuarios u JOIN roles r ON u.usu_rol = r.rol_id ORDER BY u.usu_id");
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['ok' => true, 'empleados' => $empleados]);
}

function toggleEstado(PDO $pdo, array $input): void
{
    $id = (int) ($input['empleado_id'] ?? 0);

    if ($id <= 0) {
        loggear('error_empleado_id_invalido', ['accion' => 'toggle_estado']);
        echo json_encode(['ok' => false, 'mensaje' => 'ID inválido']);
        return;
    }

    if ($id === $_SESSION['usuario']->getId()) {
        loggear('error_empleado_auto_inactivacion');
        echo json_encode(['ok' => false, 'mensaje' => 'No puedes inactivar tu propio usuario']);
        return;
    }

    $stmt = $pdo->prepare("SELECT usu_estado FROM usuarios WHERE usu_id = ?");
    $stmt->execute([$id]);
    $actual = $stmt->fetchColumn();

    $nuevoEstado = ((int)$actual === 1) ? 0 : 1;

    $stmt = $pdo->prepare("UPDATE usuarios SET usu_estado = ? WHERE usu_id = ?");
    $stmt->execute([$nuevoEstado, $id]);

    loggear('empleado_estado_toggle', [
        'empleado_id' => $id,
        'nuevo_estado' => $nuevoEstado === 1 ? 'activo' : 'inactivo'
    ]);

    echo json_encode([
        'ok' => true,
        'mensaje' => $nuevoEstado === 1 ? 'Empleado activado' : 'Empleado inactivado',
        'nuevo_estado' => $nuevoEstado
    ]);
}

function validarDatos(array $data): array
{
    $errores = [];
    $nombre = trim($data['nombre'] ?? '');
    $apellido = trim($data['apellido'] ?? '');
    $email = trim($data['email'] ?? '');
    $celular = trim($data['celular'] ?? '');
    $dni = trim($data['dni'] ?? '');
    $usuario = trim($data['usuario'] ?? '');

    if (!preg_match('/^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s]+$/', $nombre)) {
        $errores[] = "El nombre contiene caracteres inválidos";
    } elseif (strlen($nombre) < 2) {
        $errores[] = "El nombre debe tener al menos 2 caracteres";
    }
    if (!preg_match('/^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s]+$/', $apellido)) {
        $errores[] = "El apellido contiene caracteres inválidos";
    } elseif (strlen($apellido) < 2) {
        $errores[] = "El apellido debe tener al menos 2 caracteres";
    }
    if ($dni !== "") {
        if (!preg_match('/^\d{7,8}$/', $dni)) {
            $errores[] = "El DNI debe tener entre 7 y 8 dígitos numéricos";
        }
    }
    if ($celular !== "") {
        if (!preg_match('/^\d{7,10}$/', $celular)) {
            $errores[] = "El teléfono debe tener entre 7 y 10 dígitos numéricos";
        }
    }
    if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El email no tiene un formato válido";
    }
    if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $usuario)) {
        $errores[] = "El usuario debe tener entre 3 y 20 caracteres alfanuméricos";
    }
    return $errores;
}

function verificarDuplicados(PDO $pdo, string $usuario, string $email, string $dni, ?int $excludeId = null): array
{
    $errores = [];

    if ($dni !== "") {
        $sql = "SELECT COUNT(*) FROM usuarios WHERE usu_dni = ?";
        $params = [$dni];
        if ($excludeId !== null) { $sql .= " AND usu_id != ?"; $params[] = $excludeId; }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->fetchColumn() > 0) $errores[] = "El DNI ya pertenece a otro empleado";

        $sql = "SELECT COUNT(*) FROM clientes WHERE cliente_dni = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$dni]);
        if ($stmt->fetchColumn() > 0) $errores[] = "El DNI ya pertenece a un cliente";
    }

    if ($email !== "") {
        $sql = "SELECT COUNT(*) FROM usuarios WHERE usu_email = ?";
        $params = [$email];
        if ($excludeId !== null) { $sql .= " AND usu_id != ?"; $params[] = $excludeId; }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->fetchColumn() > 0) $errores[] = "El email ya pertenece a otro empleado";

        $sql = "SELECT COUNT(*) FROM clientes WHERE cliente_email = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) $errores[] = "El email ya pertenece a un cliente";
    }

    $sql = "SELECT COUNT(*) FROM usuarios WHERE usu_usuario = ?";
    $params = [$usuario];
    if ($excludeId !== null) { $sql .= " AND usu_id != ?"; $params[] = $excludeId; }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    if ($stmt->fetchColumn() > 0) $errores[] = "El nombre de usuario ya está en uso";

    return $errores;
}

function crear(PDO $pdo, array $input): void
{
    $nombre = trim($input['nombre'] ?? '');
    $apellido = trim($input['apellido'] ?? '');
    $email = trim($input['email'] ?? '');
    $celular = trim($input['celular'] ?? '');
    $dni = trim($input['dni'] ?? '');
    $usuario = trim($input['usuario'] ?? '');
    $direccion = trim($input['direccion'] ?? '');
    $rol = (int) ($input['rol'] ?? 0);

    if (empty($nombre) || empty($apellido) || empty($usuario) || $rol <= 0) {
        echo json_encode(['ok' => false, 'mensaje' => 'Completá los campos obligatorios']);
        return;
    }

    $errores = validarDatos(compact('nombre', 'apellido', 'email', 'celular', 'dni', 'usuario'));

    if (empty($errores)) {
        $erroresDuplicados = verificarDuplicados($pdo, $usuario, $email, $dni);
        if (!empty($erroresDuplicados)) {
            loggear('error_empleado_duplicado', ['usuario' => $usuario, 'dni' => $dni, 'email' => $email]);
            echo json_encode(['ok' => false, 'mensaje' => implode('<br>', $erroresDuplicados), 'form_data' => $input]);
            return;
        }

        $stmt = $pdo->prepare("
            INSERT INTO usuarios (usu_nombre, usu_apellido, usu_email, usu_celular, usu_dni, usu_usuario, usu_direccion, usu_rol, usu_contrasena, usu_estado)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([$nombre, $apellido, $email ?: null, $celular ?: null, $dni ?: null, $usuario, $direccion ?: null, $rol, password_hash("1234", PASSWORD_DEFAULT)]);

        loggear('empleado_creado', [
            'nombre' => $nombre,
            'apellido' => $apellido,
            'usuario' => $usuario
        ]);

        echo json_encode(['ok' => true, 'mensaje' => 'Empleado agregado con éxito']);
    } else {
        echo json_encode(['ok' => false, 'mensaje' => implode('<br>', $errores), 'form_data' => $input]);
    }
}

function editar(PDO $pdo, array $input): void
{
    $id = (int) ($input['empleado_id'] ?? 0);
    $nombre = trim($input['nombre'] ?? '');
    $apellido = trim($input['apellido'] ?? '');
    $email = trim($input['email'] ?? '');
    $celular = trim($input['celular'] ?? '');
    $dni = trim($input['dni'] ?? '');
    $usuario = trim($input['usuario'] ?? '');
    $direccion = trim($input['direccion'] ?? '');
    $rol = (int) ($input['rol'] ?? 0);

    if ($id <= 0 || empty($nombre) || empty($apellido) || empty($usuario) || $rol <= 0) {
        echo json_encode(['ok' => false, 'mensaje' => 'Datos inválidos']);
        return;
    }

    if ($id === $_SESSION['usuario']->getId()) {
        $stmt = $pdo->prepare("SELECT usu_rol FROM usuarios WHERE usu_id = ?");
        $stmt->execute([$id]);
        $rolActual = (int) $stmt->fetchColumn();
        if ($rol !== $rolActual) {
            loggear('error_empleado_auto_rol', ['intento_rol' => $rol]);
            echo json_encode(['ok' => false, 'mensaje' => 'No puedes modificar tu propio rol']);
            return;
        }
    }

    $errores = validarDatos(compact('nombre', 'apellido', 'email', 'celular', 'dni', 'usuario'));

    if (empty($errores)) {
        $erroresDuplicados = verificarDuplicados($pdo, $usuario, $email, $dni, $id);
        if (!empty($erroresDuplicados)) {
            loggear('error_empleado_duplicado', ['accion' => 'editar', 'empleado_id' => $id, 'usuario' => $usuario, 'dni' => $dni, 'email' => $email]);
            echo json_encode(['ok' => false, 'mensaje' => implode('<br>', $erroresDuplicados)]);
            return;
        }

        $stmt = $pdo->prepare("
            UPDATE usuarios SET usu_nombre = ?, usu_apellido = ?, usu_email = ?, usu_celular = ?, usu_dni = ?, usu_usuario = ?, usu_direccion = ?, usu_rol = ?
            WHERE usu_id = ?
        ");
        $stmt->execute([$nombre, $apellido, $email ?: null, $celular ?: null, $dni ?: null, $usuario, $direccion ?: null, $rol, $id]);

        if ((int)$id === $_SESSION['usuario']->getId()) {
            $_SESSION['usuario']->setNombre($nombre);
            $_SESSION['usuario']->setApellido($apellido);
            $_SESSION['usuario']->setEmail($email ?: null);
            $_SESSION['usuario']->setCelular($celular ?: null);
            $_SESSION['usuario']->setDni($dni ?: null);
            $_SESSION['usuario']->setUsuario($usuario);
            $_SESSION['usuario']->setDireccion($direccion ?: null);
        }

        loggear('empleado_editado', [
            'empleado_id' => $id,
            'nombre' => $nombre,
            'apellido' => $apellido,
            'usuario' => $usuario
        ]);

        echo json_encode(['ok' => true, 'mensaje' => 'Empleado modificado con éxito']);
    } else {
        echo json_encode(['ok' => false, 'mensaje' => implode('<br>', $errores)]);
    }
}
