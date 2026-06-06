/* ========================= */
/* ⚙️ CONFIG */
/* ========================= */

const drawerConfig = {

  clientes: {

    prefix: "cliente",

    titleNew:
      "Nuevo cliente",

    titleEdit:
      "Modificar cliente",

    fields: [
      "nombre",
      "apellido",
      "email",
      "celular",
      "dni"
    ]

  },

  empleados: {

    prefix: "empleado",

    titleNew:
      "Nuevo empleado",

    titleEdit:
      "Modificar empleado",

    fields: [
      "nombre",
      "apellido",
      "email",
      "celular",
      "dni",
      "usuario",
      "direccion"
    ]

  }

};

/* ========================= */
/* 🚪 DRAWER GLOBAL */
/* ========================= */

document.addEventListener("click", (e) => {

  const page =
    document.body.dataset.page
      ?.toLowerCase();

  const config =
    drawerConfig[page];

  if (!config) return;

  /* ========================= */
  /* ➕ FAB */
  /* ========================= */

  if (e.target.closest(".fab")) {

    const form =
      document.querySelector(
        ".drawer form"
      );

    if (form) {

      form.reset();

    }

    const editId =
      document.getElementById(
        `edit_${config.prefix}_id`
      );

    if (editId) {

      editId.value = "";

    }

    const title =
      document.getElementById(
        "drawer-title"
      );

    if (title) {

      title.textContent =
        config.titleNew;

    }

    openDrawer();

  }

  /* ========================= */
  /* ✏️ EDIT */
  /* ========================= */

  if (e.target.closest(".edit-btn")) {

    e.preventDefault();

    e.stopPropagation();

    const button =
      e.target.closest(
        ".edit-btn"
      );

    openDrawer();

    const editId =
      document.getElementById(
        `edit_${config.prefix}_id`
      );

    if (editId) {

      editId.value =
        button.dataset.id;

    }

    config.fields.forEach(field => {

      const input =
        document.getElementById(
          `${config.prefix}_${field}`
        );

      if (input) {

        input.value =
          button.dataset[field] || "";

      }

    });

    const title =
      document.getElementById(
        "drawer-title"
      );

    if (title) {

      title.textContent =
        config.titleEdit;

    }

  }

  /* ========================= */
  /* ❌ CLOSE X */
  /* ========================= */

  if (
    e.target.closest(
      ".drawer-close"
    )
  ) {

    closeDrawer();

  }

  /* ========================= */
  /* 🌑 CLOSE OVERLAY */
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
    document.querySelector(
      ".drawer"
    );

  const overlay =
    document.querySelector(
      ".drawer-overlay"
    );

  if (!drawer || !overlay) {
    return;
  }

  drawer.classList.add(
    "open"
  );

  overlay.classList.add(
    "open"
  );

  document.body.style.overflow =
    "hidden";

}

/* ========================= */
/* 🚪 CLOSE */
/* ========================= */

function closeDrawer() {

  const drawer =
    document.querySelector(
      ".drawer"
    );

  const overlay =
    document.querySelector(
      ".drawer-overlay"
    );

  if (!drawer || !overlay) {
    return;
  }

  drawer.classList.remove(
    "open"
  );

  overlay.classList.remove(
    "open"
  );

  document.body.style.overflow =
    "";

}