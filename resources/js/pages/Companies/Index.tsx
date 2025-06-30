import { Head, Link, router } from '@inertiajs/react';
import { Building, CheckCircle, Edit, Eye, Plus, Trash2, Users, XCircle } from 'lucide-react';
import { useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';

interface Company {
    id: number;
    name: string;
    slug: string;
    email: string;
    plan: 'basic' | 'premium' | 'enterprise';
    is_active: boolean;
    users_count: number;
    cases_count: number;
    max_users: number;
    max_cases: number;
    trial_ends_at: string | null;
    created_at: string;
}

interface Stats {
    total: number;
    active: number;
    inactive: number;
    trial: number;
}

interface Props {
    companies: {
        data: Company[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    stats: Stats;
}

const planLabels = {
    basic: 'Básico',
    premium: 'Premium',
    enterprise: 'Enterprise',
};

const planColors = {
    basic: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
    premium: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
    enterprise: 'bg-gold-100 text-gold-800 dark:bg-gold-900 dark:text-gold-300',
};

export default function CompaniesIndex({ companies, stats }: Props) {
    const [search, setSearch] = useState('');

    const handleSearch = (value: string) => {
        setSearch(value);
        router.get(route('companies.index'), { search: value }, { preserveState: true });
    };

    const toggleStatus = (company: Company) => {
        router.post(
            route('companies.toggle-status', company.id),
            {},
            {
                preserveScroll: true,
            },
        );
    };

    const deleteCompany = (company: Company) => {
        if (confirm(`Tem certeza que deseja excluir a empresa "${company.name}"?`)) {
            router.delete(route('companies.destroy', company.id));
        }
    };

    return (
        <AppLayout>
            <Head title="Gestão de Empresas - PrevidIA" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Gestão de Empresas</h1>
                        <p className="text-gray-600 dark:text-gray-400">Gerencie todas as empresas cadastradas no sistema</p>
                    </div>
                    <Link href={route('companies.create')}>
                        <Button>
                            <Plus className="mr-2 h-4 w-4" />
                            Nova Empresa
                        </Button>
                    </Link>
                </div>

                {/* Stats Cards */}
                <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total de Empresas</CardTitle>
                            <Building className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.total}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Empresas Ativas</CardTitle>
                            <CheckCircle className="h-4 w-4 text-green-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-green-600">{stats.active}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Empresas Inativas</CardTitle>
                            <XCircle className="h-4 w-4 text-red-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-red-600">{stats.inactive}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Em Período de Teste</CardTitle>
                            <Users className="h-4 w-4 text-blue-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-blue-600">{stats.trial}</div>
                        </CardContent>
                    </Card>
                </div>

                {/* Filters */}
                <Card>
                    <CardHeader>
                        <CardTitle>Filtros</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex gap-4">
                            <div className="flex-1">
                                <Input placeholder="Buscar por nome da empresa..." value={search} onChange={(e) => handleSearch(e.target.value)} />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Companies Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Empresas Cadastradas</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Empresa</TableHead>
                                    <TableHead>Plano</TableHead>
                                    <TableHead>Usuários</TableHead>
                                    <TableHead>Casos</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Criado em</TableHead>
                                    <TableHead className="text-right">Ações</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {companies.data.map((company) => (
                                    <TableRow key={company.id}>
                                        <TableCell>
                                            <div>
                                                <div className="font-medium">{company.name}</div>
                                                <div className="text-sm text-gray-500">{company.email}</div>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <Badge className={planColors[company.plan]}>{planLabels[company.plan]}</Badge>
                                        </TableCell>
                                        <TableCell>
                                            <div className="text-sm">
                                                {company.users_count} / {company.max_users}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="text-sm">
                                                {company.cases_count} / {company.max_cases}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant={company.is_active ? 'default' : 'destructive'}>
                                                {company.is_active ? 'Ativa' : 'Inativa'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>{new Date(company.created_at).toLocaleDateString('pt-BR')}</TableCell>
                                        <TableCell className="text-right">
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button variant="ghost" size="sm">
                                                        Ações
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end">
                                                    <DropdownMenuItem asChild>
                                                        <Link href={route('companies.show', company.id)}>
                                                            <Eye className="mr-2 h-4 w-4" />
                                                            Visualizar
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem asChild>
                                                        <Link href={route('companies.edit', company.id)}>
                                                            <Edit className="mr-2 h-4 w-4" />
                                                            Editar
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem onClick={() => toggleStatus(company)}>
                                                        {company.is_active ? (
                                                            <>
                                                                <XCircle className="mr-2 h-4 w-4" />
                                                                Desativar
                                                            </>
                                                        ) : (
                                                            <>
                                                                <CheckCircle className="mr-2 h-4 w-4" />
                                                                Ativar
                                                            </>
                                                        )}
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem onClick={() => deleteCompany(company)} className="text-red-600">
                                                        <Trash2 className="mr-2 h-4 w-4" />
                                                        Excluir
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>

                        {companies.data.length === 0 && <div className="py-8 text-center text-gray-500">Nenhuma empresa encontrada.</div>}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
