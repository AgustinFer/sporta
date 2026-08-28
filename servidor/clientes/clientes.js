var clientesData = [];
var clientesPaginaActual = 1;

async function cargarClientes() {
  try {
    const res = await fetch(BASE_URL + "/api/clientes.php?accion=listar");
    const data = await res.json();
    if (data.ok) {
      clientesData = data.clientes;
      renderTablaClientes();
    } else {
      console.error("API error:", data.mensaje);
      mostrarToast(data.mensaje || "Error al cargar datos", "error");
    }
  } catch (err) {
    console.error("Fetch error:", err);
    mostrarToast("Error de conexión con el servidor", "error");
  }
}

function renderTablaClientes() {
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
          '<button type="button" class="' + (parseInt(c.cliente_estado) === 1 ? "deactivate-btn" : "activate-btn") + '" onclick="toggleCliente(' + c.cliente_id + ')">' + (parseInt(c.cliente_estado) === 1 ? "Inhabilitar" : "Activar") + '</button>' +
        '</div>' +
      '</td>' +
    '</tr>';
  });
  tbody.innerHTML = html;

  if (typeof initTable === "function") initTable();
  initPaginacionClientes();
  aplicarFiltrosClientes();
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
  if (!await showConfirm("¿Cambiar estado del cliente?", "🚫")) return;
  try {
    const res = await fetch(BASE_URL + "/api/clientes.php", {
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

function initPaginacionClientes() {
  var toolbar = document.querySelector(".table-toolbar");
  if (!toolbar) return;
  if (toolbar.dataset.paginacionBound) return;
  toolbar.dataset.paginacionBound = "true";

  var btn = document.createElement("button");
  btn.type = "button";
  btn.className = "limit-selector-btn";
  btn.style.position = "relative";
  btn.dataset.limitValue = "10";

  var labelSpan = document.createElement("span");
  labelSpan.textContent = "Mostrar: 10 \u25BC";
  btn.appendChild(labelSpan);

  var dropdown = document.createElement("div");
  dropdown.className = "limit-selector-dropdown";

  var options = [
    { value: 10, label: "10" },
    { value: 20, label: "20" },
    { value: 50, label: "50" },
    { value: 0, label: "Todos" }
  ];

  options.forEach(function(opt) {
    var item = document.createElement("div");
    item.className = "limit-selector-item";
    item.textContent = opt.label;
    item.dataset.value = opt.value;
    item.addEventListener("click", function() {
      labelSpan.textContent = "Mostrar: " + opt.label + " \u25BC";
      btn.dataset.limitValue = opt.value;
      dropdown.classList.remove("open");
      clientesPaginaActual = 1;
      aplicarFiltrosClientes();
    });
    dropdown.appendChild(item);
  });

  btn.appendChild(dropdown);

  var columnBtn = toolbar.querySelector(".column-picker-btn");
  if (columnBtn) {
    toolbar.insertBefore(btn, columnBtn);
  } else {
    toolbar.appendChild(btn);
  }

  btn.addEventListener("click", function(e) {
    e.stopPropagation();
    dropdown.classList.toggle("open");
  });

  document.addEventListener("click", function() {
    dropdown.classList.remove("open");
  });

  dropdown.addEventListener("click", function(e) {
    e.stopPropagation();
  });

  var showWrapper = document.getElementById("showInactivos");
  if (!showWrapper) return;

  var pagBar = document.createElement("div");
  pagBar.className = "paginacion-bar";

  var prevBtn = document.createElement("button");
  prevBtn.type = "button";
  prevBtn.className = "paginacion-btn prev-btn";
  prevBtn.innerHTML = "\u2190 Anterior";
  pagBar.appendChild(prevBtn);

  var indicator = document.createElement("span");
  indicator.id = "pagIndicator";
  indicator.className = "pag-indicator";
  indicator.textContent = "1/1";
  pagBar.appendChild(indicator);

  var nextBtn = document.createElement("button");
  nextBtn.type = "button";
  nextBtn.className = "paginacion-btn next-btn";
  nextBtn.innerHTML = "Siguiente \u2192";
  pagBar.appendChild(nextBtn);

  var container = showWrapper.parentNode.parentNode;
  container.insertBefore(pagBar, showWrapper.parentNode.nextSibling);

  prevBtn.addEventListener("click", function() {
    if (clientesPaginaActual > 1) {
      clientesPaginaActual--;
      aplicarFiltrosClientes();
    }
  });

  nextBtn.addEventListener("click", function() {
    var totalPaginas = calcularTotalPaginas();
    if (clientesPaginaActual < totalPaginas) {
      clientesPaginaActual++;
      aplicarFiltrosClientes();
    }
  });

  var searchInput = document.getElementById("tableSearch");
  var showInactivos = document.getElementById("showInactivos");

  if (searchInput) {
    searchInput.addEventListener("input", function() {
      clientesPaginaActual = 1;
      aplicarFiltrosClientes();
    });
  }

  if (showInactivos) {
    showInactivos.addEventListener("change", function() {
      clientesPaginaActual = 1;
      aplicarFiltrosClientes();
    });
  }
}

function calcularTotalPaginas() {
  var limit = parseInt(document.querySelector(".limit-selector-btn").dataset.limitValue);
  if (limit === 0) return 1;
  var rows = document.querySelectorAll("#clientesTableBody tr");
  var visibles = 0;
  rows.forEach(function(r) {
    if (r.style.display !== "none") visibles++;
  });
  return Math.max(1, Math.ceil(visibles / limit));
}

function aplicarFiltrosClientes() {
  var rows = document.querySelectorAll("#clientesTableBody tr");
  var limit = parseInt(document.querySelector(".limit-selector-btn").dataset.limitValue);
  var pagBar = document.querySelector(".paginacion-bar");

  rows.forEach(function(r) { r.classList.remove("pag-hidden"); });

  if (limit === 0) {
    if (pagBar) pagBar.style.display = "none";
    return;
  }

  if (pagBar) pagBar.style.display = "";

  var visibles = [];
  rows.forEach(function(r) {
    if (r.style.display !== "none") visibles.push(r);
  });

  var totalPaginas = Math.max(1, Math.ceil(visibles.length / limit));
  if (clientesPaginaActual > totalPaginas) clientesPaginaActual = totalPaginas;

  var start = (clientesPaginaActual - 1) * limit;
  var end = start + limit;

  visibles.forEach(function(r, idx) {
    if (idx < start || idx >= end) r.classList.add("pag-hidden");
  });

  var indicator = document.getElementById("pagIndicator");
  if (indicator) indicator.textContent = clientesPaginaActual + "/" + totalPaginas;

  var prevBtn = document.querySelector(".prev-btn");
  var nextBtn = document.querySelector(".next-btn");
  if (prevBtn) prevBtn.disabled = clientesPaginaActual <= 1;
  if (nextBtn) nextBtn.disabled = clientesPaginaActual >= totalPaginas;
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

    fetch(BASE_URL + "/api/clientes.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(data)
    })
    .then(function(r) { return r.json(); })
    .then(async function(result) {
      if (result.ok) {
        closeDrawer();
        mostrarToast(result.mensaje, "success");
        await cargarClientes();
      } else {
        mostrarToast(result.mensaje, "error");
      }
    })
    .catch(function(err) { console.error(err); mostrarToast("Error de conexión", "error"); });
  });
}
