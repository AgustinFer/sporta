<?php

// Hace que php no cambie automaticamente el tipo de las variables
// Sirvio para arreglar problemas en la toma de datos de la bbdd

declare(strict_types=1);

/* ========================= */
/* 🔧 BASE URL */
/* ========================= */

/*
|--------------------------------------------------------------------------
| CAMBIAR SEGÚN ENTORNO
|--------------------------------------------------------------------------
|
| LOCAL EN SUBCARPETA:
| define('BASE_URL', '/sporta');
|
| PRODUCCIÓN EN RAÍZ:
| define('BASE_URL', '');
|
*/

define('BASE_URL', '');

/* ========================= */
/* 🌎 TIMEZONE */
/* ========================= */

date_default_timezone_set('America/Argentina/Buenos_Aires');

/* ========================= */
/* 📦 CLASES */
/* ========================= */

require_once __DIR__ . '/clases.php';

/* ========================= */
/* 🔐 SESSION */
/* ========================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
