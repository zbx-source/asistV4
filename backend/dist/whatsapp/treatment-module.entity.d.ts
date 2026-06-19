export declare class TreatmentModule {
    id: number;
    client_id: number;
    name: string;
    prompt: string;
    status: 'active' | 'archived';
    sort_order: number;
    created_at: Date;
    updated_at: Date;
}
