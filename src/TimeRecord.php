<?php
require_once __DIR__ . '/../config/database.php';

class TimeRecord {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    public function checkIn($userId, $projectId = null, $notes = null) {
        return $this->db->query(
            "INSERT INTO registros_tiempo (usuario_id, proyecto_id, tipo_registro, fecha_hora, notas) 
             VALUES (?, ?, 'check-in', NOW(), ?)",
            [$userId, $projectId, $notes]
        );
    }
    
    public function checkOut($userId, $notes = null) {
        return $this->db->query(
            "INSERT INTO registros_tiempo (usuario_id, tipo_registro, fecha_hora, notas) 
             VALUES (?, 'check-out', NOW(), ?)",
            [$userId, $notes]
        );
    }
    
    public function getLastCheckIn($userId) {
        $result = $this->db->query(
            "SELECT * FROM registros_tiempo 
             WHERE usuario_id = ? AND tipo_registro = 'check-in' 
             ORDER BY fecha_hora DESC LIMIT 1",
            [$userId]
        );
        return $result ? $result->fetch() : false;
    }
    
    public function getLastCheckOut($userId) {
        $result = $this->db->query(
            "SELECT * FROM registros_tiempo 
             WHERE usuario_id = ? AND tipo_registro = 'check-out' 
             ORDER BY fecha_hora DESC LIMIT 1",
            [$userId]
        );
        return $result ? $result->fetch() : false;
    }
    
    public function isCurrentlyCheckedIn($userId) {
        $lastCheckIn = $this->getLastCheckIn($userId);
        $lastCheckOut = $this->getLastCheckOut($userId);
        
        if (!$lastCheckIn) {
            return false;
        }
        
        if (!$lastCheckOut) {
            return true;
        }
        
        return strtotime($lastCheckIn['fecha_hora']) > strtotime($lastCheckOut['fecha_hora']);
    }
    
    public function getUserRecords($userId, $limit = 50) {
        return $this->db->query(
            "SELECT rt.*, p.nombre as proyecto_nombre 
             FROM registros_tiempo rt
             LEFT JOIN proyectos p ON rt.proyecto_id = p.id
             WHERE rt.usuario_id = ?
             ORDER BY rt.fecha_hora DESC
             LIMIT ?",
            [$userId, $limit]
        );
    }
    
    public function getTodayRecords($userId) {
        return $this->db->query(
            "SELECT rt.*, p.nombre as proyecto_nombre 
             FROM registros_tiempo rt
             LEFT JOIN proyectos p ON rt.proyecto_id = p.id
             WHERE rt.usuario_id = ? AND DATE(rt.fecha_hora) = CURDATE()
             ORDER BY rt.fecha_hora DESC",
            [$userId]
        );
    }
    
    public function calculateTodayHours($userId) {
        $records = $this->getTodayRecords($userId);
        if (!$records) {
            return 0;
        }
        
        $checkIns = [];
        $checkOuts = [];
        
        foreach ($records as $record) {
            if ($record['tipo_registro'] === 'check-in') {
                $checkIns[] = strtotime($record['fecha_hora']);
            } else {
                $checkOuts[] = strtotime($record['fecha_hora']);
            }
        }
        
        $totalSeconds = 0;
        $pairs = min(count($checkIns), count($checkOuts));
        
        for ($i = 0; $i < $pairs; $i++) {
            $totalSeconds += ($checkOuts[$i] - $checkIns[$i]);
        }
        
        return $totalSeconds / 3600;
    }
}