/* ========================= */
/* 🚪 DRAWER GLOBAL */
/* ========================= */

document.addEventListener("click", (e) => {

  /* ========================= */
  /* ABRIR DRAWER */
  /* ========================= */

  if (e.target.closest(".fab")) {

    const drawer = document.querySelector(".drawer");
    const overlay = document.querySelector(".drawer-overlay");

    if (!drawer || !overlay) return;

    drawer.classList.add("open");
    overlay.classList.add("open");

    document.body.style.overflow = "hidden";
  }

  /* ========================= */
  /* CERRAR CON X */
  /* ========================= */

  if (e.target.closest(".drawer-close")) {

    closeDrawer();
  }

  /* ========================= */
  /* CERRAR OVERLAY */
  /* ========================= */

  if (e.target.classList.contains("drawer-overlay")) {

    closeDrawer();
  }

});

/* ========================= */
/* CLOSE */
/* ========================= */

function closeDrawer() {

  const drawer = document.querySelector(".drawer");
  const overlay = document.querySelector(".drawer-overlay");

  if (!drawer || !overlay) return;

  drawer.classList.remove("open");
  overlay.classList.remove("open");

  document.body.style.overflow = "";

}