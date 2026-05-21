<?php

function conexion(){
$host = "localhost";
$db   = "sporta";
$user = "root";
$pass = "BJ/2StZxjvnc";

try {

    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8",
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