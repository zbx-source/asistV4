"use strict";
var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.WhatsAppModule = void 0;
const common_1 = require("@nestjs/common");
const typeorm_1 = require("@nestjs/typeorm");
const whatsapp_controller_1 = require("./whatsapp.controller");
const whatsapp_service_1 = require("./whatsapp.service");
const conversation_history_service_1 = require("./conversation-history.service");
const conversation_entity_1 = require("./conversation.entity");
const message_entity_1 = require("./message.entity");
const treatment_module_entity_1 = require("./treatment-module.entity");
const clients_module_1 = require("../clients/clients.module");
const core_rules_module_1 = require("../core-rules/core-rules.module");
const quota_module_1 = require("../quota/quota.module");
let WhatsAppModule = class WhatsAppModule {
};
exports.WhatsAppModule = WhatsAppModule;
exports.WhatsAppModule = WhatsAppModule = __decorate([
    (0, common_1.Module)({
        imports: [
            typeorm_1.TypeOrmModule.forFeature([conversation_entity_1.Conversation, message_entity_1.Message, treatment_module_entity_1.TreatmentModule]),
            clients_module_1.ClientsModule,
            core_rules_module_1.CoreRulesModule,
            quota_module_1.QuotaModule,
        ],
        controllers: [whatsapp_controller_1.WhatsAppController, whatsapp_controller_1.PortalController],
        providers: [whatsapp_service_1.WhatsAppService, conversation_history_service_1.ConversationHistoryService],
    })
], WhatsAppModule);
//# sourceMappingURL=whatsapp.module.js.map