export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    links: PaginationLink[];
};

export type Professional = {
    uuid: string;
    name: string;
    email: string | null;
    phone: string | null;
    description: string | null;
    avatar_url: string | null;
    status: 'active' | 'inactive';
    service_uuids: string[];
    services: Pick<Service, 'uuid' | 'name'>[];
};

export type Service = {
    uuid: string;
    name: string;
    description: string | null;
    category: string | null;
    duration_minutes: number;
    buffer_minutes: number;
    price: string;
    is_active: boolean;
};

export type Customer = {
    uuid: string;
    name: string;
    phone: string;
    whatsapp: string | null;
    email: string | null;
    birth_date: string | null;
    notes: string | null;
    first_appointment_at: string | null;
    last_appointment_at: string | null;
};

export type WorkingHour = {
    day_of_week: number;
    is_closed: boolean;
    starts_at: string | null;
    ends_at: string | null;
    break_starts_at: string | null;
    break_ends_at: string | null;
};
