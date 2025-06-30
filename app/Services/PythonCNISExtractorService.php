<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class PythonCNISExtractorService
{
    private string $pythonScriptPath;
    private string $pythonExecutable;

    public function __construct()
    {
        $this->pythonScriptPath = base_path('simple_cnis_extractor.py');
        $this->pythonExecutable = Config::get('python.executable', 'python');
    }

    public function processCNIS(string $filePath): array
    {
        try {
            Log::info('Iniciando processamento com Python CNIS Extractor', ['file' => $filePath]);

            // Verifica se o arquivo existe
            if (!file_exists($filePath)) {
                throw new \Exception('Arquivo não encontrado: ' . $filePath);
            }

            // Verifica se o script Python existe
            if (!file_exists($this->pythonScriptPath)) {
                throw new \Exception('Script Python não encontrado: ' . $this->pythonScriptPath);
            }

            // Executa o script Python
            $result = $this->executePythonScript($filePath);

            if (!$result['success']) {
                Log::error('Erro na execução do script Python', ['error' => $result['error']]);
                return [
                    'success' => false,
                    'error' => 'Erro no Python CNIS Extractor: ' . $result['error'],
                ];
            }

            // Decodifica o resultado JSON
            // Limpa a saída para pegar apenas o JSON
            if (preg_match('/({.*})/s', $result['output'], $matches)) {
                $json = $matches[1];
            } else {
                $json = $result['output'];
            }
            $extractedData = json_decode($json, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Erro ao decodificar JSON: ' . json_last_error_msg() . ' | Saída: ' . $result['output']);
            }

            Log::info('Processamento Python concluído com sucesso', [
                'vinculos_count' => count($extractedData['data']['vinculos_empregaticios'] ?? []),
                'text_length' => $extractedData['text_length'] ?? 0,
            ]);

            return [
                'success' => true,
                'data' => $extractedData['data'],
                'metadata' => [
                    'text_length' => $extractedData['text_length'] ?? 0,
                    'method' => 'python_extractor',
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Erro no Python CNIS Extractor', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Erro no Python CNIS Extractor: ' . $e->getMessage(),
            ];
        }
    }

    private function executePythonScript(string $filePath): array
    {
        // Escapa o caminho do arquivo para segurança
        $escapedFilePath = escapeshellarg($filePath);
        $escapedScriptPath = escapeshellarg($this->pythonScriptPath);

        // Comando para executar o script Python
        $command = "{$this->pythonExecutable} {$escapedScriptPath} {$escapedFilePath} 2>&1";

        Log::info('Executando comando Python', ['command' => $command]);

        // Executa o comando
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        $outputString = implode("\n", $output);

        Log::info('Resultado da execução Python', [
            'return_code' => $returnCode,
            'output_length' => strlen($outputString),
        ]);

        if ($returnCode !== 0) {
            return [
                'success' => false,
                'error' => "Erro na execução (código {$returnCode}): {$outputString}",
            ];
        }

        return [
            'success' => true,
            'output' => $outputString,
        ];
    }

    public function checkPythonEnvironment(): array
    {
        $checks = [
            'python_executable' => false,
            'python_script' => false,
            'required_modules' => [],
        ];

        // Verifica se o Python está disponível
        $output = [];
        $returnCode = 0;
        exec("{$this->pythonExecutable} --version 2>&1", $output, $returnCode);

        if ($returnCode === 0) {
            $checks['python_executable'] = true;
            $checks['python_version'] = $output[0] ?? 'Unknown';
        }

        // Verifica se o script existe
        if (file_exists($this->pythonScriptPath)) {
            $checks['python_script'] = true;
        }

        // Verifica módulos Python necessários
        $requiredModules = ['re', 'json', 'logging', 'PyPDF2', 'pdfplumber'];
        foreach ($requiredModules as $module) {
            $output = [];
            $returnCode = 0;
            exec("{$this->pythonExecutable} -c \"import {$module}\" 2>&1", $output, $returnCode);
            $checks['required_modules'][$module] = $returnCode === 0;
        }

        return $checks;
    }

    public function installDependencies(): array
    {
        try {
            $requirementsPath = base_path('requirements_simple.txt');
            
            if (!file_exists($requirementsPath)) {
                return [
                    'success' => false,
                    'error' => 'Arquivo requirements_simple.txt não encontrado',
                ];
            }

            $escapedRequirementsPath = escapeshellarg($requirementsPath);
            $command = "{$this->pythonExecutable} -m pip install -r {$escapedRequirementsPath} 2>&1";

            Log::info('Instalando dependências Python', ['command' => $command]);

            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);

            $outputString = implode("\n", $output);

            if ($returnCode !== 0) {
                return [
                    'success' => false,
                    'error' => "Erro na instalação (código {$returnCode}): {$outputString}",
                ];
            }

            return [
                'success' => true,
                'output' => $outputString,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erro na instalação: ' . $e->getMessage(),
            ];
        }
    }
} 