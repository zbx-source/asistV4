import { Entity, PrimaryGeneratedColumn, Column } from 'typeorm';

@Entity('messages')
export class Message {
  @PrimaryGeneratedColumn('increment', { type: 'bigint' })
  id: number;

  @Column({ type: 'bigint' })
  conversation_id: number;

  @Column({ type: 'enum', enum: ['inbound', 'outbound'] })
  direction: 'inbound' | 'outbound';

  @Column({ type: 'enum', enum: ['patient', 'ai', 'portal_user'] })
  sender_type: 'patient' | 'ai' | 'portal_user';

  @Column({ type: 'bigint', nullable: true })
  sender_id: number;

  @Column({
    type: 'enum',
    enum: ['text', 'image', 'document', 'template'],
    default: 'text',
  })
  message_type: 'text' | 'image' | 'document' | 'template';

  @Column({ type: 'text', nullable: true })
  body: string;

  @Column({ type: 'text', nullable: true })
  body_tr: string;

  @Column({ length: 500, nullable: true })
  media_url: string;

  @Column({ length: 80, nullable: true })
  media_type: string;

  @Column({ length: 100, nullable: true })
  wa_message_id: string;

  @Column({ type: 'datetime' })
  sent_at: Date;
}
