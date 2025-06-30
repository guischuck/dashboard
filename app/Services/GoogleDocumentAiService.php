<?php

namespace App\Services;

use Google\Cloud\DocumentAI\V1\Client\DocumentProcessorServiceClient;
use Google\Cloud\DocumentAI\V1\Document;
use Google\Cloud\DocumentAI\V1\ProcessRequest;
use Google\Cloud\DocumentAI\V1\RawDocument;
use Google\Protobuf\BytesValue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class GoogleDocumentAiService
{
    private string $projectId;
    private string $location;
    private DocumentProcessorServiceClient $client;
    private array $config;

    public function __construct()
    {
        // Carrega configurações
        $this->config = Config::get('document-ai', []);
        $this->projectId = $this->config['project_id'] ?? 'cnis-document-ai';
        $this->location = $this->config['location'] ?? 'us';
        
        // Configura o cliente com o arquivo de credenciais
        $credentialsPath = $this->config['credentials_path'] ?? base_path('cnis-document-ai-3a6322737b0c.json');
        
        if (!file_exists($credentialsPath)) {
            Log::warning('Arquivo de credenciais do Google Cloud não encontrado');
            throw new \Exception('Arquivo de credenciais do Google Cloud não encontrado');
        }
        
        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $credentialsPath);
        
        // Inicializa o cliente do Google Document AI
        $this->client = new DocumentProcessorServiceClient();
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

            // Processa o documento usando diferentes processadores
            $results = [
                'ocr' => $this->processWithOCR($fileContent),
                'form_parser' => $this->processWithFormParser($fileContent),
                'entity_extraction' => $this->processWithEntityExtraction($fileContent),
                'brazilian_document' => $this->processWithBrazilianDocumentProcessor($fileContent),
                'table_extractor' => $this->processWithTableExtractor($fileContent),
            ];

            // Combina os resultados dos diferentes processadores
            $extractedData = $this->combineResults($results);

            // Log dos resultados se habilitado
            if ($this->config['logging']['enabled'] ?? true) {
                $logData = [
                    'success' => true,
                    'extracted_data' => $extractedData,
                ];
                
                if ($this->config['logging']['include_raw_results'] ?? false) {
                    $logData['raw_results'] = $results;
                }
                
                Log::log($this->config['logging']['level'] ?? 'info', 'Processamento CNIS concluído', $logData);
            }

            return [
                'success' => true,
                'data' => $extractedData,
                'raw_results' => $this->config['logging']['include_raw_results'] ?? false ? $results : null,
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

    private function processWithOCR(string $fileContent): array
    {
        try {
            $processorId = $this->getProcessorId('ocr');
            
            $document = new Document([
                'mime_type' => 'application/pdf',
                'raw_document' => new RawDocument([
                    'content' => $fileContent,
                    'mime_type' => 'application/pdf',
                ]),
            ]);

            $request = new ProcessRequest([
                'name' => $processorId,
                'document' => $document,
            ]);

            $result = $this->client->processDocument($request);
            $document = $result->getDocument();
            
            return [
                'text' => $document->getText(),
                'pages' => $document->getPages(),
            ];
        } catch (\Exception $e) {
            Log::error('Erro no processamento OCR', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }

    private function processWithFormParser(string $fileContent): array
    {
        try {
            $processorId = $this->getProcessorId('form_parser');
            
            $document = new Document([
                'mime_type' => 'application/pdf',
                'raw_document' => new RawDocument([
                    'content' => $fileContent,
                    'mime_type' => 'application/pdf',
                ]),
            ]);

            $request = new ProcessRequest([
                'name' => $processorId,
                'document' => $document,
            ]);

            $result = $this->client->processDocument($request);
            $document = $result->getDocument();
            
            $formFields = [];
            foreach ($document->getPages() as $page) {
                foreach ($page->getFormFields() as $field) {
                    $formFields[] = [
                        'name' => $field->getFieldName()->getTextAnchor()->getContent(),
                        'value' => $field->getFieldValue()->getTextAnchor()->getContent(),
                        'confidence' => $field->getFieldValue()->getConfidence(),
                    ];
                }
            }
            
            return $formFields;
        } catch (\Exception $e) {
            Log::error('Erro no processamento de formulários', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }

    private function processWithEntityExtraction(string $fileContent): array
    {
        try {
            $processorId = $this->getProcessorId('entity_extractor');
            
            $document = new Document([
                'mime_type' => 'application/pdf',
                'raw_document' => new RawDocument([
                    'content' => $fileContent,
                    'mime_type' => 'application/pdf',
                ]),
            ]);

            $request = new ProcessRequest([
                'name' => $processorId,
                'document' => $document,
            ]);

            $result = $this->client->processDocument($request);
            $document = $result->getDocument();
            
            $entities = [];
            foreach ($document->getEntities() as $entity) {
                $entities[] = [
                    'type' => $entity->getType(),
                    'text' => $entity->getMentionText(),
                    'confidence' => $entity->getConfidence(),
                    'page_anchor' => $entity->getPageAnchor(),
                ];
            }
            
            return $entities;
        } catch (\Exception $e) {
            Log::error('Erro na extração de entidades', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }

    private function processWithBrazilianDocumentProcessor(string $fileContent): array
    {
        try {
            $processorId = $this->getProcessorId('brazilian_document');
            
            $document = new Document([
                'mime_type' => 'application/pdf',
                'raw_document' => new RawDocument([
                    'content' => $fileContent,
                    'mime_type' => 'application/pdf',
                ]),
            ]);

            $request = new ProcessRequest([
                'name' => $processorId,
                'document' => $document,
            ]);

            $result = $this->client->processDocument($request);
            $document = $result->getDocument();
            
            // Processa resultados específicos para documentos brasileiros
            $brazilianData = [
                'entities' => [],
                'form_fields' => [],
                'tables' => [],
            ];
            
            // Extrai entidades específicas brasileiras
            foreach ($document->getEntities() as $entity) {
                $brazilianData['entities'][] = [
                    'type' => $entity->getType(),
                    'text' => $entity->getMentionText(),
                    'confidence' => $entity->getConfidence(),
                ];
            }
            
            // Extrai campos de formulário
            foreach ($document->getPages() as $page) {
                foreach ($page->getFormFields() as $field) {
                    $brazilianData['form_fields'][] = [
                        'name' => $field->getFieldName()->getTextAnchor()->getContent(),
                        'value' => $field->getFieldValue()->getTextAnchor()->getContent(),
                        'confidence' => $field->getFieldValue()->getConfidence(),
                    ];
                }
            }
            
            return $brazilianData;
        } catch (\Exception $e) {
            Log::error('Erro no processamento de documento brasileiro', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }

    private function processWithTableExtractor(string $fileContent): array
    {
        try {
            $processorId = $this->getProcessorId('table_extractor');
            
            $document = new Document([
                'mime_type' => 'application/pdf',
                'raw_document' => new RawDocument([
                    'content' => $fileContent,
                    'mime_type' => 'application/pdf',
                ]),
            ]);

            $request = new ProcessRequest([
                'name' => $processorId,
                'document' => $document,
            ]);

            $result = $this->client->processDocument($request);
            $document = $result->getDocument();
            
            $tables = [];
            foreach ($document->getPages() as $page) {
                foreach ($page->getTables() as $table) {
                    $tableData = [
                        'rows' => [],
                        'header_rows' => $table->getHeaderRows(),
                        'body_rows' => $table->getBodyRows(),
                    ];
                    
                    // Extrai dados das células
                    foreach ($table->getBodyRows() as $row) {
                        $rowData = [];
                        foreach ($row->getCells() as $cell) {
                            $rowData[] = $cell->getTextAnchor()->getContent();
                        }
                        $tableData['rows'][] = $rowData;
                    }
                    
                    $tables[] = $tableData;
                }
            }
            
            return $tables;
        } catch (\Exception $e) {
            Log::error('Erro na extração de tabelas', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }

    private function getProcessorId(string $processorType): string
    {
        $processorConfig = $this->config['processors'][$processorType] ?? [];
        $processorId = $processorConfig['id'] ?? $processorType . '-processor';
        
        return 'projects/' . $this->projectId . '/locations/' . $this->location . '/processors/' . $processorId;
    }

    private function combineResults(array $results): array
    {
        $extractedData = [
            'dados_pessoais' => [],
            'vinculos_empregaticios' => [],
            'beneficios' => [],
        ];

        // Extrai dados pessoais dos resultados
        $extractedData['dados_pessoais'] = $this->extractPersonalData($results);
        
        // Extrai vínculos empregatícios dos resultados
        $extractedData['vinculos_empregaticios'] = $this->extractEmploymentData($results);

        return $extractedData;
    }

    private function extractPersonalData(array $results): array
    {
        $personalData = [];

        // Extrai CPF das entidades
        if (isset($results['entity_extraction']) && !isset($results['entity_extraction']['error'])) {
            foreach ($results['entity_extraction'] as $entity) {
                if ($entity['type'] === 'CPF' || 
                    (strpos($entity['text'], '.') !== false && strpos($entity['text'], '-') !== false)) {
                    $personalData['cpf'] = $entity['text'];
                    break;
                }
            }
        }

        // Extrai nome dos campos de formulário
        if (isset($results['form_parser']) && !isset($results['form_parser']['error'])) {
            foreach ($results['form_parser'] as $field) {
                if (stripos($field['name'], 'nome') !== false || 
                    stripos($field['name'], 'name') !== false) {
                    $personalData['nome'] = $field['value'];
                    break;
                }
            }
        }

        // Extrai data de nascimento
        if (isset($results['entity_extraction']) && !isset($results['entity_extraction']['error'])) {
            foreach ($results['entity_extraction'] as $entity) {
                if ($entity['type'] === 'DATE' && 
                    (strpos($entity['text'], '/') !== false)) {
                    $personalData['data_nascimento'] = $entity['text'];
                    break;
                }
            }
        }

        // Usa dados do processador brasileiro se disponível
        if (isset($results['brazilian_document']) && !isset($results['brazilian_document']['error'])) {
            $brazilianData = $results['brazilian_document'];
            
            // Extrai CPF das entidades brasileiras
            foreach ($brazilianData['entities'] ?? [] as $entity) {
                if ($entity['type'] === 'CPF' && empty($personalData['cpf'])) {
                    $personalData['cpf'] = $entity['text'];
                }
            }
            
            // Extrai nome dos campos brasileiros
            foreach ($brazilianData['form_fields'] ?? [] as $field) {
                if (stripos($field['name'], 'nome') !== false && empty($personalData['nome'])) {
                    $personalData['nome'] = $field['value'];
                }
            }
        }

        return $personalData;
    }

    private function extractEmploymentData(array $results): array
    {
        $employments = [];

        // Se temos texto do OCR, podemos usar para extrair vínculos
        if (isset($results['ocr']['text'])) {
            $text = $results['ocr']['text'];
            
            // Usa os campos de formulário para extrair dados estruturados
            if (isset($results['form_parser']) && !isset($results['form_parser']['error'])) {
                $employments = $this->extractFromFormFields($results['form_parser']);
            }
            
            // Usa dados do processador brasileiro
            if (isset($results['brazilian_document']) && !isset($results['brazilian_document']['error'])) {
                $brazilianEmployments = $this->extractFromBrazilianDocument($results['brazilian_document']);
                if (!empty($brazilianEmployments)) {
                    $employments = array_merge($employments, $brazilianEmployments);
                }
            }
            
            // Usa dados das tabelas
            if (isset($results['table_extractor']) && !isset($results['table_extractor']['error'])) {
                $tableEmployments = $this->extractFromTables($results['table_extractor']);
                if (!empty($tableEmployments)) {
                    $employments = array_merge($employments, $tableEmployments);
                }
            }
            
            // Se não conseguiu extrair dos campos, usa o texto do OCR como fallback
            if (empty($employments)) {
                $employments = $this->extractFromOCRText($text, $results['entity_extraction'] ?? []);
            }
        }

        return $employments;
    }

    private function extractFromFormFields(array $formFields): array
    {
        $employments = [];
        $currentEmployment = [];

        foreach ($formFields as $field) {
            $fieldName = strtolower($field['name']);
            $fieldValue = $field['value'];

            if (strpos($fieldName, 'empregador') !== false || strpos($fieldName, 'employer') !== false) {
                if (!empty($currentEmployment)) {
                    $employments[] = $currentEmployment;
                }
                $currentEmployment = ['empregador' => $fieldValue];
            } elseif (strpos($fieldName, 'cnpj') !== false) {
                $currentEmployment['cnpj'] = $fieldValue;
            } elseif (strpos($fieldName, 'data_inicio') !== false || strpos($fieldName, 'start_date') !== false) {
                $currentEmployment['data_inicio'] = $fieldValue;
            } elseif (strpos($fieldName, 'data_fim') !== false || strpos($fieldName, 'end_date') !== false) {
                $currentEmployment['data_fim'] = $fieldValue;
            } elseif (strpos($fieldName, 'salario') !== false || strpos($fieldName, 'salary') !== false) {
                $currentEmployment['salario'] = $fieldValue;
            }
        }

        if (!empty($currentEmployment)) {
            $employments[] = $currentEmployment;
        }

        return $employments;
    }

    private function extractFromBrazilianDocument(array $brazilianData): array
    {
        $employments = [];
        $currentEmployment = [];

        // Extrai dados das entidades brasileiras
        foreach ($brazilianData['entities'] ?? [] as $entity) {
            if ($entity['type'] === 'CNPJ') {
                $currentEmployment['cnpj'] = $entity['text'];
            } elseif ($entity['type'] === 'DATE') {
                if (empty($currentEmployment['data_inicio'])) {
                    $currentEmployment['data_inicio'] = $entity['text'];
                } elseif (empty($currentEmployment['data_fim'])) {
                    $currentEmployment['data_fim'] = $entity['text'];
                }
            }
        }

        // Extrai dados dos campos de formulário brasileiros
        foreach ($brazilianData['form_fields'] ?? [] as $field) {
            $fieldName = strtolower($field['name']);
            $fieldValue = $field['value'];

            if (strpos($fieldName, 'empregador') !== false) {
                if (!empty($currentEmployment)) {
                    $employments[] = $currentEmployment;
                }
                $currentEmployment = ['empregador' => $fieldValue];
            } elseif (strpos($fieldName, 'cnpj') !== false) {
                $currentEmployment['cnpj'] = $fieldValue;
            } elseif (strpos($fieldName, 'data_inicio') !== false) {
                $currentEmployment['data_inicio'] = $fieldValue;
            } elseif (strpos($fieldName, 'data_fim') !== false) {
                $currentEmployment['data_fim'] = $fieldValue;
            } elseif (strpos($fieldName, 'salario') !== false) {
                $currentEmployment['salario'] = $fieldValue;
            }
        }

        if (!empty($currentEmployment)) {
            $employments[] = $currentEmployment;
        }

        return $employments;
    }

    private function extractFromTables(array $tables): array
    {
        $employments = [];

        foreach ($tables as $table) {
            foreach ($table['rows'] ?? [] as $row) {
                if (count($row) >= 3) { // Mínimo de colunas para um vínculo
                    $employment = [
                        'empregador' => $row[0] ?? '',
                        'cnpj' => $row[1] ?? '',
                        'data_inicio' => $row[2] ?? '',
                        'data_fim' => $row[3] ?? 'sem data fim',
                        'salario' => $row[4] ?? '',
                    ];
                    
                    if (!empty($employment['empregador'])) {
                        $employments[] = $employment;
                    }
                }
            }
        }

        return $employments;
    }

    private function extractFromOCRText(string $text, array $entities): array
    {
        $employments = [];
        $lines = explode("\n", $text);
        
        $currentEmployment = [];
        $inEmploymentSection = false;

        foreach ($lines as $line) {
            $line = trim($line);
            
            // Identifica início de uma seção de vínculo
            if (strpos($line, 'Código Emp.') !== false || 
                preg_match('/^\d+\s+\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}/', $line) ||
                strpos($line, 'AGRUPAMENTO') !== false) {
                
                if (!empty($currentEmployment)) {
                    $employments[] = $currentEmployment;
                }
                
                $currentEmployment = [
                    'empregador' => '',
                    'cnpj' => '',
                    'data_inicio' => '',
                    'data_fim' => '',
                    'salario' => '',
                ];
                $inEmploymentSection = true;
            }
            
            if ($inEmploymentSection) {
                // Extrai CNPJ e empregador
                if (preg_match('/^\d+\s+(\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2})\s+(.+)$/', $line, $matches)) {
                    $currentEmployment['cnpj'] = $matches[1];
                    $currentEmployment['empregador'] = trim($matches[2]);
                } elseif (preg_match('/^\d+\s+(AGRUPAMENTO.+)$/', $line, $matches)) {
                    $currentEmployment['empregador'] = trim($matches[1]);
                    $currentEmployment['cnpj'] = '';
                }
                
                // Extrai datas usando entidades identificadas
                foreach ($entities as $entity) {
                    if ($entity['type'] === 'DATE' && strpos($entity['text'], '/') !== false) {
                        if (empty($currentEmployment['data_inicio'])) {
                            $currentEmployment['data_inicio'] = $entity['text'];
                        } elseif (empty($currentEmployment['data_fim'])) {
                            $currentEmployment['data_fim'] = $entity['text'];
                        }
                    }
                }
                
                // Identifica fim da seção
                if (strpos($line, 'Relações Previdenciárias') !== false ||
                    strpos($line, 'Valores Consolidados') !== false) {
                    $inEmploymentSection = false;
                }
            }
        }
        
        if (!empty($currentEmployment)) {
            $employments[] = $currentEmployment;
        }

        return $employments;
    }

    public function __destruct()
    {
        if (isset($this->client)) {
            $this->client->close();
        }
    }
} 