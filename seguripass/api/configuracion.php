<?php
/**
 * ARCHIVO: configuracion_sistema.php
 * AUTOR: Carlos Ignacio Sarmiento Garcia
 * FECHA: 23 de abril de 2026
 * DESCRIPCIÓN: Script para gestionar la obtención y actualización de los parámetros
 * operativos del plantel (horarios, días y tiempo de sesión) en Seguripass.
 */

require_once 'config.php';

// Identificación del método de solicitud HTTP
$metodoSolicitud = $_SERVER['REQUEST_METHOD'];

// =============================================================================
// 1. PROCESAMIENTO DE PETICIÓN GET: OBTENER CONFIGURACIÓN
// =============================================================================
if ($metodoSolicitud === 'GET') {
    $conexionBaseDatos = establecerConexionBaseDatos();
    
    // Consulta para obtener la configuración más reciente y el nombre del responsable
    $consultaConfiguracion = "SELECT c.*, u.nombre_completo AS modificado_por
                              FROM configuraciones c
                              JOIN usuarios u ON u.id = c.usuario_id
                              ORDER BY c.fecha_modificacion DESC LIMIT 1";
    
    $resultadoConsulta = $conexionBaseDatos->query($consultaConfiguracion);

    // Verificar si existen registros; de lo contrario, devolver valores por defecto
    if ($resultadoConsulta->num_rows === 0) {
        $valoresPredeterminados = [
            'dias_operacion' => 'Lunes a Viernes',
            'hora_inicio'    => '07:00',
            'hora_fin'       => '21:00',
            'tiempo_sesion'  => 30,
            'nombre_plantel' => 'CETis 132'
        ];
        enviarRespuestaJson($valoresPredeterminados);
    }

    // Retornar los datos encontrados en la base de datos
    enviarRespuestaJson($resultadoConsulta->fetch_assoc());
}

// =============================================================================
// 2. PROCESAMIENTO DE PETICIÓN POST: GUARDAR CONFIGURACIÓN
// =============================================================================
if ($metodoSolicitud === 'POST') {
    $cuerpoPeticion = obtenerCuerpoSolicitudJson();

    // Declaración e inicialización de variables con saneamiento básico
    $identificadorUsuario = intval($cuerpoPeticion['usuario_id']    ?? 0);
    $diasOperativos       = trim($cuerpoPeticion['dias_operacion']  ?? '');
    $horaApertura         = trim($cuerpoPeticion['hora_inicio']     ?? '');
    $horaCierre           = trim($cuerpoPeticion['hora_fin']        ?? '');
    $duracionSesion       = intval($cuerpoPeticion['tiempo_sesion'] ?? 30);
    $nombrePlantel        = trim($cuerpoPeticion['nombre_plantel']  ?? 'CETis 132');

    // Validación de campos obligatorios para la operación
    if (!$identificadorUsuario || !$diasOperativos || !$horaApertura || !$horaCierre) {
        enviarRespuestaJson(['error' => 'Error: Faltan campos obligatorios para guardar la configuración'], 400);
    }

    $conexionBaseDatos = establecerConexionBaseDatos();

    // Preparación de la sentencia SQL para prevenir inyección de datos
    $sentenciaInsertar = $conexionBaseDatos->prepare(
        "INSERT INTO configuraciones (usuario_id, dias_operacion, hora_inicio, hora_fin, tiempo_sesion, nombre_plantel)
         VALUES (?, ?, ?, ?, ?, ?)"
    );

    // Vinculación de parámetros y ejecución de la rutina
    $sentenciaInsertar->bind_param('isssss', $identificadorUsuario, $diasOperativos, $horaApertura, $horaCierre, $duracionSesion, $nombrePlantel);
    $sentenciaInsertar->execute();

    // Cierre de recursos para optimizar la ejecución del servidor
    $sentenciaInsertar->close();
    $conexionBaseDatos->close();

    enviarRespuestaJson(['ok' => true, 'mensaje' => 'La configuración se ha guardado exitosamente']);
}

// =============================================================================
// 3. RESPUESTA POR DEFECTO PARA MÉTODOS NO SOPORTADOS
// =============================================================================
enviarRespuestaJson(['error' => 'Error: El método HTTP solicitado no está permitido en este recurso'], 405);
?>
