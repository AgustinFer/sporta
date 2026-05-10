<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sporta - Inicio</title>

  <!-- CSS GLOBAL -->
  <link rel="stylesheet" href="/assets/css/global.css">
  <link rel="stylesheet" href="/assets/css/layout.css">
  <link rel="stylesheet" href="/assets/css/components.css">
  <link rel="stylesheet" href="/assets/css/drawer.css">

  <!-- CSS MÓDULO -->
  <link id="page-style" rel="stylesheet" href="/inicio/inicio.css">
</head>

<body class="screen" data-page="Inicio">

  <div class="background"></div>
  <div id="sidebar-container"></div>

  <button class="menu-toggle" id="menuToggle">☰</button>

  <main class="main-content">
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

  <!-- JS LAYOUT (carga sidebar + header) -->
  <script src="/assets/js/layout.js"></script>

  <!-- JS DRAWER -->
  <script src="/assets/js/drawer.js"></script>
  <!-- FIN CONFIG GLOBAL -->

  <!-- CLOCK -->
  <script>
    function updateClock() {
      const now = new Date();

      const time = now.toLocaleTimeString("es-AR", {
        hour: "2-digit",
        minute: "2-digit"
      });

      const date = now.toLocaleDateString("es-AR", {
        weekday: "long",
        day: "numeric",
        month: "long"
      });

      document.getElementById("clock").textContent = time;
      document.getElementById("date").textContent = date;
    }

    updateClock();
    setInterval(updateClock, 1000);
  </script>

<script>

  async function loadWeather() {

    try {

      // Coordenadas Berazategui
      const lat = -34.7653;
      const lon = -58.2128;

      const response = await fetch(
        `https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lon}&current=temperature_2m,weather_code`
      );

      const data = await response.json();

      const temp = Math.round(data.current.temperature_2m);

      const code = data.current.weather_code;

      const weatherMap = {
        0: ["☀️", "Despejado"],
        1: ["🌤️", "Mayormente despejado"],
        2: ["⛅", "Parcialmente nublado"],
        3: ["☁️", "Nublado"],
        45: ["🌫️", "Niebla"],
        48: ["🌫️", "Niebla"],
        51: ["🌦️", "Llovizna"],
        61: ["🌧️", "Lluvia"],
        63: ["🌧️", "Lluvia"],
        65: ["⛈️", "Tormenta"],
        71: ["❄️", "Nieve"]
      };

      const weather = weatherMap[code] || ["🌡️", "Clima"];

      document.getElementById("weatherTemp").textContent =
        `${temp}°C`;

      document.getElementById("weatherDesc").textContent =
        weather[1];

      document.getElementById("weatherIcon").textContent =
        weather[0];

    } catch (err) {

      console.error(err);

      document.getElementById("weatherDesc").textContent =
        "No disponible";
    }
  }

  loadWeather();

</script>

</body>
</html>
