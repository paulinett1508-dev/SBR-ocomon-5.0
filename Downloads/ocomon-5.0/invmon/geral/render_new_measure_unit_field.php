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

$data['measure_type'] = (isset($post['measure_type']) ? noHtml($post['measure_type']) : "");
$data['random'] = (isset($post['random']) ? noHtml($post['random']) : "");

$afterDomClass = "after-dom-ready";
$randomClass = $data['random'];

/* Operações possíveis para o fator de comparação */
$operations = [
	TRANS('DIVIDE') => '/',
	TRANS('MULTIPLY') => '*'
];
$opSignal = [
	'/' => TRANS('DIVIDE'),
	'*' => TRANS('MULTIPLY')
];



?>
    <label class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right <?= $randomClass; ?>" ></label>
    <div class="form-group col-md-3 <?= $randomClass; ?>" >
        <div class="field_wrapper_specs" >
            <div class="input-group">
                <div class="input-group-prepend">
                    <div class="input-group-text">
                        <a href="javascript:void(0);" class="remove_button_specs" data-random="<?= $randomClass; ?>" title="<?= TRANS('REMOVE'); ?>"><i class="fa fa-minus"></i></a>
                    </div>
                </div>
                <input type="text" class="form-control <?= $afterDomClass; ?>" id="unit_name<?= $randomClass; ?>" name="unit_name[]" required />
            </div>
            <small class="form-text text-muted"><?= TRANS('HELPER_UNIT_NAME'); ?></small>
        </div>
    </div>

    <div class="form-group col-md-2 <?= $randomClass; ?>" >
        <input type="text" class="form-control " id="unit_abbrev<?= $randomClass; ?>" name="unit_abbrev[]" required />
		<small class="form-text text-muted"><?= TRANS('HELPER_UNIT_ABBREV'); ?></small>
    </div>

    <div class="form-group col-md-2 <?= $randomClass; ?>" >
        <input type="number" class="form-control " id="equity_factor<?= $randomClass; ?>" name="equity_factor[]" required />
		<small class="form-text text-muted"><?= TRANS('HELPER_EQUITY_FACTOR'); ?></small>
    </div>

    <div class="form-group col-md-3 <?= $randomClass; ?>" >
        <select class="form-control " id="operation<?= $randomClass; ?>" name="operation[]">
            <?php
                foreach ($operations as $key => $operation) {
                    ?>
                        <option value="<?= $operation; ?>"><?= $key; ?></option>
                    <?php
                }
            ?>
        </select>
        <small class="form-text text-muted"><?= TRANS('OPERATION_TO_EQUITY'); ?></small>
    </div>
   
<?php
