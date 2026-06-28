var logsData = [];

async function cargarLogs() {
  try {
    var fechaInput = document.getElementById("filterFecha");
    var fecha = fechaInput ? fechaInput.value : "";
    var url = BASE_URL + "/api/logs.php?accion=listar";
    if (fecha) url += "&fecha=" + fecha;
    var res = await fetch(url);
    var data = await res.json();
    if (data.ok) {
      logsData = data.logs;
      renderTablaLogs();
    } else {
      mostrarToast(data.mensaje || "Error al cargar logs", "error");
    }
  } catch (err) {
    console.error("Fetch error:", err);
    mostrarToast("Error de conexión con el servidor", "error");
  }
}

function renderTablaLogs() {
  var tbody = document.getElementById("logsTableBody");
  if (!tbody) return;
  if (logsData.length === 0) {
    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:30px;color:#9ca3af;">No hay registros para esta fecha</td></tr>';
    return;
  }
  var html = "";
  logsData.forEach(function(entry, idx) {
    var hora = entry.ts ? entry.ts.split(" ")[1] || entry.ts : "--:--";
    var accion = entry.accion || "—";
    var usuario = entry.user_nombre || "anónimo";
    var ip = entry.ip || "—";
    var detalleStr = entry.detalle && Object.keys(entry.detalle).length > 0 ? JSON.stringify(entry.detalle, null, 2) : "";

    html += "<tr>" +
      '<td data-column="hora">' + esc(hora) + "</td>" +
      '<td data-column="usuario">' + esc(usuario) + "</td>" +
      '<td data-column="accion">' + esc(accion) + "</td>" +
      '<td data-column="ip">' + esc(ip) + "</td>" +
      '<td data-column="detalle">' +
        (detalleStr
          ? '<button type="button" class="log-detalle-btn" data-log-index="' + idx + '">Ver detalle</button>'
          : "—") +
      "</td>" +
      "</tr>";
  });
  tbody.innerHTML = html;

  if (typeof initTable === "function") initTable();
  initFiltroFecha();
}

function initFiltroFecha() {
  var fechaInput = document.getElementById("filterFecha");
  if (!fechaInput || fechaInput.dataset.logsFilterBound) return;
  fechaInput.dataset.logsFilterBound = "true";

  if (!fechaInput.value) {
    fechaInput.value = new Date().toLocaleDateString("en-CA");
  }

  fechaInput.addEventListener("change", function() {
    cargarLogs();
  });

  var searchInput = document.getElementById("tableSearch");
  if (searchInput && !searchInput.dataset.logsSearchBound) {
    searchInput.dataset.logsSearchBound = "true";
    searchInput.addEventListener("input", function() {
      if (typeof initTable === "function") initTable();
    });
  }

}

function abrirDetalleLog(entry) {
  if (!entry || !entry.detalle || Object.keys(entry.detalle).length === 0) return;

  var container = document.getElementById("drawer-container");
  if (!container) return;

  var html =
    '<div class="drawer-overlay"></div>' +
    '<aside class="drawer">' +
      '<div class="drawer-header">' +
        '<h3 id="drawer-title">Detalle del log</h3>' +
        '<button type="button" class="drawer-close">✕</button>' +
      '</div>' +
      '<div class="drawer-body" style="padding:24px;overflow-y:auto;flex:1;">' +

        /* --- Resumen --- */
        '<div class="log-summary">' +
          '<div class="log-summary-row">' +
            '<span class="log-summary-label">Acci&oacute;n</span>' +
            '<span class="log-summary-value">' + esc(entry.accion || "—") + '</span>' +
          '</div>' +
          (entry.ts ? '<div class="log-summary-row">' +
            '<span class="log-summary-label">Fecha y hora</span>' +
            '<span class="log-summary-value">' + esc(entry.ts) + '</span>' +
          '</div>' : "") +
          '<div class="log-summary-row">' +
            '<span class="log-summary-label">Usuario</span>' +
            '<span class="log-summary-value">' + esc(entry.user_nombre || "an&oacute;nimo") + '</span>' +
          '</div>' +
          (entry.ip ? '<div class="log-summary-row">' +
            '<span class="log-summary-label">IP</span>' +
            '<span class="log-summary-value">' + esc(entry.ip) + '</span>' +
          '</div>' : "") +
        '</div>' +

        /* --- Campos modificados --- */
        '<h4 class="log-fields-title">Campos modificados</h4>' +
        '<div class="log-fields">';

  for (var key in entry.detalle) {
    if (!entry.detalle.hasOwnProperty(key)) continue;
    var val = entry.detalle[key];
    var displayVal = esc(String(val));
    var hasOld = typeof val === "string" && val.indexOf(" (from: ") !== -1;
    var newVal = val;
    var oldVal = "";
    if (hasOld) {
      var parts = val.split(" (from: ");
      newVal = parts[0];
      oldVal = parts[1].replace(/\)$/, "");
    }

    html += '<div class="detalle-item">' +
      '<strong>' + esc(formatearClave(key)) + '</strong>';
    if (hasOld && oldVal) {
      html += '<div class="log-cambio">' +
        '<span class="log-valor-nuevo">' + esc(newVal) + '</span>' +
        '<span class="log-flecha">&rarr;</span>' +
        '<span class="log-valor-viejo">' + esc(oldVal) + '</span>' +
        '</div>';
    } else {
      html += '<span>' + displayVal + '</span>';
    }
    html += '</div>';
  }

  html +=
        '</div>' +
      '</div>' +
    '</aside>';

  container.innerHTML = html;
  if (typeof openDrawer === "function") openDrawer();
}

function formatearClave(key) {
  return key
    .replace(/_/g, " ")
    .replace(/\b\w/g, function(c) { return c.toUpperCase(); });
}

if (!document.body.dataset.logsFormBound) {
  document.body.dataset.logsFormBound = "true";

  document.addEventListener("click", function(e) {
    var page = document.body.dataset.page;
    if (!page || page.toLowerCase() !== "logs") return;

    var detalleBtn = e.target.closest(".log-detalle-btn");
    if (!detalleBtn) return;

    e.preventDefault();

    var idx = parseInt(detalleBtn.dataset.logIndex);
    var entry = logsData[idx];
    if (entry) abrirDetalleLog(entry);
  });
}

function esc(s) {
  if (!s) return "";
  return String(s).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;");
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
