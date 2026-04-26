<?php
require_once __DIR__ . '/../config/database.php';

class Alert {
    private $db;
    private $minHoursPerDay = 8;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    public function setMinHours($hours) {
        $this->minHoursPerDay = $hours;
    }
    
    public function checkUsersWithoutRecord() {
        $result = $this->db->query(
            "SELECT u.id, u.email, u.nombre, u.apellidos
             FROM usuarios u
             WHERE u.activo = 1 AND u.rol = 'user'
             AND u.id NOT IN (
                 SELECT DISTINCT usuario_id 
                 FROM registros_tiempo 
                 WHERE DATE(fecha_hora) = CURDATE()
             )"
        );
        
        $users = $result ? $result->fetchAll() : [];
        $alerts = [];
        
        foreach ($users as $user) {
            $alerts[] = [
                'usuario_id' => $user['id'],
                'tipo_alerta' => 'ausencia',
                'titulo' => 'Usuario no ha fichado hoy',
                'mensaje' => "El usuario {$user['nombre']} {$user['apellidos']} no ha registrado entrada hoy.",
                'prioridad' => 'alta'
            ];
        }
        
        return $alerts;
    }
    
    public function checkUsersWithLowHours() {
        $result = $this->db->query(
            "SELECT u.id, u.email, u.nombre, u.apellidos,
                    COUNT(CASE WHEN rt.tipo_registro = 'check-in' THEN 1 END) as entradas,
                    COUNT(CASE WHEN rt.tipo_registro = 'check-out' THEN 1 END) as salidas
             FROM usuarios u
             LEFT JOIN registros_tiempo rt ON u.id = rt.usuario_id AND DATE(rt.fecha_hora) = CURDATE()
             WHERE u.activo = 1 AND u.rol = 'user'
             GROUP BY u.id, u.email, u.nombre, u.apellidos
             HAVING entradas > 0 AND salidas > 0"
        );
        
        $users = $result ? $result->fetchAll() : [];
        $alerts = [];
        
        foreach ($users as $user) {
            $hours = min($user['entradas'], $user['salidas']);
            if ($hours < $this->minHoursPerDay) {
                $alerts[] = [
                    'usuario_id' => $user['id'],
                    'tipo_alerta' => 'retraso',
                    'titulo' => 'Horas insuficientes hoy',
                    'mensaje' => "El usuario {$user['nombre']} {$user['apellidos']} solo ha trabajado {$hours} horas hoy (mínimo: {$this->minHoursPerDay}h).",
                    'prioridad' => 'media'
                ];
            }
        }
        
        return $alerts;
    }
    
    public function saveAlert($usuarioId, $tipoAlerta, $titulo, $mensaje, $prioridad = 'media') {
        $existing = $this->db->query(
            "SELECT id FROM alertas 
             WHERE usuario_id = ? AND tipo_alerta = ? AND DATE(fecha_creacion) = CURDATE() 
             AND titulo = ?",
            [$usuarioId, $tipoAlerta, $titulo]
        );
        
        if ($existing && $existing->fetch()) {
            return false;
        }
        
        return $this->db->query(
            "INSERT INTO alertas (usuario_id, tipo_alerta, titulo, mensaje, prioridad) 
             VALUES (?, ?, ?, ?, ?)",
            [$usuarioId, $tipoAlerta, $titulo, $mensaje, $prioridad]
        );
    }
    
    public function generateDailyAlerts() {
        $alertsGenerated = 0;
        
        $noRecordAlerts = $this->checkUsersWithoutRecord();
        foreach ($noRecordAlerts as $alert) {
            if ($this->saveAlert(
                $alert['usuario_id'], 
                $alert['tipo_alerta'], 
                $alert['titulo'], 
                $alert['mensaje'], 
                $alert['prioridad']
            )) {
                $alertsGenerated++;
            }
        }
        
        $lowHoursAlerts = $this->checkUsersWithLowHours();
        foreach ($lowHoursAlerts as $alert) {
            if ($this->saveAlert(
                $alert['usuario_id'], 
                $alert['tipo_alerta'], 
                $alert['titulo'], 
                $alert['mensaje'], 
                $alert['prioridad']
            )) {
                $alertsGenerated++;
            }
        }
        
        return $alertsGenerated;
    }
    
    public function getUnreadAlerts($limit = 50) {
        $result = $this->db->query(
            "SELECT a.id, a.usuario_id, a.tipo_alerta, a.titulo, a.mensaje, a.prioridad, a.fecha_creacion,
                    u.nombre, u.apellidos
             FROM alertas a
             INNER JOIN usuarios u ON a.usuario_id = u.id
             WHERE a.leida = 0
             ORDER BY 
                 CASE a.prioridad 
                     WHEN 'alta' THEN 1 
                     WHEN 'media' THEN 2 
                     WHEN 'baja' THEN 3 
                 END ASC,
                 a.fecha_creacion DESC
             LIMIT ?",
            [$limit]
        );
        
        return $result ? $result->fetchAll() : [];
    }
    
    public function getUserUnreadAlerts($userId, $limit = 20) {
        $result = $this->db->query(
            "SELECT id, tipo_alerta, titulo, mensaje, prioridad, fecha_creacion
             FROM alertas
             WHERE usuario_id = ? AND leida = 0
             ORDER BY 
                 CASE prioridad 
                     WHEN 'alta' THEN 1 
                     WHEN 'media' THEN 2 
                     WHEN 'baja' THEN 3 
                 END ASC,
                 fecha_creacion DESC
             LIMIT ?",
            [$userId, $limit]
        );
        
        return $result ? $result->fetchAll() : [];
    }
    
    public function markAsRead($alertId) {
        return $this->db->query(
            "UPDATE alertas SET leida = 1, fecha_lectura = NOW() WHERE id = ?",
            [$alertId]
        );
    }
    
    public function markAllAsRead($userId) {
        return $this->db->query(
            "UPDATE alertas SET leida = 1, fecha_lectura = NOW() WHERE usuario_id = ?",
            [$userId]
        );
    }
    
    public function getAlertStats($days = 7) {
        $result = $this->db->query(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN prioridad = 'alta' THEN 1 ELSE 0 END) as alta,
                SUM(CASE WHEN prioridad = 'media' THEN 1 ELSE 0 END) as media,
                SUM(CASE WHEN prioridad = 'baja' THEN 1 ELSE 0 END) as baja,
                SUM(CASE WHEN leida = 0 THEN 1 ELSE 0 END) as no_leidas
             FROM alertas
             WHERE fecha_creacion >= DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$days]
        );
        
        return $result ? $result->fetch() : false;
    }
}