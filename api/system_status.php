<?php
/**
 * ELECTROVENTURA v2.0 - API DE ESTADO DEL SISTEMA
 * ===============================================
 * Proporciona información sobre el estado actual del sistema
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';
require_once '../config/config.php';

// Verificar sesión activa
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Obtener obras del día activas
    $stmt = $db->prepare("
        SELECT COUNT(*) as obras_del_dia 
        FROM obras_dia od
        INNER JOIN obras o ON od.obra_id = o.obra_id
        WHERE od.fecha = CURDATE() 
        AND od.activo = 1
        AND o.estado = 'activa'
    ");
    $stmt->execute();
    $obras_dia = $stmt->fetch(PDO::FETCH_ASSOC)['obras_del_dia'];
    
    // Obtener total de obras activas
    $stmt = $db->prepare("
        SELECT COUNT(*) as total_obras 
        FROM obras 
        WHERE estado = 'activa'
    ");
    $stmt->execute();
    $total_obras = $stmt->fetch(PDO::FETCH_ASSOC)['total_obras'];
    
    // Obtener usuarios activos por rol
    $stmt = $db->prepare("
        SELECT role, COUNT(*) as cantidad
        FROM usuarios 
        WHERE activo = 1 
        GROUP BY role
    ");
    $stmt->execute();
    $usuarios_por_rol = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $usuarios_por_rol[$row['role']] = (int)$row['cantidad'];
    }
    
    // Obtener imputaciones de hoy
    $stmt = $db->prepare("
        SELECT COUNT(DISTINCT user_id) as trabajadores_activos,
               COUNT(*) as total_registros_hoy
        FROM horas_trabajadas 
        WHERE DATE(fecha_hora) = CURDATE()
    ");
    $stmt->execute();
    $actividad_hoy = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Obtener última actividad del sistema
    $stmt = $db->prepare("
        SELECT MAX(created_at) as ultima_actividad
        FROM audit_log 
        WHERE DATE(created_at) = CURDATE()
    ");
    $stmt->execute();
    $ultima_actividad = $stmt->fetch(PDO::FETCH_ASSOC)['ultima_actividad'];
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'obras_del_dia' => (int)$obras_dia,
        'total_obras_activas' => (int)$total_obras,
        'usuarios' => [
            'admin' => $usuarios_por_rol['admin'] ?? 0,
            'supervisor' => $usuarios_por_rol['supervisor'] ?? 0,
            'trabajador' => $usuarios_por_rol['trabajador'] ?? 0,
            'total' => array_sum($usuarios_por_rol)
        ],
        'actividad_hoy' => [
            'trabajadores_activos' => (int)($actividad_hoy['trabajadores_activos'] ?? 0),
            'registros_totales' => (int)($actividad_hoy['total_registros_hoy'] ?? 0)
        ],
        'ultima_actividad' => $ultima_actividad,
        'sistema' => [
            'version' => '2.0',
            'fecha_actual' => date('Y-m-d'),
            'hora_actual' => date('H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error en system_status API: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error del servidor'
    ]);
}
?>
