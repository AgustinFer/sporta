<?php

require "../config/conexion.php";

/* ========================= */
/* 📥 ALTA CLIENTE */
/* ========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombre = trim($_POST["cliente_nombre"] ?? "");
    $apellido = trim($_POST["cliente_apellido"] ?? "");
    $email = trim($_POST["cliente_email"] ?? "");
    $celular = trim($_POST["cliente_celular"] ?? "");

    if (!empty($nombre)) {

        $stmt = $pdo->prepare("
            INSERT INTO clientes (
                cliente_nombre,
                cliente_apellido,
                cliente_email,
                cliente_celular,
                cliente_estado,
                cliente_localidad_id,
                cliente_provincia_id,
                cliente_pais_id
            )
            VALUES (
                ?, ?, ?, ?, 1, 1, 1, 1
            )
        ");

        $stmt->execute([
            $nombre,
            $apellido,
            $email,
            $celular
        ]);

        header("Location: /clientes");
        exit;

    }

}

/* ========================= */
/* 📋 LISTADO */
/* ========================= */

$stmt = $pdo->query("
    SELECT *
    FROM clientes
    ORDER BY cliente_id DESC
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
  <link rel="stylesheet" href="/assets/css/global.css">
  <link rel="stylesheet" href="/assets/css/layout.css">
  <link rel="stylesheet" href="/assets/css/components.css">
  <link rel="stylesheet" href="/assets/css/drawer.css">

  <!-- CSS MÓDULO -->
  <link id="page-style" rel="stylesheet" href="/clientes/clientes.css">
</head>

<body class="screen" data-page="Clientes">

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

      <table class="clientes-table">

        <thead>

          <tr>

            <th>ID</th>
            <th>Nombre</th>
            <th>Apellido</th>
            <th>Email</th>
            <th>Celular</th>
            <th>DNI</th>
            <th>Estado</th>

          </tr>

        </thead>

        <tbody>

          <?php if (count($clientes) > 0): ?>

            <?php foreach ($clientes as $cliente): ?>

              <tr>

                <td>
                  <?= htmlspecialchars($cliente["cliente_id"]) ?>
                </td>

                <td>
                  <?= htmlspecialchars($cliente["cliente_nombre"]) ?>
                </td>

                <td>
                  <?= htmlspecialchars($cliente["cliente_apellido"]) ?>
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

                  <?php if ($cliente["cliente_estado"]): ?>

                    <span class="status active">
                      Activo
                    </span>

                  <?php else: ?>

                    <span class="status inactive">
                      Inactivo
                    </span>

                  <?php endif; ?>

                </td>

              </tr>

            <?php endforeach; ?>

          <?php else: ?>

            <tr>

              <td colspan="7">

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

  <!-- JS LAYOUT (carga sidebar + header) -->
  <script src="/assets/js/layout.js"></script>

  <!-- JS DRAWER -->
  <script src="/assets/js/drawer.js"></script>
  <!-- FIN CONFIG GLOBAL -->

</body>
</html>
