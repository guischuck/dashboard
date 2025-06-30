<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OpenAiService;
use App\Models\LegalCase;
use App\Models\EmploymentRelationship;
use App\Models\Document;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeepSeekChatController extends Controller
{
    public function __invoke(Request $request, OpenAiService $openAi)
    {
        Log::info('OpenAI Chat Request received', [
            'client_id' => $request->client_id,
            'message' => substr($request->message ?? '', 0, 100),
            'user_id' => auth()->id(),
        ]);

        $request->validate([
            'client_id' => 'nullable|integer',
            'message' => 'required|string',
        ]);

        $userContext = '';
        $clientName = 'Usuário';
        
        // Se um cliente foi selecionado, buscar informações completas do caso
        if ($request->client_id) {
            $caseQuery = LegalCase::where('id', $request->client_id);
            
            // Filtrar por empresa se não for super admin
            if (!auth()->user()->isSuperAdmin()) {
                $caseQuery->byCompany(auth()->user()->company_id);
            }
            
            $case = $caseQuery->first();
            
            if ($case) {
                $clientName = $case->client_name;
                
                // Buscar vínculos empregatícios
                $employmentRelationships = EmploymentRelationship::where('case_id', $case->id)->get();
                
                // Buscar documentos
                $documents = Document::where('case_id', $case->id)->get();
                
                // Buscar tarefas/andamentos
                $tasks = Task::where('case_id', $case->id)->orderBy('created_at', 'desc')->take(10)->get();
                
                // Montar contexto detalhado
                $userContext = "=== INFORMAÇÕES DO CLIENTE ===\n";
                $userContext .= "Nome: {$case->client_name}\n";
                $userContext .= "CPF: {$case->client_cpf}\n";
                $userContext .= "Número do Caso: {$case->case_number}\n";
                $userContext .= "Tipo de Benefício: " . ($case->benefit_type ?? 'Não especificado') . "\n";
                $userContext .= "Status: {$case->status}\n";
                $userContext .= "Descrição: " . ($case->description ?? 'Não informada') . "\n";
                $userContext .= "Notas: " . ($case->notes ?? 'Nenhuma') . "\n";
                $userContext .= "Valor Estimado: " . ($case->estimated_value ? 'R$ ' . number_format($case->estimated_value, 2, ',', '.') : 'Não informado') . "\n";
                $userContext .= "Taxa de Sucesso: " . ($case->success_fee ? $case->success_fee . '%' : 'Não informada') . "\n\n";
                
                // Vínculos empregatícios
                if ($employmentRelationships->count() > 0) {
                    $userContext .= "=== VÍNCULOS EMPREGATÍCIOS ===\n";
                    foreach ($employmentRelationships as $relationship) {
                        $userContext .= "- Empresa: {$relationship->company_name}\n";
                        $userContext .= "  CNPJ: {$relationship->company_cnpj}\n";
                        $userContext .= "  Período: " . ($relationship->start_date ? $relationship->start_date->format('d/m/Y') : 'Não informado');
                        $userContext .= " até " . ($relationship->end_date ? $relationship->end_date->format('d/m/Y') : 'Atual') . "\n";
                        $userContext .= "  Função: " . ($relationship->job_title ?? 'Não informada') . "\n";
                        $userContext .= "  Salário: " . ($relationship->salary ? 'R$ ' . number_format($relationship->salary, 2, ',', '.') : 'Não informado') . "\n";
                        $userContext .= "  Status: {$relationship->status}\n";
                        if ($relationship->notes) {
                            $userContext .= "  Observações: {$relationship->notes}\n";
                        }
                        $userContext .= "\n";
                    }
                } else {
                    $userContext .= "=== VÍNCULOS EMPREGATÍCIOS ===\n";
                    $userContext .= "Nenhum vínculo empregatício cadastrado ainda.\n\n";
                }
                
                // Documentos
                if ($documents->count() > 0) {
                    $userContext .= "=== DOCUMENTOS ===\n";
                    foreach ($documents as $document) {
                        $userContext .= "- {$document->name} ({$document->type})\n";
                        $userContext .= "  Enviado em: " . $document->created_at->format('d/m/Y H:i') . "\n";
                        if ($document->description) {
                            $userContext .= "  Descrição: {$document->description}\n";
                        }
                    }
                    $userContext .= "\n";
                } else {
                    $userContext .= "=== DOCUMENTOS ===\n";
                    $userContext .= "Nenhum documento enviado ainda.\n\n";
                }
                
                // Andamentos recentes
                if ($tasks->count() > 0) {
                    $userContext .= "=== ANDAMENTOS RECENTES ===\n";
                    foreach ($tasks as $task) {
                        $userContext .= "- {$task->title}\n";
                        $userContext .= "  Status: {$task->status}\n";
                        $userContext .= "  Data: " . $task->created_at->format('d/m/Y H:i') . "\n";
                        if ($task->description) {
                            $userContext .= "  Descrição: {$task->description}\n";
                        }
                        $userContext .= "\n";
                    }
                } else {
                    $userContext .= "=== ANDAMENTOS RECENTES ===\n";
                    $userContext .= "Nenhum andamento registrado ainda.\n\n";
                }
                
                $userContext .= "=== PERGUNTA DO USUÁRIO ===\n";
                
                Log::info('Case context loaded', [
                    'case_id' => $case->id, 
                    'client_name' => $case->client_name,
                    'employment_relationships' => $employmentRelationships->count(),
                    'documents' => $documents->count(),
                    'tasks' => $tasks->count(),
                ]);
            }
        }

        $prompt = $userContext . $request->message;

        try {
            Log::info('Calling OpenAI API', ['prompt_length' => strlen($prompt)]);
            
            $response = $openAi->chat($prompt);
            
            Log::info('OpenAI API response', [
                'success' => $response['success'] ?? false,
                'content_length' => strlen($response['content'] ?? ''),
            ]);
            
            return response()->json([
                'success' => true,
                'response' => $response['content'] ?? 'Resposta não disponível',
            ]);
        } catch (\Exception $e) {
            Log::error('Erro no chat OpenAI: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'response' => 'Desculpe, ocorreu um erro ao processar sua mensagem. Tente novamente em alguns instantes.',
            ], 500);
        }
    }
} 