<?php
/**
 * ELECTROVENTURA v2.0 - GESTIÓN ADMINISTRATIVA
 * ============================================
 * Dashboard administrativo con CRUD completo para todas las tablas del sistema
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
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Variables exactas del proyecto */
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
        
        /* Layout principal */
        .admin-container {
            display: flex;
            padding-top: 70px;
            min-height: calc(100vh - 70px);
        }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: white;
            box-shadow: var(--card-shadow);
            position: fixed;
            top: 70px;
            left: 0;
            bottom: 0;
            overflow-y: auto;
            z-index: 999;
            transition: transform 0.3s ease;
        }
        
        .sidebar-header {
            background: var(--primary-color);
            color: white;
            padding: 1.5rem 1rem;
            margin: 0;
        }
        
        .sidebar-header h6 {
            color: white;
            margin: 0;
            font-weight: 600;
            font-size: 1rem;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-menu li {
            border-bottom: 1px solid #e9ecef;
        }
        
        .sidebar-menu a {
            display: block;
            padding: 1rem 1.5rem;
            color: #495057;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .sidebar-menu a:hover {
            background-color: var(--light-bg);
            border-left: 4px solid var(--primary-color);
            padding-left: calc(1.5rem - 4px);
            color: var(--primary-color);
        }
        
        .sidebar-menu a.active {
            background-color: var(--primary-color);
            color: white;
            border-left: 4px solid var(--secondary-color);
            padding-left: calc(1.5rem - 4px);
        }
        
        .sidebar-menu i {
            width: 20px;
            margin-right: 0.5rem;
            text-align: center;
        }
        
        /* Main content area */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
            transition: margin-left 0.3s ease;
        }
        
        .content-header {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
        }
        
        .content-header h1 {
            margin: 0 0 0.5rem 0;
            color: var(--secondary-color);
            font-size: 2rem;
        }
        
        .content-header p {
            margin: 0;
            color: #6c757d;
            font-size: 1.1rem;
        }
        
        /* Bienvenida */
        .welcome-screen {
            text-align: center;
            padding: 4rem 2rem;
        }
        
        .welcome-card {
            background: white;
            border-radius: 20px;
            padding: 4rem 2rem;
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
        }
        
        .welcome-card i.fa-cogs {
            font-size: 4rem;
            color: var(--primary-color);
            margin-bottom: 2rem;
        }
        
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            box-shadow: var(--card-shadow);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .stat-card i {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .stat-card.inventory i { color: var(--accent-color); }
        .stat-card.projects i { color: var(--primary-color); }
        .stat-card.finance i { color: var(--warning-color); }
        .stat-card.staff i { color: var(--secondary-color); }
        
        .stat-card h3 {
            color: var(--secondary-color);
            margin-bottom: 0.5rem;
            font-size: 1.3rem;
        }
        
        .stat-card p {
            color: #6c757d;
            margin: 0;
        }
        
        /* Área de tabla */
        .table-area {
            background: white;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            display: none;
        }
        
        .table-area.active {
            display: block;
        }
        
        /* Header de tabla con controles */
        .table-header {
            background: var(--light-bg);
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .table-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--secondary-color);
            margin: 0;
        }
        
        .table-controls {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }
        
        /* Controles de búsqueda y filtros */
        .search-section {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e9ecef;
            background: #fafafa;
        }
        
        .search-filters {
            display: grid;
            grid-template-columns: 2fr 1fr auto;
            gap: 1rem;
            align-items: end;
        }
        
        /* Estilos de botones */
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--secondary-color);
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
            color: white;
        }
        
        .btn-outline-primary {
            background: transparent;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        /* Formularios */
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--secondary-color);
        }
        
        .form-control, .form-select {
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(42, 157, 143, 0.25);
            outline: none;
        }
        
        /* Tabla */
        .table-container {
            padding: 0;
            overflow-x: auto;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th {
            background: var(--light-bg);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--secondary-color);
            border-bottom: 2px solid #e9ecef;
        }
        
        .data-table td {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
            vertical-align: top;
        }
        
        .data-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
        }
        
        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-dialog {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            background: var(--primary-color);
            color: white;
            padding: 1.5rem 2rem;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 600;
        }
        
        .btn-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            opacity: 0.8;
        }
        
        .btn-close:hover {
            opacity: 1;
        }
        
        .modal-body {
            padding: 2rem;
        }
        
        .modal-footer {
            padding: 1rem 2rem 2rem;
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }
        
        /* Paginación */
        .pagination-info {
            padding: 1rem 2rem;
            border-top: 1px solid #e9ecef;
            background: #fafafa;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .pagination-controls {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        
        .pagination-controls button {
            padding: 0.25rem 0.5rem;
            border: 1px solid #dee2e6;
            background: white;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .pagination-controls button:hover {
            background: var(--light-bg);
        }
        
        .pagination-controls button.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        /* Loading state */
        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 3rem;
            color: var(--primary-color);
        }
        
        .spinner {
            width: 2rem;
            height: 2rem;
            border: 0.25rem solid rgba(42, 157, 143, 0.2);
            border-top: 0.25rem solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 1rem;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Estados de la tabla */
        .no-data {
            text-align: center;
            padding: 4rem 2rem;
            color: #6c757d;
        }
        
        .no-data i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }
        
        /* Toggle sidebar button */
        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 80px;
            left: 10px;
            z-index: 1001;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            box-shadow: var(--card-shadow);
        }
        
        /* Media queries */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .sidebar-toggle {
                display: block;
            }
            
            .search-filters {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .table-controls {
                width: 100%;
                justify-content: flex-start;
            }
        }
        
        @media (max-width: 768px) {
            .admin-container {
                padding-top: 70px;
            }
            
            .main-content {
                padding: 1rem;
            }
            
            .content-header {
                padding: 1.5rem;
            }
            
            .content-header h1 {
                font-size: 1.5rem;
            }
            
            .stats-overview {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .stat-card {
                padding: 1.5rem;
            }
            
            .modal-dialog {
                width: 95%;
                margin: 1rem;
            }
            
            .modal-body {
                padding: 1rem;
            }
            
            .data-table {
                font-size: 0.85rem;
            }
            
            .data-table th,
            .data-table td {
                padding: 0.5rem;
            }
        }
    </style>
</head>
<body class="internal-page">
    <!-- Navbar -->
<body class="internal-page">
    <!-- Navbar -->
    <nav class="navbar">
        <div class="d-flex" style="width: 100%; justify-content: space-between; align-items: center;">
            <a href="<?= hasRole('admin') ? 'admin_home.php' : 'supervisor_home.php' ?>" class="navbar-brand">
                <i class="fas fa-arrow-left" style="margin-right: 0.5rem;"></i>
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

    <!-- Toggle sidebar button (móvil) -->
    <button class="sidebar-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Container principal -->
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h6><i class="fas fa-cogs"></i> Gestión Administrativa</h6>
            </div>
            <ul class="sidebar-menu">
                <!-- SISTEMA -->
                <li><a href="#" onclick="loadTable('usuarios', 'Usuarios', 'fas fa-users')"><i class="fas fa-users"></i> Usuarios</a></li>
                <li><a href="#" onclick="loadTable('proveedores', 'Proveedores', 'fas fa-building')"><i class="fas fa-building"></i> Proveedores</a></li>
                <li><a href="#" onclick="loadTable('contactos', 'Contactos', 'fas fa-address-book')"><i class="fas fa-address-book"></i> Contactos</a></li>
                
                <!-- OBRAS Y PROYECTOS -->
                <li><a href="#" onclick="loadTable('obras', 'Obras', 'fas fa-hard-hat')"><i class="fas fa-hard-hat"></i> Obras</a></li>
                <li><a href="#" onclick="loadTable('obras_dia', 'Obras del Día', 'fas fa-calendar-day')"><i class="fas fa-calendar-day"></i> Obras del Día</a></li>
                <li><a href="#" onclick="loadTable('partes_rapidos', 'Partes Rápidos', 'fas fa-tools')"><i class="fas fa-tools"></i> Partes Rápidos</a></li>
                
                <!-- FINANZAS -->
                <li><a href="#" onclick="loadTable('gastos_ingresos', 'Gastos e Ingresos', 'fas fa-chart-line')"><i class="fas fa-chart-line"></i> Gastos e Ingresos</a></li>
                
                <!-- INVENTARIOS -->
                <li><a href="#" onclick="loadTable('materiales_comprados', 'Materiales Comprados', 'fas fa-shopping-cart')"><i class="fas fa-shopping-cart"></i> Materiales Comprados</a></li>
                <li><a href="#" onclick="loadTable('gasto_hilo', 'Gasto Hilo', 'fas fa-bolt')"><i class="fas fa-bolt"></i> Gasto Hilo</a></li>
                <li><a href="#" onclick="loadTable('materiales_varios', 'Materiales Varios', 'fas fa-boxes')"><i class="fas fa-boxes"></i> Materiales Varios</a></li>
                <li><a href="#" onclick="loadTable('jung', 'JUNG', 'fas fa-plug')"><i class="fas fa-plug"></i> JUNG</a></li>
                <li><a href="#" onclick="loadTable('material_iluminacion', 'Material Iluminación', 'fas fa-lightbulb')"><i class="fas fa-lightbulb"></i> Material Iluminación</a></li>
                <li><a href="#" onclick="loadTable('efapel', 'EFAPEL', 'fas fa-toggle-on')"><i class="fas fa-toggle-on"></i> EFAPEL</a></li>
                
                <!-- RECURSOS HUMANOS -->
                <li><a href="#" onclick="loadTable('horas_trabajadores', 'Horas Trabajadores', 'fas fa-clock')"><i class="fas fa-clock"></i> Horas Trabajadores</a></li>
                <li><a href="#" onclick="loadTable('puntos', 'Puntos', 'fas fa-star')"><i class="fas fa-star"></i> Puntos</a></li>
                
                <!-- FLOTA -->
                <li><a href="#" onclick="loadTable('vehiculos', 'Vehículos', 'fas fa-car')"><i class="fas fa-car"></i> Vehículos</a></li>
                
                <!-- AUDITORÍA -->
                <li><a href="#" onclick="loadTable('auditoria', 'Auditoría', 'fas fa-history')"><i class="fas fa-history"></i> Auditoría</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header de contenido -->
            <div class="content-header">
                <h1 id="content-title"><i class="fas fa-cogs"></i> Gestión Administrativa</h1>
                <p id="content-description">Sistema completo de gestión de tablas y registros</p>
            </div>

            <!-- Pantalla de bienvenida -->
            <div id="welcome-screen" class="welcome-screen">
                <div class="welcome-card">
                    <i class="fas fa-cogs"></i>
                    <h1>Bienvenido a Gestión Administrativa</h1>
                    <p class="lead">Sistema integral de gestión para todas las tablas del sistema</p>
                    <p>Selecciona una sección del menú lateral para comenzar a trabajar con los datos.</p>
                </div>
                
                <div class="stats-overview">
                    <div class="stat-card inventory">
                        <i class="fas fa-boxes"></i>
                        <h3>Inventarios</h3>
                        <p>Gestión completa de materiales eléctricos y componentes</p>
                    </div>
                    <div class="stat-card projects">
                        <i class="fas fa-hard-hat"></i>
                        <h3>Obras</h3>
                        <p>Control de proyectos y obras en curso</p>
                    </div>
                    <div class="stat-card finance">
                        <i class="fas fa-chart-line"></i>
                        <h3>Finanzas</h3>
                        <p>Gastos e ingresos detallados por obra</p>
                    </div>
                    <div class="stat-card staff">
                        <i class="fas fa-users"></i>
                        <h3>Personal</h3>
                        <p>Control de horas y sistema de puntos</p>
                    </div>
                </div>
            </div>

            <!-- Área de tabla (inicialmente oculta) -->
            <div id="table-area" class="table-area">
                <!-- Header de tabla con controles -->
                <div class="table-header">
                    <h2 class="table-title" id="table-title">
                        <i class="fas fa-table" id="table-icon"></i>
                        <span id="table-name">Tabla</span>
                    </h2>
                    <div class="table-controls">
                        <button class="btn btn-success btn-sm" onclick="openCreateModal()">
                            <i class="fas fa-plus"></i> Nuevo
                        </button>
                        <button class="btn btn-outline-primary btn-sm" onclick="exportToExcel()">
                            <i class="fas fa-download"></i> Exportar Excel
                        </button>
                        <button class="btn btn-outline-primary btn-sm" onclick="refreshTable()">
                            <i class="fas fa-sync-alt"></i> Actualizar
                        </button>
                    </div>
                </div>

                <!-- Búsqueda y filtros -->
                <div class="search-section">
                    <div class="search-filters">
                        <div class="form-group">
                            <label class="form-label">Buscar</label>
                            <input type="text" class="form-control" id="search-input" placeholder="Buscar en todos los campos...">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Registros por página</label>
                            <select class="form-select" id="page-size">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50" selected>50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                        <div>
                            <button class="btn btn-primary" onclick="applyFilters()">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tabla de datos -->
                <div class="table-container">
                    <div id="table-loading" class="loading" style="display: none;">
                        <div class="spinner"></div>
                        <span>Cargando datos...</span>
                    </div>
                    <div id="table-content">
                        <!-- Aquí se cargará dinámicamente la tabla -->
                    </div>
                </div>

                <!-- Información de paginación -->
                <div class="pagination-info">
                    <div id="pagination-info-text">Mostrando 0 - 0 de 0 registros</div>
                    <div class="pagination-controls" id="pagination-controls">
                        <!-- Controles de paginación se generan dinámicamente -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para CREATE/EDIT -->
    <div class="modal" id="crud-modal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-title">Nuevo Registro</h5>
                <button type="button" class="btn-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="crud-form">
                    <div id="form-fields">
                        <!-- Campos se generan dinámicamente -->
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" onclick="closeModal()">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveRecord()">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // Variables con datos del servidor (se cargarán via AJAX)
        let serverData = {
            obras: [],
            proveedores: [],
            usuarios: []
        };

        // Variables globales
        let currentTable = '';
        let currentPage = 1;
        let currentPageSize = 50;
        let currentSearch = '';
        let currentData = [];
        let isEditing = false;
        let editingId = null;

        // Configuración de tablas con sus campos
        const tableConfigs = {
            'usuarios': {
                title: 'Usuarios',
                icon: 'fas fa-users',
                fields: [
                    {name: 'id', label: 'ID', type: 'hidden', readonly: true},
                    {name: 'username', label: 'Usuario', type: 'text', required: true},
                    {name: 'password', label: 'Contraseña', type: 'password', required: false, showInTable: false},
                    {name: 'nombre_completo', label: 'Nombre Completo', type: 'text', required: true},
                    {name: 'email', label: 'Email', type: 'email'},
                    {name: 'telefono', label: 'Teléfono', type: 'text'},
                    {name: 'rol', label: 'Rol', type: 'select', options: [
                        {value: 'admin', text: 'Administrador'},
                        {value: 'supervisor', text: 'Supervisor'},
                        {value: 'trabajador', text: 'Trabajador'}
                    ], required: true},
                    {name: 'activo', label: 'Activo', type: 'checkbox'}
                ]
            },
            'proveedores': {
                title: 'Proveedores',
                icon: 'fas fa-building',
                fields: [
                    {name: 'id', label: 'ID', type: 'hidden', readonly: true},
                    {name: 'nombre', label: 'Nombre', type: 'text', required: true},
                    {name: 'nif_cif', label: 'NIF/CIF', type: 'text'},
                    {name: 'direccion', label: 'Dirección', type: 'textarea'},
                    {name: 'telefono', label: 'Teléfono', type: 'text'},
                    {name: 'email', label: 'Email', type: 'email'},
                    {name: 'observaciones', label: 'Observaciones', type: 'textarea'},
                    {name: 'activo', label: 'Activo', type: 'checkbox'}
                ]
            },
            'contactos': {
                title: 'Contactos',
                icon: 'fas fa-address-book',
                fields: [
                    {name: 'id', label: 'ID', type: 'hidden', readonly: true},
                    {name: 'proveedor_id', label: 'Proveedor', type: 'select', options: 'proveedores', required: true},
                    {name: 'nombre', label: 'Nombre', type: 'text', required: true},
                    {name: 'telefono', label: 'Teléfono', type: 'text'},
                    {name: 'email', label: 'Email', type: 'email'},
                    {name: 'cargo', label: 'Cargo', type: 'text'},
                    {name: 'principal', label: 'Principal', type: 'checkbox'},
                    {name: 'observaciones', label: 'Observaciones', type: 'textarea'},
                    {name: 'activo', label: 'Activo', type: 'checkbox'}
                ]
            },
            'obras': {
                title: 'Obras',
                icon: 'fas fa-hard-hat',
                fields: [
                    {name: 'id', label: 'ID', type: 'hidden', readonly: true},
                    {name: 'codigo', label: 'Código', type: 'text', required: true},
                    {name: 'nombre', label: 'Nombre', type: 'text', required: true},
                    {name: 'descripcion', label: 'Descripción', type: 'textarea'},
                    {name: 'proveedor_id', label: 'Proveedor', type: 'select', options: 'proveedores'},
                    {name: 'direccion_obra', label: 'Dirección Obra', type: 'textarea'},
                    {name: 'fecha_inicio', label: 'Fecha Inicio', type: 'date'},
                    {name: 'fecha_fin', label: 'Fecha Fin', type: 'date'},
                    {name: 'fecha_fin_prevista', label: 'Fecha Fin Prevista', type: 'date'},
                    {name: 'presupuesto', label: 'Presupuesto', type: 'number', step: '0.01'},
                    {name: 'estado', label: 'Estado', type: 'select', options: [
                        {value: 'pendiente', text: 'Pendiente'},
                        {value: 'en_curso', text: 'En Curso'},
                        {value: 'finalizada', text: 'Finalizada'},
                        {value: 'cancelada', text: 'Cancelada'}
                    ]},
                    {name: 'observaciones', label: 'Observaciones', type: 'textarea'},
                    {name: 'activo', label: 'Activo', type: 'checkbox'}
                ]
            },
            'obras_dia': {
                title: 'Obras del Día',
                icon: 'fas fa-calendar-day',
                fields: [
                    {name: 'id', label: 'ID', type: 'hidden', readonly: true},
                    {name: 'obra_id', label: 'Obra', type: 'select', options: 'obras', required: true},
                    {name: 'fecha', label: 'Fecha', type: 'date', required: true},
                    {name: 'activa', label: 'Activa', type: 'checkbox'},
                    {name: 'observaciones', label: 'Observaciones', type: 'textarea'}
                ]
            },
            'horas_trabajadores': {
                title: 'Horas Trabajadores',
                icon: 'fas fa-clock',
                fields: [
                    {name: 'id', label: 'ID', type: 'hidden', readonly: true},
                    {name: 'obra_id', label: 'Obra', type: 'select', options: 'obras', required: true},
                    {name: 'usuario_id', label: 'Trabajador', type: 'select', options: 'usuarios', required: true},
                    {name: 'fecha', label: 'Fecha', type: 'date', required: true},
                    {name: 'hora_entrada', label: 'Hora Entrada', type: 'time'},
                    {name: 'hora_salida', label: 'Hora Salida', type: 'time'},
                    {name: 'horas', label: 'Horas', type: 'number', step: '0.01', required: true},
                    {name: 'descripcion', label: 'Descripción', type: 'textarea'},
                    {name: 'lugar_trabajo', label: 'Lugar Trabajo', type: 'text'},
                    {name: 'origen', label: 'Origen', type: 'select', options: [
                        {value: 'web', text: 'Web'},
                        {value: 'mobile', text: 'Móvil'}
                    ]},
                    {name: 'observaciones', label: 'Observaciones', type: 'textarea'}
                ]
            },
            'gastos_ingresos': {
                title: 'Gastos e Ingresos',
                icon: 'fas fa-chart-line',
                fields: [
                    {name: 'id', label: 'ID', type: 'hidden', readonly: true},
                    {name: 'obra_id', label: 'Obra', type: 'select', options: 'obras', required: true},
                    {name: 'tipo', label: 'Tipo', type: 'select', options: [
                        {value: 'gasto', text: 'Gasto'},
                        {value: 'ingreso', text: 'Ingreso'}
                    ], required: true},
                    {name: 'categoria', label: 'Categoría', type: 'text'},
                    {name: 'concepto', label: 'Concepto', type: 'text', required: true},
                    {name: 'descripcion', label: 'Descripción', type: 'textarea'},
                    {name: 'cantidad', label: 'Cantidad', type: 'number', step: '0.01', required: true},
                    {name: 'fecha', label: 'Fecha', type: 'date', required: true},
                    {name: 'proveedor_id', label: 'Proveedor', type: 'select', options: 'proveedores'},
                    {name: 'documento', label: 'Documento', type: 'text'},
                    {name: 'observaciones', label: 'Observaciones', type: 'textarea'}
                ]
            },
            'materiales_comprados': {
                title: 'Materiales Comprados',
                icon: 'fas fa-shopping-cart',
                fields: [
                    {name: 'id', label: 'ID', type: 'hidden', readonly: true},
                    {name: 'obra_id', label: 'Obra', type: 'select', options: 'obras'},
                    {name: 'material', label: 'Material', type: 'text', required: true},
                    {name: 'descripcion', label: 'Descripción', type: 'textarea'},
                    {name: 'cantidad', label: 'Cantidad', type: 'number', step: '0.001', required: true},
                    {name: 'unidad', label: 'Unidad', type: 'text'},
                    {name: 'precio_unitario', label: 'Precio Unitario', type: 'number', step: '0.01'},
                    {name: 'precio_total', label: 'Precio Total', type: 'number', step: '0.01'},
                    {name: 'proveedor_id', label: 'Proveedor', type: 'select', options: 'proveedores'},
                    {name: 'fecha_compra', label: 'Fecha Compra', type: 'date'},
                    {name: 'fecha_entrega', label: 'Fecha Entrega', type: 'date'},
                    {name: 'albaran', label: 'Albarán', type: 'text'},
                    {name: 'factura', label: 'Factura', type: 'text'},
                    {name: 'observaciones', label: 'Observaciones', type: 'textarea'},
                    {name: 'stock_general', label: 'Stock General', type: 'checkbox'}
                ]
            },
            'gasto_hilo': {
                title: 'Gasto Hilo',
                icon: 'fas fa-bolt',
                fields: [
                    {name: 'id', label: 'ID', type: 'hidden', readonly: true},
                    {name: 'obra_id', label: 'Obra', type: 'select', options: 'obras'},
                    {name: 'tipo_hilo', label: 'Tipo Hilo', type: 'text', required: true},
                    {name: 'descripcion', label: 'Descripción', type: 'textarea'},
                    {name: 'metros', label: 'Metros', type: 'number', step: '0.01', required: true},
                    {name: 'precio_metro', label: 'Precio por Metro', type: 'number', step: '0.001'},
                    {name: 'precio_total', label: 'Precio Total', type: 'number', step: '0.01'},
                    {name: 'proveedor_id', label: 'Proveedor', type: 'select', options: 'proveedores'},
                    {name: 'fecha_uso', label: 'Fecha Uso', type: 'date'},
                    {name: 'observaciones', label: 'Observaciones', type: 'textarea'},
                    {name: 'stock_general', label: 'Stock General', type: 'checkbox'}
                ]
            },
            'materiales_varios': {
                title: 'Materiales Varios',
                icon: 'fas fa-boxes',
                fields: [
                    {name: 'id', label: 'ID', type: 'hidden', readonly: true},
                    {name: 'obra_id', label: 'Obra', type: 'select', options: 'obras'},
                    {name: 'material', label: 'Material', type: 'text', required: true},
                    {name: 'descripcion', label: 'Descripción', type: 'textarea'},
                    {name: 'cantidad', label: 'Cantidad', type: 'number', step: '0.001', required: true},
                    {name: 'unidad', label: 'Unidad', type: 'text'},
                    {name: 'precio_unitario', label: 'Precio Unitario', type: 'number', step: '0.01'},
                    {name: 'precio_total', label: 'Precio Total', type: 'number', step: '0.01'},
                    {name: 'proveedor_id', label: 'Proveedor', type: 'select', options: 'proveedores'},
                    {name: 'fecha', label: 'Fecha', type: 'date'},
                    {name: 'observaciones', label: 'Observaciones', type: 'textarea'},
                    {name: 'stock_general', label: 'Stock General', type: 'checkbox'}
                ]
            },
            'jung': {
                title: 'JUNG',
                icon: 'fas fa-plug',
                fields: [
                    {name: 'id', label: 'ID', type: 'hidden', readonly: true},
                    {name: 'obra_id', label: 'Obra', type: 'select', options: 'obras'},
                    {name: 'producto', label: 'Producto', type: 'text', required: true},
                    {name: 'referencia', label: 'Referencia', type: 'text'},
                    {name: 'descripcion', label: 'Descripción', type: 'textarea'},
                    {name: 'cantidad', label: 'Cantidad', type: 'number', step: '0.001', required: true},
                    {name: 'precio_unitario', label: 'Precio Unitario', type: 'number', step: '0.01'},
                    {name: 'precio_total', label: 'Precio Total', type: 'number', step: '0.01'},
                    {name: 'proveedor_id', label: 'Proveedor', type: 'select', options: 'proveedores'},
                    {name: 'fecha', label: 'Fecha', type: 'date'},
                    {name: 'observaciones', label: 'Observaciones', type: 'textarea'},
                    {name: 'stock_general', label: 'Stock General', type: 'checkbox'}
                ]
            },
            'material_iluminacion': {
                title: 'Material Iluminación',
                icon: 'fas fa-lightbulb',
                fields: [
                    {name: 'id', label: 'ID', type: 'hidden', readonly: true},
                    {name: 'obra_id', label: 'Obra', type: 'select', options: 'obras'},
                    {name: 'tipo_luminaria', label: 'Tipo Luminaria', type: 'text', required: true},
                    {name: 'marca', label: 'Marca', type: 'text'},
                    {name: 'modelo', label: 'Modelo', type: 'text'},
                    {name: 'potencia', label: 'Potencia', type: 'text'},
                    {name: 'cantidad', label: 'Cantidad', type: 'number', step: '0.001', required: true},
                    {name: 'precio_unitario', label: 'Precio Unitario', type: 'number', step: '0.01'},
                    {name: 'precio_total', label: 'Precio Total', type: 'number', step: '0.01'},
                    {name: 'proveedor_id', label: 'Proveedor', type: 'select', options: 'proveedores'},
                    {name: 'fecha', label: 'Fecha', type: 'date'},
                    {name: 'observaciones', label: 'Observaciones', type: 'textarea'},
                    {name: 'stock_general', label: 'Stock General', type: 'checkbox'}
                ]
            },
            'efapel': {
                title: 'EFAPEL',
                icon: 'fas fa-toggle-on',
                fields: [
                    {name: 'id', label: 'ID', type: 'hidden', readonly: true},
                    {name: 'obra_id', label: 'Obra', type: 'select', options: 'obras'},
                    {name: 'producto', label: 'Producto', type: 'text', required: true},
                    {name: 'referencia', label: 'Referencia', type: 'text'},
                    {name: 'serie', label: 'Serie', type: 'text'},
                    {name: 'descripcion', label: 'Descripción', type: 'textarea'},
                    {name: 'cantidad', label: 'Cantidad', type: 'number', step: '0.001', required: true},
                    {name: 'precio_unitario', label: 'Precio Unitario', type: 'number', step: '0.01'},
                    {name: 'precio_total', label: 'Precio Total', type: 'number', step: '0.01'},
                    {name: 'proveedor_id', label: 'Proveedor', type: 'select', options: 'proveedores'},
                    {name: 'fecha', label: 'Fecha', type: 'date'},
                    {name: 'observaciones', label: 'Observaciones', type: 'textarea'},
                    {name: 'stock_general', label: 'Stock General', type: 'checkbox'}
                ]
            },
            'partes_rapidos': {
                title: 'Partes Rápidos',
                icon: 'fas fa-tools',
                fields: [
                    {name: 'id', label: 'ID', type: 'hidden', readonly: true},
                    {name: 'obra_id', label: 'Obra', type: 'select', options: 'obras'},
                    {name: 'titulo', label: 'Título', type: 'text', required: true},
                    {name: 'descripcion', label: 'Descripción', type: 'textarea'},
                    {name: 'urgencia', label: 'Urgencia', type: 'select', options: [
                        {value: 'baja', text: 'Baja'},
                        {value: 'media', text: 'Media'},
                        {value: 'alta', text: 'Alta'},
                        {value: 'critica', text: 'Crítica'}
                    ]},
                    {name: 'estado', label: 'Estado', type: 'select', options: [
                        {value: 'pendiente', text: 'Pendiente'},
                        {value: 'en_curso', text: 'En Curso'},
                        {value: 'finalizado', text: 'Finalizado'}
                    ]},
                    {name: 'asignado_a', label: 'Asignado A', type: 'select', options: 'usuarios'},
                    {name: 'fecha_limite', label: 'Fecha Límite', type: 'date'},
                    {name: 'observaciones', label: 'Observaciones', type: 'textarea'}
                ]
            },
            'puntos': {
                title: 'Puntos',
                icon: 'fas fa-star',
                fields: [
                    {name: 'id', label: 'ID', type: 'hidden', readonly: true},
                    {name: 'obra_id', label: 'Obra', type: 'select', options: 'obras'},
                    {name: 'usuario_id', label: 'Usuario', type: 'select', options: 'usuarios', required: true},
                    {name: 'concepto', label: 'Concepto', type: 'text', required: true},
                    {name: 'puntos', label: 'Puntos', type: 'number', required: true},
                    {name: 'fecha', label: 'Fecha', type: 'date', required: true},
                    {name: 'observaciones', label: 'Observaciones', type: 'textarea'}
                ]
            },
            'vehiculos': {
                title: 'Vehículos',
                icon: 'fas fa-car',
                fields: [
                    {name: 'id', label: 'ID', type: 'hidden', readonly: true},
                    {name: 'marca', label: 'Marca', type: 'text'},
                    {name: 'modelo', label: 'Modelo', type: 'text'},
                    {name: 'matricula', label: 'Matrícula', type: 'text', required: true},
                    {name: 'año', label: 'Año', type: 'number'},
                    {name: 'combustible', label: 'Combustible', type: 'select', options: [
                        {value: 'gasolina', text: 'Gasolina'},
                        {value: 'diesel', text: 'Diésel'},
                        {value: 'electrico', text: 'Eléctrico'},
                        {value: 'hibrido', text: 'Híbrido'}
                    ]},
                    {name: 'kilometraje', label: 'Kilometraje', type: 'number', step: '0.01'},
                    {name: 'fecha_compra', label: 'Fecha Compra', type: 'date'},
                    {name: 'precio_compra', label: 'Precio Compra', type: 'number', step: '0.01'},
                    {name: 'observaciones', label: 'Observaciones', type: 'textarea'},
                    {name: 'activo', label: 'Activo', type: 'checkbox'}
                ]
            },
            'auditoria': {
                title: 'Auditoría',
                icon: 'fas fa-history',
                fields: [
                    {name: 'id', label: 'ID', type: 'hidden', readonly: true},
                    {name: 'tabla', label: 'Tabla', type: 'text', readonly: true},
                    {name: 'registro_id', label: 'ID Registro', type: 'number', readonly: true},
                    {name: 'accion', label: 'Acción', type: 'text', readonly: true},
                    {name: 'datos_anteriores', label: 'Datos Anteriores', type: 'textarea', readonly: true},
                    {name: 'datos_nuevos', label: 'Datos Nuevos', type: 'textarea', readonly: true},
                    {name: 'usuario_id', label: 'Usuario', type: 'select', options: 'usuarios', readonly: true},
                    {name: 'ip_address', label: 'IP', type: 'text', readonly: true},
                    {name: 'fecha_accion', label: 'Fecha Acción', type: 'datetime-local', readonly: true}
                ],
                readonly: true // Tabla de solo lectura
            }
        };

        // Funciones principales
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Dashboard cargado correctamente');
            loadReferenceData();
        });

        async function loadReferenceData() {
            try {
                const response = await fetch('api/reference_data.php');
                const data = await response.json();
                
                if (data.error) {
                    throw new Error(data.message);
                }
                
                serverData = data;
                console.log('Datos de referencia cargados:', serverData);
            } catch (error) {
                console.error('Error cargando datos de referencia:', error);
                showError('Error cargando datos de referencia: ' + error.message);
            }
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('show');
        }

        function loadTable(tableName, displayName, icon) {
            currentTable = tableName;
            currentPage = 1;
            currentSearch = '';
            
            // Actualizar menú activo
            document.querySelectorAll('.sidebar-menu a').forEach(a => a.classList.remove('active'));
            event.target.classList.add('active');
            
            // Actualizar header
            document.getElementById('content-title').innerHTML = `<i class="${icon}"></i> ${displayName}`;
            document.getElementById('content-description').textContent = `Gestión de ${displayName.toLowerCase()}`;
            
            // Actualizar título de tabla
            document.getElementById('table-title').innerHTML = `<i class="${icon}" id="table-icon"></i> <span id="table-name">${displayName}</span>`;
            
            // Ocultar welcome, mostrar tabla
            document.getElementById('welcome-screen').style.display = 'none';
            document.getElementById('table-area').style.display = 'block';
            
            // Ocultar botón crear si es tabla de auditoría
            const createBtn = document.querySelector('[onclick="openCreateModal()"]');
            if (tableConfigs[tableName] && tableConfigs[tableName].readonly) {
                createBtn.style.display = 'none';
            } else {
                createBtn.style.display = 'inline-flex';
            }
            
            // Cargar datos
            fetchTableData();
            
            // Cerrar sidebar en móvil
            if (window.innerWidth <= 1024) {
                document.getElementById('sidebar').classList.remove('show');
            }
        }

        async function fetchTableData() {
            showLoading(true);
            
            try {
                const params = new URLSearchParams({
                    action: 'read',
                    table: currentTable,
                    page: currentPage,
                    pageSize: currentPageSize,
                    search: currentSearch
                });
                
                const response = await fetch('api/crud.php?' + params);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                console.log('API Response:', result);
                
                if (result.success) {
                    currentData = result.data;
                    renderTable(result.data, result.total);
                } else {
                    showError(result.message || 'Error al cargar los datos');
                }
            } catch (error) {
                console.error('Error en fetchTableData:', error);
                showError('Error de conexión: ' + error.message);
            } finally {
                showLoading(false);
            }
        }

        function renderTable(data, total) {
            const tableContent = document.getElementById('table-content');
            const config = tableConfigs[currentTable];
            
            if (!data || data.length === 0) {
                tableContent.innerHTML = `
                    <div class="no-data">
                        <i class="${config.icon}"></i>
                        <h3>No hay datos</h3>
                        <p>No se encontraron registros en ${config.title.toLowerCase()}</p>
                    </div>
                `;
                updatePaginationInfo(0, total);
                return;
            }
            
            // Generar tabla
            const visibleFields = config.fields.filter(field => 
                field.type !== 'hidden' && field.showInTable !== false
            );
            
            let tableHTML = `
                <table class="data-table">
                    <thead>
                        <tr>
                            ${visibleFields.map(field => `<th>${field.label}</th>`).join('')}
                            <th style="width: 120px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            data.forEach(row => {
                tableHTML += '<tr>';
                visibleFields.forEach(field => {
                    let value = row[field.name] || '';
                    
                    // Formatear valores según el tipo
                    if (field.type === 'checkbox') {
                        value = value == 1 ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>';
                    } else if (field.type === 'date') {
                        value = value ? new Date(value).toLocaleDateString('es-ES') : '';
                    } else if (field.type === 'datetime-local') {
                        value = value ? new Date(value).toLocaleString('es-ES') : '';
                    } else if (field.type === 'number' && (field.step === '0.01' || field.step === '0.001')) {
                        value = value ? parseFloat(value).toFixed(2) + '€' : '';
                    } else if (field.type === 'select' && field.options) {
                        let selectOptions = field.options;
                        
                        // Si es una referencia string, buscar en serverData
                        if (typeof selectOptions === 'string') {
                            selectOptions = serverData[selectOptions] || [];
                        }
                        
                        const option = selectOptions.find(opt => opt.value == value || opt.id == value);
                        value = option ? (option.text || option.nombre || option.nombre_completo) : value;
                    }
                    
                    // Truncar texto largo
                    if (typeof value === 'string' && value.length > 50) {
                        value = value.substring(0, 50) + '...';
                    }
                    
                    tableHTML += `<td>${value}</td>`;
                });
                
                // Botones de acción
                const isReadonly = config.readonly;
                tableHTML += `
                    <td>
                        <div class="btn-group">
                            ${!isReadonly ? `<button class="btn btn-sm btn-warning" onclick="editRecord(${row.id})" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>` : ''}
                            ${!isReadonly ? `<button class="btn btn-sm btn-danger" onclick="deleteRecord(${row.id})" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>` : ''}
                        </div>
                    </td>
                `;
                tableHTML += '</tr>';
            });
            
            tableHTML += '</tbody></table>';
            tableContent.innerHTML = tableHTML;
            
            updatePaginationInfo(data.length, total);
        }

        function updatePaginationInfo(showing, total) {
            const start = (currentPage - 1) * currentPageSize + 1;
            const end = start + showing - 1;
            
            document.getElementById('pagination-info-text').textContent = 
                `Mostrando ${start} - ${end} de ${total} registros`;
            
            // Generar controles de paginación
            const totalPages = Math.ceil(total / currentPageSize);
            const paginationControls = document.getElementById('pagination-controls');
            
            let controlsHTML = '';
            
            // Botón anterior
            controlsHTML += `
                <button ${currentPage <= 1 ? 'disabled' : ''} onclick="changePage(${currentPage - 1})">
                    <i class="fas fa-chevron-left"></i>
                </button>
            `;
            
            // Páginas
            for (let i = Math.max(1, currentPage - 2); i <= Math.min(totalPages, currentPage + 2); i++) {
                controlsHTML += `
                    <button class="${i === currentPage ? 'active' : ''}" onclick="changePage(${i})">
                        ${i}
                    </button>
                `;
            }
            
            // Botón siguiente
            controlsHTML += `
                <button ${currentPage >= totalPages ? 'disabled' : ''} onclick="changePage(${currentPage + 1})">
                    <i class="fas fa-chevron-right"></i>
                </button>
            `;
            
            paginationControls.innerHTML = controlsHTML;
        }

        function changePage(page) {
            if (page < 1) return;
            currentPage = page;
            fetchTableData();
        }

        function applyFilters() {
            currentSearch = document.getElementById('search-input').value;
            currentPageSize = parseInt(document.getElementById('page-size').value);
            currentPage = 1;
            fetchTableData();
        }

        function refreshTable() {
            fetchTableData();
        }

        function openCreateModal() {
            isEditing = false;
            editingId = null;
            document.getElementById('modal-title').textContent = `Nuevo ${tableConfigs[currentTable].title.slice(0, -1)}`;
            generateForm();
            showModal();
        }

        function editRecord(id) {
            isEditing = true;
            editingId = id;
            
            const record = currentData.find(r => r.id == id);
            if (!record) {
                showError('Registro no encontrado');
                return;
            }
            
            document.getElementById('modal-title').textContent = `Editar ${tableConfigs[currentTable].title.slice(0, -1)}`;
            generateForm(record);
            showModal();
        }

        function generateForm(data = null) {
            const config = tableConfigs[currentTable];
            const formFields = document.getElementById('form-fields');
            
            let formHTML = '';
            
            config.fields.forEach(field => {
                if (field.type === 'hidden') {
                    formHTML += `<input type="hidden" name="${field.name}" value="${data ? (data[field.name] || '') : ''}">`;
                    return;
                }
                
                const value = data ? (data[field.name] || '') : '';
                const readonly = field.readonly ? 'readonly' : '';
                const required = field.required ? 'required' : '';
                
                formHTML += `
                    <div class="form-group">
                        <label class="form-label">${field.label} ${field.required ? '<span style="color: red;">*</span>' : ''}</label>
                `;
                
                switch (field.type) {
                    case 'textarea':
                        formHTML += `<textarea class="form-control" name="${field.name}" ${readonly} ${required}>${value}</textarea>`;
                        break;
                    case 'select':
                        let selectOptions = field.options;
                        
                        // Si es una referencia string, buscar en serverData
                        if (typeof selectOptions === 'string') {
                            selectOptions = serverData[selectOptions] || [];
                        }
                        
                        formHTML += `<select class="form-select" name="${field.name}" ${readonly} ${required}>
                            <option value="">Seleccionar...</option>
                            ${selectOptions.map(opt => {
                                const optValue = opt.value || opt.id;
                                const optText = opt.text || opt.nombre || opt.nombre_completo || opt.codigo;
                                return `<option value="${optValue}" ${optValue == value ? 'selected' : ''}>${optText}</option>`;
                            }).join('')}
                        </select>`;
                        break;
                    case 'checkbox':
                        formHTML += `
                            <div>
                                <input type="checkbox" name="${field.name}" value="1" ${value == 1 ? 'checked' : ''} ${readonly}>
                                <label style="margin-left: 0.5rem;">Sí</label>
                            </div>
                        `;
                        break;
                    default:
                        formHTML += `<input type="${field.type}" class="form-control" name="${field.name}" 
                            value="${value}" ${readonly} ${required} 
                            ${field.step ? `step="${field.step}"` : ''}
                            ${field.type === 'password' && !isEditing ? '' : ''}>`;
                }
                
                formHTML += '</div>';
            });
            
            formFields.innerHTML = formHTML;
        }

        async function saveRecord() {
            const form = document.getElementById('crud-form');
            const formData = new FormData(form);
            
            // Validar campos requeridos
            const config = tableConfigs[currentTable];
            for (let field of config.fields) {
                if (field.required && !formData.get(field.name) && field.type !== 'checkbox') {
                    showError(`El campo ${field.label} es requerido`);
                    return;
                }
            }
            
            try {
                formData.append('action', isEditing ? 'update' : 'create');
                formData.append('table', currentTable);
                
                if (isEditing) {
                    formData.append('id', editingId);
                }
                
                const response = await fetch('api/crud.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    closeModal();
                    fetchTableData();
                    showSuccess(isEditing ? 'Registro actualizado correctamente' : 'Registro creado correctamente');
                } else {
                    showError(result.message || 'Error al guardar el registro');
                }
            } catch (error) {
                showError('Error de conexión: ' + error.message);
            }
        }

        async function deleteRecord(id) {
            if (!confirm('¿Estás seguro de que quieres eliminar este registro?')) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('table', currentTable);
                formData.append('id', id);
                
                const response = await fetch('api/crud.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    fetchTableData();
                    showSuccess('Registro eliminado correctamente');
                } else {
                    showError(result.message || 'Error al eliminar el registro');
                }
            } catch (error) {
                showError('Error de conexión: ' + error.message);
            }
        }

        async function exportToExcel() {
            try {
                const params = new URLSearchParams({
                    action: 'export',
                    table: currentTable,
                    search: currentSearch
                });
                
                const response = await fetch('api/crud.php?' + params);
                
                if (response.ok) {
                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `${currentTable}_${new Date().toISOString().split('T')[0]}.xlsx`;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                    showSuccess('Archivo Excel generado correctamente');
                } else {
                    showError('Error al generar el archivo Excel');
                }
            } catch (error) {
                showError('Error de conexión: ' + error.message);
            }
        }

        function showModal() {
            document.getElementById('crud-modal').classList.add('show');
        }

        function closeModal() {
            document.getElementById('crud-modal').classList.remove('show');
        }

        function showLoading(show) {
            document.getElementById('table-loading').style.display = show ? 'flex' : 'none';
        }

        function showError(message) {
            // Implementar toast/notification
            alert('Error: ' + message);
        }

        function showSuccess(message) {
            // Implementar toast/notification
            alert('Éxito: ' + message);
        }

        // Event listeners
        document.getElementById('search-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyFilters();
            }
        });

        document.getElementById('page-size').addEventListener('change', applyFilters);
        
        // Cerrar modal al hacer clic fuera
        document.getElementById('crud-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
</body>
</html>
