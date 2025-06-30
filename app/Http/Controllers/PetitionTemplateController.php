<?php

namespace App\Http\Controllers;

use App\Models\PetitionTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PetitionTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = PetitionTemplate::with(['creator', 'company'])
            ->withCount('petitions')
            ->orderBy('created_at', 'desc');

        // Filtrar templates disponíveis para a empresa
        if (auth()->user()->isSuperAdmin()) {
            // Super admin vê todos os templates
            // Sem filtro adicional
        } else {
            // Usuários normais veem apenas templates globais + da sua empresa
            $query->availableForCompany(auth()->user()->company_id);
        }

        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('benefit_type')) {
            $query->where('benefit_type', $request->benefit_type);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('scope')) {
            if ($request->scope === 'global') {
                $query->global();
            } elseif ($request->scope === 'company' && !auth()->user()->isSuperAdmin()) {
                $query->byCompany(auth()->user()->company_id);
            }
        }

        $templates = $query->paginate(15);

        $stats = $this->getTemplateStats();

        return Inertia::render('Petitions/Templates', [
            'templates' => $templates,
            'stats' => $stats,
            'filters' => $request->only(['search', 'category', 'benefit_type', 'is_active', 'scope']),
            'categories' => collect($this->getCategories())->map(fn($label, $value) => ['value' => $value, 'label' => $label])->values(),
            'benefitTypes' => collect($this->getBenefitTypes())->map(fn($label, $value) => ['value' => $value, 'label' => $label])->values(),
            'canManageGlobal' => auth()->user()->isSuperAdmin(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Petitions/TemplateCreate', [
            'categories' => collect($this->getCategories())->map(fn($label, $value) => ['value' => $value, 'label' => $label])->values(),
            'benefitTypes' => collect($this->getBenefitTypes())->map(fn($label, $value) => ['value' => $value, 'label' => $label])->values(),
            'canManageGlobal' => auth()->user()->isSuperAdmin(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'benefit_type' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:1000',
            'content' => 'required|string',
            'variables' => 'nullable|array',
            'sections' => 'nullable|array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'is_global' => 'boolean',
        ]);

        $data = $request->validated();
        $data['created_by'] = auth()->id();

        // Se for super admin e marcou como global, não precisa de company_id
        if (auth()->user()->isSuperAdmin() && $request->boolean('is_global')) {
            $data['is_global'] = true;
            $data['company_id'] = null;
        } else {
            // Senão, é um template da empresa
            $data['is_global'] = false;
            $data['company_id'] = auth()->user()->company_id;
        }

        $template = PetitionTemplate::create($data);

        return redirect()->route('petition-templates.show', $template)
            ->with('success', 'Template criado com sucesso!');
    }

    public function show(PetitionTemplate $petitionTemplate)
    {
        $petitionTemplate->load(['creator', 'petitions' => function($query) {
            $query->with(['legalCase', 'user'])->latest()->limit(10);
        }]);

        return Inertia::render('Petitions/TemplateShow', [
            'template' => $petitionTemplate,
            'variables' => $petitionTemplate->extractVariables(),
        ]);
    }

    public function edit(PetitionTemplate $petitionTemplate)
    {
        return Inertia::render('Petitions/TemplateEdit', [
            'template' => $petitionTemplate,
            'categories' => collect($this->getCategories())->map(fn($label, $value) => ['value' => $value, 'label' => $label])->values(),
            'benefitTypes' => collect($this->getBenefitTypes())->map(fn($label, $value) => ['value' => $value, 'label' => $label])->values(),
            'variables' => $petitionTemplate->extractVariables(),
            'canManageGlobal' => auth()->user()->isSuperAdmin(),
        ]);
    }

    public function update(Request $request, PetitionTemplate $petitionTemplate)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'benefit_type' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:1000',
            'content' => 'required|string',
            'variables' => 'nullable|array',
            'sections' => 'nullable|array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'is_global' => 'boolean',
        ]);

        $data = $request->validated();

        // Se for super admin e marcou como global, não precisa de company_id
        if (auth()->user()->isSuperAdmin() && $request->boolean('is_global')) {
            $data['is_global'] = true;
            $data['company_id'] = null;
        } else {
            // Senão, é um template da empresa
            $data['is_global'] = false;
            if (!$petitionTemplate->company_id) {
                $data['company_id'] = auth()->user()->company_id;
            }
        }

        $petitionTemplate->update($data);

        return redirect()->route('petition-templates.show', $petitionTemplate)
            ->with('success', 'Template atualizado com sucesso!');
    }

    public function destroy(PetitionTemplate $petitionTemplate)
    {
        // Verificar se o template está sendo usado
        if ($petitionTemplate->petitions()->count() > 0) {
            return back()->withErrors(['error' => 'Não é possível excluir um template que está sendo usado por petições.']);
        }

        $petitionTemplate->delete();

        return redirect()->route('petition-templates.index')
            ->with('success', 'Template excluído com sucesso!');
    }

    public function duplicate(PetitionTemplate $petitionTemplate)
    {
        $newTemplate = $petitionTemplate->replicate();
        $newTemplate->name = $petitionTemplate->name . ' (Cópia)';
        $newTemplate->is_default = false;
        $newTemplate->created_by = auth()->id();
        $newTemplate->save();

        return redirect()->route('petition-templates.edit', $newTemplate)
            ->with('success', 'Template duplicado com sucesso!');
    }

    public function toggleActive(PetitionTemplate $petitionTemplate)
    {
        $petitionTemplate->update([
            'is_active' => !$petitionTemplate->is_active
        ]);

        $status = $petitionTemplate->is_active ? 'ativado' : 'desativado';
        
        return back()->with('success', "Template {$status} com sucesso!");
    }

    public function preview(PetitionTemplate $petitionTemplate, Request $request)
    {
        $sampleData = $request->input('data', []);
        $previewContent = $petitionTemplate->renderTemplate($sampleData);

        return response()->json([
            'content' => $previewContent,
            'variables' => $petitionTemplate->extractVariables(),
        ]);
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

    private function getTemplateStats(): array
    {
        $query = PetitionTemplate::query();
        
        // Filtrar templates disponíveis para a empresa
        if (auth()->user()->isSuperAdmin()) {
            // Super admin vê todos os templates
            // Sem filtro adicional
        } else {
            // Usuários normais veem apenas templates globais + da sua empresa
            $query->availableForCompany(auth()->user()->company_id);
        }

        return [
            'total' => (clone $query)->count(),
            'active' => (clone $query)->where('is_active', true)->count(),
            'inactive' => (clone $query)->where('is_active', false)->count(),
            'global' => (clone $query)->where('is_global', true)->count(),
            'company' => auth()->user()->isSuperAdmin() 
                ? (clone $query)->where('is_global', false)->whereNotNull('company_id')->count()
                : (clone $query)->where('company_id', auth()->user()->company_id)->count(),
        ];
    }
}
