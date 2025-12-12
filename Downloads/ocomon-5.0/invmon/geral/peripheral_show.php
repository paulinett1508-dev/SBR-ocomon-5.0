<?php session_start();
/*                        Copyright 2023 Flávio Ribeiro

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
  */

if (!isset($_SESSION['s_logado']) || $_SESSION['s_logado'] == 0) {
    $_SESSION['session_expired'] = 1;
    echo "<script>top.window.location = '../../index.php'</script>";
    exit;
}

require_once __DIR__ . "/" . "../../includes/include_geral_new.inc.php";
require_once __DIR__ . "/" . "../../includes/classes/ConnectPDO.php";

use includes\classes\ConnectPDO;

$conn = ConnectPDO::getInstance();

$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 3, 2);

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../../includes/css/estilos.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/css/switch_radio.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap/custom.css" /> <!-- custom bootstrap v4.5 -->
    <link rel="stylesheet" type="text/css" href="../../includes/components/fontawesome/css/all.min.css" />
    <title>OcoMon&nbsp;<?= VERSAO; ?></title>
    <style>
        .navbar-nav>.nav-link:hover {
            background-color: #3a4d56 !important;
        }

        .nav-pills>li>a.active {
            /* background-color: #6c757d !important; */
            background-color: #48606b !important;
        }

        .navbar-nav i {
            margin-right: 3px;
            font-size: 12px;
            width: 20px;
            height: 20px;
            line-height: 20px;
            text-align: center;
            -ms-flex-negative: 0;
            flex-shrink: 0;
            /* background-color: #3a4d56; */
            border-radius: 4px;
        }

        .oc-cursor {
            cursor: pointer;
        }
    </style>

</head>

<body class="bg-light">

    <?php



    /* Para manter a compatibilidade com versões antigas */
    $exception = "";
    $table = "equipxpieces";
    $clausule = $QRY['componentexequip_ini'];
    $sqlTest = "SELECT * FROM {$table}";
    try {
        $conn->query($sqlTest);
    } catch (Exception $e) {
        $table = "equipXpieces";
        $clausule = $QRY['componenteXequip_ini'];
    }


    $request = (isset($_POST) && !empty($_POST) ? $_POST : "");
    $request = (empty($request) && isset($_GET) && !empty($_GET) ? $_GET : "");

    if (empty($request)) {
        echo message('warning', 'Ooops!', TRANS('MSG_ERR_NOT_EXECUTE'), '', '', 1);
        return;
    }

    $tag = $request['tag'] ?? 0;
    $unit = $request['unit'] ?? 0;
    $cod = $request['cod'] ?? 0;

    if (!$cod) {
        if ($tag && $unit) {
            $sql = "SELECT estoq_cod FROM estoque WHERE estoq_tag_inv = '{$tag}' AND estoq_tag_inst = '{$unit}'";
            $result = $conn->query($sql);
            if (!$result->rowCount()) {
                echo message('warning', 'Ooops!', TRANS('NO_RECORDS_FOUND'), '', '', 1);
                return;
            }
            $cod = $result->fetch()['estoq_cod'];
        } else {
            echo message('warning', 'Ooops!', TRANS('INFO_MISSING_TO_PROCEED'), '', '', 1);
            return;
        }
    }

    $query = $clausule;
    $query .= " AND (e.estoq_cod = '{$cod}')";

    /* Controle sobre as unidades que podem ser visualizadas pela área primária do operador */
    if (!empty($_SESSION['s_allowed_units'])) {
        $query .= " AND inst.inst_cod IN ({$_SESSION['s_allowed_units']}) ";
    }
    try {
        $resultado = $conn->query($query);
        if ($resultado->rowCount()) {
            $row = $resultado->fetch();
        } else {
            echo message('warning', 'Ooops!', TRANS('NO_RECORDS_FOUND'), '', '', 1);
            return;
        }
    } catch (Exception $e) {
        $exception .= "<hr>" . $e->getMessage();
        echo message('danger', 'Ooops!', TRANS('NO_RECORDS_FOUND') . $exception, '', '', 1);
        return;
    }


    ?>

    <div class="container">
        <div id="idLoad" class="loading" style="display:none"></div>
    </div>


    <div class="container-fluid bg-light">

        <?php

        /* MENSAGEM DE RETORNO PARA ABERTURA, EDIÇÃO E ENCERRAMENTO DO CHAMADO */
        if (isset($_SESSION['flash']) && !empty($_SESSION['flash'])) {
            echo $_SESSION['flash'];
            $_SESSION['flash'] = '';
        }
        ?>


        <h4 class="my-4"><i class="fas fa-hdd text-secondary"></i>&nbsp;<?= TRANS('DETACHED_COMPONENTS'); ?></h4>
        <button type="button" class="btn btn-secondary btn-sm" onclick="redirect('../../invmon/geral/peripherals_tagged.php?action=edit&cod=<?= $row['estoq_cod']; ?>')"><?= TRANS('BT_EDIT'); ?></button>&nbsp;

        <button type="button" class="btn btn-info btn-sm" onclick="popup_alerta('../../invmon/geral/peripheral_locations_history.php?popup=true&peripheral_id=<?= $row['estoq_cod']; ?>')"><?= TRANS('MNL_CON_HIST'); ?></button>&nbsp;

        <button type="button" class="btn btn-info btn-sm" onclick="popup_alerta('../../invmon/geral/get_peripheral_warranty_info.php?popup=true&peripheral_id=<?= $row['estoq_cod']; ?>')"><?= TRANS('LINK_GUARANT'); ?></button><br /><br />



        <!-- <form name="form_view" id="form_view" > -->
        <div class="form-group row my-4">


            <label for="type_view" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_TYPE'); ?></label>
            <div class="form-group col-md-4">
                <select class="form-control sel3" id="type_view" name="type_view" disabled>
                    <option value=""><?= TRANS('SEL_TYPE_ITEM'); ?></option>
                    <?php
                    $sql = "SELECT * FROM itens ORDER BY item_nome";
                    $exec_sql = $conn->query($sql);
                    foreach ($exec_sql->fetchAll() as $rowType) {
                    ?>
                        <option value="<?= $rowType['item_cod']; ?>" <?= ($rowType['item_cod'] == $row['estoq_tipo'] ? ' selected' : ''); ?>><?= $rowType['item_nome']; ?></option>
                    <?php
                    }
                    ?>
                </select>
            </div>


            <label for="model_full_view" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_MODEL'); ?></label>
            <div class="form-group col-md-4">
                <select class="form-control sel3" id="model_full_view" name="model_full_view" disabled>
                    <?php
                    $sql = "SELECT * FROM modelos_itens m 
                        left join fabricantes f on m.mdit_manufacturer = f.fab_cod 
                        WHERE mdit_cod = '" . $row['estoq_desc'] . "' ";
                    // dump($sql);
                    $res = $conn->query($sql);
                    $rowType = $res->fetch();

                    $oldManufacturer = ($rowType['mdit_fabricante'] ? $rowType['mdit_fabricante'] . " " : "");
                    $manufacturer = ($rowType['fab_nome'] ? $rowType['fab_nome'] . " " : "");

                    ?>

                    <option value="<?= $rowType['mdit_cod']; ?>"><?= $manufacturer . $oldManufacturer . $rowType['mdit_desc'] . " " . $rowType['mdit_desc_capacidade'] . " " . $rowType['mdit_sufixo']; ?></option>

                </select>
            </div>

            <label for="serial_number_view" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('SERIAL_NUMBER'); ?></label>
            <div class="form-group col-md-4">
                <input type="text" class="form-control " id="serial_number_view" name="serial_number_view" value="<?= $row['estoq_sn']; ?>" disabled />
            </div>

            <label for="part_number_view" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_PARTNUMBER'); ?></label>
            <div class="form-group col-md-4">
                <input type="text" class="form-control " id="part_number_view" name="part_number_view" value="<?= $row['estoq_partnumber']; ?>" disabled />
            </div>

            <label for="asset_unit_view" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_UNIT'); ?></label>
            <div class="form-group col-md-4">
                <select class="form-control sel3" id="asset_unit_view" name="asset_unit_view" disabled>
                    <option value=""><?= TRANS('SEL_UNIT'); ?></option>
                    <?php
                    $sql = "SELECT * FROM instituicao ORDER BY inst_nome";
                    $exec_sql = $conn->query($sql);
                    foreach ($exec_sql->fetchAll() as $rowType) {
                    ?>
                        <option value="<?= $rowType['inst_cod']; ?>" <?= ($row['estoq_tag_inst'] == $rowType['inst_cod'] ? ' selected' : ''); ?>><?= $rowType['inst_nome']; ?></option>
                    <?php
                    }
                    ?>
                </select>
            </div>


            <label for="asset_tag_view" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('ASSET_TAG'); ?></label>
            <div class="form-group col-md-4">
                <input type="text" class="form-control " id="asset_tag_view" name="asset_tag_view" value="<?= $row['estoq_tag_inv']; ?>" disabled />
            </div>

            <label for="department_view" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('DEPARTMENT'); ?></label>
            <div class="form-group col-md-4">
                <select class="form-control sel3" id="department_view" name="department_view" disabled>
                    <option value=""><?= TRANS('SEL_DEPARTMENT'); ?></option>
                    <?php
                    $sql = "SELECT * FROM localizacao ORDER BY local";
                    $exec_sql = $conn->query($sql);
                    foreach ($exec_sql->fetchAll() as $rowType) {
                    ?>
                        <option value="<?= $rowType['loc_id']; ?>" <?= ($row['loc_id'] == $rowType['loc_id'] ? ' selected' : ''); ?>><?= $rowType['local']; ?></option>
                    <?php
                    }
                    ?>
                </select>
            </div>

            <label for="cost_center_view" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COST_CENTER'); ?></label>
            <div class="form-group col-md-4">
                <select class="form-control sel3" id="cost_center_view" name="cost_center_view" disabled>
                    <option value=""><?= TRANS('COST_CENTER'); ?></option>
                    <?php
                    $sql = "SELECT * FROM `" . DB_CCUSTO . "`." . TB_CCUSTO . "  ORDER BY " . CCUSTO_DESC . "";
                    $exec_sql = $conn->query($sql);
                    foreach ($exec_sql->fetchAll() as $rowType) {
                    ?>
                        <option value="<?= $rowType[CCUSTO_ID]; ?>" <?= ($row['codigo'] == $rowType[CCUSTO_ID] ? ' selected' : ''); ?>><?= $rowType[CCUSTO_DESC] . " - " . $rowType[CCUSTO_COD]; ?></option>
                    <?php
                    }
                    ?>
                </select>
            </div>


            <label for="purchase_date_view" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('PURCHASE_DATE'); ?></label>
            <div class="form-group col-md-4">
                <input type="text" class="form-control " id="purchase_date_view" name="purchase_date_view" autocomplete="off" value="<?= dateScreen($row['estoq_data_compra'], 1); ?>" disabled />
            </div>

            <label for="supplier_view" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('OCO_SEL_VENDOR'); ?></label>
            <div class="form-group col-md-4">
                <select class="form-control sel3" id="supplier_view" name="supplier_view" disabled>
                    <option value=""><?= TRANS('OCO_SEL_VENDOR'); ?></option>
                    <?php
                    $sql = "SELECT * FROM fornecedores ORDER BY forn_nome";
                    $exec_sql = $conn->query($sql);
                    foreach ($exec_sql->fetchAll() as $rowType) {
                    ?>
                        <option value="<?= $rowType['forn_cod']; ?>" <?= ($row['forn_cod'] == $rowType['forn_cod'] ? ' selected' : ''); ?>><?= $rowType['forn_nome']; ?></option>
                    <?php
                    }
                    ?>
                </select>
            </div>



            <label for="invoice_number_view" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_NF'); ?></label>
            <div class="form-group col-md-4">
                <input type="text" class="form-control " id="invoice_number_view" name="invoice_number_view" value="<?= $row['estoq_nf']; ?>" disabled />
            </div>

            <label for="price_view" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_VALUE'); ?></label>
            <div class="form-group col-md-4">
                <input type="text" class="form-control " id="price_view" name="price_view" value="<?= priceScreen($row['estoq_value']); ?>" disabled />
            </div>



            <label for="time_of_warranty_view" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('OCO_SEL_WARRANTY'); ?></label>
            <div class="form-group col-md-4">
                <select class="form-control sel3" id="time_of_warranty_view" name="time_of_warranty_view" disabled>
                    <option value=""><?= TRANS('FIELD_TIME_MONTH'); ?></option>
                    <?php
                    $sql = "SELECT * FROM tempo_garantia ORDER BY tempo_meses";
                    $exec_sql = $conn->query($sql);
                    foreach ($exec_sql->fetchAll() as $rowType) {
                    ?>
                        <option value="<?= $rowType['tempo_cod']; ?>" <?= ($row['tempo_cod'] == $rowType['tempo_cod'] ? ' selected' : ''); ?>><?= $rowType['tempo_meses'] . ' ' . TRANS('MONTHS'); ?></option>
                    <?php
                    }
                    ?>
                </select>
            </div>



            <label for="condition_view" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('STATE'); ?></label>
            <div class="form-group col-md-4">
                <select class="form-control sel3" id="condition_view" name="condition_view" disabled>
                    <option value=""><?= TRANS('STATE'); ?></option>
                    <?php
                    $sql = "SELECT * FROM situacao ORDER BY situac_nome";
                    $exec_sql = $conn->query($sql);
                    foreach ($exec_sql->fetchAll() as $rowType) {
                    ?>
                        <option value="<?= $rowType['situac_cod']; ?>" <?= ($row['situac_cod'] == $rowType['situac_cod'] ? ' selected' : ''); ?>><?= $rowType['situac_nome']; ?></option>
                    <?php
                    }
                    ?>
                </select>
            </div>


            <label for="assistance_view" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('ASSISTENCE'); ?></label>
            <div class="form-group col-md-4">
                <select class="form-control sel3" id="assistance_view" name="assistance_view" disabled>
                    <option value=""><?= TRANS('SEL_TYPE_ASSIST'); ?></option>
                    <?php
                    $sql = "SELECT * FROM assistencia ORDER BY assist_desc";
                    $exec_sql = $conn->query($sql);
                    foreach ($exec_sql->fetchAll() as $rowType) {
                    ?>
                        <option value="<?= $rowType['assist_cod']; ?>" <?= ($row['assistencia_cod'] == $rowType['assist_cod'] ? ' selected' : ''); ?>><?= $rowType['assist_desc']; ?></option>
                    <?php
                    }
                    ?>
                </select>
            </div>

            <label for="warranty_type_view" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_TYPE_WARRANTY'); ?></label>
            <div class="form-group col-md-4">
                <select class="form-control sel3" id="warranty_type_view" name="warranty_type_view" disabled>
                    <option value=""><?= TRANS('SEL_WARRANTY_TYPE'); ?></option>
                    <?php
                    $sql = "SELECT * FROM tipo_garantia ORDER BY tipo_garant_nome";
                    $exec_sql = $conn->query($sql);
                    foreach ($exec_sql->fetchAll() as $rowType) {
                    ?>
                        <option value="<?= $rowType['tipo_garant_cod']; ?>" <?= ($row['garantia_cod'] == $rowType['tipo_garant_cod'] ? ' selected' : ''); ?>><?= $rowType['tipo_garant_nome']; ?></option>
                    <?php
                    }
                    ?>
                </select>
            </div>

            <div class="w-100"></div>
            <label for="additional_info_view" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('ENTRY_TYPE_ADDITIONAL_INFO'); ?></label>
            <div class="form-group col-md-10">
                <textarea class="form-control " id="additional_info_view" name="additional_info_view" disabled><?= $row['estoq_comentario']; ?></textarea>
            </div>

            <h6 class="w-100 mt-4 ml-5 border-top p-4"><i class="fas fa-laptop text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('ASSOC_EQUIP_PIECES')); ?></h6>

            <label class="col-md-2 col-form-label text-md-right"><?= TRANS('IN_EQUIPMENT'); ?></label>
            <div class="form-group col-md-10 ">
                <div class="switch-field">
                    <?php
                    $yesChecked = ($row['eqp_equip_inst'] != '' && $row['eqp_equip_inv'] != '' ? 'checked' : '');
                    $noChecked = ($row['eqp_equip_inst'] == '' || $row['eqp_equip_inv'] == '' ? 'checked' : '');
                    ?>
                    <input type="radio" id="in_equipment_view" name="in_equipment_view" value="yes" <?= $yesChecked; ?> disabled />
                    <label for="in_equipment_view"><?= TRANS('YES'); ?></label>
                    <input type="radio" id="in_equipment_view_no" name="in_equipment_view" value="no" <?= $noChecked; ?> disabled />
                    <label for="in_equipment_view_no"><?= TRANS('NOT'); ?></label>
                </div>
            </div>

            <label for="equipment_unit_view" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_UNIT'); ?></label>
            <div class="form-group col-md-4">
                <select class="form-control sel3" id="equipment_unit_view" name="equipment_unit_view" disabled>
                    <option value=""><?= TRANS('SEL_UNIT'); ?></option>
                    <?php
                    $sql = "SELECT * FROM instituicao ORDER BY inst_nome";
                    $exec_sql = $conn->query($sql);
                    foreach ($exec_sql->fetchAll() as $rowType) {
                    ?>
                        <option value="<?= $rowType['inst_cod']; ?>" <?= ($row['eqp_equip_inst'] == $rowType['inst_cod'] ? ' selected' : ''); ?>><?= $rowType['inst_nome']; ?></option>
                    <?php
                    }
                    ?>
                </select>
            </div>


            <label for="equipment_tag_view" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('ASSET_TAG'); ?></label>
            <div class="form-group col-md-4">
                <input type="text" class="form-control " id="equipment_tag_view" name="equipment_tag_view" value="<?= $row['eqp_equip_inv']; ?>" disabled />
            </div>


            <div class="row w-100"></div>
            <div class="form-group col-md-10 d-none d-md-block">
            </div>
            <div class="form-group col-12 col-md-2 ">
                <?php
                if (!isset($_GET['origin']) || $_GET['origin'] != "modal") {
                ?>
                    <button type="reset" class="btn btn-secondary btn-block close-or-return"><?= TRANS('BT_RETURN'); ?></button>
                <?php
                }
                ?>
            </div>

        </div>
        <!-- </form> -->


        <script src="../../includes/javascript/funcoes-3.0.js"></script>
        <script src="../../includes/components/jquery/jquery.js"></script>
        <script src="../../includes/components/bootstrap/js/bootstrap.min.js"></script>
        <script>
            $(function() {
                closeOrReturn();
            });


            function popup_alerta(pagina) { //Exibe uma janela popUP
                x = window.open(pagina, '_blank', 'dependent=yes,width=700,height=470,scrollbars=yes,statusbar=no,resizable=yes');
                x.moveTo(window.parent.screenX + 50, window.parent.screenY + 50);
                return false
            }

            function popup_alerta_mini(pagina) { //Exibe uma janela popUP
                x = window.open(pagina, '_blank', 'dependent=yes,width=400,height=250,scrollbars=yes,statusbar=no,resizable=yes');
                x.moveTo(100, 100);
                x.moveTo(window.parent.screenX + 50, window.parent.screenY + 50);
                return false
            }

            function popup(pagina) { //Exibe uma janela popUP
                x = window.open(pagina, 'popup', 'dependent=yes,width=400,height=200,scrollbars=yes,statusbar=no,resizable=yes');
                x.moveTo(window.parent.screenX + 100, window.parent.screenY + 100);
                return false
            }

            function closeOrReturn(jumps = -1) {
                buttonValue();
                $('.close-or-return').on('click', function() {
                    if (isPopup()) {
                        window.close();
                    } else {
                        // $('#modal1').modal('hide');
                        window.history.go(jumps);
                    }
                });
            }

            function buttonValue() {
                if (isPopup()) {
                    $('.close-or-return').text('<?= TRANS('BT_CLOSE'); ?>');
                }
            }
        </script>
    </div>
</body>

</html>