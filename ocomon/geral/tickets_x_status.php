<?php
session_start();
require_once (__DIR__ . "/" . "../../includes/include_basics_only.php");
require_once (__DIR__ . "/" . "../../includes/classes/ConnectPDO.php");
use includes\classes\ConnectPDO;

if ($_SESSION['s_logado'] != 1 || ($_SESSION['s_nivel'] != 1 && $_SESSION['s_nivel'] != 2)) {
    exit;
}

$conn = ConnectPDO::getInstance();

$isAdmin = $_SESSION['s_nivel'] == 1;
$aliasAreasFilter = ($_SESSION['requester_areas'] ? "ua.AREA" : "o.sistema");
$filtered_areas = $_SESSION['dash_filter_areas'];
$filtered_clients = $_SESSION['dash_filter_clients'];
$qry_filter_areas = "";




/* Controle para limitar os resultados com base nos clientes selecionados */
$qry_filter_clients = "";
if (!empty($filtered_clients)) {
    $qry_filter_clients = " AND o.client IN ({$filtered_clients}) ";
}


/* Filtro de seleção a partir das áreas */
if (empty($filtered_areas)) {
    if ($isAdmin) {
        $qry_filter_areas = "";
    } else {
        $qry_filter_areas = " AND (" . $aliasAreasFilter . " IN ({$_SESSION['s_uareas']}) OR " . $aliasAreasFilter . " = '-1')";
    }
} else {
    $qry_filter_areas = " AND (" . $aliasAreasFilter . " IN ({$filtered_areas}))";
}


$sql = "SELECT s.status as status, count(o.status) AS quantidade 
        FROM status s, ocorrencias o, usuarios ua 
        WHERE 
            s.stat_id = o.status AND s.stat_painel NOT IN (3) 
            AND s.stat_ignored <> 1 
            AND o.aberto_por = ua.user_id 
            {$qry_filter_clients}
            {$qry_filter_areas}
        GROUP BY status ORDER BY quantidade DESC";

$sql = $conn->query($sql);

$data = array();


if ($sql->rowCount()) {
    foreach ($sql->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $data[] = $row;
    }
} else {
    $data[] = array(
        "status" => TRANS('NO_RECORDS_FOUND'),
        "quantidade" => 0
    );
}




$data[]['chart_title'] = TRANS('TICKETS_BY_STATUS', '', 1);
// IMPORTANT, output to json
echo json_encode($data);

?>
