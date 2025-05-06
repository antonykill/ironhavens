-- Creazione della tabella game_logs
CREATE TABLE IF NOT EXISTS `game_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `log_type` enum('BUILDING','RESOURCE','DB','SYSTEM','AUTH') NOT NULL,
  `action` varchar(50) NOT NULL,
  `data` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`log_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_log_type` (`log_type`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Creazione indici aggiuntivi per prestazioni migliori
CREATE INDEX IF NOT EXISTS `idx_log_type_action` ON `game_logs` (`log_type`, `action`);
CREATE INDEX IF NOT EXISTS `idx_user_created_at` ON `game_logs` (`user_id`, `created_at`);