function initDrawerValidation() {
  var reglas = {
    soloLetras: /^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s]+$/,
    soloNumeros: /^\d{7,8}$/,
    telefono: /^\d{7,10}$/,
    email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
    usuario: /^[a-zA-Z0-9_]{3,20}$/,
    monto: /^\d{1,9}([.,]\d{1,2})?$/
  };

  var mensajes = {
    soloLetras: "Solo se permiten letras",
    soloNumeros: "Debe tener entre 7 y 8 dígitos numéricos",
    telefono: "Debe tener entre 7 y 10 dígitos numéricos",
    email: "Ingrese un email válido",
    usuario: "Entre 3 y 20 caracteres alfanuméricos",
    monto: "Ingrese un monto válido"
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

  function bindForm(form) {
    if (form.dataset.validationBound) return;
    form.dataset.validationBound = "true";

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
        var firstError = form.querySelector("[data-validate].input-error");
        if (firstError) firstError.focus();
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
      } else if (regla === "monto") {
        var v = input.value.replace(/[^0-9.,]/g, "");
        v = v.replace(/,/g, ".");
        var dot = v.indexOf(".");
        if (dot !== -1) v = v.slice(0, dot + 1) + v.slice(dot + 1).replace(/\./g, "");
        input.value = v;
      }

      var errorSpan = document.getElementById("error_" + input.id);
      if (input.classList.contains("input-error") || errorSpan?.classList.contains("visible")) {
        validarCampo(input);
      }
    });
  }

  var forms = document.querySelectorAll("#drawer-container .drawer-form");
  if (forms.length === 0) forms = document.querySelectorAll(".drawer-form");
  forms.forEach(bindForm);

  window.limpiarErroresDrawer = function() {
    document.querySelectorAll(".drawer-form .input-error").forEach(function(el) {
      el.classList.remove("input-error");
    });
    document.querySelectorAll(".drawer-form .field-error.visible").forEach(function(el) {
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
