import { Entity, PrimaryGeneratedColumn, Column, CreateDateColumn, Unique } from 'typeorm';

@Entity('patients')
@Unique(['client_id', 'phone'])
export class Patient {
  @PrimaryGeneratedColumn('increment', { type: 'bigint' })
  id: number;

  @Column({ type: 'bigint' })
  client_id: number;

  @Column({ length: 30 })
  phone: string;

  @Column({ length: 120, nullable: true })
  name: string;

  @Column({ length: 10, nullable: true })
  language: string;

  @Column({ length: 5, nullable: true })
  country_code: string;

  @Column({ length: 180, nullable: true })
  treatment_interest: string;

  @Column({
    type: 'enum',
    enum: ['new', 'photo_pending', 'price_given', 'followup', 'won', 'lost'],
    default: 'new',
  })
  pipeline_status: string;

  @Column({ type: 'datetime', nullable: true })
  pipeline_updated_at: Date;

  @CreateDateColumn()
  first_contact: Date;

  @Column({ type: 'datetime', nullable: true })
  last_contact: Date;
}