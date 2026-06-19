import { Entity, PrimaryGeneratedColumn, Column, CreateDateColumn } from 'typeorm';

@Entity('core_rules')
export class CoreRule {
  @PrimaryGeneratedColumn()
  id: number;

  @Column({ length: 20 })
  version: string;

  @Column({ type: 'mediumtext' })
  content: string;

  @Column({ type: 'enum', enum: ['active', 'archived'], default: 'active' })
  status: 'active' | 'archived';

  @CreateDateColumn()
  created_at: Date;
}
