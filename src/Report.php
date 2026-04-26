<?php
require_once __DIR__ . '/../config/database.php';

class Report {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    public function getHoursByProject($startDate = null, $endDate = null) {
        $dateFilter = '';
        $params = [];
        
        if ($startDate && $endDate) {
            $dateFilter = 'AND DATE(rt.fecha_hora) BETWEEN ? AND ?';
            $params = [$startDate, $endDate];
        } elseif ($startDate) {
            $dateFilter = 'AND DATE(rt.fecha_hora) >= ?';
            $params = [$startDate];
        } elseif ($endDate) {
            $dateFilter = 'AND DATE(rt.fecha_hora) <= ?';
            $params = [$endDate];
        }
        
        $result = $this->db->query(
            "SELECT 
                p.id,
                p.nombre,
                p.cliente,
                p.fecha_inicio,
                p.fecha_fin,
                COUNT(DISTINCT rt.usuario_id) as usuarios_activos,
                SUM(CASE WHEN rt.tipo_registro = 'check-in' THEN 1 ELSE 0 END) as total_entradas,
                SUM(CASE WHEN rt.tipo_registro = 'check-out' THEN 1 ELSE 0 END) as total_salidas,
                LEAST(
                    SUM(CASE WHEN rt.tipo_registro = 'check-in' THEN 1 ELSE 0 END),
                    SUM(CASE WHEN rt.tipo_registro = 'check-out' THEN 1 ELSE 0 END)
                ) as horas_totales
             FROM proyectos p
             LEFT JOIN registros_tiempo rt ON p.id = rt.proyecto_id $dateFilter
             WHERE p.activo = 1 OR p.activo = 0
             GROUP BY p.id, p.nombre, p.cliente, p.fecha_inicio, p.fecha_fin
             ORDER BY horas_totales DESC",
            $params
        );
        
        return $result ? $result->fetchAll() : [];
    }
    
    public function getProjectWithEstimates($startDate = null, $endDate = null) {
        $hoursByProject = $this->getHoursByProject($startDate, $endDate);
        $result = [];
        
        foreach ($hoursByProject as $project) {
            $estimatedHours = $this->getEstimatedHours($project['id']);
            $actualHours = $project['horas_totales'] ?? 0;
            $progress = $estimatedHours > 0 ? ($actualHours / $estimatedHours) * 100 : 0;
            
            $result[] = [
                'id' => $project['id'],
                'nombre' => $project['nombre'],
                'cliente' => $project['cliente'],
                'fecha_inicio' => $project['fecha_inicio'],
                'fecha_fin' => $project['fecha_fin'],
                'usuarios_activos' => $project['usuarios_activos'],
                'horas_reales' => $actualHours,
                'horas_estimadas' => $estimatedHours,
                'progreso' => round($progress, 2),
                'estado' => $progress >= 100 ? 'completado' : ($progress >= 75 ? 'avanzado' : ($progress >= 50 ? 'medio' : 'inicial'))
            ];
        }
        
        return $result;
    }
    
    private function getEstimatedHours($projectId) {
        $result = $this->db->query(
            "SELECT horas_estimadas FROM proyectos_estimados WHERE proyecto_id = ?",
            [$projectId]
        );
        $data = $result ? $result->fetch() : false;
        return $data ? $data['horas_estimadas'] : 0;
    }
    
    public function getUserHoursByProject($userId, $startDate = null, $endDate = null) {
        $dateFilter = '';
        $params = [];
        
        if ($startDate && $endDate) {
            $dateFilter = 'AND DATE(rt.fecha_hora) BETWEEN ? AND ?';
            $params = [$startDate, $endDate];
        } elseif ($startDate) {
            $dateFilter = 'AND DATE(rt.fecha_hora) >= ?';
            $params = [$startDate];
        } elseif ($endDate) {
            $dateFilter = 'AND DATE(rt.fecha_hora) <= ?';
            $params = [$endDate];
        }
        
        $result = $this->db->query(
            "SELECT 
                p.id,
                p.nombre,
                p.cliente,
                SUM(CASE WHEN rt.tipo_registro = 'check-in' THEN 1 ELSE 0 END) as entradas,
                SUM(CASE WHEN rt.tipo_registro = 'check-out' THEN 1 ELSE 0 END) as salidas,
                LEAST(
                    SUM(CASE WHEN rt.tipo_registro = 'check-in' THEN 1 ELSE 0 END),
                    SUM(CASE WHEN rt.tipo_registro = 'check-out' THEN 1 ELSE 0 END)
                ) as horas_totales
             FROM proyectos p
             INNER JOIN registros_tiempo rt ON p.id = rt.proyecto_id
             WHERE rt.usuario_id = ? $dateFilter
             GROUP BY p.id, p.nombre, p.cliente
             ORDER BY horas_totales DESC",
            array_merge([$userId], $params)
        );
        
        return $result ? $result->fetchAll() : [];
    }
    
    public function getDailyHours($projectId = null, $days = 7) {
        $projectFilter = $projectId ? 'AND rt.proyecto_id = ?' : '';
        $params = $projectId ? [$projectId, $days] : [$days];
        
        $result = $this->db->query(
            "SELECT 
                DATE(rt.fecha_hora) as fecha,
                SUM(CASE WHEN rt.tipo_registro = 'check-in' THEN 1 ELSE 0 END) as entradas,
                SUM(CASE WHEN rt.tipo_registro = 'check-out' THEN 1 ELSE 0 END) as salidas,
                LEAST(
                    SUM(CASE WHEN rt.tipo_registro = 'check-in' THEN 1 ELSE 0 END),
                    SUM(CASE WHEN rt.tipo_registro = 'check-out' THEN 1 ELSE 0 END)
                ) as horas
             FROM registros_tiempo rt
             WHERE rt.fecha_hora >= DATE_SUB(NOW(), INTERVAL ? DAY) $projectFilter
             GROUP BY DATE(rt.fecha_hora)
             ORDER BY fecha DESC",
            $params
        );
        
        return $result ? $result->fetchAll() : [];
    }
    
    public function getChartData($projectId = null, $days = 30) {
        $dailyHours = $this->getDailyHours($projectId, $days);
        
        $labels = [];
        $data = [];
        $cumulative = 0;
        $cumulativeData = [];
        
        $sortedHours = array_reverse($dailyHours);
        
        foreach ($sortedHours as $day) {
            $labels[] = date('d/m', strtotime($day['fecha']));
            $data[] = $day['horas'];
            $cumulative += $day['horas'];
            $cumulativeData[] = $cumulative;
        }
        
        return [
            'labels' => $labels,
            'daily' => $data,
            'cumulative' => $cumulativeData,
            'total' => $cumulative
        ];
    }
    
    public function getProjectComparison() {
        $result = $this->db->query(
            "SELECT 
                p.nombre,
                p.cliente,
                COUNT(DISTINCT rt.usuario_id) as team_size,
                LEAST(
                    SUM(CASE WHEN rt.tipo_registro = 'check-in' THEN 1 ELSE 0 END),
                    SUM(CASE WHEN rt.tipo_registro = 'check-out' THEN 1 ELSE 0 END)
                ) as hours_worked,
                COALESCE(pe.horas_estimadas, 0) as hours_estimated,
                ROUND(
                    CASE 
                        WHEN pe.horas_estimadas > 0 
                        THEN (LEAST(
                            SUM(CASE WHEN rt.tipo_registro = 'check-in' THEN 1 ELSE 0 END),
                            SUM(CASE WHEN rt.tipo_registro = 'check-out' THEN 1 ELSE 0 END)
                        ) / pe.horas_estimadas * 100)
                        ELSE 0 
                    END, 2
                ) as progress_percent
             FROM proyectos p
             LEFT JOIN registros_tiempo rt ON p.id = rt.proyecto_id
             LEFT JOIN proyectos_estimados pe ON p.id = pe.proyecto_id
             WHERE p.activo = 1
             GROUP BY p.id, p.nombre, p.cliente, pe.horas_estimadas
             ORDER BY progress_percent DESC"
        );
        
        return $result ? $result->fetchAll() : [];
    }
    
    public function exportToJSON($data) {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}