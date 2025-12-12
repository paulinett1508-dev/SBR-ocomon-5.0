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

$unit = (isset($post['unit']) && !empty($post['unit']) && filter_var($post['unit'], FILTER_VALIDATE_INT) ? $post['unit'] : "");
$tag = (isset($post['tag']) && !empty($post['tag']) && filter_var($post['tag'], FILTER_VALIDATE_INT) ? $post['tag'] : "");

if (empty($unit) || empty($tag)) {
    echo json_encode(['department' => '']);
    return;
}

$department = getDepartmentByUnitAndTag($conn, $unit, $tag);

if (empty($department)) {
    echo json_encode(['department' => '']);
    return;
}

echo json_encode(['department' => $department]);

// $data = array();
// foreach ($issues as $issue) {
//     $data[] = $issue;
// }
// echo json_encode($data);

?>
