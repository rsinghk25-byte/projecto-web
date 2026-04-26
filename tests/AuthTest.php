<?php
require_once __DIR__ . '/../config/database.php';

class AuthTest {
    private $db;
    private $passed = 0;
    private $failed = 0;
    private $testEmail;
    private $testPassword = 'testpassword123';
    
    public function __construct() {
        $this->db = getDB();
        $this->testEmail = 'test_' . time() . '@test.com';
    }
    
    private function assert($condition, $message) {
        if ($condition) {
            echo "✓ PASS: $message\n";
            $this->passed++;
        } else {
            echo "✗ FAIL: $message\n";
            $this->failed++;
        }
    }
    
    public function testDatabaseConnection() {
        echo "\n=== Test: Conexión a Base de Datos ===\n";
        $conn = $this->db->getConnection();
        $this->assert($conn !== null, 'La conexión no es nula');
        $this->assert($conn instanceof PDO, 'La conexión es una instancia de PDO');
    }
    
    public function testPasswordHash() {
        echo "\n=== Test: password_hash() ===\n";
        $hash = password_hash($this->testPassword, PASSWORD_DEFAULT);
        $this->assert(!empty($hash), 'El hash no está vacío');
        $this->assert(strlen($hash) >= 60, 'El hash tiene longitud adecuada');
        $this->assert(str_starts_with($hash, '$2y$'), 'El hash usa bcrypt');
        return $hash;
    }
    
    public function testPasswordVerify($hash) {
        echo "\n=== Test: password_verify() ===\n";
        $this->assert(
            password_verify($this->testPassword, $hash),
            'password_verify devuelve true para contraseña correcta'
        );
        $this->assert(
            !password_verify('wrongpassword', $hash),
            'password_verify devuelve false para contraseña incorrecta'
        );
    }
    
    public function testUserRegistration() {
        echo "\n=== Test: Registro de Usuario ===\n";
        
        $nombre = 'Test';
        $apellidos = 'User';
        $passwordHash = password_hash($this->testPassword, PASSWORD_DEFAULT);
        
        $result = $this->db->query(
            "INSERT INTO usuarios (nombre, apellidos, email, password_hash, rol) 
             VALUES (?, ?, ?, ?, 'user')",
            [$nombre, $apellidos, $this->testEmail, $passwordHash]
        );
        
        $this->assert($result !== null, 'El INSERT no devuelve null');
        
        $userId = $this->db->lastInsertId();
        $this->assert(!empty($userId), 'Se generó un ID de usuario');
        
        $user = $this->db->query(
            "SELECT * FROM usuarios WHERE id = ?",
            [$userId]
        )->fetch();
        
        $this->assert($user !== false, 'El usuario existe en la base de datos');
        $this->assert($user['email'] === $this->testEmail, 'El email coincide');
        $this->assert($user['nombre'] === $nombre, 'El nombre coincide');
        $this->assert($user['rol'] === 'user', 'El rol es user');
        
        return $userId;
    }
    
    public function testDuplicateEmail($userId) {
        echo "\n=== Test: Email Duplicado ===\n";
        
        $passwordHash = password_hash('anotherpassword', PASSWORD_DEFAULT);
        
        try {
            $result = $this->db->query(
                "INSERT INTO usuarios (nombre, apellidos, email, password_hash, rol) 
                 VALUES (?, ?, ?, ?, 'user')",
                ['Another', 'User', $this->testEmail, $passwordHash]
            );
            $this->assert(false, 'Debería fallar por email duplicado');
        } catch (PDOException $e) {
            $this->assert(true, 'Lanza excepción por email duplicado');
        }
    }
    
    public function testUserLogin($userId) {
        echo "\n=== Test: Login de Usuario ===\n";
        
        $user = $this->db->query(
            "SELECT id, email, password_hash, nombre, apellidos, rol, activo 
             FROM usuarios WHERE email = ?",
            [$this->testEmail]
        )->fetch();
        
        $this->assert($user !== false, 'Se encuentra el usuario por email');
        $this->assert($user['id'] == $userId, 'El ID de usuario coincide');
        $this->assert(
            password_verify($this->testPassword, $user['password_hash']),
            'La contraseña es válida'
        );
        $this->assert($user['activo'] == 1, 'El usuario está activo');
    }
    
    public function testInvalidLogin() {
        echo "\n=== Test: Login Inválido ===\n";
        
        $user = $this->db->query(
            "SELECT password_hash FROM usuarios WHERE email = 'nonexistent@test.com'"
        )->fetch();
        
        $this->assert($user === false, 'No se encuentra usuario inexistente');
    }
    
    public function testDeactivateUser($userId) {
        echo "\n=== Test: Desactivar Usuario ===\n";
        
        $this->db->query(
            "UPDATE usuarios SET activo = 0 WHERE id = ?",
            [$userId]
        );
        
        $user = $this->db->query(
            "SELECT activo FROM usuarios WHERE id = ?",
            [$userId]
        )->fetch();
        
        $this->assert($user['activo'] == 0, 'El usuario está desactivado');
        
        $this->db->query(
            "UPDATE usuarios SET activo = 1 WHERE id = ?",
            [$userId]
        );
    }
    
    public function cleanup($userId) {
        echo "\n=== Limpiando datos de prueba ===\n";
        $this->db->query("DELETE FROM usuarios WHERE id = ?", [$userId]);
        $deleted = $this->db->query(
            "SELECT id FROM usuarios WHERE id = ?",
            [$userId]
        )->fetch();
        $this->assert($deleted === false, 'Usuario de prueba eliminado');
    }
    
    public function run() {
        echo "========================================\n";
        echo "  Tests del Sistema de Autenticación\n";
        echo "========================================\n";
        
        $this->testDatabaseConnection();
        $hash = $this->testPasswordHash();
        $this->testPasswordVerify($hash);
        $userId = $this->testUserRegistration();
        $this->testDuplicateEmail($userId);
        $this->testUserLogin($userId);
        $this->testInvalidLogin();
        $this->testDeactivateUser($userId);
        $this->cleanup($userId);
        
        echo "\n========================================\n";
        echo "  Resultados: {$this->passed} passed, {$this->failed} failed\n";
        echo "========================================\n";
        
        return $this->failed === 0;
    }
}

$test = new AuthTest();
$success = $test->run();
exit($success ? 0 : 1);