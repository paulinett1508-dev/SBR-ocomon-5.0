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


$categories = getAssetsCategories($conn);

$data = $categories;


echo json_encode($data);

?>
