<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Processo;
use App\Models\Company;
use App\Models\HistoricoSituacao;
use Carbon\Carbon;

class ProcessoSeeder extends Seeder
{
    public function run()
    {
        // Buscar empresas existentes
        $companies = Company::all();
        
        if ($companies->isEmpty()) {
            $this->command->info('Nenhuma empresa encontrada. Execute o CompanySeeder primeiro.');
            return;
        }

        $statusOptions = [
            'Em análise',
            'Aguardando documentos', 
            'Em processamento',
            'Deferido',
            'Indeferido',
            'Exigência',
            'Aguardando manifestação',
            'Concluído',
            'Em andamento'
        ];

        $servicoOptions = [
            'Recurso Ordinário (Inicial)',
            'Aposentadoria por Idade',
            'Aposentadoria por Tempo de Contribuição',
            'Auxílio-Doença',
            'Pensão por Morte',
            'Salário-Maternidade',
            'Revisão',
            'BPC/LOAS'
        ];

        $nomes = [
            'EDSON JONSE ALVES DE OLIVEIRA',
            'ALEXANDRE BACELLAR NETO',
            'MARIA SILVA SANTOS',
            'JOÃO PEDRO OLIVEIRA',
            'ANA CAROLINA FERREIRA',
            'CARLOS EDUARDO LIMA',
            'FERNANDA COSTA RIBEIRO',
            'RICARDO ALMEIDA SOUZA',
            'PATRICIA MORAES SILVA',
            'ANDERSON BARBOSA CRUZ'
        ];

        foreach ($companies as $company) {
            // Criar entre 5-10 processos por empresa
            $numProcessos = rand(5, 10);
            
            for ($i = 0; $i < $numProcessos; $i++) {
                $protocoladoEm = Carbon::now()->subDays(rand(1, 90));
                $ultimaAtualizacao = $protocoladoEm->copy()->addDays(rand(1, 30));
                
                $situacaoAtual = $statusOptions[array_rand($statusOptions)];
                $situacaoAnterior = rand(0, 1) ? $statusOptions[array_rand($statusOptions)] : null;
                
                $processo = Processo::create([
                    'protocolo' => '19' . str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT),
                    'servico' => $servicoOptions[array_rand($servicoOptions)],
                    'situacao' => $situacaoAtual,
                    'situacao_anterior' => $situacaoAnterior,
                    'ultima_atualizacao' => $ultimaAtualizacao,
                    'protocolado_em' => $protocoladoEm,
                    'cpf' => $this->generateCpf(),
                    'nome' => $nomes[array_rand($nomes)],
                    'id_empresa' => $company->id,
                ]);

                // Criar alguns históricos de situação
                if ($situacaoAnterior && rand(0, 1)) {
                    HistoricoSituacao::create([
                        'id_processo' => $processo->id,
                        'situacao_anterior' => $situacaoAnterior,
                        'situacao_atual' => $situacaoAtual,
                        'data_mudanca' => $ultimaAtualizacao,
                        'id_empresa' => $company->id,
                    ]);
                }
            }
        }

        $this->command->info('Processos criados com sucesso!');
    }

    private function generateCpf()
    {
        // Gerar CPF válido simples para teste
        $cpf = '';
        for ($i = 0; $i < 11; $i++) {
            $cpf .= rand(0, 9);
        }
        
        // Formatar CPF
        return substr($cpf, 0, 3) . '.' . 
               substr($cpf, 3, 3) . '.' . 
               substr($cpf, 6, 3) . '-' . 
               substr($cpf, 9, 2);
    }
} 