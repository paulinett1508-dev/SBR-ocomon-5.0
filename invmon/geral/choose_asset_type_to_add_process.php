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
$data['field_id'] = "";

$data['asset_type'] = (isset($post['asset_type']) ? noHtml($post['asset_type']) : "");
$data['asset_manufacturer'] = (isset($post['asset_manufacturer']) ? noHtml($post['asset_manufacturer']) : "");
$data['asset_model'] = (isset($post['asset_model']) ? noHtml($post['asset_model']) : "");
$data['parent_id'] = (isset($post['parent_id']) ? noHtml($post['parent_id']) : "");
$data['load_saved_config'] = (isset($post['load_saved_config']) ? noHtml($post['load_saved_config']) : 0);
$data['profile_id'] = "";

$data['category_profile_id'] = "";

/* Validações */
if (empty($data['asset_type']) || empty($data['asset_manufacturer']) || empty($data['asset_model'])) {
    $data['success'] = false; 
    $data['field_id'] = (empty($data['asset_type']) ? "asset_type" : (empty($data['asset_manufacturer']) ? "asset_manufacturer" : "asset_model"));
    $data['message'] = message('warning', 'Ooops!', TRANS('MSG_EMPTY_DATA'),'');
    echo json_encode($data);
    return false;
}








/* Traz as informações relacionadas ao tipo: categoria|perfil de cadastro|etc */
$asset_type = getAssetsTypes($conn, $data['asset_type']);

$data['profile_id'] = $asset_type['profile_id'];
$data['category_id'] = $asset_type['id'];

if (empty($data['profile_id'])) {
    /* Se o tipo de ativo não tiver perfil de cadastro associado então utilizo o perfil de cadastro da categoria do ativo - se tiver perfil */
    $categorie = getAssetsCategories($conn, $data['category_id']);

    if (!empty($categorie)) {
        $data['profile_id'] = (array_key_exists('cat_default_profile', $categorie) ? $categorie['cat_default_profile'] : "");
    }
}

if (empty($data['profile_id'])) {
    /* Não tem perfil associado ao tipo de ativo e nem perfil associado à categoria do ativo */
    $data['profile_id'] = 0;
}

echo json_encode($data);
