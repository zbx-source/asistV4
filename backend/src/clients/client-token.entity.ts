import { Entity, PrimaryGeneratedColumn, Column, CreateDateColumn } from 'typeorm';

@Entity('client_tokens')
export class ClientToken {
  @PrimaryGeneratedColumn('increment', { type: 'bigint' })
  id: number;

  @Column({ type: 'bigint', unique: true })
  client_id: number;

  @Column({ length: 64, unique: true })
  token: string;

  @Column({ length: 20 })
  whatsapp_number: string;

  @Column({ length: 40, nullable: true })
  phone_number_id: string;

  @Column({ type: 'enum', enum: ['active', 'inactive'], default: 'active' })
  status: 'active' | 'inactive';

  @CreateDateColumn()
  created_at: Date;
}
