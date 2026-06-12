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

if (!empty($_POST['cambio_usuario'])) {
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

    $_SESSION['flash_success'] = "Nombre de usuario actualizado";
    header("Location: " . BASE_URL . "/ajustes/");
    exit;
}

/* ========================= */
/* CAMBIO DE CONTRASEÑA */
/* ========================= */

if (!empty($_POST['cambio_contrasena'])) {
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

      <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="flash-error"><?= $_SESSION['flash_error'] ?></div>
        <?php unset($_SESSION['flash_error']); ?>
      <?php endif; ?>

      <!-- USUARIO -->
      <div class="settings-card">
        <h3>Nombre de usuario</h3>
        <form method="POST" id="formUsuario" novalidate>
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
        <form method="POST" id="formPassword" novalidate>
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

    <?php if (isset($_SESSION['flash_success'])): ?>
    <div id="toast-container">
      <div class="toast toast-success">
        <?= $_SESSION['flash_success'] ?>
        <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
      </div>
    </div>
    <script>
      setTimeout(function(){
        var t = document.querySelector('.toast');
        if (t) {
          t.classList.add('toast-hiding');
          setTimeout(function(){ if (t.parentElement) t.remove(); }, 300);
        }
      }, 3500);
    </script>
    <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

  </main>

  <!-- INICIO CONFIG GLOBAL -->
  <div id="drawer-container"></div>

  <script>
    const BASE_URL = "<?= BASE_URL ?>";
  </script>

  <script src="<?= BASE_URL ?>/recursos/js/layout.js"></script>
  <script src="<?= BASE_URL ?>/recursos/js/table.js"></script>
  <script src="<?= BASE_URL ?>/recursos/js/drawer.js"></script>

  <script>
  (function() {

    /* ========================= */
    /* VALIDACIÓN CONTRASEÑA */
    /* ========================= */

    var passInput = document.getElementById("pass_nueva");
    var passConfirm = document.getElementById("pass_confirmar");
    var errorPass = document.getElementById("error_password");

    var reqLength = document.getElementById("req-length");
    var reqUpper  = document.getElementById("req-upper");
    var reqSpecial = document.getElementById("req-special");

    var reqList = [reqLength, reqUpper, reqSpecial];

    function validarPassword(valor) {
      var cumple = {
        length: valor.length >= 6,
        upper: /[A-Z]/.test(valor),
        special: /[^a-zA-Z0-9]/.test(valor)
      };

      reqLength.classList.toggle("req-ok", cumple.length);
      reqUpper.classList.toggle("req-ok", cumple.upper);
      reqSpecial.classList.toggle("req-ok", cumple.special);

      return cumple.length && cumple.upper && cumple.special;
    }

    function validarConfirmacion() {
      if (!passConfirm.value) {
        errorPass.textContent = "";
        errorPass.classList.remove("visible");
        return true;
      }
      if (passConfirm.value !== passInput.value) {
        errorPass.textContent = "Las contraseñas no coinciden";
        errorPass.classList.add("visible");
        return false;
      }
      errorPass.textContent = "";
      errorPass.classList.remove("visible");
      return true;
    }

    if (passInput) {
      passInput.addEventListener("input", function() {
        validarPassword(passInput.value);
        if (passConfirm.value) validarConfirmacion();
      });
    }

    if (passConfirm) {
      passConfirm.addEventListener("input", validarConfirmacion);
    }

    /* ========================= */
    /* VALIDACIÓN USUARIO (AJAX) */
    /* ========================= */

    var usuarioInput = document.getElementById("nuevo_usuario");
    var errorUsuario = document.getElementById("error_usuario");
    var okUsuario   = document.getElementById("ok_usuario");
    var usuarioOriginal = usuarioInput ? usuarioInput.value : "";
    var checkTimeout = null;

    if (usuarioInput) {

      usuarioInput.addEventListener("input", function() {
        var val = usuarioInput.value.trim();

        if (val === usuarioOriginal) {
          errorUsuario.textContent = "";
          errorUsuario.classList.remove("visible");
          okUsuario.textContent = "";
          return;
        }

        if (val.length < 3) {
          errorUsuario.textContent = "Mínimo 3 caracteres";
          errorUsuario.classList.add("visible");
          okUsuario.textContent = "";
          return;
        }

        if (checkTimeout) clearTimeout(checkTimeout);

        checkTimeout = setTimeout(function() {
          fetch(BASE_URL + "/ajustes/?check_usuario=" + encodeURIComponent(val))
            .then(function(r) { return r.json(); })
            .then(function(data) {
              if (data.disponible) {
                errorUsuario.textContent = "";
                errorUsuario.classList.remove("visible");
                okUsuario.textContent = "✓ Disponible";
              } else {
                errorUsuario.textContent = "El nombre de usuario ya está en uso";
                errorUsuario.classList.add("visible");
                okUsuario.textContent = "";
              }
            })
            .catch(function() {
              errorUsuario.textContent = "Error al verificar";
              errorUsuario.classList.add("visible");
            });
        }, 400);
      });
    }

    /* ========================= */
    /* SUBMIT PASSWORD */
    /* ========================= */

    var formPass = document.getElementById("formPassword");
    if (formPass) {
      formPass.addEventListener("submit", function(e) {
        var validaPass = validarPassword(passInput.value);
        var validaConf = validarConfirmacion();

        if (!validaPass || !validaConf) {
          e.preventDefault();
          if (!validaPass) passInput.focus();
        }
      });
    }

    /* ========================= */
    /* SUBMIT USUARIO */
    /* ========================= */

    var formUser = document.getElementById("formUsuario");
    if (formUser) {
      formUser.addEventListener("submit", function(e) {
        var val = usuarioInput.value.trim();
        if (val === usuarioOriginal) {
          e.preventDefault();
          return;
        }
        if (val.length < 3) {
          e.preventDefault();
          errorUsuario.textContent = "Mínimo 3 caracteres";
          errorUsuario.classList.add("visible");
          usuarioInput.focus();
          return;
        }
        if (errorUsuario.classList.contains("visible")) {
          e.preventDefault();
          usuarioInput.focus();
        }
      });
    }

  })();
  </script>

</body>
</html>
