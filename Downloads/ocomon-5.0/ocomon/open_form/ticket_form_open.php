<?php session_start();
/*   
	Copyright 2023 Flávio Ribeiro

	This file is part of OCOMON.

	OCOMON is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3 of the License, or
	(at your option) any later version.
	OCOMON is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with Foobar; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/


require_once __DIR__ . "/" . "../../includes/include_geral_new.inc.php";
require_once __DIR__ . "/" . "../../includes/classes/ConnectPDO.php";

use includes\classes\ConnectPDO;

$conn = ConnectPDO::getInstance();

$configExt = getConfigValues($conn);

if (!$configExt['ANON_OPEN_ALLOW'] || (isset($_SESSION['s_logado']) && $_SESSION['s_logado'] == 1)) {
	echo "<script>top.window.location = '../../login.php'</script>";
	exit();
}

$nextDay = new DateTime('+1 day');
$sysConfig = getConfig($conn);
$mailConfig = getMailConfig($conn);
$screen = getScreenInfo($conn, $configExt['ANON_OPEN_SCREEN_PFL']);
$statusInfo = getStatusInfo($conn, $configExt['ANON_OPEN_STATUS']);

$formatBar = hasFormatBar($sysConfig, '%oco%');


/* Para manter a compatibilidade com versões antigas */
$table = getTableCompat($conn);

$version4 = true;


if (!isset($_POST['submit']) || empty($_POST)) {
?>
	<!DOCTYPE html>
	<html lang="pt-BR">

	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title><?= TRANS('TICKET_OPENING'); ?></title>

		<link rel="stylesheet" type="text/css" href="../../includes/css/estilos.css" />
		<link rel="stylesheet" type="text/css" href="../../includes/css/estilos_custom.css" />
		<link rel="stylesheet" type="text/css" href="../../includes/components/jquery/datetimepicker/jquery.datetimepicker.css" />

		<link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap/custom.css" />
		<link rel="stylesheet" type="text/css" href="../../includes/components/fontawesome/css/all.min.css" />
		<link rel="stylesheet" type="text/css" href="../../includes/components/summernote/summernote-bs4.css" />
		<link rel="stylesheet" type="text/css" href="../../includes/css/util.css" />
		<link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap-select/dist/css/bootstrap-select.min.css" />
		<link rel="stylesheet" type="text/css" href="../../includes/css/my_bootstrap_select.css" />

		<!-- <link rel="stylesheet" type="text/css" href="../../includes/css/index_css.css" /> -->
		<link rel="shortcut icon" href="../../includes/icons/favicon.ico">


		<style>
			.container-mt {
				/* margin-top: 70px; */
				margin-bottom: 50px;
			}
			.container-message {
				margin-top: 50px;
				/* margin-bottom: 50px; */
			}

		</style>

	</head>

	<body>

		<div class="topo topo-color fixed-top p-2">
			<div id="header_logo">
				<span class="logo"><img src="../../MAIN_LOGO.svg" width="240"></span>
			</div>
			<div id="header_elements" class="fs-13">
				<span class="font-weight-bold d-none d-sm-block"> <?= TRANS('USER_NOT_LOGGED') . "&nbsp;&nbsp;|&nbsp;&nbsp;"; ?>
					<a class="topo-color fs-18" title="<?= TRANS('ENTER_IN'); ?>" href="../../index.php" data-toggle="popover" data-content="<?= TRANS('LOGIN_TO_ACCESS'); ?>" data-placement="left" data-trigger="hover"><i class="fas fa-sign-in-alt "></i></a>
				</span>
				<span class="d-block d-sm-none text-right">
					<a class="topo-color fs-18" href="../../index.php" title="<?= TRANS('ENTER_IN'); ?>" data-toggle="popover" data-content="<?= TRANS('LOGIN_TO_ACCESS'); ?>" data-placement="left" data-trigger="hover"><i class="fas fa-sign-in-alt "></i></a>
				</span>
			</div>

		</div>

		<div class="container-message">
			<?= message('info', TRANS('TXT_IMPORTANT') . ':', TRANS('BLIND_OPENING_INFO'), '', '', true);; ?>
		</div>

		<?php

		/* Se a abertura de chamados não estiver habilitada para o perfil de tela */
		if ((!empty($screen) && !$screen['conf_user_opencall'])) {
			$msgDisable = TRANS('MSG_OPEN_TICKET_DISABLED');
			// echo mensagem($msgDisable);
			echo message('info', 'Ooops!', $msgDisable, '', '', true);
			exit;
		}
		?>

		<div class="container">
			<div id="idLoad" class="loading" style="display:none"></div>
		</div>

		<div class="container container-mt">

			<div class="modal" tabindex="-1" id="modalDefault">
				<div class="modal-dialog modal-xl">
					<div class="modal-content">
						<div id="divModalDetails" class="p-3">
						</div>
					</div>
				</div>
			</div>
			<?php
			if (isset($_SESSION['flash']) && !empty($_SESSION['flash'])) {
				echo $_SESSION['flash'];
				$_SESSION['flash'] = '';
			}
			?>

			<div id="divResult"></div>

			<h5 class="my-4"><i class="fas fa-plus-square text-secondary"></i>&nbsp;<?= TRANS('OPENING_BLIND_TICKET') . ":"; ?></h5>
			<form name="form" id="form" method="post" action="<?= $_SERVER['PHP_SELF']; ?>" enctype="multipart/form-data">
				<?= csrf_input(); ?>
				<input type="hidden" name="MAX_FILE_SIZE" value="<?= $sysConfig['conf_upld_size']; ?>" />


				<div class="form-group row my-4">
					<?php
					/* Área de atendimento */
					if (($screen['conf_scr_area']) || empty($screen)) {
					?>
						<label for="idArea" class="col-sm-2 col-md-2 col-form-label text-md-right"><?= TRANS('RESPONSIBLE_AREA'); ?></label>
						<div class="form-group col-md-4">
							<select class="form-control " id="idArea" name="sistema">

								<option value="<?= $screen['conf_opentoarea']; ?>" selected><?= getAreaInfo($conn, $screen['conf_opentoarea'])['area_name']; ?></option>

							</select>
						</div>
					<?php

					}

					/* Tipo de problema */
					if (($screen['conf_scr_prob']) || empty($screen)) {
					?>
						<label for="idProblema" class="col-sm-2 col-md-2 col-form-label  text-md-right"><?= TRANS('ISSUE_TYPE'); ?></label>
						<div class="form-group col-md-4">
							<select class="form-control " id="idProblema" name="problema">

								<option value="" selected><?= TRANS('ISSUE_TYPE'); ?></option>
								<?php
								// $issues = getIssuesByArea($conn);
								$issues = ($version4 ? getIssuesByArea4($conn, false, null, 1) : getIssuesByArea($conn));
								foreach ($issues as $issue) {
								?>
									<option value="<?= $issue['prob_id']; ?>"><?= $issue['problema']; ?></option>
								<?php
								}
								?>
							</select>
						</div>
						<!-- Lista de tipos de problemas do mesmo tipo e categorias -->
						<div id="issueCategories"></div>
						<!-- Descrição do tipo de problema selecionado -->
						<div id="issueDescription"></div>
					<?php
					}


					/* Descrição do chamado */
					if ($screen['conf_scr_desc'] || empty($screen)) {
					?>
						<div class="w-100"></div>
						<label for="idDescricao" class="col-md-2 col-form-label  text-md-right"><?= TRANS('DESCRIPTION'); ?></label>

						<div class="form-group col-md-10">
							<textarea class="form-control" id="idDescricao" name="descricao" rows="4" required></textarea>
							<div class="invalid-feedback">
								<?= TRANS('MANDATORY_FIELD'); ?>
							</div>
							<small class="form-text text-muted">
								<?= TRANS('DESCRIPTION_HELPER'); ?>.
							</small>
						</div>
					<?php

					}


					/* Unidade */
					if (($screen['conf_scr_unit']) || empty($screen)) {
					?>
						<label for="idUnidade" class="col-sm-2 col-md-2 col-form-label  text-md-right"><?= TRANS('COL_UNIT'); ?></label>
						<div class="form-group col-md-4">
							<select class="form-control " id="idUnidade" name="instituicao">
								<option value="-1" selected><?= TRANS('SEL_UNIT'); ?></option>
								<?php
								$units = getUnits($conn);
								foreach ($units as $unit) {
								?>
									<option value="<?= $unit['inst_cod']; ?>"><?= $unit['inst_nome']; ?></option>
								<?php
								}
								?>
							</select>
						</div>
					<?php
					}


					/* Etiqueta do equipamento */
					if ($screen['conf_scr_tag'] || empty($screen)) {
					?>
						<label for="idEtiqueta" class="col-md-2 col-form-label  text-md-right text-nowrap"><?= TRANS('ASSET_TAG'); ?></label>

						<div class="form-group col-md-4">
							<input type="text" class="form-control " id="idEtiqueta" name="equipamento" value="" placeholder="<?= TRANS('FIELD_TAG_EQUIP'); ?>" />
						</div>
					<?php
					}

					/* Contato */
					if ($screen['conf_scr_contact'] || empty($screen)) {
					?>
						<label for="contato" class="col-md-2 col-form-label  text-md-right"><?= TRANS('CONTACT'); ?></label>
						<div class="form-group col-md-4">
							<input type="text" class="form-control " id="contato" name="contato" value="" autocomplete="off" placeholder="<?= TRANS('CONTACT_PLACEHOLDER'); ?>" />
						</div>

					<?php
					}


					/* E-mail de contato */
					if ($screen['conf_scr_contact_email'] || empty($screen)) {
					?>
						<label for="contato_email" class="col-md-2 col-form-label  text-md-right"><?= TRANS('CONTACT_EMAIL'); ?></label>
						<div class="form-group col-md-4">
							<input type="email" class="form-control " id="contato_email" name="contato_email" value="" autocomplete="off" placeholder="<?= TRANS('CONTACT_EMAIL_PLACEHOLDER'); ?>" />
						</div>

					<?php
					}


					/* Telefone */
					if ($screen['conf_scr_fone'] || empty($screen)) {
					?>
						<label for="idTelefone" class="col-md-2 col-form-label  text-md-right"><?= TRANS('COL_PHONE'); ?></label>
						<div class="form-group col-md-4">
							<input type="tel" class="form-control " id="idTelefone" name="telefone" value="" placeholder="<?= TRANS('PHONE_PLACEHOLDER'); ?>" />
						</div>
					<?php
					}


					/* Departamentos */
					if ($screen['conf_scr_local'] || empty($screen)) {
					?>
						<label for="idLocal" class="col-md-2 col-form-label  text-md-right"><?= TRANS('DEPARTMENT'); ?></label>

						<div class="form-group col-md-4">
							<select class="form-control " name="local" id="idLocal">
								<option value="-1"><?= TRANS('SEL_DEPARTMENT'); ?></option>
								<?php
								$departments = getDepartments($conn);
								foreach ($departments as $department) {
								?>
									<option value="<?= $department['loc_id']; ?>"><?= $department['local']; ?> - <?= $department['pred_desc']; ?></option>
								<?php
								}
								?>
							</select>
						</div>
					<?php
					}


					/* Upload de arquivos */
					if ($screen['conf_scr_upload'] || empty($screen)) {
					?>
						<label class="col-md-2 col-form-label  text-md-right"><?= TRANS('ATTACH_FILE'); ?></label>

						<div class="form-group col-md-4">
							<div class="field_wrapper" id="field_wrapper">
								<div class="input-group">
									<div class="input-group-prepend">
										<div class="input-group-text">
											<a href="javascript:void(0);" class="add_button" title="<?= TRANS('TO_ATTACH_ANOTHER'); ?>"><i class="fa fa-plus"></i></a>
										</div>
									</div>
									<!-- <input type="file" class="form-control  " name="anexo[]" /> -->
									<div class="custom-file">
										<input type="file" class="custom-file-input" name="anexo[]" id="idInputFile" aria-describedby="inputGroupFileAddon01" lang="br">
										<label class="custom-file-label text-truncate" for="inputGroupFile01"><?= TRANS('CHOOSE_FILE'); ?></label>
									</div>
								</div>
							</div>
						</div>
					<?php
					}


					/* Prioridade */
					if ($screen['conf_scr_prior'] || empty($screen)) {
					?>
						<label for="idPrioridade" class="col-md-2 col-form-label  text-md-right"><?= TRANS('OCO_PRIORITY'); ?></label>
						<div class="form-group col-md-4">
							<select class="form-control " id="idPrioridade" name="prioridade">
								<?php
								$priorities = getPriorities($conn);
								foreach ($priorities as $priority) {
								?>
									<option value="<?= $priority['pr_cod']; ?>" <?= ($priority['pr_default'] ? " selected" : ""); ?>><?= $priority['pr_desc']; ?></option>
								<?php
								}
								?>
							</select>
						</div>
					<?php
					}


					/* Data */
					if ($screen['conf_scr_date'] || empty($screen)) {
					?>
						<label for="data_abertura" class="col-md-2 col-form-label  text-md-right"><?= TRANS('OPENING_DATE'); ?></label>
						<div class="form-group col-md-4">
							<input type="text" class="form-control  " readonly id="data_abertura" name="data_abertura" value="<?= date("d/m/Y H:i:s"); ?>" />
						</div>
					<?php
					}

					/* Status do chamado */
					if ($screen['conf_scr_status'] || empty($screen)) {
					?>
						<label for="status" class="col-md-2 col-form-label  text-md-right"><?= TRANS('COL_STATUS'); ?></label>
						<div class="form-group col-md-4">
							<input type="text" class="form-control  " readonly id="status" name="status" value="<?= $statusInfo['status']; ?>" />
						</div>
					<?php
					}


					/* Agendamento do chamado */
					if ($screen['conf_scr_schedule'] || empty($screen)) {
					?>
						<label for="idDate_schedule" class="col-md-2 col-form-label  text-md-right"><?= TRANS('TO_SCHEDULE'); ?></label>
						<div class="form-group col-md-4">
							<div class="input-group">
								<div class="input-group-prepend">
									<div class="input-group-text">
										<input type="checkbox" name="allowSchedule" id="allowSchedule" value="1">
									</div>
								</div>
								<input type="text" class="form-control " id="idDate_schedule" name="date_schedule" value="" placeholder="<?= TRANS('DATE_TO_SCHEDULE'); ?>" autocomplete="off" disabled /> <!--  -->
							</div>
						</div>
						
					<?php
					}


					/* Canal de atendimento */
					$channel = getChannels($conn, $configExt['ANON_OPEN_CHANNEL']);
					if ($screen['conf_scr_channel'] || empty($screen)) {
					?>
						<label for="channel" class="col-md-2 col-form-label  text-md-right"><?= TRANS('OPENING_CHANNEL'); ?></label>
						<div class="form-group col-md-4">
							<select class="form-control " id="channel" name="channel">
								<option value="<?= $channel['id']; ?>"><?= $channel['name']; ?></option>
							</select>
						</div>
					<?php
					}






					/* Campos personalizados */
					$fields_id = [];
					/* Campos personalizados que estão no perfil mas não devem aparecer na tela de abertura */
					$fields_only_edition_ids = [];
					if (!empty($screen['conf_scr_custom_ids'])) {
						$fields_id = explode(',', $screen['conf_scr_custom_ids']);
						$fields_only_edition_ids = explode(',', $screen['cfields_only_edition']);
						
						$labelColSize = 2;
						$fieldColSize = 4;
						$fieldRowSize = 10;
						$custom_fields = getCustomFields($conn, null, 'ocorrencias');
						?>
						<!-- <div class="w-100"></div> -->
						<?php
						foreach ($custom_fields as $row) {

							// if (in_array($row['id'], $fields_id)) {
							if (in_array($row['id'], $fields_id) && !in_array($row['id'], $fields_only_edition_ids)) {

								$inlineAttributes = keyPairsToHtmlAttrs($row['field_attributes']);
								$maskType = ($row['field_mask_regex'] ? 'regex' : 'mask');
								$fieldMask = "data-inputmask-" . $maskType . "=\"" . $row['field_mask'] . "\"";
								?>
								<?= ($row['field_type'] == 'textarea' ? '<div class="w-100"></div>'  : ''); ?>
								<label for="<?= $row['field_name']; ?>" class="col-sm-<?= $labelColSize; ?> col-md-<?= $labelColSize; ?> col-form-label text-md-right " title="<?= $row['field_title']; ?>" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= $row['field_description']; ?>"><?= $row['field_label']; ?></label>
								<div class="form-group col-md-<?= ($row['field_type'] == 'textarea' ? $fieldRowSize  : $fieldColSize); ?>">
									<?php
									if ($row['field_type'] == 'select') {
										?>
										<select class="form-control custom_field_select" name="<?= $row['field_name']; ?>" id="<?= $row['field_name']; ?>" <?= $inlineAttributes; ?>>
										<?php
										
										$options = [];
										$options = getCustomFieldOptionValues($conn, $row['id']);
										?>
											<option value=""><?= TRANS('SEL_SELECT'); ?></option>
										<?php
											foreach ($options as $rowValues) {
											?>
												<option value="<?= $rowValues['id']; ?>"
													<?= ($row['field_default_value'] == $rowValues['option_value'] ? " selected" : ""); ?>
												><?= $rowValues['option_value']; ?></option>
											<?php
											}
										?>
										</select>
										<?php
									} elseif ($row['field_type'] == 'select_multi') {
										?>
										<select class="form-control custom_field_select_multi" name="<?= $row['field_name']; ?>[]" id="<?= $row['field_name']; ?>" multiple="multiple" <?= $inlineAttributes; ?>>
										<?php
										$defaultSelections = explode(',', $row['field_default_value']);
										$options = [];
										$options = getCustomFieldOptionValues($conn, $row['id']);
										?>
										<?php
											foreach ($options as $rowValues) {
											?>
												<option value="<?= $rowValues['id']; ?>"
													
													<?= (in_array($rowValues['option_value'], $defaultSelections) ? ' selected': ''); ?>
												><?= $rowValues['option_value']; ?></option>
											<?php
											}
										?>
										</select>
										<?php
									} elseif ($row['field_type'] == 'number') {
										?>
										<input class="form-control custom_field_number" type="number" name="<?= $row['field_name']; ?>" id="<?= $row['field_name']; ?>" value="<?= $row['field_default_value'] ?? ''; ?>" placeholder="<?= $row['field_placeholder']; ?>" <?= $inlineAttributes; ?>>
										<?php
									} elseif ($row['field_type'] == 'checkbox') {
										$checked_checkbox = ($row['field_default_value'] ? " checked" : "");
										?>
										<div class="form-check form-check-inline">
										<input class="form-check-input custom_field_checkbox" type="checkbox" name="<?= $row['field_name']; ?>" id="<?= $row['field_name']; ?>" <?= $checked_checkbox ?> <?= $inlineAttributes; ?>>
										<legend class="col-form-label col-form-label-sm"><?= $row['field_placeholder']; ?></legend>
										</div>
										<?php
									} elseif ($row['field_type'] == 'textarea') {
										?>
										<textarea class="form-control custom_field_textarea" name="<?= $row['field_name']; ?>" id="<?= $row['field_name']; ?>" placeholder="<?= $row['field_placeholder']; ?>" <?= $inlineAttributes; ?>><?= $row['field_default_value'] ?? ''; ?></textarea>
										<?php
									} elseif ($row['field_type'] == 'date') {
										?>
											<input class="form-control custom_field_date" type="text" name="<?= $row['field_name']; ?>" id="<?= $row['field_name']; ?>" value="<?= $row['field_default_value'] ?? ''; ?>" placeholder="<?= $row['field_placeholder']; ?>" <?= $inlineAttributes; ?> autocomplete="off">
										<?php
									} elseif ($row['field_type'] == 'time') {
										?>
											<input class="form-control custom_field_time" type="text" name="<?= $row['field_name']; ?>" id="<?= $row['field_name']; ?>" value="<?= $row['field_default_value'] ?? ''; ?>" placeholder="<?= $row['field_placeholder']; ?>" <?= $inlineAttributes; ?> autocomplete="off">
										<?php
									} elseif ($row['field_type'] == 'datetime') {
										?>
											<input class="form-control custom_field_datetime" type="text" name="<?= $row['field_name']; ?>" id="<?= $row['field_name']; ?>" value="<?= $row['field_default_value'] ?? ''; ?>" placeholder="<?= $row['field_placeholder']; ?>" <?= $inlineAttributes; ?> autocomplete="off">
										<?php
									} else {
										?>
											<input class="form-control custom_field_text" type="text" name="<?= $row['field_name']; ?>" id="<?= $row['field_name']; ?>" value="<?= $row['field_default_value'] ?? ''; ?>" placeholder="<?= $row['field_placeholder']; ?>" <?= $fieldMask; ?> <?= $inlineAttributes; ?> autocomplete="off">
										<?php
									}
								?>
								</div>
								
							<?php
							}
						} /* foreach */
					}
					/* Fim dos campos personalizados */
					?>
					<!-- <div class="w-100"></div> -->
			


					<label for="captcha" class="col-md-2 col-form-label  text-md-right"><?= TRANS('CAPTCHA'); ?></label>
					<div class="form-group col-md-4">
						<div class="input-group">
							<div class="input-group-prepend">
								<div class="input-group-text" id="img_captcha">
								</div>
								<div class="input-group-text pointer" id="reload_captcha">
									<i class="fas fa-sync-alt"></i>
								</div>
							</div>
							<input type="text" class="form-control " id="captcha" name="captcha" value="" placeholder="<?= TRANS('TYPE_CAPTCHA'); ?>" />

						</div>
					</div>

					<?php
						if ($version4) {
							?>
								<input type="hidden" name="url_process" id="url_process" value="get_issues_by_area4.php" />
							<?php
						} else {
							?>
								<input type="hidden" name="url_process" id="url_process" value="get_issues_by_area.php" />
							<?php
						}
					?>



					<input type="hidden" name="action" value="open" />
					<input type="hidden" name="submit" value="submit" />

					<div class="w-100"></div>
					<div class="form-group col-md-8 d-none d-md-block">
					</div>
					<div class="form-group col-12 col-md-2 ">
						<button type="submit" id="idSubmit" class="btn btn-primary btn-block"><?= TRANS('BT_OK'); ?></button>
					</div>
					<div class="form-group col-12 col-md-2">
						<button type="reset" id="reset" class="btn btn-secondary btn-block"><?= TRANS('BT_CANCEL'); ?></button>
					</div>

				</div>

			</form>
		</div>


		<!-- FOOTER -->
		<small>
			<div class=" fixed-bottom ">
				<div class="  bg-light border-top text-center p-2 " style="z-index:4; ">
					<div class="footer-text">
						<a href="https://ocomonphp.sourceforge.io/" target="_blank">
							OcoMon
						</a>&nbsp;-&nbsp;
						<?= TRANS('OCOMON_ABSTRACT'); ?><br />
						<?= TRANS('COL_VERSION') . ": " . VERSAO . " - " . TRANS('MNS_MSG_LIC') . " GPL"; ?>
					</div>
				</div>
			</div>
		</small>

	<?php
}
	?>


	<script src="../../includes/javascript/funcoes-3.0.js"></script>
	<script src="../../includes/components/jquery/jquery.js"></script>
	<script src="../../includes/components/jquery/jquery.initialize.min.js"></script>
	<script src="../../includes/components/jquery/datetimepicker/build/jquery.datetimepicker.full.min.js"></script>
	<script src="../../includes/components/bootstrap/js/bootstrap.bundle.js"></script>
	<script src="../../includes/components/summernote/summernote-bs4.js"></script>
	<script src="../../includes/components/summernote/lang/summernote-pt-BR.min.js"></script>
	<script src="../../includes/components/bootstrap-select/dist/js/bootstrap-select.min.js"></script>
	<script src="../../includes/components/Inputmask-5.x/dist/jquery.inputmask.min.js"></script>
	<script src="../../includes/components/Inputmask-5.x/dist/bindings/inputmask.binding.js"></script>

	<script>
		$(function() {

			/* Permitir a replicação do campo de input file */
			var maxField = <?= $sysConfig['conf_qtd_max_anexos']; ?>;
			var addButton = $('.add_button'); //Add button selector
			var wrapper = $('.field_wrapper'); //Input field wrapper

			var fieldHTML = '<div class="input-group d-block my-1"><div class="input-group-prepend"><div class="input-group-text"><a href="javascript:void(0);" class="remove_button"><i class="fa fa-minus"></i></a></div><div class="custom-file"><input type="file" class="custom-file-input" name="anexo[]"  aria-describedby="inputGroupFileAddon01" lang="br"><label class="custom-file-label text-truncate" for="inputGroupFile01"><?= TRANS('CHOOSE_FILE', '', 1); ?></label></div></div></div></div>';

			var x = 1; //Initial field counter is 1

			//Once add button is clicked
			$(addButton).click(function() {
				//Check maximum number of input fields
				if (x < maxField) {
					x++; //Increment field counter
					$(wrapper).append(fieldHTML); //Add field html
				}
			});

			//Once remove button is clicked
			$(wrapper).on('click', '.remove_button', function(e) {
				e.preventDefault();
				$(this).parent('div').parent('div').parent('div').remove(); //Remove field html
				x--; //Decrement field counter
			});

			// $('label').addClass('col-form-label-lg');
			// $('input, select').addClass('form-control-lg');

			generateCaptcha();

			$('#reload_captcha').on('click', function() {
				generateCaptcha();
			});

			/* Show selected issue */
			if ($("#idProblema").length > 0) {
				$("#idProblema").off().on("change", function() {
					showSelectedIssue();
					showIssueDescription($("#idProblema").val());
				});
			}

			if ($("#idProblema").length > 0) {
				/* Adicionei o mutation observer em função dos elementos que são adicionados após o carregamento do DOM */
				var obsRadio = $.initialize(".radio_prob", function() {
					$(".radio_prob").off().on('click', function() {
						showIssueDescription($(this).val());
					});
				}, {
					target: document.getElementById('form')
				}); /* o target limita o scopo do observer */
			}


			if ($('#idInputFile').length > 0) {
				/* Adicionei o mutation observer em função dos elementos que são adicionados após o carregamento do DOM */
				var obs = $.initialize(".custom-file-input", function() {
					$('.custom-file-input').on('change', function() {
						let fileName = $(this).val().split('\\').pop();
						$(this).next('.custom-file-label').addClass("selected").html(fileName);
					});

				}, {
					target: document.getElementById('field_wrapper')
				}); /* o target limita o scopo do observer */
			}

			var bar = '<?php print $formatBar; ?>';
			if ($('#idDescricao').length > 0 && bar == 1) {
				$('#idDescricao').summernote({
					// placeholder: 'Hello Bootstrap 4',
					toolbar: [
						['style', ['style']],
						['font', ['bold', 'underline', 'clear']],
						['fontname', ['fontname']],
						['fontsize', ['fontsize']],
						['color', ['color']],
						['para', ['ul', 'ol', 'paragraph']],
						['table', ['table']],
						['insert', ['link']],
						['view', ['fullscreen']],
					],
					lang: 'pt-BR', // default: 'en-US'
					tabsize: 2,
					// height: 100,
					height: 80, // set editor height
					minHeight: null, // set minimum height of editor
					maxHeight: null, // set maximum height of editor
					// focus: true // set focus to editable area after initializing summernote
				});
			}

			$('input, select, textarea').on('change', function() {
				$(this).removeClass('is-invalid');
			});

			$('#idSubmit').on('click', function(e) {
				e.preventDefault();
				var loading = $(".loading");
				$(document).ajaxStart(function() {
					loading.show();
				});
				$(document).ajaxStop(function() {
					loading.hide();
				});

				var form = $('form').get(0);
				// disabled the submit button
				$("#idSubmit").prop("disabled", true);

				$.ajax({
					url: './ticket_form_open_process.php',
					method: 'POST',

					data: new FormData(form),
					dataType: 'json',

					cache: false,
					processData: false,
					contentType: false,
				}).done(function(response) {

					if (!response.success) {
						$('#divResult').html(response.message);
						$('input, select, textarea').removeClass('is-invalid');
						if (response.field_id != "") {
							$('#' + response.field_id).focus().addClass('is-invalid');
						}
						$("#idSubmit").prop("disabled", false);
						generateCaptcha();
					} else {
						$('#divResult').html('');
						$('input, select, textarea').removeClass('is-invalid');
						$("#idSubmit").prop("disabled", false);
						// var url = 'ticket_show.php?numero=' + response.numero;
						// $(location).prop('href', url);
						var url = response.global_access_uri;
						$(location).prop('href', url);
						return false;
					}
				});
				return false;
			});

			$('#reset').on('click', function() {
				var url = '<?= $_SERVER['PHP_SELF'] ?>';
				$(location).prop('href', url);
				return false;
			});

			$(function() {
				$('[data-toggle="popover"]').popover()
			});

			$('.popover-dismiss').popover({
				trigger: 'focus'
			});


			$('#allowSchedule').on('click', function() {

				if ($(this).is(':checked')) {
					$('#idDate_schedule').prop('disabled', false);
					$('#idDate_schedule').val('<?= $nextDay->format('d/m/Y H:i'); ?>');
				} else {
					$('#idDate_schedule').prop('disabled', true);
					$('#idDate_schedule').val('');

				}
			});

			/* Para campos personalizados - bind pela classe*/
			$.fn.selectpicker.Constructor.BootstrapVersion = '4';
			$('.custom_field_select_multi').selectpicker({
				/* placeholder */
				title: "<?= TRANS('SEL_SELECT', '', 1); ?>",
				liveSearch: true,
				liveSearchNormalize: true,
				liveSearchPlaceholder: "<?= TRANS('BT_SEARCH', '', 1); ?>",
				noneResultsText: "<?= TRANS('NO_RECORDS_FOUND', '', 1); ?> {0}",
				style: "",
				styleBase: "form-control input-select-multi",
			});

			/* Idioma global para os calendários */
			$.datetimepicker.setLocale('pt-BR');
			
			$('#idDate_schedule').datetimepicker({
                timepicker: true,
                format: 'd/m/Y H:i',
				step: 30,
				minDate: 0,
                lazyInit: true
            });

			/* Para campos personalizados - bind pela classe*/
            $('.custom_field_datetime').datetimepicker({
                timepicker: true,
                format: 'd/m/Y H:i',
				step: 30,
				// minDate: 0,
                lazyInit: true
            });

			$('.custom_field_date').datetimepicker({
                timepicker: false,
                format: 'd/m/Y',
                lazyInit: true
            });

            $('.custom_field_time').datetimepicker({
                datepicker: false,
                format: 'H:i',
                step: 30,
                lazyInit: true
            });
		});


		/**
		 * Funções
		 */


		function showSelectedIssue() {

			if ($('#idProblema').length > 0) {
				var loading = $(".loading");
				$(document).ajaxStart(function() {
					loading.show();
				});
				$(document).ajaxStop(function() {
					loading.hide();
				});

				$.ajax({
					url: '../geral/get_issue_detailed.php',
					method: 'POST',
					dataType: 'json',
					data: {
						area: $('#idArea').val() ?? '',
						issue_selected: $('#idProblema').val() ?? '',
					},
				}).done(function(response) {

					if (response.length > 0) {
						$('#issueCategories').addClass("form-group col-md-12");
						$('#issueCategories').empty();

						var html = '<table class="table table-striped table-hover">';
						html += '<thead bg-secondary">';
						html += '<tr class="header">';
						html += '<td><?= TRANS('ISSUE_TYPE'); ?></td>';
						html += '<td><?= TRANS('COL_SLA'); ?></td>';
						html += '<td><?= $sysConfig['conf_prob_tipo_1']; ?></td>';
						html += '<td><?= $sysConfig['conf_prob_tipo_2']; ?></td>';
						html += '<td><?= $sysConfig['conf_prob_tipo_3']; ?></td>';
						html += '</tr>';
						html += '</thead>';
						for (var i in response) {
							html += '<tr>';
							html += '<td>';
							html += '<input type="radio" class="radio_prob" id="idRadioProb' + response[i].prob_id + '" name="radio_prob" value="' + response[i].prob_id + '"';
							if (response[i].prob_id == $("#idProblema").val()) {
								html += ' checked';
							}
							html += '> ';
							html += response[i].problema;
							html += '</td>';
							html += '<td>' + response[i].slas_desc + '</td>';
							html += '<td>' + (response[i].probt1_desc ?? '') + '</td>';
							html += '<td>' + (response[i].probt2_desc ?? '') + '</td>';
							html += '<td>' + (response[i].probt3_desc ?? '') + '</td>';
							html += '</tr>';
						}
						html += '</table>';
						$('#issueCategories').append(html);
					} else {
						$('#issueCategories').removeClass("form-group col-md-12");
						$('#issueCategories').empty();
					}
				});
			}
		}


		function generateCaptcha() {

			var loading = $(".loading");
			$(document).ajaxStart(function() {
				loading.show();
			});
			$(document).ajaxStop(function() {
				loading.hide();
			});

			$.ajax({
				url: './set_captcha.php',
				method: 'POST',
				dataType: 'json',

			}).done(function(response) {
				if (response.captcha != '') {
					$("#img_captcha").empty().html('<img src="' + response.captcha + '">');
				} else {
					$("#img_captcha").empty();
				}
				// $("#issueDescription").empty().html(response.description);
			});
		}


		function showIssueDescription(val) {

			var loading = $(".loading");
			$(document).ajaxStart(function() {
				loading.show();
			});
			$(document).ajaxStop(function() {
				loading.hide();
			});

			$.ajax({
				url: '../geral/get_issue_description.php',
				method: 'POST',
				dataType: 'json',
				data: {
					prob_id: val,
				},
			}).done(function(response) {
				if (response.description != '') {
					$("#issueDescription").addClass("form-group col-md-12");
				} else {
					$("#issueDescription").removeClass("form-group col-md-12");
					$("#issueDescription").empty();
				}
				$("#issueDescription").empty().html(response.description);
			});
		}


		function dateToBR(date) {

			let d = date.split('-')[2];
			let m = date.split('-')[1];
			let y = date.split('-')[0];

			var date = new Date();
			date.setDate(d);
			date.setMonth(m);
			date.setFullYear(y);

			var year = date.getFullYear().toString();
			var month = (date.getMonth() + 101).toString().substring(1);
			var day = (date.getDate() + 100).toString().substring(1);

			return day + '/' + month + '/' + year;
		}

		function getTime(date) {
			var date = new Date(date);

			var hour = ('0' + date.getHours()).slice(-2);
			var minute = ('0' + date.getMinutes()).slice(-2);
			var second = ('0' + date.getSeconds()).slice(-2);

			return hour + ':' + minute;
		}


		function popup_alerta(pagina) { //Exibe uma janela popUP
			x = window.open(pagina, 'Alerta', 'dependent=yes,width=700,height=470,scrollbars=yes,statusbar=no,resizable=yes');
			//x.moveTo(100,100);
			x.moveTo(window.parent.screenX + 50, window.parent.screenY + 50);
			return false
		}
	</script>
	</body>

	</html>