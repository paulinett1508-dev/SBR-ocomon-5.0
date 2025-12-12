<?php
/* Copyright 2023 Flávio Ribeiro

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
 */ session_start();

if (!isset($_SESSION['s_logado']) || $_SESSION['s_logado'] == 0) {
	$_SESSION['session_expired'] = 1;
	echo "<script>top.window.location = '../../index.php'</script>";
	exit;
}

require_once __DIR__ . "/" . "../../includes/include_geral_new.inc.php";
require_once __DIR__ . "/" . "../../includes/classes/ConnectPDO.php";

use includes\classes\ConnectPDO;

$conn = ConnectPDO::getInstance();

$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 1);

$_SESSION['s_page_admin'] = $_SERVER['PHP_SELF'];


$fields = [
	'conf_scr_client' => TRANS('CLIENT'),
	'conf_scr_area' => TRANS('RESPONSIBLE_AREA'),
	'conf_scr_prob' => TRANS('ISSUE_TYPE'),
	'conf_scr_desc' => TRANS('DESCRIPTION'),
	'conf_scr_unit' => TRANS('COL_UNIT'),
	'conf_scr_tag' => TRANS('ASSET_TAG'),
	'conf_scr_contact' => TRANS('CONTACT'),
	'conf_scr_contact_email' => TRANS('CONTACT_EMAIL'),
	'conf_scr_fone' => TRANS('COL_PHONE'),
	'conf_scr_local' => TRANS('DEPARTMENT'),
	'conf_scr_operator' => TRANS('TECHNICIAN'),
	'conf_scr_upload' => TRANS('ATTACH_FILE'),
	'conf_scr_prior' => TRANS('OCO_PRIORITY'),
	'conf_scr_foward' => TRANS('FORWARD_TICKET_TO'),
	'conf_scr_mail' => TRANS('OCO_FIELD_SEND_MAIL_TO'),
	'conf_scr_date' => TRANS('OPENING_DATE'),
	'conf_scr_status' => TRANS('COL_STATUS'),
	'conf_scr_schedule' => TRANS('TO_SCHEDULE'),
	'conf_scr_channel' => TRANS('OPENING_CHANNEL')
];


?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" type="text/css" href="../../includes/css/estilos.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/css/my_datatables.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/css/switch_radio.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap/custom.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/components/fontawesome/css/all.min.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/components/datatables/datatables.min.css" />

	<title>OcoMon&nbsp;<?= VERSAO; ?></title>
	<style>
		li.list_areas {
			line-height: 1.5em;
		}

		.container-switch {
			position: relative;
		}

		.switch-next-checkbox {
			position: absolute;
			top: 0;
			left: 140px;
			z-index: 1;
		}


		.container-switch-2 {
			position: relative;
		}
		.switch-next-checkbox-1 {
			position: absolute;
			top: 0;
			left: 140px;
			z-index: 1;
		}
		.switch-next-checkbox-2 {
			position: absolute;
			top: 0;
			left: 180px;
			z-index: 1;
		}
	</style>
</head>

<body>

	<div class="container">
		<div id="idLoad" class="loading" style="display:none"></div>
	</div>

	<div id="divResult"></div>


	<div class="container-fluid">
		<h4 class="my-4"><i class="fas fa-chalkboard-teacher text-secondary"></i>&nbsp;<?= TRANS('MNL_SCREEN_PROFILE'); ?></h4>
		<div class="modal" id="modal" tabindex="-1" style="z-index:9001!important">
			<div class="modal-dialog modal-xl">
				<div class="modal-content">
					<div id="divDetails">
					</div>
				</div>
			</div>
		</div>

		<?php
		if (isset($_SESSION['flash']) && !empty($_SESSION['flash'])) {
			echo $_SESSION['flash'];
			$_SESSION['flash'] = '';
		}

		$qrymsgdefault = $QRY["useropencall"];
		$execqrydefault = $conn->query($qrymsgdefault);
		$rowmsgdefault = $execqrydefault->fetch();


		$query = "SELECT c.*, a.* 
					FROM configusercall as c, sistemas as a 
					WHERE c.conf_opentoarea = a.sis_id and c.conf_cod <> 1"; //codigo 1 é reservado para as definicoes globais

		if (isset($_GET['cod'])) {
			$query .= " AND c.conf_cod = '" . (int)$_GET['cod'] . "' ";
		}
		$query .= " ORDER BY conf_name";
		$resultado = $conn->query($query);
		$registros = $resultado->rowCount();

		if ((!isset($_GET['action'])) && !isset($_POST['submit'])) {

		?>
			<!-- Modal -->
			<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header bg-light">
							<h5 class="modal-title" id="exampleModalLabel"><i class="fas fa-exclamation-triangle text-secondary"></i>&nbsp;<?= TRANS('REMOVE'); ?></h5>
							<button type="button" class="close" data-dismiss="modal" aria-label="Close">
								<span aria-hidden="true">&times;</span>
							</button>
						</div>
						<div class="modal-body">
							<?= TRANS('CONFIRM_REMOVE'); ?> <span class="j_param_id"></span>?
						</div>
						<div class="modal-footer bg-light">
							<button type="button" class="btn btn-secondary" data-dismiss="modal"><?= TRANS('BT_CANCEL'); ?></button>
							<button type="button" id="deleteButton" class="btn"><?= TRANS('BT_OK'); ?></button>
						</div>
					</div>
				</div>
			</div>

			<button class="btn btn-sm btn-primary" id="idBtIncluir" name="new"><?= TRANS("ACT_NEW"); ?></button><br /><br />
			<?php
			if ($registros == 0) {
				echo message('info', '', TRANS('NO_RECORDS_FOUND'), '', '', true);
			} else {
			?>
				<table id="table_lists" class="stripe hover order-column row-border" border="0" cellspacing="0" width="100%">

					<thead>
						<tr class="header">
							<td class="line sigla"><?= TRANS('SCREEN_PROFILE_NAME'); ?></td>
							<td class="line sigla"><?= TRANS('DESTINY_AREA'); ?></td>
							<td class="line sigla" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_USED_IN_CLASSIC_MODE'); ?>"><?= TRANS('APLIED_TO'); ?></td>
							<td class="line sigla" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_USER_IN_DYNAMIC_MODE'); ?>"><?= TRANS('PROBLEM_TYPES'); ?></td>
							<td class="line sigla"><?= TRANS('FIELDS_ENABLED'); ?></td>
							<td class="line editar"><?= TRANS('BT_EDIT'); ?></td>
							<td class="line remover"><?= TRANS('BT_REMOVE'); ?></td>
						</tr>
					</thead>
					<tbody>
						<?php

						foreach ($resultado->fetchall() as $row) {

							/* Campos habilitados */
							$fieldsInProfile = [];
							$customFieldsInProfile = [];
							foreach ($fields as $tableField => $label) {
								if ($row[$tableField]) {
									$fieldsInProfile[] = $label;
								}
							}


							/* Campos personalizados (vinculados ao perfil) não exibidos na tela de abertura */
							$customFieldsOnlyEdition = [];
							$only_edition_fields_id = [];
							if ($row['cfields_only_edition']) {
								$only_edition_fields_id = explode(',', $row['cfields_only_edition']);

								$custom_fields = getCustomFields($conn, null, 'ocorrencias');
								foreach ($custom_fields as $cfield) {
									if (in_array($cfield['id'], $only_edition_fields_id)) {
										$customFieldsOnlyEdition[] = $cfield['field_label'];
									}
								}
							}


							/* Campos personalizados */
							if ($row['conf_scr_custom_ids']) {
								$fields_id = explode(',', $row['conf_scr_custom_ids']);

								$custom_fields = getCustomFields($conn, null, 'ocorrencias');
								foreach ($custom_fields as $cfield) {
									// if (in_array($cfield['id'], $fields_id)) {
									if (in_array($cfield['id'], $fields_id) && !in_array($cfield['id'], $only_edition_fields_id)) {
										$customFieldsInProfile[] = $cfield['field_label'];
									}
								}
							}

							$listAreasIn = "";
							$sqlIn = "SELECT * FROM sistemas where sis_screen='" . $row['conf_cod'] . "' ORDER BY sistema";
							$resIn = $conn->query($sqlIn);
							foreach ($resIn->fetchall() as $rowIn) {
								$listAreasIn .= '<li class="list_areas">' . $rowIn['sistema'] ?? '' . '</li>';
							}

							$listIssuesIn = "";
							$sqlIn = "SELECT * FROM problemas WHERE prob_profile_form = '" . $row['conf_cod'] . "' ORDER BY problema";
							$resIn = $conn->query($sqlIn);
							foreach ($resIn->fetchall() as $rowIn) {
								$listIssuesIn .= '<li class="list_areas">' . $rowIn['problema'] ?? '' . '</li>';
							}

						?>
							<tr>
								<td class="line"><?= $row['conf_name']; ?>
								<?php
									if ($row['conf_is_default']) {
										echo "&nbsp;<span class='badge badge-info p-2'>" . TRANS('DEFAULT_PROFILE') . "</span>";
									}
								?>
								</td>
								<td class="line"><?= $row['sistema']; ?></td>
								<td class="line"><?= $listAreasIn; ?></td>
								<td class="line"><?= $listIssuesIn; ?></td>
								<td class="line">
									<?= strToTags(implode(",", $fieldsInProfile), 4, 'success'); ?>
									<?= strToTags(implode(",", $customFieldsInProfile), 4, 'info', 'custom-fields-link'); ?>
									<?= strToTags(implode(",", $customFieldsOnlyEdition), 4, 'warning', 'custom-fields-only-edition', 'fas fa-eye-slash'); ?>
								</td>
								<td class="line"><button type="button" class="btn btn-secondary btn-sm" onclick="redirect('<?= $_SERVER['PHP_SELF']; ?>?action=edit&cod=<?= $row['conf_cod']; ?>&cellStyle=true')"><?= TRANS('BT_EDIT'); ?></button></td>
								<td class="line"><button type="button" class="btn btn-danger btn-sm" onclick="confirmDeleteModal('<?= $row['conf_cod']; ?>')"><?= TRANS('REMOVE'); ?></button></td>
							</tr>

						<?php
						}
						?>
					</tbody>
				</table>
			<?php
			}
		} else
		if ((isset($_GET['action'])  && ($_GET['action'] == "new")) && !isset($_POST['submit'])) {

			?>
			<h6><?= TRANS('NEW_RECORD'); ?></h6>
			<form method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form">
				<?= csrf_input(); ?>
				<div class="form-group row my-4">

					<label for="profile_name" class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('SCREEN_PROFILE_NAME'); ?></label>
					<div class="form-group col-md-9">
						<input type="text" class="form-control " id="profile_name" name="profile_name" required />
					</div>

					<label for="area_to" class="col-md-3 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_AREA_USER_OPENTO'); ?>"><?= TRANS('OPT_AREA_USER_OPENTO'); ?></label>
					<div class="form-group col-md-9">
						<select class="form-control" name="area_to" id="area_to">
							<?php
							$sql = "SELECT * FROM sistemas where sis_atende = 1 AND sis_status = 1 ORDER BY sistema";
							$res = $conn->query($sql);
							foreach ($res->fetchall() as $row) {
							?>
								<option value='<?= $row['sis_id']; ?>'><?= $row['sistema']; ?></option>
							<?php
							}
							?>
						</select>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('OPT_ALLOW_USER_OPEN'); ?></label>
					<div class="form-group col-md-3 switch-field">
						<input type="radio" id="allow_user_open" name="allow_user_open" value="yes" checked />
						<label for="allow_user_open"><?= TRANS('YES'); ?></label>
						<input type="radio" id="allow_user_open_no" name="allow_user_open" value="no" />
						<label for="allow_user_open_no"><?= TRANS('NOT'); ?></label>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_DEFAULT_SCREEN_PROFILE'); ?>"><?= TRANS('SET_AS_DEFAULT_PROFILE'); ?></label>
					<div class="form-group col-md-3 switch-field">
						<input type="radio" id="is_default" name="is_default" value="yes" />
						<label for="is_default"><?= TRANS('YES'); ?></label>
						<input type="radio" id="is_default_no" name="is_default" value="no" checked/>
						<label for="is_default_no"><?= TRANS('NOT'); ?></label>
					</div>


					<div class="form-group col-md-12">
						<h6 class="my-4"><?= TRANS('AVAILABLE_FIELDS_IN_OPENING_SCREEN'); ?></h6>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_CLIENT'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<input type="radio" id="field_client" name="field_client" value="yes" checked />
						<label for="field_client"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_client_no" name="field_client" value="no" />
						<label for="field_client_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<input type="checkbox" class="" name="field_client_required" id="field_client_required">
							<small class=" text-danger"><i class="fas fa-asterisk"></i></small>
						</div>
					</div>
					<div class="w-100"></div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_AREA'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<input type="radio" id="field_area" name="field_area" value="yes" checked />
						<label for="field_area"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_area_no" name="field_area" value="no" />
						<label for="field_area_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<input type="checkbox" class="" name="field_area_required" id="field_area_required">
							<small class=" text-danger" ><i class="fas fa-asterisk"></i></small>
						</div>
					</div>


					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_TYPE_OF_PROBLEM'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<input type="radio" id="field_issue" name="field_issue" value="yes" checked />
						<label for="field_issue"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_issue_no" name="field_issue" value="no" />
						<label for="field_issue_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<input type="checkbox" class="" name="field_issue_required" id="field_issue_required">
							<small class=" text-danger" ><i class="fas fa-asterisk"></i></small>
						</div>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_DESCRIPTION'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<input type="radio" id="field_description" name="field_description" value="yes" checked />
						<label for="field_description"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_description_no" name="field_description" value="no" />
						<label for="field_description_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<input type="checkbox" class="" name="field_description_required" id="field_description_required">
							<small class=" text-danger" ><i class="fas fa-asterisk"></i></small>
						</div>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_UNIT'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<input type="radio" id="field_unit" name="field_unit" value="yes" checked />
						<label for="field_unit"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_unit_no" name="field_unit" value="no" />
						<label for="field_unit_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<input type="checkbox" class="" name="field_unit_required" id="field_unit_required">
							<small class=" text-danger" ><i class="fas fa-asterisk"></i></small>
						</div>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_ASSET_TAG'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<input type="radio" id="field_tag_number" name="field_tag_number" value="yes" checked />
						<label for="field_tag_number"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_tag_number_no" name="field_tag_number" value="no" />
						<label for="field_tag_number_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<input type="checkbox" class="" name="field_tag_number_required" id="field_tag_number_required">
							<small class=" text-danger" ><i class="fas fa-asterisk"></i></small>
						</div>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('OPT_LNK_TAG'); ?></label>
					<div class="form-group col-md-3 switch-field ">
						<input type="radio" id="field_tag_check" name="field_tag_check" value="yes" checked />
						<label for="field_tag_check"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_tag_check_no" name="field_tag_check" value="no" />
						<label for="field_tag_check_no"><?= TRANS('NOT'); ?></label>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('OPT_LNK_HIST'); ?></label>
					<div class="form-group col-md-3 switch-field ">
						<input type="radio" id="field_tag_tickets" name="field_tag_tickets" value="yes" checked />
						<label for="field_tag_tickets"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_tag_tickets_no" name="field_tag_tickets" value="no" />
						<label for="field_tag_tickets_no"><?= TRANS('NOT'); ?></label>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_CONTACT'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<input type="radio" id="field_contact" name="field_contact" value="yes" checked />
						<label for="field_contact"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_contact_no" name="field_contact" value="no" />
						<label for="field_contact_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<input type="checkbox" class="" name="field_contact_required" id="field_contact_required">
							<small class=" text-danger" ><i class="fas fa-asterisk"></i></small>
						</div>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_CONTACT_EMAIL'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<input type="radio" id="field_contact_email" name="field_contact_email" value="yes" checked />
						<label for="field_contact_email"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_contact_email_no" name="field_contact_email" value="no" />
						<label for="field_contact_email_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<input type="checkbox" class="" name="field_contact_email_required" id="field_contact_email_required">
							<small class=" text-danger" ><i class="fas fa-asterisk"></i></small>
						</div>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_PHONE'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<input type="radio" id="field_phone" name="field_phone" value="yes" checked />
						<label for="field_phone"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_phone_no" name="field_phone" value="no" />
						<label for="field_phone_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<input type="checkbox" class="" name="field_phone_required" id="field_phone_required">
							<small class=" text-danger" ><i class="fas fa-asterisk"></i></small>
						</div>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_DEPARTMENT'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<input type="radio" id="field_department" name="field_department" value="yes" checked />
						<label for="field_department"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_department_no" name="field_department" value="no" />
						<label for="field_department_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<input type="checkbox" class="" name="field_department_required" id="field_department_required">
							<small class=" text-danger" ><i class="fas fa-asterisk"></i></small>
						</div>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('BUTTON_LOAD_DEPARTMENT'); ?></label>
					<div class="form-group col-md-3 switch-field ">
						<input type="radio" id="field_load_department" name="field_load_department" value="yes" checked />
						<label for="field_load_department"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_load_department_no" name="field_load_department" value="no" />
						<label for="field_load_department_no"><?= TRANS('NOT'); ?></label>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('OPT_SCH_LOCAL'); ?></label>
					<div class="form-group col-md-3 switch-field ">
						<input type="radio" id="field_search_dep_tags" name="field_search_dep_tags" value="yes" checked />
						<label for="field_search_dep_tags"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_search_dep_tags_no" name="field_search_dep_tags" value="no" />
						<label for="field_search_dep_tags_no"><?= TRANS('NOT'); ?></label>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_OPERATOR'); ?></label>
					<div class="form-group col-md-3 switch-field ">
						<input type="radio" id="field_operator" name="field_operator" value="yes" checked />
						<label for="field_operator"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_operator_no" name="field_operator" value="no" />
						<label for="field_operator_no"><?= TRANS('NOT'); ?></label>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_DATE'); ?></label>
					<div class="form-group col-md-3 switch-field ">
						<input type="radio" id="field_date" name="field_date" value="yes" checked />
						<label for="field_date"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_date_no" name="field_date" value="no" />
						<label for="field_date_no"><?= TRANS('NOT'); ?></label>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_SCHEDULE'); ?></label>
					<div class="form-group col-md-3 switch-field ">
						<input type="radio" id="field_schedule" name="field_schedule" value="yes" checked />
						<label for="field_schedule"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_schedule_no" name="field_schedule" value="no" />
						<label for="field_schedule_no"><?= TRANS('NOT'); ?></label>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_FORWARD'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<input type="radio" id="field_forward" name="field_forward" value="yes" checked />
						<label for="field_forward"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_forward_no" name="field_forward" value="no" />
						<label for="field_forward_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<input type="checkbox" class="" name="field_forward_required" id="field_forward_required">
							<small class=" text-danger" ><i class="fas fa-asterisk"></i></small>
						</div>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_STATUS'); ?></label>
					<div class="form-group col-md-3 switch-field ">
						<input type="radio" id="field_status" name="field_status" value="yes" checked />
						<label for="field_status"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_status_no" name="field_status" value="no" />
						<label for="field_status_no"><?= TRANS('NOT'); ?></label>
					</div>


					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_ATTACH_FILE'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<input type="radio" id="field_attach_file" name="field_attach_file" value="yes" checked />
						<label for="field_attach_file"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_attach_file_no" name="field_attach_file" value="no" />
						<label for="field_attach_file_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<input type="checkbox" class="" name="field_attach_file_required" id="field_attach_file_required">
							<small class=" text-danger" ><i class="fas fa-asterisk"></i></small>
						</div>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_PRIORITY'); ?></label>
					<div class="form-group col-md-3 switch-field ">
						<input type="radio" id="field_priority" name="field_priority" value="yes" checked />
						<label for="field_priority"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_priority_no" name="field_priority" value="no" />
						<label for="field_priority_no"><?= TRANS('NOT'); ?></label>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_SEND_EMAIL'); ?></label>
					<div class="form-group col-md-3 switch-field ">
						<input type="radio" id="field_send_mail" name="field_send_mail" value="yes" checked />
						<label for="field_send_mail"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_send_mail_no" name="field_send_mail" value="no" />
						<label for="field_send_mail_no"><?= TRANS('NOT'); ?></label>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_CHANNEL'); ?></label>
					<div class="form-group col-md-3 switch-field ">
						<input type="radio" id="field_channel" name="field_channel" value="yes" checked />
						<label for="field_channel"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_channel_no" name="field_channel" value="no" />
						<label for="field_channel_no"><?= TRANS('NOT'); ?></label>
					</div>

					<?php
					/* Campos personalizados */
					$custom_fields = getCustomFields($conn, null, 'ocorrencias', null, null);

					if (count($custom_fields)) {
					?>
						<div class="w-100">
							<p class="h6 text-center font-weight-bold mt-4"><?= TRANS('EXTRA_FIELDS'); ?></p>
						</div>
						<?php
						foreach ($custom_fields as $field) {
							$disabled = (!$field['field_active'] ? " disabled" : "");
						?>
							<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= $field['field_label']; ?></label>
							<div class="form-group col-md-3 switch-field container-switch-2">

								<input type="radio" id="<?= $field["field_name"]; ?>" name="<?= $field["field_name"]; ?>" value="yes" <?= $disabled; ?> />
								<label for="<?= $field["field_name"]; ?>"><?= TRANS('YES'); ?></label>
								<input type="radio" id="<?= $field["field_name"]; ?>_no" name="<?= $field["field_name"]; ?>" value="no" checked <?= $disabled; ?> />
								<label for="<?= $field["field_name"]; ?>_no"><?= TRANS('NOT'); ?></label>
								<div class="switch-next-checkbox-1" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('DISPLAY_ONLY_WHEN_EDITING'); ?>">
									<input type="checkbox" id="<?= 'only_edition_' . $field["field_name"]; ?>" name="<?= 'only_edition_' . $field["field_name"]; ?>">
									<span class="text-muted" ><i class="fas fa-user-slash"></i></span>
								</div>
								<div class="switch-next-checkbox-2" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HIDDEN_TO_ENDUSER'); ?>">
									<input type="checkbox" id="<?= 'hidden_' . $field["field_name"]; ?>" name="<?= 'hidden_' . $field["field_name"]; ?>">
									<span class="text-muted"><i class="fas fa-eye-slash"></i></span>
								</div>
							</div>
					<?php
						}
					}
					/* Fim do trecho referente aos campos personalizados */
					?>

					<div class="w-100"></div>
					<label for="opening_message" class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('OPT_FIELD_MSG'); ?></label>
					<div class="form-group col-md-9">
						<textarea class="form-control" id="opening_message" name="opening_message"><?= $rowmsgdefault['conf_scr_msg']; ?></textarea>
					</div>

					<div class="row w-100"></div>
					<div class="form-group col-md-8 d-none d-md-block">
					</div>
					<div class="form-group col-12 col-md-2 ">

						<input type="hidden" name="action" id="action" value="new">
						<button type="submit" id="idSubmit" name="submit" class="btn btn-primary btn-block"><?= TRANS('BT_OK'); ?></button>
					</div>
					<div class="form-group col-12 col-md-2">
						<button type="reset" class="btn btn-secondary btn-block" onClick="parent.history.back();"><?= TRANS('BT_CANCEL'); ?></button>
					</div>


				</div>
			</form>
		<?php
		} else

		if ((isset($_GET['action']) && $_GET['action'] == "edit") && empty($_POST['submit'])) {

			$row = $resultado->fetch();

			/* Recebe os valores de obrigatorieda para cada campo onde se aplica */
			$required_fields = getFormRequiredInfo($conn, $row['conf_cod']);

			/* Atualizar campos personalizados que foram desativados ou excluídos */

		?>
			<h6><?= TRANS('BT_EDIT'); ?></h6>
			<form method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form">
				<?= csrf_input(); ?>
				<div class="form-group row my-4">


					<label for="profile_name" class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('SCREEN_PROFILE_NAME'); ?></label>
					<div class="form-group col-md-9">
						<input type="text" class="form-control " id="profile_name" name="profile_name" required value="<?= $row['conf_name']; ?>" />
					</div>

					<label for="area_to" class="col-md-3 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_AREA_USER_OPENTO'); ?>"><?= TRANS('OPT_AREA_USER_OPENTO'); ?></label>
					<div class="form-group col-md-9">
						<select class="form-control" name="area_to" id="area_to">
							<?php
							$sql = "SELECT * FROM sistemas where sis_atende = 1 AND sis_status = 1 ORDER BY sistema";
							$res = $conn->query($sql);
							foreach ($res->fetchall() as $rowArea) {
							?>
								<option value='<?= $rowArea['sis_id']; ?>' <?= ($rowArea['sis_id'] == $row['sis_id'] ? 'selected' : ''); ?>>
									<?= $rowArea['sistema']; ?></option>
							<?php
							}
							?>
						</select>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('OPT_ALLOW_USER_OPEN'); ?></label>
					<div class="form-group col-md-3 switch-field ">
						<?php
						$yesChecked = ($row['conf_user_opencall'] == 1 ? "checked" : "");
						$noChecked = ($row['conf_user_opencall'] == 0 ? "checked" : "");
						?>
						<input type="radio" id="allow_user_open" name="allow_user_open" value="yes" <?= $yesChecked; ?> />
						<label for="allow_user_open"><?= TRANS('YES'); ?></label>
						<input type="radio" id="allow_user_open_no" name="allow_user_open" value="no" <?= $noChecked; ?> />
						<label for="allow_user_open_no"><?= TRANS('NOT'); ?></label>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_DEFAULT_SCREEN_PROFILE'); ?>"><?= TRANS('SET_AS_DEFAULT_PROFILE'); ?></label>
					<div class="form-group col-md-3 switch-field ">
						<?php
						$yesChecked = ($row['conf_is_default'] == 1 ? "checked" : "");
						$noChecked = ($row['conf_is_default'] == 0 ? "checked" : "");
						?>
						<input type="radio" id="is_default" name="is_default" value="yes" <?= $yesChecked; ?> />
						<label for="is_default"><?= TRANS('YES'); ?></label>
						<input type="radio" id="is_default_no" name="is_default" value="no" <?= $noChecked; ?> />
						<label for="is_default_no"><?= TRANS('NOT'); ?></label>
					</div>
                    


					<div class="form-group col-md-12">
						<h6 class="my-4" id="availableFields"><?= TRANS('AVAILABLE_FIELDS_IN_OPENING_SCREEN'); ?></h6>
					</div>


					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_CLIENT'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<?php
						$yesChecked = ($row['conf_scr_client'] == 1 ? "checked" : "");
						$noChecked = ($row['conf_scr_client'] == 0 ? "checked" : "");
						?>
						<input type="radio" id="field_client" name="field_client" value="yes" <?= $yesChecked; ?> />
						<label for="field_client"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_client_no" name="field_client" value="no" <?= $noChecked; ?> />
						<label for="field_client_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<?php
							$required = (count($required_fields) && key_exists('conf_scr_client', $required_fields) && $required_fields['conf_scr_client'] ? " checked" : "");
							?>
							<input type="checkbox" class="" name="field_client_required" id="field_client_required" <?= $required; ?>>
							<small class="text-danger"><i class="fas fa-asterisk"></i></small>
						</div>
					</div>
					<div class="w-100"></div>



					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_AREA'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<?php
						$yesChecked = ($row['conf_scr_area'] == 1 ? "checked" : "");
						$noChecked = ($row['conf_scr_area'] == 0 ? "checked" : "");
						?>
						<input type="radio" id="field_area" name="field_area" value="yes" <?= $yesChecked; ?> />
						<label for="field_area"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_area_no" name="field_area" value="no" <?= $noChecked; ?> />
						<label for="field_area_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<?php
							$required = (!count($required_fields) || $required_fields['conf_scr_area'] ? " checked" : "");
							?>
							<input type="checkbox" class="" name="field_area_required" id="field_area_required" <?= $required; ?>>
							<small class="text-danger"><i class="fas fa-asterisk"></i></small>
						</div>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_TYPE_OF_PROBLEM'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<?php
						$yesChecked = ($row['conf_scr_prob'] == 1 ? "checked" : "");
						$noChecked = ($row['conf_scr_prob'] == 0 ? "checked" : "");
						?>
						<input type="radio" id="field_issue" name="field_issue" value="yes" <?= $yesChecked; ?> />
						<label for="field_issue"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_issue_no" name="field_issue" value="no" <?= $noChecked; ?> />
						<label for="field_issue_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<?php
							$required = (!count($required_fields) || $required_fields['conf_scr_prob'] ? " checked" : "");
							?>
							<input type="checkbox" class="" name="field_issue_required" id="field_issue_required" <?= $required; ?>>
							<small class=" text-danger"><i class="fas fa-asterisk"></i></small>
						</div>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_DESCRIPTION'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<?php
						$yesChecked = ($row['conf_scr_desc'] == 1 ? "checked" : "");
						$noChecked = ($row['conf_scr_desc'] == 0 ? "checked" : "");
						?>
						<input type="radio" id="field_description" name="field_description" value="yes" <?= $yesChecked; ?> />
						<label for="field_description"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_description_no" name="field_description" value="no" <?= $noChecked; ?> />
						<label for="field_description_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<?php
							$required = (!count($required_fields) || $required_fields['conf_scr_desc'] ? " checked" : "");
							?>
							<input type="checkbox" class="" name="field_description_required" id="field_description_required" <?= $required; ?>>
							<small class=" text-danger"><i class="fas fa-asterisk"></i></small>
						</div>
					</div>


					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_UNIT'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<?php
						$yesChecked = ($row['conf_scr_unit'] == 1 ? "checked" : "");
						$noChecked = ($row['conf_scr_unit'] == 0 ? "checked" : "");
						?>
						<input type="radio" id="field_unit" name="field_unit" value="yes" <?= $yesChecked; ?> />
						<label for="field_unit"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_unit_no" name="field_unit" value="no" <?= $noChecked; ?> />
						<label for="field_unit_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<?php
							$required = (count($required_fields) && $required_fields['conf_scr_unit'] ? " checked" : "");
							?>
							<input type="checkbox" class="" name="field_unit_required" id="field_unit_required" <?= $required; ?>>
							<small class=" text-danger"><i class="fas fa-asterisk"></i></small>
						</div>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_ASSET_TAG'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<?php
						$yesChecked = ($row['conf_scr_tag'] == 1 ? "checked" : "");
						$noChecked = ($row['conf_scr_tag'] == 0 ? "checked" : "");
						?>
						<input type="radio" id="field_tag_number" name="field_tag_number" value="yes" <?= $yesChecked; ?> />
						<label for="field_tag_number"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_tag_number_no" name="field_tag_number" value="no" <?= $noChecked; ?> />
						<label for="field_tag_number_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<?php
							$required = (count($required_fields) && $required_fields['conf_scr_tag'] ? " checked" : "");
							?>
							<input type="checkbox" class="" name="field_tag_number_required" id="field_tag_number_required" <?= $required; ?>>
							<small class=" text-danger"><i class="fas fa-asterisk"></i></small>
						</div>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('OPT_LNK_TAG'); ?></label>
					<div class="form-group col-md-3 switch-field ">
						<?php
						$yesChecked = ($row['conf_scr_chktag'] == 1 ? "checked" : "");
						$noChecked = ($row['conf_scr_chktag'] == 0 ? "checked" : "");
						?>
						<input type="radio" id="field_tag_check" name="field_tag_check" value="yes" <?= $yesChecked; ?> />
						<label for="field_tag_check"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_tag_check_no" name="field_tag_check" value="no" <?= $noChecked; ?> />
						<label for="field_tag_check_no"><?= TRANS('NOT'); ?></label>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('OPT_LNK_HIST'); ?></label>
					<div class="form-group col-md-3 switch-field ">
						<?php
						$yesChecked = ($row['conf_scr_chkhist'] == 1 ? "checked" : "");
						$noChecked = ($row['conf_scr_chkhist'] == 0 ? "checked" : "");
						?>
						<input type="radio" id="field_tag_tickets" name="field_tag_tickets" value="yes" <?= $yesChecked; ?> />
						<label for="field_tag_tickets"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_tag_tickets_no" name="field_tag_tickets" value="no" <?= $noChecked; ?> />
						<label for="field_tag_tickets_no"><?= TRANS('NOT'); ?></label>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_CONTACT'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<?php
						$yesChecked = ($row['conf_scr_contact'] == 1 ? "checked" : "");
						$noChecked = ($row['conf_scr_contact'] == 0 ? "checked" : "");
						?>
						<input type="radio" id="field_contact" name="field_contact" value="yes" <?= $yesChecked; ?> />
						<label for="field_contact"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_contact_no" name="field_contact" value="no" <?= $noChecked; ?> />
						<label for="field_contact_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<?php
							$required = (!count($required_fields) || $required_fields['conf_scr_contact'] ? " checked" : "");
							?>
							<input type="checkbox" class="" name="field_contact_required" id="field_contact_required" <?= $required; ?>>
							<small class=" text-danger"><i class="fas fa-asterisk"></i></small>
						</div>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_CONTACT_EMAIL'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<?php
						$yesChecked = ($row['conf_scr_contact_email'] == 1 ? "checked" : "");
						$noChecked = ($row['conf_scr_contact_email'] == 0 ? "checked" : "");
						?>
						<input type="radio" id="field_contact_email" name="field_contact_email" value="yes" <?= $yesChecked; ?> />
						<label for="field_contact_email"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_contact_email_no" name="field_contact_email" value="no" <?= $noChecked; ?> />
						<label for="field_contact_email_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<?php
							$required = (!count($required_fields) || $required_fields['conf_scr_contact_email'] ? " checked" : "");
							?>
							<input type="checkbox" class="" name="field_contact_email_required" id="field_contact_email_required" <?= $required; ?>>
							<small class=" text-danger"><i class="fas fa-asterisk"></i></small>
						</div>
					</div>




					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_PHONE'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<?php
						$yesChecked = ($row['conf_scr_fone'] == 1 ? "checked" : "");
						$noChecked = ($row['conf_scr_fone'] == 0 ? "checked" : "");
						?>
						<input type="radio" id="field_phone" name="field_phone" value="yes" <?= $yesChecked; ?> />
						<label for="field_phone"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_phone_no" name="field_phone" value="no" <?= $noChecked; ?> />
						<label for="field_phone_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<?php
							$required = (!count($required_fields) || $required_fields['conf_scr_fone'] ? " checked" : "");
							?>
							<input type="checkbox" class="" name="field_phone_required" id="field_phone_required" <?= $required; ?>>
							<small class=" text-danger"><i class="fas fa-asterisk"></i></small>
						</div>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_DEPARTMENT'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<?php
						$yesChecked = ($row['conf_scr_local'] == 1 ? "checked" : "");
						$noChecked = ($row['conf_scr_local'] == 0 ? "checked" : "");
						?>
						<input type="radio" id="field_department" name="field_department" value="yes" <?= $yesChecked; ?> />
						<label for="field_department"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_department_no" name="field_department" value="no" <?= $noChecked; ?> />
						<label for="field_department_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<?php
							$required = (!count($required_fields) || $required_fields['conf_scr_local'] ? " checked" : "");
							?>
							<input type="checkbox" class="" name="field_department_required" id="field_department_required" <?= $required; ?>>
							<small class=" text-danger"><i class="fas fa-asterisk"></i></small>
						</div>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('BUTTON_LOAD_DEPARTMENT'); ?></label>
					<div class="form-group col-md-3 switch-field ">
						<?php
						$yesChecked = ($row['conf_scr_btloadlocal'] == 1 ? "checked" : "");
						$noChecked = ($row['conf_scr_btloadlocal'] == 0 ? "checked" : "");
						?>
						<input type="radio" id="field_load_department" name="field_load_department" value="yes" <?= $yesChecked; ?> />
						<label for="field_load_department"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_load_department_no" name="field_load_department" value="no" <?= $noChecked; ?> />
						<label for="field_load_department_no"><?= TRANS('NOT'); ?></label>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('OPT_SCH_LOCAL'); ?></label>
					<div class="form-group col-md-3 switch-field ">
						<?php
						$yesChecked = ($row['conf_scr_searchbylocal'] == 1 ? "checked" : "");
						$noChecked = ($row['conf_scr_searchbylocal'] == 0 ? "checked" : "");
						?>
						<input type="radio" id="field_search_dep_tags" name="field_search_dep_tags" value="yes" <?= $yesChecked; ?> />
						<label for="field_search_dep_tags"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_search_dep_tags_no" name="field_search_dep_tags" value="no" <?= $noChecked; ?> />
						<label for="field_search_dep_tags_no"><?= TRANS('NOT'); ?></label>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_OPERATOR'); ?></label>
					<div class="form-group col-md-3 switch-field ">
						<?php
						$yesChecked = ($row['conf_scr_operator'] == 1 ? "checked" : "");
						$noChecked = ($row['conf_scr_operator'] == 0 ? "checked" : "");
						?>
						<input type="radio" id="field_operator" name="field_operator" value="yes" <?= $yesChecked; ?> />
						<label for="field_operator"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_operator_no" name="field_operator" value="no" <?= $noChecked; ?> />
						<label for="field_operator_no"><?= TRANS('NOT'); ?></label>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_DATE'); ?></label>
					<div class="form-group col-md-3 switch-field ">
						<?php
						$yesChecked = ($row['conf_scr_date'] == 1 ? "checked" : "");
						$noChecked = ($row['conf_scr_date'] == 0 ? "checked" : "");
						?>
						<input type="radio" id="field_date" name="field_date" value="yes" <?= $yesChecked; ?> />
						<label for="field_date"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_date_no" name="field_date" value="no" <?= $noChecked; ?> />
						<label for="field_date_no"><?= TRANS('NOT'); ?></label>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_SCHEDULE'); ?></label>
					<div class="form-group col-md-3 switch-field ">
						<?php
						$yesChecked = ($row['conf_scr_schedule'] == 1 ? "checked" : "");
						$noChecked = ($row['conf_scr_schedule'] == 0 ? "checked" : "");
						?>
						<input type="radio" id="field_schedule" name="field_schedule" value="yes" <?= $yesChecked; ?> />
						<label for="field_schedule"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_schedule_no" name="field_schedule" value="no" <?= $noChecked; ?> />
						<label for="field_schedule_no"><?= TRANS('NOT'); ?></label>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_FORWARD'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<?php
						$yesChecked = ($row['conf_scr_foward'] == 1 ? "checked" : "");
						$noChecked = ($row['conf_scr_foward'] == 0 ? "checked" : "");
						?>
						<input type="radio" id="field_forward" name="field_forward" value="yes" <?= $yesChecked; ?> />
						<label for="field_forward"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_forward_no" name="field_forward" value="no" <?= $noChecked; ?> />
						<label for="field_forward_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<?php
							$required = (count($required_fields) && $required_fields['conf_scr_foward'] ? " checked" : "");
							?>
							<input type="checkbox" class="" name="field_forward_required" id="field_forward_required" <?= $required; ?>>
							<small class=" text-danger"><i class="fas fa-asterisk"></i></small>
						</div>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_STATUS'); ?></label>
					<div class="form-group col-md-3 switch-field ">
						<?php
						$yesChecked = ($row['conf_scr_status'] == 1 ? "checked" : "");
						$noChecked = ($row['conf_scr_status'] == 0 ? "checked" : "");
						?>
						<input type="radio" id="field_status" name="field_status" value="yes" <?= $yesChecked; ?> />
						<label for="field_status"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_status_no" name="field_status" value="no" <?= $noChecked; ?> />
						<label for="field_status_no"><?= TRANS('NOT'); ?></label>
					</div>


					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_ATTACH_FILE'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<?php
						$yesChecked = ($row['conf_scr_upload'] == 1 ? "checked" : "");
						$noChecked = ($row['conf_scr_upload'] == 0 ? "checked" : "");
						?>
						<input type="radio" id="field_attach_file" name="field_attach_file" value="yes" <?= $yesChecked; ?> />
						<label for="field_attach_file"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_attach_file_no" name="field_attach_file" value="no" <?= $noChecked; ?> />
						<label for="field_attach_file_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<?php

							$required = (count($required_fields) && key_exists('conf_scr_upload', $required_fields) && $required_fields['conf_scr_upload'] ? " checked" : "");
							?>
							<input type="checkbox" class="" name="field_attach_file_required" id="field_attach_file_required" <?= $required; ?>>
							<small class=" text-danger"><i class="fas fa-asterisk"></i></small>
						</div>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_PRIORITY'); ?></label>
					<div class="form-group col-md-3 switch-field ">
						<?php
						$yesChecked = ($row['conf_scr_prior'] == 1 ? "checked" : "");
						$noChecked = ($row['conf_scr_prior'] == 0 ? "checked" : "");
						?>
						<input type="radio" id="field_priority" name="field_priority" value="yes" <?= $yesChecked; ?> />
						<label for="field_priority"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_priority_no" name="field_priority" value="no" <?= $noChecked; ?> />
						<label for="field_priority_no"><?= TRANS('NOT'); ?></label>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_SEND_EMAIL'); ?></label>
					<div class="form-group col-md-3 switch-field ">
						<?php
						$yesChecked = ($row['conf_scr_mail'] == 1 ? "checked" : "");
						$noChecked = ($row['conf_scr_mail'] == 0 ? "checked" : "");
						?>
						<input type="radio" id="field_send_mail" name="field_send_mail" value="yes" checked />
						<label for="field_send_mail"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_send_mail_no" name="field_send_mail" value="no" <?= $noChecked; ?> />
						<label for="field_send_mail_no"><?= TRANS('NOT'); ?></label>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_CHANNEL'); ?></label>
					<div class="form-group col-md-3 switch-field ">
						<?php
						$yesChecked = ($row['conf_scr_channel'] == 1 ? "checked" : "");
						$noChecked = ($row['conf_scr_channel'] == 0 ? "checked" : "");
						?>
						<input type="radio" id="field_channel" name="field_channel" value="yes" checked />
						<label for="field_channel"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_channel_no" name="field_channel" value="no" <?= $noChecked; ?> />
						<label for="field_channel_no"><?= TRANS('NOT'); ?></label>
					</div>




					<?php
					/* Campos personalizados */
					$custom_fields = getCustomFields($conn, null, 'ocorrencias', null, null);

					if (count($custom_fields)) {
						$fields_ids = explode(',', (string)$row['conf_scr_custom_ids']);
						$fields_only_edition_ids = explode(',', (string)$row['cfields_only_edition']);
						$fields_user_hidden_ids = explode(',', (string)$row['cfields_user_hidden']);
					?>
						<div class="w-100">
							<p class="h6 text-center font-weight-bold mt-4"><?= TRANS('EXTRA_FIELDS'); ?></p>
						</div>
						<?php
						foreach ($custom_fields as $field) {
							$disabled = (!$field['field_active'] ? " disabled" : "");
						?>
							<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= $field['field_label']; ?></label>
							<div class="form-group col-md-3 switch-field container-switch-2">
								<?php
								/* Vai aparecer como "não" mesmo que esteja no perfil, caso o campo não esteja ativo */
								$yesChecked = (in_array($field['id'], $fields_ids) && $field['field_active'] ? "checked" : "");
								$noChecked = (!in_array($field['id'], $fields_ids) || !$field['field_active'] ? "checked" : "");

								$onlyEditionChecked = (in_array($field['id'], $fields_only_edition_ids) && $field['field_active'] ? "checked" : "");
								$userHiddenChecked = (in_array($field['id'], $fields_user_hidden_ids) && $field['field_active'] ? "checked" : "");
								?>
								<input type="radio" id="<?= $field["field_name"]; ?>" name="<?= $field["field_name"]; ?>" value="yes" <?= $yesChecked; ?> <?= $disabled; ?> />
								<label for="<?= $field["field_name"]; ?>"><?= TRANS('YES'); ?></label>
								<input type="radio" id="<?= $field["field_name"]; ?>_no" name="<?= $field["field_name"]; ?>" value="no" <?= $noChecked; ?> <?= $disabled; ?> />
								<label for="<?= $field["field_name"]; ?>_no"><?= TRANS('NOT'); ?></label>
								<div class="switch-next-checkbox-1" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('DISPLAY_ONLY_WHEN_EDITING'); ?>">
									<input type="checkbox" id="<?= 'only_edition_' . $field["field_name"]; ?>" name="<?= 'only_edition_' . $field["field_name"]; ?>" <?= $onlyEditionChecked; ?>>
									<span class="text-muted" ><i class="fas fa-eye-slash"></i></span>
								</div>
								<div class="switch-next-checkbox-2" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HIDDEN_TO_ENDUSER'); ?>">
									<input type="checkbox" id="<?= 'hidden_' . $field["field_name"]; ?>" name="<?= 'hidden_' . $field["field_name"]; ?>" <?= $userHiddenChecked; ?>>
									<span class="text-muted"><i class="fas fa-user-slash"></i></span>
								</div>
							</div>
					<?php
						}
					}
					/* Fim do trecho referente aos campos personalizados */
					?>





					<div class="w-100"></div>
					<label for="opening_message" class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('OPT_FIELD_MSG'); ?></label>
					<div class="form-group col-md-9">
						<textarea class="form-control" id="opening_message" name="opening_message"><?= $row['conf_scr_msg']; ?></textarea>
					</div>



					<input type="hidden" name="cod" value="<?= (int)$_GET['cod']; ?>">
					<input type="hidden" name="action" id="action" value="edit">

					<div class="row w-100"></div>
					<div class="form-group col-md-8 d-none d-md-block">
					</div>
					<div class="form-group col-12 col-md-2 ">
						<button type="submit" id="idSubmit" name="submit" value="edit" class="btn btn-primary btn-block"><?= TRANS('BT_OK'); ?></button>
					</div>
					<div class="form-group col-12 col-md-2">
						<button type="reset" class="btn btn-secondary btn-block" onClick="parent.history.back();"><?= TRANS('BT_CANCEL'); ?></button>
					</div>

				</div>
			</form>
		<?php
		}
		?>
	</div>

	<script src="../../includes/javascript/funcoes-3.0.js"></script>
	<script src="../../includes/components/jquery/jquery.js"></script>
	<script src="../../includes/components/bootstrap/js/bootstrap.bundle.js"></script>
	<script type="text/javascript" charset="utf8" src="../../includes/components/datatables/datatables.js"></script>
	<script type="text/javascript">
		$(function() {

			$('#table_lists').DataTable({
				paging: true,
				deferRender: true,
				columnDefs: [{
					searchable: false,
					orderable: false,
					targets: ['editar', 'remover']
				}],
				"language": {
					"url": "../../includes/components/datatables/datatables.pt-br.json"
				}
			});


			$('.custom-fields-link').popover({
				content: '<?= TRANS('CUSTOM_FIELD'); ?>',
				trigger: "hover"
			});

			$('.custom-fields-only-edition').popover({
				content: '<?= TRANS('CUSTOM_FIELD_ONLY_EDITION'); ?>',
				trigger: "hover"
			});

			$(function() {
				$('[data-toggle="popover"]').popover({
					html: true
				});
			});

			$('.popover-dismiss').popover({
				trigger: 'focus'
			});


			$.each($('.switch-next-checkbox'), function(index, el) {
				var group_parent = $(this).parent(); 
				var first_checkbox_id = group_parent.find('input:first').attr('id');

				var enabled = group_parent.find('input:first').is(':checked');
				var last_checkbox_id = $(this).find('input:last').attr('id');
				
				if (!enabled) {
					$('#' + last_checkbox_id).prop('checked', false).prop('disabled', true);
				} else {
					$('#' + last_checkbox_id).prop('disabled', false);
				}
			});

			$('.container-switch').on('click', 'input', function() {

				var group_parent = $(this).parents(); //object
				var last_checkbox_id = group_parent.find('input:last').attr('id');

				if ($(this).val() == "no") {
					$('#' + last_checkbox_id).prop('checked', false).prop('disabled', true);
				} else {
					$('#' + last_checkbox_id).prop('disabled', false);
				}
			});


			/* Controle para campos customizados */
			controlFirstCheck();
			controlSecondCheck();

			$('.container-switch-2').on('click', 'input', function() {
				controlFirstCheck();
				controlSecondCheck();
			});



			if ($('#is_default').is(':checked')) {
				$('#is_default').prop('disabled', true);
				$('#is_default_no').prop('disabled', true);
			}

			if (!$('#field_tag_number').is(':checked') || !$('#field_unit').is(':checked')) {
				$('#field_tag_check').prop('disabled', true).prop('checked', false);
				$('#field_tag_check_no').prop('disabled', true).prop('checked', true);
				$('#field_tag_tickets').prop('disabled', true).prop('checked', false);
				$('#field_tag_tickets_no').prop('disabled', true).prop('checked', true);
			}

			if (!$('#field_department').is(':checked')) {
				$('#field_load_department').prop('disabled', true).prop('checked', false);
				$('#field_load_department_no').prop('disabled', true).prop('checked', true);
				$('#field_search_dep_tags').prop('disabled', true).prop('checked', false);
				$('#field_search_dep_tags_no').prop('disabled', true).prop('checked', true);
			}


			$('[name="field_tag_number"]').on('change', function() {
				if ($(this).val() == "no") {
					$('#field_tag_check').prop('checked', false).prop('disabled', true);
					$('#field_tag_check_no').prop('checked', true).prop('disabled', true);
					$('#field_tag_tickets').prop('checked', false).prop('disabled', true);
					$('#field_tag_tickets_no').prop('checked', true).prop('disabled', true);
				} else if ($('#field_unit').is(':checked')) {
					$('#field_tag_check').prop('disabled', false);
					$('#field_tag_check_no').prop('disabled', false);
					$('#field_tag_tickets').prop('disabled', false);
					$('#field_tag_tickets_no').prop('disabled', false);
				}
			});

			$('[name="field_unit"]').on('change', function() {
				if ($(this).val() == "no") {
					$('#field_tag_check').prop('checked', false).prop('disabled', true);
					$('#field_tag_check_no').prop('checked', true).prop('disabled', true);
					$('#field_tag_tickets').prop('checked', false).prop('disabled', true);
					$('#field_tag_tickets_no').prop('checked', true).prop('disabled', true);
				} else if ($('#field_tag_number').is(':checked')) {
					$('#field_tag_check').prop('disabled', false);
					$('#field_tag_check_no').prop('disabled', false);
					$('#field_tag_tickets').prop('disabled', false);
					$('#field_tag_tickets_no').prop('disabled', false);
				}
			});

			$('[name="field_department"]').on('change', function() {
				if ($(this).val() == "no") {
					$('#field_load_department').prop('checked', false).prop('disabled', true);
					$('#field_load_department_no').prop('checked', true).prop('disabled', true);
					$('#field_search_dep_tags').prop('checked', false).prop('disabled', true);
					$('#field_search_dep_tags_no').prop('checked', true).prop('disabled', true);
				} else {
					$('#field_load_department').prop('disabled', false);
					$('#field_load_department_no').prop('disabled', false);
					$('#field_search_dep_tags').prop('disabled', false);
					$('#field_search_dep_tags_no').prop('disabled', false);
				}
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

				$.ajax({
					url: './screen_profile_process.php',
					method: 'POST',
					data: $('#form').serialize(),
					dataType: 'json',
				}).done(function(response) {

					if (!response.success) {
						$('#divResult').html(response.message);
						$('input, select, textarea').removeClass('is-invalid');
						if (response.field_id != "") {
							$('#' + response.field_id).focus().addClass('is-invalid');
						}
						$("#idSubmit").prop("disabled", false);
					} else {
						$('#divResult').html('');
						$('input, select, textarea').removeClass('is-invalid');
						$("#idSubmit").prop("disabled", false);
						var url = '<?= $_SERVER['PHP_SELF'] ?>';
						$(location).prop('href', url);
						return false;
					}
				});
				return false;
			});


			
			function controlFirstCheck () {
				$.each($('.switch-next-checkbox-1'), function(index, el) {
					var group_parent = $(this).parent(); 
					var first_checkbox_id = group_parent.find('input:first').attr('id');

					var enabled = group_parent.find('input:first').is(':checked');
					var last_checkbox_id = $(this).find('input:last').attr('id');

					if (!enabled) {
						$('#' + last_checkbox_id).prop('checked', false).prop('disabled', true);
					} else {
						$('#' + last_checkbox_id).prop('disabled', false);
					}
				});
			}
				
			function controlSecondCheck () {
				$.each($('.switch-next-checkbox-2'), function(index, el) {
					var group_parent = $(this).parent(); 
					var first_checkbox_id = group_parent.find('input:first').attr('id');

					/* Observa o radio button */
					var enabled = group_parent.find('input:first').is(':checked');
					
					/* Primeira coluna de checkboxes */
					var firstColumnCheckboxID = group_parent.find('.switch-next-checkbox-1 > input:first').attr('id');
					var firstColumnChecked = $('#' + firstColumnCheckboxID).is(':checked');
					
					/* Segunda coluna de checkboxes */
					var last_checkbox_id = $(this).find('input:last').attr('id');
					
					if (!enabled || !firstColumnChecked) {
						$('#' + last_checkbox_id).prop('checked', false).prop('disabled', true);
					} else {
						$('#' + last_checkbox_id).prop('disabled', false);
					}
				});
			}



			$('#idBtIncluir').on("click", function() {
				$('#idLoad').css('display', 'block');
				var url = '<?= $_SERVER['PHP_SELF'] ?>?action=new';
				$(location).prop('href', url);
			});

			$('#bt-cancel').on('click', function() {
				var url = '<?= $_SERVER['PHP_SELF'] ?>';
				$(location).prop('href', url);
			});
		});


		function confirmDeleteModal(id) {
			$('#deleteModal').modal();
			$('#deleteButton').html('<a class="btn btn-danger" onclick="deleteData(' + id + ')"><?= TRANS('REMOVE'); ?></a>');
		}

		function deleteData(id) {

			var loading = $(".loading");
			$(document).ajaxStart(function() {
				loading.show();
			});
			$(document).ajaxStop(function() {
				loading.hide();
			});

			$.ajax({
				url: './screen_profile_process.php',
				method: 'POST',
				data: {
					cod: id,
					action: 'delete'
				},
				dataType: 'json',
			}).done(function(response) {
				var url = '<?= $_SERVER['PHP_SELF'] ?>';
				$(location).prop('href', url);
				return false;
			});
			return false;
			// $('#deleteModal').modal('hide'); // now close modal
		}
	</script>
</body>

</html>