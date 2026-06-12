<?php 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/init.php';

if(isset($_POST['iniciar'])){
    Usuario::iniciarSesion(
        $_POST['usuario'],
        $_POST['password']
    );
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Sporta - Iniciar Sesión</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  
  <!-- CSS -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/recursos/css/global.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/recursos/css/layout-login.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/recursos/css/componentes.css">
</head>

<body>

  <div class="screen">

    <!-- 🔥 FONDO CON CARRUSEL DE MÚLTIPLES IMÁGENES -->
    <div class="background">
      <div class="background-slide" style="background-image: url('<?= BASE_URL ?>/recursos/img/fondo1.jpg')"></div>
      <div class="background-slide" style="background-image: url('<?= BASE_URL ?>/recursos/img/fondo2.jpg')"></div>
      <div class="background-slide" style="background-image: url('<?= BASE_URL ?>/recursos/img/fondo3.jpg')"></div>
      <div class="background-slide" style="background-image: url('<?= BASE_URL ?>/recursos/img/fondo1.jpg')"></div>
    </div>

    <!-- LOGO -->
    <div class="logo-wrapper">
      <img src="<?= BASE_URL ?>/recursos/img/logo.png" alt="Sporta Logo" class="logo">
    </div>

    <!-- CARD LOGIN -->
    <div class="login-card">
      <div class="card-content">

        <h2>Iniciar Sesión</h2>

        <form method="POST">

          <div class="field">
            <label>Correo Electrónico o Usuario</label>
            <input
                type="text"
                name="usuario"
                placeholder="Correo o nombre de usuario"
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

          <button id="btnCambiarPass">
            ¿Olvidaste la contraseña?
          </button>
          
        </form>

      </div>
    </div>

  </div>

</body>
</html>

  <!-- Ventana de recuperar contra -->
  <!-- Fondo oscuro + ventana emergente -->
  <div id="modalPass" class="modal">

    <!-- Contenido de la ventana -->
    <div class="modal-content">

      <!-- Botón X para cerrar -->
      <span id="cerrarModal">&times;</span>

      <!-- Título -->
      <h2>Recuperar contraseña</h2>

      <!-- Input para escribir el email -->
      <input type="email" id="email" placeholder="Ingresa tu email">

      <!-- Botón para enviar -->
      <button id="enviarBtn">Enviar</button>

    </div>
  </div>

<script>
  // Mover script a su archivo correspondiente
  // Solo esta aca para facil acceso hasta que no se modifique mas
  // Guarda el botón "Cambiar contraseña"
  const btnAbrir = document.getElementById("btnCambiarPass");

  // Guarda la ventana emergente
  const modal = document.getElementById("modalPass");

  // Guarda la X para cerrar
  const cerrar = document.getElementById("cerrarModal");


  // Cuando se hace click en el botón:
  // muestra la ventana emergente
  btnAbrir.onclick = () => {
    modal.style.display = "block";
  };


  // Cuando se hace click en la X:
  // oculta la ventana
  cerrar.onclick = () => {
    modal.style.display = "none";
  };


  // Si el usuario hace click fuera de la caja blanca:
  // también se cierra la ventana
  window.onclick = (e) => {

    // Verifica si se hizo click en el fondo oscuro
    if (e.target == modal) {
      modal.style.display = "none";
    }
  };


  // Cuando se presiona el botón "Enviar"
  document.getElementById("enviarBtn").onclick = () => {

    // Obtiene el valor escrito en el input
    const email = document.getElementById("email").value;

     // ENVÍA LOS DATOS AL ARCHIVO PHP
    fetch("recuperar.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: "email=" + encodeURIComponent(email)
    })

    .then(respuesta => {

        // Si el archivo no existe o hay error
        if (!respuesta.ok) {

            // Error 404 = archivo no encontrado
            if (respuesta.status === 404) {
            alert("No existe el archivo recuperar.php");
            }

            // Otro tipo de error
            else {
            alert("Error del servidor");
            }

            // Detiene los siguientes .then()
            throw new Error("Error HTTP");
        }

        // Convierte la respuesta a texto
            return respuesta.text();
        })

        .then(data => {

        // Respuesta normal del PHP
            alert(data);

        })

        .catch(error => {

        // Error de conexión o fetch
        console.log(error);

    });
  };

</script>
