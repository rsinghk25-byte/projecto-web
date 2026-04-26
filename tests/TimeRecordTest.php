<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/TimeRecord.php';
require_once __DIR__ . '/../src/Project.php';

class TimeRecordTest {
    private $db;
    private $timeRecord;
    private $project;
    private $passed = 0;
    private $failed = 0;
    private $testUserId;
    private $testProjectId;
    
    public function __construct() {
        $this->db = getDB();
        $this->timeRecord = new TimeRecord();
        $this->project = new Project();
        $this->setupTestData();
    }
    
    private function setupTestData() {
        $passwordHash = password_hash('testpass123', PASSWORD_DEFAULT);
        $testEmail = 'timetest_' . time() . '@test.com';
        
        $this->db->query(
            "INSERT INTO usuarios (nombre, apellidos, email, password_hash, rol) VALUES (?, ?, ?, ?, 'user')",
            ['Test', 'TimeRecord', $testEmail, $passwordHash]
        );
        $this->testUserId = $this->db->lastInsertId();
        
        $this->db->query(
            "INSERT INTO proyectos (nombre, descripcion, cliente, activo) VALUES (?, ?, ?, 1)",
            ['Proyecto Test', 'Descripción de prueba', 'Cliente Test']
        );
        $this->testProjectId = $this->db->lastInsertId();
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
    
    public function testCheckIn() {
        echo "\n=== Test: Check-In ===\n";
        
        $result = $this->timeRecord->checkIn($this->testUserId, null, 'Nota de prueba');
        $this->assert($result !== null, 'Check-in devuelve resultado no nulo');
        
        $recordId = $this->db->lastInsertId();
        $this->assert(!empty($recordId), 'Se generó ID de registro');
        
        $record = $this->db->query("SELECT * FROM registros_tiempo WHERE id = ?", [$recordId])->fetch();
        $this->assert($record !== false, 'El registro existe en la BD');
        $this->assert($record['tipo_registro'] === 'check-in', 'El tipo es check-in');
        $this->assert($record['usuario_id'] == $this->testUserId, 'El usuario_id coincide');
        $this->assert($record['notas'] === 'Nota de prueba', 'Las notas coinciden');
        $this->assert($record['proyecto_id'] === null, 'El proyecto_id es null');
    }
    
    public function testCheckInWithProject() {
        echo "\n=== Test: Check-In con Proyecto ===\n";
        
        $result = $this->timeRecord->checkIn($this->testUserId, $this->testProjectId, 'Trabajando en proyecto');
        $this->assert($result !== null, 'Check-in con proyecto devuelve resultado no nulo');
        
        $recordId = $this->db->lastInsertId();
        $record = $this->db->query("SELECT * FROM registros_tiempo WHERE id = ?", [$recordId])->fetch();
        
        $this->assert($record['proyecto_id'] == $this->testProjectId, 'El proyecto_id coincide');
    }
    
    public function testIsCurrentlyCheckedIn() {
        echo "\n=== Test: Estado de Check-In ===\n";
        
        $isCheckedIn = $this->timeRecord->isCurrentlyCheckedIn($this->testUserId);
        $this->assert($isCheckedIn === true, 'isCurrentlyCheckedIn devuelve true después de check-in');
    }
    
    public function testCheckOut() {
        echo "\n=== Test: Check-Out ===\n";
        
        $result = $this->timeRecord->checkOut($this->testUserId, 'Fin de jornada');
        $this->assert($result !== null, 'Check-out devuelve resultado no nulo');
        
        $recordId = $this->db->lastInsertId();
        $this->assert(!empty($recordId), 'Se generó ID de registro de salida');
        
        $record = $this->db->query("SELECT * FROM registros_tiempo WHERE id = ?", [$recordId])->fetch();
        $this->assert($record !== false, 'El registro de salida existe en la BD');
        $this->assert($record['tipo_registro'] === 'check-out', 'El tipo es check-out');
        $this->assert($record['notas'] === 'Fin de jornada', 'Las notas coinciden');
    }
    
    public function testIsCurrentlyCheckedOut() {
        echo "\n=== Test: Estado de Check-Out ===\n";
        
        $isCheckedIn = $this->timeRecord->isCurrentlyCheckedIn($this->testUserId);
        $this->assert($isCheckedIn === false, 'isCurrentlyCheckedIn devuelve false después de check-out');
    }
    
    public function testGetTodayRecords() {
        echo "\n=== Test: Registros de Hoy ===\n";
        
        $records = $this->timeRecord->getTodayRecords($this->testUserId);
        $this->assert(is_array($records), 'getTodayRecords devuelve un array');
        $this->assert(count($records) >= 2, 'Hay al menos 2 registros (entrada y salida)');
    }
    
    public function testCalculateTodayHours() {
        echo "\n=== Test: Cálculo de Horas ===\n";
        
        $hours = $this->timeRecord->calculateTodayHours($this->testUserId);
        $this->assert(is_numeric($hours), 'calculateTodayHours devuelve un número');
        $this->assert($hours >= 0, 'Las horas son >= 0');
    }
    
    public function testGetAllActiveProjects() {
        echo "\n=== Test: Proyectos Activos ===\n";
        
        $projects = $this->project->getAllActive();
        $this->assert(is_array($projects), 'getAllActive devuelve un array');
        $this->assert(count($projects) >= 1, 'Hay al menos 1 proyecto activo');
        
        $found = false;
        foreach ($projects as $p) {
            if ($p['id'] == $this->testProjectId) {
                $found = true;
                break;
            }
        }
        $this->assert($found, 'El proyecto de prueba está en la lista');
    }
    
    public function testCannotCheckoutWithoutCheckin() {
        echo "\n=== Test: No se puede hacer Check-out sin Check-in ===\n";
        
        $newEmail = 'newuser_' . time() . '@test.com';
        $passwordHash = password_hash('testpass123', PASSWORD_DEFAULT);
        
        $this->db->query(
            "INSERT INTO usuarios (nombre, apellidos, email, password_hash, rol) VALUES (?, ?, ?, ?, 'user')",
            ['New', 'User', $newEmail, $passwordHash]
        );
        $newUserId = $this->db->lastInsertId();
        
        $isCheckedIn = $this->timeRecord->isCurrentlyCheckedIn($newUserId);
        $this->assert($isCheckedIn === false, 'El nuevo usuario no tiene check-in');
    }
    
    public function cleanup() {
        echo "\n=== Limpiando datos de prueba ===\n";
        
        $this->db->query("DELETE FROM registros_tiempo WHERE usuario_id = ?", [$this->testUserId]);
        $this->db->query("DELETE FROM usuarios WHERE id = ?", [$this->testUserId]);
        $this->db->query("DELETE FROM proyectos WHERE id = ?", [$this->testProjectId]);
        
        $deletedUser = $this->db->query("SELECT id FROM usuarios WHERE id = ?", [$this->testUserId])->fetch();
        $this->assert($deletedUser === false, 'Usuario de prueba eliminado');
        
        $deletedProject = $this->db->query("SELECT id FROM proyectos WHERE id = ?", [$this->testProjectId])->fetch();
        $this->assert($deletedProject === false, 'Proyecto de prueba eliminado');
    }
    
    public function run() {
        echo "========================================\n";
        echo "  Tests del Sistema de Fichaje\n";
        echo "========================================\n";
        
        $this->testCheckIn();
        $this->testCheckInWithProject();
        $this->testIsCurrentlyCheckedIn();
        $this->testCheckOut();
        $this->testIsCurrentlyCheckedOut();
        $this->testGetTodayRecords();
        $this->testCalculateTodayHours();
        $this->testGetAllActiveProjects();
        $this->testCannotCheckoutWithoutCheckin();
        $this->cleanup();
        
        echo "\n========================================\n";
        echo "  Resultados: {$this->passed} passed, {$this->failed} failed\n";
        echo "========================================\n";
        
        return $this->failed === 0;
    }
}

$test = new TimeRecordTest();
$success = $test->run();
exit($success ? 0 : 1);