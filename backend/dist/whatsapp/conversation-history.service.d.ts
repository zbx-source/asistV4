import { Repository } from 'typeorm';
import { Message } from './message.entity';
export interface HistoryMessage {
    role: 'user' | 'assistant';
    content: string;
}
export declare class ConversationHistoryService {
    private readonly messageRepo;
    constructor(messageRepo: Repository<Message>);
    getHistory(conversationId: number, limit?: number): Promise<HistoryMessage[]>;
    saveMessage(data: Partial<Message>): Promise<Message>;
}
