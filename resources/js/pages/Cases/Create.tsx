import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ArrowLeft, Save, Upload, FileText, CheckCircle, AlertCircle } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { useState, useRef } from 'react';

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
        title: 'Novo Caso',
        href: '/cases/create',
    },
];

export default function CreateCase() {
    const [cnisFile, setCnisFile] = useState<File | null>(null);
    const [isProcessingCnis, setIsProcessingCnis] = useState(false);
    const [cnisData, setCnisData] = useState<any>(null);
    const [cnisError, setCnisError] = useState<string | null>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const { data, setData, post, processing, errors } = useForm({
        client_name: '',
        client_cpf: '',
        benefit_type: '',
        description: '',
    });

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        console.log('Arquivo selecionado:', file); // Debug
        
        if (file) {
            if (file.type === 'application/pdf') {
                setCnisFile(file);
                setCnisError(null);
                console.log('PDF válido selecionado:', file.name);
            } else {
                setCnisError('Por favor, selecione um arquivo PDF válido.');
                setCnisFile(null);
                console.log('Arquivo inválido:', file.type);
            }
        }
    };

    const handleFileClick = () => {
        fileInputRef.current?.click();
    };

    // Função de teste para verificar autenticação
    const testAuth = async () => {
        try {
            const response = await fetch('/test-no-auth');
            const result = await response.json();
            console.log('Test no auth:', result);
        } catch (error) {
            console.error('Test no auth error:', error);
        }
    };

    // Função de teste para verificar autenticação com middleware
    const testAuthWithMiddleware = async () => {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const response = await fetch('/api/test-auth', {
                headers: {
                    'X-CSRF-TOKEN': csrfToken || '',
                },
            });
            const result = await response.json();
            console.log('Test with auth:', result);
        } catch (error) {
            console.error('Test with auth error:', error);
        }
    };

    const handleCnisUpload = async () => {
        if (!cnisFile) return;

        setIsProcessingCnis(true);
        setCnisError(null);

        const formData = new FormData();
        formData.append('cnis_file', cnisFile);

        // Obtém o token CSRF
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        console.log('CSRF Token:', csrfToken); // Debug

        try {
            const response = await fetch('/api/process-cnis', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': csrfToken || '',
                },
            });

            console.log('Response status:', response.status); // Debug
            console.log('Response headers:', response.headers); // Debug

            if (response.ok) {
                const result = await response.json();
                console.log('Result:', result); // Debug
                setCnisData(result.data);
                
                // Preencher automaticamente os campos com os dados extraídos
                if (result.data) {
                    setData('client_name', result.data.client_name || '');
                    setData('client_cpf', result.data.client_cpf || '');
                    setData('benefit_type', result.data.benefit_type || '');
                    
                    // Gera descrição automática baseada nos vínculos
                    if (result.data.vinculos_empregaticios && result.data.vinculos_empregaticios.length > 0) {
                        const vinculos = result.data.vinculos_empregaticios;
                        const totalVinculos = vinculos.length;
                        const ultimoVinculo = vinculos[vinculos.length - 1];
                        
                        let descricao = `Caso de ${result.data.benefit_type?.replace(/_/g, ' ') || 'benefício previdenciário'} para ${result.data.client_name}.\n\n`;
                        descricao += `Total de vínculos empregatícios: ${totalVinculos}\n`;
                        descricao += `Último empregador: ${ultimoVinculo.empregador}\n`;
                        descricao += `Último salário: R$ ${ultimoVinculo.salario}\n`;
                        
                        if (vinculos.length > 1) {
                            descricao += `\nHistórico de vínculos:\n`;
                            vinculos.forEach((vinculo: any, index: number) => {
                                descricao += `${index + 1}. ${vinculo.empregador} - ${vinculo.data_inicio} a ${vinculo.data_fim || 'Atual'} - R$ ${vinculo.salario}\n`;
                            });
                        }
                        
                        setData('description', descricao);
                    } else {
                        setData('description', `Caso de ${result.data.benefit_type?.replace(/_/g, ' ') || 'benefício previdenciário'} para ${result.data.client_name}.`);
                    }
                }
            } else {
                // Log da resposta completa para debug
                const responseText = await response.text();
                console.error('Response text:', responseText); // Debug
                
                try {
                    const error = JSON.parse(responseText);
                    console.error('Error response:', error); // Debug
                    setCnisError(error.error || 'Erro ao processar o CNIS');
                } catch (parseError) {
                    console.error('Parse error:', parseError); // Debug
                    setCnisError('Erro ao processar o arquivo CNIS: Resposta inválida do servidor');
                }
            }
        } catch (error) {
            console.error('Fetch error:', error); // Debug
            setCnisError('Erro ao processar o arquivo CNIS: ' + error);
        } finally {
            setIsProcessingCnis(false);
        }
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        
        // Se temos dados do CNIS, vamos gerar a descrição com IA
        if (cnisData && cnisData.vinculos_empregaticios) {
            try {
                const response = await fetch('/api/generate-case-description', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: JSON.stringify({
                        client_name: data.client_name,
                        client_cpf: data.client_cpf,
                        benefit_type: data.benefit_type,
                        vinculos_empregaticios: cnisData.vinculos_empregaticios,
                    }),
                });

                if (response.ok) {
                    const result = await response.json();
                    setData('description', result.description);
                }
            } catch (error) {
                console.error('Erro ao gerar descrição:', error);
            }
        }

        post('/cases');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Novo Caso - Sistema Jurídico" />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Link href="/cases">
                            <Button variant="outline" size="sm">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Voltar
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-3xl font-bold">Novo Caso</h1>
                            <p className="text-muted-foreground">Crie um novo caso jurídico com extração de CNIS</p>
                        </div>
                    </div>
                </div>

                {/* CNIS Upload Section */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <FileText className="h-5 w-5" />
                            Upload do CNIS
                        </CardTitle>
                        <CardDescription>
                            Faça upload do CNIS em PDF para extração automática dos dados do cliente e vínculos empregatícios
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-4">
                            {/* Botões de teste */}
                            <div className="flex space-x-2 mb-4">
                                <Button onClick={testAuth} variant="outline" size="sm">
                                    Testar Rota Sem Auth
                                </Button>
                                <Button onClick={testAuthWithMiddleware} variant="outline" size="sm">
                                    Testar Rota Com Auth
                                </Button>
                            </div>
                            
                            <div className="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                                <Upload className="h-8 w-8 text-gray-400 mx-auto mb-2" />
                                <p className="text-sm text-gray-600 mb-2">
                                    Arraste e solte o arquivo CNIS aqui ou clique para selecionar
                                </p>
                                
                                {/* Input de arquivo oculto */}
                                <input
                                    ref={fileInputRef}
                                    type="file"
                                    accept=".pdf"
                                    onChange={handleFileChange}
                                    className="hidden"
                                />
                                
                                {/* Botão para abrir o seletor de arquivo */}
                                <Button 
                                    variant="outline" 
                                    size="sm"
                                    onClick={handleFileClick}
                                    type="button"
                                >
                                    Selecionar Arquivo PDF
                                </Button>
                            </div>

                            {cnisFile && (
                                <div className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div className="flex items-center space-x-2">
                                        <FileText className="h-4 w-4 text-blue-600" />
                                        <span className="text-sm font-medium">{cnisFile.name}</span>
                                        <span className="text-xs text-gray-500">({(cnisFile.size / 1024 / 1024).toFixed(2)} MB)</span>
                                    </div>
                                    <Button
                                        onClick={handleCnisUpload}
                                        disabled={isProcessingCnis}
                                        size="sm"
                                    >
                                        {isProcessingCnis ? 'Processando...' : 'Extrair Dados'}
                                    </Button>
                                </div>
                            )}

                            {cnisError && (
                                <div className="flex items-center space-x-2 p-3 bg-red-50 border border-red-200 rounded-lg">
                                    <AlertCircle className="h-4 w-4 text-red-600" />
                                    <span className="text-sm text-red-600">{cnisError}</span>
                                </div>
                            )}

                            {cnisData && (
                                <div className="space-y-4">
                                    <div className="flex items-center space-x-2 p-3 bg-green-50 border border-green-200 rounded-lg">
                                        <CheckCircle className="h-4 w-4 text-green-600" />
                                        <span className="text-sm text-green-600">
                                            Dados extraídos com sucesso! Os campos foram preenchidos automaticamente.
                                        </span>
                                    </div>

                                    {/* Vínculos Empregatícios Extraídos */}
                                    {cnisData.vinculos_empregaticios && cnisData.vinculos_empregaticios.length > 0 && (
                                        <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                            <h4 className="font-medium text-blue-900 mb-3">Vínculos Empregatícios Extraídos:</h4>
                                            <div className="space-y-2">
                                                {cnisData.vinculos_empregaticios.map((vinculo: any, index: number) => (
                                                    <div key={index} className="bg-white p-3 rounded border">
                                                        <div className="grid grid-cols-2 gap-2 text-sm text-gray-900">
                                                            <div><strong>Empregador:</strong> {vinculo.empregador}</div>
                                                            <div><strong>Início:</strong> {vinculo.data_inicio}</div>
                                                            <div><strong>Fim:</strong> {vinculo.data_fim || 'Atual'}</div>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* Form */}
                <Card>
                    <CardHeader>
                        <CardTitle>Informações do Caso</CardTitle>
                        <CardDescription>
                            Dados do cliente e tipo de benefício
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="grid gap-6 md:grid-cols-2">
                                {/* Cliente */}
                                <div className="space-y-2">
                                    <Label htmlFor="client_name">Nome do Cliente *</Label>
                                    <Input
                                        id="client_name"
                                        value={data.client_name}
                                        onChange={(e) => setData('client_name', e.target.value)}
                                        placeholder="Nome completo do cliente"
                                    />
                                    {errors.client_name && (
                                        <p className="text-sm text-red-600">{errors.client_name}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="client_cpf">CPF do Cliente *</Label>
                                    <Input
                                        id="client_cpf"
                                        value={data.client_cpf}
                                        onChange={(e) => setData('client_cpf', e.target.value)}
                                        placeholder="000.000.000-00"
                                    />
                                    {errors.client_cpf && (
                                        <p className="text-sm text-red-600">{errors.client_cpf}</p>
                                    )}
                                </div>

                                {/* Tipo de Benefício */}
                                <div className="space-y-2">
                                    <Label htmlFor="benefit_type">Tipo de Benefício *</Label>
                                    <select
                                        id="benefit_type"
                                        value={data.benefit_type}
                                        onChange={(e) => setData('benefit_type', e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    >
                                        <option value="">Selecione o tipo de benefício</option>
                                        <option value="aposentadoria_por_idade">Aposentadoria por Idade</option>
                                        <option value="aposentadoria_por_tempo_contribuicao">Aposentadoria por Tempo de Contribuição</option>
                                        <option value="aposentadoria_professor">Aposentadoria Professor</option>
                                        <option value="aposentadoria_pcd">Aposentadoria PCD</option>
                                        <option value="aposentadoria_especial">Aposentadoria Especial</option>
                                    </select>
                                    {errors.benefit_type && (
                                        <p className="text-sm text-red-600">{errors.benefit_type}</p>
                                    )}
                                </div>
                            </div>

                            {/* Descrição */}
                            <div className="space-y-2">
                                <Label htmlFor="description">Descrição do Caso</Label>
                                <textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    placeholder="A descrição será gerada automaticamente com base nos vínculos empregatícios extraídos do CNIS..."
                                    rows={6}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                                <p className="text-xs text-gray-500">
                                    A descrição será gerada automaticamente pela IA com base nos vínculos empregatícios extraídos do CNIS.
                                </p>
                            </div>

                            {/* Submit Button */}
                            <div className="flex justify-end">
                                <Button type="submit" disabled={processing}>
                                    <Save className="mr-2 h-4 w-4" />
                                    {processing ? 'Criando Caso...' : 'Criar Caso'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
} 