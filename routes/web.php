<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\CaseController;
use App\Http\Controllers\DocumentController;
use App\Models\LegalCase;
use Illuminate\Http\Request;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

// Rota de teste fora do middleware de autenticação
Route::get('/test-no-auth', function () {
    return response()->json([
        'success' => true,
        'message' => 'Rota sem autenticação funcionando',
        'user_id' => auth()->id(),
    ]);
})->name('test-no-auth');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        $stats = [
            'total_cases' => LegalCase::count(),
            'pending_cases' => LegalCase::where('status', 'pending')->count(),
            'analysis_cases' => LegalCase::where('status', 'analysis')->count(),
            'completed_cases' => LegalCase::where('status', 'completed')->count(),
            'requirement_cases' => LegalCase::where('status', 'requirement')->count(),
            'rejected_cases' => LegalCase::where('status', 'rejected')->count(),
        ];

        $recentCases = LegalCase::select('id', 'case_number', 'client_name', 'status', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return Inertia::render('dashboard', [
            'stats' => $stats,
            'recentCases' => $recentCases,
        ]);
    })->name('dashboard');

    // Rotas do Sistema Jurídico
    Route::prefix('cases')->name('cases.')->group(function () {
        Route::get('/', [CaseController::class, 'index'])->name('index');
        Route::get('/create', [CaseController::class, 'create'])->name('create');
        Route::post('/', [CaseController::class, 'store'])->name('store');
        Route::get('/{case}', [CaseController::class, 'show'])->name('show');
        Route::get('/{case}/edit', [CaseController::class, 'edit'])->name('edit');
        Route::put('/{case}', [CaseController::class, 'update'])->name('update');
        Route::delete('/{case}', [CaseController::class, 'destroy'])->name('destroy');
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

    // API Routes
    Route::prefix('api')->group(function () {
        Route::post('/process-cnis', [DocumentController::class, 'processCnis'])->name('api.process-cnis');
        Route::post('/generate-case-description', [CaseController::class, 'generateCaseDescription'])->name('api.generate-case-description');
        
        // Rota de teste para verificar autenticação
        Route::get('/test-auth', function () {
            return response()->json([
                'success' => true,
                'user_id' => auth()->id(),
                'user' => auth()->user(),
                'message' => 'Autenticação funcionando'
            ]);
        })->name('api.test-auth');
        
        // Rota de teste simples para upload
        Route::post('/test-upload', function (Request $request) {
            return response()->json([
                'success' => true,
                'message' => 'Upload funcionando',
                'has_file' => $request->hasFile('cnis_file'),
                'user_id' => auth()->id(),
            ]);
        })->name('api.test-upload');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
