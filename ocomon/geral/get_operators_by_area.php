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

$area = (isset($post['area']) && !empty($post['area']) && ($post['area']!= "-1") && filter_var($post['area'], FILTER_VALIDATE_INT) ? $post['area'] : null);


// if (empty($area)) {
//     echo json_encode([]);
//     return;
// }

// $users = getUsersByPrimaryArea($conn, $area, [1,2]);
$users = getUsersByArea($conn, $area, true, null, true);

if (empty($users)) {
    echo json_encode([]);
    return;
}

// echo json_encode(['area' => $area]);

$data = array();
foreach ($users as $user) {
    $data[] = $user;
}

// var_dump($data); exit;

echo json_encode($data);

?>
