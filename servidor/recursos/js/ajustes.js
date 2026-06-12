function initAjustes() {

  var passInput = document.getElementById("pass_nueva");
  if (!passInput) return;

  var passConfirm = document.getElementById("pass_confirmar");
  var errorPass = document.getElementById("error_password");
  var reqLength = document.getElementById("req-length");
  var reqUpper  = document.getElementById("req-upper");
  var reqSpecial = document.getElementById("req-special");

  function validarPassword(valor) {
    var cumple = {
      length: valor.length >= 6,
      upper: /[A-Z]/.test(valor),
      special: /[^a-zA-Z0-9]/.test(valor)
    };
    reqLength.classList.toggle("req-ok", cumple.length);
    reqUpper.classList.toggle("req-ok", cumple.upper);
    reqSpecial.classList.toggle("req-ok", cumple.special);
    return cumple.length && cumple.upper && cumple.special;
  }

  function validarConfirmacion() {
    if (!passConfirm.value) {
      errorPass.textContent = "";
      errorPass.classList.remove("visible");
      return true;
    }
    if (passConfirm.value !== passInput.value) {
      errorPass.textContent = "Las contraseñas no coinciden";
      errorPass.classList.add("visible");
      return false;
    }
    errorPass.textContent = "";
    errorPass.classList.remove("visible");
    return true;
  }

  passInput.addEventListener("input", function() {
    validarPassword(passInput.value);
    if (passConfirm.value) validarConfirmacion();
  });

  passConfirm.addEventListener("input", validarConfirmacion);

  /* ========================= */
  /* VALIDACIÓN USUARIO (AJAX) */
  /* ========================= */

  var usuarioInput = document.getElementById("nuevo_usuario");
  var errorUsuario = document.getElementById("error_usuario");
  var okUsuario   = document.getElementById("ok_usuario");
  var usuarioOriginal = usuarioInput ? usuarioInput.value : "";
  var checkTimeout = null;

  if (usuarioInput) {

    usuarioInput.addEventListener("input", function() {
      var val = usuarioInput.value.trim();

      if (val === usuarioOriginal) {
        errorUsuario.textContent = "";
        errorUsuario.classList.remove("visible");
        okUsuario.textContent = "";
        return;
      }

      if (val.length < 3) {
        errorUsuario.textContent = "Mínimo 3 caracteres";
        errorUsuario.classList.add("visible");
        okUsuario.textContent = "";
        return;
      }

      if (checkTimeout) clearTimeout(checkTimeout);

      checkTimeout = setTimeout(function() {
        fetch(BASE_URL + "/ajustes/?check_usuario=" + encodeURIComponent(val))
          .then(function(r) { return r.json(); })
          .then(function(data) {
            if (data.disponible) {
              errorUsuario.textContent = "";
              errorUsuario.classList.remove("visible");
              okUsuario.textContent = "✓ Disponible";
            } else {
              errorUsuario.textContent = "El nombre de usuario ya está en uso";
              errorUsuario.classList.add("visible");
              okUsuario.textContent = "";
            }
          })
          .catch(function() {
            errorUsuario.textContent = "Error al verificar";
            errorUsuario.classList.add("visible");
          });
      }, 400);
    });
  }

  /* ========================= */
  /* SUBMIT PASSWORD */
  /* ========================= */

  var formPass = document.getElementById("formPassword");
  if (formPass) {
    formPass.addEventListener("submit", function(e) {
      var validaPass = validarPassword(passInput.value);
      var validaConf = validarConfirmacion();
      if (!validaPass || !validaConf) {
        e.preventDefault();
        if (!validaPass) passInput.focus();
      }
    });
  }

  /* ========================= */
  /* SUBMIT USUARIO */
  /* ========================= */

  var formUser = document.getElementById("formUsuario");
  if (formUser) {
    formUser.addEventListener("submit", function(e) {
      var val = usuarioInput.value.trim();
      if (val === usuarioOriginal) {
        e.preventDefault();
        return;
      }
      if (val.length < 3) {
        e.preventDefault();
        errorUsuario.textContent = "Mínimo 3 caracteres";
        errorUsuario.classList.add("visible");
        usuarioInput.focus();
        return;
      }
      if (errorUsuario.classList.contains("visible")) {
        e.preventDefault();
        usuarioInput.focus();
      }
    });
  }
}
