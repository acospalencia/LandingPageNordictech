CREATE TABLE IF NOT EXISTS visores_tickets_clientes (
    id_visor INT NOT NULL,
    id_cliente INT NOT NULL,
    id_asignado_por INT NOT NULL,
    fecha_asignacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_visor, id_cliente),
    KEY idx_visores_tickets_cliente (id_cliente),
    KEY idx_visores_tickets_asignado_por (id_asignado_por)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
