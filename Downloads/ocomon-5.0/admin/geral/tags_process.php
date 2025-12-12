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
// $data['cod'] = (isset($post['cod']) ? intval($post['cod']) : "");
$data['cod'] = (isset($post['cod']) ? noHtml($post['cod']) : "");
$data['action'] = $post['action'];
$data['field_id'] = "";

$data['tag_name'] = (isset($post['tag_name']) && !empty($post['tag_name']) ? noHtml($post['tag_name']) : "");

$data['old_tag'] = (!empty($data['cod']) ? getTagsList($conn, $data['cod'])['tag_name'] : "");


/* Validações */
if ($data['action'] == "new" || $data['action'] == "edit") {

    if (empty($data['tag_name'])) {
        $data['success'] = false; 
        $data['field_id'] = 'tag_name';
        $data['message'] = message('warning', 'Ooops!', TRANS('MSG_EMPTY_DATA'),'');
        echo json_encode($data);
        return false;
    }

    if (strlen(trim((string)$data['tag_name'])) < 4) {
        $data['success'] = false; 
        $data['field_id'] = 'tag_name';
        $data['message'] = message('warning', 'Ooops!', TRANS('ERROR_MIN_SIZE_OF_TAGNAME'),'');
        echo json_encode($data);
        return false;
    }
   
}


if ($data['action'] == 'new') {


    $sql = "SELECT tag_name FROM input_tags WHERE trim(tag_name) = '" . trim($data['tag_name']) . "' ";
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

    $sql = "INSERT INTO input_tags (tag_name) VALUES 
                ('" . trim($data['tag_name']) . "')";

    try {
        $conn->exec($sql);
        $data['success'] = true; 
        $data['message'] = TRANS('MSG_SUCCESS_INSERT');
        $_SESSION['flash'] = message('success', '', $data['message'], '');
        echo json_encode($data);
        return false;
    } catch (Exception $e) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_SAVE_RECORD');
        $_SESSION['flash'] = message('danger', '', $data['message'], '');
        echo json_encode($data);
        return false;
    }

} elseif ($data['action'] == 'edit') {

    // $oldTag = trim($data['cod']);
    $oldTag = trim($data['old_tag']);
    $newTag = trim($data['tag_name']);

    $sql = "SELECT tag_name FROM input_tags WHERE trim(tag_name) = '" . trim($data['tag_name']) . "' AND trim(tag_name) <> '" . trim($data['cod']) . "' ";
    
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $data['success'] = false; 
        $data['field_id'] = "tag_name";
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

    $sql = "UPDATE input_tags SET 
                tag_name = '" . trim($data['tag_name']) . "'
            WHERE id = '" . trim($data['cod']) . "' ";

    try {
        $conn->exec($sql);
        $data['success'] = true; 
        $data['message'] = TRANS('MSG_SUCCESS_EDIT');
        
        
        /* ATualizar as ocorrências que possuem a tag editada */
        $sqlUpdate = "UPDATE ocorrencias 
                        SET oco_tag = REPLACE(oco_tag, '{$oldTag}', '{$newTag}') 
                        WHERE MATCH(oco_tag) AGAINST('\"{$oldTag}\"' IN BOOLEAN MODE)";

        try {
            $conn->exec($sqlUpdate);
        }
        catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage();
        }
        
        $_SESSION['flash'] = message('success', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    } catch (Exception $e) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_DATA_UPDATE');
        $_SESSION['flash'] = message('danger', '', $data['message'], '');
        echo json_encode($data);
        return false;
    }

} elseif ($data['action'] == 'delete') {


    // $tag = $data['cod'];
    $tag = $data['old_tag'];
    $sql = "SELECT * FROM ocorrencias WHERE MATCH(oco_tag) AGAINST('\"{$tag}\"' IN BOOLEAN MODE)  ";
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_CANT_DEL');
        $_SESSION['flash'] = message('danger', '', $data['message'], '');
        echo json_encode($data);
        return false;
    }

    // $sql = "DELETE FROM input_tags WHERE tag_name = '" . $data['cod'] . "'";
    $sql = "DELETE FROM input_tags WHERE id = '" . $data['cod'] . "'";

    try {
        $conn->exec($sql);
        $data['success'] = true; 
        $data['message'] = TRANS('OK_DEL');
        $_SESSION['flash'] = message('success', '', $data['message'], '');
        echo json_encode($data);
        return false;
    } catch (Exception $e) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_DATA_REMOVE');
        $_SESSION['flash'] = message('danger', '', $data['message'], '');
        echo json_encode($data);
        return false;
    }
    
}

echo json_encode($data);