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

    header("Location: " . BASE_URL . "/clientes");
    exit;
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

        header("Location: " . BASE_URL . "/clientes");
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

        header("Location: " . BASE_URL . "/clientes");
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

    <!-- LISTADO -->
    <!-- TABLA -->
    <div class="table-container">

      <table class="table">

        <thead>

          <tr>

            <th>ID</th>
            <th>Nombre</th>
            <th>Apellido</th>
            <th>Email</th>
            <th>Celular</th>
            <th>DNI</th>
            <th>Estado</th>
            <th>Acciones</th>

          </tr>

        </thead>

        <tbody>

          <?php if (count($clientes) > 0): ?>

            <?php foreach ($clientes as $cliente): ?>

              <tr>

                <td>
                  <?= htmlspecialchars(
                    $cliente["cliente_id"]
                  ) ?>
                </td>

                <td>
                  <?= htmlspecialchars(
                    $cliente["cliente_nombre"]
                  ) ?>
                </td>

                <td>
                  <?= htmlspecialchars(
                    $cliente["cliente_apellido"]
                  ) ?>
                </td>

                <td>
                  <?= htmlspecialchars(
                    $cliente["cliente_email"] ?? "-"
                  ) ?>
                </td>

                <td>
                  <?= htmlspecialchars(
                    $cliente["cliente_celular"] ?? "-"
                  ) ?>
                </td>

                <td>
                  <?= htmlspecialchars(
                    $cliente["cliente_dni"] ?? "-"
                  ) ?>
                </td>

                <td>
                  <?php if ((int)$cliente["cliente_estado"] === 1): ?>
                    <span class="status active">Activo</span>
                  <?php else: ?>
                    <span class="status inactive">Inactivo</span>
                  <?php endif; ?>
                </td>

                <!-- ACCIONES -->
                <td>

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

  <script>
    const BASE_URL = "<?= BASE_URL ?>";
  </script>

  <!-- JS LAYOUT (carga sidebar + header) -->
  <script src="<?= BASE_URL ?>/recursos/js/layout.js"></script>

  <!-- JS DRAWER -->
  <script src="<?= BASE_URL ?>/recursos/js/drawer.js"></script>
  <!-- FIN CONFIG GLOBAL -->

</body>
</html>
