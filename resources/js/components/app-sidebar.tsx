import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import {
    Activity,
    BookOpen,
    Briefcase,
    Building,
    Clock,
    FileText,
    Folder,
    GitBranch,
    Globe,
    LayoutGrid,
    MessageCircle,
    Settings,
    Users,
} from 'lucide-react';
import AppLogo from './app-logo';

export function AppSidebar() {
    const { auth } = usePage().props as any;
    const user = auth?.user;

    // Navegação específica para super admin
    const superAdminNavigation: NavItem[] = [
        {
            title: 'Dashboard',
            href: '/dashboard',
            icon: LayoutGrid,
        },
        {
            title: 'Empresas',
            href: '/companies',
            icon: Building,
        },
        {
            title: 'Financeiro',
            icon: Activity,
            items: [
                {
                    title: 'Dashboard',
                    href: '/financial',
                    icon: LayoutGrid,
                },
                {
                    title: 'Planos',
                    href: '/subscription-plans',
                    icon: Briefcase,
                },
                {
                    title: 'Assinaturas',
                    href: '/financial/subscriptions',
                    icon: Users,
                },
                {
                    title: 'Pagamentos',
                    href: '/financial/payments',
                    icon: Activity,
                },
            ],
        },
        {
            title: 'Templates Globais',
            icon: Globe,
            items: [
                {
                    title: 'Petições',
                    href: '/petition-templates',
                    icon: FileText,
                },
                {
                    title: 'Workflows',
                    href: '/workflow-templates',
                    icon: GitBranch,
                },
            ],
        },
        {
            title: 'Configurações',
            href: '/settings/profile',
            icon: Settings,
        },
    ];

    // Navegação para usuários normais (empresas)
    const regularNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: '/dashboard',
            icon: LayoutGrid,
        },
        {
            title: 'Casos',
            href: '/cases',
            icon: Briefcase,
        },
        {
            title: 'Coletas',
            href: '/coletas',
            icon: FileText,
        },
        {
            title: 'Andamentos',
            href: '/andamentos',
            icon: Clock,
        },
        {
            title: 'Chat',
            href: '/chat',
            icon: MessageCircle,
        },
        {
            title: 'Processos',
            href: '/inss-processes',
            icon: Folder,
        },
        {
            title: 'Petições',
            href: '/petitions',
            icon: BookOpen,
        },
        {
            title: 'Workflows',
            href: '/tasks',
            icon: GitBranch,
        },
    ];

    // Escolher navegação baseada no tipo de usuário
    const mainNavItems = user?.is_super_admin ? superAdminNavigation : regularNavItems;

    const footerNavItems: NavItem[] = [
        {
            title: 'Configurações',
            href: '/settings',
            icon: Settings,
        },
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
