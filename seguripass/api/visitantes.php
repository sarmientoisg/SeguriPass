<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

// POST: Registrar visitante y crear solicitud
if ($method === 'POST') {
    $body = leerBody();
    $nombre   = trim($body['nombre_completo']       ?? '');
    $ident    = trim($body['identificacion_oficial'] ?? '');
    $motivo   = trim($body['motivo_visita']          ?? '');
    $persona  = trim($body['persona_a_visitar']      ?? '');
    $area     = trim($body['area']                   ?? '');

    if (!$nombre || !$ident || !$motivo || !$area) {
        responder(['error' => 'Faltan campos obligatorios'], 400);
    }

    $conn = conectar();

    // Insertar visitante
    $stmt = $conn->prepare(
        "INSERT INTO visitantes (nombre_completo, identificacion_oficial, motivo_visita, persona_a_visitar, area)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('sssss', $nombre, $ident, $motivo, $persona, $area);
    $stmt->execute();
    $visitante_id = $conn->insert_id;
    $stmt->close();

    // Crear solicitud
    $stmt2 = $conn->prepare(
        "INSERT INTO solicitudes (visitante_id) VALUES (?)"
    );
    $stmt2->bind_param('i', $visitante_id);
    $stmt2->execute();
    $solicitud_id = $conn->insert_id;
    $stmt2->close();
    $conn->close();

    responder([
        'ok'           => true,
        'visitante_id' => $visitante_id,
        'solicitud_id' => $solicitud_id,
        'mensaje'      => 'Solicitud enviada correctamente'
    ]);
}

// GET: Estado de una solicitud específica
if ($method === 'GET') {
    $solicitud_id = intval($_GET['solicitud_id'] ?? 0);
    if (!$solicitud_id) {
        responder(['error' => 'solicitud_id requerido'], 400);
    }

    $conn = conectar();
    $stmt = $conn->prepare(
        "SELECT s.id, s.estado, s.motivo_rechazo, s.fecha_atencion,
                v.nombre_completo, v.area, v.motivo_visita,
                u.nombre_completo AS prefecto
         FROM solicitudes s
         JOIN visitantes v ON v.id = s.visitante_id
         LEFT JOIN validaciones val ON val.solicitud_id = s.id
         LEFT JOIN usuarios u ON u.id = val.usuario_id
         WHERE s.id = ?
         LIMIT 1"
    );
    $stmt->bind_param('i', $solicitud_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        responder(['error' => 'Solicitud no encontrada'], 404);
    }

    $data = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    responder($data);
}

responder(['error' => 'Método no permitido'], 405);
?>
