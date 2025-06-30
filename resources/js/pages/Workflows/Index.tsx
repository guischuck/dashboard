import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Progress } from '@/components/ui/progress';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import {
    AlertTriangle,
    Calendar,
    CheckCircle,
    Clock,
    Edit,
    Eye,
    FileText,
    Plus,
    Search,
    Settings,
    ToggleLeft,
    ToggleRight,
    Trash2,
    TrendingUp,
    User,
    Users,
} from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Workflows',
        href: '/tasks',
    },
];

interface Workflow {
    id: number;
    title: string;
    description: string;
    status: string;
    priority: string;
    due_date: string;
    completed_at: string | null;
    order: number;
    is_workflow_task: boolean;
    case: {
        id: number;
        client_name: string;
        case_number: string;
    };
    assignedTo: {
        id: number;
        name: string;
    } | null;
    createdBy: {
        id: number;
        name: string;
    };
    workflowTemplate?: {
        id: number;
        name: string;
        benefit_type: string;
    };
}

interface ClientWithWorkflows {
    id: number;
    client_name: string;
    case_number: string;
    benefit_type: string;
    total_tasks: number;
    completed_tasks: number;
    progress_percentage: number;
}

interface WorkflowTemplate {
    id: number;
    benefit_type: string;
    name: string;
    description: string;
    tasks: Array<{
        title: string;
        description: string;
        order: number;
        required_documents: string[];
    }>;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    benefit_type_name: string;
}

interface WorkflowsIndexProps {
    workflows?: {
        data: Workflow[];
        links: any;
        meta: any;
    };
    templates?: {
        data: WorkflowTemplate[];
        links: any;
        meta: any;
    };
    stats?: {
        total: number;
        pending: number;
        in_progress: number;
        completed: number;
        overdue: number;
    };
    templateStats?: {
        total: number;
        active: number;
        inactive: number;
        benefit_types: number;
    };
    clientsWithWorkflows?: ClientWithWorkflows[];
    users?: Array<{
        id: number;
        name: string;
    }>;
    statuses?: Record<string, string>;
    priorities?: Record<string, string>;
    benefitTypes?: Record<string, string>;
    filters?: {
        search?: string;
        status?: string;
        priority?: string;
        benefit_type?: string;
    };
    currentTab?: string;
}

export default function WorkflowsIndex({
    workflows,
    templates,
    stats,
    templateStats,
    clientsWithWorkflows,
    users,
    statuses,
    priorities,
    benefitTypes,
    filters,
    currentTab = 'tasks',
}: WorkflowsIndexProps) {
    const [searchTerm, setSearchTerm] = useState(filters?.search || '');
    const [statusFilter, setStatusFilter] = useState(filters?.status || '');
    const [priorityFilter, setPriorityFilter] = useState(filters?.priority || '');
    const [benefitTypeFilter, setBenefitTypeFilter] = useState(filters?.benefit_type || '');

    const handleSearch = () => {
        const params: any = {
            tab: currentTab,
        };

        if (searchTerm) params.search = searchTerm;
        if (statusFilter) params.status = statusFilter;
        if (priorityFilter) params.priority = priorityFilter;
        if (benefitTypeFilter) params.benefit_type = benefitTypeFilter;

        router.get('/tasks', params, {
            preserveState: true,
        });
    };

    const handleKeyPress = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter') {
            handleSearch();
        }
    };

    const handleTabChange = (tab: string) => {
        router.get('/tasks', { tab }, { preserveState: true });
    };

    const toggleTemplate = (templateId: number) => {
        router.patch(
            `/tasks/templates/${templateId}/toggle`,
            {},
            {
                preserveState: true,
            },
        );
    };

    const deleteTemplate = (templateId: number) => {
        if (confirm('Tem certeza que deseja excluir este template?')) {
            router.delete(`/tasks/templates/${templateId}`, {
                preserveState: true,
            });
        }
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'pending':
                return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
            case 'in_progress':
                return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
            case 'completed':
                return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
            case 'cancelled':
                return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
            default:
                return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200';
        }
    };

    const getPriorityColor = (priority: string) => {
        switch (priority) {
            case 'low':
                return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
            case 'medium':
                return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
            case 'high':
                return 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200';
            case 'urgent':
                return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
            default:
                return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200';
        }
    };

    const isOverdue = (dueDate: string, status: string) => {
        return new Date(dueDate) < new Date() && status !== 'completed';
    };

    const getBenefitTypeName = (benefitType: string) => {
        const types: Record<string, string> = {
            aposentadoria_por_idade: 'Aposentadoria por Idade',
            aposentadoria_por_tempo_contribuicao: 'Aposentadoria por Tempo de Contribuição',
            aposentadoria_professor: 'Aposentadoria Professor',
            aposentadoria_pcd: 'Aposentadoria PCD',
            aposentadoria_especial: 'Aposentadoria Especial',
            auxilio_doenca: 'Auxílio-Doença',
            beneficio_por_incapacidade: 'Benefício por Incapacidade',
            pensao_por_morte: 'Pensão por Morte',
            auxilio_acidente: 'Auxílio-Acidente',
            salario_maternidade: 'Salário-Maternidade',
        };
        return types[benefitType] || benefitType;
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Workflows - PrevidIA" />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="flex items-center text-3xl font-bold">
                            <Users className="mr-3 h-8 w-8 text-primary" />
                            Workflows
                        </h1>
                        <p className="text-muted-foreground">Gerencie workflows e templates para cada tipo de benefício</p>
                    </div>
                    <div className="flex space-x-2">
                        <Link href="/tasks/templates/create">
                            <Button variant="outline">
                                <Settings className="mr-2 h-4 w-4" />
                                Novo Template
                            </Button>
                        </Link>
                        <Link href="/tasks/create">
                            <Button>
                                <Plus className="mr-2 h-4 w-4" />
                                Nova Tarefa
                            </Button>
                        </Link>
                    </div>
                </div>

                <Tabs value={currentTab} onValueChange={handleTabChange}>
                    <TabsList>
                        <TabsTrigger value="tasks">
                            <FileText className="mr-2 h-4 w-4" />
                            <span>Tarefas Ativas</span>
                        </TabsTrigger>
                        <TabsTrigger value="templates">
                            <Settings className="mr-2 h-4 w-4" />
                            <span>Templates</span>
                        </TabsTrigger>
                    </TabsList>

                    <TabsContent value="tasks" className="space-y-6">
                        {/* Search and Filters */}
                        <Card>
                            <CardContent className="pt-6">
                                <div className="flex items-center space-x-4">
                                    <div className="relative flex-1">
                                        <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transform text-muted-foreground" />
                                        <Input
                                            placeholder="Buscar por título, descrição ou cliente..."
                                            value={searchTerm}
                                            onChange={(e) => setSearchTerm(e.target.value)}
                                            onKeyPress={handleKeyPress}
                                            className="pl-10"
                                        />
                                    </div>
                                    <Select value={statusFilter || 'all'} onValueChange={(value) => setStatusFilter(value === 'all' ? '' : value)}>
                                        <SelectTrigger className="w-40">
                                            <SelectValue placeholder="Status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Todos os Status</SelectItem>
                                            {statuses &&
                                                Object.entries(statuses).map(([key, value]) => (
                                                    <SelectItem key={key} value={key}>
                                                        {value}
                                                    </SelectItem>
                                                ))}
                                        </SelectContent>
                                    </Select>
                                    <Select
                                        value={priorityFilter || 'all'}
                                        onValueChange={(value) => setPriorityFilter(value === 'all' ? '' : value)}
                                    >
                                        <SelectTrigger className="w-40">
                                            <SelectValue placeholder="Prioridade" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Todas as Prioridades</SelectItem>
                                            {priorities &&
                                                Object.entries(priorities).map(([key, value]) => (
                                                    <SelectItem key={key} value={key}>
                                                        {value}
                                                    </SelectItem>
                                                ))}
                                        </SelectContent>
                                    </Select>
                                    <Button onClick={handleSearch}>Buscar</Button>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Stats Cards */}
                        {stats && (
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-5">
                                <Card>
                                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                        <CardTitle className="text-sm font-medium">Total</CardTitle>
                                        <Users className="h-4 w-4 text-muted-foreground" />
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-2xl font-bold">{stats.total}</div>
                                        <p className="text-xs text-muted-foreground">Total de tarefas</p>
                                    </CardContent>
                                </Card>
                                <Card>
                                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                        <CardTitle className="text-sm font-medium">Pendentes</CardTitle>
                                        <Clock className="h-4 w-4 text-muted-foreground" />
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-2xl font-bold">{stats.pending}</div>
                                        <p className="text-xs text-muted-foreground">Aguardando início</p>
                                    </CardContent>
                                </Card>
                                <Card>
                                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                        <CardTitle className="text-sm font-medium">Em Andamento</CardTitle>
                                        <TrendingUp className="h-4 w-4 text-muted-foreground" />
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-2xl font-bold">{stats.in_progress}</div>
                                        <p className="text-xs text-muted-foreground">Sendo executadas</p>
                                    </CardContent>
                                </Card>
                                <Card>
                                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                        <CardTitle className="text-sm font-medium">Concluídas</CardTitle>
                                        <CheckCircle className="h-4 w-4 text-muted-foreground" />
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-2xl font-bold">{stats.completed}</div>
                                        <p className="text-xs text-muted-foreground">Finalizadas</p>
                                    </CardContent>
                                </Card>
                                <Card>
                                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                        <CardTitle className="text-sm font-medium">Atrasadas</CardTitle>
                                        <AlertTriangle className="h-4 w-4 text-muted-foreground" />
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-2xl font-bold">{stats.overdue}</div>
                                        <p className="text-xs text-muted-foreground">Vencidas</p>
                                    </CardContent>
                                </Card>
                            </div>
                        )}

                        <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                            {/* Lista de Workflows */}
                            <div className="lg:col-span-2">
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Tarefas Ativas</CardTitle>
                                        <CardDescription>Lista de todas as tarefas em andamento</CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        {workflows && workflows.data.length > 0 ? (
                                            <div className="space-y-4">
                                                {workflows.data.map((workflow) => (
                                                    <div key={workflow.id} className="space-y-3 rounded-lg border p-4">
                                                        <div className="flex items-center justify-between">
                                                            <div className="flex items-center space-x-2">
                                                                <h3 className="font-medium">{workflow.title}</h3>
                                                                {workflow.is_workflow_task && (
                                                                    <Badge variant="secondary" className="text-xs">
                                                                        Workflow
                                                                    </Badge>
                                                                )}
                                                            </div>
                                                            <div className="flex items-center space-x-2">
                                                                <Badge className={getStatusColor(workflow.status)}>
                                                                    {statuses?.[workflow.status] || workflow.status}
                                                                </Badge>
                                                                <Badge className={getPriorityColor(workflow.priority)}>
                                                                    {priorities?.[workflow.priority] || workflow.priority}
                                                                </Badge>
                                                            </div>
                                                        </div>

                                                        <p className="text-sm text-muted-foreground">{workflow.description}</p>

                                                        <div className="flex items-center justify-between text-sm">
                                                            <div className="flex items-center space-x-4">
                                                                <span className="flex items-center">
                                                                    <User className="mr-1 h-3 w-3" />
                                                                    {workflow.case.client_name}
                                                                </span>
                                                                <span className="flex items-center">
                                                                    <Calendar className="mr-1 h-3 w-3" />
                                                                    {new Date(workflow.due_date).toLocaleDateString()}
                                                                </span>
                                                                {isOverdue(workflow.due_date, workflow.status) && (
                                                                    <Badge variant="destructive" className="text-xs">
                                                                        Atrasada
                                                                    </Badge>
                                                                )}
                                                            </div>
                                                            <div className="flex space-x-2">
                                                                <Link href={`/tasks/${workflow.id}`}>
                                                                    <Button variant="ghost" size="sm">
                                                                        <Eye className="h-4 w-4" />
                                                                    </Button>
                                                                </Link>
                                                                <Link href={`/tasks/${workflow.id}/edit`}>
                                                                    <Button variant="ghost" size="sm">
                                                                        <Edit className="h-4 w-4" />
                                                                    </Button>
                                                                </Link>
                                                            </div>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        ) : (
                                            <div className="py-8 text-center">
                                                <FileText className="mx-auto h-12 w-12 text-muted-foreground" />
                                                <h3 className="mt-2 text-sm font-medium">Nenhuma tarefa encontrada</h3>
                                                <p className="mt-1 text-sm text-muted-foreground">Comece criando uma nova tarefa ou template.</p>
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>
                            </div>

                            {/* Progresso dos Clientes */}
                            <div>
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Progresso dos Clientes</CardTitle>
                                        <CardDescription>Acompanhe o progresso dos workflows por cliente</CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        {clientsWithWorkflows && clientsWithWorkflows.length > 0 ? (
                                            <div className="space-y-4">
                                                {clientsWithWorkflows.map((client) => (
                                                    <div key={client.id} className="space-y-2">
                                                        <div className="flex items-center justify-between">
                                                            <div>
                                                                <h4 className="text-sm font-medium">{client.client_name}</h4>
                                                                <p className="text-xs text-muted-foreground">
                                                                    {getBenefitTypeName(client.benefit_type)}
                                                                </p>
                                                            </div>
                                                            <span className="text-sm font-medium">{client.progress_percentage}%</span>
                                                        </div>
                                                        <Progress value={client.progress_percentage} className="h-2" />
                                                        <p className="text-xs text-muted-foreground">
                                                            {client.completed_tasks} de {client.total_tasks} tarefas concluídas
                                                        </p>
                                                    </div>
                                                ))}
                                            </div>
                                        ) : (
                                            <div className="py-4 text-center">
                                                <p className="text-sm text-muted-foreground">Nenhum cliente com workflows ativos</p>
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>
                            </div>
                        </div>
                    </TabsContent>

                    <TabsContent value="templates" className="space-y-6">
                        {/* Search and Filters para Templates */}
                        <Card>
                            <CardContent className="pt-6">
                                <div className="flex items-center space-x-4">
                                    <div className="relative flex-1">
                                        <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transform text-muted-foreground" />
                                        <Input
                                            placeholder="Buscar por nome, descrição ou tipo de benefício..."
                                            value={searchTerm}
                                            onChange={(e) => setSearchTerm(e.target.value)}
                                            onKeyPress={handleKeyPress}
                                            className="pl-10"
                                        />
                                    </div>
                                    <Select
                                        value={benefitTypeFilter || 'all'}
                                        onValueChange={(value) => setBenefitTypeFilter(value === 'all' ? '' : value)}
                                    >
                                        <SelectTrigger className="w-60">
                                            <SelectValue placeholder="Tipo de Benefício" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Todos os Benefícios</SelectItem>
                                            {benefitTypes &&
                                                Object.entries(benefitTypes).map(([key, value]) => (
                                                    <SelectItem key={key} value={key}>
                                                        {value}
                                                    </SelectItem>
                                                ))}
                                        </SelectContent>
                                    </Select>
                                    <Select value={statusFilter || 'all'} onValueChange={(value) => setStatusFilter(value === 'all' ? '' : value)}>
                                        <SelectTrigger className="w-40">
                                            <SelectValue placeholder="Status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Todos</SelectItem>
                                            <SelectItem value="active">Ativos</SelectItem>
                                            <SelectItem value="inactive">Inativos</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <Button onClick={handleSearch}>Buscar</Button>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Stats Cards para Templates */}
                        {templateStats && (
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
                                <Card>
                                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                        <CardTitle className="text-sm font-medium">Total</CardTitle>
                                        <Settings className="h-4 w-4 text-muted-foreground" />
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-2xl font-bold">{templateStats.total}</div>
                                        <p className="text-xs text-muted-foreground">Templates criados</p>
                                    </CardContent>
                                </Card>
                                <Card>
                                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                        <CardTitle className="text-sm font-medium">Ativos</CardTitle>
                                        <CheckCircle className="h-4 w-4 text-muted-foreground" />
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-2xl font-bold">{templateStats.active}</div>
                                        <p className="text-xs text-muted-foreground">Em uso</p>
                                    </CardContent>
                                </Card>
                                <Card>
                                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                        <CardTitle className="text-sm font-medium">Inativos</CardTitle>
                                        <AlertTriangle className="h-4 w-4 text-muted-foreground" />
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-2xl font-bold">{templateStats.inactive}</div>
                                        <p className="text-xs text-muted-foreground">Desabilitados</p>
                                    </CardContent>
                                </Card>
                                <Card>
                                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                        <CardTitle className="text-sm font-medium">Tipos de Benefício</CardTitle>
                                        <FileText className="h-4 w-4 text-muted-foreground" />
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-2xl font-bold">{templateStats.benefit_types}</div>
                                        <p className="text-xs text-muted-foreground">Benefícios cobertos</p>
                                    </CardContent>
                                </Card>
                            </div>
                        )}

                        {/* Lista de Templates */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Templates de Workflow</CardTitle>
                                <CardDescription>Gerencie os templates de tarefas para cada tipo de benefício</CardDescription>
                            </CardHeader>
                            <CardContent>
                                {templates && templates.data.length > 0 ? (
                                    <div className="space-y-4">
                                        {templates.data.map((template) => (
                                            <div key={template.id} className="space-y-3 rounded-lg border p-4">
                                                <div className="flex items-center justify-between">
                                                    <div className="flex items-center space-x-3">
                                                        <div>
                                                            <h3 className="font-medium">{template.name}</h3>
                                                            <p className="text-sm text-muted-foreground">
                                                                {getBenefitTypeName(template.benefit_type)}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div className="flex items-center space-x-2">
                                                        <Badge variant={template.is_active ? 'default' : 'secondary'}>
                                                            {template.is_active ? 'Ativo' : 'Inativo'}
                                                        </Badge>
                                                        <Badge variant="outline">{template.tasks.length} tarefas</Badge>
                                                    </div>
                                                </div>

                                                {template.description && <p className="text-sm text-muted-foreground">{template.description}</p>}

                                                <div className="flex items-center justify-between">
                                                    <div className="flex items-center space-x-4 text-sm text-muted-foreground">
                                                        <span>Criado em {new Date(template.created_at).toLocaleDateString()}</span>
                                                        <span>Atualizado em {new Date(template.updated_at).toLocaleDateString()}</span>
                                                    </div>
                                                    <div className="flex space-x-2">
                                                        <Button variant="ghost" size="sm" onClick={() => toggleTemplate(template.id)}>
                                                            {template.is_active ? (
                                                                <ToggleRight className="h-4 w-4" />
                                                            ) : (
                                                                <ToggleLeft className="h-4 w-4" />
                                                            )}
                                                        </Button>
                                                        <Link href={`/tasks/templates/${template.id}/edit`}>
                                                            <Button variant="ghost" size="sm">
                                                                <Edit className="h-4 w-4" />
                                                            </Button>
                                                        </Link>
                                                        <Button variant="ghost" size="sm" onClick={() => deleteTemplate(template.id)}>
                                                            <Trash2 className="h-4 w-4" />
                                                        </Button>
                                                    </div>
                                                </div>

                                                {/* Lista de tarefas do template */}
                                                <div className="border-t pt-3">
                                                    <h4 className="mb-2 text-sm font-medium">Tarefas:</h4>
                                                    <div className="space-y-1">
                                                        {template.tasks.slice(0, 3).map((task, index) => (
                                                            <div key={index} className="text-sm text-muted-foreground">
                                                                {task.order}. {task.title}
                                                            </div>
                                                        ))}
                                                        {template.tasks.length > 3 && (
                                                            <div className="text-sm text-muted-foreground">
                                                                ... e mais {template.tasks.length - 3} tarefas
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="py-8 text-center">
                                        <Settings className="mx-auto h-12 w-12 text-muted-foreground" />
                                        <h3 className="mt-2 text-sm font-medium">Nenhum template encontrado</h3>
                                        <p className="mt-1 text-sm text-muted-foreground">Comece criando um novo template de workflow.</p>
                                        <Link href="/tasks/templates/create">
                                            <Button className="mt-4">
                                                <Plus className="mr-2 h-4 w-4" />
                                                Criar Template
                                            </Button>
                                        </Link>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
