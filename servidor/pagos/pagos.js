var pagosData = [];

async function cargarPagos() {
  try {
    var res = await fetch(BASE_URL + "/api/pagos.php?accion=listar");
    var data = await res.json();
    if (data.ok) {
      pagosData = data.pagos;
      renderTablaPagos();
    } else {
      console.error("API error:", data.mensaje);
      mostrarToast(data.mensaje || "Error al cargar datos", "error");
    }
  } catch (err) {
    console.error("Fetch error:", err);
    mostrarToast("Error de conexión con el servidor", "error");
  }
}

function renderTablaPagos() {
  var tbody = document.getElementById("pagosTableBody");
  if (!tbody) return;
  if (pagosData.length === 0) {
    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:30px;color:#9ca3af;">No hay pagos registrados</td></tr>';
    return;
  }
  var html = "";
  pagosData.forEach(function(p) {
    var monto = parseFloat(p.pago_monto || 0);
    var horario = p.tur_hora_inicio ? p.tur_hora_inicio.substring(0, 5) : '--:--';

    html += '<tr>' +
      '<td data-column="cliente">' + esc(p.cliente_nombre || 'Sin cliente') + '</td>' +
      '<td data-column="cancha">Cancha ' + esc(p.cancha_numero) + '</td>' +
      '<td data-column="fecha">' + esc(p.pago_fecha_pago) + '</td>' +
      '<td data-column="metodo">' + esc(p.metodo_nombre || '—') + '</td>' +
      '<td data-column="monto" class="monto-col">$' + monto.toLocaleString('es-AR', {minimumFractionDigits:2}) + '</td>' +
      '<td data-column="factura">#' + esc(p.factura_id) + '</td>' +
      '<td data-column="acciones">' +
        '<div class="table-actions">' +
          '<button type="button" class="btn-ver" data-factura-id="' + p.factura_id + '" data-cliente="' + escAttr(p.cliente_nombre || 'Sin cliente') + '" data-cancha="Cancha ' + p.cancha_numero + '" data-horario="' + escAttr(p.tur_fecha + ' ' + horario) + '" data-total="' + parseFloat(p.factura_total || 0) + '">Ver factura</button>' +
        '</div>' +
      '</td>' +
    '</tr>';
  });
  tbody.innerHTML = html;

  if (typeof initTable === "function") initTable();
  initFiltrosFecha();
}

/* ========================= */
/* FILTROS */
/* ========================= */

function aplicarFiltros() {
  var searchInput = document.getElementById("tableSearch");
  var term = searchInput ? searchInput.value.toLowerCase() : "";
  var desde = document.getElementById("filterFechaDesde");
  var hasta = document.getElementById("filterFechaHasta");
  var valDesde = desde ? desde.value : "";
  var valHasta = hasta ? hasta.value : "";

  document.querySelectorAll("#pagosTableBody tr").forEach(function(row) {
    if (row.querySelector("td[colspan]")) return;

    var hide = false;

    if (term) {
      var text = row.innerText.toLowerCase();
      if (!text.includes(term)) hide = true;
    }

    if (!hide && valDesde) {
      var fechaCell = row.querySelector('[data-column="fecha"]');
      if (fechaCell) {
        var fechaPago = fechaCell.textContent.trim();
        if (fechaPago < valDesde) hide = true;
      }
    }

    if (!hide && valHasta) {
      var fechaCell = row.querySelector('[data-column="fecha"]');
      if (fechaCell) {
        var fechaPago = fechaCell.textContent.trim();
        if (fechaPago > valHasta) hide = true;
      }
    }

    row.style.display = hide ? "none" : "";
  });
}

function initFiltrosFecha() {
  ["tableSearch", "filterFechaDesde", "filterFechaHasta"].forEach(function(id) {
    var el = document.getElementById(id);
    if (el && !el.dataset.pagosFilterBound) {
      el.dataset.pagosFilterBound = "true";
      el.addEventListener("input", aplicarFiltros);
      el.addEventListener("change", aplicarFiltros);
    }
  });
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

/* ========================= */
/* EVENT DELEGATION */
/* ========================= */

if (!document.body.dataset.pagosFormBound) {
  document.body.dataset.pagosFormBound = "true";

  document.addEventListener("click", function(e) {
    var page = document.body.dataset.page;
    if (!page || page.toLowerCase() !== 'pagos') return;

    var verBtn = e.target.closest(".btn-ver");
    if (!verBtn) return;

    e.preventDefault();
    e.stopPropagation();

    var facturaId = verBtn.dataset.facturaId;
    abrirDetalleFactura(facturaId);
  });
}

function abrirDetalleFactura(facturaId) {
  var container = document.getElementById("pagoHistorialLista");
  if (!container) return;
  container.innerHTML = '<p style="color:#9ca3af;font-size:13px;text-align:center;padding:10px">Cargando...</p>';

  fetch(BASE_URL + "/api/pagos.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ accion: "factura_detalle", factura_id: parseInt(facturaId) })
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    if (!data.ok || !data.factura) {
      container.innerHTML = '<p style="color:#9ca3af;font-size:13px;text-align:center;padding:10px">Error al cargar factura</p>';
      return;
    }

    var f = data.factura;
    var total = parseFloat(f.factura_total || 0);
    var pagado = parseFloat(f.total_pagado || 0);
    var saldo = Math.max(0, total - pagado);

    document.getElementById("pago_factura_id").value = f.factura_id;
    document.getElementById("pago_cliente").textContent = f.cliente_nombre || "";
    document.getElementById("pago_cancha").textContent = "Cancha " + f.cancha_numero;
    document.getElementById("pago_horario").textContent = (f.tur_fecha || "") + " " + (f.tur_hora_inicio ? f.tur_hora_inicio.substring(0,5) : "");
    document.getElementById("pago_total").textContent = "$" + total.toLocaleString('es-AR', {minimumFractionDigits:2});
    document.getElementById("pago_pagado").textContent = "$" + pagado.toLocaleString('es-AR', {minimumFractionDigits:2});
    document.getElementById("pago_saldo").textContent = "$" + saldo.toLocaleString('es-AR', {minimumFractionDigits:2});
    document.getElementById("pago_estado").innerHTML = badgeEstado(f.factura_estado);

    var lista = document.getElementById("pagoHistorialLista");
    if (!data.factura.pagos || data.factura.pagos.length === 0) {
      lista.innerHTML = '<p style="color:#9ca3af;font-size:13px;text-align:center;padding:10px">Sin pagos registrados</p>';
    } else {
      var h = "";
      data.factura.pagos.forEach(function(p) {
        h += '<div class="pago-historial-item">' +
          '<div><span class="pago-monto">$' + parseFloat(p.pago_monto).toLocaleString('es-AR', {minimumFractionDigits:2}) + '</span></div>' +
          '<div><span class="pago-metodo">' + esc(p.metodo_nombre || '') + '</span> &middot; <span class="pago-fecha">' + esc(p.pago_fecha_pago) + '</span></div>' +
          '</div>';
      });
      lista.innerHTML = h;
    }

    document.getElementById("drawer-title").textContent = "Factura #" + f.factura_id;
    openDrawer();
  })
  .catch(function(err) {
    console.error(err);
    container.innerHTML = '<p style="color:#9ca3af;font-size:13px;text-align:center;padding:10px">Error al cargar factura</p>';
  });
}

function badgeEstado(estado) {
  var cls = 'pago-sin';
  var label = 'Sin pago';
  switch ((estado || '').toLowerCase()) {
    case 'pagada': cls = 'pago-pagada'; label = 'Pagada'; break;
    case 'se\xF1a': case 'senia': cls = 'pago-sena'; label = 'Se\xF1a'; break;
    case 'pendiente': cls = 'pago-pendiente'; label = 'Pendiente'; break;
  }
  return '<span class="pago-badge ' + cls + '">' + label + '</span>';
}
