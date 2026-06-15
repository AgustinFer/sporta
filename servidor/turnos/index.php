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
  <title>Sporta - Turnos</title>

  <link rel="stylesheet" href="<?= BASE_URL ?>/recursos/css/global.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/recursos/css/layout.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/recursos/css/componentes.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/recursos/css/drawer.css">

  <link id="page-style" rel="stylesheet" href="<?= BASE_URL ?>/turnos/turnos.css">
</head>

<body class="screen" data-page="Turnos" data-drawer="turnos">

  <div class="background"></div>

  <div id="sidebar-container"></div>

  <button class="menu-toggle" id="menuToggle">&#9776;</button>

  <main class="main-content">

    <div id="header-container"></div>

    <div class="turnos-toolbar">
      <h2>Administración de Turnos</h2>
      <div class="turnos-filtros">
        <label for="fechaSeleccionada">Fecha:</label>
        <input type="date" id="fechaSeleccionada">
        <button id="btnRecargar" class="turnos-btn">Actualizar</button>
      </div>
    </div>

    <div class="contenedor-tabla">
      <table id="tablaTurnos">
        <thead>
          <tr id="filaCanchas">
            <th class="columna-hora">Horario</th>
          </tr>
        </thead>
        <tbody id="cuerpoTabla"></tbody>
      </table>
    </div>

  </main>

  <div id="drawer-container"></div>

  <script>
    const BASE_URL = "<?= BASE_URL ?>";
  </script>

  <script src="<?= BASE_URL ?>/recursos/js/layout.js"></script>
  <script src="<?= BASE_URL ?>/recursos/js/table.js"></script>
  <script src="<?= BASE_URL ?>/recursos/js/drawer.js"></script>
  <script src="<?= BASE_URL ?>/recursos/js/turnos.js"></script>

</body>
</html>
