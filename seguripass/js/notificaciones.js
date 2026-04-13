/* ===== SEGURIPASS - SISTEMA DE NOTIFICACIONES ===== */

// Renderiza el botón de campana en el navbar
function iniciarNotificaciones(rol) {
  const navUser = document.querySelector('.navbar-user');
  if (!navUser) return;

  const wrapper = document.createElement('div');
  wrapper.className = 'notif-wrapper';
  wrapper.innerHTML = `
    <button class="notif-btn" id="notifBtn" onclick="togglePanel()" title="Notificaciones">
      🔔
      <span class="notif-badge hidden" id="notifBadge">0</span>
    </button>
    <div class="notif-panel" id="notifPanel">
      <div class="notif-panel-header">
        <span>Notificaciones</span>
        <button onclick="marcarTodasLeidas()">Marcar todas como leídas</button>
      </div>
      <div class="notif-list" id="notifList">
        <div class="notif-empty">Sin notificaciones</div>
      </div>
    </div>
  `;

  // Insertar antes del span de usuario
  navUser.insertBefore(wrapper, navUser.firstChild);

  // Cerrar panel al hacer click fuera
  document.addEventListener('click', function(e) {
    const panel = document.getElementById('notifPanel');
    const btn   = document.getElementById('notifBtn');
    if (panel && !panel.contains(e.target) && !btn.contains(e.target)) {
      panel.classList.remove('open');
    }
  });

  cargarNotificaciones(rol);

  // Escuchar cambios en localStorage (simula tiempo real entre pestañas)
  window.addEventListener('storage', function(e) {
    if (e.key === 'sp_nueva_solicitud' || e.key === 'sp_solicitudes' || e.key === 'sp_notificaciones') {
      cargarNotificaciones(rol);
    }
  });

  // También revisar cada 4 segundos (misma pestaña)
  setInterval(() => cargarNotificaciones(rol), 4000);
}

function togglePanel() {
  document.getElementById('notifPanel').classList.toggle('open');
}

function cargarNotificaciones(rol) {
  const notifs = obtenerNotificaciones(rol);
  const noLeidas = notifs.filter(n => !n.leida).length;

  const badge = document.getElementById('notifBadge');
  if (badge) {
    badge.textContent = noLeidas > 9 ? '9+' : noLeidas;
    badge.classList.toggle('hidden', noLeidas === 0);
  }

  const lista = document.getElementById('notifList');
  if (!lista) return;

  if (notifs.length === 0) {
    lista.innerHTML = '<div class="notif-empty">Sin notificaciones nuevas</div>';
    return;
  }

  lista.innerHTML = notifs.slice(0, 10).map(n => `
    <div class="notif-item ${n.leida ? 'leida' : ''}" onclick="clickNotif('${n.id}', '${rol}')">
      <div class="notif-icon">${n.icono}</div>
      <div>
        <div class="notif-texto">${n.texto}</div>
        <div class="notif-tiempo">${n.tiempo}</div>
      </div>
    </div>
  `).join('');
}

function obtenerNotificaciones(rol) {
  const solicitudes = JSON.parse(localStorage.getItem('sp_solicitudes') || '[]');
  const notifs = [];

  if (rol === 'prefecto' || rol === 'admin') {
    // Prefecto ve solicitudes pendientes
    solicitudes.filter(s => s.estado === 'Pendiente').forEach(s => {
      notifs.push({
        id: s.id,
        icono: '🧑‍💼',
        texto: `Nueva solicitud de acceso: ${s.nombre_completo} — ${s.area}`,
        tiempo: s.fecha,
        leida: false,
        link: '../prefecto/solicitudes.html'
      });
    });
  }

  return notifs.reverse();
}

function clickNotif(id, rol) {
  document.getElementById('notifPanel').classList.remove('open');
  if (rol === 'prefecto' || rol === 'admin') {
    window.location.href = '../prefecto/solicitudes.html';
  }
}

function marcarTodasLeidas() {
  const lista = document.getElementById('notifList');
  if (lista) lista.querySelectorAll('.notif-item').forEach(el => el.classList.add('leida'));
  const badge = document.getElementById('notifBadge');
  if (badge) badge.classList.add('hidden');
}
