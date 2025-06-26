-- =====================================================
-- SISTEMA DE GESTÃO JURÍDICA - BANCO DE DADOS
-- =====================================================

-- Criar o banco de dados
CREATE DATABASE IF NOT EXISTS `sistema_juridico` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `sistema_juridico`;

-- =====================================================
-- TABELA DE USUÁRIOS
-- =====================================================
CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL UNIQUE,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA DE TOKENS DE RESET DE SENHA
-- =====================================================
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA DE SESSÕES
-- =====================================================
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA DE CASOS JURÍDICOS
-- =====================================================
CREATE TABLE `cases` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `case_number` varchar(255) NOT NULL UNIQUE,
  `client_name` varchar(255) NOT NULL,
  `client_cpf` varchar(14) NOT NULL,
  `benefit_type` varchar(255) NOT NULL,
  `status` enum('pending','analysis','completed','requirement','rejected') NOT NULL DEFAULT 'pending',
  `description` text DEFAULT NULL,
  `estimated_value` decimal(10,2) DEFAULT NULL,
  `success_fee` decimal(5,2) NOT NULL DEFAULT 20.00,
  `filing_date` date DEFAULT NULL,
  `decision_date` date DEFAULT NULL,
  `assigned_to` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cases_assigned_to_foreign` (`assigned_to`),
  KEY `cases_created_by_foreign` (`created_by`),
  CONSTRAINT `cases_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cases_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA DE PROCESSOS INSS
-- =====================================================
CREATE TABLE `inss_processes` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `case_id` bigint(20) UNSIGNED NOT NULL,
  `process_number` varchar(255) NOT NULL UNIQUE,
  `protocol_number` varchar(255) DEFAULT NULL,
  `status` enum('analysis','completed','requirement','rejected','appeal') NOT NULL DEFAULT 'analysis',
  `last_movement` text DEFAULT NULL,
  `last_movement_date` date DEFAULT NULL,
  `is_seen` tinyint(1) NOT NULL DEFAULT 0,
  `has_changes` tinyint(1) NOT NULL DEFAULT 0,
  `movements_history` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inss_processes_case_id_foreign` (`case_id`),
  CONSTRAINT `inss_processes_case_id_foreign` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA DE VÍNCULOS EMPREGATÍCIOS
-- =====================================================
CREATE TABLE `employment_relationships` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `case_id` bigint(20) UNSIGNED NOT NULL,
  `employer_name` varchar(255) NOT NULL,
  `employer_cnpj` varchar(18) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `salary` decimal(10,2) DEFAULT NULL,
  `position` varchar(255) DEFAULT NULL,
  `cbo_code` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employment_relationships_case_id_foreign` (`case_id`),
  CONSTRAINT `employment_relationships_case_id_foreign` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA DE DOCUMENTOS
-- =====================================================
CREATE TABLE `documents` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `case_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `mime_type` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL,
  `extracted_data` json DEFAULT NULL,
  `is_processed` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `uploaded_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `documents_case_id_foreign` (`case_id`),
  KEY `documents_uploaded_by_foreign` (`uploaded_by`),
  CONSTRAINT `documents_case_id_foreign` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `documents_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA DE PETIÇÕES
-- =====================================================
CREATE TABLE `petitions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `case_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `type` varchar(255) NOT NULL,
  `status` enum('draft','generated','submitted','approved') NOT NULL DEFAULT 'draft',
  `file_path` varchar(255) DEFAULT NULL,
  `ai_generation_data` json DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `petitions_case_id_foreign` (`case_id`),
  KEY `petitions_created_by_foreign` (`created_by`),
  CONSTRAINT `petitions_case_id_foreign` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `petitions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA DE TAREFAS
-- =====================================================
CREATE TABLE `tasks` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `case_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `due_date` date DEFAULT NULL,
  `completed_at` date DEFAULT NULL,
  `assigned_to` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `required_documents` json DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tasks_case_id_foreign` (`case_id`),
  KEY `tasks_assigned_to_foreign` (`assigned_to`),
  KEY `tasks_created_by_foreign` (`created_by`),
  CONSTRAINT `tasks_case_id_foreign` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tasks_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tasks_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERIR USUÁRIO ADMIN PADRÃO
-- =====================================================
-- Senha: password (hash bcrypt)
INSERT INTO `users` (`name`, `email`, `password`, `created_at`, `updated_at`) VALUES
('Administrador', 'admin@sistema.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), NOW());

-- =====================================================
-- ÍNDICES ADICIONAIS PARA OTIMIZAÇÃO
-- =====================================================
-- Índices para busca por CPF
CREATE INDEX `idx_cases_client_cpf` ON `cases` (`client_cpf`);
CREATE INDEX `idx_cases_status` ON `cases` (`status`);
CREATE INDEX `idx_cases_benefit_type` ON `cases` (`benefit_type`);

-- Índices para processos INSS
CREATE INDEX `idx_inss_processes_status` ON `inss_processes` (`status`);
CREATE INDEX `idx_inss_processes_is_seen` ON `inss_processes` (`is_seen`);
CREATE INDEX `idx_inss_processes_has_changes` ON `inss_processes` (`has_changes`);

-- Índices para documentos
CREATE INDEX `idx_documents_type` ON `documents` (`type`);
CREATE INDEX `idx_documents_is_processed` ON `documents` (`is_processed`);

-- Índices para tarefas
CREATE INDEX `idx_tasks_status` ON `tasks` (`status`);
CREATE INDEX `idx_tasks_priority` ON `tasks` (`priority`);
CREATE INDEX `idx_tasks_due_date` ON `tasks` (`due_date`);

-- Índices para petições
CREATE INDEX `idx_petitions_type` ON `petitions` (`type`);
CREATE INDEX `idx_petitions_status` ON `petitions` (`status`);

-- =====================================================
-- COMENTÁRIOS DAS TABELAS
-- =====================================================
ALTER TABLE `users` COMMENT = 'Usuários do sistema';
ALTER TABLE `cases` COMMENT = 'Casos jurídicos dos clientes';
ALTER TABLE `inss_processes` COMMENT = 'Processos administrativos do INSS';
ALTER TABLE `employment_relationships` COMMENT = 'Vínculos empregatícios dos clientes';
ALTER TABLE `documents` COMMENT = 'Documentos anexados aos casos';
ALTER TABLE `petitions` COMMENT = 'Petições geradas para os casos';
ALTER TABLE `tasks` COMMENT = 'Tarefas do workflow dos casos';

-- =====================================================
-- FIM DO SCRIPT
-- ===================================================== 