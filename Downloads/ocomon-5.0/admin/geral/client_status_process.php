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
$data['csrf_session_key'] = (isset($post['csrf_session_key']) ? $post['csrf_session_key'] : "");


$data['status_name'] = (isset($post['status_name']) ? noHtml($post['status_name']) : "");
$data['status_description'] = (isset($post['status_description']) ? noHtml($post['status_description']) : "");

/* Validações */
if ($data['action'] == "new" || $data['action'] == "edit") {

    if (empty($data['status_name'])) {
        $data['success'] = false; 
        $data['field_id'] = "status_name";
    }

    if ($data['success'] == false) {
        $data['message'] = message('warning', 'Ooops!', TRANS('MSG_EMPTY_DATA'),'');
        echo json_encode($data);
        return false;
    }
}


if ($data['action'] == 'new') {

    /* verifica se um registro com esse nome já existe */
    $sql = "SELECT id FROM client_status WHERE status_name = '" . $data['status_name'] . "' ";
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $data['success'] = false; 
        $data['field_id'] = "status_name";
        $data['message'] = message('warning', '', TRANS('MSG_RECORD_EXISTS'), '');
        echo json_encode($data);
        return false;
    }


    if (!csrf_verify($post, $data['csrf_session_key'])) {

        $data['success'] = false; 
        $data['message'] = message('warning', 'Ooops!', TRANS('FORM_ALREADY_SENT'),'');
    
        echo json_encode($data);
        return false;
    }

    $sql = "INSERT INTO client_status 
        (
            status_name, 
            status_description
        ) 
        VALUES 
        (
            '" . $data['status_name'] . "', 
            " . dbField($data['status_description'], 'text') . "  
        )";

    try {
        $conn->exec($sql);
        $data['success'] = true; 
        $data['message'] = TRANS('MSG_SUCCESS_INSERT');
        $_SESSION['flash'] = message('success', '', $data['message'], '');
        echo json_encode($data);
        return false;
    } catch (Exception $e) {
        $exception .= "<hr>" . $e->getMessage();
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_SAVE_RECORD') . $exception;
        $_SESSION['flash'] = message('danger', '', $data['message'], '');
        echo json_encode($data);
        return false;
    }

} elseif ($data['action'] == 'edit') {

    /* verifica se um registro com esse nome já existe para outro código */
    $sql = "SELECT id FROM client_status WHERE status_name = '" . $data['status_name'] . "' AND id <> '" . $data['cod'] . "'";
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $data['success'] = false; 
        $data['field_id'] = "status_name";
        $data['message'] = message('warning', '', TRANS('MSG_RECORD_EXISTS'), '');
        echo json_encode($data);
        return false;
    }

    if (!csrf_verify($post, $data['csrf_session_key'])) {

        $data['success'] = false; 
        $data['message'] = message('warning', 'Ooops!', TRANS('FORM_ALREADY_SENT'),'');
    
        echo json_encode($data);
        return false;
    }

    $sql = "UPDATE client_status SET 
				status_name = '" . $data['status_name'] . "', 
				status_description = " . dbField($data['status_description'], 'text') . " 
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

} elseif ($data['action'] == 'delete') {



    /* Registro de sistema - não deve ser excluído */
    if ($data['cod'] == 1) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_CANT_DEL');
        $_SESSION['flash'] = message('danger', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    }
    
    $sqlFindPrevention = "SELECT * FROM clients WHERE `status` = ".$data['cod']."";
    $resFindPrevention = $conn->query($sqlFindPrevention);
    $foundPrevention = $resFindPrevention->rowCount();

    if ($foundPrevention) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_CANT_DEL');
        $_SESSION['flash'] = message('danger', '', $data['message'], '');
        echo json_encode($data);
        return false;
    }
    

    $sql = "DELETE FROM client_status WHERE id = '" . $data['cod'] . "'";

    try {
        $conn->exec($sql);
        $data['success'] = true; 
        $data['message'] = TRANS('OK_DEL');
        $_SESSION['flash'] = message('success', '', $data['message'], '');
        echo json_encode($data);
        return false;
    } catch (Exception $e) {
        $exception .= "<hr>" . $e->getMessage();
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_DATA_REMOVE');
        $_SESSION['flash'] = message('danger', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    }
    
}

echo json_encode($data);