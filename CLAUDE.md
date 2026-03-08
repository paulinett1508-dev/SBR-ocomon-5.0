# OcoMon 5.0 — Sistema de Helpdesk / Gestao de Chamados

Stack: PHP 7.4+ + MySQL/MariaDB + jQuery + CoffeeCode Router (API REST)
Submodulo: .agnostic-core/

---

## Sobre o Projeto

OcoMon e um sistema open-source de helpdesk e gestao de ocorrencias (tickets).
Licenca: GPLv3 | Autor original: Flavio Ribeiro

Modulos principais:
- `ocomon/`   — modulo principal de chamados (abertura, edicao, fechamento, relatorios)
- `invmon/`   — inventario de equipamentos
- `api/`      — REST API (Slim/CoffeeCode, JWT)
- `admin/`    — administracao do sistema
- `install/`  — scripts de migracao SQL por versao
- `includes/` — classes, funcoes, componentes compartilhados

---

## Antes de Implementar

Consulte a skill do dominio relevante antes de escrever codigo:

**Backend (PHP):**
  REST API design:         .agnostic-core/skills/backend/rest-api-design.md
  Error handling:          .agnostic-core/skills/backend/error-handling.md
  Migracao de dados:       .agnostic-core/skills/backend/estrategias-de-migracao.md

**Seguranca:**
  API hardening:           .agnostic-core/skills/security/api-hardening.md
  OWASP checklist:         .agnostic-core/skills/security/owasp-checklist.md
  Security review:         .agnostic-core/skills/security/security-review.md
  Politica de seguranca:   .agnostic-core/skills/security/politica-de-seguranca.md

**Banco de Dados (MySQL/MariaDB):**
  Schema design:           .agnostic-core/skills/database/schema-design.md
  Query compliance:        .agnostic-core/skills/database/query-compliance.md

**Frontend (jQuery + HTML/CSS):**
  HTML e CSS audit:        .agnostic-core/skills/frontend/html-css-audit.md
  Acessibilidade:          .agnostic-core/skills/frontend/accessibility.md
  UX Guidelines:           .agnostic-core/skills/frontend/ux-guidelines.md
  CSS Governance:          .agnostic-core/skills/frontend/css-governance.md

**Qualidade e Auditoria:**
  Code review:             .agnostic-core/skills/audit/code-review.md
  Pre-implementation:      .agnostic-core/skills/audit/pre-implementation.md
  Debugging sistematico:   .agnostic-core/skills/audit/systematic-debugging.md
  Refactoring:             .agnostic-core/skills/audit/refactoring.md
  Validation checklist:    .agnostic-core/skills/audit/validation-checklist.md

**Testes:**
  Testes unitarios:        .agnostic-core/skills/testing/unit-testing.md
  Testes integracao:       .agnostic-core/skills/testing/integration-testing.md

**Performance:**
  Performance audit:       .agnostic-core/skills/performance/performance-audit.md
  Caching strategies:      .agnostic-core/skills/cache/estrategias-de-cache.md

**Documentacao:**
  Technical docs:          .agnostic-core/skills/documentation/technical-docs.md
  OpenAPI / Swagger:       .agnostic-core/skills/documentation/openapi-swagger.md

**Git e Workflow:**
  Commit conventions:      .agnostic-core/skills/git/commit-conventions.md
  Branching strategy:      .agnostic-core/skills/git/branching-strategy.md
  PR template:             .agnostic-core/skills/git/pr-template.md
  Project workflow:        .agnostic-core/skills/workflow/project-workflow.md
  Goal-backward planning:  .agnostic-core/skills/workflow/goal-backward-planning.md
  Context management:      .agnostic-core/skills/workflow/context-management.md

**Antes de fazer deploy:**
  .agnostic-core/skills/devops/pre-deploy-checklist.md
  .agnostic-core/skills/devops/deploy-procedures.md

---

## Agents Disponiveis

**Reviewers:**
  Security Reviewer:       .agnostic-core/agents/reviewers/security-reviewer.md
  Code Inspector (SPARC):  .agnostic-core/agents/reviewers/code-inspector.md
  Test Reviewer:           .agnostic-core/agents/reviewers/test-reviewer.md
  Performance Reviewer:    .agnostic-core/agents/reviewers/performance-reviewer.md
  Codebase Mapper:         .agnostic-core/agents/reviewers/codebase-mapper.md
  Frontend Reviewer:       .agnostic-core/agents/reviewers/frontend-reviewer.md
  Architecture Reviewer:   .agnostic-core/agents/reviewers/architecture-reviewer.md

**Validators:**
  Migration Validator:     .agnostic-core/agents/validators/migration-validator.md

**Generators:**
  Project Planner:         .agnostic-core/agents/generators/project-planner.md
  Docs Generator:          .agnostic-core/agents/generators/docs-generator.md
  Boilerplate Generator:   .agnostic-core/agents/generators/boilerplate-generator.md

**Specialists:**
  DevOps Engineer:         .agnostic-core/agents/specialists/devops-engineer.md
  Database Architect:      .agnostic-core/agents/specialists/database-architect.md

**Workflows:**
  Brainstorm:              .agnostic-core/commands/workflows/brainstorm.md
  Debug:                   .agnostic-core/commands/workflows/debug.md
  Deploy:                  .agnostic-core/commands/workflows/deploy.md

**Guia de roteamento (qual agent/skill usar):**
  .agnostic-core/docs/agent-routing-guide.md

---

## Convencoes do Projeto

  Backend:      PHP 7.4+ (sem framework monolitico — procedural + classes custom)
  API REST:     CoffeeCode Router 1.0 + CoffeeCode DataLayer 1.1 + Firebase JWT 5.x
  Banco:        MySQL / MariaDB via PDO (ConnectPDO.php)
  Auth:         Sessao PHP (app web) + JWT Bearer Token (API REST)
  Mailer:       PHPMailer 6.2
  Frontend:     jQuery + HTML/CSS vanilla (sem bundler)
  Testes:       (a definir — sem suite de testes automatizados atualmente)
  Deploy:       Apache + mod_rewrite + PHP-FPM
  Estilo de commits: Conventional Commits (PT-BR)
  Branching:    GitFlow (main = producao, develop = integracao)

---

## Versionamento

Formato: `MAJOR.MINOR.PATCH` (SemVer)

  MAJOR — mudancas incompativeis (migracao de banco obrigatoria, quebra de API)
  MINOR — novas funcionalidades retrocompativeis
  PATCH — correcoes de bugs e ajustes menores

Versao atual: **5.0.0**
Changelog:    `changelog/` (um arquivo por versao: `v5.0.0.md`, `v5.1.0.md`, etc.)

**Commits de release:**
  chore(release): v5.1.0

**Tags git para releases:**
  git tag -a v5.1.0 -m "release: v5.1.0"
  git push origin v5.1.0

---

## Estrutura de Diretorios

```
ocomon-5.0/
├── CLAUDE.md               # este arquivo — entrada da IA no projeto
├── .agnostic-core/         # submodulo: skills, agents, commands
├── .gitmodules
├── index.php               # pagina principal (autenticado)
├── login.php               # autenticacao
├── menu-sidebar.php        # menu lateral
├── PATHS.php               # constantes de caminho
├── currentDate.php         # utilitario de data
├── ocomon/                 # modulo de chamados
│   └── geral/              # scripts PHP de ticket (abertura, edicao, relatorios...)
├── invmon/                 # modulo de inventario
├── admin/                  # administracao
├── api/                    # REST API
│   └── ocomon_api/         # app da API (CoffeeCode + JWT)
├── includes/               # compartilhados
│   ├── classes/            # AuthNew, ConnectPDO, worktime
│   ├── functions/          # dbFunctions, functions, download, showImg
│   ├── components/         # libs frontend (jQuery, scrollbar, etc.)
│   ├── css/
│   ├── javascript/
│   ├── languages/          # i18n (pt-BR, en, es)
│   ├── queries/            # queries SQL reutilizaveis
│   └── config.inc.php-dist # template de configuracao (copiar para config.inc.php)
├── install/                # scripts SQL de instalacao e migracao
│   └── 5.x/               # SQL por versao menor
└── changelog/              # notas de versao
```

---

## Regras Criticas do Projeto

- [ ] NUNCA commitar `config.inc.php` (credenciais) — ja no `.gitignore`
- [ ] NUNCA commitar `includes/logs/` — ja no `.gitignore`
- [ ] Toda query que recebe input do usuario DEVE usar PDO com prepared statements
- [ ] Toda nova rota de API DEVE validar JWT antes de processar
- [ ] Mudancas de schema de banco DEVEM ter arquivo SQL em `install/5.x/`
- [ ] PHP session_start() e verificacao de autenticacao sao obrigatorios em todo script protegido
- [ ] Nao usar `mysql_*` — usar exclusivamente PDO via `ConnectPDO.php`

---

## Checklist de Nova Feature

- [ ] Consultar skill de dominio relevante acima
- [ ] Verificar impacto em schema: arquivo SQL em `install/5.x/` se necessario
- [ ] Validar input com prepared statements (PDO)
- [ ] Checar autenticacao/autorizacao no script
- [ ] Testar fluxo completo (abertura, edicao, fechamento se for ticket)
- [ ] Atualizar `changelog/` com entrada da versao

---

## Checklist Pre-Deploy

Ver: .agnostic-core/skills/devops/pre-deploy-checklist.md

Especificos do OcoMon:
- [ ] `config.inc.php` atualizado no servidor (nao no repo)
- [ ] Scripts SQL de migracao executados em producao
- [ ] `.htaccess` e mod_rewrite habilitados no Apache
- [ ] Permissoes de pasta `includes/logs/` corretas (escrita pelo Apache)
- [ ] PHPMailer configurado com credenciais SMTP de producao

---

## Como Atualizar o agnostic-core

  git submodule update --remote .agnostic-core
  git add .agnostic-core
  git commit -m "chore(deps): atualizar agnostic-core"
