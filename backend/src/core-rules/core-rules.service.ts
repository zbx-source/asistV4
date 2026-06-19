import { Injectable, Logger } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { CoreRule } from './core-rule.entity';

@Injectable()
export class CoreRulesService {
  private readonly logger = new Logger(CoreRulesService.name);
  private cachedRule: string | null = null;
  private cacheTime: number = 0;
  private readonly CACHE_TTL_MS = 5 * 60 * 1000; // 5 dakika

  constructor(
    @InjectRepository(CoreRule)
    private readonly repo: Repository<CoreRule>,
  ) {}

  async getActiveRule(): Promise<string> {
    const now = Date.now();

    // Cache geçerliyse cache'den dön
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

  // Cache'i temizle (admin yeni rule ekleyince çağrılır)
  clearCache(): void {
    this.cachedRule = null;
    this.cacheTime = 0;
  }
}
