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

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" type="text/css" href="../../includes/css/estilos.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap/custom.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/css/switch_radio.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/components/fontawesome/css/all.min.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/components/datatables/datatables.min.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/css/my_datatables.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap-select/dist/css/bootstrap-select.min.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/css/my_bootstrap_select.css" />

	<title>OcoMon&nbsp;<?= VERSAO; ?></title>

	<style>
		.list-types-parted-of {
			line-height: 1.5em;
		}
	</style>
</head>

<body>
    
	<div class="container">
		<div id="idLoad" class="loading" style="display:none"></div>
	</div>

	<div id="divResult"></div>


	<div class="container-fluid">
		<h4 class="my-4"><i class="fas fa-box text-secondary"></i>&nbsp;<?= TRANS('ADM_EQUIP_TYPE'); ?></h4>
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
		
		

		$types = (isset($_GET['cod']) ? getAssetsTypes($conn, (int)$_GET['cod']) : getAssetsTypes($conn));
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
							<td class="line issue_type"><?= TRANS('COL_TYPE'); ?></td>
							<td class="line issue_type"><?= TRANS('CATEGORY'); ?></td>
							<td class="line issue_type"><?= TRANS('CAN_BE_PART_OF'); ?></td>
							<td class="line issue_type"><?= TRANS('CAN_BE_PARENT_OF'); ?></td>
							<td class="line issue_type"><?= TRANS('FIELD_PROFILE'); ?></td>
							<td class="line editar" width="10%"><?= TRANS('BT_EDIT'); ?></td>
							<td class="line remover" width="10%"><?= TRANS('BT_REMOVE'); ?></td>
						</tr>
					</thead>
					<tbody>
						<?php

						foreach ($types as $row) {

							$listPartOf = "";
							$arrayPartOf = getAssetsTypesPossibleParents($conn, $row['tipo_cod']);
							if (!empty($arrayPartOf)) {
								foreach ($arrayPartOf as $partOf) {
									$listPartOf .= '<li class="list-types-parted-of">'.$partOf["tipo_nome"].'</li>';
								}
							}
							$listParentOf = "";
							$arrayParentOf = getAssetsTypesPossibleChilds($conn, $row['tipo_cod']);
							if (!empty($arrayParentOf)) {
								foreach ($arrayParentOf as $parentOf) {
									$listParentOf .= '<li class="list-types-parted-of">'.$parentOf["tipo_nome"].'</li>';
								}
							}
							
						?>
							<tr>
								<td class="line"><?= $row['tipo_nome']; ?></td>
								<td class="line"><?= $row['cat_name']; ?></td>
								<td class="line"><?= $listPartOf; ?></td>
								<td class="line"><?= $listParentOf; ?></td>
								<td class="line"><?= $row['profile_name']; ?></td>
								<td class="line"><button type="button" class="btn btn-secondary btn-sm" onclick="redirect('<?= $_SERVER['PHP_SELF']; ?>?action=edit&cod=<?= $row['tipo_cod']; ?>')"><?= TRANS('BT_EDIT'); ?></button></td>
								<td class="line"><button type="button" class="btn btn-danger btn-sm" onclick="confirmDeleteModal('<?= $row['tipo_cod']; ?>')"><?= TRANS('REMOVE'); ?></button></td>
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
				<?= csrf_input('type_of_equipments'); ?>
				<div class="form-group row my-4">
					<label for="type_name" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_TYPE'); ?></label>
					<div class="form-group col-md-10">
						<input type="text" class="form-control " id="type_name" name="type_name" required />
                    </div>

					<label for="tipo_categoria" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CATEGORY'); ?></label>
					<div class="form-group col-md-10">
						<div class="input-group">
							<select class="form-control bs-select" id="tipo_categoria" name="tipo_categoria">
								<option value=""><?= TRANS('SEL_SELECT'); ?></option>
								<?php
									$categories = getAssetsCategories($conn);
									foreach ($categories as $cat) {
										?>
											<option value="<?= $cat['id']; ?>"
											><?= $cat['cat_name']; ?></option>
										<?php
									}
								?>
							</select>
							<div class="input-group-append">
								<div class="input-group-text manage_categories" data-location="assets_categories" data-params="action=new" title="<?= TRANS('ADD_CATEGORY'); ?>" data-placeholder="<?= TRANS('ADD_CATEGORY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
									<i class="fas fa-plus"></i>
								</div>
							</div>
						</div>
                    </div>

					
					<label for="is_part_of" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CAN_BE_PART_OF'); ?></label>
					<div class="form-group col-md-10">
						<select class="form-control bs-select-leave-blank" id="is_part_of" name="is_part_of[]" multiple="multiple">
							<?php
								$assetsTypes = getAssetsTypes($conn);
								foreach ($assetsTypes as $type) {
									?>
										<option value="<?= $type['tipo_cod']; ?>"
										><?= $type['tipo_nome']; ?></option>
									<?php
								}
							?>
						</select>
                    </div>

					<label for="has_parts_of" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CAN_HAVE_PARTS_OF'); ?></label>
					<div class="form-group col-md-10">
						<select class="form-control bs-select-leave-blank" id="has_parts_of" name="has_parts_of[]" multiple="multiple">
							<?php
								foreach ($assetsTypes as $type) {
									?>
										<option value="<?= $type['tipo_cod']; ?>"
										><?= $type['tipo_nome']; ?></option>
									<?php
								}
							?>
						</select>
                    </div>

					<label for="profile_id" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_PROFILE'); ?></label>
					<div class="form-group col-md-10">
						<select class="form-control bs-select-leave-blank" id="profile_id" name="profile_id">
							<?php
								$profiles = getAssetsProfiles($conn);
								foreach ($profiles as $profile) {
									?>
										<option value="<?= $profile['id']; ?>"
										><?= $profile['profile_name']; ?></option>
									<?php
								}
							?>
						</select>
                    </div>

                    

					<div class="row w-100"></div>
					<div class="form-group col-md-8 d-none d-md-block">
					</div>
					<div class="form-group col-12 col-md-2 ">

						<input type="hidden" name="action" id="action" value="new">
						<input type="hidden" name="tipo_categoria_selected" value="" id="tipo_categoria_selected" />
						<button type="submit" id="idSubmit" name="submit" class="btn btn-primary btn-block"><?= TRANS('BT_OK'); ?></button>
					</div>
					<div class="form-group col-12 col-md-2">
						<button type="reset" class="btn btn-secondary btn-block close-or-return"><?= TRANS('BT_CANCEL'); ?></button>
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
				<?= csrf_input('type_of_equipments'); ?>
				<div class="form-group row my-4">
					<label for="type_name" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_TYPE'); ?></label>
					<div class="form-group col-md-10">
						<input type="text" class="form-control " id="type_name" name="type_name" value="<?= $row['tipo_nome']; ?>" required />
                    </div>

					<label for="tipo_categoria" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CATEGORY'); ?></label>
					<div class="form-group col-md-10">
						<div class="input-group">
							<select class="form-control bs-select" id="tipo_categoria" name="tipo_categoria" value="<?= $row['tipo_nome']; ?>">
								<option value=""><?= TRANS('SEL_SELECT'); ?></option>
								<?php
									$categories = getAssetsCategories($conn);
									foreach ($categories as $cat) {
										?>
											<option value="<?= $cat['id']; ?>"
											<?= ($cat['id'] == $row['tipo_categoria'] ? " selected" : ""); ?>
											><?= $cat['cat_name']; ?></option>
										<?php
									}
								?>
							</select>
							<div class="input-group-append">
								<div class="input-group-text manage_categories" data-location="assets_categories" data-params="action=new" title="<?= TRANS('ADD_CATEGORY'); ?>" data-placeholder="<?= TRANS('ADD_CATEGORY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
									<i class="fas fa-plus"></i>
								</div>
							</div>
						</div>
                    </div>

					<label for="is_part_of" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CAN_BE_PART_OF'); ?></label>
					<div class="form-group col-md-10">
						<select class="form-control bs-select-leave-blank" id="is_part_of" name="is_part_of[]" multiple="multiple">
							<?php
								
								$arrayPartOf = getAssetsTypesPossibleParents($conn, $row['tipo_cod']);
								$assetsTypes = getAssetsTypes($conn);
								foreach ($assetsTypes as $type) {
									?>
										<option value="<?= $type['tipo_cod']; ?>"
										
										<?= (in_array($type['tipo_cod'], array_column($arrayPartOf, 'tipo_cod')) ? " selected" : ""); ?>
										
										><?= $type['tipo_nome']; ?></option>
									<?php
								}
							?>
						</select>
                    </div>

					<label for="has_parts_of" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CAN_HAVE_PARTS_OF'); ?></label>
					<div class="form-group col-md-10">
						<select class="form-control bs-select-leave-blank" id="has_parts_of" name="has_parts_of[]" multiple="multiple">
							<?php
								
								$arrayParentOf = getAssetsTypesPossibleChilds($conn, $row['tipo_cod']);
								foreach ($assetsTypes as $type) {
									?>
										<option value="<?= $type['tipo_cod']; ?>"
										<?= (in_array($type['tipo_cod'], array_column($arrayParentOf, 'tipo_cod')) ? " selected" : ""); ?>
										><?= $type['tipo_nome']; ?></option>
									<?php
								}
							?>
						</select>
                    </div>

					<label for="profile_id" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_PROFILE'); ?></label>
					<div class="form-group col-md-10">
						<select class="form-control bs-select-leave-blank" id="profile_id" name="profile_id">
							<?php
								$profiles = getAssetsProfiles($conn);
								foreach ($profiles as $profile) {
									?>
										<option value="<?= $profile['id']; ?>"
										<?= ($profile['id'] == $row['profile_id'] ? " selected" : ""); ?>
										><?= $profile['profile_name']; ?></option>
									<?php
								}
							?>
						</select>
                    </div>
                    

					<div class="row w-100"></div>
					<div class="form-group col-md-8 d-none d-md-block">
					</div>
					<div class="form-group col-12 col-md-2 ">
                        <input type="hidden" name="cod" value="<?= (int)$_GET['cod']; ?>">
						<input type="hidden" name="tipo_categoria_selected" value="<?= $row['tipo_categoria']; ?>" id="tipo_categoria_selected" />
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
	<script src="../../includes/components/bootstrap/js/bootstrap.bundle.js"></script>
    <script src="../../includes/components/bootstrap-select/dist/js/bootstrap-select.min.js"></script>
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

			$(function() {
				$('[data-toggle="popover"]').popover({
					html: true
				});
			});

			$('.popover-dismiss').popover({
				trigger: 'focus'
			});

			$('.bs-select').selectpicker({
				/* placeholder */
				title: "<?= TRANS('SEL_SELECT', '', 1); ?>",
				liveSearch: true,
				liveSearchNormalize: true,
				liveSearchPlaceholder: "<?= TRANS('BT_SEARCH', '', 1); ?>",
				noneResultsText: "<?= TRANS('NO_RECORDS_FOUND', '', 1); ?> {0}",
				style: "",
				styleBase: "form-control ",
			});

			$('.bs-select-leave-blank').selectpicker({
				/* placeholder */
				title: "<?= TRANS('NOT_DEFINE_NOW', '', 1); ?>",
				liveSearch: true,
				liveSearchNormalize: true,
				liveSearchPlaceholder: "<?= TRANS('BT_SEARCH', '', 1); ?>",
				noneResultsText: "<?= TRANS('NO_RECORDS_FOUND', '', 1); ?> {0}",
				style: "",
				styleBase: "form-control ",
			});

			closeOrReturn();

			$('.manage_categories').on('click', function() {
				loadInPopup($(this).attr('data-location'), $(this).attr('data-params'));
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
					url: './type_of_equipments_process.php',
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
							window.opener.loadAssetsTypes();
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



		function loadCategories(selected_id = '') {
			$.ajax({
				url: './get_assets_categories.php',
				method: 'POST',
				data: {
					cat_type: 1
				},
				dataType: 'json',
			}).done(function(response) {
				$('#tipo_categoria').empty().append('<option value=""><?= TRANS('SEL_TYPE'); ?></option>');
				for (var i in response) {

					var option = '<option value="' + response[i].id + '">' + response[i].cat_name + '</option>';
					$('#tipo_categoria').append(option);
					$('#tipo_categoria').selectpicker('refresh');


					if (selected_id !== '') {
						$('#tipo_categoria').val(selected_id).change();
					} else
					if ($('#tipo_categoria_selected').val() != '') {
						$('#tipo_categoria').val($('#tipo_categoria_selected').val()).change();
					}
				}
			});
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
				url: './type_of_equipments_process.php',
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

		function loadInPopup(pageBase, params) {
			let url = pageBase + '.php?' + params;
			x = window.open(url, '', 'dependent=yes,width=800,scrollbars=yes,statusbar=no,resizable=yes');
			x.moveTo(window.parent.screenX + 100, window.parent.screenY + 100);
		}

		function loadInModal(pageBase, params) {
			let url = pageBase + '.php?' + params;
			$("#divDetails").load(url);
			$('#modal').modal();
		}

		function closeOrReturn(jumps = 1) {
			buttonValue();
			$('.close-or-return').on('click', function () {
				
				if (isPopup()) {
					window.close();
				} else {
					window.history.back(jumps);
				}
			});

			$('#btReturnOrClose').on('click', function () {
				if (isPopup()) {
					window.close();
				} else {
					let url = '<?= $_SERVER['PHP_SELF']; ?>';
					window.location.href = url;
				}
			});
		}

		function buttonValue() {
			if (isPopup()) {
				$('.close-or-return, #btReturnOrClose').text('<?= TRANS('BT_CLOSE'); ?>');
			}
		}
	</script>
</body>

</html>