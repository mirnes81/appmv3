-- Table de journalisation des erreurs du module MV3 PRO Portail
-- Stocke toutes les erreurs backend/frontend pour diagnostic

CREATE TABLE IF NOT EXISTS llx_mv3_error_log (
    rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
    debug_id VARCHAR(50) NOT NULL,
    error_type VARCHAR(50),
    error_source VARCHAR(100),
    error_message TEXT,
    error_details LONGTEXT,
    http_status INTEGER,
    endpoint VARCHAR(255),
    method VARCHAR(10),
    user_id INTEGER,
    user_login VARCHAR(100),
    ip_address VARCHAR(50),
    user_agent TEXT,
    request_data LONGTEXT,
    response_data LONGTEXT,
    sql_error TEXT,
    stack_trace LONGTEXT,
    date_error DATETIME NOT NULL,
    entity INTEGER DEFAULT 1,
    INDEX idx_debug_id (debug_id),
    INDEX idx_date_error (date_error),
    INDEX idx_user_id (user_id),
    INDEX idx_endpoint (endpoint),
    INDEX idx_error_type (error_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
