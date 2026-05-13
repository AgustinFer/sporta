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

  try {

    const page = document.body.dataset.page?.toLowerCase();

    if (!page) return;

    const container = document.getElementById("drawer-container");

    if (!container) return;

    const response = await fetch(
      `/components/drawers/${page}.php?v=${Date.now()}`
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

    const res = await fetch(`/${cleanRoute}/index.php`);

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
    /* 5. DATE */
    /* ========================= */

    const dateEl = document.getElementById("current-date");

    if (dateEl) {

      dateEl.textContent = new Date().toLocaleDateString(
        "es-AR",
        {
          day: "2-digit",
          month: "2-digit",
          year: "numeric"
        }
      );

    }

    /* ========================= */
    /* 6. MENU ACTIVE */
    /* ========================= */

    setActiveMenu(cleanRoute);

    /* ========================= */
    /* 7. UI REINIT */
    /* ========================= */

    initUI();

    /* ========================= */
    /* 8. DRAWER */
    /* ========================= */

    await loadDrawer();

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
      "/" + route
    );

    loadPage(route);

  });

  window.addEventListener("popstate", () => {

    const route = normalizeRoute(
      window.location.pathname
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
    "/components/sidebar.php"
  );

  await loadComponent(
    "header-container",
    "/components/header.php"
  );

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