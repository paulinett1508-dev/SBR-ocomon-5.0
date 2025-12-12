<?php
session_start();
require_once (__DIR__ . "/" . "../../includes/include_basics_only.php");
require_once (__DIR__ . "/" . "../../includes/classes/ConnectPDO.php");
use includes\classes\ConnectPDO;

if ($_SESSION['s_logado'] != 1 || ($_SESSION['s_nivel'] != 1 && $_SESSION['s_nivel'] != 2)) {
    exit;
}

$conn = ConnectPDO::getInstance();

$post = $_POST;


$isAdmin = $_SESSION['s_nivel'] == 1;
$logged_areas = explode(',', $_SESSION['s_uareas']);

$dataPost['area'] = (isset($post['area']) && $post['area'] != "-1" ? $post['area'] : "");


if ($isAdmin) {
    /* Todos os operadores */
    $users = getUsersBySetOfAreas($conn, [], false, null, null, [1,2]);
} elseif (!empty($dataPost['area'])) {
    /* Operadores da área específica */
    $users = getUsersBySetOfAreas($conn, [$dataPost['area']], false, null, null, [1,2]);
} else {
    /* Todos os operadores das áreas que o usuário logado faz parte */
    $users = getUsersBySetOfAreas($conn, $logged_areas, false, null, null, [1,2]);
}


$data = array();

$i = 0;
foreach ($users as $row) {
    
    $data[$i]['user_id'] = $row['user_id'];
    $data[$i]['nome'] = $row['nome'];
    $data[$i]['textcolor'] = $row['user_textcolor'] ?? '#FFFFFF';
    $data[$i]['bgcolor'] = $row['user_bgcolor'] ?? '#2B414B';

    $i++;
}

echo json_encode($data);

?>
