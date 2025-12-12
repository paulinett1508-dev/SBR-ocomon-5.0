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

$data['channel_name'] = (isset($post['channel_name']) ? noHtml($post['channel_name']) : "");
$data['is_default'] = (isset($post['is_default']) ? ($post['is_default'] == "yes" ? 1 : 0) : 0);
$data['only_set_by_system'] = (isset($post['only_set_by_system']) ? ($post['only_set_by_system'] == "yes" ? 1 : 0) : 0);


/* Validações */

if ($data['action'] == "new" || $data['action'] == "edit") {

    if (empty($data['channel_name'])) {
        $data['success'] = false; 
        $data['field_id'] = "channel_name";
    }
}

if ($data['success'] == false) {
    $data['message'] = message('warning', 'Ooops!', TRANS('MSG_EMPTY_DATA'),'');
    echo json_encode($data);
    return false;
}


if ($data['is_default'] == 0) {
    
    $terms = ($data['cod'] ? " AND id <> " . $data['cod'] : '');
    
    /* Confere se existe algum canal marcado como padrão - deve existir um */
    $sql = "SELECT id FROM channels WHERE is_default = 1 {$terms} ";
    
    $res = $conn->query($sql);
    if (!$res->rowCount()) {
        /* Se não existe nenhum canal padrão, então esse será a padrão */
        $data['is_default'] = 1;
        $exception .= "<hr>" . TRANS('MSG_ONE_MUST_BE_DEFAULT');
    }
}

if ($data['action'] == 'new') {

    /* verifica se um registro com esse nome já existe */
    $sql = "SELECT id FROM channels WHERE name = '" . $data['channel_name'] . "' ";
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $data['success'] = false; 
        $data['field_id'] = "channel_name";
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

    if ($data['is_default']) {
        $sqlUpdDefault = "UPDATE channels SET is_default = 0 ";
        try {
            $conn->exec($sqlUpdDefault);
        } catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage();
        }
    }
    

    $sql = "INSERT INTO channels 
        (
            name,
            is_default, 
            only_set_by_system
        ) 
        VALUES 
        (
            '" . $data['channel_name'] . "', 
            '" . $data['is_default'] . "', 
            '" . $data['only_set_by_system'] . "' 
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


    $sql = "SELECT id FROM `channels` WHERE name = '" . $data['channel_name'] . "' AND id <> '" . $data['cod'] . "' ";
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

    
    if ($data['is_default']) {
        $sqlUpdDefault = "UPDATE channels SET is_default = 0 ";
        try {
            $conn->exec($sqlUpdDefault);
        } catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage();
        }
    }
    
    $sql = "UPDATE channels SET 
                name = '" . $data['channel_name'] . "', 
                is_default = '" . $data['is_default'] . "', 
                only_set_by_system = '" . $data['only_set_by_system'] . "' 
            WHERE id = '" . $data['cod'] . "'";


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

    $sqlFindPrevention = "SELECT * FROM ocorrencias WHERE oco_channel = ".$data['cod']."";
    $resFindPrevention = $conn->query($sqlFindPrevention);
    $foundPrevention = $resFindPrevention->rowCount();

    if ($foundPrevention) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_CANT_DEL');
        $_SESSION['flash'] = message('danger', '', $data['message'], '');
        echo json_encode($data);
        return false;
    }

    $sqlFindPrevention = "SELECT id FROM config_keys WHERE key_name = 'API_TICKET_BY_MAIL_CHANNEL' AND key_value = '".$data['cod']."'";
    $resFindPrevention = $conn->query($sqlFindPrevention);
    $foundPrevention = $resFindPrevention->rowCount();

    if ($foundPrevention) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_CANT_DEL');
        $_SESSION['flash'] = message('danger', '', $data['message'], '');
        echo json_encode($data);
        return false;
    }


    $sql = "SELECT * FROM channels WHERE is_default = 1 AND id = '" . $data['cod'] . "'";
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_CANT_DEL_DEFAULT');
        $_SESSION['flash'] = message('danger', '', $data['message'], '');
        echo json_encode($data);
        return false;
    }

    $sql = "DELETE FROM channels WHERE id = '" . $data['cod'] . "'";

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