<?php
session_start();
require_once (__DIR__ . "/" . "../../includes/include_basics_only.php");
require_once (__DIR__ . "/" . "../../includes/classes/ConnectPDO.php");
use includes\classes\ConnectPDO;

if ($_SESSION['s_logado'] != 1 || ($_SESSION['s_nivel'] != 1)) {
    return;
}

$conn = ConnectPDO::getInstance();

$post = $_POST;
$data = array();

$allowedCategories = [1,2,3];

if (!in_array($post['cat_type'], $allowedCategories)) {
    return;
}

$catSufix = $post['cat_type'];

$sql = "SELECT * FROM prob_tipo_{$catSufix} ORDER BY probt{$catSufix}_desc";
$res = $conn->query($sql);

foreach ($res->fetchAll() as $row) {
    $data[] = $row;
}

echo json_encode($data);

?>
