<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserBelongsToCompany
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        
        // Super admin não precisa de empresa
        if ($user && $user->isSuperAdmin()) {
            return $next($request);
        }
        
        // Usuários normais devem ter uma empresa
        if (!$user || !$user->hasCompany()) {
            abort(403, 'Acesso negado. Usuário deve estar vinculado a uma empresa.');
        }
        
        return $next($request);
    }
}
