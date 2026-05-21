<?php
require_once __DIR__ . '/../config/init.php';

$logoutPath = dirname($_SERVER['REQUEST_URI'], 2) . '/logout.php';
?>
<div class="sidebar">
  <div class="sidebar-logo">
    <img src="../assets/img/logo.png" class="sidebar-logo-img">
    <span class="sidebar-brand">Sporta</span>
  </div>

  <ul class="sidebar-menu">
    <li class="menu-item">
      <a href="inicio/" class="menu-link" data-route="inicio">
        <span class="menu-icon">🏠</span>
        Inicio
      </a>
    </li>

    <li class="menu-item">
      <a href="turnos/" class="menu-link" data-route="turnos">
        <span class="menu-icon">📅</span>
        Turnos
      </a>
    </li>

    <li class="menu-item">
      <a href="canchas/" class="menu-link" data-route="canchas">
        <span class="menu-icon">🎾</span>
        Canchas
      </a>
    </li>

    <li class="menu-item">
      <a href="clientes/" class="menu-link" data-route="clientes">
        <span class="menu-icon">👤</span>
        Clientes
      </a>
    </li>
    
    <?php if(isset($_SESSION['usuario']) && is_object($_SESSION['usuario']) && method_exists($_SESSION['usuario'], 'isAdmin') && $_SESSION['usuario']->isAdmin()): ?>
    <li class="menu-item">
      <a href="empleados/" class="menu-link" data-route="empleados">
        <span class="menu-icon">🧑‍💼</span>
        Empleados
      </a>
    </li>
    <?php endif; ?>

    <li class="menu-item">
      <a href="reservas/" class="menu-link" data-route="reservas">
        <span class="menu-icon">🧾</span>
        Señas y Reservas
      </a>
    </li>
  </ul>

  <div class="sidebar-footer">
    <a href="ajustes/" class="menu-link" data-route="ajustes">
      <span class="menu-icon">⚙️</span>
      Ajustes
    </a>
    <a href="<?php echo $logoutPath; ?>" class="menu-link logout">
      <span class="menu-icon">🔚</span>
      Cerrar sesión
    </a>
  </div>
</div>
