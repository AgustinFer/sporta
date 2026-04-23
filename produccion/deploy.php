<?php

$token = 'sporta2026BFMO';

// Validar token (por ejemplo vía GET)
if (!isset($_GET['token']) || $_GET['token'] !== $token) {
    http_response_code(403);
    echo "Forbidden";
    exit;
}

// Ejecutar script
$output = [];
$return_var = 0;

exec('/usr/local/bin/deploy.sh 2>&1', $output, $return_var);

if ($return_var !== 0) {
    http_response_code(500);
    echo implode("\n", $output);
    exit;
}

echo "Deploy OK\n";
echo implode("\n", $output);