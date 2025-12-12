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

$data['parent_asset_tag'] = (isset($post['parent_asset_tag']) ? noHtml($post['parent_asset_tag']) : "");
$data['parent_asset_unit'] = (isset($post['parent_asset_unit']) ? noHtml($post['parent_asset_unit']) : "");
$data['asset_type'] = (isset($post['asset_type']) ? noHtml($post['asset_type']) : "");
$data['random'] = (isset($post['random']) ? noHtml($post['random']) : "");
$data['html'] = "";


if (empty($data['asset_type']) || empty($data['parent_asset_unit']) || empty($data['parent_asset_tag'])) {
    echo "";
    return;
}


$sql = $QRY["full_detail_ini"] . " 
        AND
            c.comp_inst = '" . $data['parent_asset_unit'] . "' AND 
            c.comp_inv = '" . $data['parent_asset_tag'] . "'";
try {
    $res = $conn->prepare($sql);
    $res->execute();
    $parent_info = $res->fetch();
} catch (Exception $e) {
    $exception .= "<hr />" . $e->getMessage();
}


if (empty($parent_info)) {
    $data['success'] = false;
    $data['html'] .= '<div class="container ">';
    $data['message'] = TRANS('ASSET_NOT_FOUND_WITH_THESE_TAG_AND_UNIT');
    $data['html'] .= message('warning', 'Ooops!', $data['message'], '', '', true);
    $data['html'] .= '</div">';

    echo json_encode($data);
    return;
}

$data['department_cod'] = $parent_info['tipo_local'];
$data['department_name'] = $parent_info['local'];

$parent_id = $parent_info['comp_cod'];
$parent_type = $parent_info['tipo'];
$possible_parents = getAssetsTypesPossibleParents($conn, $data['asset_type']);


$text = $parent_info['equipamento'] . '&nbsp' . $parent_info['fab_nome'] . '<hr />';
$text .= TRANS('COL_MODEL') . ": " . $parent_info['modelo'] . "<hr />";
$text .= TRANS('DEPARTMENT') . ": " . $parent_info['local'];

$acceptedParent = false;
foreach ($possible_parents as $pParent) {
    if ($pParent['tipo_cod'] == $parent_type) {
        $acceptedParent = true;
        break;
    }
}


if (!$acceptedParent) {
    $data['success'] = false;
    $data['message'] = TRANS('ASSETS_RELATIONSHIP_NOT_COMPATIBLE');
    $data['html'] .= '<div class="container ">';
    $data['html'] .= message('warning', 'Ooops!', $data['message'] . '<hr />' . $text, '', '', true);
    $data['html'] .= '</div>';

    echo json_encode($data);
    return;
}



$data['message'] = $text;
$data['html'] = '';
$data['html'] .= '<div class="container">';
$data['html'] .= message('success', '', $data['message'], '', '', true);
$data['html'] .= '</div>';




echo json_encode($data);