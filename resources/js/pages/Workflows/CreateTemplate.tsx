import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, GripVertical, Plus, Save, Settings, Trash2 } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Workflows',
        href: '/tasks',
    },
    {
        title: 'Novo Template',
        href: '/tasks/templates/create',
    },
];

interface CreateTemplateProps {
    benefitTypes: Record<string, string>;
}

interface TaskTemplate {
    title: string;
    description: string;
    order: number;
    required_documents: string[];
}

export default function CreateTemplate({ benefitTypes }: CreateTemplateProps) {
    const [formData, setFormData] = useState({
        benefit_type: '',
        name: '',
        description: '',
    });

    const [tasks, setTasks] = useState<TaskTemplate[]>([{ title: '', description: '', order: 1, required_documents: [] }]);

    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    const updateFormData = (field: string, value: string) => {
        setFormData((prev) => ({ ...prev, [field]: value }));
        // Limpar erro do campo quando o usuário começar a digitar
        if (errors[field]) {
            setErrors((prev) => ({ ...prev, [field]: '' }));
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        // Validação básica
        const newErrors: Record<string, string> = {};

        if (!formData.benefit_type) {
            newErrors.benefit_type = 'Selecione um tipo de benefício';
        }

        if (!formData.name.trim()) {
            newErrors.name = 'Nome do template é obrigatório';
        }

        // Validar tarefas
        const validTasks = tasks.filter((task) => task.title.trim() && task.description.trim());
        if (validTasks.length === 0) {
            newErrors.tasks = 'Adicione pelo menos uma tarefa válida';
        }

        if (Object.keys(newErrors).length > 0) {
            setErrors(newErrors);
            return;
        }

        setProcessing(true);

        router.post(
            '/tasks/templates',
            {
                benefit_type: formData.benefit_type,
                name: formData.name,
                description: formData.description,
                tasks: JSON.stringify(validTasks),
            },
            {
                onFinish: () => setProcessing(false),
                onError: (errors) => setErrors(errors),
            },
        );
    };

    const addTask = () => {
        setTasks([
            ...tasks,
            {
                title: '',
                description: '',
                order: tasks.length + 1,
                required_documents: [],
            },
        ]);
    };

    const removeTask = (index: number) => {
        const newTasks = tasks.filter((_, i) => i !== index);
        // Reordenar as tarefas
        const reorderedTasks = newTasks.map((task, i) => ({ ...task, order: i + 1 }));
        setTasks(reorderedTasks);
    };

    const updateTask = (index: number, field: keyof TaskTemplate, value: any) => {
        const newTasks = [...tasks];
        newTasks[index] = { ...newTasks[index], [field]: value };
        setTasks(newTasks);
    };

    const addDocument = (taskIndex: number, document: string) => {
        if (document.trim()) {
            const newTasks = [...tasks];
            newTasks[taskIndex].required_documents = [...newTasks[taskIndex].required_documents, document.trim()];
            setTasks(newTasks);
        }
    };

    const removeDocument = (taskIndex: number, docIndex: number) => {
        const newTasks = [...tasks];
        newTasks[taskIndex].required_documents = newTasks[taskIndex].required_documents.filter((_, i) => i !== docIndex);
        setTasks(newTasks);
    };

    const moveTask = (index: number, direction: 'up' | 'down') => {
        const newTasks = [...tasks];
        const targetIndex = direction === 'up' ? index - 1 : index + 1;

        if (targetIndex >= 0 && targetIndex < newTasks.length) {
            // Trocar as tarefas de posição
            [newTasks[index], newTasks[targetIndex]] = [newTasks[targetIndex], newTasks[index]];

            // Atualizar a ordem
            newTasks.forEach((task, i) => {
                task.order = i + 1;
            });

            setTasks(newTasks);
        }
    };

    const getPresetTasks = (benefitType: string): TaskTemplate[] => {
        const presets: Record<string, TaskTemplate[]> = {
            aposentadoria_por_idade: [
                {
                    title: 'Coletar documentos pessoais',
                    description: 'RG, CPF, certidão de nascimento, comprovante de residência',
                    order: 1,
                    required_documents: ['RG', 'CPF', 'Certidão de nascimento', 'Comprovante de residência'],
                },
                {
                    title: 'Solicitar CNIS',
                    description: 'Obter extrato do CNIS atualizado no site do INSS',
                    order: 2,
                    required_documents: ['CNIS'],
                },
                {
                    title: 'Verificar idade do segurado',
                    description: 'Confirmar se atende aos requisitos de idade (65 anos homem, 60 anos mulher)',
                    order: 3,
                    required_documents: [],
                },
                {
                    title: 'Calcular tempo de contribuição',
                    description: 'Verificar se possui pelo menos 15 anos de contribuição',
                    order: 4,
                    required_documents: [],
                },
                {
                    title: 'Preparar petição inicial',
                    description: 'Elaborar petição de aposentadoria por idade',
                    order: 5,
                    required_documents: [],
                },
            ],
            aposentadoria_por_tempo_contribuicao: [
                {
                    title: 'Coletar documentos pessoais',
                    description: 'RG, CPF, certidão de nascimento, comprovante de residência',
                    order: 1,
                    required_documents: ['RG', 'CPF', 'Certidão de nascimento', 'Comprovante de residência'],
                },
                {
                    title: 'Solicitar CNIS completo',
                    description: 'Obter CNIS com histórico completo de contribuições',
                    order: 2,
                    required_documents: ['CNIS'],
                },
                {
                    title: 'Calcular tempo de contribuição',
                    description: 'Verificar se possui 35 anos (homem) ou 30 anos (mulher) de contribuição',
                    order: 3,
                    required_documents: [],
                },
                {
                    title: 'Analisar períodos especiais',
                    description: 'Verificar atividades especiais, insalubres ou perigosas',
                    order: 4,
                    required_documents: ['PPP', 'LTCAT', 'Laudos técnicos'],
                },
                {
                    title: 'Preparar petição inicial',
                    description: 'Elaborar petição de aposentadoria por tempo de contribuição',
                    order: 5,
                    required_documents: [],
                },
            ],
            auxilio_doenca: [
                {
                    title: 'Coletar documentos pessoais',
                    description: 'RG, CPF, comprovante de residência',
                    order: 1,
                    required_documents: ['RG', 'CPF', 'Comprovante de residência'],
                },
                {
                    title: 'Reunir documentos médicos',
                    description: 'Coletar laudos, exames e relatórios médicos',
                    order: 2,
                    required_documents: ['Laudos médicos', 'Exames', 'Relatórios médicos'],
                },
                {
                    title: 'Verificar carência',
                    description: 'Confirmar 12 meses de contribuição',
                    order: 3,
                    required_documents: ['CNIS'],
                },
                {
                    title: 'Agendar perícia médica',
                    description: 'Solicitar agendamento de perícia no INSS',
                    order: 4,
                    required_documents: [],
                },
            ],
        };

        return presets[benefitType] || [];
    };

    const loadPresetTasks = () => {
        if (formData.benefit_type) {
            const presetTasks = getPresetTasks(formData.benefit_type);
            if (presetTasks.length > 0) {
                setTasks(presetTasks);
            }
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Novo Template de Workflow - PrevidIA" />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="flex items-center text-3xl font-bold">
                            <Settings className="mr-3 h-8 w-8 text-primary" />
                            Novo Template de Workflow
                        </h1>
                        <p className="text-muted-foreground">Crie um template de tarefas para um tipo específico de benefício</p>
                    </div>
                    <Link href="/tasks?tab=templates">
                        <Button variant="outline">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Voltar
                        </Button>
                    </Link>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Informações Básicas */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Informações do Template</CardTitle>
                            <CardDescription>Configure as informações básicas do template</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <Label htmlFor="benefit_type">Tipo de Benefício *</Label>
                                    <Select
                                        value={formData.benefit_type}
                                        onValueChange={(value) => {
                                            updateFormData('benefit_type', value);
                                        }}
                                    >
                                        <SelectTrigger className={errors.benefit_type ? 'border-red-500' : ''}>
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
                                    {errors.benefit_type && <p className="mt-1 text-sm text-red-500">{errors.benefit_type}</p>}
                                    {formData.benefit_type && (
                                        <div className="mt-2">
                                            <Button type="button" variant="outline" size="sm" onClick={loadPresetTasks}>
                                                Carregar tarefas padrão
                                            </Button>
                                        </div>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="name">Nome do Template *</Label>
                                    <Input
                                        id="name"
                                        value={formData.name}
                                        onChange={(e) => updateFormData('name', e.target.value)}
                                        placeholder="Ex: Workflow - Aposentadoria por Idade"
                                        className={errors.name ? 'border-red-500' : ''}
                                    />
                                    {errors.name && <p className="mt-1 text-sm text-red-500">{errors.name}</p>}
                                </div>
                            </div>

                            <div>
                                <Label htmlFor="description">Descrição</Label>
                                <Textarea
                                    id="description"
                                    value={formData.description}
                                    onChange={(e) => updateFormData('description', e.target.value)}
                                    placeholder="Descrição do template de workflow..."
                                    rows={3}
                                />
                            </div>
                        </CardContent>
                    </Card>

                    {/* Tarefas do Workflow */}
                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <div>
                                    <CardTitle>Tarefas do Workflow</CardTitle>
                                    <CardDescription>Configure as tarefas que serão criadas automaticamente</CardDescription>
                                </div>
                                <Button type="button" onClick={addTask} variant="outline">
                                    <Plus className="mr-2 h-4 w-4" />
                                    Adicionar Tarefa
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {errors.tasks && <p className="text-sm text-red-500">{errors.tasks}</p>}

                            {tasks.map((task, index) => (
                                <div key={index} className="space-y-4 rounded-lg border p-4">
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center space-x-2">
                                            <GripVertical className="h-4 w-4 text-muted-foreground" />
                                            <h4 className="font-medium">Tarefa #{task.order}</h4>
                                            <div className="flex space-x-1">
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => moveTask(index, 'up')}
                                                    disabled={index === 0}
                                                >
                                                    ↑
                                                </Button>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => moveTask(index, 'down')}
                                                    disabled={index === tasks.length - 1}
                                                >
                                                    ↓
                                                </Button>
                                            </div>
                                        </div>
                                        {tasks.length > 1 && (
                                            <Button type="button" variant="destructive" size="sm" onClick={() => removeTask(index)}>
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        )}
                                    </div>

                                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <div>
                                            <Label>Título da Tarefa</Label>
                                            <Input
                                                value={task.title}
                                                onChange={(e) => updateTask(index, 'title', e.target.value)}
                                                placeholder="Ex: Análise de Documentos Iniciais"
                                            />
                                        </div>
                                    </div>

                                    <div>
                                        <Label>Descrição</Label>
                                        <Textarea
                                            value={task.description}
                                            onChange={(e) => updateTask(index, 'description', e.target.value)}
                                            placeholder="Descrição detalhada da tarefa..."
                                            rows={2}
                                        />
                                    </div>

                                    <div>
                                        <Label>Documentos Necessários</Label>
                                        <div className="space-y-2">
                                            <div className="flex space-x-2">
                                                <Input
                                                    placeholder="Ex: RG, CPF, Comprovante de residência"
                                                    onKeyPress={(e) => {
                                                        if (e.key === 'Enter') {
                                                            e.preventDefault();
                                                            addDocument(index, e.currentTarget.value);
                                                            e.currentTarget.value = '';
                                                        }
                                                    }}
                                                />
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    onClick={(e) => {
                                                        const input = e.currentTarget.previousElementSibling as HTMLInputElement;
                                                        addDocument(index, input.value);
                                                        input.value = '';
                                                    }}
                                                >
                                                    <Plus className="h-4 w-4" />
                                                </Button>
                                            </div>

                                            {task.required_documents.length > 0 && (
                                                <div className="flex flex-wrap gap-2">
                                                    {task.required_documents.map((doc, docIndex) => (
                                                        <Badge key={docIndex} variant="secondary" className="flex items-center">
                                                            <span>{doc}</span>
                                                            <Button
                                                                type="button"
                                                                variant="ghost"
                                                                size="sm"
                                                                className="ml-1 h-4 w-4 p-0 hover:bg-transparent"
                                                                onClick={() => removeDocument(index, docIndex)}
                                                            >
                                                                <Trash2 className="h-3 w-3" />
                                                            </Button>
                                                        </Badge>
                                                    ))}
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    {/* Actions */}
                    <div className="flex items-center justify-end space-x-4">
                        <Link href="/tasks?tab=templates">
                            <Button type="button" variant="outline">
                                Cancelar
                            </Button>
                        </Link>
                        <Button type="submit" disabled={processing}>
                            <Save className="mr-2 h-4 w-4" />
                            {processing ? 'Salvando...' : 'Salvar Template'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
