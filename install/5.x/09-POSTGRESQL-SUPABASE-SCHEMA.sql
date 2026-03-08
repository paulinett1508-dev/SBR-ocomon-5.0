-- =============================================================================
-- OcoMon 5.0 — Schema PostgreSQL para Supabase
-- Gerado por: Auditoria 2026-03-08 (migração MySQL → PostgreSQL)
-- =============================================================================
--
-- INSTRUÇÕES:
--   1. Execute este script no SQL Editor do Supabase (Project → SQL Editor)
--   2. Ou via psql: psql -h HOST -U USER -d postgres -f este_arquivo.sql
--   3. Após executar, migre os dados com um script ETL (MySQL Dump → PostgreSQL)
--
-- DIFERENÇAS PRINCIPAIS do schema original (MySQL → PostgreSQL):
--   - AUTO_INCREMENT          → GENERATED ALWAYS AS IDENTITY
--   - TINYINT(1)              → SMALLINT  (use 0/1 como antes; ou BOOLEAN)
--   - INT(n) display width    → INTEGER
--   - UNSIGNED                → removido (não existe em PostgreSQL)
--   - ENGINE=InnoDB           → removido
--   - DEFAULT CHARSET=utf8    → removido (Supabase é UTF-8 por padrão)
--   - backticks               → sem quotes (identificadores lowercase)
--   - DATETIME                → TIMESTAMP
--   - ENUM('a','b')           → TEXT CHECK (col IN ('a','b'))
--   - LONGBLOB                → BYTEA (imagens binárias)
--   - COMMENT=''              → removido (não suportado em CREATE TABLE)
-- =============================================================================

-- Usar schema público do Supabase
SET search_path TO public;

-- =============================================================================
-- TABELA: areaxarea_abrechamado
-- =============================================================================

CREATE TABLE IF NOT EXISTS areaxarea_abrechamado (
    area               INTEGER NOT NULL,
    area_abrechamado   INTEGER NOT NULL,
    PRIMARY KEY (area, area_abrechamado)
);

INSERT INTO areaxarea_abrechamado (area, area_abrechamado) VALUES (1, 1), (1, 2)
    ON CONFLICT DO NOTHING;

-- =============================================================================
-- TABELA: assentamentos
-- =============================================================================

CREATE TABLE IF NOT EXISTS assentamentos (
    numero             INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    ocorrencia         INTEGER NOT NULL DEFAULT 0,
    assentamento       TEXT NOT NULL,
    data               TIMESTAMP DEFAULT NULL,
    responsavel        INTEGER NOT NULL DEFAULT 0,
    asset_privated     SMALLINT NOT NULL DEFAULT 0,
    tipo_assentamento  SMALLINT NOT NULL DEFAULT 0
);

-- =============================================================================
-- TABELA: assistencia
-- =============================================================================

CREATE TABLE IF NOT EXISTS assistencia (
    assist_cod   INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    assist_desc  VARCHAR(30) DEFAULT NULL
);

INSERT INTO assistencia (assist_desc) VALUES
    ('Contrato de Manutenção'),
    ('Garantia do Fabricante'),
    ('Sem Cobertura')
    ON CONFLICT DO NOTHING;

-- =============================================================================
-- TABELA: avisos
-- =============================================================================

CREATE TABLE IF NOT EXISTS avisos (
    aviso_id    INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    avisos      TEXT,
    data        TIMESTAMP DEFAULT NULL,
    origem      INTEGER NOT NULL DEFAULT 0,
    status      VARCHAR(100) DEFAULT NULL,
    area        INTEGER NOT NULL DEFAULT 0,
    origembkp   VARCHAR(20) DEFAULT NULL
);

-- =============================================================================
-- TABELA: categorias
-- =============================================================================

CREATE TABLE IF NOT EXISTS categorias (
    cat_cod   INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    cat_desc  VARCHAR(30) NOT NULL DEFAULT ''
);

-- =============================================================================
-- TABELA: ccusto
-- =============================================================================

CREATE TABLE IF NOT EXISTS ccusto (
    codigo      INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    descricao   VARCHAR(100) DEFAULT NULL,
    codccusto   VARCHAR(20) DEFAULT NULL
);

-- =============================================================================
-- TABELA: channels
-- =============================================================================

CREATE TABLE IF NOT EXISTS channels (
    channel_id    INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    channel_name  VARCHAR(60) DEFAULT NULL,
    channel_icon  VARCHAR(60) DEFAULT NULL
);

INSERT INTO channels (channel_name, channel_icon) VALUES
    ('Portal', 'fa-globe'),
    ('Email', 'fa-envelope'),
    ('Telefone', 'fa-phone'),
    ('Presencial', 'fa-user')
    ON CONFLICT DO NOTHING;

-- =============================================================================
-- TABELA: config
-- =============================================================================

CREATE TABLE IF NOT EXISTS config (
    conf_id                 INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    conf_site               VARCHAR(200) DEFAULT NULL,
    conf_ocomon_site        VARCHAR(200) DEFAULT NULL,
    conf_email              VARCHAR(80) DEFAULT NULL,
    conf_page_size          INTEGER DEFAULT 20,
    conf_date_format        VARCHAR(20) DEFAULT 'd/m/Y',
    conf_allow_reopen       SMALLINT DEFAULT 1,
    conf_wt_areas           SMALLINT DEFAULT 1,
    conf_formatBar          VARCHAR(100) DEFAULT NULL,
    conf_language           VARCHAR(20) DEFAULT 'pt_BR.php',
    conf_user_opencall      SMALLINT DEFAULT 0,
    conf_anon_opencall      SMALLINT DEFAULT 0
);

INSERT INTO config (conf_site, conf_ocomon_site, conf_page_size, conf_language)
    VALUES ('http://localhost', 'http://localhost', 20, 'pt_BR.php')
    ON CONFLICT DO NOTHING;

-- =============================================================================
-- TABELA: config_keys
-- =============================================================================

CREATE TABLE IF NOT EXISTS config_keys (
    id          INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    key_name    VARCHAR(100) NOT NULL,
    key_value   TEXT DEFAULT NULL,
    UNIQUE (key_name)
);

-- Configuração padrão AUTH_TYPE = GOOGLE_OAUTH
INSERT INTO config_keys (key_name, key_value) VALUES
    ('AUTH_TYPE', 'GOOGLE_OAUTH'),
    ('ANON_OPEN_ALLOW', '0')
    ON CONFLICT (key_name) DO NOTHING;

-- =============================================================================
-- TABELA: configusercall
-- =============================================================================

CREATE TABLE IF NOT EXISTS configusercall (
    conf_id              INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    conf_name            VARCHAR(60) DEFAULT NULL,
    conf_user_opencall   SMALLINT DEFAULT 0,
    conf_fields          TEXT DEFAULT NULL,
    conf_areas           TEXT DEFAULT NULL,
    conf_units           TEXT DEFAULT NULL
);

INSERT INTO configusercall (conf_name, conf_user_opencall) VALUES ('Padrão', 0)
    ON CONFLICT DO NOTHING;

-- =============================================================================
-- TABELA: custom_fields
-- =============================================================================

CREATE TABLE IF NOT EXISTS custom_fields (
    cfield_id       INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    cfield_table    VARCHAR(60) NOT NULL,
    cfield_label    VARCHAR(100) NOT NULL,
    cfield_type     VARCHAR(20) NOT NULL DEFAULT 'text',
    cfield_required SMALLINT NOT NULL DEFAULT 0,
    cfield_active   SMALLINT NOT NULL DEFAULT 1,
    cfield_order    INTEGER DEFAULT 0
);

-- =============================================================================
-- TABELA: custom_fields_option_values
-- =============================================================================

CREATE TABLE IF NOT EXISTS custom_fields_option_values (
    cfov_id       INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    cfov_field_id INTEGER NOT NULL,
    cfov_value    VARCHAR(200) NOT NULL
);

-- =============================================================================
-- TABELA: dominios
-- =============================================================================

CREATE TABLE IF NOT EXISTS dominios (
    dom_cod   INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    dom_desc  VARCHAR(50) DEFAULT NULL
);

-- =============================================================================
-- TABELA: email_warranty
-- =============================================================================

CREATE TABLE IF NOT EXISTS email_warranty (
    ew_id            INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    ew_comp_inv      VARCHAR(30) DEFAULT NULL,
    ew_data_sent     DATE DEFAULT NULL,
    ew_mail_to       TEXT DEFAULT NULL,
    ew_days_to_expire INTEGER DEFAULT NULL
);

-- =============================================================================
-- TABELA: emprestimos
-- =============================================================================

CREATE TABLE IF NOT EXISTS emprestimos (
    emp_id          INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    emp_item        INTEGER DEFAULT NULL,
    emp_quant       INTEGER DEFAULT NULL,
    emp_destino     INTEGER DEFAULT NULL,
    emp_data        TIMESTAMP DEFAULT NULL,
    emp_responsavel INTEGER DEFAULT NULL,
    emp_obs         TEXT DEFAULT NULL,
    emp_tipo        SMALLINT DEFAULT NULL,
    emp_ref         INTEGER DEFAULT NULL
);

-- =============================================================================
-- TABELA: equipamentos
-- =============================================================================

CREATE TABLE IF NOT EXISTS equipamentos (
    comp_cod         INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    comp_inv         VARCHAR(30) DEFAULT NULL,
    comp_sn          VARCHAR(60) DEFAULT NULL,
    comp_tipo        INTEGER DEFAULT NULL,
    comp_marca       INTEGER DEFAULT NULL,
    comp_modelo      INTEGER DEFAULT NULL,
    comp_mem         INTEGER DEFAULT NULL,
    comp_hd          INTEGER DEFAULT NULL,
    comp_proc        VARCHAR(60) DEFAULT NULL,
    comp_placa       VARCHAR(30) DEFAULT NULL,
    comp_obs         TEXT DEFAULT NULL,
    comp_data_compra DATE DEFAULT NULL,
    comp_local       INTEGER DEFAULT NULL,
    comp_garantia    INTEGER DEFAULT NULL,
    comp_assist      INTEGER DEFAULT NULL,
    comp_so          INTEGER DEFAULT NULL,
    comp_status      SMALLINT DEFAULT 1,
    comp_unidade     INTEGER DEFAULT NULL,
    comp_usuario     VARCHAR(60) DEFAULT NULL,
    comp_responsavel INTEGER DEFAULT NULL,
    comp_mac         VARCHAR(20) DEFAULT NULL,
    comp_ip          VARCHAR(20) DEFAULT NULL,
    comp_dominio     INTEGER DEFAULT NULL,
    comp_tipo_imp    INTEGER DEFAULT NULL,
    comp_area        INTEGER DEFAULT NULL
);

-- =============================================================================
-- TABELA: fabricantes
-- =============================================================================

CREATE TABLE IF NOT EXISTS fabricantes (
    fab_cod   INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    fab_desc  VARCHAR(50) DEFAULT NULL
);

-- =============================================================================
-- TABELA: feriados
-- =============================================================================

CREATE TABLE IF NOT EXISTS feriados (
    fer_id    INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    fer_data  DATE NOT NULL,
    fer_desc  VARCHAR(100) DEFAULT NULL,
    fer_type  SMALLINT DEFAULT 0
);

-- =============================================================================
-- TABELA: form_fields
-- =============================================================================

CREATE TABLE IF NOT EXISTS form_fields (
    ff_id       INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    ff_form     VARCHAR(60) NOT NULL,
    ff_field    VARCHAR(60) NOT NULL,
    ff_required SMALLINT NOT NULL DEFAULT 0,
    UNIQUE (ff_form, ff_field)
);

-- =============================================================================
-- TABELA: fornecedores
-- =============================================================================

CREATE TABLE IF NOT EXISTS fornecedores (
    forn_cod   INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    forn_desc  VARCHAR(60) DEFAULT NULL,
    forn_email VARCHAR(80) DEFAULT NULL,
    forn_fone  VARCHAR(20) DEFAULT NULL
);

-- =============================================================================
-- TABELA: global_tickets
-- =============================================================================

CREATE TABLE IF NOT EXISTS global_tickets (
    gt_id       INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    gt_ticket   INTEGER NOT NULL,
    gt_area     INTEGER NOT NULL,
    gt_user     INTEGER NOT NULL
);

-- =============================================================================
-- TABELA: historico
-- =============================================================================

CREATE TABLE IF NOT EXISTS historico (
    hist_id       INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    hist_comp     INTEGER NOT NULL,
    hist_local    INTEGER DEFAULT NULL,
    hist_unidade  INTEGER DEFAULT NULL,
    hist_data     TIMESTAMP DEFAULT NULL,
    hist_resp     INTEGER DEFAULT NULL,
    hist_obs      TEXT DEFAULT NULL,
    hist_usuario  VARCHAR(60) DEFAULT NULL
);

-- =============================================================================
-- TABELA: hw_sw
-- =============================================================================

CREATE TABLE IF NOT EXISTS hw_sw (
    hw_cod   INTEGER NOT NULL,
    sw_cod   INTEGER NOT NULL,
    PRIMARY KEY (hw_cod, sw_cod)
);

-- =============================================================================
-- TABELA: imagens
-- =============================================================================

CREATE TABLE IF NOT EXISTS imagens (
    img_cod         INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    img_name        VARCHAR(80) DEFAULT NULL,
    img_description VARCHAR(200) DEFAULT NULL,
    img_size        BIGINT DEFAULT NULL,
    img_type        VARCHAR(60) DEFAULT NULL,
    img_data        BYTEA,
    img_ref         INTEGER DEFAULT NULL,
    img_ref_type    VARCHAR(20) DEFAULT NULL
);

-- =============================================================================
-- TABELA: input_tags
-- =============================================================================

CREATE TABLE IF NOT EXISTS input_tags (
    id        INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    tag_name  VARCHAR(100) DEFAULT NULL,
    UNIQUE (tag_name)
);

-- =============================================================================
-- TABELA: instituicao
-- =============================================================================

CREATE TABLE IF NOT EXISTS instituicao (
    inst_cod         INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    inst_nome        VARCHAR(80) NOT NULL,
    inst_sigla       VARCHAR(10) DEFAULT NULL,
    inst_client      SMALLINT DEFAULT 0,
    is_active        SMALLINT NOT NULL DEFAULT 1,
    inst_predios     SMALLINT DEFAULT 0,
    inst_reitorias   SMALLINT DEFAULT 0
);

INSERT INTO instituicao (inst_nome, inst_sigla, inst_client, is_active)
    VALUES ('Instituição Padrão', 'INST', 1, 1)
    ON CONFLICT DO NOTHING;

-- =============================================================================
-- TABELA: itens
-- =============================================================================

CREATE TABLE IF NOT EXISTS itens (
    item_cod    INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    item_tipo   VARCHAR(30) DEFAULT NULL,
    item_medida VARCHAR(10) DEFAULT NULL
);

-- =============================================================================
-- TABELA: licencas
-- =============================================================================

CREATE TABLE IF NOT EXISTS licencas (
    lic_cod   INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    lic_desc  VARCHAR(50) DEFAULT NULL
);

-- =============================================================================
-- TABELA: localizacao
-- =============================================================================

CREATE TABLE IF NOT EXISTS localizacao (
    loc_id       INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    loc_nome     VARCHAR(60) NOT NULL,
    loc_unidade  INTEGER DEFAULT NULL,
    loc_ativo    SMALLINT DEFAULT 1
);

INSERT INTO localizacao (loc_nome, loc_unidade, loc_ativo) VALUES ('Local Padrão', 1, 1)
    ON CONFLICT DO NOTHING;

-- =============================================================================
-- TABELA: lock_oco
-- =============================================================================

CREATE TABLE IF NOT EXISTS lock_oco (
    lock_id      INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    lock_oco     INTEGER NOT NULL,
    lock_user    INTEGER NOT NULL,
    lock_time    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================================================
-- TABELA: mail_hist
-- =============================================================================

CREATE TABLE IF NOT EXISTS mail_hist (
    mh_id      INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    mh_data    TIMESTAMP DEFAULT NULL,
    mh_to      TEXT DEFAULT NULL,
    mh_subject VARCHAR(200) DEFAULT NULL,
    mh_ref     INTEGER DEFAULT NULL,
    mh_type    VARCHAR(20) DEFAULT NULL,
    mh_status  SMALLINT DEFAULT 0,
    mh_error   TEXT DEFAULT NULL
);

-- =============================================================================
-- TABELA: mail_list
-- =============================================================================

CREATE TABLE IF NOT EXISTS mail_list (
    ml_id      INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    ml_name    VARCHAR(100) DEFAULT NULL,
    ml_email   VARCHAR(200) DEFAULT NULL,
    ml_type    SMALLINT DEFAULT 0,
    ml_ref     INTEGER DEFAULT NULL
);

-- =============================================================================
-- TABELA: mail_queue
-- =============================================================================

CREATE TABLE IF NOT EXISTS mail_queue (
    mq_id       INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    mq_to       TEXT DEFAULT NULL,
    mq_subject  VARCHAR(200) DEFAULT NULL,
    mq_body     TEXT DEFAULT NULL,
    mq_ref      INTEGER DEFAULT NULL,
    mq_type     VARCHAR(20) DEFAULT NULL,
    mq_created  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    mq_status   SMALLINT DEFAULT 0,
    mq_tries    INTEGER DEFAULT 0
);

-- =============================================================================
-- TABELA: mail_templates
-- =============================================================================

CREATE TABLE IF NOT EXISTS mail_templates (
    mt_id       INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    mt_name     VARCHAR(100) DEFAULT NULL,
    mt_subject  VARCHAR(200) DEFAULT NULL,
    mt_body     TEXT DEFAULT NULL,
    mt_type     VARCHAR(20) DEFAULT NULL
);

-- =============================================================================
-- TABELA: mailconfig
-- =============================================================================

CREATE TABLE IF NOT EXISTS mailconfig (
    mail_id         INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    mail_send       SMALLINT DEFAULT 0,
    mail_host       VARCHAR(100) DEFAULT NULL,
    mail_port       INTEGER DEFAULT 587,
    mail_user       VARCHAR(100) DEFAULT NULL,
    mail_pass       VARCHAR(200) DEFAULT NULL,
    mail_from       VARCHAR(100) DEFAULT NULL,
    mail_from_name  VARCHAR(100) DEFAULT NULL,
    mail_isauth     SMALLINT DEFAULT 1,
    mail_ishtml     SMALLINT DEFAULT 1,
    mail_secure     VARCHAR(10) DEFAULT 'tls'
);

INSERT INTO mailconfig (mail_send, mail_host, mail_port, mail_isauth, mail_ishtml, mail_secure)
    VALUES (0, 'smtp.gmail.com', 587, 1, 1, 'tls')
    ON CONFLICT DO NOTHING;

-- =============================================================================
-- TABELA: marcas_comp
-- =============================================================================

CREATE TABLE IF NOT EXISTS marcas_comp (
    marc_cod   INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    marc_desc  VARCHAR(60) DEFAULT NULL
);

-- =============================================================================
-- TABELA: materiais
-- =============================================================================

CREATE TABLE IF NOT EXISTS materiais (
    mat_id       INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    mat_name     VARCHAR(100) DEFAULT NULL,
    mat_quant    INTEGER DEFAULT 0,
    mat_unit     VARCHAR(20) DEFAULT NULL,
    mat_category INTEGER DEFAULT NULL,
    mat_active   SMALLINT DEFAULT 1
);

-- =============================================================================
-- TABELA: modelos_itens
-- =============================================================================

CREATE TABLE IF NOT EXISTS modelos_itens (
    mod_cod   INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    mod_desc  VARCHAR(60) DEFAULT NULL,
    mod_tipo  INTEGER DEFAULT NULL
);

-- =============================================================================
-- TABELA: moldes
-- =============================================================================

CREATE TABLE IF NOT EXISTS moldes (
    mol_cod     INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    mol_nome    VARCHAR(60) DEFAULT NULL,
    mol_desc    TEXT DEFAULT NULL,
    mol_area    INTEGER DEFAULT NULL
);

-- =============================================================================
-- TABELA: msgconfig
-- =============================================================================

CREATE TABLE IF NOT EXISTS msgconfig (
    msg_id      INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    msg_event   VARCHAR(60) NOT NULL,
    msg_active  SMALLINT DEFAULT 1,
    msg_subject VARCHAR(200) DEFAULT NULL,
    msg_body    TEXT DEFAULT NULL,
    UNIQUE (msg_event)
);

-- =============================================================================
-- TABELA: nivel
-- =============================================================================

CREATE TABLE IF NOT EXISTS nivel (
    nivel_id    INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nivel_desc  VARCHAR(30) NOT NULL
);

INSERT INTO nivel (nivel_desc) VALUES
    ('Administrador'),
    ('Operador'),
    ('Usuário'),
    ('Inativo')
    ON CONFLICT DO NOTHING;

-- =============================================================================
-- TABELA: ocorrencias
-- =============================================================================

CREATE TABLE IF NOT EXISTS ocorrencias (
    numero          INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    problema        INTEGER NOT NULL DEFAULT 0,
    data_abertura   TIMESTAMP DEFAULT NULL,
    data_fechamento TIMESTAMP DEFAULT NULL,
    sistema         INTEGER NOT NULL DEFAULT 0,
    aberto_por      INTEGER DEFAULT NULL,
    responsavel     INTEGER DEFAULT NULL,
    local           INTEGER DEFAULT NULL,
    status          INTEGER NOT NULL DEFAULT 0,
    prioridade      INTEGER DEFAULT NULL,
    solucao         INTEGER DEFAULT NULL,
    descr_problema  TEXT DEFAULT NULL,
    descr_solucao   TEXT DEFAULT NULL,
    patrimonio      VARCHAR(30) DEFAULT NULL,
    unidade         INTEGER DEFAULT NULL,
    oco_tag         TEXT DEFAULT NULL,
    canal           INTEGER DEFAULT NULL,
    tipo1           INTEGER DEFAULT NULL,
    tipo2           INTEGER DEFAULT NULL,
    tipo3           INTEGER DEFAULT NULL,
    tempo_atend     INTEGER DEFAULT NULL
);

-- Full-text search em PostgreSQL (equivale ao FULLTEXT INDEX MySQL)
CREATE INDEX IF NOT EXISTS idx_ocorrencias_tag ON ocorrencias USING gin(to_tsvector('portuguese', COALESCE(oco_tag, '')));

-- =============================================================================
-- TABELA: ocorrencias_log
-- =============================================================================

CREATE TABLE IF NOT EXISTS ocorrencias_log (
    olog_id         INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    olog_oco        INTEGER NOT NULL,
    olog_user       INTEGER NOT NULL,
    olog_data       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    olog_campo      VARCHAR(60) DEFAULT NULL,
    olog_de         TEXT DEFAULT NULL,
    olog_para       TEXT DEFAULT NULL,
    olog_tipo       SMALLINT DEFAULT 0
);

-- =============================================================================
-- TABELA: ocodeps
-- =============================================================================

CREATE TABLE IF NOT EXISTS ocodeps (
    dep_id    INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    dep_pai   INTEGER NOT NULL,
    dep_filho INTEGER NOT NULL
);

-- =============================================================================
-- TABELA: predios
-- =============================================================================

CREATE TABLE IF NOT EXISTS predios (
    pred_id       INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    pred_nome     VARCHAR(80) NOT NULL,
    pred_unidade  INTEGER DEFAULT NULL
);

-- =============================================================================
-- TABELA: prior_atend
-- =============================================================================

CREATE TABLE IF NOT EXISTS prior_atend (
    pa_id     INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    pa_desc   VARCHAR(60) DEFAULT NULL,
    pa_nivel  INTEGER DEFAULT NULL,
    pa_horas  INTEGER DEFAULT NULL
);

-- =============================================================================
-- TABELA: prior_nivel
-- =============================================================================

CREATE TABLE IF NOT EXISTS prior_nivel (
    pn_id     INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    pn_desc   VARCHAR(60) DEFAULT NULL,
    pn_ordem  INTEGER DEFAULT NULL
);

-- =============================================================================
-- TABELA: prioridades
-- =============================================================================

CREATE TABLE IF NOT EXISTS prioridades (
    prior_cod   INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    prior_desc  VARCHAR(30) DEFAULT NULL,
    prior_color VARCHAR(10) DEFAULT NULL
);

INSERT INTO prioridades (prior_desc, prior_color) VALUES
    ('Baixa',   '#28a745'),
    ('Média',   '#ffc107'),
    ('Alta',    '#fd7e14'),
    ('Urgente', '#dc3545')
    ON CONFLICT DO NOTHING;

-- =============================================================================
-- TABELA: prob_tipo_1
-- =============================================================================

CREATE TABLE IF NOT EXISTS prob_tipo_1 (
    tipo1_cod     INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    tipo1_desc    VARCHAR(60) DEFAULT NULL,
    tipo1_problem INTEGER DEFAULT NULL
);

-- =============================================================================
-- TABELA: prob_tipo_2
-- =============================================================================

CREATE TABLE IF NOT EXISTS prob_tipo_2 (
    tipo2_cod   INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    tipo2_desc  VARCHAR(60) DEFAULT NULL,
    tipo2_tipo1 INTEGER DEFAULT NULL
);

-- =============================================================================
-- TABELA: prob_tipo_3
-- =============================================================================

CREATE TABLE IF NOT EXISTS prob_tipo_3 (
    tipo3_cod   INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    tipo3_desc  VARCHAR(60) DEFAULT NULL,
    tipo3_tipo2 INTEGER DEFAULT NULL
);

-- =============================================================================
-- TABELA: problemas
-- =============================================================================

CREATE TABLE IF NOT EXISTS problemas (
    prob_cod   INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    prob_nome  VARCHAR(60) NOT NULL,
    prob_area  INTEGER DEFAULT NULL,
    prob_ativo SMALLINT DEFAULT 1
);

INSERT INTO problemas (prob_nome, prob_area, prob_ativo) VALUES ('Suporte Geral', 1, 1)
    ON CONFLICT DO NOTHING;

-- =============================================================================
-- TABELA: reitorias
-- =============================================================================

CREATE TABLE IF NOT EXISTS reitorias (
    reit_id    INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    reit_nome  VARCHAR(80) NOT NULL,
    reit_sigla VARCHAR(10) DEFAULT NULL
);

-- =============================================================================
-- TABELA: script_solution
-- =============================================================================

CREATE TABLE IF NOT EXISTS script_solution (
    ss_id      INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    ss_problem INTEGER DEFAULT NULL,
    ss_script  TEXT DEFAULT NULL,
    ss_active  SMALLINT DEFAULT 1
);

-- =============================================================================
-- TABELA: situacao
-- =============================================================================

CREATE TABLE IF NOT EXISTS situacao (
    situ_cod   INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    situ_desc  VARCHAR(30) DEFAULT NULL
);

INSERT INTO situacao (situ_desc) VALUES
    ('Em Uso'), ('Em Estoque'), ('Em Manutenção'), ('Desativado')
    ON CONFLICT DO NOTHING;

-- =============================================================================
-- TABELA: sla_out
-- =============================================================================

CREATE TABLE IF NOT EXISTS sla_out (
    so_id      INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    so_ticket  INTEGER NOT NULL,
    so_area    INTEGER NOT NULL,
    so_type    VARCHAR(20) DEFAULT NULL
);

-- =============================================================================
-- TABELA: sla_solucao
-- =============================================================================

CREATE TABLE IF NOT EXISTS sla_solucao (
    sla_id       INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    sla_area     INTEGER NOT NULL,
    sla_problem  INTEGER DEFAULT NULL,
    sla_prioridade INTEGER DEFAULT NULL,
    sla_horas    INTEGER DEFAULT NULL,
    sla_ativo    SMALLINT DEFAULT 1
);

-- =============================================================================
-- TABELA: softwares
-- =============================================================================

CREATE TABLE IF NOT EXISTS softwares (
    sw_cod      INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    sw_nome     VARCHAR(80) DEFAULT NULL,
    sw_versao   VARCHAR(20) DEFAULT NULL,
    sw_categ    INTEGER DEFAULT NULL,
    sw_licenca  INTEGER DEFAULT NULL,
    sw_fab      INTEGER DEFAULT NULL
);

-- =============================================================================
-- TABELA: solucoes
-- =============================================================================

CREATE TABLE IF NOT EXISTS solucoes (
    sol_cod   INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    sol_desc  TEXT DEFAULT NULL,
    sol_prob  INTEGER DEFAULT NULL,
    sol_ativo SMALLINT DEFAULT 1
);

-- =============================================================================
-- TABELA: status
-- =============================================================================

CREATE TABLE IF NOT EXISTS status (
    stat_id    INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    stat_nome  VARCHAR(30) NOT NULL,
    stat_fechado SMALLINT DEFAULT 0,
    stat_categ INTEGER DEFAULT NULL,
    stat_color VARCHAR(10) DEFAULT NULL,
    stat_ativo SMALLINT DEFAULT 1
);

INSERT INTO status (stat_nome, stat_fechado, stat_ativo) VALUES
    ('Aberto',       0, 1),
    ('Em Andamento', 0, 1),
    ('Pendente',     0, 1),
    ('Fechado',      1, 1)
    ON CONFLICT DO NOTHING;

-- =============================================================================
-- TABELA: status_categ
-- =============================================================================

CREATE TABLE IF NOT EXISTS status_categ (
    sc_id    INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    sc_nome  VARCHAR(60) NOT NULL,
    sc_ativo SMALLINT DEFAULT 1
);

-- =============================================================================
-- TABELA: sistemas
-- =============================================================================

CREATE TABLE IF NOT EXISTS sistemas (
    sis_id      INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    sis_nome    VARCHAR(60) NOT NULL,
    sis_sigla   VARCHAR(10) DEFAULT NULL,
    sis_email   VARCHAR(80) DEFAULT NULL,
    sis_ativo   SMALLINT DEFAULT 1,
    sis_unidade INTEGER DEFAULT NULL,
    sis_screen  INTEGER DEFAULT NULL
);

INSERT INTO sistemas (sis_nome, sis_sigla, sis_ativo) VALUES ('TI', 'TI', 1)
    ON CONFLICT DO NOTHING;

-- =============================================================================
-- TABELA: sw_padrao
-- =============================================================================

CREATE TABLE IF NOT EXISTS sw_padrao (
    sp_id      INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    sp_sw      INTEGER NOT NULL,
    sp_tipo    INTEGER DEFAULT NULL,
    sp_modelo  INTEGER DEFAULT NULL
);

-- =============================================================================
-- TABELA: tempo_garantia
-- =============================================================================

CREATE TABLE IF NOT EXISTS tempo_garantia (
    tg_id        INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    tg_desc      VARCHAR(30) DEFAULT NULL,
    tg_meses     INTEGER DEFAULT NULL
);

INSERT INTO tempo_garantia (tg_desc, tg_meses) VALUES
    ('12 meses', 12), ('24 meses', 24), ('36 meses', 36)
    ON CONFLICT DO NOTHING;

-- =============================================================================
-- TABELA: tempo_status
-- =============================================================================

CREATE TABLE IF NOT EXISTS tempo_status (
    ts_id      INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    ts_ticket  INTEGER NOT NULL,
    ts_status  INTEGER NOT NULL,
    ts_inicio  TIMESTAMP DEFAULT NULL,
    ts_fim     TIMESTAMP DEFAULT NULL,
    ts_total   INTEGER DEFAULT 0
);

-- =============================================================================
-- TABELA: tickets_extended
-- =============================================================================

CREATE TABLE IF NOT EXISTS tickets_extended (
    te_id        INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    te_ticket    INTEGER NOT NULL UNIQUE,
    te_updated   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    te_extra1    TEXT DEFAULT NULL,
    te_extra2    TEXT DEFAULT NULL,
    te_extra3    TEXT DEFAULT NULL
);

-- Trigger equivalente ao ON UPDATE CURRENT_TIMESTAMP do MySQL
CREATE OR REPLACE FUNCTION update_tickets_extended_timestamp()
RETURNS TRIGGER AS $$
BEGIN
    NEW.te_updated = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_tickets_extended_updated ON tickets_extended;
CREATE TRIGGER trg_tickets_extended_updated
    BEFORE UPDATE ON tickets_extended
    FOR EACH ROW EXECUTE FUNCTION update_tickets_extended_timestamp();

-- =============================================================================
-- TABELA: tickets_rated
-- =============================================================================

CREATE TABLE IF NOT EXISTS tickets_rated (
    tr_id      INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    tr_ticket  INTEGER NOT NULL,
    tr_rating  SMALLINT DEFAULT NULL,
    tr_comment TEXT DEFAULT NULL,
    tr_date    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (tr_ticket)
);

-- =============================================================================
-- TABELA: tickets_stages
-- =============================================================================

CREATE TABLE IF NOT EXISTS tickets_stages (
    ts_id      INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    ts_ticket  INTEGER NOT NULL,
    ts_stage   VARCHAR(60) DEFAULT NULL,
    ts_data    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ts_user    INTEGER DEFAULT NULL,
    ts_obs     TEXT DEFAULT NULL
);

-- =============================================================================
-- TABELA: tickets_x_cfields
-- =============================================================================

CREATE TABLE IF NOT EXISTS tickets_x_cfields (
    txcf_id       INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    txcf_ticket   INTEGER NOT NULL,
    txcf_field_id INTEGER NOT NULL,
    txcf_value    TEXT DEFAULT NULL
);

-- =============================================================================
-- TABELA: ticket_x_workers
-- =============================================================================

CREATE TABLE IF NOT EXISTS ticket_x_workers (
    txw_id      INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    txw_ticket  INTEGER NOT NULL,
    txw_worker  INTEGER NOT NULL
);

-- =============================================================================
-- TABELA: tipo_equip
-- =============================================================================

CREATE TABLE IF NOT EXISTS tipo_equip (
    tipo_cod   INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    tipo_nome  VARCHAR(30) DEFAULT NULL
);

-- =============================================================================
-- TABELA: tipo_garantia
-- =============================================================================

CREATE TABLE IF NOT EXISTS tipo_garantia (
    tgar_cod   INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    tgar_desc  VARCHAR(30) DEFAULT NULL
);

-- =============================================================================
-- TABELA: tipo_imp
-- =============================================================================

CREATE TABLE IF NOT EXISTS tipo_imp (
    timp_cod   INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    timp_desc  VARCHAR(30) DEFAULT NULL
);

-- =============================================================================
-- TABELA: temas / styles / uthemes / uprefs
-- =============================================================================

CREATE TABLE IF NOT EXISTS temas (
    tema_id    INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    tema_nome  VARCHAR(60) DEFAULT NULL,
    tema_css   VARCHAR(100) DEFAULT NULL,
    tema_ativo SMALLINT DEFAULT 1
);

CREATE TABLE IF NOT EXISTS styles (
    style_id   INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    style_name VARCHAR(60) DEFAULT NULL,
    style_css  VARCHAR(100) DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS uprefs (
    upref_id    INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    upref_user  INTEGER NOT NULL UNIQUE,
    upref_tema  INTEGER DEFAULT NULL,
    upref_style INTEGER DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS uthemes (
    utheme_id   INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    utheme_user INTEGER NOT NULL,
    utheme_key  VARCHAR(60) DEFAULT NULL,
    utheme_val  VARCHAR(200) DEFAULT NULL
);

-- =============================================================================
-- TABELA: user_notices
-- =============================================================================

CREATE TABLE IF NOT EXISTS user_notices (
    un_id      INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    un_user    INTEGER NOT NULL,
    un_aviso   INTEGER NOT NULL,
    un_read    SMALLINT DEFAULT 0,
    un_date    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================================================
-- TABELA: usuarios
-- =============================================================================

CREATE TABLE IF NOT EXISTS usuarios (
    user_id         INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    login           VARCHAR(100) NOT NULL,
    password        VARCHAR(200) DEFAULT NULL,   -- legado, não usar com Google OAuth
    hash            VARCHAR(200) DEFAULT NULL,   -- legado, não usar com Google OAuth
    nome            VARCHAR(120) DEFAULT NULL,
    email           VARCHAR(120) DEFAULT NULL,
    fone            VARCHAR(20) DEFAULT NULL,
    nivel           SMALLINT NOT NULL DEFAULT 3,
    AREA            INTEGER DEFAULT 1,
    user_admin      SMALLINT DEFAULT 0,
    user_client     INTEGER DEFAULT NULL,
    data_inc        TIMESTAMP DEFAULT NULL,
    data_admis      DATE DEFAULT NULL,
    last_logon      TIMESTAMP DEFAULT NULL,
    opening_mode    SMALLINT DEFAULT 1,
    sis_screen      INTEGER DEFAULT NULL,
    language        VARCHAR(20) DEFAULT NULL,
    can_route       SMALLINT DEFAULT 0,
    can_get_routed  SMALLINT DEFAULT 0,
    UNIQUE (login)
);

-- =============================================================================
-- TABELA: usuarios_areas
-- =============================================================================

CREATE TABLE IF NOT EXISTS usuarios_areas (
    ua_id      INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    ua_user    INTEGER NOT NULL,
    ua_area    INTEGER NOT NULL,
    UNIQUE (ua_user, ua_area)
);

-- =============================================================================
-- TABELA: worktime_profiles
-- =============================================================================

CREATE TABLE IF NOT EXISTS worktime_profiles (
    wtp_id       INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    wtp_name     VARCHAR(60) NOT NULL,
    wtp_mon_ini  TIME DEFAULT NULL,
    wtp_mon_fim  TIME DEFAULT NULL,
    wtp_tue_ini  TIME DEFAULT NULL,
    wtp_tue_fim  TIME DEFAULT NULL,
    wtp_wed_ini  TIME DEFAULT NULL,
    wtp_wed_fim  TIME DEFAULT NULL,
    wtp_thu_ini  TIME DEFAULT NULL,
    wtp_thu_fim  TIME DEFAULT NULL,
    wtp_fri_ini  TIME DEFAULT NULL,
    wtp_fri_fim  TIME DEFAULT NULL,
    wtp_sat_ini  TIME DEFAULT NULL,
    wtp_sat_fim  TIME DEFAULT NULL,
    wtp_sun_ini  TIME DEFAULT NULL,
    wtp_sun_fim  TIME DEFAULT NULL,
    wtp_default  SMALLINT DEFAULT 0
);

-- =============================================================================
-- TABELA: areas_x_issues / areas_x_units
-- =============================================================================

CREATE TABLE IF NOT EXISTS areas_x_issues (
    axi_id      INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    axi_area    INTEGER NOT NULL,
    axi_issue   INTEGER NOT NULL,
    UNIQUE (axi_area, axi_issue)
);

CREATE TABLE IF NOT EXISTS areas_x_units (
    axu_id      INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    axu_area    INTEGER NOT NULL,
    axu_unit    INTEGER NOT NULL,
    UNIQUE (axu_area, axu_unit)
);

-- =============================================================================
-- TABELA: access_tokens (API JWT)
-- =============================================================================

CREATE TABLE IF NOT EXISTS access_tokens (
    id          INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id     INTEGER NOT NULL,
    app         VARCHAR(100) NOT NULL,
    token       TEXT NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at  TIMESTAMP DEFAULT NULL,
    UNIQUE (user_id, app)
);

-- =============================================================================
-- TABELA: apps_register
-- =============================================================================

CREATE TABLE IF NOT EXISTS apps_register (
    app_id      INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    app_name    VARCHAR(100) NOT NULL,
    app_key     VARCHAR(200) DEFAULT NULL,
    app_active  SMALLINT DEFAULT 1,
    app_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (app_name)
);

-- =============================================================================
-- ÍNDICES DE PERFORMANCE
-- =============================================================================

CREATE INDEX IF NOT EXISTS idx_ocorrencias_sistema    ON ocorrencias (sistema);
CREATE INDEX IF NOT EXISTS idx_ocorrencias_status     ON ocorrencias (status);
CREATE INDEX IF NOT EXISTS idx_ocorrencias_abertura   ON ocorrencias (data_abertura);
CREATE INDEX IF NOT EXISTS idx_ocorrencias_unidade    ON ocorrencias (unidade);
CREATE INDEX IF NOT EXISTS idx_assentamentos_oco      ON assentamentos (ocorrencia);
CREATE INDEX IF NOT EXISTS idx_ocorrencias_log_oco    ON ocorrencias_log (olog_oco);
CREATE INDEX IF NOT EXISTS idx_equipamentos_inv       ON equipamentos (comp_inv);
CREATE INDEX IF NOT EXISTS idx_equipamentos_unidade   ON equipamentos (comp_unidade);
CREATE INDEX IF NOT EXISTS idx_usuarios_login         ON usuarios (login);
CREATE INDEX IF NOT EXISTS idx_usuarios_nivel         ON usuarios (nivel);
CREATE INDEX IF NOT EXISTS idx_tickets_extended_tic   ON tickets_extended (te_ticket);

-- =============================================================================
-- FIM DO SCHEMA
-- =============================================================================
