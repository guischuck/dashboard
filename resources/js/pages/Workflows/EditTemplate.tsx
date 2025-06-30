import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, GripVertical, Plus, Save, Settings, Trash2 } from 'lucide-react';
import { useState } from 'react';

interface WorkflowTemplate {
    id: number;
    benefit_type: string;
    name: string;
    description: string;
    tasks: Array<{
        title: string;
        description: string;
        order: number;
        required_documents: string[];
    }>;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

interface EditTemplateProps {
    template: WorkflowTemplate;
    benefitTypes: Record<string, string>;
}

interface TaskTemplate {
    title: string;
    description: string;
    order: number;
    required_documents: string[];
}

export default function EditTemplate({ template, benefitTypes }: EditTemplateProps) {
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
            title: 'Editar Template',
            href: `/tasks/templates/${template.id}/edit`,
        },
    ];

    const [formData, setFormData] = useState({
        benefit_type: template.benefit_type,
        name: template.name,
        description: template.description || '',
        is_active: template.is_active,
    });

    const [tasks, setTasks] = useState<TaskTemplate[]>(template.tasks || []);
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    const updateFormData = (field: string, value: string | boolean) => {
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

        router.put(
            `/tasks/templates/${template.id}`,
            {
                benefit_type: formData.benefit_type,
                name: formData.name,
                description: formData.description,
                is_active: formData.is_active,
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

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar Template: ${template.name} - PrevidIA`} />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="flex items-center text-3xl font-bold">
                            <Settings className="mr-3 h-8 w-8 text-primary" />
                            Editar Template de Workflow
                        </h1>
                        <p className="text-muted-foreground">Edite o template "{template.name}"</p>
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
                                    <Select value={formData.benefit_type} onValueChange={(value) => updateFormData('benefit_type', value)}>
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

                            <div className="flex items-center space-x-2">
                                <Switch
                                    id="is_active"
                                    checked={formData.is_active}
                                    onCheckedChange={(checked) => updateFormData('is_active', checked)}
                                />
                                <Label htmlFor="is_active">Template ativo</Label>
                                <p className="text-sm text-muted-foreground">
                                    {formData.is_active
                                        ? 'Este template será usado para criar tarefas automaticamente'
                                        : 'Este template está desabilitado'}
                                </p>
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
                            {processing ? 'Salvando...' : 'Salvar Alterações'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
