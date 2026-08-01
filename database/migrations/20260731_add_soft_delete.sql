-- Desactivación reversible para conservar el historial del sistema.
ALTER TABLE usuarios
    ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1 AFTER verificado,
    ADD INDEX idx_usuarios_activo (activo);

ALTER TABLE tickets
    ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1 AFTER estado,
    ADD INDEX idx_tickets_activo (activo);
