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
        try {
            \Log::info('Cases index method called');
            
            $user = auth()->user();
            $companyId = $user->company_id;
            
            // Filtrar por empresa do usuário logado
            $query = LegalCase::with(['assignedTo', 'createdBy'])
                ->where('company_id', $companyId);
            
            // Aplicar filtros de busca
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where('client_name', 'like', "%{$search}%");
            }
            
            if ($request->filled('status')) {
                $query->where('status', $request->get('status'));
            }
            
            if ($request->filled('assigned_to')) {
                $query->where('assigned_to', $request->get('assigned_to'));
            }
            
            \Log::info('Query built, getting cases for company:', ['company_id' => $companyId]);
            
            $cases = $query->orderBy('created_at', 'desc')->paginate(15);
            
            \Log::info('Cases retrieved successfully', [
                'total' => $cases->total(),
                'current_page' => $cases->currentPage(),
                'per_page' => $cases->perPage(),
                'data_count' => count($cases->items())
            ]);

            $users = User::select('id', 'name')->get();
            $statuses = [
                'pendente' => 'Pendente',
                'em_coleta' => 'Em Coleta',
                'aguarda_peticao' => 'Aguarda Petição',
                'protocolado' => 'Protocolado',
                'concluido' => 'Concluído',
                'rejeitado' => 'Rejeitado',
            ];

            \Log::info('Rendering Inertia response...');

            return Inertia::render('Cases/Index', [
                'cases' => $cases,
                'users' => $users,
                'statuses' => $statuses,
                'filters' => $request->only(['search', 'status', 'assigned_to']),
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error in cases index:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
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
        \Log::info('CaseController@store called');
        \Log::info('Request data:', $request->all());
        \Log::info('Request has vinculos_empregaticios:', ['has' => $request->has('vinculos_empregaticios')]);
        \Log::info('vinculos_empregaticios value:', ['value' => $request->input('vinculos_empregaticios')]);
        \Log::info('vinculos_empregaticios type:', ['type' => gettype($request->input('vinculos_empregaticios'))]);
        \Log::info('vinculos_empregaticios count:', ['count' => is_array($request->input('vinculos_empregaticios')) ? count($request->input('vinculos_empregaticios')) : 'not array']);
        
        $validated = $request->validate([
            'client_name' => 'required|string|max:255',
            'client_cpf' => 'required|string|max:14',
            'vinculos_empregaticios' => 'nullable|array',
        ]);

        \Log::info('Validated data:', $validated);

        $validated['case_number'] = $this->generateCaseNumber();
        $validated['created_by'] = auth()->id();
        $validated['company_id'] = auth()->user()->company_id; // ← GARANTIR COMPANY_ID
        $validated['status'] = 'pendente';

        \Log::info('About to create case with data:', $validated);

        $case = LegalCase::create($validated);

        \Log::info('Case created with ID:', ['id' => $case->id]);

        // Salvar vínculos empregatícios se fornecidos
        if (!empty($request->vinculos_empregaticios)) {
            \Log::info('Saving employment relationships:', $request->vinculos_empregaticios);
            
            foreach ($request->vinculos_empregaticios as $vinculo) {
                \Log::info('Processing vinculo:', $vinculo);
                
                $case->employmentRelationships()->create([
                    'employer_name' => $vinculo['empregador'] ?? '',
                    'employer_cnpj' => $vinculo['cnpj'] ?? '',
                    'start_date' => $this->parseDate($vinculo['data_inicio'] ?? ''),
                    'end_date' => $this->parseDate($vinculo['data_fim'] ?? ''),
                    'salary' => $this->parseSalary($vinculo['salario'] ?? ''),
                    'is_active' => true, // Todos os vínculos começam como PENDENTE (aguardando coleta)
                    'notes' => 'Extraído automaticamente do CNIS',
                ]);
            }
            
            \Log::info('Employment relationships saved successfully');
        } else {
            \Log::info('No employment relationships to save');
        }

        \Log::info('Redirecting to case show page');

        return redirect()->route('cases.vinculos', $case)
            ->with('success', 'Caso criado com sucesso! Vínculos empregatícios extraídos automaticamente.');
    }

    private function parseDate($dateString): ?string
    {
        if (empty($dateString) || $dateString === 'sem data fim') {
            return null;
        }

        // Converte dd/mm/yyyy para yyyy-mm-dd
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $dateString)) {
            $date = \DateTime::createFromFormat('d/m/Y', $dateString);
            return $date ? $date->format('Y-m-d') : null;
        }

        return null;
    }

    private function parseSalary($salaryString): ?float
    {
        if (empty($salaryString)) {
            return null;
        }

        // Remove caracteres não numéricos exceto vírgula e ponto
        $cleanSalary = preg_replace('/[^\d,.]/', '', $salaryString);
        
        // Converte vírgula para ponto para float
        $cleanSalary = str_replace(',', '.', $cleanSalary);
        
        return (float) $cleanSalary;
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
        \Log::info('CaseController@edit called', [
            'case_id' => $case->id,
            'case_status' => $case->status,
            'case_data' => $case->toArray()
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

        return Inertia::render('Cases/Edit', [
            'case' => $case,
            'users' => $users,
            'benefitTypes' => $benefitTypes,
        ]);
    }

    public function update(Request $request, LegalCase $case)
    {
        \Log::info('CaseController@update called', [
            'method' => $request->method(),
            'url' => $request->url(),
            'path' => $request->path(),
            'case_id' => $case->id,
            'request_data' => $request->all(),
            'status_value' => $request->input('status'),
            'status_type' => gettype($request->input('status')),
            'headers' => $request->headers->all()
        ]);

        // Para atualizações parciais, validar apenas os campos enviados
        $validationRules = [];
        
        if ($request->has('client_name')) {
            $validationRules['client_name'] = 'required|string|max:255';
        }
        
        if ($request->has('client_cpf')) {
            $validationRules['client_cpf'] = 'required|string|max:14';
        }
        
        if ($request->has('benefit_type')) {
            $validationRules['benefit_type'] = 'nullable|string';
        }
        
        if ($request->has('status')) {
            $validationRules['status'] = 'required|in:pendente,em_coleta,aguarda_peticao,protocolado,concluido,rejeitado';
            \Log::info('Status validation rule added:', ['rule' => $validationRules['status']]);
        }
        
        if ($request->has('description')) {
            $validationRules['description'] = 'nullable|string';
        }
        
        if ($request->has('notes')) {
            $validationRules['notes'] = 'nullable|string';
        }

        \Log::info('Validation rules:', $validationRules);

        $validated = $request->validate($validationRules);

        \Log::info('Validated data:', $validated);

        // Verificar se o benefit_type está sendo alterado e criar workflow se necessário
        $oldBenefitType = $case->benefit_type;
        $newBenefitType = $validated['benefit_type'] ?? $oldBenefitType;
        
        if ($newBenefitType && $newBenefitType !== $oldBenefitType) {
            \Log::info('Benefit type changed, creating workflow', [
                'old' => $oldBenefitType,
                'new' => $newBenefitType
            ]);
            
            $this->createWorkflowForCase($case, $newBenefitType);
        }

        $case->update($validated);

        \Log::info('Case updated successfully', [
            'case_id' => $case->id,
            'updated_fields' => array_keys($validated)
        ]);

        return redirect()->route('cases.show', $case)
            ->with('success', 'Caso atualizado com sucesso!');
    }

    private function createWorkflowForCase(LegalCase $case, string $benefitType): void
    {
        // Buscar template de workflow para o tipo de benefício
        $template = \App\Models\WorkflowTemplate::where('benefit_type', $benefitType)
            ->where('is_active', true)
            ->first();

        if (!$template) {
            \Log::warning('No workflow template found for benefit type', ['benefit_type' => $benefitType]);
            return;
        }

        // Remover tarefas de workflow existentes para este caso
        $case->tasks()->where('is_workflow_task', true)->delete();

        // Criar tarefas baseadas no template
        foreach ($template->tasks as $taskTemplate) {
            $case->tasks()->create([
                'workflow_template_id' => $template->id,
                'title' => $taskTemplate['title'],
                'description' => $taskTemplate['description'],
                'status' => 'pending',
                'priority' => 'medium',
                'due_date' => now()->addDays(7), // 7 dias para completar cada tarefa
                'assigned_to' => auth()->id(),
                'created_by' => auth()->id(),
                'required_documents' => $taskTemplate['required_documents'] ?? [],
                'order' => $taskTemplate['order'],
                'is_workflow_task' => true,
            ]);
        }

        \Log::info('Workflow created for case', [
            'case_id' => $case->id,
            'template_id' => $template->id,
            'tasks_created' => count($template->tasks)
        ]);
    }

    public function destroy(LegalCase $case)
    {
        $case->delete();

        return redirect()->route('cases.index')
            ->with('success', 'Caso excluído com sucesso!');
    }

    public function vinculos(LegalCase $case)
    {
        \Log::info('Vinculos method called for case:', ['case_id' => $case->id, 'case_number' => $case->case_number]);
        $case->load('employmentRelationships');
        \Log::info('Employment relationships loaded:', [
            'count' => $case->employmentRelationships->count(),
            'relationships' => $case->employmentRelationships->toArray()
        ]);

        return Inertia::render('Cases/Vinculos', [
            'case' => array_merge(
                $case->toArray(),
                ['employment_relationships' => $case->employmentRelationships->toArray()]
            ),
        ]);
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
        $user = auth()->user();
        $isSuperAdmin = $user->isSuperAdmin();
        
        // Dashboard do Super Admin
        if ($isSuperAdmin) {
            // Estatísticas de empresas
            $companiesStats = [
                'total' => \App\Models\Company::count(),
                'active' => \App\Models\Company::where('is_active', true)->count(),
            ];
            
            // Estatísticas de usuários
            $usersStats = [
                'total' => \App\Models\User::count(),
                'active' => \App\Models\User::where('is_active', true)->count(),
            ];
            
            // Estatísticas de templates de petição
            $petitionTemplatesStats = [
                'total' => \App\Models\PetitionTemplate::count(),
                'active' => \App\Models\PetitionTemplate::where('is_active', true)->count(),
            ];
            
            // Estatísticas de templates de workflow
            $workflowTemplatesStats = [
                'total' => \App\Models\WorkflowTemplate::count(),
                'active' => \App\Models\WorkflowTemplate::where('is_active', true)->count(),
            ];
            
            // Dados financeiros
            $financial = [
                'monthly_revenue' => \App\Models\Payment::where('created_at', '>=', now()->startOfMonth())
                    ->where('status', 'paid')
                    ->sum('amount'),
                'recent_payments' => \App\Models\Payment::where('created_at', '>=', now()->subDays(30))
                    ->where('status', 'paid')
                    ->count(),
                'active_subscriptions' => \App\Models\CompanySubscription::where('status', 'active')
                    ->where('current_period_end', '>', now())
                    ->count(),
            ];
            
            // Empresas recentes
            $recentCompanies = \App\Models\Company::with(['users', 'cases'])
                ->withCount(['users', 'cases'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
            
            // Atividade recente (7 dias)
            $recent_activity = [
                'new_companies' => \App\Models\Company::where('created_at', '>=', now()->subDays(7))->count(),
                'new_users' => \App\Models\User::where('created_at', '>=', now()->subDays(7))->count(),
                'recent_payments' => \App\Models\Payment::where('created_at', '>=', now()->subDays(7))
                    ->where('status', 'paid')
                    ->count(),
            ];
            
            return Inertia::render('dashboard', [
                'isSuperAdmin' => true,
                'companiesStats' => $companiesStats,
                'usersStats' => $usersStats,
                'petitionTemplatesStats' => $petitionTemplatesStats,
                'workflowTemplatesStats' => $workflowTemplatesStats,
                'financial' => $financial,
                'recentCompanies' => $recentCompanies,
                'recent_activity' => $recent_activity,
            ]);
        }
        
        // Dashboard do Usuário Normal
        $companyId = $user->company_id;
        
        // Verificar se o usuário tem empresa
        if (!$companyId) {
            return Inertia::render('dashboard', [
                'isSuperAdmin' => false,
                'stats' => [
                    'total_cases' => 0,
                    'pendente' => 0,
                    'em_coleta' => 0,
                    'aguarda_peticao' => 0,
                    'protocolado' => 0,
                    'concluido' => 0,
                    'rejeitado' => 0,
                ],
                'recentCases' => [],
                'casesByStatus' => [],
                'casesByMonth' => [],
                'error' => 'Usuário não possui empresa associada.'
            ]);
        }
        
        // Buscar estatísticas
        $stats = [
            'total_cases' => LegalCase::where('company_id', $companyId)->count(),
            'pendente' => LegalCase::where('company_id', $companyId)->where('status', 'pendente')->count(),
            'em_coleta' => LegalCase::where('company_id', $companyId)->where('status', 'em_coleta')->count(),
            'aguarda_peticao' => LegalCase::where('company_id', $companyId)->where('status', 'aguarda_peticao')->count(),
            'protocolado' => LegalCase::where('company_id', $companyId)->where('status', 'protocolado')->count(),
            'concluido' => LegalCase::where('company_id', $companyId)->where('status', 'concluido')->count(),
            'rejeitado' => LegalCase::where('company_id', $companyId)->where('status', 'rejeitado')->count(),
        ];

        // Buscar casos recentes
        $recentCases = LegalCase::where('company_id', $companyId)
            ->with(['assignedTo', 'createdBy'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Buscar distribuição por status
        $casesByStatus = LegalCase::where('company_id', $companyId)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status');

        // Buscar evolução mensal
        $casesByMonth = LegalCase::where('company_id', $companyId)
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('count(*) as total')
            )
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return Inertia::render('dashboard', [
            'isSuperAdmin' => false,
            'stats' => $stats,
            'recentCases' => $recentCases->toArray(),
            'casesByStatus' => $casesByStatus->toArray(),
            'casesByMonth' => $casesByMonth->toArray(),
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

    public function coletas(Request $request)
    {
        // Totais para os cards
        $totalVinculos = \App\Models\EmploymentRelationship::count();
        $clientesAtivos = \App\Models\LegalCase::whereHas('employmentRelationships', function($q) {
            $q->whereNull('collected_at');
        })->count();
        $clientesFinalizados = \App\Models\LegalCase::whereDoesntHave('employmentRelationships', function($q) {
            $q->whereNull('collected_at');
        })->count();
        $empresasPendentes = \App\Models\EmploymentRelationship::whereNull('collected_at')->distinct('employer_cnpj')->count('employer_cnpj');
        $empresasConcluidas = \App\Models\EmploymentRelationship::whereNotNull('collected_at')->distinct('employer_cnpj')->count('employer_cnpj');
        $coletasAtrasadas = \App\Models\LegalCase::whereHas('employmentRelationships', function($q) {
            $q->whereNull('collected_at');
        })->where('created_at', '<', now()->subMonths(6))->count();

        // Busca
        $tab = $request->get('tab', 'clientes');
        $search = $request->get('search', '');
        $resultados = [];
        if ($tab === 'clientes' && $search) {
            $resultados = \App\Models\LegalCase::where('client_name', 'like', "%{$search}%")
                ->orWhere('client_cpf', 'like', "%{$search}%")
                ->with('employmentRelationships')
                ->get();
        } elseif ($tab === 'empresas' && $search) {
            $resultados = \App\Models\EmploymentRelationship::where('employer_name', 'like', "%{$search}%")
                ->with('legalCase')
                ->get();
            // Forçar serialização correta
            $resultados->each(function($v) { $v->legalCase = $v->legalCase; });
        } elseif ($tab === 'cargos' && $search) {
            $resultados = \App\Models\EmploymentRelationship::where('position', 'like', "%{$search}%")
                ->with('legalCase')
                ->get();
            $resultados->each(function($v) { $v->legalCase = $v->legalCase; });
        }

        return Inertia::render('Coletas', [
            'cards' => [
                'totalVinculos' => $totalVinculos,
                'clientesAtivos' => $clientesAtivos,
                'clientesFinalizados' => $clientesFinalizados,
                'empresasPendentes' => $empresasPendentes,
                'empresasConcluidas' => $empresasConcluidas,
                'coletasAtrasadas' => $coletasAtrasadas,
            ],
            'tab' => $tab,
            'search' => $search,
            'resultados' => $resultados,
        ]);
    }
} 