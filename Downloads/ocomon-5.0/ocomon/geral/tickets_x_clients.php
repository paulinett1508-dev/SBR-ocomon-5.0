<?php
session_start();
require_once (__DIR__ . "/" . "../../includes/include_basics_only.php");
require_once (__DIR__ . "/" . "../../includes/classes/ConnectPDO.php");
use includes\classes\ConnectPDO;

if ($_SESSION['s_logado'] != 1 || ($_SESSION['s_nivel'] != 1 && $_SESSION['s_nivel'] != 2)) {
    exit;
}

$conn = ConnectPDO::getInstance();
$data = array();
$hasClient = false;

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


/* Total de ocorrências agrupadas por cliente */
$sql = "SELECT cl.nickname cliente, count(o.client) AS quantidade 
        FROM clients cl, status s, ocorrencias o, usuarios ua 
        WHERE 
            cl.id = o.client AND
            s.stat_id = o.status AND s.stat_painel NOT IN (3) 
            AND s.stat_ignored <> 1 
            AND o.aberto_por = ua.user_id 
            {$qry_filter_clients}
            {$qry_filter_areas}
        GROUP BY cliente ORDER BY quantidade DESC";
$sql = $conn->query($sql);


/* Total de ocorrências sem definição de cliente */
$labelClientNull = TRANS('CLIENT_NULL');
$sqlClientNull = "SELECT 
                    count(o.numero) AS quantidade 
                FROM 
                    `status` s, ocorrencias o, usuarios ua 
                WHERE 
                    o.client IS null AND
                    s.stat_id = o.status AND s.stat_painel NOT IN (3) AND
                    s.stat_ignored <> 1 AND
                    o.aberto_por = ua.user_id 
                    {$qry_filter_clients}
                    {$qry_filter_areas}
                ORDER BY quantidade DESC";
$sqlClientNull = $conn->query($sqlClientNull);


if ($sql->rowCount()) {
    $hasClient = true;
    foreach ($sql->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $data[] = $row;
    }
}

if ($sqlClientNull->rowCount()) {
    $totalNullClients = $sqlClientNull->fetchColumn();

    if ($totalNullClients > 0) {
        $data[] = array(
            'cliente' => $labelClientNull,
            'quantidade' => $totalNullClients
        );
    } elseif (!$hasClient) {
        $data[] = array(
            "cliente" => TRANS('NO_RECORDS_FOUND'),
            "quantidade" => 0
        );
    }
}


$data[]['chart_title'] = TRANS('TICKETS_BY_CLIENTS', '', 1);
echo json_encode($data);

?>
