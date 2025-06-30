import { type BreadcrumbItem } from '@/types';
import { Head, useForm, router } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';
import { Eye, EyeOff, Key, Plus, Trash2, UserCheck, UserX } from 'lucide-react';

import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Usuários',
        href: '/settings/users',
    },
];

interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    created_at: string;
    is_super_admin: boolean;
}

interface UsersPageProps {
    users: {
        data: User[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    currentUser: User;
}

type UserForm = {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
};

type PasswordResetForm = {
    password: string;
    password_confirmation: string;
};

export default function Users({ users, currentUser }: UsersPageProps) {
    const [showCreateDialog, setShowCreateDialog] = useState(false);
    const [showResetPasswordDialog, setShowResetPasswordDialog] = useState(false);
    const [selectedUser, setSelectedUser] = useState<User | null>(null);
    const [showPasswords, setShowPasswords] = useState({ password: false, password_confirmation: false });

    const { data, setData, post, processing, errors, reset } = useForm<UserForm>({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const {
        data: passwordData,
        setData: setPasswordData,
        patch: patchPassword,
        processing: passwordProcessing,
        errors: passwordErrors,
        reset: resetPasswordForm,
    } = useForm<PasswordResetForm>({
        password: '',
        password_confirmation: '',
    });

    const handleCreateUser: FormEventHandler = (e) => {
        e.preventDefault();
        
        post(route('users.store'), {
            onSuccess: () => {
                reset();
                setShowCreateDialog(false);
            },
        });
    };

    const handleResetPassword: FormEventHandler = (e) => {
        e.preventDefault();
        
        if (!selectedUser) return;
        
        patchPassword(route('users.reset-password', selectedUser.id), {
            onSuccess: () => {
                resetPasswordForm();
                setShowResetPasswordDialog(false);
                setSelectedUser(null);
            },
        });
    };

    const toggleUserStatus = (user: User) => {
        const newStatus = !user.email_verified_at;
        
        router.patch(
            route('users.update-status', user.id),
            { active: newStatus },
            {
                preserveScroll: true,
            }
        );
    };

    const deleteUser = (user: User) => {
        if (confirm(`Tem certeza que deseja excluir o usuário "${user.name}"?`)) {
            router.delete(route('users.destroy', user.id), {
                preserveScroll: true,
            });
        }
    };

    const openResetPasswordDialog = (user: User) => {
        setSelectedUser(user);
        setShowResetPasswordDialog(true);
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('pt-BR');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Gerenciar Usuários" />

            <SettingsLayout>
                <div className="space-y-6">
                    <div className="flex items-center justify-between">
                        <HeadingSmall title="Usuários" description="Gerencie os usuários da sua empresa" />
                        
                        <Dialog open={showCreateDialog} onOpenChange={setShowCreateDialog}>
                            <DialogTrigger asChild>
                                <Button>
                                    <Plus className="mr-2 h-4 w-4" />
                                    Novo Usuário
                                </Button>
                            </DialogTrigger>
                            <DialogContent className="sm:max-w-md">
                                <DialogHeader>
                                    <DialogTitle>Criar Novo Usuário</DialogTitle>
                                </DialogHeader>
                                <form onSubmit={handleCreateUser} className="space-y-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">Nome</Label>
                                        <Input
                                            id="name"
                                            value={data.name}
                                            onChange={(e) => setData('name', e.target.value)}
                                            required
                                            placeholder="Nome completo"
                                        />
                                        <InputError message={errors.name} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="email">E-mail</Label>
                                        <Input
                                            id="email"
                                            type="email"
                                            value={data.email}
                                            onChange={(e) => setData('email', e.target.value)}
                                            required
                                            placeholder="exemplo@empresa.com"
                                        />
                                        <InputError message={errors.email} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="password">Senha</Label>
                                        <div className="relative">
                                            <Input
                                                id="password"
                                                type={showPasswords.password ? 'text' : 'password'}
                                                value={data.password}
                                                onChange={(e) => setData('password', e.target.value)}
                                                required
                                                placeholder="Senha"
                                            />
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                className="absolute right-0 top-0 h-full px-3 py-2 hover:bg-transparent"
                                                onClick={() => setShowPasswords(prev => ({ ...prev, password: !prev.password }))}
                                            >
                                                {showPasswords.password ? (
                                                    <EyeOff className="h-4 w-4" />
                                                ) : (
                                                    <Eye className="h-4 w-4" />
                                                )}
                                            </Button>
                                        </div>
                                        <InputError message={errors.password} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="password_confirmation">Confirmar Senha</Label>
                                        <div className="relative">
                                            <Input
                                                id="password_confirmation"
                                                type={showPasswords.password_confirmation ? 'text' : 'password'}
                                                value={data.password_confirmation}
                                                onChange={(e) => setData('password_confirmation', e.target.value)}
                                                required
                                                placeholder="Confirmar senha"
                                            />
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                className="absolute right-0 top-0 h-full px-3 py-2 hover:bg-transparent"
                                                onClick={() => setShowPasswords(prev => ({ ...prev, password_confirmation: !prev.password_confirmation }))}
                                            >
                                                {showPasswords.password_confirmation ? (
                                                    <EyeOff className="h-4 w-4" />
                                                ) : (
                                                    <Eye className="h-4 w-4" />
                                                )}
                                            </Button>
                                        </div>
                                        <InputError message={errors.password_confirmation} />
                                    </div>

                                    <div className="flex justify-end space-x-2">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() => setShowCreateDialog(false)}
                                        >
                                            Cancelar
                                        </Button>
                                        <Button type="submit" disabled={processing}>
                                            {processing ? 'Criando...' : 'Criar Usuário'}
                                        </Button>
                                    </div>
                                </form>
                            </DialogContent>
                        </Dialog>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>Lista de Usuários</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Nome</TableHead>
                                        <TableHead>E-mail</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Criado em</TableHead>
                                        <TableHead className="text-right">Ações</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {users.data.map((user) => (
                                        <TableRow key={user.id}>
                                            <TableCell>
                                                <div className="font-medium">
                                                    {user.name}
                                                    {user.is_super_admin && (
                                                        <Badge variant="secondary" className="ml-2">
                                                            Admin
                                                        </Badge>
                                                    )}
                                                    {user.id === currentUser.id && (
                                                        <Badge variant="outline" className="ml-2">
                                                            Você
                                                        </Badge>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell>{user.email}</TableCell>
                                            <TableCell>
                                                <Badge variant={user.email_verified_at ? 'default' : 'destructive'}>
                                                    {user.email_verified_at ? 'Ativo' : 'Inativo'}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>{formatDate(user.created_at)}</TableCell>
                                            <TableCell className="text-right">
                                                {user.id !== currentUser.id && (
                                                    <DropdownMenu>
                                                        <DropdownMenuTrigger asChild>
                                                            <Button variant="ghost" size="sm">
                                                                Ações
                                                            </Button>
                                                        </DropdownMenuTrigger>
                                                        <DropdownMenuContent align="end">
                                                            <DropdownMenuItem onClick={() => toggleUserStatus(user)}>
                                                                {user.email_verified_at ? (
                                                                    <>
                                                                        <UserX className="mr-2 h-4 w-4" />
                                                                        Desativar
                                                                    </>
                                                                ) : (
                                                                    <>
                                                                        <UserCheck className="mr-2 h-4 w-4" />
                                                                        Ativar
                                                                    </>
                                                                )}
                                                            </DropdownMenuItem>
                                                            <DropdownMenuItem onClick={() => openResetPasswordDialog(user)}>
                                                                <Key className="mr-2 h-4 w-4" />
                                                                Alterar Senha
                                                            </DropdownMenuItem>
                                                            <DropdownMenuItem
                                                                onClick={() => deleteUser(user)}
                                                                className="text-red-600"
                                                            >
                                                                <Trash2 className="mr-2 h-4 w-4" />
                                                                Excluir
                                                            </DropdownMenuItem>
                                                        </DropdownMenuContent>
                                                    </DropdownMenu>
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>

                            {users.data.length === 0 && (
                                <div className="py-6 text-center text-muted-foreground">
                                    Nenhum usuário encontrado.
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Reset Password Dialog */}
                    <Dialog open={showResetPasswordDialog} onOpenChange={setShowResetPasswordDialog}>
                        <DialogContent className="sm:max-w-md">
                            <DialogHeader>
                                <DialogTitle>
                                    Alterar Senha - {selectedUser?.name}
                                </DialogTitle>
                            </DialogHeader>
                            <form onSubmit={handleResetPassword} className="space-y-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="new_password">Nova Senha</Label>
                                    <Input
                                        id="new_password"
                                        type="password"
                                        value={passwordData.password}
                                        onChange={(e) => setPasswordData('password', e.target.value)}
                                        required
                                        placeholder="Nova senha"
                                    />
                                    <InputError message={passwordErrors.password} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="new_password_confirmation">Confirmar Nova Senha</Label>
                                    <Input
                                        id="new_password_confirmation"
                                        type="password"
                                        value={passwordData.password_confirmation}
                                        onChange={(e) => setPasswordData('password_confirmation', e.target.value)}
                                        required
                                        placeholder="Confirmar nova senha"
                                    />
                                    <InputError message={passwordErrors.password_confirmation} />
                                </div>

                                <div className="flex justify-end space-x-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => setShowResetPasswordDialog(false)}
                                    >
                                        Cancelar
                                    </Button>
                                    <Button type="submit" disabled={passwordProcessing}>
                                        {passwordProcessing ? 'Alterando...' : 'Alterar Senha'}
                                    </Button>
                                </div>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
} 