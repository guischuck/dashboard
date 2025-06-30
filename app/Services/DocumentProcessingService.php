<?php

namespace App\Services;

use App\Models\Document;
use App\Models\EmploymentRelationship;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser as PdfParser;

class DocumentProcessingService
{
    private PdfParser $pdfParser;
    private GoogleDocumentAiService $googleDocumentAiService;
    private PythonCNISExtractorService $pythonCNISExtractorService;

    public function __construct(
        GoogleDocumentAiService $googleDocumentAiService,
        PythonCNISExtractorService $pythonCNISExtractorService
    ) {
        $this->pdfParser = new PdfParser();
        $this->googleDocumentAiService = $googleDocumentAiService;
        $this->pythonCNISExtractorService = $pythonCNISExtractorService;
    }

    public function processDocument(Document $document): array
    {
        try {
            Log::info('Iniciando processamento do documento', ['document_id' => $document->id, 'type' => $document->type]);

            $filePath = Storage::disk('public')->path($document->file_path);
            
            if (!file_exists($filePath)) {
                throw new \Exception('Arquivo não encontrado: ' . $filePath);
            }

            $content = $this->extractTextFromFile($filePath, $document->mime_type);

            switch ($document->type) {
                case 'cnis':
                    return $this->processCNIS($content, $document);
                
                case 'medical_report':
                    return $this->processMedicalReport($content, $document);
                
                default:
                    return $this->processGenericDocument($content, $document);
            }

        } catch (\Exception $e) {
            Log::error('Erro no processamento do documento', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
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
            
            // Salva para debug
            file_put_contents(storage_path('app/debug_cnis.txt'), $text);
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

        $filePath = Storage::disk('public')->path($document->file_path);
        
        // Tenta primeiro com Python
        $pythonResult = $this->pythonCNISExtractorService->processCNIS($filePath);
        
        if ($pythonResult['success']) {
            Log::info('Python CNIS Extractor processou com sucesso', ['data' => $pythonResult['data']]);
            
            $extractedData = $pythonResult['data'];
            
            // Validação e fallback para vínculos empregatícios
            if (empty($extractedData['vinculos_empregaticios'])) {
                Log::info('Python não extraiu vínculos, usando Google Document AI');
                $googleResult = $this->googleDocumentAiService->processCNIS($filePath);
                if ($googleResult['success'] && !empty($googleResult['data']['vinculos_empregaticios'])) {
                    $extractedData['vinculos_empregaticios'] = $googleResult['data']['vinculos_empregaticios'];
                } else {
                    Log::info('Google Document AI também falhou, usando método tradicional');
                    $extractedData['vinculos_empregaticios'] = $this->extractEmploymentDataImproved($content);
                }
            }
            
            // Validação e fallback para dados pessoais
            if (empty($extractedData['client_name']) && empty($extractedData['dados_pessoais'])) {
                Log::info('Python não extraiu dados pessoais, usando método tradicional');
                $extractedData['dados_pessoais'] = $this->extractPersonalDataImproved($content);
            }
        } else {
            Log::warning('Python CNIS Extractor falhou, tentando Google Document AI', ['error' => $pythonResult['error']]);
            
            // Fallback para Google Document AI
            $googleResult = $this->googleDocumentAiService->processCNIS($filePath);
            
            if ($googleResult['success']) {
                Log::info('Google Document AI processou com sucesso como fallback');
                $extractedData = $googleResult['data'];
                
                // Validação e fallback para vínculos empregatícios
                if (empty($extractedData['vinculos_empregaticios'])) {
                    Log::info('Google Document AI não extraiu vínculos, usando método tradicional');
                    $extractedData['vinculos_empregaticios'] = $this->extractEmploymentDataImproved($content);
                }
                
                // Validação e fallback para dados pessoais
                if (empty($extractedData['client_name']) && empty($extractedData['dados_pessoais'])) {
                    Log::info('Google Document AI não extraiu dados pessoais, usando método tradicional');
                    $extractedData['dados_pessoais'] = $this->extractPersonalDataImproved($content);
                }
            } else {
                Log::warning('Google Document AI também falhou, usando método tradicional', ['error' => $googleResult['error']]);
                
                $extractedData = [
                    'dados_pessoais' => $this->extractPersonalDataImproved($content),
                    'vinculos_empregaticios' => $this->extractEmploymentDataImproved($content),
                    'beneficios' => $this->extractBenefitsImproved($content),
                    'observacoes' => [],
                ];
            }
        }

        Log::info('Dados extraídos do CNIS', ['extracted_data' => $extractedData]);

        $document->update([
            'extracted_data' => $extractedData,
            'is_processed' => true,
        ]);

        if (!empty($extractedData['vinculos_empregaticios']) && $document->case_id) {
            $this->createEmploymentRelationships($document->case_id, $extractedData['vinculos_empregaticios']);
        }

        return [
            'success' => true,
            'data' => [
                'client_name' => $extractedData['client_name'] ?? $extractedData['dados_pessoais']['nome'] ?? '',
                'client_cpf' => $extractedData['client_cpf'] ?? $extractedData['dados_pessoais']['cpf'] ?? '',
                'benefit_type' => $this->suggestBenefitType(['vinculos_empregaticios' => $extractedData['vinculos_empregaticios']]),
                'vinculos_empregaticios' => $extractedData['vinculos_empregaticios'],
            ],
            'employment_relationships_created' => !empty($extractedData['vinculos_empregaticios']),
        ];
    }

    private function extractPersonalDataImproved(string $content): array
    {
        $data = [];
        
        // CPF - padrão específico do CNIS
        if (preg_match('/CPF:\s*(\d{3}\.\d{3}\.\d{3}-\d{2})/', $content, $matches)) {
            $data['cpf'] = $matches[1];
        }
        
        // Nome - padrão melhorado
        if (preg_match('/Nome:\s*([A-Z\s]+?)(?=Data de nascimento|Nome da mãe|\n)/i', $content, $matches)) {
            $data['nome'] = trim($matches[1]);
        }
        
        // Data de nascimento
        if (preg_match('/Data de nascimento:\s*(\d{2}\/\d{2}\/\d{4})/', $content, $matches)) {
            $data['data_nascimento'] = $matches[1];
        }
        
        // Nome da mãe
        if (preg_match('/Nome da mãe:\s*([A-Z\s]+)/i', $content, $matches)) {
            $data['nome_mae'] = trim($matches[1]);
        }
        
        // NIT
        if (preg_match('/NIT:\s*(\d{3}\.\d{5}\.\d{2}-\d)/', $content, $matches)) {
            $data['nit'] = $matches[1];
        }
        
        Log::info('Dados pessoais extraídos (método aprimorado)', $data);
        return $data;
    }

    private function extractEmploymentDataImproved(string $content): array
    {
        $employments = [];
        
        // Normaliza o texto para facilitar extração
        $lines = preg_split('/\r\n|\r|\n/', $content);
        $normalizedContent = implode(' ', $lines);
        
        // Busca por seções de relações previdenciárias
        $sections = $this->extractEmploymentSectionsImproved($normalizedContent);
        
        foreach ($sections as $section) {
            $employment = $this->parseEmploymentSectionImproved($section);
            if ($employment) {
                $employments[] = $employment;
            }
        }
        
        // Se não encontrou nada, tenta método alternativo linha por linha
        if (empty($employments)) {
            $employments = $this->extractEmploymentDataLineByLine($lines);
        }
        
        Log::info('Vínculos empregatícios extraídos (método aprimorado)', [
            'count' => count($employments), 
            'data' => $employments
        ]);
        
        return $employments;
    }

    private function extractEmploymentSectionsImproved(string $content): array
    {
        $sections = [];
        
        // Divide o conteúdo por sequências de vínculos
        $pattern = '/Seq\.\s+NIT.*?(?=Seq\.\s+NIT|Valores Consolidados|Legenda de Indicadores|$)/s';
        
        if (preg_match_all($pattern, $content, $matches)) {
            $sections = $matches[0];
        } else {
            // Fallback: busca por CNPJs como delimitadores
            $parts = preg_split('/(\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2})/', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
            
            for ($i = 1; $i < count($parts); $i += 2) {
                if (isset($parts[$i + 1])) {
                    $sections[] = $parts[$i] . ' ' . $parts[$i + 1];
                }
            }
        }
        
        return $sections;
    }

    private function parseEmploymentSectionImproved(string $section): ?array
    {
        $employment = [
            'empregador' => '',
            'cnpj' => '',
            'data_inicio' => '',
            'data_fim' => '',
            'tipo_vinculo' => '',
            'ultima_remuneracao' => 0
        ];
        
        // Extrai CNPJ
        if (preg_match('/(\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2})/', $section, $matches)) {
            $employment['cnpj'] = $matches[1];
        }
        
        // Extrai empregador - busca após CNPJ
        if (preg_match('/\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}\s+([A-Z\s\-\.&]+?)(?=\s+Empregado|\s+Contribuinte|\s+Tipo\s+Filiado|Data)/i', $section, $matches)) {
            $employment['empregador'] = trim($matches[1]);
        }
        
        // Padrão alternativo para empregadores
        if (empty($employment['empregador'])) {
            if (preg_match('/Origem do Vínculo\s+([A-Z\s\-\.&]+?)(?=\s+Matrícula|\s+Tipo)/i', $section, $matches)) {
                $employment['empregador'] = trim($matches[1]);
            }
        }
        
        // Tipo de vínculo
        $tipoPatterns = [
            '/Empregado\s+ou\s+Agente\s+Público/i' => 'Empregado Público',
            '/Empregado/i' => 'Empregado',
            '/Contribuinte\s+Individual/i' => 'Contribuinte Individual',
            '/Servidor\s+Público/i' => 'Servidor Público',
            '/Trabalhador/i' => 'Trabalhador'
        ];
        
        foreach ($tipoPatterns as $pattern => $tipo) {
            if (preg_match($pattern, $section)) {
                $employment['tipo_vinculo'] = $tipo;
                break;
            }
        }
        
        // Datas de início e fim
        if (preg_match('/Data\s+Início\s+Data\s+Fim.*?(\d{2}\/\d{2}\/\d{4})\s+(\d{2}\/\d{2}\/\d{4})?/i', $section, $matches)) {
            $employment['data_inicio'] = $matches[1];
            $employment['data_fim'] = $matches[2] ?? '';
        }
        
        // Padrão alternativo para datas
        if (empty($employment['data_inicio'])) {
            preg_match_all('/(\d{2}\/\d{2}\/\d{4})/', $section, $dateMatches);
            if (!empty($dateMatches[1])) {
                $employment['data_inicio'] = $dateMatches[1][0];
                if (count($dateMatches[1]) > 1) {
                    $employment['data_fim'] = end($dateMatches[1]);
                }
            }
        }
        
        // Última remuneração
        if (preg_match('/(\d{1,3}(?:\.\d{3})*,\d{2})/', $section, $matches)) {
            $employment['ultima_remuneracao'] = $this->parseMonetaryValue($matches[1]);
        }
        
        // Valida se tem dados mínimos
        if (!empty($employment['empregador']) || !empty($employment['cnpj'])) {
            $employment['empregador'] = preg_replace('/\s+/', ' ', trim($employment['empregador']));
            return $employment;
        }
        
        return null;
    }

    private function extractEmploymentDataLineByLine(array $lines): array
    {
        $employments = [];
        $currentEmployment = null;
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Início de novo vínculo por CNPJ
            if (preg_match('/(\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2})/', $line, $matches)) {
                if ($currentEmployment && !empty($currentEmployment['empregador'])) {
                    $employments[] = $currentEmployment;
                }
                
                $currentEmployment = [
                    'cnpj' => $matches[1],
                    'empregador' => '',
                    'data_inicio' => '',
                    'data_fim' => '',
                    'tipo_vinculo' => '',
                    'ultima_remuneracao' => 0
                ];
                
                // Extrai nome da empresa da mesma linha
                $afterCnpj = trim(str_replace($matches[1], '', $line));
                if ($afterCnpj) {
                    $currentEmployment['empregador'] = $afterCnpj;
                }
            }
            
            // Se está processando um vínculo
            if ($currentEmployment) {
                // Tipo de vínculo
                if (preg_match('/(Empregado|Contribuinte Individual|Servidor Público)/i', $line, $matches)) {
                    $currentEmployment['tipo_vinculo'] = $matches[1];
                }
                
                // Datas
                if (preg_match_all('/(\d{2}\/\d{2}\/\d{4})/', $line, $dateMatches)) {
                    if (empty($currentEmployment['data_inicio'])) {
                        $currentEmployment['data_inicio'] = $dateMatches[1][0];
                    }
                    if (count($dateMatches[1]) > 1) {
                        $currentEmployment['data_fim'] = end($dateMatches[1]);
                    }
                }
            }
        }
        
        // Adiciona o último vínculo
        if ($currentEmployment && !empty($currentEmployment['empregador'])) {
            $employments[] = $currentEmployment;
        }
        
        return $employments;
    }

    private function parseMonetaryValue(string $value): float
    {
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
        return (float) $value;
    }

    private function extractBenefitsImproved(string $content): array
    {
        $benefits = [];
        
        if (preg_match('/Benefício\s+(\d+)\s*-\s*([A-Z\s]+)/', $content, $matches)) {
            $benefits[] = [
                'codigo' => $matches[1],
                'descricao' => trim($matches[2]),
                'status' => 'ATIVO'
            ];
        }
        
        return $benefits;
    }

    private function createEmploymentRelationships(int $caseId, array $employments): void
    {
        foreach ($employments as $employment) {
            EmploymentRelationship::create([
                'case_id' => $caseId,
                'employer_name' => $employment['empregador'],
                'employer_cnpj' => $employment['cnpj'] ?? null,
                'start_date' => $this->parseDate($employment['data_inicio']),
                'end_date' => $this->parseDate($employment['data_fim']),
                'salary' => $employment['ultima_remuneracao'] ?? null,
                'position' => $employment['tipo_vinculo'] ?? null,
                'notes' => json_encode([
                    'cnpj' => $employment['cnpj'] ?? '',
                    'tipo_vinculo' => $employment['tipo_vinculo'] ?? '',
                    'ultima_remuneracao' => $employment['ultima_remuneracao'] ?? 0,
                ]),
            ]);
        }
    }

    private function parseDate(?string $dateString): ?string
    {
        if (empty($dateString)) {
            return null;
        }
        
        try {
            // Formatos possíveis: dd/mm/yyyy, mm/yyyy
            if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $dateString, $matches)) {
                return $matches[3] . '-' . $matches[2] . '-' . $matches[1];
            }
            
            if (preg_match('/(\d{2})\/(\d{4})/', $dateString, $matches)) {
                return $matches[2] . '-' . $matches[1] . '-01';
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('Erro ao converter data', ['date' => $dateString, 'error' => $e->getMessage()]);
            return null;
        }
    }

    private function suggestBenefitType(array $data): string
    {
        $employments = $data['vinculos_empregaticios'] ?? [];
        
        if (empty($employments)) {
            return 'aposentadoria_idade';
        }
        
        // Analisa os vínculos para sugerir tipo de benefício
        $hasPublicEmployment = false;
        $hasSpecialActivity = false;
        $totalYears = 0;
        
        foreach ($employments as $employment) {
            $tipoVinculo = $employment['tipo_vinculo'] ?? '';
            
            if (stripos($tipoVinculo, 'servidor') !== false || 
                stripos($tipoVinculo, 'público') !== false) {
                $hasPublicEmployment = true;
            }
            
            // Calcula anos de contribuição (simplificado)
            if (!empty($employment['data_inicio'])) {
                $inicio = strtotime($employment['data_inicio']);
                $fim = !empty($employment['data_fim']) ? strtotime($employment['data_fim']) : time();
                $years = ($fim - $inicio) / (365 * 24 * 3600);
                $totalYears += $years;
            }
        }
        
        // Lógica de sugestão
        if ($hasPublicEmployment) {
            return 'aposentadoria_servidor';
        }
        
        if ($totalYears >= 35) {
            return 'aposentadoria_tempo_contribuicao';
        }
        
        if ($totalYears >= 25) {
            return 'aposentadoria_especial';
        }
        
        return 'aposentadoria_idade';
    }

    private function processMedicalReport(string $content, Document $document): array
    {
        Log::info('Processando laudo médico');
        
        $extractedData = [
            'tipo_documento' => 'laudo_medico',
            'cid' => $this->extractCID($content),
            'medico' => $this->extractDoctorInfo($content),
            'data_exame' => $this->extractExamDate($content),
            'observacoes' => $this->extractMedicalObservations($content),
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

    private function extractCID(string $content): ?string
    {
        if (preg_match('/CID[:\s]*([A-Z]\d{2}(?:\.\d)?)/i', $content, $matches)) {
            return $matches[1];
        }
        return null;
    }

    private function extractDoctorInfo(string $content): array
    {
        $doctor = [];
        
        if (preg_match('/Dr\.?\s*([A-Za-z\s]+)/i', $content, $matches)) {
            $doctor['nome'] = trim($matches[1]);
        }
        
        if (preg_match('/CRM[:\s]*(\d+)/i', $content, $matches)) {
            $doctor['crm'] = $matches[1];
        }
        
        return $doctor;
    }

    private function extractExamDate(string $content): ?string
    {
        if (preg_match('/data[:\s]*(\d{2}\/\d{2}\/\d{4})/i', $content, $matches)) {
            return $this->parseDate($matches[1]);
        }
        return null;
    }

    private function extractMedicalObservations(string $content): array
    {
        $observations = [];
        
        // Busca por termos médicos comuns
        $medicalTerms = [
            'incapacidade',
            'limitação',
            'deficiência',
            'sequela',
            'dor',
            'mobilidade',
            'coordenação',
            'visão',
            'audição'
        ];
        
        foreach ($medicalTerms as $term) {
            if (stripos($content, $term) !== false) {
                $observations[] = $term;
            }
        }
        
        return $observations;
    }

    private function processGenericDocument(string $content, Document $document): array
    {
        Log::info('Processando documento genérico');
        
        $extractedData = [
            'tipo_documento' => 'generico',
            'conteudo_resumido' => substr($content, 0, 500),
            'palavras_chave' => $this->extractKeywords($content),
            'datas_encontradas' => $this->extractDates($content),
            'valores_encontrados' => $this->extractMonetaryValues($content),
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

    private function extractKeywords(string $content): array
    {
        $keywords = [];
        $legalTerms = [
            'aposentadoria', 'benefício', 'INSS', 'previdência', 'contribuição',
            'auxílio', 'pensão', 'invalidez', 'doença', 'acidente', 'trabalho',
            'salário', 'remuneração', 'vínculo', 'emprego', 'servidor'
        ];
        
        foreach ($legalTerms as $term) {
            if (stripos($content, $term) !== false) {
                $keywords[] = $term;
            }
        }
        
        return $keywords;
    }

    private function extractDates(string $content): array
    {
        preg_match_all('/(\d{2}\/\d{2}\/\d{4})/', $content, $matches);
        return array_unique($matches[1]);
    }

    private function extractMonetaryValues(string $content): array
    {
        preg_match_all('/R\$\s*(\d{1,3}(?:\.\d{3})*(?:,\d{2})?)/', $content, $matches);
        return array_unique($matches[1]);
    }
}