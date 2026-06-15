<?php

require_once __DIR__ . '/../config/init.php';

if (!isset($_SESSION['usuario'])) {

  header("Location: ../index.php");
  exit;

}

$esAdmin =
  isset($_SESSION['usuario']) &&
  $_SESSION['usuario']->isAdmin();

require_once __DIR__ . '/../config/conexion.php';

$pdo = conexion();

/* ========================= */
/* 🗑️ ELIMINAR CLIENTE */
/* ========================= */

if (isset($_POST["toggle_cliente_id"])) {

    $id = (int) $_POST["toggle_cliente_id"];

    // Traemos estado actual
    $stmt = $pdo->prepare("
        SELECT cliente_estado
        FROM clientes
        WHERE cliente_id = ?
    ");
    $stmt->execute([$id]);

    $actual = $stmt->fetchColumn();

    // invertimos estado
    $nuevoEstado = ((int)$actual === 1) ? 0 : 1;

    $stmt = $pdo->prepare("
        UPDATE clientes
        SET cliente_estado = ?
        WHERE cliente_id = ?
    ");

    $stmt->execute([$nuevoEstado, $id]);

    $_SESSION['flash_success'] = $nuevoEstado === 1
        ? "Cliente activado con éxito"
        : "Cliente inactivado con éxito";
    header("Location: " . BASE_URL . "/clientes/");
    exit;
}

function validarDatosCliente($nombre, $apellido, $email, $celular, $dni) {
    $errores = [];

    if (!preg_match('/^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s]+$/', $nombre)) {
        $errores[] = "El nombre contiene caracteres inválidos";
    }
    if (!preg_match('/^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s]+$/', $apellido)) {
        $errores[] = "El apellido contiene caracteres inválidos";
    }
    if ($dni !== "" && !preg_match('/^\d+$/', $dni)) {
        $errores[] = "El DNI solo debe contener números";
    }
    if ($celular !== "" && !preg_match('/^[\d\s\+\-\(\)]+$/', $celular)) {
        $errores[] = "El teléfono contiene caracteres inválidos";
    }
    if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El email no tiene un formato válido";
    }

    if (strlen($nombre) > 100) {
        $errores[] = "El nombre no puede superar los 100 caracteres";
    }
    if (strlen($apellido) > 100) {
        $errores[] = "El apellido no puede superar los 100 caracteres";
    }
    if (strlen($email) > 100) {
        $errores[] = "El email no puede superar los 100 caracteres";
    }
    if (strlen($celular) > 20) {
        $errores[] = "El teléfono no puede superar los 20 caracteres";
    }
    if (strlen($dni) > 20) {
        $errores[] = "El DNI no puede superar los 20 caracteres";
    }

    return $errores;
}

/* ========================= */
/* ✏️ MODIFICAR CLIENTE */
/* ========================= */

if (!empty($_POST["edit_cliente_id"])) {

    $id = (int) $_POST["edit_cliente_id"];

    $nombre = trim($_POST["cliente_nombre"] ?? "");
    $apellido = trim($_POST["cliente_apellido"] ?? "");
    $email = trim($_POST["cliente_email"] ?? "");
    $celular = trim($_POST["cliente_celular"] ?? "");
    $dni = trim($_POST["cliente_dni"] ?? "");

    if (
        !empty($nombre) &&
        !empty($apellido)
    ) {

        $errores = validarDatosCliente($nombre, $apellido, $email, $celular, $dni);

        if (empty($errores)) {

            $stmt = $pdo->prepare("
                UPDATE clientes
                SET
                    cliente_nombre = ?,
                    cliente_apellido = ?,
                    cliente_email = ?,
                    cliente_celular = ?,
                    cliente_dni = ?
                WHERE cliente_id = ?
            ");

            $stmt->execute([
                $nombre,
                $apellido,
                $email ?: null,
                $celular ?: null,
                $dni ?: null,
                $id
            ]);

            $_SESSION['flash_success'] = "Cliente modificado con éxito";
            header("Location: " . BASE_URL . "/clientes/");
            exit;

        }

        $_SESSION['flash_error'] = implode("<br>", $errores);
        header("Location: " . BASE_URL . "/clientes/");
        exit;

    }

}

/* ========================= */
/* 📥 ALTA CLIENTE */
/* ========================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    empty($_POST["edit_cliente_id"]) &&
    !isset($_POST["delete_cliente_id"])
) {

    $nombre = trim($_POST["cliente_nombre"] ?? "");
    $apellido = trim($_POST["cliente_apellido"] ?? "");
    $email = trim($_POST["cliente_email"] ?? "");
    $celular = trim($_POST["cliente_celular"] ?? "");
    $dni = trim($_POST["cliente_dni"] ?? "");

    if (
        !empty($nombre) &&
        !empty($apellido)
    ) {

        $errores = validarDatosCliente($nombre, $apellido, $email, $celular, $dni);

        if (empty($errores)) {

            $stmt = $pdo->prepare("
                INSERT INTO clientes (
                    cliente_nombre,
                    cliente_apellido,
                    cliente_email,
                    cliente_celular,
                    cliente_dni,
                    cliente_estado,
                    cliente_localidad_id,
                    cliente_provincia_id,
                    cliente_pais_id
                )
                VALUES (
                    ?, ?, ?, ?, ?, 1, 1, 1, 1
                )
            ");

            $stmt->execute([
                $nombre,
                $apellido,
                $email ?: null,
                $celular ?: null,
                $dni ?: null
            ]);

            $_SESSION['flash_success'] = "Cliente agregado con éxito";
            header("Location: " . BASE_URL . "/clientes/");
            exit;

        }

        $_SESSION['flash_error'] = implode("<br>", $errores);
        header("Location: " . BASE_URL . "/clientes/");
        exit;

    }

}

/* ========================= */
/* 📋 LISTADO */
/* ========================= */

$stmt = $pdo->query("
    SELECT *
    FROM clientes
    ORDER BY cliente_id
");

$clientes = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sporta - Clientes</title>

  <!-- CSS GLOBAL -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/recursos/css/global.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/recursos/css/layout.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/recursos/css/componentes.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/recursos/css/drawer.css">

  <!-- CSS MÓDULO -->
  <link id="page-style" rel="stylesheet" href="<?= BASE_URL ?>/clientes/clientes.css">
  <link rel="icon" href="<?= BASE_URL ?>/recursos/img/favicon.ico">
</head>

<body class="screen" data-page="Clientes" data-drawer="clientes">

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

    <!-- CABECERA -->
    <div class="list-container">

      <h2>Clientes</h2>

      <p>
        Listado de clientes registrados
      </p>

    </div>

    <?php if (isset($_SESSION['flash_error'])): ?>
      <div class="flash-error">
        <?= $_SESSION['flash_error'] ?>
      </div>
      <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <!-- TOOLBAR -->
    <div class="table-toolbar">

      <input
        type="text"
        id="tableSearch"
        class="table-search"
        placeholder="Buscar cliente..."
      >

      <label class="filter-inactivos">
        <input
          type="checkbox"
          id="showInactivos"
        >
        Mostrar inactivos
      </label>

    </div>

    <!-- LISTADO -->
    <!-- TABLA -->
    <div class="table-container">

      <table class="table">
        <thead>

          <tr>

            <th data-sort="0" data-column="id">ID</th>
            <th data-sort="1" data-column="nombre">Nombre</th>
            <th data-sort="2" data-column="apellido">Apellido</th>
            <th data-sort="3" data-column="email">Email</th>
            <th data-sort="4" data-column="celular">Celular</th>
            <th data-sort="5" data-column="dni">DNI</th>
            <th data-sort="6" data-column="estado">Estado</th>
            <th data-column="acciones">Acciones</th>

          </tr>

        </thead>

        <tbody>

          <?php if (count($clientes) > 0): ?>

            <?php foreach ($clientes as $cliente): ?>

              <tr>

                <td data-column="id">
                  <?= htmlspecialchars(
                    $cliente["cliente_id"]
                  ) ?>
                </td>

                <td data-column="nombre">
                  <?= htmlspecialchars(
                    $cliente["cliente_nombre"]
                  ) ?>
                </td>

                <td data-column="apellido">
                  <?= htmlspecialchars(
                    $cliente["cliente_apellido"]
                  ) ?>
                </td>

                <td data-column="email">
                  <?= htmlspecialchars(
                    $cliente["cliente_email"] ?? "-"
                  ) ?>
                </td>

                <td data-column="celular">
                  <?= htmlspecialchars(
                    $cliente["cliente_celular"] ?? "-"
                  ) ?>
                </td>

                <td data-column="dni">
                  <?= htmlspecialchars(
                    $cliente["cliente_dni"] ?? "-"
                  ) ?>
                </td>

                <td data-column="estado">
                  <?php if ((int)$cliente["cliente_estado"] === 1): ?>
                    <span class="status active">Activo</span>
                  <?php else: ?>
                    <span class="status inactive">Inactivo</span>
                  <?php endif; ?>
                </td>

                <!-- ACCIONES -->
                <td data-column="acciones">

                  <div class="table-actions">
                    <button
                      type="button"
                      class="edit-btn"
                      data-id="<?= $cliente["cliente_id"] ?>"
                      data-nombre="<?= htmlspecialchars($cliente["cliente_nombre"]) ?>"
                      data-apellido="<?= htmlspecialchars($cliente["cliente_apellido"]) ?>"
                      data-email="<?= htmlspecialchars($cliente["cliente_email"] ?? "") ?>"
                      data-celular="<?= htmlspecialchars($cliente["cliente_celular"] ?? "") ?>"
                      data-dni="<?= htmlspecialchars($cliente["cliente_dni"] ?? "") ?>"
                    >
                      Modificar
                    </button>

                    <form method="POST">
                      <input
                        type="hidden"
                        name="toggle_cliente_id"
                        value="<?= $cliente["cliente_id"] ?>"
                      >

                      <button
                        type="submit"
                        class="<?= ((int)$cliente["cliente_estado"] === 1) ? 'deactivate-btn' : 'activate-btn' ?>"
                        onclick="return confirm('¿Cambiar estado del cliente?')"
                      >
                        <?= ((int)$cliente["cliente_estado"] === 1) ? 'Inactivar' : 'Activar' ?>
                      </button>

                    </form>

                  </div>

                </td>

              </tr>

            <?php endforeach; ?>

          <?php else: ?>

            <tr>

              <td colspan="8">

                No hay clientes registrados

              </td>

            </tr>

          <?php endif; ?>

        </tbody>

      </table>

    </div>

    <!-- FAB -->
    <button class="fab">+</button>

  </main>

  <!-- INICIO CONFIG GLOBAL -->
  <!-- DRAWER (aunque no se use) -->
  <div id="drawer-container"></div>

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

  <script>
    const BASE_URL = "<?= BASE_URL ?>";
  </script>

  <!-- JS LAYOUT (carga sidebar + header) -->
  <script src="<?= BASE_URL ?>/recursos/js/layout.js"></script>

  <!-- JS TABLA -->
  <script src="<?= BASE_URL ?>/recursos/js/table.js"></script>

  <!-- JS DRAWER -->
  <script src="<?= BASE_URL ?>/recursos/js/drawer.js"></script>

  <!-- JS MÓDULOS -->
  <script src="<?= BASE_URL ?>/recursos/js/turnos.js"></script>
  <script src="<?= BASE_URL ?>/recursos/js/canchas.js"></script>
  <script src="<?= BASE_URL ?>/recursos/js/reservas.js"></script>

  <!-- JS VALIDACIÓN -->
  <script src="<?= BASE_URL ?>/recursos/js/validacion.js"></script>

  <!-- FIN CONFIG GLOBAL -->

</body>
</html>
