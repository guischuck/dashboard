<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EmploymentRelationshipController;
use App\Http\Controllers\Api\CollectionAttemptController;
use App\Http\Controllers\Api\DeepSeekChatController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\CompanyApiController;
use App\Http\Controllers\Api\ProcessoSyncController;
use App\Http\Controllers\PetitionController;
use App\Models\User;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('web')->group(function () {
    // Rota para buscar clientes (casos) para o chat
    Route::get('/clients', function() {
        $query = \App\Models\LegalCase::select('id', 'client_name as name');
        
        // Filtrar por empresa se não for super admin
        if (!auth()->user()->isSuperAdmin()) {
            $query->byCompany(auth()->user()->company_id);
        }
        
        return $query->get();
    });

    // Rota para chat com IA
    Route::post('/ai-chat', [DeepSeekChatController::class, '__invoke']);

    Route::patch('/employment-relationships/{id}', [EmploymentRelationshipController::class, 'update']);
    Route::get('/employment-relationships/{id}/tentativas', [CollectionAttemptController::class, 'index']);
    Route::patch('/employment-relationships/{id}/tentativas/{tentativa}', [CollectionAttemptController::class, 'update']);
    Route::patch('/tasks/{task}', [TaskController::class, 'update']);
});

// Petition API routes
Route::post('/generate-petition', [PetitionController::class, 'generateWithAI'])->name('api.petitions.generate-ai');
Route::post('/generate-from-template', [PetitionController::class, 'generateFromTemplate'])->name('api.petitions.generate-template');

// External API routes for Chrome extension (without auth, using API key)
Route::middleware(['api.cors'])->group(function () {
    Route::get('/extension/get-id-empresa', [CompanyApiController::class, 'getIdEmpresa']);
    Route::post('/extension/sync', [ProcessoSyncController::class, 'sync']);
}); 