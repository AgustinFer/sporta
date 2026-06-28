<?php

require_once __DIR__ . '/../config/init.php';

ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
    exit;
}

if (!$_SESSION['usuario']->isAdmin()) {
    echo json_encode(['ok' => false, 'mensaje' => 'Acceso denegado']);
    exit;
}

$accion = $_GET['accion'] ?? '';

$logDir = __DIR__ . '/../log';

switch ($accion) {

    case 'listar':
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            echo json_encode(['ok' => false, 'mensaje' => 'Formato de fecha inválido']);
            exit;
        }
        $archivo = $logDir . '/' . $fecha . '.log';
        $logs = [];
        if (file_exists($archivo)) {
            $lineas = file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lineas as $linea) {
                $entry = json_decode($linea, true);
                if ($entry) {
                    $logs[] = $entry;
                }
            }
        }
        echo json_encode(['ok' => true, 'logs' => $logs]);
        break;

    default:
        echo json_encode(['ok' => false, 'mensaje' => 'Acción inválida']);
        break;
}
