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

// $suggestions = '[{}]';

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../../includes/css/estilos.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/css/my_datatables.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/css/switch_radio.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/jquery/datetimepicker/jquery.datetimepicker.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap/custom.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/fontawesome/css/all.min.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/datatables/datatables.min.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/jquery/jquery.amsify.suggestags-master/css/amsify.suggestags.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap-select/dist/css/bootstrap-select.min.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/css/my_bootstrap_select.css" />


    <style>
        li.area_admins {
            line-height: 1.5em;
        }

        td.col_check {
            max-width: 5%;
        }

        .help-tip {
            cursor: help;
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
        <h4 class="my-4"><i class="fas fa-pencil-ruler text-secondary"></i>&nbsp;<?= TRANS('CUSTOM_FIELDS'); ?></h4>
        <div class="modal" id="modal" tabindex="-1" style="z-index:9001!important">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title" id="getit"><i class="fas fa-edit"></i>&nbsp;<?= TRANS('RENAME_OR_DELETE_OPTION'); ?></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <?= TRANS('HELPER_RENAME_OR_DELETE'); ?>
                    </div>
                    <div class="row mx-2">
                        <div class="form-group col-md-12" id="divDetails">
                        </div>
                    </div>
                    <!-- Footer -->
                    <div class="modal-footer bg-light">
                        <button type="button" id="bt_rename" class="btn btn-primary" onclick="updateOptionValue()"><?= TRANS('RENAME'); ?></button>
                        <button type="button" id="bt_remove" class="btn btn-danger" onclick="deleteOptionValue()"><?= TRANS('REMOVE'); ?></button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= TRANS('BT_CANCEL'); ?></button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal do Helper sobre as máscaras para campos do tipo texto -->
        <div class="modal" id="modal-mask-helper" tabindex="-1" style="z-index:9001!important">
            <div class="modal-dialog modal-xl" id="mask-helper-content">
                <!-- O conteúdo será carregado via ajax load -->
            </div>
        </div>

        <?php
        if (isset($_SESSION['flash']) && !empty($_SESSION['flash'])) {
            echo $_SESSION['flash'];
            $_SESSION['flash'] = '';
        }

        $typeList = [
            'text' => TRANS('TYPE_TEXT'),
            'number' => TRANS('TYPE_NUMBER'),
            'select' => TRANS('TYPE_SELECT'),
            'select_multi' => TRANS('TYPE_SELECT_MULTI'),
            'date' => TRANS('TYPE_DATE'),
            'time' => TRANS('TYPE_TIME'),
            'datetime' => TRANS('TYPE_DATETIME'),
            'textarea' => TRANS('TYPE_TEXTAREA'),
            'checkbox' => TRANS('TYPE_CHECKBOX')
        ];

        array_multisort($typeList, SORT_LOCALE_STRING);

        /* Formulários que terão campos customizados */
        $tableList = [
            'ocorrencias' => TRANS('TICKETS'),
            'equipamentos' => TRANS('ASSETS'),
            'clients' => TRANS('CLIENTS'),
        ];

        $COD = (isset($_GET['cod']) && !empty($_GET['cod']) ? (int)$_GET['cod'] : '');

        if (empty($COD)) {
            $custom_fields = getCustomFields($conn, null, null, null, null);
        } else {
            $custom_fields = getCustomFields($conn, $_GET['cod']);

            /* Options to select */
            $optionsText = "";
            $field_options = [];
            $field_options = getCustomFieldOptionValues($conn, $COD);
            foreach ($field_options as $option) {
                if (strlen((string)$optionsText)) $optionsText .= ",";
                $optionsText .= $option['option_value'];
            }
        }

        $registros = count($custom_fields);

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
                            <td class="line field_label"><?= TRANS('LABEL'); ?></td>
                            <td class="line field_type"><?= TRANS('COL_TYPE'); ?></td>
                            <td class="line field_default_value"><?= TRANS('DEFAULT_VALUE'); ?></td>
                            <td class="line field_table"><?= TRANS('USED_IN'); ?></td>
                            <td class="line field_required" width="8%"><?= TRANS('REQUIRED'); ?></td>
                            <td class="line field_description"><?= TRANS('DESCRIPTION'); ?></td>
                            <td class="line field_active" width="8%"><?= TRANS('ACTIVE_O'); ?></td>
                            <td class="line field_order"><?= TRANS('SORTING_CHARS'); ?></td>
                            <td class="line editar"><?= TRANS('BT_EDIT'); ?></td>
                            <td class="line remover"><?= TRANS('BT_REMOVE'); ?></td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php

                        foreach ($custom_fields as $row) {

                            $field_active = ($row['field_active'] == 1 ? '<span class="text-success"><i class="fas fa-check"></i></span>' : '');

                            $field_required = ($row['field_required'] == 1 ? '<span class="text-success"><i class="fas fa-check"></i></span>' : '');

                            $checkbox_checked = ($row['field_type'] == "checkbox" && $row['field_default_value'] == "on" ? '<span class="text-success"><i class="fas fa-check"></i></span>' : $row['field_default_value']);

                            ?>
                            <tr>
                                <td class="line"><?= $row['field_label']; ?></td>
                                <td class="line"><?= $typeList[$row['field_type']]; ?></td>
                                <td class="line"><?= $checkbox_checked; ?></td>
                                <td class="line"><?= $tableList[$row['field_table_to']]; ?></td>
                                <td class="line"><?= $field_required; ?></td>
                                <td class="line"><?= $row['field_description']; ?></td>
                                <td class="line"><?= $field_active; ?></td>
                                <td class="line"><?= $row['field_order']; ?></td>

                                <td class="line"><button type="button" class="btn btn-secondary btn-sm" onclick="redirect('<?= $_SERVER['PHP_SELF']; ?>?action=edit&cod=<?= $row['id']; ?>')"><?= TRANS('BT_EDIT'); ?></button></td>
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

            echo message('info', '', TRANS('INFO_ADD_FIELD_TO_PROFILE'), '', '', 1);
            ?>

            <form method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form">
                <?= csrf_input(); ?>
                <div class="form-group row my-4">


                    <label for="field_label" class="col-sm-2 col-md-2 col-form-label text-md-right help-tip" title="<?= TRANS('LABEL'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_CFIELD_LABEL'); ?>"><?= TRANS('LABEL'); ?></label>
                    <div class="form-group col-md-4">
                        <input type="text" class="form-control" id="field_label" name="field_label" placeholder="<?= TRANS('LABEL'); ?>" />
                        <div class="invalid-feedback">
                            <?= TRANS('MANDATORY_FIELD'); ?>
                        </div>
                    </div>

                    <label for="field_type" class="col-md-2 col-form-label text-md-right help-tip" title="<?= TRANS('COL_TYPE'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_CFIELD_TYPE'); ?>"><?= TRANS('COL_TYPE'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control " id="field_type" name="field_type">
                            <option value=""><?= TRANS('SEL_SELECT'); ?></option>
                            <?php
                            foreach ($typeList as $type => $alias) {
                                print "<option value=" . $type . ">" . $alias . "</option>";
                            }
                            ?>
                        </select>
                    </div>


                    <!-- Lista de opções caso o tipo seja select ou select_multi -->
                    <label for="field_options" class="col-sm-2 col-md-2 col-form-label text-md-right help-tip" title="<?= TRANS('OPTION_VALUES'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_CFIELD_OPTIONS'); ?>"><?= TRANS('OPTION_VALUES'); ?></label>
                    <div class="form-group col-md-4">
                        <input type="text" class="form-control" id="field_options" name="field_options" placeholder="<?= TRANS('ADD_OR_REMOVE'); ?>" disabled />
                        <div class="invalid-feedback">
                            <?= TRANS('MANDATORY_FIELD'); ?>
                        </div>
                    </div>

                    <!-- Valor padrão -->
                    <input type="hidden" name="default_value" id="default_value" value="">
                    <label for="field_default_value" class="col-sm-2 col-md-2 col-form-label text-md-right help-tip" title="<?= TRANS('DEFAULT_VALUE'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_CFIELD_DEFAULT_VALUE'); ?>"><?= TRANS('DEFAULT_VALUE'); ?></label>
                    <div class="form-group col-md-4 field_default_value">
                        <input type="text" class="form-control" id="field_default_value" name="field_default_value" />
                    </div>


                    <label for="field_table_to" class="col-md-2 col-form-label text-md-right help-tip" title="<?= TRANS('USED_IN'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_CFIELD_TABLE_TO'); ?>"><?= TRANS('USED_IN'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control " id="field_table_to" name="field_table_to">
                            <!-- <option value=""><?= TRANS('SEL_SELECT'); ?></option> -->
                            <?php
                            foreach ($tableList as $table => $alias) {
                                print "<option value=" . $table . ">" . $alias . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <label for="field_title" class="col-sm-2 col-md-2 col-form-label text-md-right help-tip" title="<?= TRANS('TITLE'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_CFIELD_TITLE'); ?>"><?= TRANS('TITLE'); ?></label>
                    <div class="form-group col-md-4">
                        <input type="text" class="form-control" id="field_title" name="field_title" />
                    </div>

                    <label for="field_placeholder" class="col-sm-2 col-md-2 col-form-label text-md-right help-tip" title="<?= TRANS('PLACEHOLDER'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_CFIELD_PLACEHOLDER'); ?>"><?= TRANS('PLACEHOLDER'); ?></label>
                    <div class="form-group col-md-4">
                        <input type="text" class="form-control" id="field_placeholder" name="field_placeholder" />
                    </div>

                    <label for="field_order" class="col-sm-2 col-md-2 col-form-label text-md-right help-tip" title="<?= TRANS('SORTING_CHARS'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_SORTING_CHARS'); ?>"><?= TRANS('SORTING_CHARS'); ?></label>
                    <div class="form-group col-md-4">
                        <input type="text" class="form-control" id="field_order" name="field_order" />
                    </div>

                    <label for="field_description" class="col-sm-2 col-md-2 col-form-label text-md-right help-tip" title="<?= TRANS('DESCRIPTION'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_CFIELD_DESCRIPTION'); ?>" ><?= TRANS('DESCRIPTION'); ?></label>
                    <div class="form-group col-md-4">
                        <textarea class="form-control" id="field_description" name="field_description"></textarea>
                    </div>

                    <label for="field_attributes" class="col-sm-2 col-md-2 col-form-label text-md-right help-tip" title="<?= TRANS('HTML_ATTRIBUTES'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_HTML_ATTRIBUTES'); ?>" ><?= TRANS('HTML_ATTRIBUTES'); ?></label>
                    <div class="form-group col-md-4">
                        <textarea class="form-control" id="field_attributes" name="field_attributes" placeholder="<?= TRANS('PLACEHOLDER_HTML_ATTRIBUTES'); ?>"></textarea>
                    </div>


                    <label for="field_mask" class="col-sm-2 col-md-2 col-form-label text-md-right" title="<?= TRANS('CFIELD_MASK'); ?>"><i class="fas fa-info-circle text-secondary modal-mask-helper"></i>&nbsp;<?= TRANS('CFIELD_MASK'); ?></label>
                    <div class="form-group col-md-4">
                        <textarea class="form-control" id="field_mask" name="field_mask" placeholder="<?= TRANS('CFIELD_MASK'); ?>"></textarea>
                    </div>

                    <label class="col-md-2 col-form-label text-md-right help-tip" title="<?= TRANS('MASK_REGEX'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_MASK_REGEX'); ?>"><?= firstLetterUp(TRANS('MASK_REGEX')); ?></label>
                    <div class="form-group col-md-4 ">
                        <div class="switch-field">
                            <?php
                            $yesChecked = "";
                            $noChecked = "checked";
                            ?>
                            <input type="radio" id="field_mask_regex" name="field_mask_regex" value="yes" <?= $yesChecked; ?> />
                            <label for="field_mask_regex"><?= TRANS('YES'); ?></label>
                            <input type="radio" id="field_mask_regex_no" name="field_mask_regex" value="no" <?= $noChecked; ?> />
                            <label for="field_mask_regex_no"><?= TRANS('NOT'); ?></label>
                        </div>
                    </div>
                    
                    
                    


                    <label class="col-md-2 col-form-label text-md-right help-tip" title="<?= TRANS('REQUIRED'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_CFIELD_REQUIRED'); ?>"><?= firstLetterUp(TRANS('REQUIRED')); ?></label>
                    <div class="form-group col-md-4 ">
                        <div class="switch-field">
                            <?php
                            $yesChecked = "checked";
                            $noChecked = "";
                            ?>
                            <input type="radio" id="field_required" name="field_required" value="yes" <?= $yesChecked; ?> />
                            <label for="field_required"><?= TRANS('YES'); ?></label>
                            <input type="radio" id="field_required_no" name="field_required" value="no" <?= $noChecked; ?> />
                            <label for="field_required_no"><?= TRANS('NOT'); ?></label>
                        </div>
                    </div>


                </div>
                <!-- <div class="form-group row my-4 " id="div_send_receive_areas"></div> -->
                <div class="form-group row my-4 ">


                    <div class="row w-100"></div>
                    <div class="form-group col-md-8 d-none d-md-block">
                    </div>
                    <div class="form-group col-12 col-md-2 ">

                        <input type="hidden" name="options_before" id="options_before" value="">
                        <input type="hidden" name="action" id="action" value="new">
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

        ?>
            <h6><?= TRANS('BT_EDIT'); ?></h6>
            <form method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form">
                <?= csrf_input(); ?>

                <div class="form-group row my-4">


                    <label for="field_label" class="col-sm-2 col-md-2 col-form-label text-md-right help-tip" title="<?= TRANS('LABEL'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_CFIELD_LABEL'); ?>"><?= TRANS('LABEL'); ?></label>
                    <div class="form-group col-md-4">
                        <input type="text" class="form-control" id="field_label" name="field_label" placeholder="<?= TRANS('PLACEHOLDER_AREA_NAME'); ?>" value="<?= $custom_fields['field_label'] ?? ''; ?>" />
                        <div class="invalid-feedback">
                            <?= TRANS('MANDATORY_FIELD'); ?>
                        </div>
                    </div>

                    <input type="hidden" name="field_type" id="field_type" value="<?= $custom_fields['field_type']?>"> 
                    <label for="field_type_readonly" class="col-md-2 col-form-label text-md-right help-tip" title="<?= TRANS('COL_TYPE'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_CFIELD_TYPE'); ?>"><?= TRANS('COL_TYPE'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control " id="field_type_readonly" name="field_type_readonly" disabled>
                            <option value=""><?= TRANS('SEL_SELECT'); ?></option>
                            <?php
                            foreach ($typeList as $type => $alias) {
                            ?>
                                <option value="<?= $type; ?>" <?= ($type == $custom_fields['field_type'] ? ' selected' : ''); ?>><?= $alias; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>


                    <?php
                        if ($custom_fields['field_type'] == 'select' || $custom_fields['field_type'] == 'select_multi') {
                        ?>
                            <!-- Listagem de opções já definidas -->
                            <div class="w-100"></div>
                            <label for="read_field_options" class="col-md-2 col-form-label text-md-right help-tip" title="<?= TRANS('CURRENT_OPTION_VALUES'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_CFIELD_CURRENT_OPTIONS'); ?>"><?= TRANS('CURRENT_OPTION_VALUES'); ?></label>
                            
                            <div class="form-group col-md-10" id="listOptions">
                                <?= strToTags($optionsText, 0, 'info', 'input-tag-link', 'fas fa-edit'); ?>
                            </div>

                            <!-- Possibilidade de inserção de novas opções -->
                            <label for="field_options" class="col-sm-2 col-md-2 col-form-label text-md-right help-tip" title="<?= TRANS('ADD_OPTION_VALUES'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_CFIELD_OPTIONS'); ?>"><?= TRANS('ADD_OPTION_VALUES'); ?></label>
                            
                            <div class="form-group col-md-10">
                                <input type="text" class="form-control" id="field_options" name="field_options" placeholder="<?= TRANS('ADD'); ?>"  />
                                <div class="invalid-feedback">
                                    <?= TRANS('MANDATORY_FIELD'); ?>
                                </div>
                            </div>
                        <?php
                        }
                    ?>
                    

                    
                    <input type="hidden" name="default_value" id="default_value" value="<?= $custom_fields['field_default_value']; ?>">
                    <label for="field_default_value" class="col-sm-2 col-md-2 col-form-label text-md-right help-tip" title="<?= TRANS('DEFAULT_VALUE'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_CFIELD_DEFAULT_VALUE'); ?>"><?= TRANS('DEFAULT_VALUE'); ?></label>
                    <div class="form-group col-md-4 field_default_value" id="div_field_default_value">
                    <?php
                    if ($custom_fields['field_type'] == 'select') {
                        $field_options = getCustomFieldOptionValues($conn, $custom_fields['id']);
                        ?>
                        <select class="form-control" id="field_default_value" name="field_default_value">
                            <option value=""></option>
                        <?php
                        foreach ($field_options as $option) {
                            ?>
                            <option value="<?= $option['option_value']; ?>"
                                <?= ($option['option_value'] == $custom_fields['field_default_value'] ? ' selected': ''); ?>
                            ><?= $option['option_value']; ?></option>
                            <?php
                        }
                        ?>
                        </select>
                        <?php
                    } elseif ($custom_fields['field_type'] == 'select_multi') {
                        $field_options = getCustomFieldOptionValues($conn, $custom_fields['id']);
                        ?>
                        <select class="form-control sel2" id="field_default_value" name="field_default_value[]" multiple="multiple">
                        <?php
                        $defaultSelections = explode(',', $custom_fields['field_default_value']);
                        foreach ($field_options as $option) {
                            ?>
                            <option value="<?= $option['option_value']; ?>"
                                <?= (in_array($option['option_value'], $defaultSelections) ? ' selected': ''); ?>
                            ><?= $option['option_value']; ?></option>
                            <?php
                        }
                        ?>
                        </select>
                        <?php
                    } elseif ($custom_fields['field_type'] == 'number') {
                        ?>
                        <input type="number" class="form-control" id="field_default_value" name="field_default_value" value="<?= $custom_fields['field_default_value']; ?>"/>
                        <?php
                    }  elseif ($custom_fields['field_type'] == 'checkbox') {
                        $checked = ($custom_fields['field_default_value'] ? " checked" : "");
                        ?>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input " type="checkbox" id="field_default_value" name="field_default_value" <?= $checked; ?>>
                            <legend class="col-form-label col-form-label-sm">&nbsp;</legend>
                        </div>

                        <?php
                    } else {
                        ?>
                        <input type="text" class="form-control" id="field_default_value" name="field_default_value" value="<?= $custom_fields['field_default_value']; ?>"/>
                        <?php
                    }
                    ?>
                    </div>
                    
                    


                    <label for="field_table_to" class="col-md-2 col-form-label text-md-right help-tip" title="<?= TRANS('USED_IN'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_CFIELD_TABLE_TO'); ?>"><?= TRANS('USED_IN'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control " id="field_table_to" name="field_table_to">
                            <option value=""><?= TRANS('SEL_SELECT'); ?></option>
                            <?php
                            foreach ($tableList as $table => $alias) {
                                ?>
                                <option value="<?= $table; ?>" <?= ($table == $custom_fields['field_table_to'] ? ' selected' : ''); ?>><?= $alias; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>

                    <label for="field_title" class="col-sm-2 col-md-2 col-form-label text-md-right help-tip" title="<?= TRANS('TITLE'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_CFIELD_TITLE'); ?>"><?= TRANS('TITLE'); ?></label>
                    <div class="form-group col-md-4">
                        <input type="text" class="form-control" id="field_title" name="field_title" value="<?= $custom_fields['field_title']; ?>"/>
                    </div>

                    <label for="field_placeholder" class="col-sm-2 col-md-2 col-form-label text-md-right help-tip" title="<?= TRANS('PLACEHOLDER'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_CFIELD_PLACEHOLDER'); ?>"><?= TRANS('PLACEHOLDER'); ?></label>
                    <div class="form-group col-md-4">
                        <input type="text" class="form-control" id="field_placeholder" name="field_placeholder" value="<?= $custom_fields['field_placeholder']; ?>"/>
                    </div>

                   
                    <label for="field_description" class="col-sm-2 col-md-2 col-form-label text-md-right help-tip" title="<?= TRANS('DESCRIPTION'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_HTML_ATTRIBUTES'); ?>" ><?= TRANS('DESCRIPTION'); ?></label>
                    <div class="form-group col-md-4">
                        <textarea class="form-control" id="field_description" name="field_description"><?= $custom_fields['field_description']; ?></textarea>
                    </div>

                    <label for="field_attributes" class="col-sm-2 col-md-2 col-form-label text-md-right help-tip" title="<?= TRANS('HTML_ATTRIBUTES'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_HTML_ATTRIBUTES'); ?>" ><?= TRANS('HTML_ATTRIBUTES'); ?></label>
                    <div class="form-group col-md-4">
                        <textarea class="form-control" id="field_attributes" name="field_attributes" placeholder="<?= TRANS('PLACEHOLDER_HTML_ATTRIBUTES'); ?>"><?= $custom_fields['field_attributes']; ?></textarea>
                    </div>

                    <label for="field_mask" class="col-sm-2 col-md-2 col-form-label text-md-right" title="<?= TRANS('CFIELD_MASK'); ?>"><i class="fas fa-info-circle text-secondary modal-mask-helper"></i>&nbsp;<?= TRANS('CFIELD_MASK'); ?></label>
                    <div class="form-group col-md-4">
                        <textarea class="form-control" id="field_mask" name="field_mask" placeholder="<?= TRANS('CFIELD_MASK'); ?>"><?= $custom_fields['field_mask']; ?></textarea>
                    </div>

                    <label class="col-md-2 col-form-label text-md-right help-tip" title="<?= TRANS('MASK_REGEX'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_MASK_REGEX'); ?>"><?= firstLetterUp(TRANS('MASK_REGEX')); ?></label>
                    <div class="form-group col-md-4 ">
                        <div class="switch-field">
                            <?php
                            $yesChecked = ($custom_fields['field_mask_regex'] == 1 ? "checked" : "");
							$noChecked = (!($custom_fields['field_mask_regex'] == 1) ? "checked" : "");
                            ?>
                            <input type="radio" id="field_mask_regex" name="field_mask_regex" value="yes" <?= $yesChecked; ?> />
                            <label for="field_mask_regex"><?= TRANS('YES'); ?></label>
                            <input type="radio" id="field_mask_regex_no" name="field_mask_regex" value="no" <?= $noChecked; ?> />
                            <label for="field_mask_regex_no"><?= TRANS('NOT'); ?></label>
                        </div>
                    </div>

                    

                    <label for="field_order" class="col-sm-2 col-md-2 col-form-label text-md-right" title="<?= TRANS('SORTING_CHARS'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_SORTING_CHARS'); ?>"><?= TRANS('SORTING_CHARS'); ?></label>
                    <div class="form-group col-md-4">
                        <input type="text" class="form-control" id="field_order" name="field_order" value="<?= $custom_fields['field_order']; ?>"/>
                    </div>


                    <label class="col-md-2 col-form-label text-md-right help-tip" title="<?= TRANS('REQUIRED'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_CFIELD_REQUIRED'); ?>"><?= firstLetterUp(TRANS('REQUIRED')); ?></label>
                    <div class="form-group col-md-4 ">
                        <div class="switch-field">
                            <?php
                            $yesChecked = ($custom_fields['field_required'] == 1 ? "checked" : "");
							$noChecked = (!($custom_fields['field_required'] == 1) ? "checked" : "");
                            ?>
                            <input type="radio" id="field_required" name="field_required" value="yes" <?= $yesChecked; ?> />
                            <label for="field_required"><?= TRANS('YES'); ?></label>
                            <input type="radio" id="field_required_no" name="field_required" value="no" <?= $noChecked; ?> />
                            <label for="field_required_no"><?= TRANS('NOT'); ?></label>
                        </div>
                    </div>

                    <label class="col-md-2 col-form-label text-md-right help-tip" title="<?= TRANS('ACTIVE_O'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_CFIELD_ACTIVE'); ?>"><?= firstLetterUp(TRANS('ACTIVE_O')); ?></label>
                    <div class="form-group col-md-4 ">
                        <div class="switch-field">
                            <?php
                            $yesChecked = ($custom_fields['field_active'] == 1 ? "checked" : "");
							$noChecked = (!($custom_fields['field_active'] == 1) ? "checked" : "");
                            ?>
                            <input type="radio" id="field_active" name="field_active" value="yes" <?= $yesChecked; ?> />
                            <label for="field_active"><?= TRANS('YES'); ?></label>
                            <input type="radio" id="field_active_no" name="field_active" value="no" <?= $noChecked; ?> />
                            <label for="field_active_no"><?= TRANS('NOT'); ?></label>
                        </div>
                    </div>

                </div>
                <!-- <div class="form-group row my-4 " id="div_send_receive_areas"></div> -->

                <div class="form-group row my-4 ">


                    <input type="hidden" name="options_before" id="options_before" value="<?= $optionsText; ?>">
                    <input type="hidden" name="cod" id="cod" value="<?= $COD; ?>">
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
    <script src="../../includes/components/jquery/datetimepicker/build/jquery.datetimepicker.full.min.js"></script>
    <script src="../../includes/components/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script type="text/javascript" charset="utf8" src="../../includes/components/datatables/datatables.js"></script>
    <script src="../../includes/components/jquery/jquery.amsify.suggestags-master/js/jquery.amsify.suggestags.js"></script>
    <script src="../../includes/components/bootstrap-select/dist/js/bootstrap-select.min.js"></script>
    <script src="../../includes/components/Inputmask-5.x/dist/jquery.inputmask.min.js"></script>


    <script type="text/javascript">
        $(function() {

            $('#table_lists').DataTable({
                paging: true,
                deferRender: true,
                // order: [0, 'DESC'],
                columnDefs: [{
                    searchable: false,
                    orderable: false,
                    targets: ['editar', 'remover', 'field_placeholder', 'field_title', 'field_description']
                }],
                "language": {
                    "url": "../../includes/components/datatables/datatables.pt-br.json"
                }
            });


            if ($('#action').val() == 'edit') {
                $(".input-tag-link").on("click", function(){
                    openModalOption($(this).attr("data-tag-name-raw"));
                }).addClass("pointer");
            }

            $.fn.selectpicker.Constructor.BootstrapVersion = '4';
            $('.sel2').selectpicker({
                /* placeholder */
                title: "<?= TRANS('SMART_EMPTY', '', 1); ?>",
                liveSearch: true,
                liveSearchNormalize: true,
                liveSearchPlaceholder: "<?= TRANS('BT_SEARCH', '', 1); ?>",
                noneResultsText: "<?= TRANS('NO_RECORDS_FOUND', '', 1); ?> {0}",
                style: "",
                styleBase: "form-control input-select-multi",
            });


            applyMask($('#field_mask').val());
            $('#field_mask, [name="field_mask_regex"]').on('change', function(){
                applyMask ($('#field_mask').val());
            });


            $('.modal-mask-helper').on('click', function(){
                $('#mask-helper-content').empty().load('../helpers/masks_helper.php');
                $('#modal-mask-helper').modal();
            }).css({cursor:'pointer'});

            if ($('#field_label').length > 0) {
                $('#field_label').on('change', function(){
                    if ($('#field_title').val() == '') {
                        $('#field_title').val($('#field_label').val());
                    }
                    if ($('#field_placeholder').val() == '') {
                        $('#field_placeholder').val($('#field_label').val());
                    }
                    if ($('#field_description').val() == '') {
                        $('#field_description').val($('#field_label').val());
                    }
                });
            }


            $(function() {
                $('[data-toggle="popover"]').popover({
                    html: true
                });
            });

            $('.popover-dismiss').popover({
                trigger: 'focus'
            });

           
            $('input[name="field_options"]').on('suggestags.change', function(){
                
                var options_before = ($('#options_before').val() != '' ? $('#options_before').val() + ',' : '');
                var options_full = options_before + $('#field_options').val();
                
                var options = options_full.split(',');
                var option = '<option value=""></option>';
                var default_value = $('#default_value').val() ?? '';

                options.forEach(function(val){
                    let selected = '';
                    if (val == default_value) {
                        selected = ' selected';
                    } else {
                        selected = '';
                    }
                    option += '<option value="' + val + '"'+ selected +'>' + val + '</option>';
                });
                
                if ($('#field_type').val() == 'select') {
                    var select = '<select class="form-control" id="field_default_value" name="field_default_value">' + option + '</select>';
                } else 
                if ($('#field_type').val() == 'select_multi') {
                    var select = '<select class="form-control sel2" id="field_default_value" name="field_default_value[]" multiple="multiple">' + option + '</select>';
                }
                
        
                $('.field_default_value').empty().append(select);

                if ($('#field_type').val() == 'select_multi') {
                    $('.sel2').selectpicker({
                        /* placeholder */
                        title: "<?= TRANS('SMART_EMPTY', '', 1); ?>",
                        style: "",
                        styleBase: "form-control input-select-multi",
                    });
                }
            });



            if ($('#field_options').length > 0) {

                if ($('#field_type').val() != 'select' && $('#field_type').val() != 'select_multi') {
                    $('#field_options').prop('disabled', true);
                    $('input[name="field_options"]').amsifySuggestags({}, 'destroy');
                } else {
                    $('input[name="field_options"]').amsifySuggestags({
                        type: 'bootstrap',
                        defaultTagClass: 'badge bg-secondary text-white p-2 m-1',
                        printValues: false,
                    });
                }
            }

            bindFieldsByType();
            if ($('#field_type').length > 0) {
                toggleMaskField($('#field_type').val());
                $('#field_type').on('change', function() {
                    toggleMaskField($('#field_type').val());
                    fieldOptionsControl();
                    bindFieldsByType();
                });
            }

            /* Idioma global para os calendários */
            $.datetimepicker.setLocale('pt-BR');


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
                    url: './custom_fields_process.php',
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
                url: './custom_fields_process.php',
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

        function fieldOptionsControl() {
            if ($('#field_type').val() != 'select' && $('#field_type').val() != 'select_multi') {
                $('input[name="field_options"]').amsifySuggestags({}, 'destroy');
                $('#field_options').val('').prop('disabled', true);

                var field = '<input type="text" class="form-control" id="field_default_value" name="field_default_value" />';
                $('.field_default_value').empty().append(field);
            } else {

                $('#field_options').prop('disabled', false);
                $('input[name="field_options"]').amsifySuggestags({
                    type: 'bootstrap',
                    defaultTagClass: 'badge bg-secondary text-white p-2 m-1',
                    // tagLimit: 20,
                    printValues: false,
                    // showPlusAfter: 10,
                });
            }
        }

        function bindFieldsByType() {
            if ($('#field_type').val() == 'number') {
                $('#field_default_value').attr('type', 'number');
                $('#field_required').prop('disabled', false);
                $('#field_required_no').prop('disabled', false);
            } else
            if ($('#field_type').val() == 'date') {
                $('#field_default_value').attr('type', 'text');
                $('#field_required').prop('disabled', false);
                $('#field_required_no').prop('disabled', false);
                $('#field_default_value').datetimepicker('destroy');
                $('#field_default_value').datetimepicker({
                    timepicker: false,
                    format: 'd/m/Y',
                });
            } else
            if ($('#field_type').val() == 'datetime') {
                $('#field_default_value').attr('type', 'text');
                $('#field_required').prop('disabled', false);
                $('#field_required_no').prop('disabled', false);
                $('#field_default_value').datetimepicker('destroy');
                $('#field_default_value').datetimepicker({
                    timepicker: true,
                    step: 30,
                    format: 'd/m/Y H:i',
                });
            } else
            if ($('#field_type').val() == 'time') {
                $('#field_default_value').attr('type', 'text');
                $('#field_required').prop('disabled', false);
                $('#field_required_no').prop('disabled', false);
                $('#field_default_value').datetimepicker('destroy');
                $('#field_default_value').datetimepicker({
                    datepicker: false,
                    step: 30,
                    format: 'H:i',
                });
            } else
            if ($('#field_type').val() == 'checkbox') {
                $('#field_default_value')
                    .attr('type', 'checkbox')
                    .removeClass("form-control")
                    .addClass("form-check-input custom_field_checkbox")
                    .wrap('<div class="form-check form-check-inline"></div>');

                $('#field_required').attr('checked', false).prop('disabled', true);
                $('#field_required_no').attr('checked', true).prop('disabled', true);

            } else {
                $('#field_default_value').attr('type', 'text');
                $('#field_required').prop('disabled', false);
                $('#field_required_no').prop('disabled', false);
            }
        }

        function toggleMaskField (fieldType) {
            if (fieldType != 'text') {
                $('#field_mask').prop('disabled', true).val('');
                $('#field_mask_regex').prop('checked', false).prop('disabled', true);
				$('#field_mask_regex_no').prop('checked', true).prop('disabled', true);
            } else {
                $('#field_mask').prop('disabled', false);
                $('#field_mask_regex').prop('disabled', false);
				$('#field_mask_regex_no').prop('disabled', false);
            }
        }

        function openModalOption(id) {

            let form = '<input class="form-control" type="text" name="option_value" id="option_value" value="' + id + '">';
            form += '<input type="hidden" name="option_value_before" id="option_value_before" value="' + id + '">';
            
            $('#divDetails').empty().html(form);

            $('#modal').modal();
        }

        function updateOptionValue() {

            var loading = $(".loading");
            $(document).ajaxStart(function() {
                loading.show();
            });
            $(document).ajaxStop(function() {
                loading.hide();
            });

            $.ajax({
                url: 'custom_field_option_value_process.php',
                method: 'POST',
                dataType: 'json',
                data: {
                    'option_value_before':$('#option_value_before').val(),
                    'option_value': $('#option_value').val(),
                    'custom_field_id': $('#cod').val(),
                    'action': 'edit'
                },
            }).done(function(response) {
                if (!response.success) {
                    $('#modal').modal('hide');
                    $('#divResult').html(response.message);
                } else {
                    $('#modal').modal('hide');
                    location.reload();
                    // $('#listOptions').load(document.URL +  ' #listOptions');
                }
            });
            return false;
        }

        function deleteOptionValue() {

            var loading = $(".loading");
            $(document).ajaxStart(function() {
                loading.show();
            });
            $(document).ajaxStop(function() {
                loading.hide();
            });

            $.ajax({
                url: 'custom_field_option_value_process.php',
                method: 'POST',
                dataType: 'json',
                data: {
                    'option_value_before':$('#option_value_before').val(),
                    'option_value': $('#option_value').val(),
                    'custom_field_id': $('#cod').val(),
                    'action': 'delete'
                },
            }).done(function(response) {
                if (!response.success) {
                    $('#modal').modal('hide');
                    $('#divResult').html(response.message);
                } else {
                    $('#modal').modal('hide');
                    location.reload();
                    // $('#listOptions').load(document.URL +  ' #listOptions');
                }
            });
            return false;
        }


        function isRegex() {
            if ($('#field_mask_regex').is(':checked')) {
                return 1;
            }
            return 0;
        }

        function applyMask (mask) {
            if (mask != '') {
                if (isRegex()) {
                    $('#field_default_value').inputmask('remove');
                    $('#field_default_value').inputmask({regex: mask});
                } else {
                    $('#field_default_value').inputmask('remove');
                    $('#field_default_value').inputmask(mask);
                }
            } else {
                $('#field_default_value').inputmask({ "placeholder": " " });
                $('#field_default_value').inputmask('remove');
            }
        }
    </script>
</body>

</html>