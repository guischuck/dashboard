import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { type BreadcrumbItem } from '@/types';
import { type PropsWithChildren } from 'react';

export default function AppSidebarLayout({ children, breadcrumbs = [] }: PropsWithChildren<{ breadcrumbs?: BreadcrumbItem[] }>) {
    console.log('AppSidebarLayout rendering with breadcrumbs:', breadcrumbs);

    try {
        return (
            <AppShell variant="sidebar">
                <AppSidebar />
                <AppContent variant="sidebar" className="overflow-x-hidden">
                    <AppSidebarHeader breadcrumbs={breadcrumbs} />
                    {children}
                </AppContent>
            </AppShell>
        );
    } catch (error) {
        console.error('Error in AppSidebarLayout:', error);
        // Fallback layout
        return (
            <div className="min-h-screen bg-background">
                <div className="flex min-h-screen">
                    <div className="w-64 border-r border-sidebar-border bg-sidebar">
                        <div className="p-4">
                            <h1 className="text-xl font-bold">Sistema Jurídico</h1>
                        </div>
                    </div>
                    <div className="flex-1">
                        <main className="flex-1">{children}</main>
                    </div>
                </div>
            </div>
        );
    }
}
