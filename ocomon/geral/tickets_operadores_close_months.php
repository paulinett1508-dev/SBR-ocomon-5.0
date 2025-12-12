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

$u_areas = (!empty($filtered_areas) ? $filtered_areas : $_SESSION['s_uareas']);

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
$operadores = [];
$data = [];

// Meses anteriores
$dates = getMonthRangesUpToNOw('P3M');
$datesBegin = $dates['ini'];
$datesEnd = $dates['end'];
$months = $dates['mLabel'];

/* PRIMEIRO BUSCO OS OPERADORES ENVOLVIDAS NA CONSULTA */


if ($_SESSION['requester_areas']) {
    
    $sql = "SELECT user_id, nome FROM usuarios WHERE nivel < 4 AND AREA IN ({$_SESSION['s_uareas']}) ORDER BY nome";
    if ($isAdmin) {
        $sql = "SELECT user_id, nome FROM usuarios WHERE nivel < 4 ORDER BY nome";
    }
    
} else {
    $sql = "SELECT user_id, nome FROM usuarios WHERE nivel < 3 AND AREA IN ({$_SESSION['s_uareas']}) ORDER BY nome";
    if ($isAdmin) {
        $sql = "SELECT user_id, nome FROM usuarios WHERE nivel < 3 ORDER BY nome";
    }
}

$result = $conn->query($sql);
foreach ($result->fetchAll() as $row) {
    $i = 0;
    foreach ($datesBegin as $dateStart) {
        /* Em cada intervalo de tempo busco os totais de cada área */

        if ($_SESSION['requester_areas']) {
            $sqlEach = "SELECT 
                            count(*) AS total, ua.nome 
                        FROM 
                            ocorrencias o, usuarios u, usuarios ua, sistemas s 
                        WHERE 
                            u.user_id = o.operador AND 
                            o.aberto_por = ua.user_id AND
                            " . $aliasAreasFilter . "  = s.sis_id AND 
                            ua.user_id = " . $row['user_id'] . " AND 
                            o.data_fechamento >= '" .  $dateStart  . "' AND 
                            o.data_fechamento <= '" .  $datesEnd[$i]  . "' AND 
                            o.data_fechamento IS NOT NULL 
                            {$qry_filter_clients}
                            {$qry_filter_areas}
                        GROUP BY ua.user_id, ua.nome 
                        ";
        } else {
            $sqlEach = "SELECT 
                            count(*) AS total, u.nome 
                        FROM 
                            ocorrencias o, usuarios u, usuarios ua, sistemas s 
                        WHERE 
                            u.user_id = o.operador AND 
                            o.aberto_por = ua.user_id AND
                            " . $aliasAreasFilter . "  = s.sis_id AND 
                            u.user_id = " . $row['user_id'] . " AND 
                            o.data_fechamento >= '" .  $dateStart  . "' AND 
                            o.data_fechamento <= '" .  $datesEnd[$i]  . "' AND 
                            o.data_fechamento IS NOT NULL 
                            {$qry_filter_clients}
                            {$qry_filter_areas} 
                        GROUP BY u.user_id, u.nome 
                        ";
        }



        $resultEach = $conn->query($sqlEach);

        if ($resultEach->rowCount()) {
            foreach ($resultEach->fetchAll() as $rowEach) {
                
                if ($rowEach['total']){
                    $operadores[] = $rowEach['nome'];
                    // $totais[] = (int)$rowEach['total'];
                    $meses[] = $months[$i];
                    $operadorDados[$rowEach['nome']][] = intval($rowEach['total']);
                } else {
                    $operadores[] = $row['nome'];
                    $operadorDados[$row['nome']][] = 0;
                    $meses[] = $months[$i];
                }
            }
        } else {
            $operadores[] = $row['nome'];
            $operadorDados[$row['nome']][] = 0;
            $meses[] = $months[$i];
        }
        $i++;
    }
}




/* Ajusto os arrays de labels para não ter repetidos */
$meses = array_unique($meses);
$operadores = array_unique($operadores);

/* Separo o conteúdo para organizar o JSON */
$data['operadores'] = $operadores;
$data['months'] = $meses;
$data['totais'] = $operadorDados;
$data['chart_title'] = ($_SESSION['requester_areas'] ? TRANS('TICKETS_BY_REQUESTER_LAST_MONTHS', '', 1) : TRANS('TICKETS_BY_TECHNITIAN_LAST_MONTHS', '', 1));


// var_dump($data); exit;
// var_dump($operadores, $totais, $meses, $operadorDados, $data); exit;

echo json_encode($data);

?>