async function loadComponent(id, file) {
  const res = await fetch(
    resolveAppPath(`${file}?v=${Date.now()}`)
  );
  const html = await res.text();

  document.getElementById(id).innerHTML = html;
}

const APP_BASE = (function() {
  const script = document.currentScript || document.querySelector('script[src*="layout.js"]');
  const pathname = new URL(script.src, window.location.origin).pathname;
  return pathname.replace(/\/assets\/js\/layout\.js$/, "") || "/";
})();

function resolveAppPath(path) {
  const base = APP_BASE.endsWith("/") ? APP_BASE : APP_BASE + "/";
  return new URL(path, window.location.origin + base).href;
}

function getRoutePath(route) {
  const clean = normalizeRoute(route);
  if (!clean) {
    return APP_BASE;
  }
  const path = APP_BASE === "/" ? `/${clean}` : `${APP_BASE}/${clean}`;
  return path.replace(/\/+/g, "/");
}

/* ========================= */
/* 🧠 NORMALIZADOR DE RUTAS */
/* ========================= */
function normalizeRoute(route) {
  return route
    .replace(/^\/+/, "")
    .replace(/\/+$/, "")
    .toLowerCase();
}

/* ========================= */
/* 🧠 UI */
/* ========================= */
function initUI() {

  const sidebar = document.querySelector(".sidebar");
  const menuToggle = document.getElementById("menuToggle");

  if (menuToggle && !menuToggle.dataset.bound) {

    menuToggle.dataset.bound = "true";

    menuToggle.onclick = () => {
      sidebar?.classList.toggle("open");
    };
  }

  document.querySelectorAll(".menu-link").forEach(link => {

    if (link.dataset.bound) return;

    link.dataset.bound = "true";

    link.onclick = () => {

      if (window.innerWidth <= 768) {
        sidebar?.classList.remove("open");
      }

    };

  });

}

/* ========================= */
/* 🔥 ACTIVE MENU */
/* ========================= */
function setActiveMenu(route) {

  const current = normalizeRoute(route);

  document
    .querySelectorAll(".menu-item")
    .forEach(i => i.classList.remove("active"));

  document.querySelectorAll(".menu-link").forEach(link => {

    const r = normalizeRoute(link.dataset.route || "");

    if (r === current) {
      link.closest(".menu-item")?.classList.add("active");
    }

  });

}

/* ========================= */
/* 🚪 DRAWER */
/* ========================= */
async function loadDrawer() {

  try {

    const page = document.body.dataset.page?.toLowerCase();

    if (!page) return;

    const container = document.getElementById("drawer-container");

    if (!container) return;

    const response = await fetch(
      resolveAppPath(`components/drawers/${page}.php?v=${Date.now()}`)
    );

    if (!response.ok) {

      container.innerHTML = "";

      return;
    }

    const html = await response.text();

    container.innerHTML = html;

  } catch (err) {

    console.error("Error cargando drawer:", err);

  }

}

/* ========================= */
/* 🚀 LOAD PAGE */
/* ========================= */
async function loadPage(route) {

  try {

    const cleanRoute = normalizeRoute(route);
    const res = await fetch(resolveAppPath(`${cleanRoute}/index.php`));

    if (!res.ok) {
      throw new Error(`No existe ${resolveAppPath(`${cleanRoute}/index.php`)}`);
    }

    const html = await res.text();

    const doc = new DOMParser().parseFromString(
      html,
      "text/html"
    );

    /* ========================= */
    /* 1. CONTENT */
    /* ========================= */

    const newContent = doc.querySelector(".main-content");
    const currentContent = document.querySelector(".main-content");

    if (newContent && currentContent) {
      currentContent.innerHTML = newContent.innerHTML;
    }

    /* ========================= */
    /* 2. BODY STATE */
    /* ========================= */

    document.body.className = "";

    doc.body.classList.forEach(c => {
      document.body.classList.add(c);
    });

    document.body.dataset.page =
      doc.body.dataset.page || cleanRoute;

    /* ========================= */
    /* 3. CSS DINÁMICO */
    /* ========================= */

      const oldStyle = document.querySelector("#page-style");
      const newStyle = doc.querySelector("#page-style");

      if (newStyle) {
        const style = document.createElement("link");

        style.id = "page-style";
        style.rel = "stylesheet";

        const href = newStyle.getAttribute("href");

        // Resolver rutas relativas del CSS respecto a la URL del documento cargado
        const resolvedHref = (function() {
          try {
            return new URL(href, res.url).href;
          } catch (e) {
            return href;
          }
        })();

        style.href = `${resolvedHref}?v=${Date.now()}`;

        // Primero añadimos la nueva hoja y esperamos a que cargue
        document.head.appendChild(style);

        await new Promise(resolve => {
          style.onload = resolve;
          style.onerror = resolve;
        });

        // Una vez cargada (o fallida), eliminamos la antigua para evitar perder estilos
        if (oldStyle) {
          oldStyle.remove();
        }

      }

    /* ========================= */
    /* 4. TITLE */
    /* ========================= */

    const pageTitle =
      doc.body.dataset.page ||
      cleanRoute.charAt(0).toUpperCase() +
      cleanRoute.slice(1);

    const titleEl = document.getElementById("page-title");

    if (titleEl) {
      titleEl.textContent = pageTitle;
    }

    document.title = `Sporta - ${pageTitle}`;

    /* ========================= */
    /* 5. MENU ACTIVE */
    /* ========================= */

    setActiveMenu(cleanRoute);

    /* ========================= */
    /* 6. UI REINIT */
    /* ========================= */

    initUI();

    /* ========================= */
    /* 7. DRAWER */
    /* ========================= */

    await loadDrawer();

    /* ========================= */
    /* 8. PAGE INIT */
    /* ========================= */

    if (cleanRoute === "inicio") {
      initInicio();
    }

    /* ========================= */
    /* 9. RESET VISUAL */
    /* ========================= */

    window.scrollTo(0, 0);

    document.body.style.background = "";
    document.documentElement.style.background = "";

  } catch (err) {

    console.error("Error cargando página:", err);

  }

}

/* ========================= */
/* DATE */
/* ========================= */

function updateDate() {

  const dateEl =
    document.getElementById("current-date");

  if (!dateEl) return;

  dateEl.textContent =
    new Date().toLocaleDateString(
      "es-AR",
      {
        day: "2-digit",
        month: "2-digit",
        year: "numeric"
      }
    );

}

/* ========================= */
/* 🏠 INICIO */
/* ========================= */

let clockInterval = null;

function initInicio() {

  initClock();

  loadWeather();

}

/* ========================= */
/* ⏰ CLOCK */
/* ========================= */

function initClock() {

  if (clockInterval) {
    clearInterval(clockInterval);
  }

  function updateClock() {

    const clock =
      document.getElementById("clock");

    const date =
      document.getElementById("date");

    if (!clock || !date) return;

    const now = new Date();

    clock.textContent =
      now.toLocaleTimeString(
        "es-AR",
        {
          hour: "2-digit",
          minute: "2-digit"
        }
      );

    date.textContent =
      now.toLocaleDateString(
        "es-AR",
        {
          weekday: "long",
          day: "numeric",
          month: "long"
        }
      );

  }

  updateClock();

  clockInterval =
    setInterval(updateClock, 1000);

}

/* ========================= */
/* 🌤️ WEATHER */
/* ========================= */

async function loadWeather() {

  try {

    const tempEl =
      document.getElementById("weatherTemp");

    const descEl =
      document.getElementById("weatherDesc");

    const iconEl =
      document.getElementById("weatherIcon");

    if (!tempEl || !descEl || !iconEl) {
      return;
    }

    const lat = -34.7653;
    const lon = -58.2128;

    const response = await fetch(
      `https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lon}&current=temperature_2m,weather_code`
    );

    const data = await response.json();

    const temp =
      Math.round(
        data.current.temperature_2m
      );

    const code =
      data.current.weather_code;

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

    const weather =
      weatherMap[code] ||
      ["🌡️", "Clima"];

    tempEl.textContent =
      `${temp}°C`;

    descEl.textContent =
      weather[1];

    iconEl.textContent =
      weather[0];

  } catch (err) {

    console.error(
      "Error clima:",
      err
    );

  }

}

/* ========================= */
/* 🧭 ROUTER */
/* ========================= */
function initRouter() {

  document.addEventListener("click", (e) => {

    const link = e.target.closest(".menu-link");

    if (!link) return;

    // Si el enlace no tiene `data-route`, dejar que el navegador lo maneje (ej. logout)
    if (!link.dataset.route) return;

    const route = normalizeRoute(
      link.dataset.route
    );

    e.preventDefault();

    history.pushState(
      {},
      "",
      getRoutePath(route)
    );

    loadPage(route);

  });

  window.addEventListener("popstate", () => {

    const path = APP_BASE === "/"
      ? window.location.pathname
      : window.location.pathname.replace(APP_BASE, "");

    const route = normalizeRoute(path);

    loadPage(route || "inicio");

  });

}

/* ========================= */
/* 🧱 INIT */
/* ========================= */
async function initLayout() {

  await loadComponent(
    "sidebar-container",
    "components/sidebar.php"
  );

  await loadComponent(
    "header-container",
    "components/header.php"
  );

  updateDate();

  initRouter();

  initUI();

}

/* ========================= */
/* 🚀 START */
/* ========================= */
document.addEventListener(
  "DOMContentLoaded",
  async () => {

    await initLayout();

    const path = APP_BASE === "/"
      ? window.location.pathname
      : window.location.pathname.replace(APP_BASE, "");

    const route = normalizeRoute(path);

    await loadPage(route || "inicio");

  }
);