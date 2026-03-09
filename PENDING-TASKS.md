# PENDING TASKS — OcoMon 5.0
**Sessão encerrada em:** 2026-03-08
**Retomar exatamente daqui na próxima sessão.**

---

## CONTEXTO GERAL

- Projeto: OcoMon 5.0 — sistema de helpdesk PHP
- Repo: https://github.com/paulinett1508-dev/SBR-ocomon-5.0
- Banco: **Supabase (PostgreSQL)** — projeto `entzzvbvfqibytzzfkif`
- Auth: **Google OAuth (Workspace)** via Supabase Auth — PKCE flow
- Deploy alvo: **VPS Hostinger** com Docker + Traefik
- Domínio novo: `suporte-ti.laboratoriosobral.com.br`
- Domínio antigo (intocável): `suporte.laboratoriosobral.com.br` (OcoMon MySQL em outro servidor)

---

## O QUE JÁ FOI FEITO

### Fase 1 — Infraestrutura (COMPLETA)
- [x] `.agnostic-core` adicionado como git submodule
- [x] `CLAUDE.md` criado com documentação completa
- [x] `ConnectPDO.php` migrado MySQL → PostgreSQL (pgsql DSN + sslmode=require)
- [x] `config.inc.php-dist` atualizado com constantes Supabase + OAuth
- [x] `config.inc.php` criado (gitignored) com credenciais reais do Supabase
- [x] `DBConfig.php` (API) migrado para driver pgsql
- [x] `SupabaseAuth.php` criado — Google OAuth PKCE
- [x] `oauth_callback.php` criado — handler completo do callback
- [x] `login.php` atualizado — botão Google OAuth
- [x] `auth_process.php` atualizado — tipo GOOGLE_OAUTH
- [x] `install/5.x/09-POSTGRESQL-SUPABASE-SCHEMA.sql` criado — 77 tabelas PostgreSQL
- [x] `changelog/AUDIT-2026-03-08.md` — auditoria técnica completa
- [x] Arquivos Docker criados (parcialmente — ver pendências abaixo)

### Fase 2 — Setup Supabase (PARCIALMENTE COMPLETA)
- [x] Projeto Supabase criado: `entzzvbvfqibytzzfkif`
- [x] Schema PostgreSQL executado no SQL Editor (77 tabelas criadas com sucesso)
- [x] Dados básicos inseridos (config, config_keys, sistemas, instituicao, localizacao, problemas)
- [x] `config.inc.php` preenchido com credenciais reais
- [ ] **PENDENTE:** Habilitar Google OAuth no Supabase Dashboard
- [ ] **PENDENTE:** Registrar OAUTH_CALLBACK_URL no Supabase (Authentication → URL Configuration)
- [ ] **PENDENTE:** Preencher `GOOGLE_WORKSPACE_DOMAIN` e `OAUTH_CALLBACK_URL` no config.inc.php
- [ ] **PENDENTE:** Configurar Google Cloud Console (OAuth 2.0 Client ID)

### Docker (INICIADO — pausado por falta de info da VPS)
- [x] `Dockerfile` criado (PHP 8.2 + Apache + pdo_pgsql + extensões)
- [x] `docker-compose.yml` criado (com labels Traefik)
- [x] `.docker/apache.conf` criado
- [x] `.docker/php.ini` criado
- [x] `.docker/traefik/docker-compose.traefik.yml` criado
- [x] `.dockerignore` criado
- [ ] **BLOQUEADO:** VPS não está vazia — precisamos checar o que está rodando antes de prosseguir

---

## PRÓXIMOS PASSOS (em ordem)

### PASSO IMEDIATO — Checar a VPS (fazer no início da próxima sessão)

Conectar na VPS via SSH e rodar os comandos abaixo para mapear tudo:

```bash
# 1. Sistema operacional e versão
cat /etc/os-release

# 2. O que está ouvindo nas portas 80 e 443 (web)
sudo ss -tlnp | grep -E ':80|:443'
# ou:
sudo netstat -tlnp | grep -E ':80|:443'

# 3. Serviços ativos no sistema
sudo systemctl list-units --type=service --state=running

# 4. Apache instalado?
apache2 -v 2>/dev/null || httpd -v 2>/dev/null || echo "Apache não encontrado"

# 5. Nginx instalado?
nginx -v 2>/dev/null || echo "Nginx não encontrado"

# 6. Docker instalado?
docker --version 2>/dev/null || echo "Docker não encontrado"
docker compose version 2>/dev/null || docker-compose --version 2>/dev/null || echo "Docker Compose não encontrado"

# 7. Containers rodando (se Docker existir)
docker ps 2>/dev/null

# 8. Virtual hosts configurados (Apache)
sudo ls /etc/apache2/sites-enabled/ 2>/dev/null
sudo cat /etc/apache2/sites-enabled/*.conf 2>/dev/null

# 9. Virtual hosts configurados (Nginx)
sudo ls /etc/nginx/sites-enabled/ 2>/dev/null
sudo cat /etc/nginx/sites-enabled/*.conf 2>/dev/null

# 10. Processos usando portas 80 e 443
sudo lsof -i :80 -i :443 | grep LISTEN

# 11. Espaço em disco disponível
df -h

# 12. Memória disponível
free -h

# 13. IP público da VPS
curl -s ifconfig.me
```

Cole o output de todos esses comandos no início da próxima sessão.

---

### APÓS CHECAR A VPS — Definir estratégia Docker

**Cenário A — VPS tem Apache/Nginx no host (sem Docker):**
- Instalar Docker na VPS
- Manter Apache/Nginx existente nas portas 80/443
- OcoMon container expõe porta interna (ex: 8081)
- Apache/Nginx existente adiciona VirtualHost como proxy reverso para 8081
- SSL via Certbot no Apache/Nginx existente

**Cenário B — VPS já tem Docker rodando:**
- Verificar se já existe Traefik ou outro proxy
- Se sim: adicionar OcoMon na rede existente
- Se não: criar rede `traefik-public` e subir Traefik

**Cenário C — VPS tem Nginx + outros projetos PHP:**
- Adicionar bloco server no Nginx existente
- Container OcoMon na porta 8081
- Nginx faz proxy para 8081 com SSL Certbot

---

### Fase 3 — Substituir CoffeeCode DataLayer por Eloquent (API REST)
**BLOQUEADOR CRÍTICO:** A API REST não funciona com PostgreSQL enquanto usar CoffeeCode DataLayer (MySQL-específico).

Arquivos a migrar (~30 arquivos em `api/ocomon_api/app/`):
- Todos os Models que estendem `DataLayer`
- Todos os Controllers
- `DBConfig.php` (já atualizado, mas DataLayer ainda usa MySQL internamente)

Plano:
```bash
cd api/ocomon_api
composer require illuminate/database
composer remove coffeecode/datalayer coffeecode/paginator coffeecode/router
```

---

### Fase 4 — Converter queries MySQL nos 56 arquivos PHP
Ver lista completa em: `changelog/AUDIT-2026-03-08.md` — Seção 4.1

Conversões necessárias:
- `DATE_ADD(d, INTERVAL n MONTH)` → `d + INTERVAL 'n months'`
- `DATE_FORMAT(d, '%Y-%m-%d')` → `TO_CHAR(d, 'YYYY-MM-DD')`
- `GROUP_CONCAT()` → `STRING_AGG()`
- `IF(cond, a, b)` → `CASE WHEN cond THEN a ELSE b END`
- `MATCH() AGAINST()` → `to_tsvector() / to_tsquery()`
- Backticks → sem quotes

---

### Fase 5 — Segurança frontend
Ver: `changelog/AUDIT-2026-03-08.md` — Seção 7

- `innerHTML` → `textContent` onde aplicável
- `htmlspecialchars()` em todos os outputs de dados do banco
- `$_SERVER['PHP_SELF']` → com escape

---

### Fase 6 — Google Cloud Console (OAuth)
**Fazer após ter o domínio `suporte-ti.laboratoriosobral.com.br` funcionando com HTTPS.**

1. console.cloud.google.com → projeto existente ou novo
2. APIs & Services → OAuth consent screen → Internal (só Workspace)
3. Credentials → OAuth 2.0 Client ID → Web application
4. Authorized redirect URIs: `https://entzzvbvfqibytzzfkif.supabase.co/auth/v1/callback`
5. Copiar Client ID e Client Secret
6. Supabase Dashboard → Authentication → Providers → Google → habilitar
7. Supabase → Authentication → URL Configuration → Redirect URLs:
   `https://suporte-ti.laboratoriosobral.com.br/includes/common/oauth_callback.php`
8. Atualizar `config.inc.php`:
   - `GOOGLE_WORKSPACE_DOMAIN` = `laboratoriosobral.com.br`
   - `OAUTH_CALLBACK_URL` = `https://suporte-ti.laboratoriosobral.com.br/includes/common/oauth_callback.php`

---

## CREDENCIAIS SUPABASE (referência — estão no config.inc.php)

- **Project ref:** `entzzvbvfqibytzzfkif`
- **URL:** `https://entzzvbvfqibytzzfkif.supabase.co`
- **Host PDO:** `db.entzzvbvfqibytzzfkif.supabase.co`
- **Domínio Workspace:** `laboratoriosobral.com.br` (a confirmar)
- **Credenciais:** em `includes/config.inc.php` (gitignored)

---

## ARQUIVOS CHAVE DO PROJETO

```
includes/config.inc.php              ← credenciais reais (gitignored)
includes/config.inc.php-dist         ← template sem credenciais (versionado)
includes/classes/ConnectPDO.php      ← conexão PostgreSQL
includes/classes/SupabaseAuth.php    ← Google OAuth PKCE
includes/common/oauth_callback.php   ← callback OAuth
login.php                            ← botão Google OAuth
install/5.x/09-POSTGRESQL-SUPABASE-SCHEMA.sql  ← schema PostgreSQL
changelog/AUDIT-2026-03-08.md        ← auditoria técnica completa
Dockerfile                           ← imagem PHP 8.2 + Apache
docker-compose.yml                   ← container OcoMon + Traefik labels
.docker/apache.conf                  ← VirtualHost Apache
.docker/php.ini                      ← configuração PHP
.docker/traefik/docker-compose.traefik.yml  ← Traefik (deploy na VPS)
.dockerignore                        ← exclusões da imagem
```

---

## PERGUNTAS EM ABERTO

1. O que exatamente está rodando na VPS? (responder com output dos comandos acima)
2. Qual o IP público da VPS? (para configurar DNS do suporte-ti)
3. DNS de `laboratoriosobral.com.br` — quem gerencia? (Registro.br, Cloudflare, painel Hostinger?)
4. O email de contato do Google Cloud Console será `ti@laboratoriosobral.com.br`?
