var clientesData = [];

async function cargarClientes() {
  try {
    const res = await fetch("../api/clientes.php?accion=listar");
    const data = await res.json();
    if (data.ok) {
      clientesData = data.clientes;
      renderTabla();
    } else {
      console.error("API error:", data.mensaje);
      mostrarToast(data.mensaje || "Error al cargar datos", "error");
    }
  } catch (err) {
    console.error("Fetch error:", err);
    mostrarToast("Error de conexión con el servidor", "error");
  }
}

function renderTabla() {
  var tbody = document.getElementById("clientesTableBody");
  if (!tbody) return;

  if (clientesData.length === 0) {
    tbody.innerHTML = '<tr><td colspan="8">No hay clientes registrados</td></tr>';
    return;
  }

  var html = "";
  clientesData.forEach(function(c) {
    var estadoHtml = parseInt(c.cliente_estado) === 1
      ? '<span class="status active">Activo</span>'
      : '<span class="status inactive">Inactivo</span>';

    html += '<tr>' +
      '<td data-column="id">' + esc(c.cliente_id) + '</td>' +
      '<td data-column="nombre">' + esc(c.cliente_nombre) + '</td>' +
      '<td data-column="apellido">' + esc(c.cliente_apellido) + '</td>' +
      '<td data-column="email">' + esc(c.cliente_email || "-") + '</td>' +
      '<td data-column="celular">' + esc(c.cliente_celular || "-") + '</td>' +
      '<td data-column="dni">' + esc(c.cliente_dni || "-") + '</td>' +
      '<td data-column="estado">' + estadoHtml + '</td>' +
      '<td data-column="acciones">' +
        '<div class="table-actions">' +
          '<button type="button" class="edit-btn" data-id="' + c.cliente_id + '" data-nombre="' + escAttr(c.cliente_nombre) + '" data-apellido="' + escAttr(c.cliente_apellido) + '" data-email="' + escAttr(c.cliente_email || "") + '" data-celular="' + escAttr(c.cliente_celular || "") + '" data-dni="' + escAttr(c.cliente_dni || "") + '">Modificar</button>' +
          '<button type="button" class="' + (parseInt(c.cliente_estado) === 1 ? "deactivate-btn" : "activate-btn") + '" onclick="toggleCliente(' + c.cliente_id + ')">' + (parseInt(c.cliente_estado) === 1 ? "Inactivar" : "Activar") + '</button>' +
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

async function toggleCliente(id) {
  if (!confirm("¿Cambiar estado del cliente?")) return;
  try {
    const res = await fetch("../api/clientes.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ accion: "toggle_estado", cliente_id: id })
    });
    const data = await res.json();
    if (data.ok) {
      mostrarToast(data.mensaje, "success");
      await cargarClientes();
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

if (!document.body.dataset.clientesFormBound) {
  document.body.dataset.clientesFormBound = "true";

  document.addEventListener("submit", function(e) {
    var form = e.target.closest("#formCliente");
    if (!form) return;

    e.preventDefault();

    var data = {
      accion: document.getElementById("edit_cliente_id").value ? "editar" : "crear",
      nombre: document.getElementById("cliente_nombre").value.trim(),
      apellido: document.getElementById("cliente_apellido").value.trim(),
      email: document.getElementById("cliente_email").value.trim(),
      celular: document.getElementById("cliente_celular").value.trim(),
      dni: document.getElementById("cliente_dni").value.trim()
    };

    if (data.accion === "editar") {
      data.cliente_id = parseInt(document.getElementById("edit_cliente_id").value);
    }

    fetch("../api/clientes.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(data)
    })
    .then(function(r) { return r.json(); })
    .then(function(result) {
      if (result.ok) {
        closeDrawer();
        mostrarToast(result.mensaje, "success");
        cargarClientes();
      } else {
        mostrarToast(result.mensaje, "error");
      }
    })
    .catch(function(err) { console.error(err); });
  });
}
