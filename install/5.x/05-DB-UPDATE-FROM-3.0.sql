/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;


ALTER TABLE `config` ADD `conf_isolate_areas` INT(1) NOT NULL DEFAULT '0' COMMENT 'Visibilidade entre areas para consultas e relatorios' AFTER `conf_sla_tolerance`; 


ALTER TABLE `hw_alter` CHANGE `hwa_item` `hwa_item` INT(4) NULL; 

ALTER TABLE `mailconfig` ADD `mail_send` TINYINT(1) NOT NULL DEFAULT '1' AFTER `mail_from_name`; 

ALTER TABLE `modelos_itens` ADD `mdit_manufacturer` INT(6) NULL AFTER `mdit_cod`, ADD INDEX (`mdit_manufacturer`); 

ALTER TABLE `modelos_itens` CHANGE `mdit_fabricante` `mdit_fabricante` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL; 

ALTER TABLE `estoque` ADD `estoq_assist` INT(2) NULL DEFAULT NULL AFTER `estoq_partnumber`, ADD `estoq_warranty_type` INT(2) NULL DEFAULT NULL AFTER `estoq_assist`, ADD INDEX (`estoq_assist`), ADD INDEX (`estoq_warranty_type`); 


ALTER TABLE `sistemas` CHANGE `sis_email` `sis_email` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL; 
ALTER TABLE `config` CHANGE `conf_upld_file_types` `conf_upld_file_types` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '%%IMG%'; 
ALTER TABLE `predios` CHANGE `pred_desc` `pred_desc` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''; 



ALTER TABLE `usuarios` CHANGE `password` `password` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''; 
ALTER TABLE `usuarios` ADD `hash` VARCHAR(255) NULL AFTER `password`; 



CREATE TABLE `channels` ( `id` INT(2) NOT NULL AUTO_INCREMENT , `name` VARCHAR(255) NOT NULL, PRIMARY KEY (`id`)) ENGINE = InnoDB CHARSET=utf8 COLLATE utf8_general_ci COMMENT = 'Canais disponíveis para abertura de chamados';

INSERT INTO `channels` (`id`, `name`) VALUES (NULL, 'Sistema Web'), (NULL, 'Telefone') ;
INSERT INTO `channels` (`id`, `name`) VALUES (NULL, 'Automático: via Email'), (NULL, 'Email') ;

ALTER TABLE `channels` ADD `is_default` TINYINT(1) NOT NULL DEFAULT '0' AFTER `name`, ADD INDEX (`is_default`); 
UPDATE `channels` SET `is_default` = '1' WHERE `channels`.`id` = 1; 

ALTER TABLE `channels` ADD `only_set_by_system` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Apenas para processos automatizados' AFTER `is_default`; 

UPDATE `channels` SET `only_set_by_system` = '1' WHERE `channels`.`id` = 3; 


ALTER TABLE `configusercall` ADD `conf_scr_channel` TINYINT(1) NOT NULL DEFAULT '0' AFTER `conf_scr_contact_email`; 

ALTER TABLE `ocorrencias` ADD `oco_channel` INT(2) NULL DEFAULT 1 AFTER `oco_prior`, ADD INDEX (`oco_channel`); 



CREATE TABLE `config_keys` ( `id` INT(3) NOT NULL AUTO_INCREMENT , `key_name` VARCHAR(255) NOT NULL , `key_value` VARCHAR(255) NULL DEFAULT NULL , PRIMARY KEY (`id`), UNIQUE (`key_name`)) ENGINE = InnoDB COMMENT = 'Configuracoes relacionadas a API e outras operacoes'; 

ALTER TABLE `config_keys` CHANGE `key_value` `key_value` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL; 

INSERT INTO `config_keys` (`id`, `key_name`, `key_value`) VALUES (NULL, 'MAIL_GET_ADDRESS', NULL), (NULL, 'MAIL_GET_IMAP_ADDRESS', NULL), (NULL, 'MAIL_GET_PORT', NULL) ;
INSERT INTO `config_keys` (`id`, `key_name`, `key_value`) VALUES (NULL, 'API_TICKET_BY_MAIL_USER', NULL) ;
INSERT INTO `config_keys` (`id`, `key_name`, `key_value`) VALUES (NULL, 'API_TICKET_BY_MAIL_APP', NULL);
INSERT INTO `config_keys` (`id`, `key_name`, `key_value`) VALUES (NULL, 'API_TICKET_BY_MAIL_TOKEN', NULL);
INSERT INTO `config_keys` (`id`, `key_name`, `key_value`) VALUES (NULL, 'MAIL_GET_CERT', '1');
INSERT INTO `config_keys` (`id`, `key_name`, `key_value`) VALUES (NULL, 'MAIL_GET_PASSWORD', NULL);
INSERT INTO `config_keys` (`id`, `key_name`, `key_value`) VALUES (NULL, 'MAIL_GET_MAILBOX', 'INBOX');
INSERT INTO `config_keys` (`id`, `key_name`, `key_value`) VALUES (NULL, 'MAIL_GET_MOVETO', 'OCOMON');
INSERT INTO `config_keys` (`id`, `key_name`, `key_value`) VALUES (NULL, 'MAIL_GET_MARK_SEEN', NULL);
INSERT INTO `config_keys` (`id`, `key_name`, `key_value`) VALUES (NULL, 'MAIL_GET_SUBJECT_CONTAINS', NULL);
INSERT INTO `config_keys` (`id`, `key_name`, `key_value`) VALUES (NULL, 'MAIL_GET_BODY_CONTAINS', NULL);
INSERT INTO `config_keys` (`id`, `key_name`, `key_value`) VALUES (NULL, 'MAIL_GET_DAYS_SINCE', '3');
INSERT INTO `config_keys` (`id`, `key_name`, `key_value`) VALUES (NULL, 'ALLOW_OPEN_TICKET_BY_EMAIL', '0') ;
INSERT INTO `config_keys` (`id`, `key_name`, `key_value`) VALUES (NULL, 'API_TICKET_BY_MAIL_CHANNEL', '3');
INSERT INTO `config_keys` (`id`, `key_name`, `key_value`) VALUES (NULL, 'API_TICKET_BY_MAIL_AREA', NULL), (NULL, 'API_TICKET_BY_MAIL_STATUS', '1') ;




--
-- Estrutura para tabela `mail_queue`
--

CREATE TABLE `mail_queue` (
  `id` int(11) UNSIGNED NOT NULL,
  `subject` varchar(255) NOT NULL DEFAULT '',
  `body` text NOT NULL,
  `from_email` varchar(255) NOT NULL DEFAULT '',
  `from_name` varchar(255) NOT NULL DEFAULT '',
  `recipient_email` varchar(255) NOT NULL DEFAULT '',
  `recipient_name` varchar(255) NOT NULL DEFAULT '',
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Índices de tabela `mail_queue`
--
ALTER TABLE `mail_queue`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de tabela `mail_queue`
--
ALTER TABLE `mail_queue`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;


ALTER TABLE `mail_queue` ADD `ticket` INT(11) NULL DEFAULT NULL AFTER `id`, ADD INDEX (`ticket`); 




CREATE TABLE `access_tokens` ( `id` INT(11) NOT NULL AUTO_INCREMENT , `user_id` INT(11) NULL DEFAULT NULL , `app` VARCHAR(255) NULL DEFAULT NULL , `token` VARCHAR(255) NOT NULL , PRIMARY KEY (`id`), INDEX (`user_id`), INDEX (`app`)) ENGINE = InnoDB; 

ALTER TABLE `access_tokens` CHANGE `token` `token` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL; 

ALTER TABLE `access_tokens` ADD `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `token`, ADD `updated_at` TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `created_at`; 






CREATE TABLE `apps_register` ( `id` INT NOT NULL AUTO_INCREMENT , `app` VARCHAR(255) NOT NULL , `controller` VARCHAR(255) NOT NULL , `methods` TEXT NOT NULL , PRIMARY KEY (`id`), UNIQUE (`app`, `controller`)) ENGINE = InnoDB COMMENT = 'Registro de apps para controle de acesso pela API'; 

INSERT INTO `apps_register` (`id`, `app`, `controller`, `methods`) VALUES (NULL, 'ticket_by_email', 'OcomonApi\\Controllers\\Tickets', 'create') ;


ALTER TABLE `utmp_usuarios` ADD `utmp_hash` TEXT NULL DEFAULT NULL AFTER `utmp_passwd`; 

ALTER TABLE `usuarios` CHANGE `password` `password` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL; 

ALTER TABLE `utmp_usuarios` CHANGE `utmp_passwd` `utmp_passwd` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL; 







CREATE TABLE `form_fields` ( `id` INT NOT NULL AUTO_INCREMENT , `entity_name` VARCHAR(30) NOT NULL , `field_name` VARCHAR(50) NOT NULL , `action_name` ENUM('new','edit','close') NOT NULL , `not_empty` TINYINT(1) NULL DEFAULT NULL , PRIMARY KEY (`id`), INDEX (`entity_name`), INDEX (`field_name`), INDEX (`action_name`)) ENGINE = InnoDB COMMENT = 'Obrigatoriedade de preenchimento de campos nos formulários';

-- Inicializacao padrao do form_fields
INSERT INTO `form_fields` (`id`, `entity_name`, `field_name`, `action_name`, `not_empty`) VALUES 
(NULL, 'ocorrencias', 'issue', 'new', '1'), 
(NULL, 'ocorrencias', 'asset_tag', 'new', '0'), (NULL, 'ocorrencias', 'area', 'new', '1'), 
(NULL, 'ocorrencias', 'contact', 'new', '1'), (NULL, 'ocorrencias', 'contact_email', 'new', '1'), 
(NULL, 'ocorrencias', 'phone', 'new', '1'), (NULL, 'ocorrencias', 'department', 'new', '1'), 
(NULL, 'ocorrencias', 'operator', 'new', '1'), (NULL, 'ocorrencias', 'unit', 'new', '0'), 
(NULL, 'ocorrencias', 'priority', 'new', '1'), (NULL, 'ocorrencias', 'channel', 'new', '1');

INSERT INTO `form_fields` (`id`, `entity_name`, `field_name`, `action_name`, `not_empty`) VALUES 
(NULL, 'ocorrencias', 'issue', 'edit', '1'), 
(NULL, 'ocorrencias', 'asset_tag', 'edit', '0'), (NULL, 'ocorrencias', 'area', 'edit', '1'), 
(NULL, 'ocorrencias', 'contact', 'edit', '1'), (NULL, 'ocorrencias', 'contact_email', 'edit', '1'), 
(NULL, 'ocorrencias', 'phone', 'edit', '1'), (NULL, 'ocorrencias', 'department', 'edit', '1'), 
(NULL, 'ocorrencias', 'operator', 'edit', '1'), (NULL, 'ocorrencias', 'unit', 'edit', '0'), 
(NULL, 'ocorrencias', 'priority', 'edit', '1'), (NULL, 'ocorrencias', 'channel', 'edit', '1');

INSERT INTO `form_fields` (`id`, `entity_name`, `field_name`, `action_name`, `not_empty`) VALUES 
(NULL, 'ocorrencias', 'issue', 'close', '1'), 
(NULL, 'ocorrencias', 'asset_tag', 'close', '0'), (NULL, 'ocorrencias', 'area', 'close', '1'), 
(NULL, 'ocorrencias', 'contact', 'close', '1'), (NULL, 'ocorrencias', 'contact_email', 'close', '1'), 
(NULL, 'ocorrencias', 'phone', 'close', '1'), (NULL, 'ocorrencias', 'department', 'close', '1'), 
(NULL, 'ocorrencias', 'operator', 'close', '1'), (NULL, 'ocorrencias', 'unit', 'close', '0'), 
(NULL, 'ocorrencias', 'priority', 'close', '1'), (NULL, 'ocorrencias', 'channel', 'close', '1');




ALTER TABLE `prior_atend` ADD `pr_font_color` VARCHAR(7) NULL DEFAULT '#000000' AFTER `pr_color`; 




CREATE TABLE `input_tags` ( `tag_name` VARCHAR(30) NOT NULL , UNIQUE (`tag_name`)) ENGINE = InnoDB COMMENT = 'Tags de referência'; 
ALTER TABLE `input_tags` ADD `id` INT NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (`id`); 

ALTER TABLE `ocorrencias` ADD `oco_tag` TEXT NULL DEFAULT NULL AFTER `oco_channel`, ADD FULLTEXT (`oco_tag`); 

INSERT INTO `config_keys` (`id`, `key_name`, `key_value`) VALUES (NULL, 'API_TICKET_BY_MAIL_TAG', NULL) ;

ALTER TABLE `mailconfig` ADD `mail_queue` TINYINT(1) NOT NULL DEFAULT '0' AFTER `mail_send`; 

ALTER TABLE `localizacao` CHANGE `local` `local` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL; 


ALTER TABLE `usuarios` ADD `forget` VARCHAR(255) NULL DEFAULT NULL AFTER `last_logon`; 

INSERT INTO `msgconfig` (`msg_cod`, `msg_event`, `msg_fromname`, `msg_replyto`, `msg_subject`, `msg_body`, `msg_altbody`) VALUES (NULL, 'forget-password', 'Sistema OcoMon', 'ocomon@yourdomain.com', 'Esqueceu sua senha?', 'Esqueceu sua senha %usuario%?\r\n\r\nVocê está recebendo esse e-mail porque solicitou a recuperação de senha de acesso ao sistema de suporte.\r\n\r\nCaso não tenha sido você o autor da solicitação, apenas ignore essa mensagem. Seus dados estão protegidos.\r\n\r\nClique aqui para definir uma nova senha de acesso: %forget_link%\r\n\r\nAtte.\r\nEquipe de Suporte.', 'Esqueceu sua senha %usuario%?\r\n\r\nVocê está recebendo esse e-mail porque solicitou a recuperação de senha de acesso ao sistema de suporte.\r\n\r\nCaso não tenha sido você o autor da solicitação, apenas ignore essa mensagem. Seus dados estão protegidos.\r\n\r\nClique aqui para definir uma nova senha de acesso: %forget_link%\r\n\r\nAtte.\r\nEquipe de Suporte.');

ALTER TABLE `mail_templates` CHANGE `tpl_msg_html` `tpl_msg_html` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL; 

ALTER TABLE `mail_templates` CHANGE `tpl_msg_text` `tpl_msg_text` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL; 

ALTER TABLE `msgconfig` CHANGE `msg_body` `msg_body` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL; 
ALTER TABLE `scripts` CHANGE `scpt_script` `scpt_script` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL; 
ALTER TABLE `mail_queue` CHANGE `body` `body` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL; 

ALTER TABLE `usuarios` ADD INDEX `AREA` (`AREA`);
ALTER TABLE `usuarios` ADD INDEX `user_admin` (`user_admin`);  

ALTER TABLE `problemas` ADD `prob_not_area` VARCHAR(255) NULL DEFAULT NULL AFTER `prob_descricao`, ADD INDEX (`prob_not_area`); 

ALTER TABLE `problemas` ADD `prob_active` TINYINT(1) NOT NULL DEFAULT '1' AFTER `prob_not_area`, ADD INDEX (`prob_active`); 


INSERT INTO `config_keys` (`id`, `key_name`, `key_value`) VALUES (NULL, 'ANON_OPEN_ALLOW', NULL), (NULL, 'ANON_OPEN_SCREEN_PFL', NULL) ;
INSERT INTO `config_keys` (`id`, `key_name`, `key_value`) VALUES (NULL, 'ANON_OPEN_USER', NULL), (NULL, 'ANON_OPEN_STATUS', '1') ;
INSERT INTO `config_keys` (`id`, `key_name`, `key_value`) VALUES (NULL, 'ANON_OPEN_CHANNEL', NULL), (NULL, 'ANON_OPEN_TAGS', NULL);
INSERT INTO `config_keys` (`id`, `key_name`, `key_value`) VALUES (NULL, 'ANON_OPEN_CAPTCHA_CASE', '1');



ALTER TABLE `sistemas` ADD `sis_months_done` INT(3) NOT NULL DEFAULT '12' COMMENT 'Tempo em meses, para filtro de exibição de encerrados' AFTER `sis_wt_profile`; 





CREATE TABLE `custom_fields` ( `id` INT(3) NOT NULL AUTO_INCREMENT , `field_name` VARCHAR(255) NOT NULL , `field_type` ENUM('text','number','select','select_multi','date','time','datetime','textarea','checkbox') NOT NULL , `field_default_value` TEXT NULL DEFAULT NULL , `field_required` TINYINT(1) NOT NULL DEFAULT '0' , PRIMARY KEY (`id`), UNIQUE (`field_name`)) ENGINE = InnoDB COMMENT = 'Campos customizaveis';

ALTER TABLE `custom_fields` ADD `field_table_to` VARCHAR(255) NOT NULL AFTER `field_required`, ADD `field_label` VARCHAR(255) NOT NULL AFTER `field_table_to`, ADD `field_order` INT NULL AFTER `field_label`, ADD INDEX (`field_table_to`); 



ALTER TABLE `custom_fields` ADD `field_title` VARCHAR(255) NULL AFTER `field_order`, ADD `field_placeholder` VARCHAR(255) NULL AFTER `field_title`, ADD `field_description` VARCHAR(255) NULL AFTER `field_placeholder`; 

ALTER TABLE `custom_fields` ADD `field_active` TINYINT(1) NOT NULL DEFAULT '1' AFTER `field_description`, ADD INDEX (`field_active`); 

ALTER TABLE `custom_fields` CHANGE `field_default_value` `field_default_value` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL; 

ALTER TABLE `custom_fields` ADD `field_attributes` TEXT NULL DEFAULT NULL AFTER `field_active`; 

ALTER TABLE `custom_fields` CHANGE `field_order` `field_order` VARCHAR(10) NULL DEFAULT NULL COMMENT 'Campo utilizado para ordenação nas telas do sistema'; 


CREATE TABLE `custom_fields_option_values` ( `id` INT NOT NULL AUTO_INCREMENT , `custom_field_id` INT(3) NOT NULL , `option_value` TEXT NOT NULL , PRIMARY KEY (`id`), INDEX (`custom_field_id`)) ENGINE = InnoDB COMMENT = 'Valores para os campos customizados do tipo select '; 


CREATE TABLE `tickets_x_cfields` ( `id` INT NOT NULL AUTO_INCREMENT , `ticket` INT NOT NULL , `cfield_id` INT NOT NULL , `cfield_value` TEXT NULL DEFAULT NULL , PRIMARY KEY (`id`), INDEX (`ticket`), INDEX (`cfield_id`)) ENGINE = InnoDB COMMENT = 'Registros com campos personalizados'; 

ALTER TABLE `tickets_x_cfields` ADD `cfield_is_key` TINYINT NULL DEFAULT NULL AFTER `cfield_value`; 


ALTER TABLE `configusercall` ADD `conf_scr_custom_ids` TEXT NULL DEFAULT NULL COMMENT 'Ids dos campos personalizados' AFTER `conf_scr_channel`; 


ALTER TABLE `config` ADD `conf_cfield_only_opened` TINYINT(1) NOT NULL DEFAULT '1' COMMENT 'Define se na edição, os campos personalizados serão limitados aos utilizados na abertura do chamado' AFTER `conf_isolate_areas`; 




ALTER TABLE `config` ADD `conf_updated_issues` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Flag para saber se o update da tabela de tipos de problemas foi realizado.' AFTER `conf_isolate_areas`, ADD INDEX (`conf_updated_issues`); 

ALTER TABLE `config` ADD `conf_allow_op_treat_own_ticket` TINYINT(1) NOT NULL DEFAULT '1' COMMENT 'Define se o operador pode tratar chamados abertos por ele mesmo' AFTER `conf_isolate_areas`; 

ALTER TABLE `config` ADD `conf_reopen_deadline` INT(2) NOT NULL DEFAULT '0' COMMENT 'Limite de tempo em dias para a reabertura de chamados' AFTER `conf_allow_reopen`; 

CREATE TABLE `areas_x_issues` ( `id` INT NOT NULL AUTO_INCREMENT , `area_id` INT NULL , `prob_id` INT NOT NULL , `old_prob_id` INT NULL, PRIMARY KEY (`id`), INDEX (`area_id`), INDEX (`prob_id`), INDEX (`old_prob_id`)) ENGINE = InnoDB COMMENT = 'NxN Areas x Problemas'; 



CREATE TABLE `screen_field_required` ( `id` INT(6) NOT NULL AUTO_INCREMENT , `profile_id` INT(6) NOT NULL , `field_name` VARCHAR(64) NOT NULL COMMENT 'Nome do campo na tabela configusercall' , `field_required` TINYINT NOT NULL DEFAULT '1' , PRIMARY KEY (`id`), INDEX (`profile_id`), INDEX (`field_name`)) ENGINE = InnoDB COMMENT = 'Obrigatoriedade de preenchim. dos campos nos perfis de tela';


ALTER TABLE `avisos` ADD `is_recurrent` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Indica se o aviso será exibindo novamente no outro dia' AFTER `is_active`, ADD INDEX (`is_recurrent`); 


ALTER TABLE `custom_fields` ADD `field_mask` TEXT NULL DEFAULT NULL COMMENT 'Máscara para campos tipo texto' AFTER `field_attributes`; 

ALTER TABLE `custom_fields` ADD `field_mask_regex` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Se a máscara é uma expressão regular' AFTER `field_mask`; 


-- Versao 5


-- Para evitar problemas com instalacoes antigas onde o valor padrao é 0000-00-00 00:00:00
ALTER TABLE `equipamentos` CHANGE `comp_data_compra` `comp_data_compra` DATETIME NULL DEFAULT NULL;

ALTER TABLE `equipamentos` ADD `comp_part_number` VARCHAR(255) NULL DEFAULT NULL AFTER `comp_assist`, ADD INDEX (`comp_part_number`); 

-- Para evitar problemas com instalacoes antigas onde o valor padrao é 0000-00-00 00:00:00
ALTER TABLE `historico` CHANGE `hist_data` `hist_data` DATETIME NULL DEFAULT CURRENT_TIMESTAMP; 

ALTER TABLE `historico` ADD `asset_id` INT NULL DEFAULT NULL COMMENT 'A partir da versao 5 esse campo referencia o ativo' AFTER `hist_cod`, ADD INDEX (`asset_id`); 

ALTER TABLE `historico` CHANGE `hist_inv` `hist_inv` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'a partir da versão 5 é nulo'; 

ALTER TABLE `historico` CHANGE `hist_inst` `hist_inst` INT(4) NULL DEFAULT NULL COMMENT 'A partir da versão 5 é nulo'; 

ALTER TABLE `historico` CHANGE `hist_data` `hist_data` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP; 

ALTER TABLE `historico` ADD `hist_user` INT NULL COMMENT 'Responsável pela ação' AFTER `hist_data`, ADD INDEX (`hist_user`); 


ALTER TABLE `status` ADD `stat_ignored` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'define se o status será ignorado pelo sistema' AFTER `stat_time_freeze`, ADD INDEX (`stat_ignored`); 


ALTER TABLE `problemas` ADD `prob_profile_form` INT NULL DEFAULT NULL COMMENT 'Define o perfil de tela para abertura de chamados desse tipo' AFTER `prob_active`, ADD INDEX (`prob_profile_form`);


ALTER TABLE `configusercall` ADD `conf_is_default` TINYINT(1) NULL DEFAULT NULL AFTER `conf_name`, ADD INDEX (`conf_is_default`); 


ALTER TABLE `sistemas` ADD `sis_opening_mode` ENUM('1','2') NOT NULL DEFAULT '1' COMMENT 'Tipo de abertura de chamados - 1:classica 2:dinâmica' AFTER `sis_months_done`; 


ALTER TABLE `areaxarea_abrechamado` ADD `default_receiver` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Define a área padrão para recebimento de chamados' AFTER `area`; 

ALTER TABLE `problemas` ADD `prob_area_default` INT(4) NULL DEFAULT NULL COMMENT 'Área padrão para recebimento de chamados desse tipo' AFTER `prob_profile_form`, ADD INDEX (`prob_area_default`); 


--
-- Estrutura da tabela `clients`
--

CREATE TABLE `clients` (
  `id` int(10) UNSIGNED NOT NULL,
  `external_id` int(11) DEFAULT NULL COMMENT 'Se o cliente vier de outra base a ser integrada',
  `type` int(11) DEFAULT NULL,
  `fullname` varchar(255) NOT NULL,
  `nickname` varchar(255) DEFAULT NULL,
  `document_type` enum('cnpj','cpf','outro') DEFAULT 'cnpj',
  `document_value` varchar(255) DEFAULT NULL,
  `contact_name` varchar(255) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(255) DEFAULT NULL,
  `contact_name_2` varchar(255) DEFAULT NULL,
  `contact_email_2` varchar(255) DEFAULT NULL,
  `contact_phone_2` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `area` int(11) DEFAULT NULL,
  `status` int(11) DEFAULT NULL COMMENT 'Referência a tabela de status de clientes',
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Tabela de clientes';

--
-- Índices para tabela `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `document_value` (`document_value`),
  ADD KEY `contact_email` (`contact_email`),
  ADD KEY `contact_email_2` (`contact_email_2`),
  ADD KEY `area` (`area`),
  ADD KEY `external_id` (`external_id`),
  ADD KEY `type` (`type`),
  ADD KEY `status` (`status`),
  ADD KEY `is_active` (`is_active`);

--
-- AUTO_INCREMENT de tabela `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;



-- Dados iniciais para clientes --

INSERT INTO `clients` (`id`, `fullname`, `nickname`) VALUES
(1, 'Cliente Solucionador Interno', 'Operação'),
(2, 'Cliente solicitante', 'Cliente solicitante');


ALTER TABLE `usuarios` ADD `user_client` INT NULL DEFAULT NULL AFTER `user_id`, ADD INDEX (`user_client`); 


ALTER TABLE `instituicao` ADD `inst_client` INT NULL DEFAULT NULL AFTER `inst_status`, ADD INDEX (`inst_client`); 

ALTER TABLE `localizacao` ADD `loc_unit` INT NULL DEFAULT NULL COMMENT 'ID da unidade' AFTER `loc_status`, ADD INDEX (`loc_unit`); 


ALTER TABLE `custom_fields` DROP INDEX `field_name`, ADD UNIQUE `field_name` (`field_name`, `field_table_to`) USING BTREE; 


ALTER TABLE `ocorrencias` ADD `client` INT NULL DEFAULT NULL COMMENT 'Referencia para o cliente' AFTER `numero`, ADD INDEX (`client`); 

ALTER TABLE `configusercall` ADD `conf_scr_client` TINYINT(1) NOT NULL DEFAULT '0' AFTER `conf_opentoarea`;

ALTER TABLE `configusercall` ADD `conf_scr_auto_client` INT NULL DEFAULT NULL AFTER `conf_scr_custom_ids`; 

UPDATE usuarios SET user_client = 1 WHERE nivel IN (1,2);



ALTER TABLE `ocorrencias_log` ADD `log_cliente` INT NULL DEFAULT NULL AFTER `log_status`, ADD INDEX (`log_cliente`); 



CREATE TABLE `client_types` ( `id` INT NOT NULL AUTO_INCREMENT, `type_name` VARCHAR(255) NOT NULL , `type_description` TEXT NULL DEFAULT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB COMMENT = 'Tipos de clientes'; 

INSERT INTO `client_types` (`id`, `type_name`, `type_description`) VALUES (NULL, 'Default', NULL);

CREATE TABLE `client_status` ( `id` INT NOT NULL AUTO_INCREMENT , `status_name` VARCHAR(255) NOT NULL , `status_description` TEXT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB COMMENT = 'Status para clientes'; 

INSERT INTO `client_status` (`id`, `status_name`, `status_description`) VALUES (NULL, 'Default', NULL);



CREATE TABLE `clients_x_cfields` ( `id` INT NOT NULL AUTO_INCREMENT , `client_id` INT NOT NULL , `cfield_id` INT NOT NULL , `cfield_value` TEXT NULL , `cfield_is_key` TINYINT(1) NULL , PRIMARY KEY (`id`), INDEX (`client_id`), INDEX (`cfield_id`), INDEX (`cfield_is_key`)) ENGINE = InnoDB COMMENT = 'Campos customizados da tabela de clientes'; 



CREATE TABLE `measure_types` ( `id` INT NOT NULL AUTO_INCREMENT , `mt_name` VARCHAR(255) NOT NULL , `mt_description` TEXT NULL DEFAULT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB COMMENT = 'Características que podem ser medidas e comparadas'; 

CREATE TABLE `measure_units` ( `id` INT NOT NULL AUTO_INCREMENT , `type_id` INT NOT NULL COMMENT 'Referente ao ID do measure_types' , `unit_name` VARCHAR(255) NULL , `unit_abbrev` VARCHAR(10) NOT NULL , `equity_factor` DOUBLE NULL , PRIMARY KEY (`id`), INDEX (`type_id`)) ENGINE = InnoDB COMMENT = 'Unidades de medida para comparação'; 

ALTER TABLE `measure_units` ADD `operation` ENUM('/','*','=') NULL DEFAULT NULL COMMENT 'Define se o valor será multiplicado ou dividido pelo valor base' AFTER `equity_factor`; 

CREATE TABLE `model_x_specs` ( `id` INT NOT NULL AUTO_INCREMENT , `model_id` INT NOT NULL , `measure_unit_id` INT NOT NULL , `spec_value` FLOAT NOT NULL , PRIMARY KEY (`id`), INDEX (`model_id`), INDEX (`measure_unit_id`)) ENGINE = InnoDB COMMENT = 'Caracteristicas mensuraveis de cada modelo';

ALTER TABLE `model_x_specs` ADD `abs_value` DOUBLE NULL DEFAULT NULL COMMENT 'Valor absoluto, utilizado para comparacao' AFTER `spec_value`, ADD INDEX (`abs_value`); 


ALTER TABLE `marcas_comp` ADD `marc_manufacturer` INT NULL AFTER `marc_tipo`, ADD INDEX (`marc_manufacturer`); 


CREATE TABLE `assets_categories` ( `id` INT NOT NULL AUTO_INCREMENT , `cat_name` VARCHAR(255) NOT NULL , `cat_description` TEXT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB COMMENT = 'Categorias para tipos de ativos'; 


ALTER TABLE `assets_categories` ADD `cat_default_profile` INT NULL DEFAULT NULL COMMENT 'Perfil de formulário de cadastro' AFTER `cat_description`, ADD INDEX (`cat_default_profile`); 

ALTER TABLE `tipo_equip` ADD `tipo_categoria` INT NULL DEFAULT NULL AFTER `tipo_nome`, ADD INDEX (`tipo_categoria`); 







CREATE TABLE `assets_fields_profiles` ( `id` INT NOT NULL AUTO_INCREMENT , `profile_name` VARCHAR(255) NOT NULL , `asset_type` TINYINT(1) NULL DEFAULT '1' , `manufacturer` TINYINT(1) NULL DEFAULT '1' , `model` TINYINT(1) NULL DEFAULT '1' , `serial_number` TINYINT(1) NULL DEFAULT '1' , `part_number` TINYINT(1) NULL DEFAULT NULL , `department` TINYINT(1) NULL DEFAULT '1' , `situation` TINYINT(1) NULL DEFAULT '1' , `net_name` TINYINT(1) NULL DEFAULT NULL , `asset_unit` TINYINT(1) NULL DEFAULT '1' , `asset_tag` TINYINT(1) NULL DEFAULT '1' , `invoice_number` TINYINT(1) NULL DEFAULT NULL , `cost_center` TINYINT(1) NULL DEFAULT NULL , `price` TINYINT(1) NULL DEFAULT NULL , `buy_date` TINYINT(1) NULL DEFAULT NULL , `supplier` TINYINT(1) NULL DEFAULT NULL , `assistance_type` TINYINT(1) NULL DEFAULT NULL , `warranty_type` TINYINT(1) NULL DEFAULT NULL , `warranty_time` TINYINT(1) NULL DEFAULT NULL , `extra_info` TINYINT(1) NOT NULL DEFAULT '1' , `field_specs_ids` VARCHAR(255) NULL DEFAULT NULL , `field_custom_ids` VARCHAR(255) NULL DEFAULT NULL , PRIMARY KEY (`id`), INDEX (`asset_type`), INDEX (`manufacturer`), INDEX (`model`), INDEX (`serial_number`), INDEX (`part_number`), INDEX (`department`), INDEX (`situation`), INDEX (`net_name`), INDEX (`asset_unit`), INDEX (`asset_tag`), INDEX (`invoice_number`), INDEX (`cost_center`), INDEX (`price`), INDEX (`buy_date`), INDEX (`supplier`), INDEX (`assistance_type`), INDEX (`warranty_type`), INDEX (`warranty_time`), INDEX (`extra_info`), INDEX (`field_specs_ids`), INDEX (`field_custom_ids`)) ENGINE = InnoDB COMMENT = 'Perfis para os formulários de cadastro dos tipos de ativos';




CREATE TABLE `assets_fields_required` ( `id` INT NOT NULL AUTO_INCREMENT , `profile_id` INT NOT NULL , `field_name` VARCHAR(255) NOT NULL , `field_required` TINYINT(1) NOT NULL DEFAULT '0' , PRIMARY KEY (`id`), INDEX (`profile_id`), INDEX (`field_name`), INDEX (`field_required`)) ENGINE = InnoDB COMMENT = 'Obrigatoriedade preenchimen dos campos no cadastro de ativos';



CREATE TABLE `profiles_x_assets_types` ( `id` INT NOT NULL AUTO_INCREMENT , `profile_id` INT NOT NULL , `asset_type_id` INT NOT NULL , PRIMARY KEY (`id`), INDEX (`profile_id`), UNIQUE (`asset_type_id`)) ENGINE = InnoDB COMMENT = 'Relação de tipos de ativos e perfis de campos para cadastro';  



CREATE TABLE `assets_types_part_of` ( `id` INT NOT NULL AUTO_INCREMENT , `parent_id` INT NOT NULL COMMENT 'Tipo de ativo pai' , `child_id` INT NOT NULL COMMENT 'tipo de ativo que poderá compor outro tipo de ativo' , PRIMARY KEY (`id`), UNIQUE (`parent_id`, `child_id`)) ENGINE = InnoDB COMMENT = 'Relação de composição entre tipos de ativos';



CREATE TABLE `assets_x_specs` ( `id` INT NOT NULL AUTO_INCREMENT , `asset_id` INT NOT NULL COMMENT 'ID do ativo', `asset_spec_id` INT NOT NULL COMMENT 'ID do modelo de especificação' , PRIMARY KEY (`id`), INDEX (`asset_id`), INDEX (`asset_spec_id`)) ENGINE = InnoDB COMMENT = 'Todas as especificações de um ativo de inventário';



CREATE TABLE `assets_x_cfields` ( `id` INT NOT NULL AUTO_INCREMENT , `asset_id` INT NOT NULL COMMENT 'ID do ativo' , `cfield_id` INT NOT NULL , `cfield_value` TEXT NULL , `cfield_is_key` TINYINT(1) NULL , PRIMARY KEY (`id`), INDEX (`asset_id`), INDEX (`cfield_id`), INDEX (`cfield_is_key`)) ENGINE = InnoDB COMMENT = 'Campos personalizados em cada ativo registrado no sistema';





ALTER TABLE `assets_x_specs` ADD `asset_spec_tagged_id` INT NULL COMMENT 'ID do ativo se especificacao corresponder a um ativo cadastrado' AFTER `asset_spec_id`, ADD UNIQUE (`asset_spec_tagged_id`); 


CREATE TABLE `model_x_child_models` ( `id` INT NOT NULL AUTO_INCREMENT , `model_id` INT NOT NULL , `model_child_id` INT NOT NULL , PRIMARY KEY (`id`), INDEX (`model_id`), INDEX (`model_child_id`)) ENGINE = InnoDB COMMENT = 'Guarda os modelos de configurações salvos'; 


CREATE TABLE `assets_x_specs_changes` ( `id` INT NOT NULL AUTO_INCREMENT , `asset_id` INT NOT NULL COMMENT 'ID do ativo' , `spec_id` INT NOT NULL COMMENT 'ID da especificacao (id do modelo)' , `user_id` INT NOT NULL COMMENT 'Usuário que executou a ação' , `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data da ação' , PRIMARY KEY (`id`), INDEX (`asset_id`), INDEX (`spec_id`), INDEX (`user_id`)) ENGINE = InnoDB COMMENT = 'Registro de modificações de configurações do ativo';

ALTER TABLE `assets_x_specs_changes` ADD `action` ENUM('add','remove') NULL DEFAULT NULL COMMENT 'Flag para saber se a especificação foi add ou removed' AFTER `spec_id`, ADD INDEX (`action`); 




CREATE TABLE `areas_x_units` ( `id` INT NOT NULL AUTO_INCREMENT , `area_id` INT NOT NULL , `unit_id` INT NOT NULL , PRIMARY KEY (`id`), INDEX (`area_id`), INDEX (`unit_id`)) ENGINE = InnoDB COMMENT = 'Controle de unidades que a area pode acessar no inventário'; 



CREATE TABLE `ticket_x_workers` ( `id` INT NOT NULL AUTO_INCREMENT , `ticket` INT NOT NULL , `user_id` INT NOT NULL , `main_worker` TINYINT(1) NOT NULL DEFAULT '0' , `assigned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP , PRIMARY KEY (`id`), INDEX (`ticket`), INDEX (`user_id`), INDEX (`main_worker`)) ENGINE = InnoDB COMMENT = 'Operadores por chamado';



CREATE TABLE `tickets_extended` ( `ticket` INT NOT NULL , `main_worker` INT NULL DEFAULT NULL , PRIMARY KEY (`ticket`), INDEX (`main_worker`)) ENGINE = InnoDB COMMENT = 'Extenção das tabela de ocorrências'; 

ALTER TABLE `tickets_extended` ADD `updated_at` TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `main_worker`, ADD INDEX (`updated_at`); 


ALTER TABLE `config` ADD `conf_status_scheduled_to_worker` INT NULL DEFAULT '2' COMMENT 'status para chamados agendados para operadores' AFTER `conf_cfield_only_opened`, ADD `conf_status_in_worker_queue` INT NULL DEFAULT 2 COMMENT 'status para chamados que entram na fila direta do operador apos agendamento' AFTER `conf_status_scheduled_to_worker`;



INSERT INTO `msgconfig` (`msg_cod`, `msg_event`, `msg_fromname`, `msg_replyto`, `msg_subject`, `msg_body`, `msg_altbody`) VALUES
(NULL, 'agendamento-para-operador', 'SISTEMA OCOMON', 'your_email@your_domain.com', 'Chamado encaminhado para %funcionario%', '<p>Caro %funcionario%,<br>\r\nO chamado <strong>%numero%</strong> foi editado e está direcionado a você.<br>\r\nDescrição: <strong>%descricao%</strong></p><p>Funcionário Responsável:<strong> %funcionario_responsavel%<br></strong>Funcionários alocados:<strong> %funcionarios%<br></strong><br>\r\nAlteração mais recente: <strong>%assentamento%</strong><br>\r\nContato: <strong>%contato%</strong>&nbsp;&nbsp;<br>\r\nTelefone: <strong>%telefone%</strong><br>\r\nOcorrência editada pelo operador: <strong>%editor%</strong><br>\r\n%site%</p>\r\n', 'Caro %funcionario%,\r\nO chamado %numero% foi editado e está direcionado a você.\r\nDescrição: %descricao%\r\n\r\nFuncionários alocados: %funcionarios%\r\n\r\nAlteração mais recente: %assentamento%\r\nContato: %contato%  \r\nTelefone: %telefone%\r\nOcorrência editada pelo operador: %editor%\r\n%site%');



ALTER TABLE `usuarios` ADD `can_route` TINYINT(1) NULL DEFAULT NULL COMMENT 'Operador pode encaminhar chamados' AFTER `forget`, ADD `can_get_routed` TINYINT(1) NULL DEFAULT NULL COMMENT 'Operador pode receber chamados encaminhados' AFTER `can_route`, ADD INDEX (`can_route`), ADD INDEX (`can_get_routed`);



ALTER TABLE `usuarios` ADD `user_bgcolor` VARCHAR(7) NOT NULL DEFAULT '#3A4D56' AFTER `can_get_routed`, ADD `user_textcolor` VARCHAR(7) NOT NULL DEFAULT '#FFFFFF' AFTER `user_bgcolor`;


UPDATE usuarios SET can_route = 1 WHERE nivel IN (1,2);
UPDATE usuarios SET can_route = 0 WHERE nivel NOT IN (1,2);
UPDATE usuarios SET can_get_routed = 1 WHERE nivel IN (1,2);
UPDATE usuarios SET can_get_routed = 0 WHERE nivel NOT IN (1,2);


ALTER TABLE `config` ADD `set_response_at_routing` ENUM('always','never','choice') NOT NULL DEFAULT 'choice' COMMENT 'Primeira resposta ao encaminhar' AFTER `conf_status_in_worker_queue`, ADD INDEX (`set_response_at_routing`); 



ALTER TABLE `instituicao` CHANGE `inst_nome` `inst_nome` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''; 


--
-- ALTERAÇOES PÓS VERSÃO 5 PREVIEW JÁ INSTALADA EM CLIENTES
--
ALTER TABLE `config` ADD `conf_status_reopen` INT NOT NULL DEFAULT '1' COMMENT 'Status para chamados reabertos' AFTER `set_response_at_routing`, ADD INDEX (`conf_status_reopen`); 



ALTER TABLE `config` ADD `conf_status_done` INT NOT NULL DEFAULT '4' COMMENT 'Status para concluídos pelo operador' AFTER `conf_status_reopen`, ADD `conf_status_done_rejected` INT NOT NULL DEFAULT '1' COMMENT 'Status para solucoes rejeitas pelo solicitante' AFTER `conf_status_done`, ADD `conf_time_to_close_after_done` INT NOT NULL DEFAULT '3' COMMENT 'Tempo em dias para que o chamado seja encerrado após conclusão' AFTER `conf_status_done_rejected`, ADD INDEX (`conf_status_done`), ADD INDEX (`conf_status_done_rejected`);


ALTER TABLE `config` ADD `conf_rate_after_deadline` ENUM("great","good","regular","bad","not_rated") NOT NULL DEFAULT 'great' COMMENT 'Avaliação que o chamado assumirá caso não seja avaliado em tempo' AFTER `conf_time_to_close_after_done`; 



CREATE TABLE `tickets_rated` ( `ticket` INT NOT NULL , `rate` ENUM("great","good","regular","bad","not_rated") NULL DEFAULT 'great' COMMENT 'Avaliacao do atendimento' , `rate_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP , `automatic_rate` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Se a avaliacao foi feita de forma automatica ou pelo solicitante' , PRIMARY KEY (`ticket`), INDEX (`rate`)) ENGINE = InnoDB;

ALTER TABLE `tickets_rated` CHANGE `rate_date` `rate_date` DATETIME on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP; 

ALTER TABLE `tickets_rated` ADD `rejected_count` INT NOT NULL DEFAULT '0' COMMENT 'Contador de vezes que o ticket tiver atendimento rejeitado' AFTER `automatic_rate`; 

ALTER TABLE `tickets_rated` CHANGE `rate_date` `rate_date` DATETIME NULL DEFAULT CURRENT_TIMESTAMP; 


INSERT INTO `msgconfig` (`msg_cod`, `msg_event`, `msg_fromname`, `msg_replyto`, `msg_subject`, `msg_body`, `msg_altbody`) VALUES (NULL, 'rejeitado-para-area', 'Sistema OcoMon', 'ocomon@yourdomain.com', 'Atendimento Rejeitado', 'Atenção:\r\n\r\nO Chamado %numero% teve a conclusão do seu atendimento rejeitada pelo solicitante.\r\n\r\nO chamado está retornando para a fila de atendimento.\r\n\r\nAtte.\r\nSistema OcoMon', 'Atenção:\r\n\r\nO Chamado %numero% teve a conclusão do seu atendimento rejeitada pelo solicitante.\r\n\r\nO chamado está retornando para a fila de atendimento.\r\n\r\nAtte.\r\nSistema OcoMon'); 


ALTER TABLE `msgconfig` CHANGE `msg_event` `msg_event` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'evento', CHANGE `msg_fromname` `msg_fromname` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'from', CHANGE `msg_replyto` `msg_replyto` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'ocomon@yourdomain.com', CHANGE `msg_subject` `msg_subject` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'subject';


ALTER TABLE `global_tickets` ADD `gt_rating_id` VARCHAR(255) NULL DEFAULT NULL AFTER `gt_id`, ADD INDEX (`gt_rating_id`); 


CREATE TABLE `users_x_area_admin` ( `id` INT NOT NULL AUTO_INCREMENT , `user_id` INT NOT NULL , `area_id` INT NOT NULL , PRIMARY KEY (`id`), UNIQUE (`user_id`, `area_id`)) ENGINE = InnoDB COMMENT = 'Usuarios x areas gerenciadas por eles'; 

ALTER TABLE `config` ADD `conf_only_weekdays_to_count_after_done` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Define se o prazo para validacao e avaliacao considera apenas dias uteis' AFTER `conf_time_to_close_after_done`; 


ALTER TABLE `configusercall` ADD `cfields_only_edition` TEXT NULL COMMENT 'Ids dos campos que só aparecerao na edicao' AFTER `conf_scr_auto_client`, ADD `cfields_user_hidden` TEXT NULL COMMENT 'Ids dos campos que nunca serao exibidos para o usuario' AFTER `cfields_only_edition`; 


ALTER TABLE `ocorrencias` ADD `profile_id` INT NULL DEFAULT NULL COMMENT 'Perfil de tela utilizado no chamado' AFTER `oco_tag`; 


INSERT INTO `msgconfig` (`msg_cod`, `msg_event`, `msg_fromname`, `msg_replyto`, `msg_subject`, `msg_body`, `msg_altbody`) VALUES (NULL, 'rejeitado-para-operador', 'Sistema OcoMon', 'ocomon@yourdomain.com', 'Seu atendimento %numero% foi rejeitado', '<p>Caro %operador%,&nbsp;</p><p>O seu atendimento ao chamado %numero% foi rejeitado pelo solicitante.Por favor, entre em contato com o solicitante para entender sobre a razão e então concluir o atendimento da forma devida.</p><p>Atte.<br></p><p>Sistema OcoMon</p>', 'Caro operador\r\n\r\nO seu atendimento ao chamado %numero% foi rejeitado pelo solicitante.\r\n\r\nPor favor, entre em contato com o solicitante para entender a razão e então conclua o atendimento.\r\n\r\nSistema OcoMon'), (NULL, 'solicita-avaliacao', 'Sistema OcoMon', 'ocomon@yourdomain.com', 'Avalie o atendimento recebido', '<p>Caro %contato%,</p><p>Seu atendimento foi concluído para o chamado %numero% e está aguardando por sua aprovação e avaliação.</p><p>Para aprovar e avaliar basta acessar o seguinte endereço: %rating_url% </p><p>Atte.Sistema OcoMon</p>', 'Caro %contato%\r\n\r\nSeu atendimento foi concluído para o chamado %numero% e está aguardando por sua aprovação e avaliação.\r\n\r\nPara aprovar e avaliar basta acessar o seguinte endereço: %rating_url% \r\n\r\nAtte.\r\nSistema OcoMon');



ALTER TABLE `predios` ADD `pred_unit` INT NULL DEFAULT NULL COMMENT 'Unidade - fará o vínculo com o cliente' AFTER `pred_desc`, ADD INDEX (`pred_unit`); 

ALTER TABLE `reitorias` ADD `reit_unit` INT NULL DEFAULT NULL COMMENT 'Unidade - referencia para clientes' AFTER `reit_nome`, ADD INDEX (`reit_unit`); 

ALTER TABLE `dominios` ADD `dom_unit` INT NULL DEFAULT NULL COMMENT 'Unidade - referência para cliente' AFTER `dom_desc`, ADD INDEX (`dom_unit`); 

ALTER TABLE `ccusto` ADD `client` INT NULL DEFAULT NULL COMMENT 'Referencia para clientes' AFTER `descricao`, ADD INDEX (`client`); 


ALTER TABLE `assets_categories` ADD `cat_is_digital` TINYINT(1) NULL DEFAULT '0' COMMENT 'Define se a categoria é para ativos físicos ou digitais' AFTER `cat_default_profile`, ADD INDEX (`cat_is_digital`); 


ALTER TABLE `assets_categories` ADD `cat_is_product` TINYINT(1) NULL DEFAULT '0' COMMENT 'Define se a categoria é para produtos' AFTER `cat_is_digital`, ADD `cat_is_service` TINYINT(1) NULL DEFAULT '0' COMMENT 'Define se a categoria é para serviços' AFTER `cat_is_product`, ADD `cat_bgcolor` VARCHAR(7) NOT NULL DEFAULT '#17A2B8' AFTER `cat_is_service`, ADD `cat_textcolor` VARCHAR(7) NOT NULL DEFAULT '#FFFFFF' AFTER `cat_bgcolor`, ADD INDEX (`cat_is_product`), ADD INDEX (`cat_is_service`);


ALTER TABLE `marcas_comp` CHANGE `marc_nome` `marc_nome` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '0'; 



INSERT INTO `measure_types` (`id`, `mt_name`, `mt_description`) VALUES
(1, 'Armazenamento', 'Capacidade de armazenamento de arquivos'),
(4, 'Volume', 'Medidas de volume'),
(5, 'Área', 'Tamanho de área, geralmente em m2'),
(6, 'Taxa de transmissão', NULL),
(7, 'Frequência', 'Utilizado para medição de desempenho em microprocessadores'),
(8, 'Largura', 'Medida de largura'),
(9, 'RPM', 'Rotações por minuto'),
(10, 'Altura', 'Medida da altura de qualquer coisa'),
(11, 'Tamanho de tela', 'Tamanho de tela de dispositivos'),
(12, 'Memória RAM', NULL),
(13, 'Peso', 'Referente a massa de cada item'),
(18, 'Carga elétrica', 'Utilizada para medir autonomia de baterias'),
(19, 'Núcleos', 'Quantidade de núcleos de processador'),
(20, 'Zoom Ótico', 'Zoom ótimo de câmeras'),
(21, 'Zoom Digital', 'Zoom digital para câmeras'),
(22, 'Potência', NULL),
(23, 'Tensão', 'Potencial elétrico');


--
-- Extraindo dados da tabela `measure_units`
--

INSERT INTO `measure_units` (`id`, `type_id`, `unit_name`, `unit_abbrev`, `equity_factor`, `operation`) VALUES
(1, 1, 'Gigabyte', 'GB', 1024, '*'),
(2, 1, 'Kilobyte', 'KB', 1024, '/'),
(3, 1, 'Megabyte', 'MB', 1, '='),
(4, 5, 'Metro quadrado', 'm2', 1, '='),
(5, 1, 'Terabyte', 'TB', 1048575, '*'),
(6, 6, 'Megabit', 'Mbit/s', 1, '='),
(7, 6, 'Gigabit', 'Gbit/s', 1000, '*'),
(8, 6, 'Terabit', 'Tbit/s', 1000000, '*'),
(9, 6, 'Megabyte', 'MB/s', 8, '*'),
(10, 6, 'Gigabyte', 'GB/s', 8000, '*'),
(11, 7, 'Megahertz', 'MHz', 1, '='),
(12, 7, 'Kiloherts', 'kHz', 1000, '/'),
(13, 7, 'Gigahertz', 'GHz', 1000, '*'),
(14, 7, 'Terahertz', 'THz', 1000000, '*'),
(15, 8, 'Metro', 'm', 1, '='),
(16, 8, 'Centrímetro', 'cm', 100, '/'),
(17, 8, 'Milímetro', 'mm', 1000, '/'),
(18, 8, 'Quilômetro', 'km', 1000, '*'),
(19, 9, 'Rotação/minuto', 'rpm', 1, '='),
(20, 10, 'metro', 'm', 1, '='),
(21, 4, 'Litro', 'l', 1, '='),
(22, 11, 'Polegada', '&#34;', 1, '='),
(23, 12, 'Gigabyte', 'GB', 1, '='),
(24, 12, 'Megabyte', 'MB', 1024, '/'),
(25, 13, 'Quilograma', 'kg', 1, '='),
(26, 13, 'grama', 'g', 1000, '/'),
(27, 13, 'miligrama', 'mg', 1000000, '/'),
(28, 13, 'Tonelada', 't', 1000, '*'),
(29, 10, 'Centrímetro', 'cm', 100, '/'),
(32, 10, 'Milímetro', 'mm', 1000, '/'),
(42, 4, 'Centrímetro cúbico', 'cm3', 1000, '/'),
(43, 4, 'Metro cúbico', 'm3', 1000, '*'),
(44, 5, 'Centrímetro quadrado', 'cm2', 10000, '/'),
(45, 18, 'Miliampere-hora', 'mAh', 1, '='),
(46, 18, 'Ampere-hora', 'Ah', 1000, '*'),
(47, 19, 'Núcleo', 'Núcleo', 1, '='),
(48, 20, 'Zoom', 'x', 1, '='),
(49, 21, 'Zoom', 'x', 1, '='),
(50, 22, 'Watt', 'W', 1, '='),
(51, 22, 'Kilowatt', 'kW', 1000, '*'),
(52, 22, 'Horse-power', 'hp', 1.34, '*'),
(53, 23, 'Volt', 'V', 1, '='),
(54, 23, 'Kilovolt', 'kV', 1000, '*');


COMMIT;



/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
