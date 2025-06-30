import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { Company, LegalCase, PetitionTemplate, User } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Building2, Clock, Edit, FileText, Mail, MapPin, Phone, ToggleLeft, ToggleRight, Users } from 'lucide-react';

interface Props {
    company: Company & {
        users: User[];
        cases: LegalCase[];
        petitionTemplates: PetitionTemplate[];
    };
    stats: {
        users_count: number;
        cases_count: number;
        templates_count: number;
        active_cases: number;
        completed_cases: number;
    };
}

export default function Show({ company, stats }: Props) {
    const handleToggleStatus = () => {
        router.post(route('companies.toggle-status', company.id));
    };

    const getPlanBadge = (plan: string) => {
        const variants = {
            basic: 'secondary',
            premium: 'default',
            enterprise: 'destructive',
        } as const;

        const labels = {
            basic: 'Básico',
            premium: 'Premium',
            enterprise: 'Enterprise',
        };

        return <Badge variant={variants[plan as keyof typeof variants] || 'secondary'}>{labels[plan as keyof typeof labels] || plan}</Badge>;
    };

    const getStatusBadge = (isActive: boolean) => {
        return <Badge variant={isActive ? 'default' : 'secondary'}>{isActive ? 'Ativa' : 'Inativa'}</Badge>;
    };

    const formatDate = (date: string | null) => {
        if (!date) return 'N/A';
        return new Date(date).toLocaleDateString('pt-BR');
    };

    return (
        <AppLayout>
            <Head title={company.name} />

            <div className="container mx-auto py-6">
                <div className="mb-6">
                    <div className="mb-4 flex items-center gap-4">
                        <Link
                            href={route('companies.index')}
                            className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
                        >
                            <ArrowLeft className="h-4 w-4" />
                            Voltar para Empresas
                        </Link>
                    </div>

                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <div className="rounded-lg bg-primary/10 p-2">
                                <Building2 className="h-6 w-6 text-primary" />
                            </div>
                            <div>
                                <div className="mb-1 flex items-center gap-3">
                                    <h1 className="text-2xl font-bold">{company.name}</h1>
                                    {getStatusBadge(company.is_active)}
                                    {getPlanBadge(company.plan)}
                                </div>
                                <p className="text-muted-foreground">Empresa cadastrada em {formatDate(company.created_at)}</p>
                            </div>
                        </div>

                        <div className="flex items-center gap-2">
                            <Button variant="outline" size="sm" onClick={handleToggleStatus} className="gap-2">
                                {company.is_active ? (
                                    <>
                                        <ToggleRight className="h-4 w-4" />
                                        Desativar
                                    </>
                                ) : (
                                    <>
                                        <ToggleLeft className="h-4 w-4" />
                                        Ativar
                                    </>
                                )}
                            </Button>

                            <Link href={route('companies.edit', company.id)}>
                                <Button size="sm" className="gap-2">
                                    <Edit className="h-4 w-4" />
                                    Editar
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>

                <div className="mb-6 grid grid-cols-1 gap-6 md:grid-cols-4">
                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center gap-3">
                                <div className="rounded-lg bg-blue-100 p-2">
                                    <Users className="h-5 w-5 text-blue-600" />
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Usuários</p>
                                    <p className="text-2xl font-bold">
                                        {stats.users_count}/{company.max_users}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center gap-3">
                                <div className="rounded-lg bg-green-100 p-2">
                                    <FileText className="h-5 w-5 text-green-600" />
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Casos</p>
                                    <p className="text-2xl font-bold">
                                        {stats.cases_count}/{company.max_cases}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center gap-3">
                                <div className="rounded-lg bg-orange-100 p-2">
                                    <FileText className="h-5 w-5 text-orange-600" />
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Casos Ativos</p>
                                    <p className="text-2xl font-bold">{stats.active_cases}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center gap-3">
                                <div className="rounded-lg bg-purple-100 p-2">
                                    <FileText className="h-5 w-5 text-purple-600" />
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Templates</p>
                                    <p className="text-2xl font-bold">{stats.templates_count}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Building2 className="h-5 w-5" />
                                Informações da Empresa
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {company.email && (
                                <div className="flex items-center gap-3">
                                    <Mail className="h-4 w-4 text-muted-foreground" />
                                    <span>{company.email}</span>
                                </div>
                            )}

                            {company.phone && (
                                <div className="flex items-center gap-3">
                                    <Phone className="h-4 w-4 text-muted-foreground" />
                                    <span>{company.phone}</span>
                                </div>
                            )}

                            {company.cnpj && (
                                <div className="flex items-start gap-3">
                                    <FileText className="mt-0.5 h-4 w-4 text-muted-foreground" />
                                    <div>
                                        <p className="text-sm text-muted-foreground">CNPJ</p>
                                        <p>{company.cnpj}</p>
                                    </div>
                                </div>
                            )}

                            {(company.address || company.city || company.state) && (
                                <div className="flex items-start gap-3">
                                    <MapPin className="mt-0.5 h-4 w-4 text-muted-foreground" />
                                    <div>
                                        <p className="text-sm text-muted-foreground">Endereço</p>
                                        <div className="space-y-1">
                                            {company.address && <p>{company.address}</p>}
                                            {(company.city || company.state) && (
                                                <p>
                                                    {company.city}
                                                    {company.city && company.state && ', '}
                                                    {company.state}
                                                    {company.zip_code && ` - ${company.zip_code}`}
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            )}

                            {company.trial_ends_at && (
                                <div className="flex items-center gap-3">
                                    <Clock className="h-4 w-4 text-muted-foreground" />
                                    <div>
                                        <p className="text-sm text-muted-foreground">Teste termina em</p>
                                        <p>{formatDate(company.trial_ends_at)}</p>
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Users className="h-5 w-5" />
                                Usuários Recentes
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {company.users.length > 0 ? (
                                <div className="space-y-3">
                                    {company.users.slice(0, 5).map((user) => (
                                        <div key={user.id} className="flex items-center justify-between">
                                            <div>
                                                <p className="font-medium">{user.name}</p>
                                                <p className="text-sm text-muted-foreground">{user.email}</p>
                                            </div>
                                            <Badge variant="outline">{user.role === 'admin' ? 'Admin' : 'Usuário'}</Badge>
                                        </div>
                                    ))}
                                    {company.users.length > 5 && (
                                        <p className="pt-2 text-center text-sm text-muted-foreground">
                                            E mais {company.users.length - 5} usuários...
                                        </p>
                                    )}
                                </div>
                            ) : (
                                <p className="py-4 text-center text-muted-foreground">Nenhum usuário cadastrado</p>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {company.cases.length > 0 && (
                    <Card className="mt-6">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <FileText className="h-5 w-5" />
                                Casos Recentes
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Cliente</TableHead>
                                        <TableHead>Tipo de Benefício</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Criado em</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {company.cases.slice(0, 10).map((case_) => (
                                        <TableRow key={case_.id}>
                                            <TableCell className="font-medium">{case_.client_name}</TableCell>
                                            <TableCell>{case_.benefit_type || 'N/A'}</TableCell>
                                            <TableCell>
                                                <Badge variant="outline">{case_.status}</Badge>
                                            </TableCell>
                                            <TableCell>{formatDate(case_.created_at)}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>

                            {company.cases.length > 10 && (
                                <p className="pt-4 text-center text-sm text-muted-foreground">E mais {company.cases.length - 10} casos...</p>
                            )}
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
