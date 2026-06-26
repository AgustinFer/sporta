function initDrawerValidation() {
  var form = document.querySelector(".drawer-form");
  if (!form || form.dataset.validationBound) return;
  form.dataset.validationBound = "true";

  var reglas = {
    soloLetras: /^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s]+$/,
    soloNumeros: /^\d{7,8}$/,
    telefono: /^\d{7,10}$/,
    email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
    usuario: /^[a-zA-Z0-9_]{3,20}$/
  };

  var mensajes = {
    soloLetras: "Solo se permiten letras",
    soloNumeros: "Debe tener entre 7 y 8 dígitos numéricos",
    telefono: "Debe tener entre 7 y 10 dígitos numéricos",
    email: "Ingrese un email válido",
    usuario: "Entre 3 y 20 caracteres alfanuméricos"
  };

  function validarCampo(input) {
    var regla = input.dataset.validate;
    var errorSpan = document.getElementById("error_" + input.id);
    if (!regla || !errorSpan) return true;

    var valor = input.value.trim();
    var regex = reglas[regla];

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
    var inputs = form.querySelectorAll("[data-validate]");
    var valido = true;

    inputs.forEach(function(input) {
      if (!validarCampo(input)) {
        valido = false;
      }
    });

    if (!valido) {
      e.preventDefault();
      e.stopPropagation();
      document.querySelector(".drawer-form [data-validate].input-error")?.focus();
    }
  });

  form.addEventListener("input", function(e) {
    var input = e.target;
    var regla = input.dataset.validate;
    if (!regla) return;

    if (regla === "soloLetras") {
      input.value = input.value.replace(/[^a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s]/g, "");
    } else if (regla === "soloNumeros" || regla === "telefono") {
      input.value = input.value.replace(/\D/g, "");
    }

    var errorSpan = document.getElementById("error_" + input.id);
    if (input.classList.contains("input-error") || errorSpan?.classList.contains("visible")) {
      validarCampo(input);
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
}

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
