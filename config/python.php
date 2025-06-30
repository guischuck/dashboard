<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Python Configuration
    |--------------------------------------------------------------------------
    |
    | Configurações para execução de scripts Python no Laravel.
    |
    */

    'executable' => env('PYTHON_EXECUTABLE', 'python'),

    /*
    |--------------------------------------------------------------------------
    | Scripts Python
    |--------------------------------------------------------------------------
    |
    | Caminhos para os scripts Python utilizados pelo sistema.
    |
    */

    'scripts' => [
        'cnis_extractor' => base_path('python_cnis_extractor.py'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Execução
    |--------------------------------------------------------------------------
    |
    | Configurações para execução de comandos Python.
    |
    */

    'execution' => [
        // Timeout para execução de comandos Python (em segundos)
        'timeout' => env('PYTHON_EXECUTION_TIMEOUT', 300),

        // Diretório de trabalho para execução
        'working_directory' => base_path(),

        // Variáveis de ambiente adicionais
        'environment' => [
            'PYTHONPATH' => base_path(),
            'PYTHONUNBUFFERED' => '1',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Log
    |--------------------------------------------------------------------------
    |
    | Configurações para logging de execução Python.
    |
    */

    'logging' => [
        'enabled' => env('PYTHON_LOGGING', true),
        'level' => env('PYTHON_LOG_LEVEL', 'info'),
        'include_command' => env('PYTHON_LOG_COMMAND', false),
        'include_output' => env('PYTHON_LOG_OUTPUT', false),
    ],
]; 