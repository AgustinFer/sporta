<?php

require_once __DIR__ . '/../config/init.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']);
    exit;
}

$url = getenv('RELOJ_API_URL') ?: 'http://34.227.17.167/api/reloj.php';
$user = getenv('RELOJ_API_USER') ?: 'alumno';
$pass = getenv('RELOJ_API_PASS') ?: 'Cambiar-Esta-Clave-2026';

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPGET => true,
    CURLOPT_USERPWD => $user . ':' . $pass,
    CURLOPT_HTTPHEADER => ['Accept: application/json'],
    CURLOPT_TIMEOUT => 8,
]);
$body = curl_exec($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch);
curl_close($ch);

if ($body === false || $httpCode !== 200) {
    $detalle = $curlErr !== '' ? $curlErr : 'HTTP ' . $httpCode;
    echo json_encode(['ok' => false, 'mensaje' => 'No se pudo obtener la hora oficial (' . $detalle . ')']);
    exit;
}

$data = json_decode($body, true);
if (!is_array($data) || !isset($data['hora'])) {
    echo json_encode(['ok' => false, 'mensaje' => 'Respuesta inválida de la API']);
    exit;
}

$data['ok'] = true;
echo json_encode($data);
