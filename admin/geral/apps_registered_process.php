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
use OcomonApi\WebControllers\AppsRegisters;

$conn = ConnectPDO::getInstance();

$post = $_POST;


$screenNotification = "";
$exception = "";
$msg_tokens = "";
$data = [];
$data['success'] = true;
$data['message'] = "";
$data['cod'] = (isset($post['cod']) ? intval($post['cod']) : "");
$data['action'] = $post['action'];
$data['field_id'] = "";

/* Por ora apenas apps para abertura de chamados são permitidos */
$api = 'OcomonApi';
$controller = 'Controllers';
$section = 'Tickets';
$methods = 'create';

$data['app_name'] = (isset($post['app_name']) && !empty($post['app_name']) ? str_slug(noHtml($post['app_name'])) : "");

$data['allowed_actions'] = (isset($post['allowed_action']) && !empty($post['allowed_action']) ? $post['allowed_action'] : []);

$data['actions'] = "";
if (!empty($data['allowed_actions'])){
    foreach ($data['allowed_actions'] as $action => $value) {
        if ($value == "yes") {
            if (strlen((string)$data['actions'])) $data['actions'] .= ",";
            $data['actions'] .= $action;
        }
    }
}

/* Validações */
if ($data['action'] == "new") {

    if (empty($data['app_name'])) {
        $data['success'] = false; 
        $data['field_id'] = "app_name";
    }
}

if ($data['action'] == "new" || $data['action'] == "edit") {

    if (empty($data['actions'])) {
        $data['success'] = false; 
        $data['field_id'] = "allowed_action";
    }
}


if ($data['success'] == false) {
    $data['message'] = message('warning', 'Ooops!', TRANS('MSG_EMPTY_DATA'),'');
    echo json_encode($data);
    return false;
}



if ($data['action'] == 'new') {

    $sql = "SELECT id FROM `apps_register` 
            WHERE 
                `app` = '" . $data['app_name'] . "' AND 
                `controller` like ('{$api}%{$controller}%{$section}') ";
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

    $appController = new AppsRegisters();
    $dataApps = [
        'app' => $data['app_name'],
        'controller' => $api . "\\" . $controller . "\\" . $section,
        'methods' => $data['actions']
    ];

    $appController->save($dataApps);

    $data['success'] = true; 
    $data['message'] = TRANS('MSG_SUCCESS_INSERT');

    $_SESSION['flash'] = message('success', '', $data['message'] . $exception, '');
    echo json_encode($data);
    return false;

} elseif ($data['action'] == 'edit') {

    if ($data['cod'] == 1) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_CANT_EDIT');
        $_SESSION['flash'] = message('danger', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    }
    
    
    $sql = "SELECT id FROM `apps_register` 
            WHERE 
                `app` = '" . $data['app_name'] . "' AND 
                `controller` like ('{$api}%{$controller}%{$section}') AND 
                id <> '".  $data['cod'] ."'
                ";
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

    $appController = new AppsRegisters();
    $dataApps = [
        'id' => $data['cod'],
        // 'app' => $data['app_name'],
        'controller' => $api . "\\" . $controller . "\\" . $section,
        'methods' => $data['actions']
    ];

    $appController->save($dataApps);

    $data['success'] = true; 
    $data['message'] = TRANS('MSG_SUCCESS_EDIT');

    $_SESSION['flash'] = message('success', '', $data['message'] . $exception, '');
    echo json_encode($data);
    return false;

} elseif ($data['action'] == 'delete') {

    if ($data['cod'] == 1) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_CANT_DEL');
        $_SESSION['flash'] = message('danger', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    }
    
    $sqlApp = "SELECT app FROM apps_register WHERE id = '". $data['cod'] ."' ";
    $resApp = $conn->query($sqlApp);
    $appName = $resApp->fetch()['app'];

    $appController = new AppsRegisters();
    if ($appController->delete($data['cod'])) {
        $data['success'] = true; 
        $data['message'] = TRANS('OK_DEL');

        $sql = "DELETE FROM access_tokens WHERE app = '{$appName}' ";
        try {
            $conn->exec($sql);
            $msg_tokens = '<hr>' . TRANS('RELATED_TOKENS_DELETED');
        }
        catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage();
        }

        $_SESSION['flash'] = message('success', '', $data['message'] . $msg_tokens . $exception, '');

        echo json_encode($data);
        return false;
    }

}

echo json_encode($data);