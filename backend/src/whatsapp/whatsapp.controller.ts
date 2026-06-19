import { Controller, Get, Post, Query, Body, Logger } from '@nestjs/common';
import { WhatsAppService } from './whatsapp.service';

@Controller('webhook')
export class WhatsAppController {
  private readonly logger = new Logger(WhatsAppController.name);

  constructor(private readonly svc: WhatsAppService) {}

  @Get()
  verify(
    @Query('hub.mode') mode: string,
    @Query('hub.verify_token') token: string,
    @Query('hub.challenge') challenge: string,
  ) {
    return this.svc.verify(mode, token, challenge);
  }

  @Post()
  async incoming(@Body() payload: any) {
    try {
      await this.svc.handleIncoming(payload);
    } catch (err) {
      this.logger.error('handleIncoming hatası:', err);
    }
    return { ok: true };
  }
}

@Controller('portal')
export class PortalController {
  private readonly logger = new Logger(PortalController.name);

  constructor(private readonly svc: WhatsAppService) {}

  @Post('send')
  async send(@Body() body: { to: string; body: string; phone_number_id: string }) {
    try {
      const ok = await this.svc.sendFromPortal(body.to, body.body, body.phone_number_id);
      return { ok };
    } catch (err) {
      this.logger.error('portal send hatası:', err);
      return { ok: false };
    }
  }

  @Post('summary')
  async summary(@Body() body: { conversation_id: number; client_id: number }) {
    try {
      const result = await this.svc.generateSummary(body.conversation_id, body.client_id);
      if (!result) return { ok: false, error: 'Özet oluşturulamadı' };
      return { ok: true, ...result };
    } catch (err) {
      this.logger.error('summary hatası:', err);
      return { ok: false, error: 'Sunucu hatası' };
    }
  }
}
