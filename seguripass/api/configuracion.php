<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET: Obtener configuración actual
if ($method === 'GET') {
    $conn   = conectar();
    $result = $conn->query(
        "SELECT c.*, u.nombre_completo AS modificado_por
         FROM configuraciones c
         JOIN usuarios u ON u.id = c.usuario_id
         ORDER BY c.fecha_modificacion DESC LIMIT 1"
    );
    if ($result->num_rows === 0) {
        responder(['dias_operacion'=>'Lunes a Viernes','hora_inicio'=>'07:00','hora_fin'=>'21:00',
                   'tiempo_sesion'=>30,'nombre_plantel'=>'CETis 132']);
    }
    responder($result->fetch_assoc());
}

// POST: Guardar configuración
if ($method === 'POST') {
    $body       = leerBody();
    $usuario_id = intval($body['usuario_id']    ?? 0);
    $dias       = trim($body['dias_operacion']  ?? '');
    $hi         = trim($body['hora_inicio']     ?? '');
    $hf         = trim($body['hora_fin']        ?? '');
    $sesion     = intval($body['tiempo_sesion'] ?? 30);
    $plantel    = trim($body['nombre_plantel']  ?? 'CETis 132');

    if (!$usuario_id || !$dias || !$hi || !$hf) {
        responder(['error' => 'Faltan campos requeridos'], 400);
    }

    $conn = conectar();
    $stmt = $conn->prepare(
        "INSERT INTO configuraciones (usuario_id, dias_operacion, hora_inicio, hora_fin, tiempo_sesion, nombre_plantel)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('isssss', $usuario_id, $dias, $hi, $hf, $sesion, $plantel);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    responder(['ok' => true, 'mensaje' => 'Configuración guardada correctamente']);
}

responder(['error' => 'Método no permitido'], 405);
?>
