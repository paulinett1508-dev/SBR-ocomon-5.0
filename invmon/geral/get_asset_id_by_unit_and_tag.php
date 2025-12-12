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
$data['success'] = true;
$data['message'] = "";


$datapost['asset_unit'] = (isset($post['asset_unit']) ? noHtml($post['asset_unit']) : "");
$datapost['asset_tag'] = (isset($post['asset_tag']) ? noHtml($post['asset_tag']) : "");

if (empty($datapost['asset_unit']) || empty($datapost['asset_tag'])) {
    
    $data['success'] = false;
    $data['message'] =  message('warning', 'Ooops', TRANS('MSG_EMPTY_DATA'), '', '');
    
    echo json_encode($data);
    return;
}

$terms = "";
if (!empty($_SESSION['s_allowed_units'])) {
    $terms = " AND comp_inst IN (" . $_SESSION['s_allowed_units'] . ")";
}

$sql = "SELECT comp_cod FROM equipamentos WHERE comp_inv = :asset_tag AND comp_inst = :asset_unit {$terms}";


try {
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':asset_tag', $datapost['asset_tag'], PDO::PARAM_STR);
    $stmt->bindParam(':asset_unit', $datapost['asset_unit'], PDO::PARAM_INT);
    
    $stmt->execute();
   

    if ($stmt->rowCount()) {
        
        $result = $stmt->fetch();
        $data['asset_id'] = $result['comp_cod'];
    } else {
        $data['asset_id'] = "";
        $data['message'] = message('warning', 'Ooops', TRANS('NO_RESULTS_FOUND_LIMITED_BY_PERMISSIONS'), '', '');
    }


} catch (Exception $e) {
    $data['success'] = false;
    $data['asset_id'] = "";
    $data['message'] = message('danger', 'Ooops', TRANS('FETCH_ERROR'), '', '');

}




echo json_encode($data);