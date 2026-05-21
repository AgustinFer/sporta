<?php
// Hace que php no cambie automaticamente el tipo de las variables
// Sirvio para arreglar problemas en la toma de datos de la bbdd
declare(strict_types=1);

require_once __DIR__ . '/clases.php';

if (session_status() === PHP_SESSION_NONE) {
	session_start();
}
?>