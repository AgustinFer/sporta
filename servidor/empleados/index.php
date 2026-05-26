<?php require_once __DIR__ . '/../config/init.php';

if(!isset($_SESSION['usuario'])){
  header("Location: ../index.php");
  exit;
}

$esAdmin = isset($_SESSION['usuario']) && $_SESSION['usuario']->isAdmin();

require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/conexion.php';

$pdo = conexion();

/* ========================= */
/* 🗑️ ELIMINAR EMPLEADO */
/* ========================= */

if (isset($_POST["delete_empleado_id"])) {

    $id = (int) $_POST["delete_empleado_id"];

    $stmt = $pdo->prepare("
        DELETE FROM usuarios
        WHERE usu_id = ?
    ");

    $stmt->execute([$id]);

    header("Location: " . BASE_URL . "/empleados");
    exit;

}

/* ========================= */
/* ✏️ MODIFICAR EMPLEADO */
/* ========================= */

if (!empty($_POST["edit_empleado_id"])) {

    $id = (int) $_POST["edit_empleado_id"];

    $nombre = trim($_POST["empleado_nombre"] ?? "");
    $apellido = trim($_POST["empleado_apellido"] ?? "");
    $email = trim($_POST["empleado_email"] ?? "");
    $celular = trim($_POST["empleado_celular"] ?? "");
    $dni = trim($_POST["empleado_dni"] ?? "");
    $usuario = trim($_POST["empleado_usuario"] ?? "");
    $direccion = trim($_POST["empleado_direccion"] ?? "");

    if (
        !empty($nombre) &&
        !empty($apellido)
    ) {

        $stmt = $pdo->prepare("
            UPDATE usuarios
            SET
                usu_nombre = ?,
                usu_apellido = ?,
                usu_email = ?,
                usu_celular = ?,
                usu_dni = ?,
                usu_usuario = ?,
                usu_direccion = ?
            WHERE usu_id = ?
        ");

        $stmt->execute([
            $nombre,
            $apellido,
            $email ?: null,
            $celular ?: null,
            $dni ?: null,
            $usuario ?: null,
            $direccion ?: null,
            $id
        ]);

        header("Location: " . BASE_URL . "/empleados");
        exit;

    }

}

/* ========================= */
/* 📥 ALTA EMPLEADO */
/* ========================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    empty($_POST["edit_empleado_id"]) &&
    !isset($_POST["delete_empleado_id"])
) {

    $nombre = trim($_POST["empleado_nombre"] ?? "");
    $apellido = trim($_POST["empleado_apellido"] ?? "");
    $email = trim($_POST["empleado_email"] ?? "");
    $celular = trim($_POST["empleado_celular"] ?? "");
    $dni = trim($_POST["empleado_dni"] ?? "");
    $usuario = trim($_POST["empleado_usuario"] ?? "");
    $direccion = trim($_POST["empleado_direccion"] ?? "");

    if (
        !empty($nombre) &&
        !empty($apellido)
    ) {

        $stmt = $pdo->prepare("
            INSERT INTO usuarios (
                usu_nombre,
                usu_apellido,
                usu_email,
                usu_celular,
                usu_dni,
                usu_usuario,
                usu_direccion,
                usu_estado
            )
            VALUES (
                ?, ?, ?, ?, ?, ?, ?, 1
            )
        ");

        $stmt->execute([
            $nombre,
            $apellido,
            $email ?: null,
            $celular ?: null,
            $dni ?: null,
            $usuario ?: null,
            $direccion ?: null
        ]);

        header("Location: " . BASE_URL . "/empleados");
        exit;

    }

}

/* ========================= */
/* 📋 LISTADO */
/* ========================= */

$stmt = $pdo->query("
    SELECT *
    FROM usuarios
    WHERE usu_estado = 1
    ORDER BY usu_id
");

$empleados = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sporta - Empleados</title>

  <!-- CSS GLOBAL -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/global.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/layout.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/components.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/drawer.css">

  <!-- CSS MÓDULO -->
  <link id="page-style" rel="stylesheet" href="<?= BASE_URL ?>/empleados/empleados.css">
</head>

<body class="screen" data-page="Empleados">

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

      <h2>Empleados</h2>

      <p>
        Listado de empleados registrados
      </p>

    </div>

    <!-- LISTADO -->
    <!-- TABLA -->
    <div class="table-container">

      <table class="empleados-table">

        <thead>

          <tr>

            <th>ID</th>
            <th>Nombre</th>
            <th>Apellido</th>
            <th>Email</th>
            <th>Celular</th>
            <th>DNI</th>
            <th>Usuario</th>
            <th>Dirección</th>
            <th>Acciones</th>

          </tr>

        </thead>

        <tbody>

          <?php if (count($empleados) > 0): ?>

            <?php foreach ($empleados as $empleado): ?>

              <tr>

                <td>
                  <?= htmlspecialchars(
                    $empleado["usu_id"]
                  ) ?>
                </td>

                <td>
                  <?= htmlspecialchars(
                    $empleado["usu_nombre"]
                  ) ?>
                </td>

                <td>
                  <?= htmlspecialchars(
                    $empleado["usu_apellido"]
                  ) ?>
                </td>

                <td>
                  <?= htmlspecialchars(
                    $empleado["usu_email"] ?? "-"
                  ) ?>
                </td>

                <td>
                  <?= htmlspecialchars(
                    $empleado["usu_celular"] ?? "-"
                  ) ?>
                </td>

                <td>
                  <?= htmlspecialchars(
                    $empleado["usu_dni"] ?? "-"
                  ) ?>
                </td>

                <td>
                  <?= htmlspecialchars(
                    $empleado["usu_usuario"] ?? "-"
                  ) ?>
                </td>

                <td>
                  <?= htmlspecialchars(
                    $empleado["usu_direccion"] ?? "-"
                  ) ?>
                </td>

                <!-- ACCIONES -->
                <td>

                  <div class="table-actions">
                    <button
                      type="button"
                      class="edit-btn"
                      data-id="<?= $empleado["usu_id"] ?>"
                      data-nombre="<?= htmlspecialchars($empleado["usu_nombre"]) ?>"
                      data-apellido="<?= htmlspecialchars($empleado["usu_apellido"]) ?>"
                      data-email="<?= htmlspecialchars($empleado["usu_email"] ?? "") ?>"
                      data-celular="<?= htmlspecialchars($empleado["usu_celular"] ?? "") ?>"
                      data-dni="<?= htmlspecialchars($empleado["usu_dni"] ?? "") ?>"
                      data-usuario="<?= htmlspecialchars($empleado["usu_usuario"] ?? "") ?>"
                      data-direccion="<?= htmlspecialchars($empleado["usu_direccion"] ?? "") ?>"
                    >
                      Modificar
                    </button>

                    <form method="POST">

                      <input
                        type="hidden"
                        name="delete_empleado_id"
                        value="<?= $empleado["usu_id"] ?>"
                      >

                      <button
                        type="submit"
                        class="delete-btn"
                        onclick="
                          return confirm(
                            '¿Eliminar empleado?'
                          )
                        "
                      >
                        Eliminar
                      </button>

                    </form>

                  </div>

                </td>

              </tr>

            <?php endforeach; ?>

          <?php else: ?>

            <tr>

              <td colspan="9">

                No hay empleados registrados

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
  <script src="<?= BASE_URL ?>/assets/js/layout.js"></script>

  <!-- JS DRAWER -->
  <script src="<?= BASE_URL ?>/assets/js/drawer.js"></script>
  <!-- FIN CONFIG GLOBAL -->

</body>
</html>
