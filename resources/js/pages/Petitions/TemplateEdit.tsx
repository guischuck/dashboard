import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Eye, FileText, Save } from 'lucide-react';
import React, { useState } from 'react';

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
}

interface Props {
    template: PetitionTemplate;
    categories: Array<{ value: string; label: string }>;
    benefitTypes: Array<{ value: string; label: string }>;
    variables: string[];
}

export default function TemplateEdit({ template, categories, benefitTypes, variables }: Props) {
    const [previewContent, setPreviewContent] = useState('');
    const [showPreview, setShowPreview] = useState(false);

    const { data, setData, put, processing, errors } = useForm({
        name: template.name,
        category: template.category,
        benefit_type: template.benefit_type || '',
        description: template.description || '',
        content: template.content,
        variables: variables,
        sections: template.sections || [],
        is_active: template.is_active,
        is_default: template.is_default,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('petition-templates.update', template.id));
    };

    const handlePreview = () => {
        setPreviewContent(data.content);
        setShowPreview(true);
    };

    const extractVariables = (content: string) => {
        const regex = /\{\{([^}]+)\}\}/g;
        const variables: string[] = [];
        let match;
        while ((match = regex.exec(content)) !== null) {
            if (!variables.includes(match[1].trim())) {
                variables.push(match[1].trim());
            }
        }
        setData('variables', variables);
    };

    const handleContentChange = (value: string) => {
        setData('content', value);
        extractVariables(value);
    };

    return (
        <AppLayout>
            <Head title={`Editar: ${template.name}`} />

            <div className="space-y-4 p-4 sm:space-y-6 sm:p-6">
                {/* Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div className="mb-2 flex items-center gap-2">
                            <Link href={route('petition-templates.show', template.id)}>
                                <Button variant="outline" size="sm">
                                    <ArrowLeft className="mr-2 h-4 w-4" />
                                    Voltar
                                </Button>
                            </Link>
                        </div>
                        <h1 className="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">
                            <FileText className="mr-2 inline-block" /> Editar Template
                        </h1>
                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">{template.name}</p>
                    </div>
                    <div className="flex flex-col gap-2 sm:flex-row">
                        <Button type="button" variant="outline" onClick={handlePreview} disabled={!data.content}>
                            <Eye className="mr-2 h-4 w-4" />
                            Preview
                        </Button>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        {/* Informações Básicas */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Informações Básicas</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <Label htmlFor="name">Nome do Template</Label>
                                    <Input
                                        id="name"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        placeholder="Ex: Recurso de Aposentadoria por Idade"
                                        className={errors.name ? 'border-red-500' : ''}
                                    />
                                    {errors.name && <p className="mt-1 text-sm text-red-500">{errors.name}</p>}
                                </div>

                                <div>
                                    <Label htmlFor="category">Categoria</Label>
                                    <select
                                        id="category"
                                        value={data.category}
                                        onChange={(e) => setData('category', e.target.value)}
                                        className="w-full rounded-md border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700"
                                    >
                                        <option value="">Selecione uma categoria</option>
                                        {categories.map((cat) => (
                                            <option key={cat.value} value={cat.value}>
                                                {cat.label}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.category && <p className="mt-1 text-sm text-red-500">{errors.category}</p>}
                                </div>

                                <div>
                                    <Label htmlFor="benefit_type">Tipo de Benefício (Opcional)</Label>
                                    <select
                                        id="benefit_type"
                                        value={data.benefit_type}
                                        onChange={(e) => setData('benefit_type', e.target.value)}
                                        className="w-full rounded-md border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700"
                                    >
                                        <option value="">Selecione um tipo de benefício</option>
                                        {benefitTypes.map((type) => (
                                            <option key={type.value} value={type.value}>
                                                {type.label}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.benefit_type && <p className="mt-1 text-sm text-red-500">{errors.benefit_type}</p>}
                                </div>

                                <div>
                                    <Label htmlFor="description">Descrição</Label>
                                    <Textarea
                                        id="description"
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        placeholder="Descreva quando usar este template..."
                                        rows={3}
                                    />
                                    {errors.description && <p className="mt-1 text-sm text-red-500">{errors.description}</p>}
                                </div>

                                <div className="space-y-2">
                                    <div className="flex items-center gap-2">
                                        <input
                                            type="checkbox"
                                            id="is_active"
                                            checked={data.is_active}
                                            onChange={(e) => setData('is_active', e.target.checked)}
                                            className="rounded"
                                        />
                                        <Label htmlFor="is_active">Template ativo</Label>
                                    </div>

                                    <div className="flex items-center gap-2">
                                        <input
                                            type="checkbox"
                                            id="is_default"
                                            checked={data.is_default}
                                            onChange={(e) => setData('is_default', e.target.checked)}
                                            className="rounded"
                                        />
                                        <Label htmlFor="is_default">Template padrão</Label>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Variáveis Detectadas */}
                        {data.variables.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Variáveis Detectadas</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="mb-3 text-sm text-gray-600 dark:text-gray-400">
                                        Estas variáveis foram detectadas no conteúdo do template:
                                    </p>
                                    <div className="flex flex-wrap gap-2">
                                        {data.variables.map((variable, index) => (
                                            <span
                                                key={index}
                                                className="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/20 dark:text-blue-300"
                                            >
                                                {`{{${variable}}}`}
                                            </span>
                                        ))}
                                    </div>
                                    <p className="mt-2 text-xs text-gray-500">Use a sintaxe {`{{variavel}}`} para criar placeholders no template.</p>
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    {/* Conteúdo do Template */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Conteúdo do Template</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div>
                                <Label htmlFor="content">Conteúdo</Label>
                                <Textarea
                                    id="content"
                                    value={data.content}
                                    onChange={(e) => handleContentChange(e.target.value)}
                                    placeholder="Digite o conteúdo do template aqui. Use {{variavel}} para criar placeholders..."
                                    rows={15}
                                    className={`font-mono ${errors.content ? 'border-red-500' : ''}`}
                                />
                                {errors.content && <p className="mt-1 text-sm text-red-500">{errors.content}</p>}
                                <p className="mt-2 text-xs text-gray-500">
                                    Dica: Use variáveis como {`{{client_name}}`}, {`{{client_cpf}}`}, {`{{case_number}}`} para dados automáticos.
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Preview Modal */}
                    {showPreview && (
                        <Card className="border-blue-200 dark:border-blue-800">
                            <CardHeader className="bg-blue-50 dark:bg-blue-900/20">
                                <div className="flex items-center justify-between">
                                    <CardTitle className="text-blue-900 dark:text-blue-100">Preview do Template</CardTitle>
                                    <Button type="button" variant="outline" size="sm" onClick={() => setShowPreview(false)}>
                                        Fechar
                                    </Button>
                                </div>
                            </CardHeader>
                            <CardContent className="mt-4">
                                <div className="rounded-md border bg-white p-4 dark:bg-gray-800">
                                    <pre className="text-sm whitespace-pre-wrap">{previewContent}</pre>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Ações */}
                    <div className="flex flex-col justify-end gap-4 sm:flex-row">
                        <Link href={route('petition-templates.show', template.id)}>
                            <Button type="button" variant="outline" className="w-full sm:w-auto">
                                Cancelar
                            </Button>
                        </Link>
                        <Button type="submit" disabled={processing} className="w-full sm:w-auto">
                            <Save className="mr-2 h-4 w-4" />
                            {processing ? 'Salvando...' : 'Salvar Alterações'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
