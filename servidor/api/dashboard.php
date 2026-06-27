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
        'clientes' => $clientes
    ]);

} catch (Exception $e) {
    echo json_encode(['ok' => false, 'mensaje' => 'Error al obtener datos del dashboard']);
}
