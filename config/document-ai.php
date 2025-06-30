<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Google Document AI Configuration
    |--------------------------------------------------------------------------
    |
    | Configurações para o Google Document AI, incluindo processadores
    | específicos para documentos brasileiros como CNIS.
    |
    */

    'project_id' => env('GOOGLE_DOCUMENT_AI_PROJECT_ID', 'cnis-document-ai'),
    'location' => env('GOOGLE_DOCUMENT_AI_LOCATION', 'us'),
    'credentials_path' => env('GOOGLE_APPLICATION_CREDENTIALS', base_path('cnis-document-ai-3a6322737b0c.json')),

    /*
    |--------------------------------------------------------------------------
    | Processadores do Google Document AI
    |--------------------------------------------------------------------------
    |
    | IDs dos processadores específicos para diferentes tipos de documentos.
    | Para documentos brasileiros, usamos processadores que funcionam bem
    | com português e formatos brasileiros.
    |
    */

    'processors' => [
        // Processador OCR genérico para extrair texto
        'ocr' => [
            'id' => env('GOOGLE_DOCUMENT_AI_OCR_PROCESSOR_ID', 'ocr-processor'),
            'description' => 'Processador OCR para extração de texto de documentos',
        ],

        // Processador de formulários para extrair campos estruturados
        'form_parser' => [
            'id' => env('GOOGLE_DOCUMENT_AI_FORM_PROCESSOR_ID', 'form-parser'),
            'description' => 'Processador de formulários para extrair campos estruturados',
        ],

        // Processador de extração de entidades para identificar CPFs, CNPJs, datas
        'entity_extractor' => [
            'id' => env('GOOGLE_DOCUMENT_AI_ENTITY_PROCESSOR_ID', 'entity-extractor'),
            'description' => 'Processador de extração de entidades (CPF, CNPJ, datas)',
        ],

        // Processador específico para documentos brasileiros
        'brazilian_document' => [
            'id' => env('GOOGLE_DOCUMENT_AI_BRAZILIAN_PROCESSOR_ID', 'brazilian-document-processor'),
            'description' => 'Processador específico para documentos brasileiros',
        ],

        // Processador de tabelas para extrair dados tabulares
        'table_extractor' => [
            'id' => env('GOOGLE_DOCUMENT_AI_TABLE_PROCESSOR_ID', 'table-extractor'),
            'description' => 'Processador para extrair tabelas e dados estruturados',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Processamento
    |--------------------------------------------------------------------------
    |
    | Configurações específicas para o processamento de documentos CNIS.
    |
    */

    'processing' => [
        // Timeout para processamento (em segundos)
        'timeout' => env('GOOGLE_DOCUMENT_AI_TIMEOUT', 60),

        // Número máximo de tentativas
        'max_retries' => env('GOOGLE_DOCUMENT_AI_MAX_RETRIES', 3),

        // Configurações específicas para CNIS
        'cnis' => [
            // Campos específicos do CNIS que queremos extrair
            'fields' => [
                'cpf' => 'CPF do contribuinte',
                'nome' => 'Nome do contribuinte',
                'data_nascimento' => 'Data de nascimento',
                'empregador' => 'Nome do empregador',
                'cnpj' => 'CNPJ do empregador',
                'data_inicio' => 'Data de início do vínculo',
                'data_fim' => 'Data de fim do vínculo',
                'salario' => 'Salário/remuneração',
                'ultima_remuneracao' => 'Última remuneração',
            ],

            // Padrões de regex como fallback (caso o Google Document AI falhe)
            'fallback_patterns' => [
                'cpf' => '/\b\d{3}\.\d{3}\.\d{3}-\d{2}\b/',
                'cnpj' => '/\b\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}\b/',
                'data' => '/\b\d{2}\/\d{2}\/\d{4}\b/',
                'data_mm_yyyy' => '/\b\d{2}\/\d{4}\b/',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Log
    |--------------------------------------------------------------------------
    |
    | Configurações para logging do processamento.
    |
    */

    'logging' => [
        'enabled' => env('GOOGLE_DOCUMENT_AI_LOGGING', true),
        'level' => env('GOOGLE_DOCUMENT_AI_LOG_LEVEL', 'info'),
        'include_raw_results' => env('GOOGLE_DOCUMENT_AI_LOG_RAW_RESULTS', false),
    ],
]; 