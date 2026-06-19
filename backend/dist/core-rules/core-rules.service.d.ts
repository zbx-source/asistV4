import { Repository } from 'typeorm';
import { CoreRule } from './core-rule.entity';
export declare class CoreRulesService {
    private readonly repo;
    private readonly logger;
    private cachedRule;
    private cacheTime;
    private readonly CACHE_TTL_MS;
    constructor(repo: Repository<CoreRule>);
    getActiveRule(): Promise<string>;
    clearCache(): void;
}
