import { Entity, PrimaryGeneratedColumn, Column, CreateDateColumn, UpdateDateColumn } from 'typeorm';

@Entity('conversations')
export class Conversation {
  @PrimaryGeneratedColumn('increment', { type: 'bigint' })
  id: number;

  @Column({ type: 'bigint' })
  client_id: number;

  @Column({ type: 'bigint' })
  patient_id: number;

  @Column({ type: 'bigint', nullable: true })
  module_id: number;

  @Column({
    type: 'enum',
    enum: ['ai_active', 'pending_takeover', 'assigned', 'with_user', 'closed'],
    default: 'ai_active',
  })
  status: 'ai_active' | 'pending_takeover' | 'assigned' | 'with_user' | 'closed';

  @Column({ length: 255, nullable: true })
  topic_summary: string;

  @Column({ type: 'text', nullable: true })
  summary_text: string;

  @Column({ type: 'bigint', nullable: true })
  summary_last_msg_id: number;

  @Column({ type: 'bigint', nullable: true })
  assigned_to: number;

  @Column({ type: 'bigint', nullable: true })
  assigned_by: number;

  @Column({ type: 'datetime', nullable: true })
  assigned_at: Date;

  @Column({ type: 'datetime', nullable: true })
  taken_over_at: Date;

  @Column({ default: false })
  template_sent: boolean;

  @Column({ default: false })
  template_replied: boolean;

  @Column({ type: 'datetime', nullable: true })
  closed_at: Date;

  @CreateDateColumn()
  started_at: Date;

  @UpdateDateColumn({ nullable: true })
  updated_at: Date;
}
