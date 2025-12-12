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

$asset_type = (isset($_GET['asset_type']) ? $_GET['asset_type'] : "");
$asset_manufacturer = (isset($_GET['asset_manufacturer']) ? $_GET['asset_manufacturer'] : "");
$asset_model = (isset($_GET['asset_model']) ? $_GET['asset_model'] : "");
$profile_id = (isset($_GET['profile_id']) ? $_GET['profile_id'] : "");


if (empty($asset_type) || empty($asset_manufacturer) || empty($asset_model)) {
	header("Location: ./choose_asset_type_to_add.php");
	return;
}

$asset_type_info = getAssetsTypes($conn, $asset_type);
$asset_manufacturer_info = getManufacturers($conn, $asset_manufacturer);
$asset_model_info = getAssetsModels($conn, $asset_model);

/* Para saber se esse ativo pode ser vinculado a outro ativo */
$possibleParents = getAssetsTypesPossibleParents($conn, $asset_type);
$hasPossibleParents = !empty($possibleParents);

if ($profile_id != "0") {
    $default_profile = getAssetsProfiles($conn, $profile_id);
} else {
    $default_profile = setBasicProfile();
}

$saved_specs = [];
$parent_tag = "";
$parent_unit = "";
$parent_department = "";
$parent_client = "";
$parent_id = (isset($_GET['parent_id']) && !empty($_GET['parent_id']) ? noHtml($_GET['parent_id']) : '');
if (!empty($parent_id)) {
     /* Indica que o ativo será filho de outro ativo */
    $parent_info = getEquipmentInfo($conn, null, null, $parent_id);
    $parent_tag = $parent_info['comp_inv'];
    $parent_unit = $parent_info['comp_inst'];
    $parent_client = getUnits($conn, null, $parent_unit)['id'];
    $parent_department = $parent_info['comp_local'];
}

/* Se deve carregar as configurações salvas para o modelo */
$load_saved_config = (isset($_GET['load_saved_config']) && $_GET['load_saved_config'] == 1 ? true : false);
$hasSavedSpecs = modelHasSavedSpecs($conn, $asset_model);
/* Dupla checagem */
$loadSavedSpecs = ($hasSavedSpecs && $load_saved_config);

$saved_model_info = [];
if ($loadSavedSpecs) {
    /* id | model_id | model_child_id */
    $saved_specs = getSavedSpecs($conn, $asset_model); 
    foreach ($saved_specs as $saved_spec) {
        $saved_model_info[] = getAssetsModels($conn, $saved_spec['model_child_id']);
    }
    $saved_model_info = arraySortByColumn($saved_model_info, 'tipo');
}

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

    <style>
        .input-group-append, .input-group-prepend {
            cursor: pointer !important;
        }

        .list-type-of-record {
            line-height: 1.5em;
        }

    </style>

    <title>OcoMon&nbsp;<?= VERSAO; ?></title>
</head>

<body>
    
    <div class="container">
        <div id="idLoad" class="loading" style="display:none"></div>
    </div>

    <div id="divResult"></div>


    <div class="container-fluid">
        <h4 class="my-4"><i class="fas fa-qrcode text-secondary"></i>&nbsp;<?= TRANS('ASSET_REGISTER'); ?></h4>
        <div class="modal" id="modalAssetNew" tabindex="-1" style="z-index:9001!important">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div id="divDetailsAssetNew">
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

            $info_html = '<li class="list-type-of-record ml-5 mt-4">' . TRANS('COL_TYPE') . ': ' . $asset_type_info['tipo_nome'] . '</li>';
            $info_html .= '<li class="list-type-of-record ml-5">' . TRANS('COL_MANUFACTURER') . ': ' . $asset_manufacturer_info['fab_nome'] . '</li>';
            $info_html .= '<li class="list-type-of-record ml-5">' . TRANS('COL_MODEL') . ': ' . $asset_model_info['modelo'] . '</li>';

            $category_name = (!empty($asset_type_info['cat_name']) ? $asset_type_info['cat_name'] : TRANS('HAS_NOT_CATEGORY'));
            $info_html_title = TRANS('NEW_RECORD') . ' ' . TRANS('IN_CATEGORY') . ' ' . $category_name;

            /* Características do modelo */
            $modelSpecs = getModelSpecs($conn, $asset_model_info['codigo']);
            $info_model_specs = "";
            if (!empty($modelSpecs)) {
                $info_model_specs .= '<div class="ml-5 mt-4">';
                $info_model_specs .= '<p class="font-weight-bold mb-2">'.TRANS('MODEL_ATTRIBUTES').'</p>';
                
                $info_model_specs .= '<ul>';
                foreach ($modelSpecs as $spec) {
                    $info_model_specs .= '<li class="list_specs">' . $spec['mt_name'] . ': ' . $spec['spec_value'] . '' . $spec['unit_abbrev'] . '</li>';
                }
                $info_model_specs .= '</ul>';
                $info_model_specs .= '</div>';
            }
        ?>
            <?= message('info', $info_html_title, $info_html . $info_model_specs, '', '', true, 'fas fa-plus'); ?>

            <form name="form" method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form" enctype="multipart/form-data">
                <?= csrf_input(); ?>
                <div class="form-group row my-4">

                    <h6 class="w-100 mt-0 ml-5 border-top p-4"><i class="fas fa-info-circle text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('BASIC_INFORMATIONS')); ?></h6>

                    
                    <input type="hidden" name="asset_type" id="asset_type" value="<?=  $asset_type_info['tipo_cod']; ?>" />
                    <input type="hidden" name="manufacturer" value="<?=  $asset_manufacturer_info['fab_cod']; ?>" />
                    <input type="hidden" name="model" value="<?=  $asset_model_info['codigo']; ?>" />
                    <input type="hidden" name="profile_id" id="profile_id" value="<?=  $profile_id; ?>" />
                    <input type="hidden" name="parent_id" id="parent_id" value="<?=  $parent_id; ?>" />
                    <input type="hidden" name="parent_unit" id="parent_unit" value="<?=  $parent_unit; ?>" />
                    <input type="hidden" name="parent_department" id="parent_department" value="<?=  $parent_department; ?>" />
                    
                    <label for="client" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CLIENT'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control bs-select" id="client" name="client" required>
                            <?php
                                $clients = getClients($conn, null, null, $_SESSION['s_allowed_clients']);
                                foreach ($clients as $client) {
                                    ?>
                                        <option value="<?= $client['id']; ?>"
                                        <?= (!empty($parent_client) && $parent_client == $client['id'] ? ' selected' : ''); ?>
                                        ><?= $client['nickname']; ?></option>
                                    <?php
                                }
                            ?>
                            
                        </select>
                    </div>


                    <label for="asset_unit" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_UNIT'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control bs-select" id="asset_unit" name="asset_unit" required>
                            
                        </select>
                    </div>

                    <label for="department" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('DEPARTMENT'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control bs-select" id="department" name="department" required>
                            
                        </select>
                    </div>

                    <label for="asset_tag" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('ASSET_TAG'); ?></label>
                    <div class="form-group col-md-4">
                        <input type="text" class="form-control " id="asset_tag" name="asset_tag" required />
                    </div>






                    <div class="w-100"></div>


                    <?php
                        /* Os campos a seguir dependem de estarem habilitados no perfil de cadastro */
                        
                        /* Número de série */
                        if ($default_profile['serial_number']) {
                            ?>
                                <label for="serial_number" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('SERIAL_NUMBER'); ?></label>
                                <div class="form-group col-md-4">
                                    <input type="text" class="form-control " id="serial_number" name="serial_number" />
                                </div>
                            <?php
                        } else {
                            ?>
                                <input type="hidden" name="serial_number" value=""/>
                            <?php
                        }


                        /* Part-number */
                        if ($default_profile['part_number']) {
                            ?>
                                <label for="part_number" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_PARTNUMBER'); ?></label>
                                <div class="form-group col-md-4">
                                    <input type="text" class="form-control " id="part_number" name="part_number" />
                                </div>
                            <?php
                        } else {
                            ?>
                                <input type="hidden" name="part_number" value=""/>
                            <?php
                        }


                        /* Nome de rede */
                        if ($default_profile['net_name']) {
                            ?>
                                <label for="net_name" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('NET_NAME'); ?></label>
                                <div class="form-group col-md-4">
                                    <input type="text" class="form-control " id="net_name" name="net_name" />
                                </div>
                            <?php
                        } else {
                            ?>
                                <input type="hidden" name="net_name" value=""/>
                            <?php
                        }



                        /* Nota fiscal */
                        if ($default_profile['invoice_number']) {
                            ?>
                                <label for="invoice_number" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_NF'); ?></label>
                                <div class="form-group col-md-4">
                                    <input type="text" class="form-control " id="invoice_number" name="invoice_number" />
                                </div>
                            <?php
                        } else {
                            ?>
                                <input type="hidden" name="invoice_number" value=""/>
                            <?php
                        }


                        /* Centro de Custo */
                        if ($default_profile['cost_center']) {
                            ?>
                                <label for="cost_center" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COST_CENTER'); ?></label>
                                <div class="form-group col-md-4">
                                <select class="form-control bs-select" id="cost_center" name="cost_center">
                                <?php
                                    $cost_centers = getCostCenters($conn);
                                    foreach ($cost_centers as $ccenter) {
                                        ?>
                                            <option value="<?= $ccenter['ccusto_id']; ?>"><?= $ccenter['ccusto_cod'] . " " . $ccenter['ccusto_name'] ?></option>
                                        <?php
                                    }
                                ?>
                                </select>
                                </div>
                            <?php
                        } else {
                            ?>
                                <input type="hidden" name="cost_center" value=""/>
                            <?php
                        }


                        /* Situação operacional */
                        if ($default_profile['situation']) {
                            ?>
                                <label for="situation" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('STATE'); ?></label>
                                <div class="form-group col-md-4">
                                    <select class="form-control bs-select" id="situation" name="situation" required>
                                        <?php
                                        $states = getOperationalStates($conn);
                                        foreach ($states as $state) {
                                        ?>
                                            <option value="<?= $state['situac_cod']; ?>"><?= $state['situac_nome']; ?></option>
                                        <?php
                                        }
                                        ?>
                                    </select>
                                </div>
                            <?php
                        } else {
                            ?>
                                <input type="hidden" name="situation" value=""/>
                            <?php
                        }



                        /* Preço */
                        if ($default_profile['price']) {
                            ?>
                                <label for="price" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_VALUE'); ?></label>
                                <div class="form-group col-md-4">
                                    <input type="text" class="form-control " id="price" name="price" />
                                </div>
                            <?php
                        } else {
                            ?>
                                <input type="hidden" name="price" value=""/>
                            <?php
                        }

                        /* Data da compra */
                        if ($default_profile['buy_date']) {
                            ?>
                                <label for="buy_date" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('PURCHASE_DATE'); ?></label>
                                <div class="form-group col-md-4">
                                    <input type="text" class="form-control " id="buy_date" name="buy_date" />
                                </div>
                            <?php
                        } else {
                            ?>
                                <input type="hidden" name="buy_date" value=""/>
                            <?php
                        }

                        

                        /* Fornecedores */
                        if ($default_profile['supplier']) {
                            ?>
                                <label for="supplier" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_VENDOR'); ?></label>
                                <div class="form-group col-md-4">
                                    <select class="form-control bs-select" id="supplier" name="supplier" required>
                                        <?php
                                        $suppliers = getSuppliers($conn);
                                        foreach ($suppliers as $supplier) {
                                        ?>
                                            <option value="<?= $supplier['forn_cod']; ?>"><?= $supplier['forn_nome']; ?></option>
                                        <?php
                                        }
                                        ?>
                                    </select>
                                </div>
                            <?php
                        } else {
                            ?>
                                <input type="hidden" name="supplier" value=""/>
                            <?php
                        }


                        /* Assistências */
                        if ($default_profile['assistance_type']) {
                            ?>
                                <label for="assistance_type" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('ASSISTENCE'); ?></label>
                                <div class="form-group col-md-4">
                                    <select class="form-control bs-select" id="assistance_type" name="assistance_type" required>
                                        <?php
                                        $assistances = getAssistancesTypes($conn);
                                        foreach ($assistances as $assistance) {
                                        ?>
                                            <option value="<?= $assistance['assist_cod']; ?>"><?= $assistance['assist_desc']; ?></option>
                                        <?php
                                        }
                                        ?>
                                    </select>
                                </div>
                            <?php
                        } else {
                            ?>
                                <input type="hidden" name="assistance_type" value=""/>
                            <?php
                        }

                        
                        /* Tipos de garantias */
                        if ($default_profile['warranty_type']) {
                            ?>
                                <label for="warranty_type" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_TYPE_WARRANTY'); ?></label>
                                <div class="form-group col-md-4">
                                    <select class="form-control bs-select" id="warranty_type" name="warranty_type" required>
                                        <?php
                                        $warranties_types = getWarrantiesTypes($conn);
                                        foreach ($warranties_types as $warranty) {
                                        ?>
                                            <option value="<?= $warranty['tipo_garant_cod']; ?>"><?= $warranty['tipo_garant_nome']; ?></option>
                                        <?php
                                        }
                                        ?>
                                    </select>
                                </div>
                            <?php
                        } else {
                            ?>
                                <input type="hidden" name="warranty_type" value=""/>
                            <?php
                        }

                        
                        /* Tempos de garantias */
                        if ($default_profile['warranty_time']) {
                            ?>
                                <label for="warranty_time" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_TIME_MONTH'); ?></label>
                                <div class="form-group col-md-4">
                                    <select class="form-control bs-select" id="warranty_time" name="warranty_time" required>
                                        <?php
                                        $warranties_times = getWarrantiesTimes($conn);
                                        foreach ($warranties_times as $warranty) {
                                        ?>
                                            <option value="<?= $warranty['tempo_cod']; ?>"><?= $warranty['tempo_meses'] . " " .TRANS('MONTHS'); ?></option>
                                        <?php
                                        }
                                        ?>
                                    </select>
                                </div>
                            <?php
                        } else {
                            ?>
                                <input type="hidden" name="warranty_time" value=""/>
                            <?php
                        }

                        ?>
                            <div class="w-100"></div>

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

                        /* Informação extra */
                        if ($default_profile['extra_info']) {
                            ?>
                                <div class="w-100"></div>
                                <label for="extra_info" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('ENTRY_TYPE_ADDITIONAL_INFO'); ?></label>
                                <div class="form-group col-md-10">
                                    <textarea class="form-control " id="extra_info" name="extra_info"></textarea>
                                </div>
                            <?php
                        } else {
                            ?>
                                <input type="hidden" name="extra_info" value=""/>
                            <?php
                        }
                    
                        
                        /* Campos de especificações | características */
                        if (!empty($default_profile['field_specs_ids']) && empty($saved_model_info)) {
                            ?>
                                <h6 class="w-100 mt-5 ml-5 border-top p-4"><i class="fas fa-puzzle-piece text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('INFO_ASSET_CONFIG')); ?></h6>
                            <?php
                            $spec_ids = explode(',', $default_profile['field_specs_ids']);

                            foreach ($spec_ids as $type_id) {
                                $type_info = getAssetsTypes($conn, $type_id);
                                ?>
                                    <label for="<?= str_slug($type_info['tipo_nome'], 'spec_'); ?>" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= $type_info['tipo_nome']; ?></label>
                                    <div class="form-group col-md-4">
                                        <div class="input-group">
                                            <select class="form-control bs-select" id="<?= str_slug($type_info['tipo_nome'], 'spec_'); ?>" name="<?= str_slug($type_info['tipo_nome'], 'spec_'); ?>[]" >
                                                <?php
                                                $type_models = getAssetsModels($conn, null, $type_info['tipo_cod']);
                                                foreach ($type_models as $model) {
                                                    $modelSpecs = getModelSpecs($conn, $model['codigo']);
                                                    $subtext = "";
                                                    foreach ($modelSpecs as $spec) {
                                                        if (strlen((string)$subtext))
                                                            $subtext .= " | ";
                                                        $subtext .= $spec['mt_name'] . ': ' . $spec['spec_value'] . '' . $spec['unit_abbrev'];
                                                    }
                                                ?>
                                                    <option data-subtext="<?= $subtext; ?>" value="<?= $model['codigo']; ?>"><?= $model['modelo']; ?></option>
                                                <?php
                                                }
                                                ?>
                                            </select>
                                            <div class="input-group-append">
                                                <div class="input-group-text manage_popups" data-location="equipments_models" data-params="action=new&asset_type=<?= $type_id; ?>" title="<?= TRANS('NEW'); ?>" data-placeholder="<?= TRANS('NEW'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                                    <i class="fas fa-plus"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php
                            }
                        } elseif (!empty($saved_model_info)) {
                            /* Será carregado o modelo salvo de configuração referente ao modelo do ativo */
                            ?>
                                <h6 class="w-100 mt-5 ml-5 border-top p-4"><i class="fas fa-puzzle-piece text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('INFO_ASSET_CONFIG')); ?></h6>
                            <?php
                            foreach ($saved_model_info as $saved) {
                                ?>
                                    <label for="<?= str_slug($saved['tipo'], 'spec_'); ?>" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= $saved['tipo']; ?></label>
                                    <div class="form-group col-md-4">
                                        <div class="input-group">
                                            <select class="form-control bs-select" id="<?= str_slug($saved['tipo'], 'spec_'); ?>" name="<?= str_slug($saved['tipo'], 'spec_'); ?>[]" >
                                                <?php
                                                $type_models = getAssetsModels($conn, null, $saved['tipo_cod']);
                                                foreach ($type_models as $model) {
                                                    $modelSpecs = getModelSpecs($conn, $model['codigo']);
                                                    $subtext = "";
                                                    foreach ($modelSpecs as $spec) {
                                                        if (strlen((string)$subtext))
                                                            $subtext .= " | ";
                                                        $subtext .= $spec['mt_name'] . ': ' . $spec['spec_value'] . '' . $spec['unit_abbrev'];
                                                    }
                                                ?>
                                                    <option data-subtext="<?= $subtext; ?>" value="<?= $model['codigo']; ?>"
                                                    <?= ($model['codigo'] == $saved['codigo'] ? ' selected' : ''); ?>
                                                    ><?= $model['modelo']; ?></option>
                                                <?php
                                                }
                                                ?>
                                            </select>
                                            <div class="input-group-append">
                                                <div class="input-group-text manage_popups" data-location="equipments_models" data-params="action=new&asset_type=<?= $saved['tipo_cod']; ?>" title="<?= TRANS('NEW'); ?>" data-placeholder="<?= TRANS('NEW'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                                    <i class="fas fa-plus"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php
                            }
                        }
                    

                        /* Mesmo que não existam campos definidos no perfil, se o tipo puder possuir componentes eles poderão ser adicionados */
                        $possibleChilds = getAssetsTypesPossibleChilds($conn, $asset_type);
                        if (!empty($possibleChilds)) {
                            ?>
                            <div class="w-100"></div>
                            <!-- Link para adicionar componentes -->
                            <label class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('ADD'); ?></label>
                            <div class="form-group col-md-10">
                                <a href="javascript:void(0);" class="add_button_new_pieces" title="<?= TRANS('ADD'); ?>"><i class="fa fa-plus"></i></a>
                            </div>
                        <?php
                        }
                    
                    ?>
                    

                </div>

                <!-- Receberá as especificações excedentes de componentes -->
                <div class="form-group row my-4 new_pieces" id="new_pieces"></div>



                <div class="form-group row my-4">

                    <?php

                    /* Campos personalizados - customizados */
					$fields_id = [];
					if (!empty($default_profile['field_custom_ids'])) {

                        ?>
                            <h6 class="w-100 mt-5 ml-5 border-top p-4"><i class="fas fa-pencil-ruler text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('CUSTOM_FIELDS')); ?></h6>
                        <?php
						$fields_id = explode(',', $default_profile['field_custom_ids']);

						$labelColSize = 2;
						$fieldColSize = 4;
						$fieldRowSize = 10;
						$custom_fields = getCustomFields($conn, null, 'equipamentos');
					?>
						<!-- <div class="w-100"></div> -->
						<?php
						foreach ($custom_fields as $row) {

							if (in_array($row['id'], $fields_id)) {

								$inlineAttributes = keyPairsToHtmlAttrs($row['field_attributes']);
								$maskType = ($row['field_mask_regex'] ? 'regex' : 'mask');
								$fieldMask = "data-inputmask-" . $maskType . "=\"" . $row['field_mask'] . "\"";
						?>

								<?= ($row['field_type'] == 'textarea' ? '<div class="w-100"></div>'  : ''); ?>
								<label for="<?= $row['field_name']; ?>" class="col-sm-<?= $labelColSize; ?> col-md-<?= $labelColSize; ?> col-form-label col-form-label-sm text-md-right " title="<?= $row['field_title']; ?>" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= $row['field_description']; ?>"><?= $row['field_label']; ?></label>
								<div class="form-group col-md-<?= ($row['field_type'] == 'textarea' ? $fieldRowSize  : $fieldColSize); ?>">
									<?php
									if ($row['field_type'] == 'select') {
									?>
										<select class="form-control custom_field_select" name="<?= $row['field_name']; ?>" id="<?= $row['field_name']; ?>" <?= $inlineAttributes; ?>>
											<?php

											$options = [];
											$options = getCustomFieldOptionValues($conn, $row['id']);
											?>
											<option value=""><?= TRANS('SEL_SELECT'); ?></option>
											<?php
											foreach ($options as $rowValues) {
											?>
												<option value="<?= $rowValues['id']; ?>" <?= ($row['field_default_value'] == $rowValues['option_value'] ? " selected" : ""); ?>><?= $rowValues['option_value']; ?></option>
											<?php
											}
											?>
										</select>
									<?php
									} elseif ($row['field_type'] == 'select_multi') {
									?>
										<select class="form-control custom_field_select_multi" name="<?= $row['field_name']; ?>[]" id="<?= $row['field_name']; ?>" multiple="multiple" placeholder="<?= $row['field_placeholder']; ?>" <?= $inlineAttributes; ?>>
											<?php
											$defaultSelections = explode(',', $row['field_default_value']);
											$options = [];
											$options = getCustomFieldOptionValues($conn, $row['id']);
											?>
											<?php
											foreach ($options as $rowValues) {
											?>
												<option value="<?= $rowValues['id']; ?>" <?= (in_array($rowValues['option_value'], $defaultSelections) ? ' selected' : ''); ?>><?= $rowValues['option_value']; ?></option>
											<?php
											}
											?>
										</select>
									<?php
									} elseif ($row['field_type'] == 'number') {
									?>
										<input class="form-control custom_field_number" type="number" name="<?= $row['field_name']; ?>" id="<?= $row['field_name']; ?>" value="<?= $row['field_default_value'] ?? ''; ?>" placeholder="<?= $row['field_placeholder']; ?>" <?= $inlineAttributes; ?>>
									<?php
									} elseif ($row['field_type'] == 'checkbox') {
										$checked_checkbox = ($row['field_default_value'] ? " checked" : "");
									?>
										<div class="form-check form-check-inline">
											<input class="form-check-input custom_field_checkbox" type="checkbox" name="<?= $row['field_name']; ?>" id="<?= $row['field_name']; ?>" <?= $checked_checkbox ?> <?= $inlineAttributes; ?>>
											<legend class="col-form-label col-form-label-sm"><?= $row['field_placeholder']; ?></legend>
										</div>
									<?php
									} elseif ($row['field_type'] == 'textarea') {
									?>
										<textarea class="form-control custom_field_textarea" name="<?= $row['field_name']; ?>" id="<?= $row['field_name']; ?>" placeholder="<?= $row['field_placeholder']; ?>" <?= $inlineAttributes; ?>><?= $row['field_default_value'] ?? ''; ?></textarea>
									<?php
									} elseif ($row['field_type'] == 'date') {
									?>
										<input class="form-control custom_field_date" type="text" name="<?= $row['field_name']; ?>" id="<?= $row['field_name']; ?>" value="<?= $row['field_default_value'] ?? ''; ?>" placeholder="<?= $row['field_placeholder']; ?>" <?= $inlineAttributes; ?> autocomplete="off">
									<?php
									} elseif ($row['field_type'] == 'time') {
									?>
										<input class="form-control custom_field_time" type="text" name="<?= $row['field_name']; ?>" id="<?= $row['field_name']; ?>" value="<?= $row['field_default_value'] ?? ''; ?>" placeholder="<?= $row['field_placeholder']; ?>" <?= $inlineAttributes; ?> autocomplete="off">
									<?php
									} elseif ($row['field_type'] == 'datetime') {
									?>
										<input class="form-control custom_field_datetime" type="text" name="<?= $row['field_name']; ?>" id="<?= $row['field_name']; ?>" value="<?= $row['field_default_value'] ?? ''; ?>" placeholder="<?= $row['field_placeholder']; ?>" <?= $inlineAttributes; ?> autocomplete="off">
									<?php
									} else {
									?>
										<input class="form-control custom_field_text" type="text" name="<?= $row['field_name']; ?>" id="<?= $row['field_name']; ?>" value="<?= $row['field_default_value'] ?? ''; ?>" placeholder="<?= $row['field_placeholder']; ?>" <?= $fieldMask; ?> <?= $inlineAttributes; ?> autocomplete="off">
									<?php
									}
									?>
								</div>
					<?php
							}
						} /* foreach */
					}



                    /* Se o tipo de ativo puder ser parte de outros ativos maiores */
                    if ($hasPossibleParents) {
                        ?>
                            <h6 class="w-100 mt-4 ml-5 border-top p-4"><i class="fas fa-link text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('LINKED_ASSET')); ?></h6>

                            <label class="col-md-2 col-form-label text-md-right"><?= TRANS('IS_PART_OF_OTHER_ASSET'); ?></label>
                            <div class="form-group col-md-10 ">
                                <div class="switch-field">
                                    <?php
                                    $yesChecked = (!empty($parent_id) ? "checked" : "");
                                    $noChecked = (empty($parent_id) ? "checked" : "");
                                    ?>
                                    <input type="radio" id="has_parent" name="has_parent" value="yes" <?= $yesChecked; ?> />
                                    <label for="has_parent"><?= TRANS('YES'); ?></label>
                                    <input type="radio" id="has_parent_no" name="has_parent" value="no" <?= $noChecked; ?> />
                                    <label for="has_parent_no"><?= TRANS('NOT'); ?></label>
                                </div>
                            </div>

                            <label for="parent_asset_tag" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('ASSET_TAG'); ?></label>
                            <div class="form-group col-md-4">
                                <?php
                                    $disabled = (empty($parent_tag) ? " disabled" : "");
                                ?>
                                <input type="text" class="form-control " id="parent_asset_tag" value="<?= (!empty($parent_tag) ? $parent_tag : ''); ?>" name="parent_asset_tag"  <?= $disabled; ?>/>
                            </div>

                            <div class="w-100"></div>
                        <?php
                    }
                    
                    ?>

                </div>


                <!-- Receberá as informações do ativo PAI -->
                <div class="form-group row my-4 parent_info" id="parent_info"></div>

                <div class="form-group row my-4">
                    <div class="row w-100"></div>
                    <div class="form-group col-md-8 d-none d-md-block"></div>
                    <div class="form-group col-12 col-md-2 ">

                        <input type="hidden" name="action" id="action" value="new">
                        <input type="hidden" name="model_selected" id="model_selected" value="">
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


            $('.bs-select').addClass('new-select2-equip-new');

            $('.new-select2-equip-new').selectpicker({
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
                prefix: 'R$ ',
                thousands: '.',
                decimal: ',',
                allowZero: false,
                affixesStay: false
            });


            if ($('#parent_id').val() != '') {
                loadUnits();
            }



            /* Controle para liberar ou não os campos de identificação do ativo pai associado */
			if (!$('#has_parent').is(":checked")) {
				$('#parent_asset_tag').prop('disabled', true).val('');
				$('#department').prop('disabled', false).selectpicker('refresh');
                $("#idSubmit").prop("disabled", false);
			} else {
				$('#parent_asset_tag').prop('disabled', false);
				$('#department').prop('disabled', true).selectpicker('val','').selectpicker('refresh');
                $("#idSubmit").prop("disabled", true);
			}

			$('[name="has_parent"]').on('change', function() {

                $('#parent_info').empty();
				if ($(this).val() == "no") {
					$('#parent_asset_tag').prop('disabled', true).val('');
					$('#department').prop('disabled', false).selectpicker('refresh');
                    $("#idSubmit").prop("disabled", false);
				} else {
					$('#parent_asset_tag').prop('disabled', false);
					$('#department').prop('disabled', true).selectpicker('val','').selectpicker('refresh');
                    $("#idSubmit").prop("disabled", true);
				}
			});

            if ($('#parent_asset_tag').val() != '') {
                $("#idSubmit").prop("disabled", false);
                renderParentInfo();
            }

            $("#parent_asset_tag").on('change', function(){
                renderParentInfo();
            });
			/* Final do controle para liberar ou não os campos de identificação de equipamento associado */


            /* Carregar a configuracao salva a partir do modelo selecionado */
            $('#load_model').on('click', function(e) {
                e.preventDefault();
                var loading = $(".loading");
                $(document).ajaxStart(function() {
                    loading.show();
                });
                $(document).ajaxStop(function() {
                    loading.hide();
                });
                
                $.ajax({
                    url: './get_configuration_model.php',
                    method: 'POST',
                    // data: $('#form').serialize(),
                    data: {model_id: $('#model_full').val()},
                    dataType: 'json',
                }).done(function(response) {
                    
                    if (!response.success) {
                        $('#divResult').html(response.message);
                    } else {
                        $('#divResult').html('');
                        // console.log(response);

                        $('#type').val(response.mold_tipo_equip).change();
                        $('#manufacturer').val(response.mold_fab).change();
                        
                        $('#model_selected').val(response.mold_marca);
                        $('#model_full').val(response.mold_marca).change();
                        // $('#model_full').selectpicker('val', response.mold_marca);
                        $('#motherboard').val(response.mold_mb).change();
                        $('#processor').val(response.mold_proc).change();
                        $('#memory').val(response.mold_memo).change();
                        $('#video').val(response.mold_video).change();
                        $('#sound').val(response.mold_som).change();
                        $('#network').val(response.mold_rede).change();
                        $('#modem').val(response.mold_modem).change();
                        $('#hdd').val(response.mold_modelohd).change();
                        $('#recorder').val(response.mold_grav).change();
                        $('#cdrom').val(response.mold_cdrom).change();
                        $('#dvdrom').val(response.mold_dvd).change();

                        // console.log('Model Selected: ' + $('#model_selected').val());

                        return false;
                    }
                });
                return false;
            });


            loadCostCenters();

            $("#client").on('change', function() {
				loadUnits();
                loadCostCenters();
                $('#parent_info').empty();
                $('#parent_asset_tag').val('');
                // loadUnits('parent_asset_unit');
			});

			$("#asset_unit").on('change', function() {
				loadDepartments();
                $('#parent_info').empty();
                $('#parent_asset_tag').val('');
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
                $("#department").prop('disabled', false);
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
                        // var url = 'equipment_show.php?tag=' + $('#asset_tag').val() + '&unit=' + $('#asset_unit').val();
                        var url = 'asset_show.php?asset_id=' + response.cod;
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


		function loadUnits(targetId = 'asset_unit') {

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
                $('#' + targetId).empty();
                if (Object.keys(data).length > 1) {
                    $('#' + targetId).append('<option value=""><?= TRANS("SEL_SELECT"); ?></option>');
                }
                $.each(data, function(key, data) {
                    $('#' + targetId).append('<option value="' + data.inst_cod + '">' + data.inst_nome + '</option>');
                });

                $('#' + targetId).selectpicker('refresh');
                if ($('#parent_id').val() != '') {
                    $('#' + targetId).selectpicker('val', $('#parent_unit').val());
                    $('#' + targetId).selectpicker('refresh');
                }
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
                if ($('#parent_id').val() != '') {
                    $('#department').selectpicker('val', $('#parent_department').val());
                    $('#department').selectpicker('refresh');
                }
                
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
                    $('#' + targetId).empty();
                    if (Object.keys(data).length > 1) {
                        $('#' + targetId).append('<option value=""><?= TRANS("SEL_SELECT"); ?></option>');
                    }
                    $.each(data, function(key, data) {
                        $('#' + targetId).append('<option data-subtext="' + data.ccusto_cod + '" value="' + data.ccusto_id + '">' + data.ccusto_name + '</option>');
                    });

                    $('#' + targetId).selectpicker('refresh');
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
                    profile_id: $('#profile_id').val(),
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


        function renderParentInfo() {
            var loading = $(".loading");
            $(document).ajaxStart(function() {
                loading.show();
            });
            $(document).ajaxStop(function() {
                loading.hide();
            });

            $.ajax({
                url: './render_parent_asset_info.php',
                method: 'POST',
                data: {
                    parent_asset_tag: $('#parent_asset_tag').val(),
                    parent_asset_unit: $('#asset_unit').val(),
                    asset_type: $('#asset_type').val(),
                    random: Math.random().toString(16).substr(2, 8)
                },
                dataType: 'json',
            }).done(function(data) {

                if (data.success) {
                    
                    $('#department').val(data.department_cod).change();
                    $('#department').selectpicker('refresh');
                    
                    // $('#department').append('<option value="' + data.department_cod + '" selected>' + data.department_name + '</option>');
                    // $('#department').selectpicker('refresh').selectpicker('render');


                    // $('#form').append('<input type="hidden" name="parent_department" value="' + data.department_cod + '">');
                    
                    $("#idSubmit").prop("disabled", false);
                } else {
                    $("#idSubmit").prop("disabled", true);
                }
                $('#parent_info').empty().append(data.html);
            });
            return false;
		}


        function loadInModal(pageBase, params) {
			let url = pageBase + '.php?' + params;
			// $(location).prop('href', url);
            $("#divDetailsAssetNew").load(url);
			$('#modalAssetNew').modal();
        }
        
        function loadInPopup(pageBase, params) {
            let url = pageBase + '.php?' + params;
            x = window.open(url,'','dependent=yes,width=800,scrollbars=yes,statusbar=no,resizable=yes');
		    x.moveTo(10,10);
		}
    </script>
</body>

</html>