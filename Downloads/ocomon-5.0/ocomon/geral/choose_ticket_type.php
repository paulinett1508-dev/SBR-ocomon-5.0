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

$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 3, 1);
$_SESSION['s_page_ocomon'] = $_SERVER['PHP_SELF'];

$params = "";

if (isset($_GET) && !empty($_GET)) {
	$params = "&" . http_build_query($_GET, "", "&");
}


if ($_SESSION['s_opening_mode'] == 1) {
	/* Mode de abertura clássico - padrão */
	header("Location: ./ticket_add.php?" . $params);
	return;
}

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
	<link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap-select/dist/css/bootstrap-select.min.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/css/my_bootstrap_select.css" />

	<title>OcoMon&nbsp;<?= VERSAO; ?></title>

	<style>
		.container-switch {
			position: relative;
		}

		.switch-next-checkbox {
			position: absolute;
			top: 0;
			left: 130px;
			z-index: 1;
		}
	</style>
</head>

<body>
	
	<div class="container">
		<div id="idLoad" class="loading" style="display:none"></div>
	</div>


	<div class="container-fluid">
		<div class="modal" id="modal" tabindex="-1" style="z-index:9001!important">
			<div class="modal-dialog modal-xl">
				<div class="modal-content">
					<div id="divDetails">
					</div>
				</div>
			</div>
		</div>

		<div class="modal fade" id="modalChooseTicketType" data-backdrop="static" data-keyboard="false" tabindex="-1" style="z-index:2001!important" role="dialog" aria-labelledby="myModalChoose" aria-hidden="true">
        	<div class="modal-dialog modal-xl" role="document">
            	<div class="modal-content">
					<div id="divResult"></div>
					<div class="modal-header text-center bg-light">

						<h4 class="modal-title w-100 font-weight-bold text-secondary"><i class="fas fa-ticket-alt"></i>&nbsp;<?= TRANS('CHOOSE_TICKET_TYPE'); ?></h4>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
                

					<?php
						$classMarginTop = "mt-4";
						
					?>
					
					
					<div class="row mx-2 <?= $classMarginTop; ?>">
								
						<div class="form-group col-sm-9 col-md-9">
							<select class="form-control" name="issue_type" id="issue_type">
								<?php
									$problems = getIssuesByArea4($conn, false, null, 0, $_SESSION['s_uareas'], null);
									foreach ($problems as $problem) {
										?>
										<option value="<?= $problem['prob_id']; ?>"><?= $problem['problema']; ?></option>
										<?php
									}
								?>
							
							</select>
							<small class="form-text text-muted"><?= TRANS('HELPER_CHOOSE_TICKET_TYPE'); ?></small>
						</div>

						<input type="hidden" name="params" id="params" value="<?= $params; ?>"/>

						<div class="form-group col-sm-3 col-md-3">
							<button class="form-control btn btn-primary nowrap" id="continue" name="continue"><?= TRANS('CONTINUE'); ?></button>
						</div>
					</div>
					<div class="row mx-2 mt-4" id="prob_description"></div>
					<div class="row mx-4" id="possible_areas"></div>


					<div class="modal-footer d-flex justify-content-end bg-light">
						<button id="cancelOpening" class="btn btn-secondary" data-dismiss="modal" aria-label="Close"><?= TRANS('BT_CANCEL'); ?></button>
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
	</div>

	<script src="../../includes/javascript/funcoes-3.0.js"></script>
	<script src="../../includes/components/jquery/jquery.js"></script>
	<script src="../../includes/components/bootstrap/js/bootstrap.bundle.js"></script>
    <script src="../../includes/components/bootstrap-select/dist/js/bootstrap-select.min.js"></script>

	<script type="text/javascript">
		$(function() {

			$(function() {
                $('[data-toggle="popover"]').popover({
                    html: true
                })
            });

            $('.popover-dismiss').popover({
                trigger: 'focus'
            });


			$.fn.selectpicker.Constructor.BootstrapVersion = '4';
            $('#issue_type').selectpicker({
				/* placeholder */
				title: "<?= TRANS('CHOOSE_TICKET_TYPE', '', 1); ?>",
				liveSearch: true,
				liveSearchNormalize: true,
				liveSearchPlaceholder: "<?= TRANS('BT_SEARCH', '', 1); ?>",
				noneResultsText: "<?= TRANS('NO_RECORDS_FOUND', '', 1); ?> {0}",
				style: "",
				styleBase: "form-control ",
			});

			$('#modalChooseTicketType').modal();
			
			$('#modalChooseTicketType').on('shown.bs.modal', function() {
				$('#issue_type').focus();
			});
			
			$('#modalChooseTicketType').on('hidden.bs.modal', function() {
				window.parent.history.back();
			});

			
			$('#issue_type').on('change', function(e) {
				e.preventDefault();
				var loading = $(".loading");
				$(document).ajaxStart(function() {
					loading.show();
				});
				$(document).ajaxStop(function() {
					loading.hide();
				});

				$.ajax({
					url: './choose_ticket_type_process.php',
					method: 'POST',
					data: {
						issue_type: $('#issue_type').val(),
						params: $('#params').val()
					},
					dataType: 'json',
				}).done(function(response) {

					if (!response.success) {
						$('#divResult').html(response.message);
						$('input, select, textarea').removeClass('is-invalid');
						if (response.field_id != "") {
							$('#' + response.field_id).focus().addClass('is-invalid');
						}
						// $("#idSubmit").prop("disabled", false);
					} else {
						$('#divResult').html('');
						$('input, select, textarea').removeClass('is-invalid');
						
						if (response.prob_descricao != "") {
							$("#prob_description").addClass("form-group col-md-12");
						} else {
							$("#prob_description").removeClass("form-group col-md-12");
							$("#prob_description").empty();
						}
						$('#prob_description').html(response.description);

						return true;
					}
				});
			})


			$('#continue').on('click', function(e) {
				e.preventDefault();
				var loading = $(".loading");
				$(document).ajaxStart(function() {
					loading.show();
				});
				$(document).ajaxStop(function() {
					loading.hide();
				});

				$.ajax({
					url: './choose_ticket_type_process.php',
					method: 'POST',
					data: {
						issue_type: $('#issue_type').val(),
						params: $('#params').val()
					},
					dataType: 'json',
				}).done(function(response) {

					if (!response.success) {
						$('#divResult').html(response.message);
						$('input, select, textarea').removeClass('is-invalid');
						if (response.field_id != "") {
							$('#' + response.field_id).focus().addClass('is-invalid');
						}
					} else {
						$('#divResult').html('');
						$('input, select, textarea').removeClass('is-invalid');
						
						// let params = 'issue_type=' + response.issue_type + '&profile_id=' + response.profile_id;
						let params = 'issue_type=' + response.issue_type + '&profile_id=' + response.profile_id + response.params;
						let url = "./ticket_add.php?" + params;
						
						$(location).prop('href', url);
						return true;
					}
				});
				return false;
			});



		});




	</script>
</body>

</html>