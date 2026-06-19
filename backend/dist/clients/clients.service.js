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
var ClientsService_1;
Object.defineProperty(exports, "__esModule", { value: true });
exports.ClientsService = void 0;
const common_1 = require("@nestjs/common");
const typeorm_1 = require("@nestjs/typeorm");
const typeorm_2 = require("typeorm");
const client_entity_1 = require("./client.entity");
const client_token_entity_1 = require("./client-token.entity");
let ClientsService = ClientsService_1 = class ClientsService {
    constructor(clientRepo, tokenRepo) {
        this.clientRepo = clientRepo;
        this.tokenRepo = tokenRepo;
        this.logger = new common_1.Logger(ClientsService_1.name);
    }
    async findByToken(token) {
        const ct = await this.tokenRepo.findOne({
            where: { token, status: 'active' },
        });
        if (!ct)
            return null;
        return this.clientRepo.findOne({
            where: { id: ct.client_id, status: 'active' },
        });
    }
    async findByPhoneNumberId(phoneNumberId) {
        const ct = await this.tokenRepo.findOne({
            where: { phone_number_id: phoneNumberId, status: 'active' },
        });
        if (!ct)
            return null;
        return this.clientRepo.findOne({
            where: { id: ct.client_id, status: 'active' },
        });
    }
    async getToken(clientId) {
        return this.tokenRepo.findOne({
            where: { client_id: clientId, status: 'active' },
        });
    }
    async findById(id) {
        return this.clientRepo.findOne({ where: { id } });
    }
};
exports.ClientsService = ClientsService;
exports.ClientsService = ClientsService = ClientsService_1 = __decorate([
    (0, common_1.Injectable)(),
    __param(0, (0, typeorm_1.InjectRepository)(client_entity_1.Client)),
    __param(1, (0, typeorm_1.InjectRepository)(client_token_entity_1.ClientToken)),
    __metadata("design:paramtypes", [typeorm_2.Repository,
        typeorm_2.Repository])
], ClientsService);
//# sourceMappingURL=clients.service.js.map