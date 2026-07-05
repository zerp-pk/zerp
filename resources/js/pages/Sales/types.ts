export interface SalesInvoice {
    id: number;
    invoice_number: string;
    invoice_date: string;
    due_date: string;
    customer_id: number;
    warehouse_id?: number;
    type: 'product' | 'service';
    subtotal: number;
    tax_amount: number;
    discount_amount: number;
    total_amount: number;
    paid_amount: number;
    balance_amount: number;
    status: 'draft' | 'posted' | 'partial' | 'paid' | 'overdue';
    display_status: 'draft' | 'posted' | 'partial' | 'paid' | 'overdue';
    payment_terms?: string;
    notes?: string;
    creator_id: number;
    created_by: number;
    created_at: string;
    updated_at: string;
    customer?: User;
    customer_details?: CustomerDetails;
    warehouse?: Warehouse;
    items?: SalesInvoiceItem[];
}

export interface SalesInvoiceItem {
    id?: number;
    invoice_id?: number;
    product_id: number;
    quantity: number;
    unit_price: number;
    discount_percentage: number;
    discount_amount: number;
    tax_percentage: number;
    tax_amount: number;
    total_amount: number;
    taxes?: Array<{id?: number; tax_name: string; tax_rate: number}>;
    product?: ProductServiceItem;
}

export interface User {
    id: number;
    name: string;
    email: string;
    type?: string;
}

export interface CustomerDetails {
    id: number;
    user_id: number;
    customer_code: string;
    company_name: string;
    contact_person_name?: string;
    contact_person_email?: string;
    contact_person_mobile?: string;
    tax_number?: string;
    payment_terms?: string;
    billing_address?: Address;
    shipping_address?: Address;
    same_as_billing: boolean;
    notes?: string;
}

export interface Address {
    name: string;
    address_line_1: string;
    address_line_2?: string;
    city: string;
    state: string;
    zip_code: string;
    country: string;
}

export interface Warehouse {
    id: number;
    name: string;
}

export interface ProductServiceItem {
    id: number;
    name: string;
    sku?: string;
    description?: string;
    price: number;
    tax_rate?: number;
    unit?: string;
}

export interface SalesFilters {
    customer_id?: string;
    warehouse_id?: string;
    status?: string;
    search?: string;
    date_range?: string;
}