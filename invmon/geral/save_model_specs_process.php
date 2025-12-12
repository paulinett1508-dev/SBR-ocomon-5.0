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


$data['asset_id'] = (isset($post['asset_id']) ? noHtml($post['asset_id']) : "");
$data['config_scope'] = (isset($post['config_scope']) && $post['config_scope'] == 0 ? 0 : null);

if (empty($data['asset_id'])) {
    
    $data['success'] = false;
    $data['message'] = message('warning', '', TRANS('FETCH_ERROR'), '', '');
    echo json_encode($data);
    return false;
}

$asset_info = getEquipmentInfo($conn, null, null, $data['asset_id']);

if (empty($asset_info)) {
    $data['success'] = false;
    $data['message'] = message('warning', '', TRANS('FETCH_ERROR'), '', '');
    echo json_encode($data);
    return false;
}

$asset_model = $asset_info['comp_marca'];

/* Primeiro removo as configuracoes salvas previamente */
$sql = "DELETE FROM model_x_child_models WHERE model_id = :model_id";
try {
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':model_id', $asset_model, PDO::PARAM_INT);
    $stmt->execute();

    /* Então atualizo a nova configuração salva para o modelo */
    
    $asset_specs = getAssetSpecs($conn, $data['asset_id'], null, $data['config_scope']);
    if (empty($asset_specs)) {
        $data['success'] = false;
        $data['message'] = message('warning', '', TRANS('FETCH_ERROR'), '', '');
        echo json_encode($data);
        return false;
    }
    
    foreach ($asset_specs as $spec) {
        $sql = "INSERT INTO model_x_child_models (model_id, model_child_id) VALUES (:model_id, :model_child_id)";
        try {
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':model_id', $asset_model, PDO::PARAM_INT);
            $stmt->bindParam(':model_child_id', $spec['asset_spec_id'], PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {
            $data['success'] = false;
            $exception .= $e->getMessage();
        }
    }

    if (strlen((string)$exception) > 0) {
        $data['message'] = message('danger', 'Ooops', '<hr />' . TRANS('FETCH_ERROR') . $exception, '', '');
    } else {
        $data['message'] = message('success', 'Yeahh', '<hr />' . TRANS('MSG_SUCCESS_SAVE_SPECS_TO_MODEL'), '', '');
    }
    echo json_encode($data);
    return true;


} catch (PDOException $e) {
    $data['success'] = false;
    $data['message'] = message('warning', '', TRANS('FETCH_ERROR') . '<hr />' . $e->getMessage(), '', '');
    echo json_encode($data);
    return false;
}





