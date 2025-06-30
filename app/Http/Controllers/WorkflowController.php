<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\LegalCase;
use App\Models\User;
use App\Models\WorkflowTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class WorkflowController extends Controller
{
    public function index(Request $request)
    {
        // Determinar a aba atual
        $currentTab = $request->get('tab', 'tasks');
        
        if ($currentTab === 'templates') {
            return $this->templatesIndex($request);
        }
        
        $query = Task::with(['case', 'assignedTo', 'createdBy', 'workflowTemplate']);
        
        // Filtrar por empresa se não for super admin
        if (!auth()->user()->isSuperAdmin()) {
            $query->whereHas('case', function($q) {
                $q->byCompany(auth()->user()->company_id);
            });
        }
        
        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('case', function($caseQuery) use ($search) {
                      $caseQuery->where('client_name', 'like', "%{$search}%");
                  });
            });
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }
        
        if ($request->filled('priority')) {
            $query->where('priority', $request->get('priority'));
        }
        
        $workflows = $query->orderBy('due_date', 'asc')->paginate(15);
        
        // Estatísticas com filtro por empresa
        $statsQuery = Task::query();
        if (!auth()->user()->isSuperAdmin()) {
            $statsQuery->whereHas('case', function($q) {
                $q->byCompany(auth()->user()->company_id);
            });
        }
        
        $stats = [
            'total' => (clone $statsQuery)->count(),
            'pending' => (clone $statsQuery)->where('status', 'pending')->count(),
            'in_progress' => (clone $statsQuery)->where('status', 'in_progress')->count(),
            'completed' => (clone $statsQuery)->where('status', 'completed')->count(),
            'overdue' => (clone $statsQuery)->where('due_date', '<', now())->where('status', '!=', 'completed')->count(),
        ];
        
        // Clientes com workflows - buscar dados mais detalhados
        $clientsQuery = LegalCase::select('id', 'client_name', 'case_number', 'benefit_type')
            ->with(['tasks' => function($query) {
                $query->select('id', 'case_id', 'status');
            }])
            ->whereHas('tasks');
            
        // Filtrar por empresa se não for super admin
        if (!auth()->user()->isSuperAdmin()) {
            $clientsQuery->byCompany(auth()->user()->company_id);
        }
        
        $clientsWithWorkflows = $clientsQuery->get()
            ->map(function($case) {
                $totalTasks = $case->tasks->count();
                $completedTasks = $case->tasks->where('status', 'completed')->count();
                $progressPercentage = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
                
                return [
                    'id' => $case->id,
                    'client_name' => $case->client_name,
                    'case_number' => $case->case_number,
                    'benefit_type' => $case->benefit_type,
                    'total_tasks' => $totalTasks,
                    'completed_tasks' => $completedTasks,
                    'progress_percentage' => $progressPercentage,
                ];
            })
            ->sortByDesc('progress_percentage')
            ->take(10)
            ->values();
        
        $usersQuery = User::select('id', 'name');
        
        // Filtrar por empresa se não for super admin
        if (!auth()->user()->isSuperAdmin()) {
            $usersQuery->where('company_id', auth()->user()->company_id);
        }
        
        $users = $usersQuery->get();
        
        $statuses = [
            'pending' => 'Pendente',
            'in_progress' => 'Em Andamento',
            'completed' => 'Concluído',
            'cancelled' => 'Cancelado',
        ];
        
        $priorities = [
            'low' => 'Baixa',
            'medium' => 'Média',
            'high' => 'Alta',
            'urgent' => 'Urgente',
        ];
        
        return Inertia::render('Workflows/Index', [
            'workflows' => $workflows,
            'stats' => $stats,
            'clientsWithWorkflows' => $clientsWithWorkflows,
            'users' => $users,
            'statuses' => $statuses,
            'priorities' => $priorities,
            'filters' => $request->only(['search', 'status', 'priority']),
            'currentTab' => $currentTab,
        ]);
    }
    
    public function templatesIndex(Request $request)
    {
        $query = WorkflowTemplate::query();
        
        // Filtrar templates disponíveis para a empresa
        if (auth()->user()->isSuperAdmin()) {
            // Super admin vê todos os templates
            // Sem filtro adicional
        } else {
            // Usuários normais veem apenas templates globais + da sua empresa
            $query->availableForCompany(auth()->user()->company_id);
        }
        
        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('benefit_type', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('benefit_type')) {
            $query->where('benefit_type', $request->get('benefit_type'));
        }
        
        if ($request->filled('status')) {
            $isActive = $request->get('status') === 'active';
            $query->where('is_active', $isActive);
        }

        if ($request->filled('scope')) {
            if ($request->scope === 'global') {
                $query->global();
            } elseif ($request->scope === 'company' && !auth()->user()->isSuperAdmin()) {
                $query->byCompany(auth()->user()->company_id);
            }
        }
        
        $templates = $query->orderBy('benefit_type')->orderBy('name')->paginate(15);
        
        // Estatísticas dos templates
        $baseQuery = WorkflowTemplate::query();
        if (!auth()->user()->isSuperAdmin()) {
            $baseQuery->availableForCompany(auth()->user()->company_id);
        }
        
        $templateStats = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('is_active', true)->count(),
            'inactive' => (clone $baseQuery)->where('is_active', false)->count(),
            'global' => (clone $baseQuery)->where('is_global', true)->count(),
            'company' => auth()->user()->isSuperAdmin() 
                ? (clone $baseQuery)->where('is_global', false)->count()
                : (clone $baseQuery)->where('company_id', auth()->user()->company_id)->count(),
        ];
        
        $benefitTypes = WorkflowTemplate::getBenefitTypes();
        
        return Inertia::render('Workflows/Index', [
            'templates' => $templates,
            'templateStats' => $templateStats,
            'benefitTypes' => $benefitTypes,
            'filters' => $request->only(['search', 'benefit_type', 'status', 'scope']),
            'currentTab' => 'templates',
            'canManageGlobal' => auth()->user()->isSuperAdmin(),
        ]);
    }
    
    public function create()
    {
        $casesQuery = LegalCase::select('id', 'client_name', 'case_number');
        $usersQuery = User::select('id', 'name');
        
        // Filtrar por empresa se não for super admin
        if (!auth()->user()->isSuperAdmin()) {
            $casesQuery->byCompany(auth()->user()->company_id);
            $usersQuery->where('company_id', auth()->user()->company_id);
        }
        
        $cases = $casesQuery->get();
        $users = $usersQuery->get();
        
        $priorities = [
            'low' => 'Baixa',
            'medium' => 'Média',
            'high' => 'Alta',
            'urgent' => 'Urgente',
        ];
        
        return Inertia::render('Workflows/Create', [
            'cases' => $cases,
            'users' => $users,
            'priorities' => $priorities,
        ]);
    }
    
    public function createTemplate()
    {
        $benefitTypes = WorkflowTemplate::getBenefitTypes();
        
        return Inertia::render('Workflows/CreateTemplate', [
            'benefitTypes' => $benefitTypes,
            'canManageGlobal' => auth()->user()->isSuperAdmin(),
        ]);
    }
    
    public function storeTemplate(Request $request)
    {
        $validated = $request->validate([
            'benefit_type' => 'required|string',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'tasks' => 'required|string',
            'is_global' => 'boolean',
        ]);
        
        // Decodificar as tarefas do JSON
        $tasks = json_decode($validated['tasks'], true);
        
        if (!$tasks || !is_array($tasks)) {
            return back()->withErrors(['tasks' => 'Formato de tarefas inválido']);
        }
        
        // Validar estrutura das tarefas
        foreach ($tasks as $index => $task) {
            if (!isset($task['title']) || !isset($task['description']) || !isset($task['order'])) {
                return back()->withErrors(['tasks' => "Tarefa #{$index} está incompleta"]);
            }
        }

        $data = [
            'benefit_type' => $validated['benefit_type'],
            'name' => $validated['name'],
            'description' => $validated['description'],
            'tasks' => $tasks,
            'is_active' => true,
        ];

        // Se for super admin e marcou como global, não precisa de company_id
        if (auth()->user()->isSuperAdmin() && $request->boolean('is_global')) {
            $data['is_global'] = true;
            $data['company_id'] = null;
        } else {
            // Senão, é um template da empresa
            $data['is_global'] = false;
            $data['company_id'] = auth()->user()->company_id;
        }
        
        WorkflowTemplate::create($data);
        
        return redirect()->route('workflows.index', ['tab' => 'templates'])
            ->with('success', 'Template de workflow criado com sucesso!');
    }
    
    public function editTemplate(WorkflowTemplate $template)
    {
        $benefitTypes = WorkflowTemplate::getBenefitTypes();
        
        return Inertia::render('Workflows/EditTemplate', [
            'template' => $template,
            'benefitTypes' => $benefitTypes,
            'canManageGlobal' => auth()->user()->isSuperAdmin(),
        ]);
    }
    
    public function updateTemplate(Request $request, WorkflowTemplate $template)
    {
        $validated = $request->validate([
            'benefit_type' => 'required|string',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'tasks' => 'required|string',
            'is_active' => 'boolean',
            'is_global' => 'boolean',
        ]);
        
        // Decodificar as tarefas do JSON
        $tasks = json_decode($validated['tasks'], true);
        
        if (!$tasks || !is_array($tasks)) {
            return back()->withErrors(['tasks' => 'Formato de tarefas inválido']);
        }

        $data = [
            'benefit_type' => $validated['benefit_type'],
            'name' => $validated['name'],
            'description' => $validated['description'],
            'tasks' => $tasks,
            'is_active' => $validated['is_active'] ?? $template->is_active,
        ];

        // Se for super admin e marcou como global, não precisa de company_id
        if (auth()->user()->isSuperAdmin() && $request->boolean('is_global')) {
            $data['is_global'] = true;
            $data['company_id'] = null;
        } else {
            // Senão, é um template da empresa
            $data['is_global'] = false;
            if (!$template->company_id) {
                $data['company_id'] = auth()->user()->company_id;
            }
        }
        
        $template->update($data);
        
        return redirect()->route('workflows.index', ['tab' => 'templates'])
            ->with('success', 'Template de workflow atualizado com sucesso!');
    }
    
    public function toggleTemplate(WorkflowTemplate $template)
    {
        $template->update(['is_active' => !$template->is_active]);
        
        $status = $template->is_active ? 'ativado' : 'desativado';
        
        return back()->with('success', "Template {$status} com sucesso!");
    }
    
    public function destroyTemplate(WorkflowTemplate $template)
    {
        // Verificar se existem tarefas usando este template
        $tasksCount = Task::where('workflow_template_id', $template->id)->count();
        
        if ($tasksCount > 0) {
            return back()->withErrors(['template' => "Não é possível excluir este template pois existem {$tasksCount} tarefas associadas a ele."]);
        }
        
        $template->delete();
        
        return back()->with('success', 'Template de workflow excluído com sucesso!');
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'case_id' => 'required|exists:cases,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'due_date' => 'required|date|after:today',
            'assigned_to' => 'nullable|exists:users,id',
            'required_documents' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);
        
        $validated['status'] = 'pending';
        $validated['created_by'] = auth()->id();
        
        Task::create($validated);
        
        return redirect()->route('workflows.index')
            ->with('success', 'Workflow criado com sucesso!');
    }
    
    public function show(Task $workflow)
    {
        $workflow->load(['case', 'assignedTo', 'createdBy', 'workflowTemplate']);
        
        return Inertia::render('Workflows/Show', [
            'workflow' => $workflow,
        ]);
    }
    
    public function edit(Task $workflow)
    {
        $casesQuery = LegalCase::select('id', 'client_name', 'case_number');
        $usersQuery = User::select('id', 'name');
        
        // Filtrar por empresa se não for super admin
        if (!auth()->user()->isSuperAdmin()) {
            $casesQuery->byCompany(auth()->user()->company_id);
            $usersQuery->where('company_id', auth()->user()->company_id);
        }
        
        $cases = $casesQuery->get();
        $users = $usersQuery->get();
        
        $priorities = [
            'low' => 'Baixa',
            'medium' => 'Média',
            'high' => 'Alta',
            'urgent' => 'Urgente',
        ];
        
        $statuses = [
            'pending' => 'Pendente',
            'in_progress' => 'Em Andamento',
            'completed' => 'Concluído',
            'cancelled' => 'Cancelado',
        ];
        
        return Inertia::render('Workflows/Edit', [
            'workflow' => $workflow,
            'cases' => $cases,
            'users' => $users,
            'priorities' => $priorities,
            'statuses' => $statuses,
        ]);
    }
    
    public function update(Request $request, Task $workflow)
    {
        $validated = $request->validate([
            'case_id' => 'required|exists:cases,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:pending,in_progress,completed,cancelled',
            'priority' => 'required|in:low,medium,high,urgent',
            'due_date' => 'required|date',
            'assigned_to' => 'nullable|exists:users,id',
            'required_documents' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);
        
        if ($validated['status'] === 'completed') {
            $validated['completed_at'] = now();
        }
        
        $workflow->update($validated);
        
        return redirect()->route('workflows.index')
            ->with('success', 'Workflow atualizado com sucesso!');
    }
    
    public function destroy(Task $workflow)
    {
        $workflow->delete();
        
        return redirect()->route('workflows.index')
            ->with('success', 'Workflow excluído com sucesso!');
    }
} 