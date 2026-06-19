import { Injectable } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { Message } from './message.entity';

export interface HistoryMessage {
  role: 'user' | 'assistant';
  content: string;
}

@Injectable()
export class ConversationHistoryService {
  constructor(
    @InjectRepository(Message)
    private readonly messageRepo: Repository<Message>,
  ) {}

  async getHistory(conversationId: number, limit = 20): Promise<HistoryMessage[]> {
    const messages = await this.messageRepo.find({
      where: { conversation_id: conversationId },
      order: { sent_at: 'ASC' },
      take: limit,
    });

    return messages
      .filter(m => m.body)
      .map(m => ({
        role: m.sender_type === 'patient' ? 'user' : 'assistant',
        content: m.body,
      }));
  }

  async saveMessage(data: Partial<Message>): Promise<Message> {
    const msg = this.messageRepo.create({
      ...data,
      sent_at: data.sent_at || new Date(),
    });
    return this.messageRepo.save(msg);
  }
}
