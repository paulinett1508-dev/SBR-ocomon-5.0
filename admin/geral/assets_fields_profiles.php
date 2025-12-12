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


$fields = [
	'asset_type' => TRANS('ASSET_TYPE'),
	'manufacturer' => TRANS('COL_MANUFACTURER'),
	'model' => TRANS('COL_MODEL'),
	'serial_number' => TRANS('SERIAL_NUMBER'),
	'part_number' => TRANS('COL_PARTNUMBER'),
	'department' => TRANS('DEPARTMENT'),
	'situation' => TRANS('STATE'),
	'net_name' => TRANS('NET_NAME'),
	'asset_unit' => TRANS('COL_UNIT'),
	'asset_tag' => TRANS('ASSET_TAG'),
	'invoice_number' => TRANS('INVOICE_NUMBER'),
	'cost_center' => TRANS('COST_CENTER'),
	'price' => TRANS('FIELD_PRICE'),
	'buy_date' => TRANS('PURCHASE_DATE'),
	'supplier' => TRANS('COL_VENDOR'),
	'assistance_type' => TRANS('ASSISTENCE'),
	'warranty_type' => TRANS('FIELD_TYPE_WARRANTY'),
	'warranty_time' => TRANS('WARRANTY_TIME'),
	'extra_info' => TRANS('ENTRY_TYPE_ADDITIONAL_INFO')
];


?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" type="text/css" href="../../includes/css/estilos.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/css/my_datatables.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/css/switch_radio.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap/custom.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/components/fontawesome/css/all.min.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/components/datatables/datatables.min.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap-select/dist/css/bootstrap-select.min.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/css/my_bootstrap_select.css" />

	<title>OcoMon&nbsp;<?= VERSAO; ?></title>
	<style>
		li.list_assets_types {
			line-height: 1.5em;
		}

		.container-switch {
			position: relative;
		}

		.switch-next-checkbox {
			position: absolute;
			top: 0;
			left: 140px;
			z-index: 1;
		}
	</style>
</head>

<body>

	<div class="container">
		<div id="idLoad" class="loading" style="display:none"></div>
	</div>

	<div id="divResult"></div>


	<div class="container-fluid">
		<h4 class="my-4"><i class="fas fa-chalkboard-teacher text-secondary"></i>&nbsp;<?= TRANS('ASSETS_FIELDS_PROFILES'); ?></h4>
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

        $profiles = (isset($_GET['cod']) && !empty($_GET['cod']) ? getAssetsProfiles($conn, noHtml($_GET['cod'])) : getAssetsProfiles($conn));

		$registros = count($profiles);

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
							<td class="line sigla"><?= TRANS('SCREEN_PROFILE_NAME'); ?></td>
							<td class="line sigla"><?= TRANS('APLIED_TO'); ?></td>
							<td class="line sigla"><?= TRANS('FIELDS_ENABLED'); ?></td>
							<td class="line editar"><?= TRANS('BT_EDIT'); ?></td>
							<td class="line remover"><?= TRANS('BT_REMOVE'); ?></td>
						</tr>
					</thead>
					<tbody>
						<?php

						foreach ($profiles as $row) {
                            
                            $appliedTo = "";

                            // $arrayAppliedTo = explode(',', $row['applied_to']);
                            // foreach ($arrayAppliedTo as $rowAppliedTo) {
							// 	$typeName = getAssetsTypes($conn, $rowAppliedTo)['tipo_nome'];
                            //     $appliedTo .= '<li class="list_assets_types">' . $typeName . '</li>';
                            // }


							$typesInProfile = getAssetsTypes($conn, null, null, null, $row['id']);
							foreach ($typesInProfile as $rowAppliedTo) {
								$typeName = $rowAppliedTo['tipo_nome'];
                                $appliedTo .= '<li class="list_assets_types">' . $typeName . '</li>';
                            }


                            /* Campos básicos habilitados */
							$fieldsInProfile = [];
							$specsFieldsInProfile = [];
							$customFieldsInProfile = [];
							foreach ($fields as $tableField => $label) {
								if ($row[$tableField]) {
									$fieldsInProfile[] = $label;
								}
							}

                            /* Campos de especificação */
                            if ($row['field_specs_ids']) {
                                $fields_specs_id = explode(',', $row['field_specs_ids']);

								$specs_fields = getAssetsTypes($conn, null, 2);
								foreach ($specs_fields as $specs_field) {
									if (in_array($specs_field['tipo_cod'], $fields_specs_id)) {
										$specsFieldsInProfile[] = $specs_field['tipo_nome'];
									}
								}
                            }

                            /* Campos personalizados */
							if ($row['field_custom_ids']) {
								$fields_id = explode(',', $row['field_custom_ids']);

								$custom_fields = getCustomFields($conn, null, 'equipamentos');
								foreach ($custom_fields as $cfield) {
									if (in_array($cfield['id'], $fields_id)) {
										$customFieldsInProfile[] = $cfield['field_label'];
									}
								}
							}
                            
                            
                            ?>
                                <tr>
                                    <td class="line"><?= $row['profile_name']; ?></td>
                                    <td class="line"><?= $appliedTo; ?></td>
                                    <td class="line">
                                        <?= strToTags(implode(",", $fieldsInProfile), 4, 'success'); ?>
                                        <?= strToTags(implode(",", $specsFieldsInProfile), 4, 'secondary'); ?>
                                        <?= strToTags(implode(",", $customFieldsInProfile), 4, 'info', 'custom-fields-link'); ?>
                                    </td>
                                    <td class="line"><button type="button" class="btn btn-secondary btn-sm" onclick="redirect('<?= $_SERVER['PHP_SELF']; ?>?action=edit&cod=<?= $row['id']; ?>&cellStyle=true')"><?= TRANS('BT_EDIT'); ?></button></td>
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
				<?= csrf_input(); ?>
				<div class="form-group row my-4">

					<label for="profile_name" class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('SCREEN_PROFILE_NAME'); ?></label>
					<div class="form-group col-md-9">
						<input type="text" class="form-control " id="profile_name" name="profile_name" required />
					</div>


					<label for="applied_to" class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('APLIED_TO'); ?></label>
					<div class="form-group col-md-9">
						<select class="form-control bs-select" id="applied_to" name="applied_to[]" multiple="multiple">
							<?php
								$assetsTypes = getAssetsTypes($conn, null, null, null, null, false);
								foreach ($assetsTypes as $type) {
									?>
										<option value="<?= $type['tipo_cod']; ?>"><?= $type['tipo_nome']; ?></option>
									<?php
								}
							?>
						</select>
					</div>

					
					<div class="form-group col-md-12">
						<p class="h6 text-center font-weight-bold mt-4"><?= TRANS('AVAILABLE_FIELDS_FOR_RECORD'); ?></p>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('ASSET_TYPE'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<input type="radio" id="field_asset_type" name="field_asset_type" value="yes" checked disabled/>
						<label for="field_asset_type"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_asset_type_no" name="field_asset_type" value="no" disabled/>
						<label for="field_asset_type_no"><?= TRANS('NOT'); ?></label>
						
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_MANUFACTURER'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<input type="radio" id="field_manufacturer" name="field_manufacturer" value="yes" checked disabled/>
						<label for="field_manufacturer"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_manufacturer_no" name="field_manufacturer" value="no" disabled/>
						<label for="field_manufacturer_no"><?= TRANS('NOT'); ?></label>
					</div>

                    <label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_MODEL'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<input type="radio" id="field_model" name="field_model" value="yes" checked disabled/>
						<label for="field_model"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_model_no" name="field_model" value="no" disabled/>
						<label for="field_model_no"><?= TRANS('NOT'); ?></label>
					</div>


                    <label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_UNIT'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<input type="radio" id="field_asset_unit" name="field_asset_unit" value="yes" checked disabled/>
						<label for="field_asset_unit"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_asset_unit_no" name="field_asset_unit" value="no" disabled/>
						<label for="field_asset_unit_no"><?= TRANS('NOT'); ?></label>
						
					</div>

                    <label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('DEPARTMENT'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<input type="radio" id="field_department" name="field_department" value="yes" checked disabled/>
						<label for="field_department"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_department_no" name="field_department" value="no" disabled/>
						<label for="field_department_no"><?= TRANS('NOT'); ?></label>
					</div>

                    <label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('ASSET_TAG'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<input type="radio" id="field_asset_tag" name="field_asset_tag" value="yes" checked disabled/>
						<label for="field_asset_tag"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_asset_tag_no" name="field_asset_tag" value="no" disabled/>
						<label for="field_asset_tag_no"><?= TRANS('NOT'); ?></label>
					</div>


                    <label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('SERIAL_NUMBER'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<input type="radio" id="field_serial_number" name="field_serial_number" value="yes" checked />
						<label for="field_serial_number"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_serial_number_no" name="field_serial_number" value="no" />
						<label for="field_serial_number_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<input type="checkbox" class="" name="field_serial_number_required" id="field_serial_number_required">
							<small class=" text-danger" ><i class="fas fa-asterisk"></i></small>
						</div>
					</div>

                    <label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_PARTNUMBER'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<input type="radio" id="field_part_number" name="field_part_number" value="yes" checked />
						<label for="field_part_number"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_part_number_no" name="field_part_number" value="no" />
						<label for="field_part_number_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<input type="checkbox" class="" name="field_part_number_required" id="field_part_number_required">
							<small class=" text-danger" ><i class="fas fa-asterisk"></i></small>
						</div>
					</div>

                    

                    <label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('STATE'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<input type="radio" id="field_situation" name="field_situation" value="yes" checked />
						<label for="field_situation"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_situation_no" name="field_situation" value="no" />
						<label for="field_situation_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<input type="checkbox" class="" name="field_situation_required" id="field_situation_required">
							<small class=" text-danger" ><i class="fas fa-asterisk"></i></small>
						</div>
					</div>

                    <label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('NET_NAME'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<input type="radio" id="field_net_name" name="field_net_name" value="yes" checked />
						<label for="field_net_name"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_net_name_no" name="field_net_name" value="no" />
						<label for="field_net_name_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<input type="checkbox" class="" name="field_net_name_required" id="field_net_name_required">
							<small class=" text-danger" ><i class="fas fa-asterisk"></i></small>
						</div>
					</div>

                    

                    <label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('INVOICE_NUMBER'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<input type="radio" id="field_invoice_number" name="field_invoice_number" value="yes" checked />
						<label for="field_invoice_number"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_invoice_number_no" name="field_invoice_number" value="no" />
						<label for="field_invoice_number_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<input type="checkbox" class="" name="field_invoice_number_required" id="field_invoice_number_required">
							<small class=" text-danger" ><i class="fas fa-asterisk"></i></small>
						</div>
					</div>

                    <label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('COST_CENTER'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<input type="radio" id="field_cost_center" name="field_cost_center" value="yes" checked />
						<label for="field_cost_center"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_cost_center_no" name="field_cost_center" value="no" />
						<label for="field_cost_center_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<input type="checkbox" class="" name="field_cost_center_required" id="field_cost_center_required">
							<small class=" text-danger" ><i class="fas fa-asterisk"></i></small>
						</div>
					</div>

                    <label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_PRICE'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<input type="radio" id="field_price" name="field_price" value="yes" checked />
						<label for="field_price"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_price_no" name="field_price" value="no" />
						<label for="field_price_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<input type="checkbox" class="" name="field_price_required" id="field_price_required">
							<small class=" text-danger" ><i class="fas fa-asterisk"></i></small>
						</div>
					</div>

                    <label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('PURCHASE_DATE'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<input type="radio" id="field_buy_date" name="field_buy_date" value="yes" checked />
						<label for="field_buy_date"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_buy_date_no" name="field_buy_date" value="no" />
						<label for="field_buy_date_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<input type="checkbox" class="" name="field_buy_date_required" id="field_buy_date_required">
							<small class=" text-danger" ><i class="fas fa-asterisk"></i></small>
						</div>
					</div>

                    <label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_VENDOR'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<input type="radio" id="field_supplier" name="field_supplier" value="yes" checked />
						<label for="field_supplier"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_supplier_no" name="field_supplier" value="no" />
						<label for="field_supplier_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<input type="checkbox" class="" name="field_supplier_required" id="field_supplier_required">
							<small class=" text-danger" ><i class="fas fa-asterisk"></i></small>
						</div>
					</div>

                    <label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('ASSISTENCE'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<input type="radio" id="field_assistance_type" name="field_assistance_type" value="yes" checked />
						<label for="field_assistance_type"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_assistance_type_no" name="field_assistance_type" value="no" />
						<label for="field_assistance_type_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<input type="checkbox" class="" name="field_assistance_type_required" id="field_assistance_type_required">
							<small class=" text-danger" ><i class="fas fa-asterisk"></i></small>
						</div>
					</div>

                    <label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_TYPE_WARRANTY'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<input type="radio" id="field_warranty_type" name="field_warranty_type" value="yes" checked />
						<label for="field_warranty_type"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_warranty_type_no" name="field_warranty_type" value="no" />
						<label for="field_warranty_type_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<input type="checkbox" class="" name="field_warranty_type_required" id="field_warranty_type_required">
							<small class=" text-danger" ><i class="fas fa-asterisk"></i></small>
						</div>
					</div>

                    <label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('WARRANTY_TIME'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<input type="radio" id="field_warranty_time" name="field_warranty_time" value="yes" checked />
						<label for="field_warranty_time"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_warranty_time_no" name="field_warranty_time" value="no" />
						<label for="field_warranty_time_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<input type="checkbox" class="" name="field_warranty_time_required" id="field_warranty_time_required">
							<small class=" text-danger" ><i class="fas fa-asterisk"></i></small>
						</div>
					</div>

                    <label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('ENTRY_TYPE_ADDITIONAL_INFO'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<input type="radio" id="field_extra_info" name="field_extra_info" value="yes" checked />
						<label for="field_extra_info"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_extra_info_no" name="field_extra_info" value="no" />
						<label for="field_extra_info_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<input type="checkbox" class="" name="field_extra_info_required" id="field_extra_info_required">
							<small class=" text-danger" ><i class="fas fa-asterisk"></i></small>
						</div>
					</div>




				</div>
				<!-- Seção para exibir os campos de especificação - 
				baseados na seleção de tipos de ativos que utilizarão o perfil -->
				<div id="specs_fields" class="form-group row my-4 specs_fields"></div>

				<div class="form-group row my-4">

					<?php
					/* Campos personalizados */
					$custom_fields = getCustomFields($conn, null, 'equipamentos', null, null);

					if (count($custom_fields)) {
					?>
						<div class="w-100">
							<p class="h6 text-center font-weight-bold mt-4"><?= TRANS('EXTRA_FIELDS'); ?></p>
						</div>
						<?php
						foreach ($custom_fields as $field) {
							$disabled = (!$field['field_active'] ? " disabled" : "");
						?>
							<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= $field['field_label']; ?></label>
							<div class="form-group col-md-3 switch-field container-switch">

								<input type="radio" id="<?= $field["field_name"]; ?>" name="<?= $field["field_name"]; ?>" value="yes" <?= $disabled; ?> />
								<label for="<?= $field["field_name"]; ?>"><?= TRANS('YES'); ?></label>
								<input type="radio" id="<?= $field["field_name"]; ?>_no" name="<?= $field["field_name"]; ?>" value="no" checked <?= $disabled; ?> />
								<label for="<?= $field["field_name"]; ?>_no"><?= TRANS('NOT'); ?></label>
							</div>
					<?php
						}
					}
					/* Fim do trecho referente aos campos personalizados */
					?>


					<div class="row w-100"></div>
					<div class="form-group col-md-8 d-none d-md-block">
					</div>
					<div class="form-group col-12 col-md-2 ">

						<input type="hidden" name="action" id="action" value="new">
						<input type="hidden" name="cod" id="cod" value="">
						<button type="submit" id="idSubmit" name="submit" class="btn btn-primary btn-block"><?= TRANS('BT_OK'); ?></button>
					</div>
					<div class="form-group col-12 col-md-2">
						<button type="reset" class="btn btn-secondary btn-block" onClick="parent.history.back();"><?= TRANS('BT_CANCEL'); ?></button>
					</div>


				</div>
			</form>
		<?php
		} else

		if ((isset($_GET['action']) && $_GET['action'] == "edit") && empty($_POST['submit'])) {

			$row = $profiles;

			/* Recebe os valores de obrigatorieda para cada campo onde se aplica */
			$required_fields = getAssetsRequiredInfo($conn, $row['id']);

		?>
			<h6><?= TRANS('BT_EDIT'); ?></h6>
			<form method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form">
				<?= csrf_input(); ?>
				<div class="form-group row my-4">



					<label for="profile_name" class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('SCREEN_PROFILE_NAME'); ?></label>
					<div class="form-group col-md-9">
						<input type="text" class="form-control " id="profile_name" name="profile_name" required value="<?= $row['profile_name']; ?>"/>
					</div>


					<label for="applied_to" class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('APLIED_TO'); ?></label>
					<div class="form-group col-md-9">
						<select class="form-control bs-select" id="applied_to" name="applied_to[]" multiple="multiple">
							<?php
								$arrayAppliedTo = explode(',', $row['applied_to']);
								// $assetsTypes = getAssetsTypes($conn, null, null, null, 0);
								$assetsTypes = getAssetsTypes($conn, null, null, null, $row['id'], false);
								$typesInProfile = getAssetsTypesByProfile($conn, $row['id']);
								
								foreach ($assetsTypes as $type) {
									?>
										<option value="<?= $type['tipo_cod']; ?>"
										
										<?php
											if (!empty($typesInProfile)) {
												foreach ($typesInProfile as $typeInProfile) {
													if ($typeInProfile['asset_type_id'] == $type['tipo_cod']) {
														echo "selected";
														break;
													}
												}
											}
										?>
										
										><?= $type['tipo_nome']; ?></option>
									<?php
								}
							?>
						</select>
					</div>


					<div class="form-group col-md-12">
						<p class="h6 text-center font-weight-bold mt-4"><?= TRANS('AVAILABLE_FIELDS_FOR_RECORD'); ?></p>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('ASSET_TYPE'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<input type="radio" id="field_asset_type" name="field_asset_type" value="yes" checked disabled/>
						<label for="field_asset_type"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_asset_type_no" name="field_asset_type" value="no" disabled/>
						<label for="field_asset_type_no"><?= TRANS('NOT'); ?></label>
						
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_MANUFACTURER'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<input type="radio" id="field_manufacturer" name="field_manufacturer" value="yes" checked disabled/>
						<label for="field_manufacturer"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_manufacturer_no" name="field_manufacturer" value="no" disabled/>
						<label for="field_manufacturer_no"><?= TRANS('NOT'); ?></label>
					</div>

                    <label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_MODEL'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<input type="radio" id="field_model" name="field_model" value="yes" checked disabled/>
						<label for="field_model"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_model_no" name="field_model" value="no" disabled/>
						<label for="field_model_no"><?= TRANS('NOT'); ?></label>
					</div>


                    <label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_UNIT'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<input type="radio" id="field_asset_unit" name="field_asset_unit" value="yes" checked disabled/>
						<label for="field_asset_unit"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_asset_unit_no" name="field_asset_unit" value="no" disabled/>
						<label for="field_asset_unit_no"><?= TRANS('NOT'); ?></label>
						
					</div>

                    <label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('DEPARTMENT'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<input type="radio" id="field_department" name="field_department" value="yes" checked disabled/>
						<label for="field_department"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_department_no" name="field_department" value="no" disabled/>
						<label for="field_department_no"><?= TRANS('NOT'); ?></label>
					</div>

                    <label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('ASSET_TAG'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<input type="radio" id="field_asset_tag" name="field_asset_tag" value="yes" checked disabled/>
						<label for="field_asset_tag"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_asset_tag_no" name="field_asset_tag" value="no" disabled/>
						<label for="field_asset_tag_no"><?= TRANS('NOT'); ?></label>
					</div>


					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('SERIAL_NUMBER'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<?php
							$yesChecked = ($row['serial_number'] == 1 ? "checked" : "");
							$noChecked = ($row['serial_number'] == 0 ? "checked" : "");
						?>
						<input type="radio" id="field_serial_number" name="field_serial_number" value="yes" <?= $yesChecked; ?> />
						<label for="field_serial_number"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_serial_number_no" name="field_serial_number" value="no" <?= $noChecked; ?>/>
						<label for="field_serial_number_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<?php
							$required = (count($required_fields) && key_exists('serial_number', $required_fields) && $required_fields['serial_number'] ? " checked" : "");
							?>
							<input type="checkbox" class="" name="field_serial_number_required" id="field_serial_number_required" <?= $required; ?>>
							<small class=" text-danger" ><i class="fas fa-asterisk"></i></small>
						</div>
					</div>

                    <label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_PARTNUMBER'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<?php
							$yesChecked = ($row['part_number'] == 1 ? "checked" : "");
							$noChecked = ($row['part_number'] == 0 ? "checked" : "");
						?>
						<input type="radio" id="field_part_number" name="field_part_number" value="yes" <?= $yesChecked; ?> />
						<label for="field_part_number"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_part_number_no" name="field_part_number" value="no" <?= $noChecked; ?>/>
						<label for="field_part_number_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<?php
							$required = (count($required_fields) && key_exists('part_number', $required_fields) && $required_fields['part_number'] ? " checked" : "");
							?>
							<input type="checkbox" class="" name="field_part_number_required" id="field_part_number_required" <?= $required; ?>>
							<small class=" text-danger" ><i class="fas fa-asterisk"></i></small>
						</div>
					</div>


					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('STATE'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<?php
							$yesChecked = ($row['situation'] == 1 ? "checked" : "");
							$noChecked = ($row['situation'] == 0 ? "checked" : "");
						?>
						<input type="radio" id="field_situation" name="field_situation" value="yes" <?= $yesChecked; ?> />
						<label for="field_situation"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_situation_no" name="field_situation" value="no" <?= $noChecked; ?>/>
						<label for="field_situation_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<?php
							$required = (count($required_fields) && key_exists('situation', $required_fields) && $required_fields['situation'] ? " checked" : "");
							?>
							<input type="checkbox" class="" name="field_situation_required" id="field_situation_required" <?= $required; ?>>
							<small class=" text-danger" ><i class="fas fa-asterisk"></i></small>
						</div>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('NET_NAME'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<?php
							$yesChecked = ($row['net_name'] == 1 ? "checked" : "");
							$noChecked = ($row['net_name'] == 0 ? "checked" : "");
						?>
						<input type="radio" id="field_net_name" name="field_net_name" value="yes" <?= $yesChecked; ?> />
						<label for="field_net_name"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_net_name_no" name="field_net_name" value="no" <?= $noChecked; ?>/>
						<label for="field_net_name_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<?php
							$required = (count($required_fields) && key_exists('net_name', $required_fields) && $required_fields['net_name'] ? " checked" : "");
							?>
							<input type="checkbox" class="" name="field_net_name_required" id="field_net_name_required" <?= $required; ?>>
							<small class=" text-danger" ><i class="fas fa-asterisk"></i></small>
						</div>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('INVOICE_NUMBER'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<?php
							$yesChecked = ($row['invoice_number'] == 1 ? "checked" : "");
							$noChecked = ($row['invoice_number'] == 0 ? "checked" : "");
						?>
						<input type="radio" id="field_invoice_number" name="field_invoice_number" value="yes" <?= $yesChecked; ?> />
						<label for="field_invoice_number"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_invoice_number_no" name="field_invoice_number" value="no" <?= $noChecked; ?>/>
						<label for="field_invoice_number_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<?php
							$required = (count($required_fields) && key_exists('invoice_number', $required_fields) && $required_fields['invoice_number'] ? " checked" : "");
							?>
							<input type="checkbox" class="" name="field_invoice_number_required" id="field_invoice_number_required" <?= $required; ?>>
							<small class=" text-danger" ><i class="fas fa-asterisk"></i></small>
						</div>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('COST_CENTER'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<?php
							$yesChecked = ($row['cost_center'] == 1 ? "checked" : "");
							$noChecked = ($row['cost_center'] == 0 ? "checked" : "");
						?>
						<input type="radio" id="field_cost_center" name="field_cost_center" value="yes" <?= $yesChecked; ?> />
						<label for="field_cost_center"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_cost_center_no" name="field_cost_center" value="no" <?= $noChecked; ?>/>
						<label for="field_cost_center_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<?php
							$required = (count($required_fields) && key_exists('cost_center', $required_fields) && $required_fields['cost_center'] ? " checked" : "");
							?>
							<input type="checkbox" class="" name="field_cost_center_required" id="field_cost_center_required" <?= $required; ?>>
							<small class=" text-danger" ><i class="fas fa-asterisk"></i></small>
						</div>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_PRICE'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<?php
							$yesChecked = ($row['price'] == 1 ? "checked" : "");
							$noChecked = ($row['price'] == 0 ? "checked" : "");
						?>
						<input type="radio" id="field_price" name="field_price" value="yes" <?= $yesChecked; ?> />
						<label for="field_price"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_price_no" name="field_price" value="no" <?= $noChecked; ?>/>
						<label for="field_price_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<?php
							$required = (count($required_fields) && key_exists('price', $required_fields) && $required_fields['price'] ? " checked" : "");
							?>
							<input type="checkbox" class="" name="field_price_required" id="field_price_required" <?= $required; ?>>
							<small class=" text-danger" ><i class="fas fa-asterisk"></i></small>
						</div>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('PURCHASE_DATE'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<?php
							$yesChecked = ($row['buy_date'] == 1 ? "checked" : "");
							$noChecked = ($row['buy_date'] == 0 ? "checked" : "");
						?>
						<input type="radio" id="field_buy_date" name="field_buy_date" value="yes" <?= $yesChecked; ?> />
						<label for="field_buy_date"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_buy_date_no" name="field_buy_date" value="no" <?= $noChecked; ?>/>
						<label for="field_buy_date_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<?php
							$required = (count($required_fields) && key_exists('buy_date', $required_fields) && $required_fields['buy_date'] ? " checked" : "");
							?>
							<input type="checkbox" class="" name="field_buy_date_required" id="field_buy_date_required" <?= $required; ?>>
							<small class=" text-danger" ><i class="fas fa-asterisk"></i></small>
						</div>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_VENDOR'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<?php
							$yesChecked = ($row['supplier'] == 1 ? "checked" : "");
							$noChecked = ($row['supplier'] == 0 ? "checked" : "");
						?>
						<input type="radio" id="field_supplier" name="field_supplier" value="yes" <?= $yesChecked; ?> />
						<label for="field_supplier"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_supplier_no" name="field_supplier" value="no" <?= $noChecked; ?>/>
						<label for="field_supplier_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<?php
							$required = (count($required_fields) && key_exists('supplier', $required_fields) && $required_fields['supplier'] ? " checked" : "");
							?>
							<input type="checkbox" class="" name="field_supplier_required" id="field_supplier_required" <?= $required; ?>>
							<small class=" text-danger" ><i class="fas fa-asterisk"></i></small>
						</div>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('ASSISTENCE'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<?php
							$yesChecked = ($row['assistance_type'] == 1 ? "checked" : "");
							$noChecked = ($row['assistance_type'] == 0 ? "checked" : "");
						?>
						<input type="radio" id="field_assistance_type" name="field_assistance_type" value="yes" <?= $yesChecked; ?> />
						<label for="field_assistance_type"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_assistance_type_no" name="field_assistance_type" value="no" <?= $noChecked; ?>/>
						<label for="field_assistance_type_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<?php
							$required = (count($required_fields) && key_exists('assistance_type', $required_fields) && $required_fields['assistance_type'] ? " checked" : "");
							?>
							<input type="checkbox" class="" name="field_assistance_type_required" id="field_assistance_type_required" <?= $required; ?>>
							<small class=" text-danger" ><i class="fas fa-asterisk"></i></small>
						</div>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_TYPE_WARRANTY'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<?php
							$yesChecked = ($row['warranty_type'] == 1 ? "checked" : "");
							$noChecked = ($row['warranty_type'] == 0 ? "checked" : "");
						?>
						<input type="radio" id="field_warranty_type" name="field_warranty_type" value="yes" <?= $yesChecked; ?> />
						<label for="field_warranty_type"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_warranty_type_no" name="field_warranty_type" value="no" <?= $noChecked; ?>/>
						<label for="field_warranty_type_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<?php
							$required = (count($required_fields) && key_exists('warranty_type', $required_fields) && $required_fields['warranty_type'] ? " checked" : "");
							?>
							<input type="checkbox" class="" name="field_warranty_type_required" id="field_warranty_type_required" <?= $required; ?>>
							<small class=" text-danger" ><i class="fas fa-asterisk"></i></small>
						</div>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('WARRANTY_TIME'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<?php
							$yesChecked = ($row['warranty_time'] == 1 ? "checked" : "");
							$noChecked = ($row['warranty_time'] == 0 ? "checked" : "");
						?>
						<input type="radio" id="field_warranty_time" name="field_warranty_time" value="yes" <?= $yesChecked; ?> />
						<label for="field_warranty_time"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_warranty_time_no" name="field_warranty_time" value="no" <?= $noChecked; ?>/>
						<label for="field_warranty_time_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<?php
							$required = (count($required_fields) && key_exists('warranty_time', $required_fields) && $required_fields['warranty_time'] ? " checked" : "");
							?>
							<input type="checkbox" class="" name="field_warranty_time_required" id="field_warranty_time_required" <?= $required; ?>>
							<small class=" text-danger" ><i class="fas fa-asterisk"></i></small>
						</div>
					</div>

					<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= TRANS('ENTRY_TYPE_ADDITIONAL_INFO'); ?></label>
					<div class="form-group col-md-3 switch-field container-switch">
						<?php
							$yesChecked = ($row['extra_info'] == 1 ? "checked" : "");
							$noChecked = ($row['extra_info'] == 0 ? "checked" : "");
						?>
						<input type="radio" id="field_extra_info" name="field_extra_info" value="yes" <?= $yesChecked; ?> />
						<label for="field_extra_info"><?= TRANS('YES'); ?></label>
						<input type="radio" id="field_extra_info_no" name="field_extra_info" value="no" <?= $noChecked; ?>/>
						<label for="field_extra_info_no"><?= TRANS('NOT'); ?></label>
						<div class="switch-next-checkbox" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REQUIRED'); ?>">
							<?php
							$required = (count($required_fields) && key_exists('extra_info', $required_fields) && $required_fields['extra_info'] ? " checked" : "");
							?>
							<input type="checkbox" class="" name="field_extra_info_required" id="field_extra_info_required" <?= $required; ?>>
							<small class=" text-danger" ><i class="fas fa-asterisk"></i></small>
						</div>
					</div>


				</div>
				<!-- Seção para exibir os campos de especificação - 
					baseados na seleção de tipos de ativos que utilizarão o perfil -->
				<div id="specs_fields" class="form-group row my-4 specs_fields"></div>

				<div class="form-group row my-4">



					<?php
					/* Campos personalizados */
					$custom_fields = getCustomFields($conn, null, 'equipamentos', null, null);

					if (count($custom_fields)) {
						$fields_ids = explode(',', $row['field_custom_ids']);
					?>
						<div class="w-100">
							<p class="h6 text-center font-weight-bold mt-4"><?= TRANS('EXTRA_FIELDS'); ?></p>
						</div>
						<?php
						foreach ($custom_fields as $field) {
							$disabled = (!$field['field_active'] ? " disabled" : "");
						?>
							<label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= $field['field_label']; ?></label>
							<div class="form-group col-md-3 switch-field ">
								<?php
								/* Vai aparecer como "não" mesmo que esteja no perfil, caso o campo não esteja ativo */
								$yesChecked = (in_array($field['id'], $fields_ids) && $field['field_active'] ? "checked" : "");
								$noChecked = (!in_array($field['id'], $fields_ids) || !$field['field_active'] ? "checked" : "");
								?>
								<input type="radio" id="<?= $field["field_name"]; ?>" name="<?= $field["field_name"]; ?>" value="yes" <?= $yesChecked; ?> <?= $disabled; ?> />
								<label for="<?= $field["field_name"]; ?>"><?= TRANS('YES'); ?></label>
								<input type="radio" id="<?= $field["field_name"]; ?>_no" name="<?= $field["field_name"]; ?>" value="no" <?= $noChecked; ?> <?= $disabled; ?> />
								<label for="<?= $field["field_name"]; ?>_no"><?= TRANS('NOT'); ?></label>
							</div>
					<?php
						}
					}
					/* Fim do trecho referente aos campos personalizados */
					?>


					<input type="hidden" name="cod" id="cod" value="<?= $_GET['cod']; ?>">
					<input type="hidden" name="action" id="action" value="edit">

					<div class="row w-100"></div>
					<div class="form-group col-md-8 d-none d-md-block">
					</div>
					<div class="form-group col-12 col-md-2 ">
						<button type="submit" id="idSubmit" name="submit" value="edit" class="btn btn-primary btn-block"><?= TRANS('BT_OK'); ?></button>
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
	<script src="../../includes/components/bootstrap/js/bootstrap.bundle.js"></script>
	<script type="text/javascript" charset="utf8" src="../../includes/components/datatables/datatables.js"></script>
    <script src="../../includes/components/bootstrap-select/dist/js/bootstrap-select.min.js"></script>

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


			$('.custom-fields-link').popover({
				content: '<?= TRANS('CUSTOM_FIELD'); ?>',
				trigger: "hover"
			});

			$(function() {
				$('[data-toggle="popover"]').popover({
					html: true
				});
			});

			$('.popover-dismiss').popover({
				trigger: 'focus'
			});


			$.fn.selectpicker.Constructor.BootstrapVersion = '4';
            $('.bs-select').selectpicker({
                /* placeholder */
                title: "<?= TRANS('SMART_EMPTY', '', 1); ?>",
                liveSearch: true,
                liveSearchNormalize: true,
                liveSearchPlaceholder: "<?= TRANS('BT_SEARCH', '', 1); ?>",
                noneResultsText: "<?= TRANS('NO_RECORDS_FOUND', '', 1); ?> {0}",
                style: "",
                styleBase: "form-control input-select-multi",
            });


			loadSpecsFields();
			$('#applied_to').on('change', function(){
				loadSpecsFields();
			});




			$.each($('.switch-next-checkbox'), function(index, el) {
				var group_parent = $(this).parent(); 
				var first_checkbox_id = group_parent.find('input:first').attr('id');

				var enabled = group_parent.find('input:first').is(':checked');
				var last_checkbox_id = $(this).find('input:last').attr('id');
				
				if (!enabled) {
					$('#' + last_checkbox_id).prop('checked', false).prop('disabled', true);
				} else {
					$('#' + last_checkbox_id).prop('disabled', false);
				}
			});

			$('.container-switch').on('click', 'input', function() {

				var group_parent = $(this).parents(); //object
				var last_checkbox_id = group_parent.find('input:last').attr('id');

				if ($(this).val() == "no") {
					$('#' + last_checkbox_id).prop('checked', false).prop('disabled', true);
				} else {
					$('#' + last_checkbox_id).prop('disabled', false);
				}
			});


			if ($('#is_default').is(':checked')) {
				$('#is_default').prop('disabled', true);
				$('#is_default_no').prop('disabled', true);
			}

			if (!$('#field_tag_number').is(':checked')) {
				$('#field_tag_check').prop('disabled', true).prop('checked', false);
				$('#field_tag_check_no').prop('disabled', true).prop('checked', true);
				$('#field_tag_tickets').prop('disabled', true).prop('checked', false);
				$('#field_tag_tickets_no').prop('disabled', true).prop('checked', true);
			}

			if (!$('#field_department').is(':checked')) {
				$('#field_load_department').prop('disabled', true).prop('checked', false);
				$('#field_load_department_no').prop('disabled', true).prop('checked', true);
				$('#field_search_dep_tags').prop('disabled', true).prop('checked', false);
				$('#field_search_dep_tags_no').prop('disabled', true).prop('checked', true);
			}


			$('[name="field_tag_number"]').on('change', function() {
				if ($(this).val() == "no") {
					$('#field_tag_check').prop('checked', false).prop('disabled', true);
					$('#field_tag_check_no').prop('checked', true).prop('disabled', true);
					$('#field_tag_tickets').prop('checked', false).prop('disabled', true);
					$('#field_tag_tickets_no').prop('checked', true).prop('disabled', true);
				} else {
					$('#field_tag_check').prop('disabled', false);
					$('#field_tag_check_no').prop('disabled', false);
					$('#field_tag_tickets').prop('disabled', false);
					$('#field_tag_tickets_no').prop('disabled', false);
				}
			});

			$('[name="field_department"]').on('change', function() {
				if ($(this).val() == "no") {
					$('#field_load_department').prop('checked', false).prop('disabled', true);
					$('#field_load_department_no').prop('checked', true).prop('disabled', true);
					$('#field_search_dep_tags').prop('checked', false).prop('disabled', true);
					$('#field_search_dep_tags_no').prop('checked', true).prop('disabled', true);
				} else {
					$('#field_load_department').prop('disabled', false);
					$('#field_load_department_no').prop('disabled', false);
					$('#field_search_dep_tags').prop('disabled', false);
					$('#field_search_dep_tags_no').prop('disabled', false);
				}
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
					url: './assets_fields_profiles_process.php',
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
				url: './assets_fields_profiles_process.php',
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

		function loadSpecsFields() {
			if ($('#profile_name').length > 0) {
				var loading = $(".loading");
				$(document).ajaxStart(function() {
					loading.show();
				});
				$(document).ajaxStop(function() {
					loading.hide();
				});

				$.ajax({
					url: './render_specs_fields_for_profile.php',
					method: 'POST',
					data: {
						applied_to: $('#applied_to').val(),
						profile_id: $('#cod').val()
					},
					// dataType: 'json',
				}).done(function(data) {
					$('#specs_fields').empty().html(data);
				});
				return false;
			}
		}
	</script>
</body>

</html>