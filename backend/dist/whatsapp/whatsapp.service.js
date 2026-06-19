"use strict";
var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
var __metadata = (this && this.__metadata) || function (k, v) {
    if (typeof Reflect === "object" && typeof Reflect.metadata === "function") return Reflect.metadata(k, v);
};
var __param = (this && this.__param) || function (paramIndex, decorator) {
    return function (target, key) { decorator(target, key, paramIndex); }
};
var WhatsAppService_1;
Object.defineProperty(exports, "__esModule", { value: true });
exports.WhatsAppService = void 0;
const common_1 = require("@nestjs/common");
const typeorm_1 = require("@nestjs/typeorm");
const typeorm_2 = require("typeorm");
const config_1 = require("@nestjs/config");
const clients_service_1 = require("../clients/clients.service");
const core_rules_service_1 = require("../core-rules/core-rules.service");
const quota_service_1 = require("../quota/quota.service");
const conversation_history_service_1 = require("./conversation-history.service");
const conversation_entity_1 = require("./conversation.entity");
const treatment_module_entity_1 = require("./treatment-module.entity");
const TOKEN_PREFIX = '#zb:';
let WhatsAppService = WhatsAppService_1 = class WhatsAppService {
    constructor(cfg, clientsSvc, coreRulesSvc, quotaSvc, history, convRepo, moduleRepo, dataSource) {
        this.cfg = cfg;
        this.clientsSvc = clientsSvc;
        this.coreRulesSvc = coreRulesSvc;
        this.quotaSvc = quotaSvc;
        this.history = history;
        this.convRepo = convRepo;
        this.moduleRepo = moduleRepo;
        this.dataSource = dataSource;
        this.logger = new common_1.Logger(WhatsAppService_1.name);
        this.verifyToken = cfg.get('META_VERIFY_TOKEN', 'dev-token');
        this.metaToken = cfg.get('META_TOKEN', '');
        this.metaEnabled = cfg.get('META_ENABLED', 'false') === 'true';
        this.openAiKey = cfg.get('OPENAI_API_KEY', '');
        this.openAiModel = cfg.get('OPENAI_MODEL', 'gpt-4o-mini');
        this.openAiUrl = cfg.get('OPENAI_API_URL', 'https://api.openai.com/v1/chat/completions');
        this.openAiTimeout = Number(cfg.get('OPENAI_TIMEOUT_MS', '60000'));
        this.openAiMaxTokens = Number(cfg.get('OPENAI_MAX_TOKENS', '2000'));
        this.historyEnabled = cfg.get('CONVERSATION_HISTORY_ENABLED', 'true') === 'true';
    }
    verify(mode, token, challenge) {
        if (mode === 'subscribe' && token === this.verifyToken) {
            this.logger.log('Webhook doğrulandı');
            return parseInt(challenge, 10);
        }
        return 'Forbidden';
    }
    async handleIncoming(payload) {
        try {
            const msgs = payload?.entry?.[0]?.changes?.[0]?.value?.messages;
            if (!msgs?.length)
                return;
            const value = payload.entry[0].changes[0].value;
            for (const msg of msgs)
                await this.processMessage(msg, value);
        }
        catch (err) {
            this.logger.error('handleIncoming error:', err);
        }
    }
    async sendFromPortal(to, body, phoneNumberId) {
        if (!this.metaEnabled) {
            this.logger.debug(`[MOCK portal] → ${to}: ${body.substring(0, 60)}`);
            return true;
        }
        try {
            const res = await fetch(`https://graph.facebook.com/v18.0/${phoneNumberId}/messages`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.metaToken}`,
                },
                body: JSON.stringify({
                    messaging_product: 'whatsapp',
                    to,
                    type: 'text',
                    text: { body },
                }),
            });
            if (!res.ok) {
                const err = await res.text();
                this.logger.error(`Meta portal send hatası: ${err}`);
                return false;
            }
            return true;
        }
        catch (err) {
            this.logger.error('Meta portal send exception:', err);
            return false;
        }
    }
    async processMessage(msg, value) {
        const from = msg.from;
        const waName = value?.contacts?.[0]?.profile?.name;
        const waId = msg.id;
        const msgType = msg.type;
        let text = '';
        let mediaUrl = null;
        let mediaType = null;
        if (msgType === 'text') {
            text = msg.text?.body || '';
        }
        else if (['image', 'document'].includes(msgType)) {
            const mediaObj = msg[msgType];
            mediaUrl = mediaObj?.id ? `https://graph.facebook.com/v18.0/${mediaObj.id}` : null;
            mediaType = mediaObj?.mime_type || msgType;
            text = msg.caption || `[${msgType} gönderildi]`;
        }
        else if (msgType === 'interactive') {
            const inter = msg.interactive;
            text = inter?.list_reply?.title || inter?.button_reply?.title || '';
        }
        else {
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
        const quotaResult = await this.quotaSvc.checkAndRecord(client.id, from, monthlyLimit, waName);
        if (!quotaResult.allowed) {
            const tokenInfo = await this.clientsSvc.getToken(client.id);
            if (tokenInfo)
                await this.sendText(from, 'Aylık hasta karşılama limitinize ulaşıldı.', tokenInfo.phone_number_id);
            return;
        }
        const conversation = await this.getOrCreateConversation(client.id, quotaResult.patientId);
        if (['with_user', 'assigned'].includes(conversation.status)) {
            await this.history.saveMessage({
                conversation_id: conversation.id,
                direction: 'inbound',
                sender_type: 'patient',
                message_type: msgType === 'image' ? 'image' : msgType === 'document' ? 'document' : 'text',
                body: cleanText || text,
                media_url: mediaUrl,
                media_type: mediaType,
                wa_message_id: waId,
                sent_at: new Date(),
            });
            if (conversation.status === 'assigned' && !conversation.template_replied) {
                await this.convRepo.update({ id: conversation.id }, { template_replied: true, updated_at: new Date() });
            }
            return;
        }
        if (conversation.status === 'pending_takeover') {
            await this.history.saveMessage({
                conversation_id: conversation.id,
                direction: 'inbound',
                sender_type: 'patient',
                message_type: 'text',
                body: cleanText || text,
                wa_message_id: waId,
                sent_at: new Date(),
            });
            return;
        }
        await this.history.saveMessage({
            conversation_id: conversation.id,
            direction: 'inbound',
            sender_type: 'patient',
            message_type: msgType === 'image' ? 'image' : msgType === 'document' ? 'document' : 'text',
            body: cleanText || text,
            media_url: mediaUrl,
            media_type: mediaType,
            wa_message_id: waId,
            sent_at: new Date(),
        });
        await this.convRepo.update({ id: conversation.id }, { updated_at: new Date() });
        const aiReply = await this.generateAiReply(client.id, conversation.id, cleanText || text);
        if (!aiReply)
            return;
        const needsHuman = this.detectHumanRequest(aiReply);
        if (needsHuman) {
            await this.convRepo.update({ id: conversation.id }, { status: 'pending_takeover', updated_at: new Date() });
        }
        const tokenInfo = await this.clientsSvc.getToken(client.id);
        if (tokenInfo)
            await this.sendText(from, aiReply, tokenInfo.phone_number_id);
        await this.history.saveMessage({
            conversation_id: conversation.id,
            direction: 'outbound',
            sender_type: 'ai',
            message_type: 'text',
            body: aiReply,
            sent_at: new Date(),
        });
    }
    async getOrCreateConversation(clientId, patientId) {
        const existing = await this.convRepo.findOne({
            where: { client_id: clientId, patient_id: patientId },
            order: { started_at: 'DESC' },
        });
        if (existing && existing.status !== 'closed')
            return existing;
        const conv = this.convRepo.create({
            client_id: clientId,
            patient_id: patientId,
            status: 'ai_active',
        });
        return this.convRepo.save(conv);
    }
    async generateAiReply(clientId, conversationId, userMessage) {
        try {
            const coreRule = await this.coreRulesSvc.getActiveRule();
            const module = await this.moduleRepo.findOne({
                where: { client_id: clientId, status: 'active' },
                order: { sort_order: 'ASC' },
            });
            const historyMessages = this.historyEnabled
                ? await this.history.getHistory(conversationId)
                : [];
            const messages = [
                ...(coreRule ? [{ role: 'system', content: coreRule }] : []),
                ...(module?.prompt ? [{ role: 'system', content: module.prompt }] : []),
                ...historyMessages,
                { role: 'user', content: userMessage },
            ];
            const controller = new AbortController();
            const timeout = setTimeout(() => controller.abort(), this.openAiTimeout);
            const res = await fetch(this.openAiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.openAiKey}`,
                },
                body: JSON.stringify({
                    model: this.openAiModel,
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
            const data = await res.json();
            return data?.choices?.[0]?.message?.content?.trim() || null;
        }
        catch (err) {
            if (err?.name === 'AbortError')
                this.logger.error('OpenAI timeout');
            else
                this.logger.error('OpenAI error:', err);
            return null;
        }
    }
    detectHumanRequest(aiReply) {
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
    async sendText(to, text, phoneNumberId) {
        if (!this.metaEnabled) {
            this.logger.debug(`[MOCK] → ${to}: ${text.substring(0, 80)}`);
            return;
        }
        try {
            const res = await fetch(`https://graph.facebook.com/v18.0/${phoneNumberId}/messages`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.metaToken}`,
                },
                body: JSON.stringify({
                    messaging_product: 'whatsapp',
                    to,
                    type: 'text',
                    text: { body: text },
                }),
            });
            if (!res.ok)
                this.logger.error(`Meta send hatası: ${await res.text()}`);
        }
        catch (err) {
            this.logger.error('Meta send exception:', err);
        }
    }
    async getMonthlyLimit(clientId) {
        const result = await this.dataSource.query(`SELECT p.monthly_quota
       FROM subscriptions s
       JOIN plans p ON p.id = s.plan_id
       WHERE s.client_id = ? AND s.status = 'active'
       ORDER BY s.created_at DESC LIMIT 1`, [clientId]);
        return result?.[0]?.monthly_quota || 20;
    }
};
exports.WhatsAppService = WhatsAppService;
exports.WhatsAppService = WhatsAppService = WhatsAppService_1 = __decorate([
    (0, common_1.Injectable)(),
    __param(5, (0, typeorm_1.InjectRepository)(conversation_entity_1.Conversation)),
    __param(6, (0, typeorm_1.InjectRepository)(treatment_module_entity_1.TreatmentModule)),
    __metadata("design:paramtypes", [config_1.ConfigService,
        clients_service_1.ClientsService,
        core_rules_service_1.CoreRulesService,
        quota_service_1.QuotaService,
        conversation_history_service_1.ConversationHistoryService,
        typeorm_2.Repository,
        typeorm_2.Repository,
        typeorm_2.DataSource])
], WhatsAppService);
//# sourceMappingURL=whatsapp.service.js.map