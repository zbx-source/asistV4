import { Entity, PrimaryGeneratedColumn, Column, CreateDateColumn, UpdateDateColumn } from 'typeorm';

@Entity('clients')
export class Client {
  @PrimaryGeneratedColumn('increment', { type: 'bigint' })
  id: number;

  @Column({ type: 'enum', enum: ['clinic', 'agency'] })
  type: 'clinic' | 'agency';

  @Column({ length: 180 })
  name: string;

  @Column({ length: 80, nullable: true })
  license_no: string;

  @Column({ length: 20, nullable: true })
  contact_phone: string;

  @Column({ length: 120, nullable: true })
  contact_email: string;

  @Column({ length: 80, nullable: true })
  city: string;

  @Column({ type: 'enum', enum: ['active', 'suspended', 'cancelled'], default: 'active' })
  status: 'active' | 'suspended' | 'cancelled';

  @CreateDateColumn()
  created_at: Date;

  @UpdateDateColumn({ nullable: true })
  updated_at: Date;
}
