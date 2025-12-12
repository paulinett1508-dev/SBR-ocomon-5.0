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

$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 2, 2);

$_SESSION['s_page_invmon'] = $_SERVER['PHP_SELF'];

$config = getConfig($conn);
$hasFiles = 0;

$type = "";


?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" type="text/css" href="../../includes/css/estilos.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap/custom.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/components/fontawesome/css/all.min.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/components/datatables/datatables.min.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/css/my_datatables.css" />

	<link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap-select/dist/css/bootstrap-select.min.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/css/my_bootstrap_select.css" />

	<title>OcoMon&nbsp;<?= VERSAO; ?></title>
</head>

<body>
    
	<div class="container">
		<div id="idLoad" class="loading" style="display:none"></div>
	</div>

	<div id="divResult"></div>


	<div class="container-fluid">
		<h4 class="my-4"><i class="fas fa-search text-secondary"></i>&nbsp;<?= TRANS('GET_ASSETS_BY_OLD_PLACE_OR_PERIOD'); ?></h4>
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

            
            <!-- <h6><?= TRANS('NEW_RECORD'); ?></h6> -->
			<form name="form" method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form" >
				<?= csrf_input(); ?>
				<div class="form-group row my-4">
                    
                    <label for="type" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_TYPE_EQUIP'); ?></label>
                    <div class="form-group col-md-10">
                        <select class="form-control sel2" id="type" name="type" required>
                            <option value=""><?= TRANS('OCO_SEL_ANY'); ?></option>
                            <?php
                            $sql = "SELECT * FROM tipo_equip ORDER BY tipo_nome";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                                ?>
                                <option value="<?= $rowType['tipo_cod']; ?>"
                                ><?= $rowType['tipo_nome']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
                    </div>


					<label for="client" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('FILTER_BY_CLIENT'); ?></label>
                    <div class="form-group col-md-10 ">
                        <select class="form-control sel2" id="client" name="client" required>
                            <option value=""><?= TRANS('SEL_SELECT'); ?></option>
							<?php
								$clients = getClients($conn, null, null, $_SESSION['s_allowed_clients']);
								foreach ($clients as $client){
									?>
										<option value="<?= $client['id']; ?>"
										><?= $client['nickname']; ?></option>
									<?php
								}
							?>
                        </select>
                    </div>
                    
                    <label for="asset_unit" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_UNIT'); ?></label>
                    <div class="form-group col-md-10">
                        <select class="form-control sel2" id="asset_unit" name="asset_unit" required>
                            <option value=""><?= TRANS('SEL_SELECT'); ?></option>
                            <?php
                            $units = getUnits($conn, null, null, null, $_SESSION['s_allowed_units']);
                            foreach ($units as $unit) {
                                ?>
                                <option data-subtext="<?= $unit['nickname']; ?>" value="<?= $unit['inst_cod']; ?>"><?= $unit['inst_nome']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
                    </div>

                    <label for="department" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_PREVIOUS_LOCAL'); ?></label>
                    <div class="form-group col-md-10">
                        <select class="form-control sel2" id="department" name="department" required>
                            <option value=""><?= TRANS('SEL_SELECT'); ?></option>
                            
                        </select>
                    </div>

					<div class="row w-100"></div>
					<div class="form-group col-md-8 d-none d-md-block">
					</div>
					<div class="form-group col-12 col-md-2 ">

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
    <script src="../../includes/components/jquery/jquery.initialize.min.js"></script>
	<script src="../../includes/components/bootstrap/js/bootstrap.bundle.js"></script>
	<script src="../../includes/components/bootstrap-select/dist/js/bootstrap-select.min.js"></script>
	<script type="text/javascript" charset="utf8" src="../../includes/components/datatables/datatables.js"></script>
	<script type="text/javascript">
		$(function() {

            $('.sel2').addClass('new-select2');

            $('.new-select2').selectpicker({
				/* placeholder */
				title: "<?= TRANS('SEL_SELECT', '', 1); ?>",
				liveSearch: true,
				liveSearchNormalize: true,
				liveSearchPlaceholder: "<?= TRANS('BT_SEARCH', '', 1); ?>",
				noneResultsText: "<?= TRANS('NO_RECORDS_FOUND', '', 1); ?> {0}",
				
				style: "",
				styleBase: "form-control input-select-multi",
			});

			$('#client').on('change', function(){
				loadUnits();
				loadDepartments();
			});
			$('#asset_unit').on('change', function(){
				loadDepartments();
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

                // var form = $('form').get(0);
				$("#idSubmit").prop("disabled", true);
				$.ajax({
					url: './search_by_previous_location_process.php',
					method: 'POST',
                    data: $('#form').serialize(),
                    // data: new FormData(form),
                    dataType: 'json',
                    
                    // cache: false,
				    // processData: false,
				    // contentType: false,
				}).done(function(response) {

					if (!response.success) {
						$('#divResult').html(response.message);
						$('input, select, textarea').removeClass('is-invalid');
						if (response.field_id != "") {
							$('#' + response.field_id).focus().addClass('is-invalid');
						}
						$("#idSubmit").prop("disabled", false);
					} else {
                        var redirect = 'show_equipment_by_previous_location.php';
                        $.redirectPost(redirect, {type: response.type, department: response.department});
					}
				});
				return false;
            });
 
            // jquery extended function
            $.extend({
                redirectPost: function(location, args)
                {
                    var form = $('<form></form>');
                    form.attr("method", "post");
                    form.attr("action", location);
                    $.each( args, function( key, value) {
                        var field = $('<input></input>');
                        field.attr("type", "hidden");
                        field.attr("name", key);
                        field.attr("value", value);

                        form.append(field);
                    });
                    $(form).appendTo('body').submit();
                }
            });
            
			$('#bt-cancel').on('click', function() {
				var url = '<?= $_SERVER['PHP_SELF'] ?>';
				$(location).prop('href', url);
			});
		});

		function loadUnits() {
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
					client: $("#client").val(),
					from_inventory: 1
				},
			}).done(function(data) {
				// let unitDb = $('#unitDb').val();
				$('#asset_unit').empty();
				if (Object.keys(data).length > 1) {
					$('#asset_unit').append('<option value=""><?= TRANS("SEL_SELECT"); ?></option>');
				}
				$.each(data, function(key, data) {
					$('#asset_unit').append('<option value="' + data.inst_cod + '">' + data.inst_nome + '</option>');
				});

				$('#asset_unit').selectpicker('refresh');
				// $('#unit').selectpicker('val', unitDb);
				
			});
		}


		function loadDepartments() {

            var loading = $(".loading");
            $(document).ajaxStart(function() {
                loading.show();
            });
            $(document).ajaxStop(function() {
                loading.hide();
            });

            $.ajax({
                url: '../../ocomon/geral/get_departments_by_client_unit.php',
                method: 'POST',
                dataType: 'json',
                data: {
                    client: $("#client").val(),
                    unit: $("#asset_unit").val()
                },
            }).done(function(data) {
                $('#department').empty();
                if (Object.keys(data).length > 1) {
                    $('#department').append('<option value=""><?= TRANS("SEL_SELECT"); ?></option>');
                }
                $.each(data, function(key, data) {

                    let unit = "";
                    if (data.unidade != null) {
                        unit = ' (' + data.unidade + ')';
                    }
                    $('#department').append('<option value="' + data.loc_id + '">' + data.local + unit + '</option>');
                });
                $('#department').selectpicker('refresh');
                if ($('#parent_id').val() != '') {
                    $('#department').selectpicker('val', $('#parent_department').val());
                    $('#department').selectpicker('refresh');
                }
                
            });
        }

	</script>
</body>

</html>