<?php
require_once __DIR__ . '/../config/database.php';

class User {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    public function getById($userId) {
        $result = $this->db->query(
            "SELECT id, email, nombre, apellidos, rol, activo, fecha_creacion 
             FROM usuarios WHERE id = ?",
            [$userId]
        );
        return $result ? $result->fetch() : false;
    }
    
    public function getAllEmployees() {
        $result = $this->db->query(
            "SELECT u.id, u.email, u.nombre, u.apellidos, u.rol, u.activo, u.fecha_creacion,
                    MAX(CASE WHEN rt.tipo_registro = 'check-in' THEN rt.fecha_hora END) as last_checkin,
                    MAX(CASE WHEN rt.tipo_registro = 'check-out' THEN rt.fecha_hora END) as last_checkout
             FROM usuarios u
             LEFT JOIN registros_tiempo rt ON u.id = rt.usuario_id
             WHERE u.rol = 'user'
             GROUP BY u.id, u.email, u.nombre, u.apellidos, u.rol, u.activo, u.fecha_creacion
             ORDER BY u.apellidos, u.nombre ASC"
        );
        return $result ? $result->fetchAll() : [];
    }
    
    public function getAllUsers() {
        $result = $this->db->query(
            "SELECT id, email, nombre, apellidos, rol, activo, fecha_creacion 
             FROM usuarios ORDER BY apellidos, nombre ASC"
        );
        return $result ? $result->fetchAll() : [];
    }
    
    public function getEmailById($userId) {
        $result = $this->db->query(
            "SELECT email FROM usuarios WHERE id = ?",
            [$userId]
        );
        $user = $result ? $result->fetch() : false;
        return $user ? $user['email'] : null;
    }
    
    public function updateProfile($userId, $nombre, $apellidos) {
        return $this->db->query(
            "UPDATE usuarios SET nombre = ?, apellidos = ? WHERE id = ?",
            [$nombre, $apellidos, $userId]
        );
    }
    
    public function changePassword($userId, $passwordHash) {
        return $this->db->query(
            "UPDATE usuarios SET password_hash = ? WHERE id = ?",
            [$passwordHash, $userId]
        );
    }
    
    public function toggleActive($userId, $activo) {
        return $this->db->query(
            "UPDATE usuarios SET activo = ? WHERE id = ?",
            [$activo ? 1 : 0, $userId]
        );
    }
    
    public function isWorking($userId) {
        $result = $this->db->query(
            "SELECT 
                MAX(CASE WHEN tipo_registro = 'check-in' THEN fecha_hora END) as last_in,
                MAX(CASE WHEN tipo_registro = 'check-out' THEN fecha_hora END) as last_out
             FROM registros_tiempo
             WHERE usuario_id = ?",
            [$userId]
        );
        
        $times = $result ? $result->fetch() : false;
        if (!$times) {
            return false;
        }
        
        if (!$times['last_in']) {
            return false;
        }
        
        if (!$times['last_out']) {
            return true;
        }
        
        return strtotime($times['last_in']) > strtotime($times['last_out']);
    }
    
    public function getTodayHours($userId) {
        $result = $this->db->query(
            "SELECT 
                SUM(CASE WHEN tipo_registro = 'check-in' THEN 1 ELSE 0 END) as checkins,
                SUM(CASE WHEN tipo_registro = 'check-out' THEN 1 ELSE 0 END) as checkouts
             FROM registros_tiempo
             WHERE usuario_id = ? AND DATE(fecha_hora) = CURDATE()",
            [$userId]
        );
        
        $data = $result ? $result->fetch() : false;
        if (!$data) {
            return 0;
        }
        
        return min($data['checkins'], $data['checkouts']);
    }
    
    public function getTotalUsers() {
        $result = $this->db->query("SELECT COUNT(*) as total FROM usuarios WHERE activo = 1");
        $data = $result ? $result->fetch() : false;
        return $data ? $data['total'] : 0;
    }
    
    public function getWorkingUsersCount() {
        $result = $this->db->query(
            "SELECT COUNT(DISTINCT u.id) as total
             FROM usuarios u
             INNER JOIN registros_tiempo rt ON u.id = rt.usuario_id
             WHERE u.activo = 1 AND rt.tipo_registro = 'check-in'
             AND rt.fecha_hora = (
                 SELECT MAX(rt2.fecha_hora)
                 FROM registros_tiempo rt2
                 WHERE rt2.usuario_id = u.id
             )"
        );
        $data = $result ? $result->fetch() : false;
        return $data ? $data['total'] : 0;
    }
}