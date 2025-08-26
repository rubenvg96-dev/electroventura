<?php
/**
 * Archivo para limpiar completamente las sesiones
 * Úsalo si tienes problemas con sesiones persistentes
 */

// Iniciar sesión para poder destruirla
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Limpiar todas las variables de sesión
$_SESSION = array();

// Destruir la cookie de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir la sesión
session_destroy();

echo "<h1>Sesión limpiada completamente</h1>";
echo "<p>Todas las sesiones han sido eliminadas.</p>";
echo "<p><a href='index.php'>Ir al inicio</a></p>";
?>
