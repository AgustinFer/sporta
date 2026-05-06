async function loadComponent(id, file) {
  const res = await fetch(file);
  const html = await res.text();
  document.getElementById(id).innerHTML = html;
}

function getBasePath() {
  const depth = window.location.pathname.split("/").length - 2;
  return "../".repeat(depth);
}

function initUI() {
  const menuToggle = document.getElementById('menuToggle');

  if (menuToggle) {
    menuToggle.onclick = () => {
      document.querySelector('.sidebar')?.classList.toggle('open');
    };
  }

  document.addEventListener('click', (e) => {
    if (e.target.classList.contains('menu-link') && window.innerWidth <= 768) {
      document.querySelector('.sidebar')?.classList.remove('open');
    }
  });
}

/* ========================= */
/* 🔥 ACTIVAR MENÚ */
/* ========================= */
function setActiveMenu() {
  const currentPath = window.location.pathname.replace(/\/$/, "");

  document.querySelectorAll(".menu-item").forEach(item => {
    item.classList.remove("active");
  });

  document.querySelectorAll(".menu-link").forEach(link => {
    const route = link.dataset.route;
    if (!route) return;

    if (currentPath === route) {
      const item = link.closest(".menu-item");
      if (item) item.classList.add("active");
    }
  });
}

/* ========================= */
/* 🚀 CARGA DINÁMICA (SPA) */
/* ========================= */
async function loadPage(route) {
  try {
    const base = getBasePath();

    const res = await fetch(base + route + "/index.html");
    const html = await res.text();

    const parser = new DOMParser();
    const doc = parser.parseFromString(html, "text/html");

    const newContent = doc.querySelector(".main-content");
    const currentContent = document.querySelector(".main-content");

    if (newContent && currentContent) {
      currentContent.innerHTML = newContent.innerHTML;
    }

    /* ========================= */
    /* 🔥 CAMBIO DE CLASE BODY */
    /* ========================= */
    document.body.className = doc.body.className;

    /* ========================= */
    /* 🎨 FONDO DINÁMICO */
    /* ========================= */
    // borrar fondo anterior
    document.querySelectorAll("[data-dynamic-bg]").forEach(el => el.remove());

    // agregar nuevo fondo si existe
    const bgClass = doc.body.dataset.bg;
    if (bgClass) {
      const bg = document.createElement("div");
      bg.className = bgClass;
      bg.setAttribute("data-dynamic-bg", "true");
      document.body.prepend(bg);
    }

    /* ========================= */
    /* 🧾 TÍTULO */
    /* ========================= */
    const page = doc.body.dataset.page || "Dashboard";
    const titleEl = document.getElementById("page-title");
    if (titleEl) titleEl.textContent = page;

    /* ========================= */
    /* 📅 FECHA */
    /* ========================= */
    const dateEl = document.getElementById("current-date");
    if (dateEl) {
      dateEl.textContent = new Date().toLocaleDateString("es-AR", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric"
      });
    }

    setActiveMenu();

  } catch (err) {
    console.error("Error cargando página:", err);
  }
}

/* ========================= */
/* 🧠 ROUTER */
/* ========================= */
function initRouter() {
  document.addEventListener("click", (e) => {
    const link = e.target.closest(".menu-link");
    if (!link) return;

    const route = link.dataset.route;
    if (!route) return;

    e.preventDefault();

    history.pushState({}, "", route);
    loadPage(route);
  });

  window.addEventListener("popstate", () => {
    loadPage(window.location.pathname);
  });
}

/* ========================= */
/* 🧱 INIT */
/* ========================= */
async function initLayout(title) {
  const base = getBasePath();

  await loadComponent("sidebar-container", base + "components/sidebar.html");
  await loadComponent("header-container", base + "components/header.html");

  initRouter();
  initUI();

  // título inicial
  const titleEl = document.getElementById("page-title");
  if (titleEl) titleEl.textContent = title;

  // fecha inicial
  const dateEl = document.getElementById("current-date");
  if (dateEl) {
    dateEl.textContent = new Date().toLocaleDateString("es-AR", {
      day: "2-digit",
      month: "2-digit",
      year: "numeric"
    });
  }

  setActiveMenu();
}

/* ========================= */
/* 🚀 START */
/* ========================= */
document.addEventListener("DOMContentLoaded", async () => {
  const page = document.body.dataset.page || "Dashboard";

  await initLayout(page);

  // cargar contenido inicial (clave SPA)
  loadPage(window.location.pathname);
});