<?php

require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/conexion.php';

ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
    exit;
}

$pdo = conexion();
$input = json_decode(file_get_contents('php://input'), true);
$accion = '';

if (is_array($input) && isset($input['accion'])) {
    $accion = $input['accion'];
} elseif (isset($_GET['accion'])) {
    $accion = $_GET['accion'];
}

try {

    switch ($accion) {

        case 'listar':
            listar($pdo);
            break;

        case 'editar':
            editar($pdo, $input);
            break;

        case 'toggle_pago':
            togglePago($pdo, $input);
            break;

        case 'registrar_pago':
            registrarPago($pdo, $input);
            break;

        case 'pago_historial':
            pagoHistorial($pdo, $input);
            break;

        default:
            echo json_encode(['ok' => false, 'mensaje' => 'Acción inválida']);
            break;
    }

} catch (Exception $e) {
    echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
}

function listar(PDO $pdo): void
{
    try {
        $stmt = $pdo->query("
            SELECT
                r.reserva_id,
                r.reser_estado,
                r.reser_observaciones,
                c.cliente_id,
                c.cliente_nombre,
                c.cliente_apellido,
                ca.cancha_numero,
                ca.cancha_precio,
                t.tur_fecha,
                t.tur_hora_inicio,
                t.tur_hora_fin,
                er.estado_reserva_descripcion,
                f.factura_id,
                f.factura_estado,
                f.factura_total,
                COALESCE(pg.total_pagado, 0) AS total_pagado
            FROM reservas r
            JOIN turnos t ON r.tur_id = t.tur_id
            JOIN canchas ca ON t.id_cancha = ca.cancha_id
            LEFT JOIN clientes c ON r.cliente_id = c.cliente_id
            LEFT JOIN estado_reserva er ON r.reser_estado = er.estado_reserva_id
            LEFT JOIN facturacion f ON r.reserva_id = f.reserva_id
            LEFT JOIN (
                SELECT factura_id, SUM(pago_monto) AS total_pagado
                FROM pagos
                GROUP BY factura_id
            ) pg ON f.factura_id = pg.factura_id
            ORDER BY t.tur_fecha DESC, t.tur_hora_inicio
        ");
        $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmtMetodos = $pdo->query("SELECT * FROM metodo_pago WHERE metodo_estado = 1 ORDER BY metodo_pago_id");
        $metodosPago = $stmtMetodos->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['ok' => true, 'reservas' => $reservas, 'metodos_pago' => $metodosPago]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'mensaje' => 'Error al cargar reservas: ' . $e->getMessage()]);
    }
}

function editar(PDO $pdo, array $input): void
{
    $id = (int) ($input['reserva_id'] ?? 0);
    $estado = (int) ($input['reser_estado'] ?? 0);
    $observaciones = trim($input['reser_observaciones'] ?? '');

    if ($id <= 0 || $estado < 1 || $estado > 3) {
        echo json_encode(['ok' => false, 'mensaje' => 'Datos inválidos']);
        return;
    }

    $stmt = $pdo->prepare("
        UPDATE reservas
        SET reser_estado = ?, reser_observaciones = ?
        WHERE reserva_id = ?
    ");
    $stmt->execute([$estado, $observaciones ?: null, $id]);

    echo json_encode(['ok' => true, 'mensaje' => 'Reserva actualizada con éxito']);
}

function togglePago(PDO $pdo, array $input): void
{
    $reservaId = (int) ($input['reserva_id'] ?? 0);
    if ($reservaId <= 0) {
        echo json_encode(['ok' => false, 'mensaje' => 'ID inválido']);
        return;
    }

    $stmt = $pdo->prepare("
        SELECT f.factura_id, f.factura_estado, ca.cancha_precio
        FROM reservas r
        JOIN turnos t ON r.tur_id = t.tur_id
        JOIN canchas ca ON t.id_cancha = ca.cancha_id
        LEFT JOIN facturacion f ON r.reserva_id = f.reserva_id
        WHERE r.reserva_id = ?
    ");
    $stmt->execute([$reservaId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['ok' => false, 'mensaje' => 'Reserva no encontrada']);
        return;
    }

    if ($row['factura_id']) {
        $nuevoEstado = $row['factura_estado'] === 'Pagada' ? 'Pendiente' : 'Pagada';
        $stmt = $pdo->prepare("UPDATE facturacion SET factura_estado = ? WHERE factura_id = ?");
        $stmt->execute([$nuevoEstado, $row['factura_id']]);

        // If toggling to Pagada, register a payment for the full amount
        if ($nuevoEstado === 'Pagada') {
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM pagos WHERE factura_id = ?");
            $stmtCheck->execute([$row['factura_id']]);
            if ((int)$stmtCheck->fetchColumn() === 0) {
                $stmtPago = $pdo->prepare("
                    INSERT INTO pagos (factura_id, metodo_pago_id, pago_fecha_pago, pago_monto)
                    VALUES (?, 1, CURDATE(), ?)
                ");
                $stmtPago->execute([$row['factura_id'], $row['cancha_precio']]);
            }
        }
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO facturacion (reserva_id, factura_fecha_emision, factura_total, factura_estado)
            VALUES (?, CURDATE(), ?, 'Pagada')
        ");
        $stmt->execute([$reservaId, $row['cancha_precio']]);

        $facturaId = $pdo->lastInsertId();
        $stmtPago = $pdo->prepare("
            INSERT INTO pagos (factura_id, metodo_pago_id, pago_fecha_pago, pago_monto)
            VALUES (?, 1, CURDATE(), ?)
        ");
        $stmtPago->execute([$facturaId, $row['cancha_precio']]);

        echo json_encode(['ok' => true, 'mensaje' => 'Reserva marcada como Pagada']);
        return;
    }

    echo json_encode([
        'ok' => true,
        'mensaje' => $nuevoEstado === 'Pagada' ? 'Reserva marcada como Pagada' : 'Reserva marcada como Pendiente',
        'nuevo_estado' => $nuevoEstado
    ]);
}

function registrarPago(PDO $pdo, array $input): void
{
    $reservaId = (int) ($input['reserva_id'] ?? 0);
    $monto = (float) ($input['monto'] ?? 0);
    $metodoPagoId = (int) ($input['metodo_pago_id'] ?? 0);
    $fechaPago = trim($input['fecha_pago'] ?? date('Y-m-d'));

    if ($reservaId <= 0 || $monto <= 0 || $metodoPagoId <= 0) {
        echo json_encode(['ok' => false, 'mensaje' => 'Datos de pago inválidos']);
        return;
    }

    $stmt = $pdo->prepare("
        SELECT f.factura_id, f.factura_total, COALESCE(SUM(p.pago_monto), 0) AS total_pagado
        FROM reservas r
        JOIN turnos t ON r.tur_id = t.tur_id
        JOIN canchas ca ON t.id_cancha = ca.cancha_id
        LEFT JOIN facturacion f ON r.reserva_id = f.reserva_id
        LEFT JOIN pagos p ON f.factura_id = p.factura_id
        WHERE r.reserva_id = ?
        GROUP BY f.factura_id, f.factura_total
    ");
    $stmt->execute([$reservaId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || !$row['factura_id']) {
        // Create facturacion row first
        $stmtPrecio = $pdo->prepare("
            SELECT ca.cancha_precio
            FROM reservas r
            JOIN turnos t ON r.tur_id = t.tur_id
            JOIN canchas ca ON t.id_cancha = ca.cancha_id
            WHERE r.reserva_id = ?
        ");
        $stmtPrecio->execute([$reservaId]);
        $precio = $stmtPrecio->fetchColumn();

        $stmtFact = $pdo->prepare("
            INSERT INTO facturacion (reserva_id, factura_fecha_emision, factura_total, factura_estado)
            VALUES (?, CURDATE(), ?, 'Pendiente')
        ");
        $stmtFact->execute([$reservaId, $precio]);
        $facturaId = $pdo->lastInsertId();
    } else {
        $facturaId = (int) $row['factura_id'];
    }

    $stmtPago = $pdo->prepare("
        INSERT INTO pagos (factura_id, metodo_pago_id, pago_fecha_pago, pago_monto)
        VALUES (?, ?, ?, ?)
    ");
    $stmtPago->execute([$facturaId, $metodoPagoId, $fechaPago, $monto]);

    // Update factura_estado based on total pagado vs total
    $stmtTotales = $pdo->prepare("
        SELECT f.factura_total, COALESCE(SUM(p.pago_monto), 0) AS total_pagado
        FROM facturacion f
        LEFT JOIN pagos p ON f.factura_id = p.factura_id
        WHERE f.factura_id = ?
        GROUP BY f.factura_id, f.factura_total
    ");
    $stmtTotales->execute([$facturaId]);
    $totales = $stmtTotales->fetch(PDO::FETCH_ASSOC);

    $nuevoEstado = 'Pendiente';
    if ($totales) {
        if ((float)$totales['total_pagado'] >= (float)$totales['factura_total']) {
            $nuevoEstado = 'Pagada';
        } elseif ((float)$totales['total_pagado'] > 0) {
            $nuevoEstado = 'Seña';
        }
    }

    $stmtUpd = $pdo->prepare("UPDATE facturacion SET factura_estado = ? WHERE factura_id = ?");
    $stmtUpd->execute([$nuevoEstado, $facturaId]);

    echo json_encode([
        'ok' => true,
        'mensaje' => 'Pago registrado con éxito',
        'factura_estado' => $nuevoEstado
    ]);
}

function pagoHistorial(PDO $pdo, array $input): void
{
    $reservaId = (int) ($input['reserva_id'] ?? 0);
    if ($reservaId <= 0) {
        echo json_encode(['ok' => false, 'mensaje' => 'ID inválido']);
        return;
    }

    $stmt = $pdo->prepare("
        SELECT p.pago_id, p.pago_fecha_pago, p.pago_monto, mp.metodo_nombre
        FROM pagos p
        JOIN facturacion f ON p.factura_id = f.factura_id
        LEFT JOIN metodo_pago mp ON p.metodo_pago_id = mp.metodo_pago_id
        WHERE f.reserva_id = ?
        ORDER BY p.pago_fecha_pago DESC, p.pago_id DESC
    ");
    $stmt->execute([$reservaId]);
    $pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmtFact = $pdo->prepare("
        SELECT f.factura_id, f.factura_total, f.factura_estado
        FROM facturacion f
        WHERE f.reserva_id = ?
    ");
    $stmtFact->execute([$reservaId]);
    $factura = $stmtFact->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok' => true,
        'pagos' => $pagos,
        'factura' => $factura
    ]);
}
