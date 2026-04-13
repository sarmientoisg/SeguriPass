-- ===================================================
-- SEGURIPASS - Script de Base de Datos
-- Ejecutar en phpMyAdmin o MySQL de XAMPP
-- ===================================================

CREATE DATABASE IF NOT EXISTS seguripass
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_spanish_ci;

USE seguripass;

-- ===== TABLA: usuarios (prefectos y administradores) =====
CREATE TABLE IF NOT EXISTS usuarios (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  nombre_completo  VARCHAR(100) NOT NULL,
  numero_empleado  VARCHAR(20)  NOT NULL UNIQUE,
  usuario          VARCHAR(50)  NOT NULL UNIQUE,
  contrasena       VARCHAR(255) NOT NULL,
  turno            VARCHAR(20)  NOT NULL,
  area             VARCHAR(50)  NOT NULL,
  permisos         VARCHAR(20)  NOT NULL DEFAULT 'Prefecto',
  observaciones    TEXT,
  fecha_registro   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ===== TABLA: visitantes =====
CREATE TABLE IF NOT EXISTS visitantes (
  id                   INT AUTO_INCREMENT PRIMARY KEY,
  nombre_completo      VARCHAR(100) NOT NULL,
  identificacion_oficial VARCHAR(50) NOT NULL,
  motivo_visita        VARCHAR(150) NOT NULL,
  persona_a_visitar    VARCHAR(100),
  area                 VARCHAR(50)  NOT NULL,
  fecha_registro       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  estado               VARCHAR(20)  NOT NULL DEFAULT 'Pendiente'
) ENGINE=InnoDB;

-- ===== TABLA: solicitudes =====
CREATE TABLE IF NOT EXISTS solicitudes (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  visitante_id     INT          NOT NULL,
  fecha_solicitud  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  estado           VARCHAR(20)  NOT NULL DEFAULT 'Pendiente',
  motivo_rechazo   VARCHAR(200),
  fecha_atencion   TIMESTAMP    NULL,
  FOREIGN KEY (visitante_id) REFERENCES visitantes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===== TABLA: validaciones =====
CREATE TABLE IF NOT EXISTS validaciones (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  solicitud_id     INT          NOT NULL,
  usuario_id       INT          NOT NULL,
  resultado        VARCHAR(20)  NOT NULL,
  fecha_validacion TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (solicitud_id) REFERENCES solicitudes(id) ON DELETE CASCADE,
  FOREIGN KEY (usuario_id)   REFERENCES usuarios(id)   ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===== TABLA: reportes =====
CREATE TABLE IF NOT EXISTS reportes (
  id                 INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id         INT         NOT NULL,
  rango_fecha_inicio DATE        NOT NULL,
  rango_fecha_fin    DATE        NOT NULL,
  formato            VARCHAR(10) NOT NULL,
  area_filtro        VARCHAR(50),
  fecha_generacion   TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===== TABLA: respaldos =====
CREATE TABLE IF NOT EXISTS respaldos (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id     INT          NOT NULL,
  tipo           VARCHAR(20)  NOT NULL,
  nombre_archivo VARCHAR(100) NOT NULL,
  fecha          TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  estado         VARCHAR(20)  NOT NULL DEFAULT 'Completado',
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===== TABLA: configuraciones =====
CREATE TABLE IF NOT EXISTS configuraciones (
  id                  INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id          INT         NOT NULL,
  dias_operacion      VARCHAR(30),
  hora_inicio         TIME,
  hora_fin            TIME,
  tiempo_sesion       INT         DEFAULT 30,
  nombre_plantel      VARCHAR(100) DEFAULT 'CETis 132',
  fecha_modificacion  TIMESTAMP   DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===== ADMINISTRADOR POR DEFECTO =====
-- Contraseña: admin123 (hash SHA2-256)
INSERT INTO usuarios (nombre_completo, numero_empleado, usuario, contrasena, turno, area, permisos)
VALUES (
  'Administrador del Sistema',
  'ADMIN001',
  'admin',
  SHA2('admin123', 256),
  'Tiempo Completo',
  'Administración',
  'Administrador'
) ON DUPLICATE KEY UPDATE id = id;
