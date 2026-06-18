<?php

require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/conexion.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido']);
    exit;
}

$email = trim($_POST['usuario'] ?? '');
$pass = $_POST['password'] ?? '';

if (empty($email) || empty($pass)) {
    $campo = empty($email) && empty($pass) ? null : (empty($email) ? 'usuario' : 'password');
    echo json_encode(['ok' => false, 'mensaje' => 'Completá todos los campos', 'campo' => $campo]);
    exit;
}

$con = conexion();

$sql = "
SELECT
    u.usu_id, u.usu_usuario, u.usu_contrasena,
    u.usu_nombre, u.usu_apellido, u.usu_email,
    u.usu_celular, u.usu_dni, u.usu_direccion,
    u.usu_fecha_alta, u.usu_estado,
    r.rol_nombre,
    l.localidad_nombre,
    p.provincia_nombre,
    pa.pais_nombre
FROM usuarios u
INNER JOIN roles r ON u.usu_rol = r.rol_id
LEFT JOIN localidades l ON u.usu_localidad_id = l.localidad_id
LEFT JOIN provincias p ON u.usu_provincia_id = p.provincia_id
LEFT JOIN paises pa ON u.usu_pais_id = pa.pais_id
WHERE (u.usu_email = :usuario OR BINARY u.usu_usuario = :usuario)
AND u.usu_estado = 1
";

$stmt = $con->prepare($sql);
$stmt->bindParam(':usuario', $email, PDO::PARAM_STR);
$stmt->execute();

if ($stmt->rowCount() !== 1) {
    echo json_encode(['ok' => false, 'mensaje' => 'Usuario no encontrado', 'campo' => 'usuario']);
    exit;
}

$datos = $stmt->fetch(PDO::FETCH_ASSOC);

if (!password_verify($pass, $datos['usu_contrasena'])) {
    echo json_encode(['ok' => false, 'mensaje' => 'Contraseña incorrecta', 'campo' => 'password']);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['usuario'] = new Usuario(
    $datos['usu_id'],
    $datos['usu_nombre'],
    $datos['usu_apellido'],
    $datos['usu_email'],
    $datos['usu_celular'],
    $datos['usu_dni'],
    $datos['usu_usuario'],
    $datos['rol_nombre'],
    $datos['usu_direccion'],
    $datos['localidad_nombre']
);

echo json_encode([
    'ok' => true,
    'redirect' => BASE_URL . '/inicio/'
]);
