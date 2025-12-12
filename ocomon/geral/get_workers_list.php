<?php
session_start();
require_once (__DIR__ . "/" . "../../includes/include_basics_only.php");
require_once (__DIR__ . "/" . "../../includes/classes/ConnectPDO.php");
use includes\classes\ConnectPDO;

if ($_SESSION['s_logado'] != 1 || ($_SESSION['s_nivel'] != 1 && $_SESSION['s_nivel'] != 2)) {
    exit;
}
/* Funcionários não tem acesso */
if (!$_SESSION['s_can_route']) {
    exit;
}
$conn = ConnectPDO::getInstance();

$post = $_POST;


$dataPost['area'] = (isset($post['area']) && $post['area'] != "-1" ? $post['area'] : "");

$dataPost['main_work_setted'] = (isset($post['main_work_setted']) ? $post['main_work_setted'] : "");
$dataPost['fromMenu'] = (isset($post['fromMenu']) && $post['fromMenu'] == 1 ? 1 : 0);

/**
 * Se a chamada for feita por meio do menu, avaliar o nível do usuário: 
 * Se for nível 1, carregar todos os usuários de nível de operação e que podem receber encaminhamentos de outros usuários
 * Se for nível 2, carregar apenas os usuários pertencentes às mesmas áreas que o usuário logado e que também possam receber
 * encaminhamentos de outros usuários
 */
$logged_areas = explode(',', $_SESSION['s_uareas']);

if ($dataPost['fromMenu']) {
    if ($_SESSION['s_nivel'] == 1) {
        // $users = getUsers($conn, null, [1,2], null, true);
        $users = getUsersBySetOfAreas($conn, [], false, null, true, [1,2]);
    } else {
        // $users = getUsers($conn, null, [1,2], null, true, $logged_areas);
        $users = getUsersBySetOfAreas($conn, $logged_areas, false, null, true, [1,2]);
    }
} elseif (!empty($dataPost['area'])) {
    // $users = getUsersByArea($conn, $dataPost['area'], true, null, true);
    $users = getUsersBySetOfAreas($conn, [$dataPost['area']], false, null, true, [1,2]);

} else {
    // $users = getUsers($conn, null, [1,2], null, true, $logged_areas);
    $users = getUsersBySetOfAreas($conn, $logged_areas, false, null, true, [1,2]);
}

$data = array();

$i = 0;
foreach ($users as $row) {
    
    if (empty($dataPost['main_work_setted'])) {

        $data[$i]['user_id'] = $row['user_id'];
        $data[$i]['nome'] = $row['nome'];
        $data[$i]['textcolor'] = $row['user_textcolor'] ?? '#FFFFFF';
        $data[$i]['bgcolor'] = $row['user_bgcolor'] ?? '#2B414B';
    
    } elseif ($dataPost['main_work_setted'] != $row['user_id']) {

        $data[$i]['user_id'] = $row['user_id'];
        $data[$i]['nome'] = $row['nome'];
        $data[$i]['textcolor'] = $row['user_textcolor'] ?? '#FFFFFF';
        $data[$i]['bgcolor'] = $row['user_bgcolor'] ?? '#2B414B';
    }
    $i++;
}

echo json_encode($data);

?>
