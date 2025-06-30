<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiService
{
    private string $apiKey;
    private string $baseUrl = 'https://api.openai.com/v1';
    private string $model = 'gpt-4o-mini';

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        $this->baseUrl = config('services.openai.base_url', 'https://api.openai.com/v1');
        $this->model = config('services.openai.model', 'gpt-4o-mini');
    }

    public function generatePetition(array $caseData, string $prompt): array
    {
        try {
            $systemPrompt = 'Você é um advogado especialista em direito previdenciário do INSS. Gere petições jurídicas precisas e bem fundamentadas.';
            
            $userPrompt = $this->buildPetitionPrompt($caseData, $prompt);
            
            $response = Http::timeout(120)->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/chat/completions', [
                'model' => $this->model,
                'store' => true,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt
                    ],
                    [
                        'role' => 'user',
                        'content' => $userPrompt
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

            Log::error('OpenAI API error', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => 'Erro na API do OpenAI: ' . $response->status(),
            ];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('OpenAI connection timeout', [
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Timeout na conexão com a API. Tente novamente em alguns segundos.',
            ];

        } catch (\Exception $e) {
            Log::error('OpenAI service error', [
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
            
            $response = Http::timeout(60)->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/chat/completions', [
                'model' => $this->model,
                'store' => true,
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

            Log::error('OpenAI API error', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => 'Erro na API do OpenAI: ' . $response->status(),
            ];

        } catch (\Exception $e) {
            Log::error('OpenAI document analysis error', [
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Erro interno: ' . $e->getMessage(),
            ];
        }
    }

    public function chat(string $message): array
    {
        try {
            Log::info('OpenAI Chat API call started', ['message_length' => strlen($message)]);
            
            $systemPrompt = 'Você é uma assistente previdenciária especializada em direito do INSS. Responda de forma clara, precisa e útil sobre questões previdenciárias, análise de documentos CNIS, estratégias para benefícios, vínculos empregatícios e orientações sobre coleta de documentos. Seja sempre profissional e técnica em suas respostas.

Quando receber informações detalhadas sobre um cliente (vínculos empregatícios, documentos, andamentos), use essas informações para dar respostas mais precisas e personalizadas. Analise os dados fornecidos e identifique possíveis problemas, oportunidades ou próximos passos.

Se não houver informações específicas do cliente, forneça orientações gerais sobre direito previdenciário.';
            
            $response = Http::timeout(180)->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/chat/completions', [
                'model' => $this->model,
                'store' => true,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt
                    ],
                    [
                        'role' => 'user',
                        'content' => $message
                    ]
                ],
                'temperature' => 0.7,
                'max_tokens' => 4000,
                'top_p' => 0.95,
            ]);

            Log::info('OpenAI API response received', [
                'status' => $response->status(),
                'successful' => $response->successful(),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('OpenAI API response parsed', [
                    'has_choices' => isset($data['choices']),
                    'choices_count' => count($data['choices'] ?? []),
                ]);
                
                return [
                    'success' => true,
                    'content' => $data['choices'][0]['message']['content'] ?? '',
                    'usage' => $data['usage'] ?? null,
                ];
            }

            Log::error('OpenAI Chat API error', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => 'Erro na API do OpenAI: ' . $response->status(),
            ];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('OpenAI Chat connection timeout', [
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Timeout na conexão com a API. Tente novamente em alguns segundos.',
            ];

        } catch (\Exception $e) {
            Log::error('OpenAI Chat service error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Erro interno: ' . $e->getMessage(),
            ];
        }
    }

    private function buildPetitionPrompt(array $caseData, string $userPrompt): string
    {
        $clientInfo = "Cliente: {$caseData['client_name']} (CPF: {$caseData['client_cpf']})";
        $benefitInfo = "Tipo de Benefício: {$caseData['benefit_type']}";
        
        $employmentInfo = '';
        if (!empty($caseData['employment_relationships'])) {
            $employmentInfo = "\nVínculos Empregatícios:\n";
            foreach ($caseData['employment_relationships'] as $employment) {
                $employmentInfo .= "- {$employment['employer_name']} ({$employment['start_date']} a " . 
                    ($employment['end_date'] ?? 'atual') . ")\n";
            }
        }

        return "Gere uma petição jurídica para o INSS com as seguintes informações:

{$clientInfo}
{$benefitInfo}
{$employmentInfo}

Solicitação específica: {$userPrompt}

Formato da resposta:
- Petição formal e técnica
- Estrutura adequada para submissão
- Fundamentação jurídica sólida
- Citações legais quando necessário";
    }

    private function buildDocumentAnalysisPrompt(string $content, string $documentType): string
    {
        return "Analise o seguinte documento do tipo '{$documentType}' e extraia as informações relevantes:

{$content}

Forneça uma análise estruturada com:
1. Informações principais extraídas
2. Dados relevantes para direito previdenciário
3. Possíveis inconsistências ou problemas
4. Recomendações para próximos passos";
    }

    private function parseAnalysisResponse(string $response): array
    {
        // Parse básico da resposta - pode ser melhorado conforme necessário
        return [
            'summary' => $response,
            'extracted_data' => [],
            'recommendations' => [],
        ];
    }
} 