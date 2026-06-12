<?php require_once __DIR__ . '/../config/init.php';

if(!isset($_SESSION['usuario'])){
  header("Location: ../index.php");
  exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sporta - Reservas</title>

  <!-- CSS GLOBAL -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/recursos/css/global.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/recursos/css/layout.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/recursos/css/componentes.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/recursos/css/drawer.css">

  <!-- CSS MÓDULO -->
  <link id="page-style" rel="stylesheet" href="<?= BASE_URL ?>/reservas/reservas.css">
</head>

<body class="screen" data-page="Señas y Reservas" data-drawer="reservas">

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
      <h2>Señas y Reservas</h2>
      <p>Control de pagos y reservas</p>
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

  <!-- JS TABLA -->
  <script src="<?= BASE_URL ?>/recursos/js/table.js"></script>

  <!-- JS DRAWER -->
  <script src="<?= BASE_URL ?>/recursos/js/drawer.js"></script>
  <!-- FIN CONFIG GLOBAL -->

</body>
</html>
