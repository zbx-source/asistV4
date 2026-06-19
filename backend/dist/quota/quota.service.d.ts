import { Repository, DataSource } from 'typeorm';
import { QuotaUsage } from './quota-usage.entity';
import { Patient } from './patient.entity';
export interface QuotaCheckResult {
    allowed: boolean;
    used: number;
    limit: number;
    isNew: boolean;
    patientId: number;
}
export declare class QuotaService {
    private readonly quotaRepo;
    private readonly patientRepo;
    private readonly dataSource;
    private readonly logger;
    constructor(quotaRepo: Repository<QuotaUsage>, patientRepo: Repository<Patient>, dataSource: DataSource);
    checkAndRecord(clientId: number, phone: string, monthlyLimit: number, waName?: string): Promise<QuotaCheckResult>;
    getQuota(clientId: number): Promise<QuotaUsage | null>;
    private extractCountryCode;
}
