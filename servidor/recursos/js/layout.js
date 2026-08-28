/* =============================================
   TEMA OSCURO — init inmediato (evita flash)
   ============================================= */
function initTheme() {
  var theme = localStorage.getItem('sporta-theme');
  if (theme === 'dark') {
    document.documentElement.setAttribute('data-theme', 'dark');
  } else {
    document.documentElement.removeAttribute('data-theme');
  }
}
initTheme();

var BASE_URL = (function() {
  var scripts = document.getElementsByTagName('script');
  for (var i = 0; i < scripts.length; i++) {
    var src = scripts[i].src;
    if (src && src.indexOf('recursos/js/layout.js') !== -1) {
      return src.substring(0, src.indexOf('recursos/js/layout.js')).replace(/\/+$/, '');
    }
  }
  return window.location.pathname.replace(/\/[^/]+\/?$/, '');
})();

function normalizeRoute(route) {
  return route.replace(/^\/+/, '').replace(/\/+$/, '').toLowerCase();
}

function getBaseRoute() {
  var path = window.location.pathname;
  var basePath = BASE_URL;
  var m = basePath.match(/https?:\/\/[^\/]+(.*)/);
  if (m) basePath = m[1];
  var idx = path.indexOf(basePath);
  if (idx === 0) {
    return path.substring(basePath.length);
  }
  return path;
}

async function loadComponent(id, file) {
  var res = await fetch(file + '?v=' + Date.now());
  var html = await res.text();
  html = html.replace(/\{BASE_URL\}/g, BASE_URL);
  document.getElementById(id).innerHTML = html;
}

function initUI() {
  var sidebar = document.querySelector('.sidebar');
  var menuToggle = document.getElementById('menuToggle');
  if (menuToggle && !menuToggle.dataset.bound) {
    menuToggle.dataset.bound = 'true';
    menuToggle.onclick = function() {
      sidebar && sidebar.classList.toggle('open');
    };
  }
  document.querySelectorAll('.menu-link').forEach(function(link) {
    if (link.dataset.bound) return;
    link.dataset.bound = 'true';
    link.onclick = function() {
      if (window.innerWidth <= 768) {
        sidebar && sidebar.classList.remove('open');
      }
    };
  });
  if (sidebar) {
    document.addEventListener('click', function(e) {
      if (window.innerWidth > 768) return;
      if (!sidebar.classList.contains('open')) return;
      if (sidebar.contains(e.target) || (menuToggle && menuToggle.contains(e.target))) return;
      sidebar.classList.remove('open');
    });
  }
}

function setActiveMenu(route) {
  var current = normalizeRoute(route);
  document.querySelectorAll('.menu-item').forEach(function(i) { i.classList.remove('active'); });
  document.querySelectorAll('.menu-link').forEach(function(link) {
    var r = normalizeRoute(link.dataset.route || '');
    if (r === current) {
      var item = link.closest('.menu-item');
      if (item) item.classList.add('active');
    }
  });
}

async function loadDrawer() {
  var container = document.getElementById('drawer-container');
  if (!container) return;
  var drawer = document.body.dataset.drawer;
  if (!drawer) {
    container.innerHTML = '';
    return;
  }
  try {
    var response = await fetch(BASE_URL + '/componentes/drawers/' + drawer + '.html?v=' + Date.now());
    if (!response.ok) {
      container.innerHTML = '';
      return;
    }
    var html = await response.text();
    html = html.replace(/\{BASE_URL\}/g, BASE_URL);
    container.innerHTML = html;
    if (typeof initDrawerValidation === 'function') {
      initDrawerValidation();
    }
  } catch (err) {
    console.error('Error cargando drawer:', err);
    container.innerHTML = '';
  }
}

async function loadPage(route) {
  try {
    var cleanRoute = normalizeRoute(route);
    if (!cleanRoute) cleanRoute = 'inicio';

    if (cleanRoute === 'canchas' && window.currentUser && !window.currentUser.isAdmin) {
      window.history.pushState({}, '', BASE_URL + '/inicio/');
      loadPage('inicio');
      return;
    }

    var res = await fetch(BASE_URL + '/' + cleanRoute + '/index.html?v=' + Date.now());
    if (!res.ok) {
      throw new Error('No existe /' + cleanRoute + '/index.html');
    }

    var html = await res.text();
    var doc = new DOMParser().parseFromString(html, 'text/html');

    /* 1. CONTENT */
    var newContent = doc.querySelector('.main-content');
    var currentContent = document.querySelector('.main-content');
    if (newContent && currentContent) {
      currentContent.innerHTML = newContent.innerHTML;

      /* Ejecutar scripts inline (innerHTML no los ejecuta) */
      doc.querySelectorAll('script:not([src])').forEach(function(s) {
        try {
          var script = document.createElement('script');
          script.textContent = s.textContent;
          document.body.appendChild(script);
          document.body.removeChild(script);
        } catch (e) {
          console.error('Error ejecutando script inline:', e);
        }
      });
    }

    /* 2. BODY STATE */
    document.body.className = '';
    doc.body.classList.forEach(function(c) { document.body.classList.add(c); });
    document.body.dataset.page = doc.body.dataset.page || cleanRoute;
    if (doc.body.dataset.drawer) {
      document.body.dataset.drawer = doc.body.dataset.drawer;
    } else {
      delete document.body.dataset.drawer;
    }

    /* 3. CSS DINÁMICO */
    var oldStyle = document.querySelector('#page-style');
    if (oldStyle) oldStyle.remove();
    var newStyle = doc.querySelector('#page-style');
    if (newStyle) {
      var style = document.createElement('link');
      style.id = 'page-style';
      style.rel = 'stylesheet';
      var href = newStyle.getAttribute('href');
      style.href = BASE_URL + '/' + cleanRoute + '/' + href + '?v=' + Date.now();
      document.head.appendChild(style);
      await new Promise(function(resolve) {
        style.onload = resolve;
        style.onerror = resolve;
      });
    }

    /* 4. TITLE */
    var pageTitle = doc.body.dataset.page || cleanRoute.charAt(0).toUpperCase() + cleanRoute.slice(1);
    document.title = 'Sporta - ' + pageTitle;
    updateDate();

    /* 5. MENU ACTIVE */
    setActiveMenu(cleanRoute);

    /* 6. UI REINIT */
    initUI();

    /* 7. SCRIPTS DINÁMICOS (load BEFORE page init) */
    var scriptPromises = [];
    doc.querySelectorAll('script[src]').forEach(function(s) {
      var src = s.getAttribute('src');
      if (src && !document.querySelector('script[src="' + src.replace(/"/g, '\\"') + '"]')) {
        var script = document.createElement('script');
        script.src = src.indexOf('://') !== -1 || src.indexOf('/') === 0 ? src : BASE_URL + '/' + cleanRoute + '/' + src;
        script.async = false;
        document.head.appendChild(script);
        scriptPromises.push(new Promise(function(resolve) {
          script.onload = resolve;
          script.onerror = resolve;
        }));
      }
    });
    await Promise.all(scriptPromises);

    /* 8. DRAWER */
    await loadDrawer();

    /* 9. PAGE INIT */
    if (typeof initTable === 'function') initTable();
    if (typeof initColumnPicker === 'function') initColumnPicker();
    if (typeof initDrawerPage === 'function') initDrawerPage();
    if (typeof initAjustes === 'function') initAjustes();
    if (typeof initTurnosPage === 'function' && cleanRoute === 'turnos') initTurnosPage();
    if (typeof initCanchasPage === 'function' && cleanRoute === 'canchas') initCanchasPage();
    if (typeof cargarClientes === 'function' && cleanRoute === 'clientes') await cargarClientes();
    if (typeof cargarEmpleados === 'function' && cleanRoute === 'empleados') await cargarEmpleados();
    if (typeof cargarReservas === 'function' && cleanRoute === 'reservas') {
      await cargarReservas();
    }
    if (typeof cargarPagos === 'function' && cleanRoute === 'pagos') await cargarPagos();
    if (cleanRoute === 'inicio') initInicio();

    /* 10. RESET VISUAL */
    window.scrollTo(0, 0);
    document.body.style.background = '';
    document.documentElement.style.background = '';

  } catch (err) {
    console.error('Error cargando pagina:', err);
    mostrarToast('Error al cargar la página: ' + (err.message || 'desconocido'), 'error');
  }
}

function updateDate() {
  var dateEl = document.getElementById('current-date');
  if (!dateEl) return;
  dateEl.textContent = new Date().toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

var clockInterval = null;

function initInicio() {
  var welcomeEl = document.querySelector('.welcome');
  if (welcomeEl && window.currentUser) {
    welcomeEl.textContent = 'Bienvenido, ' + window.currentUser.nombre + ' ' + window.currentUser.apellido;
  }
  initClock();
  loadWeather();
  cargarDashboard();
}

function initClock() {
  if (clockInterval) clearInterval(clockInterval);
  function updateClock() {
    var clock = document.getElementById('clock');
    var date = document.getElementById('date');
    if (!clock || !date) return;
    var now = new Date();
    clock.textContent = now.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' });
    date.textContent = now.toLocaleDateString('es-AR', { weekday: 'long', day: 'numeric', month: 'long' });
  }
  updateClock();
  clockInterval = setInterval(updateClock, 1000);
}

async function loadWeather() {
  try {
    var tempEl = document.getElementById('weatherTemp');
    var descEl = document.getElementById('weatherDesc');
    var iconEl = document.getElementById('weatherIcon');
    if (!tempEl || !descEl || !iconEl) return;
    var lat = -34.7653;
    var lon = -58.2128;
    var response = await fetch('https://api.open-meteo.com/v1/forecast?latitude=' + lat + '&longitude=' + lon + '&current=temperature_2m,weather_code');
    var data = await response.json();
    var temp = Math.round(data.current.temperature_2m);
    var code = data.current.weather_code;
    var weatherMap = {
      0: ['☀️', 'Despejado'],
      1: ['🌤️', 'Mayormente despejado'],
      2: ['⛅', 'Parcialmente nublado'],
      3: ['☁️', 'Nublado'],
      45: ['🌫️', 'Niebla'],
      48: ['🌫️', 'Niebla'],
      51: ['🌦️', 'Llovizna'],
      61: ['🌧️', 'Lluvia'],
      63: ['🌧️', 'Lluvia'],
      65: ['⛈️', 'Tormenta'],
      71: ['❄️', 'Nieve']
    };
    var weather = weatherMap[code] || ['🌡️', 'Clima'];
    tempEl.textContent = temp + '°C';
    descEl.textContent = weather[1];
    iconEl.textContent = weather[0];
  } catch (err) {
    console.error('Error clima:', err);
  }
}

function esc(s) {
  var div = document.createElement('div');
  div.textContent = s;
  return div.innerHTML;
}

async function cargarDashboard() {
  try {
    var res = await fetch(BASE_URL + '/api/dashboard.php');
    if (!res.ok) throw new Error('Error HTTP ' + res.status);
    var data = await res.json();
    if (!data.ok) throw new Error(data.mensaje || 'Error del servidor');

    var list = document.getElementById('proximos-turnos');
    if (list) {
      if (data.proximos_turnos && data.proximos_turnos.length > 0) {
        list.innerHTML = data.proximos_turnos.map(function(t) {
          var hora = t.tur_hora_inicio ? t.tur_hora_inicio.substring(0, 5) : '--:--';
          return '<div class="turno-item">' +
            '<span>' + hora + '</span>' +
            '<p>Cancha ' + t.cancha_numero + ' - ' + esc(t.cliente_nombre) + ' ' + esc(t.cliente_apellido) + '</p>' +
            '</div>';
        }).join('');
      } else {
        list.innerHTML = '<p style="opacity:0.6;padding:8px 0">No hay turnos hoy</p>';
      }
    }

    var thEl = document.getElementById('turnos-hoy');
    if (thEl) thEl.textContent = data.turnos_hoy;

    var inEl = document.getElementById('ingresos');
    if (inEl) inEl.textContent = '$' + Number(data.ingresos).toLocaleString('es-AR', { minimumFractionDigits: 0 });

    var caEl = document.getElementById('canchas-activas');
    if (caEl) caEl.textContent = data.canchas_activas;

    var clEl = document.getElementById('clientes');
    if (clEl) clEl.textContent = data.clientes;

    var impCount = document.getElementById('impagas-count');
    if (impCount) impCount.textContent = data.reservas_impagas.count;

    var impList = document.getElementById('reservas-impagas');
    if (impList) {
      if (data.reservas_impagas.lista && data.reservas_impagas.lista.length > 0) {
        impList.innerHTML = data.reservas_impagas.lista.map(function(r) {
          var hora = r.tur_hora_inicio ? r.tur_hora_inicio.substring(0, 5) : '--:--';
          var deuda = Number(r.factura_total - r.total_pagado);
          return '<div class="reserva-impaga-item">' +
            '<span class="ri-cliente">' + esc(r.cliente_nombre) + ' ' + esc(r.cliente_apellido) + '</span>' +
            '<span class="ri-cancha">Cancha ' + r.cancha_numero + ' ' + hora + '</span>' +
            '<span class="ri-deuda">$' + deuda.toLocaleString('es-AR') + '</span>' +
            '</div>';
        }).join('');
      } else {
        impList.innerHTML = '<p style="opacity:0.6;padding:8px 0">No hay reservas impagas</p>';
      }
    }

    if (data.ingresos_7d && data.ingresos_7d.length > 0) {
      drawMiniChart('chart-ingresos', data.ingresos_7d);
    }

  } catch (err) {
    console.error('Dashboard error:', err);
  }
}

function drawMiniChart(canvasId, data) {
  var canvas = document.getElementById(canvasId);
  if (!canvas) return;
  var rect = canvas.parentElement.getBoundingClientRect();
  var w = rect.width - 48;
  var dpr = window.devicePixelRatio || 1;
  canvas.width = w * dpr;
  canvas.height = 200 * dpr;
  canvas.style.width = w + 'px';
  canvas.style.height = '200px';
  var ctx = canvas.getContext('2d');
  ctx.scale(dpr, dpr);

  var pad = { top: 20, right: 20, bottom: 30, left: 50 };
  var cw = w - pad.left - pad.right;
  var ch = 200 - pad.top - pad.bottom;

  var maxVal = 0;
  data.forEach(function(d) { if (d.total > maxVal) maxVal = d.total; });
  if (maxVal === 0) maxVal = 1;
  var roundTo = Math.pow(10, Math.floor(Math.log10(maxVal)));
  var yMax = Math.ceil(maxVal / roundTo) * roundTo;
  if (yMax === 0) yMax = 1;

  ctx.clearRect(0, 0, canvas.width, canvas.height);

  var dias = ['dom', 'lun', 'mar', 'mié', 'jue', 'vie', 'sáb'];

  ctx.strokeStyle = 'rgba(255,255,255,0.06)';
  ctx.lineWidth = 1;
  for (var i = 0; i <= 4; i++) {
    var y = pad.top + (ch / 4) * i;
    ctx.beginPath();
    ctx.moveTo(pad.left, y);
    ctx.lineTo(pad.left + cw, y);
    ctx.stroke();
    ctx.fillStyle = 'rgba(255,255,255,0.3)';
    ctx.font = '11px sans-serif';
    ctx.textAlign = 'right';
    ctx.fillText('$' + Math.round(yMax - (yMax / 4) * i).toLocaleString('es-AR'), pad.left - 8, y + 4);
  }

  var puntos = [];
  data.forEach(function(d, i) {
    var x = pad.left + (i / (data.length - 1 || 1)) * cw;
    var y = pad.top + ch - (d.total / yMax) * ch;
    puntos.push({ x: x, y: y });

    ctx.fillStyle = 'rgba(255,255,255,0.5)';
    ctx.font = '11px sans-serif';
    ctx.textAlign = 'center';
    var dateObj = new Date(d.fecha + 'T12:00:00');
    var label = dias[dateObj.getDay()];
    ctx.fillText(label, x, pad.top + ch + 18);
  });

  if (puntos.length > 1) {
    var gradient = ctx.createLinearGradient(0, pad.top, 0, pad.top + ch);
    gradient.addColorStop(0, 'rgba(250, 204, 21, 0.25)');
    gradient.addColorStop(1, 'rgba(250, 204, 21, 0.01)');
    ctx.beginPath();
    ctx.moveTo(puntos[0].x, pad.top + ch);
    puntos.forEach(function(p) { ctx.lineTo(p.x, p.y); });
    ctx.lineTo(puntos[puntos.length - 1].x, pad.top + ch);
    ctx.closePath();
    ctx.fillStyle = gradient;
    ctx.fill();
  }

  ctx.strokeStyle = '#facc15';
  ctx.lineWidth = 2.5;
  ctx.lineJoin = 'round';
  ctx.beginPath();
  puntos.forEach(function(p, i) {
    if (i === 0) ctx.moveTo(p.x, p.y);
    else ctx.lineTo(p.x, p.y);
  });
  ctx.stroke();

  puntos.forEach(function(p) {
    ctx.beginPath();
    ctx.arc(p.x, p.y, 4, 0, Math.PI * 2);
    ctx.fillStyle = '#facc15';
    ctx.fill();
    ctx.strokeStyle = '#1e1e2e';
    ctx.lineWidth = 2;
    ctx.stroke();
  });
}

function initRouter() {
  document.addEventListener('click', function(e) {
    var link = e.target.closest('.menu-link');
    if (!link) return;
    var route = normalizeRoute(link.dataset.route);
    e.preventDefault();
    history.pushState({}, '', BASE_URL + '/' + route + '/');
    loadPage(route);
  });
  window.addEventListener('popstate', function() {
    var route = normalizeRoute(getBaseRoute());
    loadPage(route || 'inicio');
  });
}

function setTheme(dark) {
  if (document.startViewTransition) {
    var toggle = document.getElementById('themeToggleSwitch');
    if (toggle) {
      var rect = toggle.getBoundingClientRect();
      var x = rect.left + rect.width / 2;
      var y = rect.top + rect.height / 2;
      document.documentElement.style.setProperty('--toggle-x', x + 'px');
      document.documentElement.style.setProperty('--toggle-y', y + 'px');
    }
    document.startViewTransition(function() {
      if (dark) {
        document.documentElement.setAttribute('data-theme', 'dark');
        localStorage.setItem('sporta-theme', 'dark');
      } else {
        document.documentElement.removeAttribute('data-theme');
        localStorage.setItem('sporta-theme', 'light');
      }
      var chk = document.getElementById('themeToggleCheckbox');
      if (chk) chk.checked = dark;
    });
  } else {
    if (dark) {
      document.documentElement.setAttribute('data-theme', 'dark');
      localStorage.setItem('sporta-theme', 'dark');
    } else {
      document.documentElement.removeAttribute('data-theme');
      localStorage.setItem('sporta-theme', 'light');
    }
    var chk = document.getElementById('themeToggleCheckbox');
    if (chk) chk.checked = dark;
  }
}

function initThemeToggle() {
  var chk = document.getElementById('themeToggleCheckbox');
  if (!chk || chk.dataset.bound) return;
  chk.dataset.bound = 'true';

  chk.checked = document.documentElement.getAttribute('data-theme') === 'dark';

  chk.addEventListener('change', function() {
    setTheme(chk.checked);
  });
}

/* ========================= */
/* CONFIRM MODAL */
/* ========================= */
function showConfirm(message, icon) {
  if (!icon) icon = '🔒';
  var colorClass = '';
  if (icon === '🚫' || icon === '⚠️') colorClass = ' confirm-icon-wrap--red';
  else if (icon === '✅') colorClass = ' confirm-icon-wrap--green';
  else if (icon === '🔑') colorClass = ' confirm-icon-wrap--yellow';

  return new Promise(function(resolve) {
    var overlay = document.createElement('div');
    overlay.className = 'confirm-overlay';
    overlay.innerHTML =
      '<div class="confirm-modal">' +
        '<div class="confirm-icon-wrap' + colorClass + '">' +
          '<span class="confirm-icon">' + icon + '</span>' +
        '</div>' +
        '<p class="confirm-message">' + message + '</p>' +
        '<div class="confirm-actions">' +
          '<button class="confirm-btn confirm-cancel">Cancelar</button>' +
          '<button class="confirm-btn confirm-ok">Confirmar</button>' +
        '</div>' +
      '</div>';

    document.body.appendChild(overlay);

    requestAnimationFrame(function() {
      overlay.classList.add('active');
    });

    function close(result) {
      overlay.classList.remove('active');
      setTimeout(function() {
        overlay.remove();
        resolve(result);
      }, 300);
    }

    overlay.querySelector('.confirm-cancel').addEventListener('click', function() {
      close(false);
    });

    overlay.querySelector('.confirm-ok').addEventListener('click', function() {
      close(true);
    });

    overlay.addEventListener('click', function(e) {
      if (e.target === overlay) close(false);
    });
  });
}

function initAjustes() {
  var usuarioInput = document.getElementById('nuevo_usuario');
  var errorUsuario = document.getElementById('error_usuario');
  var okUsuario = document.getElementById('ok_usuario');

  if (usuarioInput) {
    fetch(BASE_URL + '/api/usuario.php')
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data.ok && data.usuario) {
          usuarioInput.value = data.usuario.usuario;
        }
      })
      .catch(function(err) { console.error('Error cargando usuario:', err); });
  }

  var passInput = document.getElementById('pass_nueva');
  if (!passInput) return;
  var passConfirm = document.getElementById('pass_confirmar');
  var errorPass = document.getElementById('error_password');
  var reqLength = document.getElementById('req-length');
  var reqUpper = document.getElementById('req-upper');
  var reqNumber = document.getElementById('req-number');
  var reqSpecial = document.getElementById('req-special');
  function validarPassword(valor) {
    var cumple = { length: valor.length >= 6, upper: /[A-Z]/.test(valor), number: /[0-9]/.test(valor), special: /[^a-zA-Z0-9]/.test(valor) };
    reqLength.classList.toggle('req-ok', cumple.length);
    reqUpper.classList.toggle('req-ok', cumple.upper);
    reqNumber.classList.toggle('req-ok', cumple.number);
    reqSpecial.classList.toggle('req-ok', cumple.special);
    return cumple.length && cumple.upper && cumple.number && cumple.special;
  }
  function validarConfirmacion() {
    if (!passConfirm.value) { errorPass.textContent = ''; errorPass.classList.remove('visible'); return true; }
    if (passConfirm.value !== passInput.value) { errorPass.textContent = 'Las contraseñas no coinciden'; errorPass.classList.add('visible'); return false; }
    errorPass.textContent = ''; errorPass.classList.remove('visible'); return true;
  }
  passInput.addEventListener('input', function() { validarPassword(passInput.value); if (passConfirm && passConfirm.value) validarConfirmacion(); });
  if (passConfirm) passConfirm.addEventListener('input', validarConfirmacion);

  if (!usuarioInput) return;
  var usuarioOriginal = '';
  var checkTimeout = null;
  if (usuarioInput) {
    usuarioInput.addEventListener('input', function() {
      var val = usuarioInput.value.trim();
      if (val === usuarioOriginal) { errorUsuario.textContent = ''; errorUsuario.classList.remove('visible'); okUsuario.textContent = ''; okUsuario.classList.remove('visible'); return; }
      if (val.length < 3) { errorUsuario.textContent = 'Minimo 3 caracteres'; errorUsuario.classList.add('visible'); okUsuario.textContent = ''; okUsuario.classList.remove('visible'); return; }
      if (checkTimeout) clearTimeout(checkTimeout);
      checkTimeout = setTimeout(function() {
        fetch(BASE_URL + '/api/ajustes.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ accion: 'check_usuario', usuario: val })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
          if (data.disponible) { errorUsuario.textContent = ''; errorUsuario.classList.remove('visible'); okUsuario.textContent = '✓ Disponible'; okUsuario.classList.add('visible'); }
          else { errorUsuario.textContent = 'El nombre de usuario ya esta en uso'; errorUsuario.classList.add('visible'); okUsuario.textContent = ''; okUsuario.classList.remove('visible'); }
        })
        .catch(function() { errorUsuario.textContent = 'Error al verificar'; errorUsuario.classList.add('visible'); });
      }, 400);
    });
  }
  var formPass = document.getElementById('formPassword');
  if (formPass) {
    formPass.addEventListener('submit', async function(e) {
      e.preventDefault();
      var validaPass = validarPassword(passInput.value);
      var validaConf = validarConfirmacion();
      if (!validaPass || !validaConf) {
        if (!validaPass) {
          errorPass.textContent = 'La contraseña no cumple con los requisitos';
          errorPass.classList.add('visible');
          passInput.focus();
        }
        return;
      }
      if (!await showConfirm('¿Está seguro de que desea cambiar su contraseña?', '🔒')) return;
      fetch(BASE_URL + '/api/ajustes.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          accion: 'cambio_contrasena',
          pass_actual: document.getElementById('pass_actual').value,
          pass_nueva: passInput.value,
          pass_confirmar: passConfirm.value
        })
      })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data.ok) {
          mostrarToast(data.mensaje, 'success');
          formPass.reset();
          document.querySelectorAll('.req-ok').forEach(function(el) { el.classList.remove('req-ok'); });
        } else mostrarToast(data.mensaje, 'error');
      })
      .catch(function() { mostrarToast('Error de conexion', 'error'); });
    });

    var btnReset = document.querySelector('.btn-reset-pass');
    if (btnReset) {
      btnReset.addEventListener('click', async function() {
        if (!await showConfirm('¿Restablecer contraseña a 1234?', '🔑')) return;
        fetch(BASE_URL + '/api/ajustes.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ accion: 'cambio_contrasena_default' })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
          if (data.ok) mostrarToast(data.mensaje, 'success');
          else mostrarToast(data.mensaje, 'error');
        })
        .catch(function() { mostrarToast('Error de conexion', 'error'); });
      });
    }
  }
  var formUser = document.getElementById('formUsuario');
  if (formUser) {
    formUser.addEventListener('submit', function(e) {
      e.preventDefault();
      var val = usuarioInput.value.trim();
      if (val === usuarioOriginal) return;
      if (val.length < 3) { errorUsuario.textContent = 'Minimo 3 caracteres'; errorUsuario.classList.add('visible'); usuarioInput.focus(); return; }
      if (errorUsuario.classList.contains('visible')) { usuarioInput.focus(); return; }
      fetch(BASE_URL + '/api/ajustes.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ accion: 'cambio_usuario', nuevo_usuario: val })
      })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data.ok) { usuarioOriginal = val; okUsuario.textContent = '✓ Cambio aplicado'; okUsuario.classList.add('visible'); errorUsuario.classList.remove('visible'); mostrarToast(data.mensaje, 'success'); }
        else mostrarToast(data.mensaje, 'error');
      })
      .catch(function() { mostrarToast('Error de conexion', 'error'); });
    });
  }

  /* ========================= */
  /* NOMBRE Y APELLIDO */
  /* ========================= */

  var inputNombre = document.getElementById('edit_nombre');
  var inputApellido = document.getElementById('edit_apellido');
  var errorNombre = document.getElementById('error_nombre');
  var errorApellido = document.getElementById('error_apellido');
  var formNombre = document.getElementById('formNombre');

  if (formNombre) {
    formNombre.addEventListener('submit', function(e) {
      e.preventDefault();
      var nombre = inputNombre.value.trim();
      var apellido = inputApellido.value.trim();
      if (!nombre) { errorNombre.textContent = 'El nombre no puede estar vacío'; errorNombre.classList.add('visible'); inputNombre.focus(); return; }
      if (!apellido) { errorApellido.textContent = 'El apellido no puede estar vacío'; errorApellido.classList.add('visible'); inputApellido.focus(); return; }
      fetch(BASE_URL + '/api/ajustes.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ accion: 'cambio_nombre', nombre: nombre, apellido: apellido })
      })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data.ok) { mostrarToast(data.mensaje, 'success'); }
        else mostrarToast(data.mensaje, 'error');
      })
      .catch(function() { mostrarToast('Error de conexion', 'error'); });
    });
  }

  /* ========================= */
  /* DIRECCIÓN */
  /* ========================= */

  var inputDireccion = document.getElementById('edit_direccion');
  var errorDireccion = document.getElementById('error_direccion');
  var formDireccion = document.getElementById('formDireccion');

  if (formDireccion) {
    formDireccion.addEventListener('submit', function(e) {
      e.preventDefault();
      var direccion = inputDireccion.value.trim();
      if (!direccion) { errorDireccion.textContent = 'La dirección no puede estar vacía'; errorDireccion.classList.add('visible'); inputDireccion.focus(); return; }
      fetch(BASE_URL + '/api/ajustes.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ accion: 'cambio_direccion', direccion: direccion })
      })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data.ok) { mostrarToast(data.mensaje, 'success'); }
        else mostrarToast(data.mensaje, 'error');
      })
      .catch(function() { mostrarToast('Error de conexion', 'error'); });
    });
  }

  /* ========================= */
  /* EMAIL */
  /* ========================= */

  var inputEmail = document.getElementById('edit_email');
  var errorEmail = document.getElementById('error_email');
  var formEmail = document.getElementById('formEmail');

  if (formEmail) {
    formEmail.addEventListener('submit', function(e) {
      e.preventDefault();
      var email = inputEmail.value.trim();
      if (!email) { errorEmail.textContent = 'El email no puede estar vacío'; errorEmail.classList.add('visible'); inputEmail.focus(); return; }
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { errorEmail.textContent = 'Ingrese un email válido'; errorEmail.classList.add('visible'); inputEmail.focus(); return; }
      fetch(BASE_URL + '/api/ajustes.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ accion: 'cambio_email', email: email })
      })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data.ok) { mostrarToast(data.mensaje, 'success'); }
        else mostrarToast(data.mensaje, 'error');
      })
      .catch(function() { mostrarToast('Error de conexion', 'error'); });
    });
  }

  /* ========================= */
  /* CELULAR */
  /* ========================= */

  var inputCelular = document.getElementById('edit_celular');
  var errorCelular = document.getElementById('error_celular');
  var formCelular = document.getElementById('formCelular');

  if (formCelular) {
    formCelular.addEventListener('submit', function(e) {
      e.preventDefault();
      var celular = inputCelular.value.trim();
      if (!celular) { errorCelular.textContent = 'El celular no puede estar vacío'; errorCelular.classList.add('visible'); inputCelular.focus(); return; }
      if (!/^\d+$/.test(celular)) { errorCelular.textContent = 'Solo se permiten números'; errorCelular.classList.add('visible'); inputCelular.focus(); return; }
      if (celular.length > 10) { errorCelular.textContent = 'Máximo 10 dígitos'; errorCelular.classList.add('visible'); inputCelular.focus(); return; }
      fetch(BASE_URL + '/api/ajustes.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ accion: 'cambio_celular', celular: celular })
      })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data.ok) { mostrarToast(data.mensaje, 'success'); }
        else mostrarToast(data.mensaje, 'error');
      })
      .catch(function() { mostrarToast('Error de conexion', 'error'); });
    });
  }

  /* ========================= */
  /* DNI */
  /* ========================= */

  var inputDni = document.getElementById('edit_dni');
  var errorDni = document.getElementById('error_dni');
  var formDni = document.getElementById('formDni');

  if (formDni) {
    formDni.addEventListener('submit', function(e) {
      e.preventDefault();
      var dni = inputDni.value.trim();
      if (!dni) { errorDni.textContent = 'El DNI no puede estar vacío'; errorDni.classList.add('visible'); inputDni.focus(); return; }
      if (!/^\d+$/.test(dni)) { errorDni.textContent = 'Solo se permiten números'; errorDni.classList.add('visible'); inputDni.focus(); return; }
      fetch(BASE_URL + '/api/ajustes.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ accion: 'cambio_dni', dni: dni })
      })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data.ok) { mostrarToast(data.mensaje, 'success'); }
        else mostrarToast(data.mensaje, 'error');
      })
      .catch(function() { mostrarToast('Error de conexion', 'error'); });
    });
  }

  /* ========================= */
  /* PRE-CARGAR DATOS ACTUALES + PERFIL */
  /* ========================= */

  fetch(BASE_URL + '/api/usuario.php')
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (!data.ok || !data.usuario) return;
      var u = data.usuario;

      if (document.getElementById('edit_nombre')) document.getElementById('edit_nombre').value = u.nombre || '';
      if (document.getElementById('edit_apellido')) document.getElementById('edit_apellido').value = u.apellido || '';
      if (document.getElementById('edit_direccion')) document.getElementById('edit_direccion').value = u.direccion || '';
      if (document.getElementById('edit_email')) document.getElementById('edit_email').value = u.email || '';
      if (document.getElementById('edit_celular')) document.getElementById('edit_celular').value = u.celular || '';
      if (document.getElementById('edit_dni')) document.getElementById('edit_dni').value = u.dni || '';

      /* Profile card */
      var nameEl = document.getElementById('profileName');
      if (nameEl) nameEl.textContent = (u.nombre || '') + ' ' + (u.apellido || '');

      var roleEl = document.getElementById('profileRole');
      if (roleEl) { roleEl.textContent = u.rol || '—'; roleEl.dataset.role = u.rol || ''; }

      var userEl = document.getElementById('profileUsuario');
      if (userEl) userEl.textContent = u.usuario || '—';
    })
    .catch(function(err) { console.error('Error cargando datos de usuario:', err); });
}

function mostrarToast(mensaje, tipo) {
  var contenedor = document.getElementById('toast-container');
  if (!contenedor) return;
  var toast = document.createElement('div');
  toast.className = 'toast toast-' + (tipo || 'success');
  toast.innerHTML = '<span>' + mensaje + '</span><button class="toast-close" onclick="this.parentElement.remove()">&times;</button>';
  contenedor.appendChild(toast);
  setTimeout(function() {
    toast.classList.add('toast-hiding');
    setTimeout(function() { toast.remove(); }, 300);
  }, 3500);
}

async function initLayout() {
  try {
    var res = await fetch(BASE_URL + '/api/usuario.php');
    var data = await res.json();
    if (!data.ok) {
      window.location.href = BASE_URL + '/';
      return false;
    }
    window.currentUserId = data.usuario.id;
    window.currentUser = data.usuario;
  } catch (e) {
    window.location.href = BASE_URL + '/';
    return false;
  }

  await loadComponent('sidebar-container', BASE_URL + '/componentes/sidebar.html');

  if (!window.currentUser.isAdmin) {
    document.querySelectorAll('.menu-admin').forEach(function(el) {
      el.style.display = 'none';
    });
  }
  initThemeToggle();
  await loadComponent('header-container', BASE_URL + '/componentes/header.html');

  updateDate();
  initRouter();
  initUI();
  return true;
}

document.addEventListener('DOMContentLoaded', async function() {
  var ok = await initLayout();
  if (!ok) return;
  var route = normalizeRoute(getBaseRoute());
  await loadPage(route || 'inicio');
});
