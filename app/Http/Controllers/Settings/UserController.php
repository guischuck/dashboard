<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Show the users management page.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $companyId = $user->company_id;
        
        if (!$companyId) {
            abort(403, 'Usuário sem empresa associada');
        }
        
        // Buscar usuários da mesma empresa
        $users = User::where('company_id', $companyId)
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        
        return Inertia::render('settings/users', [
            'users' => $users,
            'currentUser' => $user,
        ]);
    }

    /**
     * Store a new user.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $companyId = $user->company_id;
        
        if (!$companyId) {
            abort(403, 'Usuário sem empresa associada');
        }
        
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $newUser = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'company_id' => $companyId,
            'is_super_admin' => false,
        ]);

        return back()->with('success', 'Usuário criado com sucesso!');
    }

    /**
     * Update user status (activate/deactivate).
     */
    public function updateStatus(Request $request, User $user)
    {
        $currentUser = $request->user();
        
        // Verificar se o usuário pertence à mesma empresa
        if ($user->company_id !== $currentUser->company_id) {
            abort(403, 'Não autorizado');
        }
        
        // Não permitir desativar o próprio usuário
        if ($user->id === $currentUser->id) {
            return back()->with('error', 'Você não pode desativar sua própria conta');
        }
        
        $request->validate([
            'active' => ['required', 'boolean'],
        ]);
        
        // Como não temos campo active, vamos usar o email_verified_at
        // para simular ativação/desativação
        $user->update([
            'email_verified_at' => $request->active ? now() : null,
        ]);
        
        $status = $request->active ? 'ativado' : 'desativado';
        return back()->with('success', "Usuário {$status} com sucesso!");
    }

    /**
     * Delete a user.
     */
    public function destroy(Request $request, User $user)
    {
        $currentUser = $request->user();
        
        // Verificar se o usuário pertence à mesma empresa
        if ($user->company_id !== $currentUser->company_id) {
            abort(403, 'Não autorizado');
        }
        
        // Não permitir excluir o próprio usuário
        if ($user->id === $currentUser->id) {
            return back()->with('error', 'Você não pode excluir sua própria conta');
        }
        
        $user->delete();
        
        return back()->with('success', 'Usuário excluído com sucesso!');
    }

    /**
     * Reset user password.
     */
    public function resetPassword(Request $request, User $user)
    {
        $currentUser = $request->user();
        
        // Verificar se o usuário pertence à mesma empresa
        if ($user->company_id !== $currentUser->company_id) {
            abort(403, 'Não autorizado');
        }
        
        $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);
        
        $user->update([
            'password' => Hash::make($request->password),
        ]);
        
        return back()->with('success', 'Senha alterada com sucesso!');
    }
}
