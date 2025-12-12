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

$post = $_POST;

$html = "";
$exception = "";
$data = [];
$data['success'] = true;
$data['message'] = "";

// $data['measure_type'] = (isset($post['measure_type']) ? noHtml($post['measure_type']) : "");
$data['random'] = (isset($post['random']) ? noHtml($post['random']) : "");

$types = getAssetsTypes($conn);

$afterDomClass = "after-dom-ready-aggregated";
$randomClass = $data['random'];

$operators = [
    '=' => TRANS('EQUAL_TO'),
    '>' => TRANS('GREATER_THAN'),
    '<' => TRANS('LESS_THAN'),
    '>=' => TRANS('GREATER_OR_EQUAL_TO'),
    '<=' => TRANS('LESS_OR_EQUAL_TO')
];



?>
    <!-- <label class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right <?= $randomClass; ?>" ></label> -->
    <label class="col-sm-1 col-md-1 col-form-label col-form-label-sm text-md-right <?= $randomClass; ?>" ><a href="javascript:void(0);" class="remove_button_specs" data-random="<?= $randomClass; ?>" title="<?= TRANS('REMOVE_FILTER'); ?>"><span class="text-danger"><i class="fa fa-minus"></i></span></a></label>
    <div class="form-group col-md-3 <?= $randomClass; ?>" >
        <div class="field_wrapper_specs" >
            <div class="input-group">
                <div class="input-group-prepend">
                    <div class="input-group-text" title="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_NOT_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                        <i class="fas fa-puzzle-piece"></i>&nbsp;
                        <input type="checkbox" class="first-check" name="no_empty_[]" id="no_empty_<?= $randomClass . '_'; ?>" value="1" disabled>
                    </div>
                </div>
                <select class="form-control bs-select sel-control aggregated-types <?= $afterDomClass; ?>" name="asset_type_aggregated[]" id="<?= $randomClass . '_'; ?>" >
                <?php
                    foreach ($types as $type) {
                        if (canBeChild($conn, $type['tipo_cod'])) {
                        ?>
                            <option value="<?= $type['tipo_cod']; ?>"><?= $type['tipo_nome']; ?></option>
                        <?php
                        }
                    }
                ?>
                </select>
                <div class="input-group-append">
                    <div class="input-group-text" title="<?= TRANS('SMART_EMPTY'); ?>" data-placeholder="<?= TRANS('SMART_EMPTY'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                        <i class="fas fa-times"></i>&nbsp;
                        <input type="checkbox" class="last-check" name="no_[]" id="no_<?= $randomClass . '_'; ?>" value="1" disabled>
                    </div>
                </div>
            </div>
            <small class="form-text text-muted"><?= TRANS('AGGREGATED_ASSET_TYPE'); ?></small>
        </div>
    </div>


    <div class="form-group col-md-2 <?= $randomClass; ?>" >
        <div class="field_wrapper_specs" >
                <select class="form-control bs-select sel-control <?= $afterDomClass; ?>" name="measure_type_aggregated[]" id="<?= $randomClass; ?>" >
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
            <small class="form-text text-muted"><?= TRANS('HELPER_CHOOSE_MEASURE_TYPE'); ?></small>
        </div>
    </div>

    <div class="form-group col-md-2 <?= $randomClass; ?>" >
        <select class="form-control bs-select" name="operation_aggregated[]" id="operation_<?= $randomClass; ?>">
            <?php
                foreach ($operators as $key => $operator) {
                    ?>
                    <option value="<?= $key; ?>"
                    <?= ($key == '=' ? ' selected' : ''); ?>
                    ><?= $operator; ?></option>
                    <?php
                }
            ?>
        </select>
    </div>


    <div class="form-group col-md-2 <?= $randomClass; ?>" >
        <input type="number" class="form-control" name="measure_value_aggregated[]" id="<?= $randomClass .'_'. $randomClass .'_'. $randomClass ?>" disabled/>
        <small class="form-text text-muted"><?= TRANS('COL_VALUE'); ?></small>
    </div>

    <div class="form-group col-md-2 <?= $randomClass; ?>" >
        <select class="form-control bs-select" name="measure_unit_aggregated[]" id="<?= $randomClass .'_'. $randomClass ?>">
            <option value=""><?= TRANS('SEL_SELECT'); ?></option>
        </select>
        <small class="form-text text-muted"><?= TRANS('MEASURE_UNIT'); ?></small>
    </div>
   
<?php
