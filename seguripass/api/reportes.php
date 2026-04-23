<?php
/**
 * ARCHIVO: gestion_reportes.php
 * AUTOR: Carlos Ignacio Sarmiento Garcia
 * FECHA: 23 de abril de 2026
 * DESCRIPCIÓN: Script para la generación histórica y consulta de reportes de visitas.
 * Permite filtrar por rangos de fecha, áreas específicas y formatos de salida.
 */

require_once 'config.php';

// Identificación del método de solicitud HTTP
$metodoSolicitud = $_SERVER['REQUEST_METHOD'];

// =============================================================================
// 1. PROCESAMIENTO DE PETICIÓN GET: LISTAR REPORTES GENERADOS
// =============================================================================
if ($metodoSolicitud === 'GET') {
    $conexionBaseDatos = establecerConexionBaseDatos();
    
    // Consulta para listar el historial de reportes con subconsulta para conteo de registros
    $consultaHistorial = "SELECT r.id, r.rango_fecha_inicio, r.rango_fecha_fin, r.formato, r.area_filtro,
                r.fecha_generacion, u.nombre_completo AS generado_por,
                (SELECT COUNT(*) FROM solicitudes s
                 JOIN visitantes v ON v.id = s.visitante_id
                 WHERE DATE(s.fecha_solicitud) BETWEEN r.rango_fecha_inicio AND r.rango_fecha_fin
                 AND (r.area_filtro IS NULL OR r.area_filtro = '' OR v.area = r.area_filtro)
                ) AS total_registros
         FROM reportes r
         JOIN usuarios u ON u.id = r.usuario_id
         ORDER BY r.fecha_generacion DESC";
         
    $resultadoConsulta = $conexionBaseDatos->query($consultaHistorial);
    $listadoReportes   = $resultadoConsulta->fetch_all(MYSQLI_ASSOC);
    
    $conexionBaseDatos->close();
    enviarRespuestaJson($listadoReportes);
}

// =============================================================================
// 2. PROCESAMIENTO DE PETICIÓN POST: GENERAR Y REGISTRAR REPORTE
// =============================================================================
if ($metodoSolicitud === 'POST') {
    $cuerpoPeticion = obtenerCuerpoSolicitudJson();
    
    // Inicialización de variables con nomenclatura descriptiva
    $identificadorUsuario = intval($cuerpoPeticion['usuario_id']         ?? 0);
    $fechaInicioFiltro    = trim($cuerpoPeticion['rango_fecha_inicio']   ?? '');
    $fechaFinFiltro       = trim($cuerpoPeticion['rango_fecha_fin']      ?? '');
    $formatoReporte       = trim($cuerpoPeticion['formato']              ?? '');
    $areaEspecifica       = trim($cuerpoPeticion['area_filtro']          ?? '');

    // Validación de campos obligatorios para la generación
    if (!$identificadorUsuario || !$fechaInicioFiltro || !$fechaFinFiltro || !$formatoReporte) {
        enviarRespuestaJson(['error' => 'Error: Los campos de usuario, fechas y formato son obligatorios'], 400);
    }

    $conexionBaseDatos = establecerConexionBaseDatos();

    /**
     * FASE 1: Registro del log del reporte generado en la base de datos.
     */
    $sentenciaLog = $conexionBaseDatos->prepare(
        "INSERT INTO reportes (usuario_id, rango_fecha_inicio, rango_fecha_fin, formato, area_filtro)
         VALUES (?, ?, ?, ?, ?)"
    );
    $sentenciaLog->bind_param('issss', $identificadorUsuario, $fechaInicioFiltro, $fechaFinFiltro, $formatoReporte, $areaEspecifica);
    $sentenciaLog->execute();
    $sentenciaLog->close();

    /**
     * FASE 2: Extracción de datos maestros para el contenido del reporte.
     * Se construye una consulta dinámica basada en si existe un filtro de área.
     */
    $consultaMaestra = "SELECT v.nombre_completo, v.identificacion_oficial, v.motivo_visita,
                               v.persona_a_visitar, v.area, s.estado, s.fecha_solicitud, s.fecha_atencion,
                               u.nombre_completo AS prefecto
                        FROM solicitudes s
                        JOIN visitantes v ON v.id = s.visitante_id
                        LEFT JOIN validaciones val ON val.solicitud_id = s.id
                        LEFT JOIN usuarios u ON u.id = val.usuario_id
                        WHERE DATE(s.fecha_solicitud) BETWEEN ? AND ?";

    $parametrosBusqueda = [$fechaInicioFiltro, $fechaFinFiltro];
    $tiposDatos         = 'ss';

    // Agregar filtro de área a la consulta si fue proporcionado por el usuario
    if ($areaEspecifica) {
        $consultaMaestra   .= " AND v.area = ?";
        $parametrosBusqueda[] = $areaEspecifica;
        $tiposDatos          .= 's';
    }

    $consultaMaestra .= " ORDER BY s.fecha_solicitud DESC";

    $sentenciaDatos = $conexionBaseDatos->prepare($consultaMaestra);
    $sentenciaDatos->bind_param($tiposDatos, ...$parametrosBusqueda);
    $sentenciaDatos->execute();
    
    $datosReporte = $sentenciaDatos->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Cierre de conexiones y recursos
    $sentenciaDatos->close();
    $conexionBaseDatos->close();

    // Entrega de los resultados al cliente
    enviarRespuestaJson([
        'ok'    => true, 
        'datos' => $datosReporte, 
        'total' => count($datosReporte)
    ]);
}

// Respuesta para métodos no permitidos
enviarRespuestaJson(['error' => 'Error: El método solicitado no está disponible para este módulo'], 405);
?>
