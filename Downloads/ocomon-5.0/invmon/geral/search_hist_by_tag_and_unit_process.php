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

$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 2, 2);

$post = $_POST;

$config = getConfig($conn);
// $rowLogado = getUserInfo($conn, $_SESSION['s_uid']);


$exception = "";
$screenNotification = "";
$data = [];
$data['success'] = true;
$data['message'] = "";
$data['cod'] = (isset($post['cod']) ? intval($post['cod']) : "");
// $data['action'] = $post['action'];
$data['field_id'] = "";


$data['asset_unit'] = (isset($post['asset_unit']) ? noHtml($post['asset_unit']) : "");
$data['asset_tag'] = (isset($post['asset_tag']) ? noHtml($post['asset_tag']) : "");


/* Checagem de preenchimento dos campos obrigatórios*/

if ($data['asset_unit'] == "") {
    $data['success'] = false; 
    $data['field_id'] = "asset_unit";
} elseif ($data['asset_tag'] == "") {
    $data['success'] = false; 
    $data['field_id'] = "asset_tag";
}

if ($data['success'] == false) {
    $data['message'] = message('warning', '', TRANS('MSG_EMPTY_DATA'), '');
    echo json_encode($data);
    return false;
}

$data['asset_id'] = getAssetIdFromTag($conn, $data['asset_unit'], $data['asset_tag']);

if (!$data['asset_id']) {
    $data['success'] = false;
    $data['message'] = message('warning', '', TRANS('NO_RECORDS_FOUND'), '');
    echo json_encode($data);
    return false;
}

echo json_encode($data);
return true;

