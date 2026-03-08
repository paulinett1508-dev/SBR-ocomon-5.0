# OcoMon 5.0 — Sistema de Helpdesk / Gestao de Chamados

Stack: PHP 7.4+ + Supabase (PostgreSQL) + jQuery + CoffeeCode Router (API REST)
Auth: Google OAuth 2.0 (Google Workspace) via Supabase Auth
Submodulo: .agnostic-core/

---

## Sobre o Projeto

OcoMon e um sistema open-source de helpdesk e gestao de ocorrencias (tickets).
Licenca: GPLv3 | Autor original: Flavio Ribeiro

Modulos principais:
- `ocomon/`   — modulo principal de chamados (abertura, edicao, fechamento, relatorios)
- `invmon/`   — inventario de equipamentos
- `api/`      — REST API (CoffeeCode Router, JWT)
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

**Banco de Dados (PostgreSQL / Supabase):**
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

**Guia de roteamento:**
  .agnostic-core/docs/agent-routing-guide.md

---

## Convencoes do Projeto

  Backend:      PHP 7.4+ (procedural + classes custom — sem framework monolitico)
  API REST:     CoffeeCode Router 1.0 + [MIGRAR PARA Eloquent standalone] + Firebase JWT 5.x
  Banco:        Supabase (PostgreSQL 15+) via PDO pgsql + Session Pooler (porta 5432)
  Auth web:     Google OAuth 2.0 (Google Workspace) via Supabase Auth — PKCE flow
  Auth API:     JWT Supabase (RS256) — em migracao de HS256 com chave de usuario
  Mailer:       PHPMailer 6.2 (SMTP)
  Frontend:     jQuery 3.7.1 + Bootstrap 4.5 + HTML/CSS vanilla
  Testes:       (a definir — sem suite automatizada atualmente)
  Deploy:       Apache + mod_rewrite + PHP-FPM
  Estilo de commits: Conventional Commits (PT-BR)
  Branching:    GitFlow (main = producao, develop = integracao)

---

## Autenticacao Google OAuth — Fluxo

  1. config.inc.php: AUTH_TYPE = 'GOOGLE_OAUTH'
  2. login.php renderiza botao "Entrar com Google"
  3. SupabaseAuth::getAuthorizationUrl() gera URL + PKCE verifier (na sessao)
  4. Supabase redireciona para Google (so contas @GOOGLE_WORKSPACE_DOMAIN)
  5. Google autenticado → Supabase redireciona para oauth_callback.php?code=...
  6. oauth_callback.php troca code por token, valida dominio, cria/encontra usuario
  7. session_regenerate_id(true) + populacao completa da sessao PHP
  8. Redireciona para index.php

  Arquivos chave:
    includes/classes/SupabaseAuth.php    — classe OAuth + PKCE
    includes/common/oauth_callback.php   — handler do callback

---

## Banco de Dados — Supabase PostgreSQL

  Conexao:  ConnectPDO.php usa pgsql DSN com sslmode=require
  Schema:   install/5.x/09-POSTGRESQL-SUPABASE-SCHEMA.sql
  Auditoria: changelog/AUDIT-2026-03-08.md

  ATENCAO — Bloqueador na API:
    CoffeeCode DataLayer e MySQL-especifico. A API REST NAO funciona com
    PostgreSQL sem migrar o ORM. Ver Auditoria — Secao 4.3.
    Plano: substituir por illuminate/database (Eloquent standalone).

  Queries MySQL que precisam de conversao manual (56 arquivos PHP):
    DATE_ADD(d, INTERVAL n MONTH) → d + INTERVAL 'n months'
    DATE_FORMAT(d, '%Y-%m-%d')    → TO_CHAR(d, 'YYYY-MM-DD')
    GROUP_CONCAT()                → STRING_AGG()
    IF(cond, a, b)                → CASE WHEN cond THEN a ELSE b END
    MATCH() AGAINST()             → to_tsvector() / to_tsquery()
    backticks                     → sem quotes (identificadores lowercase)
    TINYINT(1) = 1                → SMALLINT = 1 (ou recast para BOOLEAN)

---

## Versionamento

Formato: `MAJOR.MINOR.PATCH` (SemVer)

  MAJOR — mudancas incompativeis (migracao de banco obrigatoria, quebra de API)
  MINOR — novas funcionalidades retrocompativeis
  PATCH — correcoes de bugs e ajustes menores

Versao atual: **5.1.0** (migracao Supabase + Google OAuth)
Changelog:    `changelog/` (um arquivo por versao: `AUDIT-2026-03-08.md`, etc.)

**Commits de release:**
  chore(release): v5.1.0

**Tags git para releases:**
  git tag -a v5.1.0 -m "release: v5.1.0"
  git push origin v5.1.0

---

## Estrutura de Diretorios

```
ocomon-5.0/
├── CLAUDE.md                            # este arquivo — entrada da IA
├── .agnostic-core/                      # submodulo: skills, agents, commands
├── .gitmodules
├── index.php                            # pagina principal (autenticado)
├── login.php                            # autenticacao (Google OAuth + fallback classico)
├── menu-sidebar.php                     # menu lateral
├── PATHS.php                            # constantes de caminho
├── ocomon/                              # modulo de chamados
│   └── geral/                           # ~100 scripts PHP de ticket
├── invmon/                              # modulo de inventario
├── admin/                               # administracao
├── api/                                 # REST API
│   └── ocomon_api/                      # app da API
├── includes/
│   ├── classes/
│   │   ├── ConnectPDO.php               # PDO singleton — PostgreSQL (Supabase)
│   │   ├── SupabaseAuth.php             # NOVO — Google OAuth via Supabase Auth
│   │   └── AuthNew.class.php            # auth local legada (manutencao apenas)
│   ├── common/
│   │   ├── oauth_callback.php           # NOVO — handler do callback OAuth
│   │   └── auth_process.php             # auth classica + bloqueio GOOGLE_OAUTH
│   ├── functions/                       # dbFunctions, functions, download
│   ├── queries/                         # queries SQL reutilizaveis (pendente conversao PG)
│   └── config.inc.php-dist              # template de config (Supabase + OAuth)
├── install/
│   ├── 5.x/
│   │   ├── 01-...-FRESH_INSTALL.sql     # schema MySQL original (referencia)
│   │   └── 09-POSTGRESQL-SUPABASE-SCHEMA.sql  # NOVO — schema PostgreSQL
└── changelog/
    ├── AUDIT-2026-03-08.md              # auditoria tecnica completa
    └── changelog-5.0.md                 # changelog versao 5.0
```

---

## Regras Criticas do Projeto

- [ ] NUNCA commitar `config.inc.php` (credenciais Supabase + OAuth) — ja no `.gitignore`
- [ ] NUNCA commitar `includes/logs/` — ja no `.gitignore`
- [ ] NUNCA expor SUPABASE_SERVICE_KEY no frontend ou em logs
- [ ] Toda query PDO DEVE usar prepared statements com placeholders (:param)
- [ ] Sem backticks em queries PostgreSQL — usar identificadores lowercase sem quotes
- [ ] Toda nova rota de API DEVE validar JWT antes de processar
- [ ] Mudancas de schema DEVEM ter arquivo SQL em `install/5.x/`
- [ ] session_regenerate_id(true) obrigatorio apos autenticacao bem-sucedida
- [ ] Todo output HTML DEVE usar htmlspecialchars($var, ENT_QUOTES, 'UTF-8')
- [ ] Nao usar mysql_* — PDO pgsql apenas

---

## Checklist de Nova Feature

- [ ] Consultar skill de dominio relevante acima
- [ ] Verificar impacto em schema: arquivo SQL em `install/5.x/` se necessario
- [ ] Queries com prepared statements + PostgreSQL-compatible
- [ ] Checar autenticacao/autorizacao
- [ ] Output HTML com htmlspecialchars
- [ ] Atualizar `changelog/` com entrada da versao

---

## Checklist Pre-Deploy

Ver: .agnostic-core/skills/devops/pre-deploy-checklist.md

Especificos do OcoMon + Supabase:
- [ ] `config.inc.php` configurado com credenciais Supabase reais
- [ ] SUPABASE_URL, SUPABASE_ANON_KEY, SUPABASE_SERVICE_KEY, SUPABASE_JWT_SECRET preenchidos
- [ ] GOOGLE_WORKSPACE_DOMAIN configurado (ex: "laboratoriosobral.com.br")
- [ ] OAUTH_CALLBACK_URL registrada no Supabase Dashboard (Authentication → URL Configuration)
- [ ] Google Cloud Console: Authorized redirect URIs inclui https://PROJECT.supabase.co/auth/v1/callback
- [ ] Schema PostgreSQL executado no Supabase SQL Editor
- [ ] `.htaccess` e mod_rewrite habilitados no Apache
- [ ] Permissoes de pasta `includes/logs/` corretas
- [ ] Supabase Auth > Google Provider habilitado com Client ID e Secret do GCP
- [ ] AUTH_TYPE = 'GOOGLE_OAUTH' configurado na tabela config_keys do Supabase
- [ ] CoffeeCode DataLayer substituido por Eloquent na API (Fase 3 — pendente)

---

## Como Atualizar o agnostic-core

  git submodule update --remote .agnostic-core
  git add .agnostic-core
  git commit -m "chore(deps): atualizar agnostic-core"
