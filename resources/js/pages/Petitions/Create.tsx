import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { ArrowLeft, Bot, CheckCircle, ChevronLeft, ChevronRight, Eye, File, Loader2, Search, Settings, Sparkles, User } from 'lucide-react';
import { useMemo, useState } from 'react';

interface Case {
    id: number;
    client_name: string;
    case_number: string;
    benefit_type?: string;
    client_cpf?: string;
    description?: string;
}

interface Template {
    id: number;
    name: string;
    category: string;
    benefit_type?: string;
    description?: string;
}

interface CreateProps {
    cases: Case[];
    templates: Record<string, Template[]>;
    categories: Record<string, string>;
    benefitTypes: Record<string, string>;
}

type Step = 'client' | 'method' | 'template' | 'config' | 'preview';

export default function PetitionsCreate({ cases, templates, categories, benefitTypes }: CreateProps) {
    const [currentStep, setCurrentStep] = useState<Step>('client');
    const [method, setMethod] = useState<'template' | 'ia'>('template');
    const [caseId, setCaseId] = useState('');
    const [templateId, setTemplateId] = useState('');
    const [category, setCategory] = useState('');
    const [title, setTitle] = useState('');
    const [content, setContent] = useState('');
    const [iaPrompt, setIaPrompt] = useState('');
    const [notes, setNotes] = useState('');
    const [templateVariables] = useState<Record<string, string>>({});
    const [loading, setLoading] = useState(false);
    const [clientSearch, setClientSearch] = useState('');

    // Filtrar clientes baseado na busca
    const filteredCases = useMemo(() => {
        if (!clientSearch) return cases;
        return cases.filter(
            (c) =>
                c.client_name.toLowerCase().includes(clientSearch.toLowerCase()) ||
                c.case_number.toLowerCase().includes(clientSearch.toLowerCase()) ||
                (c.client_cpf && c.client_cpf.includes(clientSearch)),
        );
    }, [cases, clientSearch]);

    const selectedCase = useMemo(() => {
        return cases.find((c) => c.id.toString() === caseId);
    }, [cases, caseId]);

    const selectedTemplate = useMemo(() => {
        if (!templateId) return null;
        return Object.values(templates)
            .flat()
            .find((t) => t.id.toString() === templateId);
    }, [templates, templateId]);

    const handleClientSelect = (clientId: string) => {
        setCaseId(clientId);
        const selectedCase = cases.find((c) => c.id.toString() === clientId);
        if (selectedCase?.benefit_type) {
            // Auto-selecionar categoria baseada no tipo de benefício
            setCategory('requerimento'); // padrão
        }
        setCurrentStep('method');
    };

    const handleMethodSelect = (selectedMethod: 'template' | 'ia') => {
        setMethod(selectedMethod);
        if (selectedMethod === 'template') {
            setCurrentStep('template');
        } else {
            setCurrentStep('config');
        }
    };

    const handleTemplateSelect = (templateId: string) => {
        setTemplateId(templateId);
        const template = Object.values(templates)
            .flat()
            .find((t) => t.id.toString() === templateId);
        if (template) {
            setCategory(template.category);
            setTitle(`${template.name} - ${selectedCase?.client_name}`);
        }
        setCurrentStep('config');
    };

    const handleGenerateFromTemplate = async () => {
        if (!templateId || !caseId) return;

        setLoading(true);
        try {
            const response = await fetch('/api/generate-from-template', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    template_id: templateId,
                    case_id: caseId,
                    variables: templateVariables,
                }),
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    setContent(data.content);
                    setCurrentStep('preview');
                } else {
                    setContent('Erro ao gerar petição: ' + (data.error || 'Erro desconhecido'));
                }
            } else {
                const errorData = await response.json().catch(() => ({}));
                setContent('Erro ao gerar petição: ' + (errorData.error || 'Erro de conexão'));
            }
        } catch (error) {
            console.error('Erro na geração:', error);
            setContent('Erro ao gerar petição. Tente novamente.');
        } finally {
            setLoading(false);
        }
    };

    const handleGenerateWithAI = async () => {
        if (!caseId || !iaPrompt) return;

        setLoading(true);
        try {
            const response = await fetch('/api/generate-petition', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    case_id: caseId,
                    category: category,
                    prompt: iaPrompt,
                    template_id: templateId || null,
                }),
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    setContent(data.content);
                    setCurrentStep('preview');
                } else {
                    setContent('Erro ao gerar petição: ' + (data.error || 'Erro desconhecido'));
                }
            } else {
                const errorData = await response.json().catch(() => ({}));
                setContent('Erro ao gerar petição: ' + (errorData.error || 'Erro de conexão'));
            }
        } catch (error) {
            console.error('Erro na geração:', error);
            setContent('Erro ao gerar petição. Tente novamente.');
        } finally {
            setLoading(false);
        }
    };

    const handleSave = () => {
        router.post('/petitions', {
            case_id: caseId,
            type: method,
            template_id: templateId || null,
            category,
            title,
            content,
            template_variables: templateVariables,
            ai_prompt: iaPrompt,
            notes,
        });
    };

    const steps = [
        { id: 'client', title: 'Cliente', icon: User },
        { id: 'method', title: 'Método', icon: Sparkles },
        { id: 'template', title: 'Template', icon: File, show: method === 'template' },
        { id: 'config', title: 'Configuração', icon: Settings },
        { id: 'preview', title: 'Preview', icon: Eye },
    ].filter((step) => step.show !== false);

    const currentStepIndex = steps.findIndex((step) => step.id === currentStep);

    return (
        <AppLayout>
            <Head title="Nova Petição" />

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="outline" size="sm" onClick={() => router.visit('/petitions')}>
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Voltar
                        </Button>
                        <div>
                            <h1 className="text-2xl font-bold">Nova Petição</h1>
                            <p className="text-muted-foreground">Crie petições usando templates ou inteligência artificial</p>
                        </div>
                    </div>
                </div>

                {/* Progress Steps */}
                <div className="flex items-center justify-center gap-4 py-4">
                    {steps.map((step, index) => {
                        const isActive = step.id === currentStep;
                        const isCompleted = index < currentStepIndex;
                        const Icon = step.icon;

                        return (
                            <div key={step.id} className="flex items-center">
                                <div
                                    className={`flex items-center gap-2 rounded-lg border px-4 py-2 transition-colors ${
                                        isActive
                                            ? 'border-primary bg-primary text-primary-foreground'
                                            : isCompleted
                                              ? 'border-green-200 bg-green-100 text-green-700'
                                              : 'border-border bg-muted text-muted-foreground'
                                    }`}
                                >
                                    <Icon className="h-4 w-4" />
                                    <span className="text-sm font-medium">{step.title}</span>
                                </div>
                                {index < steps.length - 1 && <ChevronRight className="mx-2 h-4 w-4 text-muted-foreground" />}
                            </div>
                        );
                    })}
                </div>

                {/* Step Content */}
                <div className="flex-1">
                    {currentStep === 'client' && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <User className="h-5 w-5" />
                                    Selecionar Cliente
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="relative">
                                    <Search className="absolute top-3 left-3 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        placeholder="Buscar por nome, número do caso ou CPF..."
                                        value={clientSearch}
                                        onChange={(e) => setClientSearch(e.target.value)}
                                        className="pl-10"
                                    />
                                </div>

                                <div className="grid max-h-96 gap-3 overflow-y-auto">
                                    {filteredCases.map((case_) => (
                                        <Card
                                            key={case_.id}
                                            className={`cursor-pointer transition-colors hover:bg-muted/50 ${
                                                caseId === case_.id.toString() ? 'ring-2 ring-primary' : ''
                                            }`}
                                            onClick={() => handleClientSelect(case_.id.toString())}
                                        >
                                            <CardContent className="p-4">
                                                <div className="flex items-center justify-between">
                                                    <div>
                                                        <h3 className="font-medium">{case_.client_name}</h3>
                                                        <p className="text-sm text-muted-foreground">Caso: {case_.case_number}</p>
                                                        {case_.client_cpf && <p className="text-sm text-muted-foreground">CPF: {case_.client_cpf}</p>}
                                                    </div>
                                                    {case_.benefit_type && (
                                                        <Badge variant="secondary">{benefitTypes[case_.benefit_type] || case_.benefit_type}</Badge>
                                                    )}
                                                </div>
                                            </CardContent>
                                        </Card>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {currentStep === 'method' && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Sparkles className="h-5 w-5" />
                                    Escolher Método
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid gap-4 md:grid-cols-2">
                                    <Card
                                        className={`cursor-pointer transition-colors hover:bg-muted/50 ${
                                            method === 'template' ? 'ring-2 ring-primary' : ''
                                        }`}
                                        onClick={() => handleMethodSelect('template')}
                                    >
                                        <CardContent className="p-6 text-center">
                                            <File className="mx-auto mb-4 h-12 w-12 text-blue-500" />
                                            <h3 className="mb-2 font-semibold">Usar Template</h3>
                                            <p className="text-sm text-muted-foreground">
                                                Escolha um template pré-definido e personalize com os dados do cliente
                                            </p>
                                        </CardContent>
                                    </Card>

                                    <Card
                                        className={`cursor-pointer transition-colors hover:bg-muted/50 ${
                                            method === 'ia' ? 'ring-2 ring-primary' : ''
                                        }`}
                                        onClick={() => handleMethodSelect('ia')}
                                    >
                                        <CardContent className="p-6 text-center">
                                            <Bot className="mx-auto mb-4 h-12 w-12 text-purple-500" />
                                            <h3 className="mb-2 font-semibold">Inteligência Artificial</h3>
                                            <p className="text-sm text-muted-foreground">
                                                Gere uma petição personalizada usando IA com base nos dados do caso
                                            </p>
                                        </CardContent>
                                    </Card>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {currentStep === 'template' && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <File className="h-5 w-5" />
                                    Selecionar Template
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                {Object.entries(templates).map(([categoryKey, categoryTemplates]) => (
                                    <div key={categoryKey}>
                                        <h3 className="mb-3 font-semibold">{categories[categoryKey]}</h3>
                                        <div className="grid gap-3">
                                            {categoryTemplates.map((template) => (
                                                <Card
                                                    key={template.id}
                                                    className={`cursor-pointer transition-colors hover:bg-muted/50 ${
                                                        templateId === template.id.toString() ? 'ring-2 ring-primary' : ''
                                                    }`}
                                                    onClick={() => handleTemplateSelect(template.id.toString())}
                                                >
                                                    <CardContent className="p-4">
                                                        <div className="flex items-center justify-between">
                                                            <div>
                                                                <h4 className="font-medium">{template.name}</h4>
                                                                {template.description && (
                                                                    <p className="mt-1 text-sm text-muted-foreground">{template.description}</p>
                                                                )}
                                                            </div>
                                                            <div className="flex gap-2">
                                                                {template.benefit_type && (
                                                                    <Badge variant="outline">{benefitTypes[template.benefit_type]}</Badge>
                                                                )}
                                                                <Badge variant="secondary">{categories[template.category]}</Badge>
                                                            </div>
                                                        </div>
                                                    </CardContent>
                                                </Card>
                                            ))}
                                        </div>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    )}

                    {currentStep === 'config' && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Settings className="h-5 w-5" />
                                    Configuração
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                <div className="grid gap-6 md:grid-cols-2">
                                    <div className="space-y-4">
                                        <div>
                                            <label className="text-sm font-medium">Título da Petição</label>
                                            <Input
                                                value={title}
                                                onChange={(e) => setTitle(e.target.value)}
                                                placeholder="Digite o título da petição"
                                            />
                                        </div>

                                        <div>
                                            <label className="text-sm font-medium">Categoria</label>
                                            <Select value={category} onValueChange={setCategory}>
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Selecione a categoria" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {Object.entries(categories).map(([key, value]) => (
                                                        <SelectItem key={key} value={key}>
                                                            {value}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        {method === 'ia' && (
                                            <div>
                                                <label className="text-sm font-medium">Prompt para IA</label>
                                                <Textarea
                                                    value={iaPrompt}
                                                    onChange={(e) => setIaPrompt(e.target.value)}
                                                    placeholder="Descreva o que você quer na petição..."
                                                    rows={4}
                                                />
                                            </div>
                                        )}

                                        <div>
                                            <label className="text-sm font-medium">Notas (opcional)</label>
                                            <Textarea
                                                value={notes}
                                                onChange={(e) => setNotes(e.target.value)}
                                                placeholder="Notas adicionais..."
                                                rows={3}
                                            />
                                        </div>
                                    </div>

                                    <div className="space-y-4">
                                        <h4 className="font-medium">Dados do Cliente</h4>
                                        {selectedCase && (
                                            <div className="space-y-2 rounded-lg bg-muted p-4">
                                                <p>
                                                    <strong>Nome:</strong> {selectedCase.client_name}
                                                </p>
                                                <p>
                                                    <strong>Caso:</strong> {selectedCase.case_number}
                                                </p>
                                                {selectedCase.client_cpf && (
                                                    <p>
                                                        <strong>CPF:</strong> {selectedCase.client_cpf}
                                                    </p>
                                                )}
                                                {selectedCase.benefit_type && (
                                                    <p>
                                                        <strong>Benefício:</strong> {benefitTypes[selectedCase.benefit_type]}
                                                    </p>
                                                )}
                                            </div>
                                        )}

                                        {selectedTemplate && (
                                            <div className="space-y-2">
                                                <h4 className="font-medium">Template Selecionado</h4>
                                                <div className="rounded-lg bg-muted p-4">
                                                    <p>
                                                        <strong>{selectedTemplate.name}</strong>
                                                    </p>
                                                    <p className="text-sm text-muted-foreground">{selectedTemplate.description}</p>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </div>

                                <div className="flex justify-between">
                                    <Button variant="outline" onClick={() => setCurrentStep(method === 'template' ? 'template' : 'method')}>
                                        <ChevronLeft className="mr-2 h-4 w-4" />
                                        Voltar
                                    </Button>

                                    <Button
                                        onClick={method === 'template' ? handleGenerateFromTemplate : handleGenerateWithAI}
                                        disabled={loading || !title || !category || (method === 'ia' && !iaPrompt)}
                                    >
                                        {loading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                                        {method === 'template' ? 'Gerar do Template' : 'Gerar com IA'}
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {currentStep === 'preview' && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Eye className="h-5 w-5" />
                                    Preview da Petição
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                <div className="space-y-4">
                                    <div>
                                        <label className="text-sm font-medium">Título</label>
                                        <Input value={title} onChange={(e) => setTitle(e.target.value)} />
                                    </div>

                                    <div>
                                        <label className="text-sm font-medium">Conteúdo</label>
                                        <Textarea value={content} onChange={(e) => setContent(e.target.value)} rows={20} className="font-mono" />
                                    </div>
                                </div>

                                <div className="flex justify-between">
                                    <Button variant="outline" onClick={() => setCurrentStep('config')}>
                                        <ChevronLeft className="mr-2 h-4 w-4" />
                                        Editar
                                    </Button>

                                    <Button onClick={handleSave}>
                                        <CheckCircle className="mr-2 h-4 w-4" />
                                        Salvar Petição
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
