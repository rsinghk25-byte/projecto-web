# Mejoras de Seguridad Implementadas

Este documento describe las mejoras de seguridad aplicadas al proyecto de Control Horario.

## 1. Protección contra SQL Injection

### Implementación:
- **Prepared Statements con PDO**: Todas las consultas SQL utilizan prepared statements con parámetros vinculados
- **Nunca se concatena SQL con datos de usuario directamente**

### Archivos afectados:
- `config/database.php` - Clase Database con método `query()` que usa prepared statements
- `src/*.php` - Todas las clases usan `$db->query()` con parámetros

### Ejemplo:
```php
// CORRECTO - Prepared statement
$stmt = $db->query("SELECT * FROM usuarios WHERE email = ?", [$email]);

// INCORRECTO - Nunca hacer esto
$sql = "SELECT * FROM usuarios WHERE email = '" . $email . "'";
```

## 2. Protección contra XSS (Cross-Site Scripting)

### Implementación:
- **Función `escape()`**: Aplica `htmlspecialchars()` a todo el contenido mostrado
- **Sanitización de entrada**: Los datos de entrada se limpian antes de procesarse

### Archivos afectados:
- `includes/security.php` - Funciones `escape()`, `sanitizeInput()`, `sanitizeEmail()`
- `public/*.php` - Todas las vistas usan `escape()` para mostrar datos

### Ejemplo:
```php
// En vistas PHP
echo escape($user['nombre']); // Previene inyección de HTML/JS

// En procesamiento
$nombre = sanitizeInput($_POST['nombre']);
```

## 3. Protección contra CSRF (Cross-Site Request Forgery)

### Implementación:
- **Tokens CSRF**: Cada formulario incluye un token único generado por sesión
- **Verificación en procesamiento**: Se valida el token antes de procesar cualquier POST

### Archivos afectados:
- `includes/security.php` - Funciones `generateCsrfToken()`, `verifyCsrfToken()`, `csrfField()`
- `public/login.php`, `public/register.php` - Incluyen `<?php echo csrfField(); ?>`
- `actions/login.php`, `actions/register.php` - Verifican con `verifyPostWithCsrf()`

### Ejemplo:
```php
// En formulario
<form method="POST">
    <?php echo csrfField(); ?>
    <!-- campos -->
</form>

// En procesamiento
if (!verifyPostWithCsrf()) {
    die('Token CSRF inválido');
}
```

## 4. Manejo Seguro de Sesiones

### Implementación:
- **Cookies seguras**: `httponly`, `secure` (si HTTPS), `samesite=Strict`
- **Regeneración de ID**: El ID de sesión se regenera cada 30 minutos
- **Validación de IP**: Se verifica que la IP no cambie durante la sesión
- **Timeout de actividad**: Sesión expira después de 1 hora de inactividad

### Archivos afectados:
- `includes/security.php` - Funciones `startSecureSession()`, `validateSession()`, `checkSessionActivity()`
- `includes/auth.php` - Integra validación de sesión en `isLoggedIn()`

### Configuración:
```php
ini_set('session.cookie_httponly', 1);      // Previene acceso JS a cookies
ini_set('session.use_strict_mode', 1);      // Previene fijación de sesión
ini_set('session.cookie_secure', 1);        // Solo HTTPS (si está disponible)
ini_set('session.cookie_samesite', 'Strict'); // Previene CSRF via cookies
```

## 5. Rate Limiting (Limitación de Intentos)

### Implementación:
- **Login**: Máximo 5 intentos cada 5 minutos
- **Registro**: Máximo 3 intentos cada 5 minutos
- **Registro de eventos**: Los intentos excedidos se registran en logs

### Archivos afectados:
- `includes/security.php` - Funciones `checkRateLimit()`, `clearRateLimit()`
- `actions/login.php`, `actions/register.php` - Aplican rate limiting

### Ejemplo:
```php
if (!checkRateLimit('login', 5, 300)) {
    logSecurityEvent('RATE_LIMIT', 'Login attempts exceeded');
    die('Demasiados intentos. Espere 5 minutos.');
}
```

## 6. Validación de Datos

### Implementación:
- **Email**: `filter_var()` con `FILTER_VALIDATE_EMAIL`
- **Enteros**: `filter_var()` con `FILTER_VALIDATE_INT`
- **Longitud máxima**: Los campos de texto tienen límites estrictos
- **Contraseñas**: Mínimo 8 caracteres (se recomienda mayúscula y número)

### Archivos afectados:
- `includes/security.php` - Funciones `isValidEmail()`, `isValidPositiveInt()`, `limitLength()`
- `actions/register.php`, `actions/login.php` - Validan todos los inputs

## 7. Headers de Seguridad

### Implementación:
- **X-Frame-Options: DENY** - Previene clickjacking
- **X-Content-Type-Options: nosniff** - Previene MIME sniffing
- **X-XSS-Protection: 1; mode=block** - Activa filtro XSS del navegador
- **Referrer-Policy: strict-origin-when-cross-origin** - Controla información de referer

### Archivos afectados:
- `includes/security.php` - Función `setSecurityHeaders()`
- `includes/auth.php` - Llama a `setSecurityHeaders()` en cada página protegida

## 8. Registro de Eventos de Seguridad

### Implementación:
- **Logging**: Todos los eventos de seguridad se registran en el log del servidor
- **Información registrada**: Timestamp, IP, evento, detalles, user agent

### Archivos afectados:
- `includes/security.php` - Función `logSecurityEvent()`
- `actions/login.php`, `actions/register.php` - Registran intentos fallidos, CSRF violations

### Ejemplo de log:
```
SECURITY: {"timestamp":"2026-04-26 14:00:00","ip":"192.168.1.100","event":"FAILED_LOGIN","details":"Contraseña inválida para: user@example.com","user_agent":"Mozilla/5.0..."}
```

## 9. Protección de Contraseñas

### Implementación:
- **Hash con bcrypt**: `password_hash()` con `PASSWORD_DEFAULT`
- **Verificación segura**: `password_verify()` para comparar
- **Nunca se almacenan en texto plano**

### Archivos afectados:
- `actions/register.php` - Hashea contraseña al registrar
- `actions/login.php` - Verifica contraseña al hacer login

## 10. Protección contra Clickjacking

### Implementación:
- **X-Frame-Options: DENY** - La página no se puede incrustar en iframes

### Archivos afectados:
- `includes/security.php` - Función `setSecurityHeaders()`

## Resumen de Archivos de Seguridad

| Archivo | Función Principal |
|---------|-------------------|
| `includes/security.php` | Funciones de seguridad centrales |
| `includes/auth.php` | Autenticación con validación de sesión |
| `config/database.php` | Conexión PDO con prepared statements |
| `actions/login.php` | Login con rate limiting y CSRF |
| `actions/register.php` | Registro con validación y CSRF |

## Buenas Prácticas Adicionales Recomendadas

1. **Usar HTTPS en producción** - Esencial para proteger datos en tránsito
2. **Configurar correctamente el servidor** - Apache/Nginx con headers de seguridad
3. **Mantener PHP actualizado** - Última versión estable
4. **Usar variables de entorno** - Para credenciales de base de datos
5. **Implementar 2FA** - Autenticación de dos factores para mayor seguridad
6. **Auditoría regular** - Revisar logs de seguridad periódicamente
7. **Backup de base de datos** - Copias de seguridad cifradas