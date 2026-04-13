<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET: Listar solicitudes (con filtro opcional por estado)
if ($method === 'GET') {
    $estado = $_GET['estado'] ?? '';
    $buscar = $_GET['buscar'] ?? '';
    $conn   = conectar();

    $sql = "SELECT s.id, s.estado, s.motivo_rechazo, s.fecha_solicitud, s.fecha_atencion,
                   v.nombre_completo, v.identificacion_oficial, v.motivo_visita,
                   v.persona_a_visitar, v.area,
                   u.nombre_completo AS prefecto
            FROM solicitudes s
            JOIN visitantes v ON v.id = s.visitante_id
            LEFT JOIN validaciones val ON val.solicitud_id = s.id
            LEFT JOIN usuarios u ON u.id = val.usuario_id
            WHERE 1=1";

    $params = [];
    $types  = '';

    if ($estado) {
        $sql .= " AND s.estado = ?";
        $params[] = $estado;
        $types   .= 's';
    }
    if ($buscar) {
        $sql .= " AND v.nombre_completo LIKE ?";
        $like = "%$buscar%";
        $params[] = $like;
        $types   .= 's';
    }

    $sql .= " ORDER BY s.fecha_solicitud DESC";

    $stmt = $conn->prepare($sql);
    if ($types) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $rows   = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();
    responder($rows);
}

// PUT: Aprobar o rechazar solicitud
if ($method === 'PUT') {
    $body        = leerBody();
    $id          = intval($body['id']             ?? 0);
    $estado      = trim($body['estado']           ?? '');
    $usuario_id  = intval($body['usuario_id']     ?? 0);
    $motivo_rec  = trim($body['motivo_rechazo']   ?? '');

    if (!$id || !$estado || !$usuario_id) {
        responder(['error' => 'Faltan campos requeridos'], 400);
    }
    if (!in_array($estado, ['Aprobado', 'Rechazado'])) {
        responder(['error' => 'Estado inválido'], 400);
    }

    $conn = conectar();

    // Actualizar solicitud
    $stmt = $conn->prepare(
        "UPDATE solicitudes
         SET estado = ?, motivo_rechazo = ?, fecha_atencion = NOW()
         WHERE id = ?"
    );
    $stmt->bind_param('ssi', $estado, $motivo_rec, $id);
    $stmt->execute();
    $stmt->close();

    // Registrar validación
    $stmt2 = $conn->prepare(
        "INSERT INTO validaciones (solicitud_id, usuario_id, resultado)
         VALUES (?, ?, ?)"
    );
    $stmt2->bind_param('iis', $id, $usuario_id, $estado);
    $stmt2->execute();
    $stmt2->close();

    // Actualizar estado del visitante
    $stmt3 = $conn->prepare(
        "UPDATE visitantes v
         JOIN solicitudes s ON s.visitante_id = v.id
         SET v.estado = ?
         WHERE s.id = ?"
    );
    $stmt3->bind_param('si', $estado, $id);
    $stmt3->execute();
    $stmt3->close();

    $conn->close();
    responder(['ok' => true, 'mensaje' => "Solicitud $estado correctamente"]);
}

responder(['error' => 'Método no permitido'], 405);
?>
