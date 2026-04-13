/* ===== SEGURIPASS - VALIDACIONES Y UTILIDADES ===== */

// Muestra error en un campo
function mostrarError(inputId, msg) {
  const input = document.getElementById(inputId);
  const err   = document.getElementById(inputId + '_err');
  if (input) input.classList.add('error');
  if (err)   { err.textContent = msg; err.classList.add('visible'); }
}

// Limpia error de un campo
function limpiarError(inputId) {
  const input = document.getElementById(inputId);
  const err   = document.getElementById(inputId + '_err');
  if (input) input.classList.remove('error');
  if (err)   err.classList.remove('visible');
}

// Limpia todos los errores de un formulario
function limpiarErrores(formId) {
  const form = document.getElementById(formId);
  if (!form) return;
  form.querySelectorAll('.form-control').forEach(el => el.classList.remove('error'));
  form.querySelectorAll('.error-msg').forEach(el => el.classList.remove('visible'));
}

// Validación: solo letras y espacios (nombres)
function soloLetras(e) {
  const char = String.fromCharCode(e.which);
  if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]$/.test(char)) {
    e.preventDefault();
  }
}

// Validación: solo números
function soloNumeros(e) {
  const char = String.fromCharCode(e.which);
  if (!/^[0-9]$/.test(char)) {
    e.preventDefault();
  }
}

// Validación: alfanumérico (sin caracteres especiales raros)
function alfanumerico(e) {
  const char = String.fromCharCode(e.which);
  if (!/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\-_.,]$/.test(char)) {
    e.preventDefault();
  }
}

// Validación: contraseña (letras, números, algunos especiales)
function validaPassword(e) {
  const char = String.fromCharCode(e.which);
  if (!/^[a-zA-Z0-9@#$%!_\-.]$/.test(char)) {
    e.preventDefault();
  }
}

// Aplica restricciones de teclado a inputs
function aplicarRestricciones() {
  document.querySelectorAll('[data-tipo="nombre"]').forEach(el => {
    el.addEventListener('keypress', soloLetras);
    el.setAttribute('maxlength', el.dataset.max || '100');
  });
  document.querySelectorAll('[data-tipo="numero"]').forEach(el => {
    el.addEventListener('keypress', soloNumeros);
    el.setAttribute('maxlength', el.dataset.max || '20');
  });
  document.querySelectorAll('[data-tipo="alfanumerico"]').forEach(el => {
    el.addEventListener('keypress', alfanumerico);
    el.setAttribute('maxlength', el.dataset.max || '150');
  });
  document.querySelectorAll('[data-tipo="password"]').forEach(el => {
    el.addEventListener('keypress', validaPassword);
    el.setAttribute('maxlength', el.dataset.max || '128');
  });
}

// Mostrar alerta temporal
function mostrarAlerta(tipo, mensaje, duracion = 3500) {
  let contenedor = document.getElementById('alertas-container');
  if (!contenedor) {
    contenedor = document.createElement('div');
    contenedor.id = 'alertas-container';
    contenedor.style.cssText = 'position:fixed;top:70px;right:20px;z-index:999;width:320px;';
    document.body.appendChild(contenedor);
  }
  const alerta = document.createElement('div');
  const iconos = { success: '✅', error: '❌', info: 'ℹ️', warning: '⚠️' };
  alerta.className = `alert alert-${tipo}`;
  alerta.innerHTML = `<span>${iconos[tipo] || ''}</span><span>${mensaje}</span>`;
  contenedor.appendChild(alerta);
  setTimeout(() => alerta.remove(), duracion);
}

// Abrir modal
function abrirModal(id) {
  const m = document.getElementById(id);
  if (m) m.classList.add('open');
}

// Cerrar modal
function cerrarModal(id) {
  const m = document.getElementById(id);
  if (m) m.classList.remove('open');
}

// Cerrar modal al hacer click fuera
document.addEventListener('click', function(e) {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('open');
  }
});

// Inicializar al cargar
document.addEventListener('DOMContentLoaded', aplicarRestricciones);
