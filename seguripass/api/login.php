<?php
/**
 * ARCHIVO: login.php
 * AUTOR: Carlos Ignacio Sarmiento Garcia
 * FECHA: 23 de abril de 2026
 * DESCRIPCIÓN: Script para la validación de credenciales de acceso al sistema Seguripass.
 * Verifica la existencia del usuario y la integridad de la contraseña mediante SHA-256.
 */

require_once 'config.php';

// Validar que la petición se realice exclusivamente por el método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    enviarRespuestaJson(['error' => 'Error: El método solicitado no está permitido para esta operación'], 405);
}

// Obtención y saneamiento de las credenciales recibidas
$cuerpoPeticion     = obtenerCuerpoSolicitudJson();
$nombreUsuario      = trim($cuerpoPeticion['usuario']    ?? '');
$contrasenaAcceso   = trim($cuerpoPeticion['contrasena'] ?? '');

// Verificar que ambos campos contengan información
if (!$nombreUsuario || !$contrasenaAcceso) {
    enviarRespuestaJson(['error' => 'Error: El nombre de usuario y la contraseña son obligatorios'], 400);
}

// Establecer conexión con el servidor de datos
$conexionBaseDatos = establecerConexionBaseDatos();

/**
 * Preparación de la consulta de validación.
 * Se utiliza la función SHA2 de MySQL para comparar el hash de la contraseña proporcionada.
 */
$sentenciaValidacion = $conexionBaseDatos->prepare(
    "SELECT id, nombre_completo, numero_empleado, turno, area, permisos
     FROM usuarios
     WHERE usuario = ? AND contrasena = SHA2(?, 256)
     LIMIT 1"
);

// Vinculación de parámetros y ejecución de la búsqueda
$sentenciaValidacion->bind_param('ss', $nombreUsuario, $contrasenaAcceso);
$sentenciaValidacion->execute();
$resultadoConsulta = $sentenciaValidacion->get_result();

// =============================================================================
// EVALUACIÓN DE RESULTADOS DE AUTENTICACIÓN
// =============================================================================

if ($resultadoConsulta->num_rows === 0) {
    // Si no hay coincidencias, liberar recursos y retornar error de credenciales
    $sentenciaValidacion->close();
    $conexionBaseDatos->close();
    enviarRespuestaJson(['error' => 'Acceso denegado: Usuario o contraseña incorrectos'], 401);
}

// Extraer los datos del usuario autenticado
$datosUsuario = $resultadoConsulta->fetch_assoc();

// Liberar memoria y cerrar conexión
$sentenciaValidacion->close();
$conexionBaseDatos->close();

// Retornar información del perfil del usuario con éxito
enviarRespuestaJson([
    'ok'              => true,
    'id'              => $datosUsuario['id'],
    'nombre_completo' => $datosUsuario['nombre_completo'],
    'numero_empleado' => $datosUsuario['numero_empleado'],
    'turno'           => $datosUsuario['turno'],
    'area'            => $datosUsuario['area'],
    'rol'             => $datosUsuario['permisos']
]);

// Fin del archivo login.php
?>
