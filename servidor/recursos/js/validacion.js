(function() {

  const reglas = {
    soloLetras: /^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s]+$/,
    soloNumeros: /^\d{1,8}$/,
    telefono: /^\d{1,10}$/,
    email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  };

  const mensajes = {
    soloLetras: "Solo se permiten letras",
    soloNumeros: "Máximo 8 dígitos numéricos",
    telefono: "Máximo 10 dígitos numéricos",
    email: "Ingrese un email válido"
  };

  const form = document.querySelector(".drawer-form");
  if (!form) return;

  function validarCampo(input) {
    const regla = input.dataset.validate;
    const errorSpan = document.getElementById("error_" + input.id);
    if (!regla || !errorSpan) return true;

    const valor = input.value.trim();
    const regex = reglas[regla];

    if (valor === "") {
      if (input.required) {
        mostrarError(input, errorSpan, "Este campo es obligatorio");
        return false;
      }
      limpiarError(input, errorSpan);
      return true;
    }

    if (regex && !regex.test(valor)) {
      mostrarError(input, errorSpan, mensajes[regla] || "Valor inválido");
      return false;
    }

    limpiarError(input, errorSpan);
    return true;
  }

  function mostrarError(input, errorSpan, msg) {
    input.classList.add("input-error");
    errorSpan.textContent = msg;
    errorSpan.classList.add("visible");
  }

  function limpiarError(input, errorSpan) {
    input.classList.remove("input-error");
    if (errorSpan) {
      errorSpan.textContent = "";
      errorSpan.classList.remove("visible");
    }
  }

  form.addEventListener("submit", function(e) {
    const inputs = form.querySelectorAll("[data-validate]");
    let valido = true;

    inputs.forEach(function(input) {
      if (!validarCampo(input)) {
        valido = false;
      }
    });

    if (!valido) {
      e.preventDefault();
      document.querySelector(".drawer-form [data-validate].input-error")?.focus();
    }
  });

  form.addEventListener("input", function(e) {
    const input = e.target;
    if (input.dataset.validate) {
      const errorSpan = document.getElementById("error_" + input.id);
      if (input.classList.contains("input-error") || errorSpan?.classList.contains("visible")) {
        validarCampo(input);
      }
    }
  });

  window.limpiarErroresDrawer = function() {
    form.querySelectorAll(".input-error").forEach(function(el) {
      el.classList.remove("input-error");
    });
    form.querySelectorAll(".field-error.visible").forEach(function(el) {
      el.textContent = "";
      el.classList.remove("visible");
    });
  };

})();

/* ========================= */
/* 🚪 RESTAURAR DRAWER (form_data) */
/* ========================= */

function initDrawerPage() {

  if (typeof formData === "undefined" || !formData) return;

  var page = document.body.dataset.page;
  if (!page) return;

  var key = page.toLowerCase();
  var config = drawerConfig[key];
  if (!config) return;

  openDrawer();

  var title = document.getElementById("drawer-title");
  if (title) {
    title.textContent = formData.edit_id ? config.titleEdit : config.titleNew;
  }

  var editId = document.getElementById("edit_" + config.prefix + "_id");
  if (editId) {
    editId.value = formData.edit_id || "";
  }

  config.fields.forEach(function(field) {
    var input = document.getElementById(config.prefix + "_" + field);
    if (input && formData[field] !== undefined) {
      input.value = formData[field];
    }
  });

  if (typeof limpiarErroresDrawer === "function") {
    limpiarErroresDrawer();
  }

  formData = null;

}
