import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, BarChart3, Calendar, Copy, Edit, FileText, Settings, Trash2, User } from 'lucide-react';
import { useState } from 'react';

interface PetitionTemplate {
    id: number;
    name: string;
    category: string;
    benefit_type: string | null;
    description: string | null;
    content: string;
    variables: string[];
    sections: string[];
    is_active: boolean;
    is_default: boolean;
    created_at: string;
    updated_at: string;
    creator: {
        name: string;
    };
    petitions: Array<{
        id: number;
        title: string;
        created_at: string;
        legal_case: {
            client_name: string;
        };
        user: {
            name: string;
        };
    }>;
    petitions_count: number;
}

interface Props {
    template: PetitionTemplate;
    variables: string[];
}

export default function TemplateShow({ template, variables }: Props) {
    const [showContent, setShowContent] = useState(false);

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const getCategoryBadge = (category: string) => {
        const colors: Record<string, string> = {
            recurso: 'bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-300',
            requerimento: 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300',
            defesa: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300',
            impugnacao: 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300',
            contestacao: 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-300',
            mandado_seguranca: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300',
            acao_ordinaria: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/20 dark:text-indigo-300',
        };

        const categoryLabels: Record<string, string> = {
            recurso: 'Recurso',
            requerimento: 'Requerimento',
            defesa: 'Defesa',
            impugnacao: 'Impugnação',
            contestacao: 'Contestação',
            mandado_seguranca: 'Mandado de Segurança',
            acao_ordinaria: 'Ação Ordinária',
        };

        return <Badge className={`text-xs ${colors[category] || 'bg-gray-100 text-gray-800'}`}>{categoryLabels[category] || category}</Badge>;
    };

    const getStatusBadge = (isActive: boolean, isDefault: boolean) => {
        if (isDefault) {
            return (
                <Badge variant="default" className="text-xs">
                    Padrão
                </Badge>
            );
        }
        return isActive ? (
            <Badge variant="secondary" className="text-xs">
                Ativo
            </Badge>
        ) : (
            <Badge variant="outline" className="text-xs">
                Inativo
            </Badge>
        );
    };

    const handleToggleActive = () => {
        router.patch(
            route('petition-templates.toggle-active', template.id),
            {},
            {
                preserveScroll: true,
            },
        );
    };

    const handleDuplicate = () => {
        router.post(
            route('petition-templates.duplicate', template.id),
            {},
            {
                preserveScroll: true,
            },
        );
    };

    const handleDelete = () => {
        if (confirm('Tem certeza que deseja excluir este template?')) {
            router.delete(route('petition-templates.destroy', template.id));
        }
    };

    return (
        <AppLayout>
            <Head title={`Template: ${template.name}`} />

            <div className="space-y-4 p-4 sm:space-y-6 sm:p-6">
                {/* Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div className="mb-2 flex items-center gap-2">
                            <Link href={route('petition-templates.index')}>
                                <Button variant="outline" size="sm">
                                    <ArrowLeft className="mr-2 h-4 w-4" />
                                    Voltar
                                </Button>
                            </Link>
                        </div>
                        <h1 className="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">
                            <FileText className="mr-2 inline-block" /> {template.name}
                        </h1>
                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">Detalhes e estatísticas do template</p>
                    </div>
                    <div className="flex flex-col gap-2 sm:flex-row">
                        <Link href={route('petition-templates.edit', template.id)}>
                            <Button variant="outline" className="w-full sm:w-auto">
                                <Edit className="mr-2 h-4 w-4" />
                                Editar
                            </Button>
                        </Link>
                        <Button variant="outline" onClick={handleDuplicate} className="w-full sm:w-auto">
                            <Copy className="mr-2 h-4 w-4" />
                            Duplicar
                        </Button>
                        <Button variant="outline" onClick={handleToggleActive} className="w-full sm:w-auto">
                            <Settings className="mr-2 h-4 w-4" />
                            {template.is_active ? 'Desativar' : 'Ativar'}
                        </Button>
                        {template.petitions_count === 0 && (
                            <Button variant="destructive" onClick={handleDelete} className="w-full sm:w-auto">
                                <Trash2 className="mr-2 h-4 w-4" />
                                Excluir
                            </Button>
                        )}
                    </div>
                </div>

                {/* Informações Básicas */}
                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div className="space-y-6 lg:col-span-2">
                        <Card>
                            <CardHeader>
                                <CardTitle>Informações do Template</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div>
                                        <label className="text-sm font-medium text-gray-500 dark:text-gray-400">Categoria</label>
                                        <div className="mt-1">{getCategoryBadge(template.category)}</div>
                                    </div>

                                    <div>
                                        <label className="text-sm font-medium text-gray-500 dark:text-gray-400">Status</label>
                                        <div className="mt-1">{getStatusBadge(template.is_active, template.is_default)}</div>
                                    </div>

                                    {template.benefit_type && (
                                        <div>
                                            <label className="text-sm font-medium text-gray-500 dark:text-gray-400">Tipo de Benefício</label>
                                            <div className="mt-1">
                                                <Badge variant="outline" className="text-xs">
                                                    {template.benefit_type}
                                                </Badge>
                                            </div>
                                        </div>
                                    )}

                                    <div>
                                        <label className="text-sm font-medium text-gray-500 dark:text-gray-400">Criado por</label>
                                        <div className="mt-1 flex items-center gap-1">
                                            <User className="h-4 w-4 text-gray-400" />
                                            <span className="text-sm">{template.creator.name}</span>
                                        </div>
                                    </div>

                                    <div>
                                        <label className="text-sm font-medium text-gray-500 dark:text-gray-400">Data de Criação</label>
                                        <div className="mt-1 flex items-center gap-1">
                                            <Calendar className="h-4 w-4 text-gray-400" />
                                            <span className="text-sm">{formatDate(template.created_at)}</span>
                                        </div>
                                    </div>

                                    <div>
                                        <label className="text-sm font-medium text-gray-500 dark:text-gray-400">Última Atualização</label>
                                        <div className="mt-1 flex items-center gap-1">
                                            <Calendar className="h-4 w-4 text-gray-400" />
                                            <span className="text-sm">{formatDate(template.updated_at)}</span>
                                        </div>
                                    </div>
                                </div>

                                {template.description && (
                                    <div>
                                        <label className="text-sm font-medium text-gray-500 dark:text-gray-400">Descrição</label>
                                        <p className="mt-1 text-sm text-gray-900 dark:text-gray-100">{template.description}</p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Variáveis */}
                        {variables.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Variáveis do Template</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="mb-3 text-sm text-gray-600 dark:text-gray-400">Estas variáveis são substituídas automaticamente:</p>
                                    <div className="flex flex-wrap gap-2">
                                        {variables.map((variable, index) => (
                                            <span
                                                key={index}
                                                className="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/20 dark:text-blue-300"
                                            >
                                                {`{{${variable}}}`}
                                            </span>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        )}

                        {/* Conteúdo */}
                        <Card>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <CardTitle>Conteúdo do Template</CardTitle>
                                    <Button variant="outline" size="sm" onClick={() => setShowContent(!showContent)}>
                                        {showContent ? 'Ocultar' : 'Mostrar'}
                                    </Button>
                                </div>
                            </CardHeader>
                            {showContent && (
                                <CardContent>
                                    <div className="rounded-md bg-gray-50 p-4 dark:bg-gray-800">
                                        <pre className="font-mono text-sm whitespace-pre-wrap">{template.content}</pre>
                                    </div>
                                </CardContent>
                            )}
                        </Card>
                    </div>

                    {/* Estatísticas */}
                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <BarChart3 className="h-5 w-5" />
                                    Estatísticas
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="text-center">
                                    <div className="text-3xl font-bold text-blue-600 dark:text-blue-400">{template.petitions_count}</div>
                                    <div className="text-sm text-gray-500 dark:text-gray-400">Petições criadas</div>
                                </div>

                                <div className="text-center">
                                    <div className="text-xl font-semibold text-green-600 dark:text-green-400">{variables.length}</div>
                                    <div className="text-sm text-gray-500 dark:text-gray-400">Variáveis</div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Ações Rápidas */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Ações Rápidas</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-2">
                                <Link href={route('petitions.create', { template: template.id })}>
                                    <Button className="w-full">
                                        <FileText className="mr-2 h-4 w-4" />
                                        Criar Petição
                                    </Button>
                                </Link>
                                <Link href={route('petition-templates.edit', template.id)}>
                                    <Button variant="outline" className="w-full">
                                        <Edit className="mr-2 h-4 w-4" />
                                        Editar Template
                                    </Button>
                                </Link>
                                <Button variant="outline" onClick={handleDuplicate} className="w-full">
                                    <Copy className="mr-2 h-4 w-4" />
                                    Duplicar Template
                                </Button>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                {/* Petições Criadas */}
                {template.petitions && template.petitions.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Petições Criadas com este Template</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Título</TableHead>
                                            <TableHead>Cliente</TableHead>
                                            <TableHead>Criado por</TableHead>
                                            <TableHead>Data</TableHead>
                                            <TableHead className="text-right">Ações</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {template.petitions.map((petition) => (
                                            <TableRow key={petition.id}>
                                                <TableCell className="font-medium">
                                                    <Link
                                                        href={route('petitions.show', petition.id)}
                                                        className="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                                    >
                                                        {petition.title}
                                                    </Link>
                                                </TableCell>
                                                <TableCell>{petition.legal_case.client_name}</TableCell>
                                                <TableCell>{petition.user.name}</TableCell>
                                                <TableCell>{formatDate(petition.created_at)}</TableCell>
                                                <TableCell className="text-right">
                                                    <Link href={route('petitions.show', petition.id)}>
                                                        <Button variant="outline" size="sm">
                                                            Ver
                                                        </Button>
                                                    </Link>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
