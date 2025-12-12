<?php session_start();
/*      Copyright 2023 Flávio Ribeiro

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

require_once __DIR__ . "/" . "../../includes/include_basics_only.php";
require_once __DIR__ . "/" . "../../includes/classes/ConnectPDO.php";

use includes\classes\ConnectPDO;

$conn = ConnectPDO::getInstance();

$table = (isset($_POST['table']) ? noHtml($_POST['table']) : "");

if (empty($table)) {
    echo "Table not defined";
    return;
}

$labelColSize = 2;
$fieldColSize = 4;

/* Tipos de campos que entram no filtro avançado */
// $types = ["select", "select_multi", "checkbox", "date", "datetime"];
$types = ["date", "datetime", "select", "select_multi", "number", "text", "checkbox"];
$custom_fields = getCustomFields($conn, null, $table, $types);

if (count($custom_fields)) {
?>
<!-- <div class="w-100"></div> -->

<div class="accordion" id="accordionCustomFields">
    <div class="card">
        <div class="card-header bg-oc-teal" id="cardCustomFields">
            <h2 class="mb-0">
                <button class="btn btn-block text-left text-white" type="button" data-toggle="collapse" data-target="#customFields" aria-expanded="false" aria-controls="customFields" onclick="this.blur();">
                    <h6 class="font-weight-bold"><i class="fas fa-align-right text-white"></i>&nbsp;<?= firstLetterUp(TRANS('CUSTOM_FIELDS')); ?></h6>
                </button>
            </h2>
        </div>

        <div id="customFields" class="collapse " aria-labelledby="cardCustomFields" data-parent="#accordionCustomFields">
            <!-- <div class="card-body"> -->
            <div class="form-group row my-4">


                <!-- Checkbox para marcar ou desmarcar todos os checkboxes de renderização -->
                <div class=" form-group col-md-2">
                </div>
                <div class=" form-group col-md-10">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input custom_field_checkbox" type="checkbox" name="toggle_check_norender_checkboxes" id="toggle_check_norender_checkboxes" >
                        <legend class="col-form-label col-form-label-sm"><?= TRANS('TOGGLE_NORENDER_CHECKBOXES'); ?></legend>
                    </div>
                    <hr />
                </div>
                <div class="w-100"></div>



            <?php
            foreach ($custom_fields as $row) {

                $inlineAttributes = keyPairsToHtmlAttrs($row['field_attributes']);
                $maskType = ($row['field_mask_regex'] ? 'regex' : 'mask');
                $fieldMask = "data-inputmask-" . $maskType . "=\"" . $row['field_mask'] . "\"";

                /* Para os tipos com data serão renderizados dois inputs para cada para definição de período*/
                if ($row['field_type'] != 'date' && $row['field_type'] != 'datetime' && $row['field_type'] != 'number') {
                ?>
                    <label for="<?= $row['field_name']; ?>" class="col-sm-<?= $labelColSize; ?> col-md-<?= $labelColSize; ?> col-form-label col-form-label-sm text-md-right " title="<?= $row['field_title']; ?>" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= $row['field_description']; ?>"><?= $row['field_label']; ?></label>
                    <div class="form-group col-md-<?= $fieldColSize; ?>">
                <?php
                }

            
                if ($row['field_type'] == 'select') {
                ?>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-edit"></i>&nbsp;
                                <input type="checkbox" class="first-check" name="no_empty_<?= $row['field_name']; ?>" id="no_empty_<?= $row['field_name']; ?>" value="1">
                            </div>
                        </div>

                        <select class="form-control custom_field_select" name="<?= $row['field_name']; ?>[]" id="<?= $row['field_name']; ?>" multiple="multiple" <?= $inlineAttributes; ?>>
                            <?php

                            $options = [];
                            $options = getCustomFieldOptionValues($conn, $row['id']);
                            foreach ($options as $rowValues) {
                            ?>
                                <option value="<?= $rowValues['id']; ?>"><?= $rowValues['option_value']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                        
                        <div class="input-group-append double-append">
                            <div class="input-group-text" title="<?= TRANS('SMART_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-times"></i>&nbsp;
                                <input type="checkbox" class="last-check" name="no_<?= $row['field_name']; ?>" id="no_<?= $row['field_name']; ?>" value="1">
                            </div>
                        </div>
                        <div class="input-group-append double-append">
                            <div class="input-group-text" title="<?= TRANS('DONT_RENDER'); ?>" data-placeholder="<?= TRANS('DONT_RENDER'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-eye-slash"></i>&nbsp;
                                <input type="checkbox" class="no-render" name="norender_<?= $row['field_name']; ?>" id="norender_<?= $row['field_name']; ?>" value="1">
                            </div>
                        </div>
                    </div>
                <?php
                } elseif ($row['field_type'] == 'select_multi') {
                ?>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-edit"></i>&nbsp;
                                <input type="checkbox" class="first-check" name="no_empty_<?= $row['field_name']; ?>" id="no_empty_<?= $row['field_name']; ?>" value="1">
                            </div>
                        </div>
                    
                        <select class="form-control custom_field_select_multi" name="<?= $row['field_name']; ?>[]" id="<?= $row['field_name']; ?>" multiple="multiple" <?= $inlineAttributes; ?>>
                            <?php
                            $options = [];
                            $options = getCustomFieldOptionValues($conn, $row['id']);
                            ?>
                            <?php
                            foreach ($options as $rowValues) {
                            ?>
                                <option value="<?= $rowValues['id']; ?>"><?= $rowValues['option_value']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                        
                        <div class="input-group-append double-append">
                            <div class="input-group-text" title="<?= TRANS('SMART_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-times"></i>&nbsp;
                                <input type="checkbox" class="last-check" name="no_<?= $row['field_name']; ?>" id="no_<?= $row['field_name']; ?>" value="1">
                            </div>
                        </div>
                        <div class="input-group-append double-append">
                            <div class="input-group-text" title="<?= TRANS('DONT_RENDER'); ?>" data-placeholder="<?= TRANS('DONT_RENDER'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-eye-slash"></i>&nbsp;
                                <input type="checkbox" class="no-render" name="norender_<?= $row['field_name']; ?>" id="norender_<?= $row['field_name']; ?>" value="1">
                            </div>
                        </div>
                    </div>
                <?php
                } elseif ($row['field_type'] == 'checkbox') {
                ?>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input custom_field_checkbox" type="checkbox" name="<?= $row['field_name']; ?>" id="<?= $row['field_name']; ?>" <?= $inlineAttributes; ?>>
                        <legend class="col-form-label col-form-label-sm"><?= $row['field_placeholder']; ?></legend>
                    </div>
                <?php
                } elseif ($row['field_type'] == 'textarea') {
                ?>
                    <textarea class="form-control custom_field_textarea" name="<?= $row['field_name']; ?>" id="<?= $row['field_name']; ?>" placeholder="<?= $row['field_placeholder']; ?>" <?= $inlineAttributes; ?>></textarea>
                <?php
                } elseif ($row['field_type'] == 'number') {
                ?>
                    <div class="w-100"></div>

                    <label for="minNum_<?= $row['field_name']; ?>" class="col-sm-<?= $labelColSize; ?> col-md-<?= $labelColSize; ?> col-form-label col-form-label-sm text-md-right " title="<?= $row['field_title']; ?>" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= $row['field_description']; ?>"><?= $row['field_label']; ?>&nbsp;<small>(<?= TRANS('MIN_VALUE'); ?>)</small></label>
                    <div class=" form-group col-md-<?= $fieldColSize; ?>">
                    
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <div class="input-group-text" title="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                    <i class="fas fa-edit"></i>&nbsp;
                                    <input type="checkbox" class="first-check-number" name="no_empty_<?= $row['field_name']; ?>" id="no_empty_<?= $row['field_name']; ?>" value="1" >
                                </div>
                            </div>

                            <input class="form-control custom_field_number custom_field_number_min" type="number" name="minNum_<?= $row['field_name']; ?>" id="minNum_<?= $row['field_name']; ?>" value="" placeholder="<?= TRANS('OCO_SEL_ANY'); ?>" autocomplete="off">

                            <div class="input-group-append double-append">
                                <div class="input-group-text" title="<?= TRANS('SMART_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                    <i class="fas fa-times"></i>&nbsp;
                                    <input type="checkbox" class="last-check-number" name="no_<?= $row['field_name']; ?>" id="no_<?= $row['field_name']; ?>" value="1" >
                                </div>
                            </div>
                            <div class="input-group-append double-append">
                                <div class="input-group-text" title="<?= TRANS('DONT_RENDER'); ?>" data-placeholder="<?= TRANS('DONT_RENDER'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                    <i class="fas fa-eye-slash"></i>&nbsp;
                                    <input type="checkbox" class="no-render" name="norender_<?= $row['field_name']; ?>" id="norender_<?= $row['field_name']; ?>" value="1">
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- Segundo campo para numero limite máximo -->
                    <label for="maxNum_<?= $row['field_name']; ?>" class="col-sm-<?= $labelColSize; ?> col-md-<?= $labelColSize; ?> col-form-label col-form-label-sm text-md-right " title="<?= $row['field_title']; ?>" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= $row['field_description']; ?>"><?= $row['field_label']; ?>&nbsp;<small>(<?= TRANS('MAX_VALUE'); ?>)</small></label>
                    <div class="form-group col-md-<?= $fieldColSize; ?>">

                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('MAX_VALUE'); ?>" data-placeholder="<?= TRANS('MAX_VALUE'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <!-- <i class="fas fa-sort-numeric-up-alt"></i>&nbsp; -->
                                <i class="fas fa-less-than-equal"></i>&nbsp;
                            </div>
                        </div>

                        <input class="form-control custom_field_number custom_field_number_max" type="number" name="maxNum_<?= $row['field_name']; ?>" id="maxNum_<?= $row['field_name']; ?>" value="" placeholder="<?= TRANS('OCO_SEL_ANY'); ?>" autocomplete="off" >

                    </div>


                    <?php
                } elseif ($row['field_type'] == 'date') {
                ?>
                <div class="w-100"></div>
                <!-- Primeiro campo para data inicial do período -->
                <label for="min_<?= $row['field_name']; ?>" class="col-sm-<?= $labelColSize; ?> col-md-<?= $labelColSize; ?> col-form-label col-form-label-sm text-md-right " title="<?= $row['field_title']; ?>" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= $row['field_description']; ?>"><?= $row['field_label']; ?>&nbsp;<small>(<?= TRANS('MIN_DATE'); ?>)</small></label>
                <div class=" form-group col-md-<?= $fieldColSize; ?>">
                
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-edit"></i>&nbsp;
                                <input type="checkbox" class="first-check-date" name="no_empty_<?= $row['field_name']; ?>" id="no_empty_<?= $row['field_name']; ?>" value="1" >
                            </div>
                        </div>

                        <input class="form-control custom_field_date custom_field_date_min" type="text" name="min_<?= $row['field_name']; ?>" id="min_<?= $row['field_name']; ?>" value="" placeholder="<?= TRANS('OCO_SEL_ANY'); ?>" <?= $inlineAttributes; ?> autocomplete="off">

                        <div class="input-group-append double-append">
                            <div class="input-group-text" title="<?= TRANS('SMART_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-times"></i>&nbsp;
                                <input type="checkbox" class="last-check-date" name="no_<?= $row['field_name']; ?>" id="no_<?= $row['field_name']; ?>" value="1" >
                            </div>
                        </div>
                        <div class="input-group-append double-append">
                            <div class="input-group-text" title="<?= TRANS('DONT_RENDER'); ?>" data-placeholder="<?= TRANS('DONT_RENDER'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-eye-slash"></i>&nbsp;
                                <input type="checkbox" class="no-render" name="norender_<?= $row['field_name']; ?>" id="norender_<?= $row['field_name']; ?>" value="1">
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Segundo campo para data final do período -->
                <label for="max_<?= $row['field_name']; ?>" class="col-sm-<?= $labelColSize; ?> col-md-<?= $labelColSize; ?> col-form-label col-form-label-sm text-md-right " title="<?= $row['field_title']; ?>" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= $row['field_description']; ?>"><?= $row['field_label']; ?>&nbsp;<small>(<?= TRANS('MAX_DATE'); ?>)</small></label>
                <div class="form-group col-md-<?= $fieldColSize; ?>">

                <div class="input-group">
                    <div class="input-group-prepend">
                        <div class="input-group-text" title="<?= TRANS('MAX_DATE'); ?>" data-placeholder="<?= TRANS('MAX_DATE'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                            <i class="fas fa-less-than-equal"></i>&nbsp;
                        </div>
                    </div>

                    <input class="form-control custom_field_date custom_field_date_max" type="text" name="max_<?= $row['field_name']; ?>" id="max_<?= $row['field_name']; ?>" value="" placeholder="<?= TRANS('OCO_SEL_ANY'); ?>" <?= $inlineAttributes; ?> autocomplete="off" >

                </div>
                <?php
                } elseif ($row['field_type'] == 'datetime') {
                    ?>
                    <div class="w-100"></div>
                    <!-- Primeiro campo para data inicial do período -->
                    <label for="min_<?= $row['field_name']; ?>" class="col-sm-<?= $labelColSize; ?> col-md-<?= $labelColSize; ?> col-form-label col-form-label-sm text-md-right " title="<?= $row['field_title']; ?>" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= $row['field_description']; ?>"><?= $row['field_label']; ?>&nbsp;<small>(<?= TRANS('MIN_DATE'); ?>)</small></label>
                    <div class=" form-group col-md-<?= $fieldColSize; ?>">
                    
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <div class="input-group-text" title="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                    <i class="fas fa-edit"></i>&nbsp;
                                    <input type="checkbox" class="first-check-date" name="no_empty_<?= $row['field_name']; ?>" id="no_empty_<?= $row['field_name']; ?>" value="1" >
                                </div>
                            </div>

                            <input class="form-control custom_field_datetime custom_field_datetime_min" type="text" name="min_<?= $row['field_name']; ?>" id="min_<?= $row['field_name']; ?>" value="" placeholder="<?= TRANS('OCO_SEL_ANY'); ?>" <?= $inlineAttributes; ?> autocomplete="off">

                            <div class="input-group-append double-append">
                                <div class="input-group-text" title="<?= TRANS('SMART_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                    <i class="fas fa-times"></i>&nbsp;
                                    <input type="checkbox" class="last-check-date" name="no_<?= $row['field_name']; ?>" id="no_<?= $row['field_name']; ?>" value="1" >
                                </div>
                            </div>
                            <div class="input-group-append double-append">
                                <div class="input-group-text" title="<?= TRANS('DONT_RENDER'); ?>" data-placeholder="<?= TRANS('DONT_RENDER'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                    <i class="fas fa-eye-slash"></i>&nbsp;
                                    <input type="checkbox" class="no-render" name="norender_<?= $row['field_name']; ?>" id="norender_<?= $row['field_name']; ?>" value="1">
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- Segundo campo para data final do período -->
                    <label for="max_<?= $row['field_name']; ?>" class="col-sm-<?= $labelColSize; ?> col-md-<?= $labelColSize; ?> col-form-label col-form-label-sm text-md-right " title="<?= $row['field_title']; ?>" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= $row['field_description']; ?>"><?= $row['field_label']; ?>&nbsp;<small>(<?= TRANS('MAX_DATE'); ?>)</small></label>
                    <div class="form-group col-md-<?= $fieldColSize; ?>">

                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('MAX_DATE'); ?>" data-placeholder="<?= TRANS('MAX_DATE'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-less-than-equal"></i>&nbsp;
                            </div>
                        </div>

                        <input class="form-control custom_field_datetime custom_field_datetime_max" type="text" name="max_<?= $row['field_name']; ?>" id="max_<?= $row['field_name']; ?>" value="" placeholder="<?= TRANS('OCO_SEL_ANY'); ?>" <?= $inlineAttributes; ?> autocomplete="off" >

                    </div>
                    <?php
                } elseif ($row['field_type'] == 'time') {
                    ?>
                        <input class="form-control custom_field_time" type="text" name="<?= $row['field_name']; ?>" id="<?= $row['field_name']; ?>" value="" placeholder="<?= $row['field_placeholder']; ?>" <?= $inlineAttributes; ?> autocomplete="off">
                    <?php
                } else {
                    /* campo do tipo texto */
                ?>
                    
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text" title="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-edit"></i>&nbsp;
                                <input type="checkbox" class="first-check-text" name="no_empty_<?= $row['field_name']; ?>" id="no_empty_<?= $row['field_name']; ?>" value="1" >
                            </div>
                        </div>
                    
                    
                        <input class="form-control custom_field_text" type="text" name="<?= $row['field_name']; ?>" id="<?= $row['field_name']; ?>" value="" placeholder="<?= $row['field_placeholder']; ?>" <?= $fieldMask; ?> <?= $inlineAttributes; ?> autocomplete="off">

                        <div class="input-group-append double-append">
                            <div class="input-group-text" title="<?= TRANS('SMART_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-times"></i>&nbsp;
                                <input type="checkbox" class="last-check-text" name="no_<?= $row['field_name']; ?>" id="no_<?= $row['field_name']; ?>" value="1" >
                            </div>
                        </div>
                        <div class="input-group-append double-append">
                            <div class="input-group-text" title="<?= TRANS('DONT_RENDER'); ?>" data-placeholder="<?= TRANS('DONT_RENDER'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                <i class="fas fa-eye-slash"></i>&nbsp;
                                <input type="checkbox" class="no-render" name="norender_<?= $row['field_name']; ?>" id="norender_<?= $row['field_name']; ?>" value="1">
                            </div>
                        </div>
                    </div>
                        
                <?php
                }
                ?>
            </div>
            <?php
            } /* foreach */

                ?>
            </div>
            <!-- </div> -->
            <!-- card-body -->
        </div>
    </div>
</div>
<?php
}
?>