<?php
/**
 * Cargador simple de variables de entorno desde .env
 */
function loadEnv($envPath = null) {

    if ($envPath === null) {

        $possiblePaths = [
            __DIR__ . '/.env',         // mismo directorio
            __DIR__ . '/../.env',      // 1 nivel arriba
            __DIR__ . '/../../.env'    // 2 niveles arriba
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $envPath = $path;
                break;
            }
        }

        if ($envPath === null) {
            throw new Exception(
                "Archivo .env no encontrado. Rutas buscadas: " .
                implode(', ', $possiblePaths)
            );
        }
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
