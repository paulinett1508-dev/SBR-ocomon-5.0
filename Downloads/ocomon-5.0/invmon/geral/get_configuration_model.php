<?php
session_start();
require_once (__DIR__ . "/" . "../../includes/include_basics_only.php");
require_once (__DIR__ . "/" . "../../includes/classes/ConnectPDO.php");
use includes\classes\ConnectPDO;

if ($_SESSION['s_logado'] != 1 || ($_SESSION['s_nivel'] != 1 && $_SESSION['s_nivel'] != 2)) {
    return;
}

$conn = ConnectPDO::getInstance();

$post = $_POST;
$data = array();

$model_id = (isset($post['model_id']) && !empty($post['model_id']) && $post['model_id'] != '-1' ? $post['model_id'] : "");

if (empty($model_id)) {
    $data['success'] = false; 
    $data['message'] = message('info', 'Ooops!', TRANS('MSG_ERROR_LOAD_CONFIG_MODEL'), '');
    echo json_encode($data);
    return;
}

$sql = "SELECT * FROM moldes WHERE mold_marca = '{$model_id}' ";

try {
    $res = $conn->query($sql);
}
catch (Exception $e) {
    $data['exception'] .= "<hr>" . $e->getMessage();
    $data['success'] = false; 
    $data['message'] = message('warning', 'Ooops!', $data['exception'], '');
    echo json_encode($data);
    return;
}

if (!$res->rowCount()) {
    $data['success'] = false; 
    $data['message'] = message('info', 'Ooops!', TRANS('MSG_ERROR_LOAD_CONFIG_MODEL'), '');
    echo json_encode($data);
    return;
}

$row = $res->fetch();
$data = $row;

$data['success'] = true; 
$data['message'] = ""; 

echo json_encode($data);

?>
