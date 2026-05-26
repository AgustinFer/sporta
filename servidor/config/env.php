<?php
/**
 * Cargador simple de variables de entorno desde .env
 */
function loadEnv($envPath = null) {
    if ($envPath === null) {
        // Buscar .env en la raíz del proyecto (3 niveles arriba de config/)
        $envPath = __DIR__ . '/../.env';
    }

    if (!file_exists($envPath)) {
        throw new Exception("Archivo .env no encontrado en: $envPath, probando un directorio más arriba..");
        $envPath = __DIR__ . '/../../.env';

    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        // Ignorar comentarios
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parsear KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remover comillas si las hay
            if ((strpos($value, '"') === 0 && strpos($value, '"') === strlen($value) - 1) ||
                (strpos($value, "'") === 0 && strpos($value, "'") === strlen($value) - 1)) {
                $value = substr($value, 1, -1);
            }

            // Asignar a $_ENV y putenv
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// Cargar .env automáticamente al incluir este archivo
loadEnv();
?>
