import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { Client } from './client.entity';
import { ClientToken } from './client-token.entity';
import { ClientsService } from './clients.service';

@Module({
  imports: [TypeOrmModule.forFeature([Client, ClientToken])],
  providers: [ClientsService],
  exports: [ClientsService],
})
export class ClientsModule {}
