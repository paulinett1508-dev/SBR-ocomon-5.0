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


$exception = "";
$screenNotification = "";
$data = [];
$data['success'] = true;
$data['message'] = "";
$data['cod'] = (isset($post['cod']) ? intval($post['cod']) : "");
$data['numero'] = (isset($post['numero']) ? intval($post['numero']) : "");
$data['action'] = $post['action'];
$data['field_id'] = "";


// var_dump($post); exit();

$data['asset_tag'] = (isset($post['asset_tag']) ? noHtml($post['asset_tag']) : "");
$data['asset_unit'] = (isset($post['asset_unit']) ? noHtml($post['asset_unit']) : "");
$data['software'] = (isset($post['software']) ? noHtml($post['software']) : "");




/* Checagem de preenchimento dos campos obrigatórios*/
if ($data['action'] == "new") {

    if ($data['asset_tag'] == "") {
        $data['success'] = false; 
        $data['field_id'] = "asset_tag";
    } elseif ($data['asset_unit'] == "") {
        $data['success'] = false; 
        $data['field_id'] = "asset_unit";
    } elseif ($data['software'] == "") {
        $data['success'] = false; 
        $data['field_id'] = "software";
    } 

    if ($data['success'] == false) {
        $data['message'] = message('warning', '', TRANS('MSG_EMPTY_DATA'), '');
        echo json_encode($data);
        return false;
    }

}


/* Processamento */
if ($data['action'] == "new") {


    $sql = "SELECT hws_cod FROM hw_sw 
            WHERE hws_sw_cod = '" . $data['software'] . "' AND hws_hw_cod = '" . $data['asset_tag'] . "'  
            AND hws_hw_inst = '" . $data['asset_unit'] . "' 
    ";

    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $data['success'] = false; 
        $data['field_id'] = "asset_tag";
        $data['message'] = message('warning', '', TRANS('MSG_RECORD_EXISTS'), '');
        echo json_encode($data);
        return false;
    }

    /* Verificação de CSRF */
    if (!csrf_verify($post)) {
        $data['success'] = false; 
        $data['message'] = message('warning', 'Ooops!', TRANS('FORM_ALREADY_SENT'),'');
        echo json_encode($data);
        return false;
    }

	$sql = "INSERT INTO hw_sw 
        (
            hws_sw_cod, hws_hw_cod, hws_hw_inst
        )
		VALUES 
        (
            '" . $data['software'] . "', 
            '" . $data['asset_tag'] . "', 
            '" . $data['asset_unit'] . "' 
        )";
		
    try {
        $conn->exec($sql);
        $data['success'] = true; 
        $data['message'] = TRANS('MSG_SUCCESS_INSERT');
        
    } catch (Exception $e) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_SAVE_RECORD') . "<br/>" . $sql;
        $_SESSION['flash'] = message('danger', '', $data['message'], '');
        echo json_encode($data);
        return false;
    }

} elseif ($data['action'] == 'load') {

    
    $sqlA = "SELECT * FROM sw_padrao ORDER BY swp_sw_cod ";
    $commitA = $conn->query($sqlA);
    foreach ($commitA->fetchall() as $rowA) {

        $sqlTemp = "SELECT * FROM hw_sw WHERE hws_sw_cod = '" . $rowA['swp_sw_cod'] . "' AND hws_hw_cod ='" . $data['asset_tag'] . "' AND hws_hw_inst = '" . $data['asset_unit'] ."' ";
        $commitTemp = $conn->query($sqlTemp);
        $regs = $commitTemp->rowCount();
        if ($regs == 0) {
            $sqlB = "INSERT into hw_sw (hws_sw_cod, hws_hw_cod, hws_hw_inst) values ('".$rowA["swp_sw_cod"]."', '".$data['asset_tag']."', ".$data['asset_unit'].")";
            
            try {
                $conn->exec($sqlB);
                $data['success'] = true; 
                $data['message'] = TRANS('MSG_SUCCESS_INSERT');
            }
            catch (Exception $e) {
                $data['success'] = false; 
                $data['message'] = TRANS('MSG_PROBLEM_CAD_SOFTWARES') . "<br />". $sql . "<br />" . $e->getMessage();
                $_SESSION['flash'] = message('danger', 'Ooops!', $data['message'], '');
                echo json_encode($data);
                return false;
            }
        } else {
            $data['success'] = true; 
            $data['message'] = TRANS('NONE_SOFTWARE_LOAD');
        }
    }
            
   
} 


elseif ($data['action'] == 'reset') {

    $sql = "DELETE FROM hw_sw WHERE hws_hw_cod ='" . $data['asset_tag'] . "' AND hws_hw_inst ='" . $data['asset_unit'] . "'";
	try {
        $conn->exec($sql);
        $data['success'] = true; 
        $data['message'] = TRANS('OK_DEL');

        $_SESSION['flash'] = message('success', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    } catch (Exception $e) {
        $exception .= "<hr>" . $e->getMessage() . "<hr>";
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_DATA_REMOVE');
        $_SESSION['flash'] = message('danger', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    }
    
   
}


elseif ($data['action'] == 'delete') {


    /* Sem restrições para excluir o registro */
    $sql = "DELETE FROM hw_sw WHERE hws_cod = '" . $data['cod'] . "' ";

    try {
        $conn->exec($sql);
        $data['success'] = true; 
        $data['message'] = TRANS('OK_DEL');

        $_SESSION['flash'] = message('success', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    } catch (Exception $e) {
        $exception .= "<hr>" . $e->getMessage() . "<hr>";
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_DATA_REMOVE');
        $_SESSION['flash'] = message('danger', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    }
}



$_SESSION['flash'] = message('success', '', $data['message'] . $exception, '');
echo json_encode($data);
return false;