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



$dates = [];
$datesBegin = [];
$datesEnd = [];
$months = [];
$clientes = [];
$data = [];

// Meses anteriores
$dates = getMonthRangesUpToNOw('P3M');
$datesBegin = $dates['ini'];
$datesEnd = $dates['end'];
$months = $dates['mLabel'];


$clients = getClients($conn);

foreach ($clients as $row) {
    $i = 0;
    foreach ($datesBegin as $dateStart) {
        /* Em cada intervalo de tempo busco os totais de cada área */

        $sqlEach = "SELECT count(*) AS total, cl.nickname AS cliente 
                    FROM ocorrencias o, sistemas s, usuarios ua, `status` st, clients cl
                    WHERE 
                        s.sis_id = " . $aliasAreasFilter . " AND 
                        o.aberto_por = ua.user_id AND 
                        o.status = st.stat_id AND 
                        st.stat_ignored <> 1 AND 
                        
                        o.client = cl.id AND
                        o.client = {$row['id']} AND 

                        o.oco_real_open_date >= '" .  $dateStart  . "' AND 
                        o.oco_real_open_date <= '" .  $datesEnd[$i]  . "' 
                        {$qry_filter_clients}
                        {$qry_filter_areas}
                    GROUP BY cliente
                    ";
        
        $resultEach = $conn->query($sqlEach);
        $countResults = $resultEach->rowCount();

        if ($countResults) {
            foreach ($resultEach->fetchAll() as $rowEach) {

                if ($rowEach['cliente']) {
                    $clientes[] = $rowEach['cliente'];
                    $meses[] = $months[$i];
                    $clientesDados[$rowEach['cliente']][] = $rowEach['total'];
                } else {
                    $clientes[] = $row['nickname'];
                    $meses[] = $months[$i];
                    $clientesDados[$row['nickname']][] = 0;
                }
            }
        } else {
            $clientes[] = $row['nickname'];
            $meses[] = $months[$i];
            $clientesDados[$row['nickname']][] = 0;
        }

        $i++;
    }
}



/* Ajusto os arrays de labels para não ter repetidos */
$meses = array_unique($meses);
$clientes = array_unique($clientes);

/* Separo o conteúdo para organizar o JSON */
$data['clientes'] = $clientes;
$data['months'] = $meses;
$data['totais'] = $clientesDados;
$data['chart_title'] = ($_SESSION['requester_areas'] ? TRANS('TICKETS_BY_CLIENT_LAST_MONTHS_REQUESTER', '', 1) : TRANS('TICKETS_BY_CLIENT_LAST_MONTHS', '', 1));


echo json_encode($data);

?>
