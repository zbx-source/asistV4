import { Injectable, Logger } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
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

@Injectable()
export class QuotaService {
  private readonly logger = new Logger(QuotaService.name);

  constructor(
    @InjectRepository(QuotaUsage)
    private readonly quotaRepo: Repository<QuotaUsage>,

    @InjectRepository(Patient)
    private readonly patientRepo: Repository<Patient>,

    private readonly dataSource: DataSource,
  ) {}

  async checkAndRecord(
    clientId: number,
    phone: string,
    monthlyLimit: number,
    waName?: string,
  ): Promise<QuotaCheckResult> {
    const now = new Date();
    const year  = now.getFullYear();
    const month = now.getMonth() + 1;

    return this.dataSource.transaction(async (em) => {
      let patient = await em.findOne(Patient, {
        where: { client_id: clientId, phone },
      });

      const isNew = !patient;

      if (!patient) {
        patient = em.create(Patient, {
          client_id:    clientId,
          phone,
          name:         waName || null,
          country_code: this.extractCountryCode(phone),
          first_contact: now,
          last_contact:  now,
        });
        await em.save(patient);
      } else {
        await em.update(Patient, { id: patient.id }, { last_contact: now });
      }

      let quota = await em.findOne(QuotaUsage, {
        where: { client_id: clientId, year, month },
      });

      if (!quota) {
        quota = em.create(QuotaUsage, {
          client_id: clientId,
          year,
          month,
          used_count: 0,
        });
        await em.save(quota);
      }

      if (isNew) {
        await em.increment(QuotaUsage, { id: quota.id }, 'used_count', 1);
        quota.used_count += 1;
      }

      const allowed = quota.used_count <= monthlyLimit;

      return {
        allowed,
        used:      quota.used_count,
        limit:     monthlyLimit,
        isNew,
        patientId: patient.id,
      };
    });
  }

  async getQuota(clientId: number): Promise<QuotaUsage | null> {
    const now = new Date();
    return this.quotaRepo.findOne({
      where: {
        client_id: clientId,
        year:  now.getFullYear(),
        month: now.getMonth() + 1,
      },
    });
  }

  private extractCountryCode(phone: string): string | null {
    const p = phone.startsWith('+') ? phone : '+' + phone;

    const map: [string, string][] = [
      // Türkiye & yakın coğrafya
      ['+90',  'TR'], ['+994', 'AZ'], ['+995', 'GE'],
      // Arap coğrafyası
      ['+966', 'SA'], ['+971', 'AE'], ['+965', 'KW'],
      ['+968', 'OM'], ['+974', 'QA'], ['+973', 'BH'],
      ['+964', 'IQ'], ['+962', 'JO'], ['+961', 'LB'],
      ['+20',  'EG'], ['+218', 'LY'], ['+216', 'TN'],
      ['+213', 'DZ'], ['+212', 'MA'], ['+249', 'SD'],
      ['+967', 'YE'],
      // Avrupa
      ['+44',  'GB'], ['+49',  'DE'], ['+33',  'FR'],
      ['+31',  'NL'], ['+43',  'AT'], ['+41',  'CH'],
      ['+39',  'IT'], ['+34',  'ES'], ['+46',  'SE'],
      ['+32',  'BE'], ['+45',  'DK'], ['+47',  'NO'],
      // Diğer önemli pazarlar
      ['+7',   'RU'], ['+380', 'UA'], ['+998', 'UZ'],
      ['+993', 'TM'], ['+992', 'TJ'], ['+996', 'KG'],
      ['+93',  'AF'], ['+98',  'IR'], ['+92',  'PK'],
      ['+1',   'US'],
    ];

    map.sort((a, b) => b[0].length - a[0].length);

    for (const [prefix, code] of map) {
      if (p.startsWith(prefix)) return code;
    }
    return null;
  }
}