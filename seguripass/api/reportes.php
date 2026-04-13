<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET: Listar reportes generados
if ($method === 'GET') {
    $conn   = conectar();
    $result = $conn->query(
        "SELECT r.id, r.rango_fecha_inicio, r.rango_fecha_fin, r.formato, r.area_filtro,
                r.fecha_generacion, u.nombre_completo AS generado_por,
                (SELECT COUNT(*) FROM solicitudes s
                 JOIN visitantes v ON v.id = s.visitante_id
                 WHERE DATE(s.fecha_solicitud) BETWEEN r.rango_fecha_inicio AND r.rango_fecha_fin
                 AND (r.area_filtro IS NULL OR r.area_filtro = '' OR v.area = r.area_filtro)
                ) AS total_registros
         FROM reportes r
         JOIN usuarios u ON u.id = r.usuario_id
         ORDER BY r.fecha_generacion DESC"
    );
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $conn->close();
    responder($rows);
}

// POST: Generar reporte (guarda el registro y devuelve los datos)
if ($method === 'POST') {
    $body       = leerBody();
    $usuario_id = intval($body['usuario_id']         ?? 0);
    $fi         = trim($body['rango_fecha_inicio']   ?? '');
    $ff         = trim($body['rango_fecha_fin']      ?? '');
    $formato    = trim($body['formato']              ?? '');
    $area       = trim($body['area_filtro']          ?? '');

    if (!$usuario_id || !$fi || !$ff || !$formato) {
        responder(['error' => 'Faltan campos requeridos'], 400);
    }

    $conn = conectar();

    // Guardar registro del reporte
    $stmt = $conn->prepare(
        "INSERT INTO reportes (usuario_id, rango_fecha_inicio, rango_fecha_fin, formato, area_filtro)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('issss', $usuario_id, $fi, $ff, $formato, $area);
    $stmt->execute();
    $stmt->close();

    // Obtener datos para el reporte
    $sql = "SELECT v.nombre_completo, v.identificacion_oficial, v.motivo_visita,
                   v.persona_a_visitar, v.area, s.estado, s.fecha_solicitud, s.fecha_atencion,
                   u.nombre_completo AS prefecto
            FROM solicitudes s
            JOIN visitantes v ON v.id = s.visitante_id
            LEFT JOIN validaciones val ON val.solicitud_id = s.id
            LEFT JOIN usuarios u ON u.id = val.usuario_id
            WHERE DATE(s.fecha_solicitud) BETWEEN ? AND ?";

    $params = [$fi, $ff];
    $types  = 'ss';

    if ($area) {
        $sql    .= " AND v.area = ?";
        $params[] = $area;
        $types   .= 's';
    }

    $sql .= " ORDER BY s.fecha_solicitud DESC";

    $stmt2 = $conn->prepare($sql);
    $stmt2->bind_param($types, ...$params);
    $stmt2->execute();
    $datos = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt2->close();
    $conn->close();

    responder(['ok' => true, 'datos' => $datos, 'total' => count($datos)]);
}

responder(['error' => 'Método no permitido'], 405);
?>
