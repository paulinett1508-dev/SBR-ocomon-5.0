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

$data['cat_name'] = (isset($post['cat_name']) ? noHtml($post['cat_name']) : "");
$data['cat_description'] = (isset($post['cat_description']) ? noHtml($post['cat_description']) : "");
$data['cat_default_profile'] = (isset($post['cat_default_profile']) ? noHtml($post['cat_default_profile']) : "");
$data['is_digital'] = (isset($post['is_digital']) ? ($post['is_digital'] == "yes" ? 1 : 0) : 0);
$data['is_product'] = (isset($post['is_product']) ? ($post['is_product'] == "yes" ? 1 : 0) : 0);
$data['bgcolor'] = (isset($post['bgcolor']) && !empty($post['bgcolor']) ? noHtml($post['bgcolor']) : '#3A4D56');
$data['textcolor'] = (isset($post['textcolor']) && !empty($post['textcolor']) ? noHtml($post['textcolor']) : '#FFFFFF');


/* Validações */
if ($data['action'] == "new" || $data['action'] == "edit") {

    if (empty($data['cat_name'])) {
        $data['success'] = false; 
        $data['field_id'] = "cat_name";
    }

    if ($data['success'] == false) {
        $data['message'] = message('warning', 'Ooops!', TRANS('MSG_EMPTY_DATA'),'');
        echo json_encode($data);
        return false;
    }
}


if ($data['action'] == 'new') {

    /* verifica se um registro com esse nome já existe */
    $sql = "SELECT id FROM assets_categories WHERE cat_name = '" . $data['cat_name'] . "' ";
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $data['success'] = false; 
        $data['field_id'] = "cat_name";
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

    $sql = "INSERT INTO assets_categories 
        (
            cat_name, 
            cat_description,
            cat_default_profile,
            cat_is_digital,
            cat_is_product,
            cat_bgcolor,
            cat_textcolor
        ) 
        VALUES 
        (
            '" . $data['cat_name'] . "', 
            " . dbField($data['cat_description'], 'text') . ",  
            " . dbField($data['cat_default_profile']) . ", 
            {$data['is_digital']},
            {$data['is_product']},
            '{$data['bgcolor']}',
            '{$data['textcolor']}'
        )";

    try {
        $conn->exec($sql);
        $data['success'] = true; 
        $data['message'] = TRANS('MSG_SUCCESS_INSERT');

        $_SESSION['flash'] = message('success', '', $data['message'] . $exception, '');
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
    $sql = "SELECT id FROM assets_categories WHERE cat_name = '" . $data['cat_name'] . "' AND id <> '" . $data['cod'] . "'";
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $data['success'] = false; 
        $data['field_id'] = "cat_name";
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

    $sql = "UPDATE assets_categories SET 
				cat_name = '" . $data['cat_name'] . "', 
				cat_description = " . dbField($data['cat_description'], 'text') . ", 
				cat_default_profile = " . dbField($data['cat_default_profile']) . ", 
                cat_is_digital = {$data['is_digital']},
                cat_is_product = {$data['is_product']},
                cat_bgcolor = '{$data['bgcolor']}',
                cat_textcolor = '{$data['textcolor']}'
            WHERE id = '" . $data['cod'] . "'";

    try {
        $conn->exec($sql);
        $data['success'] = true; 
        $data['message'] = TRANS('MSG_SUCCESS_EDIT');

        $_SESSION['flash'] = message('success', '', $data['message'] . $exception, '');
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

    
    $sqlFindPrevention = "SELECT * FROM tipo_equip WHERE tipo_categoria = ".$data['cod']."";
    $resFindPrevention = $conn->query($sqlFindPrevention);
    $foundPrevention = $resFindPrevention->rowCount();

    if ($foundPrevention) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_CANT_DEL');
        $_SESSION['flash'] = message('danger', '', $data['message'], '');
        echo json_encode($data);
        return false;
    }
    

    $sql = "DELETE FROM assets_categories WHERE id = '" . $data['cod'] . "'";

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