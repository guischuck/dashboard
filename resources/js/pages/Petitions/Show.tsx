import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Bot, Calendar, Clock, Download, FileText, User } from 'lucide-react';

interface Petition {
    id: number;
    title: string;
    content: string;
    type: 'pre_cadastrada' | 'ia';
    created_at: string;
    legal_case: {
        id: number;
        client_name: string;
        case_number: string;
        benefit_type?: string;
    };
    user: {
        name: string;
    };
}

interface Props {
    petition: Petition;
}

export default function Show({ petition }: Props) {
    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const getTypeBadge = (type: string) => {
        return type === 'pre_cadastrada' ? (
            <Badge variant="secondary" className="flex items-center gap-1">
                <FileText className="h-3 w-3" />
                Pré-cadastrada
            </Badge>
        ) : (
            <Badge variant="default" className="flex items-center gap-1">
                <Bot className="h-3 w-3" />
                IA
            </Badge>
        );
    };

    return (
        <AppLayout>
            <Head title={`Petição - ${petition.title}`} />

            <div className="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800">
                <div className="mx-auto max-w-7xl p-4 sm:p-6">
                    {/* Header */}
                    <div className="mb-6">
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div className="flex items-center gap-4">
                                <Link href={route('petitions.index')}>
                                    <Button variant="outline" size="sm">
                                        <ArrowLeft className="mr-2 h-4 w-4" />
                                        Voltar
                                    </Button>
                                </Link>
                                <div>
                                    <h1 className="flex items-center text-3xl font-bold">
                                        <FileText className="mr-2 inline-block" /> Visualizar Petição
                                    </h1>
                                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                        Petição criada em {formatDate(petition.created_at)}
                                    </p>
                                </div>
                            </div>
                            <div className="flex items-center gap-2">
                                {getTypeBadge(petition.type)}
                                <Link href={route('petitions.download', petition.id)}>
                                    <Button>
                                        <Download className="mr-2 h-4 w-4" />
                                        Download
                                    </Button>
                                </Link>
                            </div>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-4">
                        {/* Informações */}
                        <div className="space-y-4 lg:col-span-1">
                            <Card className="border-blue-200 bg-gradient-to-r from-blue-50 to-blue-100 dark:border-blue-700 dark:from-blue-900/20 dark:to-blue-800/20">
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-lg">
                                        <User className="h-5 w-5 text-blue-600" />
                                        Informações do Cliente
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div>
                                        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Nome do Cliente</label>
                                        <div className="text-sm font-medium text-gray-900 dark:text-white">{petition.legal_case.client_name}</div>
                                    </div>
                                    <div>
                                        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Número do Caso</label>
                                        <div className="text-sm text-gray-600 dark:text-gray-400">{petition.legal_case.case_number}</div>
                                    </div>
                                    {petition.legal_case.benefit_type && (
                                        <div>
                                            <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                Tipo de Benefício
                                            </label>
                                            <Badge variant="outline" className="text-xs">
                                                {petition.legal_case.benefit_type}
                                            </Badge>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>

                            <Card className="border-green-200 bg-gradient-to-r from-green-50 to-green-100 dark:border-green-700 dark:from-green-900/20 dark:to-green-800/20">
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-lg">
                                        <Clock className="h-5 w-5 text-green-600" />
                                        Detalhes da Petição
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div>
                                        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Criado por</label>
                                        <div className="flex items-center gap-2 text-sm text-gray-900 dark:text-white">
                                            <User className="h-4 w-4" />
                                            {petition.user.name}
                                        </div>
                                    </div>
                                    <div>
                                        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Data de Criação</label>
                                        <div className="flex items-center gap-2 text-sm text-gray-900 dark:text-white">
                                            <Calendar className="h-4 w-4" />
                                            {formatDate(petition.created_at)}
                                        </div>
                                    </div>
                                    <div>
                                        <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de Petição</label>
                                        {getTypeBadge(petition.type)}
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        {/* Conteúdo */}
                        <div className="lg:col-span-3">
                            <Card className="h-fit">
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-lg">
                                        <FileText className="h-5 w-5 text-purple-600" />
                                        Conteúdo da Petição
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="prose prose-sm dark:prose-invert max-w-none">
                                        <div className="overflow-x-auto rounded-lg border-2 border-gray-200 bg-white p-4 sm:p-6 dark:border-gray-700 dark:bg-gray-800">
                                            <pre className="font-mono text-sm leading-relaxed whitespace-pre-wrap">{petition.content}</pre>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
