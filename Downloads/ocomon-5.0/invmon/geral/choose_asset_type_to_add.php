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
$_SESSION['s_page_invmon'] = $_SERVER['PHP_SELF'];


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
		li.list_specs {
			line-height: 1.5em;
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

		<div class="modal fade" id="modalChooseAssetType" data-backdrop="static" data-keyboard="false" tabindex="-1" style="z-index:2001!important" role="dialog" aria-labelledby="myModalChoose" aria-hidden="true">
        	<div class="modal-dialog modal-xl" role="document">
            	<div class="modal-content">
					<div id="divResult"></div>
					<div class="modal-header text-center bg-light">

						<h4 class="modal-title w-100 font-weight-bold text-secondary"><i class="fas fa-qrcode"></i>&nbsp;<?= TRANS('ASSET_REGISTER'); ?></h4>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
                
					<div class="row mx-2 mt-4">
								
						<div class="form-group col-sm-12 col-md-12">
							<div class="input-group">
								<select class="form-control bs-select" name="asset_type" id="asset_type">
									<?php
										$assetsTypes = getAssetsTypes($conn);
										foreach ($assetsTypes as $type) {
											?>
											<option value="<?= $type['tipo_cod']; ?>"><?= $type['tipo_nome']; ?></option>
											<?php
										}
									?>
								</select>
								<div class="input-group-append">
									<div class="input-group-text manage_popups" data-location="type_of_equipments" data-params="action=new" title="<?= TRANS('NEW'); ?>" data-placeholder="<?= TRANS('NEW'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
										<i class="fas fa-plus"></i>
									</div>
								</div>
							</div>
							<small class="form-text text-muted"><?= TRANS('HELPER_CHOOSE_ASSET_TYPE'); ?></small>
						</div>

						<div class="form-group col-sm-12 col-md-12">
							<div class="input-group">
								<select class="form-control bs-select" name="asset_manufacturer" id="asset_manufacturer">
									<?php
										$manufacturers = getManufacturers($conn, null, null);
										foreach ($manufacturers as $manufacturer) {
											?>
											<option value="<?= $manufacturer['fab_cod']; ?>"><?= $manufacturer['fab_nome']; ?></option>
											<?php
										}
									?>
								</select>
								<div class="input-group-append">
									<div class="input-group-text manage_popups" data-location="manufacturers" data-params="action=new" title="<?= TRANS('NEW'); ?>" data-placeholder="<?= TRANS('NEW'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
										<i class="fas fa-plus"></i>
									</div>
								</div>
							</div>
							<small class="form-text text-muted"><?= TRANS('SEL_MANUFACTURER'); ?></small>
						</div>


						<div class="form-group col-sm-12 col-md-12">
							<div class="input-group">
								<select class="form-control bs-select" name="asset_model" id="asset_model">
								</select>
								<div class="input-group-append">
									<div class="input-group-text manage_popups" data-location="equipments_models" data-params="action=new" title="<?= TRANS('NEW'); ?>" data-placeholder="<?= TRANS('NEW'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
										<i class="fas fa-plus"></i>
									</div>
								</div>
							</div>
							<small class="form-text text-muted"><?= TRANS('SEL_ASSET_MODEL'); ?></small>
						</div>



						
					</div>
					<!-- <div class="row mx-2 mt-4" id="model_specs"></div> -->
					<div class="form-group col-sm-12 col-md-12" id="model_specs"></div>


					

					<div class="modal-footer d-flex justify-content-end bg-light">
						<input type="hidden" name="tipo_selected" value="" id="tipo_selected" />
						<input type="hidden" name="manufacturer_selected" value="" id="manufacturer_selected" />


						<button class="btn btn-primary nowrap" id="continue" name="continue"><?= TRANS('CONTINUE'); ?></button>
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


			$.fn.selectpicker.Constructor.BootstrapVersion = '4';
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

			$('#modalChooseAssetType').modal();
			
			$('#modalChooseAssetType').on('shown.bs.modal', function() {
				$('#asset_type').focus();
			});
			
			$('#modalChooseAssetType').on('hidden.bs.modal', function() {
				window.parent.history.back();
			});

			$('.manage_popups').css('cursor', 'pointer').on('click', function() {
				var params = $(this).attr('data-params');
				var location = $(this).attr('data-location');
				if (location == 'equipments_models') {
					if ($('#asset_type').val() != '') {
						params += '&asset_type=' + $('#asset_type').val();
					}
					if ($('#asset_manufacturer').val() != '') {
						params += '&manufacturer=' + $('#asset_manufacturer').val();
					}
				}
				loadInPopup(location, params);
			});

			$('#asset_type, #asset_manufacturer').on('change', function(e){
				e.preventDefault();
				loadModelSpecs();
				showModelsByType();
			});


			$('#asset_model').on('change', function(e) {
				e.preventDefault();
				loadModelSpecs();
			});


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
					url: './choose_asset_type_to_add_process.php',
					method: 'POST',
					// data: $('#form').serialize(),
					data: {
						asset_type: $('#asset_type').val(),
						asset_manufacturer: $('#asset_manufacturer').val(),
						asset_model: $('#asset_model').val(),
						load_saved_config: ($('#load_saved_config').length > 0 && $('#load_saved_config').is(':checked') ? 1 : 0),
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

						let newParams = '';
						if (response.parent_id != "") {
							newParams = '&parent_id=' + response.parent_id;
						}

						if (response.load_saved_config == 1) {
							newParams += '&load_saved_config=1';
						}
						
						let params = 'asset_type=' + response.asset_type + '&asset_manufacturer=' + response.asset_manufacturer + '&asset_model=' + response.asset_model + '&profile_id=' + response.profile_id;
						let url = "./asset_add.php?" + params + newParams;
						
						$(location).prop('href', url);
						return true;
					}
				});
				return false;
			});



		});


		function loadAssetsTypes(selected_id = '') {
			$.ajax({
				url: './get_assets_types.php',
				method: 'POST',
				data: {
					cat_type: 1
				},
				dataType: 'json',
			}).done(function(response) {
				$('#asset_type').empty().append('<option value=""><?= TRANS('SEL_TYPE'); ?></option>');
				for (var i in response) {

					var option = '<option value="' + response[i].tipo_cod + '">' + response[i].tipo_nome + '</option>';
					$('#asset_type').append(option);
					$('#asset_type').selectpicker('refresh');


					if (selected_id !== '') {
						$('#asset_type').val(selected_id).change();
					} else
					if ($('#tipo_selected').val() != '') {
						$('#asset_type').val($('#tipo_selected').val()).change();
					}
				}
			});
		}

		function loadManufacturers(selected_id = '') {
			$.ajax({
				url: './get_manufacturers.php',
				method: 'POST',
				data: {
					cat_type: 1
				},
				dataType: 'json',
			}).done(function(response) {
				$('#asset_manufacturer').empty().append('<option value=""><?= TRANS('SEL_TYPE'); ?></option>');
				for (var i in response) {

					var option = '<option value="' + response[i].fab_cod + '">' + response[i].fab_nome + '</option>';
					$('#asset_manufacturer').append(option);
					$('#asset_manufacturer').selectpicker('refresh');


					if (selected_id !== '') {
						$('#asset_manufacturer').val(selected_id).change();
					} else
					if ($('#manufacturer_selected').val() != '') {
						$('#asset_manufacturer').val($('#manufacturer_selected').val()).change();
					}
				}
			});
		}


		/* Não mudar o nome da função pois é padrão para que a janela popup chame a função desse parent */
		function showModelsByType() {
            // e.preventDefault();
			var loading = $(".loading");
			$(document).ajaxStart(function() {
				loading.show();
			});
			$(document).ajaxStop(function() {
				loading.hide();
			});

			$.ajax({
				url: './get_asset_type_models.php',
				method: 'POST',
				data: {
					asset_type: $('#asset_type').val(),
					asset_manufacturer: $('#asset_manufacturer').val()
				},
				dataType: 'json',
			}).done(function(response) {

					/* Atualizar o campo de modelos de ativos */
					$('#divResult').html('');
					$('input, select, textarea').removeClass('is-invalid');
					
					$('#model_specs').empty();
					$('#asset_model').empty();
					$('#asset_model').selectpicker('refresh');
					if (Object.keys(response).length > 0) {

						$.each(response, function(key, data) {
							$('#asset_model').append('<option value="' + data.codigo + '">' + data.modelo + '</option>');
						});
						
						$('#asset_model').selectpicker('refresh');
						$('#asset_model').focus();
					}
					return true;
			});
        }


		function loadModelSpecs (){
			var loading = $(".loading");
			$(document).ajaxStart(function() {
				loading.show();
			});
			$(document).ajaxStop(function() {
				loading.hide();
			});

			$.ajax({
				url: './render_asset_model_specs.php',
				method: 'POST',
				data: {
					asset_type: $('#asset_type').val(),
					asset_manufacturer: $('#asset_manufacturer').val(),
					asset_model: $('#asset_model').val()
				},
				// dataType: 'json',
			}).done(function(response) {
				$('#model_specs').empty().html(response);
				return true;
			});
		}


		function reloadAssetModels(asset_type, field_to_update) {
            var loading = $(".loading");
            $(document).ajaxStart(function() {
                loading.show();
            });
            $(document).ajaxStop(function() {
                loading.hide();
            });

            $.ajax({
                url: './get_asset_type_models_with_specs.php',
                method: 'POST',
                data: {
                    asset_type: asset_type
                },
                dataType: 'json',
            }).done(function(data) {
                let html = '';

                if (data.length > 0) {
                    for (i in data) {
                        html += '<option data-subtext="' + data[i].spec + '" value="' + data[i].codigo + '">' + data[i].modelo + '</option>';
                        var field = data[i].field_id;
                    }
                }

                if (field_to_update != '') {
                    $('#' + field_to_update).html(html);
                    $('#' + field_to_update).selectpicker('refresh');
                } else {
                    // $('#'+field).empty().html(html);
                    // $('#'+field).selectpicker('refresh');
                    $('#asset_model').empty().html(html);
                    $('#asset_model').selectpicker('refresh');
                }
                
            });
            return false;
		}



		function loadInPopup(pageBase, params) {
			let url = pageBase + '.php?' + params;
			x = window.open(url, '', 'dependent=yes,width=800,scrollbars=yes,statusbar=no,resizable=yes');
			x.moveTo(window.parent.screenX + 100, window.parent.screenY + 100);
		}


	</script>
</body>

</html>