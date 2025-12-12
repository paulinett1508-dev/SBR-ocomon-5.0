<?php
session_start();
require_once (__DIR__ . "/" . "../../includes/include_basics_only.php");
require_once (__DIR__ . "/" . "../../includes/classes/ConnectPDO.php");


use includes\classes\ConnectPDO;

if ($_SESSION['s_logado'] != 1 || ($_SESSION['s_nivel'] != 1 && $_SESSION['s_nivel'] != 2)) {
    exit;
}

$conn = ConnectPDO::getInstance();
//Todas AS áreas que o usuário percente
$isAdmin = $_SESSION['s_nivel'] == 1;
$uareas = $_SESSION['s_uareas'];


$post = $_POST;


/* Limitar o retorno de acordo com a configuração de isolamento de visibilidade */
$filter_areas = "";
if (isAreasIsolated($conn) && $_SESSION['s_nivel'] != 1) {
    /* Visibilidade isolada entre áreas para usuários não admin */
    $u_areas = $_SESSION['s_uareas'];
    $filter_areas = " AND a.sis_id IN ({$u_areas}) ";
}


$worker_id = (isset($post['worker_id']) && !empty($post['worker_id']) ? $post['worker_id'] : "");
$client = (isset($post['client']) && !empty($post['client']) ? $post['client'] : "");
$area = (isset($post['area']) && !empty($post['area']) ? $post['area'] : "");

// if ($_SESSION['s_can_route'] != 1 && !$isAdmin) {
//     $worker_id = $_SESSION['s_uid'];
// }

// $terms = "";
// $terms .= (!empty($worker_id) ? " AND txw.user_id = :worker_id " : "");
// $left_join = (!empty($terms) ? " LEFT JOIN ticket_x_workers AS txw ON txw.ticket = o.numero " : "");
$left_join = "";

$terms = "";
$terms .= (!empty($worker_id) ? " AND o.operador = :worker_id " : "");

$terms2 = "";
$terms2 .= (!empty($client) ? " AND o.client = {$client}" : "");
$terms2 .= (!empty($area) ? " AND o.sistema = {$area}" : "");

$sql = "SELECT 
    o.numero, 
    o.descricao, 
    o.data_abertura,
    o.data_fechamento,
    o.oco_scheduled_to, 
    o.contato,
    c.nickname as cliente,
    u.nome AS operador,
    ua.nome AS aberto_por,
    st.status `status`,
    l.local, 
    p.problema,
    a.sistema AS area_atendimento,
    asol.sistema AS area_solicitante, 
    mainw.user_textcolor mainworker_textcolor, mainw.user_bgcolor mainworker_bgcolor
    FROM 
    ocorrencias o 
        LEFT JOIN clients AS c ON c.id = o.client
        LEFT JOIN sistemas AS a ON a.sis_id = o.sistema
        LEFT JOIN localizacao AS l ON l.loc_id = o.local
        LEFT JOIN usuarios AS u ON u.user_id = o.operador
        LEFT JOIN usuarios AS ua ON ua.user_id = o.aberto_por
        LEFT JOIN sistemas AS asol ON asol.sis_id = ua.AREA
        LEFT JOIN `status` AS st ON st.stat_id = o.status
        LEFT JOIN problemas AS p ON p.prob_id = o.problema
        LEFT JOIN tickets_extended te ON te.ticket = o.numero
        LEFT JOIN usuarios mainw on mainw.user_id = te.main_worker
        {$left_join}
    WHERE 
    st.stat_ignored <> 1 AND 
    o.data_abertura >= :start AND 
    o.data_abertura <= :end 
    {$terms}
    {$terms2}
    {$filter_areas}
    ";

try {
    $res = $conn->prepare($sql);
    $res->bindParam(':start', $post['start']);
    $res->bindParam(':end', $post['end']);
    if (!empty($worker_id)) {
        $res->bindParam(':worker_id', $worker_id);
    }
    $res->execute();
}
catch (Exception $e) {
    $exception .= "<hr>" . $e->getMessage();
    echo $exception;
    return;
}



$data = array();

$i = 0;
foreach ($res->fetchAll() AS $row) {

    $data[$i]['textColor'] = $post['color'];
    $data[$i]['borderColor'] = $post['borderColor'];
    $data[$i]['backgroundColor'] = $post['bgColor'];
    $data[$i]['id'] = $row['numero'];



    // $data[$i]['title'] = truncateText(noHtml($row['descricao']), 100);
    // $data[$i]['title'] = noHtml($row['descricao']);
    $data[$i]['title'] = noHtml($row['numero']);
    
    $data[$i]['descricao'] = $row['descricao'] ?? '';
    
    // $data[$i]['start'] = $row['oco_scheduled_to'];
    $data[$i]['start'] = $row['data_abertura'];
    // $data[$i]['end'] = $row['data_fechamento'] ?? date('Y-m-d H:i:s');
    $data[$i]['url'] = getGlobalUri($conn, $row['numero']);
    $data[$i]['contato'] = $row['contato'] ?? '';
    $data[$i]['cliente'] = $row['cliente'] ?? '';
    $data[$i]['status'] = $row['status'];
    $data[$i]['operador'] = $row['operador'] ?? '';
    $data[$i]['aberto_por'] = $row['aberto_por'];
    $data[$i]['departamento'] = $row['local'] ?? '';
    $data[$i]['problema'] = $row['problema'] ?? '';
    $data[$i]['area_atendimento'] = $row['area_atendimento'] ?? '';
    $data[$i]['area_solicitante'] = $row['area_solicitante'];
    $data[$i]['data_abertura'] = dateScreen($row['data_abertura']);
    $data[$i]['oco_scheduled_to'] = (!empty($row['oco_scheduled_to']) ? dateScreen($row['oco_scheduled_to']) : '');
    $data[$i]['data_fechamento'] = (!empty($row['data_fechamento']) ? dateScreen($row['data_fechamento']) : '');
    // $data[$i]['start_date'] = $post['start'];


    $workersList = "";
    $data[$i]['funcionarios'] = $workersList;
    if (!empty($ticketWorkers = getTicketWorkers($conn, $row['numero']))) {
        foreach ($ticketWorkers as $worker) {
            if (strlen((string)$workersList)) 
                $workersList .= ", ";
            $workersList .= $worker['nome'];
        }
        $data[$i]['funcionarios'] = $workersList;
    }

    $i++;
    // $data[] = $row;
}


// $data[]['start_date'] = $post['start'];

echo json_encode($data);
// echo json_encode([]);

?>
