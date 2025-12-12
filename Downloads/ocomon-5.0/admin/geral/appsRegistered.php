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

$api = 'OcomonApi';
$controller = 'Controllers';
$section = 'Tickets';
$section_name = TRANS('TICKETS');
$possible_actions = ['read', 'update', 'create', 'close', 'delete'];
$allowed_actions = ['create', 'read'];




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

	<style>
		.copy-to-clipboard input {
			border: none;
			background: transparent;
		}

		.copied {
			position: absolute;
			background: #1266ae;
			color: #fff;
			font-weight: bold;
			z-index: 9001;
			width: 100%;
			top: 0;
			text-align: center;
			padding: 15px;
			display: none;
			font-size: 18px;
		}
	</style>

	<title>OcoMon&nbsp;<?= VERSAO; ?></title>
</head>

<body>

	<div class="container">
		<div id="idLoad" class="loading" style="display:none"></div>
	</div>

	<div id="divResult"></div>


	<div class="container-fluid">
		<h4 class="my-4"><i class="fas fa-link text-secondary"></i>&nbsp;<?= TRANS('APPS_THROUGH_API'); ?></h4>
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

		// $terms = " WHERE controller like ('{$api}%{$controller}%{$section}') ";
		$terms = " ";
		$query = "SELECT * FROM apps_register {$terms}";
		if (isset($_GET['cod'])) {
			$query .= " WHERE id = " . (int)$_GET['cod'] . " ";
		}
		$query .= " ORDER BY app";

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


			<div class='copied'></div>

			<!-- Modal token -->
			<div class="modal fade" id="modalToken" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header bg-light">
							<h5 class="modal-title" id="exampleModalLabel"><i class="fas fa-key text-secondary"></i>&nbsp;<?= TRANS('TOKEN'); ?></h5>
							<button type="button" class="close" data-dismiss="modal" aria-label="Close">
								<span aria-hidden="true">&times;</span>
							</button>
						</div>
						<div class="modal-body">
							<textarea class="form-control copy-to-clipboard" id="textareaToken" rows="6" readonly></textarea>
						</div>
						<div class="modal-footer bg-light">
							<button type="button" class="btn btn-secondary" data-dismiss="modal"><?= TRANS('BT_CLOSE'); ?></button>
							<button type="button" id="copyButton" class="btn"><?= TRANS('SMART_BUTTON_COPY'); ?></button>
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
							<td class="line dependencia"><?= TRANS("APP_NAME"); ?></td>
							<td class="line painel"><?= TRANS("ACCESS_TO"); ?></td>
							<td class="line freeze"><?= TRANS("ALLOWED_ACTIONS"); ?></td>
							<td class="line editar"><?= TRANS("BT_ALTER"); ?></td>
							<td class="line remover"><?= TRANS("BT_REMOVE"); ?></td>
						</tr>
					</thead>
					<tbody>
						<?php

						foreach ($resultado->fetchall() as $row) {
							$arrayMethods = explode(",", $row['methods']);
							$transMethods = "";
							foreach ($arrayMethods as $method) {
								if (strlen((string)$transMethods)) $transMethods .= ", ";
								$transMethods .= TRANS(strtoupper($method));
							}
						?>
							<tr id="<?= $row['id']; ?>">
								<td class="line" data-content="<?= $row['app']; ?>"><?= $row['app']; ?></td>
								<td class="line"><?= $section_name ?></td>
								<td class="line"><?= $transMethods; ?></td>
								<?php
								/* O registro 1 é padrão para abertura de chamados por e-mail */
								if ($row['id'] == 1) {
								?>
									<td class="line"><button type="button" class="btn btn-secondary btn-sm" disabled><?= TRANS('BT_EDIT'); ?></button></td>
									<td class="line"><button type="button" class="btn btn-danger btn-sm" disabled><?= TRANS('REMOVE'); ?></button></td>
								<?php
								} else {
								?>
									<td class="line"><button type="button" class="btn btn-secondary btn-sm" onclick="redirect('<?= $_SERVER['PHP_SELF']; ?>?action=edit&cod=<?= $row['id']; ?>')"><?= TRANS('BT_EDIT'); ?></button></td>
									<td class="line"><button type="button" class="btn btn-danger btn-sm" onclick="confirmDeleteModal('<?= $row['id']; ?>')"><?= TRANS('REMOVE'); ?></button></td>
								<?php
								}
								?>
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
			<form method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form">
				<?= csrf_input(); ?>
				<?= message('info', TRANS('INFORMATION') . '<hr>', TRANS('INFO_TEMPORARY_FOR_NEW_APPS'), '', '', 1); ?>

				<?= alertRequiredModule('curl'); ?>

				<div class="form-group row my-4">

					<label for="app_name" class="col-sm-2 col-md-2 col-form-label text-md-right"><?= TRANS('APP_NAME'); ?></label>
					<div class="form-group col-md-10">
						<input type="text" class="form-control" id="app_name" name="app_name" required placeholder="<?= TRANS('APP_NAME'); ?>" />
						<div class="invalid-feedback">
							<?= TRANS('MANDATORY_FIELD'); ?>
						</div>
					</div>

					<label for="access_to" class="col-sm-2 col-md-2 col-form-label text-md-right"><?= TRANS('ACCESS_TO'); ?></label>
					<div class="form-group col-md-10">
						<input type="text" class="form-control" id="access_to" name="access_to" disabled value="<?= $section_name; ?>" />
					</div>

					<label for="allowed_actions" class="col-sm-2 col-md-2 col-form-label text-md-right"><?= TRANS('ALLOWED_ACTIONS'); ?></label>
					<div class="form-group col-md-10">
					</div>
					<?php
					foreach ($possible_actions as $action) {
					?>
						<label class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS(strtoupper($action)); ?></label>
						<div class="form-group col-md-4 switch-field">
							<?php
							$disabled = (!in_array($action, $allowed_actions) ? " disabled" : "");
							$yesChecked = "";
							$noChecked = ($yesChecked == "" ? "checked" : "");
							?>
							<input type="radio" id="allowed_action[<?= $action; ?>]" name="allowed_action[<?= $action; ?>]" value="yes" <?= $yesChecked; ?> <?= $disabled; ?> />
							<label for="allowed_action[<?= $action; ?>]"><?= TRANS('YES'); ?></label>
							<input type="radio" id="allowed_action_no[<?= $action; ?>]" name="allowed_action[<?= $action; ?>]" value="no" <?= $noChecked; ?> <?= $disabled; ?> />
							<label for="allowed_action_no[<?= $action; ?>]"><?= TRANS('NOT'); ?></label>
						</div>
					<?php
					}
					?>


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
			<form method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form">
				<?= csrf_input(); ?>
				<?= message('info', TRANS('INFORMATION') . '<hr>', TRANS('INFO_TEMPORARY_FOR_NEW_APPS'), '', '', 1); ?>
				<?= alertRequiredModule('curl'); ?>
				
				<div class="form-group row my-4">

					<label for="app_name" class="col-sm-2 col-md-2 col-form-label text-md-right"><?= TRANS('APP_NAME'); ?></label>
					<div class="form-group col-md-10">
						<input type="text" class="form-control" id="app_name" name="app_name" value="<?= $row['app']; ?>" placeholder="<?= TRANS('APP_NAME'); ?>" disabled />
						<div class="invalid-feedback">
							<?= TRANS('MANDATORY_FIELD'); ?>
						</div>
					</div>

					<label for="access_to" class="col-sm-2 col-md-2 col-form-label text-md-right"><?= TRANS('ACCESS_TO'); ?></label>
					<div class="form-group col-md-10">
						<input type="text" class="form-control" id="access_to" name="access_to" disabled value="<?= $section_name; ?>" />
					</div>

					<label for="allowed_actions" class="col-sm-2 col-md-2 col-form-label text-md-right"><?= TRANS('ALLOWED_ACTIONS'); ?></label>
					<div class="form-group col-md-10">
					</div>
					<?php
					foreach ($possible_actions as $action) {
					?>
						<label class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS(strtoupper($action)); ?></label>
						<div class="form-group col-md-4 switch-field">
							<?php
							$disabled = (!in_array($action, $allowed_actions) ? " disabled" : "");
							$arrayActions = explode(',', $row['methods']);
							$yesChecked = (in_array($action, $arrayActions) ? " checked" : "");
							$noChecked = ($yesChecked == "" ? "checked" : "");
							?>
							<input type="radio" id="allowed_action[<?= $action; ?>]" name="allowed_action[<?= $action; ?>]" value="yes" <?= $yesChecked; ?> <?= $disabled; ?> />
							<label for="allowed_action[<?= $action; ?>]"><?= TRANS('YES'); ?></label>
							<input type="radio" id="allowed_action_no[<?= $action; ?>]" name="allowed_action[<?= $action; ?>]" value="no" <?= $noChecked; ?> <?= $disabled; ?> />
							<label for="allowed_action_no[<?= $action; ?>]"><?= TRANS('NOT'); ?></label>
						</div>
					<?php
					}
					?>

					<div class="row w-100"></div>
					<div class="form-group col-md-8 d-none d-md-block">
					</div>
					<div class="form-group col-12 col-md-2 ">

						<input type="hidden" name="action" id="action" value="edit">
						<input type="hidden" name="cod" id="cod" value="<?= $row['id']; ?>">
						<button type="submit" id="idSubmit" name="submit" class="btn btn-primary btn-block"><?= TRANS('BT_OK'); ?></button>
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
	<!-- <script type="text/javascript" src="../../includes/components/jquery/jquery-ui-1.12.1/jquery-ui.js"></script> -->
	<script src="../../includes/components/bootstrap/js/bootstrap.bundle.min.js"></script>
	<script type="text/javascript" charset="utf8" src="../../includes/components/datatables/datatables.js"></script>
	<script type="text/javascript">
		$(function() {

			var myTable = $('#table_lists').DataTable({
				paging: true,
				deferRender: true,
				// order: [0, 'DESC'],
				columnDefs: [{
					searchable: false,
					orderable: false,
					targets: ['editar', 'remover']
				}],
				"language": {
					"url": "../../includes/components/datatables/datatables.pt-br.json"
				}
			});


			$(function() {
				$('[data-toggle="popover"]').popover()
			});

			$('.popover-dismiss').popover({
				trigger: 'focus'
			});


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

				$("#idSubmit").prop("disabled", true);
				$.ajax({
					url: './apps_registered_process.php',
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

			$("#copyButton").on('click', function() {
				$("#textareaToken").focus();
				$("#textareaToken").select();
				document.execCommand('copy');
				$(".copied").text("<?= TRANS('COPIED_TO_CLIPBOARD'); ?>").show().fadeOut(1200);
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
				url: './apps_registered_process.php',
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