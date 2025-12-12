<?php
session_start();
require_once (__DIR__ . "/" . "../../includes/include_basics_only.php");
require_once (__DIR__ . "/" . "../../includes/classes/ConnectPDO.php");
use includes\classes\ConnectPDO;

if ($_SESSION['s_logado'] != 1 || ($_SESSION['s_nivel'] != 1 && $_SESSION['s_nivel'] != 2)) {
    exit;
}


$post = (isset($_POST) && !empty($_POST) ? $_POST : '');

if (empty($post)) {
    exit;
}


$conn = ConnectPDO::getInstance();
$config = getConfig($conn);

$data = array();
$data['success'] = true;
$data['message'] = "";
$exception = "";
$isAdmin = $_SESSION['s_nivel'] == 1;
$doneStatus = $config['conf_status_done'];



if (empty($post['d_ini']) || !isValidDate($post['d_ini'], 'd/m/Y')) {
    $data['success'] = false; 
    $data['field_id'] = "d_ini";
    $data['message'] = message('warning', '', TRANS('BAD_FIELD_FORMAT'), '');
    echo json_encode($data);
    return false;
}

if (empty($post['d_fim']) || !isValidDate($post['d_fim'], 'd/m/Y')) {
    $data['success'] = false; 
    $data['field_id'] = "d_fim";
    $data['message'] = message('warning', '', TRANS('BAD_FIELD_FORMAT'), '');
    echo json_encode($data);
    return false;
}




$start_time = ' 00:00:00';
$end_time = ' 23:59:59';
$criterio = "";
$filter_areas = "";
$areas_names = "";
$clientName = "";


$start_date = $post['d_ini'] . $start_time;
$start_date = dateDB($start_date);

$end_date = $post['d_fim'] . $end_time;
$end_date = dateDB($end_date);

if ($start_date > $end_date) {
    $data['success'] = false; 
    $data['field_id'] = "d_ini";
    $data['message'] = message('warning', '', TRANS('MSG_COMPARE_DATE'), '');
    echo json_encode($data);
    return false;
}


$client = (isset($post['client']) && !empty($post['client']) ? $post['client'] : "");
$clientName = (!empty($client) ? getClients($conn, $client)['nickname']: "");
$filter_client = (!empty($client) ? " AND o.client IN ({$client}) " : "");
$noneClient = TRANS('FILTERED_CLIENT') . ": " . TRANS('NONE_FILTER') . "&nbsp;&nbsp;";
$criterio = (!empty($client) ? TRANS('FILTERED_CLIENT') . ": {$clientName}&nbsp;&nbsp;" : $noneClient );


$area = (isset($post['area']) && !empty($post['area']) && $post['area'] != '-1' ? $post['area'] : "");

if (empty($area)) {
    if (isAreasIsolated($conn) && !$isAdmin) {
        /* Visibilidade isolada entre áreas para usuários não admin */
        $u_areas = $_SESSION['s_uareas'];
        $filter_areas = " AND o.sistema IN ({$u_areas}) ";
    
        $array_areas_names = getUserAreasNames($conn, $u_areas);
    
        foreach ($array_areas_names as $area_name) {
            if (strlen((string)$areas_names))
                $areas_names .= ", ";
            $areas_names .= $area_name;
        }
    } else {
        $filter_areas = "";
        $areas_names = TRANS('ALL');
    }
} else {
    $filter_areas = " AND o.sistema IN ({$area})";
    $areas_names = getUserAreasNames($conn, $area)[0];
}


/**
 * Quantidade de chamados abertos no período
 */
$sql = "SELECT COUNT(*) AS total
        FROM 
            ocorrencias o, `status` s 
        WHERE
            o.`status` = s.stat_id AND
            s.stat_ignored <> 1 AND
            o.data_abertura >= '{$start_date}' AND 
            o.data_abertura <= '{$end_date}' 
            {$filter_client} 
            {$filter_areas} 
";

try {
    $res = $conn->query($sql);
    $data['opened'] = (int)$res->fetch()['total'];
}
catch (Exception $e) {
    $exception .= "<hr>" . $e->getMessage();
}



/**
 * Quantidade de chamados concluídos (concluidos e encerrados) no período
 */
$sql = "SELECT COUNT(*) AS total
        FROM 
            ocorrencias o, `status` s 
        WHERE
            o.`status` = s.stat_id AND
            s.stat_ignored <> 1 AND
            o.status IN (4, {$doneStatus}) AND
            o.data_fechamento >= '{$start_date}' AND 
            o.data_fechamento <= '{$end_date}' 
            {$filter_client} 
            {$filter_areas} 
";

try {
    $res = $conn->query($sql);
    $data['done'] = (int)$res->fetch()['total'];
}
catch (Exception $e) {
    $exception .= "<hr>" . $e->getMessage();
}


/**
 * Quantidade de chamados encerrados no período
 */
$sql = "SELECT COUNT(*) AS total
        FROM 
            ocorrencias o, `status` s 
        WHERE
            o.`status` = s.stat_id AND
            s.stat_ignored <> 1 AND
            o.status = 4 AND
            o.data_fechamento >= '{$start_date}' AND 
            o.data_fechamento <= '{$end_date}' 
            {$filter_client} 
            {$filter_areas} 
";

try {
    $res = $conn->query($sql);
    $data['closed'] = (int)$res->fetch()['total'];
}
catch (Exception $e) {
    $exception .= "<hr>" . $e->getMessage();
}


/**
 * Quantidade de chamados com atendimento iniciado no período
 */
$sql = "SELECT COUNT(*) AS total
        FROM 
            ocorrencias o, `status` s 
        WHERE
            o.`status` = s.stat_id AND
            s.stat_ignored <> 1 AND
            o.status = 4 AND
            o.data_atendimento >= '{$start_date}' AND 
            o.data_atendimento <= '{$end_date}' 
            {$filter_client} 
            {$filter_areas} 
";

try {
    $res = $conn->query($sql);
    $data['started'] = (int)$res->fetch()['total'];
}
catch (Exception $e) {
    $exception .= "<hr>" . $e->getMessage();
}


/**
 * Quantidade de chamados encerrados, de forma automática, no período
 */
$sql = "SELECT COUNT(*) AS total
        FROM 
            ocorrencias o, `status` s, tickets_rated tr 
        WHERE
            o.`status` = s.stat_id AND
            s.stat_ignored <> 1 AND
            o.status = 4 AND
            o.numero = tr.ticket AND
            tr.rate IS NOT NULL AND
            tr.automatic_rate = 1 AND 
            tr.rate_date >= '{$start_date}' AND 
            tr.rate_date <= '{$end_date}' 
            {$filter_client} 
            {$filter_areas} 
";

try {
    $res = $conn->query($sql);
    $data['auto_closed'] = (int)$res->fetch()['total'];
}
catch (Exception $e) {
    $exception .= "<hr>" . $e->getMessage();
}


/**
 * Quantidade de chamados avaliados no período
 */
$sql = "SELECT 
            COUNT(tr.rate) AS total
        FROM 
            ocorrencias o, tickets_rated tr
        WHERE 
            o.numero = tr.ticket AND
            tr.rate IS NOT NULL AND 
            tr.rate_date >= '{$start_date}' AND 
            tr.rate_date <= '{$end_date}' 
            {$filter_client} 
            {$filter_areas} 
        ";
try {
    $res = $conn->query($sql);
    $data['rated'] = (int)$res->fetch()['total'];
}
catch (Exception $e) {
    $exception .= "<hr>" . $e->getMessage();
}


/**
 * Avaliação dos chamados no período
 */
$sql = "SELECT 
            tr.rate as rate, COUNT(tr.rate) AS total
        FROM 
            usuarios ua, ocorrencias o 
            LEFT JOIN tickets_rated tr ON o.numero = tr.ticket
        WHERE 
            o.aberto_por = ua.user_id AND
            tr.rate IS NOT NULL AND 
            tr.rate_date >= '{$start_date}' AND 
            tr.rate_date <= '{$end_date}' 
            {$filter_client} 
            {$filter_areas} 
            GROUP BY tr.rate ORDER BY total DESC    
        ";
try {
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        foreach ($res->fetchAll() as $row) {
            $data['rating'][] = $row;
        }
    } else {
        $data['rating'][] = array(
            "rate" => TRANS('NO_RECORDS_FOUND'),
            "total" => 0
        );
    }
}
catch (Exception $e) {
    $exception .= "<hr>" . $e->getMessage();
}





$data[]['chart_title'] = TRANS('TICKETS_IN_PERIOD', '', 1);





// var_dump([
//     'cliente' => $client,
//     'Nome cliente' => $clientName,
//     'area' => $area,
//     'Nome areas' => $areas_names,
//     'filter_client' => $filter_client,
//     'filter_areas' => $filter_areas,
//     'data inicial' => $start_date,
//     'data final' => $end_date,
//     'SQL' => $sql,
//     'data' => $data,
//     'exception' => $exception
// ]); exit;


// IMPORTANT, output to json
echo json_encode($data);

?>
