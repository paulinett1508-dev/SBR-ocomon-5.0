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
$datapost = [];
$data = [];
$dataFiltered = [];

$datapost['applied_to'] = (isset($post['applied_to']) && !empty(array_filter($post['applied_to'], function($v) { return !empty($v); })) ? array_map('noHtml', $post['applied_to']) : []);

if (empty($datapost['applied_to'])){
    return json_encode([]);
}

/* Na edição o id do perfil é enviado */
$data['profile_id'] = (isset($post['profile_id']) && !empty($post['profile_id']) ? $post['profile_id'] : "");

$specs_ids = "";
$array_specs_ids = [];
if (!empty($data['profile_id'])) {
    $specs_ids = getAssetsProfiles($conn, $data['profile_id'])['field_specs_ids'];
    $array_specs_ids = explode(',', $specs_ids);
}



$dataFiltered = getPossibleChildsFromManyAssetsTypes($conn, $datapost['applied_to']);


if (count($dataFiltered)) {
?>
    <div class="w-100">
        <p class="h6 text-center font-weight-bold mt-4"><?= TRANS('SPECS_FIELDS'); ?></p>
    </div>
    <?php
    foreach ($dataFiltered as $field) {
    ?>
        <label class="col-md-3 col-form-label col-form-label-sm text-md-right"><?= $field['tipo_nome']; ?></label>
        <div class="form-group col-md-3 switch-field container-switch">

            <?php
                $yesChecked = (in_array($field['tipo_cod'], $array_specs_ids) ? "checked" : "");
                $noChecked = (!in_array($field['tipo_cod'], $array_specs_ids) ? "checked" : "");
            ?>

            <input type="radio" id="<?= str_slug($field["tipo_nome"], 'spec_'); ?>" name="<?= str_slug($field["tipo_nome"], 'spec_'); ?>" value="yes" <?= $yesChecked; ?> />
            <label for="<?= str_slug($field["tipo_nome"], 'spec_'); ?>"><?= TRANS('YES'); ?></label>
            <input type="radio" id="<?= str_slug($field["tipo_nome"], 'spec_'); ?>_no" name="<?= str_slug($field["tipo_nome"], 'spec_'); ?>" value="no" <?= $noChecked; ?>  />
            <label for="<?= str_slug($field["tipo_nome"], 'spec_'); ?>_no"><?= TRANS('NOT'); ?></label>
        </div>
    <?php
    }
}