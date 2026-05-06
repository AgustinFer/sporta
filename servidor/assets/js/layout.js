async function loadComponent(id, file) {
  const res = await fetch(file);
  const html = await res.text();
  document.getElementById(id).innerHTML = html;
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

    link.onclick = (e) => {
      if (window.innerWidth <= 768) {
        sidebar?.classList.remove("open");
      }
    };
  });
}

/* ========================= */
/* 🔥 ACTIVE MENU (FIX ROBUSTO) */
/* ========================= */
function setActiveMenu(route) {
  document.querySelectorAll(".menu-item").forEach(i => i.classList.remove("active"));

  document.querySelectorAll(".menu-link").forEach(link => {
    const r = link.dataset.route;
    if (!r) return;

    const cleanRoute = r.replace("/", "");
    const current = route.replace("/", "");

    if (cleanRoute === current) {
      link.closest(".menu-item")?.classList.add("active");
    }
  });
}

/* ========================= */
/* 🚀 LOAD PAGE */
/* ========================= */
async function loadPage(route) {
  try {
    const cleanRoute = route.replace(/^\/+/, "");

    const res = await fetch(`/${cleanRoute}/index.html`);
    const html = await res.text();

    const parser = new DOMParser();
    const doc = parser.parseFromString(html, "text/html");

    /* 1. CONTENT */
    const newContent = doc.querySelector(".main-content");
    const currentContent = document.querySelector(".main-content");

    if (newContent && currentContent) {
      currentContent.innerHTML = newContent.innerHTML;
    }

    /* 2. BODY STATE */
    document.body.className = doc.body.className;
    document.body.dataset.page = doc.body.dataset.page;

    /* 3. TITLE */
    const titleEl = document.getElementById("page-title");
    if (titleEl) titleEl.textContent = doc.body.dataset.page || "Dashboard";

    /* 4. DATE */
    const dateEl = document.getElementById("current-date");
    if (dateEl) {
      dateEl.textContent = new Date().toLocaleDateString("es-AR", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric"
      });
    }

    /* 5. ACTIVE MENU */
    setActiveMenu(cleanRoute);

    /* 6. UI REINIT */
    initUI();

    /* 7. SCROLL RESET */
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

    const route = link.dataset.route;
    if (!route) return;

    e.preventDefault();

    const cleanRoute = route.replace("/", "");

    history.pushState({}, "", "/" + cleanRoute);
    loadPage(cleanRoute);
  });

  window.addEventListener("popstate", () => {
    const route = window.location.pathname.replace(/^\/+/, "");
    loadPage(route || "inicio");
  });
}

/* ========================= */
/* 🧱 INIT */
/* ========================= */
async function initLayout(title) {
  await loadComponent("sidebar-container", "/components/sidebar.html");
  await loadComponent("header-container", "/components/header.html");

  initRouter();
  initUI();

  const titleEl = document.getElementById("page-title");
  if (titleEl) titleEl.textContent = title;

  const dateEl = document.getElementById("current-date");
  if (dateEl) {
    dateEl.textContent = new Date().toLocaleDateString("es-AR", {
      day: "2-digit",
      month: "2-digit",
      year: "numeric"
    });
  }

  setActiveMenu(title.toLowerCase());
}

/* ========================= */
/* 🚀 START */
/* ========================= */
document.addEventListener("DOMContentLoaded", async () => {
  const page = document.body.dataset.page || "inicio";

  await initLayout(page);
  await loadPage(page);
});