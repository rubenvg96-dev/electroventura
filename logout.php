<?php
/**
 * ELECTROVENTURA v2.0 - CERRAR SESIÓN
 * ===================================
 * Cierra la sesión del usuario y redirige al login
 */

session_start();

// Log de la actividad antes de destruir la sesión
if (isset($_SESSION['user_id'])) {
    require_once 'config/database.php';
    require_once 'config/config.php';
    
    try {
        logActivity($_SESSION['user_id'], 'LOGOUT', 'Cierre de sesión');
    } catch (Exception $e) {
        // Error en el log, pero continúa con el logout
        error_log("Error en logout log: " . $e->getMessage());
    }
}

// Destruir todas las variables de sesión
$_SESSION = array();

// Destruir la cookie de sesión si existe
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir la sesión
session_destroy();

// Redirigir al login
header("Location: login.php");
exit();
?>
