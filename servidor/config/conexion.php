<?php

// Cargar variables de entorno desde .env
require_once __DIR__ . '/env.php';

function conexion(){
    $host = getenv('DB_HOST');
    $db   = getenv('DB_NAME');
    $user = getenv('DB_USER');
    $pass = getenv('DB_PASS');
    $charset = getenv('DB_CHARSET') ?: 'utf8';

    try {

        $pdo = new PDO(
            "mysql:host=$host;dbname=$db;charset=$charset",
            $user,
            $pass
        );

        $pdo->setAttribute(
            PDO::ATTR_ERRMODE,
            PDO::ERRMODE_EXCEPTION
        );

        return $pdo;

    } catch (PDOException $e) {

        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        http_response_code(500);
        die(json_encode(['ok' => false, 'mensaje' => 'Error de conexión a la base de datos']));

    }
}
