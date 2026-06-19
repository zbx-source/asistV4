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
var WhatsAppController_1, PortalController_1;
Object.defineProperty(exports, "__esModule", { value: true });
exports.PortalController = exports.WhatsAppController = void 0;
const common_1 = require("@nestjs/common");
const whatsapp_service_1 = require("./whatsapp.service");
let WhatsAppController = WhatsAppController_1 = class WhatsAppController {
    constructor(svc) {
        this.svc = svc;
        this.logger = new common_1.Logger(WhatsAppController_1.name);
    }
    verify(mode, token, challenge) {
        return this.svc.verify(mode, token, challenge);
    }
    async incoming(payload) {
        try {
            await this.svc.handleIncoming(payload);
        }
        catch (err) {
            this.logger.error('handleIncoming hatası:', err);
        }
        return { ok: true };
    }
};
exports.WhatsAppController = WhatsAppController;
__decorate([
    (0, common_1.Get)(),
    __param(0, (0, common_1.Query)('hub.mode')),
    __param(1, (0, common_1.Query)('hub.verify_token')),
    __param(2, (0, common_1.Query)('hub.challenge')),
    __metadata("design:type", Function),
    __metadata("design:paramtypes", [String, String, String]),
    __metadata("design:returntype", void 0)
], WhatsAppController.prototype, "verify", null);
__decorate([
    (0, common_1.Post)(),
    __param(0, (0, common_1.Body)()),
    __metadata("design:type", Function),
    __metadata("design:paramtypes", [Object]),
    __metadata("design:returntype", Promise)
], WhatsAppController.prototype, "incoming", null);
exports.WhatsAppController = WhatsAppController = WhatsAppController_1 = __decorate([
    (0, common_1.Controller)('webhook'),
    __metadata("design:paramtypes", [whatsapp_service_1.WhatsAppService])
], WhatsAppController);
let PortalController = PortalController_1 = class PortalController {
    constructor(svc) {
        this.svc = svc;
        this.logger = new common_1.Logger(PortalController_1.name);
    }
    async send(body) {
        try {
            const ok = await this.svc.sendFromPortal(body.to, body.body, body.phone_number_id);
            return { ok };
        }
        catch (err) {
            this.logger.error('portal send hatası:', err);
            return { ok: false };
        }
    }
};
exports.PortalController = PortalController;
__decorate([
    (0, common_1.Post)('send'),
    __param(0, (0, common_1.Body)()),
    __metadata("design:type", Function),
    __metadata("design:paramtypes", [Object]),
    __metadata("design:returntype", Promise)
], PortalController.prototype, "send", null);
exports.PortalController = PortalController = PortalController_1 = __decorate([
    (0, common_1.Controller)('portal'),
    __metadata("design:paramtypes", [whatsapp_service_1.WhatsAppService])
], PortalController);
//# sourceMappingURL=whatsapp.controller.js.map