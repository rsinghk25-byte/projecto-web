# Explicación del código de conexión a base de datos

## Archivo: `config/database.php`

### Clase Database (Patrón Singleton)

La clase `Database` implementa el patrón **Singleton**, que garantiza que solo exista una instancia de conexión a la base de datos durante toda la ejecución del script.

### Propiedades de configuración:

```php
private $host = 'localhost';        // Servidor MySQL (normalmente localhost)
private $dbname = 'control_horario'; // Nombre de la base de datos
private $username = 'root';         // Usuario de MySQL
private $password = '';             // Contraseña (vacía por defecto en XAMPP)
private $charset = 'utf8mb4';       // UTF-8 con soporte para emojis
```

### Método `getInstance()` - Patrón Singleton:

```php
public static function getInstance() {
    if (self::$instance === null) {
        self::$instance = new self();
    }
    return self::$instance;
}
```
- Verifica si ya existe una instancia
- Si no existe, la crea
- Si ya existe, devuelve la misma instancia
- Esto evita múltiples conexiones innecesarias

### Método `connect()` - Conexión con PDO:

**DSN (Data Source Name):**
```php
$dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
```
- Define el driver (`mysql`), servidor, base de datos y conjunto de caracteres

**Opciones de PDO:**
```php
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,      // Lanza excepciones en errores
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Devuelve arrays asociativos
    PDO::ATTR_EMULATE_PREPARES => false,              // Usa prepared statements nativos
    PDO::ATTR_PERSISTENT => false,                    // No usa conexiones persistentes
];
```

**Try-Catch para manejo seguro de errores:**
```php
try {
    $this->connection = new PDO($dsn, $this->username, $this->password, $options);
} catch (PDOException $e) {
    error_log("Error de conexión: " . $e->getMessage());
    die("Error de conexión. Contacte con el administrador.");
}
```
- `try`: Intenta crear la conexión
- `catch`: Si falla, registra el error en el log del servidor (no lo muestra al usuario)
- `die()`: Muestra un mensaje genérico al usuario (sin información sensible)

### Método `query()` - Consultas seguras:

```php
public function query($sql, $params = []) {
    try {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Error en consulta: " . $e->getMessage());
        return null;
    }
}
```
- `prepare()`: Compila la consulta SQL con placeholders
- `execute()`: Ejecuta con parámetros vinculados (previene SQL injection)
- Devuelve el resultado o `null` si hay error

### Método `lastInsertId()`:
Devuelve el último ID generado por un INSERT (para campos AUTO_INCREMENT).

### Métodos de transacción:
- `beginTransaction()`: Inicia una transacción
- `commit()`: Confirma los cambios
- `rollback()`: Cancela los cambios (deshace)

### Función helper `getDB()`:
```php
function getDB() {
    return Database::getInstance();
}
```
Forma rápida de obtener la conexión sin escribir `Database::getInstance()`.

## Ejemplo de uso:

```php
// Incluir el archivo de configuración
require_once 'config/database.php';

// Obtener la conexión
$db = getDB();

// Ejecutar una consulta SELECT
$result = $db->query("SELECT * FROM usuarios WHERE email = ?", ['usuario@empresa.com']);
$usuario = $result->fetch();

// Ejecutar un INSERT
$db->query(
    "INSERT INTO registros_tiempo (usuario_id, tipo_registro, fecha_hora) VALUES (?, ?, NOW())",
    [1, 'check-in']
);
$ultimo_id = $db->lastInsertId();
```

## Seguridad implementada:

1. **Prepared Statements**: Previene SQL injection
2. **Manejo seguro de errores**: No expone credenciales ni detalles de la BD
3. **Error logging**: Registra errores en el log del servidor para debugging
4. **Singleton**: Evita múltiples conexiones (mejora rendimiento)
5. **UTF-8 (utf8mb4)**: Soporte completo para caracteres especiales y emojis