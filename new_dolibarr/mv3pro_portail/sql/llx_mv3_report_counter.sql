-- Table compteur pour numérotation unique des rapports
CREATE TABLE IF NOT EXISTS llx_mv3_report_counter (
    entity INTEGER NOT NULL,
    year INTEGER NOT NULL,
    last_value INTEGER DEFAULT 0 NOT NULL,

    PRIMARY KEY (entity, year),
    INDEX idx_mv3_counter_entity (entity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
