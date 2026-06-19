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

$accion = $_GET['accion'] ?? '';

try {

    if ($accion === 'listar') {
        $stmt = $pdo->query("SELECT * FROM metodo_pago WHERE metodo_estado = 1 ORDER BY metodo_pago_id");
        $metodos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['ok' => true, 'metodos_pago' => $metodos]);
    } else {
        echo json_encode(['ok' => false, 'mensaje' => 'Acción inválida']);
    }

} catch (Exception $e) {
    echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
}
