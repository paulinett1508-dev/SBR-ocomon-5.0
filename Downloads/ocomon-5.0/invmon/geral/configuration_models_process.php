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

/* Para manter a compatibilidade com versões antigas */
$table = "equipxpieces";
$sqlTest = "SELECT * FROM {$table}";
try {
    $conn->query($sqlTest);
}
catch (Exception $e) {
    $table = "equipXpieces";
}


$exception = "";
$screenNotification = "";
$data = [];
$data['success'] = true;
$data['message'] = "";
$data['cod'] = (isset($post['cod']) ? intval($post['cod']) : "");
$data['action'] = $post['action'];
$data['field_id'] = "";


$data['type'] = (isset($post['type']) ? noHtml($post['type']) : "");    
$data['manufacturer'] = (isset($post['manufacturer']) ? noHtml($post['manufacturer']) : "");
$data['model_full'] = (isset($post['model_full']) ? noHtml($post['model_full']) : "");

$data['motherboard'] = (isset($post['motherboard']) ? noHtml($post['motherboard']) : "");
$data['processor'] = (isset($post['processor']) ? noHtml($post['processor']) : "");
$data['memory'] = (isset($post['memory']) ? noHtml($post['memory']) : "");
$data['video'] = (isset($post['video']) ? noHtml($post['video']) : "");
$data['sound'] = (isset($post['sound']) ? noHtml($post['sound']) : "");
$data['network'] = (isset($post['network']) ? noHtml($post['network']) : "");
$data['modem'] = (isset($post['modem']) ? noHtml($post['modem']) : "");
$data['hdd'] = (isset($post['hdd']) ? noHtml($post['hdd']) : "");
$data['recorder'] = (isset($post['recorder']) ? noHtml($post['recorder']) : "");
$data['cdrom'] = (isset($post['cdrom']) ? noHtml($post['cdrom']) : "");
$data['dvdrom'] = (isset($post['dvdrom']) ? noHtml($post['dvdrom']) : "");

$data['printer_type'] = (isset($post['printer_type']) ? noHtml($post['printer_type']) : "");
$data['monitor_size'] = (isset($post['monitor_size']) ? noHtml($post['monitor_size']) : "");
$data['scanner_resolution'] = (isset($post['scanner_resolution']) ? noHtml($post['scanner_resolution']) : "");




/* Checagem de preenchimento dos campos obrigatórios*/
if ($data['action'] == "new" || $data['action'] == "edit") {

    if ($data['type'] == "") {
        $data['success'] = false; 
        $data['field_id'] = "type";
    } elseif ($data['manufacturer'] == "") {
        $data['success'] = false; 
        $data['field_id'] = "manufacturer";
    } elseif ($data['model_full'] == "") {
        $data['success'] = false; 
        $data['field_id'] = "model_full";
    }
    

    if ($data['success'] == false) {
        $data['message'] = message('warning', '', TRANS('MSG_EMPTY_DATA'), '');
        echo json_encode($data);
        return false;
    }


    /* Checagem se já existe configuração cadastrada para esse modelo */
    // $terms = ($data['action'] == "edit" ? " AND mold_cod <> '" . $data['cod'] . "' " : "");
    $terms = (!empty($data['cod']) ? " AND mold_cod <> '" . $data['cod'] . "' " : "");
    $sql = "SELECT mold_cod FROM moldes WHERE 
            mold_marca = '" . $data['model_full'] . "' 
            {$terms}
    ";
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $data['success'] = false; 
        $data['field_id'] = "asset_tag";
        $data['message'] = message('warning', '', TRANS('MSG_CONFIG_EXISTS_TO_THIS_MODEL'), '');
        echo json_encode($data);
        return false;
    }

}



/* Processamento */
if ($data['action'] == "new") {

    /* Verificação de CSRF */
    if (!csrf_verify($post)) {
        $data['success'] = false; 
        $data['message'] = message('warning', 'Ooops!', TRANS('FORM_ALREADY_SENT'),'');
        echo json_encode($data);
        return false;
    }


	$sql = "INSERT INTO moldes 
        (
            mold_tipo_equip, mold_fab, mold_marca, 
            mold_mb, mold_proc, mold_memo, mold_video, mold_som, 
            mold_rede, mold_modem, mold_modelohd, mold_grav, mold_cdrom, mold_dvd
        )
		VALUES 
        (
            '" . $data['type'] . "', 
            '" . $data['manufacturer'] . "', 
            '" . $data['model_full'] . "', 
            " . dbField($data['motherboard'], 'int') . ", 
            " . dbField($data['processor'], 'int') . ", 
            " . dbField($data['memory'], 'int') . ", 
            " . dbField($data['video'], 'int') . ", 
            " . dbField($data['sound'], 'int') . ", 
            " . dbField($data['network'], 'int') . ", 
            " . dbField($data['modem'], 'int') . ", 
            " . dbField($data['hdd'], 'int') . ", 
            " . dbField($data['recorder'], 'int') . ", 
            " . dbField($data['cdrom'], 'int') . ", 
            " . dbField($data['dvdrom'], 'int') . "
        )";
		
    try {
        $conn->exec($sql);
        $data['success'] = true; 
        // $data['equipment_id'] = $conn->lastInsertId();
        $data['message'] = TRANS('MSG_SUCCESS_INSERT') . $exception;
        
    } catch (Exception $e) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_SAVE_RECORD') . $exception;
        $_SESSION['flash'] = message('danger', '', $data['message'], '');
        echo json_encode($data);
        return false;
    }

} elseif ($data['action'] == 'edit') {

    if (!csrf_verify($post)) {
        $data['success'] = false; 
        $data['message'] = message('warning', 'Ooops!', TRANS('FORM_ALREADY_SENT'),'');
    
        echo json_encode($data);
        return false;
    }

    $sql = "UPDATE moldes SET 

                mold_marca = '" . $data['model_full'] . "', 
                mold_tipo_equip = '" . $data['type'] . "', 
                mold_fab = '" . $data['manufacturer'] . "', 
                mold_mb = " . dbField($data['motherboard'], 'int') . ", 
                mold_proc = " . dbField($data['processor'], 'int') . ", 
                mold_memo = " . dbField($data['memory'], 'int') . ", 
                mold_video = " . dbField($data['video'], 'int') . ", 
                mold_som = " . dbField($data['sound'], 'int') . ", 
                mold_rede = " . dbField($data['network'], 'int') . ", 
                mold_modelohd = " . dbField($data['hdd'], 'int') . ", 
                mold_modem = " . dbField($data['modem'], 'int') . ", 
                mold_cdrom = " . dbField($data['cdrom'], 'int') . ", 
                mold_dvd = " . dbField($data['dvdrom'], 'int') . ", 
                mold_grav = " . dbField($data['recorder'], 'int') . " 
            WHERE 
                mold_cod = '" . $data['cod'] . "'";
    try {
        $conn->exec($sql);

        $data['success'] = true; 

        $data['message'] = TRANS('MSG_SUCCESS_EDIT') . $exception;

    } catch (Exception $e) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_DATA_UPDATE') . "<br />". $sql . "<br />" . $e->getMessage();
        $_SESSION['flash'] = message('danger', 'Ooops!', $data['message'], '');
        echo json_encode($data);
        return false;
    }
} elseif ($data['action'] == 'delete') {


    /* Confere se há impedimentos para excluir o registro */

    /* Preciso verificar os impedimentos */
    // $sql = "SELECT * FROM {$table} WHERE eqp_piece_id = '" . $data['cod'] . "' ";
    // $res = $conn->query($sql);
    // if ($res->rowCount()) {
    //     $data['success'] = false; 
    //     $data['message'] = TRANS('MSG_CANT_DEL');
    //     $_SESSION['flash'] = message('danger', '', $data['message'] . $exception, '');
    //     echo json_encode($data);
    //     return false;
    // }

    /* Sem restrições para excluir o registro */
    $sql = "DELETE FROM moldes WHERE mold_marca = '" . $data['cod'] . "'";

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