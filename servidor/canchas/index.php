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
  <title>Sporta - Canchas</title>

  <!-- CSS GLOBAL -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/recursos/css/global.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/recursos/css/layout.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/recursos/css/componentes.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/recursos/css/drawer.css">

  <!-- CSS MÓDULO -->
  <link id="page-style" rel="stylesheet" href="<?= BASE_URL ?>/canchas/canchas.css">
</head>

<body class="screen" data-page="Canchas" data-drawer="canchas">

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

    <!-- CONTENIDO CANCHAS -->
    <div class="courts-container">

      <!-- CANCHA 1 -->
      <div class="court-card">
        <div class="court-header">
          <h2>Cancha 1</h2>
          <span class="court-status available">Activa</span>
        </div>

        <div class="court-info">
          <p><strong>Tipo:</strong> Padel Indoor</p>
          <p><strong>Iluminación:</strong> LED</p>
          <p><strong>Estado:</strong> Disponible</p>
        </div>

        <button class="book-btn">Editar</button>
      </div>

      <!-- CANCHA 2 -->
      <div class="court-card">
        <div class="court-header">
          <h2>Cancha 2</h2>
          <span class="court-status semi-available">Mantenimiento</span>
        </div>

        <div class="court-info">
          <p><strong>Tipo:</strong> Padel Outdoor</p>
          <p><strong>Iluminación:</strong> Halógena</p>
          <p><strong>Estado:</strong> En revisión</p>
        </div>

        <button class="book-btn">Editar</button>
      </div>

      <!-- CANCHA 3 -->
      <div class="court-card">
        <div class="court-header">
          <h2>Cancha 3</h2>
          <span class="court-status busy">Inactiva</span>
        </div>

        <div class="court-info">
          <p><strong>Tipo:</strong> Sintética</p>
          <p><strong>Iluminación:</strong> LED</p>
          <p><strong>Estado:</strong> Fuera de servicio</p>
        </div>

        <button class="book-btn">Editar</button>
      </div>

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
