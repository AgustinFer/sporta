async function loadComponent(id, file) {
  const res = await fetch(file);
  const html = await res.text();
  document.getElementById(id).innerHTML = html;
}

function getBasePath() {
  const depth = window.location.pathname.split("/").length - 2;
  return "../".repeat(depth);
}

/* ========================= */
/* 🧠 UI */
/* ========================= */
function initUI() {
  const sidebar = document.querySelector('.sidebar');
  const menuToggle = document.getElementById("menuToggle");

  // evitar duplicación de listeners (FIX CLAVE SPA)
  if (menuToggle && !menuToggle.dataset.bound) {
    menuToggle.dataset.bound = "true";

    menuToggle.onclick = () => {
      sidebar?.classList.toggle('open');
    };
  }

  document.querySelectorAll(".menu-link").forEach(link => {
    if (link.dataset.bound) return;

    link.dataset.bound = "true";

    link.onclick = () => {
      if (window.innerWidth <= 768) {
        sidebar?.classList.remove('open');
      }
    };
  });
}

/* ========================= */
/* 🔥 ACTIVE MENU */
/* ========================= */
function setActiveMenu() {
  const currentPath = window.location.pathname.replace(/\/$/, "");

  document.querySelectorAll(".menu-item").forEach(item => {
    item.classList.remove("active");
  });

  document.querySelectorAll(".menu-link").forEach(link => {
    const route = link.dataset.route;
    if (!route) return;

    if (currentPath.includes(route)) {
      const item = link.closest(".menu-item");
      if (item) item.classList.add("active");
    }
  });
}

/* ========================= */
/* 🚀 SPA ROUTER LOAD */
/* ========================= */
async function loadPage(route) {
  try {
    const base = getBasePath();

    const res = await fetch(base + route + "/index.html");
    const html = await res.text();

    const parser = new DOMParser();
    const doc = parser.parseFromString(html, "text/html");

    /* 1. REEMPLAZO CONTENIDO */
    const newContent = doc.querySelector(".main-content");
    const currentContent = document.querySelector(".main-content");

    if (newContent && currentContent) {
      currentContent.innerHTML = newContent.innerHTML;
    }

    /* 2. BODY STATE */
    document.body.className = doc.body.className;
    document.body.dataset.page = doc.body.dataset.page;

    /* 3. CSS DINÁMICO (FIX ROBUSTO) */
    const newStyle = doc.querySelector("#page-style");

    let currentStyle = document.querySelector("#page-style");

    if (newStyle) {
      if (!currentStyle) {
        currentStyle = document.createElement("link");
        currentStyle.rel = "stylesheet";
        currentStyle.id = "page-style";
        document.head.appendChild(currentStyle);
      }

      currentStyle.href = newStyle.getAttribute("href");
    } else if (currentStyle) {
      currentStyle.remove();
    }

    /* 4. TÍTULO */
    const page = doc.body.dataset.page || "Dashboard";
    const titleEl = document.getElementById("page-title");
    if (titleEl) titleEl.textContent = page;

    /* 5. FECHA */
    const dateEl = document.getElementById("current-date");
    if (dateEl) {
      dateEl.textContent = new Date().toLocaleDateString("es-AR", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric"
      });
    }

    /* 6. FIX VISUAL SPA (REPAINT FORZADO) */
    requestAnimationFrame(() => {
      document.documentElement.scrollTop = 0;
    });

    /* 7. UI REINIT */
    initUI();
    setActiveMenu();

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

    const route = link.dataset.route;
    if (!route) return;

    e.preventDefault();

    history.pushState({}, "", "/" + route);
    loadPage(route);
  });

  window.addEventListener("popstate", () => {
    const route = window.location.pathname.replace("/", "");
    loadPage(route || "inicio");
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

  document.getElementById("page-title").textContent = title;

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
  const page = document.body.dataset.page || "inicio";

  await initLayout(page);

  loadPage(window.location.pathname.replace("/", "") || "inicio");
});