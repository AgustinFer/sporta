/* ========================= */
/* ⚙️ CONFIG */
/* ========================= */

const drawerConfig = {

  clientes: {
    prefix: "cliente",
    titleNew: "Nuevo cliente",
    titleEdit: "Modificar cliente",
    fields: ["nombre", "apellido", "email", "celular", "dni"]
  },

  empleados: {
    prefix: "empleado",
    titleNew: "Nuevo empleado",
    titleEdit: "Modificar empleado",
    fields: ["nombre", "apellido", "email", "celular", "dni", "usuario", "direccion", "rol"]
  },

  reservas: {
    prefix: "reserva",
    titleNew: "Nueva reserva",
    titleEdit: "Modificar reserva",
    fields: [] // lo que uses en ese módulo
  }

};

/* ========================= */
/* 🚪 DRAWER GLOBAL */
/* ========================= */

document.addEventListener("click", (e) => {

  /* ========================= */
  /* ❌ CERRAR (siempre) */
  /* ========================= */

  if (e.target.closest(".drawer-close")) {
    closeDrawer();
    return;
  }

  if (e.target.classList.contains("drawer-overlay")) {
    closeDrawer();
    return;
  }

  const drawerKey =
    document.body.dataset.drawer;

  const config =
    drawerConfig[drawerKey];

  if (!config) return;

/* ========================= */
/* ➕ FAB (NUEVO) */
/* ========================= */

  if (e.target.closest(".fab")) {

    const form =
      document.querySelector(".drawer form");

    if (form) form.reset();

    const editId =
      document.getElementById(`edit_${config.prefix}_id`);

    if (editId) editId.value = "";

    const title =
      document.getElementById("drawer-title");

    if (title) title.textContent = config.titleNew;

    if (typeof limpiarErroresDrawer === "function") {
      limpiarErroresDrawer();
    }

    var rolSelectFAB = document.getElementById("empleado_rol");
    if (rolSelectFAB) {
      rolSelectFAB.disabled = false;
      var hint = document.querySelector(".self-rol-hint");
      if (hint) hint.remove();
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

    openDrawer();

    const editId =
      document.getElementById(`edit_${config.prefix}_id`);

    if (editId) editId.value = button.dataset.id;

    config.fields.forEach(field => {

      const input =
        document.getElementById(`${config.prefix}_${field}`);

      if (input) {
        input.value = button.dataset[field] || "";
      }

    });

    const title =
      document.getElementById("drawer-title");

    if (title) title.textContent = config.titleEdit;

    if (button.dataset.self === "true" || button.dataset.self === true) {
      var rolSelect = document.getElementById("empleado_rol");
      if (rolSelect) {
        rolSelect.disabled = true;
        var label = document.querySelector('label[for="empleado_rol"]') || rolSelect.closest('div')?.querySelector('label');
        if (label) {
          var hint = document.createElement("small");
          hint.className = "self-rol-hint";
          hint.textContent = "No puedes modificar tu propio rol";
          var existing = label.parentElement.querySelector(".self-rol-hint");
          if (existing) existing.remove();
          label.parentElement.appendChild(hint);
        }
      }
    } else {
      var rolSelect = document.getElementById("empleado_rol");
      if (rolSelect) {
        rolSelect.disabled = false;
        var hint = document.querySelector(".self-rol-hint");
        if (hint) hint.remove();
      }
    }

  }

});

/* ========================= */
/* 🚪 OPEN */
/* ========================= */

function openDrawer() {

  const drawer = document.querySelector(".drawer");
  const overlay = document.querySelector(".drawer-overlay");

  if (!drawer || !overlay) return;

  drawer.classList.add("open");
  overlay.classList.add("open");

  document.body.style.overflow = "hidden";
}

/* ========================= */
/* 🚪 CLOSE */
/* ========================= */

function closeDrawer() {

  const drawer = document.querySelector(".drawer");
  const overlay = document.querySelector(".drawer-overlay");

  if (!drawer || !overlay) return;

  drawer.classList.remove("open");
  overlay.classList.remove("open");

  document.body.style.overflow = "";
}