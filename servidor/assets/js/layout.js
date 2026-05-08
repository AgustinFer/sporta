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

    /* 2. BODY STATE (FIX ROBUSTO) */
    // limpiamos clases sin romper estructura base
    document.body.className = "";
    doc.body.classList.forEach(c => document.body.classList.add(c));

    document.body.dataset.page = doc.body.dataset.page || cleanRoute;

    /* 3. CSS DINÁMICO (CRÍTICO PARA TU BUG) 
    const newStyle = doc.querySelector("#page-style");
    const currentStyle = document.querySelector("#page-style");

    const fallbackCss = `/${cleanRoute}/${cleanRoute}.css`;

    if (currentStyle) {
      if (newStyle?.getAttribute("href")) {
        currentStyle.href = newStyle.getAttribute("href");
      } else {
        currentStyle.href = fallbackCss;
      }
    } */

    /* 3. CSS DINÁMICO (FIX DEFINITIVO) */

    // borrar CSS anterior
    const oldStyle = document.querySelector("#page-style");
    if (oldStyle) {
      oldStyle.remove();
    }

    // obtener nuevo CSS
    const newStyle = doc.querySelector("#page-style");

    if (newStyle) {
      const style = document.createElement("link");

      style.id = "page-style";
      style.rel = "stylesheet";

      // anti-cache SPA
      const href = newStyle.getAttribute("href");
      style.href = `${href}?v=${Date.now()}`;

      document.head.appendChild(style);

      // esperar carga REAL del CSS
      await new Promise(resolve => {
        style.onload = resolve;
        style.onerror = resolve;
      });
    }

    /* 4. TITLE */
    const titleEl = document.getElementById("page-title");
    if (titleEl) {
      titleEl.textContent =
        doc.body.dataset.page ||
        cleanRoute.charAt(0).toUpperCase() + cleanRoute.slice(1);
    }

    const pageTitle =
      doc.body.dataset.page ||
      cleanRoute.charAt(0).toUpperCase() + cleanRoute.slice(1);

    if (titleEl) {
      titleEl.textContent = pageTitle;
    }

    /* 🔥 TÍTULO DEL NAVEGADOR */
    document.title = `Sporta - ${pageTitle}`;

    /* 5. DATE */
    const dateEl = document.getElementById("current-date");
    if (dateEl) {
      dateEl.textContent = new Date().toLocaleDateString("es-AR", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric"
      });
    }

    /* 6. MENU ACTIVE */
    setActiveMenu(cleanRoute);

    /* 7. UI REINIT */
    initUI();

    /* 8. RESET VISUAL (EVITA GLITCHES DE FONDO) */
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

  const route = normalizeRoute(window.location.pathname);

  await loadPage(route || "inicio");
});