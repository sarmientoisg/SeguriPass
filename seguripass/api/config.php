<?php
/**
 * ARCHIVO: database_helper.php
 * AUTOR: Carlos Ignacio Sarmiento Garcia
 * FECHA: 23 de abril de 2026
 * DESCRIPCIÓN: Script de configuración y utilidades para la conexión a la base de datos 
 * y manejo de respuestas API para el sistema Seguripass.
 */

// =============================================================================
// 1. CONFIGURACIÓN DE PARÁMETROS DE LA BASE DE DATOS
// =============================================================================

// Constantes descriptivas para la conexión
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // Usuario por defecto en entornos de desarrollo XAMPP
define('DB_PASS', '');           // Credencial de acceso (vacía por defecto)
define('DB_NAME', 'seguripass'); // Nombre de la base de datos del proyecto

// =============================================================================
// 2. CONFIGURACIÓN DE CABECERAS (CORS Y FORMATO)
// =============================================================================

// Establecer el tipo de contenido y el juego de caracteres
header('Content-Type: application/json; charset=utf-8');

// Configuración de permisos de acceso cruzado (CORS) para el frontend
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

/**
 * Gestión de la petición de pre-vuelo (Preflight) para CORS.
 * Verifica si el método de solicitud es OPTIONS para cerrar la conexión con éxito.
 */
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// =============================================================================
// 3. DEFINICIÓN DE RUTINAS Y MÉTODOS
// =============================================================================

/**
 * Establecer una conexión con el servidor de base de datos MySQL.
 * 
 * @return mysqli Objeto de conexión activa.
 */
function establecerConexionBaseDatos() {
    // Inicialización del objeto de conexión con los parámetros definidos
    $conexionServidor = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Verificación de errores críticos de conexión mediante mensajes descriptivos
    if ($conexionServidor->connect_error) {
        $mensajeErrorConexion = 'Error crítico: No se pudo conectar a la base de datos. Detalles: ' . $conexionServidor->connect_error;
        enviarRespuestaJson(['error' => $mensajeErrorConexion], 500);
    }

    // Configuración del set de caracteres para evitar errores de codificación
    $conexionServidor->set_charset('utf8mb4');
    
    return $conexionServidor;
}

/**
 * Finalizar la ejecución enviando una respuesta estructurada en formato JSON.
 * 
 * @param mixed $datosCuerpo Información a enviar en la respuesta.
 * @param int $codigoEstadoHttp Código de estado HTTP (por defecto 200).
 */
function enviarRespuestaJson($datosCuerpo, $codigoEstadoHttp = 200) {
    // Definición del código de estado de la respuesta
    http_response_code($codigoEstadoHttp);
    
    // Serialización de los datos asegurando el soporte de caracteres especiales
    echo json_encode($datosCuerpo, JSON_UNESCAPED_UNICODE);
    
    // Finalización limpia del script
    exit();
}

/**
 * Obtener y decodificar el contenido JSON recibido en el cuerpo de la petición.
 * 
 * @return array Arreglo asociativo con los datos procesados.
 */
function obtenerCuerpoSolicitudJson() {
    // Lectura del flujo de entrada de PHP para capturar datos RAW
    $entradaCruda = file_get_contents('php://input');
    
    // Decodificación a arreglo asociativo. Se inicializa como arreglo vacío si es nulo.
    $datosDecodificados = json_decode($entradaCruda, true) ?? [];
    
    return $datosDecodificados;
}

// Fin del archivo database_helper.php
?>
