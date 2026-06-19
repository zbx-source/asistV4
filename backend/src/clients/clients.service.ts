import { Injectable, Logger } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { Client } from './client.entity';
import { ClientToken } from './client-token.entity';

@Injectable()
export class ClientsService {
  private readonly logger = new Logger(ClientsService.name);

  constructor(
    @InjectRepository(Client)
    private readonly clientRepo: Repository<Client>,

    @InjectRepository(ClientToken)
    private readonly tokenRepo: Repository<ClientToken>,
  ) {}

  // Token ile client bul (eski yöntem — backward compat)
  async findByToken(token: string): Promise<Client | null> {
    const ct = await this.tokenRepo.findOne({
      where: { token, status: 'active' },
    });
    if (!ct) return null;
    return this.clientRepo.findOne({
      where: { id: ct.client_id, status: 'active' },
    });
  }

  // phone_number_id ile client bul (yeni yöntem)
  async findByPhoneNumberId(phoneNumberId: string): Promise<Client | null> {
    const ct = await this.tokenRepo.findOne({
      where: { phone_number_id: phoneNumberId, status: 'active' },
    });
    if (!ct) return null;
    return this.clientRepo.findOne({
      where: { id: ct.client_id, status: 'active' },
    });
  }

  // Client ID'den token bilgisi al
  async getToken(clientId: number): Promise<ClientToken | null> {
    return this.tokenRepo.findOne({
      where: { client_id: clientId, status: 'active' },
    });
  }

  async findById(id: number): Promise<Client | null> {
    return this.clientRepo.findOne({ where: { id } });
  }
}
