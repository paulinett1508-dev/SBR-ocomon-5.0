/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;


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
