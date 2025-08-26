<?php
/**
 * ELECTROVENTURA v2.0 - GESTIÓN ADMINISTRATIVA
 * ============================================
 * Dashboard administrativo con gestión completa de módulos
 */

require_once 'config/config.php';
requireLogin();

// Solo admin y supervisor pueden acceder
if (!canAccessDashboard()) {
    redirect('imputaciones.php');
}

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title>Gestión Administrativa - Electroventura v2.0</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    
    <style>
        /* Usar exactamente los mismos colores que electroventura_php */
        :root {
            --primary-color: #2a9d8f;
            --secondary-color: #264653;
            --accent-color: #f4a261;
            --warning-color: #e76f51;
            --light-bg: #f8f9fa;
            --card-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        
        body {
            background: linear-gradient(135deg, #e8f5f4 0%, #f0f9f8 50%, #e8f5f4 100%) !important;
            padding-top: 0 !important;
            margin: 0;
        }
        
        .management-header {
            background-color: #2a9d8f;
            color: white;
            padding: 2rem 0;
            text-align: center;
            margin-bottom: 2rem;
            margin-top: 80px; /* Espacio para el navbar */
        }
        
        .module-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            padding: 1rem;
        }
        
        .module-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            border: none;
        }
        
        .module-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        .module-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 1rem;
        }
        
        .module-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--secondary-color);
        }
        
        .module-description {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .module-button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            font-weight: 500;
        }
        
        .module-button:hover {
            background-color: var(--secondary-color);
            color: white;
            text-decoration: none;
        }
        
        /* Colores por categoría - usando la paleta original */
        .category-inventory { background-color: var(--accent-color); }
        .category-projects { background-color: var(--primary-color); }
        .category-finance { background-color: var(--warning-color); }
        .category-staff { background-color: var(--secondary-color); }
        .category-fleet { background-color: #6f42c1; }
        
        @media (max-width: 768px) {
            .module-grid {
                grid-template-columns: 1fr;
                padding: 0.5rem;
            }
            
            .management-header {
                padding: 1rem 0;
            }
        }
    </style>
</head>
<body class="internal-page">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="<?= hasRole('admin') ? 'admin_home.php' : 'supervisor_home.php' ?>">
                <i class="fas fa-bolt"></i>
                Electroventura v2.0
            </a>
            <div class="navbar-user">
                <span class="user-name"><?= htmlspecialchars($user['nombre_completo'] ?? 'Usuario') ?></span>
                <span class="user-role"><?= ucfirst($user['rol'] ?? 'usuario') ?></span>
                <a href="logout.php" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Salir
                </a>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <div class="management-header">
        <div class="container">
            <h1><i class="fas fa-cogs"></i> Gestión Administrativa</h1>
            <p class="mb-0">Sistema completo de gestión de módulos y registros</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container-fluid">
        <div class="module-grid" id="moduleGrid">
            <!-- Los módulos se cargarán dinámicamente -->
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Configuración de módulos administrativos
        const modules = [
            {
                id: 'materialesComprados',
                name: 'Materiales Comprados',
                icon: 'fas fa-shopping-cart',
                category: 'inventory',
                description: 'Gestión completa de materiales comprados por obra',
                table: 'materiales_comprados'
            },
            {
                id: 'obras',
                name: 'Obras',
                icon: 'fas fa-hard-hat',
                category: 'projects', 
                description: 'Administración de obras y proyectos',
                table: 'obras'
            },
            {
                id: 'gastoHilo',
                name: 'Gasto Hilo',
                icon: 'fas fa-bolt',
                category: 'inventory',
                description: 'Control de gastos en material de hilo eléctrico',
                table: 'gasto_hilo'
            },
            {
                id: 'materialesVarios',
                name: 'Materiales Varios',
                icon: 'fas fa-boxes',
                category: 'inventory',
                description: 'Inventario de materiales diversos',
                table: 'materiales_varios'
            },
            {
                id: 'jung',
                name: 'JUNG',
                icon: 'fas fa-plug',
                category: 'inventory',
                description: 'Productos y materiales de la marca JUNG',
                table: 'jung'
            },
            {
                id: 'materialIluminacion',
                name: 'Material Iluminación',
                icon: 'fas fa-lightbulb',
                category: 'inventory',
                description: 'Gestión de material de iluminación',
                table: 'material_iluminacion'
            },
            {
                id: 'efapel',
                name: 'EFAPEL',
                icon: 'fas fa-toggle-on',
                category: 'inventory',
                description: 'Productos y materiales de la marca EFAPEL',
                table: 'efapel'
            },
            {
                id: 'partesRapidos',
                name: 'Partes Rápidos',
                icon: 'fas fa-tools',
                category: 'projects',
                description: 'Gestión de partes de trabajo rápidos',
                table: 'partes_rapidos'
            },
            {
                id: 'gastosIngresos',
                name: 'Gastos e Ingresos',
                icon: 'fas fa-chart-line',
                category: 'finance',
                description: 'Control financiero por obra',
                table: 'gastos_ingresos'
            },
            {
                id: 'horasTrabajadores',
                name: 'Horas Trabajadores',
                icon: 'fas fa-clock',
                category: 'staff',
                description: 'Registro y control de horas trabajadas',
                table: 'horas_trabajadores'
            },
            {
                id: 'puntos',
                name: 'Puntos',
                icon: 'fas fa-star',
                category: 'staff',
                description: 'Sistema de puntos y recompensas',
                table: 'puntos'
            },
            {
                id: 'vehiculos',
                name: 'Vehículos',
                icon: 'fas fa-car',
                category: 'fleet',
                description: 'Gestión de flota de vehículos',
                table: 'vehiculos'
            }
        ];

        // Renderizar módulos
        function renderModules() {
            const grid = document.getElementById('moduleGrid');
            
            modules.forEach(module => {
                const moduleCard = document.createElement('div');
                moduleCard.className = 'module-card';
                moduleCard.innerHTML = `
                    <div class="module-icon category-${module.category}">
                        <i class="${module.icon}"></i>
                    </div>
                    <div class="module-title">${module.name}</div>
                    <div class="module-description">${module.description}</div>
                    <button class="module-button" onclick="openModule('${module.table}', '${module.name}')">
                        <i class="fas fa-external-link-alt"></i> Abrir Módulo
                    </button>
                `;
                grid.appendChild(moduleCard);
            });
        }

        // Abrir módulo en nueva ventana/pestaña
        function openModule(table, name) {
            // Por ahora redirigir a una página genérica o mostrar mensaje
            alert(`Abriendo módulo: ${name}\nTabla: ${table}\n\n(En desarrollo - se integrará con el sistema de gestión completo)`);
            
            // Futura implementación:
            // window.open(`modulo.php?tabla=${table}`, '_blank');
        }

        // Inicializar cuando carga la página
        document.addEventListener('DOMContentLoaded', function() {
            renderModules();
        });
    </script>
</body>
</html>
