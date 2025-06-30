import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { CalendarIcon, FileText, CheckCircle, AlertCircle, Eye, EyeOff, Search, Filter, X, ExternalLink, Check } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Andamentos',
        href: '/andamentos',
    },
];

interface Andamento {
    id: number;
    situacao_anterior: string;
    situacao_atual: string;
    data_mudanca: string;
    visto: boolean;
    visto_em?: string;
    processo: {
        id: number;
        protocolo: string;
        nome: string;
        cpf: string;
        servico: string;
        situacao: string;
    };
}

interface AndamentosIndexProps {
    andamentos: {
        data: Andamento[];
        total: number;
        current_page: number;
        last_page: number;
    };
    stats: {
        total_alteracoes: number;
        nao_vistos: number;
        vistos: number;
        alteracoes_hoje: number;
    };
    situacaoOptions: string[];
    filters: {
        search?: string;
        nova_situacao?: string;
        periodo?: string;
        visualizacao?: string;
    };
    error?: string;
}

export default function AndamentosIndex({ andamentos, stats, situacaoOptions, filters, error }: AndamentosIndexProps) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedNovaSituacao, setSelectedNovaSituacao] = useState(filters.nova_situacao || 'all');
    const [selectedPeriodo, setSelectedPeriodo] = useState(filters.periodo || 'all');
    const [selectedVisualizacao, setSelectedVisualizacao] = useState(filters.visualizacao || 'all');

    const handleFilter = () => {
        router.get('/andamentos', {
            search: searchTerm,
            nova_situacao: selectedNovaSituacao === 'all' ? '' : selectedNovaSituacao,
            periodo: selectedPeriodo === 'all' ? '' : selectedPeriodo,
            visualizacao: selectedVisualizacao === 'all' ? '' : selectedVisualizacao,
        }, {
            preserveState: true,
        });
    };

    const clearFilters = () => {
        setSearchTerm('');
        setSelectedNovaSituacao('all');
        setSelectedPeriodo('all');
        setSelectedVisualizacao('all');
        router.get('/andamentos');
    };

    const marcarVisto = (andamentoId: number) => {
        router.patch(`/andamentos/${andamentoId}/marcar-visto`, {}, {
            preserveState: true,
            onSuccess: () => {
                // Atualiza a página para refletir as mudanças
            }
        });
    };

    const marcarTodosVistos = () => {
        router.post('/andamentos/marcar-todos-vistos', {
            search: searchTerm,
            nova_situacao: selectedNovaSituacao === 'all' ? '' : selectedNovaSituacao,
            periodo: selectedPeriodo === 'all' ? '' : selectedPeriodo,
            visualizacao: selectedVisualizacao === 'all' ? '' : selectedVisualizacao,
        }, {
            preserveState: true,
            onSuccess: () => {
                // Atualiza a página para refletir as mudanças
            }
        });
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'Em Análise':
                return 'bg-blue-100 text-blue-800 border-blue-200';
            case 'Concluída':
                return 'bg-green-100 text-green-800 border-green-200';
            case 'Em Exigência':
            case 'Exigência':
                return 'bg-orange-100 text-orange-800 border-orange-200';
            default:
                return 'bg-gray-100 text-gray-800 border-gray-200';
        }
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    if (error) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Andamentos" />
                <div className="flex h-full flex-1 flex-col gap-6 p-6">
                    {/* Header */}
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-3xl font-bold">Andamentos</h1>
                            <p className="text-muted-foreground">
                                Acompanhe as mudanças de situação dos processos
                            </p>
                        </div>
                    </div>

                    <Card className="p-8 text-center">
                        <CardHeader>
                            <CardTitle className="text-red-600">Erro ao carregar dados</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-muted-foreground">{error}</p>
                        </CardContent>
                    </Card>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Andamentos" />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold">Andamentos</h1>
                        <p className="text-muted-foreground">
                            Acompanhe as mudanças de situação dos processos - {stats?.total_alteracoes || 0} alterações encontradas
                        </p>
                    </div>
                </div>

                {/* Stats Cards */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total de Alterações</CardTitle>
                            <FileText className="h-4 w-4 text-blue-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-blue-600">{stats?.total_alteracoes || 0}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Não Vistos</CardTitle>
                            <EyeOff className="h-4 w-4 text-red-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-red-600">{stats?.nao_vistos || 0}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Vistos</CardTitle>
                            <Eye className="h-4 w-4 text-green-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-green-600">{stats?.vistos || 0}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Alterações Hoje</CardTitle>
                            <CalendarIcon className="h-4 w-4 text-purple-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-purple-600">{stats?.alteracoes_hoje || 0}</div>
                        </CardContent>
                    </Card>
                </div>

                {/* Filtros */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Filter className="h-4 w-4" />
                            Filtros
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
                            <div className="space-y-2">
                                <label className="text-sm font-medium">Buscar</label>
                                <div className="relative">
                                    <Search className="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        placeholder="Nome, protocolo ou CPF"
                                        value={searchTerm}
                                        onChange={(e) => setSearchTerm(e.target.value)}
                                        className="pl-8"
                                    />
                                </div>
                            </div>
                            
                            <div className="space-y-2">
                                <label className="text-sm font-medium">Nova Situação</label>
                                <Select value={selectedNovaSituacao} onValueChange={setSelectedNovaSituacao}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Todas as situações" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todas as situações</SelectItem>
                                        {situacaoOptions.map((situacao) => (
                                            <SelectItem key={situacao} value={situacao}>
                                                {situacao}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            
                            <div className="space-y-2">
                                <label className="text-sm font-medium">Período</label>
                                <Select value={selectedPeriodo} onValueChange={setSelectedPeriodo}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Todos os períodos" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todos os períodos</SelectItem>
                                        <SelectItem value="hoje">Hoje</SelectItem>
                                        <SelectItem value="semana">Esta semana</SelectItem>
                                        <SelectItem value="mes">Este mês</SelectItem>
                                        <SelectItem value="trimestre">Este trimestre</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            
                            <div className="space-y-2">
                                <label className="text-sm font-medium">Visualização</label>
                                <Select value={selectedVisualizacao} onValueChange={setSelectedVisualizacao}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Todos" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todos</SelectItem>
                                        <SelectItem value="nao_visto">Apenas não vistos</SelectItem>
                                        <SelectItem value="visto">Apenas vistos</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            
                            <div className="space-y-2">
                                <label className="text-sm font-medium">&nbsp;</label>
                                <div className="flex gap-2">
                                    <Button onClick={handleFilter} className="flex-1">
                                        Filtrar
                                    </Button>
                                    <Button onClick={clearFilters} variant="outline" size="icon">
                                        <X className="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Andamentos dos Processos */}
                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <div>
                                <CardTitle>Andamentos dos Processos</CardTitle>
                                <p className="text-sm text-muted-foreground">
                                    ({andamentos?.total || 0} alterações encontradas)
                                </p>
                            </div>
                            {stats?.nao_vistos > 0 && (
                                <Button 
                                    onClick={marcarTodosVistos}
                                    variant="default"
                                    className="bg-green-600 hover:bg-green-700"
                                >
                                    <Check className="h-4 w-4 mr-2" />
                                    Marcar Todos como Visto
                                </Button>
                            )}
                        </div>
                    </CardHeader>
                    <CardContent>
                        {andamentos?.data?.length > 0 ? (
                            <div className="space-y-4">
                                {/* Header da tabela */}
                                <div className="grid grid-cols-12 gap-4 py-3 px-4 bg-gray-50 rounded-lg text-sm font-medium text-gray-700">
                                    <div className="col-span-2">PROTOCOLO</div>
                                    <div className="col-span-2">CLIENTE</div>
                                    <div className="col-span-2">SERVIÇO</div>
                                    <div className="col-span-2">SITUAÇÃO ANTERIOR</div>
                                    <div className="col-span-2">NOVA SITUAÇÃO</div>
                                    <div className="col-span-1">ÚLTIMA ATUALIZAÇÃO</div>
                                    <div className="col-span-1">AÇÕES</div>
                                </div>

                                {/* Dados */}
                                {andamentos.data.map((andamento) => (
                                    <div
                                        key={andamento.id}
                                        className="grid grid-cols-12 gap-4 py-4 px-4 border rounded-lg hover:bg-gray-50 transition-colors"
                                    >
                                        <div className="col-span-2 flex items-center gap-2">
                                            <div 
                                                className={`w-2 h-2 rounded-full ${andamento.visto ? 'bg-gray-400' : 'bg-red-500'}`}
                                                title={andamento.visto ? 'Visto' : 'Não visto'}
                                            />
                                            <span className="font-mono text-sm">{andamento.processo.protocolo}</span>
                                        </div>
                                        
                                        <div className="col-span-2">
                                            <div className="font-medium">{andamento.processo.nome}</div>
                                            <div className="text-sm text-muted-foreground">CPF: {andamento.processo.cpf}</div>
                                        </div>
                                        
                                        <div className="col-span-2 text-sm">
                                            {andamento.processo.servico}
                                        </div>
                                        
                                        <div className="col-span-2">
                                            <Badge className={getStatusColor(andamento.situacao_anterior || 'N/A')}>
                                                {andamento.situacao_anterior || 'N/A'}
                                            </Badge>
                                        </div>
                                        
                                        <div className="col-span-2">
                                            <Badge className={getStatusColor(andamento.situacao_atual)}>
                                                {andamento.situacao_atual}
                                            </Badge>
                                        </div>
                                        
                                        <div className="col-span-1 text-xs text-muted-foreground">
                                            {formatDate(andamento.data_mudanca)}
                                        </div>
                                        
                                        <div className="col-span-1 flex items-center gap-1">
                                            <Button
                                                asChild
                                                variant="outline"
                                                size="sm"
                                                className="h-8 w-8 p-0"
                                                title="Ver no INSS"
                                            >
                                                <a
                                                    href={`https://atendimento.inss.gov.br/tarefas/detalhar_tarefa/${andamento.processo.protocolo}`}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                >
                                                    <ExternalLink className="h-3 w-3" />
                                                </a>
                                            </Button>
                                            
                                            {!andamento.visto && (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    className="h-8 w-8 p-0"
                                                    onClick={() => marcarVisto(andamento.id)}
                                                    title="Marcar como visto"
                                                >
                                                    <Check className="h-3 w-3" />
                                                </Button>
                                            )}
                                        </div>
                                    </div>
                                ))}

                                {/* Paginação */}
                                {andamentos.last_page > 1 && (
                                    <div className="flex items-center justify-center gap-2">
                                        <div className="text-sm text-muted-foreground">
                                            Página {andamentos.current_page} de {andamentos.last_page}
                                        </div>
                                    </div>
                                )}
                            </div>
                        ) : (
                            <div className="py-12 text-center">
                                <FileText className="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                                <h3 className="text-lg font-semibold mb-2">Nenhum andamento encontrado</h3>
                                <p className="mb-4 text-muted-foreground">
                                    {searchTerm || (selectedNovaSituacao !== 'all') || (selectedPeriodo !== 'all') || (selectedVisualizacao !== 'all')
                                        ? 'Nenhum andamento encontrado com os filtros aplicados' 
                                        : 'Não há alterações de situação para exibir'}
                                </p>
                                {(searchTerm || (selectedNovaSituacao !== 'all') || (selectedPeriodo !== 'all') || (selectedVisualizacao !== 'all')) && (
                                    <Button onClick={clearFilters} variant="outline">
                                        Limpar filtros
                                    </Button>
                                )}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
