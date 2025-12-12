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

$config = getConfig($conn);

/* Para manter a compatibilidade com versões antigas */
$table = "equipxpieces";
$clausule = $QRY['componentexequip_ini'];
$sqlTest = "SELECT * FROM {$table}";
try {
    $conn->query($sqlTest);
}
catch (Exception $e) {
    $table = "equipXpieces";
    $clausule = $QRY['componenteXequip_ini'];
}


$asset_id = (isset($_GET['asset_id']) ? (int)$_GET['asset_id'] : "");
if (empty($asset_id)) {
    echo message('warning', 'Ooops!', TRANS('MSG_ERR_NOT_EXECUTE'), '', '', 1);
    return;
}


$query = $QRY["full_detail_ini"];
$query .= " AND (c.comp_cod = '" . $asset_id . "')";
$query .= $QRY["full_detail_fim"];

try {
    $res = $conn->query($query);
    $row = $res->fetch();
}
catch (Exception $e) {
    $exception .= "<hr>" . $e->getMessage();
    echo message('danger', 'Ooops!', $exception, '', '', 1);
    return;
}


$isChild = assetHasParent($conn, $asset_id);

$asset_tag = $row['etiqueta'];
$asset_unit = $row['cod_inst'];
$asset_type = $row['tipo'];
$hasCustomFields = hasCustomFields($conn, $asset_id, 'assets_x_cfields');


$unit_info = (getUnits($conn, null, $asset_unit) ?? "");
$client_id = (!empty($unit_info) && array_key_exists('id', $unit_info) ? $unit_info['id'] : "");
$client_name = (!empty($unit_info) && array_key_exists('nickname', $unit_info) ? $unit_info['nickname'] : "");
$department_info = getDepartments($conn, null, $row['tipo_local']);
$department_unit = (!empty($department_info['unidade']) ? "&nbsp;(" . $department_info['unidade'] . ")" : "");
$inconsistent_department = ($asset_unit != $department_info['loc_unit'] && !empty($department_info['loc_unit']));

$alertText = "<hr />" .TRANS('ASSET_UNIT') . ":&nbsp;" . $row['instituicao'] . "<br />";
$alertText .= TRANS('DEPARTMENT_UNIT') . ":&nbsp;" . $department_info['unidade'];

$inconsistent_alert = ($inconsistent_department ? message('danger', 'Ooops!', TRANS('INCONSISTENT_UNIT_X_DEPARTMENT') . $alertText, '', '', true) : "");





/* Anexos */
$sqlFiles = "SELECT  i.* FROM imagens i  WHERE 
                i.img_inv = '{$asset_tag}' AND i.img_inst = '{$asset_unit}' 
            ORDER BY i.img_cod ";
$resultFiles = $conn->query($sqlFiles);
$hasFiles = $resultFiles->rowCount();

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" type="text/css" href="../../includes/css/estilos.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/css/switch_radio.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/jquery/datetimepicker/jquery.datetimepicker.css" />
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
		<h4 class="my-4"><i class="fas fa-qrcode text-secondary"></i>&nbsp;<?= TRANS('ASSETS'); ?></h4>
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

		echo $inconsistent_alert;
        

		if ((!isset($_GET['action'])) && !isset($_POST['submit'])) {

			?>
			<h6><?= TRANS('BT_EDIT'); ?></h6>
            
            
			<form name="form" method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form" enctype="multipart/form-data">
				<?= csrf_input(); ?>
				<div class="form-group row my-4">
                    
                    <h6 class="w-100 mt-5 ml-5 border-top p-4"><i class="fas fa-info-circle text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('BASIC_INFORMATIONS')); ?></h6>




                    <label for="asset_type_readonly" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_TYPE'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control bs-select" id="asset_type_readonly" name="asset_type_readonly" disabled>
                            <option value=""><?= TRANS('SEL_TYPE_EQUIP'); ?></option>
                            <?php
                            $sql = "SELECT * FROM tipo_equip ORDER BY tipo_nome";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                                ?>
								<option value="<?= $rowType['tipo_cod']; ?>"
                                <?= ($row['tipo'] == $rowType['tipo_cod'] ? ' selected' : ''); ?>
                                ><?= $rowType['tipo_nome']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
					</div>

                    <label for="manufacturer" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_MANUFACTURER'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control bs-select" id="manufacturer" name="manufacturer" required>
                            <option value=""><?= TRANS('SEL_MANUFACTURER'); ?></option>
                            <?php
                            $sql = "SELECT * FROM fabricantes ORDER BY fab_nome";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                                ?>
								<option value="<?= $rowType['fab_cod']; ?>"
                                <?= ($row['fab_cod'] == $rowType['fab_cod'] ? ' selected' : ''); ?>
                                ><?= $rowType['fab_nome']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
					</div>

                    <label for="model" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_MODEL'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control bs-select" id="model" name="model" required>
                            <option value=""><?= TRANS('SEL_MODEL'); ?></option>
                            
                        </select>
					</div>

                    <label for="asset_tag" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('ASSET_TAG'); ?></label>
					<div class="form-group col-md-4">
						<input type="text" class="form-control " id="asset_tag" name="asset_tag" value="<?= $row['etiqueta']; ?>" required />
                    </div>




                    <label for="client" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CLIENT'); ?></label>
                    <div class="form-group col-md-4">
                        <?php
                            $disabled = ($isChild ? ' disabled' : '');
                        ?>
                        <select class="form-control bs-select" id="client" name="client" required <?= $disabled; ?>>
                            <?php
                                $clients = getClients($conn, null, null, $_SESSION['s_allowed_clients']);
                                foreach ($clients as $client) {
                                    ?>
                                        <option value="<?= $client['id']; ?>"
                                        <?= ($client['id'] == $client_id ? " selected" : "") ?>
                                        ><?= $client['nickname']; ?></option>
                                    <?php
                                }
                            ?>
                            
                        </select>
                    </div>
                    <div class="w-100"></div>

                    <!-- Ver para carregar apenas unidades que estiverem vinculadas ao cliente -->
                    <label for="asset_unit" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_UNIT'); ?></label>
                    <div class="form-group col-md-4">
                        <?php
                            $disabled = ($isChild ? ' disabled' : '');
                        ?>
                        <select class="form-control bs-select" id="asset_unit" name="asset_unit" required <?= $disabled; ?>>
                            <option value=""><?= TRANS('SEL_UNIT'); ?></option>
                            <?php
                            
                            $units = getUnits($conn, null, null, $client_id, $_SESSION['s_allowed_units']);
                            foreach ($units as $rowType) {
                                ?>
								<option value="<?= $rowType['inst_cod']; ?>"
                                    <?= ($row['cod_inst'] == $rowType['inst_cod'] ? ' selected' : ''); ?>
                                ><?= $rowType['inst_nome']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
					</div>

                    <!-- Carregar apenas departamentos que estiverem vinculados à unidade ou ao cliente -->
                    <label for="department" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('DEPARTMENT'); ?></label>
                    <div class="form-group col-md-4">
                        <?php
                            $disabled = ($isChild ? ' disabled' : '');
                        ?>
                        <select class="form-control bs-select" id="department" name="department" required <?= $disabled; ?>>
                            
                            <option value=""><?= TRANS('SEL_DEPARTMENT'); ?></option>
                            <?php
                            
                            $departments = getDepartments($conn, null, null, $asset_unit);
                            foreach ($departments as $rowType) {
                                ?>
								<option value="<?= $rowType['loc_id']; ?>"
                                <?= ($row['tipo_local'] == $rowType['loc_id'] ? ' selected' : ''); ?>
                                ><?= $rowType['local']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
					</div>

                    
					
                
                    <label for="serial_number" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('SERIAL_NUMBER'); ?></label>
					<div class="form-group col-md-4">
						<input type="text" class="form-control " id="serial_number" name="serial_number" value="<?= $row['serial']; ?>" />
                    </div>
					
                    <label for="part_number" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_PARTNUMBER'); ?></label>
					<div class="form-group col-md-4">
						<input type="text" class="form-control " id="part_number" name="part_number" value="<?= $row['serial']; ?>" />
                    </div>
					
                    
                    <label for="net_name" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('NET_NAME'); ?></label>
					<div class="form-group col-md-4">
						<input type="text" class="form-control " id="net_name" name="net_name" value="<?= $row['nome']; ?>" />
					</div>

                    <label for="situation" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('STATE'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control bs-select" id="situation" name="situation" required>
                            <option value=""><?= TRANS('STATE'); ?></option>
                            <?php
                            
                            $states = getOperationalStates($conn);
                            
                            foreach ($states as $rowType) {
                                ?>
								<option value="<?= $rowType['situac_cod']; ?>"
                                <?= ($row['situac_cod'] == $rowType['situac_cod'] ? ' selected' : ''); ?>
                                ><?= $rowType['situac_nome']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
					</div>


                    <label class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('ATTACH_FILE'); ?></label>
					<div class="form-group col-md-4">
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
                        $specs = getAssetSpecs($conn, $asset_id, false);
                        $possibleChilds = getAssetsTypesPossibleChilds($conn, $asset_type);

                        if (!empty($specs) || !empty($possibleChilds)) {
                            ?>
                                <!-- Campos de especificação  -->
                                <h6 class="w-100 mt-5 ml-5 border-top p-4"><i class="fas fa-puzzle-piece text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('SUBTTL_ALL_DATA_COMPLE_CONFIG')); ?></h6>
                                <?php

                                
                                foreach ($specs as $spec) {
                                    $thisId = str_slug($spec['tipo_nome'], 'spec_', true);
                                    ?>
                                        <label for="<?= $thisId; ?>" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= $spec['tipo_nome']; ?></label>
                                        <div class="form-group col-md-4">
                                            <div class="input-group">
                                                <select class="form-control bs-select" id="<?= $thisId; ?>" name="<?= str_slug($spec['tipo_nome'], 'spec_'); ?>[]" >
                                                    <option value=""><?= TRANS('SEL_NONE'); ?></option>
                                                    <?php
                                                    $type_models = getAssetsModels($conn, null, $spec['tipo_cod']);
                                                    foreach ($type_models as $model) {
                                                        $modelSpecs = getModelSpecs($conn, $model['codigo']);
                                                        $subtext = "";
                                                        foreach ($modelSpecs as $modelSpec) {
                                                            if (strlen((string)$subtext))
                                                                $subtext .= " | ";
                                                            $subtext .= $modelSpec['mt_name'] . ': ' . $modelSpec['spec_value'] . '' . $modelSpec['unit_abbrev'];
                                                        }
                                                    ?>
                                                        <option data-subtext="<?= $subtext; ?>" value="<?= $model['codigo']; ?>"
                                                            <?= ($model['codigo'] == $spec['marc_cod'] ? " selected" : ""); ?>
                                                        ><?= $model['modelo']; ?></option>
                                                    <?php
                                                    }
                                                    ?>
                                                </select>
                                                <div class="input-group-append">
                                                    <div class="input-group-text manage_popups" data-location="equipments_models" data-params="action=new&asset_type=<?= $spec['tipo_cod']; ?>&this=<?= $thisId; ?>" title="<?= TRANS('NEW'); ?>" data-placeholder="<?= TRANS('NEW'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                                        <i class="fas fa-plus"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php
                                }

                                ?>
                                    <div class="w-100"></div>
                                <?php
                                /* Mesmo que não existam campos definidos no perfil, se o tipo puder possuir componentes eles poderão ser adicionados */
                                // $possibleChilds = getAssetsTypesPossibleChilds($conn, $asset_type);
                                if (!empty($possibleChilds)) {
                                    ?>
                                    <!-- Link para adicionar componentes -->
                                    <label class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('ADD'); ?></label>
                                    <div class="form-group col-md-10">
                                        <a href="javascript:void(0);" class="add_button_new_pieces" title="<?= TRANS('ADD'); ?>"><i class="fa fa-plus"></i></a>
                                    </div>
                                <?php
                                }


                                ?>
                            <?php
                        }
                    ?>

                    </div>
                    
                    <!-- Receberá as especificações excedentes de componentes -->
                    <div class="form-group row my-4 new_pieces" id="new_pieces"></div>
                    
                    <div class="form-group row my-4">
                    <?php


                    if ($hasCustomFields) {
                        /* Campos personalizados */
                        $labelColSize = 2;
                        $fieldColSize = 4;
                        $fieldRowSize = 10;
                        $custom_fields = getCustomFields($conn, null, 'equipamentos');

                        if (!empty($custom_fields) || $hasCustomFields) {
                        ?>
                            <div class="w-100"></div>
                            <h6 class="w-100 mt-5 ml-5 border-top p-4"><i class="fas fa-pencil-ruler text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('CUSTOM_FIELDS')); ?></h6>
                            <?php
                        }

                        foreach ($custom_fields as $cfield) {
                            
                            $maskType = ($cfield['field_mask_regex'] ? 'regex' : 'mask');
                            $fieldMask = "data-inputmask-" . $maskType . "=\"" . $cfield['field_mask'] . "\"";
                            $inlineAttributes = keyPairsToHtmlAttrs($cfield['field_attributes']);
                            $field_value = getAssetCustomFields($conn, $asset_id, $cfield['id']);

                            /* Controle de acordo com a opção global conf_cfield_only_opened */
                            if (!empty($field_value['field_id'])) {
                            ?>
                                <?= ($cfield['field_type'] == 'textarea' ? '<div class="w-100"></div>'  : ''); ?>
                                <label for="<?= $cfield['field_name']; ?>" class="col-sm-<?= $labelColSize; ?> col-md-<?= $labelColSize; ?> col-form-label col-form-label-sm text-md-right " title="<?= $cfield['field_title']; ?>" data-pop="popover" data-placement="top" data-trigger="hover" data-content="<?= $cfield['field_description']; ?>"><?= $cfield['field_label']; ?></label>
                                <div class="form-group col-md-<?= ($cfield['field_type'] == 'textarea' ? $fieldRowSize  : $fieldColSize); ?>">
                                    <?php
                                    if ($cfield['field_type'] == 'select') {
                                    ?>
                                        <select class="form-control custom_field_select" name="<?= $cfield['field_name']; ?>" id="<?= $cfield['field_name']; ?>" <?= $inlineAttributes; ?>>
                                            <?php

                                            $options = [];
                                            $options = getCustomFieldOptionValues($conn, $cfield['id']);
                                            ?>
                                            <option value=""><?= TRANS('SEL_SELECT'); ?></option>
                                            <?php
                                            foreach ($options as $cfieldValues) {
                                            ?>
                                                <option value="<?= $cfieldValues['id']; ?>" <?= ($cfieldValues['id'] == $field_value['field_value_idx'] ? " selected" : ""); ?>><?= $cfieldValues['option_value']; ?></option>
                                            <?php
                                            }
                                            ?>
                                        </select>
                                    <?php
                                    } elseif ($cfield['field_type'] == 'select_multi') {
                                    ?>
                                        <select class="form-control custom_field_select_multi" name="<?= $cfield['field_name']; ?>[]" id="<?= $cfield['field_name']; ?>" multiple="multiple" <?= $inlineAttributes; ?>>
                                            <?php

                                            $options = [];
                                            $options = getCustomFieldOptionValues($conn, $cfield['id']);
                                            $defaultSelections = explode(',', $field_value['field_value_idx']);

                                            ?>
                                            <option value=""><?= TRANS('SEL_SELECT'); ?></option>
                                            <?php
                                            foreach ($options as $cfieldValues) {
                                            ?>
                                                <option value="<?= $cfieldValues['id']; ?>" <?= (in_array($cfieldValues['id'], $defaultSelections) ? ' selected' : ''); ?>><?= $cfieldValues['option_value']; ?></option>
                                            <?php
                                            }
                                            ?>
                                        </select>
                                    <?php
                                    } elseif ($cfield['field_type'] == 'number') {
                                    ?>
                                        <input class="form-control custom_field_number" type="number" name="<?= $cfield['field_name']; ?>" id="<?= $cfield['field_name']; ?>" value="<?= $field_value['field_value']; ?>" placeholder="<?= $cfield['field_placeholder']; ?>" <?= $inlineAttributes; ?>>
                                    <?php
                                    } elseif ($cfield['field_type'] == 'checkbox') {
                                        $checked_checkbox = ($field_value['field_value'] == "on" ? " checked" : "");
                                    ?>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input custom_field_checkbox" type="checkbox" name="<?= $cfield['field_name']; ?>" id="<?= $cfield['field_name']; ?>" <?= $checked_checkbox; ?> placeholder="<?= $cfield['field_placeholder']; ?>" <?= $inlineAttributes; ?>>
                                            <legend class="col-form-label col-form-label-sm"><?= $cfield['field_placeholder']; ?></legend>
                                        </div>
                                    <?php
                                    } elseif ($cfield['field_type'] == 'textarea') {
                                    ?>
                                        <textarea class="form-control custom_field_textarea" name="<?= $cfield['field_name']; ?>" id="<?= $cfield['field_name']; ?>" placeholder="<?= $cfield['field_placeholder']; ?>" <?= $inlineAttributes; ?>><?= $field_value['field_value']; ?></textarea>
                                    <?php
                                    } elseif ($cfield['field_type'] == 'date') {
                                    ?>
                                        <input class="form-control custom_field_date" type="text" name="<?= $cfield['field_name']; ?>" id="<?= $cfield['field_name']; ?>" value="<?= dateScreen($field_value['field_value'], 1); ?>" placeholder="<?= $cfield['field_placeholder']; ?>" <?= $inlineAttributes; ?> autocomplete="off">
                                    <?php
                                    } elseif ($cfield['field_type'] == 'time') {
                                    ?>
                                        <input class="form-control custom_field_time" type="text" name="<?= $cfield['field_name']; ?>" id="<?= $cfield['field_name']; ?>" value="<?= $field_value['field_value']; ?>" placeholder="<?= $cfield['field_placeholder']; ?>" <?= $inlineAttributes; ?> autocomplete="off">
                                    <?php
                                    } elseif ($cfield['field_type'] == 'datetime') {
                                    ?>
                                        <input class="form-control custom_field_datetime" type="text" name="<?= $cfield['field_name']; ?>" id="<?= $cfield['field_name']; ?>" value="<?= dateScreen($field_value['field_value'], 0, 'd/m/Y H:i'); ?>" placeholder="<?= $cfield['field_placeholder']; ?>" <?= $inlineAttributes; ?> autocomplete="off">
                                    <?php
                                    } else {
                                    ?>
                                        <input class="form-control custom_field_text" type="text" name="<?= $cfield['field_name']; ?>" id="<?= $cfield['field_name']; ?>" value="<?= $field_value['field_value']; ?>" placeholder="<?= $cfield['field_placeholder']; ?>" <?= $fieldMask; ?> <?= $inlineAttributes; ?> autocomplete="off">
                                    <?php
                                    }
                                    ?>
                                </div>

                        <?php
                                /* Fim do controle de acordo com a configuração global */
                            }
                        }
                        ?>
                        <div class="w-100"></div>
                        <?php
                        /* Fim dos campos personalizados */
                    }




                        /* Para checagem se o ativo possui configurações estáticas das versões anteriores' */
                        $hasDeprecatedConfigs = false;
                        $hasDeprecatedPrinterConfigs = false;
                        
                        $deprecatedConfigs = [
                            $row['fabricante_mb'],
                            $row['processador'],
                            $row['memoria'],
                            $row['fabricante_video'],
                            $row['fabricante_som'],
                            $row['rede_fabricante'],
                            $row['fabricante_modem'],
                            $row['fabricante_hd'],
                            $row['fabricante_cdrom'],
                            $row['fabricante_gravador'],
                            $row['fabricante_dvd']
                        ];

                        $deprecatedPrinterConfigs = [
                            $row['impressora'],
                            $row['polegada_nome'],
                            $row['resol_nome']
                        ];

                        
                        foreach ($deprecatedConfigs as $key => $value) {
                            if ($value) {
                                $hasDeprecatedConfigs = true;
                                break;
                            }
                        }

                        foreach ($deprecatedPrinterConfigs as $key => $value) {
                            if ($value) {
                                $hasDeprecatedPrinterConfigs = true;
                                break;
                            }
                        }

                        if ($hasDeprecatedConfigs) {
                            ?>
                                <h6 class="w-100 mt-5 ml-5 border-top p-4"><i class="fas fa-hdd text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('LEGACY_INFO')); ?>&nbsp;<span class="badge badge-warning text-small"><?= TRANS('DEPRECATED'); ?></span></h6>


                                <?php
                                    if (!empty($row['cod_mb'])) {
                                        ?>
                                            <label for="motherboard" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('MOTHERBOARD'); ?></label>
                                            <div class="form-group col-md-4">
                                                <select class="form-control bs-select" id="motherboard" name="motherboard" >
                                                    <option value=""><?= TRANS('SEL_NONE'); ?></option>
                                                    <?php
                                                    $sql = "SELECT * FROM modelos_itens WHERE mdit_tipo = 10 ORDER BY mdit_fabricante, mdit_desc";
                                                    $exec_sql = $conn->query($sql);
                                                    foreach ($exec_sql->fetchAll() as $rowType) {
                                                        ?>
                                                        <option value="<?= $rowType['mdit_cod']; ?>"
                                                        <?= ($row['cod_mb'] == $rowType['mdit_cod'] ? ' selected' : ''); ?>
                                                        ><?= $rowType['mdit_fabricante'] . " " . $rowType['mdit_desc'] . " " . $rowType['mdit_desc_capacidade'] . "" . $rowType['mdit_sufixo']; ?></option>
                                                        <?php
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        <?php
                                    }
                                
                                    if (!empty($row['cod_processador'])) {
                                        ?>
                                            <label for="processor" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('PROCESSOR'); ?></label>
                                            <div class="form-group col-md-4">
                                                <select class="form-control bs-select" id="processor" name="processor" >
                                                    <option value=""><?= TRANS('SEL_NONE'); ?></option>
                                                    <?php
                                                    $sql = "SELECT * FROM modelos_itens WHERE mdit_tipo = 11 ORDER BY mdit_fabricante, mdit_desc";
                                                    $exec_sql = $conn->query($sql);
                                                    foreach ($exec_sql->fetchAll() as $rowType) {
                                                        ?>
                                                        <option value="<?= $rowType['mdit_cod']; ?>"
                                                        <?= ($row['cod_processador'] == $rowType['mdit_cod'] ? ' selected' : ''); ?>
                                                        ><?= $rowType['mdit_fabricante'] . " " . $rowType['mdit_desc'] . " " . $rowType['mdit_desc_capacidade'] . "" . $rowType['mdit_sufixo']; ?></option>
                                                        <?php
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        <?php
                                    }
                                
                                    if (!empty($row['cod_memoria'])) {
                                        ?>
                                            <label for="memory" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CARD_MEMORY'); ?></label>
                                            <div class="form-group col-md-4">
                                                <select class="form-control bs-select" id="memory" name="memory" >
                                                    <option value=""><?= TRANS('SEL_NONE'); ?></option>
                                                    <?php
                                                    $sql = "SELECT * FROM modelos_itens WHERE mdit_tipo = 7 ORDER BY mdit_fabricante, mdit_desc";
                                                    $exec_sql = $conn->query($sql);
                                                    foreach ($exec_sql->fetchAll() as $rowType) {
                                                        ?>
                                                        <option value="<?= $rowType['mdit_cod']; ?>"
                                                        <?= ($row['cod_memoria'] == $rowType['mdit_cod'] ? ' selected' : ''); ?>
                                                        ><?= $rowType['mdit_fabricante'] . " " . $rowType['mdit_desc'] . " " . $rowType['mdit_desc_capacidade'] . "" . $rowType['mdit_sufixo']; ?></option>
                                                        <?php
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        <?php
                                    }

                                    if (!empty($row['cod_video'])) {
                                        ?>
                                             <label for="video" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CARD_VIDEO'); ?></label>
                                            <div class="form-group col-md-4">
                                                <select class="form-control bs-select" id="video" name="video" >
                                                    <option value=""><?= TRANS('SEL_NONE'); ?></option>
                                                    <?php
                                                    $sql = "SELECT * FROM modelos_itens WHERE mdit_tipo = 2 ORDER BY mdit_fabricante, mdit_desc";
                                                    $exec_sql = $conn->query($sql);
                                                    foreach ($exec_sql->fetchAll() as $rowType) {
                                                        ?>
                                                        <option value="<?= $rowType['mdit_cod']; ?>"
                                                        <?= ($row['cod_video'] == $rowType['mdit_cod'] ? ' selected' : ''); ?>
                                                        ><?= $rowType['mdit_fabricante'] . " " . $rowType['mdit_desc'] . " " . $rowType['mdit_desc_capacidade'] . "" . $rowType['mdit_sufixo']; ?></option>
                                                        <?php
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        <?php
                                    }

                                    if (!empty($row['cod_som'])) {
                                        ?>
                                            <label for="sound" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CARD_SOUND'); ?></label>
                                            <div class="form-group col-md-4">
                                                <select class="form-control bs-select" id="sound" name="sound" >
                                                    <option value=""><?= TRANS('SEL_NONE'); ?></option>
                                                    <?php
                                                    $sql = "SELECT * FROM modelos_itens WHERE mdit_tipo = 4 ORDER BY mdit_fabricante, mdit_desc";
                                                    $exec_sql = $conn->query($sql);
                                                    foreach ($exec_sql->fetchAll() as $rowType) {
                                                        ?>
                                                        <option value="<?= $rowType['mdit_cod']; ?>"
                                                        <?= ($row['cod_som'] == $rowType['mdit_cod'] ? ' selected' : ''); ?>
                                                        ><?= $rowType['mdit_fabricante'] . " " . $rowType['mdit_desc'] . " " . $rowType['mdit_desc_capacidade'] . "" . $rowType['mdit_sufixo']; ?></option>
                                                        <?php
                                                    }
                                                    ?>
                                                </select>
                                            </div>

                                        <?php
                                    }

                                    if (!empty($row['cod_rede'])) {
                                        ?>
                                            <label for="network" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CARD_NETWORK'); ?></label>
                                            <div class="form-group col-md-4">
                                                <select class="form-control bs-select" id="network" name="network" >
                                                    <option value=""><?= TRANS('SEL_NONE'); ?></option>
                                                    <?php
                                                    $sql = "SELECT * FROM modelos_itens WHERE mdit_tipo = 3 ORDER BY mdit_fabricante, mdit_desc";
                                                    $exec_sql = $conn->query($sql);
                                                    foreach ($exec_sql->fetchAll() as $rowType) {
                                                        ?>
                                                        <option value="<?= $rowType['mdit_cod']; ?>"
                                                        <?= ($row['cod_rede'] == $rowType['mdit_cod'] ? ' selected' : ''); ?>
                                                        ><?= $rowType['mdit_fabricante'] . " " . $rowType['mdit_desc'] . " " . $rowType['mdit_desc_capacidade'] . "" . $rowType['mdit_sufixo']; ?></option>
                                                        <?php
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        <?php
                                    }

                                    if (!empty($row['cod_modem'])) {
                                        ?>
                                            <label for="modem" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CARD_MODEN'); ?></label>
                                            <div class="form-group col-md-4">
                                                <select class="form-control bs-select" id="modem" name="modem" >
                                                    <option value=""><?= TRANS('SEL_NONE'); ?></option>
                                                    <?php
                                                    $sql = "SELECT * FROM modelos_itens WHERE mdit_tipo = 6 ORDER BY mdit_fabricante, mdit_desc";
                                                    $exec_sql = $conn->query($sql);
                                                    foreach ($exec_sql->fetchAll() as $rowType) {
                                                        ?>
                                                        <option value="<?= $rowType['mdit_cod']; ?>"
                                                        <?= ($row['cod_modem'] == $rowType['mdit_cod'] ? ' selected' : ''); ?>
                                                        ><?= $rowType['mdit_fabricante'] . " " . $rowType['mdit_desc'] . " " . $rowType['mdit_desc_capacidade'] . "" . $rowType['mdit_sufixo']; ?></option>
                                                        <?php
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        <?php
                                    }

                                    if (!empty($row['cod_hd'])) {
                                        ?>
                                            <label for="hdd" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('MNL_HD'); ?></label>
                                            <div class="form-group col-md-4">
                                                <select class="form-control bs-select" id="hdd" name="hdd" >
                                                    <option value=""><?= TRANS('SEL_NONE'); ?></option>
                                                    <?php
                                                    $sql = "SELECT * FROM modelos_itens WHERE mdit_tipo = 1 ORDER BY mdit_fabricante, mdit_desc";
                                                    $exec_sql = $conn->query($sql);
                                                    foreach ($exec_sql->fetchAll() as $rowType) {
                                                        ?>
                                                        <option value="<?= $rowType['mdit_cod']; ?>"
                                                        <?= ($row['cod_hd'] == $rowType['mdit_cod'] ? ' selected' : ''); ?>
                                                        ><?= $rowType['mdit_fabricante'] . " " . $rowType['mdit_desc'] . " " . $rowType['mdit_desc_capacidade'] . "" . $rowType['mdit_sufixo']; ?></option>
                                                        <?php
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        <?php
                                    }

                                    if (!empty($row['cod_gravador'])) {
                                        ?>
                                            <label for="recorder" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_RECORD_CD'); ?></label>
                                            <div class="form-group col-md-4">
                                                <select class="form-control bs-select" id="recorder" name="recorder" >
                                                    <option value=""><?= TRANS('SEL_NONE'); ?></option>
                                                    <?php
                                                    $sql = "SELECT * FROM modelos_itens WHERE mdit_tipo = 9 ORDER BY mdit_fabricante, mdit_desc";
                                                    $exec_sql = $conn->query($sql);
                                                    foreach ($exec_sql->fetchAll() as $rowType) {
                                                        ?>
                                                        <option value="<?= $rowType['mdit_cod']; ?>"
                                                        <?= ($row['cod_gravador'] == $rowType['mdit_cod'] ? ' selected' : ''); ?>
                                                        ><?= $rowType['mdit_fabricante'] . " " . $rowType['mdit_desc'] . " " . $rowType['mdit_desc_capacidade'] . "" . $rowType['mdit_sufixo']; ?></option>
                                                        <?php
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        <?php
                                    }

                                    if (!empty($row['cod_cdrom'])) {
                                        ?>
                                            <label for="cdrom" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_CDROM'); ?></label>
                                            <div class="form-group col-md-4">
                                                <select class="form-control bs-select" id="cdrom" name="cdrom" >
                                                    <option value=""><?= TRANS('SEL_NONE'); ?></option>
                                                    <?php
                                                    $sql = "SELECT * FROM modelos_itens WHERE mdit_tipo = 5 ORDER BY mdit_fabricante, mdit_desc";
                                                    $exec_sql = $conn->query($sql);
                                                    foreach ($exec_sql->fetchAll() as $rowType) {
                                                        ?>
                                                        <option value="<?= $rowType['mdit_cod']; ?>"
                                                        <?= ($row['cod_cdrom'] == $rowType['mdit_cod'] ? ' selected' : ''); ?>
                                                        ><?= $rowType['mdit_fabricante'] . " " . $rowType['mdit_desc'] . " " . $rowType['mdit_desc_capacidade'] . "" . $rowType['mdit_sufixo']; ?></option>
                                                        <?php
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        <?php
                                    }

                                    if (!empty($row['cod_dvd'])) {
                                        ?>
                                            <label for="dvdrom" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('DVD'); ?></label>
                                            <div class="form-group col-md-4">
                                                <select class="form-control bs-select" id="dvdrom" name="dvdrom" >
                                                    <option value=""><?= TRANS('SEL_NONE'); ?></option>
                                                    <?php
                                                    $sql = "SELECT * FROM modelos_itens WHERE mdit_tipo = 8 ORDER BY mdit_fabricante, mdit_desc";
                                                    $exec_sql = $conn->query($sql);
                                                    foreach ($exec_sql->fetchAll() as $rowType) {
                                                        ?>
                                                        <option value="<?= $rowType['mdit_cod']; ?>"
                                                        <?= ($row['cod_dvd'] == $rowType['mdit_cod'] ? ' selected' : ''); ?>
                                                        ><?= $rowType['mdit_fabricante'] . " " . $rowType['mdit_desc'] . " " . $rowType['mdit_desc_capacidade'] . "" . $rowType['mdit_sufixo']; ?></option>
                                                        <?php
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        <?php
                                    }
                        }

                        if ($hasDeprecatedPrinterConfigs) {
                            ?>
                                <h6 class="w-100 mt-5 ml-5 border-top p-4"><i class="fas fa-print text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('LEGACY_INFO')); ?>&nbsp;<span class="badge badge-warning text-small"><?= TRANS('DEPRECATED'); ?></span></h6>

                                <?php
                                    if (!empty($row['tipo_imp'])) {
                                        ?>
                                            <label for="printer_type" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_TYPE_PRINTER'); ?></label>
                                            <div class="form-group col-md-4">
                                                <select class="form-control bs-select" id="printer_type" name="printer_type" >
                                                    <option value=""><?= TRANS('SEL_TYPE_PRINTER'); ?></option>
                                                    <?php
                                                    $sql = "SELECT * FROM tipo_imp ORDER BY tipo_imp_nome";
                                                    $exec_sql = $conn->query($sql);
                                                    foreach ($exec_sql->fetchAll() as $rowType) {
                                                        ?>
                                                        <option value="<?= $rowType['tipo_imp_cod']; ?>"
                                                        <?= ($row['tipo_imp'] == $rowType['tipo_imp_cod'] ? ' selected' : ''); ?>
                                                        ><?= $rowType['tipo_imp_nome']; ?></option>
                                                        <?php
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        <?php
                                    }

                                    if (!empty($row['polegada_cod'])) {
                                        ?>
                                            <label for="monitor_size" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_MONITOR'); ?></label>
                                            <div class="form-group col-md-4">
                                                <select class="form-control bs-select" id="monitor_size" name="monitor_size" >
                                                    <option value=""><?= TRANS('SEL_SIZE_MONITOR'); ?></option>
                                                    <?php
                                                    $sql = "SELECT * FROM polegada ORDER BY pole_nome";
                                                    $exec_sql = $conn->query($sql);
                                                    foreach ($exec_sql->fetchAll() as $rowType) {
                                                        ?>
                                                        <option value="<?= $rowType['pole_cod']; ?>"
                                                        <?= ($row['polegada_cod'] == $rowType['pole_cod'] ? ' selected' : ''); ?>
                                                        ><?= $rowType['pole_nome']; ?></option>
                                                        <?php
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        <?php
                                    }

                                    if (!empty($row['resolucao_cod'])) {
                                        ?>
                                            <label for="scanner_resolution" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_SCANNER'); ?></label>
                                            <div class="form-group col-md-4">
                                                <select class="form-control bs-select" id="scanner_resolution" name="scanner_resolution" >
                                                    <option value=""><?= TRANS('SEL_RESOLUT_SCANNER'); ?></option>
                                                    <?php
                                                    $sql = "SELECT * FROM resolucao ORDER BY resol_nome";
                                                    $exec_sql = $conn->query($sql);
                                                    foreach ($exec_sql->fetchAll() as $rowType) {
                                                        ?>
                                                        <option value="<?= $rowType['resol_cod']; ?>"
                                                        <?= ($row['resolucao_cod'] == $rowType['resol_cod'] ? ' selected' : ''); ?>
                                                        ><?= $rowType['resol_nome']; ?></option>
                                                        <?php
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        <?php
                                    }
                        }
                    ?>

                    <h6 class="w-100 mt-5 ml-5 border-top p-4"><i class="fas fa-file-invoice-dollar text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('TXT_OBS_DATA_COMPLEM_2')); ?></h6>


                    <label for="invoice_number" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_NF'); ?></label>
					<div class="form-group col-md-4">
						<input type="text" class="form-control " id="invoice_number" name="invoice_number" value="<?= $row['nota']; ?>" />
					</div>


					<label for="cost_center" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COST_CENTER'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control bs-select" id="cost_center" name="cost_center" >
                            <option value=""><?= TRANS('COST_CENTER'); ?></option>
                            <?php
                            $sql = "SELECT * FROM `" . DB_CCUSTO . "`." . TB_CCUSTO . "  ORDER BY " . CCUSTO_DESC . "";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                                ?>
								<option value="<?= $rowType[CCUSTO_ID]; ?>"
                                <?= ($row['ccusto'] == $rowType[CCUSTO_ID] ? ' selected' : ''); ?>
                                ><?= $rowType[CCUSTO_DESC] . " - " . $rowType[CCUSTO_COD]; ?></option>
                                <?php
                            }
                            ?>
                        </select>
					</div>
                    <input type="hidden" name="costcenterDb" id="costcenterDb" value="<?= $row['ccusto']; ?>">

                    <label for="price" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_VALUE'); ?></label>
					<div class="form-group col-md-4">
						<input type="text" class="form-control " id="price" name="price" value="<?= priceScreen($row['valor']); ?>" />
					</div>
					
                    <label for="buy_date" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('PURCHASE_DATE'); ?></label>
					<div class="form-group col-md-4">
						<input type="text" class="form-control " id="buy_date" name="buy_date" autocomplete="off" value="<?= dateScreen($row['data_compra'],1); ?>" />
					</div>


                    <label for="supplier" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_VENDOR'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control bs-select" id="supplier" name="supplier" >
                            <option value=""><?= TRANS('OCO_SEL_VENDOR'); ?></option>
                            <?php
                            $sql = "SELECT * FROM fornecedores ORDER BY forn_nome";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                                ?>
								<option value="<?= $rowType['forn_cod']; ?>"
                                <?= ($row['fornecedor_cod'] == $rowType['forn_cod'] ? ' selected' : ''); ?>
                                ><?= $rowType['forn_nome']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
					</div>

                    <label for="assistance" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('ASSISTENCE'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control bs-select" id="assistance" name="assistance" >
                            <option value=""><?= TRANS('SEL_TYPE_ASSIST'); ?></option>
                            <?php
                            $sql = "SELECT * FROM assistencia ORDER BY assist_desc";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                                ?>
								<option value="<?= $rowType['assist_cod']; ?>"
                                <?= ($row['assistencia_cod'] == $rowType['assist_cod'] ? ' selected' : ''); ?>
                                ><?= $rowType['assist_desc']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
					</div>

					<label for="warranty_type" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_TYPE_WARRANTY'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control bs-select" id="warranty_type" name="warranty_type" >
                            <option value=""><?= TRANS('SEL_WARRANTY_TYPE'); ?></option>
                            <?php
                            $sql = "SELECT * FROM tipo_garantia ORDER BY tipo_garant_nome";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                                ?>
								<option value="<?= $rowType['tipo_garant_cod']; ?>"
                                <?= ($row['garantia_cod'] == $rowType['tipo_garant_cod'] ? ' selected' : ''); ?>
                                ><?= $rowType['tipo_garant_nome']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
					</div>
					
					<label for="time_of_warranty" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('OCO_SEL_WARRANTY'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control bs-select" id="time_of_warranty" name="time_of_warranty" >
                            <option value=""><?= TRANS('FIELD_TIME_MONTH'); ?></option>
                            <?php
                            $sql = "SELECT * FROM tempo_garantia ORDER BY tempo_meses";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                                ?>
								<option value="<?= $rowType['tempo_cod']; ?>"
                                <?= ($row['tempo_cod'] == $rowType['tempo_cod'] ? ' selected' : ''); ?>
                                ><?= $rowType['tempo_meses'] . ' ' . TRANS('MONTHS'); ?></option>
                                <?php
                            }
                            ?>
                        </select>
					</div>

                    <div class="w-100"></div>
					<label for="extra_info" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('ENTRY_TYPE_ADDITIONAL_INFO'); ?></label>
					<div class="form-group col-md-10">
						<textarea class="form-control " id="extra_info" name="extra_info"><?= $row['comentario']; ?></textarea>
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

						<input type="hidden" name="action" id="action" value="edit">
						<input type="hidden" name="asset_type" id="asset_type" value="<?= $asset_type; ?>">
						<input type="hidden" name="asset_id" id="asset_id" value="<?= $asset_id; ?>">
                        <input type="hidden" name="cont" value="<?= $cont; ?>" />
						<input type="hidden" name="cod" id="cod" value="<?= $row['comp_cod']; ?>">
						<input type="hidden" name="old_department" id="old_department" value="<?= $row['tipo_local']; ?>">
						<input type="hidden" name="old_unit" id="old_unit" value="<?= $asset_unit; ?>">
						<input type="hidden" name="old_tag" id="old_tag" value="<?= $asset_tag; ?>">
                        <input type="hidden" name="model_selected" value="<?= $row['modelo_cod']; ?>" id="model_selected"/>
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
    <script src="../../includes/components/jquery/plentz-jquery-maskmoney/dist/jquery.maskMoney.min.js"></script>
    <script src="../../includes/components/jquery/jquery.initialize.min.js"></script>
    <script src="../../includes/components/jquery/datetimepicker/build/jquery.datetimepicker.full.min.js"></script>
	<script src="../../includes/components/bootstrap/js/bootstrap.bundle.js"></script>
	<script src="../../includes/components/bootstrap-select/dist/js/bootstrap-select.min.js"></script>
	<script type="text/javascript" charset="utf8" src="../../includes/components/datatables/datatables.js"></script>
    <script src="../../includes/components/Inputmask-5.x/dist/jquery.inputmask.min.js"></script>
	<script src="../../includes/components/Inputmask-5.x/dist/bindings/inputmask.binding.js"></script>
	<script type="text/javascript">
		$(function() {

            // $('.bs-select').addClass('new-select2');

            $('.bs-select').selectpicker({
				/* placeholder */
				title: "<?= TRANS('SEL_SELECT', '', 1); ?>",
                showSubtext: true,
				liveSearch: true,
				liveSearchNormalize: true,
				liveSearchPlaceholder: "<?= TRANS('BT_SEARCH', '', 1); ?>",
				noneResultsText: "<?= TRANS('NO_RECORDS_FOUND', '', 1); ?> {0}",
				
				style: "",
				styleBase: "form-control input-select-multi",
			});


            if ($('#new_pieces').length > 0) {
                /* Adicionei o mutation observer em função dos elementos que são adicionados após o carregamento do DOM */
                var afterDom1 = $.initialize(".after-dom-ready", function() {
					
					$('.bs-select').selectpicker({
						/* placeholder */
                        title: "<?= TRANS('SEL_SELECT', '', 1); ?>",
                        showSubtext: true,
                        liveSearch: true,
                        liveSearchNormalize: true,
                        liveSearchPlaceholder: "<?= TRANS('BT_SEARCH', '', 1); ?>",
                        noneResultsText: "<?= TRANS('NO_RECORDS_FOUND', '', 1); ?> {0}",
                        
                        style: "",
                        styleBase: "form-control input-select-multi",
					});

                    $('.after-dom-ready').on('change', function() {
						var myId = $(this).attr('id');
                        loadModelsByNewPiece(myId);
					});

                    $('.manage_popups_after_dom').off().css('cursor', 'pointer').on('click', function() {
                        
                        let params = $(this).attr('data-params');
                        let this_field = $(this).attr('data-model_id');
                        let type_id = $(this).attr('data-type_id');
                        let asset_type = $('#'+type_id).val();

                        loadInPopup($(this).attr('data-location'), params+'&asset_type='+asset_type+'&this='+this_field);
                    });

                }, {
                    target: document.getElementById('new_pieces')
                }); /* o target limita o scopo do observer */
            }





			/* Carregamento dos modelos com base na seleção de tipo */
			showModelsByType($('#model_selected').val() ?? '');
			// $('#type').on('change', function() {
			// 	showModelsByType();
			// });
            /* Final do carregamento dos modelos */
            

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

            /* Idioma global para os calendários */
			$.datetimepicker.setLocale('pt-BR');
            /* Para campos personalizados - bind pela classe*/
            $('#buy_date').datetimepicker({
                timepicker: false,
                format: 'd/m/Y',
                lazyInit: true
            });

			/* Trazer os parâmetros do banco a partir da opção que será criada para internacionaliação */
			$('#price').maskMoney({
                prefix:'R$ ',
                thousands:'.', 
                decimal:',', 
                allowZero: false, 
                affixesStay: false
            });

            loadCostCenters();

            $("#client").on('change', function() {
				loadUnits();
                loadCostCenters();
			});

			$("#asset_unit").on('change', function() {
				loadDepartments();
			});

            $('.manage_popups').css('cursor', 'pointer').on('click', function() {
				loadInPopup($(this).attr('data-location'), $(this).attr('data-params'));
			});


            $('.add_button_new_pieces').on('click', function() {
                loadNewSpecField();
			});

            $('.new_pieces').on('click', '.remove_button_specs', function(e) {
                e.preventDefault();
				dataRandom = $(this).attr('data-random');
				$("."+dataRandom).remove();
				// availablesMeasureTypesControl();
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
                $("#client").prop("disabled", false);
                $("#asset_unit").prop("disabled", false);
                $("#department").prop("disabled", false);
				$("#idSubmit").prop("disabled", true);
				$.ajax({
					url: './assets_process.php',
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
						var url = 'asset_show.php?asset_id=' + $('#asset_id').val();
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


		function showModelsByType_old (selected_id = '') {
			/* Popular os modelos de acordo com o tipo selecionado */
			if ($('#model').length > 0) {
				
				var loading = $(".loading");
				$(document).ajaxStart(function() {
					loading.show();
				});
				$(document).ajaxStop(function() {
					loading.hide();
				});
				
				$.ajax({
					url: './get_models_by_type_of_equipment.php',
					method: 'POST',
					dataType: 'json',
					data: {
						type: $('#asset_type').val(),
						model_selected: $('#model_selected').val() ?? '',
					},
				}).done(function(response) {
					$('#model').empty().append('<option value=""><?= TRANS('SEL_MODEL'); ?></option>');
					for (var i in response) {
						var option = '<option value="' + response[i].marc_cod + '">' + response[i].marc_nome + '</option>';
						$('#model').append(option);

						if (selected_id !== '') {
							// $('#model').val(selected_id).change();
                            $('#model').selectpicker('refresh').selectpicker('val', selected_id);

						}
					}
                    $('#model').selectpicker('refresh');

				});
			}
		}

		function showModelsByType (selected_id = '') {
			/* Popular os modelos de acordo com o tipo selecionado */
			if ($('#model').length > 0) {
				
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
					dataType: 'json',
					data: {
						asset_type: $('#asset_type').val(),
						model_selected: $('#model_selected').val() ?? '',
					},
				}).done(function(response) {
					$('#model').empty().append('<option value=""><?= TRANS('SEL_MODEL'); ?></option>');
                    
                    let html = '';
                    
                    if (response.length > 0) {
                        for (i in response) {
                            html += '<option data-subtext="' + response[i].spec + '" value="' + response[i].codigo + '">' + response[i].modelo + '</option>';
                        }
                    }

                    $('#model').append(html);

                    if (selected_id !== '') {
                        $('#model').selectpicker('refresh').selectpicker('val', selected_id);
					}
                    $('#model').selectpicker('refresh');

				});
			}
		}


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
                    client: $("#client").val()
                },
            }).done(function(data) {
                $('#asset_unit').empty();
                if (Object.keys(data).length > 1) {
                    $('#asset_unit').append('<option value=""><?= TRANS("SEL_SELECT"); ?></option>');
                }
                $.each(data, function(key, data) {
                    $('#asset_unit').append('<option value="' + data.inst_cod + '">' + data.inst_nome + '</option>');
                });

                $('#asset_unit').selectpicker('refresh');
                loadDepartments();
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
            });
        }

        function loadCostCenters(targetId = 'cost_center') {

            if ($('#cost_center').length > 0) {
                var loading = $(".loading");
                $(document).ajaxStart(function() {
                    loading.show();
                });
                $(document).ajaxStop(function() {
                    loading.hide();
                });

                $.ajax({
                    url: '../../ocomon/geral/get_costcenters_by_client.php',
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        client: $("#client").val()
                    },
                }).done(function(data) {
                    let costcenterDb = $('#costcenterDb').val();
                    $('#' + targetId).empty();
                    if (Object.keys(data).length > 1) {
                        $('#' + targetId).append('<option value=""><?= TRANS("SEL_SELECT"); ?></option>');
                    }
                    $.each(data, function(key, data) {
                        $('#' + targetId).append('<option data-subtext="' + data.ccusto_cod + '" value="' + data.ccusto_id + '">' + data.ccusto_name + '</option>');
                    });

                    $('#' + targetId).selectpicker('refresh');

                    $('#' + targetId).selectpicker('val', costcenterDb);
                    // if ($('#parent_id').val() != '') {
                    //     $('#' + targetId).selectpicker('val', $('#parent_unit').val());
                    //     $('#' + targetId).selectpicker('refresh');
                    // }
                });
            }
        }

        function loadNewSpecField() {
            var loading = $(".loading");
            $(document).ajaxStart(function() {
                loading.show();
            });
            $(document).ajaxStop(function() {
                loading.hide();
            });

            $.ajax({
                url: './render_new_spec_field_to_add_asset.php',
                method: 'POST',
                data: {
                    asset_type: $('#asset_type').val(),
                    random: Math.random().toString(16).substr(2, 8)
                },
                // dataType: 'json',
            }).done(function(data) {
                $('#new_pieces').append(data);
            });
            return false;
		}

        function loadModelsByNewPiece(elementID) {
			if ($('#new_pieces').length > 0) {
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
						asset_type: $('#'+elementID).val(),
					},
					dataType: 'json',
				}).done(function(data) {
					let html = '';
                    
                    if (data.length > 0) {
                        for (i in data) {
                            html += '<option data-subtext="' + data[i].spec + '" value="' + data[i].codigo + '">' + data[i].modelo + '</option>';
                        }
                    }
					/* Para conseguir mapear os ids que vêm após o carregamento do DOM, 
					criei a regra de duplicar o ID para o segundo campo - Assim só preciso passar um parâmetro para a função */
					$('#'+elementID+'_'+elementID).empty().html(html);
					$('#'+elementID+'_'+elementID).selectpicker('refresh');
					$('#'+elementID+'_'+elementID).selectpicker('render');
				});
				return false;
			}
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
                    $('#'+field).empty().html(html);
                    $('#'+field).selectpicker('refresh');
                }
                
            });
            return false;
		}

        function loadInPopup(pageBase, params) {
            let url = pageBase + '.php?' + params;
            x = window.open(url,'','dependent=yes,width=800,scrollbars=yes,statusbar=no,resizable=yes');
		    x.moveTo(10,10);
		}

	</script>
</body>

</html>