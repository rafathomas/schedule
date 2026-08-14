export type User = {
    id: number;
    name: string;
    email: string;
    phone?: string | null;
    current_company_id?: number | null;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Company = {
    id: number;
    uuid: string;
    name: string;
    slug: string;
    status: string;
    primary_color: string;
    logo_url: string | null;
    segment: string | null;
    phone: string | null;
    whatsapp: string | null;
    postal_code: string | null;
    address: string | null;
    address_number: string | null;
    address_complement: string | null;
    city: string | null;
    state: string | null;
    timezone: string;
    appointment_interval_minutes: number;
    minimum_notice_minutes: number;
    maximum_notice_days: number;
    onboarding_steps: Record<string, boolean> | null;
    onboarding_completed_at: string | null;
};

export type Auth = {
    user: User;
};

/* @chisel-passkeys */
export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};
/* @end-chisel-passkeys */

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
