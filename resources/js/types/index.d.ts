import { LucideIcon } from 'lucide-react';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href?: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
    items?: NavItem[];
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    company_id?: number;
    role?: string;
    is_active?: boolean;
    last_login_at?: string | null;
    [key: string]: unknown;
}

export interface Company {
    id: number;
    name: string;
    slug: string;
    email?: string | null;
    cnpj?: string | null;
    phone?: string | null;
    address?: string | null;
    city?: string | null;
    state?: string | null;
    zip_code?: string | null;
    logo_path?: string | null;
    settings?: Record<string, unknown>;
    plan: 'basic' | 'premium' | 'enterprise';
    max_users: number;
    max_cases: number;
    is_active: boolean;
    trial_ends_at?: string | null;
    subscription_ends_at?: string | null;
    created_at: string;
    updated_at: string;
    users_count?: number;
    cases_count?: number;
    [key: string]: unknown;
}

export interface LegalCase {
    id: number;
    client_name: string;
    client_cpf: string;
    benefit_type?: string | null;
    status: string;
    notes?: string | null;
    workflow_tasks?: Record<string, unknown>;
    company_id: number;
    assigned_to?: number | null;
    created_at: string;
    updated_at: string;
    assignedTo?: User;
    company?: Company;
    [key: string]: unknown;
}

export interface PetitionTemplate {
    id: number;
    name: string;
    description?: string | null;
    content: string;
    variables?: string[];
    is_active: boolean;
    company_id: number;
    created_at: string;
    updated_at: string;
    company?: Company;
    [key: string]: unknown;
}

export interface EmploymentRelationship {
    id: number;
    case_id: number;
    employer_name: string;
    start_date: string;
    end_date?: string | null;
    salary?: number | null;
    role?: string | null;
    collected_at?: string | null;
    created_at: string;
    updated_at: string;
    case?: LegalCase;
    [key: string]: unknown;
}

export interface Document {
    id: number;
    case_id: number;
    name: string;
    type: string;
    file_path: string;
    file_size?: number | null;
    mime_type?: string | null;
    processed_at?: string | null;
    extraction_data?: Record<string, unknown>;
    created_at: string;
    updated_at: string;
    case?: LegalCase;
    [key: string]: unknown;
}
