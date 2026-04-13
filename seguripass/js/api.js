/* ===== SEGURIPASS - CLIENTE API ===== */

const API_BASE = '/seguripass/api';

// Petición genérica
async function apiRequest(endpoint, method = 'GET', body = null) {
  const opts = {
    method,
    headers: { 'Content-Type': 'application/json' }
  };
  if (body) opts.body = JSON.stringify(body);

  try {
    const res  = await fetch(`${API_BASE}/${endpoint}`, opts);
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Error en el servidor');
    return data;
  } catch (err) {
    console.error(`[API] ${endpoint}:`, err.message);
    throw err;
  }
}

// ===== AUTH =====
const Auth = {
  async login(usuario, contrasena) {
    return apiRequest('login.php', 'POST', { usuario, contrasena });
  },
  guardarSesion(data) {
    sessionStorage.setItem('sp_id',           data.id);
    sessionStorage.setItem('sp_usuario',      data.nombre_completo);
    sessionStorage.setItem('sp_rol',          data.rol);
    sessionStorage.setItem('sp_num_empleado', data.numero_empleado);
    sessionStorage.setItem('sp_turno',        data.turno);
    sessionStorage.setItem('sp_area',         data.area);
  },
  cerrarSesion() {
    sessionStorage.clear();
    window.location.href = '/seguripass/login.html';
  },
  verificar(rolRequerido = null) {
    const usuario = sessionStorage.getItem('sp_usuario');
    if (!usuario) { window.location.href = '/seguripass/login.html'; return false; }
    if (rolRequerido && sessionStorage.getItem('sp_rol') !== rolRequerido) {
      window.location.href = '/seguripass/login.html'; return false;
    }
    return true;
  }
};

// ===== VISITANTES =====
const Visitantes = {
  async registrar(datos) {
    return apiRequest('visitantes.php', 'POST', datos);
  },
  async estadoSolicitud(solicitud_id) {
    return apiRequest(`visitantes.php?solicitud_id=${solicitud_id}`);
  }
};

// ===== SOLICITUDES =====
const Solicitudes = {
  async listar(estado = '', buscar = '') {
    let q = `solicitudes.php?`;
    if (estado) q += `estado=${encodeURIComponent(estado)}&`;
    if (buscar) q += `buscar=${encodeURIComponent(buscar)}`;
    return apiRequest(q);
  },
  async validar(id, estado, usuario_id, motivo_rechazo = '') {
    return apiRequest('solicitudes.php', 'PUT', { id, estado, usuario_id, motivo_rechazo });
  }
};

// ===== PREFECTOS =====
const Prefectos = {
  async listar() {
    return apiRequest('prefectos.php');
  },
  async registrar(datos) {
    return apiRequest('prefectos.php', 'POST', datos);
  },
  async modificar(datos) {
    return apiRequest('prefectos.php', 'PUT', datos);
  },
  async eliminar(id) {
    return apiRequest(`prefectos.php?id=${id}`, 'DELETE');
  }
};

// ===== REPORTES =====
const Reportes = {
  async listar() {
    return apiRequest('reportes.php');
  },
  async generar(datos) {
    return apiRequest('reportes.php', 'POST', datos);
  }
};

// ===== CONFIGURACION =====
const Configuracion = {
  async obtener() {
    return apiRequest('configuracion.php');
  },
  async guardar(datos) {
    return apiRequest('configuracion.php', 'POST', datos);
  }
};

// ===== RESPALDO =====
const Respaldo = {
  async historial() {
    return apiRequest('respaldo.php');
  },
  async generar(usuario_id) {
    return apiRequest('respaldo.php', 'POST', { usuario_id, tipo: 'Respaldo' });
  }
};
