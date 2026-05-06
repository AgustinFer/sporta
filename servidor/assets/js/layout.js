async function loadComponent(id, file) {
  const res = await fetch(file);
  const html = await res.text();
  document.getElementById(id).innerHTML = html;
}

function getBasePath() {
  const depth = window.location.pathname.split("/").length - 2;
  return "../".repeat(depth);
}

function setActiveMenu() {
  const currentPath = window.location.pathname.replace(/\/$/, "");

  document.querySelectorAll(".menu-item").forEach(item => {
    item.classList.remove("active");
  });

  document.querySelectorAll(".menu-link").forEach(link => {
    const linkPath = new URL(link.href).pathname.replace(/\/$/, "");

    if (currentPath === linkPath) {
      const item = link.closest(".menu-item");
      if (item) item.classList.add("active");
    }
  });
}

async function initLayout(title) {
  const base = getBasePath();

  await loadComponent("sidebar-container", base + "components/sidebar.html");
  await loadComponent("header-container", base + "components/header.html");

  // título
  const titleEl = document.getElementById("page-title");
  if (titleEl) titleEl.textContent = title;

  // fecha
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

document.addEventListener("DOMContentLoaded", () => {
  const page = document.body.dataset.page || "Dashboard";
  initLayout(page);
});