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

  <link rel="stylesheet" href="<?= BASE_URL ?>/recursos/css/global.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/recursos/css/layout.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/recursos/css/componentes.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/recursos/css/drawer.css">

  <link id="page-style" rel="stylesheet" href="<?= BASE_URL ?>/canchas/canchas.css">
  <link rel="icon" href="<?= BASE_URL ?>/recursos/img/favicon.ico">
</head>

<body class="screen" data-page="Canchas" data-drawer="canchas">

  <div class="background"></div>

  <div id="sidebar-container"></div>

  <button class="menu-toggle" id="menuToggle">&#9776;</button>

  <main class="main-content">

    <div id="header-container"></div>

    <div class="canchas-toolbar">
      <h2>Administración de Canchas</h2>
      <div class="canchas-acciones">
        <label class="check-inhabilitadas">
          <input type="checkbox" id="chkMostrarInhabilitadas">
          Mostrar inhabilitadas
        </label>

      </div>
    </div>

    <div class="contenedor-burbujas" id="contenedorBurbujas"></div>

    <button class="fab">+</button>

  </main>

  <div id="drawer-container"></div>

  <script>
    const BASE_URL = "<?= BASE_URL ?>";
  </script>

  <script src="<?= BASE_URL ?>/recursos/js/layout.js"></script>
  <script src="<?= BASE_URL ?>/recursos/js/table.js"></script>
  <script src="<?= BASE_URL ?>/recursos/js/drawer.js"></script>
  <script src="<?= BASE_URL ?>/recursos/js/turnos.js"></script>
  <script src="<?= BASE_URL ?>/recursos/js/canchas.js"></script>

</body>
</html>
