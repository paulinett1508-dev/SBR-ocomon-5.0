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

include "PATHS.php";
require_once "includes/functions/functions.php";
require_once "includes/functions/dbFunctions.php";
include_once "includes/queries/queries.php";
require_once "" . $includesPath . "config.inc.php";
include_once "" . $includesPath . "versao.php";

require_once __DIR__ . "/" . "includes/classes/ConnectPDO.php";

use includes\classes\ConnectPDO;

$conn = ConnectPDO::getInstance();

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
</head>

<body style="background-color: #666666;">

	<?php
	if (!isset($_GET['code']) || empty($_GET['code'])) {
	?>
		<div class="h5"><?= message('danger', 'Ooops!', TRANS('INVALID_LINK'), '', '', true); ?></div>
	<?php
		return;
	}

	$code = noHtml($_GET['code']);
	list($user_id, $code) = explode('|', $code);

	$user = getUsers($conn, $user_id);

	if (!count($user)) {
	?>
		<div class="h5"><?= message('danger', 'Ooops!', TRANS('USERNAME_OR_EMAIL_NOT_FOUND'), '', '', true); ?></div>
	<?php
		return;
	}

	if (empty($user['forget']) || $user['forget'] != $code) {
	?>
		<div class="h5"><?= message('danger', 'Ooops!', TRANS('INVALID_LINK'), '', '', true); ?></div>
	<?php
		return;
	}
	?>

	<div class="limiter">
		<div class="container-login100">
			<div class="wrap-login100">


				<div class="modal" id="modal" tabindex="-1" style="z-index:9001!important">
					<div class="modal-dialog modal-lg">
						<div class="modal-content">
							<div id="divDetails">
								<p><?= TRANS('USER_SELF_REGISTER'); ?></p>
							</div>
						</div>
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
							<img src="./MAIN_LOGO.svg" alt="OcoMon">
						</span>
						<span class="login100-form-title mt-5 text-secondary">
							<?= TRANS('TTL_ALTER_PASS'); ?>
						</span>
					</div>



					<div class="wrap-input100 m-t-55">
						<input class="input100" type="password" name="new_pass_1" id="new_pass_1" autocomplete="off">
						<span class="focus-input100"></span>
						<span class="label-input100"><?= TRANS('TTL_NEWS_PASS'); ?></span>
					</div>


					<div class="wrap-input100 ">
						<input class="input100" type="password" name="new_pass_2" id="new_pass_2">
						<span class="focus-input100"></span>
						<span class="label-input100"><?= TRANS('REPEAT_NEW_PASSWORD'); ?></span>
					</div>



					<input type="hidden" name="user_id" id="user_id" value="<?= $user_id; ?>" />
					<input type="hidden" name="code" id="code" value="<?= $code; ?>" />
					<input type="hidden" name="action" id="action" value="edit" />
					<div class="container-login100-form-btn">
						<button class="login100-form-btn" id="idSubmit">
							<?= TRANS('BT_OK'); ?>
						</button>
					</div>

					
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
	<script src="./includes/components/jquery/MHS/jquery.md5.min.js"></script>
	<script src="./includes/components/bootstrap/js/bootstrap.bundle.js"></script>
	<script src="./includes/javascript/funcoes-3.0.js"></script>
	<script src="./includes/javascript/login.js"></script>

	<script>
		$('input').on('change', function() {
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

			let csrf = $('#csrf').val();
			let new_pass_1 = ($('#new_pass_1').val() != "" ? $.MD5($('#new_pass_1').val()) : "");
			let new_pass_2 = ($('#new_pass_2').val() != "" ? $.MD5($('#new_pass_2').val()) : "");
			let action = $('#action').val();
			// let cod = $('#cod').val();
			let user_id = $('#user_id').val();
			let code = $('#code').val();
			$("#idSubmit").prop("disabled", true);
			$.ajax({
				url: './includes/common/set_new_pass_process.php',
				method: 'POST',
				data: {
					"csrf": csrf,
					"new_pass_1": new_pass_1,
					"new_pass_2": new_pass_2,
					"action": action,
					// "cod" : cod,
					"user_id": user_id,
					"code": code,
				},
				dataType: 'json',
			}).done(function(response) {

				if (!response.success) {
					// $('#divResult').html(response.message);
					$('#divResult').html('<div class=" h5 ">' + response.message + '</div>');
					$('input, select, textarea').removeClass('is-invalid');
					if (response.field_id != "") {
						$('#' + response.field_id).focus().addClass('is-invalid');
					}
					$("#idSubmit").prop("disabled", false);
				} else {
					$('#divResult').html('');
					$('input, select, textarea').removeClass('is-invalid');
					$("#idSubmit").prop("disabled", false);
					var url = 'index.php';
					$(location).prop('href', url);
					return false;
				}
			});
			return false;
		});
	</script>

</body>

</html>