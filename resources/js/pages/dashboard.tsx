import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Briefcase, FileText, Users, BarChart3, BookOpen, Plus, TrendingUp, AlertTriangle, CheckCircle } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

interface DashboardProps {
    stats?: {
        total_cases: number;
        pending_cases: number;
        analysis_cases: number;
        completed_cases: number;
        requirement_cases: number;
        rejected_cases: number;
    };
    recentCases?: Array<{
        id: number;
        case_number: string;
        client_name: string;
        status: string;
        created_at: string;
    }>;
}

export default function Dashboard({ stats, recentCases }: DashboardProps) {
    const defaultStats = {
        total_cases: 0,
        pending_cases: 0,
        analysis_cases: 0,
        completed_cases: 0,
        requirement_cases: 0,
        rejected_cases: 0,
    };

    const currentStats = stats || defaultStats;

    const statCards = [
        {
            title: 'Total de Casos',
            value: currentStats.total_cases,
            icon: Briefcase,
            color: 'bg-blue-500',
            href: '/cases',
        },
        {
            title: 'Em Análise',
            value: currentStats.analysis_cases,
            icon: BarChart3,
            color: 'bg-yellow-500',
            href: '/cases?status=analysis',
        },
        {
            title: 'Concluídos',
            value: currentStats.completed_cases,
            icon: CheckCircle,
            color: 'bg-green-500',
            href: '/cases?status=completed',
        },
        {
            title: 'Pendentes',
            value: currentStats.pending_cases,
            icon: AlertTriangle,
            color: 'bg-orange-500',
            href: '/cases?status=pending',
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
            title: 'Upload de Documento',
            description: 'Fazer upload de documentos',
            icon: FileText,
            href: '/documents/create',
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
            title: 'Nova Tarefa',
            description: 'Criar tarefa do workflow',
            icon: Users,
            href: '/tasks/create',
            color: 'bg-orange-500 hover:bg-orange-600',
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard - Sistema Jurídico" />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold">Dashboard</h1>
                        <p className="text-muted-foreground">Visão geral do sistema jurídico</p>
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
                        <Card key={stat.title}>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">{stat.title}</CardTitle>
                                <stat.icon className={`h-4 w-4 ${stat.color.replace('bg-', 'text-')}`} />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{stat.value}</div>
                                <Link href={stat.href} className="text-xs text-muted-foreground hover:underline">
                                    Ver detalhes →
                                </Link>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Quick Actions */}
                <div>
                    <h2 className="text-xl font-semibold mb-4">Ações Rápidas</h2>
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        {quickActions.map((action) => (
                            <Card key={action.title} className="hover:shadow-md transition-shadow">
                                <CardHeader>
                                    <div className="flex items-center space-x-2">
                                        <div className={`p-2 rounded-lg ${action.color}`}>
                                            <action.icon className="h-4 w-4 text-white" />
                                        </div>
                                        <CardTitle className="text-sm">{action.title}</CardTitle>
                                    </div>
                                    <CardDescription className="text-xs">{action.description}</CardDescription>
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
                <div className="grid gap-6 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Casos Recentes</CardTitle>
                            <CardDescription>Últimos casos criados no sistema</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {recentCases && recentCases.length > 0 ? (
                                <div className="space-y-3">
                                    {recentCases.slice(0, 5).map((case_) => (
                                        <div key={case_.id} className="flex items-center justify-between p-3 border rounded-lg">
                                            <div>
                                                <p className="font-medium">{case_.case_number}</p>
                                                <p className="text-sm text-muted-foreground">{case_.client_name}</p>
                                            </div>
                                            <Badge variant={case_.status === 'completed' ? 'default' : 'secondary'}>
                                                {case_.status}
                                            </Badge>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="text-center py-8">
                                    <Briefcase className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
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

                    <Card>
                        <CardHeader>
                            <CardTitle>Atividades Recentes</CardTitle>
                            <CardDescription>Últimas atividades do sistema</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                <div className="flex items-center space-x-3 p-3 border rounded-lg">
                                    <div className="p-2 bg-blue-100 rounded-lg">
                                        <FileText className="h-4 w-4 text-blue-600" />
                                    </div>
                                    <div>
                                        <p className="text-sm font-medium">Documento processado</p>
                                        <p className="text-xs text-muted-foreground">CNIS extraído automaticamente</p>
                                    </div>
                                </div>
                                <div className="flex items-center space-x-3 p-3 border rounded-lg">
                                    <div className="p-2 bg-green-100 rounded-lg">
                                        <BookOpen className="h-4 w-4 text-green-600" />
                                    </div>
                                    <div>
                                        <p className="text-sm font-medium">Petição gerada</p>
                                        <p className="text-xs text-muted-foreground">Recurso criado com IA</p>
                                    </div>
                                </div>
                                <div className="flex items-center space-x-3 p-3 border rounded-lg">
                                    <div className="p-2 bg-orange-100 rounded-lg">
                                        <Users className="h-4 w-4 text-orange-600" />
                                    </div>
                                    <div>
                                        <p className="text-sm font-medium">Tarefa concluída</p>
                                        <p className="text-xs text-muted-foreground">Documentação enviada</p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
