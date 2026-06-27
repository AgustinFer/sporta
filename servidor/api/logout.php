<?php

require_once __DIR__ . '/../config/init.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

loggear('logout');

session_unset();
session_destroy();

header('Location: ' . BASE_URL . '/');
exit;
