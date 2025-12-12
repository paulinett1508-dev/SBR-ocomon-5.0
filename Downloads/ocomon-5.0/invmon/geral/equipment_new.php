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

/* Para manter a compatibilidade com versões antigas */
$table = "equipxpieces";
$clausule = $QRY['componentexequip_ini'];
$sqlTest = "SELECT * FROM {$table}";
try {
    $conn->query($sqlTest);
} catch (Exception $e) {
    $table = "equipXpieces";
    $clausule = $QRY['componenteXequip_ini'];
}

$type = "";


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

    </style>

    <title>OcoMon&nbsp;<?= VERSAO; ?></title>
</head>

<body>
    
    <div class="container">
        <div id="idLoad" class="loading" style="display:none"></div>
    </div>

    <div id="divResult"></div>


    <div class="container-fluid">
        <h4 class="my-4"><i class="fas fa-laptop text-secondary"></i>&nbsp;<?= TRANS('MNL_EQUIPAMENTOS'); ?></h4>
        <div class="modal" id="modalEquipmentNew" tabindex="-1" style="z-index:9001!important">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div id="divDetailsEquipmentNew">
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
            <h6><?= TRANS('NEW_RECORD'); ?></h6>


            <form name="form" method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form" enctype="multipart/form-data">
                <?= csrf_input(); ?>
                <div class="form-group row my-4">

                    <h6 class="w-100 mt-5 ml-5 border-top p-4"><i class="fas fa-info-circle text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('BASIC_INFORMATIONS')); ?></h6>

                    <label for="type" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_TYPE'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2-equip-new" id="type" name="type" required>
                            <option value=""><?= TRANS('SEL_TYPE_EQUIP'); ?></option>
                            <?php
                            $sql = "SELECT * FROM tipo_equip ORDER BY tipo_nome";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                            ?>
                                <option value="<?= $rowType['tipo_cod']; ?>"><?= $rowType['tipo_nome']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>


                    <label for="manufacturer" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_MANUFACTURER'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2-equip-new" id="manufacturer" name="manufacturer" required>
                            <option value=""><?= TRANS('SEL_MANUFACTURER'); ?></option>
                            <?php
                            $sql = "SELECT * FROM fabricantes ORDER BY fab_nome";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                            ?>
                                <option value="<?= $rowType['fab_cod']; ?>"><?= $rowType['fab_nome']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>

                    <label for="model_full" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_MODEL'); ?></label>
                    <div class="form-group col-md-4">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <div class="input-group-text manage" id="new_model" data-location="equipments_models" data-params="action=new" title="<?= TRANS('NEW'); ?>" data-placeholder="<?= TRANS('NEW'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                    <i class="fas fa-plus"></i>
                                </div>
                            </div>
                            <select class="form-control sel2-equip-new" id="model_full" name="model_full" required>
                                <option value=""><?= TRANS('SEL_MODEL'); ?></option>
                            </select>
                            <div class="input-group-append">
                                <div class="input-group-text " id="load_model" title="<?= TRANS('BT_LOAD_CONFIG'); ?>" data-placeholder="<?= TRANS('BT_LOAD_CONFIG'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                    <i class="fas fa-sync-alt"></i>
                                </div>
                            </div>
                        </div>
                    </div>


                    <label for="serial_number" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('SERIAL_NUMBER'); ?></label>
                    <div class="form-group col-md-4">
                        <input type="text" class="form-control " id="serial_number" name="serial_number" />
                    </div>

                    <label for="department" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('DEPARTMENT'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2-equip-new" id="department" name="department" required>
                            <option value=""><?= TRANS('SEL_DEPARTMENT'); ?></option>
                            <?php
                            $sql = "SELECT * FROM localizacao ORDER BY local";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                            ?>
                                <option value="<?= $rowType['loc_id']; ?>"><?= $rowType['local']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>

                    <label for="condition" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('STATE'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2-equip-new" id="condition" name="condition" required>
                            <option value=""><?= TRANS('STATE'); ?></option>
                            <?php
                            $sql = "SELECT * FROM situacao ORDER BY situac_nome";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                            ?>
                                <option value="<?= $rowType['situac_cod']; ?>"><?= $rowType['situac_nome']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>

                    <label for="net_name" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('NET_NAME'); ?></label>
                    <div class="form-group col-md-4">
                        <input type="text" class="form-control " id="net_name" name="net_name" />
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

                    <h6 class="w-100 mt-5 ml-5 border-top p-4"><i class="fas fa-hdd text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('SUBTTL_INFO_CONFIG_EQUIP')); ?></h6>


                    <label for="motherboard" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('MOTHERBOARD'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2-equip-new" id="motherboard" name="motherboard">
                            <option value=""><?= TRANS('SEL_NONE'); ?></option>
                            <?php
                            $sql = "SELECT * FROM modelos_itens WHERE mdit_tipo = 10 ORDER BY mdit_fabricante, mdit_desc";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                            ?>
                                <option value="<?= $rowType['mdit_cod']; ?>"><?= $rowType['mdit_fabricante'] . " " . $rowType['mdit_desc'] . " " . $rowType['mdit_desc_capacidade'] . "" . $rowType['mdit_sufixo']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>

                    <label for="processor" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('PROCESSOR'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2-equip-new" id="processor" name="processor">
                            <option value=""><?= TRANS('SEL_NONE'); ?></option>
                            <?php
                            $sql = "SELECT * FROM modelos_itens WHERE mdit_tipo = 11 ORDER BY mdit_fabricante, mdit_desc";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                            ?>
                                <option value="<?= $rowType['mdit_cod']; ?>"><?= $rowType['mdit_fabricante'] . " " . $rowType['mdit_desc'] . " " . $rowType['mdit_desc_capacidade'] . "" . $rowType['mdit_sufixo']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>

                    <label for="memory" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CARD_MEMORY'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2-equip-new" id="memory" name="memory">
                            <option value=""><?= TRANS('SEL_NONE'); ?></option>
                            <?php
                            $sql = "SELECT * FROM modelos_itens WHERE mdit_tipo = 7 ORDER BY mdit_fabricante, mdit_desc";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                            ?>
                                <option value="<?= $rowType['mdit_cod']; ?>"><?= $rowType['mdit_fabricante'] . " " . $rowType['mdit_desc'] . " " . $rowType['mdit_desc_capacidade'] . "" . $rowType['mdit_sufixo']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>

                    <label for="video" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CARD_VIDEO'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2-equip-new" id="video" name="video">
                            <option value=""><?= TRANS('SEL_NONE'); ?></option>
                            <?php
                            $sql = "SELECT * FROM modelos_itens WHERE mdit_tipo = 2 ORDER BY mdit_fabricante, mdit_desc";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                            ?>
                                <option value="<?= $rowType['mdit_cod']; ?>"><?= $rowType['mdit_fabricante'] . " " . $rowType['mdit_desc'] . " " . $rowType['mdit_desc_capacidade'] . "" . $rowType['mdit_sufixo']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>

                    <label for="sound" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CARD_SOUND'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2-equip-new" id="sound" name="sound">
                            <option value=""><?= TRANS('SEL_NONE'); ?></option>
                            <?php
                            $sql = "SELECT * FROM modelos_itens WHERE mdit_tipo = 4 ORDER BY mdit_fabricante, mdit_desc";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                            ?>
                                <option value="<?= $rowType['mdit_cod']; ?>"><?= $rowType['mdit_fabricante'] . " " . $rowType['mdit_desc'] . " " . $rowType['mdit_desc_capacidade'] . "" . $rowType['mdit_sufixo']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>


                    <label for="network" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CARD_NETWORK'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2-equip-new" id="network" name="network">
                            <option value=""><?= TRANS('SEL_NONE'); ?></option>
                            <?php
                            $sql = "SELECT * FROM modelos_itens WHERE mdit_tipo = 3 ORDER BY mdit_fabricante, mdit_desc";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                            ?>
                                <option value="<?= $rowType['mdit_cod']; ?>"><?= $rowType['mdit_fabricante'] . " " . $rowType['mdit_desc'] . " " . $rowType['mdit_desc_capacidade'] . "" . $rowType['mdit_sufixo']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>

                    <label for="modem" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CARD_MODEN'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2-equip-new" id="modem" name="modem">
                            <option value=""><?= TRANS('SEL_NONE'); ?></option>
                            <?php
                            $sql = "SELECT * FROM modelos_itens WHERE mdit_tipo = 6 ORDER BY mdit_fabricante, mdit_desc";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                            ?>
                                <option value="<?= $rowType['mdit_cod']; ?>"><?= $rowType['mdit_fabricante'] . " " . $rowType['mdit_desc'] . " " . $rowType['mdit_desc_capacidade'] . "" . $rowType['mdit_sufixo']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>

                    <label for="hdd" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('MNL_HD'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2-equip-new" id="hdd" name="hdd">
                            <option value=""><?= TRANS('SEL_NONE'); ?></option>
                            <?php
                            $sql = "SELECT * FROM modelos_itens WHERE mdit_tipo = 1 ORDER BY mdit_fabricante, mdit_desc";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                            ?>
                                <option value="<?= $rowType['mdit_cod']; ?>"><?= $rowType['mdit_fabricante'] . " " . $rowType['mdit_desc'] . " " . $rowType['mdit_desc_capacidade'] . "" . $rowType['mdit_sufixo']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>

                    <label for="recorder" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_RECORD_CD'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2-equip-new" id="recorder" name="recorder">
                            <option value=""><?= TRANS('SEL_NONE'); ?></option>
                            <?php
                            $sql = "SELECT * FROM modelos_itens WHERE mdit_tipo = 9 ORDER BY mdit_fabricante, mdit_desc";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                            ?>
                                <option value="<?= $rowType['mdit_cod']; ?>"><?= $rowType['mdit_fabricante'] . " " . $rowType['mdit_desc'] . " " . $rowType['mdit_desc_capacidade'] . "" . $rowType['mdit_sufixo']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>

                    <label for="cdrom" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_CDROM'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2-equip-new" id="cdrom" name="cdrom">
                            <option value=""><?= TRANS('SEL_NONE'); ?></option>
                            <?php
                            $sql = "SELECT * FROM modelos_itens WHERE mdit_tipo = 5 ORDER BY mdit_fabricante, mdit_desc";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                            ?>
                                <option value="<?= $rowType['mdit_cod']; ?>"><?= $rowType['mdit_fabricante'] . " " . $rowType['mdit_desc'] . " " . $rowType['mdit_desc_capacidade'] . "" . $rowType['mdit_sufixo']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>

                    <label for="dvdrom" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('DVD'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2-equip-new" id="dvdrom" name="dvdrom">
                            <option value=""><?= TRANS('SEL_NONE'); ?></option>
                            <?php
                            $sql = "SELECT * FROM modelos_itens WHERE mdit_tipo = 8 ORDER BY mdit_fabricante, mdit_desc";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                            ?>
                                <option value="<?= $rowType['mdit_cod']; ?>"><?= $rowType['mdit_fabricante'] . " " . $rowType['mdit_desc'] . " " . $rowType['mdit_desc_capacidade'] . "" . $rowType['mdit_sufixo']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>



                    <h6 class="w-100 mt-5 ml-5 border-top p-4"><i class="fas fa-print text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('SUBTTL_INFO_ITEM_ESPEC')); ?></h6>

                    <label for="printer_type" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_TYPE_PRINTER'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2-equip-new" id="printer_type" name="printer_type">
                            <option value=""><?= TRANS('SEL_TYPE_PRINTER'); ?></option>
                            <?php
                            $sql = "SELECT * FROM tipo_imp ORDER BY tipo_imp_nome";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                            ?>
                                <option value="<?= $rowType['tipo_imp_cod']; ?>"><?= $rowType['tipo_imp_nome']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>

                    <label for="monitor_size" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_MONITOR'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2-equip-new" id="monitor_size" name="monitor_size">
                            <option value=""><?= TRANS('SEL_SIZE_MONITOR'); ?></option>
                            <?php
                            $sql = "SELECT * FROM polegada ORDER BY pole_nome";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                            ?>
                                <option value="<?= $rowType['pole_cod']; ?>"><?= $rowType['pole_nome']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>

                    <label for="scanner_resolution" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_SCANNER'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2-equip-new" id="scanner_resolution" name="scanner_resolution">
                            <option value=""><?= TRANS('SEL_RESOLUT_SCANNER'); ?></option>
                            <?php
                            $sql = "SELECT * FROM resolucao ORDER BY resol_nome";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                            ?>
                                <option value="<?= $rowType['resol_cod']; ?>"><?= $rowType['resol_nome']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>

                    <h6 class="w-100 mt-5 ml-5 border-top p-4"><i class="fas fa-file-invoice-dollar text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('SUBTTL_INFO_COUNT')); ?></h6>

                    <label for="asset_unit" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_UNIT'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2-equip-new" id="asset_unit" name="asset_unit" required>
                            <option value=""><?= TRANS('SEL_UNIT'); ?></option>
                            <?php
                            $sql = "SELECT * FROM instituicao ORDER BY inst_nome";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                            ?>
                                <option value="<?= $rowType['inst_cod']; ?>"><?= $rowType['inst_nome']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>

                    <label for="asset_tag" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('ASSET_TAG'); ?></label>
                    <div class="form-group col-md-4">
                        <input type="text" class="form-control " id="asset_tag" name="asset_tag" required />
                    </div>

                    <label for="invoice_number" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_NF'); ?></label>
                    <div class="form-group col-md-4">
                        <input type="text" class="form-control " id="invoice_number" name="invoice_number" />
                    </div>


                    <label for="cost_center" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COST_CENTER'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2-equip-new" id="cost_center" name="cost_center">
                            <option value=""><?= TRANS('COST_CENTER'); ?></option>
                            <?php
                            $sql = "SELECT * FROM `" . DB_CCUSTO . "`." . TB_CCUSTO . "  ORDER BY " . CCUSTO_DESC . "";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                            ?>
                                <option value="<?= $rowType[CCUSTO_ID]; ?>"><?= $rowType[CCUSTO_DESC] . " - " . $rowType[CCUSTO_COD]; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>

                    <label for="price" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_VALUE'); ?></label>
                    <div class="form-group col-md-4">
                        <input type="text" class="form-control " id="price" name="price" />
                    </div>

                    <label for="purchase_date" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('PURCHASE_DATE'); ?></label>
                    <div class="form-group col-md-4">
                        <input type="text" class="form-control " id="purchase_date" name="purchase_date" autocomplete="off" />
                    </div>


                    <label for="supplier" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_VENDOR'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2-equip-new" id="supplier" name="supplier">
                            <option value=""><?= TRANS('OCO_SEL_VENDOR'); ?></option>
                            <?php
                            $sql = "SELECT * FROM fornecedores ORDER BY forn_nome";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                            ?>
                                <option value="<?= $rowType['forn_cod']; ?>"><?= $rowType['forn_nome']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>

                    <label for="assistance" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('ASSISTENCE'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2-equip-new" id="assistance" name="assistance">
                            <option value=""><?= TRANS('SEL_TYPE_ASSIST'); ?></option>
                            <?php
                            $sql = "SELECT * FROM assistencia ORDER BY assist_desc";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                            ?>
                                <option value="<?= $rowType['assist_cod']; ?>"><?= $rowType['assist_desc']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>

                    <label for="warranty_type" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_TYPE_WARRANTY'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2-equip-new" id="warranty_type" name="warranty_type">
                            <option value=""><?= TRANS('SEL_WARRANTY_TYPE'); ?></option>
                            <?php
                            $sql = "SELECT * FROM tipo_garantia ORDER BY tipo_garant_nome";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                            ?>
                                <option value="<?= $rowType['tipo_garant_cod']; ?>"><?= $rowType['tipo_garant_nome']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>

                    <label for="time_of_warranty" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('OCO_SEL_WARRANTY'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2-equip-new" id="time_of_warranty" name="time_of_warranty">
                            <option value=""><?= TRANS('FIELD_TIME_MONTH'); ?></option>
                            <?php
                            $sql = "SELECT * FROM tempo_garantia ORDER BY tempo_meses";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                            ?>
                                <option value="<?= $rowType['tempo_cod']; ?>"><?= $rowType['tempo_meses'] . ' ' . TRANS('MONTHS'); ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>

                    <div class="w-100"></div>
                    <label for="additional_info" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('ENTRY_TYPE_ADDITIONAL_INFO'); ?></label>
                    <div class="form-group col-md-10">
                        <textarea class="form-control " id="additional_info" name="additional_info"></textarea>
                    </div>





                    <div class="row w-100"></div>
                    <div class="form-group col-md-8 d-none d-md-block">
                    </div>
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
    <script type="text/javascript">
        $(function() {

            $('.sel2-equip-new').addClass('new-select2-equip-new');

            $('.new-select2-equip-new').selectpicker({
				/* placeholder */
				title: "<?= TRANS('SEL_SELECT', '', 1); ?>",
				liveSearch: true,
				liveSearchNormalize: true,
				liveSearchPlaceholder: "<?= TRANS('BT_SEARCH', '', 1); ?>",
				noneResultsText: "<?= TRANS('NO_RECORDS_FOUND', '', 1); ?> {0}",
				
				style: "",
				styleBase: "form-control input-select-multi",
			});


            /* Carregamento dos modelos com base na seleção de tipo */
            $('#type').on('change', function() {
                showModelsByType();
            });
            showModelsByType($('#model_selected').val() ?? '');
            /* Final do carregamento dos modelos */

            $('.manage').on('click', function() {
                // let url = $(this).attr('data-location') + '.php';
                
                // loadInModal($(this).attr('data-location'), $(this).attr('data-params'));
                loadInPopup($(this).attr('data-location'), $(this).attr('data-params'));
			});


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
            $('#purchase_date').datetimepicker({
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
                    url: './equipment_process.php',
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
                        var url = 'equipment_show.php?tag=' + $('#asset_tag').val() + '&unit=' + $('#asset_unit').val();
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


        function showModelsByType(selected_id = '') {
            /* Popular os modelos de acordo com o tipo selecionado */

            if ($('#model_full').length > 0) {

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
                        type: $('#type').val(),
                        model_selected: $('#model_selected').val() ?? '',
                    },
                }).done(function(response) {
                    $('#model_full').empty().append('<option value=""><?= TRANS('SEL_MODEL'); ?></option>');
                    for (var i in response) {
                        var option = '<option value="' + response[i].marc_cod + '">' + response[i].marc_nome + '</option>';
                        $('#model_full').append(option);

                        if (selected_id !== '') {
                            $('#model_full').val(selected_id).change();
                            
                        } else
                        if ($('#model_selected').val() != '') {
                            // $('#model_full').val($('#model_selected').val()).change();
                            $('#model_full').selectpicker('refresh').selectpicker('val', $('#model_selected').val());
                        }
                    }
                    $('#model_full').selectpicker('refresh');
                });
            }
        }

        function bypassCsrf() {
			$('#bypassCsrf').val(1);
		}

        function loadInModal(pageBase, params) {
			let url = pageBase + '.php?' + params;
			// $(location).prop('href', url);
            $("#divDetailsEquipmentNew").load(url);
			$('#modalEquipmentNew').modal();
        }
        
        function loadInPopup(pageBase, params) {
            let url = pageBase + '.php?' + params;
            x = window.open(url,'','dependent=yes,width=800,scrollbars=yes,statusbar=no,resizable=yes');
		    x.moveTo(10,10);
		}
    </script>
</body>

</html>