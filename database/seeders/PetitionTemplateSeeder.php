<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PetitionTemplate;
use App\Models\User;

class PetitionTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();
        if (!$user) {
            $this->command->warn('Nenhum usuário encontrado. Criando templates sem usuário.');
            return;
        }

        $templates = [
            [
                'name' => 'Recurso de Aposentadoria por Idade',
                'category' => 'recurso',
                'benefit_type' => 'aposentadoria_idade',
                'description' => 'Template para recurso de aposentadoria por idade negada pelo INSS',
                'content' => $this->getRecursoAposentadoriaIdadeTemplate(),
                'is_active' => true,
                'is_default' => true,
                'created_by' => $user->id,
            ],
            [
                'name' => 'Requerimento de Auxílio-Doença',
                'category' => 'requerimento',
                'benefit_type' => 'auxilio_doenca',
                'description' => 'Template para requerimento de auxílio-doença',
                'content' => $this->getRequerimentoAuxilioDoencaTemplate(),
                'is_active' => true,
                'is_default' => false,
                'created_by' => $user->id,
            ],
            [
                'name' => 'Recurso de Aposentadoria Especial',
                'category' => 'recurso',
                'benefit_type' => 'aposentadoria_especial',
                'description' => 'Template para recurso de aposentadoria especial',
                'content' => $this->getRecursoAposentadoriaEspecialTemplate(),
                'is_active' => true,
                'is_default' => false,
                'created_by' => $user->id,
            ],
        ];

        foreach ($templates as $template) {
            PetitionTemplate::updateOrCreate(
                ['name' => $template['name']],
                $template
            );
        }

        $this->command->info('Templates de petições criados com sucesso!');
    }

    private function getRecursoAposentadoriaIdadeTemplate(): string
    {
        return 'EXCELENTÍSSIMO SENHOR DOUTOR JUIZ FEDERAL

{{client_name}}, portador do CPF nº {{client_cpf}}, vem respeitosamente propor

AÇÃO PREVIDENCIÁRIA - APOSENTADORIA POR IDADE

em face do INSTITUTO NACIONAL DO SEGURO SOCIAL - INSS

I - DOS FATOS

O requerente possui {{idade_atual}} anos e {{tempo_contribuicao}} anos de contribuição.
Protocolou requerimento administrativo em {{data_requerimento_administrativo}}, indeferido em {{data_indeferimento}}.

II - DOS VÍNCULOS EMPREGATÍCIOS

{{employment_0_employer}} - {{employment_0_start}} a {{employment_0_end}}
{{employment_1_employer}} - {{employment_1_start}} a {{employment_1_end}}

III - DO DIREITO

A aposentadoria por idade é devida aos 65 anos (homem) ou 62 anos (mulher), com carência de 180 contribuições.

IV - DOS PEDIDOS

Requer-se a concessão da aposentadoria por idade e pagamento das parcelas vencidas.

{{cidade}}, {{current_date}}.

{{lawyer_name}}
OAB nº {{oab_number}}';
    }

    private function getRequerimentoAuxilioDoencaTemplate(): string
    {
        return 'EXCELENTÍSSIMO SENHOR DOUTOR JUIZ FEDERAL

{{client_name}}, portador do CPF nº {{client_cpf}}, vem propor

AÇÃO PREVIDENCIÁRIA - AUXÍLIO-DOENÇA

em face do INSTITUTO NACIONAL DO SEGURO SOCIAL - INSS

I - DOS FATOS

O requerente encontra-se incapacitado para o trabalho desde {{data_inicio_incapacidade}} em razão de {{doenca_cid}}.
Protocolou requerimento em {{data_requerimento_administrativo}}, indeferido em {{data_indeferimento}}.

II - DA INCAPACIDADE

A incapacidade está comprovada por relatórios médicos e exames anexos.

III - DO DIREITO

O auxílio-doença é devido ao segurado incapacitado para o trabalho por mais de 15 dias.

IV - DOS PEDIDOS

Requer-se a concessão do auxílio-doença e pagamento das parcelas vencidas.

{{cidade}}, {{current_date}}.

{{lawyer_name}}
OAB nº {{oab_number}}';
    }

    private function getRecursoAposentadoriaEspecialTemplate(): string
    {
        return 'EXCELENTÍSSIMO SENHOR DOUTOR JUIZ FEDERAL

{{client_name}}, portador do CPF nº {{client_cpf}}, vem propor

AÇÃO PREVIDENCIÁRIA - APOSENTADORIA ESPECIAL

em face do INSTITUTO NACIONAL DO SEGURO SOCIAL - INSS

I - DOS FATOS

O requerente trabalhou {{tempo_atividade_especial}} anos em atividade especial.
Protocolou requerimento em {{data_requerimento_administrativo}}, indeferido em {{data_indeferimento}}.

II - DA ATIVIDADE ESPECIAL

Comprova atividade especial através de PPP e LTCAT anexos.

III - DO DIREITO

A aposentadoria especial é devida após 15, 20 ou 25 anos de atividade especial.

IV - DOS PEDIDOS

Requer-se a concessão da aposentadoria especial.

{{cidade}}, {{current_date}}.

{{lawyer_name}}
OAB nº {{oab_number}}';
    }
}
