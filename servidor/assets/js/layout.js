async function loadComponent(id, file) {
  const res = await fetch(file);
  const html = await res.text();
  document.getElementById(id).innerHTML = html;
}

function setActiveMenu() {
  const currentPath = window.location.pathname.replace(/\/$/, "");

  const links = document.querySelectorAll(".menu-link");

  links.forEach(link => {
    const linkPath = new URL(link.href).pathname.replace(/\/$/, "");

    if (currentPath === linkPath) {
      const item = link.closest(".menu-item");
      if (item) {
        item.classList.add("active");
      }
    }
  });
}

async function initLayout(title) {
  await loadComponent("sidebar-container", "/components/sidebar.html");
  await loadComponent("header-container", "/components/header.html");

  // título
  document.getElementById("page-title").textContent = title;

  // fecha
  const date = new Date().toLocaleDateString();
  document.getElementById("current-date").textContent = date;

  // 🔥 activar menú
  setActiveMenu();
}

document.addEventListener("DOMContentLoaded", () => {
  const page = document.body.dataset.page || "Dashboard";
  initLayout(page);
});