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

$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 2, 1);

$_SESSION['s_page_ocomon'] = $_SERVER['PHP_SELF'];


?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" type="text/css" href="../../includes/css/estilos.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/jquery/datetimepicker/jquery.datetimepicker.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap/custom.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/components/fontawesome/css/all.min.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/components/datatables/datatables.min.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/css/my_datatables.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap-select/dist/css/bootstrap-select.min.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/css/my_bootstrap_select.css" />

	<title>OcoMon&nbsp;<?= VERSAO; ?></title>

	<style>
		.table_lines {
            max-width: 150px !important;
            /* max-width: 15vw !important; */
        }
		.line-height {
            line-height: 1.5em;
        }
		
		hr.thick {
			border: 1px solid;
			border-radius: 5px;
		}
	</style>
</head>

<body>
	
	<div class="container">
		<div id="idLoad" class="loading" style="display:none"></div>
	</div>


	<div class="container-fluid">
		<h5 class="my-4"><i class="fas fa-database text-secondary"></i>&nbsp;<?= TRANS('TLT_CONS_SOLUT_PROB'); ?></h5>
		<div class="modal" id="modal" tabindex="-1" style="z-index:9001!important">
			<div class="modal-dialog modal-xl">
				<div class="modal-content">
					<div id="divDetails" style="position:relative">
                    <iframe id="ticketsInfo"  frameborder="0" style="position:absolute;top:0px;width:95%;height:100vh;"></iframe>
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


		<form method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form" onSubmit="return false;">
			<!-- onSubmit="return false;" -->
			<div class="form-group row my-4">

				<label for="data_inicial" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('START_DATE'); ?></label>
				<div class="form-group col-md-4">
					<input type="text" class="form-control " id="data_inicial" name="data_inicial" placeholder="<?= TRANS('PLACEHOLDER_START_DATE_PERIOD_SEARCH'); ?>" autocomplete="off" />
				</div>

				<label for="data_final" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('END_DATE'); ?></label>
				<div class="form-group col-md-4">
					<input type="text" class="form-control " id="data_final" name="data_final" placeholder="<?= TRANS('PLACEHOLDER_END_DATE_PERIOD_SEARCH'); ?>" autocomplete="off" />
				</div>
				<label for="problema" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('SEARCH_TERMS'); ?></label>
				<div class="form-group col-md-10">
					<textarea class="form-control " id="problema" name="problema" rows="4" required placeholder="<?= TRANS('HELPER_SEARCH_KNOWLEDGE_BASE'); ?>"></textarea>
					<small class="form-text text-muted">
						<?= TRANS('SEARCH_HELPER'); ?>.
					</small>
				</div>


				<label for="operador" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('TECHNICIAN'); ?></label>
				<div class="form-group col-md-4">
					<select class="form-control sel2" id="operador" name="operador">
						<option value="-1" selected><?= TRANS('OCO_SEL_OPERATOR'); ?></option>
						<?php

						$users = getUsers($conn, null, [1,2]);
						foreach ($users as $user) {
							?>
								<option value="<?= $user['user_id']; ?>"><?= $user['nome']; ?></option>
							<?php
						}
						?>
					</select>
				</div>


				<!-- <label class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CONSIDER'); ?></label> -->
				<div class="form-group col-md-6">
					<div class="form-check form-check-inline">
						<input class="form-check-input " type="checkbox" name="search_in_comments">
						<legend class="col-form-label col-form-label-sm"><?= TRANS('SEARCH_IN_COMMENTS'); ?></legend>
					</div>
					<div class="form-check form-check-inline">
						<input class="form-check-input " type="checkbox" name="anyword">
						<legend class="col-form-label col-form-label-sm"><?= TRANS('AT_LEAST_ONE_OF_THE_WORDS'); ?></legend>
					</div>
					<div class="form-check form-check-inline">
						<input class="form-check-input " type="checkbox" name="onlyImgs">
						<legend class="col-form-label col-form-label-sm"><?= TRANS('ONLY_TICKETS_WITH_ATTACHMENTS'); ?></legend>
					</div>

				</div>


				<div class="row w-100">
					<div class="form-group col-md-8 d-none d-md-block">
					</div>
					<div class="form-group col-12 col-md-2 ">
						<button type="submit" id="idSubmit" class="btn btn-primary btn-block"><?= TRANS('BT_OK'); ?></button>
					</div>
					<div class="form-group col-12 col-md-2">
						<button type="reset" class="btn btn-secondary btn-block" onClick="parent.history.back();"><?= TRANS('BT_CANCEL'); ?></button>
					</div>
				</div>


			</div>
		</form>

	</div>

	<div class="container-fluid">
		<div id="divResult">
		</div>
	</div>


	<script src="../../includes/javascript/funcoes-3.0.js"></script>
	<script src="../../includes/components/jquery/jquery.js"></script>
    <script src="../../includes/components/jquery/jquery.initialize.min.js"></script>
    <script type="text/javascript" charset="utf8" src="../../includes/components/datatables/datatables.js"></script>
    <script src="../../includes/components/jquery/datetimepicker/build/jquery.datetimepicker.full.min.js"></script>
	<script src="../../includes/components/bootstrap/js/bootstrap.bundle.js"></script>
	<script src="../../includes/components/bootstrap-select/dist/js/bootstrap-select.min.js"></script>
	<script>
		$(function() {
			
            /* Idioma global para os calendários */
            $.datetimepicker.setLocale('pt-BR');
            
            /* Calendários de início e fim do período */
            $('#data_inicial').datetimepicker({
                format: 'd/m/Y',
                onShow: function(ct) {
                    this.setOptions({
                        maxDate: $('#data_final').datetimepicker('getValue')
                    })
                },
                timepicker: false
            });
            $('#data_final').datetimepicker({
                format: 'd/m/Y',
                onShow: function(ct) {
                    this.setOptions({
                        minDate: $('#data_inicial').datetimepicker('getValue')
                    })
                },
                timepicker: false
            });


			var obsTable = $.initialize("#table_solutions", function() {
				var table = $('#table_solutions').DataTable({
					paging: true,
					deferRender: true,
					// order: [0, 'DESC'],
					columnDefs: [
						{
							searchable: false,
							orderable: false,
							targets: ['descricao', 'descricao_tech', 'solucao']
						},
						{
                            className: 'table_lines line-height',
                            targets: '_all'
                        },
					],
					"language": {
						"url": "../../includes/components/datatables/datatables.pt-br.json"
					}
				});
			}, {
                target: document.getElementById('divResult')
            });



			


			$.fn.selectpicker.Constructor.BootstrapVersion = '4';
			$('.sel2').selectpicker({
				/* placeholder */
				title: "<?= TRANS('SEL_SELECT', '', 1); ?>",
				liveSearch: true,
				liveSearchNormalize: true,
				liveSearchPlaceholder: "<?= TRANS('BT_SEARCH', '', 1); ?>",
				noneResultsText: "<?= TRANS('NO_RECORDS_FOUND', '', 1); ?> {0}",
				style: "",
				styleBase: "form-control input-select-multi",
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
					url: 'get_solutions_result.php',
					method: 'POST',
					data: $('#form').serialize(),
				}).done(function(response) {
					$('#divResult').html(response);
				});
				return false;
			});


		});

		function openTicketInfo(ticket) {

			let location = 'ticket_show.php?numero=' + ticket;
			// $("#divDetails").load(location);
			$("#ticketsInfo").attr('src',location)
			$('#modal').modal();
		}

	</script>
</body>

</html>