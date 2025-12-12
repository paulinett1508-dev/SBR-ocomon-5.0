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
use OcomonApi\WebControllers\AccessTokens;

$conn = ConnectPDO::getInstance();

$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 1);

$_SESSION['s_page_admin'] = $_SERVER['PHP_SELF'];

$idUserTicketByEmail = "";
$userTicketByEmail = getConfigValue($conn, 'API_TICKET_BY_MAIL_USER');

if ($userTicketByEmail) {
	$idUserTicketByEmail = getUserInfo($conn, 0, $userTicketByEmail)['user_id'];
}


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
		<h4 class="my-4"><i class="fas fa-key text-secondary"></i>&nbsp;<?= TRANS('ACCESS_TOKENS'); ?></h4>
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

		$query = "SELECT * FROM access_tokens ";
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
							<?= TRANS('CONFIRM_REMOVE'); ?>?
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
							<td class="line status"><?= TRANS("FIELD_USER"); ?></td>
							<td class="line dependencia"><?= TRANS("APP_NAME"); ?></td>
							<td class="line "><?= TRANS("TOKEN"); ?></td>
							<td class="line freeze"><?= TRANS("CREATED_AT"); ?></td>
							<td class="line freeze"><?= TRANS("ACTIVE_UNTIL"); ?></td>
							<td class="line editar"><?= TRANS("BT_UPDATE"); ?></td>
							<td class="line remover"><?= TRANS("BT_REMOVE"); ?></td>
						</tr>
					</thead>
					<tbody>
						<?php

						foreach ($resultado->fetchall() as $row) {
							$shortToken = substr($row['token'], 0, 65) . "...";
							$userInfo = getUserInfo($conn, $row['user_id']);
							$user = $userInfo['nome'];
							$login = $userInfo['login'];

							$tokenDB = (new AccessTokens())->expire_at($login, $row['app'], $row['token']);
						?>
							<tr id="<?= $row['id']; ?>">
								<td class="line"><?= $login; ?></td>
								<td class="line"><?= $row['app']; ?></td>
								<td class="line pointer token-show" data-content="<?= $row['token']; ?>"><?= $shortToken ?></td>
								<td class="line"><?= dateScreen($row['created_at']); ?></td>
								<td class="line"><?= $tokenDB; ?></td>
								<td class="line"><button type="button" class="btn btn-secondary btn-sm" onclick="redirect('<?= $_SERVER['PHP_SELF']; ?>?action=edit&cod=<?= $row['id']; ?>')"><?= TRANS('BT_UPDATE'); ?></button></td>
								<td class="line"><button type="button" class="btn btn-danger btn-sm" onclick="confirmDeleteModal('<?= $row['id']; ?>')"><?= TRANS('REMOVE'); ?></button></td>
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

			/* Apps sem tokens definidos */
			$sql = "SELECT app.app, app.controller, app.methods
			FROM apps_register app 
			LEFT JOIN access_tokens tk on app.app = tk.app
			WHERE 
				tk.app IS NULL 
			ORDER BY app.app";
			$exec_sql = $conn->query($sql);

			if (!$exec_sql->rowCount()) {
				echo message('info', TRANS('INFORMATION') . '<hr>', TRANS('INFO_TEMPORARY_FOR_NEW_TOKENS'), '', '', 1);

			} else {

				?>
				<form method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form">
					<?= csrf_input(); ?>
					<?= message('info', TRANS('INFORMATION') . '<hr>', TRANS('INFO_TEMPORARY_FOR_NEW_TOKENS'), '', '', 1); ?>
					<div class="form-group row my-4">

						<label for="app_name" class="col-sm-2 col-md-2 col-form-label text-md-right"><?= TRANS('APP_NAME'); ?></label>
						<div class="form-group col-md-10">
							<select class="form-control" id="app_name" name="app_name" required>
								<?php
								/* Apps sem tokens definidos */
								$sql = "SELECT app.app, app.controller, app.methods
										FROM apps_register app 
										LEFT JOIN access_tokens tk on app.app = tk.app
										WHERE 
											tk.app IS NULL 
										ORDER BY app.app";
								$exec_sql = $conn->query($sql);
								foreach ($exec_sql->fetchAll() as $rowApp) {
									print "<option value=" . $rowApp['app'] . ">" . $rowApp['app'] . "</option>";
								}
								?>
							</select>
						</div>

						<label for="user_id" class="col-sm-2 col-md-2 col-form-label text-md-right"><?= TRANS('FIELD_USER'); ?></label>
						<div class="form-group col-md-10">
							<select class="form-control" id="user_id" name="user_id" required>
								<option value="" selected><?= TRANS('SEL_SELECT'); ?></option>
								<?php
									$users = getUsers($conn, null, [1,2]);
									foreach ($users as $user) {
										?>
											<option value="<?= $user['user_id']?>"><?= $user['login']; ?></option>
										<?php
									}
								?>
							</select>
							<div class="invalid-feedback">
								<?= TRANS('MANDATORY_FIELD'); ?>
							</div>
						</div>

						<label for="lifespan" class="col-sm-2 col-md-2 col-form-label text-md-right"><?= TRANS('DURATION_TIME'); ?></label>
						<div class="form-group col-md-10">
							<select class="form-control" id="lifespan" name="lifespan" required>
								<?php
									$lifespan = [];
									$lifespan[1] = TRANS('LIFESPAN_1_DAY');
									$lifespan[10] = TRANS('LIFESPAN_10_DAYS');
									$lifespan[30] = TRANS('LIFESPAN_30_DAYS');
									$lifespan[180] = TRANS('LIFESPAN_180_DAYS');
									$lifespan[365] = TRANS('LIFESPAN_365_DAYS');
									foreach ($lifespan as $key => $value) {
										?>
											<option value="<?= $key; ?>"><?= $value; ?></option>
										<?php
									}
								?>
							</select>
							<div class="invalid-feedback">
								<?= TRANS('MANDATORY_FIELD'); ?>
							</div>
						</div>


						<div class="row w-100"></div>
						<div class="form-group col-md-8 d-none d-md-block">
						</div>
						<div class="form-group col-12 col-md-2 ">

							<input type="hidden" name="action" id="action" value="new">
							<button type="submit" id="idSubmit" name="submit" class="btn btn-primary btn-block"><?= TRANS('BT_OK'); ?></button>
						</div>
						<div class="form-group col-12 col-md-2">
							<button type="reset" class="btn btn-secondary btn-block" id="bt-cancel"><?= TRANS('BT_CANCEL'); ?></button>
						</div>


					</div>
				</form>
			<?php
			}
		} elseif ((isset($_GET['action']) && $_GET['action'] == "edit") && empty($_POST['submit'])) {
			$row = $resultado->fetch();
			?>
				<form method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form">
					<?= csrf_input(); ?>
					<?= message('info', TRANS('INFORMATION') . '<hr>', TRANS('INFO_TEMPORARY_FOR_NEW_TOKENS'), '', '', 1); ?>
					<div class="form-group row my-4">

						<label for="app_name" class="col-sm-2 col-md-2 col-form-label text-md-right"><?= TRANS('APP_NAME'); ?></label>
						<div class="form-group col-md-10">
							<select class="form-control" id="app_name" name="app_name" readonly>
								<option value="<?= $row['app']; ?>"><?= $row['app']; ?></option>
							</select>
						</div>

						<label for="user_id" class="col-sm-2 col-md-2 col-form-label text-md-right"><?= TRANS('FIELD_USER'); ?></label>
						<div class="form-group col-md-10">
							<?php
								$disable = "";
								if ($row['user_id'] == $idUserTicketByEmail) {
									$disable = "disabled";
									?>
										<input type="hidden" name="system_user" value="<?= $row['user_id']; ?>"/>
									<?php
								}
							?>
							<select class="form-control" id="user_id" name="user_id" <?= $disable; ?>>
								<option value="" selected><?= TRANS('SEL_SELECT'); ?></option>
								<?php
									$users = getUsers($conn, null, [1,2]);
									foreach ($users as $user) {
										?>
											<option value="<?= $user['user_id']?>"
											<?= ($user['user_id'] == $row['user_id'] ? " selected" : ""); ?>
											><?= $user['login']; ?></option>
										<?php
									}
								?>
							</select>
							<div class="invalid-feedback">
								<?= TRANS('MANDATORY_FIELD'); ?>
							</div>
						</div>

						<label for="lifespan" class="col-sm-2 col-md-2 col-form-label text-md-right"><?= TRANS('DURATION_TIME'); ?></label>
						<div class="form-group col-md-10">
							<select class="form-control" id="lifespan" name="lifespan" required>
								<?php
									$lifespan = [];
									$lifespan[1] = TRANS('LIFESPAN_1_DAY');
									$lifespan[10] = TRANS('LIFESPAN_10_DAYS');
									$lifespan[30] = TRANS('LIFESPAN_30_DAYS');
									$lifespan[180] = TRANS('LIFESPAN_180_DAYS');
									$lifespan[365] = TRANS('LIFESPAN_365_DAYS');
									foreach ($lifespan as $key => $value) {
										?>
										<option value="<?= $key; ?>"><?= $value; ?></option>
										<?php
									}
								?>
							</select>
							<div class="invalid-feedback">
								<?= TRANS('MANDATORY_FIELD'); ?>
							</div>
						</div>


						<div class="row w-100"></div>
						<div class="form-group col-md-8 d-none d-md-block">
						</div>
						<div class="form-group col-12 col-md-2 ">

							<input type="hidden" name="cod" id="cod" value="<?= $row['id']; ?>">
							<input type="hidden" name="action" id="action" value="edit">
							<button type="submit" id="idSubmit" name="submit" class="btn btn-primary btn-block"><?= TRANS('BT_OK'); ?></button>
						</div>
						<div class="form-group col-12 col-md-2">
							<button type="reset" class="btn btn-secondary btn-block" id="bt-cancel"><?= TRANS('BT_CANCEL'); ?></button>
						</div>


					</div>
				</form>
			<?php
		}
		?>
	</div>

	<script src="../../includes/javascript/funcoes-3.0.js"></script>
	<script src="../../includes/components/jquery/jquery.js"></script>
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

			$('#table_lists').on('click', 'td', function() {

				if ($(this).hasClass('token-show')) {
					$("#textareaToken").val($(this).attr("data-content"));
					$("#textareaToken").select();
					document.execCommand('copy');

					$('#copyButton').html('<a class="btn btn-primary"><?= TRANS('SMART_BUTTON_COPY'); ?></a>');
					$('#modalToken').modal();
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
					url: './tokens_process.php',
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
				url: './tokens_process.php',
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