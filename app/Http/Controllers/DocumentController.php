<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\LegalCase;
use App\Services\DocumentProcessingService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DocumentController extends Controller
{
    private DocumentProcessingService $documentProcessingService;

    public function __construct(DocumentProcessingService $documentProcessingService)
    {
        $this->documentProcessingService = $documentProcessingService;
    }

    public function index(Request $request)
    {
        $query = Document::with(['case', 'uploadedBy'])
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when($request->type, function ($query, $type) {
                $query->where('type', $type);
            })
            ->when($request->case_id, function ($query, $caseId) {
                $query->where('case_id', $caseId);
            });

        $documents = $query->orderBy('created_at', 'desc')->paginate(15);

        $documentTypes = [
            'cnis' => 'CNIS',
            'medical_report' => 'Laudo Médico',
            'identity' => 'Documento de Identidade',
            'work_card' => 'Carteira de Trabalho',
            'medical_certificate' => 'Atestado Médico',
            'other' => 'Outro',
        ];

        return Inertia::render('Documents/Index', [
            'documents' => $documents,
            'documentTypes' => $documentTypes,
            'filters' => $request->only(['search', 'type', 'case_id']),
        ]);
    }

    public function create()
    {
        $cases = LegalCase::select('id', 'case_number', 'client_name')->get();
        $documentTypes = [
            'cnis' => 'CNIS',
            'medical_report' => 'Laudo Médico',
            'identity' => 'Documento de Identidade',
            'work_card' => 'Carteira de Trabalho',
            'medical_certificate' => 'Atestado Médico',
            'other' => 'Outro',
        ];

        return Inertia::render('Documents/Create', [
            'cases' => $cases,
            'documentTypes' => $documentTypes,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'case_id' => 'required|exists:cases,id',
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'file' => 'required|file|max:10240', // 10MB max
            'notes' => 'nullable|string',
        ]);

        $file = $request->file('file');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('documents', $fileName, 'public');

        $document = Document::create([
            'case_id' => $validated['case_id'],
            'name' => $validated['name'],
            'type' => $validated['type'],
            'file_path' => $filePath,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'notes' => $validated['notes'],
            'uploaded_by' => auth()->id(),
        ]);

        // Processa o documento automaticamente se for CNIS
        if ($validated['type'] === 'cnis') {
            $this->documentProcessingService->processDocument($document);
        }

        return redirect()->route('documents.show', $document)
            ->with('success', 'Documento enviado com sucesso!');
    }

    public function show(Document $document)
    {
        $document->load(['case', 'uploadedBy']);

        return Inertia::render('Documents/Show', [
            'document' => $document,
        ]);
    }

    public function edit(Document $document)
    {
        $cases = LegalCase::select('id', 'case_number', 'client_name')->get();
        $documentTypes = [
            'cnis' => 'CNIS',
            'medical_report' => 'Laudo Médico',
            'identity' => 'Documento de Identidade',
            'work_card' => 'Carteira de Trabalho',
            'medical_certificate' => 'Atestado Médico',
            'other' => 'Outro',
        ];

        return Inertia::render('Documents/Edit', [
            'document' => $document,
            'cases' => $cases,
            'documentTypes' => $documentTypes,
        ]);
    }

    public function update(Request $request, Document $document)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        $document->update($validated);

        return redirect()->route('documents.show', $document)
            ->with('success', 'Documento atualizado com sucesso!');
    }

    public function destroy(Document $document)
    {
        // Remove o arquivo físico
        if (Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return redirect()->route('documents.index')
            ->with('success', 'Documento excluído com sucesso!');
    }

    public function download(Document $document)
    {
        if (!Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'Arquivo não encontrado');
        }

        return Storage::disk('public')->download(
            $document->file_path,
            $document->file_name
        );
    }

    public function process(Document $document)
    {
        $result = $this->documentProcessingService->processDocument($document);

        if ($result['success']) {
            return back()->with('success', 'Documento processado com sucesso!');
        }

        return back()->with('error', 'Erro ao processar documento: ' . $result['error']);
    }

    public function caseDocuments(LegalCase $case)
    {
        $documents = $case->documents()
            ->with('uploadedBy')
            ->orderBy('created_at', 'desc')
            ->get();

        $documentTypes = [
            'cnis' => 'CNIS',
            'medical_report' => 'Laudo Médico',
            'identity' => 'Documento de Identidade',
            'work_card' => 'Carteira de Trabalho',
            'medical_certificate' => 'Atestado Médico',
            'other' => 'Outro',
        ];

        return Inertia::render('Documents/CaseDocuments', [
            'case' => $case,
            'documents' => $documents,
            'documentTypes' => $documentTypes,
        ]);
    }

    public function processCnis(Request $request)
    {
        Log::info('DEBUG: Entrou no método processCnis');
        Log::info('DEBUG: Usuário autenticado', ['user_id' => auth()->id(), 'user' => auth()->user()]);
        Log::info('DEBUG: Headers da requisição', ['headers' => $request->headers->all()]);
        Log::info('DEBUG: Método da requisição', ['method' => $request->method()]);
        Log::info('DEBUG: URL da requisição', ['url' => $request->url()]);
        
        $path = \Illuminate\Support\Facades\Storage::disk('local')->path('teste_cnis.txt');
        Log::info('DEBUG: Caminho absoluto do arquivo', ['path' => $path]);
        file_put_contents($path, 'Teste direto com file_put_contents - ' . now());
        
        try {
            Log::info('Iniciando processamento de CNIS', ['request' => $request->all()]);
            
            if (!$request->hasFile('cnis_file')) {
                Log::error('DEBUG: Nenhum arquivo foi enviado');
                return response()->json([
                    'success' => false,
                    'error' => 'Nenhum arquivo foi enviado'
                ], 400);
            }

            $file = $request->file('cnis_file');
            
            // Validação do arquivo
            $request->validate([
                'cnis_file' => 'required|file|mimes:pdf,txt|max:10240', // 10MB max
            ]);

            Log::info('Arquivo recebido', [
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime' => $file->getMimeType()
            ]);

            // Salva o arquivo temporariamente
            $tempPath = $file->store('temp/cnis', 'local');
            $fullPath = Storage::disk('local')->path($tempPath);
            
            Log::info('Arquivo salvo', ['path' => $fullPath]);

            // Cria um documento temporário para processamento
            $document = Document::create([
                'case_id' => null, // Será associado quando o caso for criado
                'name' => $file->getClientOriginalName(),
                'file_path' => $tempPath,
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'type' => 'cnis',
                'is_processed' => false,
                'uploaded_by' => auth()->id(),
                'notes' => 'CNIS enviado durante criação do caso',
            ]);

            Log::info('Documento criado', ['document_id' => $document->id]);

            // Processa o CNIS
            $processingService = app(DocumentProcessingService::class);
            $result = $processingService->processDocument($document);

            Log::info('Resultado do processamento', ['result' => $result]);

            if (!$result['success']) {
                Log::error('DEBUG: Erro no processamento', ['error' => $result['error']]);
                return response()->json([
                    'success' => false,
                    'error' => $result['error'] ?? 'Erro no processamento do CNIS'
                ], 500);
            }

            Log::info('DEBUG: Processamento concluído com sucesso');
            return response()->json([
                'success' => true,
                'message' => 'CNIS processado com sucesso!',
                'data' => $result['data'],
                'document_id' => $document->id
            ]);

        } catch (\Exception $e) {
            Log::error('Erro no processamento de CNIS', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro interno: ' . $e->getMessage()
            ], 500);
        }
    }
} 