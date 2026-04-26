<?php
require_once __DIR__ . '/../config/database.php';

class Project {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    public function getAllActive() {
        $result = $this->db->query(
            "SELECT id, nombre, descripcion, cliente 
             FROM proyectos 
             WHERE activo = 1 
             ORDER BY nombre ASC"
        );
        return $result ? $result->fetchAll() : [];
    }
    
    public function getById($projectId) {
        $result = $this->db->query(
            "SELECT * FROM proyectos WHERE id = ?",
            [$projectId]
        );
        return $result ? $result->fetch() : false;
    }
    
    public function create($nombre, $descripcion = null, $cliente = null, $fechaInicio = null, $fechaFin = null) {
        return $this->db->query(
            "INSERT INTO proyectos (nombre, descripcion, cliente, fecha_inicio, fecha_fin) 
             VALUES (?, ?, ?, ?, ?)",
            [$nombre, $descripcion, $cliente, $fechaInicio, $fechaFin]
        );
    }
    
    public function update($projectId, $nombre, $descripcion = null, $cliente = null, $fechaInicio = null, $fechaFin = null) {
        return $this->db->query(
            "UPDATE proyectos 
             SET nombre = ?, descripcion = ?, cliente = ?, fecha_inicio = ?, fecha_fin = ? 
             WHERE id = ?",
            [$nombre, $descripcion, $cliente, $fechaInicio, $fechaFin, $projectId]
        );
    }
    
    public function toggleActive($projectId, $activo) {
        return $this->db->query(
            "UPDATE proyectos SET activo = ? WHERE id = ?",
            [$activo ? 1 : 0, $projectId]
        );
    }
    
    public function getUserProjectHours($userId) {
        $result = $this->db->query(
            "SELECT p.id, p.nombre, 
                    COUNT(rt.id) as total_registros,
                    SUM(CASE WHEN rt.tipo_registro = 'check-in' THEN 1 ELSE 0 END) as entradas,
                    SUM(CASE WHEN rt.tipo_registro = 'check-out' THEN 1 ELSE 0 END) as salidas
             FROM proyectos p
             LEFT JOIN registros_tiempo rt ON p.id = rt.proyecto_id AND rt.usuario_id = ?
             WHERE p.activo = 1
             GROUP BY p.id, p.nombre
             ORDER BY p.nombre ASC",
            [$userId]
        );
        return $result ? $result->fetchAll() : [];
    }
}