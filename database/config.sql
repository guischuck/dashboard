-- =====================================================
-- CONFIGURAÇÕES ADICIONAIS PARA LARAVEL
-- =====================================================

-- Configurar timezone do banco
SET time_zone = '-03:00';

-- Configurar charset e collation
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;
SET collation_connection = 'utf8mb4_unicode_ci';

-- Configurar modo SQL
SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO';

-- =====================================================
-- TABELAS ADICIONAIS DO LARAVEL (se necessário)
-- =====================================================

-- Tabela de cache (se usar cache de banco)
CREATE TABLE IF NOT EXISTS `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de jobs (para filas)
CREATE TABLE IF NOT EXISTS `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de jobs falhados
CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DADOS INICIAIS DE EXEMPLO
-- =====================================================

-- Inserir tipos de benefício comuns
-- (Estes dados podem ser inseridos via seeder do Laravel)

-- =====================================================
-- PROCEDURES ÚTEIS
-- =====================================================

-- Procedure para limpar dados antigos
DELIMITER //
CREATE PROCEDURE `CleanOldData`()
BEGIN
    -- Limpar sessões antigas (mais de 30 dias)
    DELETE FROM `sessions` WHERE `last_activity` < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 30 DAY));
    
    -- Limpar cache expirado
    DELETE FROM `cache` WHERE `expiration` < UNIX_TIMESTAMP();
    
    -- Limpar jobs antigos (mais de 7 dias)
    DELETE FROM `jobs` WHERE `created_at` < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 7 DAY));
    
    -- Limpar jobs falhados antigos (mais de 30 dias)
    DELETE FROM `failed_jobs` WHERE `failed_at` < DATE_SUB(NOW(), INTERVAL 30 DAY);
END //
DELIMITER ;

-- =====================================================
-- VIEWS ÚTEIS
-- =====================================================

-- View para casos com informações resumidas
CREATE VIEW `vw_cases_summary` AS
SELECT 
    c.id,
    c.case_number,
    c.client_name,
    c.client_cpf,
    c.benefit_type,
    c.status,
    c.estimated_value,
    c.filing_date,
    c.decision_date,
    u.name as assigned_to_name,
    creator.name as created_by_name,
    COUNT(DISTINCT d.id) as documents_count,
    COUNT(DISTINCT p.id) as petitions_count,
    COUNT(DISTINCT t.id) as tasks_count,
    COUNT(DISTINCT CASE WHEN t.status = 'pending' THEN t.id END) as pending_tasks_count
FROM `cases` c
LEFT JOIN `users` u ON c.assigned_to = u.id
LEFT JOIN `users` creator ON c.created_by = creator.id
LEFT JOIN `documents` d ON c.id = d.case_id
LEFT JOIN `petitions` p ON c.id = p.case_id
LEFT JOIN `tasks` t ON c.id = t.case_id
WHERE c.deleted_at IS NULL
GROUP BY c.id;

-- View para processos INSS com mudanças
CREATE VIEW `vw_inss_processes_changes` AS
SELECT 
    ip.id,
    ip.process_number,
    ip.protocol_number,
    ip.status,
    ip.last_movement,
    ip.last_movement_date,
    ip.has_changes,
    ip.is_seen,
    c.case_number,
    c.client_name,
    c.client_cpf
FROM `inss_processes` ip
JOIN `cases` c ON ip.case_id = c.id
WHERE ip.has_changes = 1 OR ip.is_seen = 0
ORDER BY ip.last_movement_date DESC;

-- =====================================================
-- TRIGGERS ÚTEIS
-- =====================================================

-- Trigger para atualizar has_changes quando houver mudança no processo
DELIMITER //
CREATE TRIGGER `tr_inss_processes_update_changes` 
BEFORE UPDATE ON `inss_processes`
FOR EACH ROW
BEGIN
    IF NEW.last_movement != OLD.last_movement OR NEW.status != OLD.status THEN
        SET NEW.has_changes = 1;
        SET NEW.is_seen = 0;
    END IF;
END //
DELIMITER ;

-- Trigger para gerar número de caso automaticamente (se necessário)
-- (Este trigger seria uma alternativa ao método no controller)

-- =====================================================
-- FIM DO SCRIPT DE CONFIGURAÇÃO
-- ===================================================== 