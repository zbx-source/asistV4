import { Module } from '@nestjs/common';
import { ConfigModule, ConfigService } from '@nestjs/config';
import { TypeOrmModule } from '@nestjs/typeorm';

import { WhatsAppModule } from './whatsapp/whatsapp.module';
import { ClientsModule } from './clients/clients.module';
import { CoreRulesModule } from './core-rules/core-rules.module';
import { QuotaModule } from './quota/quota.module';

@Module({
  imports: [
    ConfigModule.forRoot({ isGlobal: true }),

    TypeOrmModule.forRootAsync({
      imports: [ConfigModule],
      inject: [ConfigService],
      useFactory: (cfg: ConfigService) => ({
        type: 'mysql',
        host:     cfg.get('DB_HOST', 'localhost'),
        port:     cfg.get<number>('DB_PORT', 3306),
        username: cfg.get('DB_USER', 'zbasist_usr'),
        password: cfg.get('DB_PASS'),
        database: cfg.get('DB_NAME', 'zbasist'),
        entities:  [__dirname + '/**/*.entity{.ts,.js}'],
        synchronize: false,
        charset: 'utf8mb4',
        timezone: '+03:00',
      }),
    }),

    WhatsAppModule,
    ClientsModule,
    CoreRulesModule,
    QuotaModule,
  ],
})
export class AppModule {}
