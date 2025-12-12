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

$suggestions = '[{}]';

$config = getConfig($conn);
$version4 = $config['conf_updated_issues'];
$issues_app = ($version4 ? 'issues_by_area_4.0.php' : 'issues_by_area.php');

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
	<link rel="stylesheet" type="text/css" href="../../includes/components/jquery/jquery.amsify.suggestags-master/css/amsify.suggestags.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap-select/dist/css/bootstrap-select.min.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/css/my_bootstrap_select.css" />

	<style>
		li.area_admins {
			line-height: 1.5em;
		}

		td.admins {
			min-width: 15%;
		}
		td.col_check {
			max-width: 5%;
		}

		.area-ban:before {
            font-family: "Font Awesome\ 5 Free";
            content: "\f05e";
            font-weight: 900;
            font-size: 16px;
        }
		.area-info:before {
            font-family: "Font Awesome\ 5 Free";
            content: "\f05a";
            font-weight: 900;
            font-size: 16px;
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

		/* input[type='checkbox'][readonly]{
			pointer-events: none;
		} */
	</style>

	<title>OcoMon&nbsp;<?= VERSAO; ?></title>
</head>

<body>
	
	<div class="container">
		<div id="idLoad" class="loading" style="display:none"></div>
	</div>

	<div id="divResult"></div>


	<div class="container-fluid">
		<h4 class="my-4"><i class="fas fa-headset text-secondary"></i>&nbsp;<?= TRANS('SERVICE_AREAS'); ?></h4>
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

		$query = "SELECT s.*, c.*, w.* FROM sistemas AS s 
					LEFT JOIN configusercall as c on s.sis_screen = c.conf_cod 
					LEFT JOIN worktime_profiles as w on w.id = s.sis_wt_profile ";

		$COD = (isset($_GET['cod']) && !empty($_GET['cod']) ? noHtml($_GET['cod']) : '' );
		$adminsText = "";
		if (!empty($COD)){
			$query .= " WHERE sis_id = '{$COD}' ";

			/* Area admins */
			$area_admins = [];
			$area_admins = getAreaAdmins($conn, $COD);
			foreach ($area_admins as $admin) {
				if (strlen((string)$adminsText)) $adminsText .=",";
				$adminsText .= $admin['user_id'];
			}

			$arrayUsers = [];
			// $sqlUsers = "SELECT user_id, nome FROM usuarios WHERE AREA = '{$COD}'  ORDER BY nome";
			// $resUsers = $conn->query($sqlUsers);
			// if ($resUsers->rowCount()) {
			// 	foreach ($resUsers->fetchall() as $user) {
			// 		$arrayUsers[] = ["tag" => $user['nome'], "value" => $user['user_id']];
			// 	}
			// }

			$areaInfo = getAreaInfo($conn, $COD);
			$onlyOpenArea = (!empty($areaInfo) && $areaInfo['atende'] == 0 ? true: false);
			
			/**
			 * Se a área de de nível somente abertura, as sugestões podem ser qualquer usuário de nível somente abertura
			 */
			if ($onlyOpenArea) {
				$possibleUsers = getOnlyOpenUsers($conn);
			} else {
				$possibleUsers = getUsersByArea($conn, $COD, false, null, null);
			}

			if (count($possibleUsers)) {
				foreach ($possibleUsers as $user) {
					$arrayUsers[] = ["tag" => $user['nome'], "value" => $user['user_id']];
				}
			}

			/* Sugestões para a seleção de usuários administradores */
			$suggestions = json_encode($arrayUsers);	
		}


		$query .= " ORDER  BY sistema";
		try {
			$resultado = $conn->query($query);
			
		} catch (Exception $e) {
			echo message('danger', 'Ooops!', $e->getMessage(), '');
			return false;
		}

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
							<td class="line area"><?= TRANS('AREA'); ?></td>
							<td class="line col_check" width="8%"><?= TRANS('PROCESS_TICKETS'); ?></td>
							<td class="line dynamic_mode"><?= TRANS('OPENING_MODE_DYNAMIC'); ?></td>
							<td class="line subject"><?= TRANS('ACCESS_MODULES'); ?></td>
							<!-- <td class="line email"><?= TRANS('COL_EMAIL'); ?></td> -->
							<td class="line admins" width="15%"><?= TRANS('MANAGERS'); ?></td>
							<td class="line screen_profile"><?= TRANS('COL_SCREEN_PROFILE'); ?></td>
							<td class="line wc_profile"><?= TRANS('WORKTIME_PROFILE'); ?></td>
							<td class="line col_check" width="8%"><?= TRANS('ACTIVE_O'); ?></td>
							<td class="line editar"><?= TRANS('PROBLEM_TYPES'); ?></td>
							<td class="line editar"><?= TRANS('BT_EDIT'); ?></td>
							<td class="line remover"><?= TRANS('BT_REMOVE'); ?></td>
						</tr>
					</thead>
					<tbody>
						<?php
						
						foreach ($resultado->fetchall() as $row) {
							$process_tickets = ($row['sis_atende'] ? '<span class="text-success"><i class="fas fa-check"></i></span>' : '');

							$dynamic_mode = ($row['sis_opening_mode'] == 2 ? '<span class="text-success"><i class="fas fa-check"></i></span>' : '');

							$disableViewIssues = (!$row['sis_atende'] ? ' disabled': '');


							$rowClass = (!empty($disableViewIssues) ? 'text-secondary area-ban' : 'text-info area-info');
							$popoverIssues = (empty($disableViewIssues) ? 'data-toggle="popover" data-placement="top" data-trigger="hover" data-content="' . TRANS("SEE_THE_TYPES_OF_ISSUES_FOR_THIS_AREA") . '"': '');

							$lstatus = ($row['sis_status'] == 1 ? '<span class="text-success"><i class="fas fa-check"></i></span>' : '');

							$textScreen = ($row['conf_name'] == "" ? TRANS('ALL_FIELDS_SCREEN') : $row['conf_name']);
							$modulesText = "";
							$modules = [];
							$modules[] = (getModuleAccess($conn, 1, $row['sis_id']) ? TRANS('MOD_TICKETS') : "");
							$modules[] = (getModuleAccess($conn, 2, $row['sis_id']) ? TRANS('MOD_INVENTORY') : "");
							foreach ($modules as $mod) {
								if (!empty($mod))
									$modulesText .= '<li class="area_admins">' . $mod ?? '' . '</li>';
							}
							/* Administradores da área */
							$admins = [];
							$admins = getAreaAdmins($conn, $row['sis_id']);
							$adminsNames = "";
							if (count($admins)) {
								?>
								<ol>
								<?php
								foreach ($admins as $admin) {
									$adminsNames .= '<li class="area_admins">' . $admin['nome'] ?? '' . '</li>';
								}
								?>
								</ol>
								<?php
							}
							?>
							<tr>
								<td class="line"><?= $row['sistema']; ?></td>
								<td class="line"><?= $process_tickets; ?></td>
								<td class="line"><?= $dynamic_mode; ?></td>
								<td class="line"><?= $modulesText; ?></td>
								<!-- <td class="line"><?= $row['sis_email']; ?></td> -->
								<td class="line admins"><?= $adminsNames; ?></td>
								<td class="line"><?= NVL($textScreen); ?></td>
								<td class="line"><?= $row['name']; ?></td>
								<td class="line"><?= $lstatus; ?></td>
								<td class="line"><button type="button" class="btn btn-sm <?= $rowClass; ?>" <?= $popoverIssues; ?> <?= $disableViewIssues; ?> onclick="redirect('<?= $issues_app; ?>?area=<?= $row['sis_id']; ?>')"></button></td>
								<td class="line"><button type="button" class="btn btn-secondary btn-sm" onclick="redirect('<?= $_SERVER['PHP_SELF']; ?>?action=edit&cod=<?= $row['sis_id']; ?>')"><?= TRANS('BT_EDIT'); ?></button></td>
								<td class="line"><button type="button" class="btn btn-danger btn-sm" onclick="confirmDeleteModal('<?= $row['sis_id']; ?>')"><?= TRANS('REMOVE'); ?></button></td>
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


					<label for="area" class="col-sm-2 col-md-2 col-form-label text-md-right"><?= TRANS('AREA'); ?></label>
					<div class="form-group col-md-4">
						<input type="text" class="form-control" id="area" name="area" required placeholder="<?= TRANS('PLACEHOLDER_AREA_NAME'); ?>" />
						<div class="invalid-feedback">
							<?= TRANS('MANDATORY_FIELD'); ?>
						</div>
					</div>

					<label class="col-md-2 col-form-label text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('UNCHECK_IF_ENDUSER_AREA'); ?>"><?= firstLetterUp(TRANS('PROCESS_TICKETS')); ?></label>
					<div class="form-group col-md-4 ">
						<div class="switch-field">
							<?php
							$yesChecked = "checked";
							$noChecked = "";
							?>
							<input type="radio" id="process_tickets" name="process_tickets" value="yes" <?= $yesChecked; ?> />
							<label for="process_tickets"><?= TRANS('YES'); ?></label>
							<input type="radio" id="process_tickets_no" name="process_tickets" value="no" <?= $noChecked; ?> />
							<label for="process_tickets_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>


					<label for="email" class="col-sm-2 col-md-2 col-form-label text-md-right"><?= TRANS('COL_EMAIL'); ?></label>
					<div class="form-group col-md-4">
						<input type="email" class="form-control" id="email" name="email" required placeholder="<?= TRANS('PLACEHOLDER_AREA_EMAIL'); ?>" />
						<div class="invalid-feedback">
							<?= TRANS('MANDATORY_FIELD'); ?>
						</div>
					</div>

					<label for="screen_profile" class="col-md-2 col-form-label text-md-right"><?= TRANS('SCREEN_NAME'); ?></label>
					<div class="form-group col-md-4">
						<select class="form-control " id="screen_profile" name="screen_profile" >
							<option value=""><?= TRANS('SEL_SCREEN'); ?></option>
							<?php
							$sql = "select * from configusercall order by conf_name";
							$commit = $conn->query($sql);
							foreach ($commit->fetchAll() as $row) {
								print "<option value=" . $row['conf_cod'] . ">" . $row["conf_name"] . "</option>";
							}
							?>
						</select>
					</div>


					

					<label for="wt_profile" class="col-md-2 col-form-label text-md-right"><?= TRANS('WORKTIME_PROFILE'); ?></label>
					<div class="form-group col-md-4">
						<select class="form-control " id="wt_profile" name="wt_profile" required>
							<?php
							$sql = "SELECT id, name, is_default FROM worktime_profiles ORDER BY name ";
							$result = $conn->query($sql);
							foreach ($result->fetchAll() as $rowWT) {
								print "<option value = '" . $rowWT['id'] . "' ";
								if ($rowWT['is_default'] == 1) print " selected";
								print ">" . $rowWT['name'] . "</option>";
							}
							?>
						</select>
					</div>

					<label for="months" class="col-sm-2 col-md-2 col-form-label text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_AREA_MONTHS_DONE'); ?>"><?= TRANS('AREA_MONTHS_DONE'); ?></label>
					<div class="form-group col-md-4">
						<input type="number" min="1" class="form-control" id="months" name="months" value="12" required placeholder="<?= TRANS('FIELD_TIME_MONTH'); ?>" />
					</div>


					<label class="col-md-2 col-form-label text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_OPENING_MODE_DYNAMIC'); ?>"><?= firstLetterUp(TRANS('OPENING_MODE_DYNAMIC')); ?></label>
					<div class="form-group col-md-4 ">
						<div class="switch-field">
							<?php
							$yesChecked = "checked";
							$noChecked = "";
							?>
							<input type="radio" id="dynamic_mode" name="dynamic_mode" value="yes" <?= $yesChecked; ?> />
							<label for="dynamic_mode"><?= TRANS('YES'); ?></label>
							<input type="radio" id="dynamic_mode_no" name="dynamic_mode" value="no" <?= $noChecked; ?> />
							<label for="dynamic_mode_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

					<label class="col-md-2 col-form-label text-md-right"><?= firstLetterUp(TRANS('ACTIVE_O')); ?></label>
					<div class="form-group col-md-4 ">
						<div class="switch-field">
							<?php
							$yesChecked = "checked";
							$noChecked = "";
							?>
							<input type="radio" id="area_active" name="area_active" value="yes" <?= $yesChecked; ?> />
							<label for="area_active"><?= TRANS('YES'); ?></label>
							<input type="radio" id="area_active_no" name="area_active" value="no" <?= $noChecked; ?> />
							<label for="area_active_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

					<h6 class="w-100 mt-5 mb-4 ml-5 border-top p-4"><i class="fas fa-project-diagram text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('ACCESS_MODULES')); ?></h6>
					<label class="col-md-2 col-form-label text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('MOD_TICKETS'); ?>"><?= TRANS('MOD_TICKETS'); ?></label>
					<div class="form-group col-md-4 ">
						<div class="switch-field">
							<?php
							$yesChecked = "checked";
							$noChecked = "";
							?>
							<input type="radio" id="mod_tickets" name="mod_tickets" value="yes" <?= $yesChecked; ?> />
							<label for="mod_tickets"><?= TRANS('YES'); ?></label>
							<input type="radio" id="mod_tickets_no" name="mod_tickets" value="no" <?= $noChecked; ?> />
							<label for="mod_tickets_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

					<label class="col-md-2 col-form-label text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('MOD_INVENTORY'); ?>"><?= TRANS('MOD_INVENTORY'); ?></label>
					<div class="form-group col-md-4 ">
						<div class="switch-field">
							<?php
							$yesChecked = "checked";
							$noChecked = "";
							?>
							<input type="radio" id="mod_inventory" name="mod_inventory" value="yes" <?= $yesChecked; ?> />
							<label for="mod_inventory"><?= TRANS('YES'); ?></label>
							<input type="radio" id="mod_inventory_no" name="mod_inventory" value="no" <?= $noChecked; ?> />
							<label for="mod_inventory_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>


					<!-- Link para adicionar filtro de clientes e unidades -->
					<label class="col-sm-6 col-md-6 col-form-label col-form-label-sm text-md-right toggle_show "><?= TRANS('ASSETS_CLIENT_LIMIT_ACCESS'); ?></label>
					<div class="form-group col-md-6 toggle_show">
						<a href="javascript:void(0);" class="add_button_new_client" title="<?= TRANS('ADD'); ?>"><i class="fa fa-plus"></i></a>
					</div>


				</div>

					<!-- Receberá as linhas de clientes e unidades -->
				<div id="new_clients" class="form-group row my-4 new_clients"></div>


				<div class="form-group row my-4 " id="div_send_receive_areas"></div>
				<div class="form-group row my-4 ">


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
		?>
			<h6><?= TRANS('BT_EDIT'); ?></h6>
			<form method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form">
				<?= csrf_input(); ?>

				<div class="form-group row my-4">


					<label for="area" class="col-sm-2 col-md-2 col-form-label text-md-right"><?= TRANS('AREA'); ?></label>
					<div class="form-group col-md-4">
						<input type="text" class="form-control" id="area" name="area" value="<?= $row['sistema']; ?>" placeholder="<?= TRANS('PLACEHOLDER_AREA_NAME'); ?>" />
						<div class="invalid-feedback">
							<?= TRANS('MANDATORY_FIELD'); ?>
						</div>
					</div>


					<label class="col-md-2 col-form-label text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('UNCHECK_IF_ENDUSER_AREA'); ?>"><?= firstLetterUp(TRANS('PROCESS_TICKETS')); ?></label>
					<div class="form-group col-md-4 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($row['sis_atende'] == 1 ? "checked" : "");
							$noChecked = (!($row['sis_atende'] == 1) ? "checked" : "");
							?>
							<input type="radio" id="process_tickets" name="process_tickets" value="yes" <?= $yesChecked; ?> />
							<label for="process_tickets"><?= TRANS('YES'); ?></label>
							<input type="radio" id="process_tickets_no" name="process_tickets" value="no" <?= $noChecked; ?> />
							<label for="process_tickets_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>


					<label for="email" class="col-sm-2 col-md-2 col-form-label text-md-right"><?= TRANS('COL_EMAIL'); ?></label>
					<div class="form-group col-md-4">
						<input type="email" class="form-control" id="email" name="email" value="<?= $row['sis_email']; ?>" placeholder="<?= TRANS('COL_EMAIL'); ?>" />
						<div class="invalid-feedback">
							<?= TRANS('MANDATORY_FIELD'); ?>
						</div>
					</div>

					<label for="screen_profile" class="col-md-2 col-form-label text-md-right"><?= TRANS('SCREEN_NAME'); ?></label>
					<div class="form-group col-md-4">
						<select class="form-control " id="screen_profile" name="screen_profile" >
							<?php

							print "<option value=''>" . TRANS('SEL_SCREEN') . "</option>";
							$sql2 = "select * from configusercall order by conf_name";
							$commit2 = $conn->query($sql2);
							foreach ($commit2->fetchall() as $rowB) {
								print "<option value=" . $rowB["conf_cod"] . "";

								/* Para não passar valor nulo para o getScreenInfo */
								if (isset($row['sis_screen'])) {
									if ($rowB['conf_cod'] == getScreenInfo($conn, $row['sis_screen'])['conf_cod']) {
										print " selected";
									}
								}
								print ">" . $rowB["conf_name"] . "</option>";
							} 
							?>
						</select>

					</div>


					<label for="wt_profile" class="col-md-2 col-form-label text-md-right"><?= TRANS('WORKTIME_PROFILE'); ?></label>
					<div class="form-group col-md-4">
						<select class="form-control " id="wt_profile" name="wt_profile" required>
							<?php
							$sql = "SELECT id, name, is_default FROM worktime_profiles ORDER BY name ";
							$result = $conn->query($sql);
							foreach ($result->fetchall() as $rowWT) {
								?>
								<option value="<?= $rowWT['id']; ?>" <?= ($rowWT['id'] == $row['sis_wt_profile'] ? 'selected' : ''); ?>><?= $rowWT['name']; ?></option>
								<?php
							}
							?>
						</select>
					</div>


					<label for="months" class="col-sm-2 col-md-2 col-form-label text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_AREA_MONTHS_DONE'); ?>"><?= TRANS('AREA_MONTHS_DONE'); ?></label>
					<div class="form-group col-md-4">
						<input type="number" min="1" class="form-control" id="months" name="months" value="<?= $row['sis_months_done']; ?>" placeholder="<?= TRANS('FIELD_TIME_MONTH'); ?>" />
					</div>

					<label class="col-md-2 col-form-label text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_OPENING_MODE_DYNAMIC'); ?>"><?= firstLetterUp(TRANS('OPENING_MODE_DYNAMIC')); ?></label>
					<div class="form-group col-md-4 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($row['sis_opening_mode'] == 2 ? "checked" : "");
							$noChecked = (!($row['sis_opening_mode'] == 2) ? "checked" : "");
							?>
							<input type="radio" id="dynamic_mode" name="dynamic_mode" value="yes" <?= $yesChecked; ?> />
							<label for="dynamic_mode"><?= TRANS('YES'); ?></label>
							<input type="radio" id="dynamic_mode_no" name="dynamic_mode" value="no" <?= $noChecked; ?> />
							<label for="dynamic_mode_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

					<label class="col-md-2 col-form-label text-md-right"><?= firstLetterUp(TRANS('ACTIVE_O')); ?></label>
					<div class="form-group col-md-4 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($row['sis_status'] == 1 ? "checked" : "");
							$noChecked = (!($row['sis_status'] == 1) ? "checked" : "");
							?>
							<input type="radio" id="area_active" name="area_active" value="yes" <?= $yesChecked; ?> />
							<label for="area_active"><?= TRANS('YES'); ?></label>
							<input type="radio" id="area_active_no" name="area_active" value="no" <?= $noChecked; ?> />
							<label for="area_active_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>


					<div class="h6 w-100 my-4 border-top p-4"><i class="fa fa-project-diagram text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('ACCESS_MODULES')); ?>:</div>
					<label class="col-md-2 col-form-label text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('MOD_TICKETS'); ?>"><?= TRANS('MOD_TICKETS'); ?></label>
					<div class="form-group col-md-4 ">
						<div class="switch-field">
							<?php
							$yesChecked = (getModuleAccess($conn, 1, $COD) ? "checked" : "");
							$noChecked = (!(getModuleAccess($conn, 1, $COD)) ? "checked" : "");
							?>
							<input type="radio" id="mod_tickets" name="mod_tickets" value="yes" <?= $yesChecked; ?> />
							<label for="mod_tickets"><?= TRANS('YES'); ?></label>
							<input type="radio" id="mod_tickets_no" name="mod_tickets" value="no" <?= $noChecked; ?> />
							<label for="mod_tickets_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

					<label class="col-md-2 col-form-label text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('MOD_INVENTORY'); ?>"><?= TRANS('MOD_INVENTORY'); ?></label>
					<div class="form-group col-md-4 ">
						<div class="switch-field">
							<?php
							$yesChecked = (getModuleAccess($conn, 2, $COD) ? "checked" : "");
							$noChecked = (!(getModuleAccess($conn, 2, $COD)) ? "checked" : "");
							?>
							<input type="radio" id="mod_inventory" name="mod_inventory" value="yes" <?= $yesChecked; ?> />
							<label for="mod_inventory"><?= TRANS('YES'); ?></label>
							<input type="radio" id="mod_inventory_no" name="mod_inventory" value="no" <?= $noChecked; ?> />
							<label for="mod_inventory_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

					<!-- Link para adicionar filtro de clientes e unidades -->
					<label class="col-sm-6 col-md-6 col-form-label col-form-label-sm text-md-right toggle_show"><?= TRANS('ASSETS_CLIENT_LIMIT_ACCESS'); ?></label>
					<div class="form-group col-md-6 toggle_show">
						<a href="javascript:void(0);" class="add_button_new_client" title="<?= TRANS('ADD'); ?>"><i class="fa fa-plus"></i></a>
					</div>


					<?php
						$clients = getAreaAllowedClients($conn, $COD);
						if (count($clients)) {
							
							$i = 0;
							?>
								<label class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right toggle_show"></label>
							<?php
							foreach ($clients as $client) {
								$allowedUnits = array_column(getAreaAllowedUnits($conn, $COD, $client['inst_client']), 'unit_id');	
								if ($i != 0) {
									?>
										<label class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right toggle_show"></label>
									<?php
								}
								?>
									<div class="form-group col-md-4 toggle_show">
										<div class="field_wrapper_specs" id="field_wrapper_specs">
												
											<select class="form-control bs-select sel-control disableControl" name="asset_client[]" id="asset_client<?= $i; ?>">
												<option value="<?= $client['inst_client']; ?>" selected ><?= $client['nickname']; ?></option>
											
											</select>
											<small class="form-text text-muted"><?= TRANS('CLIENT'); ?></small>
										</div>
									</div>

									<div class="form-group col-md-5 toggle_show">
										<select class="form-control bs-select disableControl" name="asset_unit[]" id="asset_unit<?= $i; ?>_asset_unit<?= $i; ?>" multiple="multiple">

											<?php
												$units = getUnits($conn, null, null, $client['inst_client']);
												foreach ($units as $unit) {
													?>
													<option value="<?= $unit['inst_cod']; ?>"
													<?= (in_array($unit['inst_cod'], $allowedUnits) ? " selected" : ""); ?>
													><?= $unit['inst_nome']; ?></option>
													<?php
												}
											?>
										</select>
										<small class="form-text text-muted"><?= TRANS('UNITS'); ?></small>
									</div>

									<div class="form-group col-md-1 toggle_show"> <!-- container-form-line -->
										<!-- <div class="switch-next-checkbox"> -->
											<input type="checkbox" name="deleteClientLimitation[]" value="<?= $client['inst_client']; ?>">&nbsp;<span class="align-top"><i class="fas fa-trash-alt text-danger" title="<?= TRANS('REMOVE'); ?>"></i></span>
										<!-- </div> -->
									</div>
									<div class="w-100"></div>
								<?php
								$i++;
							}
						}
					?>



				</div>

				<!-- Receberá as linhas de clientes e unidades -->
				<div id="new_clients" class="form-group row my-4 new_clients toggle_show"></div>


				<div class="form-group row my-4">

					<!-- Managers -->
					<div class="h6 w-100 my-4 border-top p-4 help-tip" title="<?= TRANS('MANAGERS'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_AREA_ADMINS_AREAS'); ?>"><i class="fas fa-user-tie text-secondary"></i>&nbsp;<?= TRANS('MANAGERS'); ?>:</div>
					
					<label class="col-md-2 col-form-label col-form-label-sm text-md-right" ><?= TRANS('MNL_USUARIOS'); ?></label>
					<div class="form-group col-md-10">
						<input type="text" class="form-control " id="area_admins" name="area_admins" value="<?= $adminsText; ?>" placeholder="<?= TRANS('ADD_OR_REMOVE'); ?>" />
						<div class="invalid-feedback">
							<?= TRANS('ERROR_MIN_SIZE_OF_TAGNAME'); ?>
						</div>
					</div>
				
				</div>
				<div class="form-group row my-4 " id="div_send_receive_areas"></div>
				
				
				<div class="form-group row my-4 ">


					<input type="hidden" name="cod" id="cod" value="<?= $COD; ?>">
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
	<script src="../../includes/components/jquery/jquery.initialize.min.js"></script>
	<script src="../../includes/components/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../includes/components/bootstrap-select/dist/js/bootstrap-select.min.js"></script>

	<script type="text/javascript" charset="utf8" src="../../includes/components/datatables/datatables.js"></script>
	<script src="../../includes/components/jquery/jquery.amsify.suggestags-master/js/jquery.amsify.suggestags.js"></script>

	<script type="text/javascript">
		$(function() {

			$('#table_lists').DataTable({
				language: {
					url: "../../includes/components/datatables/datatables.pt-br.json",
				},
				paging: true,
				deferRender: true,
				// order: [0, 'DESC'],
				columnDefs: [{
					searchable: false,
					orderable: false,
					targets: ['editar', 'remover']
				}],
				
			});

			$.fn.selectpicker.Constructor.BootstrapVersion = '4';
			$('.bs-select').selectpicker({
				/* placeholder */
				title: "<?= TRANS('SEL_SELECT', '', 1); ?>",
				liveSearch: true,
				actionsBox: true,
				deselectAllText: "<?= TRANS('DESELECT_ALL', '', 1); ?>",
				selectAllText: "<?= TRANS('SELECT_ALL', '', 1); ?>",
				liveSearchNormalize: true,
				liveSearchPlaceholder: "<?= TRANS('BT_SEARCH', '', 1); ?>",
				noneResultsText: "<?= TRANS('NO_RECORDS_FOUND', '', 1); ?> {0}",
				style: "",
				styleBase: "form-control ",
			});

			if ($('#area_admins').length > 0) {
				$('input[name="area_admins"]').amsifySuggestags({
					type : 'bootstrap',
					defaultTagClass: 'badge bg-secondary text-white p-2 m-1',
					tagLimit: 20,
					printValues: false,
					showPlusAfter: 10,
					
					suggestions: <?= $suggestions; ?>,
					whiteList: true
					
				});
			}


			$(function() {
				$('[data-toggle="popover"]').popover({
					html: true
				});
			});

			$('.popover-dismiss').popover({
				trigger: 'focus'
			});


			if (!$('#process_tickets').is(':checked')) {
				$('#mod_inventory').prop('disabled', true).prop('checked', false);
				$('#mod_inventory_no').prop('disabled', true).prop('checked', true);
			}

			$('[name="process_tickets"]').on('change', function() {
				if ($(this).val() == "no") {
					$('#mod_inventory').prop('checked', false).prop('disabled', true);
					$('#mod_inventory_no').prop('checked', true).prop('disabled', true);

					$('.toggle_show').hide();
					$.each($('.disableControl'), function(index, el) {
						$(this).prop('disabled', true);
						if ($(this).hasClass('bs-select')) {
							$(this).selectpicker('refresh');
						}
					});

					$('.areaFrom_yes').prop('checked', false).prop('disabled', true);
					$('.areaFrom_no').prop('checked', true).prop('disabled', true);
				} else {
					$('#mod_inventory').prop('disabled', false);
					$('#mod_inventory_no').prop('disabled', false);

					$('.areaFrom_yes').prop('disabled', false);
					$('.areaFrom_no').prop('disabled', false);
				}
			});

			if ($('#mod_inventory').is(':checked')) {
				$('.toggle_show').show();
			} else {
				$('.toggle_show').hide();
			}

			$('.add_button_new_client').on('click', function() {
				if ($('#mod_inventory').is(':checked')) {
					renderNewClients();
					availablesClientsControl();
				}
			});

			$('[name="mod_inventory"]').on('change', function() {
				if ($(this).val() == "no") {

					$('.toggle_show').hide();
					
					$.each($('.disableControl'), function(index, el) {
						$(this).prop('disabled', true);
						if ($(this).hasClass('bs-select')) {
							$(this).selectpicker('refresh');
						}
					});
					
				} else {

					$('.toggle_show').show();
					$.each($('.disableControl'), function(index, el) {
						$(this).prop('disabled', false);
						if ($(this).hasClass('bs-select')) {
							$(this).selectpicker('refresh');
						}
					});
				}
			});

			$('.new_clients').on('click', '.remove_button_client', function(e) {
                e.preventDefault();
				dataRandom = $(this).attr('data-random');
				$("."+dataRandom).remove();
				availablesClientsControl();
            });


			$.ajax({
                url: 'get_send_receive_areas.php',
                type: 'POST',
                data: {
					'cod': (typeof $('#cod') !== 'undefined' ? $('#cod').val() : ""),
					'action': $('#action').val()
                },
                success: function(data) {
                    $('#div_send_receive_areas').html(data);
                }
            });


			if ($('#div_send_receive_areas').length > 0) {
				/* Adicionei o mutation observer em função dos elementos que são adicionados após o carregamento do DOM */
				var obs = $.initialize(".switch-next-checkbox, .container-switch", function() {

					$(function() {
						$('[data-toggle="popover"]').popover({
							html: true
						});
					});

					$('.popover-dismiss').popover({
						trigger: 'focus'
					});
					
					
					controlSetDefaultArea();

					$('.container-switch').on('click', 'input', function() {

						var group_parent = $(this).parents(); //object
						var last_checkbox_id = group_parent.find('input:last').attr('id');
						var last_checkbox_name = group_parent.find('input:last').attr('name');

						if ($(this).val() == "no") {
							// $('#' + last_checkbox_id).prop('checked', false).prop('disabled', true);
							$('input[name="'+last_checkbox_name +'"]').prop('checked', false).prop('disabled', true);
							
						} else {
							// $('#' + last_checkbox_id).prop('disabled', false);
							$('input[name="'+last_checkbox_name +'"]').prop('disabled', false);
						}
					});

					$('input[name^="default_area"]').on('change', function() {
						controlInputDefaulArea($(this));
					})

				}, {
					target: document.getElementById('div_send_receive_areas')
				}); /* o target limita o scopo do observer */



				var obs2 = $.initialize(".new-label", function() {

					
					if ($('#action').val() == "new") {

						$('#area').on('blur', function() {
							$('#new_label').empty().html($('#area').val());
						});

						$('[name="process_tickets"]').on('change', function() {
							if ($(this).val() == "no") {

								$('#areaToOwnArea').prop('checked', false).prop('disabled', true);
								$('#areaToOwnArea_no').prop('checked', true).prop('disabled', true);
								$('#default_areaOwnArea').prop('checked', false).prop('disabled', true);
								// $('#default_areaOwnArea').prop('checked', false).attr("readonly");
							} else {
								$('#areaToOwnArea').prop('disabled', false);
								$('#areaToOwnArea_no').prop('disabled', false);
							}
						});
						
					} else if ($('#action').val() == "edit") {
						$('[name="process_tickets"]').on('change', function() {
							if ($(this).val() == "no") {

								$('.radio-own-area').prop('checked', false).prop('disabled', true);
								$('.radio-no-own-area').prop('checked', true).prop('disabled', true);
								$('.checkbox-own-area').prop('checked', false).prop('disabled', true);
								
							} else {
								$('.radio-own-area').prop('disabled', false);
								$('.radio-no-own-area').prop('disabled', false);
								// $('.checkbox-own-area').prop('disabled', false);
							}
						});
					}

				}, {
					target: document.getElementById('div_send_receive_areas')
				}); /* o target limita o scopo do observer */
			}

			


			if ($('#new_clients').length > 0) {
                /* Adicionei o mutation observer em função dos elementos que são adicionados após o carregamento do DOM */
                var obsClient = $.initialize(".after-dom-ready", function() {
					
					$.fn.selectpicker.Constructor.BootstrapVersion = '4';
					$('.bs-select').selectpicker({
						/* placeholder */
						title: "<?= TRANS('SEL_SELECT', '', 1); ?>",
						liveSearch: true,
						actionsBox: true,
                    	deselectAllText: "<?= TRANS('DESELECT_ALL', '', 1); ?>",
                    	selectAllText: "<?= TRANS('SELECT_ALL', '', 1); ?>",
						liveSearchNormalize: true,
						liveSearchPlaceholder: "<?= TRANS('BT_SEARCH', '', 1); ?>",
						noneResultsText: "<?= TRANS('NO_RECORDS_FOUND', '', 1); ?> {0}",
						style: "",
						styleBase: "form-control input-select-multi",
					});

					availablesClientsControl();

					
					
					$('[name="mod_inventory"]').on('change', function() {
						if ($(this).val() == "no") {
							
							$.each($('.disableControl'), function(index, el) {
								$(this).prop('disabled', true);
								if ($(this).hasClass('bs-select')) {
									$(this).selectpicker('refresh');
								}
							});
							
						} else {
							$.each($('.disableControl'), function(index, el) {
								$(this).prop('disabled', false);
								if ($(this).hasClass('bs-select')) {
									$(this).selectpicker('refresh');
								}
							});
						}
					});


                    // $('.after-dom-ready').on('change', function() {
                    $('.sel-control').on('change', function() {

						availablesClientsControl();

						var myId = $(this).attr('id');
						loadUnits(myId);
					});

                }, {
                    target: document.getElementById('new_clients')
                }); /* o target limita o scopo do observer */

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

				// $("input[name^='default_area']").attr("disabled",false); /* também funciona */
				// $("input[name^='default_area']").prop('disabled',false); /* também funciona? */
				$('input[type="checkbox"]:disabled').each(function(e) {
					$(this).removeAttr('disabled');
				});

				$("#idSubmit").prop("disabled", true);
				$.ajax({
					url: './areas_process.php',
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




		function renderNewClients() {
            var loading = $(".loading");
            $(document).ajaxStart(function() {
                loading.show();
            });
            $(document).ajaxStop(function() {
                loading.hide();
            });

            $.ajax({
                url: './render_clients_x_units.php',
                method: 'POST',
                data: {
                    // asset_client: $('#asset_client').val(),
                    random: Math.random().toString(16).substr(2, 8)
                },
                // dataType: 'json',
            }).done(function(data) {
                $('#new_clients').append(data);
            });
            return false;
		}


		function loadUnits(elementID) {
			var loading = $(".loading");
			$(document).ajaxStart(function() {
				loading.show();
			});
			$(document).ajaxStop(function() {
				loading.hide();
			});

			$.ajax({
				url: '../../ocomon/geral/get_units_by_client.php',
				method: 'POST',
				dataType: 'json',
				data: {
					client: $('#'+elementID).val(),
				},
			}).done(function(data) {
				// let unitDb = $('#unitDb').val();

				let target_id = elementID + '_' + elementID;

				$('#'+target_id).empty();
				if (Object.keys(data).length > 1) {
					$('#'+target_id).append('<option value=""><?= TRANS("SEL_SELECT"); ?></option>');
				}
				$.each(data, function(key, data) {
					$('#'+target_id).append('<option value="' + data.inst_cod + '">' + data.inst_nome + '</option>');
				});

				$('#'+target_id).selectpicker('refresh');
				// $('#'+target_id).selectpicker('val', unitDb);
				
			});
		}



		/* Faz o controle das opções de tipos de características disponíveis para seleção */
		function availablesClientsControl() {
			let keys = [];
			let values = [];

			/* Primeiro habilito todos os options */
			$('.sel-control').each(function(){
				$(this).find('option').each(function(){
					$(this).prop('disabled', false);
					
					if ($(this).hasClass('bs-select')) {
						$(this).selectpicker('refresh');
					}
				});
			});

			/* Pegando todos os IDs dos Selects e seus respectivos valores */
			$('select[name^="asset_client"]').each(function() {
				
				let id = $(this).attr('id');
				let value = $(this).val();
				
				keys.push(id);
				values.push(value);
			});

			for (var i = 0; i < keys.length; i++) {
				/* Para cada option confiro em todos os Selects */
				$('.sel-control').each(function(){

					/* Controle de seleção - Desabilita todos os options que tiverem o valor já selecionado para o ID checado*/
					if ($(this).attr('id') != keys[i]) {
						
						if (values[i] != '') {
							$(this).find('[value="'+values[i]+'"]').prop('disabled', true);
							if ($(this).hasClass('bs-select')) {
								$(this).selectpicker('refresh');
							}
						}
					} else {
						$(this).find('[value="'+values[i]+'"]').prop('disabled', false);
						if ($(this).hasClass('bs-select')) {
							$(this).selectpicker('refresh');
						}
					}
				});
			} 
		}





		function controlInputDefaulArea(el) {
			if (el.is(':checked')) {
				$.each($('.switch-next-checkbox'), function(index, el) {
					$('input[name^="default_area"]').prop('checked', false);
				});
				$(el).prop('checked', true).prop('disabled', true);
			}
		}


		function controlSetDefaultArea () {
			$.each($('.switch-next-checkbox'), function(index, el) {
				var group_parent = $(this).parent(); 

				/* Radio: ID da opção SIM */
				var first_checkbox_id = group_parent.find('input:first').attr('id');

				/* Se a opção está marcada como SIM */
				var enabled = group_parent.find('input:first').is(':checked');
				
				/* checkbox "padrao" */
				var last_checkbox_id = $(this).find('input:last').attr('id');
				var last_checkbox_name = $(this).find('input:last').attr('name');
				

				if (!enabled) {
					// $('#' + last_checkbox_id).prop('checked', false).prop('disabled', true);
					$('#' + last_checkbox_name).prop('checked', false).prop('disabled', true);
					// $('#' + last_checkbox_name).prop('checked', false).attr("readonly")
				} else {
					// $('#' + last_checkbox_id).prop('disabled', false);
					$('#' + last_checkbox_name).prop('disabled', false);
					// $('#' + last_checkbox_name).removeAttr("readonly");
				}
			});
		}


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
				url: './areas_process.php',
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