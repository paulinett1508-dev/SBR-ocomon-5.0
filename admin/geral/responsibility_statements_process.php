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


$exception = "";
$screenNotification = "";
$data = [];
$data['success'] = true;
$data['message'] = "";
$data['cod'] = (isset($post['cod']) ? intval($post['cod']) : "");
$data['action'] = $post['action'];
$data['field_id'] = "";


$data['header'] = (isset($post['field_header']) && !empty($post['field_header']) ? noHtml($post['field_header']) : "");
$data['title'] = (isset($post['field_title']) && !empty($post['field_title']) ? noHtml($post['field_title']) : "");
$data['p1_bfr_list'] = (isset($post['paragraph_1_bfr_list']) && !empty($post['paragraph_1_bfr_list']) ? noHtml($post['paragraph_1_bfr_list']) : "");
$data['p2_bfr_list'] = (isset($post['paragraph_2_bfr_list']) && !empty($post['paragraph_2_bfr_list']) ? noHtml($post['paragraph_2_bfr_list']) : "");
$data['p3_bfr_list'] = (isset($post['paragraph_3_bfr_list']) && !empty($post['paragraph_3_bfr_list']) ? noHtml($post['paragraph_3_bfr_list']) : "");
$data['p1_aft_list'] = (isset($post['paragraph_1_aft_list']) && !empty($post['paragraph_1_aft_list']) ? noHtml($post['paragraph_1_aft_list']) : "");
$data['p2_aft_list'] = (isset($post['paragraph_2_aft_list']) && !empty($post['paragraph_2_aft_list']) ? noHtml($post['paragraph_2_aft_list']) : "");
$data['p3_aft_list'] = (isset($post['paragraph_3_aft_list']) && !empty($post['paragraph_3_aft_list']) ? noHtml($post['paragraph_3_aft_list']) : "");



/* Validações */
if ($data['action'] == "edit") {

    if (empty($data['header'])) {
        $data['success'] = false; 
        $data['field_id'] = "field_header";
    } elseif (empty($data['title'])) {
        $data['success'] = false; 
        $data['field_id'] = "field_title";
    }

    if ($data['success'] == false) {
        $data['message'] = message('warning', 'Ooops!', TRANS('MSG_EMPTY_DATA'),'');
        echo json_encode($data);
        return false;
    }
}


if ($data['action'] == 'edit') {

    /* verifica se um registro com esse titulo já existe para outro código */
    $sql = "SELECT title FROM asset_statements WHERE title = '" . $data['title'] . "' AND id <> '" . $data['cod'] . "'";
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $data['success'] = false; 
        $data['field_id'] = "supplier_name";
        $data['message'] = message('warning', '', TRANS('MSG_RECORD_EXISTS'), '');
        echo json_encode($data);
        return false;
    }

    if (!csrf_verify($post)) {
        $data['success'] = false; 
        $data['message'] = message('warning', 'Ooops!', TRANS('FORM_ALREADY_SENT'),'');
    
        echo json_encode($data);
        return false;
    }

    
    $sql = "UPDATE asset_statements SET 
				header = '" . $data['header'] . "', 
				title = '" . $data['title'] . "', 
                p1_bfr_list = " . dbField($data['p1_bfr_list'],'text') . ", 
                p2_bfr_list = " . dbField($data['p2_bfr_list'],'text') . ", 
                p3_bfr_list = " . dbField($data['p3_bfr_list'],'text') . ", 
                p1_aft_list = " . dbField($data['p1_aft_list'],'text') . ", 
                p2_aft_list = " . dbField($data['p2_aft_list'],'text') . ", 
                p3_aft_list = " . dbField($data['p3_aft_list'],'text') . " 

            WHERE id = '" . $data['cod'] . "'";

    try {
        $conn->exec($sql);
        $data['success'] = true; 
        $data['message'] = TRANS('MSG_SUCCESS_EDIT');
        $_SESSION['flash'] = message('success', '', $data['message'], '');
        echo json_encode($data);
        return false;
    } catch (Exception $e) {
        $exception .= "<hr>" . $e->getMessage();
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_DATA_UPDATE') . $exception;
        $_SESSION['flash'] = message('danger', '', $data['message'], '');
        echo json_encode($data);
        return false;
    }

} 

echo json_encode($data);