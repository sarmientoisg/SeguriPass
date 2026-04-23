<?php
/**
 * ARCHIVO: gestion_usuarios.php
 * AUTOR: Carlos Ignacio Sarmiento Garcia
 * FECHA: 23 de abril de 2026
 * DESCRIPCIÓN: Script CRUD para la administración de usuarios (prefectos) del sistema Seguripass.
 * Permite listar, registrar, actualizar y eliminar personal operativo.
 */

require_once 'config.php';

// Identificación del método de solicitud HTTP para determinar la acción
$metodoSolicitud = $_SERVER['REQUEST_METHOD'];

// =============================================================================
// 1. PROCESAMIENTO DE PETICIÓN GET: LISTAR TODOS LOS USUARIOS
// =============================================================================
if ($metodoSolicitud === 'GET') {
    $conexionBaseDatos = establecerConexionBaseDatos();
    
    // Consulta para obtener el catálogo completo de personal registrado
    $consultaUsuarios = "SELECT id, nombre_completo, numero_empleado, usuario, turno, area, permisos, observaciones, fecha_registro
                         FROM usuarios 
                         ORDER BY fecha_registro DESC";
                         
    $resultadoConsulta = $conexionBaseDatos->query($consultaUsuarios);
    
    // Extracción de todos los registros en un arreglo asociativo
    $listadoUsuarios = $resultadoConsulta->fetch_all(MYSQLI_ASSOC);
    
    $conexionBaseDatos->close();
    enviarRespuestaJson($listadoUsuarios);
}

// =============================================================================
// 2. PROCESAMIENTO DE PETICIÓN POST: REGISTRAR NUEVO USUARIO
// =============================================================================
if ($metodoSolicitud === 'POST') {
    $cuerpoPeticion = obtenerCuerpoSolicitudJson();
    
    // Inicialización y saneamiento de variables recibidas
    $nombreCompleto   = trim($cuerpoPeticion['nombre_completo']  ?? '');
    $numeroEmpleado   = trim($cuerpoPeticion['numero_empleado']  ?? '');
    $nombreAcceso     = trim($cuerpoPeticion['usuario']          ?? '');
    $contrasenaPlana  = trim($cuerpoPeticion['contrasena']       ?? '');
    $turnoAsignado    = trim($cuerpoPeticion['turno']            ?? '');
    $areaAsignada     = trim($cuerpoPeticion['area']             ?? '');
    $rolPermisos      = trim($cuerpoPeticion['rol']              ?? 'Prefecto');
    $observaciones    = trim($cuerpoPeticion['observaciones']    ?? '');

    // Validación de integridad de datos obligatorios
    if (!$nombreCompleto || !$numeroEmpleado || !$nombreAcceso || !$contrasenaPlana || !$turnoAsignado || !$areaAsignada) {
        enviarRespuestaJson(['error' => 'Error: Todos los campos marcados como obligatorios deben ser completados'], 400);
    }
    
    // Validación de seguridad para la longitud de la contraseña
    if (strlen($contrasenaPlana) < 8) {
        enviarRespuestaJson(['error' => 'Seguridad: La contraseña debe contener al menos 8 caracteres'], 400);
    }

    $conexionBaseDatos = establecerConexionBaseDatos();

    /**
     * VERIFICACIÓN DE DUPLICADOS:
     * Se comprueba que no exista otro registro con el mismo número de empleado o nombre de usuario.
     */
    $sentenciaVerificar = $conexionBaseDatos->prepare("SELECT id FROM usuarios WHERE numero_empleado = ? OR usuario = ?");
    $sentenciaVerificar->bind_param('ss', $numeroEmpleado, $nombreAcceso);
    $sentenciaVerificar->execute();
    $sentenciaVerificar->store_result();
    
    if ($sentenciaVerificar->num_rows > 0) {
        $sentenciaVerificar->close(); 
        $conexionBaseDatos->close();
        enviarRespuestaJson(['error' => 'Conflicto: El número de empleado o el nombre de usuario ya se encuentran registrados'], 409);
    }
    $sentenciaVerificar->close();

    // Inserción del nuevo registro con cifrado de contraseña SHA-256
    $sentenciaInsertar = $conexionBaseDatos->prepare(
        "INSERT INTO usuarios (nombre_completo, numero_empleado, usuario, contrasena, turno, area, permisos, observaciones)
         VALUES (?, ?, ?, SHA2(?, 256), ?, ?, ?, ?)"
    );
    $sentenciaInsertar->bind_param('ssssssss', $nombreCompleto, $numeroEmpleado, $nombreAcceso, $contrasenaPlana, $turnoAsignado, $areaAsignada, $rolPermisos, $observaciones);
    $sentenciaInsertar->execute();
    
    $nuevoIdGenerado = $conexionBaseDatos->insert_id;
    $sentenciaInsertar->close();
    $conexionBaseDatos->close();

    enviarRespuestaJson([
        'ok'      => true, 
        'id'      => $nuevoIdGenerado, 
        'mensaje' => 'El usuario ha sido registrado exitosamente en el sistema'
    ]);
}

// =============================================================================
// 3. PROCESAMIENTO DE PETICIÓN PUT: ACTUALIZAR DATOS DE USUARIO
// =============================================================================
if ($metodoSolicitud === 'PUT') {
    $cuerpoPeticion = obtenerCuerpoSolicitudJson();
    
    // Identificador único requerido para la actualización
    $identificadorUsuario = intval($cuerpoPeticion['id']             ?? 0);
    $nombreActualizado    = trim($cuerpoPeticion['nombre_completo']  ?? '');
    $numeroEmpleadoAct    = trim($cuerpoPeticion['numero_empleado']  ?? '');
    $turnoActualizado     = trim($cuerpoPeticion['turno']            ?? '');
    $areaActualizada      = trim($cuerpoPeticion['area']             ?? '');
    $rolActualizado       = trim($cuerpoPeticion['rol']              ?? '');

    if (!$identificadorUsuario || !$nombreActualizado || !$numeroEmpleadoAct || !$turnoActualizado || !$areaActualizada || !$rolActualizado) {
        enviarRespuestaJson(['error' => 'Error: Información insuficiente para realizar la actualización'], 400);
    }

    $conexionBaseDatos = establecerConexionBaseDatos();
    
    // Actualización de campos permitidos
    $sentenciaActualizar = $conexionBaseDatos->prepare(
        "UPDATE usuarios SET nombre_completo=?, numero_empleado=?, turno=?, area=?, permisos=? WHERE id=?"
    );
    $sentenciaActualizar->bind_param('sssssi', $nombreActualizado, $numeroEmpleadoAct, $turnoActualizado, $areaActualizada, $rolActualizado, $identificadorUsuario);
    $sentenciaActualizar->execute();
    
    $sentenciaActualizar->close();
    $conexionBaseDatos->close();
    
    enviarRespuestaJson(['ok' => true, 'mensaje' => 'Los datos del usuario han sido actualizados correctamente']);
}

// =============================================================================
// 4. PROCESAMIENTO DE PETICIÓN DELETE: ELIMINAR USUARIO
// =============================================================================
if ($metodoSolicitud === 'DELETE') {
    // Se obtiene el ID mediante el parámetro de la URL
    $identificadorEliminar = intval($_GET['id'] ?? 0);
    
    if (!$identificadorEliminar) {
        enviarRespuestaJson(['error' => 'Error: Se requiere el identificador del usuario para proceder con la eliminación'], 400);
    }

    $conexionBaseDatos = establecerConexionBaseDatos();
    
    $sentenciaEliminar = $conexionBaseDatos->prepare("DELETE FROM usuarios WHERE id = ?");
    $sentenciaEliminar->bind_param('i', $identificadorEliminar);
    $sentenciaEliminar->execute();
    
    $sentenciaEliminar->close();
    $conexionBaseDatos->close();
    
    enviarRespuestaJson(['ok' => true, 'mensaje' => 'El registro ha sido eliminado del sistema de forma definitiva']);
}

// Respuesta por defecto para métodos HTTP no implementados
enviarRespuestaJson(['error' => 'Error: El método solicitado no es válido para este recurso'], 405);
?>

