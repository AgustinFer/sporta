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

        case 'factura_detalle':
            facturaDetalle($pdo, $input);
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
    $stmt = $pdo->query("
        SELECT
            p.pago_id,
            p.pago_fecha_pago,
            p.pago_monto,
            mp.metodo_nombre,
            f.factura_id,
            f.factura_total,
            f.factura_estado,
            CONCAT(c.cliente_nombre, ' ', c.cliente_apellido) AS cliente_nombre,
            ca.cancha_numero,
            t.tur_fecha,
            t.tur_hora_inicio
        FROM pagos p
        JOIN facturacion f ON p.factura_id = f.factura_id
        JOIN reservas r ON f.reserva_id = r.reserva_id
        JOIN turnos t ON r.tur_id = t.tur_id
        JOIN canchas ca ON t.id_cancha = ca.cancha_id
        LEFT JOIN clientes c ON r.cliente_id = c.cliente_id
        LEFT JOIN metodo_pago mp ON p.metodo_pago_id = mp.metodo_pago_id
        ORDER BY p.pago_fecha_pago DESC, p.pago_id DESC
    ");
    $pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok' => true,
        'pagos' => $pagos
    ]);
}

function facturaDetalle(PDO $pdo, array $input): void
{
    $facturaId = (int) ($input['factura_id'] ?? 0);
    if ($facturaId <= 0) {
        echo json_encode(['ok' => false, 'mensaje' => 'ID inválido']);
        return;
    }

    $stmt = $pdo->prepare("
        SELECT
            f.factura_id,
            f.factura_fecha_emision,
            f.factura_total,
            f.factura_estado,
            CONCAT(c.cliente_nombre, ' ', c.cliente_apellido) AS cliente_nombre,
            ca.cancha_numero,
            t.tur_fecha,
            t.tur_hora_inicio,
            COALESCE(pg.total_pagado, 0) AS total_pagado
        FROM facturacion f
        JOIN reservas r ON f.reserva_id = r.reserva_id
        JOIN turnos t ON r.tur_id = t.tur_id
        JOIN canchas ca ON t.id_cancha = ca.cancha_id
        LEFT JOIN clientes c ON r.cliente_id = c.cliente_id
        LEFT JOIN (
            SELECT factura_id, SUM(pago_monto) AS total_pagado
            FROM pagos
            GROUP BY factura_id
        ) pg ON f.factura_id = pg.factura_id
        WHERE f.factura_id = ?
    ");
    $stmt->execute([$facturaId]);
    $factura = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$factura) {
        echo json_encode(['ok' => false, 'mensaje' => 'Factura no encontrada']);
        return;
    }

    $stmtPagos = $pdo->prepare("
        SELECT p.pago_id, p.pago_fecha_pago, p.pago_monto, mp.metodo_nombre
        FROM pagos p
        LEFT JOIN metodo_pago mp ON p.metodo_pago_id = mp.metodo_pago_id
        WHERE p.factura_id = ?
        ORDER BY p.pago_fecha_pago DESC, p.pago_id DESC
    ");
    $stmtPagos->execute([$facturaId]);
    $factura['pagos'] = $stmtPagos->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok' => true,
        'factura' => $factura
    ]);
}
