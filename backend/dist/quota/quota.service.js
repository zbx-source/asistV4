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
var QuotaService_1;
Object.defineProperty(exports, "__esModule", { value: true });
exports.QuotaService = void 0;
const common_1 = require("@nestjs/common");
const typeorm_1 = require("@nestjs/typeorm");
const typeorm_2 = require("typeorm");
const quota_usage_entity_1 = require("./quota-usage.entity");
const patient_entity_1 = require("./patient.entity");
let QuotaService = QuotaService_1 = class QuotaService {
    constructor(quotaRepo, patientRepo, dataSource) {
        this.quotaRepo = quotaRepo;
        this.patientRepo = patientRepo;
        this.dataSource = dataSource;
        this.logger = new common_1.Logger(QuotaService_1.name);
    }
    async checkAndRecord(clientId, phone, monthlyLimit, waName) {
        const now = new Date();
        const year = now.getFullYear();
        const month = now.getMonth() + 1;
        return this.dataSource.transaction(async (em) => {
            let patient = await em.findOne(patient_entity_1.Patient, {
                where: { client_id: clientId, phone },
            });
            const isNew = !patient;
            if (!patient) {
                patient = em.create(patient_entity_1.Patient, {
                    client_id: clientId,
                    phone,
                    name: waName || null,
                    country_code: this.extractCountryCode(phone),
                    first_contact: now,
                    last_contact: now,
                });
                await em.save(patient);
            }
            else {
                await em.update(patient_entity_1.Patient, { id: patient.id }, { last_contact: now });
            }
            let quota = await em.findOne(quota_usage_entity_1.QuotaUsage, {
                where: { client_id: clientId, year, month },
            });
            if (!quota) {
                quota = em.create(quota_usage_entity_1.QuotaUsage, {
                    client_id: clientId,
                    year,
                    month,
                    used_count: 0,
                });
                await em.save(quota);
            }
            if (isNew) {
                await em.increment(quota_usage_entity_1.QuotaUsage, { id: quota.id }, 'used_count', 1);
                quota.used_count += 1;
            }
            const allowed = quota.used_count <= monthlyLimit;
            return {
                allowed,
                used: quota.used_count,
                limit: monthlyLimit,
                isNew,
                patientId: patient.id,
            };
        });
    }
    async getQuota(clientId) {
        const now = new Date();
        return this.quotaRepo.findOne({
            where: {
                client_id: clientId,
                year: now.getFullYear(),
                month: now.getMonth() + 1,
            },
        });
    }
    extractCountryCode(phone) {
        if (phone.startsWith('+90'))
            return 'TR';
        if (phone.startsWith('+49'))
            return 'DE';
        if (phone.startsWith('+44'))
            return 'GB';
        if (phone.startsWith('+971'))
            return 'AE';
        if (phone.startsWith('+966'))
            return 'SA';
        if (phone.startsWith('+7'))
            return 'RU';
        if (phone.startsWith('+33'))
            return 'FR';
        if (phone.startsWith('+31'))
            return 'NL';
        if (phone.startsWith('+43'))
            return 'AT';
        if (phone.startsWith('+41'))
            return 'CH';
        return null;
    }
};
exports.QuotaService = QuotaService;
exports.QuotaService = QuotaService = QuotaService_1 = __decorate([
    (0, common_1.Injectable)(),
    __param(0, (0, typeorm_1.InjectRepository)(quota_usage_entity_1.QuotaUsage)),
    __param(1, (0, typeorm_1.InjectRepository)(patient_entity_1.Patient)),
    __metadata("design:paramtypes", [typeorm_2.Repository,
        typeorm_2.Repository,
        typeorm_2.DataSource])
], QuotaService);
//# sourceMappingURL=quota.service.js.map