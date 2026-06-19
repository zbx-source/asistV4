import { Injectable, Logger } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository, DataSource } from 'typeorm';
import { ConfigService } from '@nestjs/config';

import { ClientsService } from '../clients/clients.service';
import { CoreRulesService } from '../core-rules/core-rules.service';
import { QuotaService } from '../quota/quota.service';
import { ConversationHistoryService } from './conversation-history.service';

import { Conversation } from './conversation.entity';
import { TreatmentModule } from './treatment-module.entity';
import { Patient } from '../quota/patient.entity';

type OpenAIChatRole = 'system' | 'user' | 'assistant';
interface OpenAIChatMessage { role: OpenAIChatRole; content: string; }

@Injectable()
export class WhatsAppService {
  private readonly logger = new Logger(WhatsAppService.name);

  private readonly verifyToken:     string;
  private readonly metaToken:       string;
  private readonly metaEnabled:     boolean;
  private readonly openAiKey:       string;
  private readonly openAiModel:     string;
  private readonly openAiUrl:       string;
  private readonly openAiTimeout:   number;
  private readonly openAiMaxTokens: number;
  private readonly historyEnabled:  boolean;

  constructor(
    private readonly cfg: ConfigService,
    private readonly clientsSvc: ClientsService,
    private readonly coreRulesSvc: CoreRulesService,
    private readonly quotaSvc: QuotaService,
    private readonly history: ConversationHistoryService,

    @InjectRepository(Conversation)
    private readonly convRepo: Repository<Conversation>,

    @InjectRepository(TreatmentModule)
    private readonly moduleRepo: Repository<TreatmentModule>,

    @InjectRepository(Patient)
    private readonly patientRepo: Repository<Patient>,

    private readonly dataSource: DataSource,
  ) {
    this.verifyToken    = cfg.get('META_VERIFY_TOKEN', 'dev-token');
    this.metaToken      = cfg.get('META_TOKEN', '');
    this.metaEnabled    = cfg.get('META_ENABLED', 'false') === 'true';
    this.openAiKey      = cfg.get('OPENAI_API_KEY', '');
    this.openAiModel    = cfg.get('OPENAI_MODEL', 'gpt-4o-mini');
    this.openAiUrl      = cfg.get('OPENAI_API_URL', 'https://api.openai.com/v1/chat/completions');
    this.openAiTimeout  = Number(cfg.get('OPENAI_TIMEOUT_MS', '60000'));
    this.openAiMaxTokens = Number(cfg.get('OPENAI_MAX_TOKENS', '2000'));
    this.historyEnabled = cfg.get('CONVERSATION_HISTORY_ENABLED', 'true') === 'true';
  }

  // ── Meta webhook doğrulama ─────────────────────────────────────────────
  verify(mode: string, token: string, challenge: string): string | number {
    if (mode === 'subscribe' && token === this.verifyToken) {
      this.logger.log('Webhook doğrulandı');
      return parseInt(challenge, 10);
    }
    return 'Forbidden';
  }

  // ── Gelen webhook payload ──────────────────────────────────────────────
  async handleIncoming(payload: any): Promise<void> {
    try {
      const msgs = payload?.entry?.[0]?.changes?.[0]?.value?.messages;
      if (!msgs?.length) return;
      const value = payload.entry[0].changes[0].value;
      for (const msg of msgs) await this.processMessage(msg, value);
    } catch (err) {
      this.logger.error('handleIncoming error:', err);
    }
  }

  // ── Portal'dan mesaj gönder ────────────────────────────────────────────
  async sendFromPortal(to: string, body: string, phoneNumberId: string): Promise<boolean> {
    if (!this.metaEnabled) {
      this.logger.debug(`[MOCK portal] → ${to}: ${body.substring(0, 60)}`);
      return true;
    }
    try {
      const res = await fetch(
        `https://graph.facebook.com/v18.0/${phoneNumberId}/messages`,
        {
          method: 'POST',
          headers: {
            'Content-Type':  'application/json',
            'Authorization': `Bearer ${this.metaToken}`,
          },
          body: JSON.stringify({
            messaging_product: 'whatsapp',
            to,
            type: 'text',
            text: { body },
          }),
        },
      );
      if (!res.ok) {
        const err = await res.text();
        this.logger.error(`Meta portal send hatası: ${err}`);
        return false;
      }
      return true;
    } catch (err) {
      this.logger.error('Meta portal send exception:', err);
      return false;
    }
  }

  // ── Tek mesajı işle ────────────────────────────────────────────────────
  private async processMessage(msg: any, value: any): Promise<void> {
    const from   = msg.from;
    const waName = value?.contacts?.[0]?.profile?.name;
    const waId   = msg.id;
    const msgType= msg.type;

    let text: string = '';
    let mediaUrl: string | null = null;
    let mediaType: string | null = null;

    if (msgType === 'text') {
      text = msg.text?.body || '';
    } else if (['image', 'document'].includes(msgType)) {
      const mediaObj = msg[msgType];
      mediaUrl  = mediaObj?.id ? `https://graph.facebook.com/v18.0/${mediaObj.id}` : null;
      mediaType = mediaObj?.mime_type || msgType;
      text      = msg.caption || `[${msgType} gönderildi]`;
    } else if (msgType === 'interactive') {
      const inter = msg.interactive;
      text = inter?.list_reply?.title || inter?.button_reply?.title || '';
    } else {
      return;
    }

    const phoneNumberId = value?.metadata?.phone_number_id || '';
    const cleanText = text;
    const client = await this.clientsSvc.findByPhoneNumberId(phoneNumberId);
    if (!client) {
      this.logger.warn(`Client bulunamadi. phone_number_id=${phoneNumberId} from=${from}`);
      return;
    }
    const monthlyLimit = await this.getMonthlyLimit(client.id);
    const quotaResult  = await this.quotaSvc.checkAndRecord(client.id, from, monthlyLimit, waName);

    if (!quotaResult.allowed) {
      const tokenInfo = await this.clientsSvc.getToken(client.id);
      if (tokenInfo) await this.sendText(from, 'Aylık hasta karşılama limitinize ulaşıldı.', tokenInfo.phone_number_id);
      return;
    }

    const conversation = await this.getOrCreateConversation(
      client.id,
      quotaResult.patientId,
    );

    // AI aktif değilse sadece kaydet
    if (['with_user', 'assigned'].includes(conversation.status)) {
      await this.history.saveMessage({
        conversation_id: conversation.id,
        direction:    'inbound',
        sender_type:  'patient',
        message_type: msgType === 'image' ? 'image' : msgType === 'document' ? 'document' : 'text',
        body:         cleanText || text,
        media_url:    mediaUrl,
        media_type:   mediaType,
        wa_message_id: waId,
        sent_at:      new Date(),
      });

      if (conversation.status === 'assigned' && !conversation.template_replied) {
        await this.convRepo.update(
          { id: conversation.id },
          { template_replied: true, updated_at: new Date() },
        );
      }
      return;
    }

    if (conversation.status === 'pending_takeover') {
      await this.history.saveMessage({
        conversation_id: conversation.id,
        direction:    'inbound',
        sender_type:  'patient',
        message_type: 'text',
        body:         cleanText || text,
        wa_message_id: waId,
        sent_at:      new Date(),
      });
      return;
    }

    // Gelen mesajı kaydet
    await this.history.saveMessage({
      conversation_id: conversation.id,
      direction:    'inbound',
      sender_type:  'patient',
      message_type: msgType === 'image' ? 'image' : msgType === 'document' ? 'document' : 'text',
      body:         cleanText || text,
      media_url:    mediaUrl,
      media_type:   mediaType,
      wa_message_id: waId,
      sent_at:      new Date(),
    });

    await this.convRepo.update({ id: conversation.id }, { updated_at: new Date() });

    // AI yanıtı
    const aiReply = await this.generateAiReply(client.id, conversation.id, cleanText || text);
    if (!aiReply) return;

    // İnsan devralma sinyali var mı?
    const needsHuman = this.detectHumanRequest(aiReply);
    if (needsHuman) {
      await this.convRepo.update(
        { id: conversation.id },
        { status: 'pending_takeover', updated_at: new Date() },
      );
    }

    const tokenInfo = await this.clientsSvc.getToken(client.id);
    if (tokenInfo) await this.sendText(from, aiReply, tokenInfo.phone_number_id);

    await this.history.saveMessage({
      conversation_id: conversation.id,
      direction:    'outbound',
      sender_type:  'ai',
      message_type: 'text',
      body:         aiReply,
      sent_at:      new Date(),
    });
  }

  // ── Konuşma özeti çıkar ────────────────────────────────────────────────
  async generateSummary(conversationId: number, clientId: number): Promise<{
    summary: string;
    treatment: string;
    pipeline: string;
    cached: boolean;
  } | null> {
    try {
      const conv = await this.convRepo.findOne({
        where: { id: conversationId, client_id: clientId },
      });
      if (!conv) return null;

      // Son mesaj ID'sini al
      const lastMsg = await this.dataSource.query(
        'SELECT id FROM messages WHERE conversation_id = ? ORDER BY sent_at DESC LIMIT 1',
        [conversationId],
      );
      const lastMsgId = lastMsg?.[0]?.id || 0;

      // Cache kontrolü — son mesaj değişmediyse eski özeti döndür
      if (conv.summary_text && conv.summary_last_msg_id === lastMsgId) {
        return {
          summary: conv.summary_text,
          treatment: '',
          pipeline: '',
          cached: true,
        };
      }

      // Konuşma geçmişi
      const history = await this.history.getHistory(conversationId, 50);
      if (!history.length) return null;

      const historyText = history
        .map(m => `${m.role === 'user' ? 'Hasta' : 'AI'}: ${m.content}`)
        .join('\n');

      const messages: OpenAIChatMessage[] = [
        {
          role: 'system',
          content: `Sen bir sağlık turizmi konuşma analiz asistanısın.
Aşağıdaki hasta-AI konuşmasını analiz et ve şu formatta JSON döndür:

{
  "summary": "Konuşmanın TAMAMEN TÜRKÇE özeti (2-3 cümle, hastanın ne istediği, ne sorduğu, sürecin nerede kaldığı)",
  "treatment": "İlgilenilen tedavi Türkçe (implant, saç ekimi, burun estetiği vs.)",
  "pipeline": "Süreç durumu, şunlardan biri: new, photo_pending, price_given, followup, won, lost"
}

ÖNEMLİ: summary alanını TAMAMEN TÜRKÇE yaz. Konuşma hangi dilde olursa olsun özet Türkçe olmalı. Arapça, İngilizce veya başka dilde kelime KULLANMA.
SADECE JSON döndür, başka bir şey yazma.`,
        },
        { role: 'user', content: historyText },
      ];

      const controller = new AbortController();
      const timeout = setTimeout(() => controller.abort(), this.openAiTimeout);

      const res = await fetch(this.openAiUrl, {
        method: 'POST',
        headers: {
          'Content-Type':  'application/json',
          'Authorization': `Bearer ${this.openAiKey}`,
        },
        body: JSON.stringify({
          model: this.openAiModel,
          messages,
          max_completion_tokens: 500,
          temperature: 0.3,
        }),
        signal: controller.signal,
      });

      clearTimeout(timeout);

      if (!res.ok) {
        this.logger.error(`OpenAI summary error: ${await res.text()}`);
        return null;
      }

      const data = await res.json() as any;
      const usage = data?.usage;
      const content = data?.choices?.[0]?.message?.content?.trim() || '';

      // Token kullanımı logla
      if (usage) {
        await this.logTokenUsage(clientId, conversationId, 'summary', usage);
      }

      // JSON parse
      let parsed: any;
      try {
        parsed = JSON.parse(content.replace(/```json|```/g, '').trim());
      } catch {
        this.logger.warn('Summary JSON parse hatası');
        parsed = { summary: content, treatment: '', pipeline: '' };
      }

      const summary = parsed.summary || content;
      const treatment = parsed.treatment || '';
      const pipeline = parsed.pipeline || '';

      // DB güncelle
      await this.convRepo.update(
        { id: conversationId },
        {
          summary_text: summary,
          topic_summary: treatment ? `${treatment} - ${this.pipelineLabel(pipeline)}` : null,
          summary_last_msg_id: lastMsgId,
        },
      );

      // Hasta bilgilerini güncelle
      const updates: Record<string, any> = {};
      if (treatment) updates.treatment_interest = treatment;
      if (pipeline && pipeline !== 'new') {
        const patient = await this.patientRepo.findOne({ where: { id: conv.patient_id } });
        if (patient && patient.pipeline_status !== pipeline) {
          updates.pipeline_status = pipeline;
          updates.pipeline_updated_at = new Date();
          await this.dataSource.query(
            `INSERT INTO patient_timeline (patient_id, client_id, from_status, to_status, changed_by, note)
             VALUES (?, ?, ?, ?, 'ai', ?)`,
            [conv.patient_id, clientId, patient.pipeline_status, pipeline, summary],
          );
        }
      }
      if (Object.keys(updates).length > 0) {
        await this.patientRepo.update({ id: conv.patient_id }, updates);
      }

      return { summary, treatment, pipeline, cached: false };
    } catch (err) {
      this.logger.error('generateSummary error:', err);
      return null;
    }
  }

  private pipelineLabel(status: string): string {
    const map: Record<string, string> = {
      new: 'yeni',
      photo_pending: 'fotoğraf bekleniyor',
      price_given: 'fiyat verildi',
      followup: 'takipte',
      won: 'kazanıldı',
      lost: 'kaybedildi',
    };
    return map[status] || status;
  }

  // ── Token kullanım logu ────────────────────────────────────────────────
  private async logTokenUsage(
    clientId: number,
    conversationId: number | null,
    type: 'chat' | 'summary',
    usage: { prompt_tokens: number; completion_tokens: number; total_tokens: number },
  ): Promise<void> {
    try {
      await this.dataSource.query(
        `INSERT INTO ai_usage_log (client_id, conversation_id, type, model, prompt_tokens, completion_tokens, total_tokens)
         VALUES (?, ?, ?, ?, ?, ?, ?)`,
        [clientId, conversationId, type, this.openAiModel, usage.prompt_tokens, usage.completion_tokens, usage.total_tokens],
      );
    } catch (err) {
      this.logger.error('logTokenUsage error:', err);
    }
  }

  // ── Konuşma bul veya oluştur ───────────────────────────────────────────
  private async getOrCreateConversation(
    clientId: number,
    patientId: number,
  ): Promise<Conversation> {
    const existing = await this.convRepo.findOne({
      where: { client_id: clientId, patient_id: patientId },
      order: { started_at: 'DESC' },
    });

    if (existing && existing.status !== 'closed') return existing;

    const conv = this.convRepo.create({
      client_id:  clientId,
      patient_id: patientId,
      status:     'ai_active',
    });
    return this.convRepo.save(conv);
  }

  // ── AI yanıtı üret ─────────────────────────────────────────────────────
  private async generateAiReply(
    clientId: number,
    conversationId: number,
    userMessage: string,
  ): Promise<string | null> {
    try {
      const coreRule = await this.coreRulesSvc.getActiveRule();

      const module = await this.moduleRepo.findOne({
        where: { client_id: clientId, status: 'active' },
        order: { sort_order: 'ASC' },
      });

      const historyMessages: OpenAIChatMessage[] = this.historyEnabled
        ? await this.history.getHistory(conversationId)
        : [];

      const messages: OpenAIChatMessage[] = [
        ...(coreRule ? [{ role: 'system' as OpenAIChatRole, content: coreRule }] : []),
        ...(module?.prompt ? [{ role: 'system' as OpenAIChatRole, content: module.prompt }] : []),
        ...historyMessages,
        { role: 'user', content: userMessage },
      ];

      const controller = new AbortController();
      const timeout    = setTimeout(() => controller.abort(), this.openAiTimeout);

      const res = await fetch(this.openAiUrl, {
        method: 'POST',
        headers: {
          'Content-Type':  'application/json',
          'Authorization': `Bearer ${this.openAiKey}`,
        },
        body: JSON.stringify({
          model:       this.openAiModel,
          messages,
          max_completion_tokens: this.openAiMaxTokens,
          temperature: 0.7,
        }),
        signal: controller.signal,
      });

      clearTimeout(timeout);

      if (!res.ok) {
        const errBody = await res.text();
        this.logger.error(`OpenAI HTTP ${res.status}: ${errBody}`);
        return null;
      }

      const data = await res.json() as any;
      const usage = data?.usage;

      // Token kullanımı logla
      if (usage) {
        await this.logTokenUsage(clientId, conversationId, 'chat', usage);
      }

      return data?.choices?.[0]?.message?.content?.trim() || null;

    } catch (err: any) {
      if (err?.name === 'AbortError') this.logger.error('OpenAI timeout');
      else this.logger.error('OpenAI error:', err);
      return null;
    }
  }

  // ── İnsan talebi tespiti ───────────────────────────────────────────────
  private detectHumanRequest(aiReply: string): boolean {
    const signals = [
      'sizi bir koordinatörümüzle',
      'sizi bir sağlık profesyoneliyle',
      'uzmanımızla görüştürmek',
      'bir yetkilimize bağlamak',
      'konuşmak ister misiniz',
    ];
    const lower = aiReply.toLowerCase();
    return signals.some(s => lower.includes(s));
  }

  // ── Meta'ya mesaj gönder ───────────────────────────────────────────────
  private async sendText(to: string, text: string, phoneNumberId: string): Promise<void> {
    if (!this.metaEnabled) {
      this.logger.debug(`[MOCK] → ${to}: ${text.substring(0, 80)}`);
      return;
    }
    try {
      const res = await fetch(
        `https://graph.facebook.com/v18.0/${phoneNumberId}/messages`,
        {
          method: 'POST',
          headers: {
            'Content-Type':  'application/json',
            'Authorization': `Bearer ${this.metaToken}`,
          },
          body: JSON.stringify({
            messaging_product: 'whatsapp',
            to,
            type: 'text',
            text: { body: text },
          }),
        },
      );
      if (!res.ok) this.logger.error(`Meta send hatası: ${await res.text()}`);
    } catch (err) {
      this.logger.error('Meta send exception:', err);
    }
  }

  // ── Aylık limit al ────────────────────────────────────────────────────
  private async getMonthlyLimit(clientId: number): Promise<number> {
    const result = await this.dataSource.query(
      `SELECT p.monthly_quota
       FROM subscriptions s
       JOIN plans p ON p.id = s.plan_id
       WHERE s.client_id = ? AND s.status = 'active'
       ORDER BY s.created_at DESC LIMIT 1`,
      [clientId],
    );
    return result?.[0]?.monthly_quota || 20;
  }
}
