<?php

require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/conexion.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario'])) {
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
    loggear('error_excepcion', ['archivo' => 'clientes.php', 'mensaje' => $e->getMessage()]);
    echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
}

function listar(PDO $pdo): void
{
    $stmt = $pdo->query("SELECT cliente_id, cliente_nombre, cliente_apellido, cliente_email, cliente_celular, cliente_dni, cliente_estado FROM clientes ORDER BY cliente_id");
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['ok' => true, 'clientes' => $clientes]);
}

function toggleEstado(PDO $pdo, array $input): void
{
    $id = (int) ($input['cliente_id'] ?? 0);
    if ($id <= 0) {
        loggear('error_cliente_id_invalido', ['accion' => 'toggle_estado']);
        echo json_encode(['ok' => false, 'mensaje' => 'ID inválido']);
        return;
    }

    $stmt = $pdo->prepare("SELECT cliente_estado FROM clientes WHERE cliente_id = ?");
    $stmt->execute([$id]);
    $actual = $stmt->fetchColumn();

    $nuevoEstado = ((int)$actual === 1) ? 0 : 1;

    $stmt = $pdo->prepare("UPDATE clientes SET cliente_estado = ? WHERE cliente_id = ?");
    $stmt->execute([$nuevoEstado, $id]);

    loggear('cliente_estado_toggle', [
        'cliente_id' => $id,
        'nuevo_estado' => $nuevoEstado === 1 ? 'activo' : 'inactivo'
    ]);

    echo json_encode([
        'ok' => true,
        'mensaje' => $nuevoEstado === 1 ? 'Cliente activado' : 'Cliente inactivado',
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
    if ($celular === "") {
        $errores[] = "El teléfono es obligatorio";
    } elseif (!preg_match('/^\d{7,10}$/', $celular)) {
        $errores[] = "El teléfono debe tener entre 7 y 10 dígitos numéricos";
    }
    if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El email no tiene un formato válido";
    }
    return $errores;
}

function verificarDuplicados(PDO $pdo, string $email, string $dni, ?int $excludeId = null): array
{
    $errores = [];

    if ($dni !== "") {
        $sql = "SELECT COUNT(*) FROM clientes WHERE cliente_dni = ?";
        $params = [$dni];
        if ($excludeId !== null) { $sql .= " AND cliente_id != ?"; $params[] = $excludeId; }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->fetchColumn() > 0) $errores[] = "El DNI ya pertenece a otro cliente";

        $sql = "SELECT COUNT(*) FROM usuarios WHERE usu_dni = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$dni]);
        if ($stmt->fetchColumn() > 0) $errores[] = "El DNI ya pertenece a un empleado";
    }

    if ($email !== "") {
        $sql = "SELECT COUNT(*) FROM clientes WHERE cliente_email = ?";
        $params = [$email];
        if ($excludeId !== null) { $sql .= " AND cliente_id != ?"; $params[] = $excludeId; }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->fetchColumn() > 0) $errores[] = "El email ya pertenece a otro cliente";

        $sql = "SELECT COUNT(*) FROM usuarios WHERE usu_email = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) $errores[] = "El email ya pertenece a un empleado";
    }

    return $errores;
}

function crear(PDO $pdo, array $input): void
{
    $nombre = trim($input['nombre'] ?? '');
    $apellido = trim($input['apellido'] ?? '');
    $email = trim($input['email'] ?? '');
    $celular = trim($input['celular'] ?? '');
    $dni = trim($input['dni'] ?? '');

    if (empty($nombre) || empty($apellido) || empty($celular)) {
        echo json_encode(['ok' => false, 'mensaje' => 'Nombre, apellido y teléfono son obligatorios']);
        return;
    }

    $errores = validarDatos([
        'nombre' => $nombre, 'apellido' => $apellido,
        'email' => $email, 'celular' => $celular, 'dni' => $dni
    ]);

    if (!empty($errores)) {
        echo json_encode(['ok' => false, 'mensaje' => implode('<br>', $errores)]);
        return;
    }

    $erroresDuplicados = verificarDuplicados($pdo, $email, $dni);
    if (!empty($erroresDuplicados)) {
        loggear('error_cliente_duplicado', ['dni' => $dni, 'email' => $email]);
        echo json_encode(['ok' => false, 'mensaje' => implode('<br>', $erroresDuplicados)]);
        return;
    }

    $stmt = $pdo->prepare("
        INSERT INTO clientes (cliente_nombre, cliente_apellido, cliente_email, cliente_celular, cliente_dni, cliente_estado, cliente_localidad_id, cliente_provincia_id, cliente_pais_id)
        VALUES (?, ?, ?, ?, ?, 1, 1, 1, 1)
    ");
    $stmt->execute([$nombre, $apellido, $email ?: null, $celular ?: null, $dni ?: null]);

    $clienteId = (int) $pdo->lastInsertId();

    loggear('cliente_creado', [
        'cliente_id' => $clienteId,
        'nombre' => $nombre,
        'apellido' => $apellido
    ]);

    echo json_encode(['ok' => true, 'mensaje' => 'Cliente agregado con éxito', 'cliente_id' => $clienteId]);
}

function editar(PDO $pdo, array $input): void
{
    $id = (int) ($input['cliente_id'] ?? 0);
    $nombre = trim($input['nombre'] ?? '');
    $apellido = trim($input['apellido'] ?? '');
    $email = trim($input['email'] ?? '');
    $celular = trim($input['celular'] ?? '');
    $dni = trim($input['dni'] ?? '');

    if ($id <= 0 || empty($nombre) || empty($apellido) || empty($celular)) {
        loggear('error_cliente_datos_invalidos', ['accion' => 'editar', 'cliente_id' => $id]);
        echo json_encode(['ok' => false, 'mensaje' => 'Nombre, apellido y teléfono son obligatorios']);
        return;
    }

    $errores = validarDatos([
        'nombre' => $nombre, 'apellido' => $apellido,
        'email' => $email, 'celular' => $celular, 'dni' => $dni
    ]);

    if (!empty($errores)) {
        echo json_encode(['ok' => false, 'mensaje' => implode('<br>', $errores)]);
        return;
    }

    $erroresDuplicados = verificarDuplicados($pdo, $email, $dni, $id);
    if (!empty($erroresDuplicados)) {
        loggear('error_cliente_duplicado', ['accion' => 'editar', 'cliente_id' => $id, 'dni' => $dni, 'email' => $email]);
        echo json_encode(['ok' => false, 'mensaje' => implode('<br>', $erroresDuplicados)]);
        return;
    }

    $stmt = $pdo->prepare("
        UPDATE clientes
        SET cliente_nombre = ?, cliente_apellido = ?, cliente_email = ?, cliente_celular = ?, cliente_dni = ?
        WHERE cliente_id = ?
    ");
    $stmt->execute([$nombre, $apellido, $email ?: null, $celular ?: null, $dni ?: null, $id]);

    loggear('cliente_editado', [
        'cliente_id' => $id,
        'nombre' => $nombre,
        'apellido' => $apellido
    ]);

    echo json_encode(['ok' => true, 'mensaje' => 'Cliente modificado con éxito']);
}
