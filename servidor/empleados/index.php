<?php require_once '../config/init.php'; ?>

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

    <div class="list-container">
      <h2>Empleados</h2>
      <p>Gestión del personal</p>
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
