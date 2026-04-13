<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET: Listar todos los prefectos
if ($method === 'GET') {
    $conn = conectar();
    $result = $conn->query(
        "SELECT id, nombre_completo, numero_empleado, usuario, turno, area, permisos, observaciones, fecha_registro
         FROM usuarios ORDER BY fecha_registro DESC"
    );
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $conn->close();
    responder($rows);
}

// POST: Registrar nuevo prefecto
if ($method === 'POST') {
    $body    = leerBody();
    $nombre  = trim($body['nombre_completo']  ?? '');
    $numEmp  = trim($body['numero_empleado']  ?? '');
    $usr     = trim($body['usuario']          ?? '');
    $pass    = trim($body['contrasena']       ?? '');
    $turno   = trim($body['turno']            ?? '');
    $area    = trim($body['area']             ?? '');
    $rol     = trim($body['rol']              ?? 'Prefecto');
    $obs     = trim($body['observaciones']    ?? '');

    if (!$nombre || !$numEmp || !$usr || !$pass || !$turno || !$area) {
        responder(['error' => 'Faltan campos obligatorios'], 400);
    }
    if (strlen($pass) < 8) {
        responder(['error' => 'La contraseña debe tener mínimo 8 caracteres'], 400);
    }

    $conn = conectar();

    // Verificar duplicados
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE numero_empleado = ? OR usuario = ?");
    $stmt->bind_param('ss', $numEmp, $usr);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close(); $conn->close();
        responder(['error' => 'El número de empleado o usuario ya existe'], 409);
    }
    $stmt->close();

    $stmt2 = $conn->prepare(
        "INSERT INTO usuarios (nombre_completo, numero_empleado, usuario, contrasena, turno, area, permisos, observaciones)
         VALUES (?, ?, ?, SHA2(?, 256), ?, ?, ?, ?)"
    );
    $stmt2->bind_param('ssssssss', $nombre, $numEmp, $usr, $pass, $turno, $area, $rol, $obs);
    $stmt2->execute();
    $id = $conn->insert_id;
    $stmt2->close();
    $conn->close();

    responder(['ok' => true, 'id' => $id, 'mensaje' => 'Prefecto registrado correctamente']);
}

// PUT: Modificar prefecto
if ($method === 'PUT') {
    $body   = leerBody();
    $id     = intval($body['id']             ?? 0);
    $nombre = trim($body['nombre_completo']  ?? '');
    $numEmp = trim($body['numero_empleado']  ?? '');
    $turno  = trim($body['turno']            ?? '');
    $area   = trim($body['area']             ?? '');
    $rol    = trim($body['rol']              ?? '');

    if (!$id || !$nombre || !$numEmp || !$turno || !$area || !$rol) {
        responder(['error' => 'Faltan campos requeridos'], 400);
    }

    $conn = conectar();
    $stmt = $conn->prepare(
        "UPDATE usuarios SET nombre_completo=?, numero_empleado=?, turno=?, area=?, permisos=? WHERE id=?"
    );
    $stmt->bind_param('sssssi', $nombre, $numEmp, $turno, $area, $rol, $id);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    responder(['ok' => true, 'mensaje' => 'Prefecto actualizado correctamente']);
}

// DELETE: Eliminar prefecto
if ($method === 'DELETE') {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) responder(['error' => 'ID requerido'], 400);

    $conn = conectar();
    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    responder(['ok' => true, 'mensaje' => 'Prefecto eliminado']);
}

responder(['error' => 'Método no permitido'], 405);
?>
