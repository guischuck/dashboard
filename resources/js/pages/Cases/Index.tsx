import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';

import { Briefcase, Plus, Search } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Casos',
        href: '/cases',
    },
];

interface Case {
    id: number;
    case_number: string;
    client_name: string;
    client_cpf: string;
    status: string;
    created_at: string;
    assigned_to?: {
        id: number;
        name: string;
    };
}

interface CasesIndexProps {
    cases: {
        data: Case[];
        total: number;
    };
    users: Array<{ id: number; name: string }>;
    statuses: Record<string, string>;
    filters: {
        search?: string;
        status?: string;
        assigned_to?: string;
    };
}

export default function CasesIndex({ cases, users, statuses, filters }: CasesIndexProps) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');

    // Função helper para obter o texto do status
    const getStatusText = (status: string) => {
        const statusMap: Record<string, string> = {
            pendente: 'Pendente',
            em_coleta: 'Em Coleta',
            aguarda_peticao: 'Aguarda Petição',
            protocolado: 'Protocolado',
            concluido: 'Concluído',
            rejeitado: 'Rejeitado',
        };

        return statusMap[status] || statuses[status] || status;
    };

    // Função helper para obter a cor do status
    const getStatusColor = (status: string) => {
        switch (status) {
            case 'pendente':
                return 'bg-gradient-to-r from-yellow-400 to-yellow-500 text-white shadow-lg';
            case 'em_coleta':
                return 'bg-gradient-to-r from-blue-400 to-blue-500 text-white shadow-lg';
            case 'aguarda_peticao':
                return 'bg-gradient-to-r from-orange-400 to-orange-500 text-white shadow-lg';
            case 'protocolado':
                return 'bg-gradient-to-r from-purple-400 to-purple-500 text-white shadow-lg';
            case 'concluido':
                return 'bg-gradient-to-r from-green-400 to-green-500 text-white shadow-lg';
            case 'rejeitado':
                return 'bg-gradient-to-r from-red-400 to-red-500 text-white shadow-lg';
            default:
                return 'bg-gradient-to-r from-gray-400 to-gray-500 text-white shadow-lg';
        }
    };

    const handleSearch = () => {
        router.get(
            '/cases',
            {
                search: searchTerm,
            },
            {
                preserveState: true,
            },
        );
    };

    const handleKeyPress = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter') {
            handleSearch();
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Casos - Sistema Jurídico" />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold">Casos</h1>
                        <p className="text-muted-foreground">Gerencie os casos jurídicos dos clientes</p>
                    </div>
                    <Link href="/cases/create">
                        <Button>
                            <Plus className="mr-2 h-4 w-4" />
                            Novo Caso
                        </Button>
                    </Link>
                </div>

                {/* Search Bar */}
                <Card>
                    <CardContent className="pt-6">
                        <div className="flex items-center space-x-2">
                            <div className="relative flex-1">
                                <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transform text-muted-foreground" />
                                <Input
                                    placeholder="Buscar por nome do cliente..."
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                    onKeyPress={handleKeyPress}
                                    className="pl-10"
                                />
                            </div>
                            <Button onClick={handleSearch} variant="outline">
                                Buscar
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Cases List */}
                <Card>
                    <CardHeader>
                        <CardTitle>Casos ({cases?.total || 0})</CardTitle>
                        <CardDescription>Lista de todos os casos jurídicos cadastrados no sistema</CardDescription>
                    </CardHeader>
                    <CardContent>
                        {cases?.data && cases.data.length > 0 ? (
                            <div className="space-y-4">
                                {cases.data.map((case_) => (
                                    <div
                                        key={case_.id}
                                        className="flex items-center justify-between rounded-lg border p-4 transition-colors hover:bg-accent hover:text-accent-foreground"
                                    >
                                        <div className="flex-1">
                                            <div className="flex items-center space-x-3">
                                                <div>
                                                    <h3 className="text-lg font-medium">{case_.client_name}</h3>
                                                    <p className="text-sm text-muted-foreground">CPF: {case_.client_cpf}</p>
                                                </div>
                                                <div className="flex items-center">
                                                    <span
                                                        className={`rounded-full px-3 py-1.5 text-xs font-semibold tracking-wide uppercase ${getStatusColor(case_.status)} transition-all duration-200 hover:scale-105`}
                                                    >
                                                        {getStatusText(case_.status)}
                                                    </span>
                                                </div>
                                            </div>
                                            <div className="mt-2 text-sm text-muted-foreground">
                                                <span>Criado em: {new Date(case_.created_at).toLocaleDateString('pt-BR')}</span>
                                            </div>
                                        </div>
                                        <div className="flex items-center space-x-2">
                                            <Link href={`/cases/${case_.id}/vinculos`}>
                                                <Button variant="outline" size="sm">
                                                    Vínculos
                                                </Button>
                                            </Link>
                                            <Link href={`/cases/${case_.id}`}>
                                                <Button variant="outline" size="sm">
                                                    Ver
                                                </Button>
                                            </Link>
                                            <Link href={`/cases/${case_.id}/edit`}>
                                                <Button variant="outline" size="sm">
                                                    Editar
                                                </Button>
                                            </Link>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="py-12 text-center">
                                <Briefcase className="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
                                <h3 className="mb-2 text-lg font-medium">Nenhum caso encontrado</h3>
                                <p className="mb-4 text-muted-foreground">
                                    {searchTerm ? 'Nenhum caso encontrado com os critérios de busca' : 'Comece criando seu primeiro caso jurídico'}
                                </p>
                                {!searchTerm && (
                                    <Link href="/cases/create">
                                        <Button>
                                            <Plus className="mr-2 h-4 w-4" />
                                            Criar Primeiro Caso
                                        </Button>
                                    </Link>
                                )}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
