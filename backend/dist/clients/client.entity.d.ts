export declare class Client {
    id: number;
    type: 'clinic' | 'agency';
    name: string;
    license_no: string;
    contact_phone: string;
    contact_email: string;
    city: string;
    status: 'active' | 'suspended' | 'cancelled';
    created_at: Date;
    updated_at: Date;
}
