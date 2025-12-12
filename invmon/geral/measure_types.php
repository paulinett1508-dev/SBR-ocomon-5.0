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

/* Operações possíveis para o fator de comparação */
$operations = [
	TRANS('DIVIDE') => '/',
	TRANS('MULTIPLY') => '*'
	
];
$opSignal = [
	'/' => TRANS('DIVIDE'),
	'*' => TRANS('MULTIPLY'),
	'=' => TRANS('REFERENCE_BASE')
];


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
	<link rel="stylesheet" type="text/css" href="../../includes/components/datatables/datatables.min.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/css/my_datatables.css" />

	<title>OcoMon&nbsp;<?= VERSAO; ?></title>
</head>

<body>
    
	<div class="container">
		<div id="idLoad" class="loading" style="display:none"></div>
	</div>

	<div id="divResult"></div>


	<div class="container-fluid">
		<h4 class="my-4"><i class="fas fa-ruler-combined text-secondary"></i>&nbsp;<?= TRANS('MEASURE_TYPES'); ?></h4>
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
        
        
		$types = (isset($_GET['cod']) ? getMeasureTypes($conn, $_GET['cod']) : getMeasureTypes($conn));
		$registros = count($types);

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
							<td class="line issue_type"><?= TRANS('MEASURE_TYPE'); ?></td>
							<td class="line issue_type"><?= TRANS('DESCRIPTION'); ?></td>
							<td class="line issue_type"><?= TRANS('MEASURE_UNITS'); ?></td>
							<td class="line editar" width="10%"><?= TRANS('BT_EDIT'); ?></td>
							<td class="line remover" width="10%"><?= TRANS('BT_REMOVE'); ?></td>
						</tr>
					</thead>
					<tbody>
						<?php

						foreach ($types as $row) {
						    ?>
							<tr>
								<td class="line"><?= $row['mt_name']; ?></td>
								<td class="line"><?= $row['mt_description']; ?></td>
								<td class="line"><?= renderMeasureUnitsByType($conn, $row['id']); ?></td>
								<td class="line"><button type="button" class="btn btn-secondary btn-sm" onclick="redirect('<?= $_SERVER['PHP_SELF']; ?>?action=edit&cod=<?= $row['id']; ?>')"><?= TRANS('BT_EDIT'); ?></button></td>
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

			?>
			<h6><?= TRANS('NEW_RECORD'); ?></h6>
			<form method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form">
				<?= csrf_input('measure_types'); ?>
				<div class="form-group row my-4">
					<label for="mt_name" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('MEASURE_TYPE'); ?></label>
					<div class="form-group col-md-10">
						<input type="text" class="form-control " id="mt_name" name="mt_name" required />
                    </div>

                    <label for="mt_description" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('DESCRIPTION'); ?></label>
					<div class="form-group col-md-10">
					<textarea class="form-control" id="mt_description" name="mt_description" rows="3"></textarea>
                    </div>

					<label class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('MEASURE_UNIT'); ?></label>
					<div class="form-group col-md-3">
						<div class="field_wrapper_specs" id="field_wrapper_specs">
							<div class="input-group">
								<div class="input-group-prepend">
									<div class="input-group-text">
										<a href="javascript:void(0);" class="add_button_specs" title="<?= TRANS('ADD'); ?>"><i class="fa fa-plus"></i></a>
									</div>
								</div>
								<input type="text" class="form-control " id="unit_name" name="unit_name[]" required />
							</div>
							<small class="form-text text-muted"><?= TRANS('HELPER_UNIT_NAME'); ?></small>
						</div>
					</div>
					<div class="form-group col-md-2">
						<div class="field_wrapper_specs" id="field_wrapper_specs">
							<input type="text" class="form-control " id="unit_abbrev" name="unit_abbrev[]" required />
							<small class="form-text text-muted"><?= TRANS('HELPER_UNIT_ABBREV'); ?></small>
						</div>
					</div>
					<div class="form-group col-md-2">
						<div class="field_wrapper_specs" id="field_wrapper_specs">
							<input type="number" class="form-control " id="equity_factor" name="equity_factor[]" value="1" readonly />
							<small class="form-text text-muted"><?= TRANS('HELPER_EQUITY_FACTOR'); ?></small>
						</div>
					</div>
					<div class="form-group col-md-3">
						<div class="field_wrapper_specs" id="field_wrapper_specs">
							<select class="form-control " id="operation" name="operation[]">
								<option value="="><?= TRANS('REFERENCE_BASE'); ?></option>
								
							</select>
							<small class="form-text text-muted"><?= TRANS('OPERATION_TO_EQUITY'); ?></small>
						</div>
					</div>


				</div>

				
				<!-- Receberá cada uma das novas unidades de medida -->
				<div id="new_units" class="form-group row my-4 new_units"> <!-- new_specs -->
				</div>


				<div class="form-group row my-4">


					<div class="row w-100"></div>
					<div class="form-group col-md-8 d-none d-md-block">
					</div>
					<div class="form-group col-12 col-md-2 ">

						<input type="hidden" name="action" id="action" value="new">
						<button type="submit" id="idSubmit" name="submit" class="btn btn-primary btn-block"><?= TRANS('BT_OK'); ?></button>
					</div>
					<div class="form-group col-12 col-md-2">
						<button type="reset" class="btn btn-secondary btn-block close-or-return" ><?= TRANS('BT_CANCEL'); ?></button>
					</div>


				</div>
			</form>
		<?php
		} else

		if ((isset($_GET['action']) && $_GET['action'] == "edit") && empty($_POST['submit'])) {

			$row = $types;
		    ?>
			<h6><?= TRANS('BT_EDIT'); ?></h6>
			<form method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form">
				<?= csrf_input('measure_types'); ?>
				<div class="form-group row my-4">
					<label for="mt_name" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('MEASURE_TYPE'); ?></label>
					<div class="form-group col-md-10">
						<input type="text" class="form-control " id="mt_name" name="mt_name" value="<?= $row['mt_name']; ?>" required />
                    </div>
                    
                    <label for="mt_description" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('DESCRIPTION'); ?></label>
					<div class="form-group col-md-10">
						<textarea class="form-control" id="mt_description" name="mt_description" rows="3"><?= $row['mt_description']; ?></textarea>
                    </div>

					<label for="show_units" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('REGISTERED_MEASURE_UNITS'); ?></label>
					<div class="form-group col-md-10">
						<?= renderMeasureUnitsByType($conn, $row['id']); ?>
                    </div>
					
					<?php
						$measureUnits = getMeasureUnits($conn, null, $row['id']);
						if (count($measureUnits)) {
							$i = 0;
							?>
								<label class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('MEASURE_UNIT'); ?></label>
							<?php

							foreach ($measureUnits as $unit) {

								$editable = ($unit['equity_factor'] == 1 ? " readonly" : "");
								$disabled = ($unit['equity_factor'] == 1 ? " disabled" : "");

								if ($i != 0) {
									?>
										<label class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"></label>
									<?php
								}
								?>
									<input type="hidden" name="unit_id[]" value="<?= $unit['id']; ?>">
									<div class="form-group col-md-3">
										<div class="field_wrapper_specs" id="field_wrapper_specs">
												
											<input type="text" class="form-control " id="unit_name<?= $i; ?>" name="unit_name_update[]" value="<?= $unit['unit_name']; ?>" required />
										</div>
									</div>
									<div class="form-group col-md-2">
										<input type="text" class="form-control" name="unit_abbrev_update[]" id="unit_abbrev<?= $i; ?>" value="<?= $unit['unit_abbrev']; ?>"/>
									</div>
									<div class="form-group col-md-2">
										<input type="number" class="form-control" name="equity_factor_update[]" id="equity_factor<?= $i; ?>" value="<?= $unit['equity_factor']; ?>" <?= $editable; ?>/>
									</div>
									<div class="form-group col-md-2">

										<select class="form-control " id="operation<?= $i; ?>" name="operation_update[]">
										<?php

											if ($unit['equity_factor'] == 1) {
												?>
													<option value="="><?= TRANS('REFERENCE_BASE'); ?></option>
												<?php
											} else {
												foreach ($operations as $key => $operation) {
													?>
														<option value="<?= $operation; ?>"
														<?= ($operation == $unit['operation'] ? " selected" : ""); ?>
														><?= $key; ?></option>
													<?php
												}
											}
										?>
										</select>
									</div>
									<div class="form-group col-md-1">
										<input type="checkbox" name="delete_unit[]" value="<?= $unit['id']; ?>" <?= $disabled; ?>>&nbsp;<span class="align-top"><i class="fas fa-trash-alt text-danger" title="<?= TRANS('REMOVE'); ?>"></i></span>
									</div>
									<div class="w-100"></div>
								<?php
								$i++;
							}
						}
					?>

				<!-- Link para adicionar unidades -->
				<label class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('MEASURE_UNIT'); ?></label>
				<div class="form-group col-md-10">
					<a href="javascript:void(0);" class="add_button_specs" title="<?= TRANS('ADD'); ?>"><i class="fa fa-plus"></i></a>
                </div>


				</div>
				<!-- Receberá cada uma das novas unidades de medida -->
				<div id="new_units" class="form-group row my-4 new_units">
				</div>
				<div class="form-group row my-4">


					<div class="row w-100"></div>
					<div class="form-group col-md-8 d-none d-md-block">
					</div>
					<div class="form-group col-12 col-md-2 ">
                        <input type="hidden" name="cod" value="<?= $_GET['cod']; ?>">
                        <input type="hidden" name="action" id="action" value="edit">
						<button type="submit" id="idSubmit" name="submit" value="edit" class="btn btn-primary btn-block"><?= TRANS('BT_OK'); ?></button>
					</div>
					<div class="form-group col-12 col-md-2">
						<button type="reset" class="btn btn-secondary btn-block close-or-return" ><?= TRANS('BT_CANCEL'); ?></button>
					</div>
				</div>
			</form>
		<?php
		}
		?>
	</div>

	<script src="../../includes/javascript/funcoes-3.0.js"></script>
	<script src="../../includes/components/jquery/jquery.js"></script>
	<script src="../../includes/components/bootstrap/js/bootstrap.min.js"></script>
	<script type="text/javascript" charset="utf8" src="../../includes/components/datatables/datatables.js"></script>
	<script type="text/javascript">
		$(function() {

			$('#table_lists').DataTable({
				paging: true,
				deferRender: true,
				columnDefs: [{
					searchable: false,
					orderable: false,
					targets: ['editar', 'remover']
				}],
				"language": {
					"url": "../../includes/components/datatables/datatables.pt-br.json"
				}
			});

			closeOrReturn();

			$('.add_button_specs').on('click', function() {
				loadNewMeasureUnitField();
			});

			$('.new_units').on('click', '.remove_button_specs', function(e) {
                e.preventDefault();
				console.log('clicou em remover')
				dataRandom = $(this).attr('data-random');
				$("."+dataRandom).remove();
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
					url: './measure_types_process.php',
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

						if (isPopup()) {
							window.opener.loadMeasuresTypes();
						}

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



		function loadNewMeasureUnitField() {
			// if ($('#measure_type').length > 0) {
			if ($('#mt_name').length > 0) {
				var loading = $(".loading");
				$(document).ajaxStart(function() {
					loading.show();
				});
				$(document).ajaxStop(function() {
					loading.hide();
				});

				$.ajax({
					url: './render_new_measure_unit_field.php',
					method: 'POST',
					data: {
						// measure_type: $('#measure_type').val(),
						random: Math.random().toString(16).substr(2, 8)
					},
					// dataType: 'json',
				}).done(function(data) {
					$('#new_units').append(data);
				});
				return false;
			}
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
				url: './measure_types_process.php',
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
		}

		function closeOrReturn (jumps = 1) {
			buttonValue ();
			$('.close-or-return').on('click', function(){
				if (isPopup()) {
					window.close();
				} else {
					window.history.back(jumps);
				}
			});
		}

		function buttonValue () {
			if (isPopup()) {
				$('.close-or-return').text('<?= TRANS('BT_CLOSE'); ?>');
			}
		}
	</script>
</body>

</html>