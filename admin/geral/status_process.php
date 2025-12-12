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



$screenNotification = "";
$exception = "";
$data = [];
$data['success'] = true;
$data['message'] = "";
$data['cod'] = (isset($post['cod']) ? intval($post['cod']) : "");
$data['action'] = $post['action'];
$data['field_id'] = "";
/* Status que não devem ser excluidos */
$data['system_status'] = [1,2,4,7,12];
/* Status que não podem ser marcados como ignored */
$data['cant_ignore'] = [1,2,4,7];

$data['status'] = (isset($post['status']) ? noHtml($post['status']) : "");
$data['categoria'] = (isset($post['categoria']) ? noHtml($post['categoria']) : "");
$data['painel'] = (isset($post['painel']) ? noHtml($post['painel']) : "");


$data['time_freeze'] = (isset($post['time_freeze']) ? ($post['time_freeze'] == "yes" ? 1 : 0) : 0);
$data['time_freeze'] = ($data['painel'] == '3' || $data['cod'] == '4' ? 1 : $data['time_freeze']);

$data['ignored'] = (isset($post['ignored']) ? ($post['ignored'] == "yes" ? 1 : 0) : 0);
if ($data['ignored'] == 1) {
    $data['time_freeze'] = 1;
}



/* Validações */
if ($data['action'] == "new") {

    if (empty($data['status'])) {
        $data['success'] = false; 
        $data['field_id'] = "status";
    } elseif (empty($data['categoria'])) {
        $data['success'] = false; 
        $data['field_id'] = "categoria";
    }  elseif (empty($data['painel'])) {
        $data['success'] = false; 
        $data['field_id'] = "painel";
    }
}

if ($data['action'] == "edit") {

    if (empty($data['status'])) {
        $data['success'] = false; 
        $data['field_id'] = "status";
    }
}

if ($data['success'] == false) {
    $data['message'] = message('warning', 'Ooops!', TRANS('MSG_EMPTY_DATA'),'');
    echo json_encode($data);
    return false;
}



if ($data['action'] == 'new') {

    $sql = "SELECT stat_id FROM `status` WHERE `status` = '" . $data['status'] . "' ";
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $data['success'] = false; 
        $data['field_id'] = "status";
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

    $sql = "INSERT INTO 
                status 
                (
                    status, 
                    stat_cat, 
                    stat_painel, 
                    stat_time_freeze, 
                    stat_ignored
                ) 
                VALUES 
                (
                    '" . $data['status'] . "', 
                    '" . $data['categoria'] . "', 
                    '" . $data['painel'] . "', 
                    '" . $data['time_freeze'] . "', 
                    '" . $data['ignored'] . "'
                )";
    try {
        $conn->exec($sql);
        $data['success'] = true; 
        $data['message'] = TRANS('MSG_SUCCESS_INSERT');

        $_SESSION['flash'] = message('success', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    } catch (Exception $e) {
        $exception .= "<hr>" . $e->getMessage() . "<hr>" . $sql;
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_SAVE_RECORD');
        $_SESSION['flash'] = message('danger', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    }

} elseif ($data['action'] == 'edit') {


    $sql = "SELECT stat_id FROM `status` WHERE status = '" . $data['status'] . "' AND stat_id <> '" . $data['cod'] . "' ";
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $data['success'] = false; 
        $data['field_id'] = "area";
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

    $terms = "";
    if (!empty($data['categoria'])) {
        $terms .= "stat_cat = '" . $data['categoria'] . "', ";
    }
    if (!empty($data['painel'])) {
        $terms .= "stat_painel = '" . $data['painel'] . "', ";
    }

    if (!in_array($data['cod'], $data['cant_ignore'])) {
        $terms .= " stat_ignored = '" . $data['ignored'] . "', ";
    }

    $sql = "UPDATE status SET 
                status = '" . $data['status'] . "', 
                {$terms}
                stat_time_freeze = " . $data['time_freeze'] . " 
                
            WHERE stat_id = '" . $data['cod'] . "'";


    try {
        $conn->exec($sql);
        $data['success'] = true; 
        $data['message'] = TRANS('MSG_SUCCESS_EDIT');

        $_SESSION['flash'] = message('success', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    } catch (Exception $e) {
        $exception .= "<hr>" . $e->getMessage() . "<hr>" . $sql;
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_DATA_UPDATE');
        $_SESSION['flash'] = message('danger', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    }

} elseif ($data['action'] == 'delete') {

   
    /* Confere se não é status de sistema */
    if (in_array($data['cod'], $data['system_status'])) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_CANT_DEL_SYSTEM_REGISTER');
        $_SESSION['flash'] = message('danger', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    }


    /* Confere na tabela de ocorrências se a área está associada */
    $sql = "SELECT numero FROM ocorrencias WHERE status = '" . $data['cod'] . "' ";
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_CANT_DEL');
        $_SESSION['flash'] = message('danger', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    }



    /* Sem restrições para excluir a área */
    $sql = "DELETE FROM status WHERE stat_id = '" . $data['cod'] . "'";

    try {
        $conn->exec($sql);
        $data['success'] = true; 
        $data['message'] = TRANS('OK_DEL');

        $_SESSION['flash'] = message('success', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    } catch (Exception $e) {
        $exception .= "<hr>" . $e->getMessage() . "<hr>" . $sql;
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_DATA_REMOVE');
        $_SESSION['flash'] = message('danger', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    }
    
}

echo json_encode($data);