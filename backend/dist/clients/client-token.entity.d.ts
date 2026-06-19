export declare class ClientToken {
    id: number;
    client_id: number;
    token: string;
    whatsapp_number: string;
    phone_number_id: string;
    status: 'active' | 'inactive';
    created_at: Date;
}
