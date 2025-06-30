<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CompanyController extends Controller
{
    // O middleware de autorização é aplicado nas rotas via can:manage-companies

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $companies = Company::with('users')
            ->withCount(['users', 'cases'])
            ->paginate(15);

        return Inertia::render('Companies/Index', [
            'companies' => $companies,
            'stats' => [
                'total' => Company::count(),
                'active' => Company::where('is_active', true)->count(),
                'inactive' => Company::where('is_active', false)->count(),
                'trial' => Company::whereNotNull('trial_ends_at')
                    ->where('trial_ends_at', '>', now())
                    ->count(),
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Companies/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'cnpj' => 'nullable|string|max:18',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:2',
            'zip_code' => 'nullable|string|max:10',
            'plan' => 'required|in:basic,premium,enterprise',
            'max_users' => 'required|integer|min:1|max:1000',
            'max_cases' => 'required|integer|min:1|max:10000',
            'trial_days' => 'nullable|integer|min:0|max:365',
        ]);

        $company = Company::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'email' => $request->email,
            'cnpj' => $request->cnpj,
            'phone' => $request->phone,
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'zip_code' => $request->zip_code,
            'plan' => $request->plan,
            'max_users' => $request->max_users,
            'max_cases' => $request->max_cases,
            'is_active' => true,
            'trial_ends_at' => $request->trial_days ? now()->addDays($request->trial_days) : null,
        ]);

        return redirect()->route('companies.index')
            ->with('success', 'Empresa criada com sucesso!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Company $company)
    {
        $company->load(['users', 'cases.assignedTo', 'petitionTemplates']);

        return Inertia::render('Companies/Show', [
            'company' => $company,
            'stats' => [
                'users_count' => $company->users()->count(),
                'cases_count' => $company->cases()->count(),
                'templates_count' => $company->petitionTemplates()->count(),
                'active_cases' => $company->cases()->whereIn('status', ['pendente', 'em_coleta', 'aguarda_peticao'])->count(),
                'completed_cases' => $company->cases()->where('status', 'concluido')->count(),
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Company $company)
    {
        return Inertia::render('Companies/Edit', [
            'company' => $company
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Company $company)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'cnpj' => 'nullable|string|max:18',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:2',
            'zip_code' => 'nullable|string|max:10',
            'plan' => 'required|in:basic,premium,enterprise',
            'max_users' => 'required|integer|min:1|max:1000',
            'max_cases' => 'required|integer|min:1|max:10000',
            'is_active' => 'boolean',
        ]);

        $company->update($request->all());

        return redirect()->route('companies.index')
            ->with('success', 'Empresa atualizada com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company)
    {
        // Verificar se a empresa tem dados antes de excluir
        if ($company->users()->count() > 0 || $company->cases()->count() > 0) {
            return redirect()->route('companies.index')
                ->with('error', 'Não é possível excluir uma empresa que possui usuários ou casos cadastrados.');
        }

        $company->delete();

        return redirect()->route('companies.index')
            ->with('success', 'Empresa excluída com sucesso!');
    }

    /**
     * Toggle company status (activate/deactivate)
     */
    public function toggleStatus(Company $company)
    {
        $company->update(['is_active' => !$company->is_active]);

        $status = $company->is_active ? 'ativada' : 'desativada';
        
        return redirect()->back()
            ->with('success', "Empresa {$status} com sucesso!");
    }

    /**
     * Extend trial period
     */
    public function extendTrial(Request $request, Company $company)
    {
        $request->validate([
            'days' => 'required|integer|min:1|max:365'
        ]);

        $trialEnd = $company->trial_ends_at ?? now();
        $company->update([
            'trial_ends_at' => $trialEnd->addDays($request->days)
        ]);

        return redirect()->back()
            ->with('success', "Período de teste estendido por {$request->days} dias!");
    }
}
