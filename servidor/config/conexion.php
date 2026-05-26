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

        die("Error DB: " . $e->getMessage());

    }
}
