<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CompanyApiController extends Controller
{
    public function getIdEmpresa(Request $request): JsonResponse
    {
        try {
            $apiKey = $this->getApiKey($request);

            if (!$apiKey) {
                return response()->json(['error' => 'API Key não fornecida'], 401);
            }

            // Limpar API key se tiver vírgula
            if (strpos($apiKey, ',') !== false) {
                $parts = explode(',', $apiKey);
                $apiKey = trim($parts[0]);
            }

            // Buscar empresa pela API key
            $company = Company::where('api_key', $apiKey)->first();

            if (!$company) {
                return response()->json(['error' => 'API Key inválida'], 401);
            }

            return response()->json([
                'success' => true,
                'id_empresa' => $company->id,
                'razao_social' => $company->razao_social ?: $company->name,
            ]);

        } catch (\Exception $e) {
            Log::error("Erro em getIdEmpresa: " . $e->getMessage());
            return response()->json(['error' => 'Erro interno do servidor'], 500);
        }
    }

    private function getApiKey(Request $request): ?string
    {
        // Tentar pegar do header X-API-Key
        $apiKey = $request->header('X-API-Key');
        
        if (!$apiKey) {
            // Tentar pegar da query string
            $apiKey = $request->query('api_key');
        }

        return $apiKey ? trim($apiKey) : null;
    }
} 