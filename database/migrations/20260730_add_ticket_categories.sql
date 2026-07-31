-- Agrega categorías estructuradas para poder filtrar los tickets.
-- Ejecutar una sola vez en la base de datos antes de publicar el código PHP.

ALTER TABLE tickets
    ADD COLUMN categoria VARCHAR(50) NOT NULL DEFAULT 'Sin categoría' AFTER prioridad,
    ADD COLUMN otra_categoria VARCHAR(120) NULL AFTER categoria,
    ADD INDEX idx_tickets_categoria (categoria);
