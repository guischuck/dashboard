<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WorkflowTemplate;

class WorkflowTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'benefit_type' => 'aposentadoria_por_idade',
                'name' => 'Workflow - Aposentadoria por Idade',
                'description' => 'Workflow padrão para aposentadoria por idade',
                'tasks' => [
                    [
                        'title' => 'Análise de Documentos',
                        'description' => 'Revisar documentos fornecidos pelo cliente',
                        'order' => 1,
                        'required_documents' => ['RG', 'CPF', 'Carteira de Trabalho']
                    ],
                    [
                        'title' => 'Verificação de Idade',
                        'description' => 'Verificar se cliente atende requisito de idade (65 anos homem/60 anos mulher)',
                        'order' => 2,
                        'required_documents' => ['Certidão de Nascimento', 'RG']
                    ],
                    [
                        'title' => 'Coleta de Vínculos',
                        'description' => 'Coletar informações dos vínculos empregatícios (mínimo 15 anos)',
                        'order' => 3,
                        'required_documents' => ['CNIS', 'Vínculos Empregatícios']
                    ],
                    [
                        'title' => 'Elaboração da Petição',
                        'description' => 'Redigir petição inicial para aposentadoria por idade',
                        'order' => 4,
                        'required_documents' => ['Procuração', 'Documentos Pessoais']
                    ],
                    [
                        'title' => 'Protocolo no INSS',
                        'description' => 'Protocolar pedido no INSS',
                        'order' => 5,
                        'required_documents' => ['Petição', 'Documentos Comprobatórios']
                    ]
                ],
                'is_active' => true,
            ],
            [
                'benefit_type' => 'aposentadoria_por_tempo_contribuicao',
                'name' => 'Workflow - Aposentadoria por Tempo de Contribuição',
                'description' => 'Workflow padrão para aposentadoria por tempo de contribuição',
                'tasks' => [
                    [
                        'title' => 'Análise de Documentos',
                        'description' => 'Revisar documentos fornecidos pelo cliente',
                        'order' => 1,
                        'required_documents' => ['RG', 'CPF', 'Carteira de Trabalho']
                    ],
                    [
                        'title' => 'Cálculo de Tempo de Contribuição',
                        'description' => 'Verificar se cliente possui tempo necessário (35 anos homem/30 anos mulher)',
                        'order' => 2,
                        'required_documents' => ['CNIS', 'Vínculos Empregatícios']
                    ],
                    [
                        'title' => 'Reconhecimento de Tempo Especial',
                        'description' => 'Verificar períodos especiais que podem ser convertidos',
                        'order' => 3,
                        'required_documents' => ['PPP', 'LTCAT', 'Laudos Técnicos']
                    ],
                    [
                        'title' => 'Elaboração da Petição',
                        'description' => 'Redigir petição inicial para aposentadoria por tempo de contribuição',
                        'order' => 4,
                        'required_documents' => ['Procuração', 'Documentos Pessoais']
                    ],
                    [
                        'title' => 'Protocolo no INSS',
                        'description' => 'Protocolar pedido no INSS',
                        'order' => 5,
                        'required_documents' => ['Petição', 'Documentos Comprobatórios']
                    ]
                ],
                'is_active' => true,
            ],
            [
                'benefit_type' => 'aposentadoria_professor',
                'name' => 'Workflow - Aposentadoria Professor',
                'description' => 'Workflow padrão para aposentadoria de professor',
                'tasks' => [
                    [
                        'title' => 'Análise de Documentos',
                        'description' => 'Revisar documentos fornecidos pelo cliente',
                        'order' => 1,
                        'required_documents' => ['RG', 'CPF', 'Carteira de Trabalho']
                    ],
                    [
                        'title' => 'Verificação de Atividade Docente',
                        'description' => 'Comprovar exercício exclusivo em funções de magistério (30 anos homem/25 anos mulher)',
                        'order' => 2,
                        'required_documents' => ['Declarações Escolares', 'Contratos de Trabalho', 'CNIS']
                    ],
                    [
                        'title' => 'Coleta de Vínculos Educacionais',
                        'description' => 'Coletar informações de todos os vínculos em instituições de ensino',
                        'order' => 3,
                        'required_documents' => ['Certidões de Tempo de Serviço', 'Declarações das Escolas']
                    ],
                    [
                        'title' => 'Elaboração da Petição',
                        'description' => 'Redigir petição inicial para aposentadoria de professor',
                        'order' => 4,
                        'required_documents' => ['Procuração', 'Documentos Pessoais', 'Comprovantes de Magistério']
                    ],
                    [
                        'title' => 'Protocolo no INSS',
                        'description' => 'Protocolar pedido no INSS',
                        'order' => 5,
                        'required_documents' => ['Petição', 'Documentos Comprobatórios']
                    ]
                ],
                'is_active' => true,
            ],
            [
                'benefit_type' => 'aposentadoria_pcd',
                'name' => 'Workflow - Aposentadoria PCD',
                'description' => 'Workflow padrão para aposentadoria de pessoa com deficiência',
                'tasks' => [
                    [
                        'title' => 'Análise de Documentos',
                        'description' => 'Revisar documentos fornecidos pelo cliente',
                        'order' => 1,
                        'required_documents' => ['RG', 'CPF', 'Carteira de Trabalho']
                    ],
                    [
                        'title' => 'Avaliação da Deficiência',
                        'description' => 'Comprovar deficiência através de laudos médicos e perícias',
                        'order' => 2,
                        'required_documents' => ['Laudos Médicos', 'Relatórios de Perícia', 'Exames']
                    ],
                    [
                        'title' => 'Cálculo de Tempo de Contribuição',
                        'description' => 'Verificar tempo de contribuição conforme grau da deficiência',
                        'order' => 3,
                        'required_documents' => ['CNIS', 'Vínculos Empregatícios', 'Comprovantes de Deficiência']
                    ],
                    [
                        'title' => 'Elaboração da Petição',
                        'description' => 'Redigir petição inicial para aposentadoria PCD',
                        'order' => 4,
                        'required_documents' => ['Procuração', 'Documentos Pessoais', 'Laudos Médicos']
                    ],
                    [
                        'title' => 'Protocolo no INSS',
                        'description' => 'Protocolar pedido no INSS',
                        'order' => 5,
                        'required_documents' => ['Petição', 'Documentos Comprobatórios']
                    ]
                ],
                'is_active' => true,
            ],
            [
                'benefit_type' => 'aposentadoria_especial',
                'name' => 'Workflow - Aposentadoria Especial',
                'description' => 'Workflow padrão para aposentadoria especial',
                'tasks' => [
                    [
                        'title' => 'Análise de Documentos',
                        'description' => 'Revisar documentos fornecidos pelo cliente',
                        'order' => 1,
                        'required_documents' => ['RG', 'CPF', 'Carteira de Trabalho']
                    ],
                    [
                        'title' => 'Levantamento de Atividades Especiais',
                        'description' => 'Identificar períodos de exposição a agentes nocivos',
                        'order' => 2,
                        'required_documents' => ['PPP', 'LTCAT', 'SB-40', 'DIRBEN-8030']
                    ],
                    [
                        'title' => 'Análise de Laudos Técnicos',
                        'description' => 'Avaliar documentos técnicos que comprovem atividade especial',
                        'order' => 3,
                        'required_documents' => ['PPP', 'LTCAT', 'Laudos Ambientais']
                    ],
                    [
                        'title' => 'Cálculo de Tempo Especial',
                        'description' => 'Calcular tempo de atividade especial (15, 20 ou 25 anos)',
                        'order' => 4,
                        'required_documents' => ['CNIS', 'Documentos Técnicos', 'Vínculos Empregatícios']
                    ],
                    [
                        'title' => 'Elaboração da Petição',
                        'description' => 'Redigir petição inicial para aposentadoria especial',
                        'order' => 5,
                        'required_documents' => ['Procuração', 'Documentos Pessoais', 'Laudos Técnicos']
                    ],
                    [
                        'title' => 'Protocolo no INSS',
                        'description' => 'Protocolar pedido no INSS',
                        'order' => 6,
                        'required_documents' => ['Petição', 'Documentos Comprobatórios']
                    ]
                ],
                'is_active' => true,
            ],
            [
                'benefit_type' => 'auxilio_doenca',
                'name' => 'Workflow - Auxílio-Doença',
                'description' => 'Workflow padrão para auxílio-doença',
                'tasks' => [
                    [
                        'title' => 'Análise de Documentos',
                        'description' => 'Revisar documentos fornecidos pelo cliente',
                        'order' => 1,
                        'required_documents' => ['RG', 'CPF', 'Carteira de Trabalho']
                    ],
                    [
                        'title' => 'Avaliação Médica',
                        'description' => 'Analisar laudos médicos e histórico de saúde',
                        'order' => 2,
                        'required_documents' => ['Laudos Médicos', 'Exames', 'Relatórios Hospitalares']
                    ],
                    [
                        'title' => 'Verificação de Carência',
                        'description' => 'Verificar se cliente possui carência mínima (12 contribuições)',
                        'order' => 3,
                        'required_documents' => ['CNIS', 'Vínculos Empregatícios']
                    ],
                    [
                        'title' => 'Elaboração da Petição',
                        'description' => 'Redigir petição inicial para auxílio-doença',
                        'order' => 4,
                        'required_documents' => ['Procuração', 'Documentos Pessoais', 'Laudos Médicos']
                    ],
                    [
                        'title' => 'Protocolo no INSS',
                        'description' => 'Protocolar pedido no INSS',
                        'order' => 5,
                        'required_documents' => ['Petição', 'Documentos Comprobatórios']
                    ]
                ],
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            // Verificar se já existe um template para este tipo de benefício
            $existing = WorkflowTemplate::where('benefit_type', $template['benefit_type'])->first();
            
            if (!$existing) {
                WorkflowTemplate::create($template);
                $this->command->info("Template criado: {$template['name']}");
            } else {
                $this->command->info("Template já existe: {$template['name']}");
            }
        }
    }
} 