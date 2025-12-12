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

$isAdmin = $_SESSION['s_nivel'] == 1;
$exception = "";

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



/* Controle para limitar os resultados das consultas às áreas do usuário logado quando a opção estiver habilitada */
$qry_filter_areas = "";
/* Filtro de seleção de áreas - formulário no painel de controle */
$filtered_areas = "";
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

$maxAgeToCalc = 1; /* Chamados com tempo de vida maiores de $maxAgeToCalc anos não serão processados para cálculos de tempo nos cards */
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
$sqlTotalEmAberto = "SELECT 
                        count(*) AS total 
                    FROM 
                        ocorrencias o, status s, usuarios ua 
                    WHERE 
                        o.aberto_por = ua.user_id AND 
                        s.stat_ignored <> 1 AND 
                        s.stat_painel not in (3) AND 
                        o.status = s.stat_id AND 
                        o.oco_scheduled = 0 
                        {$qry_filter_clients} 
                        {$qry_filter_areas} ";
try {
    $res = $conn->query($sqlTotalEmAberto);
    /* final do total em aberto */
    $totalEmAberto = $res->fetch()['total'];
}
catch (Exception $e) {
    // echo 'Erro: ', $e->getMessage(), "<br/>";
    $erro = true;
}


/* Fila zerada */
$olderTicket = '';
$olderAge = '';
/* Chamado mais antigo em aberto */
$sql = "SELECT 
            o.oco_real_open_date as data, o.numero 
        FROM 
            ocorrencias o, status s, usuarios ua 
        WHERE 
            o.aberto_por = ua.user_id AND 
            s.stat_painel not in (3) AND 
            o.status = s.stat_id AND 
            s.stat_ignored <> 1 AND
            o.oco_real_open_date = (
                SELECT min(o.oco_real_open_date) 
                FROM 
                    ocorrencias o, status s , usuarios ua 
                WHERE 
                    o.aberto_por = ua.user_id AND
                    s.stat_painel not in (3) AND 
                    s.stat_ignored <> 1 AND 
                    o.status = s.stat_id 
                    {$qry_filter_clients} 
                    {$qry_filter_areas}
            ) {$qry_filter_clients} {$qry_filter_areas}"; /* {$filter_areas} */
try {
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $rowOlder = $res->fetch();
        $olderTicket = $rowOlder['numero'];
        $olderAge = absoluteTime($rowOlder['data'], date('Y-m-d H:i:s'))['inTime'];
    }
    /* Final do chamado mais antigo em aberto */
}
catch (Exception $e) {
    // echo 'Erro: ', $e->getMessage(), "<br/>";
    $erro = true;
}


$newerTicket = '';
$newerAge = '';
/* Chamado mais recente em aberto */
$sql = "SELECT 
            o.oco_real_open_date as data, o.numero 
        FROM 
            ocorrencias o, status s, usuarios ua 
        WHERE 
            o.aberto_por = ua.user_id AND 
            s.stat_painel not in (3) AND 
            o.status = s.stat_id AND 
            s.stat_ignored <> 1 AND 
            o.oco_real_open_date = (
                    SELECT max(o.oco_real_open_date) 
                    FROM 
                        ocorrencias o, status s, usuarios ua 
                    WHERE 
                        o.aberto_por = ua.user_id AND 
                        s.stat_painel not in (3) AND 
                        s.stat_ignored <> 1 AND 
                        o.status = s.stat_id 
                        {$qry_filter_clients} 
                        {$qry_filter_areas}
                    ) {$qry_filter_clients} {$qry_filter_areas}";
try {
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $rowNewer = $res->fetch();
        $newerTicket = $rowNewer['numero'];
        $newerAge = absoluteTime($rowNewer['data'], date('Y-m-d H:i:s'))['inTime'];
    }
    /* Final do chamado mais recente em aberto */
}
catch (Exception $e) {
    // echo 'Erro: ', $e->getMessage(), "<br/>";
    $erro = true;
}




$agendados = 0;
/* Agendados */
$sqlAgendados = "SELECT 
                    count(*) as total 
                FROM 
                    ocorrencias o, usuarios ua, `status` s
                WHERE 
                    o.aberto_por = ua.user_id AND 
                    o.status = s.stat_id AND 
                    s.stat_ignored <> 1 AND
                    o.oco_scheduled = 1 
                    {$qry_filter_clients} 
                    {$qry_filter_areas}
                ";

try {
    $resultAgendados = $conn->query($sqlAgendados);
    $agendados = $resultAgendados->fetch()['total'];
}
catch (Exception $e) {
    $exception .= "<hr>" . $e->getMessage();
}


/* Aguardando avaliacao */
$waitingRate = 0;
$doneStatus = $config['conf_status_done'];
$daysToApprove = $config['conf_time_to_close_after_done'];
$onlyBusinessDays = ($config['conf_only_weekdays_to_count_after_done'] ? true : false);

$dateFrom = subDaysFromDate(date('Y-m-d H:i:s'), $daysToApprove, $onlyBusinessDays);


$sqlWaitingRate = "SELECT 
            count(*) as total
        FROM 
            usuarios ua, `status` s, ocorrencias o
            LEFT JOIN tickets_rated tr ON tr.ticket = o.numero
        WHERE
            o.aberto_por = ua.user_id AND 
            o.status = s.stat_id AND 
            s.stat_ignored <> 1 AND
            o.`operador` <> o.`aberto_por` AND
            o.`status` = {$doneStatus} AND
            o.`data_fechamento` IS NOT NULL AND
            o.`data_fechamento` >= '{$dateFrom}' AND
            tr.rate IS NULL
            {$qry_filter_clients} 
            {$qry_filter_areas}
        ";
try {
    $resultWaitingRate = $conn->query($sqlWaitingRate);
    $waitingRate = $resultWaitingRate->fetch()['total'];
}
catch (Exception $e) {
    $exception .= "<hr>" . $e->getMessage();
}



/* Fila aberta */
$sqlFilaGeral = "SELECT 
                    count(*) as total 
                FROM 
                    ocorrencias o, status s, usuarios ua 
                WHERE 
                    o.aberto_por = ua.user_id AND 
                    o.status = s.stat_id AND 
                    s.stat_ignored <> 1 AND 
                    s.stat_painel in (2) AND 
                    o.oco_scheduled = 0 
                    {$qry_filter_clients} 
                    {$qry_filter_areas}
                ";
$resultFilaGeral = $conn->query($sqlFilaGeral);
$filaGeral = $resultFilaGeral->fetch()['total'];
$percFilaGeral = 0;
if ($totalEmAberto) {
    $percFilaGeral = round($filaGeral * 100 / $totalEmAberto, 2);
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

$sql = $QRY["ocorrencias_full_ini"] . " WHERE stat_ignored <> 1 AND s.stat_painel not in (3) {$qry_filter_clients}  {$qry_filter_areas}"; /* apenas abertos */
$res = $conn->query($sql);
$countRecords = $res->rowCount();
foreach ($res->fetchAll() as $row) {

    $referenceDate = (!empty($row['oco_real_open_date']) ? $row['oco_real_open_date'] : $row['data_abertura']);
    $dataAtendimento = $row['data_atendimento']; //data da primeira resposta ao chamado
    $dataFechamento = $row['data_fechamento'];

    /* MÉTODOS PARA O CÁLCULO DE TEMPO VÁLIDO DE RESPOSTA E SOLUÇÃO */
    $holidays = getHolidays($conn);
    $profileCod = getProfileCod($conn, $_SESSION['s_wt_areas'], $row['numero']);
    $worktimeProfile = getWorktimeProfile($conn, $profileCod);

    /* Objeto para o cálculo de Tempo válido de SOLUÇÃO - baseado no perfil de jornada de trabalho e nas etapas em cada status */
    $newWT = new WorkTime( $worktimeProfile, $holidays );
    
    /* Objeto para o cálculo de Tempo válido de RESPOSTA baseado no perfil de jornada de trabalho e nas etapas em cada status */
    $newWTResponse = new WorkTime( $worktimeProfile, $holidays );

    /* Objeto para checagem se o momento atual está coberto pelo perfil de jornada associado */
    $objWT = new Worktime( $worktimeProfile, $holidays );

    /* Realiza todas as checagens necessárias para retornar os tempos de resposta e solução para o chamado */
    
    if (!olderThan($referenceDate, $maxAgeToCalc)) {
        $ticketTimeInfo = getTicketTimeInfo($conn, $newWT, $newWTResponse, $row['numero'], $referenceDate, $dataAtendimento, $dataFechamento, $row['status_cod'], $objWT);
    } else {
        $ticketTimeInfo = $defaultValues;
    }
    
    
    

    /* Retorna os leds indicativos (bolinhas) para os tempos de resposta e solução */
    $ledSlaResposta = showLedSLA($ticketTimeInfo['response']['seconds'], $percLimit, $row['sla_resposta_tempo']);
    $ledSlaSolucao = showLedSLA($ticketTimeInfo['solution']['seconds'], $percLimit, $row['sla_solucao_tempo']);

    $isRunning = $ticketTimeInfo['running'];
    if (isTicketFrozen($conn, $row['numero'])) {
        /* Pausado em função do status */
        $frozenByStatus ++;
    } elseif (!$isRunning) {
        /* Pausado em função da jornada de trabalho */
        $frozenByWorktime ++;
    }

    
    if (getSlaResult($ticketTimeInfo['response']['seconds'], $percLimit, $row['sla_resposta_tempo']) == 1) {
        $countResponseUndefined ++;
    } elseif (getSlaResult($ticketTimeInfo['response']['seconds'], $percLimit, $row['sla_resposta_tempo']) == 2) {
        $countResponseGreen ++;
    } elseif (getSlaResult($ticketTimeInfo['response']['seconds'], $percLimit, $row['sla_resposta_tempo']) == 3) {
        $countResponseYellow ++;
    } elseif (getSlaResult($ticketTimeInfo['response']['seconds'], $percLimit, $row['sla_resposta_tempo']) == 4) {
        $countResponseRed ++;
    }

    if (getSlaResult($ticketTimeInfo['solution']['seconds'], $percLimit, $row['sla_solucao_tempo']) == 1) {
        $countSolutionUndefined ++;
    } elseif (getSlaResult($ticketTimeInfo['solution']['seconds'], $percLimit, $row['sla_solucao_tempo']) == 2) {
        $countSolutionGreen ++;
    } elseif (getSlaResult($ticketTimeInfo['solution']['seconds'], $percLimit, $row['sla_solucao_tempo']) == 3) {
        $countSolutionYellow ++;
    } elseif (getSlaResult($ticketTimeInfo['solution']['seconds'], $percLimit, $row['sla_solucao_tempo']) == 4) {
        $countSolutionRed ++;
    }

    $absoluteReponseTime += absoluteTime($referenceDate, (!empty($dataAtendimento) ? $dataAtendimento : date('Y-m-d H:i:s')))['inSeconds'];
    $absoluteSolutionTime += absoluteTime($referenceDate, date('Y-m-d H:i:s'))['inSeconds'];

    $filteredResponseTime += $ticketTimeInfo['response']['seconds'];
    $filteredSolutionTime += $ticketTimeInfo['solution']['seconds'];

}
/* Variáveis sobre os chamados em aberto no sistema */
if ($countRecords) {
    $percResponseUndefined = round($countResponseUndefined * 100 / $countRecords,2);
    $percResponseGreen = round($countResponseGreen * 100 / $countRecords,2);
    $percResponseYellow = round($countResponseYellow * 100 / $countRecords,2);
    $percResponseRed = round($countResponseRed * 100 / $countRecords,2);
    $percSolutionUndefined = round($countSolutionUndefined * 100 / $countRecords,2);
    $percSolutionGreen = round($countSolutionGreen * 100 / $countRecords,2);
    $percSolutionYellow = round($countSolutionYellow * 100 / $countRecords,2);
    $percSolutionRed = round($countSolutionRed * 100 / $countRecords,2);
    $avgAbsoluteResponseTime = secToTime(floor($absoluteReponseTime/$countRecords))['verbose'];
    $avgAbsoluteSolutionTime = secToTime(floor($absoluteSolutionTime/$countRecords))['verbose'];
    $avgFilteredResponseTime = secToTime(floor($filteredResponseTime/$countRecords))['verbose'];
    $avgFilteredSolutionTime = secToTime(floor($filteredSolutionTime/$countRecords))['verbose'];
}

/* final das variáveis sobre os chamados em aberto no sistema */


$data = array();

/* Variáveis para as listagens vindas a partir dos cards */

$data['olderTicket'] = $olderTicket;
$data['olderTicketFilter']["ticket"] = $olderTicket;

$data['olderAge'] = truncateTime($olderAge, 2);
$data['newerTicket'] = $newerTicket;
$data['newerTicketFilter']["ticket"] = $newerTicket;
$data['newerAge'] = truncateTime($newerAge, 2);


$data['filaGeral'] = $filaGeral;
$data['filaGeralFilter']["open_queue"] = 1;
$data['filaGeralFilter']["app_from"] = $dataPosted['app_from'];
$data['filaGeralFilter']["is_requester_area"] = $dataPosted['requester_areas'];
$data['filaGeralFilter']["areas_filter"] = $filtered_areas;
$data['filaGeralFilter']["clients_filter"] = $filtered_clients;
$data['filaGeralFilter']["render_custom_fields"] = $dataPosted['render_custom_fields'];


$data['agendados'] = $agendados;
$data['agendadosFilter']["scheduled"] = 1;
$data['agendadosFilter']["app_from"] = $dataPosted['app_from'];
$data['agendadosFilter']["is_requester_area"] = $dataPosted['requester_areas'];
$data['agendadosFilter']["areas_filter"] = $filtered_areas;
$data['agendadosFilter']["clients_filter"] = $filtered_clients;
$data['agendadosFilter']["render_custom_fields"] = $dataPosted['render_custom_fields'];


$data['waitingRate'] = $waitingRate;
$data['waitingRateFilter']["waitingRate"] = 1;
$data['waitingRateFilter']["app_from"] = $dataPosted['app_from'];
$data['waitingRateFilter']["is_requester_area"] = $dataPosted['requester_areas'];
$data['waitingRateFilter']["areas_filter"] = $filtered_areas;
$data['waitingRateFilter']["clients_filter"] = $filtered_clients;
$data['waitingRateFilter']["render_custom_fields"] = $dataPosted['render_custom_fields'];

$data['percFilaGeral'] = $percFilaGeral;
$data['frozenByStatus'] = $frozenByStatus;
$data['frozenByStatusFilter']["time_freeze_status_only"] = 1;
$data['frozenByStatusFilter']["app_from"] = $dataPosted['app_from'];
$data['frozenByStatusFilter']["is_requester_area"] = $dataPosted['requester_areas'];
$data['frozenByStatusFilter']["areas_filter"] = $filtered_areas;
$data['frozenByStatusFilter']["clients_filter"] = $filtered_clients;
$data['frozenByStatusFilter']["render_custom_fields"] = $dataPosted['render_custom_fields'];

$data['frozenByWorktime'] = $frozenByWorktime;

/* SLA de Resposta */
$data['percResponseUndefined'] = $percResponseUndefined;
$data['percResponseGreen'] = $percResponseGreen;
$data['percResponseYellow'] = $percResponseYellow;
$data['percResponseRed'] = $percResponseRed;
/* SLA de Solução */
$data['percSolutionUndefined'] = $percSolutionUndefined;
$data['percSolutionGreen'] = $percSolutionGreen;
$data['percSolutionYellow'] = $percSolutionYellow;
$data['percSolutionRed'] = $percSolutionRed;
/* Média absoluta de resposta e solução para chamados em aberto */
$data['openAvgAbsResponseTime'] = truncateTime($avgAbsoluteResponseTime, 2);
$data['openAvgAbsSolutionTime'] = truncateTime($avgAbsoluteSolutionTime, 2);
/* Média filtrada (considera o tempo filtrado) de resposta e solução para chamados em aberto */
$data['openAvgFilteredResponseTime'] = truncateTime($avgFilteredResponseTime, 2);
$data['openAvgFilteredSolutionTime'] = truncateTime($avgFilteredSolutionTime, 2);
/* Final das info dos chamados em aberto */



/* Busca geral de ocorrencias concluúdas para os cálculos de tempos de solução  */
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


$sql = $QRY["ocorrencias_full_ini"] . " WHERE stat_ignored <> 1 AND o.status = 4 AND o.data_fechamento >= '" . date('Y-m-01 00:00:00') . "' {$qry_filter_clients}  {$qry_filter_areas}"; /* apenas encerrados no mês corrente */
$res = $conn->query($sql);
$countRecords = $res->rowCount();
foreach ($res->fetchAll() as $row) {

    $referenceDate = (!empty($row['oco_real_open_date']) ? $row['oco_real_open_date'] : $row['data_abertura']);
    $dataAtendimento = $row['data_atendimento']; //data da primeira resposta ao chamado
    $dataFechamento = $row['data_fechamento'];

    /* MÉTODOS PARA O CÁLCULO DE TEMPO VÁLIDO DE RESPOSTA E SOLUÇÃO */
    $holidays = getHolidays($conn);
    $profileCod = getProfileCod($conn, $_SESSION['s_wt_areas'], $row['numero']);
    $worktimeProfile = getWorktimeProfile($conn, $profileCod);

    /* Objeto para o cálculo de Tempo válido de SOLUÇÃO - baseado no perfil de jornada de trabalho e nas etapas em cada status */
    $newWT = new WorkTime( $worktimeProfile, $holidays );
    
    /* Objeto para o cálculo de Tempo válido de RESPOSTA baseado no perfil de jornada de trabalho e nas etapas em cada status */
    $newWTResponse = new WorkTime( $worktimeProfile, $holidays );

    /* Objeto para checagem se o momento atual está coberto pelo perfil de jornada associado */
    $objWT = new Worktime( $worktimeProfile, $holidays );

    /* Realiza todas as checagens necessárias para retornar os tempos de resposta e solução para o chamado */
    
    if (!olderThan($referenceDate, $maxAgeToCalc)) {
        $ticketTimeInfo = getTicketTimeInfo($conn, $newWT, $newWTResponse, $row['numero'], $referenceDate, $dataAtendimento, $dataFechamento, $row['status_cod'], $objWT);
    } else {
        $ticketTimeInfo = $defaultValues;
    }

    /* Retorna os leds indicativos (bolinhas) para os tempos de resposta e solução */
    $ledSlaResposta = showLedSLA($ticketTimeInfo['response']['seconds'], $percLimit, $row['sla_resposta_tempo']);
    $ledSlaSolucao = showLedSLA($ticketTimeInfo['solution']['seconds'], $percLimit, $row['sla_solucao_tempo']);

    
    if (getSlaResult($ticketTimeInfo['response']['seconds'], $percLimit, $row['sla_resposta_tempo']) == 1) {
        $countResponseUndefined ++;
    } elseif (getSlaResult($ticketTimeInfo['response']['seconds'], $percLimit, $row['sla_resposta_tempo']) == 2) {
        $countResponseGreen ++;
    } elseif (getSlaResult($ticketTimeInfo['response']['seconds'], $percLimit, $row['sla_resposta_tempo']) == 3) {
        $countResponseYellow ++;
    } elseif (getSlaResult($ticketTimeInfo['response']['seconds'], $percLimit, $row['sla_resposta_tempo']) == 4) {
        $countResponseRed ++;
    }

    if (getSlaResult($ticketTimeInfo['solution']['seconds'], $percLimit, $row['sla_solucao_tempo']) == 1) {
        $countSolutionUndefined ++;
    } elseif (getSlaResult($ticketTimeInfo['solution']['seconds'], $percLimit, $row['sla_solucao_tempo']) == 2) {
        $countSolutionGreen ++;
    } elseif (getSlaResult($ticketTimeInfo['solution']['seconds'], $percLimit, $row['sla_solucao_tempo']) == 3) {
        $countSolutionYellow ++;
    } elseif (getSlaResult($ticketTimeInfo['solution']['seconds'], $percLimit, $row['sla_solucao_tempo']) == 4) {
        $countSolutionRed ++;
    }

    $absoluteReponseTime += absoluteTime($referenceDate, (!empty($dataAtendimento) ? $dataAtendimento : date('Y-m-d H:i:s')))['inSeconds'];

    $absoluteSolutionTime += absoluteTime($referenceDate, date('Y-m-d H:i:s'))['inSeconds'];

}
/* Variáveis sobre os chamados encerrados no mês corrente */
if ($countRecords) {
    $percResponseUndefined = round($countResponseUndefined * 100 / $countRecords,2);
    $percResponseGreen = round($countResponseGreen * 100 / $countRecords,2);
    $percResponseYellow = round($countResponseYellow * 100 / $countRecords,2);
    $percResponseRed = round($countResponseRed * 100 / $countRecords,2);
    $percSolutionUndefined = round($countSolutionUndefined * 100 / $countRecords,2);
    $percSolutionGreen = round($countSolutionGreen * 100 / $countRecords,2);
    $percSolutionYellow = round($countSolutionYellow * 100 / $countRecords,2);
    $percSolutionRed = round($countSolutionRed * 100 / $countRecords,2);
    $avgAbsoluteResponseTime = secToTime(floor($absoluteReponseTime/$countRecords))['verbose'];
    $avgAbsoluteSolutionTime = secToTime(floor($absoluteSolutionTime/$countRecords))['verbose'];
}

/* final das variáveis sobre os chamados encerrados no mês corrente */



/* SLA de Resposta - chamados encerrados no mês corrente */
$data['percDoneResponseUndefined'] = $percResponseUndefined;
$data['percDoneResponseGreen'] = $percResponseGreen;
$data['percDoneResponseYellow'] = $percResponseYellow;
$data['percDoneResponseRed'] = $percResponseRed;
/* SLA de Solução - encerrados */
$data['percDoneSolutionUndefined'] = $percSolutionUndefined;
$data['percDoneSolutionGreen'] = $percSolutionGreen;
$data['percDoneSolutionYellow'] = $percSolutionYellow;
$data['percDoneSolutionRed'] = $percSolutionRed;
/* Média absoluta de resposta e solução para chamados encerrados */
$data['doneAvgAbsResponseTime'] = $avgAbsoluteResponseTime;
$data['doneAvgAbsSolutionTime'] = $avgAbsoluteSolutionTime;
/* Final das info dos chamados encerrados no mês corrente */




/* Busca geral de ocorrencias concluídas na data atual para os cálculos de tempos de solução  */
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


$sql = $QRY["ocorrencias_full_ini"] . " WHERE stat_ignored <> 1 AND o.status = 4 AND o.data_fechamento >= '" . date('Y-m-d 00:00:00') . "' {$qry_filter_clients}  {$qry_filter_areas}"; /* apenas encerrados na data corrente corrente */
$res = $conn->query($sql);
$countRecords = $res->rowCount();
foreach ($res->fetchAll() as $row) {

    $referenceDate = (!empty($row['oco_real_open_date']) ? $row['oco_real_open_date'] : $row['data_abertura']);
    $dataAtendimento = $row['data_atendimento']; //data da primeira resposta ao chamado
    $dataFechamento = $row['data_fechamento'];

    /* MÉTODOS PARA O CÁLCULO DE TEMPO VÁLIDO DE RESPOSTA E SOLUÇÃO */
    $holidays = getHolidays($conn);
    $profileCod = getProfileCod($conn, $_SESSION['s_wt_areas'], $row['numero']);
    $worktimeProfile = getWorktimeProfile($conn, $profileCod);

    /* Objeto para o cálculo de Tempo válido de SOLUÇÃO - baseado no perfil de jornada de trabalho e nas etapas em cada status */
    $newWT = new WorkTime( $worktimeProfile, $holidays );
    
    /* Objeto para o cálculo de Tempo válido de RESPOSTA baseado no perfil de jornada de trabalho e nas etapas em cada status */
    $newWTResponse = new WorkTime( $worktimeProfile, $holidays );

    /* Objeto para checagem se o momento atual está coberto pelo perfil de jornada associado */
    $objWT = new Worktime( $worktimeProfile, $holidays );

    /* Realiza todas as checagens necessárias para retornar os tempos de resposta e solução para o chamado */
    if (!olderThan($referenceDate, $maxAgeToCalc)) {
        $ticketTimeInfo = getTicketTimeInfo($conn, $newWT, $newWTResponse, $row['numero'], $referenceDate, $dataAtendimento, $dataFechamento, $row['status_cod'], $objWT);
    } else {
        $ticketTimeInfo = $defaultValues;
    }
    

    /* Retorna os leds indicativos (bolinhas) para os tempos de resposta e solução */
    $ledSlaResposta = showLedSLA($ticketTimeInfo['response']['seconds'], $percLimit, $row['sla_resposta_tempo']);
    $ledSlaSolucao = showLedSLA($ticketTimeInfo['solution']['seconds'], $percLimit, $row['sla_solucao_tempo']);

    
    if (getSlaResult($ticketTimeInfo['response']['seconds'], $percLimit, $row['sla_resposta_tempo']) == 1) {
        $countResponseUndefined ++;
    } elseif (getSlaResult($ticketTimeInfo['response']['seconds'], $percLimit, $row['sla_resposta_tempo']) == 2) {
        $countResponseGreen ++;
    } elseif (getSlaResult($ticketTimeInfo['response']['seconds'], $percLimit, $row['sla_resposta_tempo']) == 3) {
        $countResponseYellow ++;
    } elseif (getSlaResult($ticketTimeInfo['response']['seconds'], $percLimit, $row['sla_resposta_tempo']) == 4) {
        $countResponseRed ++;
    }

    if (getSlaResult($ticketTimeInfo['solution']['seconds'], $percLimit, $row['sla_solucao_tempo']) == 1) {
        $countSolutionUndefined ++;
    } elseif (getSlaResult($ticketTimeInfo['solution']['seconds'], $percLimit, $row['sla_solucao_tempo']) == 2) {
        $countSolutionGreen ++;
    } elseif (getSlaResult($ticketTimeInfo['solution']['seconds'], $percLimit, $row['sla_solucao_tempo']) == 3) {
        $countSolutionYellow ++;
    } elseif (getSlaResult($ticketTimeInfo['solution']['seconds'], $percLimit, $row['sla_solucao_tempo']) == 4) {
        $countSolutionRed ++;
    }

    $absoluteReponseTime += absoluteTime($referenceDate, (!empty($dataAtendimento) ? $dataAtendimento : date('Y-m-d H:i:s')))['inSeconds'];

    $absoluteSolutionTime += absoluteTime($referenceDate, date('Y-m-d H:i:s'))['inSeconds'];

}
/* Variáveis sobre os chamados encerrados na data corrente */
if ($countRecords) {
    $percResponseUndefined = round($countResponseUndefined * 100 / $countRecords,2);
    $percResponseGreen = round($countResponseGreen * 100 / $countRecords,2);
    $percResponseYellow = round($countResponseYellow * 100 / $countRecords,2);
    $percResponseRed = round($countResponseRed * 100 / $countRecords,2);
    $percSolutionUndefined = round($countSolutionUndefined * 100 / $countRecords,2);
    $percSolutionGreen = round($countSolutionGreen * 100 / $countRecords,2);
    $percSolutionYellow = round($countSolutionYellow * 100 / $countRecords,2);
    $percSolutionRed = round($countSolutionRed * 100 / $countRecords,2);
    $avgAbsoluteResponseTime = secToTime(floor($absoluteReponseTime/$countRecords))['verbose'];
    $avgAbsoluteSolutionTime = secToTime(floor($absoluteSolutionTime/$countRecords))['verbose'];
}

/* final das variáveis sobre os chamados encerrados na data corrente */



/* SLA de Resposta - chamados encerrados na data corrente */
$data['percDoneTodayResponseUndefined'] = $percResponseUndefined;
$data['percDoneTodayResponseGreen'] = $percResponseGreen;
$data['percDoneTodayResponseYellow'] = $percResponseYellow;
$data['percDoneTodayResponseRed'] = $percResponseRed;
/* SLA de Solução - encerrados */
$data['percDoneTodaySolutionUndefined'] = $percSolutionUndefined;
$data['percDoneTodaySolutionGreen'] = $percSolutionGreen;
$data['percDoneTodaySolutionYellow'] = $percSolutionYellow;
$data['percDoneTodaySolutionRed'] = $percSolutionRed;
/* Média absoluta de resposta e solução para chamados encerrados */
$data['doneTodayAvgAbsResponseTime'] = $avgAbsoluteResponseTime;
$data['doneTodayAvgAbsSolutionTime'] = $avgAbsoluteSolutionTime;
/* Final das info dos chamados encerrados na data corrente */




echo json_encode($data);
