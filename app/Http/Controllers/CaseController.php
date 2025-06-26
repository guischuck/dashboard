<?php

namespace App\Http\Controllers;

use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class CaseController extends Controller
{
    public function index(Request $request)
    {
        $query = LegalCase::with(['assignedTo', 'createdBy', 'inssProcesses'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('case_number', 'like', "%{$search}%")
                      ->orWhere('client_name', 'like', "%{$search}%")
                      ->orWhere('client_cpf', 'like', "%{$search}%");
                });
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->assigned_to, function ($query, $assignedTo) {
                $query->where('assigned_to', $assignedTo);
            });

        $cases = $query->orderBy('created_at', 'desc')->paginate(15);

        $users = User::select('id', 'name')->get();
        $statuses = [
            'pending' => 'Pendente',
            'analysis' => 'Em Análise',
            'completed' => 'Concluído',
            'requirement' => 'Exigência',
            'rejected' => 'Rejeitado',
        ];

        return Inertia::render('Cases/Index', [
            'cases' => $cases,
            'users' => $users,
            'statuses' => $statuses,
            'filters' => $request->only(['search', 'status', 'assigned_to']),
        ]);
    }

    public function create()
    {
        $benefitTypes = [
            'aposentadoria_por_idade' => 'Aposentadoria por Idade',
            'aposentadoria_por_tempo_contribuicao' => 'Aposentadoria por Tempo de Contribuição',
            'aposentadoria_professor' => 'Aposentadoria Professor',
            'aposentadoria_pcd' => 'Aposentadoria PCD',
            'aposentadoria_especial' => 'Aposentadoria Especial',
        ];

        return Inertia::render('Cases/Create', [
            'benefitTypes' => $benefitTypes,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_name' => 'required|string|max:255',
            'client_cpf' => 'required|string|max:14',
            'benefit_type' => 'required|string',
            'description' => 'nullable|string',
        ]);

        $validated['case_number'] = $this->generateCaseNumber();
        $validated['created_by'] = auth()->id();
        $validated['status'] = 'pending';

        $case = LegalCase::create($validated);

        return redirect()->route('cases.show', $case)
            ->with('success', 'Caso criado com sucesso!');
    }

    public function show(LegalCase $case)
    {
        $case->load([
            'assignedTo',
            'createdBy',
            'inssProcesses',
            'employmentRelationships',
            'documents',
            'petitions',
            'tasks' => function ($query) {
                $query->orderBy('due_date', 'asc');
            },
        ]);

        $users = User::select('id', 'name')->get();
        $benefitTypes = [
            'aposentadoria_por_idade' => 'Aposentadoria por Idade',
            'aposentadoria_por_tempo_contribuicao' => 'Aposentadoria por Tempo de Contribuição',
            'aposentadoria_por_invalidez' => 'Aposentadoria por Invalidez',
            'auxilio_doenca' => 'Auxílio-Doença',
            'beneficio_por_incapacidade' => 'Benefício por Incapacidade',
            'pensao_por_morte' => 'Pensão por Morte',
            'auxilio_acidente' => 'Auxílio-Acidente',
            'salario_maternidade' => 'Salário-Maternidade',
            'outro' => 'Outro',
        ];

        return Inertia::render('Cases/Show', [
            'case' => $case,
            'users' => $users,
            'benefitTypes' => $benefitTypes,
        ]);
    }

    public function edit(LegalCase $case)
    {
        $users = User::select('id', 'name')->get();
        $benefitTypes = [
            'aposentadoria_por_idade' => 'Aposentadoria por Idade',
            'aposentadoria_por_tempo_contribuicao' => 'Aposentadoria por Tempo de Contribuição',
            'aposentadoria_por_invalidez' => 'Aposentadoria por Invalidez',
            'auxilio_doenca' => 'Auxílio-Doença',
            'beneficio_por_incapacidade' => 'Benefício por Incapacidade',
            'pensao_por_morte' => 'Pensão por Morte',
            'auxilio_acidente' => 'Auxílio-Acidente',
            'salario_maternidade' => 'Salário-Maternidade',
            'outro' => 'Outro',
        ];

        return Inertia::render('Cases/Edit', [
            'case' => $case,
            'users' => $users,
            'benefitTypes' => $benefitTypes,
        ]);
    }

    public function update(Request $request, LegalCase $case)
    {
        $validated = $request->validate([
            'client_name' => 'required|string|max:255',
            'client_cpf' => 'required|string|max:14',
            'benefit_type' => 'required|string',
            'description' => 'nullable|string',
            'estimated_value' => 'nullable|numeric|min:0',
            'success_fee' => 'nullable|numeric|min:0|max:100',
            'filing_date' => 'nullable|date',
            'decision_date' => 'nullable|date',
            'status' => 'required|in:pending,analysis,completed,requirement,rejected',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $case->update($validated);

        return redirect()->route('cases.show', $case)
            ->with('success', 'Caso atualizado com sucesso!');
    }

    public function destroy(LegalCase $case)
    {
        $case->delete();

        return redirect()->route('cases.index')
            ->with('success', 'Caso excluído com sucesso!');
    }

    private function generateCaseNumber(): string
    {
        $year = date('Y');
        $lastCase = LegalCase::where('case_number', 'like', "CASE-{$year}-%")
            ->orderBy('case_number', 'desc')
            ->first();

        if ($lastCase) {
            $lastNumber = (int) substr($lastCase->case_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf("CASE-%s-%04d", $year, $newNumber);
    }

    public function dashboard()
    {
        $stats = [
            'total_cases' => LegalCase::count(),
            'pending_cases' => LegalCase::where('status', 'pending')->count(),
            'analysis_cases' => LegalCase::where('status', 'analysis')->count(),
            'completed_cases' => LegalCase::where('status', 'completed')->count(),
            'requirement_cases' => LegalCase::where('status', 'requirement')->count(),
            'rejected_cases' => LegalCase::where('status', 'rejected')->count(),
        ];

        $recentCases = LegalCase::with(['assignedTo', 'createdBy'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $casesByStatus = LegalCase::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status');

        $casesByMonth = LegalCase::select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('count(*) as total')
            )
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return Inertia::render('Cases/Dashboard', [
            'stats' => $stats,
            'recentCases' => $recentCases,
            'casesByStatus' => $casesByStatus,
            'casesByMonth' => $casesByMonth,
        ]);
    }

    public function generateCaseDescription(Request $request)
    {
        $request->validate([
            'client_name' => 'required|string',
            'client_cpf' => 'required|string',
            'benefit_type' => 'required|string',
            'vinculos_empregaticios' => 'required|array',
        ]);

        try {
            $vinculos = $request->vinculos_empregaticios;
            $benefitType = $request->benefit_type;
            
            // Calcula tempo total de contribuição
            $totalContribuicao = 0;
            $maiorSalario = 0;
            $empregadores = [];
            
            foreach ($vinculos as $vinculo) {
                $inicio = \DateTime::createFromFormat('d/m/Y', $vinculo['data_inicio']);
                $fim = $vinculo['data_fim'] ? \DateTime::createFromFormat('d/m/Y', $vinculo['data_fim']) : new \DateTime();
                
                if ($inicio && $fim) {
                    $diff = $inicio->diff($fim);
                    $totalContribuicao += $diff->y + ($diff->m / 12) + ($diff->d / 365);
                }
                
                $salario = (float) str_replace(',', '.', $vinculo['salario']);
                if ($salario > $maiorSalario) {
                    $maiorSalario = $salario;
                }
                
                $empregadores[] = $vinculo['empregador'];
            }
            
            $anosContribuicao = round($totalContribuicao, 1);
            $empregadoresUnicos = array_unique($empregadores);
            
            // Gera descrição baseada no tipo de benefício
            $description = "Caso de {$benefitType} para o cliente {$request->client_name} (CPF: {$request->client_cpf}).\n\n";
            $description .= "RESUMO DOS VÍNCULOS EMPREGATÍCIOS:\n";
            $description .= "- Tempo total de contribuição: {$anosContribuicao} anos\n";
            $description .= "- Maior remuneração: R$ " . number_format($maiorSalario, 2, ',', '.') . "\n";
            $description .= "- Empregadores: " . implode(', ', $empregadoresUnicos) . "\n\n";
            
            $description .= "VÍNCULOS DETALHADOS:\n";
            foreach ($vinculos as $index => $vinculo) {
                $description .= ($index + 1) . ". {$vinculo['empregador']}\n";
                $description .= "   Período: {$vinculo['data_inicio']} a " . ($vinculo['data_fim'] ?: 'Atual') . "\n";
                $description .= "   Remuneração: R$ {$vinculo['salario']}\n\n";
            }
            
            // Adiciona análise específica por tipo de benefício
            switch ($benefitType) {
                case 'aposentadoria_por_idade':
                    $description .= "ANÁLISE PARA APOSENTADORIA POR IDADE:\n";
                    $description .= "- Requisito: 65 anos (homem) ou 60 anos (mulher) + 15 anos de contribuição\n";
                    $description .= "- Cliente possui {$anosContribuicao} anos de contribuição\n";
                    break;
                    
                case 'aposentadoria_por_tempo_contribuicao':
                    $description .= "ANÁLISE PARA APOSENTADORIA POR TEMPO DE CONTRIBUIÇÃO:\n";
                    $description .= "- Requisito: 35 anos (homem) ou 30 anos (mulher) de contribuição\n";
                    $description .= "- Cliente possui {$anosContribuicao} anos de contribuição\n";
                    break;
                    
                case 'aposentadoria_professor':
                    $description .= "ANÁLISE PARA APOSENTADORIA DE PROFESSOR:\n";
                    $description .= "- Requisito: 30 anos (homem) ou 25 anos (mulher) de contribuição + exercício em funções de magistério\n";
                    $description .= "- Cliente possui {$anosContribuicao} anos de contribuição\n";
                    break;
                    
                case 'aposentadoria_pcd':
                    $description .= "ANÁLISE PARA APOSENTADORIA DE PCD:\n";
                    $description .= "- Requisito: Deficiência grave + tempo de contribuição variável\n";
                    $description .= "- Cliente possui {$anosContribuicao} anos de contribuição\n";
                    break;
                    
                case 'aposentadoria_especial':
                    $description .= "ANÁLISE PARA APOSENTADORIA ESPECIAL:\n";
                    $description .= "- Requisito: Exposição a agentes nocivos + tempo variável conforme grau de risco\n";
                    $description .= "- Cliente possui {$anosContribuicao} anos de contribuição\n";
                    break;
            }
            
            $description .= "\nPRÓXIMOS PASSOS:\n";
            $description .= "1. Verificar documentação complementar\n";
            $description .= "2. Analisar períodos de interrupção\n";
            $description .= "3. Calcular benefício estimado\n";
            $description .= "4. Preparar petição inicial";
            
            return response()->json([
                'success' => true,
                'description' => $description
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar descrição: ' . $e->getMessage()
            ], 500);
        }
    }
} 