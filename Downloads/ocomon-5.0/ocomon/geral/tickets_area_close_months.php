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

// $u_areas = (!empty($filtered_areas) ? $filtered_areas : $_SESSION['s_uareas']);

// $allAreasInfo = getAreas($conn, 0, 1, null);
// $arrayAllAreas = [];
// foreach ($allAreasInfo as $sigleArea) {
//     $arrayAllAreas[] = $sigleArea['sis_id'];
// }
// $allAreas = implode(",", $arrayAllAreas);

// if ($isAdmin) {
//     $u_areas = (!empty($filtered_areas) ? $filtered_areas : $allAreas);

//     if (empty($filtered_areas) && !$_SESSION['requester_areas']) {
//         /* Padrão, não precisa filtrar por área - todas as áreas de destino */
//         $qry_filter_areas = "";

//     } else {
//         $qry_filter_areas = " AND " . $aliasAreasFilter . " IN ({$u_areas}) ";
//     } 
// } else {
//     $u_areas = (!empty($filtered_areas) ? $filtered_areas : $_SESSION['s_uareas']);
//     $qry_filter_areas = " AND " . $aliasAreasFilter . " IN ({$u_areas}) ";
// }


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
$areas = [];
$data = [];

// Meses anteriores
$dates = getMonthRangesUpToNOw('P3M');
$datesBegin = $dates['ini'];
$datesEnd = $dates['end'];
$months = $dates['mLabel'];

/* PRIMEIRO BUSCO AS AREAS ENVOLVIDAS NA CONSULTA */
// $sql = "SELECT sis_id, sistema FROM sistemas WHERE sis_id IN ({$u_areas}) ORDER by sistema";

if ($_SESSION['requester_areas']) {
    
    $sql = "SELECT s.sis_id, s.sistema FROM sistemas s WHERE s.sis_id IN ({$_SESSION['s_uareas']})";
    if ($isAdmin) {
        $sql = "SELECT s.sis_id, s.sistema FROM sistemas s ";
    }
    
} else {
    $sql = "SELECT s.sis_id, s.sistema FROM sistemas s WHERE s.sis_atende = 1 AND s.sis_id IN ({$_SESSION['s_uareas']})";
    if ($isAdmin) {
        $sql = "SELECT s.sis_id, s.sistema FROM sistemas s WHERE s.sis_atende = 1 ";
    }
}

$result = $conn->query($sql);
foreach ($result->fetchAll() as $row) {
    $i = 0;
    foreach ($datesBegin as $dateStart) {
        /* Em cada intervalo de tempo busco os totais de cada área */

        $sqlEach = "SELECT 
                        count(*) AS total, s.sistema
                    FROM 
                        ocorrencias o, sistemas s, usuarios ua, `status` st
                    WHERE 
                        -- s.sis_id = o.sistema 
                        -- AND s.sis_id = " . $row['sis_id'] . " 
                        -- AND " . $aliasAreasFilter . "  in (" . $row['sis_id'] . ") 
                        o.aberto_por = ua.user_id AND  
                        o.status = st.stat_id AND
                        st.stat_ignored <> 1 AND
                        " . $aliasAreasFilter . "  = s.sis_id AND 
                        o.data_fechamento >= '" .  $dateStart  . "' AND 
                        o.data_fechamento <= '" .  $datesEnd[$i]  . "' 
                        " . $qry_filter_areas . "
                        {$qry_filter_clients}
                        {$qry_filter_areas}
                    GROUP BY s.sistema
                    ";
        
        $resultEach = $conn->query($sqlEach);
        $countResults = $resultEach->rowCount();
        if ($countResults) {
            foreach ($resultEach->fetchAll() as $rowEach) {
                
                if ($rowEach['sistema']){
                    $areas[] = $rowEach['sistema'];
                    // $totais[] = (int)$rowEach['total'];
                    $meses[] = $months[$i];
                    $areasDados[$rowEach['sistema']][] = intval($rowEach['total']);
                } else {
                    $areas[] = $row['sistema'];
                    $areasDados[$row['sistema']][] = 0;
                    $meses[] = $months[$i];
                }
            }
        } else {
            $areas[] = $row['sistema'];
            $meses[] = $months[$i];
            $areasDados[$row['sistema']][] = 0;
        }
        $i++;
    }
}



/* Ajusto os arrays de labels para não ter repetidos */
$meses = array_unique($meses);
$areas = array_unique($areas);

/* Separo o conteúdo para organizar o JSON */
$data['areas'] = $areas;
$data['months'] = $meses;
$data['totais'] = $areasDados;

// TICKETS_CLOSED_BY_REQUESTER_AREA_LAST_MONTHS
// $data['chart_title'] = TRANS('TICKETS_CLOSED_BY_AREA_LAST_MONTHS', '', 1);
$data['chart_title'] = ($_SESSION['requester_areas'] ? TRANS('TICKETS_CLOSED_BY_REQUESTER_AREA_LAST_MONTHS', '', 1) : TRANS('TICKETS_CLOSED_BY_AREA_LAST_MONTHS', '', 1));

// var_dump($areas, $totais, $meses, $areasDados, $data);

echo json_encode($data);

?>