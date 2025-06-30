<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\CaseController;
use App\Http\Controllers\DocumentController;
use Illuminate\Http\Request;
use App\Http\Controllers\PetitionController;
use App\Http\Controllers\PetitionTemplateController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\FinancialController;
use App\Http\Controllers\SubscriptionPlanController;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [CaseController::class, 'dashboard'])->middleware('ensure.user.company')->name('dashboard');
    Route::get('chat', function () {
        return Inertia::render('Chat');
    })->name('chat');

    // Página de Coletas
    Route::get('coletas', [\App\Http\Controllers\CaseController::class, 'coletas'])->name('coletas');

    // Rotas dos Andamentos INSS
    Route::prefix('andamentos')->name('andamentos.')->middleware('ensure.user.company')->group(function () {
        Route::get('/', [\App\Http\Controllers\AndamentoController::class, 'index'])->name('index');
        Route::patch('/{andamento}/marcar-visto', [\App\Http\Controllers\AndamentoController::class, 'marcarVisto'])->name('marcar-visto');
        Route::post('/marcar-todos-vistos', [\App\Http\Controllers\AndamentoController::class, 'marcarTodosVistos'])->name('marcar-todos-vistos');
    });

    // Rotas do Sistema Jurídico
    Route::prefix('cases')->name('cases.')->group(function () {
        Route::get('/', [CaseController::class, 'index'])->name('index');
        Route::get('/create', [CaseController::class, 'create'])->name('create');
        Route::post('/', [CaseController::class, 'store'])->name('store');
        Route::get('/{case}', [CaseController::class, 'show'])->name('show');
        Route::get('/{case}/edit', [CaseController::class, 'edit'])->name('edit');
        Route::put('/{case}', [CaseController::class, 'update'])->name('update');
        Route::patch('/{case}', [CaseController::class, 'update'])->name('update.patch');
        Route::delete('/{case}', [CaseController::class, 'destroy'])->name('destroy');
        Route::get('/{case}/vinculos', [CaseController::class, 'vinculos'])->name('vinculos');
        Route::get('/dashboard/overview', [CaseController::class, 'dashboard'])->name('dashboard');
    });

    Route::prefix('documents')->name('documents.')->group(function () {
        Route::get('/', [DocumentController::class, 'index'])->name('index');
        Route::get('/create', [DocumentController::class, 'create'])->name('create');
        Route::post('/', [DocumentController::class, 'store'])->name('store');
        Route::get('/{document}', [DocumentController::class, 'show'])->name('show');
        Route::get('/{document}/edit', [DocumentController::class, 'edit'])->name('edit');
        Route::put('/{document}', [DocumentController::class, 'update'])->name('update');
        Route::delete('/{document}', [DocumentController::class, 'destroy'])->name('destroy');
        Route::get('/{document}/download', [DocumentController::class, 'download'])->name('download');
        Route::post('/{document}/process', [DocumentController::class, 'process'])->name('process');
        Route::get('/case/{case}', [DocumentController::class, 'caseDocuments'])->name('case.documents');
    });

    // Rotas dos Processos INSS
    Route::prefix('inss-processes')->name('inss-processes.')->middleware('ensure.user.company')->group(function () {
        Route::get('/', [\App\Http\Controllers\ProcessoController::class, 'index'])->name('index');
        Route::get('/{processo}', [\App\Http\Controllers\ProcessoController::class, 'show'])->name('show');
    });

    // Rotas de Petições
    Route::prefix('petitions')->name('petitions.')->group(function () {
        Route::get('/', [\App\Http\Controllers\PetitionController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\PetitionController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\PetitionController::class, 'store'])->name('store');
        Route::get('/{petition}', [\App\Http\Controllers\PetitionController::class, 'show'])->name('show');
        Route::get('/{petition}/download', [\App\Http\Controllers\PetitionController::class, 'download'])->name('download');
    });

    // Rotas de Workflows
    Route::prefix('tasks')->name('workflows.')->group(function () {
        Route::get('/', [\App\Http\Controllers\WorkflowController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\WorkflowController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\WorkflowController::class, 'store'])->name('store');
        Route::get('/{workflow}', [\App\Http\Controllers\WorkflowController::class, 'show'])->name('show');
        Route::get('/{workflow}/edit', [\App\Http\Controllers\WorkflowController::class, 'edit'])->name('edit');
        Route::put('/{workflow}', [\App\Http\Controllers\WorkflowController::class, 'update'])->name('update');
        Route::delete('/{workflow}', [\App\Http\Controllers\WorkflowController::class, 'destroy'])->name('destroy');
        
        // Rotas para Templates de Workflow
        Route::prefix('templates')->name('templates.')->group(function () {
            Route::get('/create', [\App\Http\Controllers\WorkflowController::class, 'createTemplate'])->name('create');
            Route::post('/', [\App\Http\Controllers\WorkflowController::class, 'storeTemplate'])->name('store');
            Route::get('/{template}/edit', [\App\Http\Controllers\WorkflowController::class, 'editTemplate'])->name('edit');
            Route::put('/{template}', [\App\Http\Controllers\WorkflowController::class, 'updateTemplate'])->name('update');
            Route::patch('/{template}/toggle', [\App\Http\Controllers\WorkflowController::class, 'toggleTemplate'])->name('toggle');
            Route::delete('/{template}', [\App\Http\Controllers\WorkflowController::class, 'destroyTemplate'])->name('destroy');
        });
    });

    // API Routes
    Route::prefix('api')->group(function () {
        Route::post('/process-cnis', [DocumentController::class, 'processCnis'])->name('api.process-cnis');
        Route::post('/generate-case-description', [CaseController::class, 'generateCaseDescription'])->name('api.generate-case-description');
        Route::post('/generate-petition', [\App\Http\Controllers\PetitionController::class, 'generateWithAI'])->name('api.generate-petition');
        
        // Rotas para upload de documentos
        Route::post('/cases/{case}/upload-documents', [DocumentController::class, 'uploadForCase'])->name('api.cases.upload-documents');
        Route::get('/cases/{case}/documents', [DocumentController::class, 'getCaseDocuments'])->name('api.cases.documents');
        Route::delete('/documents/{document}', [DocumentController::class, 'deleteDocument'])->name('api.documents.delete');
        
        // Rotas para workflow
        Route::post('/cases/{case}/workflow-tasks', [DocumentController::class, 'updateWorkflowTasks'])->name('api.cases.workflow-tasks');
        Route::get('/cases/{case}/workflow-tasks', [DocumentController::class, 'getWorkflowTasks'])->name('api.cases.workflow-tasks.get');
        
        // Rota para salvar anotações
        Route::patch('/cases/{case}/notes', [CaseController::class, 'updateNotes'])->name('api.cases.update-notes');
    });

    // Petitions routes
    Route::resource('petitions', PetitionController::class);
    Route::get('/petitions/{petition}/download', [PetitionController::class, 'download'])->name('petitions.download');
    Route::patch('/petitions/{petition}/submit', [PetitionController::class, 'submit'])->name('petitions.submit');

    // Petition Templates routes
    Route::resource('petition-templates', PetitionTemplateController::class);
    Route::post('/petition-templates/{petitionTemplate}/duplicate', [PetitionTemplateController::class, 'duplicate'])->name('petition-templates.duplicate');
    Route::patch('/petition-templates/{petitionTemplate}/toggle-active', [PetitionTemplateController::class, 'toggleActive'])->name('petition-templates.toggle-active');
    Route::post('/petition-templates/{petitionTemplate}/preview', [PetitionTemplateController::class, 'preview'])->name('petition-templates.preview');

    // Companies routes (apenas para super admins)
    Route::middleware('can:manage-companies')->group(function () {
        Route::resource('companies', CompanyController::class);
        Route::post('/companies/{company}/toggle-status', [\App\Http\Controllers\CompanyController::class, 'toggleStatus'])->name('companies.toggle-status');
        Route::post('/companies/{company}/extend-trial', [\App\Http\Controllers\CompanyController::class, 'extendTrial'])->name('companies.extend-trial');
    });

    // Rotas do super admin
    Route::middleware(['ensure.super.admin'])->group(function () {
        Route::resource('companies', CompanyController::class);
        
        // Módulo Financeiro
        Route::get('/financial', [FinancialController::class, 'dashboard'])->name('financial.dashboard');
        Route::get('/financial/subscriptions', [FinancialController::class, 'subscriptions'])->name('financial.subscriptions');
        Route::get('/financial/payments', [FinancialController::class, 'payments'])->name('financial.payments');
        
        // Ações de assinatura
        Route::post('/financial/subscriptions/{subscription}/renew', [FinancialController::class, 'renewSubscription'])->name('financial.subscriptions.renew');
        Route::post('/financial/subscriptions/{subscription}/cancel', [FinancialController::class, 'cancelSubscription'])->name('financial.subscriptions.cancel');
        Route::post('/financial/subscriptions/{subscription}/suspend', [FinancialController::class, 'suspendSubscription'])->name('financial.subscriptions.suspend');
        Route::post('/financial/subscriptions/{subscription}/reactivate', [FinancialController::class, 'reactivateSubscription'])->name('financial.subscriptions.reactivate');
        Route::post('/financial/subscriptions/{subscription}/extend-trial', [FinancialController::class, 'extendTrial'])->name('financial.subscriptions.extend-trial');
        
        // Planos de assinatura
        Route::resource('subscription-plans', SubscriptionPlanController::class);
        Route::post('/subscription-plans/{subscriptionPlan}/duplicate', [SubscriptionPlanController::class, 'duplicate'])->name('subscription-plans.duplicate');
        Route::patch('/subscription-plans/{subscriptionPlan}/toggle-active', [SubscriptionPlanController::class, 'toggleActive'])->name('subscription-plans.toggle-active');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
