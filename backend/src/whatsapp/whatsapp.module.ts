import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';

import { WhatsAppController, PortalController } from './whatsapp.controller';
import { WhatsAppService } from './whatsapp.service';
import { ConversationHistoryService } from './conversation-history.service';

import { Conversation } from './conversation.entity';
import { Message } from './message.entity';
import { TreatmentModule } from './treatment-module.entity';
import { Patient } from '../quota/patient.entity';

import { ClientsModule } from '../clients/clients.module';
import { CoreRulesModule } from '../core-rules/core-rules.module';
import { QuotaModule } from '../quota/quota.module';

@Module({
  imports: [
    TypeOrmModule.forFeature([Conversation, Message, TreatmentModule, Patient]),
    ClientsModule,
    CoreRulesModule,
    QuotaModule,
  ],
  controllers: [WhatsAppController, PortalController],
  providers: [WhatsAppService, ConversationHistoryService],
})
export class WhatsAppModule {}