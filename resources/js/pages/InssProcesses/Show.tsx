import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Calendar, Clock, FileText, User, Building } from 'lucide-react';

interface HistoricoSituacao {
    id: number;
    situacao_anterior: string;
    situacao_atual: string;
    data_mudanca: string;
}

interface Processo {
    id: number;
    protocolo: string;
    nome: string;
    cpf: string;
    servico: string;
    situacao: string;
    situacao_anterior?: string;
    protocolado_em: string;
    ultima_atualizacao: string;
    criado_em: string;
    atualizado_em: string;
    historicoSituacoes: HistoricoSituacao[];
    company: {
        id: number;
        name: string;
    };
}

interface ProcessoShowProps {
    processo: Processo;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Processos INSS',
        href: '/inss-processes',
    },
    {
        title: 'Detalhes',
        href: '#',
    },
];

export default function ProcessoShow({ processo }: ProcessoShowProps) {
    const getStatusColor = (status: string) => {
        const normalizedStatus = status.toLowerCase();
        
        if (normalizedStatus.includes('análise') || normalizedStatus.includes('processamento') || normalizedStatus.includes('andamento')) {
            return 'bg-blue-100 text-blue-800 border-blue-200';
        }
        if (normalizedStatus.includes('deferido') || normalizedStatus.includes('concluído') || normalizedStatus.includes('aprovado')) {
            return 'bg-green-100 text-green-800 border-green-200';
        }
        if (normalizedStatus.includes('exigência') || normalizedStatus.includes('pendente')) {
            return 'bg-orange-100 text-orange-800 border-orange-200';
        }
        if (normalizedStatus.includes('indeferido') || normalizedStatus.includes('rejeitado')) {
            return 'bg-red-100 text-red-800 border-red-200';
        }
        
        return 'bg-gray-100 text-gray-800 border-gray-200';
    };

    const formatDateTime = (dateString: string) => {
        if (!dateString) return 'N/A';
        return new Date(dateString).toLocaleDateString('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const formatDate = (dateString: string) => {
        if (!dateString) return 'N/A';
        return new Date(dateString).toLocaleDateString('pt-BR');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Processo ${processo.protocolo} - Sistema Jurídico`} />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Link href="/inss-processes">
                            <Button variant="outline" size="sm">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Voltar aos Processos
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Process Info */}
                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    {/* Main Info */}
                    <div className="space-y-6 lg:col-span-2">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center space-x-2">
                                    <FileText className="h-5 w-5" />
                                    <span>Informações do Processo</span>
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <p className="text-sm font-medium text-gray-600">Protocolo</p>
                                        <p className="font-mono text-lg">{processo.protocolo}</p>
                                    </div>
                                    <div>
                                        <p className="text-sm font-medium text-gray-600">Status Atual</p>
                                        <Badge 
                                            variant="outline" 
                                            className={`mt-1 ${getStatusColor(processo.situacao)}`}
                                        >
                                            {processo.situacao}
                                        </Badge>
                                    </div>
                                    <div>
                                        <p className="text-sm font-medium text-gray-600">Serviço</p>
                                        <p>{processo.servico}</p>
                                    </div>
                                    <div>
                                        <p className="text-sm font-medium text-gray-600">Status Anterior</p>
                                        <p>{processo.situacao_anterior || 'N/A'}</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center space-x-2">
                                    <User className="h-5 w-5" />
                                    <span>Dados do Cliente</span>
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <p className="text-sm font-medium text-gray-600">Nome</p>
                                        <p className="text-lg">{processo.nome}</p>
                                    </div>
                                    <div>
                                        <p className="text-sm font-medium text-gray-600">CPF</p>
                                        <p className="font-mono">{processo.cpf}</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Histórico de Mudanças */}
                        {processo.historicoSituacoes && processo.historicoSituacoes.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center space-x-2">
                                        <Clock className="h-5 w-5" />
                                        <span>Histórico de Mudanças</span>
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="rounded-md border">
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead>Data</TableHead>
                                                    <TableHead>Situação Anterior</TableHead>
                                                    <TableHead>Nova Situação</TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {processo.historicoSituacoes.map((historico) => (
                                                    <TableRow key={historico.id}>
                                                        <TableCell>
                                                            {formatDateTime(historico.data_mudanca)}
                                                        </TableCell>
                                                        <TableCell>
                                                            <Badge variant="outline" className="bg-gray-100 text-gray-800">
                                                                {historico.situacao_anterior || 'N/A'}
                                                            </Badge>
                                                        </TableCell>
                                                        <TableCell>
                                                            <Badge 
                                                                variant="outline" 
                                                                className={getStatusColor(historico.situacao_atual)}
                                                            >
                                                                {historico.situacao_atual}
                                                            </Badge>
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

                    {/* Sidebar */}
                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center space-x-2">
                                    <Calendar className="h-5 w-5" />
                                    <span>Datas Importantes</span>
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <p className="text-sm font-medium text-gray-600">Protocolado em</p>
                                    <p>{formatDate(processo.protocolado_em)}</p>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-gray-600">Última Atualização</p>
                                    <p>{formatDateTime(processo.ultima_atualizacao)}</p>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-gray-600">Sincronizado em</p>
                                    <p>{formatDateTime(processo.criado_em)}</p>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-gray-600">Última Modificação</p>
                                    <p>{formatDateTime(processo.atualizado_em)}</p>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center space-x-2">
                                    <Building className="h-5 w-5" />
                                    <span>Empresa</span>
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-lg">{processo.company.name}</p>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
} 