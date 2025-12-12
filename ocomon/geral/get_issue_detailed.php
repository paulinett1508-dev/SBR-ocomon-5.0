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
$data = array();

if (!isset($post['issue_selected']) || empty($post['issue_selected'])) {
    echo json_encode([]);
    return;
}

$post['area'] = (isset($post['area']) && $post['area'] != '-1' ? $post['area'] : null);
$issues = getIssueDetailed($conn, $post['issue_selected'], $post['area']);

if (empty($issues)) {
    echo json_encode([]);
    return;
}

foreach ($issues as $issue) {
    $data[] = $issue;
}

// $data[]['novo'] = ""

echo json_encode($data);

?>
