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

$data = [];
$data['success'] = true;
$data['message'] = "";
$exception = "";


$data['child_id'] = (isset($post['child_id']) ? noHtml($post['child_id']) : "");
$data['parent_id'] = (isset($post['parent_id']) ? noHtml($post['parent_id']) : "");
$data['child_new_department'] = (isset($post['child_new_department']) ? noHtml($post['child_new_department']) : "");
$data['remove_specification'] = (isset($post['remove_specification']) && !empty($post['remove_specification']) ? $post['remove_specification'] : 'false');

if (empty($data['child_id']) || empty($data['child_new_department'])) {
    
    $data['success'] = false;
    $data['message'] = message('warning', '', TRANS('MSG_EMPTY_DATA'), '', '');
    $data['field_id'] = 'child_new_department';
    echo json_encode($data);
    return false;
    
}


/* Removo a especificação ou apenas o vínculo com o ativo registrado */
if ($data['remove_specification'] == 'true') {
    $sql = "DELETE 
            FROM 
                assets_x_specs 
            WHERE 
                asset_spec_tagged_id = :child_id";
} else {
    $sql = "UPDATE assets_x_specs 
        SET
            asset_spec_tagged_id = NULL
        WHERE 
            asset_spec_tagged_id = :child_id";
}

try {
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':child_id', $data['child_id']);
    $stmt->execute();

    if ($data['remove_specification'] == 'true') {
        insertNewAssetSpecChange($conn, $data['parent_id'], $data['child_id'], 'remove', $_SESSION['s_uid']);
    }


    /* Alteração do departmento do ativo filho  */
    $sql = "UPDATE
                equipamentos
            SET
                comp_local = :child_new_department
            WHERE
                comp_cod = :child_id
            ";
    try {
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':child_id', $data['child_id']);
        $stmt->bindParam(':child_new_department', $data['child_new_department']);
        $stmt->execute();
    } catch (Exception $e) {
        $exception .= "<hr />" . TRANS('ERROR_UPDATING_CHILD_DEPARTMENT');
        $exception .= "<hr />" . $e->getMessage();
    }


    /* Desenvolver processo de armazenamento do histórico de mudança de localização */


} catch (PDOException $e) {
    $data['success'] = false;
    $data['message'] = message('danger', 'Ooops', $e->getMessage(), '', '');
    echo json_encode($data);
    return false;
}



$data['message'] = TRANS('CHILD_SUCCESS_UNLINKED');
$_SESSION['flash'] = message('success', '', $data['message'] . $exception, '');
echo json_encode($data);
return false;