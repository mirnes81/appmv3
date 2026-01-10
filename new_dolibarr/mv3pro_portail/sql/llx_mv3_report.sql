-- Table pour les rapports de chantier
CREATE TABLE IF NOT EXISTS llx_mv3_report (
    rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
    entity INTEGER DEFAULT 1 NOT NULL,
    ref VARCHAR(30) NOT NULL,

    fk_project INTEGER DEFAULT NULL,
    fk_user_author INTEGER NOT NULL,
    fk_user_assigned INTEGER DEFAULT NULL,

    date_report DATE NOT NULL,
    time_start DATETIME DEFAULT NULL,
    time_end DATETIME DEFAULT NULL,
    duration_minutes INTEGER DEFAULT NULL,

    note_public TEXT,
    note_private TEXT,

    status INTEGER DEFAULT 0 NOT NULL,

    datec DATETIME,
    tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fk_user_creat INTEGER,
    fk_user_modif INTEGER,

    UNIQUE KEY uk_mv3_report_ref (ref, entity),
    INDEX idx_mv3_report_entity (entity),
    INDEX idx_mv3_report_project (fk_project),
    INDEX idx_mv3_report_author (fk_user_author),
    INDEX idx_mv3_report_date (date_report),
    INDEX idx_mv3_report_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
