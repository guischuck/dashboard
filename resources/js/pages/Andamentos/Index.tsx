import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { Clock, Filter, Plus, Search } from 'lucide-react';

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

export default function AndamentosIndex() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Andamentos - PrevidIA" />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="flex items-center text-3xl font-bold">
                            <Clock className="mr-3 h-8 w-8 text-primary" />
                            Andamentos
                        </h1>
                        <p className="text-muted-foreground">Acompanhe o progresso dos processos previdenciários</p>
                    </div>
                    <Button>
                        <Plus className="mr-2 h-4 w-4" />
                        Novo Andamento
                    </Button>
                </div>

                {/* Search and Filters */}
                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-center space-x-4">
                            <div className="relative flex-1">
                                <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transform text-muted-foreground" />
                                <Input placeholder="Buscar por cliente ou processo..." className="pl-10" />
                            </div>
                            <Button variant="outline">
                                <Filter className="mr-2 h-4 w-4" />
                                Filtros
                            </Button>
                            <Button>Buscar</Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Stats Cards */}
                <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total de Andamentos</CardTitle>
                            <Clock className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">0</div>
                            <p className="text-xs text-muted-foreground">+0% em relação ao mês anterior</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Em Andamento</CardTitle>
                            <div className="h-4 w-4 rounded-full bg-blue-500"></div>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">0</div>
                            <p className="text-xs text-muted-foreground">Processos ativos</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Concluídos</CardTitle>
                            <div className="h-4 w-4 rounded-full bg-green-500"></div>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">0</div>
                            <p className="text-xs text-muted-foreground">Processos finalizados</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Pendentes</CardTitle>
                            <div className="h-4 w-4 rounded-full bg-yellow-500"></div>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">0</div>
                            <p className="text-xs text-muted-foreground">Aguardando ação</p>
                        </CardContent>
                    </Card>
                </div>

                {/* Andamentos List */}
                <Card>
                    <CardHeader>
                        <CardTitle>Andamentos Recentes</CardTitle>
                        <CardDescription>Lista dos últimos andamentos registrados no sistema</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="py-12 text-center">
                            <Clock className="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
                            <h3 className="mb-2 text-lg font-medium">Nenhum andamento encontrado</h3>
                            <p className="mb-4 text-muted-foreground">Comece registrando o primeiro andamento de processo</p>
                            <Button>
                                <Plus className="mr-2 h-4 w-4" />
                                Criar Primeiro Andamento
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
