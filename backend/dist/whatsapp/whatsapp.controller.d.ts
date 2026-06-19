import { WhatsAppService } from './whatsapp.service';
export declare class WhatsAppController {
    private readonly svc;
    private readonly logger;
    constructor(svc: WhatsAppService);
    verify(mode: string, token: string, challenge: string): string | number;
    incoming(payload: any): Promise<{
        ok: boolean;
    }>;
}
export declare class PortalController {
    private readonly svc;
    private readonly logger;
    constructor(svc: WhatsAppService);
    send(body: {
        to: string;
        body: string;
        phone_number_id: string;
    }): Promise<{
        ok: boolean;
    }>;
}
