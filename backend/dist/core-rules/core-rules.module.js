"use strict";
var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.CoreRulesModule = void 0;
const common_1 = require("@nestjs/common");
const typeorm_1 = require("@nestjs/typeorm");
const core_rule_entity_1 = require("./core-rule.entity");
const core_rules_service_1 = require("./core-rules.service");
let CoreRulesModule = class CoreRulesModule {
};
exports.CoreRulesModule = CoreRulesModule;
exports.CoreRulesModule = CoreRulesModule = __decorate([
    (0, common_1.Module)({
        imports: [typeorm_1.TypeOrmModule.forFeature([core_rule_entity_1.CoreRule])],
        providers: [core_rules_service_1.CoreRulesService],
        exports: [core_rules_service_1.CoreRulesService],
    })
], CoreRulesModule);
//# sourceMappingURL=core-rules.module.js.map