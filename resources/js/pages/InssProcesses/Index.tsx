import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { CalendarIcon, FileText, CheckCircle, AlertCircle, Users, Search, Filter, X, ExternalLink } from 'lucide-react';
import { cn } from '@/lib/utils';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Processos INSS',
        href: '/inss-processes',
    },
];

interface Processo {
    id: number;
    protocolo: string;
    nome: string;
    cpf: string;
    servico: string;
    situacao: string;
    protocolado_em: string;
    ultima_atualizacao: string;
}

interface ProcessosIndexProps {
    processos: {
        data: Processo[];
        total: number;
        current_page: number;
        last_page: number;
    };
    stats: {
        processos_ativos: number;
        processos_concluidos: number;
        processos_exigencia: number;
        protocolados_hoje: number;
        total_processos: number;
    };
    statusOptions: string[];
    servicoOptions: string[];
    filters: {
        search?: string;
        status?: string;
        servico?: string;
        periodo?: string;
    };
    error?: string;
}

export default function InssProcessesIndex({ processos, stats, statusOptions, servicoOptions, filters, error }: ProcessosIndexProps) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedStatus, setSelectedStatus] = useState(filters.status || 'all');
    const [selectedServico, setSelectedServico] = useState(filters.servico || 'all');
    const [selectedPeriodo, setSelectedPeriodo] = useState(filters.periodo || 'all');

    const handleFilter = () => {
        router.get('/inss-processes', {
            search: searchTerm,
            status: selectedStatus === 'all' ? '' : selectedStatus,
            servico: selectedServico === 'all' ? '' : selectedServico,
            periodo: selectedPeriodo === 'all' ? '' : selectedPeriodo,
        }, {
            preserveState: true,
        });
    };

    const clearFilters = () => {
        setSearchTerm('');
        setSelectedStatus('all');
        setSelectedServico('all');
        setSelectedPeriodo('all');
        router.get('/inss-processes');
    };

    const filterByStatus = (status: string) => {
        setSelectedStatus(status);
        router.get('/inss-processes', {
            search: searchTerm,
            status: status === 'all' ? '' : status,
            servico: selectedServico === 'all' ? '' : selectedServico,
            periodo: selectedPeriodo === 'all' ? '' : selectedPeriodo,
        }, {
            preserveState: true,
        });
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'Em Análise':
                return 'bg-blue-100 text-blue-800 border-blue-200';
            case 'Concluída':
                return 'bg-green-100 text-green-800 border-green-200';
            case 'Em Exigência':
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
        });
    };

    if (error) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Processos INSS" />
                <div className="flex h-full flex-1 flex-col gap-6 p-6">
                    {/* Header */}
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-3xl font-bold">Processos INSS</h1>
                            <p className="text-muted-foreground">
                                Gerencie seus processos do INSS
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
            <Head title="Processos INSS" />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold">Processos INSS</h1>
                        <p className="text-muted-foreground">
                            Gerencie seus processos do INSS - {stats?.total_processos || 0} processos encontrados
                        </p>
                    </div>
                </div>

                {/* Stats Cards */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card className={cn(
                        "cursor-pointer transition-all duration-200 hover:shadow-md",
                        selectedStatus === 'Em Análise' && "ring-2 ring-blue-500"
                    )} onClick={() => filterByStatus('Em Análise')}>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Em Análise</CardTitle>
                            <FileText className="h-4 w-4 text-blue-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-blue-600">{stats?.processos_ativos || 0}</div>
                            <p className="text-xs text-muted-foreground mt-1">Clique para filtrar</p>
                        </CardContent>
                    </Card>
                    
                    <Card className={cn(
                        "cursor-pointer transition-all duration-200 hover:shadow-md",
                        selectedStatus === 'Concluída' && "ring-2 ring-green-500"
                    )} onClick={() => filterByStatus('Concluída')}>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Concluída</CardTitle>
                            <CheckCircle className="h-4 w-4 text-green-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-green-600">{stats?.processos_concluidos || 0}</div>
                            <p className="text-xs text-muted-foreground mt-1">Clique para filtrar</p>
                        </CardContent>
                    </Card>
                    
                    <Card className={cn(
                        "cursor-pointer transition-all duration-200 hover:shadow-md",
                        selectedStatus === 'Em Exigência' && "ring-2 ring-orange-500"
                    )} onClick={() => filterByStatus('Em Exigência')}>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Em Exigência</CardTitle>
                            <AlertCircle className="h-4 w-4 text-orange-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-orange-600">{stats?.processos_exigencia || 0}</div>
                            <p className="text-xs text-muted-foreground mt-1">Clique para filtrar</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Protocolados Hoje</CardTitle>
                            <CalendarIcon className="h-4 w-4 text-purple-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-purple-600">{stats?.protocolados_hoje || 0}</div>
                            <p className="text-xs text-muted-foreground mt-1">Novos processos</p>
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
                                <label className="text-sm font-medium">Status</label>
                                <Select value={selectedStatus} onValueChange={setSelectedStatus}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Selecionar status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todos os status</SelectItem>
                                        {statusOptions.map((status) => (
                                            <SelectItem key={status} value={status}>
                                                {status}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            
                            <div className="space-y-2">
                                <label className="text-sm font-medium">Serviço</label>
                                <Select value={selectedServico} onValueChange={setSelectedServico}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Selecionar serviço" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todos os serviços</SelectItem>
                                        {servicoOptions.map((servico) => (
                                            <SelectItem key={servico} value={servico}>
                                                {servico}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            
                            <div className="space-y-2">
                                <label className="text-sm font-medium">Período</label>
                                <Select value={selectedPeriodo} onValueChange={setSelectedPeriodo}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Selecionar período" />
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

                {/* Lista de Processos */}
                <div className="space-y-4">
                    {processos?.data?.length > 0 ? (
                        <>
                            <div className="grid gap-4">
                                {processos.data.map((processo) => (
                                    <Card key={processo.id} className="p-6">
                                        <div className="flex items-center justify-between">
                                            <div className="flex-1 space-y-2">
                                                <div className="flex items-center gap-4">
                                                    <h3 className="text-lg font-semibold">{processo.nome}</h3>
                                                    <Badge className={getStatusColor(processo.situacao)}>
                                                        {processo.situacao}
                                                    </Badge>
                                                </div>
                                                
                                                <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm text-muted-foreground">
                                                    <div>
                                                        <span className="font-medium">CPF:</span> {processo.cpf}
                                                    </div>
                                                    <div>
                                                        <span className="font-medium">Protocolo:</span> {processo.protocolo}
                                                    </div>
                                                    <div>
                                                        <span className="font-medium">Serviço:</span> {processo.servico}
                                                    </div>
                                                    <div>
                                                        <span className="font-medium">Atualizado em:</span> {formatDate(processo.ultima_atualizacao)}
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div className="flex items-center gap-2">
                                                <Button
                                                    asChild
                                                    variant="outline"
                                                    size="sm"
                                                    className="gap-2"
                                                >
                                                    <a
                                                        href={`https://atendimento.inss.gov.br/tarefas/detalhar_tarefa/${processo.protocolo}`}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        title="Abrir processo no site do INSS"
                                                    >
                                                        <ExternalLink className="h-4 w-4" />
                                                        Ver no INSS
                                                    </a>
                                                </Button>
                                            </div>
                                        </div>
                                    </Card>
                                ))}
                            </div>

                            {/* Paginação */}
                            {processos.last_page > 1 && (
                                <div className="flex items-center justify-center gap-2">
                                    <div className="text-sm text-muted-foreground">
                                        Página {processos.current_page} de {processos.last_page}
                                    </div>
                                </div>
                            )}
                        </>
                    ) : (
                        <Card className="p-8 text-center">
                            <CardContent>
                                <Users className="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                                <h3 className="text-lg font-semibold mb-2">Nenhum processo encontrado</h3>
                                <p className="mb-4 text-muted-foreground">
                                    {searchTerm || (selectedStatus !== 'all') || (selectedServico !== 'all') || (selectedPeriodo !== 'all') 
                                        ? 'Nenhum processo encontrado com os filtros aplicados' 
                                        : 'Nenhum processo encontrado com status: Em Análise, Em Exigência ou Concluída'}
                                </p>
                                {(searchTerm || (selectedStatus !== 'all') || (selectedServico !== 'all') || (selectedPeriodo !== 'all')) && (
                                    <Button onClick={clearFilters} variant="outline">
                                        Limpar filtros
                                    </Button>
                                )}
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>
        </AppLayout>
    );
} 