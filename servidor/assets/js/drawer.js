function initDrawer() {

  document.addEventListener("click", (e) => {

    // ABRIR
    if (e.target.closest(".fab")) {

      const drawer = document.querySelector(".drawer");
      const overlay = document.querySelector(".drawer-overlay");

      if (!drawer || !overlay) return;

      drawer.classList.add("open");
      overlay.classList.add("open");

      document.body.style.overflow = "hidden";
    }

    // CERRAR CON X
    if (e.target.closest(".drawer-close")) {

      closeDrawer();
    }

    // CERRAR CON OVERLAY
    if (e.target.classList.contains("drawer-overlay")) {

      closeDrawer();
    }

  });

}

function closeDrawer() {

  const drawer = document.querySelector(".drawer");
  const overlay = document.querySelector(".drawer-overlay");

  if (!drawer || !overlay) return;

  drawer.classList.remove("open");
  overlay.classList.remove("open");

  document.body.style.overflow = "";
}