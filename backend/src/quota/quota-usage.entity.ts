import { Entity, PrimaryGeneratedColumn, Column, Unique } from 'typeorm';

@Entity('quota_usage')
@Unique(['client_id', 'year', 'month'])
export class QuotaUsage {
  @PrimaryGeneratedColumn('increment', { type: 'bigint' })
  id: number;

  @Column({ type: 'bigint' })
  client_id: number;

  @Column({ type: 'smallint' })
  year: number;

  @Column({ type: 'tinyint' })
  month: number;

  @Column({ type: 'int', default: 0 })
  used_count: number;

  @Column({ default: false })
  warning_1_sent: boolean;

  @Column({ default: false })
  warning_2_sent: boolean;
}
