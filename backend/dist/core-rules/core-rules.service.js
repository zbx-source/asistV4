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
var CoreRulesService_1;
Object.defineProperty(exports, "__esModule", { value: true });
exports.CoreRulesService = void 0;
const common_1 = require("@nestjs/common");
const typeorm_1 = require("@nestjs/typeorm");
const typeorm_2 = require("typeorm");
const core_rule_entity_1 = require("./core-rule.entity");
let CoreRulesService = CoreRulesService_1 = class CoreRulesService {
    constructor(repo) {
        this.repo = repo;
        this.logger = new common_1.Logger(CoreRulesService_1.name);
        this.cachedRule = null;
        this.cacheTime = 0;
        this.CACHE_TTL_MS = 5 * 60 * 1000;
    }
    async getActiveRule() {
        const now = Date.now();
        if (this.cachedRule && (now - this.cacheTime) < this.CACHE_TTL_MS) {
            return this.cachedRule;
        }
        const rule = await this.repo.findOne({
            where: { status: 'active' },
            order: { id: 'DESC' },
        });
        if (!rule) {
            this.logger.warn('Aktif core rule bulunamadı!');
            return '';
        }
        this.cachedRule = rule.content;
        this.cacheTime = now;
        return rule.content;
    }
    clearCache() {
        this.cachedRule = null;
        this.cacheTime = 0;
    }
};
exports.CoreRulesService = CoreRulesService;
exports.CoreRulesService = CoreRulesService = CoreRulesService_1 = __decorate([
    (0, common_1.Injectable)(),
    __param(0, (0, typeorm_1.InjectRepository)(core_rule_entity_1.CoreRule)),
    __metadata("design:paramtypes", [typeorm_2.Repository])
], CoreRulesService);
//# sourceMappingURL=core-rules.service.js.map