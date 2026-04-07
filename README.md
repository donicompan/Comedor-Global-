# Cardón POS — Sistema de gestión para restaurantes

Sistema de punto de venta (POS) para restaurantes. Gestión de mesas, pedidos, cocina, ventas y licenciamiento por cliente.

---

## Características

- Panel de mesas en tiempo real (multi-zona configurable)
- Gestión de pedidos con roles: Cajero y Mozo
- Pantalla de cocina optimizada para TV/tablet
- Carta digital por categorías
- Reportes de ventas por día, semana y mes
- Administración de productos, personal y configuración
- Sistema de licencias con período de prueba de 14 días
- Actualizaciones automáticas desde el panel de administración

---

## Requisitos del servidor

- PHP 8.1 o superior (con extensiones: `mysqli`, `zip`, `mbstring`)
- MySQL 8.0 / MariaDB 10.6 o superior
- Apache con `mod_rewrite` (XAMPP funciona sin configuración adicional)

---

## Instalación en cliente nuevo

### 1. Importar la base de datos

Desde phpMyAdmin o consola:
```bash
mysql -u usuario -p nombre_bd < "BD Cardon/comedor_plato.sql"
```

### 2. Configurar credenciales

```bash
cp config.example.php config.php
```

Editar `config.php` con los datos del cliente:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'usuario_mysql');
define('DB_PASS', 'contraseña_mysql');
define('DB_NAME', 'nombre_bd');

// Misma clave que usás en tu herramienta generar_licencia.php
define('APP_SECRET', 'TU_CLAVE_SECRETA');

// URL del manifest de actualizaciones (proveída por el desarrollador)
define('UPDATE_MANIFEST_URL', 'https://tu-servidor.com/manifest.json');
define('UPDATE_ALLOWED_HOSTS', ['github.com', 'raw.githubusercontent.com']);
```

### 3. Verificar permisos

La carpeta `uploads/` debe tener permisos de escritura:
```bash
chmod 755 uploads/
```

### 4. Cambiar contraseñas de ejemplo

Desde **Admin → Cajeros / Mozos**, cambiar todas las contraseñas antes de entregar al cliente.

### 5. Generar y activar la licencia

1. Abrí `http://localhost/P/generar_licencia.php` (solo desde tu máquina)
2. Ingresá el email y duración
3. Enviá la clave al cliente
4. El cliente la activa desde la pantalla de expiración o desde Admin → Configuración

---

## Workflow de actualización (para el desarrollador)

1. Modificar archivos PHP según los cambios
2. Actualizar `version.php` con el nuevo número de versión
3. Si hay cambios de BD, agregar archivo en `migrations/` (ver sección Migraciones)
4. Abrir `http://localhost/P/build_update.php` y descargar el ZIP de actualización
5. Subir el ZIP a GitHub Releases o tu servidor
6. Actualizar el `manifest.json` en tu servidor con la nueva versión y URL

Los clientes ven el botón "Actualización disponible" en Admin → Configuración y la instalan con un clic. Sus datos no se modifican.

---

## Migraciones de base de datos

Los cambios de esquema se aplican automáticamente al primer acceso después de una actualización.

Formato de archivo: `migrations/NNN_descripcion.php`

```php
<?php
// migrations/001_ejemplo.php
return [
    "ALTER TABLE mesa ADD COLUMN IF NOT EXISTS color VARCHAR(7) DEFAULT '#ffffff'",
    "CREATE TABLE IF NOT EXISTS nueva_tabla (...)",
];
```

---

## Archivos del desarrollador (NO distribuir)

| Archivo | Motivo |
|---|---|
| `config.php` | Credenciales de BD del cliente |
| `generar_licencia.php` | Genera licencias — herramienta interna |
| `build_update.php` | Genera paquetes de distribución |
| `migrate_passwords.php` | Script de migración de uso único |

El script `build_update.php` los excluye automáticamente de todos los paquetes generados.

---

## Seguridad implementada

- Contraseñas con bcrypt (`password_hash` / `password_verify`)
- Protección CSRF en todos los formularios POST
- `session_regenerate_id()` al iniciar sesión
- Prepared statements en todas las consultas (prevención SQL injection)
- Salida escapada con `htmlspecialchars()` (prevención XSS)
- Control de acceso por rol en cada página
- Licencias firmadas con HMAC-SHA256
- Herramientas del desarrollador restringidas a localhost
- Errores de BD logueados sin exponer detalles al usuario

---

## Estructura de archivos clave

```
/
├── index.php                        # Login
├── principal.php                    # Panel de mesas
├── cocina.php                       # Pantalla de cocina (sin licencia)
├── admin.php                        # Administración
├── ventas.php                       # Reportes
│
├── app.php                          # Cargador principal + licencia + migraciones
├── conexion.php                     # Conexión BD
├── config.php                       # Credenciales (no en git)
├── config.example.php               # Plantilla de configuración
├── licencia.php                     # Motor de licencias
├── version.php                      # Versión actual
├── updater.php                      # Motor de actualizaciones (AJAX)
├── csrf.php                         # Helpers CSRF
│
├── migrations/                      # Migraciones de BD
├── uploads/                         # Archivos del cliente (logo, etc.)
├── backups/                         # Backups automáticos pre-actualización
└── BD Cardon/
    └── comedor_plato.sql            # Schema + datos de ejemplo
```
