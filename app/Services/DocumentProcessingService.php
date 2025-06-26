<?php

namespace App\Services;

use App\Models\Document;
use App\Models\EmploymentRelationship;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;
use Illuminate\Support\Facades\Log;

class DocumentProcessingService
{
    private DeepSeekService $deepSeekService;
    private GoogleDocumentAiService $googleDocumentAiService;
    private Parser $pdfParser;

    public function __construct(DeepSeekService $deepSeekService, GoogleDocumentAiService $googleDocumentAiService)
    {
        $this->deepSeekService = $deepSeekService;
        $this->googleDocumentAiService = $googleDocumentAiService;
        $this->pdfParser = new Parser();
    }

    public function processDocument(Document $document): array
    {
        try {
            $filePath = Storage::disk('local')->path($document->file_path);
            
            if (!file_exists($filePath)) {
                return [
                    'success' => false,
                    'error' => 'Arquivo não encontrado',
                ];
            }

            $content = $this->extractTextFromFile($filePath, $document->mime_type);
            
            if (empty($content)) {
                return [
                    'success' => false,
                    'error' => 'Não foi possível extrair texto do documento',
                ];
            }

            Log::info('Conteúdo extraído do PDF', ['content_length' => strlen($content)]);

            // Processa baseado no tipo de documento
            switch ($document->type) {
                case 'cnis':
                    return $this->processCNIS($content, $document);
                case 'medical_report':
                    return $this->processMedicalReport($content, $document);
                default:
                    return $this->processGenericDocument($content, $document);
            }

        } catch (\Exception $e) {
            Log::error('Document processing error', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Erro no processamento: ' . $e->getMessage(),
            ];
        }
    }

    private function extractTextFromFile(string $filePath, string $mimeType): string
    {
        Log::info('Extraindo texto do arquivo', ['file' => $filePath, 'mime' => $mimeType]);
        
        if (str_contains($mimeType, 'pdf')) {
            return $this->extractTextFromPDF($filePath);
        }
        
        if (str_contains($mimeType, 'text') || str_contains($mimeType, 'plain')) {
            $content = file_get_contents($filePath);
            Log::info('Texto extraído de arquivo de texto', ['length' => strlen($content)]);
            return $content;
        }
        
        // Para outros tipos, tenta extrair como texto
        $content = file_get_contents($filePath);
        Log::info('Texto extraído como fallback', ['length' => strlen($content)]);
        return $content;
    }

    private function extractTextFromPDF(string $filePath): string
    {
        try {
            $pdf = $this->pdfParser->parseFile($filePath);
            $text = $pdf->getText();
            Log::info('Texto extraído com smalot/pdfparser', ['length' => strlen($text)]);
            // Salva o texto extraído para debug
            file_put_contents(storage_path('app/teste_cnis.txt'), $text);
            return $text;
        } catch (\Exception $e) {
            Log::error('PDF extraction error', [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);
            return '';
        }
    }

    private function processCNIS(string $content, Document $document): array
    {
        Log::info('Processando CNIS', ['content_preview' => substr($content, 0, 500)]);

        // Tenta usar Google Document AI primeiro
        $filePath = Storage::disk('local')->path($document->file_path);
        $googleResult = $this->googleDocumentAiService->processCNIS($filePath);
        
        if ($googleResult['success']) {
            Log::info('Google Document AI processou com sucesso', ['data' => $googleResult['data']]);
            
            $extractedData = $googleResult['data'];
            
            // Se não conseguiu extrair vínculos com Google Document AI, tenta com método tradicional
            if (empty($extractedData['vinculos_empregaticios'])) {
                Log::info('Google Document AI não extraiu vínculos, usando método tradicional');
                $extractedData['vinculos_empregaticios'] = $this->extractEmploymentData($content);
            }
            
            // Se não conseguiu extrair dados pessoais, tenta com método tradicional
            if (empty($extractedData['dados_pessoais'])) {
                Log::info('Google Document AI não extraiu dados pessoais, usando método tradicional');
                $extractedData['dados_pessoais'] = $this->extractPersonalData($content);
            }
        } else {
            Log::warning('Google Document AI falhou, usando método tradicional', ['error' => $googleResult['error']]);
            
            // Fallback: usa método tradicional
            $extractedData = [
                'dados_pessoais' => $this->extractPersonalData($content),
                'vinculos_empregaticios' => $this->extractEmploymentData($content),
                'beneficios' => [],
                'observacoes' => [],
            ];
        }

        Log::info('Dados extraídos do CNIS', ['extracted_data' => $extractedData]);

        $document->update([
            'extracted_data' => $extractedData,
            'is_processed' => true,
        ]);

        if (!empty($extractedData['vinculos_empregaticios'])) {
            $this->createEmploymentRelationships($document->case_id, $extractedData['vinculos_empregaticios']);
        }

        return [
            'success' => true,
            'data' => [
                'client_name' => $extractedData['dados_pessoais']['nome'] ?? '',
                'client_cpf' => $extractedData['dados_pessoais']['cpf'] ?? '',
                'benefit_type' => $this->suggestBenefitType(['vinculos_empregaticios' => $extractedData['vinculos_empregaticios']]),
                'vinculos_empregaticios' => $extractedData['vinculos_empregaticios'],
            ],
            'employment_relationships_created' => !empty($extractedData['vinculos_empregaticios']),
        ];
    }

    private function extractPersonalData(string $content): array
    {
        $data = [];
        
        // Padrões mais robustos para CPF
        $cpfPatterns = [
            '/CPF[:\s]*(\d{3}\.\d{3}\.\d{3}-\d{2})/',
            '/(\d{3}\.\d{3}\.\d{3}-\d{2})/',
            '/CPF[:\s]*(\d{11})/',
            '/(\d{11})/',
        ];
        
        foreach ($cpfPatterns as $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                $cpf = $matches[1];
                // Formata CPF se necessário
                if (strlen($cpf) === 11) {
                    $cpf = substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
                }
                $data['cpf'] = $cpf;
                break;
            }
        }
        
        // Padrões para nome
        $nomePatterns = [
            '/NOME[:\s]*([^\n\r]+)/i',
            '/CLIENTE[:\s]*([^\n\r]+)/i',
            '/SERVIDOR[:\s]*([^\n\r]+)/i',
            '/BENEFICIÁRIO[:\s]*([^\n\r]+)/i',
            '/TITULAR[:\s]*([^\n\r]+)/i',
        ];
        
        foreach ($nomePatterns as $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                $nome = trim($matches[1]);
                // Remove caracteres especiais e números
                $nome = preg_replace('/[0-9\-\_\.]/', '', $nome);
                $nome = trim($nome);
                if (strlen($nome) > 3) {
                    $data['nome'] = $nome;
                    break;
                }
            }
        }
        
        // Data de nascimento
        $nascPatterns = [
            '/NASCIMENTO[:\s]*(\d{2}\/\d{2}\/\d{4})/',
            '/NASC[:\s]*(\d{2}\/\d{2}\/\d{4})/',
            '/DATA[:\s]*NASC[:\s]*(\d{2}\/\d{2}\/\d{4})/',
        ];
        
        foreach ($nascPatterns as $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                $data['data_nascimento'] = $matches[1];
                break;
            }
        }
        
        Log::info('Dados pessoais extraídos', ['data' => $data]);
        return $data;
    }

    private function extractEmploymentData(string $content): array
    {
        $employments = [];
        $lines = preg_split('/\r\n|\r|\n/', $content);
        $tiposVinculo = ['Empregado', 'Contribuinte Individual', 'Trabalhador', 'Público', 'Cooperativa'];
        for ($i = 0; $i < count($lines); $i++) {
            if (stripos($lines[$i], 'Código Emp.') !== false) {
                $j = $i + 1;
                while ($j < count($lines) && stripos($lines[$j], 'Código Emp.') === false) {
                    if (preg_match('/\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}/', $lines[$j], $cnpjMatch)) {
                        $line = $lines[$j];
                        $empregador = trim(preg_replace('/^.*?\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}\s*/', '', $line));
                        if (empty($empregador) && isset($lines[$j+1])) {
                            $empregador = trim($lines[$j+1]);
                        }
                        // Busca tipo de vínculo nas próximas 1-5 linhas
                        $tipoVinculoValido = false;
                        for ($k = 1; $k <= 5; $k++) {
                            if (isset($lines[$j+$k])) {
                                foreach ($tiposVinculo as $tipo) {
                                    if (stripos($lines[$j+$k], $tipo) !== false) {
                                        $tipoVinculoValido = true;
                                        break 2;
                                    }
                                }
                            }
                        }
                        if (!$tipoVinculoValido || stripos($empregador, 'Não Cooperado') !== false || preg_match('/^[0-9,.\/-]+$/', $empregador)) {
                            $j++;
                            continue;
                        }
                        $data_inicio = null;
                        $data_fim = null;
                        if (isset($lines[$j+2])) {
                            if (preg_match_all('/(\d{2}\/\d{2}\/\d{4}|\d{2}\/\d{4})/', $lines[$j+2], $dateMatches)) {
                                $datas = $dateMatches[0];
                                if (count($datas) > 0) $data_inicio = $datas[0];
                                if (count($datas) > 1) $data_fim = $datas[1];
                            }
                        }
                        if ($empregador && $data_inicio) {
                            $employments[] = [
                                'empregador' => $empregador,
                                'data_inicio' => $data_inicio,
                                'data_fim' => $data_fim,
                            ];
                        }
                    }
                    $j++;
                }
                $i = $j - 1;
            }
        }
        return $employments;
    }

    private function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('d/m/Y', $date);
        return $d && $d->format('d/m/Y') === $date;
    }

    private function extractBenefitsData(string $content): array
    {
        $benefits = [];
        
        // Extrai informações sobre benefícios
        if (preg_match_all('/BENEFÍCIO[:\s]*([^\n\r]+)/i', $content, $matches)) {
            foreach ($matches[1] as $benefit) {
                $benefits[] = trim($benefit);
            }
        }
        
        return $benefits;
    }

    private function createEmploymentRelationships(?int $caseId, array $employments): void
    {
        if (!$caseId) return;
        
        foreach ($employments as $employment) {
            EmploymentRelationship::create([
                'case_id' => $caseId,
                'employer_name' => $employment['empregador'],
                'start_date' => $this->parseDate($employment['data_inicio']),
                'end_date' => $employment['data_fim'] ? $this->parseDate($employment['data_fim']) : null,
                'salary' => (float) str_replace(',', '.', $employment['salario']),
            ]);
        }
    }

    private function parseDate(string $dateString): ?string
    {
        $date = \DateTime::createFromFormat('d/m/Y', $dateString);
        return $date ? $date->format('Y-m-d') : null;
    }

    private function suggestBenefitType(array $extractedData): string
    {
        // Lógica simples para sugerir tipo de benefício baseado nos dados
        $vinculos = $extractedData['vinculos_empregaticios'] ?? [];
        $totalContribuicao = 0;
        
        foreach ($vinculos as $vinculo) {
            $inicio = \DateTime::createFromFormat('d/m/Y', $vinculo['data_inicio']);
            $fim = $vinculo['data_fim'] ? \DateTime::createFromFormat('d/m/Y', $vinculo['data_fim']) : new \DateTime();
            
            if ($inicio && $fim) {
                $diff = $inicio->diff($fim);
                $totalContribuicao += $diff->y + ($diff->m / 12) + ($diff->d / 365);
            }
        }
        
        // Sugestões baseadas no tempo de contribuição
        if ($totalContribuicao >= 35) {
            return 'aposentadoria_por_tempo_contribuicao';
        } elseif ($totalContribuicao >= 30) {
            return 'aposentadoria_professor';
        } else {
            return 'aposentadoria_por_idade';
        }
    }

    private function processMedicalReport(string $content, Document $document): array
    {
        // Implementação para laudos médicos
        $extractedData = [
            'diagnostico' => '',
            'medico' => '',
            'data_exame' => '',
            'observacoes' => '',
        ];

        $document->update([
            'extracted_data' => $extractedData,
            'is_processed' => true,
        ]);

        return [
            'success' => true,
            'data' => $extractedData,
        ];
    }

    private function processGenericDocument(string $content, Document $document): array
    {
        $extractedData = [
            'content' => $content,
            'word_count' => str_word_count($content),
            'extracted_at' => now()->toISOString(),
        ];

        $document->update([
            'extracted_data' => $extractedData,
            'is_processed' => true,
        ]);

        return [
            'success' => true,
            'data' => $extractedData,
        ];
    }
} 