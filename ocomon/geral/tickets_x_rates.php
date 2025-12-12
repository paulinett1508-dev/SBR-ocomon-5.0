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

$first_month_day = date("Y-m-01 00:00:00");
// $first_month_day = date("Y-01-01 00:00:00");
$now = date("Y-m-d H:i:s");

$rating_labels = ratingLabels();
$rating_classes = ratingClasses();



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


$sql = "SELECT 
            tr.rate as rate, COUNT(tr.rate) AS quantidade
        FROM 
            usuarios ua, ocorrencias o 
            LEFT JOIN tickets_rated tr ON o.numero = tr.ticket
        WHERE 
            o.aberto_por = ua.user_id AND
            tr.rate IS NOT NULL AND 
            o.data_fechamento > '{$first_month_day}' AND
            o.data_fechamento <= '{$now}' 
            {$qry_filter_clients}
            {$qry_filter_areas}
            GROUP BY tr.rate ORDER BY quantidade DESC    
        ";

$sql = $conn->query($sql);

$data = array();




if ($sql->rowCount()) {
    foreach ($sql->fetchAll(PDO::FETCH_ASSOC) as $row) {
        // $data[] = $row;
        $data[] = [
            'rate' => $rating_labels[$row['rate']],
            'quantidade' => $row['quantidade'],
            'classe' => $rating_classes[$row['rate']]
        ];
    }
} else {
    $data[] = array(
        "rate" => TRANS('NO_RECORDS_FOUND'),
        "quantidade" => 0
    );
}




$data[]['chart_title'] = TRANS('TICKETS_BY_RATE_CURR_MONTH', '', 1);
// IMPORTANT, output to json
echo json_encode($data);

?>
