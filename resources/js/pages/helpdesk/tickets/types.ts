import { PaginatedData, ModalState, AuthContext } from '@/types/common';
import { HelpdeskCategory } from '../categories/types';

export interface HelpdeskTicket {
    id: number;
    ticket_id: string;
    title: string;
    description: string;
    status: 'open' | 'in_progress' | 'resolved' | 'closed';
    priority: 'low' | 'medium' | 'high' | 'urgent';
    category_id: number;
    category?: HelpdeskCategory;
    creator_id: number;
    creator?: {
        id: number;
        name: string;
        email: string;
    };
    assignedTo?: {
        id: number;
        name: string;
        email: string;
    };
    resolved_at?: string;
    created_at: string;
    replies?: HelpdeskReply[];
}

export interface HelpdeskReply {
    id: number;
    ticket_id: number;
    message: string;
    attachments?: string[];
    is_internal: boolean;
    created_by: number;
    creator?: {
        id: number;
        name: string;
        email: string;
    };
    created_at: string;
}

export interface CreateHelpdeskTicketFormData {
    title: string;
    description: string;
    priority: 'low' | 'medium' | 'high' | 'urgent';
    category_id: number;
    company_id?: number | null;
}

export interface EditHelpdeskTicketFormData {
    title: string;
    description: string;
    status: 'open' | 'in_progress' | 'resolved' | 'closed';
    priority: 'low' | 'medium' | 'high' | 'urgent';
    category_id: number;
}

export interface CreateHelpdeskReplyFormData {
    message: string;
    attachments?: string[];
    is_internal: boolean;
}

export interface HelpdeskTicketFilters {
    title: string;
    status: string;
    priority: string;
    category_id: string;
    company_id: string;
}

export type PaginatedHelpdeskTickets = PaginatedData<HelpdeskTicket>;
export type HelpdeskTicketModalState = ModalState<HelpdeskTicket>;

export interface HelpdeskTicketsIndexProps {
    tickets: PaginatedHelpdeskTickets;
    categories: HelpdeskCategory[];
    companies: { id: number; name: string; }[];
    auth: AuthContext;
    [key: string]: unknown;
}

export interface CreateHelpdeskTicketProps {
    categories: HelpdeskCategory[];
    companies: { id: number; name: string; }[];
    auth: AuthContext;
    [key: string]: unknown;
}

export interface EditHelpdeskTicketProps {
    ticket: HelpdeskTicket;
    categories: HelpdeskCategory[];
    onSuccess: () => void;
}

export interface ShowHelpdeskTicketProps {
    ticket: HelpdeskTicket;
    auth: AuthContext;
    [key: string]: unknown;
}