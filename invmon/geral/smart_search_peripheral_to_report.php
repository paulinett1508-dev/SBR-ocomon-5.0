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

$imgsPath = "../../includes/imgs/";

$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 2, 2);

$_SESSION['s_page_invmon'] = $_SERVER['PHP_SELF'];

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../../includes/css/estilos.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/components/jquery/datetimepicker/jquery.datetimepicker.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap/custom.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/fontawesome/css/all.min.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/datatables/datatables.min.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/css/my_datatables.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap-select/dist/css/bootstrap-select.min.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/css/my_bootstrap_select.css" />

    <title>OcoMon&nbsp;<?= VERSAO; ?></title>

    <style>
        .input-group>.input-group-prepend {
            max-width: 60px;
            min-width: 60px;
        }

        .input-group .input-group-text {
            width: 100%;
        }

        .input-group>.input-group-append {
            max-width: 60px;
            min-width: 60px;
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



        <h5 class="my-4"><i class="fas fa-filter text-secondary"></i>&nbsp;<?= TRANS('TTL_SMART_SEARCH_PERIPHERAL_TO_REPORT'); ?></h5>
        <form method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form" onSubmit="return false;">
            <div class="form-group row my-4">
                <!-- form-row -->



                <!-- Unidade -->
                <label for="unidade" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_UNIT'); ?></label>
                <div class="form-group col-md-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-city"></i>&nbsp;
                                <input type="checkbox" class="first-check" name="no_empty_unidade" id="no_empty_unidade" value="1">
                            </div>
                        </div>
                        <select class="form-control sel2 " id="unidade" name="unidade[]" multiple="multiple">
                            <?php
                            $units = getUnits($conn, null, null, null, $_SESSION['s_allowed_units']);
                            foreach ($units as $unit) {
                                ?>
                                <option data-subtext="<?= $unit['nickname']; ?>" value="<?= $unit['inst_cod']; ?>"><?= $unit['inst_nome']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
                        <div class="input-group-append">
                            <div class="input-group-text" title="<?= TRANS('SMART_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-times"></i>&nbsp;
                                <input type="checkbox" class="last-check" name="no_unidade" id="no_unidade" value="1">
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Etiqueta -->
                <label for="etiqueta" class="col-md-2 col-form-label col-form-label-sm text-md-right text-nowrap"><?= TRANS('FIELD_TAG_EQUIP'); ?></label>
                <div class="form-group col-md-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-barcode"></i>&nbsp;
                                <input type="checkbox" name="no_empty_etiqueta" id="no_empty_etiqueta" class="first-check-text" value="1">
                            </div>
                        </div>
                        <input type="text" class="form-control " id="etiqueta" name="etiqueta" placeholder="<?= TRANS('OCO_SEL_ANY'); ?>" />

                        <div class="input-group-append">
                            <div class="input-group-text" title="<?= TRANS('SMART_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-times"></i>&nbsp;
                                <input type="checkbox" name="no_etiqueta" id="no_etiqueta" class="last-check-text" value="1">
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Tipo do componente -->
                <label for="equip_type" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_TYPE'); ?></label>
                <div class="form-group col-md-4">
                    <div class="input-group" name="terceiro-parent">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <!-- <i class="fas fa-tag"></i>&nbsp; -->
                                <i class="fas fa-laptop"></i>&nbsp;
                                <input type="checkbox" class="first-check" name="no_empty_equip_type" id="no_empty_equip_type" value="1">
                            </div>
                        </div>
                        <select class="form-control sel2" id="equip_type" name="equip_type[]" multiple="multiple">
                            <?php
                            $sql = "SELECT * FROM itens ORDER BY item_nome";
                            $resultado = $conn->query($sql);
                            foreach ($resultado->fetchAll(PDO::FETCH_ASSOC) as $row) {
                                print "<option value='" . $row['item_cod'] . "'";
                                print ">" . $row['item_nome'] . "</option>";
                            }
                            ?>
                        </select>
                        <div class="input-group-append">
                            <div class="input-group-text" title="<?= TRANS('SMART_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-times"></i>&nbsp;
                                <input type="checkbox" class="last-check" name="no_equip_type" id="no_equip_type" value="1">
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Fabricante -->
                <label for="manufacturer" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_MANUFACTURER'); ?></label>
                <div class="form-group col-md-4">
                    <div class="input-group" name="terceiro-parent">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <!-- <i class="fas fa-tag"></i>&nbsp; -->
                                <i class="fas fa-industry"></i>&nbsp;
                                <input type="checkbox" class="first-check" name="no_empty_manufacturer" id="no_empty_manufacturer" value="1">
                            </div>
                        </div>

                        <select class="form-control sel2" id="manufacturer" name="manufacturer[]" multiple="multiple">
                            <?php
                            $sql = "SELECT * FROM fabricantes ORDER BY fab_nome";
                            $resultado = $conn->query($sql);
                            foreach ($resultado->fetchAll(PDO::FETCH_ASSOC) as $row) {
                                print "<option value='" . $row['fab_cod'] . "'";
                                print ">" . $row['fab_nome'] . "</option>";
                            }
                            ?>
                        </select>

                        <div class="input-group-append">
                            <div class="input-group-text" title="<?= TRANS('SMART_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-times"></i>&nbsp;
                                <input type="checkbox" class="last-check" name="no_manufacturer" id="no_manufacturer" value="1">
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Modelo -->
                <label for="model" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_MODEL'); ?></label>
                <div class="form-group col-md-4">
                    <div class="input-group" name="terceiro-parent">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <!-- <i class="fas fa-tag"></i>&nbsp; -->
                                <i class="fas fa-clone"></i>&nbsp;
                                <input type="checkbox" class="first-check" name="no_empty_model" id="no_empty_model" value="1">
                            </div>
                        </div>
                        <select class="form-control sel2" id="model" name="model[]" multiple="multiple">
                            <?php
                            $sql = "SELECT * FROM modelos_itens m 
                                        left join fabricantes f on m.mdit_manufacturer = f.fab_cod 
                                    ORDER BY mdit_desc, mdit_desc_capacidade ";
                            $resultado = $conn->query($sql);
                            foreach ($resultado->fetchAll(PDO::FETCH_ASSOC) as $row) {
                                $oldManufacturer = ($row['mdit_fabricante'] ? $row['mdit_fabricante'] . " " : "");
                                $manufacturer = ($row['fab_nome'] ? $row['fab_nome'] . " " : "");

                                print "<option value='" . $row['mdit_cod'] . "'";
                                print ">" . $row['mdit_desc'] . " " . $row['mdit_desc_capacidade'] . " " . $row['mdit_sufixo'] . "</option>";
                            }
                            ?>
                        </select>
                        <div class="input-group-append">
                            <div class="input-group-text" title="<?= TRANS('SMART_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-times"></i>&nbsp;
                                <input type="checkbox" class="last-check" name="no_model" id="no_model" value="1">
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Número de série -->
                <label for="serial_number" class="col-md-2 col-form-label col-form-label-sm text-md-right text-nowrap"><?= TRANS('SERIAL_NUMBER'); ?></label>
                <div class="form-group col-md-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-ticket-alt"></i>&nbsp;
                                <input type="checkbox" name="no_empty_serial_number" id="no_empty_serial_number" class="first-check-text" value="1">
                            </div>
                        </div>
                        <input type="text" class="form-control " id="serial_number" name="serial_number" placeholder="<?= TRANS('OCO_SEL_ANY'); ?>" />

                        <div class="input-group-append">
                            <div class="input-group-text" title="<?= TRANS('SMART_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-times"></i>&nbsp;
                                <input type="checkbox" name="no_serial_number" id="no_serial_number" class="last-check-text" value="1">
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Part-number -->
                <label for="part_number" class="col-md-2 col-form-label col-form-label-sm text-md-right text-nowrap"><?= TRANS('COL_PARTNUMBER'); ?></label>
                <div class="form-group col-md-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-ticket-alt"></i>&nbsp;
                                <input type="checkbox" name="no_empty_part_number" id="no_empty_part_number" class="first-check-text" value="1">
                            </div>
                        </div>
                        <input type="text" class="form-control " id="part_number" name="part_number" placeholder="<?= TRANS('OCO_SEL_ANY'); ?>" />

                        <div class="input-group-append">
                            <div class="input-group-text" title="<?= TRANS('SMART_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-times"></i>&nbsp;
                                <input type="checkbox" name="no_part_number" id="no_part_number" class="last-check-text" value="1">
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Departamento -->
                <label for="departamento" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('DEPARTMENT'); ?></label>

                <div class="form-group col-md-4">
                    <div class="input-group" name="terceiro-parent">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <!-- <i class="fas fa-tag"></i>&nbsp; -->
                                <i class="fas fa-door-closed"></i>&nbsp;
                                <input type="checkbox" class="first-check" name="no_empty_departamento" id="no_empty_departamento" value="1">
                            </div>
                        </div>
                        <select class="form-control sel2" id="departamento" name="departamento[]" multiple="multiple">
                            <?php
                            $sql = "SELECT * FROM localizacao ORDER BY local";
                            $resultado = $conn->query($sql);
                            foreach ($resultado->fetchAll(PDO::FETCH_ASSOC) as $row) {
                                print "<option value='" . $row['loc_id'] . "'";
                                print ">" . $row['local'] . "</option>";
                            }
                            ?>
                        </select>
                        <div class="input-group-append">
                            <div class="input-group-text" title="<?= TRANS('SMART_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-times"></i>&nbsp;
                                <input type="checkbox" class="last-check" name="no_departamento" id="no_departamento" value="1">
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Centro de Custo -->
                <label for="cost_center" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COST_CENTER'); ?></label>

                <div class="form-group col-md-4">
                    <div class="input-group" name="terceiro-parent">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <!-- <i class="fas fa-tag"></i>&nbsp; -->
                                <i class="fas fa-file-invoice-dollar"></i>&nbsp;
                                <input type="checkbox" class="first-check" name="no_empty_cost_center" id="no_empty_cost_center" value="1">
                            </div>
                        </div>
                        <select class="form-control sel2" id="cost_center" name="cost_center[]" multiple="multiple">
                            <?php
                            $sql = "SELECT * FROM `" . DB_CCUSTO . "`." . TB_CCUSTO . "  ORDER BY " . CCUSTO_DESC . "";
                            $resultado = $conn->query($sql);
                            foreach ($resultado->fetchAll(PDO::FETCH_ASSOC) as $row) {
                                print "<option value='" . $row[CCUSTO_ID] . "'";
                                print ">" . $row[CCUSTO_DESC] . " - " . $row[CCUSTO_COD] . "</option>";
                            }
                            ?>
                        </select>
                        <div class="input-group-append">
                            <div class="input-group-text" title="<?= TRANS('SMART_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-times"></i>&nbsp;
                                <input type="checkbox" class="last-check" name="no_cost_center" id="no_cost_center" value="1">
                            </div>
                        </div>
                    </div>
                </div>





                <!-- Fornecedor -->
                <label for="supplier" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_VENDOR'); ?></label>

                <div class="form-group col-md-4">
                    <div class="input-group" name="terceiro-parent">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <!-- <i class="fas fa-tag"></i>&nbsp; -->
                                <i class="fas fa-user-tie"></i>&nbsp;
                                <input type="checkbox" class="first-check" name="no_empty_supplier" id="no_empty_supplier" value="1">
                            </div>
                        </div>
                        <select class="form-control sel2" id="supplier" name="supplier[]" multiple="multiple">
                            <?php
                            $sql = "SELECT * FROM fornecedores ORDER BY forn_nome";
                            $resultado = $conn->query($sql);
                            foreach ($resultado->fetchAll(PDO::FETCH_ASSOC) as $row) {
                                print "<option value='" . $row['forn_cod'] . "'";
                                print ">" . $row['forn_nome'] . "</option>";
                            }
                            ?>
                        </select>
                        <div class="input-group-append">
                            <div class="input-group-text" title="<?= TRANS('SMART_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-times"></i>&nbsp;
                                <input type="checkbox" class="last-check" name="no_supplier" id="no_supplier" value="1">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Assistencia -->
                <label for="assistance" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('ASSISTENCE'); ?></label>

                <div class="form-group col-md-4">
                    <div class="input-group" name="terceiro-parent">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <!-- <i class="fas fa-tag"></i>&nbsp; -->
                                <i class="fas fa-shield-alt"></i>&nbsp;
                                <input type="checkbox" class="first-check" name="no_empty_assistance" id="no_empty_assistance" value="1">
                            </div>
                        </div>
                        <select class="form-control sel2" id="assistance" name="assistance[]" multiple="multiple">
                            <?php
                            $sql = "SELECT * FROM assistencia ORDER BY assist_desc";
                            $resultado = $conn->query($sql);
                            foreach ($resultado->fetchAll(PDO::FETCH_ASSOC) as $row) {
                                print "<option value='" . $row['assist_cod'] . "'";
                                print ">" . $row['assist_desc'] . "</option>";
                            }
                            ?>
                        </select>
                        <div class="input-group-append">
                            <div class="input-group-text" title="<?= TRANS('SMART_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-times"></i>&nbsp;
                                <input type="checkbox" class="last-check" name="no_assistance" id="no_assistance" value="1">
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Nota fiscal -->
                <label for="invoice_number" class="col-md-2 col-form-label col-form-label-sm text-md-right text-nowrap"><?= TRANS('INVOICE_NUMBER'); ?></label>
                <div class="form-group col-md-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-file-invoice"></i>&nbsp;
                                <input type="checkbox" name="no_empty_invoice_number" id="no_empty_invoice_number" class="first-check-text" value="1">
                            </div>
                        </div>
                        <input type="text" class="form-control " id="invoice_number" name="invoice_number" placeholder="<?= TRANS('OCO_SEL_ANY'); ?>" />

                        <div class="input-group-append">
                            <div class="input-group-text" title="<?= TRANS('SMART_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-times"></i>&nbsp;
                                <input type="checkbox" name="no_invoice_number" id="no_invoice_number" class="last-check-text" value="1">
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Situacao -->
                <label for="condition" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('STATE'); ?></label>
                <div class="form-group col-md-4">
                    <div class="input-group" name="terceiro-parent">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <!-- <i class="fas fa-tag"></i>&nbsp; -->
                                <i class="fas fa-hashtag"></i>&nbsp;
                                <input type="checkbox" class="first-check" name="no_empty_condition" id="no_empty_condition" value="1">
                            </div>
                        </div>
                        <select class="form-control sel2" id="condition" name="condition[]" multiple="multiple">
                            <?php
                            $sql = "SELECT * FROM situacao ORDER BY situac_nome";
                            $resultado = $conn->query($sql);
                            foreach ($resultado->fetchAll(PDO::FETCH_ASSOC) as $row) {
                                print "<option value='" . $row['situac_cod'] . "'";
                                print ">" . $row['situac_nome'] . "</option>";
                            }
                            ?>
                        </select>
                        <div class="input-group-append">
                            <div class="input-group-text" title="<?= TRANS('SMART_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-times"></i>&nbsp;
                                <input type="checkbox" class="last-check" name="no_condition" id="no_condition" value="1">
                            </div>
                        </div>
                    </div>
                </div>



                <!-- Tipo de garantia -->
                <label for="warranty_type" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_TYPE_WARRANTY'); ?></label>

                <div class="form-group col-md-4">
                    <div class="input-group" name="terceiro-parent">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <!-- <i class="fas fa-tag"></i>&nbsp; -->
                                <i class="fas fa-toolbox"></i>&nbsp;
                                <input type="checkbox" class="first-check" name="no_empty_warranty_type" id="no_empty_warranty_type" value="1">
                            </div>
                        </div>
                        <select class="form-control sel2" id="warranty_type" name="warranty_type[]" multiple="multiple">
                            <?php
                            $sql = "SELECT * FROM tipo_garantia ORDER BY tipo_garant_nome";
                            $resultado = $conn->query($sql);
                            foreach ($resultado->fetchAll(PDO::FETCH_ASSOC) as $row) {
                                print "<option value='" . $row['tipo_garant_cod'] . "'";
                                print ">" . $row['tipo_garant_nome'] . "</option>";
                            }
                            ?>
                        </select>
                        <div class="input-group-append">
                            <div class="input-group-text" title="<?= TRANS('SMART_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-times"></i>&nbsp;
                                <input type="checkbox" class="last-check" name="no_warranty_type" id="no_warranty_type" value="1">
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Status da garantia -->
                <label for="warranty_status" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('WARRANTY_STATUS'); ?></label>

                <div class="form-group col-md-4">
                    <div class="input-group" name="terceiro-parent">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <!-- <i class="fas fa-tag"></i>&nbsp; -->
                                <i class="fas fa-business-time"></i>&nbsp;
                                <input type="checkbox" class="first-check" name="no_empty_warranty_status" id="no_empty_warranty_status" value="1">
                            </div>
                        </div>
                        <select class="form-control" id="warranty_status" name="warranty_status">
                            <option value=""><?= TRANS('OCO_SEL_ANY'); ?></option>
                            <?php
                            $warranty_status = [];
                            $warranty_status[1] = TRANS('UNDER_WARRANTY');
                            $warranty_status[2] = TRANS('SEL_GUARANTEE_EXPIRED');
                            // $warranty_status[3] = TRANS('MSG_NOT_DEFINED');

                            foreach ($warranty_status as $key => $value) {
                            ?>
                                <option value="<?= $key; ?>"><?= $value; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                        <div class="input-group-append">
                            <div class="input-group-text" title="<?= TRANS('SMART_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-times"></i>&nbsp;
                                <input type="checkbox" class="last-check" name="no_warranty_status" id="no_warranty_status" value="1">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Vínculo com equipamento -->
                <label for="equipment" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('ASSOC_EQUIP_PIECES'); ?></label>
                <div class="form-group col-md-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_ONLY_IN_EQUIPMENT'); ?>" data-placeholder="<?= TRANS('SMART_ONLY_IN_EQUIPMENT'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-stream"></i>&nbsp;
                                <input type="checkbox" class="first-check-text" name="only_relatives" id="only_relatives" value="1">
                            </div>
                        </div>

                        <input type="text" class="form-control " id="equipment" name="equipment" placeholder="<?= TRANS('OCO_SEL_ANY'); ?>" autocomplete="off" readonly />
                        <div class="input-group-append">
                            <div class="input-group-text" title="<?= TRANS('SMART_ONLY_OUT_OF_EQUIPMENT'); ?>" data-placeholder="<?= TRANS('SMART_ONLY_OUT_OF_EQUIPMENT'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <!-- <i class="fas fa-clock"></i>&nbsp; -->
                                <i class="fas fa-times"></i>&nbsp;
                                <input type="checkbox" class="last-check-text" name="no_relatives" id="no_relatives" value="1">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="w-100"></div>

                <!-- Data mínima de aquisição -->
                <label for="purchase_date_from" class="col-md-2 col-form-label col-form-label-sm text-md-right text-nowrap"><?= TRANS('SMART_MIN_PURCHASE_DATE'); ?></label>
                <div class="form-group col-md-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-calendar-alt"></i>&nbsp;
                                <input type="checkbox" name="no_empty_purchase_date_from" id="no_empty_purchase_date_from" class="first-check-text" value="1">
                            </div>
                        </div>
                        <input type="text" class="form-control " id="purchase_date_from" name="purchase_date_from" placeholder="<?= TRANS('OCO_SEL_ANY'); ?>" />

                        <div class="input-group-append">
                            <div class="input-group-text" title="<?= TRANS('SMART_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-times"></i>&nbsp;
                                <input type="checkbox" name="no_purchase_date_from" id="no_purchase_date_from" class="last-check-text" value="1">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Data máxima de aquisição -->
                <label for="purchase_date_to" class="col-md-2 col-form-label col-form-label-sm text-md-right text-nowrap"><?= TRANS('SMART_MAX_PURCHASE_DATE'); ?></label>
                <div class="form-group col-md-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-calendar-alt"></i>&nbsp;
                                <input type="checkbox" name="no_empty_purchase_date_to" id="no_empty_purchase_date_to" class="first-check-text" value="1">
                            </div>
                        </div>
                        <input type="text" class="form-control " id="purchase_date_to" name="purchase_date_to" placeholder="<?= TRANS('OCO_SEL_ANY'); ?>" />

                        <div class="input-group-append">
                            <div class="input-group-text" title="<?= TRANS('SMART_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-times"></i>&nbsp;
                                <input type="checkbox" name="no_purchase_date_to" id="no_purchase_date_to" class="last-check-text" value="1">
                            </div>
                        </div>
                    </div>
                </div>






                <div class="form-group col-md-6 d-none d-md-block"></div>

                <div class="row w-100"></div>
                <div class="form-group col-md-8 d-none d-md-block">
                </div>
                <div class="form-group col-12 col-md-2 ">
                    <button type="submit" id="idSearch" class="btn btn-primary btn-block"><?= TRANS('BT_SEARCH'); ?></button>
                </div>
                <div class="form-group col-12 col-md-2">
                    <button type="reset" id="idReset" class="btn btn-secondary btn-block text-nowrap"><?= TRANS('BT_CLEAR'); ?></button>
                </div>



            </div>
        </form>
    </div>


    <div id="print-info" class="d-none">&nbsp;</div>
    <div class="container-fluid">
        <div id="divResult"></div>
    </div>

    <script src="../../includes/javascript/funcoes-3.0.js"></script>
    <script src="../../includes/components/jquery/jquery.js"></script>
    <script type="text/javascript" charset="utf8" src="../../includes/components/datatables/datatables.js"></script>
    <script src="../../includes/components/jquery/jquery.initialize.min.js"></script>
	<script src="../../includes/components/jquery/datetimepicker/build/jquery.datetimepicker.full.min.js"></script>
    <script src="../../includes/components/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../includes/components/bootstrap-select/dist/js/bootstrap-select.min.js"></script>
    <script>
        $(function() {

            $(function() {
                $('[data-toggle="popover"]').popover()
            });

            $('.popover-dismiss').popover({
                trigger: 'focus'
            });

            /* Idioma global para os calendários */
			$.datetimepicker.setLocale('pt-BR');
            $('#purchase_date_from').datetimepicker({
                timepicker: false,
                format: 'd/m/Y',
                lazyInit: true
            });

            $('#purchase_date_to').datetimepicker({
                timepicker: false,
                format: 'd/m/Y',
                lazyInit: true
            });


            $('.first-check-text').on('click', function() {

                var group_parent = $(this).parents().eq(2); //object
                var select_input_id = group_parent.find(':text').attr('id');
                var last_checkbox_id = group_parent.find('input:last').attr('id');

                if ($(this).is(':checked')) {

                    $('#' + select_input_id).prop('disabled', true);
                    $('#' + last_checkbox_id).prop('checked', false);

                    $('#' + select_input_id).val('');
                    // $('#' + select_input_id).attr('placeholder', $(this).parent().attr('title'));
                    $('#' + select_input_id).attr('placeholder', $(this).parent().attr('data-placeholder'));

                } else {
                    $('#' + select_input_id).prop('disabled', false);
                    $('#' + select_input_id).attr('placeholder', '<?= TRANS('OCO_SEL_ANY', '', 1); ?>');
                }
            });

            $('.last-check-text').on('click', function() {

                var group_parent = $(this).parents().eq(2); //object
                var select_input_id = group_parent.find(':text').attr('id');
                var first_checkbox_id = group_parent.find('input:first').attr('id');

                if ($(this).is(':checked')) {
                    $('#' + select_input_id).prop('disabled', true);
                    $('#' + first_checkbox_id).prop('checked', false);

                    $('#' + select_input_id).val('');
                    $('#' + select_input_id).attr('placeholder', $(this).parent().attr('data-placeholder'));
                } else {
                    $('#' + select_input_id).prop('disabled', false);
                    $('#' + select_input_id).attr('placeholder', '<?= TRANS('OCO_SEL_ANY', '', 1); ?>');
                }
            });


            $('.first-check').on('click', function() {

                var group_parent = $(this).parents().eq(2); //object
                var select_input_id = group_parent.find('select').attr('id');
                var last_checkbox_id = group_parent.find('input:last').attr('id');

                 if ($(this).is(':checked')) {

                    $('#' + select_input_id).prop('disabled', true)
                        .selectpicker({title: $(this).parent().attr('data-placeholder')})
                        .selectpicker('refresh');
                    $('#' + last_checkbox_id).prop('checked', false);
                    $('#' + select_input_id).val(null).trigger('change');

                } else {
                    
                    $('#' + select_input_id).prop('disabled', false)
                        .selectpicker({title: "<?= TRANS('OCO_SEL_ANY', '', 1); ?>"})
                        .selectpicker('refresh');
                }
            });

            $('.last-check').on('click', function() {

                var group_parent = $(this).parents().eq(2); //object
                var select_input_id = group_parent.find('select').attr('id');
                var first_checkbox_id = group_parent.find('input:first').attr('id');

                if ($(this).is(':checked')) {

                    $('#' + select_input_id).prop('disabled', true)
                        .selectpicker({title: $(this).parent().attr('data-placeholder')})
                        .selectpicker('refresh');
                    $('#' + first_checkbox_id).prop('checked', false);

                    $('#' + select_input_id).val(null).trigger('change');
                } else {
                    
                    $('#' + select_input_id).prop('disabled', false)
                        .selectpicker({title: "<?= TRANS('OCO_SEL_ANY', '', 1); ?>"})
                        .selectpicker('refresh');
                }
            });



            $('#idSearch').on('click', function(e) {
                e.preventDefault();
                var loading = $(".loading");
                $(document).ajaxStart(function() {
                    loading.show();
                });

                $(document).ajaxStop(function() {
                    loading.hide();
                });

                $.ajax({
                    url: 'get_full_peripherals_table.php',
                    method: 'POST',
                    data: $('#form').serialize(),
                }).done(function(response) {
                    $('#divResult').html(response);
                });
                return false;
            });

            $("#idReset").click(function(e) {

                e.preventDefault();
                $("#form").trigger('reset');

                $(this).closest('form').find("input[type=text]").prop('disabled', false);
                $(this).closest('form').find("input[type=text]").attr('placeholder', '<?= TRANS('OCO_SEL_ANY', '', 1); ?>');
                $(this).closest('form').find("select").prop('disabled', false);

                $('.sel2').selectpicker('render');

                // $('.sel1').select2();

                $('#data_abertura_from').prop('disabled', true);
                $('#data_abertura_from').attr('placeholder', '<?= TRANS('FIELD_CURRENT_MONTH', '', 1); ?>');

            });

            $.fn.selectpicker.Constructor.BootstrapVersion = '4';
            $('.sel2').selectpicker({
                /* placeholder */
                title: "<?= TRANS('OCO_SEL_ANY', '', 1); ?>",
                liveSearch: true,
                liveSearchNormalize: true,
                liveSearchPlaceholder: "<?= TRANS('BT_SEARCH', '', 1); ?>",
                noneResultsText: "<?= TRANS('NO_RECORDS_FOUND', '', 1); ?> {0}",
                maxOptions: 5,
                maxOptionsText: "<?= TRANS('TEXT_MAX_OPTIONS', '', 1); ?>",
                style: "",
                styleBase: "form-control input-select-multi",
            });


            /* Adicionei o mutation observer em função dos elementos que são adicionados após o carregamento do DOM */
            var obs2 = $.initialize("#table_info", function() {
                $('#table_info').html($('#table_info_hidden').html());
                $('#print-info').html($('#table_info').html());

                /* Collumn resize */
                var pressed = false;
                var start = undefined;
                var startX, startWidth;

                $("table td").mousedown(function(e) {
                    start = $(this);
                    pressed = true;
                    startX = e.pageX;
                    startWidth = $(this).width();
                    $(start).addClass("resizing");
                });

                $(document).mousemove(function(e) {
                    if (pressed) {
                        $(start).width(startWidth + (e.pageX - startX));
                    }
                });

                $(document).mouseup(function() {
                    if (pressed) {
                        $(start).removeClass("resizing");
                        pressed = false;
                    }
                });
                /* end Collumn resize */

            }, {
                target: document.getElementById('divResult')
            }); /* o target limita o scopo do mutate observer */



            /* Adicionei o mutation observer em função dos elementos que são adicionados após o carregamento do DOM */
            var obs = $.initialize("#table_tickets_queue", function() {

                var criterios = $('#divCriterios').text();

                var table = $('#table_tickets_queue').DataTable({

                    paging: true,
                    pageLength: 25,
                    deferRender: true,
                    fixedHeader: true,
                    // scrollX: 300, /* para funcionar a coluna fixa */
                    // fixedColumns: true,
                    columnDefs: [{
                            targets: [
                                'unidade',
                                'etiqueta',
                            ],
                            visible: true,
                        },
                        {
                            targets: [
                                'serial_number',
                                'part_number',
                                'state',
                                'supplier',
                                'cost_center',
                                'invoice_number',
                                'purchase_date',
                                'assistance',
                                'warranty_type'
                            ],
                            orderable: true,
                            searchable: true,
                            visible: false
                        },

                    ],

                    colReorder: {
                        iFixedColumns: 1
                    },

                    "language": {
                        "url": "../../includes/components/datatables/datatables.pt-br.json"
                    },

                });

                // new $.fn.dataTable.ColReorder(table);

                new $.fn.dataTable.Buttons(table, {

                    buttons: [{
                            extend: 'print',
                            text: '<?= TRANS('SMART_BUTTON_PRINT', '', 1) ?>',
                            title: '<?= TRANS('SMART_CUSTOM_REPORT_TITLE', '', 1) ?>',
                            // message: 'Relatório de Ocorrências',
                            message: $('#print-info').html(),
                            autoPrint: true,

                            customize: function(win) {
                                $(win.document.body).find('table').addClass('display').css('font-size', '10px');
                                $(win.document.body).find('tr:nth-child(odd) td').each(function(index) {
                                    $(this).css('background-color', '#f9f9f9');
                                });
                                $(win.document.body).find('h1').css('text-align', 'center');
                            },
                            exportOptions: {
                                columns: ':visible'
                            },
                        },
                        {
                            extend: 'copyHtml5',
                            text: '<?= TRANS('SMART_BUTTON_COPY', '', 1) ?>',
                            exportOptions: {
                                columns: ':visible'
                            }
                        },
                        {
                            extend: 'excel',
                            text: "Excel",
                            exportOptions: {
                                columns: ':visible'
                            },
                            filename: '<?= TRANS('SMART_CUSTOM_REPORT_FILE_NAME', '', 1); ?>-<?= date('d-m-Y-H:i:s'); ?>',
                        },
                        {
                            extend: 'csvHtml5',
                            text: "CVS",
                            exportOptions: {
                                columns: ':visible'
                            },

                            filename: '<?= TRANS('SMART_CUSTOM_REPORT_FILE_NAME', '', 1); ?>-<?= date('d-m-Y-H:i:s'); ?>',
                        },
                        {
                            extend: 'pdfHtml5',
                            text: "PDF",

                            exportOptions: {
                                columns: ':visible',
                            },
                            title: '<?= TRANS('SMART_CUSTOM_REPORT_TITLE', '', 1); ?>',
                            filename: '<?= TRANS('SMART_CUSTOM_REPORT_FILE_NAME', '', 1); ?>-<?= date('d-m-Y-H:i:s'); ?>',
                            orientation: 'landscape',
                            pageSize: 'A4',

                            customize: function(doc) {
                                var criterios = $('#divCriterios').text()
                                var rdoc = doc;
                                var rcout = doc.content[doc.content.length - 1].table.body.length - 1;
                                doc.content.splice(0, 1);
                                var now = new Date();
                                var jsDate = now.getDate() + '/' + (now.getMonth() + 1) + '/' + now.getFullYear() + ' ' + now.getHours() + ':' + now.getMinutes() + ':' + now.getSeconds();
                                doc.pageMargins = [30, 70, 30, 30];
                                doc.defaultStyle.fontSize = 8;
                                doc.styles.tableHeader.fontSize = 9;

                                doc['header'] = (function(page, pages) {
                                    return {
                                        table: {
                                            widths: ['100%'],
                                            headerRows: 0,
                                            body: [
                                                [{
                                                    text: '<?= TRANS('SMART_CUSTOM_REPORT_TITLE', '', 1); ?>',
                                                    alignment: 'center',
                                                    fontSize: 14,
                                                    bold: true,
                                                    margin: [0, 10, 0, 0]
                                                }],
                                            ]
                                        },
                                        layout: 'noBorders',
                                        margin: 10
                                    }
                                });

                                doc['footer'] = (function(page, pages) {
                                    return {
                                        columns: [{
                                                alignment: 'left',
                                                text: ['Criado em: ', {
                                                    text: jsDate.toString()
                                                }]
                                            },
                                            {
                                                alignment: 'center',
                                                text: 'Total ' + rcout.toString() + ' linhas'
                                            },
                                            {
                                                alignment: 'right',
                                                text: ['página ', {
                                                    text: page.toString()
                                                }, ' de ', {
                                                    text: pages.toString()
                                                }]
                                            }
                                        ],
                                        margin: 10
                                    }
                                });

                                var objLayout = {};
                                objLayout['hLineWidth'] = function(i) {
                                    return .8;
                                };
                                objLayout['vLineWidth'] = function(i) {
                                    return .5;
                                };
                                objLayout['hLineColor'] = function(i) {
                                    return '#aaa';
                                };
                                objLayout['vLineColor'] = function(i) {
                                    return '#aaa';
                                };
                                objLayout['paddingLeft'] = function(i) {
                                    return 5;
                                };
                                objLayout['paddingRight'] = function(i) {
                                    return 35;
                                };
                                doc.content[doc.content.length - 1].layout = objLayout;

                            }

                        },
                        {
                            extend: 'colvis',
                            text: '<?= TRANS('SMART_BUTTON_MANAGE_COLLUMNS', '', 1) ?>',
                            // className: 'btn btn-primary',
                            columns: ':gt(0)'
                        },
                    ]
                });

                table.buttons().container()
                    .appendTo($('.display-buttons:eq(0)', table.table().container()));


            }, {
                target: document.getElementById('divResult')
            }); /* o target limita o scopo do mutate observer */


        });


        function openPeripheralInfo(estoq_cod) {

            let location = 'peripheral_show.php?cod=' + estoq_cod;
            // $("#divDetails").load(location);
            // $('#modal').modal();
            x = window.open(location, '_blank', 'dependent=yes,width=800,height=600,scrollbars=yes,statusbar=no,resizable=yes');
            x.moveTo(window.parent.screenX + 50, window.parent.screenY + 50);
            return false

        }
        function openEquipmentInfo(asset_tag, asset_unit) {

            let location = 'equipment_show.php?tag=' + asset_tag + '&unit=' + asset_unit;
            x = window.open(location, '_blank', 'dependent=yes,width=800,height=600,scrollbars=yes,statusbar=no,resizable=yes');
            x.moveTo(window.parent.screenX + 50, window.parent.screenY + 50);
            return false

        }
    </script>
</body>

</html>