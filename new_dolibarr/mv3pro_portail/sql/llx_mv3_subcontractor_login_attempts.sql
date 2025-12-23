/*
  # Table pour le rate limiting des connexions sous-traitants

  1. Tables créées
    - `llx_mv3_subcontractor_login_attempts` - Historique des tentatives de connexion
      - Tracking des tentatives réussies et échouées
      - Rate limiting par IP (5 échecs max en 15 minutes)
      - Nettoyage automatique après 24h

  2. Sécurité
    - Index sur ip_address et attempt_time pour performance
    - Pas de RLS nécessaire (table technique de sécurité)
    - Suppression automatique des vieilles entrées

  3. Utilisation
    - Prévention du brute force sur codes PIN
    - Logging des connexions pour audit
    - Blocage temporaire après échecs répétés
*/

CREATE TABLE IF NOT EXISTS llx_mv3_subcontractor_login_attempts (
    rowid INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    pin_code VARCHAR(10),
    success TINYINT(1) DEFAULT 0,
    attempt_time DATETIME NOT NULL,
    fk_subcontractor INT DEFAULT NULL,
    INDEX idx_ip_time (ip_address, attempt_time),
    INDEX idx_success_time (success, attempt_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
