import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import {
    AlertTriangle,
    BarChart3,
    BookOpen,
    Briefcase,
    Building2,
    CheckCircle,
    FileText,
    MessageSquare,
    Plus,
    Upload,
    Users,
    Workflow,
} from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

interface DashboardProps {
    isSuperAdmin?: boolean;
    stats?: {
        total_cases: number;
        pendente: number;
        em_coleta: number;
        aguarda_peticao: number;
        protocolado: number;
        concluido: number;
        rejeitado: number;
    };
    recentCases?: Array<{
        id: number;
        case_number: string;
        client_name: string;
        status: string;
        created_at: string;
    }>;
    companiesStats?: {
        total: number;
        active: number;
    };
    usersStats?: {
        total: number;
        active: number;
    };
    petitionTemplatesStats?: {
        total: number;
        active: number;
    };
    workflowTemplatesStats?: {
        total: number;
        active: number;
    };
    financial?: {
        monthly_revenue: number;
        recent_payments: number;
        active_subscriptions: number;
    };
    recentCompanies?: Array<any>;
    recent_activity?: {
        new_companies: number;
        new_users: number;
        recent_payments: number;
    };
}

export default function Dashboard(allProps: DashboardProps) {
    // Extrair props
    const { isSuperAdmin = false, stats, recentCases, ...restProps } = allProps;

    const defaultStats = {
        total_cases: 0,
        pendente: 0,
        em_coleta: 0,
        aguarda_peticao: 0,
        protocolado: 0,
        concluido: 0,
        rejeitado: 0,
    };

    const currentStats = stats || defaultStats;

    const formatCurrency = (value: number) => {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL',
        }).format(value);
    };

    const getStatusBadgeVariant = (status: string) => {
        switch (status) {
            case 'concluido':
                return 'default';
            case 'pendente':
                return 'secondary';
            case 'em_coleta':
                return 'outline';
            default:
                return 'secondary';
        }
    };

    // Dashboard do Super Admin
    if (isSuperAdmin) {
        const adminStatCards = [
            {
                title: 'Empresas',
                value: restProps.companiesStats?.total || 0,
                icon: Building2,
                color: 'bg-blue-500',
                href: '/companies',
                subtitle: `${restProps.companiesStats?.active || 0} ativas`,
            },
            {
                title: 'Usuários',
                value: restProps.usersStats?.total || 0,
                icon: Users,
                color: 'bg-green-500',
                href: '/users',
                subtitle: `${restProps.usersStats?.active || 0} ativos`,
            },
            {
                title: 'Templates',
                value: restProps.petitionTemplatesStats?.total || 0,
                icon: FileText,
                color: 'bg-purple-500',
                href: '/templates',
                subtitle: `${restProps.petitionTemplatesStats?.active || 0} ativos`,
            },
            {
                title: 'Workflows',
                value: restProps.workflowTemplatesStats?.total || 0,
                icon: Workflow,
                color: 'bg-orange-500',
                href: '/workflows',
                subtitle: `${restProps.workflowTemplatesStats?.active || 0} ativos`,
            },
        ];

        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Dashboard Administrativo - Sistema Jurídico" />
                <div className="flex h-full min-h-screen flex-1 flex-col gap-6 bg-background p-6">
                    {/* Header */}
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-3xl font-bold">Dashboard Administrativo</h1>
                            <p className="text-muted-foreground">Visão geral do sistema PrevidIA</p>
                        </div>
                    </div>

                    {/* Stats Cards */}
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        {adminStatCards.map((stat) => (
                            <Card key={stat.title} className="border-border bg-card">
                                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle className="text-sm font-medium text-card-foreground">{stat.title}</CardTitle>
                                    <stat.icon className={`h-4 w-4 ${stat.color.replace('bg-', 'text-')}`} />
                                </CardHeader>
                                <CardContent>
                                    <div className="text-2xl font-bold text-card-foreground">{stat.value}</div>
                                    <p className="text-xs text-muted-foreground">{stat.subtitle}</p>
                                </CardContent>
                            </Card>
                        ))}
                    </div>

                    {/* Financial Cards */}
                    {restProps.financial && (
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            <Card className="border-border bg-card">
                                <CardHeader>
                                    <CardTitle className="text-sm text-card-foreground">Receita Mensal</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-2xl font-bold text-green-600">
                                        {formatCurrency(restProps.financial.monthly_revenue || 0)}
                                    </div>
                                </CardContent>
                            </Card>
                            <Card className="border-border bg-card">
                                <CardHeader>
                                    <CardTitle className="text-sm text-card-foreground">Pagamentos (30 dias)</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-2xl font-bold text-card-foreground">{restProps.financial.recent_payments || 0}</div>
                                </CardContent>
                            </Card>
                            <Card className="border-border bg-card">
                                <CardHeader>
                                    <CardTitle className="text-sm text-card-foreground">Assinaturas Ativas</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-2xl font-bold text-card-foreground">{restProps.financial.active_subscriptions || 0}</div>
                                </CardContent>
                            </Card>
                        </div>
                    )}

                    {/* Recent Companies */}
                    {restProps.recentCompanies && restProps.recentCompanies.length > 0 && (
                        <Card className="border-border bg-card">
                            <CardHeader>
                                <CardTitle className="text-card-foreground">Empresas Recentes</CardTitle>
                                <CardDescription className="text-muted-foreground">Últimas empresas cadastradas no sistema</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-3">
                                    {restProps.recentCompanies.slice(0, 5).map((company: any) => (
                                        <div
                                            key={company.id}
                                            className="flex items-center justify-between rounded-lg border border-border bg-card p-3"
                                        >
                                            <div className="flex-1">
                                                <p className="font-medium text-card-foreground">{company.name}</p>
                                                <p className="text-sm text-muted-foreground">
                                                    {company.users_count} usuários • {company.cases_count} casos
                                                </p>
                                            </div>
                                            <Badge variant={company.is_active ? 'default' : 'secondary'}>
                                                {company.is_active ? 'Ativa' : 'Inativa'}
                                            </Badge>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </AppLayout>
        );
    }

    // Dashboard do Usuário Normal
    const statCards = [
        {
            title: 'Total de Casos',
            value: stats?.total_cases || currentStats.total_cases || 0,
            icon: Briefcase,
            color: 'bg-blue-500',
            href: '/cases',
        },
        {
            title: 'Pendentes',
            value: stats?.pendente || currentStats.pendente || 0,
            icon: AlertTriangle,
            color: 'bg-yellow-500',
            href: '/cases?status=pendente',
        },
        {
            title: 'Em Coleta',
            value: stats?.em_coleta || currentStats.em_coleta || 0,
            icon: BarChart3,
            color: 'bg-blue-500',
            href: '/cases?status=em_coleta',
        },
        {
            title: 'Concluídos',
            value: stats?.concluido || currentStats.concluido || 0,
            icon: CheckCircle,
            color: 'bg-green-500',
            href: '/cases?status=concluido',
        },
    ];

    const quickActions = [
        {
            title: 'Novo Caso',
            description: 'Criar um novo caso jurídico',
            icon: Plus,
            href: '/cases/create',
            color: 'bg-blue-500 hover:bg-blue-600',
        },
        {
            title: 'Coletas',
            description: 'Gerenciar coletas de documentos',
            icon: Upload,
            href: '/coletas',
            color: 'bg-green-500 hover:bg-green-600',
        },
        {
            title: 'Gerar Petição',
            description: 'Criar petição com IA',
            icon: BookOpen,
            href: '/petitions/create',
            color: 'bg-purple-500 hover:bg-purple-600',
        },
        {
            title: 'AI Chat',
            description: 'Conversar com assistente jurídico',
            icon: MessageSquare,
            href: '/chat',
            color: 'bg-orange-500 hover:bg-orange-600',
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard - Sistema Jurídico" />
            <div className="flex h-full min-h-screen flex-1 flex-col gap-6 bg-background p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold">Dashboard</h1>
                        <p className="text-muted-foreground">Visão geral dos seus casos e atividades</p>
                    </div>
                    <Link href="/cases/create">
                        <Button>
                            <Plus className="mr-2 h-4 w-4" />
                            Novo Caso
                        </Button>
                    </Link>
                </div>

                {/* Stats Cards */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    {statCards.map((stat) => (
                        <Card key={stat.title} className="border-border bg-card">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium text-card-foreground">{stat.title}</CardTitle>
                                <stat.icon className={`h-4 w-4 ${stat.color.replace('bg-', 'text-')}`} />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold text-card-foreground">{stat.value}</div>
                                <Link href={stat.href} className="text-xs text-muted-foreground hover:underline">
                                    Ver detalhes →
                                </Link>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Quick Actions */}
                <div>
                    <h2 className="mb-4 text-xl font-semibold text-foreground">Ações Rápidas</h2>
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        {quickActions.map((action) => (
                            <Card key={action.title} className="border-border bg-card transition-shadow hover:shadow-md">
                                <CardHeader>
                                    <div className="flex items-center space-x-2">
                                        <div className={`rounded-lg p-2 ${action.color}`}>
                                            <action.icon className="h-4 w-4 text-white" />
                                        </div>
                                        <CardTitle className="text-sm text-card-foreground">{action.title}</CardTitle>
                                    </div>
                                    <CardDescription className="text-xs text-muted-foreground">{action.description}</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <Link href={action.href}>
                                        <Button variant="outline" size="sm" className="w-full">
                                            Acessar
                                        </Button>
                                    </Link>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                </div>

                {/* Recent Cases */}
                <div className="grid gap-6 md:grid-cols-1">
                    <Card className="border-border bg-card">
                        <CardHeader>
                            <CardTitle className="text-card-foreground">Casos Recentes</CardTitle>
                            <CardDescription className="text-muted-foreground">Últimos casos criados no sistema</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {recentCases && recentCases.length > 0 ? (
                                <div className="space-y-3">
                                    {recentCases.slice(0, 5).map((case_) => (
                                        <div key={case_.id} className="flex items-center justify-between rounded-lg border border-border bg-card p-3">
                                            <div className="flex-1">
                                                <p className="font-medium text-card-foreground">{case_.client_name}</p>
                                                <p className="text-sm text-muted-foreground">
                                                    {case_.case_number} • Criado em {new Date(case_.created_at).toLocaleDateString('pt-BR')}
                                                </p>
                                            </div>
                                            <Badge variant={getStatusBadgeVariant(case_.status)}>{case_.status}</Badge>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="py-8 text-center">
                                    <Briefcase className="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
                                    <p className="text-muted-foreground">Nenhum caso encontrado</p>
                                    <Link href="/cases/create">
                                        <Button variant="outline" size="sm" className="mt-2">
                                            Criar primeiro caso
                                        </Button>
                                    </Link>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
