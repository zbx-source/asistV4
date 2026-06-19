import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { CoreRule } from './core-rule.entity';
import { CoreRulesService } from './core-rules.service';

@Module({
  imports: [TypeOrmModule.forFeature([CoreRule])],
  providers: [CoreRulesService],
  exports: [CoreRulesService],
})
export class CoreRulesModule {}
