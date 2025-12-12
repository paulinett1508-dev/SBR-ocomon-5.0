<?php
/*                        Copyright 2023 Flávio Ribeiro

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

is_file("./includes/config.inc.php")
	or die("Você precisa configurar o arquivo config.inc.php em OCOMON/INCLUDES/para iniciar o uso do OCOMON!<br>Leia o arquivo <a href='LEIAME.md'>LEIAME.md</a> para obter as principais informações sobre a instalação do OCOMON!" .
		"<br><br>You have to configure the config.inc.php file in OCOMON/INCLUDES/ to start using Ocomon!<br>Read the file <a href='LEIAME.md'>LEIAME.md</a> to get the main informations about the Ocomon Installation!");

if (version_compare(phpversion(), '7.4', '<')) {
	session_start();
	session_destroy();
	echo "A versão mínima do PHP deve ser a 7.4. Será necessário atualizar o PHP para poder utilizar o OcoMon.<hr>";
	echo "OcoMon needs at least PHP 7.4 to run properly.";
	return;
}

// if (!function_exists('mb_internal_encoding')) {
// 	/* Não possui o módulo mbstring */
// 	session_start();
// 	session_destroy();
// 	echo "É necessário instalar o módulo mbstring no seu PHP para que o OcoMon funcione adequadamente.<hr>";
// 	echo "You need to install mbstring PHP module in order to OcoMon runs properly.";
// 	return;
// }

session_start();
include "PATHS.php";
require_once "includes/functions/functions.php";
require_once "includes/functions/dbFunctions.php";
include_once "includes/queries/queries.php";
require_once "" . $includesPath . "config.inc.php";
include_once "" . $includesPath . "versao.php";


$missingModule = alertRequiredModule('pdo');
if (strlen((string)$missingModule)) {
	echo $missingModule;
	return;
}

$missingModule = alertRequiredModule('pdo_mysql');
if (strlen((string)$missingModule)) {
	echo $missingModule;
	return;
}

$missingModule = alertRequiredModule('mbstring');
if (strlen((string)$missingModule)) {
	echo $missingModule;
	return;
}


require_once __DIR__ . "/" . "includes/classes/ConnectPDO.php";

use includes\classes\ConnectPDO;

$conn = ConnectPDO::getInstance();

if (!$conn) {
	echo "<hr />" . TRANS('MSG_CONECTION_TO_DATABASE_FAILED') . "<hr />";
	return;
}

if (!isset($_SESSION['s_language'])) {
	$_SESSION['s_language'] = "pt_BR.php";
}

if (isset($_SESSION['s_logado']) && $_SESSION['s_logado'] == 1) {
	redirect('./index.php');
	exit;
}

$screen = getScreenInfo($conn, 1);
$mailConfig = getMailConfig($conn);
$configExt = getConfigValues($conn);

if (isset($_SESSION['session_expired']) && $_SESSION['session_expired'] == 1) {
	$_SESSION['flash'] = message('warning', 'Ooops!', TRANS('MSG_EXPIRED_SESSION'), '');
	$_SESSION['session_expired'] = '0';
}

$showForgetPass = ($mailConfig['mail_send'] ? true : false);
$showSelfRegister = ($screen['conf_user_opencall'] && $mailConfig['mail_send'] ? true : false);
$showOpenTicket = $configExt['ANON_OPEN_ALLOW'];

$authType = (isset($configExt['AUTH_TYPE']) ? $configExt['AUTH_TYPE'] : 'SYSTEM'); /* SYSTEM OR LDAP */

$login_cookie = filter_input(INPUT_COOKIE, "oc_login");
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
	<title>OcoMon&nbsp;<?= VERSAO; ?></title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="<?= TRANS('TTL_OCOMON'); ?>">
	<link rel="stylesheet" href="./includes/components/bootstrap/custom.css">
	<link rel="stylesheet" href="./includes/components/fontawesome/css/all.min.css">
	<link rel="stylesheet" type="text/css" href="./includes/css/estilos.css" />
	<link rel="stylesheet" type="text/css" href="./includes/css/estilos_custom.css" />
	<!-- <link rel="stylesheet" type="text/css" href="./includes/css/index_css.css" /> -->
	<link rel="stylesheet" type="text/css" href="./includes/css/util.css" />
	<link rel="stylesheet" type="text/css" href="./includes/css/login.css" />
	<link rel="shortcut icon" href="./includes/icons/favicon.ico">

	<style>
		.modal-recovery {
			max-width: 560px;
			margin: 30px auto;
		}
	</style>
</head>

<body style="background-color: #666666;">

	<div class="limiter">
		<div class="container-login100">
			<div class="wrap-login100">


				<div class="modal " id="modal" tabindex="-1" style="z-index:9001!important">
					<div class="modal-dialog modal-lg ">
						<div class="modal-content">
							<div id="divDetails">
								<p><?= TRANS('USER_SELF_REGISTER'); ?></p>
							</div>
						</div>
					</div>
				</div>

				<div class="modal" id="modalRecovery" tabindex="-1" style="z-index:9001!important">
					<div class="modal-dialog "><!-- modal-recovery -->
						<div class="modal-content">
							<div id="divDetailsRecovery">
							</div>
						</div>
						<!-- <div class="modal-content">
							<div id="divDetailsRecovery" style="position:relative">
								<iframe id="iframeDetailsRecovery"  frameborder="0" style="position:absolute;top:0px;width:95%;height:100vh;"></iframe>
							</div>
						</div> -->
					</div>
				</div>

				<div class="container">
					<div id="idLoad" class="loading" style="display:none"></div>
				</div>



				<form class="login100-form">

					<?php
					if (isset($_SESSION['flash']) && !empty($_SESSION['flash'])) {
					?>
						<div class="h5"><?= $_SESSION['flash']; ?></div>
					<?php
						$_SESSION['flash'] = '';
					}
					?>
					<div id="divResult"></div>

					<div class="  ">
						<!-- login-logo -->
						<span class="login100-form-title ">
							<!-- p-b-43 -->
							<!-- topo-color -->
							<img src="./MAIN_LOGO.svg" alt="OcoMon" width="280">
						</span>
					</div>



					<div class="wrap-input100 m-t-50">
						<input class="input100" type="text" name="user" id="user" value="<?= $login_cookie ?? null; ?>" autocomplete="off" tabindex="1">
						<span class="focus-input100"></span>
						<span class="label-input100"><?= TRANS('FIELD_USER'); ?></span>
					</div>


					<div class="wrap-input100 ">
						<input class="input100" type="password" name="pass" id="pass" tabindex="2">
						<span class="focus-input100"></span>
						<span class="label-input100"><?= TRANS('PASSWORD'); ?></span>
					</div>

					<div class="flex-sb-m w-full p-t-3 p-b-32">
						<div class="contact100-form-checkbox">
							<input class="input-checkbox100" id="remember_user" type="checkbox" <?= ($login_cookie ? "checked" : ""); ?> name="remember_user">
							<label class="label-checkbox100" for="remember_user">
								<?= TRANS('REMEMBER_MY_USERNAME'); ?>
							</label>
						</div>

						<?php
						if ($authType == "SYSTEM" && $showForgetPass) {
						?>
							<div>
								<a href="#" class="txt1" id="forgot_pass">
									<?= TRANS('FORGOT_PASSWORD'); ?>
								</a>
							</div>
						<?php
						}
						?>
					</div>

					<input type="hidden" name="auth_type" id="auth_type" value="<?= $authType; ?>">
					<div class="container-login100-form-btn">
						<button class="login100-form-btn bg-primary" id="bt_login" tabindex="3">
							<?= TRANS('ENTER_IN'); ?>
						</button>
					</div>

					<!-- Links para auto-cadastro e abertura de chamados sem cadastro -->
					<?php
					if ($showSelfRegister || $showOpenTicket) {
					?>
						<div class="text-center p-t-15 p-b-8">
							<span class="txt1">
								<?= TRANS('UNREGISTERED'); ?>
							</span>
						</div>

						<div class="login100-form-social flex-c-m">
							<?php
							if ($showSelfRegister) {
							?>
								<a href="#" id="registerToOpen" class="login100-form-social-item flex-c-m bg-info m-r-5" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('MNS_MSG_CAD_ABERTURA_1'); ?>">
									<i class="fas fa-user-plus btlogin-actions" aria-hidden="true"></i>
								</a>
							<?php
							}

							if ($showOpenTicket) {
							?>
								<a href="#" id="openBlindTicket" class="login100-form-social-item flex-c-m bg-info m-r-5" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('OPEN_BLIND_TICKET'); ?>">
									<i class="fas fa-headset btlogin-actions" aria-hidden="true"></i>
								</a>
							<?php
							}
							?>
						</div>

					<?php
					}
					?>


					<!-- FOOTER -->
					<div class="footer bg-light border-top text-center p-2 d-none d-sm-block">
						<div class="txt1">
							<a href="https://ocomonphp.sourceforge.io/" target="_blank">
								OcoMon
							</a>&nbsp;-&nbsp;
							<?= TRANS('OCOMON_ABSTRACT'); ?> -
							<?= TRANS('COL_VERSION') . ": " . VERSAO . " - " . TRANS('MNS_MSG_LIC') . " GPL"; ?>
						</div>
					</div>


				</form>
				<div class="login100-more login-screen">
				</div>
			</div>
		</div>
	</div>




	<script src="./includes/components/jquery/jquery.js"></script>
	<script src="./includes/components/jquery/jquery.initialize.min.js"></script>
	<script src="./includes/components/jquery/MHS/jquery.md5.min.js"></script>
	<script src="./includes/components/bootstrap/js/bootstrap.bundle.js"></script>
	<script src="./includes/javascript/funcoes-3.0.js"></script>
	<script src="./includes/javascript/login.js"></script>

	<script>
		$(function() {


			$(function() {
				$('[data-toggle="popover"]').popover()
			});

			$('.popover-dismiss').popover({
				trigger: 'focus'
			});


			if ($('#user').hasClass('has-val')) {
				$('#pass').focus();
			} else {
				$('#user').focus();
			}


			$('#forgot_pass').on('click', function() {
				requireAccessRecovery();
			}).css({
				cursor: "pointer"
			});

			if ($('#registerToOpen').length > 0) {
				$('#registerToOpen').on('click', function() {
					autosubscribeform();
				}).css({
					cursor: "pointer"
				});
			}

			if ($('#openBlindTicket').length > 0) {
				$('#openBlindTicket').on('click', function() {
					var url = './ocomon/open_form/ticket_form_open.php';
					$(location).prop('href', url);
					// return false;
				}).css({
					cursor: "pointer"
				});
			}


			// $("#sidebar").load('menu-sidebar.php');
			$('input, select, textarea').on('change', function() {
				$(this).removeClass('is-invalid');
			});

			$('#bt_login').on('click', function(e) {
				e.preventDefault();
				var loading = $(".loading");
				$(document).ajaxStart(function() {
					loading.show();
				});
				$(document).ajaxStop(function() {
					loading.hide();
				});

				let user = $('#user').val();
				let auth_type = $('#auth_type').val();
				if (auth_type != "LDAP") {
					var pass = ($('#pass').val() != "" ? $.MD5($('#pass').val()) : "");
				} else {
					var pass = ($('#pass').val() != "" ? $('#pass').val() : "");
				}
				let csrf = $('#csrf').val();

				$("#bt_login").prop("disabled", true);
				$.ajax({
					url: '<?= $commonPath ?>auth_process.php',
					method: 'POST',
					data: {
						"csrf": csrf,
						"user": user,
						"pass": pass,
						"remember_user": ($('#remember_user').is(":checked") ? 1 : 0)
					},
					dataType: 'json',
				}).done(function(response) {

					if (!response.success) {

						$('#divResult').html('<div class=" h5 ">' + response.message + '</div>');
						// $('#divResult').html(response.message);
						$('input, select, textarea').removeClass('is-invalid');
						if (response.field_id != "") {
							$('#' + response.field_id).focus().addClass('is-invalid');
						}
						$("#bt_login").prop("disabled", false);
					} else {
						$('#divResult').html('');
						$('input, select, textarea').removeClass('is-invalid');
						$("#bt_login").prop("disabled", false);
						var url = 'index.php';
						$(location).prop('href', url);
						return false;
					}
				});
				return false;
			});

		});

		function autosubscribeform() {
			let location = './newUser.php';
			$("#divDetails").load(location);
			$('#modal').modal();
		}

		function requireAccessRecovery() {
			let location = './includes/common/require_access_recovery.php';
			$("#divDetailsRecovery").load(location);
			// $("#iframeDetailsRecovery").attr('src',location)
			$('#modalRecovery').modal();
		}
	</script>

</body>

</html>