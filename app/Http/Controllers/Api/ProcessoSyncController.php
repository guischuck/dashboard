<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Processo;
use App\Models\HistoricoSituacao;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessoSyncController extends Controller
{
    public function sync(Request $request): JsonResponse
    {
        try {
            // Validar API Key
            $apiKey = $this->getApiKey($request);
            if (!$apiKey) {
                return response()->json(['error' => 'API Key não fornecida'], 401);
            }

            // Validar dados de entrada
            $validated = $request->validate([
                'processos' => 'required|array',
                'id_empresa' => 'required|integer',
                'processos.*.protocolo' => 'required|string|min:3',
                'processos.*.cpf' => 'required|string|min:8',
                'processos.*.servico' => 'nullable|string',
                'processos.*.situacao' => 'nullable|string',
                'processos.*.nome' => 'nullable|string',
                'processos.*.ultimaAtualizacao' => 'nullable|string',
                'processos.*.dataProtocolo' => 'nullable|string',
            ]);

            $idEmpresa = $validated['id_empresa'];
            $processos = $validated['processos'];

            // Verificar se empresa existe e API key é válida
            $company = Company::where('id', $idEmpresa)
                             ->where('api_key', $apiKey)
                             ->first();

            if (!$company) {
                return response()->json(['error' => 'Empresa não encontrada'], 401);
            }

            if (empty($processos)) {
                return response()->json([
                    'success' => true,
                    'processados' => 0,
                    'message' => 'Nenhum processo'
                ]);
            }

            // Iniciar transação
            DB::beginTransaction();

            $processados = 0;
            $mudancas = 0;

            // Buscar processos existentes para comparar situações
            $protocolos = array_column($processos, 'protocolo');
            $processosExistentes = Processo::whereIn('protocolo', $protocolos)
                                         ->where('id_empresa', $idEmpresa)
                                         ->get()
                                         ->keyBy('protocolo');

            foreach ($processos as $processoData) {
                try {
                    $protocolo = trim($processoData['protocolo']);
                    $cpf = trim($processoData['cpf']);
                    $servico = trim($processoData['servico'] ?? 'N/A');
                    $situacao = trim($processoData['situacao'] ?? 'N/A');
                    $nome = trim($processoData['nome'] ?? 'N/A');

                    // Converter datas
                    $ultimaAtualizacao = $this->formatarDataMySQL($processoData['ultimaAtualizacao'] ?? null);
                    $protocoladoEm = isset($processoData['dataProtocolo']) 
                                   ? $this->formatarDataMySQL($processoData['dataProtocolo'])
                                   : null;

                    // Verificar se processo já existe
                    $processoExistente = $processosExistentes->get($protocolo);
                    $situacaoAnterior = null;

                    if ($processoExistente) {
                        $situacaoAnterior = $processoExistente->situacao;
                        
                        // Se houve mudança de situação, registrar no histórico
                        if ($situacaoAnterior !== $situacao) {
                            HistoricoSituacao::create([
                                'id_processo' => $processoExistente->id,
                                'situacao_anterior' => $situacaoAnterior,
                                'situacao_atual' => $situacao,
                                'id_empresa' => $idEmpresa,
                            ]);
                            $mudancas++;
                        }
                    }

                    // Atualizar ou criar processo
                    Processo::updateOrCreate(
                        [
                            'protocolo' => $protocolo,
                            'id_empresa' => $idEmpresa,
                        ],
                        [
                            'servico' => $servico,
                            'situacao' => $situacao,
                            'situacao_anterior' => $situacaoAnterior,
                            'ultima_atualizacao' => $ultimaAtualizacao,
                            'protocolado_em' => $protocoladoEm ?: ($processoExistente ? $processoExistente->protocolado_em : null),
                            'cpf' => $cpf,
                            'nome' => $nome,
                            'criado_em' => $processoExistente ? $processoExistente->criado_em : now(),
                            'atualizado_em' => now(),
                        ]
                    );

                    $processados++;

                } catch (\Exception $e) {
                    Log::error("Erro ao processar protocolo {$protocolo}: " . $e->getMessage());
                    continue;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'processados' => $processados,
                'mudancas' => $mudancas,
                'total' => count($processos),
                'message' => "{$processados} processos sincronizados" . 
                           ($mudancas > 0 ? " ({$mudancas} mudanças detectadas)" : ""),
                'historico_disponivel' => true,
                'protocolado_disponivel' => true
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro em sync: " . $e->getMessage());
            
            return response()->json(['error' => 'Erro interno'], 500);
        }
    }

    private function getApiKey(Request $request): ?string
    {
        // Tentar pegar do header X-API-Key
        $apiKey = $request->header('X-API-Key');
        
        if (!$apiKey) {
            // Tentar pegar dos dados JSON
            $apiKey = $request->input('api_key');
        }

        if ($apiKey && strpos($apiKey, ',') !== false) {
            $parts = explode(',', $apiKey);
            $apiKey = trim($parts[0]);
        }

        return $apiKey ? trim($apiKey) : null;
    }

    private function formatarDataMySQL(?string $dataISO): ?string
    {
        if (empty($dataISO)) {
            return now()->format('Y-m-d H:i:s');
        }

        try {
            // Se é formato ISO (2025-06-23T22:49:00.000Z)
            if (strpos($dataISO, 'T') !== false) {
                // Remove o .000Z do final se existir
                $dataISO = preg_replace('/\.\d{3}Z$/', 'Z', $dataISO);
                // Remove o Z do final se existir
                $dataISO = rtrim($dataISO, 'Z');
                // Substitui T por espaço
                $dataISO = str_replace('T', ' ', $dataISO);
            }

            // Se já está em formato brasileiro dd/mm/yyyy
            if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})/', $dataISO, $matches)) {
                $dia = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                $mes = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                $ano = $matches[3];
                return "{$ano}-{$mes}-{$dia} 00:00:00";
            }

            // Tentar criar Carbon e formatar
            $date = Carbon::parse($dataISO);
            return $date->format('Y-m-d H:i:s');

        } catch (\Exception $e) {
            Log::error("Erro ao formatar data: {$dataISO} - " . $e->getMessage());
            return now()->format('Y-m-d H:i:s');
        }
    }
} 