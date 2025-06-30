<?php

namespace App\Http\Controllers;

use App\Models\HistoricoSituacao;
use App\Models\Processo;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class AndamentoController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                throw new \Exception('Usuário não logado');
            }
            
            $companyId = $user->company_id;
            if (!$companyId) {
                throw new \Exception('Usuário sem empresa associada');
            }
            
            // Query base para andamentos (histórico de situações) filtrado por empresa
            $query = HistoricoSituacao::with(['processo'])
                ->where('id_empresa', $companyId)
                ->whereHas('processo', function($q) {
                    $q->whereIn('situacao', ['Em Análise', 'Em Exigência', 'Concluída']);
                });
            
            // Aplicar filtros de busca
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->whereHas('processo', function($q) use ($search) {
                    $q->where('nome', 'like', "%{$search}%")
                      ->orWhere('protocolo', 'like', "%{$search}%")
                      ->orWhere('cpf', 'like', "%{$search}%");
                });
            }
            
            if ($request->filled('nova_situacao')) {
                $query->where('situacao_atual', $request->get('nova_situacao'));
            }
            
            if ($request->filled('visualizacao')) {
                $visualizacao = $request->get('visualizacao');
                if ($visualizacao === 'visto') {
                    $query->where('visto', true);
                } elseif ($visualizacao === 'nao_visto') {
                    $query->where('visto', false);
                }
            }
            
            // Filtro de período
            if ($request->filled('periodo')) {
                $periodo = $request->get('periodo');
                switch ($periodo) {
                    case 'hoje':
                        $query->whereDate('data_mudanca', today());
                        break;
                    case 'semana':
                        $query->where('data_mudanca', '>=', now()->subWeek());
                        break;
                    case 'mes':
                        $query->where('data_mudanca', '>=', now()->subMonth());
                        break;
                    case 'trimestre':
                        $query->where('data_mudanca', '>=', now()->subMonths(3));
                        break;
                }
            }
            
            // Buscar andamentos paginados
            $andamentos = $query->orderBy('data_mudanca', 'desc')->paginate(15);
            
            // Calcular estatísticas
            $stats = $this->getStats($companyId);
            
            // Buscar opções para filtros
            $situacaoOptions = HistoricoSituacao::where('id_empresa', $companyId)
                ->whereHas('processo', function($q) {
                    $q->whereIn('situacao', ['Em Análise', 'Em Exigência', 'Concluída']);
                })
                ->distinct()
                ->pluck('situacao_atual')
                ->filter()
                ->sort()
                ->values();
            
            return Inertia::render('Andamentos/Index', [
                'andamentos' => $andamentos,
                'stats' => $stats,
                'situacaoOptions' => $situacaoOptions,
                'filters' => $request->only(['search', 'nova_situacao', 'periodo', 'visualizacao']),
            ]);
            
        } catch (\Exception $e) {
            return Inertia::render('Andamentos/Index', [
                'error' => $e->getMessage(),
                'andamentos' => [
                    'data' => [],
                    'total' => 0,
                    'current_page' => 1,
                    'last_page' => 1,
                ],
                'stats' => [
                    'total_alteracoes' => 0,
                    'nao_vistos' => 0,
                    'vistos' => 0,
                    'alteracoes_hoje' => 0,
                ],
                'situacaoOptions' => [],
                'filters' => [],
            ]);
        }
    }

    public function marcarVisto($id)
    {
        try {
            $user = auth()->user();
            $companyId = $user->company_id;
            
            $andamento = HistoricoSituacao::where('id_empresa', $companyId)
                ->findOrFail($id);
                
            $andamento->update([
                'visto' => true,
                'visto_em' => now(),
            ]);
            
            return back()->with('success', 'Andamento marcado como visto!');
            
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao marcar como visto: ' . $e->getMessage());
        }
    }

    public function marcarTodosVistos(Request $request)
    {
        try {
            $user = auth()->user();
            $companyId = $user->company_id;
            
            $query = HistoricoSituacao::where('id_empresa', $companyId)
                ->where('visto', false)
                ->whereHas('processo', function($q) {
                    $q->whereIn('situacao', ['Em Análise', 'Em Exigência', 'Concluída']);
                });
            
            // Aplicar mesmos filtros da busca se existirem
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->whereHas('processo', function($q) use ($search) {
                    $q->where('nome', 'like', "%{$search}%")
                      ->orWhere('protocolo', 'like', "%{$search}%")
                      ->orWhere('cpf', 'like', "%{$search}%");
                });
            }
            
            if ($request->filled('nova_situacao')) {
                $query->where('situacao_atual', $request->get('nova_situacao'));
            }
            
            if ($request->filled('periodo')) {
                $periodo = $request->get('periodo');
                switch ($periodo) {
                    case 'hoje':
                        $query->whereDate('data_mudanca', today());
                        break;
                    case 'semana':
                        $query->where('data_mudanca', '>=', now()->subWeek());
                        break;
                    case 'mes':
                        $query->where('data_mudanca', '>=', now()->subMonth());
                        break;
                    case 'trimestre':
                        $query->where('data_mudanca', '>=', now()->subMonths(3));
                        break;
                }
            }
            
            $updated = $query->update([
                'visto' => true,
                'visto_em' => now(),
            ]);
            
            return back()->with('success', "Marcados {$updated} andamentos como vistos!");
            
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao marcar todos como vistos: ' . $e->getMessage());
        }
    }

    private function getStats($companyId)
    {
        $baseQuery = HistoricoSituacao::where('id_empresa', $companyId)
            ->whereHas('processo', function($q) {
                $q->whereIn('situacao', ['Em Análise', 'Em Exigência', 'Concluída']);
            });
        
        // Total de alterações
        $totalAlteracoes = $baseQuery->count();
        
        // Não vistos
        $naoVistos = $baseQuery->where('visto', false)->count();
        
        // Vistos
        $vistos = $baseQuery->where('visto', true)->count();
        
        // Alterações hoje
        $alteracoesHoje = $baseQuery->whereDate('data_mudanca', today())->count();
        
        return [
            'total_alteracoes' => $totalAlteracoes,
            'nao_vistos' => $naoVistos,
            'vistos' => $vistos,
            'alteracoes_hoje' => $alteracoesHoje,
        ];
    }
}
