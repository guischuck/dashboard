<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class GoogleDocumentAiService
{
    private string $projectId;
    private string $location;
    private string $processorId;

    public function __construct()
    {
        // Configuração do Google Cloud (para uso futuro)
        $this->projectId = 'cnis-document-ai';
        $this->location = 'us';
        $this->processorId = 'ocr-processor';
        
        // Configura o cliente com o arquivo de credenciais
        $credentialsPath = base_path('cnis-document-ai-3a6322737b0c.json');
        
        if (!file_exists($credentialsPath)) {
            Log::warning('Arquivo de credenciais do Google Cloud não encontrado, usando fallback');
        } else {
            putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $credentialsPath);
        }
        
        // Por enquanto, não inicializamos o cliente
        // $this->client = new DocumentAiServiceClient();
    }

    public function processCNIS(string $filePath): array
    {
        try {
            Log::info('Iniciando processamento com Google Document AI', ['file' => $filePath]);

            // Lê o arquivo
            $fileContent = file_get_contents($filePath);
            if (!$fileContent) {
                throw new \Exception('Não foi possível ler o arquivo');
            }

            // Por enquanto, vamos usar o método tradicional de extração
            // e simular que o Google Document AI funcionou
            Log::info('Simulando processamento do Google Document AI');
            
            // Extrai texto do PDF usando o parser existente
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();
            
            // Extrai dados usando métodos tradicionais
            $extractedData = [
                'dados_pessoais' => $this->extractPersonalDataFromText($text),
                'vinculos_empregaticios' => $this->extractEmploymentDataFromText($text),
                'beneficios' => [],
            ];

            return [
                'success' => true,
                'data' => $extractedData,
            ];

        } catch (\Exception $e) {
            Log::error('Erro no Google Document AI', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Erro no Google Document AI: ' . $e->getMessage(),
            ];
        }
    }

    private function extractPersonalDataFromText(string $text): array
    {
        $personalData = [];
        
        // Padrões para CPF
        $cpfPatterns = [
            '/CPF[:\s]*(\d{3}\.\d{3}\.\d{3}-\d{2})/',
            '/NIT[:\s]*\d+\.\d+\s+CPF[:\s]*(\d{3}\.\d{3}\.\d{3}-\d{2})/',
            '/(\d{3}\.\d{3}\.\d{3}-\d{2})/',
        ];
        
        foreach ($cpfPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $personalData['cpf'] = $matches[1];
                break;
            }
        }
        
        // Padrões para nome
        $nomePatterns = [
            '/CPF[:\s]*\d{3}\.\d{3}\.\d{3}-\d{2}\s+Nome[:\s]*([^\n\r]+)/i',
            '/Nome[:\s]*([^\n\r]+)/i',
            '/CPF[:\s]*\d{3}\.\d{3}\.\d{3}-\d{2}\s+([A-Z\s]+)/',
        ];
        
        foreach ($nomePatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $nome = trim($matches[1]);
                // Remove caracteres especiais e números
                $nome = preg_replace('/[0-9\-\_\.]/', '', $nome);
                $nome = trim($nome);
                if (strlen($nome) > 3 && !preg_match('/^\d+$/', $nome)) {
                    $personalData['nome'] = $nome;
                    break;
                }
            }
        }
        
        // Data de nascimento
        $nascPatterns = [
            '/Data de nascimento[:\s]*(\d{2}\/\d{2}\/\d{4})/',
            '/Nascimento[:\s]*(\d{2}\/\d{2}\/\d{4})/',
        ];
        
        foreach ($nascPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $personalData['data_nascimento'] = $matches[1];
                break;
            }
        }
        
        return $personalData;
    }

    private function extractEmploymentDataFromText(string $text): array
    {
        $employments = [];
        
        // Divide o texto em seções por vínculo
        $sections = $this->splitIntoEmploymentSections($text);
        
        foreach ($sections as $section) {
            $employment = $this->extractEmploymentFromSection($section);
            if ($employment && !empty($employment['empregador'])) {
                $employments[] = $employment;
            }
        }
        
        return $employments;
    }

    private function splitIntoEmploymentSections(string $text): array
    {
        $sections = [];
        $lines = explode("\n", $text);
        $currentSection = [];
        $inEmploymentSection = false;
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Identifica início de uma seção de vínculo
            if (preg_match('/^Código Emp\./', $line) || 
                preg_match('/^\d+\s+\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}/', $line) ||
                preg_match('/^\d+\s+AGRUPAMENTO/', $line)) {
                
                // Salva a seção anterior se existir
                if (!empty($currentSection)) {
                    $sections[] = implode("\n", $currentSection);
                }
                
                $currentSection = [$line];
                $inEmploymentSection = true;
            } elseif ($inEmploymentSection) {
                $currentSection[] = $line;
                
                // Identifica fim da seção (próximo vínculo ou fim do documento)
                if (preg_match('/^Relações Previdenciárias/', $line) ||
                    preg_match('/^Valores Consolidados/', $line)) {
                    $sections[] = implode("\n", $currentSection);
                    $currentSection = [];
                    $inEmploymentSection = false;
                }
            }
        }
        
        // Adiciona a última seção
        if (!empty($currentSection)) {
            $sections[] = implode("\n", $currentSection);
        }
        
        return $sections;
    }

    private function extractEmploymentFromSection(string $section): ?array
    {
        $lines = explode("\n", $section);
        $employment = [
            'empregador' => '',
            'cnpj' => '',
            'data_inicio' => '',
            'data_fim' => '',
            'salario' => '',
            'ultima_remuneracao' => '',
        ];
        
        Log::info('Processando seção de vínculo', ['section' => $section]);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Extrai empregador e CNPJ
            if (preg_match('/^\d+\s+(\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2})\s+(.+)$/', $line, $matches)) {
                $employment['cnpj'] = $matches[1];
                $employment['empregador'] = trim($matches[2]);
                Log::info('Empregador encontrado', ['empregador' => $employment['empregador'], 'cnpj' => $employment['cnpj']]);
            } elseif (preg_match('/^\d+\s+(AGRUPAMENTO.+)$/', $line, $matches)) {
                $empregador = trim($matches[1]);
                // Remove "Contribuinte Individual" e CNPJ do nome
                $empregador = preg_replace('/\tContribuinte Individual.*$/', '', $empregador);
                $empregador = preg_replace('/\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}/', '', $empregador);
                $empregador = trim($empregador);
                
                $employment['empregador'] = $empregador;
                $employment['cnpj'] = '';
                Log::info('Agrupamento encontrado', ['empregador' => $employment['empregador']]);
            }
            
            // Extrai datas de início e fim - padrão mais específico
            if (preg_match('/(\d{2}\/\d{2}\/\d{4})\s+(\d{2}\/\d{2}\/\d{4})/', $line, $matches)) {
                $employment['data_inicio'] = $matches[1];
                $employment['data_fim'] = $matches[2];
                Log::info('Duas datas completas encontradas', ['inicio' => $employment['data_inicio'], 'fim' => $employment['data_fim']]);
            } elseif (preg_match('/(\d{2}\/\d{2}\/\d{4})\s+(\d{2}\/\d{4})/', $line, $matches)) {
                // Data de início completa + data de fim em formato MM/YYYY
                $employment['data_inicio'] = $matches[1];
                $employment['data_fim'] = $this->convertMonthYearToFullDate($matches[2]);
                Log::info('Data completa + MM/YYYY encontradas', ['inicio' => $employment['data_inicio'], 'fim' => $employment['data_fim']]);
            } elseif (preg_match('/(\d{2}\/\d{2}\/\d{4})/', $line, $matches)) {
                // Se só encontrou uma data, pode ser início ou fim
                if (empty($employment['data_inicio'])) {
                    $employment['data_inicio'] = $matches[1];
                    Log::info('Data de início encontrada', ['inicio' => $employment['data_inicio']]);
                } elseif (empty($employment['data_fim'])) {
                    $employment['data_fim'] = $matches[1];
                    Log::info('Data de fim encontrada', ['fim' => $employment['data_fim']]);
                }
            }
            
            // Extrai última remuneração
            if (preg_match('/Últ\. Remun\.\s*(\d{2}\/\d{4})/', $line, $matches)) {
                $employment['ultima_remuneracao'] = $matches[1];
                Log::info('Última remuneração encontrada', ['ultima_remuneracao' => $employment['ultima_remuneracao']]);
            }
        }
        
        // Se não tem data de fim, usa "sem data fim"
        if (empty($employment['data_fim'])) {
            $employment['data_fim'] = 'sem data fim';
            Log::info('Data de fim definida como "sem data fim"');
        }
        
        // Extrai salário da seção de remunerações
        $employment['salario'] = $this->extractSalaryFromSection($section);
        
        Log::info('Vínculo processado', ['employment' => $employment]);
        
        return $employment;
    }

    private function calculateEndDateFromLastRemuneration(string $lastRemuneration): string
    {
        // Formato: MM/YYYY
        if (preg_match('/(\d{2})\/(\d{4})/', $lastRemuneration, $matches)) {
            $month = (int)$matches[1];
            $year = (int)$matches[2];
            
            // Último dia do mês
            $lastDay = date('t', mktime(0, 0, 0, $month, 1, $year));
            
            return sprintf('%02d/%02d/%04d', $lastDay, $month, $year);
        }
        
        return 'Atual';
    }

    private function extractSalaryFromSection(string $section): string
    {
        $lines = explode("\n", $section);
        $lastSalary = '';
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Procura por remunerações no formato MM/YYYY VALOR
            if (preg_match('/\d{2}\/\d{4}\s+([\d\.,]+)/', $line, $matches)) {
                $salary = $matches[1];
                // Converte para formato numérico
                $salary = str_replace(['.', ','], ['', '.'], $salary);
                if (is_numeric($salary)) {
                    $lastSalary = number_format((float)$salary, 2, ',', '.');
                }
            }
        }
        
        return $lastSalary;
    }

    private function convertMonthYearToFullDate(string $monthYear): string
    {
        // Converte MM/YYYY para o último dia do mês
        if (preg_match('/(\d{2})\/(\d{4})/', $monthYear, $matches)) {
            $month = (int)$matches[1];
            $year = (int)$matches[2];
            
            // Último dia do mês
            $lastDay = date('t', mktime(0, 0, 0, $month, 1, $year));
            
            return sprintf('%02d/%02d/%04d', $lastDay, $month, $year);
        }
        
        return $monthYear; // Retorna como está se não conseguir converter
    }
} 