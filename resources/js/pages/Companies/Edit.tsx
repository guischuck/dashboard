import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { Company } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Building2, Trash2 } from 'lucide-react';

interface Props {
    company: Company;
}

interface CompanyFormData {
    name: string;
    email: string;
    cnpj: string;
    phone: string;
    address: string;
    city: string;
    state: string;
    zip_code: string;
    plan: 'basic' | 'premium' | 'enterprise';
    max_users: number;
    max_cases: number;
    is_active: boolean;
    [key: string]: string | number | boolean;
}

export default function Edit({ company }: Props) {
    const { data, setData, put, processing, errors } = useForm<CompanyFormData>({
        name: company.name || '',
        email: company.email || '',
        cnpj: company.cnpj || '',
        phone: company.phone || '',
        address: company.address || '',
        city: company.city || '',
        state: company.state || '',
        zip_code: company.zip_code || '',
        plan: company.plan || 'basic',
        max_users: company.max_users || 5,
        max_cases: company.max_cases || 100,
        is_active: company.is_active || false,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('companies.update', company.id));
    };

    const handleDelete = () => {
        if (confirm('Tem certeza que deseja excluir esta empresa? Esta ação não pode ser desfeita.')) {
            router.delete(route('companies.destroy', company.id));
        }
    };

    const planLimits = {
        basic: { users: 5, cases: 100 },
        premium: { users: 10, cases: 500 },
        enterprise: { users: 50, cases: 2000 },
    };

    const handlePlanChange = (plan: 'basic' | 'premium' | 'enterprise') => {
        setData({
            ...data,
            plan,
            max_users: planLimits[plan].users,
            max_cases: planLimits[plan].cases,
        });
    };

    return (
        <AppLayout>
            <Head title={`Editar ${company.name}`} />

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
                                <h1 className="text-2xl font-bold">Editar Empresa</h1>
                                <p className="text-muted-foreground">{company.name}</p>
                            </div>
                        </div>

                        <Button variant="destructive" size="sm" onClick={handleDelete} className="gap-2">
                            <Trash2 className="h-4 w-4" />
                            Excluir
                        </Button>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Informações Básicas</CardTitle>
                            <CardDescription>Dados principais da empresa</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="name">Nome da Empresa *</Label>
                                    <Input
                                        id="name"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        placeholder="Ex: Escritório de Advocacia Silva"
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="email">E-mail</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                        placeholder="contato@empresa.com"
                                    />
                                    <InputError message={errors.email} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="cnpj">CNPJ</Label>
                                    <Input
                                        id="cnpj"
                                        value={data.cnpj}
                                        onChange={(e) => setData('cnpj', e.target.value)}
                                        placeholder="00.000.000/0000-00"
                                    />
                                    <InputError message={errors.cnpj} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="phone">Telefone</Label>
                                    <Input
                                        id="phone"
                                        value={data.phone}
                                        onChange={(e) => setData('phone', e.target.value)}
                                        placeholder="(11) 99999-9999"
                                    />
                                    <InputError message={errors.phone} />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="address">Endereço</Label>
                                <Textarea
                                    id="address"
                                    value={data.address}
                                    onChange={(e) => setData('address', e.target.value)}
                                    placeholder="Rua, número, complemento"
                                    rows={3}
                                />
                                <InputError message={errors.address} />
                            </div>

                            <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                                <div className="space-y-2">
                                    <Label htmlFor="city">Cidade</Label>
                                    <Input id="city" value={data.city} onChange={(e) => setData('city', e.target.value)} placeholder="São Paulo" />
                                    <InputError message={errors.city} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="state">Estado</Label>
                                    <Input
                                        id="state"
                                        value={data.state}
                                        onChange={(e) => setData('state', e.target.value)}
                                        placeholder="SP"
                                        maxLength={2}
                                    />
                                    <InputError message={errors.state} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="zip_code">CEP</Label>
                                    <Input
                                        id="zip_code"
                                        value={data.zip_code}
                                        onChange={(e) => setData('zip_code', e.target.value)}
                                        placeholder="00000-000"
                                    />
                                    <InputError message={errors.zip_code} />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Plano e Limites</CardTitle>
                            <CardDescription>Configure o plano e limites de uso da empresa</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="plan">Plano *</Label>
                                    <Select value={data.plan} onValueChange={handlePlanChange}>
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="basic">Básico (5 usuários, 100 casos)</SelectItem>
                                            <SelectItem value="premium">Premium (10 usuários, 500 casos)</SelectItem>
                                            <SelectItem value="enterprise">Enterprise (50 usuários, 2000 casos)</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.plan} />
                                </div>

                                <div className="flex items-center space-x-2">
                                    <Switch id="is_active" checked={data.is_active} onCheckedChange={(checked) => setData('is_active', checked)} />
                                    <Label htmlFor="is_active">Empresa Ativa</Label>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="max_users">Máximo de Usuários *</Label>
                                    <Input
                                        id="max_users"
                                        type="number"
                                        min="1"
                                        max="1000"
                                        value={data.max_users}
                                        onChange={(e) => setData('max_users', parseInt(e.target.value) || 1)}
                                    />
                                    <InputError message={errors.max_users} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="max_cases">Máximo de Casos *</Label>
                                    <Input
                                        id="max_cases"
                                        type="number"
                                        min="1"
                                        max="10000"
                                        value={data.max_cases}
                                        onChange={(e) => setData('max_cases', parseInt(e.target.value) || 1)}
                                    />
                                    <InputError message={errors.max_cases} />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <div className="flex items-center gap-4">
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Salvando...' : 'Salvar Alterações'}
                        </Button>

                        <Link
                            href={route('companies.index')}
                            className="inline-flex h-10 items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium ring-offset-background transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50"
                        >
                            Cancelar
                        </Link>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
