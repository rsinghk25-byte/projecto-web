-- Base de datos para Sistema de Control Horario
-- Ejecutar este script para crear la estructura completa

-- Crear base de datos
CREATE DATABASE IF NOT EXISTS control_horario CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE control_horario;

-- ============================================
-- TABLA: usuarios
-- ============================================
CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(150) NOT NULL,
    rol ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_rol (rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: proyectos
-- ============================================
CREATE TABLE proyectos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    cliente VARCHAR(100),
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    fecha_inicio DATE,
    fecha_fin DATE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_activo (activo),
    INDEX idx_cliente (cliente)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: registros_tiempo
-- ============================================
CREATE TABLE registros_tiempo (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    proyecto_id INT,
    tipo_registro ENUM('check-in', 'check-out') NOT NULL,
    fecha_hora DATETIME NOT NULL,
    ubicacion VARCHAR(255),
    ip_address VARCHAR(45),
    notas TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (proyecto_id) REFERENCES proyectos(id) ON DELETE SET NULL,
    INDEX idx_usuario_fecha (usuario_id, fecha_hora),
    INDEX idx_proyecto (proyecto_id),
    INDEX idx_tipo (tipo_registro)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: alertas
-- ============================================
CREATE TABLE alertas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    tipo_alerta ENUM('ausencia', 'retraso', 'incidencia', 'sistema') NOT NULL,
    titulo VARCHAR(100) NOT NULL,
    mensaje TEXT NOT NULL,
    leida BOOLEAN NOT NULL DEFAULT FALSE,
    prioridad ENUM('baja', 'media', 'alta') NOT NULL DEFAULT 'media',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_lectura TIMESTAMP NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario_leida (usuario_id, leida),
    INDEX idx_prioridad (prioridad),
    INDEX idx_tipo (tipo_alerta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DATOS DE EJEMPLO (opcional)
-- ============================================

-- Usuario administrador (password: admin123 - hash de ejemplo)
INSERT INTO usuarios (email, password_hash, nombre, apellidos, rol) VALUES
('admin@empresa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'Sistema', 'admin');

-- Usuario de ejemplo (password: user123 - hash de ejemplo)
INSERT INTO usuarios (email, password_hash, nombre, apellidos, rol) VALUES
('usuario@empresa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Juan', 'Pérez García', 'user');

-- Proyecto de ejemplo
INSERT INTO proyectos (nombre, descripcion, cliente, activo, fecha_inicio) VALUES
('Desarrollo Web', 'Proyecto de desarrollo de aplicación web', 'Cliente Ejemplo S.L.', TRUE, '2026-01-01');