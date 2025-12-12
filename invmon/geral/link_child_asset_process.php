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


$data['child_model'] = (isset($post['child_model']) ? noHtml($post['child_model']) : "");
$data['parent_id'] = (isset($post['parent_id']) ? noHtml($post['parent_id']) : "");
$data['child_tag'] = (isset($post['child_tag']) ? noHtml($post['child_tag']) : "");

if (empty($data['child_model']) || empty($data['parent_id']) || empty($data['child_tag'])) {
    
    $data['success'] = false;
    $data['message'] = message('warning', '', TRANS('MSG_EMPTY_DATA'), '', '');
    $data['field_id'] = 'child_tag';
    echo json_encode($data);
    return false;
    
}

$parent_info = getEquipmentInfo($conn, null, null, $data['parent_id']);

$child_info = getEquipmentInfo($conn, $parent_info['comp_inst'], $data['child_tag']);

/* Primeiro chego se o ativo filho existe para a mesma unidade do ativo pai */
if (empty($child_info)) {
    $data['success'] = false;
    $data['message'] = message('warning', '', TRANS('MSG_ASSET_NOT_FOUND_IN_PARENT_UNIT'), '', '');
    $data['field_id'] = 'child_tag';
    echo json_encode($data);
    return false;
}

/* Checo se o modelo do ativo da etiqueta fornecida corresponde ao modelo da especificação informada */
if ($child_info['comp_marca'] != $data['child_model']) {
    $data['success'] = false;
    $data['message'] = message('warning', '', TRANS('MSG_TAG_NOT_TO_THE_MODEL'), '', '');
    $data['field_id'] = 'child_tag';
    echo json_encode($data);
    return false;
}

/* Checo se o ativo da etiqueta fornecida já não está vinculado a outro ativo */
$hasParent = assetHasParent($conn, $child_info['comp_cod']);
if ($hasParent) {
    $data['success'] = false;
    $data['message'] = message('warning', '', TRANS('ASSET_HAS_PARENT'), '', '');
    $data['field_id'] = 'child_tag';
    echo json_encode($data);
    return false;
}


/* Não existindo nenhum restrição, realizo a vinculação */
$sql = "UPDATE assets_x_specs 
        SET
            asset_spec_tagged_id = :child_id
        WHERE 
            asset_id = :parent_id AND 
            asset_spec_id = :child_model AND
            asset_spec_tagged_id IS NULL
            ";
try {
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':child_id', $child_info['comp_cod']);
    $stmt->bindParam(':parent_id', $data['parent_id']);
    $stmt->bindParam(':child_model', $data['child_model']);
    $stmt->execute();


    /* Alteração do departmento do ativo filho - agora deve ser o mesmo do ativo pai */
    $sql = "UPDATE
                equipamentos
            SET
                comp_local = :parent_department
            WHERE
                comp_cod = :child_id
            ";
    try {
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':child_id', $child_info['comp_cod']);
        $stmt->bindParam(':parent_department', $parent_info['comp_local']);
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



$data['message'] = TRANS('CHILD_SUCCESS_LINKED');
$_SESSION['flash'] = message('success', '', $data['message'] . $exception, '');
echo json_encode($data);
return false;