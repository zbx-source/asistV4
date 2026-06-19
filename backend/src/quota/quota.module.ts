import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { QuotaUsage } from './quota-usage.entity';
import { Patient } from './patient.entity';
import { QuotaService } from './quota.service';

@Module({
  imports: [TypeOrmModule.forFeature([QuotaUsage, Patient])],
  providers: [QuotaService],
  exports: [QuotaService],
})
export class QuotaModule {}
