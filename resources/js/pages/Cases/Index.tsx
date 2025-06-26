import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Briefcase, Plus } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Casos',
        href: '/cases',
    },
];

export default function CasesIndex() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Casos - Sistema Jurídico" />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold">Casos</h1>
                        <p className="text-muted-foreground">Gerencie os casos jurídicos dos clientes</p>
                    </div>
                    <Link href="/cases/create">
                        <Button>
                            <Plus className="mr-2 h-4 w-4" />
                            Novo Caso
                        </Button>
                    </Link>
                </div>

                {/* Cases List */}
                <Card>
                    <CardHeader>
                        <CardTitle>Casos</CardTitle>
                        <CardDescription>
                            Lista de todos os casos jurídicos cadastrados no sistema
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="text-center py-12">
                            <Briefcase className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                            <h3 className="text-lg font-medium mb-2">Nenhum caso encontrado</h3>
                            <p className="text-muted-foreground mb-4">
                                Comece criando seu primeiro caso jurídico
                            </p>
                            <Link href="/cases/create">
                                <Button>
                                    <Plus className="mr-2 h-4 w-4" />
                                    Criar Primeiro Caso
                                </Button>
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
} 