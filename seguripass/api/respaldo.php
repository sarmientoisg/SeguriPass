<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET: Historial de respaldos
if ($method === 'GET') {
    $conn   = conectar();
    $result = $conn->query(
        "SELECT r.*, u.nombre_completo AS realizado_por
         FROM respaldos r
         JOIN usuarios u ON u.id = r.usuario_id
         ORDER BY r.fecha DESC"
    );
    responder($result->fetch_all(MYSQLI_ASSOC));
}

// POST: Registrar respaldo y devolver datos para descarga
if ($method === 'POST') {
    $body       = leerBody();
    $usuario_id = intval($body['usuario_id'] ?? 0);
    $tipo       = trim($body['tipo']         ?? 'Respaldo');

    if (!$usuario_id) responder(['error' => 'usuario_id requerido'], 400);

    $conn = conectar();

    // Exportar todas las tablas relevantes
    $datos = [];

    $tablas = ['usuarios','visitantes','solicitudes','validaciones','reportes','configuraciones'];
    foreach ($tablas as $tabla) {
        $result = $conn->query("SELECT * FROM `$tabla`");
        $datos[$tabla] = $result->fetch_all(MYSQLI_ASSOC);
    }

    $nombre = 'respaldo_seguripass_' . date('Y-m-d_H-i-s') . '.json';

    // Registrar en BD
    $stmt = $conn->prepare(
        "INSERT INTO respaldos (usuario_id, tipo, nombre_archivo, estado) VALUES (?, ?, ?, 'Completado')"
    );
    $stmt->bind_param('iss', $usuario_id, $tipo, $nombre);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    responder([
        'ok'     => true,
        'nombre' => $nombre,
        'datos'  => $datos,
        'mensaje'=> 'Respaldo generado correctamente'
    ]);
}

responder(['error' => 'Método no permitido'], 405);
?>
