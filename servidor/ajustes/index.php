<?php

require_once __DIR__ . '/../config/init.php';

if(!isset($_SESSION['usuario'])){
  header("Location: ../index.php");
  exit;
}

$pdo = conexion();
$usuario = $_SESSION['usuario'];
$userId = $usuario->getId();

/* ========================= */
/* AJAX: UNICIDAD DE USUARIO */
/* ========================= */

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['check_usuario'])) {
    $check = trim($_GET['check_usuario']);
    header('Content-Type: application/json');

    if ($check === '') {
        echo json_encode(['disponible' => false]);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM usuarios
        WHERE usu_usuario = :usuario
        AND usu_id != :id
    ");
    $stmt->execute([
        ':usuario' => $check,
        ':id' => $userId
    ]);

    echo json_encode(['disponible' => $stmt->fetchColumn() == 0]);
    exit;
}

/* ========================= */
/* CAMBIO DE USUARIO */
/* ========================= */

if (isset($_POST['cambio_usuario'])) {
    $nuevoUsuario = trim($_POST['nuevo_usuario'] ?? '');

    if ($nuevoUsuario === '') {
        $_SESSION['flash_error'] = "El nombre de usuario no puede estar vacío";
        header("Location: " . BASE_URL . "/ajustes/");
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM usuarios
        WHERE usu_usuario = :usuario
        AND usu_id != :id
    ");
    $stmt->execute([
        ':usuario' => $nuevoUsuario,
        ':id' => $userId
    ]);

    if ($stmt->fetchColumn() > 0) {
        $_SESSION['flash_error'] = "El nombre de usuario ya está en uso";
        header("Location: " . BASE_URL . "/ajustes/");
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE usuarios SET usu_usuario = :usuario WHERE usu_id = :id
    ");
    $stmt->execute([
        ':usuario' => $nuevoUsuario,
        ':id' => $userId
    ]);

    $_SESSION['usuario']->setUsuario($nuevoUsuario);

    $_SESSION['flash_success'] = "Nombre de usuario actualizado";
    header("Location: " . BASE_URL . "/ajustes/");
    exit;
}

/* ========================= */
/* CAMBIO DE CONTRASEÑA */
/* ========================= */

if (isset($_POST['cambio_contrasena'])) {
    $passActual = $_POST['pass_actual'] ?? '';
    $passNueva  = $_POST['pass_nueva'] ?? '';
    $passConfirmar = $_POST['pass_confirmar'] ?? '';

    $stmt = $pdo->prepare("
        SELECT usu_contrasena FROM usuarios WHERE usu_id = :id
    ");
    $stmt->execute([':id' => $userId]);
    $hashActual = $stmt->fetchColumn();

    if (!password_verify($passActual, $hashActual)) {
        $_SESSION['flash_error'] = "La contraseña actual no es correcta";
        header("Location: " . BASE_URL . "/ajustes/");
        exit;
    }

    if ($passNueva !== $passConfirmar) {
        $_SESSION['flash_error'] = "Las contraseñas no coinciden";
        header("Location: " . BASE_URL . "/ajustes/");
        exit;
    }

    if (strlen($passNueva) < 6) {
        $_SESSION['flash_error'] = "La contraseña debe tener al menos 6 caracteres";
        header("Location: " . BASE_URL . "/ajustes/");
        exit;
    }

    if (!preg_match('/[A-Z]/', $passNueva)) {
        $_SESSION['flash_error'] = "La contraseña debe contener al menos una mayúscula";
        header("Location: " . BASE_URL . "/ajustes/");
        exit;
    }

    if (!preg_match('/[^a-zA-Z0-9]/', $passNueva)) {
        $_SESSION['flash_error'] = "La contraseña debe contener al menos un caracter especial";
        header("Location: " . BASE_URL . "/ajustes/");
        exit;
    }

    $hash = password_hash($passNueva, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        UPDATE usuarios SET usu_contrasena = :hash WHERE usu_id = :id
    ");
    $stmt->execute([
        ':hash' => $hash,
        ':id' => $userId
    ]);

    $_SESSION['flash_success'] = "Contraseña actualizada correctamente";
    header("Location: " . BASE_URL . "/ajustes/");
    exit;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sporta - Ajustes</title>

  <!-- CSS GLOBAL -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/recursos/css/global.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/recursos/css/layout.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/recursos/css/componentes.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/recursos/css/drawer.css">

  <!-- CSS MÓDULO -->
  <link id="page-style" rel="stylesheet" href="<?= BASE_URL ?>/ajustes/ajustes.css">
</head>

<body class="screen" data-page="Ajustes">

  <!-- FONDO -->
  <div class="background"></div>

  <!-- SIDEBAR DINÁMICO -->
  <div id="sidebar-container"></div>

  <!-- BOTÓN MOBILE -->
  <button class="menu-toggle" id="menuToggle">☰</button>

  <!-- CONTENIDO -->
  <main class="main-content">

    <!-- HEADER DINÁMICO -->
    <div id="header-container"></div>

    <div class="settings-container">

      <h2>Ajustes</h2>
      <p>Configuración de tu cuenta</p>

      <!-- USUARIO -->
      <div class="settings-card">
        <h3>Nombre de usuario</h3>
        <form method="POST" id="formUsuario" action="<?= BASE_URL ?>/ajustes/" novalidate>
          <div class="field">
            <label for="nuevo_usuario">Nuevo nombre de usuario</label>
            <input
              type="text"
              name="nuevo_usuario"
              id="nuevo_usuario"
              value="<?= htmlspecialchars($usuario->getUsuario()) ?>"
              required
              autocomplete="username"
            >
            <span class="field-error" id="error_usuario"></span>
            <span class="field-ok" id="ok_usuario"></span>
          </div>
          <button type="submit" name="cambio_usuario" class="btn-settings">
            Guardar usuario
          </button>
        </form>
      </div>

      <!-- CONTRASEÑA -->
      <div class="settings-card">
        <h3>Contraseña</h3>
        <form method="POST" id="formPassword" action="<?= BASE_URL ?>/ajustes/" novalidate>
          <div class="field">
            <label for="pass_actual">Contraseña actual</label>
            <input
              type="password"
              name="pass_actual"
              id="pass_actual"
              required
              autocomplete="current-password"
            >
          </div>
          <div class="field">
            <label for="pass_nueva">Nueva contraseña</label>
            <input
              type="password"
              name="pass_nueva"
              id="pass_nueva"
              required
              autocomplete="new-password"
            >
            <ul class="password-requirements">
              <li id="req-length">Mínimo 6 caracteres</li>
              <li id="req-upper">Una mayúscula</li>
              <li id="req-special">Un caracter especial</li>
            </ul>
          </div>
          <div class="field">
            <label for="pass_confirmar">Confirmar contraseña</label>
            <input
              type="password"
              name="pass_confirmar"
              id="pass_confirmar"
              required
              autocomplete="new-password"
            >
            <span class="field-error" id="error_password"></span>
          </div>
          <button type="submit" name="cambio_contrasena" class="btn-settings">
            Cambiar contraseña
          </button>
        </form>
      </div>

    </div>

  </main>

  <!-- TOAST -->
  <?php if (isset($_SESSION['flash_success']) || isset($_SESSION['flash_error'])): ?>
  <div id="toast-container">
    <?php if (isset($_SESSION['flash_success'])): ?>
    <div class="toast toast-success">
      <?= $_SESSION['flash_success'] ?>
      <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
    </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['flash_error'])): ?>
    <div class="toast toast-error">
      <?= $_SESSION['flash_error'] ?>
      <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
    </div>
    <?php endif; ?>
  </div>
  <script>
    setTimeout(function(){
      document.querySelectorAll('.toast').forEach(function(t) {
        t.classList.add('toast-hiding');
        setTimeout(function(){ if (t.parentElement) t.remove(); }, 300);
      });
    }, 3500);
  </script>
  <?php unset($_SESSION['flash_success']); ?>
  <?php unset($_SESSION['flash_error']); ?>
  <?php endif; ?>

  <!-- INICIO CONFIG GLOBAL -->
  <div id="drawer-container"></div>

  <script>
    const BASE_URL = "<?= BASE_URL ?>";
  </script>

  <script src="<?= BASE_URL ?>/recursos/js/layout.js"></script>
  <script src="<?= BASE_URL ?>/recursos/js/table.js"></script>
  <script src="<?= BASE_URL ?>/recursos/js/drawer.js"></script>
  <script>initAjustes();</script>

</body>
</html>
