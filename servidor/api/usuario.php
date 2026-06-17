<?php

require_once __DIR__ . '/../config/init.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['ok' => false]);
    exit;
}

$user = $_SESSION['usuario'];

echo json_encode([
    'ok' => true,
    'usuario' => [
        'id' => $user->getId(),
        'nombre' => $user->getNombre(),
        'apellido' => $user->getApellido(),
        'email' => $user->getEmail(),
        'usuario' => $user->getUsuario(),
        'rol' => $user->getRol(),
        'isAdmin' => $user->isAdmin()
    ]
]);
