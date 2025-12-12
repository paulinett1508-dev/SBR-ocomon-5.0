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


$data['asset_type'] = (isset($post['asset_type']) ? noHtml($post['asset_type']) : "");
$data['asset_manufacturer'] = (isset($post['asset_manufacturer']) ? noHtml($post['asset_manufacturer']) : "");
$data['asset_model'] = (isset($post['asset_model']) ? noHtml($post['asset_model']) : "");

if (empty($data['asset_type']) || empty($data['asset_model']) || empty($data['asset_model'])) {
    echo "";
    return;
}

$modelSpecs = getModelSpecs($conn, $data['asset_model']);

// var_dump($modelSpecs); 

$html_msg = '';
$html = "";

if (!empty($modelSpecs)) {
    $data['message'] = TRANS('MODEL_ATTRIBUTES');
    $html_msg .= '<div class="ml-5">';
    foreach ($modelSpecs as $spec) {
        $html_msg .= '<li class="list_specs">' . $spec['mt_name'] . ': ' . $spec['spec_value'] . '' . $spec['unit_abbrev'] . '</li>';
    }
    $html_msg .= '</div>';
}

if (modelHasSavedSpecs($conn, $data['asset_model'])) {
    $html .= '<div class="w-100"></div>';
    $html .= '<div class="form-group row my-2">';
    $html .= '<label class="col-md-8 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="' . TRANS('LOAD_SAVED_SPECS_TO_MODEL') . '">' . firstLetterUp(TRANS('LOAD_SAVED_SPECS_TO_MODEL')) . '</label>';
    $html .= '<div class="form-group col-md-4 ">';
    $html .= '<div class="switch-field">';

    $yesChecked = "checked";
	$noChecked = "";

    $html .= '<input type="radio" id="load_saved_config" name="load_saved_config" value="yes" ' . $yesChecked . ' />';
    $html .= '<label for="load_saved_config">'. TRANS('YES') .'</label>';
    $html .= '<input type="radio" id="load_saved_config_no" name="load_saved_config" value="no" '. $noChecked .' />';
    $html .= '<label for="load_saved_config_no">'. TRANS('NOT') .'</label>';
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '</div>';

}


if (strlen((string)$html_msg) > 0) {
    echo message('info', $data['message'], $html_msg, '', '', true);
}
if (strlen((string)$html)) {
    echo $html;
}





