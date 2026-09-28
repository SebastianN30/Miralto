export type Table = {
    id: number;
    name: string;
    capacity: number | null;
    zone: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    orders_count?: number;
};

export type EmployeeRole = 'waiter' | 'cook' | 'other';

export type Employee = {
    id: number;
    name: string;
    position: string | null;
    role: EmployeeRole;
    user_id: number | null;
    user?: { id: number; username: string | null; is_active: boolean } | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    orders_count?: number;
};

export type Supplier = {
    id: number;
    name: string;
    contact_name: string | null;
    phone: string | null;
    email: string | null;
    notes: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
};

export type Category = {
    id: number;
    name: string;
    description: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    products?: Product[];
    active_products?: Product[];
    products_count?: number;
};

export type Ingredient = {
    id: number;
    name: string;
    unit: string;
    cost_per_unit: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
};

export type ProductIngredient = Ingredient & {
    pivot: {
        quantity: string;
    };
};

export type Printer = {
    id: number;
    name: string;
    description: string | null;
    ip_address: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
};

export type Product = {
    id: number;
    category_id: number | null;
    printer_id: number | null;
    name: string;
    description: string | null;
    price: string;
    cost: string | null;
    stock: number | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
    category?: Category;
    ingredients?: ProductIngredient[];
};

export type OrderItem = {
    id: number;
    order_id: number;
    product_id: number;
    quantity: number;
    price: string;
    subtotal: string;
    notes: string | null;
    prepared_at: string | null;
    created_at: string;
    updated_at: string;
    product?: Product;
};

export type OrderStatus = 'pending' | 'paid' | 'cancelled';
export type PaymentMethod = 'cash' | 'transfer' | 'card';

export type Order = {
    id: number;
    user_id: number;
    cash_register_id: number | null;
    table_id: number | null;
    table_name: string | null;
    table?: Table;
    employee_id: number | null;
    employee?: Pick<Employee, 'id' | 'name'> | null;
    total: string;
    status: OrderStatus;
    payment_method: PaymentMethod | null;
    payment_amount_1: string | null;
    payment_method_2: PaymentMethod | null;
    payment_amount_2: string | null;
    notes: string | null;
    service_charge: boolean;
    service_charge_percentage: string | null;
    service_charge_amount: string | null;
    tax: boolean;
    tax_amount: string | null;
    created_at: string;
    updated_at: string;
    user?: {
        id: number;
        name: string;
        email?: string;
    };
    items?: OrderItem[];
    logs?: OrderLog[];
};

export type OrderLogAction = 'created' | 'status_changed' | 'payment_updated' | 'deleted';

export type OrderLog = {
    id: number;
    order_id: number;
    user_id: number | null;
    action: OrderLogAction | string;
    description: string;
    changes: Record<string, { from: unknown; to: unknown }> | null;
    created_at: string;
    updated_at: string;
    user?: { id: number; name: string };
};

export type CashRegisterStatus = 'open' | 'closed';

export type CashMovementType = 'income' | 'expense' | 'sale' | 'refund';

export type CashMovement = {
    id: number;
    cash_register_id: number;
    user_id: number | null;
    order_id: number | null;
    type: CashMovementType;
    payment_method: PaymentMethod | null;
    amount: string;
    description: string;
    created_at: string;
    updated_at: string;
    user?: { id: number; name: string };
    order?: { id: number; total: string; status: OrderStatus };
};

export type CashRegister = {
    id: number;
    user_id: number;
    opened_at: string;
    closed_at: string | null;
    opening_amount: string;
    closing_amount: string | null;
    opening_notes: string | null;
    closing_notes: string | null;
    status: CashRegisterStatus;
    created_at: string;
    updated_at: string;
    user?: { id: number; name: string };
    movements?: CashMovement[];
    movements_count?: number;
    total_sales_cash?: string | number | null;
    total_sales_other?: string | number | null;
    total_expense?: string | number | null;
    orders?: Order[];
};

export type WalletType = 'nubank' | 'daviplata' | 'nequi' | 'other';
export type WalletTransactionType = 'income' | 'expense' | 'payment';

export type Wallet = {
    id: number;
    name: string;
    type: WalletType;
    account_identifier: string | null;
    initial_balance: string;
    is_active: boolean;
    notes: string | null;
    created_at: string;
    updated_at: string;
    transactions?: WalletTransaction[];
    transactions_count?: number;
    total_inbound?: string | number | null;
    total_outbound?: string | number | null;
    current_balance?: number;
};

export type WalletTransaction = {
    id: number;
    wallet_id: number;
    user_id: number | null;
    order_id: number | null;
    type: WalletTransactionType;
    amount: string;
    description: string;
    reference: string | null;
    transaction_date: string;
    created_at: string;
    updated_at: string;
    user?: { id: number; name: string };
    order?: { id: number; total: string; status: OrderStatus };
};

export type PaginatedData<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
    prev_page_url: string | null;
    next_page_url: string | null;
};
