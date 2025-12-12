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


/* Campos customizados */
$custom_fields_full = getCustomFields($conn, null, 'equipamentos');
$custom_fields_classes = [];
foreach ($custom_fields_full as $cfield) {
    $custom_fields_classes[] = $cfield['field_name'];
}
$custom_fields_classes_text = implode(",", $custom_fields_classes);


$logo = '../../includes/logos/MAIN_LOGO.png';
// Read image path, convert to base64 encoding
$logoType = pathinfo($logo, PATHINFO_EXTENSION);
$logoData = file_get_contents($logo);
$imgData = base64_encode($logoData);
// Format the image SRC:  data:{mime};base64,{data};
$imgSrc = 'data:image/' . $logoType . ';base64,'.$imgData;

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
    <link rel="stylesheet" type="text/css" href="../../includes/css/switch_radio.css" />

    <link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap-select/dist/css/bootstrap-select.min.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/css/my_bootstrap_select.css" />

    <title>OcoMon&nbsp;<?= VERSAO; ?></title>

    <style>
        .input-group >.input-group-prepend {
            max-width: 60px;
            min-width: 60px;
        }

        .input-group .input-group-text {
            width: 100%;
        }

        .input-group >.input-group-append {
            max-width: 60px;
            min-width: 60px;
        }

        .input-group>.double-append {
            max-width: 55px;
            min-width: 55px;
        }

        .list-attributes {
            line-height: 1.5em;
        }

    </style>

</head>

<body>
    
    <div class="container">
        <div id="idLoad" class="loading" style="display:none"></div>
    </div>

    <div class="container-fluid">

        <div class="modal" id="modal" tabindex="-1" style="z-index:9001!important" id="modalSubs">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div id="divDetails">
                    </div>
                </div>
            </div>
        </div>



        <h5 class="my-4"><i class="fas fa-filter text-secondary"></i>&nbsp;<?= TRANS('TTL_SMART_SEARCH_INVENTORY_TO_REPORT'); ?></h5>
        <form method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form" onSubmit="return false;">
            <div class="form-group row my-4">
                <!-- form-row -->
                
                <!-- Cliente -->
                <label for="client" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CLIENT'); ?></label>
                <div class="form-group col-md-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-user-tie"></i>&nbsp;
                            </div>
                        </div>
                        <select class="form-control sel2 " id="client" name="client[]" multiple="multiple">
                            <?php

                                $clients = getClients($conn, null, null, $_SESSION['s_allowed_clients']);
                                foreach ($clients as $client) {
                                    ?>
                                    <option value="<?= $client['id']; ?>"><?= $client['nickname']; ?></option>
                                    <?php
                                }
                            ?>
                        </select>
                    </div>
                </div>
                
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
                            $departments = getDepartments($conn);
                            foreach ($departments as $department) {
                                $client = (!empty($department['nickname']) ? " (" . $department['nickname'] .")" : "");
                                ?>
                                <option data-subtext="<?= $client; ?>" value="<?= $department['loc_id']; ?>"><?= $department['local']; ?></option>
                                <?php
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

                <!-- Etiqueta -->
                <label for="etiqueta" class="col-md-2 col-form-label col-form-label-sm text-md-right text-nowrap"><?= TRANS('FIELD_TAG_EQUIP'); ?></label>
                <div class="form-group col-md-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-qrcode"></i>&nbsp;
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


                <!-- Tipo do equipamento -->
                <label for="equip_type" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_TYPE'); ?></label>
                <div class="form-group col-md-4">
                    <div class="input-group" name="terceiro-parent">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <!-- <i class="fas fa-tag"></i>&nbsp; -->
                                <i class="fas fa-box"></i>&nbsp;
                                <input type="checkbox" class="first-check" name="no_empty_equip_type" id="no_empty_equip_type" value="1">
                            </div>
                        </div>
                        <select class="form-control sel2" id="equip_type" name="equip_type[]" multiple="multiple">
                            <?php
                            $sql = "SELECT * FROM tipo_equip ORDER BY tipo_nome";
                            $resultado = $conn->query($sql);
                            foreach ($resultado->fetchAll(PDO::FETCH_ASSOC) as $row) {
                                print "<option value='" . $row['tipo_cod'] . "'";
                                print ">" . $row['tipo_nome'] . "</option>";
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



                <!-- Categoria do ativo -->
                <label for="asset_category" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('ASSET_CATEGORY'); ?></label>
                <div class="form-group col-md-4">
                    <div class="input-group" name="terceiro-parent">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <!-- <i class="fas fa-tag"></i>&nbsp; -->
                                <i class="fas fa-tag"></i>&nbsp;
                                <input type="checkbox" class="first-check" name="no_empty_asset_category" id="no_empty_asset_category" value="1">
                            </div>
                        </div>
                        <select class="form-control sel2" id="asset_category" name="asset_category[]" multiple="multiple">
                            <?php
                            
                            $categories = getAssetsCategories($conn);
                            foreach ($categories as $category) {
                                ?>
                                    <option value="<?= $category['id']; ?>"><?= $category['cat_name']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
                        <div class="input-group-append">
                            <div class="input-group-text" title="<?= TRANS('SMART_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-times"></i>&nbsp;
                                <input type="checkbox" class="last-check" name="no_asset_category" id="no_asset_category" value="1">
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
                            $sql = "SELECT * FROM marcas_comp ORDER BY marc_nome";
                            $resultado = $conn->query($sql);
                            foreach ($resultado->fetchAll(PDO::FETCH_ASSOC) as $row) {
                                print "<option value='" . $row['marc_cod'] . "'";
                                print ">" . $row['marc_nome'] . "</option>";
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

                <!-- Part number -->
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



                


            </div>

            <!-- <div class="form-group row my-4">
                <div class="w-100"></div>
                <label class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('ADD'); ?></label>
                <div class="form-group col-md-10">
                    <a href="javascript:void(0);" class="add_button_new_pieces" title="<?= TRANS('ADD'); ?>"><i class="fa fa-plus"></i></a>
                </div>
            </div> -->

            <!-- Receberá as especificações para filtro -->
            <!-- <div class="form-group row my-4 new_pieces" id="new_pieces"></div> -->





            <!-- Guia para consulta sobre características diretas do ativo -->
            <div class="accordion" id="accordionAttributes">
                <div class="card ">
                    <div class="card-header bg-success" id="cardAttributes">
                        <h2 class="mb-0">
                            <button class="btn btn-block text-left text-white" type="button" data-toggle="collapse" data-target="#attributes" aria-expanded="false" aria-controls="attributes" onclick="this.blur();">
                                <h6 class="font-weight-bold"><i class="fas fa-ruler-combined"></i>&nbsp;<?= firstLetterUp(TRANS('DIRECT_ATTRIBUTES')); ?></h6>
                            </button>
                        </h2>
                    </div>

                    <div id="attributes" class="collapse " aria-labelledby="cardAttributes" data-parent="#accordionAttributes">
                        <div class="form-group row my-4">
                            
                            <label class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('ADD_FILTER'); ?></label>
                            <div class="form-group col-md-10">
                                <a href="javascript:void(0);" class="add_button_specs" title="<?= TRANS('NEW_SPEC'); ?>"><span class="text-success"><i class="fa fa-plus"></span></i></a>
                            </div>
                        
                        </div>
                        
                        <!-- Div que receberá o conteúdo dinámico dos atributos diretos-->
                        <div class="form-group row my-4 attribute_fields" id="attribute_fields"></div>
                    </div>
                </div>
            </div>






            <!-- Guia para consulta sobre características agregadas do ativo -->
            <div class="accordion" id="accordionAggregatedAttributes">
                <div class="card">
                    <div class="card-header bg-oc-olive" id="cardAggregatedAttributes">
                        <h2 class="mb-0">
                            <button class="btn btn-block text-left text-white" type="button" data-toggle="collapse" data-target="#aggregatedAttributes" aria-expanded="false" aria-controls="aggregatedAttributes" onclick="this.blur();">
                                <h6 class="font-weight-bold"><i class="fas fa-puzzle-piece text-white"></i>&nbsp;<?= firstLetterUp(TRANS('AGGREGATED_ATTRIBUTES')); ?></h6>
                            </button>
                        </h2>
                    </div>

                    <div id="aggregatedAttributes" class="collapse " aria-labelledby="cardAggregatedAttributes" data-parent="#accordionAggregatedAttributes">
                        <div class="form-group row my-4">
                            
                            <label class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('ADD_FILTER'); ?></label>
                            <div class="form-group col-md-10">
                                <a href="javascript:void(0);" class="add_button_aggregated_attribute" title="<?= TRANS('NEW_SPEC'); ?>"><span class="text-success"><i class="fa fa-plus"></i></span></a>
                            </div>
                        
                        </div>
                        
                        <!-- Div que receberá o conteúdo dinámico dos atributos agregados-->
                        <div class="form-group row my-4 aggregated_attribute_fields" id="aggregated_attribute_fields"></div>
                    </div>
                </div>
            </div>









            <!-- Aqui será o bloco referente aos campos personalizados -->
            <div id="div_custom_fields"></div>
            <!-- Fim do bloco referente aos campos personalizados -->

            <div class="form-group row my-4">


                <div class="form-group col-md-6 d-none d-md-block"></div>

                <div class="row w-100"></div>
                <div class="form-group col-md-8 d-none d-md-block">
                </div>
                <div class="form-group col-12 col-md-2 ">
                    <input type="hidden" name="custom_fields_classes_text" id="custom_fields_classes_text" value="<?= $custom_fields_classes_text; ?>">
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
    <script src="../../includes/components/Inputmask-5.x/dist/inputmask.min.js"></script>
    <script src="../../includes/components/Inputmask-5.x/dist/bindings/inputmask.binding.js"></script>
    <script src="./js/smart_search_assets_columns.js"></script>

    <script>
        $(function() {

            let hiddenColunsCookie = getCookie('oc_assets_sf_hidden_columns');
            let hiddenColunsCookieArray = hiddenColunsCookie.split(',');
            let customFieldsClassesText = $('#custom_fields_classes_text').val();
            let customFieldsClassesArray = customFieldsClassesText.split(',');

            var allColumns = reportAllColumns
            .concat(customFieldsClassesArray);

            var defaultHiddenColumns = hiddenColunsCookieArray
            if (defaultHiddenColumns == null || defaultHiddenColumns.length == 0 || defaultHiddenColumns == '') {
                defaultHiddenColumns = reportDefaultHiddenColumns.concat(customFieldsClassesArray);
            }

            let columnsOrderCookie = getCookie('oc_assets_sf_columns_order');
            let colunsOrderCookieArray = columnsOrderCookie.split(',');
            var defaultColumnsOrder = colunsOrderCookieArray;

            $(function() {
                $('[data-toggle="popover"]').popover()
            });

            $('.popover-dismiss').popover({
                trigger: 'focus'
            });

            /* Completa o form com os campos personalizados e ativos */
            $.ajax({
                url: '../../ocomon/geral/smart_search_custom_fields.php',
                type: 'POST',
                data: {
                    table: 'equipamentos'
                },

                success: function(data) {
                    $('#div_custom_fields').html(data);
                },
                
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



            $('.add_button_new_pieces').on('click', function() {
                loadNewSpecField();
			});

            // $('.new_pieces').on('click', '.remove_button_specs', function(e) {
            //     e.preventDefault();
			// 	dataRandom = $(this).attr('data-random');
			// 	$("."+dataRandom).remove();
            // });



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



            /* Atributos diretos dos ativos */
            var attrsObs = $.initialize(".after-dom-ready", function() {
					
                $('.bs-select').selectpicker({
                    /* placeholder */
                    container: 'body',
                    title: "<?= TRANS('SEL_SELECT', '', 1); ?>",
                    liveSearch: true,
                    liveSearchNormalize: true,
                    liveSearchPlaceholder: "<?= TRANS('BT_SEARCH', '', 1); ?>",
                    noneResultsText: "<?= TRANS('NO_RECORDS_FOUND', '', 1); ?> {0}",
                    style: "",
                    styleBase: "form-control ",
                });

                // availablesMeasureTypesControl();

                $('.after-dom-ready').on('change', function() {

                    // availablesMeasureTypesControl();

                    // var selectedValue = $(this).val();
                    var myId = $(this).attr('id');
                    loadMeasureUnits(myId);
                    measureValueControl(myId);
                });

            }, {
                target: document.getElementById('attribute_fields')
            }); /* o target limita o scopo do observer */



            /* Atributos agregados aos ativos */
            var aggregatedAttrsObs = $.initialize(".after-dom-ready-aggregated", function() {
					
                $('.bs-select').selectpicker({
                    /* placeholder */
                    container: 'body',
                    title: "<?= TRANS('SEL_SELECT', '', 1); ?>",
                    liveSearch: true,
                    liveSearchNormalize: true,
                    liveSearchPlaceholder: "<?= TRANS('BT_SEARCH', '', 1); ?>",
                    noneResultsText: "<?= TRANS('NO_RECORDS_FOUND', '', 1); ?> {0}",
                    style: "",
                    styleBase: "form-control ",
                });

                $('#aggregated_attribute_fields').on('click', '.first-check', function(){
                    let myId = $(this).attr('id');
                    let prefix = 'no_empty_';
                    let sufix = '_';

                    let baseRandomId = myId.substr(prefix.length, myId.length - (prefix.length + sufix.length));

                    if ($(this).is(':checked')) {
                        
                        $('#no_' + baseRandomId + '_').prop('checked', false);
                        
                        $('#' + baseRandomId).prop('disabled', true)
                            // .selectpicker({title: $(this).parent().attr('data-placeholder')})
                            .selectpicker('refresh')
                            .selectpicker('val', '');
                        $('#operation_' + baseRandomId).prop('disabled', true)
                            .selectpicker('refresh')
                            .selectpicker('val', '');

                        $('#' + baseRandomId + '_' + baseRandomId + '_' + baseRandomId).prop('disabled', true)
                            .selectpicker('val', 0)
                            .selectpicker('refresh')

                        $('#' + baseRandomId + '_' + baseRandomId).prop('disabled', true)
                            .selectpicker('val', '')
                            .selectpicker('refresh')
                        
                    } else {
                        $('#no_' + baseRandomId + '_').prop('disabled', false);
                        
                        $('#' + baseRandomId).prop('disabled', false)
                            .selectpicker('refresh');

                        $('#operation_' + baseRandomId).prop('disabled', false)
                            .selectpicker('refresh');

                        $('#' + baseRandomId + '_' + baseRandomId + '_' + baseRandomId).prop('disabled', false)
                            .selectpicker('refresh')

                        $('#' + baseRandomId + '_' + baseRandomId).prop('disabled', false)
                            .selectpicker('refresh')
                    }
                })

                $('#aggregated_attribute_fields').on('click', '.last-check', function(){
                    let myId = $(this).attr('id');
                    let prefix = 'no_';
                    let sufix = '_';

                    let baseRandomId = myId.substr(prefix.length, myId.length - (prefix.length + sufix.length));

                    if ($(this).is(':checked')) {

                        $('#no_empty_' + baseRandomId + '_').prop('checked', false);

                        $('#' + baseRandomId).prop('disabled', true)
                            // .selectpicker({title: $(this).parent().attr('data-placeholder')})
                            .selectpicker('refresh')
                            .selectpicker('val', '');
                        $('#operation_' + baseRandomId).prop('disabled', true)
                            .selectpicker('refresh')
                            .selectpicker('val', '');

                        $('#' + baseRandomId + '_' + baseRandomId + '_' + baseRandomId).prop('disabled', true)
                            .selectpicker('val', 0)
                            .selectpicker('refresh')

                        $('#' + baseRandomId + '_' + baseRandomId).prop('disabled', true)
                            .selectpicker('val', '')
                            .selectpicker('refresh')
                        
                    } else {
                        $('#no_empty_' + baseRandomId + '_').prop('disabled', false);
                        
                        $('#' + baseRandomId).prop('disabled', false)
                            .selectpicker('refresh');

                        $('#operation_' + baseRandomId).prop('disabled', false)
                            .selectpicker('refresh');

                        $('#' + baseRandomId + '_' + baseRandomId + '_' + baseRandomId).prop('disabled', false)
                            .selectpicker('refresh')

                        $('#' + baseRandomId + '_' + baseRandomId).prop('disabled', false)
                            .selectpicker('refresh')
                    }
                })
                
                
                $('.aggregated-types').on('change', function() {
                    var myId = $(this).attr('id');
                    
                    var selected_value = $(this).val();


                    
                    if (selected_value != '') {
                        $('#no_empty_' + myId).prop('disabled', false);
                        $('#no_' + myId).prop('disabled', false);
                    } else {
                        $('#no_empty_' + myId).prop('disabled', true).prop('checked', false);
                        $('#no_' + myId).prop('disabled', true).prop('checked', false);
                    }

                    $('#no_empty_' + myId).attr('name', 'no_empty_[' + selected_value + ']');
                    $('#no_' + myId).attr('name', 'no_[' + selected_value + ']');
                   

                    typeMeasureValueControl(myId);
                });

                $('.after-dom-ready-aggregated').on('change', function() {
                    var myId = $(this).attr('id');

                    loadMeasureUnits(myId);
                    measureValueControl(myId);
                });

    
            }, {
                target: document.getElementById('aggregated_attribute_fields')
            }); /* o target limita o scopo do observer */




            /* Para campos personalizados (criados após o carregamento do DOM) - bind pelas classes*/
            var obsCustomFields = $.initialize("#accordionCustomFields", function() {

                $('#toggle_check_norender_checkboxes').change(function() {
                    if ($(this).is(':checked')) {
                        selectAllNoRenderCheckboxes();
                    } else {
                        unselectAllNoRenderCheckboxes();
                    }
                });

                $('.custom_field_select_multi, .custom_field_select').selectpicker({
                    container: "body",
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

                $('.custom_field_datetime').datetimepicker({
                    timepicker: true,
                    format: 'd/m/Y H:i',
                    step: 30,
                    lazyInit: true
                });

                $('.custom_field_date').datetimepicker({
                    timepicker: false,
                    format: 'd/m/Y',
                    lazyInit: true
                });

                $('.custom_field_time').datetimepicker({
                    datepicker: false,
                    format: 'H:i',
                    step: 30,
                    lazyInit: true
                });

                customDateFillControl();

                customNumberFillControl();

                /* Controle dos checkboxes para os campos do tipo data */
                $('.first-check-date').on('click', function() {

                    customDateFillControl();

                    var group_parent = $(this).parents().eq(2); //object
                    var select_input_id = group_parent.find(':text').attr('id');
                    var last_checkbox_id = group_parent.find('input:last').attr('id');

                    var next_group_parent = $($(this).parents().eq(3).next()).next(); //object
                    var next_select_input_id = next_group_parent.find(':text').attr('id');

                    if ($(this).is(':checked')) {

                        $('#' + select_input_id).prop('disabled', true);
                        $('#' + last_checkbox_id).prop('checked', false);

                        $('#' + select_input_id).val('');
                        $('#' + select_input_id).attr('placeholder', $(this).parent().attr('data-placeholder'));

                        $('#' + next_select_input_id).val('').prop('disabled', true);

                    } else {
                        $('#' + select_input_id).prop('disabled', false);
                        $('#' + select_input_id).attr('placeholder', '<?= TRANS('OCO_SEL_ANY', '', 1); ?>');

                        $('#' + next_select_input_id).val('').prop('disabled', false);
                    }
                });

                $('.last-check-date').on('click', function() {

                    customDateFillControl();

                    var group_parent = $(this).parents().eq(2); //object
                    var select_input_id = group_parent.find(':text').attr('id');
                    var first_checkbox_id = group_parent.find('input:first').attr('id');

                    var next_group_parent = $($(this).parents().eq(3).next()).next(); //object
                    var next_select_input_id = next_group_parent.find(':text').attr('id');

                    if ($(this).is(':checked')) {
                        $('#' + select_input_id).prop('disabled', true);
                        $('#' + first_checkbox_id).prop('checked', false);

                        $('#' + select_input_id).val('');
                        $('#' + select_input_id).attr('placeholder', $(this).parent().attr('data-placeholder'));

                        $('#' + next_select_input_id).val('').prop('disabled', true);
                    } else {
                        $('#' + select_input_id).prop('disabled', false);
                        $('#' + select_input_id).attr('placeholder', '<?= TRANS('OCO_SEL_ANY', '', 1); ?>');

                        $('#' + next_select_input_id).val('').prop('disabled', false);
                    }
                });



                $('.first-check-text').on('click', function() {

                    var group_parent = $(this).parents().eq(2); //object
                    var select_input_id = group_parent.find(':text').attr('id');
                    var last_checkbox_id = group_parent.find('input:last').attr('id');

                    if ($(this).is(':checked')) {

                        $('#' + select_input_id).prop('disabled', true);
                        $('#' + last_checkbox_id).prop('checked', false);

                        $('#' + select_input_id).val('');
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


                $('.first-check-number').on('click', function() {

                    customNumberFillControl();

                    var group_parent = $(this).parents().eq(2); //object
                    var select_input_id = group_parent.find('.custom_field_number').attr('id');
                    var last_checkbox_id = group_parent.find('input:last').attr('id');

                    var next_group_parent = $($(this).parents().eq(3).next()).next(); //object
                    var next_select_input_id = next_group_parent.find('.custom_field_number').attr('id');

                    if ($(this).is(':checked')) {

                        $('#' + select_input_id).prop('disabled', true);
                        $('#' + last_checkbox_id).prop('checked', false);

                        $('#' + select_input_id).val('');
                        $('#' + select_input_id).attr('placeholder', $(this).parent().attr('data-placeholder'));

                        $('#' + next_select_input_id).val('').prop('disabled', true);

                    } else {
                        $('#' + select_input_id).prop('disabled', false);
                        $('#' + select_input_id).attr('placeholder', '<?= TRANS('OCO_SEL_ANY', '', 1); ?>');

                        $('#' + next_select_input_id).val('').prop('disabled', false);
                    }
                });

                $('.last-check-number').on('click', function() {

                    customNumberFillControl();

                    var group_parent = $(this).parents().eq(2); //object
                    var select_input_id = group_parent.find('.custom_field_number').attr('id');
                    var first_checkbox_id = group_parent.find('input:first').attr('id');

                    var next_group_parent = $($(this).parents().eq(3).next()).next(); //object
                    var next_select_input_id = next_group_parent.find('.custom_field_number').attr('id');

                    if ($(this).is(':checked')) {
                        $('#' + select_input_id).prop('disabled', true);
                        $('#' + first_checkbox_id).prop('checked', false);

                        $('#' + select_input_id).val('');
                        $('#' + select_input_id).attr('placeholder', $(this).parent().attr('data-placeholder'));

                        $('#' + next_select_input_id).val('').prop('disabled', true);
                    } else {
                        $('#' + select_input_id).prop('disabled', false);
                        $('#' + select_input_id).attr('placeholder', '<?= TRANS('OCO_SEL_ANY', '', 1); ?>');

                        $('#' + next_select_input_id).val('').prop('disabled', false);
                    }
                });



            }, {
                target: document.getElementById('div_custom_fields')
            }); /* o target limita o scopo do observer */



            $('.add_button_specs').on('click', function() {
				loadNewAttributeField();
			});
            $('.add_button_aggregated_attribute').on('click', function() {
				loadAggregatedAttributeField();
			});

            $('.attribute_fields').on('click', '.remove_button_specs', function(e) {
                e.preventDefault();
				dataRandom = $(this).attr('data-random');
				$("."+dataRandom).remove();
            });

            $('.aggregated_attribute_fields').on('click', '.remove_button_specs', function(e) {
                e.preventDefault();
				dataRandom = $(this).attr('data-random');
				$("."+dataRandom).remove();
            });


            $('#idSearch').on('click', function(e) {
                e.preventDefault();
                submitSearch();
            });

            $("#idReset").click(function(e) {

                e.preventDefault();
                $("#form").trigger('reset');

                $(this).closest('form').find("input[type=text]").prop('disabled', false);
                $(this).closest('form').find("input[type=text]").attr('placeholder', '<?= TRANS('OCO_SEL_ANY', '', 1); ?>');
                $(this).closest('form').find("select").prop('disabled', false);

                $('.sel2').selectpicker('render');

                $('#data_abertura_from').prop('disabled', true);
                $('#data_abertura_from').attr('placeholder', '<?= TRANS('FIELD_CURRENT_MONTH', '', 1); ?>');

            });

            $.fn.selectpicker.Constructor.BootstrapVersion = '4';
            
            
            $('.sel2').selectpicker({
                /* placeholder */
                title: "<?= TRANS('OCO_SEL_ANY', '', 1); ?>",
                showSubtext: true,
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
                            targets: defaultHiddenColumns,
                            visible: false,
                        },
                        {
                            targets: reportNotOrderable,
                            orderable: false,
                            searchable: false,
                        },
                        {
                            targets: reportNotSearchable,
                            searchable: false,
                        },
                    ],

                    colReorder: {
                        iFixedColumns: 1,
                        order : defaultColumnsOrder
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
                                        columns: [
                                            {
                                                margin: [20, 10, 0, 0],
                                                image: '<?= $imgSrc; ?>'
                                            } ,
                                            {
                                                table: {
                                                    widths: ['100%'],
                                                    headerRows: 0,
                                                    body: [
                                                        [{
                                                            text: '<?= TRANS('SMART_CUSTOM_REPORT_TITLE', '', 1); ?>',
                                                            alignment: 'center',
                                                            
                                                            fontSize: 14,
                                                            bold: true,
                                                            margin: [0, 20, 0, 0]
                                                            
                                                        }],
                                                    ]
                                                },
                                                layout: 'noBorders',
                                                margin: 10,
                                            }
                                        ],
                                        
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
                            // columns: ':gt(0)', 
                            collectionLayout: 'four-column',
                            attr: {
                                id: 'colvisID'
                            },
                        },
                        {
                            text: '<?= TRANS('REMEMBER_VISIBLE_COLUMNS', '', 1) ?>',
                            attr: {
                                title: '<?= TRANS('REMEMBER_VISIBLE_COLUMNS', '', 1) ?>',
                                id: 'customButton'
                            },
                        }
                    ]
                });

                table.buttons().container()
                    .appendTo($('.display-buttons:eq(0)', table.table().container()));


            }, {
                target: document.getElementById('divResult')
            }); /* o target limita o scopo do mutate observer */



            /* Observando o gerenciamento de colunas*/
            var obsColvis = $.initialize("#table_tickets_queue", function() {

                var table2 = $('#table_tickets_queue').DataTable();

                $('#customButton').on('click', function(){
                    defaultHiddenColumns = getHiddenColumns(table2, allColumns);

                    defaultColumnsOrder = getColumnsOrder(table2);

                });

            }, {
                target: document.getElementById('divResult')
            }); /* o target limita o scopo do mutate observer */


        });


        function submitSearch () {
            var loading = $(".loading");
            $(document).ajaxStart(function() {
                loading.show();
            });

            $(document).ajaxStop(function() {
                loading.hide();
            });

            $.ajax({
                url: 'get_full_equipments_table.php',
                method: 'POST',
                data: $('#form').serialize(),
            }).done(function(response) {
                $('#divResult').html(response);
            });
            return false;
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
                url: './render_new_spec_field_to_filter_asset.php',
                method: 'POST',
                data: {
                    random: Math.random().toString(16).substr(2, 8)
                },
                // dataType: 'json',
            }).done(function(data) {
                $('#new_pieces').append(data);
            });
            return false;
		}

        // function loadModelsByNewPiece(elementID) {
		// 	if ($('#new_pieces').length > 0) {
		// 		var loading = $(".loading");
		// 		$(document).ajaxStart(function() {
		// 			loading.show();
		// 		});
		// 		$(document).ajaxStop(function() {
		// 			loading.hide();
		// 		});

		// 		$.ajax({
		// 			url: './get_asset_type_models_with_specs.php',
		// 			method: 'POST',
		// 			data: {
		// 				asset_type: $('#'+elementID).val(),
		// 			},
		// 			dataType: 'json',
		// 		}).done(function(data) {
		// 			let html = '';
                    
        //             if (data.length > 0) {
        //                 for (i in data) {
        //                     html += '<option data-subtext="' + data[i].spec + '" value="' + data[i].codigo + '">' + data[i].modelo + '</option>';
        //                 }
        //             }
		// 			/* Para conseguir mapear os ids que vêm após o carregamento do DOM, 
		// 			criei a regra de duplicar o ID para o segundo campo - Assim só preciso passar um parâmetro para a função */
		// 			$('#'+elementID+'_'+elementID).empty().html(html);
		// 			$('#'+elementID+'_'+elementID).selectpicker('refresh');
		// 			$('#'+elementID+'_'+elementID).selectpicker('render');
		// 		});
		// 		return false;
		// 	}
		// }


        function customDateFillControl() {
            $('.custom_field_date_min').on('change focus', function() {
                var next_group_parent = $($(this).parents().eq(1).next()).next(); //object
                var next_select_input_id = next_group_parent.find(':text').attr('id');

                $(this).datetimepicker({
                    format: 'd/m/Y',
                    onShow: function(ct) {

                        if ($('#' + next_select_input_id).val() != '') {
                            this.setOptions({
                                maxDate: $('#' + next_select_input_id).datetimepicker('getValue')
                            })
                        }

                    },
                    timepicker: false
                });
            });

            $('.custom_field_date_max').on('change focus', function() {
                var prev_group_parent = $(this).parents().prev().prev(); //object
                var prev_select_input_id = prev_group_parent.find(':text').attr('id');

                $(this).datetimepicker({
                    format: 'd/m/Y',
                    onShow: function(ct) {
                        if ($('#' + prev_select_input_id).val() != '') {
                            this.setOptions({
                                minDate: $('#' + prev_select_input_id).datetimepicker('getValue')
                            })
                        }
                    },
                    timepicker: false
                });
            });

            $('.custom_field_datetime_min').on('change focus', function() {
                var next_group_parent = $($(this).parents().eq(1).next()).next(); //object
                var next_select_input_id = next_group_parent.find(':text').attr('id');

                $(this).datetimepicker({
                    format: 'd/m/Y H:i',
                    onShow: function(ct) {
                        if ($('#' + next_select_input_id).val() != '') {
                            this.setOptions({
                                maxDate: $('#' + next_select_input_id).datetimepicker('getValue')
                            })
                        }
                    },
                    timepicker: true
                });
            });

            $('.custom_field_datetime_max').on('change focus', function() {
                var prev_group_parent = $(this).parents().prev().prev(); //object
                var prev_select_input_id = prev_group_parent.find(':text').attr('id');

                $(this).datetimepicker({
                    format: 'd/m/Y H:i',
                    onShow: function(ct) {
                        if ($('#' + prev_select_input_id).val() != '') {
                            this.setOptions({
                                minDate: $('#' + prev_select_input_id).datetimepicker('getValue')
                            })
                        }
                    },
                    timepicker: true
                });
            });
        }


        function customNumberFillControl() {
            $('.custom_field_number_min').on('change focus blur', function() {
                var next_group_parent = $($(this).parents().eq(1).next()).next(); //object
                var next_select_input_id = next_group_parent.find('.custom_field_number_max').attr('id');

                if ($(this).val() != '') {
                    $('#' + next_select_input_id).attr("min", $(this).val());
                } else {
                    $('#' + next_select_input_id).removeAttr("min");
                }
            });

            $('.custom_field_number_max').on('change focus blur', function() {
                var prev_group_parent = $(this).parents().prev().prev(); //object
                var prev_select_input_id = prev_group_parent.find('.custom_field_number_min').attr('id');

                if ($(this).val() != '') {
                    $('#' + prev_select_input_id).attr("max", $(this).val());
                } else {
                    $('#' + prev_select_input_id).removeAttr("max");
                }
            });
        }

		function loadNewAttributeField() {
            var loading = $(".loading");
            $(document).ajaxStart(function() {
                loading.show();
            });
            $(document).ajaxStop(function() {
                loading.hide();
            });

            $.ajax({
                url: './render_new_specification_field_to_filter.php',
                method: 'POST',
                data: {
                    random: Math.random().toString(16).substr(2, 8)
                },
            }).done(function(data) {
                $('#attribute_fields').append(data);
            });
            return false;
		}


        function loadAggregatedAttributeField() {
            var loading = $(".loading");
            $(document).ajaxStart(function() {
                loading.show();
            });
            $(document).ajaxStop(function() {
                loading.hide();
            });

            $.ajax({
                url: './render_aggregated_attr_field_to_filter.php',
                method: 'POST',
                data: {
                    random: Math.random().toString(16).substr(2, 8)
                },
            }).done(function(data) {
                $('#aggregated_attribute_fields').append(data);
            });
            return false;
		}

        function loadMeasureUnits(elementID = "measure_type") {
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
                $('#'+elementID+'_'+elementID).selectpicker('refresh');
            });
            return false;
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

        function typeMeasureValueControl(elementID = "asset_type_aggregated") {
			
			let elToListener = elementID + '_';
            let elToEnableOrDisable = elementID;
            
			if ($('#' + elToEnableOrDisable).length > 0) {
				if ($('#'+elToListener).val() != '') {
					enableField(elToEnableOrDisable);
				} else {
					disableField(elToEnableOrDisable);
				}
			}
		}


        function getHiddenColumns(table, columnsClasses) {
            // console.log(table.column('.aberto_por').visible() === true ? 'visible' : 'not visible');
            let hiddenColumns = []

            for (let i in columnsClasses) {
                if (table.column('.' + columnsClasses[i]).visible() !== true) {
                    hiddenColumns.push(columnsClasses[i]) 
                }
            }

            /* Fazer um ajax para gravar cookies com o array de colunas ocultas - Esse array deve ser consultado 
            toda a vez que o datatables for carregado */
            $.ajax({
                url: 'set_cookie_assets_recent_columns.php',
                type: 'POST',
                data: {
                    columnsClasses: hiddenColumns,
                    app: 'smartSearch'
                },
                success: function(data) {
                    // console.log(data);
                }
            });

            defaultHiddenColumns = hiddenColumns;
            return hiddenColumns;
        }


        function getColumnsOrder(table) {
            let columnsOrder = []

            columnsOrder = table.colReorder.order();

            $.ajax({
                url: 'set_cookie_assets_columns_order.php',
                type: 'POST',
                data: {
                    columnsOrder: columnsOrder,
                    app: 'smartSearch'
                },
                success: function(data) {
                    console.log(data);
                }
            });

            defaultColumnsOrder = columnsOrder;
            return columnsOrder;
        }


        function selectAllNoRenderCheckboxes() {
            /* Selecionar todos os checkboxes que tenham a classe no-render */
            $('.no-render').each(function() {
                $(this).prop('checked', true);
            });
        }

        function unselectAllNoRenderCheckboxes() {
            /* Selecionar todos os checkboxes que tenham a classe no-render */
            $('.no-render').each(function() {
                $(this).prop('checked', false);
            });
        }


        function openEquipmentInfo(asset_tag, asset_unit) {
            let location = 'equipment_show.php?tag=' + asset_tag + '&unit=' + asset_unit;
            popup_alerta_wide(location);
        }

        function openAssetInfo(asset_id) {
            let location = 'asset_show.php?asset_id=' + asset_id;
            popup_alerta_wide(location);
        }

    </script>
</body>

</html>