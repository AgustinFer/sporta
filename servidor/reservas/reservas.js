var reservasData = [];

async function cargarReservas() {
  try {
    var res = await fetch(BASE_URL + "/api/reservas.php?accion=listar");
    var data = await res.json();
    if (data.ok) {
      reservasData = data.reservas;
      if (data.metodos_pago) {
        reservasMetodosPago = data.metodos_pago;
      }
      renderTablaReservas();
    } else {
      console.error("API error:", data.mensaje);
      mostrarToast(data.mensaje || "Error al cargar datos", "error");
    }
  } catch (err) {
    console.error("Fetch error:", err);
    mostrarToast("Error de conexión con el servidor", "error");
  }
}

function renderTablaReservas() {
  var tbody = document.getElementById("reservasTableBody");
  if (!tbody) return;
  if (reservasData.length === 0) {
    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:30px;color:#9ca3af;">No hay reservas registradas</td></tr>';
    return;
  }
  var html = "";
  reservasData.forEach(function(r, i) {
    var estadoHtml = badgeEstado(r.reser_estado, r.estado_reserva_descripcion);
    var pagoHtml = badgePago(r.factura_estado, r.total_pagado, r.factura_total);
    var saldo = (parseFloat(r.factura_total || 0) - parseFloat(r.total_pagado || 0)).toFixed(2);
    var saldoHtml = saldo > 0
      ? '<span class="saldo-positivo">$' + Number(saldo).toLocaleString('es-AR', {minimumFractionDigits:2}) + '</span>'
      : '<span class="saldo-cero">$0,00</span>';
    var clienteNombre = (r.cliente_nombre || '') + ' ' + (r.cliente_apellido || '');
    var horario = r.tur_hora_inicio ? r.tur_hora_inicio.substring(0, 5) : '--:--';
    html += '<tr>' +
      '<td data-column="cliente">' + esc(clienteNombre.trim() || 'Sin cliente') + '</td>' +
      '<td data-column="cancha">Cancha ' + esc(r.cancha_numero) + '</td>' +
      '<td data-column="fecha">' + esc(r.tur_fecha) + '</td>' +
      '<td data-column="horario">' + esc(horario) + '</td>' +
      '<td data-column="estado">' + estadoHtml + '</td>' +
      '<td data-column="pago">' + pagoHtml + '</td>' +
      '<td data-column="saldo">' + saldoHtml + '</td>' +
      '<td data-column="acciones">' +
        '<div class="table-actions">' +
          '<button type="button" class="edit-btn" data-id="' + r.reserva_id + '" data-estado="' + r.reser_estado + '" data-observaciones="' + escAttr(r.reser_observaciones || '') + '" data-cliente="' + escAttr(clienteNombre.trim()) + '" data-cancha="Cancha ' + r.cancha_numero + '" data-horario="' + escAttr(r.tur_fecha + ' ' + horario) + '">Modificar</button>' +
          '<button type="button" class="btn-pago" data-id="' + r.reserva_id + '" data-cliente="' + escAttr(clienteNombre.trim()) + '" data-cancha="Cancha ' + r.cancha_numero + '" data-total="' + r.factura_total + '" data-pagado="' + (r.total_pagado || 0) + '">Pago</button>' +
        '</div>' +
      '</td>' +
    '</tr>';
  });
  tbody.innerHTML = html;
  if (typeof initTable === "function") {
    initTable();
  }
  initFiltroPendientes();
  initFiltroHoy();
}

function badgeEstado(estado, descripcion) {
  var cls = '';
  switch (parseInt(estado)) {
    case 1: cls = 'status'; break;
    case 2: cls = 'status active'; break;
    case 3: cls = 'status inactive'; break;
    default: cls = 'status';
  }
  return '<span class="' + cls + '">' + esc(descripcion || '') + '</span>';
}

function badgePago(estado, pagado, total) {
  pagado = parseFloat(pagado || 0);
  total = parseFloat(total || 0);

  if (estado === 'Pagada' || (total > 0 && pagado >= total)) {
    return '<span class="pago-badge pago-pagada">Pagada</span>';
  } else if (pagado > 0) {
    return '<span class="pago-badge pago-sena">Seña</span>';
  } else if (estado === 'Pendiente') {
    return '<span class="pago-badge pago-pendiente">Pendiente</span>';
  } else {
    return '<span class="pago-badge pago-sin">Sin pago</span>';
  }
}

function esc(s) {
  if (!s) return "";
  return String(s).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;");
}

function escAttr(s) {
  if (!s) return "";
  return String(s).replace(/&/g,"&amp;").replace(/"/g,"&quot;").replace(/'/g,"&#39;");
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

var reservasMetodosPago = [];

async function cargarMetodosPago() {
  try {
    var res = await fetch(BASE_URL + "/api/metodos_pago.php?accion=listar");
    var data = await res.json();
    if (data.ok) {
      reservasMetodosPago = data.metodos_pago;
    }
  } catch (err) {
    console.error(err);
  }
}

function llenarSelectMetodosPago() {
  var select = document.getElementById("metodo_pago_id");
  if (!select) return;
  var html = '<option value="">Seleccionar método</option>';
  reservasMetodosPago.forEach(function(mp) {
    html += '<option value="' + mp.metodo_pago_id + '">' + esc(mp.metodo_nombre) + '</option>';
  });
  select.innerHTML = html;
}

/* ========================= */
/* EVENT DELEGATION */
/* ========================= */

if (!document.body.dataset.reservasFormBound) {
  document.body.dataset.reservasFormBound = "true";

  /* FAB not needed for this module, but handle drawer open via button clicks */

  document.addEventListener("click", function(e) {
    var page = document.body.dataset.page;
    if (!page || page.toLowerCase() !== 'señas y reservas') return;

    /* Botón Modificar */
    var editBtn = e.target.closest(".edit-btn");
    if (editBtn) {
      e.preventDefault();
      e.stopPropagation();

      document.getElementById("edit_reserva_id").value = editBtn.dataset.id;
      document.getElementById("reser_estado").value = editBtn.dataset.estado;
      document.getElementById("reser_observaciones").value = editBtn.dataset.observaciones || "";
      document.getElementById("edit_cliente").textContent = editBtn.dataset.cliente || "";
      document.getElementById("edit_cancha").textContent = editBtn.dataset.cancha || "";
      document.getElementById("edit_horario").textContent = editBtn.dataset.horario || "";

      document.getElementById("panelPago").style.display = "none";
      document.getElementById("panelEditar").style.display = "block";
      document.getElementById("drawer-title").textContent = "Modificar reserva";

      openDrawer();
      return;
    }

    /* Botón Pago */
    var pagoBtn = e.target.closest(".btn-pago");
    if (pagoBtn) {
      e.preventDefault();
      e.stopPropagation();

      var reservaId = pagoBtn.dataset.id;
      document.getElementById("pago_reserva_id").value = reservaId;
      document.getElementById("pago_cliente").textContent = pagoBtn.dataset.cliente || "";
      document.getElementById("pago_cancha").textContent = pagoBtn.dataset.cancha || "";

      var totalFact = parseFloat(pagoBtn.dataset.total || 0);
      var pagado = parseFloat(pagoBtn.dataset.pagado || 0);
      var saldo = Math.max(0, totalFact - pagado);

      document.getElementById("pago_total").textContent = "$" + totalFact.toLocaleString('es-AR', {minimumFractionDigits:2});
      document.getElementById("pago_pagado").textContent = "$" + pagado.toLocaleString('es-AR', {minimumFractionDigits:2});
      document.getElementById("pago_saldo").textContent = "$" + saldo.toLocaleString('es-AR', {minimumFractionDigits:2});
      document.getElementById("pago_monto").value = saldo > 0 ? saldo.toFixed(2) : "";
      document.getElementById("pago_monto").max = saldo.toFixed(2);
      document.getElementById("pago_fecha").value = new Date().toISOString().split('T')[0];

      document.getElementById("panelEditar").style.display = "none";
      document.getElementById("panelPago").style.display = "block";
      document.getElementById("drawer-title").textContent = "Registrar pago / Seña";

      llenarSelectMetodosPago();
      cargarHistorialPagos(reservaId, totalFact, pagado);

      openDrawer();
      return;
    }
  });

  /* Submit: Editar reserva */
  document.addEventListener("submit", function(e) {
    var page = document.body.dataset.page;
    if (!page || page.toLowerCase() !== 'señas y reservas') return;

    var formEditar = e.target.closest("#formEditarReserva");
    if (formEditar) {
      e.preventDefault();

      var reservaId = document.getElementById("edit_reserva_id").value;
      var data = {
        accion: "editar",
        reserva_id: parseInt(reservaId),
        reser_estado: parseInt(document.getElementById("reser_estado").value),
        reser_observaciones: document.getElementById("reser_observaciones").value.trim()
      };

      fetch(BASE_URL + "/api/reservas.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data)
      })
      .then(function(r) { return r.json(); })
      .then(function(result) {
        if (result.ok) {
          closeDrawer();
          mostrarToast(result.mensaje, "success");
          cargarReservas();
        } else {
          mostrarToast(result.mensaje, "error");
        }
      })
      .catch(function(err) { console.error(err); });
      return;
    }

    /* Submit: Registrar pago */
    var formPago = e.target.closest("#formRegistrarPago");
    if (formPago) {
      e.preventDefault();

      var reservaId = document.getElementById("pago_reserva_id").value;
      var monto = parseFloat(document.getElementById("pago_monto").value);

      var data = {
        accion: "registrar_pago",
        reserva_id: parseInt(reservaId),
        monto: monto,
        metodo_pago_id: parseInt(document.getElementById("metodo_pago_id").value),
        fecha_pago: document.getElementById("pago_fecha").value
      };

      fetch(BASE_URL + "/api/reservas.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data)
      })
      .then(function(r) { return r.json(); })
      .then(function(result) {
        if (result.ok) {
          closeDrawer();
          mostrarToast(result.mensaje, "success");
          cargarReservas();
        } else {
          mostrarToast(result.mensaje, "error");
        }
      })
      .catch(function(err) { console.error(err); });
      return;
    }
  });
}

function cargarHistorialPagos(reservaId, totalFact, pagado) {
  var container = document.getElementById("pagoHistorialLista");
  if (!container) return;

  fetch(BASE_URL + "/api/reservas.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ accion: "pago_historial", reserva_id: parseInt(reservaId) })
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    if (!data.ok || !data.pagos || data.pagos.length === 0) {
      container.innerHTML = '<p style="color:#9ca3af;font-size:13px;text-align:center;padding:10px">Sin pagos registrados</p>';
      return;
    }

    var html = "";
    data.pagos.forEach(function(p) {
      html += '<div class="pago-historial-item">' +
        '<div><span class="pago-monto">$' + parseFloat(p.pago_monto).toLocaleString('es-AR', {minimumFractionDigits:2}) + '</span></div>' +
        '<div><span class="pago-metodo">' + esc(p.metodo_nombre || '') + '</span> &middot; <span class="pago-fecha">' + esc(p.pago_fecha_pago) + '</span></div>' +
        '</div>';
    });
    container.innerHTML = html;
  })
  .catch(function(err) {
    console.error(err);
    container.innerHTML = '<p style="color:#9ca3af;font-size:13px;text-align:center;padding:10px">Error al cargar historial</p>';
  });
}

/* ========================= */
/* FILTROS */
/* ========================= */

function aplicarFiltros() {
  var mostrarSoloPendientes = document.getElementById("showSoloPendientes")?.checked;
  var mostrarSoloHoy = document.getElementById("showSoloHoy")?.checked;
  var hoyStr = new Date().toISOString().split('T')[0];

  document.querySelectorAll("#reservasTableBody tr").forEach(function(row) {
    if (row.querySelector("td[colspan]")) return;

    if (mostrarSoloHoy) {
      var fechaCell = row.querySelector('[data-column="fecha"]');
      if (!fechaCell || fechaCell.textContent.trim() !== hoyStr) {
        row.style.display = "none";
        return;
      }
    }

    if (mostrarSoloPendientes) {
      var pagoCell = row.querySelector('[data-column="pago"]');
      if (!pagoCell) return;
      var esPendiente = pagoCell.querySelector(".pago-pendiente, .pago-sin");
      if (!esPendiente) {
        row.style.display = "none";
        return;
      }
    }

    row.style.display = "";
  });
}

function initFiltroPendientes() {
  var chk = document.getElementById("showSoloPendientes");
  if (!chk || chk.dataset.bound) return;
  chk.dataset.bound = "true";
  chk.addEventListener("change", aplicarFiltros);
}

function initFiltroHoy() {
  var chk = document.getElementById("showSoloHoy");
  if (!chk || chk.dataset.bound) return;
  chk.dataset.bound = "true";
  chk.addEventListener("change", aplicarFiltros);
}


