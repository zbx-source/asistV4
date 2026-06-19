import { Repository } from 'typeorm';
import { Client } from './client.entity';
import { ClientToken } from './client-token.entity';
export declare class ClientsService {
    private readonly clientRepo;
    private readonly tokenRepo;
    private readonly logger;
    constructor(clientRepo: Repository<Client>, tokenRepo: Repository<ClientToken>);
    findByToken(token: string): Promise<Client | null>;
    findByPhoneNumberId(phoneNumberId: string): Promise<Client | null>;
    getToken(clientId: number): Promise<ClientToken | null>;
    findById(id: number): Promise<Client | null>;
}
