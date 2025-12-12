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


$data['software_name'] = (isset($post['software_name']) ? noHtml($post['software_name']) : "");
$data['software_version'] = (isset($post['software_version']) ? noHtml($post['software_version']) : "");
$data['manufacture'] = (isset($post['manufacture']) ? noHtml($post['manufacture']) : "");
$data['category'] = (isset($post['category']) ? noHtml($post['category']) : "");
$data['license_type'] = (isset($post['license_type']) ? noHtml($post['license_type']) : "");
$data['amount'] = (isset($post['amount']) ? noHtml($post['amount']) : "");
$data['supplier'] = (isset($post['supplier']) ? noHtml($post['supplier']) : "");
$data['invoice_number'] = (isset($post['invoice_number']) ? noHtml($post['invoice_number']) : "");



/* Checagem de preenchimento dos campos obrigatórios*/
if ($data['action'] == "new" || $data['action'] == "edit") {

    if ($data['software_name'] == "") {
        $data['success'] = false; 
        $data['field_id'] = "software_name";
    } elseif ($data['software_version'] == "") {
        $data['success'] = false; 
        $data['field_id'] = "software_version";
    } elseif ($data['manufacture'] == "") {
        $data['success'] = false; 
        $data['field_id'] = "manufacture";
    } elseif ($data['category'] == "") {
        $data['success'] = false; 
        $data['field_id'] = "category";
    } elseif ($data['license_type'] == "") {
        $data['success'] = false; 
        $data['field_id'] = "license_type";
    }

    if ($data['success'] == false) {
        $data['message'] = message('warning', '', TRANS('MSG_EMPTY_DATA'), '');
        echo json_encode($data);
        return false;
    }

    if ($data['amount'] != "" && !filter_var($data['amount'], FILTER_VALIDATE_INT)) {
        $data['success'] = false; 
        $data['field_id'] = "amount";
        $data['message'] = message('warning', '', TRANS('MSG_ERROR_WRONG_FORMATTED'), '');
        echo json_encode($data);
        return false;
    }
}


/* Processamento */
if ($data['action'] == "new") {


    $sql = "SELECT soft_cod FROM softwares 
            WHERE soft_desc = '" . $data['software_name'] . "' AND soft_fab = " . $data['manufacture'] . "  
            AND soft_versao = '" . $data['software_version'] . "' 
    ";

    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $data['success'] = false; 
        $data['field_id'] = "software_name";
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

	$sql = "INSERT INTO softwares 
        (
            soft_desc, soft_versao, soft_fab, soft_cat, soft_tipo_lic, soft_qtd_lic, soft_forn, soft_nf
        )
		VALUES 
        (
            '" . $data['software_name'] . "', 
            '" . $data['software_version'] . "', 
            '" . $data['manufacture'] . "', 
            '" . $data['category'] . "', 
            '" . $data['license_type'] . "', 
            " . dbField($data['amount'],'int') . ", 
            " . dbField($data['supplier'],'int') . ", 
            " . dbField($data['invoice_number'],'text') . " 
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

} elseif ($data['action'] == 'edit') {

    $sql = "SELECT soft_cod FROM softwares 
            WHERE soft_desc = '" . $data['software_name'] . "' AND soft_fab = " . $data['manufacture'] . "  
            AND soft_versao = '" . $data['software_version'] . "' AND soft_cod <> '" . $data['cod'] . "'
    ";
    
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $data['success'] = false; 
        $data['field_id'] = "software_name";
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

    $sql = "UPDATE softwares SET 
    
                soft_desc = '" . $data['software_name'] . "', 
                soft_versao = '" . $data['software_version'] . "', 
                soft_fab = '" . $data['manufacture'] . "', 
                soft_cat = '" . $data['category'] . "', 
                soft_tipo_lic = '" . $data['license_type'] . "', 
                soft_qtd_lic = " . dbField($data['amount'], 'int') . ", 
                soft_forn = " . dbField($data['supplier'], 'int') . ", 
                soft_nf = " . dbField($data['invoice_number'], 'text') . " 
            WHERE 
                soft_cod = '" . $data['cod'] . "'";
            
    try {
        $conn->exec($sql);

        $data['success'] = true; 
        $data['message'] = TRANS('MSG_SUCCESS_EDIT');

    } catch (Exception $e) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_DATA_UPDATE') . "<br />". $sql . "<br />" . $e->getMessage();
        $_SESSION['flash'] = message('danger', 'Ooops!', $data['message'], '');
        echo json_encode($data);
        return false;
    }
} elseif ($data['action'] == 'delete') {


    /* Confere se há impedimentos para excluir o registro */
    $sql = "SELECT * FROM hw_sw WHERE hws_sw_cod = '" . $data['cod'] . "' ";
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_CANT_DEL');
        $_SESSION['flash'] = message('danger', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    }

    $sql = "SELECT * FROM sw_padrao WHERE swp_sw_cod = '" . $data['cod'] . "' ";
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_CANT_DEL');
        $_SESSION['flash'] = message('danger', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    }

    
    /* Sem restrições para excluir o registro */
    $sql = "DELETE FROM softwares WHERE soft_cod = '" . $data['cod'] . "'";

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