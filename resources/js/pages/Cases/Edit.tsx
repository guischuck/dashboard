import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Save, Trash2 } from 'lucide-react';

interface Case {
    id: number;
    case_number: string;
    client_name: string;
    client_cpf: string;
    benefit_type: string | null;
    status: string;
    description: string | null;
}

interface EditProps {
    case: Case;
    users: Array<{ id: number; name: string }>;
    benefitTypes: Record<string, string>;
}

export default function Edit({ case: case_, users, benefitTypes }: EditProps) {
    console.log('Case data received:', case_);
    console.log('Case status:', case_?.status);

    const { data, setData, put, processing, errors } = useForm({
        client_name: case_?.client_name || '',
        client_cpf: case_?.client_cpf || '',
        benefit_type: case_?.benefit_type ?? '',
        status: case_?.status || 'pendente',
        description: case_?.description ?? '',
    });

    console.log('Form data initialized:', data);
    console.log('Current status in form:', data.status);

    if (!case_ || !users || !benefitTypes) {
        return <div className="p-8 text-center text-red-600">Erro ao carregar dados do caso. Verifique se o caso existe e tente novamente.</div>;
    }

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Dashboard',
            href: '/dashboard',
        },
        {
            title: 'Casos',
            href: '/cases',
        },
        {
            title: case_.case_number,
            href: `/cases/${case_.id}`,
        },
        {
            title: 'Editar',
            href: `/cases/${case_.id}/edit`,
        },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        console.log('Enviando dados:', data);
        console.log('Status selecionado:', data.status);
        put(`/cases/${case_.id}`);
    };

    const handleDelete = () => {
        if (confirm(`Tem certeza que deseja remover o caso "${case_.client_name}"? Esta ação não pode ser desfeita.`)) {
            router.delete(`/cases/${case_.id}`);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar Caso ${case_.case_number} - Sistema Jurídico`} />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Link href={`/cases/${case_.id}`}>
                            <Button variant="outline" size="sm">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Voltar ao Caso
                            </Button>
                        </Link>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Editar Caso</CardTitle>
                            <CardDescription>Atualize as informações do caso {case_.case_number}</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            {/* Basic Information */}
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <Label htmlFor="case_number">Número do Caso</Label>
                                    <Input id="case_number" value={case_.case_number} disabled className="bg-gray-50" />
                                    <p className="mt-1 text-sm text-gray-500">Número do caso não pode ser alterado</p>
                                </div>
                                <div>
                                    <Label htmlFor="status">Situação</Label>
                                    <Select
                                        value={data.status}
                                        onValueChange={(value) => {
                                            console.log('Status changed to:', value);
                                            setData('status', value);
                                        }}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Selecione a situação" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="pendente">Pendente</SelectItem>
                                            <SelectItem value="em_coleta">Em Coleta</SelectItem>
                                            <SelectItem value="aguarda_peticao">Aguarda Petição</SelectItem>
                                            <SelectItem value="protocolado">Protocolado</SelectItem>
                                            <SelectItem value="concluido">Concluído</SelectItem>
                                            <SelectItem value="rejeitado">Rejeitado</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {errors.status && <p className="mt-1 text-sm text-red-600">{errors.status}</p>}
                                </div>
                                <div>
                                    <Label htmlFor="client_name">Nome do Cliente</Label>
                                    <Input
                                        id="client_name"
                                        value={data.client_name}
                                        onChange={(e) => setData('client_name', e.target.value)}
                                        placeholder="Nome completo do cliente"
                                    />
                                    {errors.client_name && <p className="mt-1 text-sm text-red-600">{errors.client_name}</p>}
                                </div>
                                <div>
                                    <Label htmlFor="client_cpf">CPF</Label>
                                    <Input
                                        id="client_cpf"
                                        value={data.client_cpf}
                                        onChange={(e) => setData('client_cpf', e.target.value)}
                                        placeholder="000.000.000-00"
                                    />
                                    {errors.client_cpf && <p className="mt-1 text-sm text-red-600">{errors.client_cpf}</p>}
                                </div>
                                <div>
                                    <Label htmlFor="benefit_type">Tipo de Benefício</Label>
                                    <Select value={data.benefit_type} onValueChange={(value) => setData('benefit_type', value)}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Selecione o tipo de benefício" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {Object.entries(benefitTypes).map(([key, value]) => (
                                                <SelectItem key={key} value={key}>
                                                    {value}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.benefit_type && <p className="mt-1 text-sm text-red-600">{errors.benefit_type}</p>}
                                </div>
                            </div>

                            {/* Description */}
                            <div>
                                <Label htmlFor="description">Descrição</Label>
                                <Textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    placeholder="Descrição detalhada do caso..."
                                    rows={4}
                                />
                                {errors.description && <p className="mt-1 text-sm text-red-600">{errors.description}</p>}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Actions */}
                    <div className="flex items-center justify-between">
                        <Button type="button" variant="destructive" onClick={handleDelete} className="flex items-center space-x-2">
                            <Trash2 className="h-4 w-4" />
                            Remover Caso
                        </Button>

                        <div className="flex space-x-2">
                            <Link href={`/cases/${case_.id}`}>
                                <Button variant="outline" type="button">
                                    Cancelar
                                </Button>
                            </Link>
                            <Button type="submit" disabled={processing}>
                                <Save className="mr-2 h-4 w-4" />
                                {processing ? 'Salvando...' : 'Salvar Alterações'}
                            </Button>
                        </div>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
