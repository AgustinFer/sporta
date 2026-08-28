<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/conexion.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
    exit;
}

try {
    $pdo = conexion();
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['accion'])) {
        throw new Exception('Acción no especificada');
    }

    switch ($input['accion']) {
        case 'obtener_datos':
            obtenerDatos($pdo, $input);
            break;
        case 'crear_reserva':
            crearReserva($pdo, $input);
            break;
        case 'cambiar_estado':
            cambiarEstado($pdo, $input);
            break;
        case 'obtener_canchas':
            obtenerCanchas($pdo, $input);
            break;
        case 'crear_cancha':
            crearCancha($pdo, $input);
            break;
        case 'actualizar_cancha':
            actualizarCancha($pdo, $input);
            break;
        case 'eliminar_cancha':
            eliminarCancha($pdo, $input);
            break;
        case 'habilitar_cancha':
            habilitarCancha($pdo, $input);
            break;
        default:
            throw new Exception('Acción inválida');
    }
} catch (Exception $e) {
    echo json_encode([
        'ok' => false,
        'mensaje' => $e->getMessage()
    ]);
}


function obtenerDatos(PDO $pdo, array $input): void
{
    $fecha = $input['fecha'] ?? date('Y-m-d');

    $sql = "
SELECT cancha_id, cancha_numero, cancha_estado
FROM canchas
WHERE cancha_estado != 3
   OR cancha_id IN (
       SELECT DISTINCT t.id_cancha
       FROM turnos t
       INNER JOIN reservas r ON t.tur_id = r.tur_id
       WHERE t.tur_fecha = ?
   )
ORDER BY cancha_numero
";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$fecha]);
    $canchas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sql = "
SELECT
cliente_id,
cliente_nombre,
cliente_apellido
FROM clientes
WHERE cliente_estado = 1
ORDER BY cliente_apellido, cliente_nombre
";
    $stmt = $pdo->query($sql);
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sql = "SELECT
                r.reserva_id,
                r.reser_estado,
                r.reser_observaciones,
                c.cliente_id,
                c.cliente_nombre,
                c.cliente_apellido,
                t.tur_id,
                t.tur_fecha,
                t.tur_hora_inicio,
                t.tur_hora_fin,
                ca.cancha_id,
                ca.cancha_numero
            FROM reservas r
            INNER JOIN turnos t ON r.tur_id = t.tur_id
            INNER JOIN canchas ca ON t.id_cancha = ca.cancha_id
            LEFT JOIN clientes c ON r.cliente_id = c.cliente_id
            WHERE t.tur_fecha = ?
            ORDER BY t.tur_hora_inicio, ca.cancha_numero
        ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$fecha]);
    $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok' => true,
        'canchas' => $canchas,
        'clientes' => $clientes,
        'reservas' => $reservas
    ]);
}


function crearReserva(PDO $pdo, array $input): void
{
    $canchaId = (int)$input['cancha_id'];
    $clienteId = (int)$input['cliente_id'];
    $fecha = trim($input['fecha']);

    $hoy = date('Y-m-d');

    if ($fecha < $hoy) {
        echo json_encode([
            'ok' => false,
            'mensaje' => 'No se puede reservar en una fecha pasada'
        ]);
        return;
    }

    if ($fecha === $hoy) {
        $inicioTurno = strtotime($fecha . ' ' . $input['hora_inicio']);
        if ($inicioTurno <= time()) {
            echo json_encode([
                'ok' => false,
                'mensaje' => 'No se puede reservar un horario ya pasado'
            ]);
            return;
        }
    }

    $horaInicio = trim($input['hora_inicio']);
    $observaciones = trim($input['observaciones'] ?? '');
    $horaFin = date('H:i:s', strtotime($horaInicio . ' +1 hour'));
    $horaInicio = date('H:i:s', strtotime($horaInicio));

    $sql = "
    SELECT COUNT(*) total
    FROM reservas r
    INNER JOIN turnos t ON r.tur_id = t.tur_id
    WHERE t.id_cancha = ?
      AND t.tur_fecha = ?
      AND t.tur_hora_inicio = ?
      AND r.reser_estado IN (1,2)
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$canchaId, $fecha, $horaInicio]);
    $ocupado = (int)$stmt->fetchColumn();

    if ($ocupado > 0) {
        echo json_encode([
            'ok' => false,
            'mensaje' => 'El horario ya está reservado'
        ]);
        return;
    }

    $sql = "
    SELECT COUNT(*) total
    FROM reservas r
    INNER JOIN turnos t ON r.tur_id = t.tur_id
    WHERE r.cliente_id = ?
      AND t.tur_fecha = ?
      AND t.tur_hora_inicio = ?
      AND r.reser_estado IN (1,2)
      AND t.id_cancha != ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$clienteId, $fecha, $horaInicio, $canchaId]);
    $clienteOcupado = (int)$stmt->fetchColumn();

    if ($clienteOcupado > 0 && empty($input['confirm_same_client'])) {
        echo json_encode([
            'ok' => false,
            'requires_confirmation' => true,
            'mensaje' => 'Este cliente ya tiene una reserva activa en el mismo horario. ¿Desea continuar?'
        ]);
        return;
    }

    $sql = "SELECT cancha_estado FROM canchas WHERE cancha_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$canchaId]);
    $estadoCancha = (int)$stmt->fetchColumn();

    if ($estadoCancha === 2) {
        echo json_encode([
            'ok' => false,
            'mensaje' => 'La cancha está en mantenimiento'
        ]);
        return;
    }
    if ($estadoCancha === 3) {
        echo json_encode([
            'ok' => false,
            'mensaje' => 'La cancha está inhabilitada'
        ]);
        return;
    }

    $pdo->beginTransaction();
    try {
        $sql = "INSERT INTO turnos(id_cancha, tur_fecha, tur_hora_inicio, tur_hora_fin) VALUES (?,?,?,?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$canchaId, $fecha, $horaInicio, $horaFin]);
        $turId = $pdo->lastInsertId();

        $sql = "INSERT INTO reservas(usu_id, cliente_id, tur_id, reser_fecha, reser_estado, reser_observaciones) VALUES (NULL,?,?,CURDATE(),1,?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$clienteId, $turId, $observaciones]);

        $pdo->commit();


        echo json_encode(['ok' => true, 'mensaje' => 'Reserva creada']);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}


function cambiarEstado(PDO $pdo, array $input): void
{
    $reservaId = (int)$input['reserva_id'];
    $estado = (int)$input['estado'];

    if (!in_array($estado, [1, 2, 3])) {
        throw new Exception('Estado inválido');
    }

    /* Get the turno info for this reserva */
    $sql = "SELECT t.id_cancha, t.tur_fecha, t.tur_hora_inicio, r.reser_estado AS estado_actual
            FROM reservas r
            INNER JOIN turnos t ON r.tur_id = t.tur_id
            WHERE r.reserva_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$reservaId]);
    $reserva = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reserva) {
        throw new Exception('Reserva no encontrada');
    }

    $estadoActual = (int)$reserva['estado_actual'];

    /* If estado hasn't changed, no-op */
    if ($estado === $estadoActual) {
        echo json_encode(['ok' => true, 'mensaje' => 'Estado actualizado']);
        return;
    }

    /* If the current reserva is cancelled and there's another active one at the same slot, block */
    if ($estadoActual === 3) {
        $sql = "SELECT COUNT(*)
                FROM reservas r
                INNER JOIN turnos t ON r.tur_id = t.tur_id
                WHERE t.id_cancha = ?
                  AND t.tur_fecha = ?
                  AND t.tur_hora_inicio = ?
                  AND r.reserva_id != ?
                  AND r.reser_estado IN (1,2)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $reserva['id_cancha'],
            $reserva['tur_fecha'],
            $reserva['tur_hora_inicio'],
            $reservaId
        ]);
        $activos = (int)$stmt->fetchColumn();

        if ($activos > 0) {
            echo json_encode([
                'ok' => false,
                'mensaje' => 'No se puede modificar: ya hay una reserva activa en este horario'
            ]);
            return;
        }
    }

    $sql = "UPDATE reservas SET reser_estado = ? WHERE reserva_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$estado, $reservaId]);

    $mapa = [1 => 'pendiente', 2 => 'confirmada', 3 => 'cancelada'];

    echo json_encode(['ok' => true, 'mensaje' => 'Estado actualizado']);
}

function obtenerCanchas(PDO $pdo, array $input): void
{
    $incluirInhabilitadas = !empty($input['incluir_inhabilitadas']);

    $sql = "
    SELECT c.cancha_id, c.cancha_numero, c.cancha_precio, c.descripcion, c.cancha_estado, ec.descripcion AS estado_descripcion
    FROM canchas c
    LEFT JOIN estado_cancha ec ON c.cancha_estado = ec.estado_cancha_id
    ";
    if (!$incluirInhabilitadas) {
        $sql .= " WHERE c.cancha_estado != 3 ";
    }
    $sql .= " ORDER BY c.cancha_numero ";

    $stmt = $pdo->query($sql);
    $canchas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['ok' => true, 'canchas' => $canchas]);
}

function crearCancha(PDO $pdo, array $input): void
{
    $numero = (int)$input['cancha_numero'];
    $precio = (float)$input['cancha_precio'];
    $descripcion = trim($input['descripcion'] ?? '');
    $estado = (int)($input['cancha_estado'] ?? 1);

    if ($numero <= 0) {
        throw new Exception('El número de cancha debe ser mayor a 0');
    }
    if ($precio < 0) {
        throw new Exception('El precio no puede ser negativo');
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM canchas WHERE cancha_numero = ?");
    $stmt->execute([$numero]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('Ya existe una cancha con ese número');
    }

    $sql = "INSERT INTO canchas (cancha_numero, cancha_precio, descripcion, cancha_tipo, cancha_estado) VALUES (?, ?, ?, 1, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$numero, $precio, $descripcion, $estado]);


    echo json_encode(['ok' => true, 'mensaje' => 'Cancha creada correctamente']);
}

function actualizarCancha(PDO $pdo, array $input): void
{
    $canchaId = (int)$input['cancha_id'];
    $numero = (int)$input['cancha_numero'];
    $precio = (float)$input['cancha_precio'];
    $descripcion = trim($input['descripcion'] ?? '');
    $estado = (int)($input['cancha_estado'] ?? 1);

    if ($numero <= 0) {
        throw new Exception('El número de cancha debe ser mayor a 0');
    }
    if ($precio < 0) {
        throw new Exception('El precio no puede ser negativo');
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM canchas WHERE cancha_numero = ? AND cancha_id != ?");
    $stmt->execute([$numero, $canchaId]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('Ya existe otra cancha con ese número');
    }

    $sql = "UPDATE canchas SET cancha_numero = ?, cancha_precio = ?, descripcion = ?, cancha_estado = ? WHERE cancha_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$numero, $precio, $descripcion, $estado, $canchaId]);


    echo json_encode(['ok' => true, 'mensaje' => 'Cancha actualizada correctamente']);
}

function eliminarCancha(PDO $pdo, array $input): void
{
    $canchaId = (int)$input['cancha_id'];
    $sql = "UPDATE canchas SET cancha_estado = 3 WHERE cancha_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$canchaId]);


    echo json_encode(['ok' => true, 'mensaje' => 'Cancha inhabilitada correctamente']);
}

function habilitarCancha(PDO $pdo, array $input): void
{
    $canchaId = (int)$input['cancha_id'];
    $sql = "UPDATE canchas SET cancha_estado = 1 WHERE cancha_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$canchaId]);


    echo json_encode(['ok' => true, 'mensaje' => 'Cancha habilitada correctamente']);
}
