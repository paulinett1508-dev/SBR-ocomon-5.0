<?php
session_start();
require_once (__DIR__ . "/" . "../../includes/include_basics_only.php");
require_once (__DIR__ . "/" . "../../includes/classes/ConnectPDO.php");
use includes\classes\ConnectPDO;

// if ($_SESSION['s_logado'] != 1 || ($_SESSION['s_nivel'] != 1 && $_SESSION['s_nivel'] != 2)) {
//     exit;
// }
if ($_SESSION['s_logado'] != 1) {
    exit;
}

$conn = ConnectPDO::getInstance();

$isAdmin = $_SESSION['s_nivel'] == 1;
$isRequester = $_SESSION['s_nivel'] == 3;

$dateToday = date('Y-m-d');

$exceptions = "";
$user = $_SESSION['s_uid'];
$uareas = explode(',', $_SESSION['s_uareas']);
$today = date('Y-m-d');

$sent = false;
$notice_id = [];

$data = array();

foreach ($uareas as $uarea) {

    if ($isAdmin) {
        $terms = "";
    } elseif ($isRequester) {
        $terms = " AND ((FIND_IN_SET('{$uarea}', area) > 0) ) ";
    } else {
        $terms = " AND ((FIND_IN_SET('{$uarea}', area) > 0) OR area = -1 OR area IS NULL) ";
    }

    $sql = "SELECT *, DATE_FORMAT(`data`, '%d/%m/%Y %T') as formatted_date  
    FROM 
        avisos 
    WHERE 
        expire_date >= '{$today}' 
        AND is_active = 1 
        {$terms} 
    ";
    $res = $conn->query($sql);

    foreach ($res->fetchAll() as $row) {

        $recorrence = ($row['is_recurrent'] ? "AND DATE_FORMAT(`last_shown`, '%Y-%m-%d') = '{$dateToday}'" : "");

        $sql = "SELECT notice_id
                FROM user_notices WHERE
                notice_id = '" . $row['aviso_id'] . "' AND
                user_id = '" . $user . "' 
                {$recorrence}
        ";
        try {
            $result = $conn->query($sql);
            /* Só enviará a notificação caso já não tenha sido mostrada para o user (no dia atual?) */
            if (!$result->rowCount() && !in_array($row['aviso_id'], $notice_id)) {
                $data[] = array_map('toHtml', $row);
                $notice_id[] = $row['aviso_id'];
                $notice_id = array_unique($notice_id);
            }
        }
        catch (Exception $e) {
            $exceptions .= "<br/>" . $e->getMessage . "<br/>" . $sql;
            echo $exceptions;
        }
    }

}

echo json_encode($data);

?>
