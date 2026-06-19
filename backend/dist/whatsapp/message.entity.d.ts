export declare class Message {
    id: number;
    conversation_id: number;
    direction: 'inbound' | 'outbound';
    sender_type: 'patient' | 'ai' | 'portal_user';
    sender_id: number;
    message_type: 'text' | 'image' | 'document' | 'template';
    body: string;
    body_tr: string;
    media_url: string;
    media_type: string;
    wa_message_id: string;
    sent_at: Date;
}
