/* ========================= */
/* 🚪 DRAWER GLOBAL */
/* ========================= */

document.addEventListener("click", (e) => {

  /* ========================= */
  /* ➕ ABRIR DESDE FAB */
  /* ========================= */

  if (e.target.closest(".fab")) {

    const form =
      document.querySelector(".drawer form");

    /* ========================= */
    /* RESET FORM */
    /* ========================= */

    if (form) {

      form.reset();

    }

    /* ========================= */
    /* LIMPIAR ID EDIT */
    /* ========================= */

    const editId =
      document.getElementById(
        "edit_cliente_id"
      );

    if (editId) {

      editId.value = "";

    }

    /* ========================= */
    /* TÍTULO */
    /* ========================= */

    const title =
      document.getElementById(
        "drawer-title"
      );

    if (title) {

      title.textContent =
        "Nuevo cliente";

    }

    openDrawer();

  }

  /* ========================= */
  /* ✏️ EDITAR */
  /* ========================= */

  if (e.target.closest(".edit-btn")) {

    e.preventDefault();

    e.stopPropagation();

    const button =
      e.target.closest(".edit-btn");

    /* ========================= */
    /* ABRIR */
    /* ========================= */

    openDrawer();

    /* ========================= */
    /* CARGAR DATOS */
    /* ========================= */

    const editId =
      document.getElementById(
        "edit_cliente_id"
      );

    if (editId) {

      editId.value =
        button.dataset.id;

    }

    const nombre =
      document.getElementById(
        "cliente_nombre"
      );

    if (nombre) {

      nombre.value =
        button.dataset.nombre;

    }

    const apellido =
      document.getElementById(
        "cliente_apellido"
      );

    if (apellido) {

      apellido.value =
        button.dataset.apellido;

    }

    const email =
      document.getElementById(
        "cliente_email"
      );

    if (email) {

      email.value =
        button.dataset.email;

    }

    const celular =
      document.getElementById(
        "cliente_celular"
      );

    if (celular) {

      celular.value =
        button.dataset.celular;

    }

    const dni =
      document.getElementById(
        "cliente_dni"
      );

    if (dni) {

      dni.value =
        button.dataset.dni;

    }

    /* ========================= */
    /* TÍTULO */
    /* ========================= */

    const title =
      document.getElementById(
        "drawer-title"
      );

    if (title) {

      title.textContent =
        "Modificar cliente";

    }

  }

  /* ========================= */
  /* ❌ CERRAR CON X */
  /* ========================= */

  if (e.target.closest(".drawer-close")) {

    closeDrawer();

  }

  /* ========================= */
  /* 🌑 CERRAR OVERLAY */
  /* ========================= */

  if (
    e.target.classList.contains(
      "drawer-overlay"
    )
  ) {

    closeDrawer();

  }

});

/* ========================= */
/* 🚪 OPEN */
/* ========================= */

function openDrawer() {

  const drawer =
    document.querySelector(".drawer");

  const overlay =
    document.querySelector(
      ".drawer-overlay"
    );

  if (!drawer || !overlay) return;

  drawer.classList.add("open");

  overlay.classList.add("open");

  document.body.style.overflow =
    "hidden";

}

/* ========================= */
/* 🚪 CLOSE */
/* ========================= */

function closeDrawer() {

  const drawer =
    document.querySelector(".drawer");

  const overlay =
    document.querySelector(
      ".drawer-overlay"
    );

  if (!drawer || !overlay) return;

  drawer.classList.remove("open");

  overlay.classList.remove("open");

  document.body.style.overflow =
    "";

}