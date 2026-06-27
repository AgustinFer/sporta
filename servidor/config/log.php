<?php

function loggear($accion, $detalle = [])
{
    $logDir = __DIR__ . '/../log';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0775, true);
        @file_put_contents($logDir . '/.htaccess', "Deny from all\n");
    }

    $archivo = $logDir . '/' . date('Y-m-d') . '.log';

    $usuarioId = 0;
    $usuarioNombre = 'anonimo';
    if (isset($_SESSION['usuario'])) {
        $u = $_SESSION['usuario'];
        $usuarioId = $u->getId();
        $usuarioNombre = $u->getNombre() . ' ' . $u->getApellido();
    }

    $entry = [
        'ts' => date('Y-m-d H:i:s'),
        'user_id' => $usuarioId,
        'user_nombre' => trim($usuarioNombre),
        'accion' => $accion,
        'detalle' => $detalle,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
    ];

    $line = json_encode($entry, JSON_UNESCAPED_UNICODE) . "\n";
    @file_put_contents($archivo, $line, FILE_APPEND | LOCK_EX);
}
