async function loadComponent(id, file) {
  const res = await fetch(file);
  const html = await res.text();
  document.getElementById(id).innerHTML = html;
}

async function initLayout(title) {
  await loadComponent("sidebar-container", "/components/sidebar.html");
  await loadComponent("header-container", "/components/header.html");

  // título dinámico
  document.getElementById("page-title").textContent = title;

  // fecha
  const date = new Date().toLocaleDateString();
  document.getElementById("current-date").textContent = date;
}

document.addEventListener("DOMContentLoaded", () => {
  const page = document.body.dataset.page || "Dashboard";
  initLayout(page);
});