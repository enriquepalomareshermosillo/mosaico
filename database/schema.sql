-- Selecciona soyservi_mosaico01 en phpMyAdmin antes de importar.
-- No modifica ni elimina tablas existentes.
CREATE TABLE IF NOT EXISTS mosaic_links (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 title VARCHAR(120) NOT NULL,
 url VARCHAR(2048) NOT NULL,
 image VARCHAR(255) NOT NULL DEFAULT '',
 category VARCHAR(60) NOT NULL DEFAULT 'General',
 position INT UNSIGNED NOT NULL DEFAULT 0,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS mosaic_login_attempts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 ip_hash CHAR(64) NOT NULL,
 attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX attempts_by_ip (ip_hash, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Ejecutar en soyservi_mosaico01. Conserva todos los accesos existentes.
CREATE TABLE IF NOT EXISTS mosaic_settings (
 id TINYINT UNSIGNED PRIMARY KEY,
 theme VARCHAR(20) NOT NULL DEFAULT 'nocturno',
 admin_hash VARCHAR(255) DEFAULT NULL,
 auth_version INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO mosaic_settings (id,theme) VALUES (1,'nocturno');
