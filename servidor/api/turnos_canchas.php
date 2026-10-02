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
        case 'crear_cancha':
        case 'actualizar_cancha':
        case 'eliminar_cancha':
        case 'habilitar_cancha':
        case 'prever_deshabilitar':
        case 'ejecutar_deshabilitar':
        case 'finalizar_mantenimiento':
            if (!$_SESSION['usuario']->isAdmin()) {
                echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
                exit;
            }
            switch ($input['accion']) {
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
                case 'prever_deshabilitar':
                    preverDeshabilitar($pdo, $input);
                    break;
                case 'ejecutar_deshabilitar':
                    ejecutarDeshabilitar($pdo, $input);
                    break;
                case 'finalizar_mantenimiento':
                    finalizarMantenimiento($pdo, $input);
                    break;
            }
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
  AND cliente_id != 999
ORDER BY cliente_apellido, cliente_nombre
";
    $stmt = $pdo->query($sql);
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sql = "SELECT
                r.reserva_id,
                r.reser_estado,
                r.reser_observaciones,
                r.cliente_id,
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
            LEFT JOIN clientes c ON r.cliente_id = c.cliente_id AND c.cliente_id != 999
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
        $horaInicioRaw = trim($input['hora_inicio']);
        $inicioTurno = strtotime($fecha . ' ' . $horaInicioRaw);
        $finTurno = $inicioTurno + 3600;
        if ($finTurno <= time()) {
            echo json_encode([
                'ok' => false,
                'mensaje' => 'No se puede reservar un horario ya finalizado'
            ]);
            return;
        }
        // Si inicioTurno <= now < finTurno => hora en curso: se permite (soft-assist pago total en FE)
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
        // Si hay reserva real, prima ese mensaje; solo fantasma => mantenimiento.
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM reservas r
            INNER JOIN turnos t ON r.tur_id = t.tur_id
            WHERE t.id_cancha = ?
              AND t.tur_fecha = ?
              AND t.tur_hora_inicio = ?
              AND r.cliente_id != 999
              AND r.reser_estado IN (1,2)
        ");
        $stmt->execute([$canchaId, $fecha, $horaInicio]);
        $hayReal = (int)$stmt->fetchColumn() > 0;

        echo json_encode([
            'ok' => false,
            'mensaje' => $hayReal ? 'El horario ya está reservado' : 'La cancha está en mantenimiento en ese horario'
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

        $reservaId = (int)$pdo->lastInsertId();
        // Obtener precio de la cancha para asistencia de pago
        $stmtPrecio = $pdo->prepare("SELECT cancha_precio FROM canchas WHERE cancha_id = ?");
        $stmtPrecio->execute([$canchaId]);
        $precioCancha = $stmtPrecio->fetchColumn();
        $horaInicioTs = strtotime($fecha . ' ' . $horaInicio);
        $horaFinTs = $horaInicioTs + 3600;
        $esHoy = ($fecha === date('Y-m-d'));
        $esHoraEnCurso = $esHoy && $horaInicioTs <= time() && time() < $horaFinTs;

        echo json_encode([
            'ok' => true,
            'mensaje' => 'Reserva creada',
            'reserva_id' => $reservaId,
            'factura_total' => $precioCancha !== false ? (float)$precioCancha : null,
            'requiere_sena' => $esHoy,
            'requiere_pago_total' => $esHoraEnCurso,
            'hora_en_curso' => $esHoraEnCurso
        ]);
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
    SELECT c.cancha_id, c.cancha_numero, c.cancha_precio, c.descripcion, c.cancha_estado, ec.descripcion AS estado_descripcion,
           EXISTS (
               SELECT 1 FROM reservas r
               INNER JOIN turnos t ON r.tur_id = t.tur_id
               WHERE t.id_cancha = c.cancha_id
                 AND r.cliente_id = 999
                 AND r.reser_estado IN (1,2)
                 AND t.tur_fecha >= CURDATE()
           ) AS en_mantenimiento
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
    // Las canchas nuevas siempre nacen Disponibles (el estado es de solo lectura).
    $estado = 1;

    if ($numero <= 0) {
        throw new Exception('El número de cancha debe ser mayor a 0');
    }
    if ($precio < 0) {
        throw new Exception('El precio no puede ser negativo');
    }
    if ($precio > 99999999.99) {
        throw new Exception('El precio no puede superar $99.999.999,99');
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

    if ($numero <= 0) {
        throw new Exception('El número de cancha debe ser mayor a 0');
    }
    if ($precio < 0) {
        throw new Exception('El precio no puede ser negativo');
    }
    if ($precio > 99999999.99) {
        throw new Exception('El precio no puede superar $99.999.999,99');
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM canchas WHERE cancha_numero = ? AND cancha_id != ?");
    $stmt->execute([$numero, $canchaId]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('Ya existe otra cancha con ese número');
    }

    // El estado es de solo lectura en el formulario: solo cambia vía
    // ejecutar_deshabilitar (2/3), finalizar_mantenimiento o habilitar_cancha (1).
    $sql = "UPDATE canchas SET cancha_numero = ?, cancha_precio = ?, descripcion = ? WHERE cancha_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$numero, $precio, $descripcion, $canchaId]);


    echo json_encode(['ok' => true, 'mensaje' => 'Cancha actualizada correctamente']);
}

function eliminarCancha(PDO $pdo, array $input): void
{
    $canchaId = (int)$input['cancha_id'];

    $preview = preverDeshabilitarInterno($pdo, $canchaId, 3, null, null, null, null);
    if (!$preview['puede_deshabilitar']) {
        echo json_encode([
            'ok' => false,
            'mensaje' => 'La cancha tiene reservas futuras. Use la vista previa para decidir qué hacer con cada una.',
            'reservas_afectadas' => $preview['reservas_afectadas']
        ]);
        return;
    }

    $pdo->beginTransaction();
    try {
        $sql = "UPDATE canchas SET cancha_estado = 3 WHERE cancha_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$canchaId]);
        $pdo->commit();
        echo json_encode(['ok' => true, 'mensaje' => 'Cancha inhabilitada correctamente']);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function habilitarCancha(PDO $pdo, array $input): void
{
    $canchaId = (int)$input['cancha_id'];
    $sql = "UPDATE canchas SET cancha_estado = 1 WHERE cancha_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$canchaId]);


    echo json_encode(['ok' => true, 'mensaje' => 'Cancha habilitada correctamente']);
}

function preverDeshabilitar(PDO $pdo, array $input): void
{
    $canchaId = (int)($input['cancha_id'] ?? 0);
    $estadoDestino = (int)($input['estado_destino'] ?? 3);
    $fechaDesde = !empty($input['fecha_desde']) ? $input['fecha_desde'] : null;
    $fechaHasta = !empty($input['fecha_hasta']) ? $input['fecha_hasta'] : null;
    $horaDesde = !empty($input['hora_desde']) ? $input['hora_desde'] : null;
    $horaHasta = !empty($input['hora_hasta']) ? $input['hora_hasta'] : null;

    if ($canchaId <= 0 || !in_array($estadoDestino, [2, 3])) {
        echo json_encode(['ok' => false, 'mensaje' => 'Parámetros inválidos']);
        return;
    }

    $resultado = preverDeshabilitarInterno($pdo, $canchaId, $estadoDestino, $fechaDesde, $fechaHasta, $horaDesde, $horaHasta);
    echo json_encode(['ok' => true] + $resultado);
}

function ejecutarDeshabilitar(PDO $pdo, array $input): void
{
    $canchaId = (int)($input['cancha_id'] ?? 0);
    $estadoDestino = (int)($input['estado_destino'] ?? 3);
    $plan = $input['plan'] ?? [];
    $fechaDesde = !empty($input['fecha_desde']) ? $input['fecha_desde'] : null;
    $fechaHasta = !empty($input['fecha_hasta']) ? $input['fecha_hasta'] : null;
    $horaDesde = !empty($input['hora_desde']) ? $input['hora_desde'] : null;
    $horaHasta = !empty($input['hora_hasta']) ? $input['hora_hasta'] : null;
    $motivo = !empty($input['motivo']) ? $input['motivo'] : '';

    if ($canchaId <= 0 || !in_array($estadoDestino, [2, 3])) {
        echo json_encode(['ok' => false, 'mensaje' => 'Parámetros inválidos']);
        return;
    }

    $pdo->beginTransaction();
    try {
        $stats = ['mismo_horario' => 0, 'canceladas' => 0, 'avisar' => 0, 'slots_senados_omitidos' => 0];
        $ahoraEjec = date('Y-m-d H:i:s');

        foreach ($plan as $item) {
            $reservaId = (int)($item['reserva_id'] ?? 0);
            $accion = $item['accion'] ?? 'avisar';
            $nuevaCanchaId = isset($item['nueva_cancha_id']) ? (int)$item['nueva_cancha_id'] : null;

            if ($reservaId <= 0) continue;

            $stmt = $pdo->prepare("
                SELECT r.reserva_id, r.tur_id, r.cliente_id, r.reser_estado, t.tur_fecha, t.tur_hora_inicio, t.tur_hora_fin, ca.cancha_precio
                FROM reservas r
                INNER JOIN turnos t ON r.tur_id = t.tur_id
                INNER JOIN canchas ca ON t.id_cancha = ca.cancha_id
                WHERE r.reserva_id = ? AND r.reser_estado IN (1,2)
            ");
            $stmt->execute([$reservaId]);
            $reserva = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$reserva) continue;

            // Nunca tocar reservas ya jugadas o en curso.
            if ($reserva['tur_fecha'] . ' ' . $reserva['tur_hora_inicio'] < $ahoraEjec) continue;

            switch ($accion) {
                case 'reubicar_mismo_horario':
                    if ($nuevaCanchaId) {
                        $stmt = $pdo->prepare("UPDATE turnos SET id_cancha = ? WHERE tur_id = ?");
                        $stmt->execute([$nuevaCanchaId, $reserva['tur_id']]);
                        $stats['mismo_horario']++;
                    }
                    break;
                case 'cancelar':
                    $stmt = $pdo->prepare("UPDATE reservas SET reser_estado = 3 WHERE reserva_id = ?");
                    $stmt->execute([$reservaId]);
                    $stats['canceladas']++;
                    break;
                case 'avisar':
                default:
                    $stats['avisar']++;
                    break;
            }
        }

        // Inhabilitar (3) bloquea la cancha entera. Mantenimiento (2) NO toca
        // cancha_estado: el bloqueo es por slot vía fantasmas 999 (ver abajo).
        if ($estadoDestino === 3) {
            $sql = "UPDATE canchas SET cancha_estado = 3 WHERE cancha_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$canchaId]);
        }

        if ($estadoDestino === 2) {
            $obs = 'Mantenimiento programado' . ($motivo ? ': ' . $motivo : '');
            // Turnos con seña (pagado > 0): intocables, ni siquiera fantasma.
            $sqlPagados = "
                SELECT t.tur_id
                FROM turnos t
                INNER JOIN reservas r ON r.tur_id = t.tur_id
                LEFT JOIN facturacion f ON f.reserva_id = r.reserva_id
                LEFT JOIN pagos p ON p.factura_id = f.factura_id
                WHERE t.id_cancha = ?
                  AND r.reser_estado IN (1,2)
                  AND r.cliente_id != 999
            ";
            $paramsPagados = [$canchaId];
            if ($fechaDesde && $fechaHasta) {
                $sqlPagados .= " AND t.tur_fecha >= ? AND t.tur_fecha <= ?";
                $paramsPagados[] = $fechaDesde;
                $paramsPagados[] = $fechaHasta;
            }
            $sqlPagados .= " GROUP BY t.tur_id HAVING COALESCE(SUM(p.pago_monto), 0) > 0";
            $stmtPag = $pdo->prepare($sqlPagados);
            $stmtPag->execute($paramsPagados);
            $turIdsSenados = [];
            foreach ($stmtPag->fetchAll(PDO::FETCH_COLUMN) as $tid) {
                $turIdsSenados[(int)$tid] = true;
            }
            if ($fechaDesde && $fechaHasta) {
                // Cobertura completa del rango: los turnos se crean on-demand,
                // así que se generan las filas turno que falten y se les pone
                // fantasma 999 a todas (solo los slots del rango quedan bloqueados).
                $dDesde = new DateTime($fechaDesde);
                $dHasta = new DateTime($fechaHasta);
                if ($dHasta < $dDesde) {
                    $tmp = $dDesde; $dDesde = $dHasta; $dHasta = $tmp;
                }
                if ($dDesde->diff($dHasta)->days > 31) {
                    throw new Exception('El rango de mantenimiento no puede superar 31 días');
                }
                $hDesde = $horaDesde ? (int)substr($horaDesde, 0, 2) : 8;
                $hHasta = $horaHasta ? (int)substr($horaHasta, 0, 2) : 23;
                $hDesde = max(0, min(23, $hDesde));
                $hHasta = max(0, min(23, $hHasta));

                $stmtTurno = $pdo->prepare("SELECT tur_id FROM turnos WHERE id_cancha = ? AND tur_fecha = ? AND tur_hora_inicio = ?");
                $stmtNewTurno = $pdo->prepare("INSERT INTO turnos (id_cancha, tur_fecha, tur_hora_inicio, tur_hora_fin) VALUES (?, ?, ?, ?)");
                $stmtFantasma = $pdo->prepare("SELECT 1 FROM reservas WHERE tur_id = ? AND cliente_id = 999 AND reser_estado IN (1,2)");
                $stmtNewFantasma = $pdo->prepare("
                    INSERT INTO reservas (usu_id, cliente_id, tur_id, reser_fecha, reser_estado, reser_observaciones)
                    VALUES (NULL, 999, ?, CURDATE(), 1, ?)
                ");

                for ($d = clone $dDesde; $d <= $dHasta; $d->modify('+1 day')) {
                    $fecha = $d->format('Y-m-d');
                    for ($h = $hDesde; $h < $hHasta; $h++) {
                        $horaInicio = sprintf('%02d:00:00', $h);
                        $horaFin = date('H:i:s', strtotime($horaInicio . ' +1 hour'));
                        $stmtTurno->execute([$canchaId, $fecha, $horaInicio]);
                        $turId = $stmtTurno->fetchColumn();
                        if (!$turId) {
                            $stmtNewTurno->execute([$canchaId, $fecha, $horaInicio, $horaFin]);
                            $turId = (int)$pdo->lastInsertId();
                        } else {
                            $turId = (int)$turId;
                        }
                        // Señado: no se toca ni con fantasma.
                        if (isset($turIdsSenados[$turId])) {
                            $stats['slots_senados_omitidos']++;
                            continue;
                        }
                        $stmtFantasma->execute([$turId]);
                        if (!$stmtFantasma->fetchColumn()) {
                            $stmtNewFantasma->execute([$turId, $obs]);
                        }
                    }
                }
            } else {
                // Sin rango: cubrir turnos existentes (compatibilidad).
                $stmt = $pdo->prepare("SELECT tur_id FROM turnos WHERE id_cancha = ?");
                $stmt->execute([$canchaId]);
                $stmtFantasma = $pdo->prepare("SELECT 1 FROM reservas WHERE tur_id = ? AND cliente_id = 999 AND reser_estado IN (1,2)");
                $stmtNewFantasma = $pdo->prepare("
                    INSERT INTO reservas (usu_id, cliente_id, tur_id, reser_fecha, reser_estado, reser_observaciones)
                    VALUES (NULL, 999, ?, CURDATE(), 1, ?)
                ");
                foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $turId) {
                    $turId = (int)$turId;
                    if (isset($turIdsSenados[$turId])) {
                        $stats['slots_senados_omitidos']++;
                        continue;
                    }
                    $stmtFantasma->execute([$turId]);
                    if (!$stmtFantasma->fetchColumn()) {
                        $stmtNewFantasma->execute([$turId, $obs]);
                    }
                }
            }
        }

        $pdo->commit();
        echo json_encode([
            'ok' => true,
            'mensaje' => 'Ejecutado correctamente',
            'stats' => $stats
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function finalizarMantenimiento(PDO $pdo, array $input): void
{
    $canchaId = (int)($input['cancha_id'] ?? 0);
    if ($canchaId <= 0) {
        echo json_encode(['ok' => false, 'mensaje' => 'ID de cancha inválido']);
        return;
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("
            DELETE r FROM reservas r
            INNER JOIN turnos t ON r.tur_id = t.tur_id
            WHERE t.id_cancha = ? AND r.cliente_id = 999
        ");
        $stmt->execute([$canchaId]);

        $stmt = $pdo->prepare("UPDATE canchas SET cancha_estado = 1 WHERE cancha_id = ?");
        $stmt->execute([$canchaId]);

        $pdo->commit();
        echo json_encode(['ok' => true, 'mensaje' => 'Mantenimiento finalizado, cancha disponible']);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function preverDeshabilitarInterno(PDO $pdo, int $canchaId, int $estadoDestino, ?string $fechaDesde, ?string $fechaHasta, ?string $horaDesde, ?string $horaHasta): array
{
    // Los horarios pasados (incluido el turno en curso) no se consideran:
    // lo ya jugado no se puede mover ni bloquear.
    $ahora = date('Y-m-d H:i:s');
    $where = "t.id_cancha = ? AND r.reser_estado IN (1,2) AND CONCAT(t.tur_fecha, ' ', t.tur_hora_inicio) >= ?";
    $params = [$canchaId, $ahora];

    if ($fechaDesde) {
        $where .= " AND t.tur_fecha >= ?";
        $params[] = $fechaDesde;
    }
    if ($fechaHasta) {
        $where .= " AND t.tur_fecha <= ?";
        $params[] = $fechaHasta;
    }
    if ($horaDesde) {
        $where .= " AND t.tur_hora_inicio >= ?";
        $params[] = $horaDesde;
    }
    if ($horaHasta) {
        $where .= " AND t.tur_hora_inicio < ?";
        $params[] = $horaHasta;
    }

    $sql = "
        SELECT r.reserva_id, r.cliente_id, r.reser_estado, r.reser_observaciones,
               c.cliente_nombre, c.cliente_apellido, c.cliente_celular,
               t.tur_id, t.tur_fecha, t.tur_hora_inicio, t.tur_hora_fin,
               ca.cancha_id AS cancha_origen_id, ca.cancha_numero AS cancha_origen_numero, ca.cancha_precio
        FROM reservas r
        INNER JOIN turnos t ON r.tur_id = t.tur_id
        INNER JOIN canchas ca ON t.id_cancha = ca.cancha_id
        LEFT JOIN clientes c ON r.cliente_id = c.cliente_id
        WHERE $where
        ORDER BY t.tur_fecha, t.tur_hora_inicio
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $reservasAfectadas = [];
    foreach ($reservas as $r) {
        $precio = (float)$r['cancha_precio'];
        $altsMismoHorario = buscarAlternativasMismoHorario($pdo, $canchaId, $r['tur_fecha'], $r['tur_hora_inicio'], $precio);

        $accionPropuesta = !empty($altsMismoHorario) ? 'reubicar_mismo_horario' : 'avisar';

        $reservasAfectadas[] = [
            'reserva_id' => (int)$r['reserva_id'],
            'cliente_id' => (int)$r['cliente_id'],
            'cliente_nombre' => $r['cliente_nombre'],
            'cliente_apellido' => $r['cliente_apellido'],
            'cliente_celular' => $r['cliente_celular'],
            'tur_fecha' => $r['tur_fecha'],
            'tur_hora_inicio' => $r['tur_hora_inicio'],
            'tur_hora_fin' => $r['tur_hora_fin'],
            'cancha_origen_numero' => (int)$r['cancha_origen_numero'],
            'precio_original' => $precio,
            'alternativas_mismo_horario' => $altsMismoHorario,
            'accion_propuesta' => $accionPropuesta
        ];
    }

    // Reservas ya jugadas dentro del rango (se omiten del plan).
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM reservas r
        INNER JOIN turnos t ON r.tur_id = t.tur_id
        WHERE t.id_cancha = ?
          AND r.reser_estado IN (1,2)
          AND CONCAT(t.tur_fecha, ' ', t.tur_hora_inicio) < ?
          AND (? IS NULL OR t.tur_fecha >= ?)
          AND (? IS NULL OR t.tur_fecha <= ?)
          AND (? IS NULL OR t.tur_hora_inicio >= ?)
          AND (? IS NULL OR t.tur_hora_inicio < ?)
    ");
    $stmt->execute([$canchaId, $ahora, $fechaDesde, $fechaDesde, $fechaHasta, $fechaHasta, $horaDesde, $horaDesde, $horaHasta, $horaHasta]);
    $pasadasOmitidas = (int)$stmt->fetchColumn();

    return [
        'puede_deshabilitar' => empty($reservasAfectadas),
        'reservas_afectadas' => $reservasAfectadas,
        'reservas_pasadas_omitidas' => $pasadasOmitidas
    ];
}

function buscarAlternativasMismoHorario(PDO $pdo, int $excluirCanchaId, string $fecha, string $horaInicio, float $precio): array
{
    // Sin filtro de precio: se mantiene el turno (misma fecha/hora) en otra
    // cancha. Ordenadas por cercanía de precio; la factura conserva el original.
    $sql = "
        SELECT c.cancha_id, c.cancha_numero, c.cancha_precio
        FROM canchas c
        WHERE c.cancha_estado = 1
          AND c.cancha_id != ?
          AND NOT EXISTS (
              SELECT 1 FROM turnos t
              JOIN reservas r ON t.tur_id = r.tur_id
              WHERE t.id_cancha = c.cancha_id
                AND t.tur_fecha = ?
                AND t.tur_hora_inicio = ?
                AND r.reser_estado IN (1,2)
          )
        ORDER BY ABS(c.cancha_precio - ?), c.cancha_numero
        LIMIT 5
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$excluirCanchaId, $fecha, $horaInicio, $precio]);
    $alts = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $p = (float)$row['cancha_precio'];
        $alts[] = [
            'cancha_id' => (int)$row['cancha_id'],
            'cancha_numero' => (int)$row['cancha_numero'],
            'cancha_precio' => $p,
            'diferencia' => round($p - $precio, 2)
        ];
    }
    return $alts;
}
