async function loadComponent(id, file) {
  const res = await fetch(file);
  const html = await res.text();
  document.getElementById(id).innerHTML = html;
}

/* ========================= */
/* 🧠 NORMALIZADOR DE RUTAS */
/* ========================= */
function normalizeRoute(route) {
  return route
    .replace(/^\/+/, "")   // sin slash inicial
    .replace(/\/+$/, "")   // sin slash final
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

  document.querySelectorAll(".menu-item").forEach(i => i.classList.remove("active"));

  document.querySelectorAll(".menu-link").forEach(link => {
    const r = normalizeRoute(link.dataset.route || "");

    if (r === current) {
      link.closest(".menu-item")?.classList.add("active");
    }
  });
}

/* ========================= */
/* 🚀 LOAD PAGE (FIX DEFINITIVO) */
/* ========================= */
async function loadPage(route) {
  try {
    const cleanRoute = normalizeRoute(route);

    const res = await fetch(`/${cleanRoute}/index.html`);
    if (!res.ok) throw new Error(`No existe /${cleanRoute}/index.html`);

    const html = await res.text();

    const doc = new DOMParser().parseFromString(html, "text/html");

    /* 1. CONTENT */
    const newContent = doc.querySelector(".main-content");
    const currentContent = document.querySelector(".main-content");

    if (newContent && currentContent) {
      currentContent.innerHTML = newContent.innerHTML;
    }

    /* 2. BODY STATE */
    document.body.className = doc.body.className || "";
    document.body.dataset.page = doc.body.dataset.page || cleanRoute;

    /* 3. TITLE */
    const titleEl = document.getElementById("page-title");
    if (titleEl) {
      titleEl.textContent =
        doc.body.dataset.page || cleanRoute.charAt(0).toUpperCase() + cleanRoute.slice(1);
    }

    /* 4. DATE */
    const dateEl = document.getElementById("current-date");
    if (dateEl) {
      dateEl.textContent = new Date().toLocaleDateString("es-AR", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric"
      });
    }

    /* 5. MENU */
    setActiveMenu(cleanRoute);

    /* 6. UI */
    initUI();

    /* 7. RESET SCROLL */
    window.scrollTo(0, 0);

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

    const route = normalizeRoute(link.dataset.route);
    e.preventDefault();

    history.pushState({}, "", "/" + route);
    loadPage(route);
  });

  window.addEventListener("popstate", () => {
    const route = normalizeRoute(window.location.pathname);
    loadPage(route || "inicio");
  });
}

/* ========================= */
/* 🧱 INIT */
/* ========================= */
async function initLayout() {
  await loadComponent("sidebar-container", "/components/sidebar.html");
  await loadComponent("header-container", "/components/header.html");

  initRouter();
  initUI();
}

/* ========================= */
/* 🚀 START (CRÍTICO) */
/* ========================= */
document.addEventListener("DOMContentLoaded", async () => {
  await initLayout();

  const route = normalizeRoute(window.location.pathname || "inicio");
  await loadPage(route);
});