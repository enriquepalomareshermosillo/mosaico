-- Ejecutar en soyservi_mosaico01. Conserva todos los accesos existentes.
CREATE TABLE IF NOT EXISTS mosaic_settings (
 id TINYINT UNSIGNED PRIMARY KEY,
 theme VARCHAR(20) NOT NULL DEFAULT 'nocturno',
 admin_hash VARCHAR(255) DEFAULT NULL,
 auth_version INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO mosaic_settings (id,theme) VALUES (1,'nocturno');
