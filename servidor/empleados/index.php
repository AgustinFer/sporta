<?php

require_once __DIR__ . '/../config/init.php';

if (!isset($_SESSION['usuario'])) {

  header("Location: ../index.php");
  exit;

}

if (!$_SESSION['usuario']->isAdmin()) {
    header("Location: " . BASE_URL . "/inicio/");
    exit;
}

require_once __DIR__ . '/../config/conexion.php';

$pdo = conexion();

/* ========================= */
/* 🔄 TOGGLE ESTADO EMPLEADO */
/* ========================= */

if (isset($_POST["toggle_empleado_id"])) {

    $id = (int) $_POST["toggle_empleado_id"];

    if ($id === $_SESSION['usuario']->getId()) {
        $_SESSION['flash_error'] = "No puedes inactivar tu propio usuario";
        header("Location: " . BASE_URL . "/empleados/");
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT usu_estado
        FROM usuarios
        WHERE usu_id = ?
    ");
    $stmt->execute([$id]);

    $actual = $stmt->fetchColumn();

    $nuevoEstado = ((int)$actual === 1) ? 0 : 1;

    $stmt = $pdo->prepare("
        UPDATE usuarios
        SET usu_estado = ?
        WHERE usu_id = ?
    ");

    $stmt->execute([$nuevoEstado, $id]);

    $_SESSION['flash_success'] = $nuevoEstado === 1
        ? "Empleado activado con éxito"
        : "Empleado inactivado con éxito";
    header("Location: " . BASE_URL . "/empleados/");
    exit;
}

/* ========================= */
/* ✅ VALIDACIÓN */
/* ========================= */

function validarDatosEmpleado($nombre, $apellido, $email, $celular, $dni, $usuario = '', $direccion = '') {
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
    if (strlen($usuario) > 50) {
        $errores[] = "El usuario no puede superar los 50 caracteres";
    }
    if (strlen($direccion) > 255) {
        $errores[] = "La dirección no puede superar los 255 caracteres";
    }

    return $errores;
}

/* ========================= */
/* 🔍 VERIFICAR DUPLICADOS */
/* ========================= */

function verificarDuplicadosEmpleado($pdo, $usuario, $email, $dni, $excludeId = null) {
    $errores = [];

    if ($dni !== "") {
        $sql = "SELECT COUNT(*) FROM usuarios WHERE usu_dni = ?";
        $params = [$dni];
        if ($excludeId !== null) {
            $sql .= " AND usu_id != ?";
            $params[] = $excludeId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->fetchColumn() > 0) {
            $errores[] = "El DNI ingresado ya pertenece a otro empleado";
        }
    }

    if ($email !== "") {
        $sql = "SELECT COUNT(*) FROM usuarios WHERE usu_email = ?";
        $params = [$email];
        if ($excludeId !== null) {
            $sql .= " AND usu_id != ?";
            $params[] = $excludeId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->fetchColumn() > 0) {
            $errores[] = "El email ingresado ya pertenece a otro empleado";
        }
    }

    $sql = "SELECT COUNT(*) FROM usuarios WHERE usu_usuario = ?";
    $params = [$usuario];
    if ($excludeId !== null) {
        $sql .= " AND usu_id != ?";
        $params[] = $excludeId;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    if ($stmt->fetchColumn() > 0) {
        $errores[] = "El nombre de usuario ingresado ya pertenece a otro empleado";
    }

    return $errores;
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
    $rol = (int) ($_POST["empleado_rol"] ?? 0);

    $formData = [
        'edit_id' => $id,
        'nombre' => $nombre,
        'apellido' => $apellido,
        'email' => $email,
        'celular' => $celular,
        'dni' => $dni,
        'usuario' => $usuario,
        'direccion' => $direccion,
        'rol' => $rol,
    ];

    if (
        !empty($nombre) &&
        !empty($apellido) &&
        !empty($usuario) &&
        $rol > 0
    ) {

        $errores = validarDatosEmpleado($nombre, $apellido, $email, $celular, $dni, $usuario, $direccion);

        if (empty($errores)) {

            $erroresDuplicados = verificarDuplicadosEmpleado($pdo, $usuario, $email, $dni, $id);

            if (!empty($erroresDuplicados)) {
                $_SESSION['form_data'] = $formData;
                $_SESSION['flash_error'] = implode("<br>", $erroresDuplicados);
                header("Location: " . BASE_URL . "/empleados/");
                exit;
            }

            $stmt = $pdo->prepare("
                UPDATE usuarios
                SET
                    usu_nombre = ?,
                    usu_apellido = ?,
                    usu_email = ?,
                    usu_celular = ?,
                    usu_dni = ?,
                    usu_usuario = ?,
                    usu_direccion = ?,
                    usu_rol = ?
                WHERE usu_id = ?
            ");

            $stmt->execute([
                $nombre,
                $apellido,
                $email ?: null,
                $celular ?: null,
                $dni ?: null,
                $usuario,
                $direccion ?: null,
                $rol,
                $id
            ]);

            $_SESSION['flash_success'] = "Empleado modificado con éxito";
            header("Location: " . BASE_URL . "/empleados/");
            exit;
        }

        $_SESSION['form_data'] = $formData;
        $_SESSION['flash_error'] = implode("<br>", $errores);
        header("Location: " . BASE_URL . "/empleados/");
        exit;
    }
}

/* ========================= */
/* 📥 ALTA EMPLEADO */
/* ========================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    empty($_POST["edit_empleado_id"]) &&
    !isset($_POST["toggle_empleado_id"])
) {

    $nombre = trim($_POST["empleado_nombre"] ?? "");
    $apellido = trim($_POST["empleado_apellido"] ?? "");
    $email = trim($_POST["empleado_email"] ?? "");
    $celular = trim($_POST["empleado_celular"] ?? "");
    $dni = trim($_POST["empleado_dni"] ?? "");
    $usuario = trim($_POST["empleado_usuario"] ?? "");
    $direccion = trim($_POST["empleado_direccion"] ?? "");
    $rol = (int) ($_POST["empleado_rol"] ?? 0);

    $formData = [
        'edit_id' => '',
        'nombre' => $nombre,
        'apellido' => $apellido,
        'email' => $email,
        'celular' => $celular,
        'dni' => $dni,
        'usuario' => $usuario,
        'direccion' => $direccion,
        'rol' => $rol,
    ];

    if (
        !empty($nombre) &&
        !empty($apellido) &&
        !empty($usuario) &&
        $rol > 0
    ) {

        $errores = validarDatosEmpleado($nombre, $apellido, $email, $celular, $dni, $usuario, $direccion);

        if (empty($errores)) {

            $erroresDuplicados = verificarDuplicadosEmpleado($pdo, $usuario, $email, $dni);

            if (!empty($erroresDuplicados)) {
                $_SESSION['form_data'] = $formData;
                $_SESSION['flash_error'] = implode("<br>", $erroresDuplicados);
                header("Location: " . BASE_URL . "/empleados/");
                exit;
            }

            try {

                $stmt = $pdo->prepare("
                    INSERT INTO usuarios (
                        usu_nombre,
                        usu_apellido,
                        usu_email,
                        usu_celular,
                        usu_dni,
                        usu_usuario,
                        usu_direccion,
                        usu_rol,
                        usu_contrasena,
                        usu_estado
                    )
                    VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, 1
                    )
                ");

                $stmt->execute([
                    $nombre,
                    $apellido,
                    $email ?: null,
                    $celular ?: null,
                    $dni ?: null,
                    $usuario,
                    $direccion ?: null,
                    $rol,
                    password_hash("1234", PASSWORD_DEFAULT)
                ]);

                $_SESSION['flash_success'] = "Empleado agregado con éxito";
                header("Location: " . BASE_URL . "/empleados/");
                exit;

            } catch (PDOException $e) {

                $_SESSION['form_data'] = $formData;
                $_SESSION['flash_error'] = "Error al crear empleado";
                header("Location: " . BASE_URL . "/empleados/");
                exit;

            }

        }

        $_SESSION['form_data'] = $formData;
        $_SESSION['flash_error'] = implode("<br>", $errores);
        header("Location: " . BASE_URL . "/empleados/");
        exit;

    }

}

/* ========================= */
/* 📋 LISTADO */
/* ========================= */

$stmt = $pdo->query("
    SELECT u.*, r.rol_nombre
    FROM usuarios u
    JOIN roles r ON u.usu_rol = r.rol_id
    ORDER BY u.usu_id
");

$empleados = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>

  <meta charset="UTF-8">

  <meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
  >

  <title>
    Sporta - Empleados
  </title>

  <!-- CSS GLOBAL -->
  <link
    rel="stylesheet"
    href="<?= BASE_URL ?>/recursos/css/global.css"
  >

  <link
    rel="stylesheet"
    href="<?= BASE_URL ?>/recursos/css/layout.css"
  >

  <link
    rel="stylesheet"
    href="<?= BASE_URL ?>/recursos/css/componentes.css"
  >

  <link
    rel="stylesheet"
    href="<?= BASE_URL ?>/recursos/css/drawer.css"
  >

  <!-- CSS MÓDULO -->
  <link
    id="page-style"
    rel="stylesheet"
    href="<?= BASE_URL ?>/empleados/empleados.css"
  >
  <link rel="icon" href="<?= BASE_URL ?>/recursos/img/favicon.ico">
</head>

<body
  class="screen"
  data-page="Empleados"
  data-drawer="empleados"
>

  <!-- FONDO -->
  <div class="background"></div>

  <!-- SIDEBAR -->
  <div id="sidebar-container"></div>

  <!-- MOBILE -->
  <button
    class="menu-toggle"
    id="menuToggle"
  >
    ☰
  </button>

  <!-- CONTENIDO -->
  <main class="main-content">

    <!-- HEADER -->
    <div id="header-container"></div>

    <!-- CABECERA -->
    <div class="list-container">

      <h2>
        Empleados
      </h2>

      <p>
        Listado de empleados registrados
      </p>

    </div>

    <!-- TOOLBAR -->
    <div class="table-toolbar">

      <input
        type="text"
        id="tableSearch"
        class="table-search"
        placeholder="Buscar empleado..."
      >

      <label class="filter-inactivos">
        <input
          type="checkbox"
          id="showInactivos"
        >
        Mostrar inactivos
      </label>

    </div>

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
            <th data-sort="6" data-column="usuario">Usuario</th>
            <th data-sort="7" data-column="direccion">Dirección</th>
            <th data-sort="8" data-column="rol">Rol</th>
            <th data-sort="9" data-column="estado">Estado</th>
            <th data-column="acciones">Acciones</th>

          </tr>

        </thead>

        <tbody>

          <?php if (count($empleados) > 0): ?>

            <?php foreach ($empleados as $empleado): ?>

              <tr>

                <td data-column="id">
                  <?= htmlspecialchars(
                    $empleado["usu_id"]
                  ) ?>
                </td>

                <td data-column="nombre">
                  <?= htmlspecialchars(
                    $empleado["usu_nombre"]
                  ) ?>
                </td>

                <td data-column="apellido">
                  <?= htmlspecialchars(
                    $empleado["usu_apellido"]
                  ) ?>
                </td>

                <td data-column="email">
                  <?= htmlspecialchars(
                    $empleado["usu_email"] ?? "-"
                  ) ?>
                </td>

                <td data-column="celular">
                  <?= htmlspecialchars(
                    $empleado["usu_celular"] ?? "-"
                  ) ?>
                </td>

                <td data-column="dni">
                  <?= htmlspecialchars(
                    $empleado["usu_dni"] ?? "-"
                  ) ?>
                </td>

                <td data-column="usuario">
                  <?= htmlspecialchars(
                    $empleado["usu_usuario"]
                  ) ?>
                </td>

                <td data-column="direccion">
                  <?= htmlspecialchars(
                    $empleado["usu_direccion"] ?? "-"
                  ) ?>
                </td>

                <td data-column="rol">
                  <?= htmlspecialchars(
                    $empleado["rol_nombre"]
                  ) ?>
                </td>

                <td data-column="estado">
                  <?php if ((int)$empleado["usu_estado"] === 1): ?>
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
                      data-id="<?= $empleado["usu_id"] ?>"
                      data-nombre="<?= htmlspecialchars($empleado["usu_nombre"]) ?>"
                      data-apellido="<?= htmlspecialchars($empleado["usu_apellido"]) ?>"
                      data-email="<?= htmlspecialchars($empleado["usu_email"] ?? "") ?>"
                      data-celular="<?= htmlspecialchars($empleado["usu_celular"] ?? "") ?>"
                      data-dni="<?= htmlspecialchars($empleado["usu_dni"] ?? "") ?>"
                      data-usuario="<?= htmlspecialchars($empleado["usu_usuario"]) ?>"
                      data-direccion="<?= htmlspecialchars($empleado["usu_direccion"] ?? "") ?>"
                      data-rol="<?= htmlspecialchars($empleado["usu_rol"]) ?>"
                      data-self="<?= (int)$empleado["usu_id"] === (int)$_SESSION['usuario']->getId() ? 'true' : '' ?>"
                    >
                      Modificar
                    </button>

                    <form method="POST">
                      <input
                        type="hidden"
                        name="toggle_empleado_id"
                        value="<?= $empleado["usu_id"] ?>"
                      >

                      <button
                        type="submit"
                        class="<?= ((int)$empleado["usu_estado"] === 1) ? 'deactivate-btn' : 'activate-btn' ?>"
                        onclick="return confirm('¿Cambiar estado del empleado?')"
                      >
                        <?= ((int)$empleado["usu_estado"] === 1) ? 'Inactivar' : 'Activar' ?>
                      </button>

                    </form>

                  </div>

                </td>

              </tr>

            <?php endforeach; ?>

          <?php else: ?>

            <tr>

              <td colspan="11">

                No hay empleados registrados

              </td>

            </tr>

          <?php endif; ?>

        </tbody>

      </table>

    </div>

    <!-- FAB -->
    <button class="fab">
      +
    </button>

  </main>

  <!-- DRAWER -->
  <div id="drawer-container"></div>

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

  <script>
    var formData = <?= json_encode($_SESSION['form_data'] ?? null) ?>;
    <?php unset($_SESSION['form_data']); ?>
  </script>

  <script>
    const BASE_URL =
      "<?= BASE_URL ?>";
  </script>

  <!-- JS -->
  <script src="<?= BASE_URL ?>/recursos/js/layout.js"></script>

  <script src="<?= BASE_URL ?>/recursos/js/table.js"></script>

  <script src="<?= BASE_URL ?>/recursos/js/drawer.js"></script>

  <script src="<?= BASE_URL ?>/recursos/js/turnos.js"></script>

  <script src="<?= BASE_URL ?>/recursos/js/canchas.js"></script>
  <script src="<?= BASE_URL ?>/recursos/js/reservas.js"></script>

  <script src="<?= BASE_URL ?>/recursos/js/validacion.js"></script>

</body>
</html>
