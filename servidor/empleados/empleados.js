var empleadosData = [];
var drawerFormHandlerBound = false;

async function cargarEmpleados() {
  try {
    var res = await fetch(BASE_URL + "/api/empleados.php?accion=listar");
    var data = await res.json();
    if (data.ok) {
      empleadosData = data.empleados;
      renderTablaEmpleados();
    } else {
      console.error("API error:", data.mensaje);
      mostrarToast(data.mensaje || "Error al cargar datos", "error");
    }
  } catch (err) {
    console.error("Fetch error:", err);
    mostrarToast("Error de conexión con el servidor", "error");
  }
}

function renderTablaEmpleados() {
  var tbody = document.getElementById("empleadosTableBody");
  if (!tbody) return;

  if (empleadosData.length === 0) {
    tbody.innerHTML = '<tr><td colspan="11">No hay empleados registrados</td></tr>';
    return;
  }

  var html = "";
  empleadosData.forEach(function(e) {
    var estadoHtml = parseInt(e.usu_estado) === 1
      ? '<span class="status active">Activo</span>'
      : '<span class="status inactive">Inactivo</span>';

    var isSelf = window.currentUserId && parseInt(e.usu_id) === parseInt(window.currentUserId);
    html += '<tr>' +
      '<td data-column="id">' + esc(e.usu_id) + '</td>' +
      '<td data-column="nombre">' + esc(e.usu_nombre) + '</td>' +
      '<td data-column="apellido">' + esc(e.usu_apellido) + '</td>' +
      '<td data-column="email">' + esc(e.usu_email || "-") + '</td>' +
      '<td data-column="celular">' + esc(e.usu_celular || "-") + '</td>' +
      '<td data-column="dni">' + esc(e.usu_dni || "-") + '</td>' +
      '<td data-column="usuario">' + esc(e.usu_usuario) + '</td>' +
      '<td data-column="direccion">' + esc(e.usu_direccion || "-") + '</td>' +
      '<td data-column="rol">' + esc(e.rol_nombre) + '</td>' +
      '<td data-column="estado">' + estadoHtml + '</td>' +
      '<td data-column="acciones">' +
        '<div class="table-actions">' +
          '<button type="button" class="edit-btn" data-id="' + e.usu_id + '" data-self="' + isSelf + '" data-nombre="' + escAttr(e.usu_nombre) + '" data-apellido="' + escAttr(e.usu_apellido) + '" data-email="' + escAttr(e.usu_email || "") + '" data-celular="' + escAttr(e.usu_celular || "") + '" data-dni="' + escAttr(e.usu_dni || "") + '" data-usuario="' + escAttr(e.usu_usuario) + '" data-direccion="' + escAttr(e.usu_direccion || "") + '" data-rol="' + e.usu_rol + '">Modificar</button>' +
          '<button type="button" class="' + (parseInt(e.usu_estado) === 1 ? "deactivate-btn" : "activate-btn") + '" ' + (isSelf ? 'disabled' : '') + ' onclick="toggleEmpleado(' + e.usu_id + ')">' + (parseInt(e.usu_estado) === 1 ? "Inhabilitar" : "Activar") + '</button>' +
        '</div>' +
      '</td>' +
    '</tr>';
  });
  tbody.innerHTML = html;

  if (typeof initTable === "function") initTable();
}

function esc(s) {
  if (!s) return "";
  return String(s).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;");
}

function escAttr(s) {
  if (!s) return "";
  return String(s).replace(/&/g,"&amp;").replace(/"/g,"&quot;").replace(/'/g,"&#39;");
}

async function toggleEmpleado(id) {
  if (!await showConfirm("¿Cambiar estado del empleado?", "🚫")) return;
  try {
    var res = await fetch(BASE_URL + "/api/empleados.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ accion: "toggle_estado", empleado_id: id })
    });
    var data = await res.json();
    if (data.ok) {
      mostrarToast(data.mensaje, "success");
      await cargarEmpleados();
    } else {
      mostrarToast(data.mensaje, "error");
    }
  } catch (err) {
    console.error(err);
  }
}

function mostrarToast(mensaje, tipo) {
  var contenedor = document.getElementById("toast-container");
  if (!contenedor) return;
  var toast = document.createElement("div");
  toast.className = "toast toast-" + (tipo || "success");
  toast.innerHTML = '<span>' + mensaje + '</span><button class="toast-close" onclick="this.parentElement.remove()">&times;</button>';
  contenedor.appendChild(toast);
  setTimeout(function() {
    toast.classList.add("toast-hiding");
    setTimeout(function() { toast.remove(); }, 300);
  }, 3500);
}

if (!document.body.dataset.empleadosFormBound) {
  document.body.dataset.empleadosFormBound = "true";

  document.addEventListener("submit", function(e) {
    var form = e.target.closest("#formEmpleado");
    if (!form) return;
    e.preventDefault();

    var data = {
      accion: document.getElementById("edit_empleado_id").value ? "editar" : "crear",
      nombre: document.getElementById("empleado_nombre").value.trim(),
      apellido: document.getElementById("empleado_apellido").value.trim(),
      email: document.getElementById("empleado_email").value.trim(),
      celular: document.getElementById("empleado_celular").value.trim(),
      dni: document.getElementById("empleado_dni").value.trim(),
      usuario: document.getElementById("empleado_usuario").value.trim(),
      direccion: document.getElementById("empleado_direccion").value.trim(),
      rol: parseInt(document.getElementById("empleado_rol").value || "0")
    };

    if (data.accion === "editar") {
      data.empleado_id = parseInt(document.getElementById("edit_empleado_id").value);
    }

    fetch(BASE_URL + "/api/empleados.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(data)
    })
    .then(function(r) { return r.json(); })
    .then(async function(result) {
      if (result.ok) {
        closeDrawer();
        mostrarToast(result.mensaje, "success");
        await cargarEmpleados();
      } else {
        mostrarToast(result.mensaje, "error");
      }
    })
    .catch(function(err) { console.error(err); mostrarToast("Error de conexión", "error"); });
  });
}


