# Guía de Instalación y Puesta en Marcha

## Requisitos Previos

- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web (Apache/Nginx)
- Git (opcional, para clonar el repositorio)

## Paso 1: Configurar la Base de Datos

### 1.1 Crear la base de datos y usuario MySQL

```bash
# Acceder a MySQL como root
mysql -u root -p

# Ejecutar en MySQL:
CREATE DATABASE control_horario CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'controlhorario'@'localhost' IDENTIFIED BY 'tu_contraseña_segura';
GRANT ALL PRIVILEGES ON control_horario.* TO 'controlhorario'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 1.2 Importar el script SQL

```bash
# Importar la estructura de tablas
mysql -u controlhorario -p control_horario < database.sql

# O ejecutar manualmente:
mysql -u controlhorario -p control_horario
source database.sql
```

## Paso 2: Configurar la Conexión a la Base de Datos

Editar el archivo `config/database.php`:

```php
private $host = 'localhost';
private $dbname = 'control_horario';
private $username = 'controlhorario';  // Cambiar por tu usuario
private $password = 'tu_contraseña_segura';  // Cambiar por tu contraseña
private $charset = 'utf8mb4';
```

## Paso 3: Configurar el Servidor Web

### Opción A: Apache (con .htaccess)

1. Asegurarse de que el módulo `mod_rewrite` está habilitado:
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

2. Crear un VirtualHost apuntando al directorio `public/`:

```apache
<VirtualHost *:80>
    ServerName controlhorario.local
    DocumentRoot /ruta/al/proyecto/public
    
    <Directory /ruta/al/proyecto/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/controlhorario_error.log
    CustomLog ${APACHE_LOG_DIR}/controlhorario_access.log combined
</VirtualHost>
```

3. Crear archivo `.htaccess` en `public/`:

```apache
RewriteEngine On

# Redirigir todo el tráfico a index.php (si se usa enrutamiento)
# RewriteCond %{REQUEST_FILENAME} !-f
# RewriteCond %{REQUEST_FILENAME} !-d
# RewriteRule ^(.*)$ index.php [QSA,L]

# Prevenir acceso a directorios
Options -Indexes

# Headers de seguridad adicionales
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "DENY"
    Header set X-XSS-Protection "1; mode=block"
</IfModule>
```

### Opción B: Nginx

Configuración recomendada:

```nginx
server {
    listen 80;
    server_name controlhorario.local;
    root /ruta/al/proyecto/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(ht|git) {
        deny all;
    }

    location ~ /\. {
        deny all;
    }
}
```

## Paso 4: Permisos de Archivos

```bash
# Establecer propietario correcto (www-data para Apache/Nginx)
sudo chown -R www-data:www-data /ruta/al/proyecto

# Permisos para directorios
find /ruta/al/proyecto -type d -exec chmod 755 {} \;

# Permisos para archivos
find /ruta/al/proyecto -type f -exec chmod 644 {} \;

# El archivo config/database.php debe ser más restrictivo
chmod 600 /ruta/al/proyecto/config/database.php
```

## Paso 5: Configuración de PHP

Editar `php.ini` (ubicación varía según sistema):

```ini
; Habilitar extensiones necesarias
extension=pdo_mysql

; Configuración de seguridad
expose_php = Off
display_errors = Off
log_errors = On
error_log = /var/log/php_errors.log

; Límites de sesión
session.cookie_httponly = 1
session.cookie_secure = 1  ; Solo si usas HTTPS
session.cookie_samesite = Strict
session.use_strict_mode = 1
```

## Paso 6: Configurar HTTPS (Recomendado)

### Usar Let's Encrypt (gratuito):

```bash
# Instalar Certbot
sudo apt install certbot python3-certbot-apache  # Para Apache
sudo apt install certbot python3-certbot-nginx   # Para Nginx

# Obtener certificado
sudo certbot --apache -d controlhorario.local    # Apache
sudo certbot --nginx -d controlhorario.local     # Nginx
```

## Paso 7: Probar la Instalación

1. Acceder a la aplicación en el navegador: `http://controlhorario.local` (o tu dominio)

2. Debería mostrar la página de inicio con opciones de login/registro

3. Probar registro de usuario:
   - Ir a `/register.php`
   - Completar formulario
   - Debería redirigir a login con mensaje de éxito

4. Probar login:
   - Iniciar sesión con las credenciales registradas
   - Debería redirigir al dashboard

5. Probar funcionalidades:
   - Registrar entrada (check-in)
   - Seleccionar proyecto
   - Registrar salida (check-out)
   - Ver resumen de horas

## Paso 8: Configuración de Correos (Opcional)

Para envío de notificaciones por email, configurar en `config/email.php`:

```php
define('EMAIL_HOST', 'smtp.gmail.com');
define('EMAIL_PORT', 587);
define('EMAIL_USER', 'tu_email@gmail.com');
define('EMAIL_PASSWORD', 'tu_contraseña');
define('EMAIL_FROM', 'no-reply@tudominio.com');
```

## Paso 9: Tareas Programadas (Cron Jobs)

Para generar alertas automáticas diariamente:

```bash
# Editar crontab
crontab -e

# Añadir línea para generar alertas cada día a las 23:59
59 23 * * * /usr/bin/php /ruta/al/proyecto/cron/generate-alerts.php >> /var/log/controlhorario_cron.log 2>&1
```

## Paso 10: Monitoreo y Mantenimiento

### Logs a revisar:
- `/var/log/apache2/error.log` o `/var/log/nginx/error.log`
- `/var/log/php_errors.log`
- `logs/security.log` (eventos de seguridad)

### Backup de base de datos:
```bash
# Script de backup diario
mysqldump -u controlhorario -p control_horario > /backups/control_horario_$(date +%Y%m%d).sql
```

## Solución de Problemas Comunes

### Error: "PDOException: could not find driver"
```bash
# Instalar extensión PDO MySQL
sudo apt install php7.4-mysql  # Ubuntu/Debian
sudo yum install php-pdo       # CentOS/RHEL
```

### Error: "Access denied for user"
- Verificar credenciales en `config/database.php`
- Asegurarse de que el usuario MySQL tiene permisos

### Error: "Headers already sent"
- Asegurarse de que no hay espacios en blanco antes de `<?php`
- Verificar que no hay BOM en los archivos

### Error: "CSRF token mismatch"
- Limpiar cookies del navegador
- Verificar que las sesiones están funcionando

## Verificación Final

Ejecutar script de verificación:

```php
<?php
// test_install.php (crear temporalmente en public/)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';

echo "=== Verificación de Instalación ===\n\n";

// 1. Verificar conexión a BD
try {
    $db = getDB();
    echo "✓ Conexión a base de datos: OK\n";
} catch (Exception $e) {
    echo "✗ Conexión a base de datos: FALLÓ\n";
}

// 2. Verificar tablas
$tables = ['usuarios', 'proyectos', 'registros_tiempo', 'alertas'];
foreach ($tables as $table) {
    $result = $db->query("SHOW TABLES LIKE ?", [$table]);
    if ($result && $result->fetch()) {
        echo "✓ Tabla $table: Existe\n";
    } else {
        echo "✗ Tabla $table: No existe\n";
    }
}

// 3. Verificar funciones de seguridad
if (function_exists('startSecureSession')) {
    echo "✓ Funciones de seguridad: Disponibles\n";
} else {
    echo "✗ Funciones de seguridad: No disponibles\n";
}

echo "\n=== Verificación Completada ===\n";
?>
```

Acceder a `test_install.php` desde el navegador para verificar que todo funciona correctamente.

## Notas de Seguridad Importantes

1. **Nunca** subir `config/database.php` a repositorios públicos
2. Usar **siempre** HTTPS en producción
3. Mantener PHP y MySQL actualizados
4. Cambiar las credenciales por defecto
5. Revisar logs regularmente
6. Hacer backups periódicos