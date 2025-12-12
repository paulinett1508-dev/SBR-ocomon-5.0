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

$data['issue_type'] = (isset($post['issue_type']) ? noHtml($post['issue_type']) : "");
$data['params'] = (isset($post['params']) ? noHtml($post['params']) : "");
$data['profile_id'] = "";
$data['prob_descricao'] = "";


/* Validações */

if (empty($data['issue_type'])) {
    $data['success'] = false; 
    $data['field_id'] = "issue_type";
    $data['message'] = message('warning', 'Ooops!', TRANS('MSG_EMPTY_DATA'),'');
    echo json_encode($data);
    return false;
}


$script = "";
$hasScript = issueHasScript($conn, $data['issue_type']);
$enduser = issueHasEnduserScript($conn, $data['issue_type']);

if (($_SESSION['s_nivel'] < 3 && $hasScript) || ($enduser)) {
    $script = "<hr><p class='text-success'><a onClick=\"popup('../../admin/geral/scripts_documentation.php?action=endview&prob=".$data['issue_type']."')\"><br /><i class='far fa-hand-point-right'></i>&nbsp;".TRANS('TIPS')."</a></p>";
}



$sql = "SELECT prob_descricao, prob_profile_form FROM problemas WHERE prob_id = '" . $data['issue_type'] . "' ";
try {
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        
        $row = $res->fetch();
        
        $data['profile_id'] = ($row['prob_profile_form'] ?? getDefaultScreenProfile($conn));
        $data['prob_descricao'] = $row['prob_descricao'];

        if (!empty($row['prob_descricao'])) {
            $data['description'] = message('info', TRANS('TYPE_OF_ISSUE_INDICATED_TO'), $row['prob_descricao'] . $script, '', '', true, 'far fa-lightbulb');
        }
        
    }
}
catch (Exception $e) {
    $exception .= "<hr>" . $e->getMessage();
}



echo json_encode($data);
