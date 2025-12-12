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

require_once __DIR__ . "/" . "../../includes/include_geral_new.inc.php";
require_once __DIR__ . "/" . "../../includes/classes/ConnectPDO.php";

use includes\classes\ConnectPDO;

$conn = ConnectPDO::getInstance();


?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	
	<title>OcoMon&nbsp;<?= VERSAO; ?></title>
</head>

<body>
	<div class="container">
		<div id="idLoad" class="loading" style="display:none"></div>
	</div>


	<div class="p-2 topo-color"><br />
		<span><img src="./MAIN_LOGO.svg" width="220" style="padding-bottom: 12px;"></span>
	</div>



	<div id="divResultRecovery"></div>


	<div class="container">
		<h5 class="my-4"><i class="fas fa-key text-secondary"></i>&nbsp;<?= TRANS('REQUIRE_ACCESS_RECOVERY'); ?></h5>
		<div class="modal" id="modal-require" tabindex="-1" style="z-index:9001!important">
			<div class="modal-dialog modal-xl">
				<div class="modal-content">
					<div id="divDetails-require">
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

		<h6 class="mb-4"><?= TRANS('HELPER_REQUIRE_ACCESS_RECOVERY'); ?></h6>
		<form method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form" autocomplete="off">
			<?= csrf_input(); ?>
			<div class="form-group row my-4">


				<label for="login_name" class="col-md-2 col-form-label text-md-right"><?= TRANS('COL_LOGIN'); ?></label>
				<div class="form-group col-md-10">
					<input type="login_name" class="form-control " id="login_name" name="login_name" />
					<div class="invalid-feedback">
						<?= TRANS('MANDATORY_FIELD'); ?>
					</div>
				</div>

				<label for="email" class="col-md-2 col-form-label text-md-right"><?= TRANS('COL_EMAIL'); ?></label>
				<div class="form-group col-md-10">
					<input type="email" class="form-control " id="email" name="email" />
					<div class="invalid-feedback">
						<?= TRANS('MANDATORY_FIELD'); ?>
					</div>
				</div>



				<div class="row w-100"></div>
				<div class="form-group col-md-8 d-none d-md-block">
				</div>
				<div class="form-group col-12 col-md-6 ">

					<input type="hidden" name="action" id="action" value="require_recovery">
					<button type="submit" id="idSubmit" name="submit" class="btn btn-primary btn-block"><?= TRANS('BT_OK'); ?></button>
				</div>
				<div class="form-group col-12 col-md-6">
					<button type="reset" class="btn btn-secondary btn-block" data-dismiss="modal"><?= TRANS('BT_CANCEL'); ?></button>
				</div>

			</div>
		</form>
	</div>

	<!-- <script src="../../includes/javascript/funcoes-3.0.js"></script> -->
	<!-- <script src="../../includes/components/jquery/jquery.js"></script> -->
	<!-- <script src="../../includes/components/bootstrap/js/bootstrap.min.js"></script> -->
	<!-- <script src="../../includes/components/jquery/MHS/jquery.md5.min.js"></script> -->
	<script type="text/javascript">
		$(function() {

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
					url: './includes/common/require_access_recovery_process.php',
					method: 'POST',
					data: $('#form').serialize(),
					dataType: 'json',
				}).done(function(response) {

					if (!response.success) {
						// $('#divResultRecovery').html(response.message);
						$('#divResultRecovery').html('<div class=" h5 ">' + response.message + '</div>');
						$('input, select, textarea').removeClass('is-invalid');
						if (response.field_id != "") {
							$('#' + response.field_id).focus().addClass('is-invalid');
						}
						$("#idSubmit").prop("disabled", false);
					} else {
						$('#divResultRecovery').html('');
						$('input, select, textarea').removeClass('is-invalid');
						$("#idSubmit").prop("disabled", false);
						var url = 'index.php';
						$(location).prop('href', url);
						return false;
					}
				});
				return false;
			});

		});
	</script>
</body>

</html>