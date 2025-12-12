<?php
session_start();
require_once (__DIR__ . "/" . "../../includes/include_basics_only.php");
require_once (__DIR__ . "/" . "../../includes/classes/ConnectPDO.php");
use includes\classes\ConnectPDO;

if ($_SESSION['s_logado'] != 1 || ($_SESSION['s_nivel'] > 3)) {
    return;
}

$conn = ConnectPDO::getInstance();

$post = $_POST;

if (!isset($post['prob_id']) || empty($post['prob_id'])) {
    echo json_encode(['description' => '']);
    return;
}

$script = "";
$issue = issueDescription($conn, $post['prob_id']);

$hasScript = issueHasScript($conn, $post['prob_id']);
$enduser = issueHasEnduserScript($conn, $post['prob_id']);

if (($_SESSION['s_nivel'] < 3 && $hasScript) || ($enduser)) {
    $script = "<hr><p class='text-success'><a onClick=\"popup('../../admin/geral/scripts_documentation.php?action=endview&prob=".$post['prob_id']."')\"><br /><i class='far fa-hand-point-right'></i>&nbsp;".TRANS('TIPS')."</a></p>";
}


// if ($hasScript) {
//     $script = "<hr><p class='text-success'><a onClick=\"popup('../../admin/geral/scripts_documentation.php?action=endview&prob=".$post['prob_id']."')\"><br /><i class='far fa-hand-point-right'></i>&nbsp;".TRANS('TIPS')."</a></p>";
// }



if (empty($issue) && !$hasScript) {
    echo json_encode(['description' => '']);
    return;
}

$message = $issue . $script;
$message = message('info', TRANS('TYPE_OF_ISSUE_INDICATED_TO'), $message, '', '', true, 'far fa-lightbulb');

$data = array();

$data['description'] = $message;

echo json_encode($data);

?>
