import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Building, ChevronDown, ChevronUp, Copy, FileText } from 'lucide-react';
import { useEffect, useState } from 'react';

interface EmploymentRelationship {
    id: number;
    employer_name: string;
    employer_cnpj: string;
    start_date: string;
    end_date: string | null;
    salary: number | null;
    is_active: boolean;
    notes: string;
    created_at: string;
    cargo?: string;
    documentos?: string;
    observacoes?: string;
}

interface Case {
    id: number;
    case_number: string;
    client_name: string;
    client_cpf: string;
    employment_relationships: EmploymentRelationship[];
}

interface VinculosProps {
    case: Case;
}

interface EditableDateProps {
    value: string | null;
    onChange: (val: string) => void;
}

interface EditableTextProps {
    value: string;
    onChange: (val: string) => void;
    placeholder?: string;
}

function EditableDate({ value, onChange }: EditableDateProps) {
    const [editing, setEditing] = useState(false);
    const [val, setVal] = useState(value || '');
    const [saving, setSaving] = useState(false);

    const handleSave = async () => {
        if (val !== value) {
            setSaving(true);
            await onChange(val);
            setSaving(false);
        }
        setEditing(false);
    };

    return editing ? (
        <input
            type="date"
            value={val ? val.slice(0, 10) : ''}
            onChange={(e) => setVal(e.target.value)}
            onBlur={handleSave}
            onKeyDown={(e) => e.key === 'Enter' && handleSave()}
            className={`rounded border px-2 py-1 ${saving ? 'opacity-50' : ''}`}
            disabled={saving}
            autoFocus
        />
    ) : (
        <span onClick={() => setEditing(true)} className="cursor-pointer text-blue-600 underline hover:text-blue-800 dark:text-blue-300">
            {value ? new Date(value).toLocaleDateString('pt-BR') : '---'}
        </span>
    );
}

function EditableText({ value, onChange, placeholder }: EditableTextProps) {
    const [editing, setEditing] = useState(false);
    const [val, setVal] = useState(value);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        setVal(value);
    }, [value]);

    const handleSave = async () => {
        if (val !== value) {
            setSaving(true);
            await onChange(val);
            setSaving(false);
        }
        setEditing(false);
    };

    return editing ? (
        <input
            type="text"
            value={val}
            placeholder={placeholder}
            onChange={(e) => setVal(e.target.value)}
            onBlur={handleSave}
            onKeyDown={(e) => e.key === 'Enter' && handleSave()}
            className={`w-full rounded border px-2 py-1 ${saving ? 'opacity-50' : ''}`}
            disabled={saving}
            autoFocus
        />
    ) : (
        <span onClick={() => setEditing(true)} className="cursor-pointer text-blue-600 underline hover:text-blue-800 dark:text-blue-300">
            {value || <span className="text-muted-foreground">{placeholder}</span>}
        </span>
    );
}

export default function Vinculos({ case: case_ }: VinculosProps) {
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
            title: 'Vínculos Empregatícios',
            href: `/cases/${case_.id}/vinculos`,
        },
    ];

    const formatDate = (dateString: string | null) => {
        if (!dateString) return 'Em andamento';
        return new Date(dateString).toLocaleDateString('pt-BR');
    };

    const formatSalary = (salary: number | null) => {
        if (!salary) return 'Não informado';
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL',
        }).format(salary);
    };

    const formatCNPJ = (cnpj: string) => {
        if (!cnpj) return 'Não informado';
        // Remove caracteres não numéricos
        const cleanCNPJ = cnpj.replace(/\D/g, '');

        // Formata CNPJ: XX.XXX.XXX/XXXX-XX
        if (cleanCNPJ.length === 14) {
            return cleanCNPJ.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5');
        }

        return cnpj; // Retorna original se não conseguir formatar
    };

    const [employmentRelationships, setEmploymentRelationships] = useState(case_.employment_relationships);
    const [expanded, setExpanded] = useState<number | null>(null);
    const [modalOpen, setModalOpen] = useState(false);
    const [modalVinculo, setModalVinculo] = useState<EmploymentRelationship | null>(null);
    const [tentativasData, setTentativasData] = useState<{ [key: string]: any }>({});

    // Carregar dados das tentativas existentes
    useEffect(() => {
        const loadTentativasData = async () => {
            try {
                for (const relationship of employmentRelationships) {
                    const response = await fetch(`/api/employment-relationships/${relationship.id}/tentativas`);
                    if (response.ok) {
                        const tentativas = await response.json();
                        tentativas.forEach((tentativa: any) => {
                            const fields = ['endereco', 'rastreamento', 'data_envio', 'retorno', 'email', 'telefone'];
                            fields.forEach((field) => {
                                if (tentativa[field]) {
                                    const key = `${relationship.id}_${tentativa.tentativa_num}_${field}`;
                                    setTentativasData((prev) => ({
                                        ...prev,
                                        [key]: tentativa[field],
                                    }));
                                }
                            });
                        });
                    }
                }
            } catch (error) {
                console.error('Erro ao carregar tentativas:', error);
            }
        };

        loadTentativasData();
    }, [employmentRelationships]);

    const handleDateChange = (idx: number, field: 'start_date' | 'end_date', value: string) => {
        const updatedRelationships = [...employmentRelationships];
        updatedRelationships[idx] = {
            ...updatedRelationships[idx],
            [field]: value,
        };
        setEmploymentRelationships(updatedRelationships);
    };

    const handleFieldChange = (idx: number, field: 'cargo' | 'documentos' | 'observacoes', value: string) => {
        const updatedRelationships = [...employmentRelationships];
        updatedRelationships[idx] = {
            ...updatedRelationships[idx],
            [field]: value,
        };
        setEmploymentRelationships(updatedRelationships);
    };

    const toggleExpand = (idx: number) => {
        setExpanded(expanded === idx ? null : idx);
    };

    const toggleStatus = async (idx: number) => {
        const updated = [...employmentRelationships];
        const newStatus = !updated[idx].is_active;
        updated[idx] = {
            ...updated[idx],
            is_active: newStatus,
        };
        setEmploymentRelationships(updated);

        // Salvar no backend
        try {
            console.log('Enviando toggle status:', {
                id: updated[idx].id,
                is_active: newStatus,
            });

            const response = await fetch(`/api/employment-relationships/${updated[idx].id}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ is_active: newStatus }),
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            console.log('Status alterado com sucesso:', result);
        } catch (error) {
            console.error('Erro ao salvar status:', error);
            alert('Erro ao salvar status. Tente novamente.');
            // Reverter mudança local em caso de erro
            const reverted = [...employmentRelationships];
            reverted[idx].is_active = !newStatus;
            setEmploymentRelationships(reverted);
        }
    };

    const removeVinculo = (idx: number) => {
        const updated = [...employmentRelationships];
        updated.splice(idx, 1);
        setEmploymentRelationships(updated);
    };

    const openModal = (vinculo: EmploymentRelationship) => {
        setModalVinculo(vinculo);
        setModalOpen(true);
    };
    const closeModal = () => setModalOpen(false);

    const saveField = async (idx: number, field: string, value: string) => {
        // Atualiza localmente
        const updated = [...employmentRelationships];
        updated[idx] = { ...updated[idx], [field]: value };
        setEmploymentRelationships(updated);

        // Salva no backend
        try {
            const response = await fetch(`/api/employment-relationships/${updated[idx].id}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ [field]: value }),
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            console.log('Campo salvo com sucesso:', result);
        } catch (error) {
            console.error('Erro ao salvar campo:', error);
            alert(`Erro ao salvar ${field}. Tente novamente.`);
        }
    };

    const saveTentativa = async (idx: number, tentativa: number, field: string, value: string) => {
        // Atualizar estado local
        const key = `${employmentRelationships[idx].id}_${tentativa}_${field}`;
        setTentativasData((prev) => ({
            ...prev,
            [key]: value,
        }));

        try {
            console.log('Salvando tentativa:', {
                employmentId: employmentRelationships[idx].id,
                tentativa,
                field,
                value,
                url: `/api/employment-relationships/${employmentRelationships[idx].id}/tentativas/${tentativa}`,
            });

            const response = await fetch(`/api/employment-relationships/${employmentRelationships[idx].id}/tentativas/${tentativa}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ [field]: value }),
            });

            if (!response.ok) {
                const errorText = await response.text();
                console.error('Erro HTTP:', response.status, errorText);
                throw new Error(`HTTP error! status: ${response.status} - ${errorText}`);
            }

            const result = await response.json();
            console.log('Tentativa salva com sucesso:', result);
        } catch (error) {
            console.error('Erro ao salvar tentativa:', error);
            const errorMessage = error instanceof Error ? error.message : 'Erro desconhecido';
            alert(`Erro ao salvar ${field} da tentativa ${tentativa}. Tente novamente. Erro: ${errorMessage}`);
        }
    };

    const getTentativaValue = (relationshipId: number, tentativa: number, field: string): string => {
        const key = `${relationshipId}_${tentativa}_${field}`;
        return tentativasData[key] || '';
    };

    const updateTentativaValue = (relationshipId: number, tentativa: number, field: string, value: string) => {
        const key = `${relationshipId}_${tentativa}_${field}`;
        setTentativasData((prev) => ({
            ...prev,
            [key]: value,
        }));
    };

    const gerarTextoCarta = (vinculo: EmploymentRelationship) => {
        return `NOTIFICAÇÃO EXTRAJUDICIAL REQUERIMENTO DE PPP E LTCAT

${vinculo.employer_name}
CNPJ: ${formatCNPJ(vinculo.employer_cnpj)}

${case_.client_name}, neste ato representado por seu advogado que este subscreve, vem, por meio deste, solicitar a emissão de PPP (Perfil Profissiográfico Previdenciário) com o respectivo LTCAT (Laudo Técnico das Condições Ambientais de Trabalho), para fins de instrução no processo de aposentadoria em face do Instituto Nacional do Seguro Social - INSS.

Vale salientar que o PPP somente é válido diante do preenchimento de todos os campos, principalmente aos que se referem à exposição dos agentes nocivos, indicação do responsável técnico pelos registros ambientais (Tema 208 - TNU), carimbo e assinatura do responsável da empresa. Ressalta-se, ainda, que o documento deve seguir o modelo estabelecido pela Instrução normativa nº 133/2022 e 128/2022.

Requer também que o PPP venha acompanhado de autorização que identifique o representante legal responsável pela assinatura do laudo ou a cópia do contrato social da empresa, quando o representante legal for o sócio da mesma.

Os documentos deverão ser encaminhados por e-mail para extrajudicial@koetzadvocacia.com.br

DADOS DO REQUERENTE:
Nome: ${case_.client_name}
CPF: ${case_.client_cpf}
Período: ${formatDate(vinculo.start_date)} a ${formatDate(vinculo.end_date)}

Certa de vossa atenção,
aguardo retorno. Atenciosamente,
Eduardo Koetz - OAB/SC 42.934`;
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Vínculos - ${case_.case_number} - Sistema Jurídico`} />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <div className="flex items-center space-x-4">
                            <Link href={`/cases/${case_.id}`}>
                                <Button variant="outline" size="sm">
                                    <ArrowLeft className="mr-2 h-4 w-4" />
                                    Voltar ao Caso
                                </Button>
                            </Link>
                        </div>
                        <h1 className="mt-4 text-3xl font-bold">Vínculos Empregatícios</h1>
                        <p className="text-muted-foreground">
                            Caso: {case_.case_number} - {case_.client_name}
                        </p>
                    </div>
                </div>

                {/* Case Info */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center space-x-2">
                            <FileText className="h-5 w-5" />
                            <span>Informações do Cliente</span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <p className="text-sm font-medium text-gray-600">Nome do Cliente</p>
                                <p className="text-lg">{case_.client_name}</p>
                            </div>
                            <div>
                                <p className="text-sm font-medium text-gray-600">CPF</p>
                                <p className="text-lg">{case_.client_cpf}</p>
                            </div>
                            <div>
                                <p className="text-sm font-medium text-gray-600">Número do Caso</p>
                                <p className="font-mono text-lg">{case_.case_number}</p>
                            </div>
                            <div>
                                <p className="text-sm font-medium text-gray-600">Total de Vínculos</p>
                                <p className="text-lg font-bold text-blue-600">{case_.employment_relationships.length}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Employment Relationships Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Lista de Vínculos Empregatícios</CardTitle>
                        <CardDescription>Edite os dados e eles serão salvos automaticamente</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Empregador</TableHead>
                                        <TableHead>CNPJ</TableHead>
                                        <TableHead>Data Início</TableHead>
                                        <TableHead>Data Fim</TableHead>
                                        <TableHead>Cargo</TableHead>
                                        <TableHead>Documentos</TableHead>
                                        <TableHead>Observações</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {employmentRelationships.map((relationship, idx) => (
                                        <TableRow key={relationship.id}>
                                            <TableCell>{relationship.employer_name}</TableCell>
                                            <TableCell>
                                                <code className="rounded bg-muted px-2 py-1 text-sm text-foreground">
                                                    {formatCNPJ(relationship.employer_cnpj)}
                                                </code>
                                            </TableCell>
                                            <TableCell>
                                                <EditableDate value={relationship.start_date} onChange={(val) => saveField(idx, 'start_date', val)} />
                                            </TableCell>
                                            <TableCell>
                                                <EditableDate value={relationship.end_date} onChange={(val) => saveField(idx, 'end_date', val)} />
                                            </TableCell>
                                            <TableCell>
                                                <EditableText
                                                    value={relationship.cargo || ''}
                                                    onChange={(val) => saveField(idx, 'cargo', val)}
                                                    placeholder="Cargo"
                                                />
                                            </TableCell>
                                            <TableCell>
                                                <EditableText
                                                    value={relationship.documentos || ''}
                                                    onChange={(val) => saveField(idx, 'documentos', val)}
                                                    placeholder="Documentos"
                                                />
                                            </TableCell>
                                            <TableCell>
                                                <EditableText
                                                    value={relationship.observacoes || ''}
                                                    onChange={(val) => saveField(idx, 'observacoes', val)}
                                                    placeholder="Observações"
                                                />
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>

                {/* Employment Relationships Cards */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center space-x-2">
                            <Building className="h-5 w-5" />
                            <span>Vínculos Empregatícios</span>
                        </CardTitle>
                        <CardDescription>Lista de todos os vínculos empregatícios extraídos do CNIS</CardDescription>
                    </CardHeader>
                    <CardContent>
                        {case_.employment_relationships.length > 0 ? (
                            <div className="overflow-x-auto">
                                {employmentRelationships.map((relationship, idx) => (
                                    <div key={relationship.id} className="mb-4 rounded-lg border bg-card shadow-sm">
                                        <div className="flex cursor-pointer items-center justify-between px-4 py-3" onClick={() => toggleExpand(idx)}>
                                            <span className="text-lg font-bold">{relationship.employer_name}</span>
                                            <div className="ml-auto flex gap-2">
                                                <Button
                                                    size="sm"
                                                    variant={relationship.is_active ? 'secondary' : 'default'}
                                                    className={
                                                        relationship.is_active
                                                            ? 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200'
                                                            : 'bg-green-100 text-green-800 hover:bg-green-200'
                                                    }
                                                    onClick={(e) => {
                                                        e.stopPropagation();
                                                        toggleStatus(idx);
                                                    }}
                                                >
                                                    {relationship.is_active ? 'Pendente' : 'Concluído'}
                                                </Button>
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    className="border-blue-300 text-blue-700 hover:bg-blue-50"
                                                    onClick={(e) => {
                                                        e.stopPropagation();
                                                        openModal(relationship);
                                                    }}
                                                >
                                                    Gerar Carta
                                                </Button>
                                                <Button
                                                    size="sm"
                                                    variant="destructive"
                                                    onClick={(e) => {
                                                        e.stopPropagation();
                                                        removeVinculo(idx);
                                                    }}
                                                >
                                                    Remover
                                                </Button>
                                                <span>{expanded === idx ? <ChevronUp /> : <ChevronDown />}</span>
                                            </div>
                                        </div>
                                        {expanded === idx && (
                                            <div className="border-t bg-muted p-4">
                                                <div className="mb-2 font-semibold">Tentativas de Contato</div>
                                                <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                                                    {[1, 2, 3].map((tentativa) => (
                                                        <div key={tentativa} className="rounded-lg border bg-background p-4">
                                                            <div className="mb-2 font-medium">Tentativa {tentativa}</div>
                                                            <div className="mb-2">
                                                                <label className="mb-1 block text-sm font-medium">Endereço</label>
                                                                <input
                                                                    type="text"
                                                                    className="w-full rounded border px-2 py-1"
                                                                    placeholder="Rua, número, cidade..."
                                                                    value={getTentativaValue(relationship.id, tentativa, 'endereco')}
                                                                    onChange={(e) =>
                                                                        updateTentativaValue(relationship.id, tentativa, 'endereco', e.target.value)
                                                                    }
                                                                    onBlur={(e) => saveTentativa(idx, tentativa, 'endereco', e.target.value)}
                                                                />
                                                            </div>
                                                            <div className="mb-2">
                                                                <label className="mb-1 block text-sm font-medium">Cód. Rastreio</label>
                                                                <input
                                                                    type="text"
                                                                    className="w-full rounded border px-2 py-1"
                                                                    placeholder="XX123456789BR"
                                                                    value={getTentativaValue(relationship.id, tentativa, 'rastreamento')}
                                                                    onChange={(e) =>
                                                                        updateTentativaValue(
                                                                            relationship.id,
                                                                            tentativa,
                                                                            'rastreamento',
                                                                            e.target.value,
                                                                        )
                                                                    }
                                                                    onBlur={(e) => saveTentativa(idx, tentativa, 'rastreamento', e.target.value)}
                                                                />
                                                            </div>
                                                            <div className="mb-2">
                                                                <label className="mb-1 block text-sm font-medium">Data de Envio</label>
                                                                <input
                                                                    type="date"
                                                                    className="w-full rounded border px-2 py-1"
                                                                    value={getTentativaValue(relationship.id, tentativa, 'data_envio')}
                                                                    onChange={(e) =>
                                                                        updateTentativaValue(relationship.id, tentativa, 'data_envio', e.target.value)
                                                                    }
                                                                    onBlur={(e) => saveTentativa(idx, tentativa, 'data_envio', e.target.value)}
                                                                />
                                                            </div>
                                                            <div className="mb-2">
                                                                <label className="mb-1 block text-sm font-medium">Retorno</label>
                                                                <select
                                                                    className="w-full rounded border px-2 py-1"
                                                                    value={getTentativaValue(relationship.id, tentativa, 'retorno') || 'Aguardando'}
                                                                    onChange={(e) => {
                                                                        updateTentativaValue(relationship.id, tentativa, 'retorno', e.target.value);
                                                                        saveTentativa(idx, tentativa, 'retorno', e.target.value);
                                                                    }}
                                                                >
                                                                    <option value="Aguardando">Aguardando</option>
                                                                    <option value="Recebido">Recebido</option>
                                                                    <option value="Devolvido">Devolvido</option>
                                                                    <option value="Não Localizado">Não Localizado</option>
                                                                </select>
                                                            </div>
                                                            <div className="mb-2">
                                                                <label className="mb-1 block text-sm font-medium">Email</label>
                                                                <input
                                                                    type="email"
                                                                    className="w-full rounded border px-2 py-1"
                                                                    placeholder="contato@empresa.com"
                                                                    value={getTentativaValue(relationship.id, tentativa, 'email')}
                                                                    onChange={(e) =>
                                                                        updateTentativaValue(relationship.id, tentativa, 'email', e.target.value)
                                                                    }
                                                                    onBlur={(e) => saveTentativa(idx, tentativa, 'email', e.target.value)}
                                                                />
                                                            </div>
                                                            <div>
                                                                <label className="mb-1 block text-sm font-medium">Telefone</label>
                                                                <input
                                                                    type="text"
                                                                    className="w-full rounded border px-2 py-1"
                                                                    placeholder="(XX) XXXXX-XXXX"
                                                                    value={getTentativaValue(relationship.id, tentativa, 'telefone')}
                                                                    onChange={(e) =>
                                                                        updateTentativaValue(relationship.id, tentativa, 'telefone', e.target.value)
                                                                    }
                                                                    onBlur={(e) => saveTentativa(idx, tentativa, 'telefone', e.target.value)}
                                                                />
                                                            </div>
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="py-12 text-center">
                                <Building className="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
                                <h3 className="mb-2 text-lg font-medium">Nenhum vínculo encontrado</h3>
                                <p className="text-muted-foreground">Este caso ainda não possui vínculos empregatícios cadastrados.</p>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Modal de Gerar Carta */}
                <Dialog open={modalOpen} onOpenChange={setModalOpen}>
                    <DialogContent className="max-h-[90vh] w-full max-w-2xl overflow-y-auto md:max-w-4xl">
                        <DialogHeader>
                            <DialogTitle className="text-lg md:text-xl">Notificação Extrajudicial - Requerimento de PPP e LTCAT</DialogTitle>
                            <DialogDescription className="text-sm md:text-base">
                                Carta para: <b>{modalVinculo?.employer_name}</b> - CNPJ: <b>{formatCNPJ(modalVinculo?.employer_cnpj || '')}</b>
                            </DialogDescription>
                        </DialogHeader>
                        <div className="my-4 rounded-lg border bg-white p-4 font-mono text-xs leading-relaxed md:p-6 md:text-sm">
                            <div className="mb-4 text-center md:mb-6">
                                <h2 className="mb-2 text-base font-bold md:text-lg">NOTIFICAÇÃO EXTRAJUDICIAL REQUERIMENTO DE PPP E LTCAT</h2>
                            </div>

                            <div className="mb-4">
                                <p className="font-bold">{modalVinculo?.employer_name}</p>
                                <p>CNPJ: {formatCNPJ(modalVinculo?.employer_cnpj || '')}</p>
                            </div>

                            <div className="mb-6 space-y-4 text-justify">
                                <p>
                                    <strong>{case_.client_name}</strong>, neste ato representado por seu advogado que este subscreve, vem, por meio
                                    deste, solicitar a emissão de PPP (Perfil Profissiográfico Previdenciário) com o respectivo LTCAT (Laudo Técnico
                                    das Condições Ambientais de Trabalho), para fins de instrução no processo de aposentadoria em face do Instituto
                                    Nacional do Seguro Social - INSS.
                                </p>

                                <p>
                                    Vale salientar que o PPP somente é válido diante do preenchimento de todos os campos, principalmente aos que se
                                    referem à exposição dos agentes nocivos, indicação do responsável técnico pelos registros ambientais (Tema 208 -
                                    TNU), carimbo e assinatura do responsável da empresa. Ressalta-se, ainda, que o documento deve seguir o modelo
                                    estabelecido pela Instrução normativa nº 133/2022 e 128/2022.
                                </p>

                                <p>
                                    Requer também que o PPP venha acompanhado de autorização que identifique o representante legal responsável pela
                                    assinatura do laudo ou a cópia do contrato social da empresa, quando o representante legal for o sócio da mesma.
                                </p>

                                <p>
                                    Os documentos deverão ser encaminhados por e-mail para <strong>extrajudicial@koetzadvocacia.com.br</strong>
                                </p>
                            </div>

                            <div className="mb-6">
                                <p className="mb-2 font-bold">DADOS DO REQUERENTE:</p>
                                <p>
                                    <strong>Nome:</strong> {case_.client_name}
                                </p>
                                <p>
                                    <strong>CPF:</strong> {case_.client_cpf}
                                </p>
                                <p>
                                    <strong>Período:</strong>{' '}
                                    {modalVinculo ? `${formatDate(modalVinculo.start_date)} a ${formatDate(modalVinculo.end_date)}` : ''}
                                </p>
                            </div>

                            <div className="mt-8">
                                <p className="mb-2">Certa de vossa atenção,</p>
                                <p className="mb-4">aguardo retorno. Atenciosamente,</p>
                                <p className="font-bold">Eduardo Koetz - OAB/SC 42.934</p>
                            </div>
                        </div>
                        <DialogFooter className="flex flex-col gap-2 sm:flex-row">
                            <Button
                                variant="outline"
                                className="w-full sm:w-auto"
                                onClick={async () => {
                                    if (modalVinculo) {
                                        try {
                                            const cartaContent = gerarTextoCarta(modalVinculo);
                                            await navigator.clipboard.writeText(cartaContent);
                                            alert('Carta copiada para a área de transferência!');
                                        } catch (error) {
                                            // Fallback para navegadores que não suportam clipboard API
                                            const textArea = document.createElement('textarea');
                                            textArea.value = gerarTextoCarta(modalVinculo);
                                            document.body.appendChild(textArea);
                                            textArea.select();
                                            document.execCommand('copy');
                                            document.body.removeChild(textArea);
                                            alert('Carta copiada para a área de transferência!');
                                        }
                                    }
                                }}
                            >
                                <Copy className="mr-2 h-4 w-4" />
                                Copiar Texto
                            </Button>
                            <Button onClick={closeModal} className="w-full sm:w-auto">
                                Fechar
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
