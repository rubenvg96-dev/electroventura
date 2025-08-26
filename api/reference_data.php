<?php
// Configuración estricta para evitar cualquier output no deseado
error_reporting(0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

// Evitar cualquier output antes del JSON
ob_start();

require_once '../config/config.php';

// Limpiar completamente cualquier output previo
ob_end_clean();
ob_start();

// Verificar sesión sin iniciar una nueva si ya existe
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

if (!isLoggedIn()) {
    sendJsonResponse(['error' => true, 'message' => 'No autorizado']);
}

header('Content-Type: application/json');

/**
 * Función para enviar respuesta JSON limpia
 */
function sendJsonResponse($data) {
    // Limpiar completamente cualquier output previo
    while (ob_get_level()) {
        ob_end_clean();
    }
    ob_start();
    
    // Asegurar header JSON
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    
    echo json_encode($data);
    ob_end_flush();
    exit;
}

try {
    $database = Database::getInstance();
    $pdo = $database->getConnection();
    
    $response = [
        'obras' => [],
        'proveedores' => [],
        'usuarios' => []
    ];
    
    // Obtener obras
    $stmt = $pdo->query("SELECT id, codigo, nombre FROM obras WHERE activo = 1 ORDER BY codigo");
    $response['obras'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener proveedores
    $stmt = $pdo->query("SELECT id, nombre FROM proveedores WHERE activo = 1 ORDER BY nombre");
    $response['proveedores'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener usuarios
    $stmt = $pdo->query("SELECT id, nombre_completo FROM usuarios WHERE activo = 1 ORDER BY nombre_completo");
    $response['usuarios'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendJsonResponse($response);
    
} catch (Exception $e) {
    sendJsonResponse([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}
?>
