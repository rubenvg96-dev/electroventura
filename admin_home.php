<?php
/**
 * ELECTROVENTURA v2.0 - PÁGINA DE INICIO ADMINISTRADOR
 * ===================================================
 * Página principal para administradores con 3 opciones:
 * 1. Gestionar Obras del Día
 * 2. Imputaciones 
 * 3. Dashboard
 */

require_once 'config/config.php';
requireLogin();

// Solo administradores pueden acceder
if (!canManageObrasDelDia()) {
    redirect('supervisor_home.php');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Panel de Administración - Electroventura v2.0</title>
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
                <span class="user-role">Administrador</span>
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
                <h1>Panel de Administración</h1>
                <div class="user-info">
                    <i class="fas fa-user-shield"></i> 
                    Bienvenido, <?= htmlspecialchars(getCurrentUser()['nombre_completo'] ?? 'Usuario') ?>
                </div>
                <div class="user-role">Control total del sistema</div>
            </div>

            <!-- Opciones disponibles -->
            <div class="opciones-grid admin">
                <!-- Opción 1: Gestionar Obras del Día -->
                <a href="gestionar_obras.php" class="opcion-card obras">
                    <div class="opcion-content">
                        <div class="icon">
                            <i class="fas fa-hard-hat"></i>
                        </div>
                        <h3>Gestionar Obras del Día</h3>
                        <p>Seleccionar y configurar las obras activas para el día actual. Control de disponibilidad de obras.</p>
                    </div>
                </a>

                <!-- Opción 2: Imputaciones -->
                <a href="imputaciones.php" class="opcion-card imputaciones">
                    <div class="opcion-content">
                        <div class="icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <h3>Imputaciones</h3>
                        <p>Registrar horas trabajadas, gastos de materiales y otros costes asociados a las obras del día.</p>
                    </div>
                </a>

                <!-- Opción 3: Dashboard -->
                <a href="dashboard.php" class="opcion-card dashboard">
                    <div class="opcion-content">
                        <div class="icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3>Dashboard</h3>
                        <p>Informes y análisis completos. Visualización de datos por obra o vista general del negocio.</p>
                    </div>
                </a>

                <!-- Opción 4: Gestión Administrativa -->
                <a href="gestion_administrativa.php" class="opcion-card gestion">
                    <div class="opcion-content">
                        <div class="icon">
                            <i class="fas fa-cogs"></i>
                        </div>
                        <h3>Gestión Administrativa</h3>
                        <p>Sistema completo de gestión de módulos, materiales, gastos y registros administrativos.</p>
                    </div>
                </a>
            </div>

            <!-- Información del estado actual del sistema -->
            <div class="mt-4">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Estado del sistema:</strong> Como administrador tienes acceso completo a todas las funcionalidades. 
                    Recuerda configurar las obras del día antes de que el equipo comience las imputaciones.
                </div>
            </div>

            <!-- Accesos rápidos adicionales -->
            <div class="mt-3 text-center">
                <small style="color: #6c757d;">
                    <i class="fas fa-clock"></i> Último acceso: <?= date('d/m/Y H:i', $_SESSION['login_time']) ?>
                </small>
            </div>
        </div>
    </div>

    <!-- JavaScript para UX -->
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

            // Verificar el estado del sistema
            checkSystemStatus();
        });

        // Función para verificar estado del sistema
        async function checkSystemStatus() {
            try {
                const response = await fetch('api/system_status.php');
                const data = await response.json();
                
                if (data.obras_del_dia === 0) {
                    // Mostrar alerta si no hay obras del día configuradas
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-warning mt-3';
                    alert.innerHTML = `
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Atención:</strong> No hay obras configuradas para hoy. 
                        <a href="gestionar_obras.php" style="color: inherit; text-decoration: underline;">
                            Configúralas ahora
                        </a>.
                    `;
                    
                    const container = document.querySelector('.inicio-card');
                    const lastChild = container.lastElementChild;
                    container.insertBefore(alert, lastChild);
                }
            } catch (error) {
                // Error al verificar estado del sistema
            }
        }

        // Prevenir acceso directo sin sesión
        if (!document.querySelector('.navbar-user')) {
            window.location.href = 'login.php';
        }
    </script>
</body>
</html>
