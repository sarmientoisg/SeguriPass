<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(['error' => 'Método no permitido'], 405);
}

$body = leerBody();
$usuario   = trim($body['usuario']   ?? '');
$contrasena = trim($body['contrasena'] ?? '');

if (!$usuario || !$contrasena) {
    responder(['error' => 'Usuario y contraseña son requeridos'], 400);
}

$conn = conectar();

$stmt = $conn->prepare(
    "SELECT id, nombre_completo, numero_empleado, turno, area, permisos
     FROM usuarios
     WHERE usuario = ? AND contrasena = SHA2(?, 256)
     LIMIT 1"
);
$stmt->bind_param('ss', $usuario, $contrasena);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    responder(['error' => 'Usuario o contraseña incorrectos'], 401);
}

$user = $result->fetch_assoc();
$stmt->close();
$conn->close();

responder([
    'ok'              => true,
    'id'              => $user['id'],
    'nombre_completo' => $user['nombre_completo'],
    'numero_empleado' => $user['numero_empleado'],
    'turno'           => $user['turno'],
    'area'            => $user['area'],
    'rol'             => $user['permisos']
]);
?>
