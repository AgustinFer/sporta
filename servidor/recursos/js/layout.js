function getBaseRoute() {

  const path = window.location.pathname;

  if (!BASE_URL) {
    return path;
  }

  return path.replace(BASE_URL, "");

}

async function loadComponent(id, file) {
  const res = await fetch(
    `${file}?v=${Date.now()}`
  );
  const html = await res.text();

  document.getElementById(id).innerHTML = html;
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

    const container =
      document.getElementById("drawer-container");

    if (!container) return;

    const drawer =
      document.body.dataset.drawer;

    if (!drawer) {

        container.innerHTML = "";
        return;

    }

    try {

        const response = await fetch(
            `${BASE_URL}/componentes/drawers/${drawer}.php`
        );

        if (!response.ok) {

            container.innerHTML = "";
            return;

        }

        container.innerHTML =
          await response.text();

    } catch (err) {

        console.error(
          "Error cargando drawer:",
          err
        );

        container.innerHTML = "";

    }

}

/* ========================= */
/* 🚀 LOAD PAGE */
/* ========================= */
async function loadPage(route) {

  try {

    const cleanRoute = normalizeRoute(route);

    const res = await fetch(`${BASE_URL}/${cleanRoute}/index.php`);

    if (!res.ok) {
      throw new Error(`No existe /${cleanRoute}/index.php`);
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

    if (doc.body.dataset.drawer) {

      document.body.dataset.drawer =
        doc.body.dataset.drawer;

    } else {

      delete document.body.dataset.drawer;

    }

    /* ========================= */
    /* 3. CSS DINÁMICO */
    /* ========================= */

    const oldStyle = document.querySelector("#page-style");

    if (oldStyle) {
      oldStyle.remove();
    }

    const newStyle = doc.querySelector("#page-style");

    if (newStyle) {

      const style = document.createElement("link");

      style.id = "page-style";
      style.rel = "stylesheet";

      const href = newStyle.getAttribute("href");

      style.href = `${href}?v=${Date.now()}`;

      document.head.appendChild(style);

      await new Promise(resolve => {

        style.onload = resolve;
        style.onerror = resolve;

      });

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

    const route = normalizeRoute(
      link.dataset.route
    );

    e.preventDefault();

    history.pushState(
      {},
      "",
      `${BASE_URL}/${route}`
    );

    loadPage(route);

  });

  window.addEventListener("popstate", () => {

    const route = normalizeRoute(
      getBaseRoute()
    );

    loadPage(route || "inicio");

  });

}

/* ========================= */
/* 🧱 INIT */
/* ========================= */
async function initLayout() {

  await loadComponent(
    "sidebar-container",
    `${BASE_URL}/componentes/sidebar.php`
  );

  await loadComponent(
    "header-container",
    `${BASE_URL}/componentes/header.php`
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

    const route = normalizeRoute(
      window.location.pathname
    );

    await loadPage(route || "inicio");

  }
);
