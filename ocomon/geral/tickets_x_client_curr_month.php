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
$arrayFilteredClients = (!empty($filtered_clients) ? explode(',' , $filtered_clients) : []);
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


$d_ini_completa = date("Y-m-01 00:00:00");
$d_fim_completa = date("Y-m-d H:i:s");
$totalAbertos = 0;
$totalFechados = 0;
$totalCancelados = 0;
$i = 0;


$clients = getClients($conn);

foreach ($clients as $row) {
    
    /* Só fará as consultas para os ids informados ou para todos caso nenhum cliente tenha sido selecionado */
    if (in_array($row['id'], $arrayFilteredClients) || empty($filtered_clients)) {
        $query_ab_sw = "SELECT 
                            count(*) AS abertos, cl.nickname AS cliente
                        FROM 
                            ocorrencias AS o, sistemas AS s, usuarios ua, `status` st, clients cl
                        WHERE 
                            " . $aliasAreasFilter . "  = s.sis_id AND 
                            o.aberto_por = ua.user_id AND 
                            o.status = st.stat_id AND
                            st.stat_ignored <> 1 AND
                            o.client = cl.id AND 
                            o.oco_real_open_date >= '" . $d_ini_completa . "' AND
                            o.oco_real_open_date <= '" . $d_fim_completa . "' AND 
                            
                            o.client = {$row['id']} 

                            {$qry_filter_clients}
                            {$qry_filter_areas}
                        GROUP BY cl.nickname";
        $query_ab_sw = $conn->query($query_ab_sw);
        $totalAbertos += $query_ab_sw->fetch(PDO::FETCH_ASSOC)['abertos'] ?? 0;
        

        $query_fe_sw = "SELECT 
                            count(*) AS fechados, cl.nickname AS cliente, cl.id
                        FROM 
                            ocorrencias AS o, sistemas AS s, usuarios ua, `status` st, clients cl
                        WHERE 
                            " . $aliasAreasFilter . " = s.sis_id AND 
                            o.aberto_por = ua.user_id AND 
                            o.status = st.stat_id AND
                            st.stat_ignored <> 1 AND
                            o.data_fechamento >= '" . $d_ini_completa . "' AND
                            o.data_fechamento <= '" . $d_fim_completa . "' AND 

                            o.client = {$row['id']} 

                            {$qry_filter_clients}
                            {$qry_filter_areas} 
                        GROUP by cliente, cl.id";
        $query_fe_sw = $conn->query($query_fe_sw);
        $totalFechados += $query_fe_sw->fetch(PDO::FETCH_ASSOC)['fechados'] ?? 0;

        $query_ca_sw = "SELECT 
                            count(*) AS cancelados, cl.nickname AS cliente
                        FROM 
                            ocorrencias AS o, sistemas AS s, usuarios ua, `status` st, clients cl
                        WHERE 
                            " . $aliasAreasFilter . " = s.sis_id AND 
                            o.aberto_por = ua.user_id AND 
                            o.status = st.stat_id AND
                            st.stat_ignored <> 1 AND
                            o.oco_real_open_date >= '" . $d_ini_completa . "' AND
                            o.oco_real_open_date <= '" . $d_fim_completa . "' AND 
                            o.status in (12) AND

                            o.client = {$row['id']} 

                            {$qry_filter_clients}
                            {$qry_filter_areas}
                        GROUP by cliente";
        $query_ca_sw = $conn->query($query_ca_sw);
        $totalCancelados += $query_ca_sw->fetch(PDO::FETCH_ASSOC)['cancelados'] ?? 0;

        $data[$i]['cliente'] = $row['nickname'];
        $data[$i]['abertos'] = $totalAbertos;
        $data[$i]['fechados'] = $totalFechados;
        $data[$i]['cancelados'] = $totalCancelados;

        $totalAbertos = 0;
        $totalFechados = 0;
        $totalCancelados = 0;

        $i++;
    }
}

//TICKETS_BY_REQUESTER_AREA_CURRENT_MONTH
$data[]['chart_title'] = ($_SESSION['requester_areas'] ? TRANS('TICKETS_BY_CLIENTS_CURRENT_MONTH_REQUESTER', '', 1) : TRANS('TICKETS_BY_CLIENTS_CURRENT_MONTH', '', 1));

// IMPORTANT, output to json
echo json_encode($data);

?>
