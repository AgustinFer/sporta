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

    $stmt = $pdo->prepare("
        SELECT t.tur_hora_inicio, c.cliente_nombre, c.cliente_apellido, ca.cancha_numero
        FROM reservas r
        JOIN turnos t ON r.tur_id = t.tur_id
        JOIN clientes c ON r.cliente_id = c.cliente_id
        JOIN canchas ca ON t.id_cancha = ca.cancha_id
        WHERE t.tur_fecha = CURDATE() AND r.reser_estado != 3
        ORDER BY t.tur_hora_inicio
        LIMIT 5
    ");
    $stmt->execute();
    $proximosTurnos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM reservas r
        JOIN turnos t ON r.tur_id = t.tur_id
        WHERE t.tur_fecha = CURDATE() AND r.reser_estado != 3
    ");
    $stmt->execute();
    $turnosHoy = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(pago_monto), 0) FROM pagos
        WHERE pago_fecha_pago = CURDATE()
    ");
    $stmt->execute();
    $ingresos = (float)$stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT r.reserva_id, c.cliente_nombre, c.cliente_apellido,
               ca.cancha_numero, t.tur_fecha, t.tur_hora_inicio,
               COALESCE(f.factura_total, 0) as factura_total, COALESCE(p.total_pagado, 0) as total_pagado
        FROM reservas r
        JOIN clientes c ON r.cliente_id = c.cliente_id
        JOIN turnos t ON r.tur_id = t.tur_id
        JOIN canchas ca ON t.id_cancha = ca.cancha_id
        LEFT JOIN facturacion f ON r.reserva_id = f.reserva_id
        LEFT JOIN (
            SELECT factura_id, SUM(pago_monto) as total_pagado
            FROM pagos GROUP BY factura_id
        ) p ON f.factura_id = p.factura_id
        WHERE r.reser_estado != 3
          AND (f.factura_id IS NULL OR p.total_pagado IS NULL OR p.total_pagado < f.factura_total)
        ORDER BY t.tur_fecha, t.tur_hora_inicio
        LIMIT 5
    ");
    $stmt->execute();
    $reservasImpagasLista = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM reservas r
        LEFT JOIN facturacion f ON r.reserva_id = f.reserva_id
        LEFT JOIN (
            SELECT factura_id, SUM(pago_monto) as total_pagado
            FROM pagos GROUP BY factura_id
        ) p ON f.factura_id = p.factura_id
        WHERE r.reser_estado != 3
          AND (f.factura_id IS NULL OR p.total_pagado IS NULL OR p.total_pagado < f.factura_total)
    ");
    $stmt->execute();
    $reservasImpagasCount = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT p.pago_fecha_pago as fecha, COALESCE(SUM(p.pago_monto), 0) as total
        FROM pagos p
        WHERE p.pago_fecha_pago >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        GROUP BY p.pago_fecha_pago
        ORDER BY p.pago_fecha_pago
    ");
    $stmt->execute();
    $ingresos7dRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $ingresos7d = [];
    for ($i = 6; $i >= 0; $i--) {
        $fecha = date('Y-m-d', strtotime("-$i days"));
        $total = 0;
        foreach ($ingresos7dRaw as $row) {
            if ($row['fecha'] === $fecha) {
                $total = (float)$row['total'];
                break;
            }
        }
        $ingresos7d[] = ['fecha' => $fecha, 'total' => $total];
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM canchas WHERE cancha_estado = 1");
    $stmt->execute();
    $canchasActivas = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM clientes WHERE cliente_estado = 1");
    $stmt->execute();
    $clientes = (int)$stmt->fetchColumn();

    echo json_encode([
        'ok' => true,
        'proximos_turnos' => $proximosTurnos,
        'turnos_hoy' => $turnosHoy,
        'ingresos' => $ingresos,
        'canchas_activas' => $canchasActivas,
        'clientes' => $clientes,
        'reservas_impagas' => [
            'count' => $reservasImpagasCount,
            'lista' => $reservasImpagasLista
        ],
        'ingresos_7d' => $ingresos7d
    ]);

} catch (Exception $e) {
    loggear('error_excepcion', ['archivo' => 'dashboard.php', 'mensaje' => $e->getMessage()]);
    echo json_encode(['ok' => false, 'mensaje' => 'Error al obtener datos del dashboard']);
}
