-- Table pour les lignes de tâches des rapports
CREATE TABLE IF NOT EXISTS llx_mv3_report_line (
    rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
    entity INTEGER DEFAULT 1 NOT NULL,
    fk_report INTEGER NOT NULL,

    label VARCHAR(255) NOT NULL,
    description TEXT,
    qty_minutes INTEGER DEFAULT NULL,
    note TEXT,

    sort_order INTEGER DEFAULT 0,

    datec DATETIME,
    tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_mv3_report_line_report (fk_report),
    INDEX idx_mv3_report_line_entity (entity),
    CONSTRAINT fk_mv3_report_line_report FOREIGN KEY (fk_report)
        REFERENCES llx_mv3_report(rowid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
