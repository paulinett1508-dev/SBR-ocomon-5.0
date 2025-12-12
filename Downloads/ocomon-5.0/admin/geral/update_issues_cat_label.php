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

$config = getConfig($conn);



$exception = "";
$data = [];
$data['success'] = true;
$data['message'] = "";
$data['cod'] = (isset($post['cod']) ? intval($post['cod']) : "");
$data['action'] = $post['action'];
$data['field_id'] = "";

$data['cat_table'] = (isset($post['cat_table']) ? noHtml($post['cat_table']) : "");
$data['cat_label'] = (isset($post['cat_label']) ? noHtml($post['cat_label']) : "");

$data['current_label'] = $config['conf_' . $data['cat_table']];

$cat1 = $config['conf_prob_tipo_1'];
$cat2 = $config['conf_prob_tipo_2'];
$cat3 = $config['conf_prob_tipo_3'];

$terms = "";

if ($data['cat_table'] == "prob_tipo_1") {
    $terms = "WHERE conf_prob_tipo_2 <> '" . $data['cat_label'] . "' AND conf_prob_tipo_3 <> '" . $data['cat_label'] . "' ";
} elseif ($data['cat_table'] == "prob_tipo_2") {
    $terms = "WHERE conf_prob_tipo_1 <> '" . $data['cat_label'] . "' AND conf_prob_tipo_3 <> '" . $data['cat_label'] . "' ";
} elseif ($data['cat_table'] == "prob_tipo_3") {
    $terms = "WHERE conf_prob_tipo_1 <> '" . $data['cat_label'] . "' AND conf_prob_tipo_2 <> '" . $data['cat_label'] . "' ";
}


/* Validações */
if ($data['action'] == "edit") {

    if (empty($data['cat_label'])) {
        $data['success'] = false; 
        $data['field_id'] = 'cat_label';
    }

    if (!$data['success']) {
        $data['message'] = message('warning', 'Ooops!', TRANS('MSG_EMPTY_DATA'),'');
        echo json_encode($data);
        return false;
    }
}


if ($data['action'] == 'edit') {


    $sql = "SELECT * FROM config {$terms}";
    try {
        $res = $conn->query($sql);
        if (!$res->rowCount()) {
            $data['success'] = false; 
            $data['field_id'] = 'cat_label';
            $data['message'] = message('warning', 'Ooops!', TRANS('MSG_RECORD_EXISTS'),'');
            echo json_encode($data);
            return false;
        }
    }
    catch (Exception $e) {
        $exception .= "<hr>" . $e->getMessage();
    }



    $sql = "UPDATE config 
            SET 
                conf_" . $data['cat_table'] . " = '" . $data['cat_label'] . "'
            ";
    try {
        $conn->exec($sql);
    }
    catch (Exception $e) {
        $exception .= "<hr>" . $e->getMessage();

        $data['success'] = false;
        $data['message'] = message('warning', '', TRANS('MSG_SOMETHING_GOT_WRONG') . $exception, '');
        echo json_encode($data);
        return false;
    }

}

// $_SESSION['flash'] = message('success', '', TRANS('MSG_SUCCESS_EDIT') . $exception, '', '');
$data['message'] = message('success', '', TRANS('MSG_SUCCESS_EDIT') . $exception, '', '');
echo json_encode($data);
return true;