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
  <title>Sporta - Inicio</title>

  <!-- CSS GLOBAL -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/recursos/css/global.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/recursos/css/layout.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/recursos/css/componentes.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/recursos/css/drawer.css">

  <!-- CSS MÓDULO -->
  <link id="page-style" rel="stylesheet" href="<?= BASE_URL ?>/inicio/inicio.css">
</head>

<body class="screen" data-page="Inicio">

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

    <!-- HERO -->
    <section class="hero">

      <div class="hero-left">
        <p class="welcome">Bienvenido</p>

        <h1 id="clock">--:--</h1>

        <p class="date" id="date">
          XXXXXX, X de XXXX
        </p>
      </div>

      <div class="weather-card">

       <div class="weather-icon" id="weatherIcon">
    ☀️
  </div>

  <div>
    <h3>Berazategui</h3>

    <p class="temp" id="weatherTemp">
      --°C
    </p>

    <span id="weatherDesc">
      Cargando clima...
    </span>
  </div>

</div>

    </section>

    <!-- GRID -->
    <section class="home-grid">

      <div class="info-card large">
        <h3>Próximos turnos</h3>

        <div class="turno-item">
          <span>18:00</span>
          <p>Cancha 1 - Fernández</p>
        </div>

        <div class="turno-item">
          <span>19:30</span>
          <p>Cancha 2 - Gómez</p>
        </div>

        <div class="turno-item">
          <span>21:00</span>
          <p>Cancha 3 - Martínez</p>
        </div>
      </div>

      <div class="info-card">
        <h3>Turnos hoy</h3>
        <p class="big-number">18</p>
      </div>

      <div class="info-card">
        <h3>Ingresos</h3>
        <p class="big-number">$45k</p>
      </div>

      <div class="info-card">
        <h3>Canchas activas</h3>
        <p class="big-number">3</p>
      </div>

      <div class="info-card">
        <h3>Clientes</h3>
        <p class="big-number">124</p>
      </div>

    </section>

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
