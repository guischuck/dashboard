import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Progress } from '@/components/ui/progress';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { AlertCircle, ArrowLeft, Building, CheckCircle, FileText, Loader2, Plus, Upload, User } from 'lucide-react';
import { useRef, useState } from 'react';

interface VinculoEmpregaticios {
    empregador: string;
    cnpj: string;
    data_inicio: string;
    data_fim: string;
}

interface CNISData {
    client_name: string;
    client_cpf: string;
    vinculos_empregaticios: VinculoEmpregaticios[];
}

interface Props {
    benefitTypes: Record<string, string>;
}

export default function CreateCase({ benefitTypes }: Props) {
    const { data, setData, errors } = useForm({
        client_name: '',
        client_cpf: '',
        benefit_type: '',
        notes: '',
        vinculos_empregaticios: [] as any[],
    });

    const [cnisFile, setCnisFile] = useState<File | null>(null);
    const [cnisData, setCnisData] = useState<CNISData | null>(null);
    const [isProcessingCnis, setIsProcessingCnis] = useState(false);
    const [uploadProgress, setUploadProgress] = useState(0);
    const [cnisError, setCnisError] = useState<string | null>(null);
    const [isDragging, setIsDragging] = useState(false);

    const fileInputRef = useRef<HTMLInputElement>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Casos', href: '/cases' },
        { title: 'Novo Caso', href: '#' },
    ];

    const handleDragOver = (e: React.DragEvent) => {
        e.preventDefault();
        setIsDragging(true);
    };

    const handleDragLeave = (e: React.DragEvent) => {
        e.preventDefault();
        setIsDragging(false);
    };

    const handleDrop = (e: React.DragEvent) => {
        e.preventDefault();
        setIsDragging(false);
        const files = e.dataTransfer.files;
        if (files.length > 0 && files[0].type === 'application/pdf') {
            setCnisFile(files[0]);
        }
    };

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file && file.type === 'application/pdf') {
            setCnisFile(file);
        }
    };

    const handleFileClick = () => {
        fileInputRef.current?.click();
    };

    const handleCnisUpload = async () => {
        if (!cnisFile) return;

        setIsProcessingCnis(true);
        setUploadProgress(0);
        setCnisError(null);

        const progressInterval = setInterval(() => {
            setUploadProgress((prev) => {
                if (prev >= 90) return prev;
                return prev + Math.random() * 15;
            });
        }, 200);

        try {
            const formData = new FormData();
            formData.append('cnis_file', cnisFile);

            const response = await fetch('/api/process-cnis', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            const result = await response.json();

            if (result.success && result.data) {
                setCnisData(result.data);
                setData({
                    ...data,
                    client_name: result.data.client_name || '',
                    client_cpf: result.data.client_cpf || '',
                    vinculos_empregaticios: result.data.vinculos_empregaticios || [],
                });
                setUploadProgress(100);
            } else {
                setCnisError(result.error || 'Erro ao processar o arquivo CNIS');
            }
        } catch (error) {
            setCnisError('Erro de conexão. Tente novamente.');
        } finally {
            clearInterval(progressInterval);
            setIsProcessingCnis(false);
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        router.post('/cases', data);
    };

    const addManualVinculo = () => {
        const newVinculo = {
            empregador: '',
            cnpj: '',
            data_inicio: '',
            data_fim: '',
        };
        const vinculos = Array.isArray(data.vinculos_empregaticios) ? data.vinculos_empregaticios : [];
        setData('vinculos_empregaticios', [...vinculos, newVinculo]);
    };

    const updateVinculo = (index: number, field: keyof VinculoEmpregaticios, value: string) => {
        const vinculos = Array.isArray(data.vinculos_empregaticios) ? data.vinculos_empregaticios : [];
        const updatedVinculos = [...vinculos];
        updatedVinculos[index] = { ...updatedVinculos[index], [field]: value };
        setData('vinculos_empregaticios', updatedVinculos);
    };

    const removeVinculo = (index: number) => {
        const vinculos = Array.isArray(data.vinculos_empregaticios) ? data.vinculos_empregaticios : [];
        const updatedVinculos = vinculos.filter((_, i) => i !== index);
        setData('vinculos_empregaticios', updatedVinculos);
    };

    const vinculos = Array.isArray(data.vinculos_empregaticios) ? data.vinculos_empregaticios : [];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Novo Caso - Sistema Jurídico" />

            <div className="container mx-auto px-6 py-8">
                <div className="mx-auto max-w-4xl">
                    {/* Header */}
                    <div className="mb-8 flex items-center justify-between">
                        <div>
                            <h1 className="text-3xl font-bold text-foreground">Criar Novo Caso</h1>
                            <p className="mt-2 text-muted-foreground">Faça upload do CNIS ou preencha manualmente os dados do cliente</p>
                        </div>
                        <Link href="/cases">
                            <Button variant="outline" className="gap-2">
                                <ArrowLeft className="h-4 w-4" />
                                Voltar
                            </Button>
                        </Link>
                    </div>

                    <div className="grid gap-8">
                        {/* Upload CNIS Section */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Upload className="h-5 w-5" />
                                    Upload do CNIS (Opcional)
                                </CardTitle>
                                <CardDescription>Faça upload do arquivo PDF do CNIS para extração automática dos dados</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                {/* Drop Zone */}
                                <div
                                    className={`relative cursor-pointer rounded-xl border-2 border-dashed p-8 text-center transition-all duration-300 ${
                                        isDragging
                                            ? 'border-blue-500 bg-blue-50 dark:bg-blue-950/30'
                                            : cnisFile
                                              ? 'border-green-400 bg-green-50 dark:bg-green-950/30'
                                              : 'border-border hover:border-blue-400 hover:bg-accent/50'
                                    } `}
                                    onDragOver={handleDragOver}
                                    onDragLeave={handleDragLeave}
                                    onDrop={handleDrop}
                                    onClick={handleFileClick}
                                >
                                    <input ref={fileInputRef} type="file" accept=".pdf" onChange={handleFileChange} className="hidden" />

                                    {cnisFile ? (
                                        <div className="space-y-4">
                                            <FileText className="mx-auto h-12 w-12 text-green-600" />
                                            <div>
                                                <h3 className="text-lg font-semibold text-green-800 dark:text-green-200">{cnisFile.name}</h3>
                                                <p className="text-green-600 dark:text-green-400">{(cnisFile.size / 1024 / 1024).toFixed(2)} MB</p>
                                            </div>
                                            {!cnisData && (
                                                <Button
                                                    onClick={(e) => {
                                                        e.stopPropagation();
                                                        handleCnisUpload();
                                                    }}
                                                    disabled={isProcessingCnis}
                                                    className="bg-green-600 hover:bg-green-700"
                                                >
                                                    {isProcessingCnis ? (
                                                        <>
                                                            <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                                            Processando...
                                                        </>
                                                    ) : (
                                                        <>
                                                            <FileText className="mr-2 h-4 w-4" />
                                                            Extrair Dados
                                                        </>
                                                    )}
                                                </Button>
                                            )}
                                        </div>
                                    ) : (
                                        <div className="space-y-4">
                                            <Upload className="mx-auto h-12 w-12 text-muted-foreground" />
                                            <div>
                                                <h3 className="text-lg font-semibold">Arraste o arquivo CNIS aqui</h3>
                                                <p className="text-muted-foreground">ou clique para selecionar (apenas PDF)</p>
                                            </div>
                                        </div>
                                    )}
                                </div>

                                {/* Upload Progress */}
                                {isProcessingCnis && (
                                    <div className="space-y-3 rounded-lg bg-blue-50 p-4 dark:bg-blue-950/30">
                                        <div className="flex items-center justify-between text-sm">
                                            <span className="font-medium text-blue-700 dark:text-blue-300">Processando arquivo CNIS...</span>
                                            <span className="text-blue-600 dark:text-blue-400">{uploadProgress.toFixed(0)}%</span>
                                        </div>
                                        <Progress value={uploadProgress} className="h-2" />
                                    </div>
                                )}

                                {/* Error Message */}
                                {cnisError && (
                                    <div className="flex items-start space-x-3 rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-950/30">
                                        <AlertCircle className="mt-0.5 h-5 w-5 flex-shrink-0 text-red-600" />
                                        <div>
                                            <p className="font-medium text-red-800 dark:text-red-200">Erro no processamento</p>
                                            <p className="mt-1 text-sm text-red-700 dark:text-red-300">{cnisError}</p>
                                        </div>
                                    </div>
                                )}

                                {/* Success Message */}
                                {cnisData && (
                                    <div className="flex items-start space-x-3 rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-950/30">
                                        <CheckCircle className="mt-0.5 h-5 w-5 flex-shrink-0 text-green-600" />
                                        <div>
                                            <p className="font-medium text-green-800 dark:text-green-200">Dados extraídos com sucesso!</p>
                                            <p className="mt-1 text-sm text-green-700 dark:text-green-300">
                                                {cnisData.vinculos_empregaticios?.length || 0} vínculos encontrados
                                            </p>
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Form Section */}
                        <form onSubmit={handleSubmit} className="space-y-8">
                            {/* Client Data */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <User className="h-5 w-5" />
                                        Dados do Cliente
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="grid gap-6 md:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="client_name">Nome Completo *</Label>
                                        <Input
                                            id="client_name"
                                            value={String(data.client_name || '')}
                                            onChange={(e) => setData('client_name', e.target.value)}
                                            placeholder="Digite o nome completo do cliente"
                                            required
                                        />
                                        {errors.client_name && <p className="text-sm text-red-600">{errors.client_name}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="client_cpf">CPF *</Label>
                                        <Input
                                            id="client_cpf"
                                            value={String(data.client_cpf || '')}
                                            onChange={(e) => setData('client_cpf', e.target.value)}
                                            placeholder="000.000.000-00"
                                            required
                                        />
                                        {errors.client_cpf && <p className="text-sm text-red-600">{errors.client_cpf}</p>}
                                    </div>

                                    <div className="space-y-2">
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
                                        {errors.benefit_type && <p className="text-sm text-red-600">{errors.benefit_type}</p>}
                                    </div>

                                    <div className="space-y-2 md:col-span-2">
                                        <Label htmlFor="notes">Observações</Label>
                                        <Textarea
                                            id="notes"
                                            value={String(data.notes || '')}
                                            onChange={(e) => setData('notes', e.target.value)}
                                            placeholder="Observações sobre o caso..."
                                            rows={3}
                                        />
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Employment Links */}
                            <Card>
                                <CardHeader>
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <CardTitle className="flex items-center gap-2">
                                                <Building className="h-5 w-5" />
                                                Vínculos Empregatícios
                                            </CardTitle>
                                            <CardDescription>
                                                {vinculos.length > 0 ? `${vinculos.length} vínculo(s) adicionado(s)` : 'Nenhum vínculo adicionado'}
                                            </CardDescription>
                                        </div>
                                        <Button type="button" variant="outline" size="sm" onClick={addManualVinculo} className="gap-2">
                                            <Plus className="h-4 w-4" />
                                            Adicionar Vínculo
                                        </Button>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    {vinculos.length > 0 ? (
                                        <div className="space-y-4">
                                            {vinculos.map((vinculo: any, index: number) => (
                                                <div key={index} className="rounded-lg border border-border p-4">
                                                    <div className="mb-4 flex items-center justify-between">
                                                        <Badge variant="outline">Vínculo #{index + 1}</Badge>
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => removeVinculo(index)}
                                                            className="text-red-600 hover:text-red-700"
                                                        >
                                                            Remover
                                                        </Button>
                                                    </div>

                                                    <div className="grid gap-4 md:grid-cols-2">
                                                        <div className="space-y-2">
                                                            <Label>Empregador *</Label>
                                                            <Input
                                                                value={vinculo.empregador || ''}
                                                                onChange={(e) => updateVinculo(index, 'empregador', e.target.value)}
                                                                placeholder="Nome da empresa"
                                                                required
                                                            />
                                                        </div>

                                                        <div className="space-y-2">
                                                            <Label>CNPJ</Label>
                                                            <Input
                                                                value={vinculo.cnpj || ''}
                                                                onChange={(e) => updateVinculo(index, 'cnpj', e.target.value)}
                                                                placeholder="00.000.000/0000-00"
                                                            />
                                                        </div>

                                                        <div className="space-y-2">
                                                            <Label>Data de Início</Label>
                                                            <Input
                                                                type="date"
                                                                value={
                                                                    vinculo.data_inicio
                                                                        ? new Date(vinculo.data_inicio.split('/').reverse().join('-'))
                                                                              .toISOString()
                                                                              .split('T')[0]
                                                                        : ''
                                                                }
                                                                onChange={(e) => {
                                                                    const dateValue = e.target.value;
                                                                    const formattedDate = dateValue
                                                                        ? new Date(dateValue).toLocaleDateString('pt-BR')
                                                                        : '';
                                                                    updateVinculo(index, 'data_inicio', formattedDate);
                                                                }}
                                                            />
                                                        </div>

                                                        <div className="space-y-2">
                                                            <Label>Data de Fim</Label>
                                                            <Input
                                                                type="date"
                                                                value={
                                                                    vinculo.data_fim && vinculo.data_fim !== 'Atual'
                                                                        ? new Date(vinculo.data_fim.split('/').reverse().join('-'))
                                                                              .toISOString()
                                                                              .split('T')[0]
                                                                        : ''
                                                                }
                                                                onChange={(e) => {
                                                                    const dateValue = e.target.value;
                                                                    const formattedDate = dateValue
                                                                        ? new Date(dateValue).toLocaleDateString('pt-BR')
                                                                        : '';
                                                                    updateVinculo(index, 'data_fim', formattedDate);
                                                                }}
                                                                placeholder="Deixe vazio se ainda ativo"
                                                            />
                                                        </div>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <div className="py-8 text-center text-muted-foreground">
                                            <Building className="mx-auto mb-4 h-12 w-12 opacity-50" />
                                            <p>Nenhum vínculo empregatício adicionado</p>
                                            <p className="text-sm">Use o botão "Adicionar Vínculo" ou faça upload do CNIS</p>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>

                            {/* Submit Button */}
                            <div className="flex justify-end">
                                <Button type="submit" size="lg" className="gap-2">
                                    <CheckCircle className="h-4 w-4" />
                                    Criar Caso
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
