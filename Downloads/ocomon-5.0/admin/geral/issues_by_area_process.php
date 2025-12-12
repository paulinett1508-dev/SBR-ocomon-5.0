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


$erro = false;
$screenNotification = "";
$exception = "";
$data = [];
$data['success'] = true;
$data['message'] = "";
$data['cod'] = (isset($post['cod']) ? intval($post['cod']) : "");
$data['action'] = $post['action'];
$data['field_id'] = "";

$data['area'] = (isset($post['area']) ? (int)noHtml($post['area']) : "");
$data['issue'] = (isset($post['issue']) ? (int)noHtml($post['issue']) : "");


if (empty($data['area']) || empty($data['issue'])) {
    $data['success'] = false; 
    $data['field_id'] = "problema";
    $data['message'] = message('warning', 'Ooops!', TRANS('MSG_EMPTY_DATA'),'');
    echo json_encode($data);
    return false;
}



$exception_list = "";
$sql = "SELECT prob_not_area FROM problemas WHERE prob_id = '" . $data['issue'] . "' ";
try {
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        if (!empty($probs_not_area = $res->fetch()['prob_not_area'])) {
            $exception_list = $probs_not_area;
        }
    }
}
catch (Exception $e) {
    $exception .= "<hr>" . $e->getMessage();
    $data['success'] = false; 
    $data['field_id'] = "problema";
    $data['message'] = message('warning', 'Ooops!', $exception,'');
    echo json_encode($data);
    return false;
}

// var_dump($data, $exception_list); exit;



if ($data['action'] == 'add_exception') {

    if (strlen((string)$exception_list)) 
        $exception_list .= ',';
    $exception_list .= $data['area'];

    /* Atualizando a lista de exceções */
    $sql = "UPDATE problemas SET prob_not_area = '{$exception_list}' WHERE prob_id = '" . $data['issue'] . "' ";
    try {
        $conn->exec($sql);
        $data['success'] = true; 
        $data['message'] = TRANS('EXCEPTION_SUCCESS_RECORDED');
        $_SESSION['flash'] = message('success', '', $data['message'], '');
        echo json_encode($data);
        return false;
    }
    catch (Exception $e) {
        $exception .= "<hr>" . $e->getMessage();
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_SAVE_RECORD');
        $_SESSION['flash'] = message('danger', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    }


} elseif ($data['action'] == 'remove_exception') {


    $arrayExceptions = explode(',', $exception_list);

    //Remove element by value using unset()
    if (($key = array_search($data['area'], $arrayExceptions)) !== false){
        unset($arrayExceptions[$key]);
    }

    $newExceptionList = implode(',', $arrayExceptions);


    $sql = "UPDATE problemas SET 
                prob_not_area = " . dbField($newExceptionList,'text') . " 
            WHERE prob_id='" . $data['issue'] . "'";

    try {
        $conn->exec($sql);
        $data['success'] = true; 
        $data['message'] = TRANS('EXCEPTION_SUCCESS_REMOVED');
        $_SESSION['flash'] = message('success', '', $data['message'], '');
        echo json_encode($data);
        return false;
    } catch (Exception $e) {
        $exception .= "<hr>" . $e->getMessage();
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_DATA_UPDATE');
        $_SESSION['flash'] = message('danger', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    }

}

echo json_encode($data);