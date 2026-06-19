export declare class Conversation {
    id: number;
    client_id: number;
    patient_id: number;
    module_id: number;
    status: 'ai_active' | 'pending_takeover' | 'assigned' | 'with_user' | 'closed';
    assigned_to: number;
    assigned_by: number;
    assigned_at: Date;
    taken_over_at: Date;
    template_sent: boolean;
    template_replied: boolean;
    closed_at: Date;
    started_at: Date;
    updated_at: Date;
}
