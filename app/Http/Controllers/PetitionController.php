<?php

namespace App\Http\Controllers;

use App\Models\Petition;
use App\Models\PetitionTemplate;
use App\Models\LegalCase;
use App\Models\User;
use App\Services\OpenAiService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class PetitionController extends Controller
{
    public function index(Request $request)
    {
        $query = Petition::with(['legalCase', 'user', 'template'])
            ->orderBy('created_at', 'desc');

        // Filtrar por empresa se não for super admin
        if (!auth()->user()->isSuperAdmin()) {
            $query->whereHas('legalCase', function($q) {
                $q->byCompany(auth()->user()->company_id);
            });
        }

        // Filtros
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereHas('legalCase', function($q) use ($search) {
                      $q->where('client_name', 'like', "%{$search}%")
                        ->orWhere('case_number', 'like', "%{$search}%");
                  });
            });
        }

        $petitions = $query->paginate(15);

        return Inertia::render('Petitions/Index', [
            'petitions' => $petitions,
            'filters' => $request->only(['status', 'category', 'type', 'search']),
            'stats' => $this->getPetitionStats(),
            'templatesCount' => $this->getTemplatesCount(),
        ]);
    }

    public function create(Request $request)
    {
        $casesQuery = LegalCase::with(['employmentRelationships'])
            ->select('id', 'client_name', 'case_number', 'benefit_type', 'client_cpf', 'description')
            ->orderBy('client_name');

        // Filtrar por empresa se não for super admin
        if (!auth()->user()->isSuperAdmin()) {
            $casesQuery->byCompany(auth()->user()->company_id);
        }

        $cases = $casesQuery->get();

        $templatesQuery = PetitionTemplate::active()
            ->select('id', 'name', 'category', 'benefit_type', 'description')
            ->orderBy('name');

        // Filtrar templates disponíveis para a empresa
        if (auth()->user()->isSuperAdmin()) {
            // Super admin vê todos os templates
            // Sem filtro adicional
        } else {
            // Usuários normais veem apenas templates globais + da sua empresa
            $templatesQuery->availableForCompany(auth()->user()->company_id);
        }

        $templates = $templatesQuery->get()->groupBy('category');

        return Inertia::render('Petitions/Create', [
            'cases' => $cases,
            'templates' => $templates,
            'categories' => $this->getCategories(),
            'benefitTypes' => $this->getBenefitTypes(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'case_id' => 'required|exists:cases,id',
            'type' => 'required|in:template,ia',
            'template_id' => 'required_if:type,template|exists:petition_templates,id',
            'category' => 'required|string|max:100',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'template_variables' => 'nullable|array',
            'ai_prompt' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $petition = Petition::create([
                'case_id' => $request->case_id,
                'user_id' => auth()->id(),
                'template_id' => $request->template_id,
                'type' => $request->type,
                'category' => $request->category,
                'title' => $request->title,
                'content' => $request->content,
                'template_variables' => $request->template_variables,
                'ai_prompt' => $request->ai_prompt,
                'notes' => $request->notes,
                'status' => 'draft',
            ]);

            // Gerar arquivo .docx
            $fileName = $this->generateDocumentFile($petition);
            $petition->update(['file_path' => $fileName]);

            // Marcar como gerado se não for rascunho
            if ($request->type === 'ia' || $request->template_id) {
                $petition->markAsGenerated();
            }

            DB::commit();

            return redirect()->route('petitions.show', $petition)
                ->with('success', 'Petição criada com sucesso!');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating petition', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors(['error' => 'Erro ao criar petição: ' . $e->getMessage()]);
        }
    }

    public function show(Petition $petition)
    {
        $petition->load(['legalCase.employmentRelationships', 'user', 'template']);
        
        return Inertia::render('Petitions/Show', [
            'petition' => $petition,
            'canEdit' => $petition->status === 'draft',
            'canSubmit' => in_array($petition->status, ['draft', 'generated', 'reviewed']),
        ]);
    }

    public function edit(Petition $petition)
    {
        // Só permite editar se for rascunho
        if ($petition->status !== 'draft') {
            return redirect()->route('petitions.show', $petition)
                ->withErrors(['error' => 'Apenas petições em rascunho podem ser editadas.']);
        }

        $petition->load(['legalCase', 'template']);
        
        $templatesQuery = PetitionTemplate::active()
            ->select('id', 'name', 'category', 'benefit_type', 'description')
            ->orderBy('name');

        // Filtrar templates disponíveis para a empresa
        if (auth()->user()->isSuperAdmin()) {
            // Super admin vê todos os templates
            // Sem filtro adicional
        } else {
            // Usuários normais veem apenas templates globais + da sua empresa
            $templatesQuery->availableForCompany(auth()->user()->company_id);
        }

        $templates = $templatesQuery->get()->groupBy('category');

        return Inertia::render('Petitions/Edit', [
            'petition' => $petition,
            'templates' => $templates,
            'categories' => $this->getCategories(),
            'benefitTypes' => $this->getBenefitTypes(),
        ]);
    }

    public function update(Request $request, Petition $petition)
    {
        // Só permite atualizar se for rascunho
        if ($petition->status !== 'draft') {
            return back()->withErrors(['error' => 'Apenas petições em rascunho podem ser editadas.']);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'template_variables' => 'nullable|array',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $petition->update($request->validated());

            // Regenerar arquivo
            $fileName = $this->generateDocumentFile($petition);
            $petition->update(['file_path' => $fileName]);

            return redirect()->route('petitions.show', $petition)
                ->with('success', 'Petição atualizada com sucesso!');

        } catch (\Exception $e) {
            \Log::error('Error updating petition', [
                'petition_id' => $petition->id,
                'message' => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'Erro ao atualizar petição: ' . $e->getMessage()]);
        }
    }

    public function destroy(Petition $petition)
    {
        // Só permite excluir se for rascunho
        if ($petition->status !== 'draft') {
            return back()->withErrors(['error' => 'Apenas petições em rascunho podem ser excluídas.']);
        }

        try {
            // Excluir arquivo se existir
            if ($petition->file_path && Storage::disk('public')->exists($petition->file_path)) {
                Storage::disk('public')->delete($petition->file_path);
            }

            $petition->delete();

            return redirect()->route('petitions.index')
                ->with('success', 'Petição excluída com sucesso!');

        } catch (\Exception $e) {
            \Log::error('Error deleting petition', [
                'petition_id' => $petition->id,
                'message' => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'Erro ao excluir petição: ' . $e->getMessage()]);
        }
    }

    public function download(Petition $petition)
    {
        if (!$petition->file_path || !Storage::disk('public')->exists($petition->file_path)) {
            // Tentar regenerar o arquivo
            try {
                $fileName = $this->generateDocumentFile($petition);
                $petition->update(['file_path' => $fileName]);
            } catch (\Exception $e) {
                abort(404, 'Arquivo não encontrado e não foi possível regenerar');
            }
        }

        return Storage::disk('public')->download(
            $petition->file_path, 
            $petition->title . '.docx'
        );
    }

    public function generateWithAI(Request $request)
    {
        $request->validate([
            'case_id' => 'required|exists:cases,id',
            'category' => 'required|string',
            'prompt' => 'required|string|max:2000',
            'template_id' => 'nullable|exists:petition_templates,id',
        ]);

        set_time_limit(180); // 3 minutos

        try {
            $case = LegalCase::with(['employmentRelationships'])->findOrFail($request->case_id);
            
            // Preparar dados completos do caso
            $caseData = $this->prepareCaseDataForAI($case);
            
            // Se tem template, incluir no contexto
            $templateContext = '';
            if ($request->template_id) {
                $template = PetitionTemplate::findOrFail($request->template_id);
                $templateContext = "\n\nTemplate de referência:\n" . $template->content;
            }

            \Log::info('Generating petition with AI', [
                'case_id' => $request->case_id,
                'category' => $request->category,
                'prompt' => $request->prompt,
                'has_template' => !empty($templateContext),
            ]);

            $openAiService = new OpenAiService();
            $result = $openAiService->generatePetition($caseData, $request->prompt . $templateContext);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'content' => $result['content'],
                    'usage' => $result['usage'] ?? null,
                    'case_data' => $caseData, // Para debug
                ]);
            } else {
                \Log::error('AI generation failed', $result);
                return response()->json([
                    'success' => false,
                    'error' => $result['error'],
                ], 500);
            }

        } catch (\Exception $e) {
            \Log::error('Petition AI generation error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function generateFromTemplate(Request $request)
    {
        $request->validate([
            'template_id' => 'required|exists:petition_templates,id',
            'case_id' => 'required|exists:cases,id',
            'variables' => 'nullable|array',
        ]);

        try {
            $template = PetitionTemplate::findOrFail($request->template_id);
            $case = LegalCase::with(['employmentRelationships'])->findOrFail($request->case_id);
            
            // Preparar dados do caso para substituição
            $templateData = $this->prepareCaseDataForTemplate($case, $request->variables ?? []);
            
            // Gerar conteúdo do template
            $content = $template->renderTemplate($templateData);
            
            return response()->json([
                'success' => true,
                'content' => $content,
                'variables' => $template->extractVariables(),
                'template_data' => $templateData,
            ]);

        } catch (\Exception $e) {
            \Log::error('Template generation error', [
                'message' => $e->getMessage(),
                'template_id' => $request->template_id,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro ao gerar template: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function submit(Petition $petition)
    {
        if (!in_array($petition->status, ['draft', 'generated', 'reviewed'])) {
            return back()->withErrors(['error' => 'Petição não pode ser submetida no status atual.']);
        }

        $petition->markAsSubmitted();

        return back()->with('success', 'Petição submetida com sucesso!');
    }

    private function prepareCaseDataForAI(LegalCase $case): array
    {
        return [
            'client_name' => $case->client_name,
            'client_cpf' => $case->client_cpf ?? 'Não informado',
            'case_number' => $case->case_number,
            'benefit_type' => $case->benefit_type ?? 'Não especificado',
            'description' => $case->description ?? 'Não informado',
            'employment_relationships' => $case->employmentRelationships->map(function($employment) {
                return [
                    'employer_name' => $employment->employer_name,
                    'start_date' => $employment->start_date,
                    'end_date' => $employment->end_date,
                    'position' => $employment->position,
                    'salary' => $employment->salary,
                    'contribution_period' => $employment->contribution_period,
                ];
            })->toArray(),
            'total_contribution_time' => $case->employmentRelationships->sum('contribution_period') ?? 0,
            'case_status' => $case->status,
            'created_at' => $case->created_at->format('d/m/Y'),
        ];
    }

    private function prepareCaseDataForTemplate(LegalCase $case, array $customVariables = []): array
    {
        $baseData = [
            'client_name' => $case->client_name,
            'client_cpf' => $case->client_cpf ?? '',
            'case_number' => $case->case_number,
            'benefit_type' => $case->benefit_type_text ?? '',
            'description' => $case->description ?? '',
            'current_date' => now()->format('d/m/Y'),
            'lawyer_name' => auth()->user()->name,
            'total_employment_relationships' => $case->employmentRelationships->count(),
        ];

        // Adicionar dados dos vínculos
        $employmentData = [];
        foreach ($case->employmentRelationships as $index => $employment) {
            $employmentData["employment_{$index}_employer"] = $employment->employer_name;
            $employmentData["employment_{$index}_start"] = $employment->start_date;
            $employmentData["employment_{$index}_end"] = $employment->end_date;
            $employmentData["employment_{$index}_position"] = $employment->position;
        }

        return array_merge($baseData, $employmentData, $customVariables);
    }

    private function generateDocumentFile(Petition $petition): string
    {
        $fileName = 'peticao_' . $petition->id . '_' . time() . '.docx';
        
        // Aqui você pode usar uma biblioteca como PhpWord para gerar um arquivo Word real
        // Por enquanto, vamos salvar como texto simples
        $content = "PETIÇÃO\n\n";
        $content .= "Título: " . $petition->title . "\n\n";
        $content .= $petition->content;
        
        Storage::disk('public')->put($fileName, $content);
        
        return $fileName;
    }

    private function getPetitionStats(): array
    {
        $query = Petition::query();
        
        // Filtrar por empresa se não for super admin
        if (!auth()->user()->isSuperAdmin()) {
            $query->whereHas('legalCase', function($q) {
                $q->byCompany(auth()->user()->company_id);
            });
        }

        return [
            'total' => (clone $query)->count(),
            'draft' => (clone $query)->where('status', 'draft')->count(),
            'generated' => (clone $query)->where('status', 'generated')->count(),
            'submitted' => (clone $query)->where('status', 'submitted')->count(),
            'approved' => (clone $query)->where('status', 'approved')->count(),
            'ai_generated' => (clone $query)->where('type', 'ia')->count(),
            'template_based' => (clone $query)->where('type', 'template')->count(),
        ];
    }

    private function getTemplatesCount(): int
    {
        $query = PetitionTemplate::active();
        
        // Filtrar templates disponíveis para a empresa
        if (auth()->user()->isSuperAdmin()) {
            // Super admin vê todos os templates
            // Sem filtro adicional
        } else {
            // Usuários normais veem apenas templates globais + da sua empresa
            $query->availableForCompany(auth()->user()->company_id);
        }
        
        return $query->count();
    }

    private function getCategories(): array
    {
        return [
            'recurso' => 'Recurso',
            'requerimento' => 'Requerimento',
            'defesa' => 'Defesa',
            'impugnacao' => 'Impugnação',
            'contestacao' => 'Contestação',
            'mandado_seguranca' => 'Mandado de Segurança',
            'acao_ordinaria' => 'Ação Ordinária',
        ];
    }

    private function getBenefitTypes(): array
    {
        return [
            'aposentadoria_idade' => 'Aposentadoria por Idade',
            'aposentadoria_tempo' => 'Aposentadoria por Tempo de Contribuição',
            'aposentadoria_invalidez' => 'Aposentadoria por Invalidez',
            'aposentadoria_especial' => 'Aposentadoria Especial',
            'aposentadoria_professor' => 'Aposentadoria de Professor',
            'aposentadoria_pcd' => 'Aposentadoria da Pessoa com Deficiência',
            'auxilio_doenca' => 'Auxílio-Doença',
            'auxilio_acidente' => 'Auxílio-Acidente',
            'pensao_morte' => 'Pensão por Morte',
            'salario_maternidade' => 'Salário-Maternidade',
            'bpc' => 'Benefício de Prestação Continuada',
        ];
    }
} 