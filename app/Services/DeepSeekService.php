<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeepSeekService
{
    private string $apiKey;
    private string $baseUrl = 'https://api.deepseek.com/v1';

    public function __construct()
    {
        $this->apiKey = config('services.deepseek.api_key', 'sk-909462f78a694d23b6ebe222a84948f4');
    }

    public function generatePetition(array $caseData, string $petitionType): array
    {
        try {
            $prompt = $this->buildPetitionPrompt($caseData, $petitionType);
            
            $response = Http::timeout(25)->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/chat/completions', [
                'model' => 'deepseek-chat',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Você é um advogado especialista em direito previdenciário do INSS. Gere petições jurídicas precisas e bem fundamentadas.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.7,
                'max_tokens' => 4000,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'content' => $data['choices'][0]['message']['content'] ?? '',
                    'usage' => $data['usage'] ?? null,
                ];
            }

            Log::error('DeepSeek API error', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => 'Erro na API do DeepSeek: ' . $response->status(),
            ];

        } catch (\Exception $e) {
            Log::error('DeepSeek service error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Erro interno: ' . $e->getMessage(),
            ];
        }
    }

    public function analyzeDocument(string $documentContent, string $documentType): array
    {
        try {
            $prompt = $this->buildDocumentAnalysisPrompt($documentContent, $documentType);
            
            $response = Http::timeout(25)->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/chat/completions', [
                'model' => 'deepseek-chat',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Você é um especialista em análise de documentos previdenciários. Extraia informações relevantes de forma estruturada.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.3,
                'max_tokens' => 2000,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['choices'][0]['message']['content'] ?? '';
                
                return [
                    'success' => true,
                    'data' => $this->parseAnalysisResponse($content),
                    'raw_response' => $content,
                ];
            }

            Log::error('DeepSeek API error', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => 'Erro na API do DeepSeek: ' . $response->status(),
            ];

        } catch (\Exception $e) {
            Log::error('DeepSeek document analysis error', [
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Erro interno: ' . $e->getMessage(),
            ];
        }
    }

    private function buildPetitionPrompt(array $caseData, string $petitionType): string
    {
        $clientInfo = "Cliente: {$caseData['client_name']} (CPF: {$caseData['client_cpf']})";
        $benefitInfo = "Tipo de Benefício: {$caseData['benefit_type']}";
        $description = $caseData['description'] ?? 'Não informado';
        
        $employmentInfo = '';
        if (!empty($caseData['employment_relationships'])) {
            $employmentInfo = "\nVínculos Empregatícios:\n";
            foreach ($caseData['employment_relationships'] as $employment) {
                $employmentInfo .= "- {$employment['employer_name']} ({$employment['start_date']} a " . 
                    ($employment['end_date'] ?? 'atual') . ")\n";
            }
        }

        return "Gere uma petição do tipo '{$petitionType}' com as seguintes informações:

{$clientInfo}
{$benefitInfo}
Descrição do caso: {$description}
{$employmentInfo}

A petição deve ser:
- Formal e técnica
- Bem fundamentada juridicamente
- Específica para o tipo de benefício
- Estruturada adequadamente
- Pronta para submissão

Gere apenas o conteúdo da petição, sem comentários adicionais.";
    }

    private function buildDocumentAnalysisPrompt(string $content, string $documentType): string
    {
        return "Analise o seguinte documento do tipo '{$documentType}' e extraia as informações relevantes:

{$content}

Extraia e retorne em formato JSON as seguintes informações:
- Dados pessoais do segurado (nome, CPF, data de nascimento)
- Vínculos empregatícios (empregador, período, salário, função)
- Informações sobre benefícios
- Datas importantes
- Valores monetários
- Observações relevantes

Retorne apenas o JSON válido, sem texto adicional.";
    }

    private function parseAnalysisResponse(string $response): array
    {
        try {
            // Tenta extrair JSON da resposta
            if (preg_match('/\{.*\}/s', $response, $matches)) {
                return json_decode($matches[0], true) ?? [];
            }
            
            // Se não encontrar JSON, retorna a resposta como está
            return ['raw_content' => $response];
        } catch (\Exception $e) {
            return ['raw_content' => $response, 'parse_error' => $e->getMessage()];
        }
    }
} 