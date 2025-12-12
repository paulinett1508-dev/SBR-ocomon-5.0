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


$data['child_model'] = (isset($post['child_model']) ? noHtml($post['child_model']) : "");
$data['parent_id'] = (isset($post['parent_id']) ? noHtml($post['parent_id']) : "");

if (empty($data['child_model']) || empty($data['parent_id'])) {
    return json_encode([]);
}

$model = getAssetsModels($conn, $data['child_model']);

$data['asset_type'] = $model['tipo'];
$data['manufacturer'] = $model['fabricante'];
$data['model'] = $model['modelo'];
$data['cod_asset_type'] = $model['tipo_cod'];
$data['cod_manufacturer'] = $model['fabricante_cod'];

$data['free_to_link'] = isAssetModelFreeToLink($conn, $data['child_model']);


echo json_encode($data);