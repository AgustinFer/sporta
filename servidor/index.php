<?php 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/init.php';

if(isset($_POST['iniciar'])){
    Usuario::iniciarSesion($_POST['email'],$_POST['password']);
  }

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Sporta - Iniciar Sesión</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  
  <!-- CSS -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/global.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/layout-login.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/components.css">
</head>

<body>

  <div class="screen">

    <!-- 🔥 FONDO CON CARRUSEL DE MÚLTIPLES IMÁGENES -->
    <div class="background">
      <div class="background-slide" style="background-image: url('<?= BASE_URL ?>/assets/img/fondo1.jpg')"></div>
      <div class="background-slide" style="background-image: url('<?= BASE_URL ?>/assets/img/fondo2.jpg')"></div>
      <div class="background-slide" style="background-image: url('<?= BASE_URL ?>/assets/img/fondo3.jpg')"></div>
      <div class="background-slide" style="background-image: url('<?= BASE_URL ?>/assets/img/fondo1.jpg')"></div>
    </div>

    <!-- LOGO -->
    <div class="logo-wrapper">
      <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="Sporta Logo" class="logo">
    </div>

    <!-- CARD LOGIN -->
    <div class="login-card">
      <div class="card-content">

        <h2>Iniciar Sesión</h2>

        <form method="POST">

          <div class="field">
            <label>Correo Electrónico</label>
            <input 
              type="email" 
              name="email"
              placeholder="tu@email.com"
              required
            >
          </div>

          <div class="field">
            <label>Contraseña</label>
            <input 
              type="password" 
              name="password"
              placeholder="Ingresa tu contraseña"
              required
            >
          </div>

          <button type="submit" name="iniciar">
            Iniciar Sesión
          </button>

        </form>

        <span class="forgot">¿Olvidaste la contraseña?</span>

      </div>
    </div>

  </div>

</body>
</html>
