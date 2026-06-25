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
  <link rel="stylesheet" href="<?= $BASE ?>/recursos/css/minijuego.css">
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

        <form id="loginForm">

          <div class="field">
            <label>Correo Electrónico o Usuario</label>
            <input type="text" id="usuario" placeholder="Correo o nombre de usuario" required>
          </div>

          <div class="field">
            <label>Contraseña</label>
            <input type="password" id="password" placeholder="Ingresa tu contraseña" required>
          </div>

          <div id="loginError" class="field-error" style="text-align:center;margin-bottom:12px"></div>

          <button type="submit" id="loginBtn">Iniciar Sesión</button>

        </form>

        <button id="btnCambiarPass" type="button">¿Olvidaste la contraseña?</button>

      </div>
    </div>

  </div>

  <span id="easter-egg-login" class="easter-egg-ball">⚽</span>

  <div id="modalPass" class="modal">
    <div class="modal-content">
      <span id="cerrarModal">&times;</span>
      <h2>Recuperar contraseña</h2>
      <div id="recoverForm">
        <input type="email" id="email" placeholder="Ingresa tu email">
        <div id="recoverMsg" class="field-error" style="text-align:center;margin-bottom:12px"></div>
        <button id="enviarBtn">Enviar</button>
      </div>
    </div>
  </div>

  <div id="minijuego-overlay" class="minijuego-overlay">
    <div class="minijuego-card">
      <div class="minijuego-header">
        <div class="minijuego-titulo">Sporta Memory</div>
        <div class="minijuego-stats">
          <span>Pares: <strong id="mj-pares">0</strong> / 6</span>
          <span>Intentos: <strong id="mj-intentos">0</strong></span>
        </div>
      </div>
      <div class="minijuego-area" id="mj-area">
        <div class="memory-grid" id="mj-grid"></div>
      </div>
      <div class="minijuego-footer">
        <button id="mj-restart" class="minijuego-btn" style="display:none">Jugar de nuevo</button>
        <button id="mj-cerrar" class="minijuego-btn minijuego-btn-secondary">Cerrar</button>
      </div>
    </div>
  </div>

  <script>
    var BASE_URL = <?= json_encode($BASE) ?>;

    /* ========================= */
    /* 🎮 MINIJUEGO — Sporta Memory */
    /* ========================= */
    var mj = {
      overlay: null, area: null, grid: null,
      paresEl: null, intentosEl: null,
      restartBtn: null, cerrarBtn: null,
      cards: [], flipped: [], matchedCount: 0,
      moves: 0, locked: false,
      emojis: ['⚽','🏀','🎾','⚾','🏐','🏉'],
      init: function() {
        this.overlay = document.getElementById('minijuego-overlay');
        this.area = document.getElementById('mj-area');
        this.grid = document.getElementById('mj-grid');
        this.paresEl = document.getElementById('mj-pares');
        this.intentosEl = document.getElementById('mj-intentos');
        this.restartBtn = document.getElementById('mj-restart');
        this.cerrarBtn = document.getElementById('mj-cerrar');
        this.cerrarBtn.onclick = function() { mj.overlay.classList.remove('is-open'); };
        this.restartBtn.onclick = function() { mj.startGame(); };
      },
      shuffle: function(a) {
        for (var i = a.length - 1; i > 0; i--) {
          var j = Math.floor(Math.random() * (i + 1));
          var t = a[i]; a[i] = a[j]; a[j] = t;
        }
        return a;
      },
      startGame: function() {
        this.matchedCount = 0; this.moves = 0;
        this.flipped = []; this.locked = false;
        this.paresEl.textContent = '0';
        this.intentosEl.textContent = '0';
        this.restartBtn.style.display = 'none';
        var q = this.area.querySelector('.memory-complete');
        if (q) q.remove();
        var deck = [];
        for (var i = 0; i < this.emojis.length; i++) {
          deck.push({ id: i, emoji: this.emojis[i] });
          deck.push({ id: i, emoji: this.emojis[i] });
        }
        this.shuffle(deck);
        this.cards = deck;
        this.grid.innerHTML = '';
        for (var k = 0; k < deck.length; k++) {
          var el = document.createElement('div');
          el.className = 'memory-card';
          el.dataset.index = k;
          el.innerHTML = '<div class="memory-card-inner"><div class="memory-card-face memory-card-back"></div><div class="memory-card-face memory-card-front">' + deck[k].emoji + '</div></div>';
          el.onclick = function() { mj.flipCard(parseInt(this.dataset.index)); };
          this.grid.appendChild(el);
        }
      },
      flipCard: function(idx) {
        if (this.locked) return;
        var card = this.grid.children[idx];
        if (!card || card.classList.contains('is-flipped') || card.classList.contains('is-matched')) return;
        if (this.flipped.length >= 2) return;
        card.classList.add('is-flipped');
        this.flipped.push(idx);
        if (this.flipped.length === 2) {
          this.moves++;
          this.intentosEl.textContent = this.moves;
          this.locked = true;
          var idx0 = this.flipped[0], idx1 = this.flipped[1];
          var self = this;
          if (this.cards[idx0].id === this.cards[idx1].id) {
            setTimeout(function() {
              self.grid.children[idx0].classList.add('is-matched');
              self.grid.children[idx1].classList.add('is-matched');
              self.matchedCount++;
              self.paresEl.textContent = self.matchedCount;
              self.flipped = [];
              self.locked = false;
              if (self.matchedCount === 6) self.showComplete();
            }, 400);
          } else {
            setTimeout(function() {
              self.grid.children[idx0].classList.remove('is-flipped');
              self.grid.children[idx1].classList.remove('is-flipped');
              self.flipped = [];
              self.locked = false;
            }, 800);
          }
        }
      },
      showComplete: function() {
        var d = document.createElement('div');
        d.className = 'memory-complete';
        d.innerHTML = '<h3>🎉 Completado</h3><p>' + this.moves + ' intentos</p>';
        this.area.appendChild(d);
        this.restartBtn.style.display = 'block';
      }
    };

    function mostrarMiniJuego() {
      if (!mj.overlay) mj.init();
      mj.overlay.classList.add('is-open');
      mj.startGame();
    }

    document.addEventListener('DOMContentLoaded', function() {
      mj.init();
      var ball = document.getElementById('easter-egg-login');
      if (ball) {
        var vw = window.innerWidth, vh = window.innerHeight;
        var esquina = Math.floor(Math.random() * 4);
        var x, y;
        if (esquina === 0) { x = 10 + Math.random() * 80; y = 10 + Math.random() * 80; }
        else if (esquina === 1) { x = vw - 90 + Math.random() * 80; y = 10 + Math.random() * 80; }
        else if (esquina === 2) { x = 10 + Math.random() * 80; y = vh - 90 + Math.random() * 80; }
        else { x = vw - 90 + Math.random() * 80; y = vh - 90 + Math.random() * 80; }
        ball.style.left = x + 'px';
        ball.style.top = y + 'px';
        ball.onclick = mostrarMiniJuego;
      }
    });

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
        } else if (data.minijuego) {
          mostrarMiniJuego();
          btn.disabled = false;
          btn.textContent = "Iniciar Sesión";
        } else {
          error.textContent = data.mensaje;
          error.classList.add("visible");
          marcarError(data.campo);
          btn.disabled = false;
          btn.textContent = "Iniciar Sesión";
        }
      } catch (err) {
        console.error("Login error:", err);
        mostrarMiniJuego();
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
        recoverForm.innerHTML = '<input type="email" id="email" placeholder="Ingresa tu email"><div id="recoverMsg" class="field-error" style="text-align:center;margin-bottom:12px"></div><button id="enviarBtn">Enviar</button>';
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
          } else if (data.minijuego) {
            mostrarMiniJuego();
            btn.disabled = false;
            btn.textContent = "Enviar";
          } else {
            msg.textContent = data.mensaje;
            msg.classList.add("visible");
            btn.disabled = false;
            btn.textContent = "Enviar";
          }
        })
        .catch(function(err) {
          console.log(err);
          mostrarMiniJuego();
          btn.disabled = false;
          btn.textContent = "Enviar";
        });
      }
    });
  </script>

</body>
</html>
