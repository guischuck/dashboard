-- =====================================================
-- SISTEMA JURÍDICO - ESTRUTURA COMPLETA DO BANCO DE DADOS
-- =====================================================
-- Este arquivo contém a estrutura completa do banco de dados
-- baseada em todas as migrations do Laravel aplicadas ao projeto.
-- Criado automaticamente em: 2025-01-28
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- TABELAS DE SISTEMA (Laravel)
-- =====================================================

-- Tabela de usuários
CREATE TABLE `users` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `email` varchar(255) NOT NULL,
    `email_verified_at` timestamp NULL DEFAULT NULL,
    `password` varchar(255) NOT NULL,
    `remember_token` varchar(100) DEFAULT NULL,
    `company_id` bigint(20) UNSIGNED DEFAULT NULL,
    `role` enum('super_admin', 'admin', 'user') NOT NULL DEFAULT 'user',
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    `last_login_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `users_email_unique` (`email`),
    KEY `users_company_id_index` (`company_id`),
    KEY `users_role_index` (`role`),
    KEY `users_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de reset de senhas
CREATE TABLE `password_reset_tokens` (
    `email` varchar(255) NOT NULL,
    `token` varchar(255) NOT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de sessões
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

-- Tabelas de cache
CREATE TABLE `cache` (
    `key` varchar(255) NOT NULL,
    `value` mediumtext NOT NULL,
    `expiration` int(11) NOT NULL,
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache_locks` (
    `key` varchar(255) NOT NULL,
    `owner` varchar(255) NOT NULL,
    `expiration` int(11) NOT NULL,
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabelas de jobs/filas
CREATE TABLE `jobs` (
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

CREATE TABLE `job_batches` (
    `id` varchar(255) NOT NULL,
    `name` varchar(255) NOT NULL,
    `total_jobs` int(11) NOT NULL,
    `pending_jobs` int(11) NOT NULL,
    `failed_jobs` int(11) NOT NULL,
    `failed_job_ids` longtext NOT NULL,
    `options` mediumtext DEFAULT NULL,
    `cancelled_at` int(11) DEFAULT NULL,
    `created_at` int(11) NOT NULL,
    `finished_at` int(11) DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `failed_jobs` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid` varchar(255) NOT NULL,
    `connection` text NOT NULL,
    `queue` text NOT NULL,
    `payload` longtext NOT NULL,
    `exception` longtext NOT NULL,
    `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELAS PRINCIPAIS DO SISTEMA
-- =====================================================

-- Tabela de empresas/escritórios
CREATE TABLE `companies` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL COMMENT 'Nome da empresa/escritório',
    `slug` varchar(255) NOT NULL COMMENT 'Slug único para URLs',
    `cnpj` varchar(18) DEFAULT NULL COMMENT 'CNPJ da empresa',
    `email` varchar(255) DEFAULT NULL COMMENT 'Email principal da empresa',
    `phone` varchar(255) DEFAULT NULL COMMENT 'Telefone da empresa',
    `address` text DEFAULT NULL COMMENT 'Endereço completo',
    `city` varchar(255) DEFAULT NULL COMMENT 'Cidade',
    `state` varchar(2) DEFAULT NULL COMMENT 'Estado (UF)',
    `zip_code` varchar(10) DEFAULT NULL COMMENT 'CEP',
    `logo_path` varchar(255) DEFAULT NULL COMMENT 'Caminho do logo',
    `settings` json DEFAULT NULL COMMENT 'Configurações específicas da empresa',
    `plan` enum('basic', 'premium', 'enterprise') NOT NULL DEFAULT 'basic' COMMENT 'Plano contratado',
    `max_users` int(11) NOT NULL DEFAULT 5 COMMENT 'Máximo de usuários permitidos',
    `max_cases` int(11) NOT NULL DEFAULT 100 COMMENT 'Máximo de casos permitidos',
    `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Se a empresa está ativa',
    `trial_ends_at` timestamp NULL DEFAULT NULL COMMENT 'Fim do período de teste',
    `subscription_ends_at` timestamp NULL DEFAULT NULL COMMENT 'Fim da assinatura',
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `companies_slug_unique` (`slug`),
    KEY `companies_slug_index` (`slug`),
    KEY `companies_is_active_index` (`is_active`),
    KEY `companies_plan_index` (`plan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de casos jurídicos
CREATE TABLE `cases` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `case_number` varchar(255) NOT NULL,
    `client_name` varchar(255) NOT NULL,
    `client_cpf` varchar(14) NOT NULL,
    `benefit_type` varchar(255) DEFAULT NULL COMMENT 'Tipo de benefício INSS',
    `status` enum('pendente', 'em_coleta', 'aguarda_peticao', 'protocolado', 'concluido', 'rejeitado') NOT NULL DEFAULT 'pendente',
    `description` text DEFAULT NULL,
    `notes` text DEFAULT NULL,
    `workflow_tasks` json DEFAULT NULL,
    `estimated_value` decimal(10,2) DEFAULT NULL,
    `success_fee` decimal(5,2) NOT NULL DEFAULT 20.00 COMMENT 'Percentual de sucesso',
    `filing_date` date DEFAULT NULL,
    `decision_date` date DEFAULT NULL,
    `assigned_to` bigint(20) UNSIGNED DEFAULT NULL,
    `created_by` bigint(20) UNSIGNED NOT NULL,
    `company_id` bigint(20) UNSIGNED DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    `deleted_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `cases_case_number_unique` (`case_number`),
    KEY `cases_assigned_to_foreign` (`assigned_to`),
    KEY `cases_created_by_foreign` (`created_by`),
    KEY `cases_company_id_index` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de vínculos empregatícios
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
    `collected_at` timestamp NULL DEFAULT NULL,
    `cargo` varchar(255) DEFAULT NULL,
    `documentos` text DEFAULT NULL,
    `observacoes` text DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `employment_relationships_case_id_foreign` (`case_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de tentativas de coleta
CREATE TABLE `collection_attempts` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `employment_relationship_id` bigint(20) UNSIGNED NOT NULL,
    `tentativa_num` tinyint(3) UNSIGNED NOT NULL,
    `endereco` varchar(255) DEFAULT NULL,
    `rastreamento` varchar(255) DEFAULT NULL,
    `data_envio` date DEFAULT NULL,
    `retorno` varchar(255) DEFAULT NULL,
    `email` varchar(255) DEFAULT NULL,
    `telefone` varchar(255) DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `collection_attempts_unique` (`employment_relationship_id`, `tentativa_num`),
    KEY `collection_attempts_employment_relationship_id_foreign` (`employment_relationship_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de documentos
CREATE TABLE `documents` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `case_id` bigint(20) UNSIGNED DEFAULT NULL,
    `name` varchar(255) NOT NULL,
    `type` varchar(255) NOT NULL COMMENT 'cnis, medical_report, etc',
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
    KEY `documents_uploaded_by_foreign` (`uploaded_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de processos INSS
CREATE TABLE `inss_processes` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `case_id` bigint(20) UNSIGNED NOT NULL,
    `process_number` varchar(255) NOT NULL,
    `protocol_number` varchar(255) DEFAULT NULL,
    `status` enum('analysis', 'completed', 'requirement', 'rejected', 'appeal') NOT NULL DEFAULT 'analysis',
    `last_movement` text DEFAULT NULL,
    `last_movement_date` date DEFAULT NULL,
    `is_seen` tinyint(1) NOT NULL DEFAULT 0,
    `has_changes` tinyint(1) NOT NULL DEFAULT 0,
    `movements_history` json DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `inss_processes_process_number_unique` (`process_number`),
    KEY `inss_processes_case_id_foreign` (`case_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de petições
CREATE TABLE `petitions` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `case_id` bigint(20) UNSIGNED NOT NULL,
    `title` varchar(255) NOT NULL,
    `content` text NOT NULL,
    `type` varchar(255) NOT NULL COMMENT 'recurso, requerimento, etc',
    `status` enum('draft', 'generated', 'submitted', 'approved') NOT NULL DEFAULT 'draft',
    `file_path` varchar(255) DEFAULT NULL,
    `ai_generation_data` json DEFAULT NULL,
    `created_by` bigint(20) UNSIGNED NOT NULL,
    `user_id` bigint(20) UNSIGNED DEFAULT NULL,
    `template_variables` json DEFAULT NULL,
    `ai_prompt` text DEFAULT NULL,
    `ai_tokens_used` int(11) DEFAULT NULL,
    `generated_at` timestamp NULL DEFAULT NULL,
    `submitted_at` timestamp NULL DEFAULT NULL,
    `notes` text DEFAULT NULL,
    `version` varchar(255) NOT NULL DEFAULT '1.0',
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `petitions_case_id_foreign` (`case_id`),
    KEY `petitions_created_by_foreign` (`created_by`),
    KEY `petitions_user_id_foreign` (`user_id`),
    KEY `petitions_status_created_at_index` (`status`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de templates de petições
CREATE TABLE `petition_templates` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL COMMENT 'Nome do template',
    `category` varchar(255) NOT NULL COMMENT 'Categoria: recurso, requerimento, defesa, etc.',
    `benefit_type` varchar(255) DEFAULT NULL COMMENT 'Tipo de benefício específico',
    `description` text DEFAULT NULL COMMENT 'Descrição do template',
    `content` longtext NOT NULL COMMENT 'Conteúdo do template com placeholders',
    `variables` json DEFAULT NULL COMMENT 'Variáveis disponíveis no template',
    `sections` json DEFAULT NULL COMMENT 'Seções do template',
    `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Se o template está ativo',
    `is_global` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Se é template global',
    `is_default` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Se é template padrão',
    `created_by` bigint(20) UNSIGNED NOT NULL COMMENT 'Usuário que criou',
    `company_id` bigint(20) UNSIGNED DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `petition_templates_category_benefit_type_index` (`category`, `benefit_type`),
    KEY `petition_templates_is_active_index` (`is_active`),
    KEY `petition_templates_created_by_foreign` (`created_by`),
    KEY `petition_templates_company_id_index` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de templates de workflow
CREATE TABLE `workflow_templates` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `benefit_type` varchar(255) NOT NULL COMMENT 'aposentadoria_por_idade, etc.',
    `name` varchar(255) NOT NULL COMMENT 'Nome do template',
    `description` text DEFAULT NULL,
    `tasks` json NOT NULL COMMENT 'Array de tarefas do template',
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    `is_global` tinyint(1) NOT NULL DEFAULT 0,
    `company_id` bigint(20) UNSIGNED DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `workflow_templates_company_id_foreign` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de tarefas
CREATE TABLE `tasks` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `case_id` bigint(20) UNSIGNED NOT NULL,
    `workflow_template_id` bigint(20) UNSIGNED DEFAULT NULL,
    `order` int(11) NOT NULL DEFAULT 0,
    `is_workflow_task` tinyint(1) NOT NULL DEFAULT 0,
    `title` varchar(255) NOT NULL,
    `description` text DEFAULT NULL,
    `status` enum('pending', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    `priority` enum('low', 'medium', 'high', 'urgent') NOT NULL DEFAULT 'medium',
    `due_date` date DEFAULT NULL,
    `completed_at` date DEFAULT NULL,
    `assigned_to` bigint(20) UNSIGNED DEFAULT NULL,
    `created_by` bigint(20) UNSIGNED NOT NULL,
    `required_documents` json DEFAULT NULL,
    `notes` text DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    `deleted_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `tasks_case_id_foreign` (`case_id`),
    KEY `tasks_workflow_template_id_foreign` (`workflow_template_id`),
    KEY `tasks_assigned_to_foreign` (`assigned_to`),
    KEY `tasks_created_by_foreign` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELAS DE ASSINATURA E PAGAMENTOS
-- =====================================================

-- Tabela de planos de assinatura
CREATE TABLE `subscription_plans` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL COMMENT 'Nome do plano (Básico, Premium, Enterprise)',
    `slug` varchar(255) NOT NULL COMMENT 'Slug para identificação',
    `description` text DEFAULT NULL COMMENT 'Descrição do plano',
    `price` decimal(10,2) NOT NULL COMMENT 'Preço do plano',
    `billing_cycle` enum('monthly', 'quarterly', 'annual') NOT NULL COMMENT 'Ciclo de cobrança',
    `max_users` int(11) DEFAULT NULL COMMENT 'Máximo de usuários (null = ilimitado)',
    `max_cases` int(11) DEFAULT NULL COMMENT 'Máximo de casos (null = ilimitado)',
    `features` json DEFAULT NULL COMMENT 'Features do plano em JSON',
    `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Plano ativo',
    `is_featured` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Plano em destaque',
    `trial_days` int(11) NOT NULL DEFAULT 30 COMMENT 'Dias de trial',
    `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT 'Ordem de exibição',
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `subscription_plans_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de assinaturas de empresas
CREATE TABLE `company_subscriptions` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` bigint(20) UNSIGNED NOT NULL,
    `subscription_plan_id` bigint(20) UNSIGNED NOT NULL,
    `status` enum('trial', 'active', 'suspended', 'cancelled', 'expired') NOT NULL DEFAULT 'trial',
    `trial_ends_at` datetime DEFAULT NULL COMMENT 'Fim do período de trial',
    `current_period_start` datetime NOT NULL COMMENT 'Início do período atual',
    `current_period_end` datetime NOT NULL COMMENT 'Fim do período atual',
    `cancelled_at` datetime DEFAULT NULL COMMENT 'Data de cancelamento',
    `ends_at` datetime DEFAULT NULL COMMENT 'Data de término definitivo',
    `amount` decimal(10,2) NOT NULL COMMENT 'Valor da assinatura',
    `currency` varchar(3) NOT NULL DEFAULT 'BRL' COMMENT 'Moeda',
    `metadata` json DEFAULT NULL COMMENT 'Dados adicionais',
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `company_subscriptions_company_id_foreign` (`company_id`),
    KEY `company_subscriptions_subscription_plan_id_foreign` (`subscription_plan_id`),
    KEY `company_subscriptions_company_id_status_index` (`company_id`, `status`),
    KEY `company_subscriptions_current_period_end_index` (`current_period_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de pagamentos
CREATE TABLE `payments` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_subscription_id` bigint(20) UNSIGNED NOT NULL,
    `payment_id` varchar(255) NOT NULL COMMENT 'ID do pagamento no gateway',
    `status` enum('pending', 'processing', 'paid', 'failed', 'cancelled', 'refunded') NOT NULL,
    `payment_method` enum('credit_card', 'debit_card', 'bank_transfer', 'pix', 'boleto') NOT NULL,
    `amount` decimal(10,2) NOT NULL COMMENT 'Valor do pagamento',
    `currency` varchar(3) NOT NULL DEFAULT 'BRL' COMMENT 'Moeda',
    `paid_at` datetime DEFAULT NULL COMMENT 'Data do pagamento',
    `due_date` datetime DEFAULT NULL COMMENT 'Data de vencimento',
    `gateway` varchar(255) DEFAULT NULL COMMENT 'Gateway de pagamento (stripe, mercadopago, etc)',
    `gateway_payment_id` varchar(255) DEFAULT NULL COMMENT 'ID no gateway',
    `gateway_response` json DEFAULT NULL COMMENT 'Resposta do gateway',
    `failure_reason` text DEFAULT NULL COMMENT 'Motivo da falha',
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `payments_payment_id_unique` (`payment_id`),
    KEY `payments_company_subscription_id_foreign` (`company_subscription_id`),
    KEY `payments_status_paid_at_index` (`status`, `paid_at`),
    KEY `payments_due_date_index` (`due_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- CHAVES ESTRANGEIRAS
-- =====================================================

-- Usuários
ALTER TABLE `users`
    ADD CONSTRAINT `users_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

-- Casos
ALTER TABLE `cases`
    ADD CONSTRAINT `cases_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    ADD CONSTRAINT `cases_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
    ADD CONSTRAINT `cases_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

-- Vínculos empregatícios
ALTER TABLE `employment_relationships`
    ADD CONSTRAINT `employment_relationships_case_id_foreign` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE CASCADE;

-- Tentativas de coleta
ALTER TABLE `collection_attempts`
    ADD CONSTRAINT `collection_attempts_employment_relationship_id_foreign` FOREIGN KEY (`employment_relationship_id`) REFERENCES `employment_relationships` (`id`) ON DELETE CASCADE;

-- Documentos
ALTER TABLE `documents`
    ADD CONSTRAINT `documents_case_id_foreign` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `documents_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`);

-- Processos INSS
ALTER TABLE `inss_processes`
    ADD CONSTRAINT `inss_processes_case_id_foreign` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE CASCADE;

-- Petições
ALTER TABLE `petitions`
    ADD CONSTRAINT `petitions_case_id_foreign` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `petitions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
    ADD CONSTRAINT `petitions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

-- Templates de petições
ALTER TABLE `petition_templates`
    ADD CONSTRAINT `petition_templates_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
    ADD CONSTRAINT `petition_templates_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

-- Templates de workflow
ALTER TABLE `workflow_templates`
    ADD CONSTRAINT `workflow_templates_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

-- Tarefas
ALTER TABLE `tasks`
    ADD CONSTRAINT `tasks_case_id_foreign` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `tasks_workflow_template_id_foreign` FOREIGN KEY (`workflow_template_id`) REFERENCES `workflow_templates` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `tasks_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    ADD CONSTRAINT `tasks_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

-- Assinaturas de empresas
ALTER TABLE `company_subscriptions`
    ADD CONSTRAINT `company_subscriptions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `company_subscriptions_subscription_plan_id_foreign` FOREIGN KEY (`subscription_plan_id`) REFERENCES `subscription_plans` (`id`) ON DELETE CASCADE;

-- Pagamentos
ALTER TABLE `payments`
    ADD CONSTRAINT `payments_company_subscription_id_foreign` FOREIGN KEY (`company_subscription_id`) REFERENCES `company_subscriptions` (`id`) ON DELETE CASCADE;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- INSERÇÕES INICIAIS (OPCIONAL)
-- =====================================================

-- Inserir usuário super admin inicial (senha: password)
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Super Admin', 'admin@sistema.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', 1, NOW(), NOW());

-- Inserir planos de assinatura padrão
INSERT INTO `subscription_plans` (`name`, `slug`, `description`, `price`, `billing_cycle`, `max_users`, `max_cases`, `is_active`, `trial_days`, `sort_order`, `created_at`, `updated_at`) VALUES
('Básico', 'basic', 'Plano básico para pequenos escritórios', 99.90, 'monthly', 5, 100, 1, 30, 1, NOW(), NOW()),
('Premium', 'premium', 'Plano premium para escritórios médios', 199.90, 'monthly', 10, 500, 1, 30, 2, NOW(), NOW()),
('Enterprise', 'enterprise', 'Plano enterprise para grandes escritórios', 399.90, 'monthly', 50, 2000, 1, 30, 3, NOW(), NOW());

-- =====================================================
-- FIM DO SCRIPT
-- ===================================================== 