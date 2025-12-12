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

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" type="text/css" href="../../includes/css/estilos.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/css/switch_radio.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap/custom.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/components/fontawesome/css/all.min.css" />

	<style>
		hr.thick {
			border: 1px solid;
			color: #CCCCCC !important;
			/* border-radius: 5px; */

		}
	</style>

	<title>OcoMon&nbsp;<?= VERSAO; ?></title>
</head>

<body>

	<div class="container">
		<div id="idLoad" class="loading" style="display:none"></div>
	</div>

	<div id="divResult"></div>


	<div class="container-fluid bg-light">
		<h4 class="my-4"><i class="fas fa-envelope text-secondary"></i>&nbsp;<?= TRANS('TTL_CONFIG_MAIL'); ?></h4>
		<div class="modal" id="modal" tabindex="-1" style="z-index:9001!important">
			<div class="modal-dialog modal-xl">
				<div class="modal-content">
					<div id="divDetails">
					</div>
				</div>
			</div>
		</div>

		<div class="modal fade" id="modalAlertQueue" tabindex="-1" style="z-index:2001!important" role="dialog" aria-labelledby="myModalAlertQueue" aria-hidden="true">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<div id="divResultAlertQueue"></div>
					<div class="modal-header text-center bg-secondary">

						<h4 class="modal-title w-100 font-weight-bold text-white"><i class="fas fa-exclamation-circle"></i>&nbsp;<?= TRANS('TXT_IMPORTANT'); ?></h4>
						<button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>

					<div class="row p-3 mt-3">
						<div class="col">
							<p><?= TRANS('ALERT_BF_SET_MAIL_QUEUE'); ?></p>
						</div>
					</div>

					<div class="modal-footer d-flex justify-content-end bg-light">
						<button id="closeQueueMessage" class="btn btn-secondary" data-dismiss="modal" aria-label="Close"><?= TRANS('BT_CLOSE'); ?></button>
					</div>
				</div>
			</div>
		</div>

		<?php
		if (isset($_SESSION['flash']) && !empty($_SESSION['flash'])) {
			echo $_SESSION['flash'];
			$_SESSION['flash'] = '';
		}

		$registros = 1;
		/* Configuraçoes para envio de e-mails */
		$config = getMailConfig($conn);


		/* Classes para o grid */
		$colLabel = "col-sm-3 text-md-right font-weight-bold p-2 mb-4";
		$colsDefault = "small text-break border-bottom rounded p-2 bg-white"; /* border-secondary */
		$colContent = $colsDefault . " col-sm-9 col-md-9 ";
		$colContentLine = $colsDefault . " col-sm-9";
		/* Duas colunas */
		$colLabel2 = "col-sm-3 text-md-right font-weight-bold p-2 mb-4";
		$colContent2 = $colsDefault . " col-sm-3 col-md-3";


		if ((!isset($_GET['action'])) && !isset($_POST['submit'])) {

		?>
			<button class="btn btn-sm btn-primary bt-edit" id="idBtEdit" name="edit"><?= TRANS("BT_EDIT"); ?></button><br /><br />
			<?php
			if ($registros == 0) {
				echo message('info', '', TRANS('NO_RECORDS_FOUND'), '', '', true);
			} else {
			?>

				<div class="row my-2">
					<!-- Envio de emails habilitado ou não -->
					<div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('OPT_SEND_EMAIL_ENABLED')); ?></div>
					<div class="<?= $colContent; ?>">
						<?php
						$yesChecked = ($config['mail_send'] == 1 ? "checked" : "");
						$noChecked = ($config['mail_send'] == 0 ? "checked" : "");
						?>
						<div class="switch-field">
							<input type="radio" id="mail_send" name="mail_send" value="yes" <?= $yesChecked; ?> disabled />
							<label for="mail_send"><?= TRANS('YES'); ?></label>
							<input type="radio" id="mail_send_no" name="mail_send" value="no" <?= $noChecked; ?> disabled />
							<label for="mail_send_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>


					<!-- Habilita/desabilita fila de e-mails -->
					<div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('MAIL_QUEUE')); ?></div>
					<div class="<?= $colContent; ?>">
						<?php
						$yesChecked = ($config['mail_queue'] == 1 ? "checked" : "");
						$noChecked = ($config['mail_queue'] == 0 ? "checked" : "");
						?>
						<div class="switch-field">
							<input type="radio" id="mail_queue" name="mail_queue" value="yes" <?= $yesChecked; ?> disabled />
							<label for="mail_queue"><?= TRANS('YES'); ?></label>
							<input type="radio" id="mail_queue_no" name="mail_queue" value="no" <?= $noChecked; ?> disabled />
							<label for="mail_queue_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

					<!-- SMTP -->
					<div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('OPT_USE_SMTP')); ?></div>
					<div class="<?= $colContent; ?>">
						<?php
						$yesChecked = ($config['mail_issmtp'] == 1 ? "checked" : "");
						$noChecked = ($config['mail_issmtp'] == 0 ? "checked" : "");
						?>
						<div class="switch-field">
							<input type="radio" id="mail_is_smtp" name="mail_is_smtp" value="yes" <?= $yesChecked; ?> disabled />
							<label for="mail_is_smtp"><?= TRANS('YES'); ?></label>
							<input type="radio" id="mail_is_smtp_no" name="mail_is_smtp" value="no" <?= $noChecked; ?> disabled />
							<label for="mail_is_smtp_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>
					<div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('OPT_SMTP_ADDRESS')); ?></div>
					<div class="<?= $colContent; ?>"><?= $config['mail_host']; ?></div>
					<div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('OPT_SMTP_PORT')); ?></div>
					<div class="<?= $colContent; ?>"><?= $config['mail_port']; ?></div>
					<div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('OPT_SMTP_SECURE')); ?></div>
					<div class="<?= $colContent; ?>"><?= $config['mail_secure']; ?></div>
					<div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('OPT_NEED_AUTH')); ?></div>
					<div class="<?= $colContent; ?>">
						<?php
						$yesChecked = ($config['mail_isauth'] == 1 ? "checked" : "");
						$noChecked = ($config['mail_isauth'] == 0 ? "checked" : "");
						?>
						<div class="switch-field">
							<input type="radio" id="need_authentication" name="need_authentication" value="yes" <?= $yesChecked; ?> disabled />
							<label for="need_authentication"><?= TRANS('YES'); ?></label>
							<input type="radio" id="need_authentication_no" name="need_authentication" value="no" <?= $noChecked; ?> disabled />
							<label for="need_authentication_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

					<div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('FIELD_USER')); ?></div>
					<div class="<?= $colContent; ?>"><?= $config['mail_user']; ?></div>
					<div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('OPT_ADDRESS_FROM')); ?></div>
					<div class="<?= $colContent; ?>"><?= $config['mail_from']; ?></div>
					<div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('OPT_ADDRESS_FROM_NAME')); ?></div>
					<div class="<?= $colContent; ?>"><?= $config['mail_from_name']; ?></div>

					<div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('OPT_CONTENT_HTML')); ?></div>
					<div class="<?= $colContent; ?>">
						<?php
						$yesChecked = ($config['mail_ishtml'] == 1 ? "checked" : "");
						$noChecked = ($config['mail_ishtml'] == 0 ? "checked" : "");
						?>
						<div class="switch-field">
							<input type="radio" id="html_content" name="html_content" value="yes" <?= $yesChecked; ?> disabled />
							<label for="html_content"><?= TRANS('YES'); ?></label>
							<input type="radio" id="html_content_no" name="html_content" value="no" <?= $noChecked; ?> disabled />
							<label for="html_content_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>
				</div>

			<?php
			}
		} else
		if ((isset($_GET['action'])  && ($_GET['action'] == "edit")) && !isset($_POST['submit'])) {

			?>
			<h6><?= TRANS('EDITION'); ?></h6>
			<form method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form">
				<?= csrf_input(); ?>
				<?= alertRequiredModule('openssl'); ?>

				<div class="form-group row my-4">

					<!-- Envio de e-mails habilitado/desabilitado -->
					<label class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('OPT_SEND_EMAIL_ENABLED'); ?>"><?= firstLetterUp(TRANS('OPT_SEND_EMAIL_ENABLED')); ?></label>
					<div class="form-group col-md-10 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($config['mail_send'] == 1 ? "checked" : "");
							$noChecked = ($config['mail_send'] == 0 ? "checked" : "");
							?>
							<input type="radio" id="mail_send" name="mail_send" value="yes" <?= $yesChecked; ?> />
							<label for="mail_send"><?= TRANS('YES'); ?></label>
							<input type="radio" id="mail_send_no" name="mail_send" value="no" <?= $noChecked; ?> />
							<label for="mail_send_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>


					<!-- Fila de e-mails habilitada/desabilitada -->
					<label class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_MAIL_QUEUE'); ?>"><?= firstLetterUp(TRANS('MAIL_QUEUE')); ?></label>
					<div class="form-group col-md-10 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($config['mail_queue'] == 1 ? "checked" : "");
							$noChecked = ($config['mail_queue'] == 0 ? "checked" : "");
							?>
							<input type="radio" id="mail_queue" name="mail_queue" value="yes" <?= $yesChecked; ?> />
							<label for="mail_queue"><?= TRANS('YES'); ?></label>
							<input type="radio" id="mail_queue_no" name="mail_queue" value="no" <?= $noChecked; ?> />
							<label for="mail_queue_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

					<label class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('OPT_USE_SMTP'); ?>"><?= firstLetterUp(TRANS('OPT_USE_SMTP')); ?></label>
					<div class="form-group col-md-10 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($config['mail_issmtp'] == 1 ? "checked" : "");
							$noChecked = ($config['mail_issmtp'] == 0 ? "checked" : "");
							?>
							<input type="radio" id="mail_is_smtp" name="mail_is_smtp" value="yes" <?= $yesChecked; ?> />
							<label for="mail_is_smtp"><?= TRANS('YES'); ?></label>
							<input type="radio" id="mail_is_smtp_no" name="mail_is_smtp" value="no" <?= $noChecked; ?> />
							<label for="mail_is_smtp_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

					<label for="mail_host" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('OPT_SMTP_ADDRESS'); ?>"><?= firstLetterUp(TRANS('OPT_SMTP_ADDRESS')); ?></label>
					<div class="form-group col-md-10">
						<input type="text" class="form-control" name="mail_host" id="mail_host" required value="<?= $config['mail_host']; ?>" />
					</div>
					<label for="smtp_port" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('OPT_SMTP_PORT'); ?>"><?= firstLetterUp(TRANS('OPT_SMTP_PORT')); ?></label>
					<div class="form-group col-md-10">
						<input type="number" class="form-control" name="smtp_port" id="smtp_port" required value="<?= $config['mail_port']; ?>" />
					</div>

					<label for="smtp_secure" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_SMTP_SECURE'); ?>"><?= firstLetterUp(TRANS('OPT_SMTP_SECURE')); ?></label>
					<div class="form-group col-md-10">
						<input type="text" class="form-control" name="smtp_secure" id="smtp_secure" required value="<?= $config['mail_secure']; ?>" />
					</div>

					<label class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('OPT_NEED_AUTH'); ?>"><?= firstLetterUp(TRANS('OPT_NEED_AUTH')); ?></label>
					<div class="form-group col-md-10 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($config['mail_isauth'] == 1 ? "checked" : "");
							$noChecked = ($config['mail_isauth'] == 0 ? "checked" : "");
							?>
							<input type="radio" id="need_authentication" name="need_authentication" value="yes" <?= $yesChecked; ?> />
							<label for="need_authentication"><?= TRANS('YES'); ?></label>
							<input type="radio" id="need_authentication_no" name="need_authentication" value="no" <?= $noChecked; ?> />
							<label for="need_authentication_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

					<label for="smtp_user" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('OPT_USER_TO_AUTH'); ?>"><?= firstLetterUp(TRANS('OPT_USER_TO_AUTH')); ?></label>
					<div class="form-group col-md-10">
						<input type="text" class="form-control" name="smtp_user" id="smtp_user" value="<?= $config['mail_user']; ?>" />
					</div>

					<label for="smtp_pass" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('OPT_PASS_TO_AUTH'); ?>"><?= firstLetterUp(TRANS('OPT_PASS_TO_AUTH')); ?></label>
					<div class="form-group col-md-10">
						<input type="password" class="form-control" name="smtp_pass" id="smtp_pass" value="<?= $config['mail_pass']; ?>" />
					</div>

					<label for="address_from" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('OPT_ADDRESS_FROM'); ?>"><?= firstLetterUp(TRANS('OPT_ADDRESS_FROM')); ?></label>
					<div class="form-group col-md-10">
						<input type="text" class="form-control" name="address_from" id="address_from" required value="<?= $config['mail_from']; ?>" />
					</div>

					<label for="address_from_name" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('OPT_ADDRESS_FROM_NAME'); ?>"><?= firstLetterUp(TRANS('OPT_ADDRESS_FROM_NAME')); ?></label>
					<div class="form-group col-md-10">
						<input type="text" class="form-control" name="address_from_name" id="address_from_name" required value="<?= $config['mail_from_name']; ?>" />
					</div>

					<label class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('OPT_CONTENT_HTML'); ?>"><?= firstLetterUp(TRANS('OPT_CONTENT_HTML')); ?></label>
					<div class="form-group col-md-10 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($config['mail_ishtml'] == 1 ? "checked" : "");
							$noChecked = ($config['mail_ishtml'] == 0 ? "checked" : "");
							?>
							<input type="radio" id="html_content" name="html_content" value="yes" <?= $yesChecked; ?> />
							<label for="html_content"><?= TRANS('YES'); ?></label>
							<input type="radio" id="html_content_no" name="html_content" value="no" <?= $noChecked; ?> />
							<label for="html_content_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>


					<!-- ---------------------------------------- -->
					<div class="row w-100"></div>
					<div class="form-group col-md-5 d-none d-md-block">
					</div>


					<div class="form-group col-12 col-md-3">
						<input type="button" class="btn btn-success btn-block" name="testConnection" id="testConnection" value="<?= TRANS('TEST_MAIL_SETTINGS'); ?>">
					</div>

					<div class="form-group col-12 col-md-2 ">

						<input type="hidden" name="action" id="action" value="edit">
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
	<!-- <script src="../../includes/components/bootstrap/js/bootstrap.min.js"></script> -->
	<script src="../../includes/components/bootstrap/js/bootstrap.bundle.js"></script>
	<!-- <script type="text/javascript" charset="utf8" src="../../includes/components/datatables/datatables.js"></script> -->
	<script type="text/javascript">
		$(function() {

			$(function() {
				$('[data-toggle="popover"]').popover()
			});

			$('.popover-dismiss').popover({
				trigger: 'focus'
			});


			$('#mail_queue').on('change', function() {
				if ($(this).val() == 'yes') {
					$('#modalAlertQueue').modal();
				}
			});


			$('#testConnection').on('click', function(e) {
				e.preventDefault();
				var loading = $(".loading");
				$(document).ajaxStart(function() {
					loading.show();
				});
				$(document).ajaxStop(function() {
					loading.hide();
				});
				$("#testConnection").prop("disabled", true);
				$("#idSubmit").prop("disabled", true);
				$("#testConnection").val('<?= TRANS('WAIT'); ?>');

				$.ajax({
					url: './test_email_settings.php',
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
						$("#testConnection").prop("disabled", false);
						$("#idSubmit").prop("disabled", false);
						$("#testConnection").val('<?= TRANS('TEST_MAIL_SETTINGS'); ?>');
					} else {
						$('#divResult').html(response.message);
						$('input, select, textarea').removeClass('is-invalid');
						$("#testConnection").prop("disabled", false);
						$("#idSubmit").prop("disabled", false);
						$("#testConnection").val('<?= TRANS('TEST_MAIL_SETTINGS'); ?>');
						return false;
					}
				});
				return false;
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
					url: './config_mail_process.php',
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

			$('.bt-edit').on("click", function() {
				$('#idLoad').css('display', 'block');
				var url = '<?= $_SERVER['PHP_SELF'] ?>?action=edit';
				$(location).prop('href', url);
			});

			$('#bt-cancel').on('click', function() {
				var url = '<?= $_SERVER['PHP_SELF'] ?>';
				$(location).prop('href', url);
			});
		});
	</script>
</body>

</html>