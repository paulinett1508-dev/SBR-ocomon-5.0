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

	<style>
		li.list_specs {
			line-height: 1.5em;
		}

		.container-form-line {
			position: relative;
		}

		.switch-next-checkbox {
			position: absolute;
			top: 0;
			left: 0;
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
		<h4 class="my-4"><i class="fas fa-clone text-secondary"></i>&nbsp;<?= TRANS('EQUIPMENTS_MODELS'); ?></h4>
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

		if (isset($_GET['cod'])) {
            /* ARQUIVOS */
            $sqlFiles = "SELECT  i.* FROM imagens i  WHERE i.img_model = '".(int)$_GET['cod']."' ORDER BY i.img_inv ";
            $resultFiles = $conn->query($sqlFiles);
            $hasFiles = $resultFiles->rowCount();
		}


		$asset_type = (isset($_GET['asset_type']) ? noHtml($_GET['asset_type']) : "");
		$manufacturer_id = (isset($_GET['manufacturer']) ? noHtml($_GET['manufacturer']) : "");
		$field_to_update = (isset($_GET['this']) ? noHtml($_GET['this']) : "");

        
		$models = (isset($_GET['cod']) ? getAssetsModels($conn, (int)$_GET['cod']) : getAssetsModels($conn));
		$registros = count($models);
		

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
							<td class="line col_type"><?= TRANS('COL_TYPE'); ?></td>
							<td class="line col_type"><?= TRANS('COL_MANUFACTURER'); ?></td>
							<td class="line col_model"><?= TRANS('COL_MODEL'); ?></td>
							<td class="line col_model"><?= TRANS('ATTRIBUTES'); ?></td>
							<td class="line editar" width="10%"><?= TRANS('BT_EDIT'); ?></td>
							<td class="line remover" width="10%"><?= TRANS('BT_REMOVE'); ?></td>
						</tr>
					</thead>
					<tbody>
						<?php

						// foreach ($resultado->fetchall() as $row) {
						foreach ($models as $row) {

							$renderAttrs = "";
							$attributes = getModelSpecs($conn, $row['codigo']);
							
							foreach ($attributes as $attr) {
								$renderAttrs .= "<li class='list_specs'>" . $attr['mt_name'].": " . $attr['spec_value'] . $attr['unit_abbrev'] . "</li>";
							}

						?>
							<tr>
								<td class="line"><?= $row['tipo']; ?></td>
								<td class="line"><?= $row['fabricante']; ?></td>
								<td class="line"><?= $row['modelo']; ?></td>
								<td class="line"><?= $renderAttrs; ?></td>
								<td class="line"><button type="button" class="btn btn-secondary btn-sm" onclick="redirect('<?= $_SERVER['PHP_SELF']; ?>?action=edit&cod=<?= $row['codigo']; ?>')"><?= TRANS('BT_EDIT'); ?></button></td>
								<td class="line"><button type="button" class="btn btn-danger btn-sm" onclick="confirmDeleteModal('<?= $row['codigo']; ?>')"><?= TRANS('REMOVE'); ?></button></td>
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
			<form name="form" method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form" enctype="multipart/form-data">
				<?= csrf_input('csrf_equip_models'); ?>
				<div class="form-group row my-4">

					<label for="type" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_TYPE'); ?></label>
                    <div class="form-group col-md-10">
						<div class="input-group">
							<select class="form-control sel2" id="type" name="type" required>
								<option value=""><?= TRANS('SEL_SELECT'); ?></option>
								<?php
								$sql = "SELECT * FROM tipo_equip ORDER BY tipo_nome";
								$exec_sql = $conn->query($sql);
								foreach ($exec_sql->fetchAll() as $rowType) {
									?>
									<option value="<?= $rowType['tipo_cod']; ?>"
									<?= (!empty($asset_type) && $asset_type == $rowType['tipo_cod'] ? " selected" : ""); ?>
									><?= $rowType['tipo_nome']; ?></option>
									<?php
								}
								?>
							</select>
							<div class="input-group-append">
									<div class="input-group-text manage_popups" data-location="type_of_equipments" data-params="action=new" title="<?= TRANS('ADD'); ?>" data-placeholder="<?= TRANS('ADD'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
										<i class="fas fa-plus"></i>
									</div>
							</div>
						</div>
                    </div>


					<label for="manufacturer" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_MANUFACTURER'); ?></label>
                    <div class="form-group col-md-10">
						<div class="input-group">
							<select class="form-control bs-select " id="manufacturer" name="manufacturer" required>
								<option value=""><?= TRANS('SEL_SELECT'); ?></option>
								<?php
								$manufacturers = getManufacturers($conn, null);
								foreach ($manufacturers as $manufacturer) {
									?>
									<option value="<?= $manufacturer['fab_cod']; ?>"
									<?= (!empty($manufacturer_id) && $manufacturer_id == $manufacturer['fab_cod'] ? ' selected' : ''); ?>
									><?= $manufacturer['fab_nome']; ?></option>
									<?php
								}
								?>
							</select>
							<div class="input-group-append">
								<div class="input-group-text manage_popups" data-location="manufacturers" data-params="action=new" title="<?= TRANS('ADD'); ?>" data-placeholder="<?= TRANS('ADD'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
									<i class="fas fa-plus"></i>
								</div>
							</div>
						</div>
                    </div>
					
					
					<label for="model_name" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_MODEL'); ?></label>
					<div class="form-group col-md-10">
						<input type="text" class="form-control " id="model_name" name="model_name" required />
                    </div>
                    

					<label class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('ATTRIBUTE'); ?></label>
					<div class="form-group col-md-4">
						<div class="field_wrapper_specs" id="field_wrapper_specs">
							<div class="input-group">
								<div class="input-group-prepend">
									<div class="input-group-text">
										<a href="javascript:void(0);" class="add_button_specs" title="<?= TRANS('NEW_SPEC'); ?>"><i class="fa fa-plus"></i></a>
									</div>
								</div>
								<select class="form-control bs-select sel-control" name="measure_type[]" id="measure_type">
									<option value=""><?= TRANS('SEL_SELECT'); ?></option>
								<?php
									$types = getMeasureTypes($conn, null, true);
									foreach ($types as $type) {
										?>
										<option value="<?= $type['id']; ?>"><?= $type['mt_name']; ?></option>
										<?php
									}
								?>
								</select>
							</div>
							<small class="form-text text-muted"><?= TRANS('HELPER_CHOOSE_MEASURE_TYPE'); ?></small>
						</div>
					</div>

					<div class="form-group col-md-2">
						<input type="number" class="form-control" name="measure_value[]" id="measure_type_measure_type_measure_type" disabled/>
						<small class="form-text text-muted"><?= TRANS('HELPER_CHOOSE_MEASURE_VALUE'); ?></small>
					</div>

					<div class="form-group col-md-4">
						<select class="form-control" name="measure_unit[]" id="measure_type_measure_type">
							<option value=""><?= TRANS('SEL_SELECT'); ?></option>
						</select>
						<small class="form-text text-muted"><?= TRANS('HELPER_CHOOSE_MEASURE_UNIT'); ?></small>
					</div>
					

				</div>

				
				<!-- Receberá cada uma das novas especificações do modelo -->
				<div id="new_specs" class="form-group row my-4 new_specs">
				</div>


				<div class="form-group row my-4">

					<div class="w-100"></div>
                    <label class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('ATTACH_FILE'); ?></label>
					<div class="form-group col-md-10">
						<div class="field_wrapper" id="field_wrapper">
							<div class="input-group">
								<div class="input-group-prepend">
									<div class="input-group-text">
										<a href="javascript:void(0);" class="add_button" title="<?= TRANS('TO_ATTACH_ANOTHER'); ?>"><i class="fa fa-plus"></i></a>
									</div>
								</div>
								<div class="custom-file">
									<input type="file" class="custom-file-input" name="anexo[]" id="idInputFile" aria-describedby="inputGroupFileAddon01" lang="br">
									<label class="custom-file-label text-truncate" for="inputGroupFile01"><?= TRANS('CHOOSE_FILE'); ?></label>
								</div>
							</div>
						</div>
					</div>

					<div class="row w-100"></div>
					<div class="form-group col-md-8 d-none d-md-block">
					</div>
					<div class="form-group col-12 col-md-2 ">

						<input type="hidden" name="action" id="action" value="new">
						<input type="hidden" name="tipo_selected" value="" id="tipo_selected" />
						<input type="hidden" name="manufacturer_selected" value="" id="manufacturer_selected" />
						<!-- Vem do cadastro de ativos -->
						<input type="hidden" name="asset_type" value="<?= (isset($asset_type) ? $asset_type : ""); ?>" id="asset_type" />
						<input type="hidden" name="field_to_update" value="<?= (isset($field_to_update) ? $field_to_update : ""); ?>" id="field_to_update" />

						
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

			$row = $models;
		    ?>
			<h6><?= TRANS('BT_EDIT'); ?></h6>
			<form name="form" method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form" enctype="multipart/form-data">
				<?= csrf_input('csrf_equip_models'); ?>
				<div class="form-group row my-4">
                    

					<label for="type" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_TYPE'); ?></label>
                    <div class="form-group col-md-10">
						<div class="input-group">
							<select class="form-control sel2" id="type" name="type" required>
								<?php
								$sql = "SELECT * FROM tipo_equip WHERE tipo_cod= " . $row["tipo_cod"] . "";
								$resTipo = $conn->query($sql);
								$model = $resTipo->fetch()['tipo_cod'];
								?>
								<option value=""><?= TRANS('SEL_SELECT'); ?></option>
								<?php
								$sql = "SELECT * FROM tipo_equip ORDER BY tipo_nome";
								$exec_sql = $conn->query($sql);
								foreach ($exec_sql->fetchAll() as $rowType) {
									?>
									<option value="<?= $rowType['tipo_cod']; ?>"
										<?= ($rowType['tipo_cod'] == $model ? ' selected' : ''); ?>
									><?= $rowType['tipo_nome']; ?></option>
									<?php
								}
								?>
							</select>
							<div class="input-group-append">
									<div class="input-group-text manage_popups" data-location="type_of_equipments" data-params="action=new" title="<?= TRANS('ADD'); ?>" data-placeholder="<?= TRANS('ADD'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
										<i class="fas fa-plus"></i>
									</div>
							</div>
						</div>
                    </div>
				

					<label for="manufacturer" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_MANUFACTURER'); ?></label>
                    <div class="form-group col-md-10">
						<div class="input-group">
							<select class="form-control bs-select " id="manufacturer" name="manufacturer" required>
								<option value=""><?= TRANS('SEL_SELECT'); ?></option>
								<?php
								$manufacturers = getManufacturers($conn, null);
								foreach ($manufacturers as $manufacturer) {
									?>
									<option value="<?= $manufacturer['fab_cod']; ?>"
									<?= ($manufacturer['fab_cod'] == $row['fabricante_cod'] ? " selected" : ""); ?>
									><?= $manufacturer['fab_nome']; ?></option>
									<?php
								}
								?>
							</select>
							<div class="input-group-append">
								<div class="input-group-text manage_popups" data-location="manufacturers" data-params="action=new" title="<?= TRANS('ADD'); ?>" data-placeholder="<?= TRANS('ADD'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
									<i class="fas fa-plus"></i>
								</div>
							</div>
						</div>
                    </div>


				
                    <label for="model_name" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_MODEL'); ?></label>
					<div class="form-group col-md-10">
						<input type="text" class="form-control " id="model_name" name="model_name" value="<?= $row['modelo']; ?>" required />
                    </div>
                    


					<?php
						$attributes = getModelSpecs($conn, $row['codigo']);
						if (count($attributes)) {
							$i = 0;
							?>
								<label class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('ATTRIBUTES'); ?></label>
							<?php
							foreach ($attributes as $attr) {

								if ($i != 0) {
									?>
										<label class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"></label>
									<?php
								}
								?>
									<input type="hidden" name="spec_id[]" value="<?= $attr['spec_id']; ?>">
									<div class="form-group col-md-4">
										<div class="field_wrapper_specs" id="field_wrapper_specs">
												
											<select class="form-control sel-control" name="measure_type_update[]" id="measure_type<?= $i; ?>">
												<option value="<?= $attr['type_id']; ?>"><?= $attr['mt_name']; ?></option>
											
											</select>
										</div>
									</div>

									<div class="form-group col-md-2">
										<input type="number" class="form-control" name="measure_value_update[]" id="measure_type<?= $i; ?>_measure_type<?= $i; ?>_measure_type<?= $i; ?>" value="<?= $attr['spec_value']; ?>"/>
									</div>

									<div class="form-group col-md-3">
										<select class="form-control" name="measure_unit_update[]" id="measure_type<?= $i; ?>_measure_type<?= $i; ?>">

											<?php
												$units = getMeasureUnits($conn, null, $attr['type_id']);
												foreach ($units as $unit) {
													?>
													<option value="<?= $unit['id']; ?>"
													<?= ($unit['id'] == $attr['unit_id'] ? " selected" : ""); ?>
													><?= $unit['unit_abbrev']; ?></option>
													<?php
												}
											?>
										</select>
									</div>

									<div class="form-group col-md-1"> <!-- container-form-line -->
										<!-- <div class="switch-next-checkbox"> -->
											<input type="checkbox" name="deleteSpec[]" value="<?= $attr['spec_id']; ?>">&nbsp;<span class="align-top"><i class="fas fa-trash-alt text-danger" title="<?= TRANS('REMOVE'); ?>"></i></span>
										<!-- </div> -->
									</div>
									<div class="w-100"></div>
								<?php
								$i++;
							}
						}
					?>


				<!-- Link para adicionar características -->
				<label class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('ATTRIBUTES'); ?></label>
				<div class="form-group col-md-10">
					<a href="javascript:void(0);" class="add_button_specs" title="<?= TRANS('NEW_SPEC'); ?>"><i class="fa fa-plus"></i></a>
                </div>


				</div>
				<!-- Receberá cada uma das novas especificações do modelo -->
				<div id="new_specs" class="form-group row my-4 new_specs">
				</div>
				<div class="form-group row my-4">

                    <label class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('ATTACH_FILE'); ?></label>

					<div class="form-group col-md-10">
						<div class="field_wrapper" id="field_wrapper">
							<div class="input-group">
								<div class="input-group-prepend">
									<div class="input-group-text">
										<a href="javascript:void(0);" class="add_button" title="<?= TRANS('TO_ATTACH_ANOTHER'); ?>"><i class="fa fa-plus"></i></a>
									</div>
								</div>
								<div class="custom-file">
									<input type="file" class="custom-file-input" name="anexo[]" id="idInputFile" aria-describedby="inputGroupFileAddon01" lang="br">
									<label class="custom-file-label text-truncate" for="inputGroupFile01"><?= TRANS('CHOOSE_FILE'); ?></label>
								</div>
							</div>
						</div>
					</div>
                    

                    <?php
                    /* TRECHO PARA EXIBIÇÃO DA LISTAGEM DE ARQUIVOS ANEXOS */
                        $cont = 0;
                        if ($hasFiles) {

                            ?>
                            <div class="form-group col-md-12 my-2">

                                <div class="col-sm-12 border-bottom rounded p-0 bg-white " id="files">
                                    <!-- collapse -->
                                    <table class="table  table-hover table-striped rounded">
                                        <!-- table-responsive -->
                                        <!-- <thead class="bg-secondary text-white"> -->
                                        <thead class=" text-white" style="background-color: #48606b;">
                                            <tr>
                                                <th scope="col">#</th>
                                                <th scope="col"><?= TRANS('COL_TYPE'); ?></th>
                                                <!-- <th scope="col"><?= TRANS('SIZE'); ?></th> -->
                                                <th scope="col"><?= TRANS('FILE'); ?></th>
                                                <th scope="col"><?= TRANS('REMOVE'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $i = 1;
                                            // $cont = 0;
                                            foreach ($resultFiles->fetchAll() as $rowFiles) {
                                                $cont++;
                                                $size = round($rowFiles['img_size'] / 1024, 1);
                                                $rowFiles['img_tipo'] . "](" . $size . "k)";

                                                if (isImage($rowFiles["img_tipo"])) {

                                                    $viewImage = "&nbsp;<a onClick=\"javascript:popupWH('../../includes/functions/showImg.php?" .
                                                        "file=" . $rowFiles['img_cod'] . "&cod=" . $rowFiles['img_cod'] . "'," . $rowFiles['img_largura'] . "," . $rowFiles['img_altura'] . ")\" " .
                                                        "title='" . TRANS('VIEW') . "'><i class='fa fa-search'></i></a>";
                                                } else {
                                                    $viewImage = "";
                                                }
                                            ?>
                                                <tr>
                                                    <th scope="row"><?= $i; ?></th>
                                                    <td><?= $rowFiles['img_tipo']; ?></td>
                                                    <!-- <td><?= $size; ?></td> -->
                                                    <td><a onClick="redirect('../../includes/functions/download.php?file=<?= $rowFiles['img_cod']; ?>&cod=<?= $rowFiles['img_cod']; ?>')" title="Download the file"><?= $rowFiles['img_nome']; ?></a><?= $viewImage; ?></i></td>
                                                    <td><input type="checkbox" name="delImg[<?= $cont; ?>]" value="<?= $rowFiles['img_cod']; ?>">&nbsp;<span class="align-top"><i class="fas fa-trash-alt text-danger"></i></span></td>

                                                </tr>
                                            <?php
                                                $i++;
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <?php
                        }
                        /* FINAL DO TRECHO DE LISTAGEM DE ARQUIVOS ANEXOS*/
                    ?>

					<div class="row w-100"></div>
					<div class="form-group col-md-8 d-none d-md-block">
					</div>
					<div class="form-group col-12 col-md-2 ">
                        <input type="hidden" name="cod" value="<?= (int)$_GET['cod']; ?>">
                        <input type="hidden" name="action" id="action" value="edit">
                        <input type="hidden" name="cont" value="<?= $cont; ?>" />
						<input type="hidden" name="tipo_selected" value="<?= $row['tipo_cod']; ?>" id="tipo_selected" />
						<input type="hidden" name="manufacturer_selected" value="<?= $row['fabricante_cod']; ?>" id="manufacturer_selected" />
						<!-- Vem do cadastro de ativos - em princípio apenas para novos registros -->
						<input type="hidden" name="asset_type" value="<?= (isset($asset_type) ? $asset_type : ""); ?>" id="asset_type" />
						<input type="hidden" name="field_to_update" value="<?= (isset($field_to_update) ? $field_to_update : ""); ?>" id="field_to_update" />


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
    <script src="../../includes/components/jquery/jquery.initialize.min.js"></script>
	<script src="../../includes/components/bootstrap/js/bootstrap.bundle.js"></script>
    <script src="../../includes/components/bootstrap-select/dist/js/bootstrap-select.min.js"></script>
	<script type="text/javascript" charset="utf8" src="../../includes/components/datatables/datatables.js"></script>
	<script type="text/javascript">
		$(function() {

			// var random = (length = 8) => {
			// 	return Math.random().toString(16).substr(2, length);
			// };

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

			/* Ajusta o botão de fechar caso seja popup ou não */
			closeOrReturn ();

            /* Permitir a replicação do campo de input file */
            var maxField = <?= $config['conf_qtd_max_anexos']; ?>;
            var addButton = $('.add_button'); //Add button selector
            var wrapper = $('.field_wrapper'); //Input field wrapper

            var fieldHTML = '<div class="input-group my-1 d-block"><div class="input-group-prepend"><div class="input-group-text"><a href="javascript:void(0);" class="remove_button"><i class="fa fa-minus"></i></a></div><div class="custom-file"><input type="file" class="custom-file-input" name="anexo[]"  aria-describedby="inputGroupFileAddon01" lang="br"><label class="custom-file-label text-truncate" for="inputGroupFile01"><?= TRANS('CHOOSE_FILE', '', 1); ?></label></div></div></div></div>';

            var x = 1; //Initial field counter is 1

            //Once add button is clicked
            $(addButton).click(function() {
                //Check maximum number of input fields
                if (x < maxField) {
                    x++; //Increment field counter
                    $(wrapper).append(fieldHTML); //Add field html
                }
            });

            //Once remove button is clicked
            $(wrapper).on('click', '.remove_button', function(e) {
                e.preventDefault();
                $(this).parent('div').parent('div').parent('div').remove(); //Remove field html
                x--; //Decrement field counter
            });

            if ($('#idInputFile').length > 0) {
                /* Adicionei o mutation observer em função dos elementos que são adicionados após o carregamento do DOM */
                var obs = $.initialize(".custom-file-input", function() {
                    $('.custom-file-input').on('change', function() {
                        let fileName = $(this).val().split('\\').pop();
                        $(this).next('.custom-file-label').addClass("selected").html(fileName);
                    });

                }, {
                    target: document.getElementById('field_wrapper')
                }); /* o target limita o scopo do observer */

            }

            if ($('#table_lists').length > 0) {
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
            }

			$('.manage_popups').css('cursor', 'pointer').on('click', function() {
				loadInPopup($(this).attr('data-location'), $(this).attr('data-params'));
			});



			loadMeasureUnits();
			measureValueControl();
			$('#measure_type').on('change', function() {
				measureValueControl();
				loadMeasureUnits();
				availablesMeasureTypesControl();
			});

			$('.add_button_specs').on('click', function() {
				loadNewSpecField();
				availablesMeasureTypesControl();
			});

			$('.new_specs').on('click', '.remove_button_specs', function(e) {
                e.preventDefault();
				dataRandom = $(this).attr('data-random');
				// $("div[data-random='"+dataRandom+"']").remove();
				$("."+dataRandom).remove();
				availablesMeasureTypesControl();
            });


			// if ($('#measure_type').length > 0) {
			if ($('#type').length > 0) {
                /* Adicionei o mutation observer em função dos elementos que são adicionados após o carregamento do DOM */
                var obs = $.initialize(".after-dom-ready", function() {
					
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

					availablesMeasureTypesControl();

                    $('.after-dom-ready').on('change', function() {

						availablesMeasureTypesControl();

						// var selectedValue = $(this).val();
						var myId = $(this).attr('id');
						loadMeasureUnits(myId);
						measureValueControl(myId);
					});

                }, {
                    target: document.getElementById('new_specs')
                }); /* o target limita o scopo do observer */

            }


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

                var form = $('form').get(0);
				$("#idSubmit").prop("disabled", true);
				$.ajax({
					url: './equipments_models_process.php',
					method: 'POST',
                    // data: $('#form').serialize(),
                    data: new FormData(form),
                    dataType: 'json',
                    
                    cache: false,
				    processData: false,
				    contentType: false,
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
							
							if ($('#asset_type').length > 0 && $('#asset_type').val() != '') {
								window.opener.reloadAssetModels($('#asset_type').val(), $('#field_to_update').val());
							} else {
								window.opener.showModelsByType();
							}
							
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


		function loadMeasureUnits(elementID = "measure_type") {
			// if ($('#measure_type').length > 0) {
			if ($('#type').length > 0) {
				var loading = $(".loading");
				$(document).ajaxStart(function() {
					loading.show();
				});
				$(document).ajaxStop(function() {
					loading.hide();
				});

				$.ajax({
					url: './get_measure_units_by_type.php',
					method: 'POST',
					data: {
						measure_type: $('#'+elementID).val(),
					},
					dataType: 'json',
				}).done(function(data) {
					let html = '';
					if (data.length > 0) {
						for (i in data) {
							html += '<option value="' + data[i].id + '">' + data[i].unit_abbrev + ' (' + data[i].unit_name + ')</option>';
						}
					}
					/* Para conseguir mapear os ids que vêm após o carregamento do DOM, 
					criei a regra de duplicar o ID para o segundo campo - Assim só preciso passar um parâmetro para a função */
					$('#'+elementID+'_'+elementID).empty().html(html);
				});
				return false;
			}
		}


		function loadNewSpecField() {
			// if ($('#measure_type').length > 0) {
			if ($('#type').length > 0) {
				var loading = $(".loading");
				$(document).ajaxStart(function() {
					loading.show();
				});
				$(document).ajaxStop(function() {
					loading.hide();
				});

				$.ajax({
					url: './render_new_specification_field.php',
					method: 'POST',
					data: {
						// measure_type: $('#measure_type').val(),
						random: Math.random().toString(16).substr(2, 8)
					},
					// dataType: 'json',
				}).done(function(data) {
					$('#new_specs').append(data);
				});
				return false;
			}
		}


		function loadAssetsTypes(selected_id = '') {
			$.ajax({
				url: './get_assets_types.php',
				method: 'POST',
				data: {
					cat_type: 1
				},
				dataType: 'json',
			}).done(function(response) {
				$('#type').empty().append('<option value=""><?= TRANS('SEL_TYPE'); ?></option>');
				for (var i in response) {

					var option = '<option value="' + response[i].tipo_cod + '">' + response[i].tipo_nome + '</option>';
					$('#type').append(option);
					$('#type').selectpicker('refresh');


					if (selected_id !== '') {
						$('#type').val(selected_id).change();
					} else
					if ($('#tipo_selected').val() != '') {
						$('#type').val($('#tipo_selected').val()).change();
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
				$('#manufacturer').empty().append('<option value=""><?= TRANS('SEL_TYPE'); ?></option>');
				for (var i in response) {

					var option = '<option value="' + response[i].fab_cod + '">' + response[i].fab_nome + '</option>';
					$('#manufacturer').append(option);
					$('#manufacturer').selectpicker('refresh');


					if (selected_id !== '') {
						$('#manufacturer').val(selected_id).change();
					} else
					if ($('#manufacturer_selected').val() != '') {
						$('#manufacturer').val($('#manufacturer_selected').val()).change();
					}
				}
			});
		}


		function enableField(fieldID) {
			if ($('#'+fieldID).length > 0) {
				$('#'+fieldID).prop('disabled', false);
			}
		}

		function disableField(fieldID) {
			if ($('#'+fieldID).length > 0) {
				$('#'+fieldID).prop('disabled', true);
				$('#'+fieldID).val('');
			}
		}

		function measureValueControl(elementID = "measure_type") {
			
			let fieldID = elementID+'_'+elementID+'_'+elementID;

			if ($('#'+elementID).length > 0) {
				if ($('#'+elementID).val() != '') {
					enableField(fieldID);
				} else {
					disableField(fieldID);
				}
			}
		}


		/* Faz o controle das opções de tipos de características disponíveis para seleção */
		function availablesMeasureTypesControl() {
			let keys = [];
			let values = [];

			/* Primeiro habilito todos os options */
			$('.sel-control').each(function(){
				$(this).find('option').each(function(){
					$(this).prop('disabled', false);
					
					if ($(this).hasClass('bs-select')) {
						$(this).selectpicker('refresh');
					}
				});
			});

			/* Pegando todos os IDs dos Selects e seus respectivos valores */
			$('select[name^="measure_type"]').each(function() {
				
				let id = $(this).attr('id');
				let value = $(this).val();
				
				keys.push(id);
				values.push(value);
			});

			for (var i = 0; i < keys.length; i++) {
				/* Para cada option confiro em todos os Selects */
				$('.sel-control').each(function(){

					/* Controle de seleção - Desabilita todos os options que tiverem o valor já selecionado para o ID checado*/
					if ($(this).attr('id') != keys[i]) {
						
						if (values[i] != '') {
							$(this).find('[value="'+values[i]+'"]').prop('disabled', true);
							if ($(this).hasClass('bs-select')) {
								$(this).selectpicker('refresh');
							}
						}
						
					} else {
						$(this).find('[value="'+values[i]+'"]').prop('disabled', false);
						if ($(this).hasClass('bs-select')) {
							$(this).selectpicker('refresh');
						}
					}
				});
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
				url: './equipments_models_process.php',
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

		function loadInPopup(pageBase, params) {
			let url = pageBase + '.php?' + params;
			x = window.open(url, '', 'dependent=yes,width=800,scrollbars=yes,statusbar=no,resizable=yes');
			x.moveTo(window.parent.screenX + 100, window.parent.screenY + 100);
		}


	</script>
</body>

</html>