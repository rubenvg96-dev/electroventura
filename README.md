# Electroventura v2.0

## 📋 Descripción

**Electroventura v2.0** es un sistema integral de gestión de obras eléctricas diseñado para empresas del sector eléctrico. La aplicación permite gestionar obras, registrar horas de trabajo, controlar gastos e ingresos, gestionar materiales y generar informes detallados.

## 🎯 Propósito

El sistema está diseñado para:
- **Gestionar obras eléctricas** de forma centralizada
- **Controlar las horas trabajadas** por los empleados en cada obra
- **Registrar gastos e ingresos** asociados a cada proyecto
- **Gestionar materiales** utilizados en las obras
- **Generar informes y dashboards** para la toma de decisiones
- **Auditar la actividad** del sistema

## 👥 Roles de Usuario

### 🔒 Administrador
**Acceso completo al sistema**

#### Funcionalidades disponibles:
- **Gestionar Obras del Día**
  - Seleccionar y configurar qué obras están disponibles cada día
  - Activar/desactivar obras para fechas específicas
  - Control total sobre la disponibilidad de obras

- **Imputaciones**
  - ✅ Registrar horas trabajadas (propias y de otros)
  - ✅ Registrar gastos e ingresos
  - ✅ Gestionar materiales (varios tipos: Jung, Efapel, Iluminación, etc.)
  - ✅ Ver y modificar imputaciones existentes

- **Dashboard Completo**
  - 📊 Informes por obra o vista general
  - 📈 Análisis financiero detallado
  - 📋 Resúmenes de actividad
  - 🔍 Filtros por fechas y obras

- **Gestión Administrativa**
  - 👤 Gestión de usuarios
  - 🏢 Gestión de proveedores y contactos
  - ⚙️ Configuración del sistema
  - 📁 Acceso a logs y auditorías

#### Páginas accesibles:
- `admin_home.php` - Panel principal de administración
- `gestionar_obras.php` - Configuración de obras diarias
- `imputaciones.php` - Registro completo de actividades
- `dashboard.php` - Informes y análisis
- `gestion_administrativa.php` - Administración del sistema

---

### 👨‍💼 Supervisor
**Control y seguimiento de obras**

#### Funcionalidades disponibles:
- **Imputaciones**
  - ✅ Registrar horas trabajadas (propias)
  - ✅ Registrar gastos e ingresos
  - ✅ Gestionar materiales
  - ✅ Supervisar imputaciones de trabajadores

- **Dashboard**
  - 📊 Informes por obra o vista general
  - 📈 Análisis financiero
  - 📋 Seguimiento de actividades
  - 🔍 Filtros por fechas y obras

#### Limitaciones:
- ❌ No puede gestionar obras del día (solo admin)
- ❌ No tiene acceso a gestión administrativa completa

#### Páginas accesibles:
- `supervisor_home.php` - Panel de supervisión
- `imputaciones.php` - Registro de actividades
- `dashboard.php` - Informes y análisis

---

### 👷‍♂️ Trabajador
**Registro de horas y actividades**

#### Funcionalidades disponibles:
- **Imputaciones (Solo Horas)**
  - ✅ Registrar horas trabajadas propias
  - ✅ Ver obras asignadas del día
  - ✅ Agregar descripciones de trabajo realizado
  - ✅ Ver historial de horas registradas

#### Limitaciones:
- ❌ No puede registrar gastos, ingresos o materiales
- ❌ No tiene acceso al dashboard
- ❌ No puede gestionar obras ni configuraciones
- ❌ Solo ve sus propias imputaciones

#### Páginas accesibles:
- `trabajador_home.php` - Pantalla de bienvenida (redirección automática)
- `imputaciones.php` - Registro de horas únicamente

## 🗃️ Estructura de Datos

### Tablas Principales:
- **`usuarios`** - Gestión de usuarios y roles
- **`obras`** - Información de proyectos/obras
- **`obras_dia`** - Configuración diaria de obras disponibles
- **`horas_trabajadores`** - Registro de horas por trabajador y obra
- **`gastos_ingresos`** - Control financiero por obra
- **`proveedores`** - Clientes y proveedores
- **`contactos`** - Contactos de proveedores
- **`auditoria`** - Log de actividades del sistema

### Tipos de Materiales:
- Materiales varios
- Jung
- Efapel
- Iluminación
- Materiales comprados

## 🔐 Sistema de Seguridad

- **Autenticación** basada en usuario/contraseña
- **Autorización** por roles (admin, supervisor, trabajador)
- **Sesiones** con timeout automático (2 horas)
- **Auditoría completa** de actividades
- **Validación CSRF** en formularios
- **Logs de errores** y actividad

## 📊 APIs Disponibles

### `api/day_summary.php`
Proporciona resumen de actividades del día actual:
- Obras activas
- Horas trabajadas por obra
- Resumen de materiales
- Balance financiero del día

### `api/system_status.php`
Estado general del sistema y estadísticas básicas

## 🚀 Características Técnicas

- **PHP 8.0+** con PDO para base de datos
- **MySQL/MariaDB** como motor de base de datos
- **Diseño responsive** optimizado para móviles
- **Interfaz moderna** con Bootstrap y Font Awesome
- **Logging** completo de errores y actividades
- **Backup automático** de configuraciones

## 📱 Compatibilidad

- ✅ **Navegadores web** (Chrome, Firefox, Safari, Edge)
- ✅ **Dispositivos móviles** (iOS, Android)
- ✅ **Tablets** y dispositivos táctiles
- ✅ **Modo PWA** (Progressive Web App)

## 🔄 Flujo de Trabajo Típico

1. **Administrador** configura las obras disponibles para el día
2. **Trabajadores** inician sesión y registran sus horas de trabajo
3. **Supervisores** registran gastos, materiales y supervisan el progreso
4. **Dashboard** proporciona informes en tiempo real
5. **Sistema** mantiene auditoría completa de todas las actividades

## 📁 Estructura del Proyecto

```
electroventura_php2/
├── 📄 index.php                 # Punto de entrada principal
├── 🔐 login.php                 # Sistema de autenticación
├── 🚪 logout.php                # Cerrar sesión
├── 👤 *_home.php               # Páginas de inicio por rol
├── 📊 dashboard.php             # Informes y análisis
├── 📝 imputaciones.php          # Registro de actividades
├── ⚙️  gestionar_obras.php      # Configuración de obras (admin)
├── 🗂️  gestion_administrativa.php # Panel administrativo
├── 📁 config/                   # Configuraciones del sistema
│   ├── config.php              # Configuración general
│   └── database.php            # Conexión a base de datos
├── 📁 api/                      # APIs REST
│   ├── day_summary.php         # Resumen del día
│   └── system_status.php       # Estado del sistema
├── 📁 assets/                   # Recursos estáticos
│   └── css/styles.css          # Estilos principales
├── 📁 sql/                      # Scripts de base de datos
│   └── schema_v2.sql           # Esquema completo
└── 📁 logs/                     # Archivos de log
    └── error.log               # Log de errores
```

## 🚀 Instalación

1. **Clonar** el proyecto en el servidor web
2. **Configurar** la base de datos en `config/database.php`
3. **Ejecutar** el script `sql/schema_v2.sql`
4. **Crear usuario administrador** inicial
5. **Configurar permisos** de carpetas `logs/` y `uploads/`
6. **Acceder** vía navegador web

---

**Electroventura v2.0** - Sistema de gestión integral para empresas eléctricas
