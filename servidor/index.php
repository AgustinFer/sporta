<?php

require_once __DIR__ . '/config/init.php';

$BASE = BASE_URL;

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Sporta - Iniciar Sesión</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link rel="stylesheet" href="<?= $BASE ?>/recursos/css/global.css">
  <link rel="stylesheet" href="<?= $BASE ?>/recursos/css/componentes.css">
  <link rel="stylesheet" href="<?= $BASE ?>/recursos/css/layout-login.css">
</head>
<body>

  <div class="screen">

    <div class="background">
      <div class="background-slide" style="background-image: url('<?= $BASE ?>/recursos/img/fondo1.jpg')"></div>
      <div class="background-slide" style="background-image: url('<?= $BASE ?>/recursos/img/fondo2.jpg')"></div>
      <div class="background-slide" style="background-image: url('<?= $BASE ?>/recursos/img/fondo3.jpg')"></div>
      <div class="background-slide" style="background-image: url('<?= $BASE ?>/recursos/img/fondo1.jpg')"></div>
    </div>

    <div class="logo-wrapper">
      <img src="<?= $BASE ?>/recursos/img/logo.png" alt="Sporta Logo" class="logo">
    </div>

    <div class="login-card">
      <div class="card-content">

        <h2>Iniciar Sesión</h2>

        <form id="loginForm" autocomplete="off">

          <div class="field">
            <label>Correo Electrónico o Usuario</label>
            <input type="text" id="usuario" placeholder="Correo o nombre de usuario" autocomplete="chrome-off" required>
          </div>

          <div class="field">
            <label>Contraseña</label>
            <input type="password" id="password" placeholder="Ingresa tu contraseña" autocomplete="new-password" required>
          </div>

          <div id="loginError" class="field-error" style="text-align:center;margin-bottom:12px"></div>

          <button type="submit" id="loginBtn">Iniciar Sesión</button>

        </form>

        <button id="btnCambiarPass" type="button">¿Olvidaste la contraseña?</button>

        <p class="version">Versión: 0.1.92</p>

      </div>

    </div>

  </div>

  <div id="modalPass" class="modal">
    <div class="modal-content">
      <span id="cerrarModal">&times;</span>
      <h2>Recuperar contraseña</h2>
      <div id="recoverForm">
<input type="email" id="email" placeholder="Ingresa tu email" autocomplete="off">
        <div id="recoverMsg" class="field-error" style="text-align:center;margin-bottom:12px"></div>
        <button id="enviarBtn">Enviar</button>
      </div>
    </div>
  </div>

  <script>
    var BASE_URL = <?= json_encode($BASE) ?>;

    var inputUsuario = document.getElementById("usuario");
    var inputPassword = document.getElementById("password");

    var campos = { usuario: inputUsuario, password: inputPassword };

    function limpiarErrores() {
      Object.values(campos).forEach(function(el) { el.classList.remove("input-error"); });
    }

    function marcarError(nombre) {
      if (nombre && campos[nombre]) campos[nombre].classList.add("input-error");
    }

    Object.values(campos).forEach(function(el) {
      el.addEventListener("input", limpiarErrores);
    });

    document.getElementById("loginForm").addEventListener("submit", async function(e) {
      e.preventDefault();
      var btn = document.getElementById("loginBtn");
      var error = document.getElementById("loginError");
      limpiarErrores();

      var valUsuario = inputUsuario.value.trim();
      var valPassword = inputPassword.value.trim();

      if (!valUsuario) marcarError("usuario");
      if (!valPassword) marcarError("password");
      if (!valUsuario || !valPassword) return;

      btn.disabled = true;
      btn.textContent = "Ingresando...";
      error.textContent = "";
      error.classList.remove("visible");

      var formData = new URLSearchParams();
      formData.append("usuario", valUsuario);
      formData.append("password", valPassword);

      try {
        var res = await fetch(BASE_URL + "/api/login.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: formData.toString()
        });
        var data = await res.json();
        if (data.ok) {
          window.location.href = data.redirect;
        } else {
          error.textContent = data.mensaje;
          error.classList.add("visible");
          marcarError(data.campo);
          btn.disabled = false;
          btn.textContent = "Iniciar Sesión";
        }
      } catch (err) {
        console.error("Login error:", err);
        error.textContent = "Error de conexión";
        error.classList.add("visible");
        btn.disabled = false;
        btn.textContent = "Iniciar Sesión";
      }
    });

    var modal = document.getElementById("modalPass");
    var btnAbrir = document.getElementById("btnCambiarPass");
    var cerrar = document.getElementById("cerrarModal");

    btnAbrir.onclick = function() {
      modal.classList.add("is-open");
      var recoverForm = document.getElementById("recoverForm");
      if (!recoverForm.querySelector("input")) {
        recoverForm.innerHTML = '<input type="email" id="email" placeholder="Ingresa tu email" autocomplete="off"><div id="recoverMsg" class="field-error" style="text-align:center;margin-bottom:12px"></div><button id="enviarBtn">Enviar</button>';
      }
      var msg = document.getElementById("recoverMsg");
      if (msg) { msg.textContent = ""; msg.classList.remove("visible"); }
      var btn = document.getElementById("enviarBtn");
      if (btn) { btn.disabled = false; btn.textContent = "Enviar"; }
    };
    cerrar.onclick = function() { modal.classList.remove("is-open"); };
    window.onclick = function(e) { if (e.target == modal) modal.classList.remove("is-open"); };

    document.getElementById("modalPass").addEventListener("click", function(e) {
      if (e.target.id === "enviarBtn") {
        var email = document.getElementById("email").value;
        var btn = e.target;
        var msg = document.getElementById("recoverMsg");
        msg.textContent = "";
        msg.classList.remove("visible");
        if (!email.trim()) {
          msg.textContent = "Debe ingresar un email";
          msg.classList.add("visible");
          return;
        }
        btn.disabled = true;
        btn.textContent = "Enviando...";
        fetch(BASE_URL + "/api/recuperar.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: "email=" + encodeURIComponent(email)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
          if (data.ok) {
            document.getElementById("recoverForm").innerHTML = '<p style="text-align:center;color:#1a1a2e;margin:20px 0">' + data.mensaje + '</p>';
          } else {
            msg.textContent = data.mensaje;
            msg.classList.add("visible");
            btn.disabled = false;
            btn.textContent = "Enviar";
          }
        })
        .catch(function(err) {
          console.error("Recuperar error:", err);
          var msgBox = document.getElementById("recoverMsg");
          if (msgBox) { msgBox.textContent = "Error de conexión"; msgBox.classList.add("visible"); }
          btn.disabled = false;
          btn.textContent = "Enviar";
        });
      }
    });
  </script>

</body>
</html>
