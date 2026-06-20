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

        $isApi = isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') !== false;
        if ($isApi) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['ok' => false, 'mensaje' => 'Error de conexion a la base de datos']);
            exit;
        }
        die("Error DB: " . $e->getMessage());

    }
}
