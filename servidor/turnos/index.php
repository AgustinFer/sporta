<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sporta - Turnos</title>

  <!-- CSS GLOBAL -->
  <link rel="stylesheet" href="../assets/css/global.css">
  <link rel="stylesheet" href="../assets/css/layout.css">
  <link rel="stylesheet" href="../assets/css/components.css">
  <link rel="stylesheet" href="../assets/css/drawer.css">

  <!-- CSS MÓDULO -->
  <link id="page-style" rel="stylesheet" href="turnos.css">
</head>

<body class="screen" data-page="Turnos">

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

    <!-- CONTENIDO DE TURNOS -->
    <div class="courts-container">

      <!-- CANCHA 1 -->
      <div class="court-card">
        <div class="court-header">
          <h2>Cancha 1</h2>
          <span class="court-status available">Disponible</span>
        </div>

        <div class="schedule-list">
          <div class="schedule-item">
            <span class="time">16:30 - 17:00</span>
            <span class="partner">Christian Rippa</span>
          </div>
          <div class="schedule-item">
            <span class="time">18:00 - 19:30</span>
            <span class="partner">Facundo Ulela</span>
          </div>
        </div>

        <button class="book-btn">Reservar Turno</button>
      </div>

      <!-- CANCHA 2 -->
      <div class="court-card">
        <div class="court-header">
          <h2>Cancha 2</h2>
          <span class="court-status semi-available">Parcial</span>
        </div>

        <div class="schedule-list">
          <div class="schedule-item">
            <span class="time">19:00 - 19:30</span>
            <span class="partner">Carolina Levy</span>
          </div>
        </div>

        <button class="book-btn">Reservar Turno</button>
      </div>

      <!-- CANCHA 3 -->
      <div class="court-card">
        <div class="court-header">
          <h2>Cancha 3</h2>
          <span class="court-status busy">Completo</span>
        </div>

        <div class="schedule-list">
          <div class="schedule-item">
            <span class="time">21:00 - 22:30</span>
            <span class="partner">Adrian Parrilla</span>
          </div>
        </div>

        <button class="book-btn" disabled>Sin Disponibilidad</button>
      </div>

    </div>

    <!-- FAB -->
    <button class="fab">+</button>

  </main>

  <!-- INICIO CONFIG GLOBAL -->
  <!-- DRAWER (aunque no se use) -->
  <div id="drawer-container"></div>

  <!-- JS LAYOUT (carga sidebar + header) -->
  <script src="../assets/js/layout.js"></script>

  <!-- JS DRAWER -->
  <script src="../assets/js/drawer.js"></script>
  <!-- FIN CONFIG GLOBAL -->

</body>
</html>