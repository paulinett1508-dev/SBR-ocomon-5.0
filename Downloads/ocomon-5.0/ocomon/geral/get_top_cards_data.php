<?php session_start();
/*                        Copyright 2023 Flávio Ribeiro

         This file is part of OCOMON.

         OCOMON is free software; you can redistribute it and/or modify
         it under the terms of the GNU General Public License as published by
         the Free Software Foundation; either version 3 of the License, or
         (at your option) any later version.
         OCOMON is distributed in the hope that it will be useful,
         but WITHOUT ANY WARRANTY; without even the implied warranty of
         MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
         GNU General Public License for more details.

         You should have received a copy of the GNU General Public License
         along with Foobar; if not, write to the Free Software
         Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
  */

if (!isset($_SESSION['s_logado']) || $_SESSION['s_logado'] == 0) {
    $_SESSION['session_expired'] = 1;
    echo "<script>top.window.location = '../../index.php'</script>";
    exit;
}

require_once __DIR__ . "/" . "../../includes/include_basics_only.php";
require_once __DIR__ . "/" . "../../includes/classes/ConnectPDO.php";
require_once __DIR__ . "/" . "../../includes/classes/worktime/Worktime.php";
require_once __DIR__ . "/" . "../../includes/functions/getWorktimeProfile.php";
use includes\classes\ConnectPDO;

$conn = ConnectPDO::getInstance();

$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 2, 1);

set_time_limit(300);

/* if (!isset($_POST['numero'])) {
    exit();
} */

$isAdmin = $_SESSION['s_nivel'] == 1;


$dataPosted = [];
$post = [];


if (isset($_POST)){
    $post = $_POST;
}


$dataPosted['client'] = (isset($post['client']) && !empty($post['client']) ? $post['client'] : "");
$dataPosted['area'] = (isset($post['area']) && !empty($post['area']) ? $post['area'] : "");
/* Se for filtro pelas áreas de destino do chamado (padrão) */
$dataPosted['requester_areas'] = (isset($post['requester_areas']) ? ($post['requester_areas'] == "yes" ? 1 : 0) : 0);
$dataPosted['render_custom_fields'] = (isset($post['render_custom_fields']) ? ($post['render_custom_fields'] == "yes" ? 1 : 0) : 0);
$dataPosted['app_from'] = (isset($post['app_from']) ? (noHtml($post['app_from'])) : "");



/* Filtro de seleção de clientes - formulário no painel de controle */
$filtered_clients = "";
/* Controle para limitar os resultados com base nos clientes selecionados */
$qry_filter_clients = "";
if (!empty($dataPosted['client'])) {
    $filtered_clients = implode(',', $dataPosted['client']);
}
if (!empty($filtered_clients)) {
    $qry_filter_clients = " AND o.client IN ({$filtered_clients}) ";
}



/* Filtro de seleção de áreas - formulário no painel de controle */
$filtered_areas = "";
/* Controle para limitar os resultados das consultas às áreas do usuário logado quando a opção estiver habilitada */
$qry_filter_areas = "";
if (!empty($dataPosted['area'])) {
    $filtered_areas = implode(',', $dataPosted['area']);
}

$aliasAreasFilter = ($dataPosted['requester_areas'] ? "ua.AREA" : "o.sistema");


if (empty($filtered_areas)) {
    if ($isAdmin) {
        $qry_filter_areas = "";
    } else {
        $qry_filter_areas = " AND (" . $aliasAreasFilter . " IN ({$_SESSION['s_uareas']}) OR " . $aliasAreasFilter . " = '-1')";
    }
} else {
    $qry_filter_areas = " AND (" . $aliasAreasFilter . " IN ({$filtered_areas}))";
}


$hoje = date('Y-m-d 00:00:00');
$mes = date('Y-m-01 00:00:00');

$config = getConfig($conn);
$percLimit = $config['conf_sla_tolerance']; 

/* Chamados com tempo de vida maiores de $maxAgeToCalc anos não serão processados para cálculos de tempo nos cards */
$maxAgeToCalc = 1; 
$defaultValues = [
    'running' => 1,
    'response' => [
        'time' => 'Nao calculado',
        'seconds' => '0',
    ],
    'solution' => [
        'time' => 'Nao calculado',
        'seconds' => '0'
    ]
];


$totalEmAberto = 0;

/* Total de chamados em aberto no sistema */
$sqlTotalEmAberto = "SELECT count(*) AS total FROM 
                        ocorrencias o, status, usuarios ua 
                    WHERE 
                        status.stat_ignored <> 1 AND 
                        status.stat_painel not in (3) AND o.status = status.stat_id AND 
                        o.oco_scheduled = 0 AND o.aberto_por = ua.user_id 
                        {$qry_filter_clients}
                        {$qry_filter_areas} ";

try {
    $res = $conn->query($sqlTotalEmAberto);
}
catch (Exception $e) {
    echo 'Erro: ', $e->getMessage(), "<br/>";
    $erro = true;
    return false;
}
$totalEmAberto = $res->fetch()['total'];
/* final do total em aberto */





/* Abertos na data corrente */
$sqlOpenToday = "SELECT count(*) AS total FROM 
                    ocorrencias o, usuarios ua, `status` s
                WHERE 
                    o.aberto_por = ua.user_id AND 
                    o.status = s.stat_id AND 
                    s.stat_ignored <> 1 AND   
                    o.oco_real_open_date >= '". $hoje ."' 
                     {$qry_filter_clients}
                    {$qry_filter_areas} ";
try {
    $resultOpenToday = $conn->query($sqlOpenToday);
} catch (Exception $e) {
    // echo 'Erro: ', $e->getMessage(), "<br/>";
    return false;
}
$abertosHoje = $resultOpenToday->fetch()['total'];

/* Abertos no mês corrente */
$sqlOpenMonth = "SELECT count(*) AS total FROM 
                    ocorrencias o, usuarios ua, `status` s
                WHERE 
                    o.aberto_por = ua.user_id AND 
                    o.status = s.stat_id AND 
                    s.stat_ignored <> 1 AND 
                    o.oco_real_open_date >= '". $mes ."' 
                     {$qry_filter_clients}
                    {$qry_filter_areas}";
try {
    $resultOpenMonth = $conn->query($sqlOpenMonth);
} catch (Exception $e) {
    echo 'Erro: ', $e->getMessage(), "<br/>";
    return false;
}

$abertosMes = $resultOpenMonth->fetch()['total'];

/* Fechados na data corrente */
$sqlCloseToday = "SELECT count(*) AS total 
                    FROM ocorrencias o, usuarios ua, `status` s
                    WHERE 
                        o.aberto_por = ua.user_id AND 
                        o.status = s.stat_id AND
                        s.stat_ignored <> 1 AND
                        o.data_fechamento >= '". $hoje ."' 
                        {$qry_filter_clients}
                        {$qry_filter_areas}";
try {
    $resultCloseToday = $conn->query($sqlCloseToday);
} catch (Exception $e) {
    echo 'Erro: ', $e->getMessage(), "<br/>";
    return false;
}
$fechadosHoje = $resultCloseToday->fetch()['total'];

/* Fechados no mês corrente */
$sqlCloseMonth = "SELECT count(*) AS total 
                    FROM ocorrencias o, usuarios ua, `status` s
                    WHERE 
                        o.aberto_por = ua.user_id AND  
                        o.status = s.stat_id AND
                        s.stat_ignored <> 1 AND
                        o.data_fechamento >= '". $mes ."' 
                        {$qry_filter_clients}
                        {$qry_filter_areas}";
try {
    $resultCloseMonth = $conn->query($sqlCloseMonth);
} catch (Exception $e) {
    echo 'Erro: ', $e->getMessage(), "<br/>";
    return false;
}
$fechadosMes = $resultCloseMonth->fetch()['total'];


/* Modificar para pegar todas as ocorrências em status vinculados aos operadores - painel superior */
$sqlEmProgresso = "SELECT count(*) AS total 
                    FROM 
                        ocorrencias o, status s, usuarios ua 
                    WHERE 
                        o.status NOT IN (1, 4, 12) AND s.stat_painel in (1) AND 
                        s.stat_ignored <> 1 AND 
                        o.status = s.stat_id AND o.oco_scheduled = 0 
                        AND o.aberto_por = ua.user_id 
                        {$qry_filter_clients}
                        {$qry_filter_areas}";
try {
    $resultEmProgresso = $conn->query($sqlEmProgresso);
} catch (Exception $e) {
    echo 'Erro: ', $e->getMessage(), "<br/>";
    // return false;
}
$emProgresso = $resultEmProgresso->fetch()['total'];

$percEmProgresso = 0;
if ($totalEmAberto) {
    $percEmProgresso = round($emProgresso * 100 / $totalEmAberto, 2);
}


/* Chamados sem resposta */
$sqlSemResposta = "SELECT count(*) AS total 
                    FROM ocorrencias o, usuarios ua, `status` s
                    WHERE 
                        o.aberto_por = ua.user_id AND 
                        o.status = s.stat_id AND
                        s.stat_ignored <> 1 AND
                        o.data_atendimento IS NULL 
                        {$qry_filter_clients}
                        {$qry_filter_areas}";
try {
    $resultSemResposta = $conn->query($sqlSemResposta);
} catch (Exception $e) {
    // echo 'Erro: ', $e->getMessage(), "<br/>";
    return false;
}
$semResposta = $resultSemResposta->fetch()['total'];
$percSemResposta = 0;
if ($totalEmAberto) {
    $percSemResposta = round($semResposta * 100 / $totalEmAberto, 2);
}



/* Busca geral de ocorrencias em aberto para os cálculos de tempos de resposta  */
$countResponseUndefined = 0;
$countResponseGreen = 0;
$countResponseYellow = 0;
$countResponseRed = 0;

$countSolutionUndefined = 0;
$countSolutionGreen = 0;
$countSolutionYellow = 0;
$countSolutionRed = 0;

$absoluteReponseTime = 0;
$absoluteSolutionTime = 0;
$filteredResponseTime = 0;
$filteredSolutionTime = 0;

$frozenByStatus = 0;
$frozenByWorktime = 0;

$percResponseUndefined = 0;
$percResponseGreen = 0;
$percResponseYellow = 0;
$percResponseRed = 0;
$percSolutionUndefined = 0;
$percSolutionGreen = 0;
$percSolutionYellow = 0;
$percSolutionRed = 0;
$avgAbsoluteResponseTime = 0;
$avgAbsoluteSolutionTime = 0;
$avgFilteredResponseTime = 0;
$avgFilteredSolutionTime = 0;

/* final das variáveis sobre os chamados em aberto no sistema */


$data = array();

/* info dos chamados em aberto */
$data['abertosHoje'] = $abertosHoje;
$data['abertosHojeFilter']["data_abertura_from"] = date('Y-m-d');
$data['abertosHojeFilter']["app_from"] = $dataPosted['app_from'];
$data['abertosHojeFilter']["is_requester_area"] = $dataPosted['requester_areas'];
$data['abertosHojeFilter']["areas_filter"] = $filtered_areas;
$data['abertosHojeFilter']["clients_filter"] = $filtered_clients;
$data['abertosHojeFilter']["render_custom_fields"] = $dataPosted['render_custom_fields'];

$data['fechadosHoje'] = $fechadosHoje;
$data['fechadosHojeFilter']["data_fechamento_from"] = date('Y-m-d');
$data['fechadosHojeFilter']["app_from"] = $dataPosted['app_from'];
$data['fechadosHojeFilter']["is_requester_area"] = $dataPosted['requester_areas'];
$data['fechadosHojeFilter']["areas_filter"] = $filtered_areas;
$data['fechadosHojeFilter']["clients_filter"] = $filtered_clients;
$data['fechadosHojeFilter']["render_custom_fields"] = $dataPosted['render_custom_fields'];


$data['abertosMes'] = $abertosMes;
$data['abertosMesFilter']["current_month"] = 1;
$data['abertosMesFilter']["app_from"] = $dataPosted['app_from'];
$data['abertosMesFilter']["is_requester_area"] = $dataPosted['requester_areas'];
$data['abertosMesFilter']["areas_filter"] = $filtered_areas;
$data['abertosMesFilter']["clients_filter"] = $filtered_clients;
$data['abertosMesFilter']["render_custom_fields"] = $dataPosted['render_custom_fields'];


$data['fechadosMes'] = $fechadosMes;
$data['fechadosMesFilter']["closed_current_month"] = 1;
$data['fechadosMesFilter']["app_from"] = $dataPosted['app_from'];
$data['fechadosMesFilter']["is_requester_area"] = $dataPosted['requester_areas'];
$data['fechadosMesFilter']["areas_filter"] = $filtered_areas;
$data['fechadosMesFilter']["clients_filter"] = $filtered_clients;
$data['fechadosMesFilter']["render_custom_fields"] = $dataPosted['render_custom_fields'];


$data['emProgresso'] = $emProgresso;
$data['emProgressoFilter']["em_progresso"] = 1;
$data['emProgressoFilter']["app_from"] = $dataPosted['app_from'];
$data['emProgressoFilter']["is_requester_area"] = $dataPosted['requester_areas'];
$data['emProgressoFilter']["areas_filter"] = $filtered_areas;
$data['emProgressoFilter']["clients_filter"] = $filtered_clients;
$data['emProgressoFilter']["render_custom_fields"] = $dataPosted['render_custom_fields'];

$data['percEmProgresso'] = $percEmProgresso;

$data['semResposta'] = $semResposta;
$data['semRespostaFilter']["empty_response"] = 1;
$data['semRespostaFilter']["app_from"] = $dataPosted['app_from'];
$data['semRespostaFilter']["is_requester_area"] = $dataPosted['requester_areas'];
$data['semRespostaFilter']["areas_filter"] = $filtered_areas;
$data['semRespostaFilter']["clients_filter"] = $filtered_clients;
$data['semRespostaFilter']["render_custom_fields"] = $dataPosted['render_custom_fields'];


$data['percSemResposta'] = $percSemResposta;


echo json_encode($data);
