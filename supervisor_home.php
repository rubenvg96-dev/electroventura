<?php
/**
 * ELECTROVENTURA v2.0 - PÁGINA DE INICIO SUPERVISOR
 * =================================================
 * Página principal para supervisores con 2 opciones:
 * 1. Imputaciones 
 * 2. Dashboard
 */

require_once 'config/config.php';
requireLogin();

// Redirigir trabajadores a su página
if (isTrabajador()) {
    redirect('trabajador_home.php');
}

// Redirigir admins a su página si acceden aquí
if (canManageObrasDelDia()) {
    redirect('admin_home.php');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Panel de Supervisión - Electroventura v2.0</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="internal-page">
    <!-- Header de navegación -->
    <nav class="navbar">
        <div class="d-flex" style="width: 100%; justify-content: space-between; align-items: center;">
            <a href="#" class="navbar-brand">
                <i class="fas fa-bolt"></i>
                Electroventura v2.0
            </a>
            <div class="navbar-user">
                <span class="user-name"><?= htmlspecialchars(getCurrentUser()['nombre_completo'] ?? 'Usuario') ?></span>
                <span class="user-role">Supervisor</span>
                <a href="logout.php" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Salir
                </a>
            </div>
        </div>
    </nav>

    <!-- Contenido principal -->
    <div class="inicio-container">
        <div class="inicio-card fade-in">
            <!-- Encabezado -->
            <div class="inicio-header">
                <h1>Panel de Supervisión</h1>
                <div class="user-info">
                    <i class="fas fa-user-tie"></i> 
                    Bienvenido, <?= htmlspecialchars(getCurrentUser()['nombre_completo'] ?? 'Usuario') ?>
                </div>
                <div class="user-role">Control y seguimiento de obras</div>
            </div>

            <!-- Opciones disponibles -->
            <div class="opciones-grid supervisor">
                <!-- Opción 1: Imputaciones -->
                <a href="imputaciones.php" class="opcion-card imputaciones">
                    <div class="opcion-content">
                        <div class="icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <h3>Imputaciones</h3>
                        <p>Registrar y supervisar horas trabajadas, gastos de materiales y costes de las obras activas del día.</p>
                    </div>
                </a>

                <!-- Opción 2: Dashboard -->
                <a href="dashboard.php" class="opcion-card dashboard">
                    <div class="opcion-content">
                        <div class="icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3>Dashboard</h3>
                        <p>Informes detallados y análisis de rendimiento. Control de avance y costes por obra.</p>
                    </div>
                </a>

                <!-- Opción 3: Gestión Administrativa -->
                <a href="gestion_administrativa.php" class="opcion-card gestion">
                    <div class="opcion-content">
                        <div class="icon">
                            <i class="fas fa-cogs"></i>
                        </div>
                        <h3>Gestión Administrativa</h3>
                        <p>Acceso a módulos de gestión de materiales, gastos y registros administrativos.</p>
                    </div>
                </a>
            </div>

            <!-- Información del rol -->
            <div class="mt-4">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Tu rol como supervisor:</strong> Puedes registrar imputaciones en las obras del día y acceder 
                    a informes completos para el seguimiento y control del trabajo realizado.
                </div>
            </div>

            <!-- Resumen rápido del día -->
            <div class="mt-3" id="resumenDia">
                <!-- Se cargará dinámicamente -->
            </div>

            <!-- Información de acceso -->
            <div class="mt-3 text-center">
                <small style="color: #6c757d;">
                    <i class="fas fa-clock"></i> Último acceso: <?= date('d/m/Y H:i', $_SESSION['login_time']) ?>
                </small>
            </div>
        </div>
    </div>

    <!-- JavaScript para UX y carga de datos -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Animación de entrada
            document.querySelector('.inicio-card').classList.add('fade-in');
            
            // Efecto hover en las tarjetas
            const opciones = document.querySelectorAll('.opcion-card');
            opciones.forEach(opcion => {
                opcion.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-10px) scale(1.02)';
                });
                
                opcion.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
                
                // Efecto de click
                opcion.addEventListener('mousedown', function() {
                    this.style.transform = 'translateY(-5px) scale(0.98)';
                });
                
                opcion.addEventListener('mouseup', function() {
                    this.style.transform = 'translateY(-10px) scale(1.02)';
                });
            });

            // Cargar resumen del día
            loadDayResumen();
        });

        // Función para cargar resumen del día
        async function loadDayResumen() {
            try {
                const response = await fetch('api/day_summary.php');
                const data = await response.json();
                
                const resumenContainer = document.getElementById('resumenDia');
                
                if (data.success && data.obras_activas > 0) {
                    resumenContainer.innerHTML = `
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <strong>Estado del día:</strong> 
                            ${data.obras_activas} obra${data.obras_activas > 1 ? 's' : ''} activa${data.obras_activas > 1 ? 's' : ''} • 
                            ${data.total_trabajadores || 0} trabajador${(data.total_trabajadores || 0) !== 1 ? 'es' : ''} disponible${(data.total_trabajadores || 0) !== 1 ? 's' : ''}
                        </div>
                    `;
                } else if (data.obras_activas === 0) {
                    resumenContainer.innerHTML = `
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Atención:</strong> No hay obras configuradas para hoy. 
                            Contacta con el administrador para activar obras.
                        </div>
                    `;
                }
            } catch (error) {
                // Error al cargar resumen del día
            }
        }

        // Prevenir acceso directo sin sesión
        if (!document.querySelector('.navbar-user')) {
            window.location.href = 'login.php';
        }

        // Auto-refresh del resumen cada 5 minutos
        setInterval(loadDayResumen, 300000);
    </script>
</body>
</html>
