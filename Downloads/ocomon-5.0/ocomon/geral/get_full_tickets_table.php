<?php session_start();
 /* Copyright 2023 Flávio Ribeiro

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
include_once __DIR__ . "/" . "../../includes/functions/getWorktimeProfile.php";

require_once __DIR__ . "/" . "../../includes/components/html2text-master/vendor/autoload.php";


$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 2, 1);

use includes\classes\ConnectPDO;
$conn = ConnectPDO::getInstance();

$isAdmin = $_SESSION['s_nivel'] == 1;


$imgsPath = "../../includes/imgs/";
$iconFrozen = "<span class='text-oc-teal' title='" . TRANS('HNT_TIMER_STOPPED') . "'><i class='fas fa-pause fa-lg'></i></span>";
$iconOutOfWorktime = "<span class='text-oc-teal' title='" . TRANS('HNT_TIMER_OUT_OF_WORKTIME') . "'><i class='fas fa-pause fa-lg'></i></i></span>";
$iconTicketClosed = "<span class='text-oc-teal' title='" . TRANS('HNT_TICKET_CLOSED') . "'><i class='fas fa-check fa-lg'></i></i></span>";
$config = getConfig($conn);
$percLimit = $config['conf_sla_tolerance']; 

$doneStatus = $config['conf_status_done'];
$daysToApprove = $config['conf_time_to_close_after_done'];
$onlyBusinessDays = ($config['conf_only_weekdays_to_count_after_done'] ? true : false);

$hoje_start = date('Y-m-d 00:00:00');
$hoje_end = date('Y-m-d 23:59:59');
$mes_start = date('Y-m-01 00:00:00');

$post = $_POST;

$hasParam = false;
$ignoreStatus = true;
$dashboardTerms = [];


// $render_custom_fields = 1;
$render_custom_fields = (isset($post['render_custom_fields']) ? $post['render_custom_fields']: 1);

// $hidden_columns = json_encode(explode(",", filter_input(INPUT_COOKIE, "oc_sf_hidden_columns")));
// $hidden_columns_text = filter_input(INPUT_COOKIE, "oc_sf_hidden_columns");



foreach ($post as $key => $value) {
    if ($value != '') {
        $hasParam = true;
    }
}

if (!$hasParam) {
    echo message('warning', '', TRANS('CHOOSE_AT_LEAST_ONE_CRITERIA'), '', '' , 1);
    exit;
}

$terms = "";
$criteria = array();
$criterText = "";
$badgeClass = "badge badge-info p-2 mb-1";
$badgeClassEmptySearch = "badge badge-danger p-2 mb-1";

$rateLabels = ratingLabels();
$rateClasses = ratingClasses();

$slaIndicatorLabel = [];
$slaIndicatorLabel[1] = TRANS('SMART_NOT_IDENTIFIED');
$slaIndicatorLabel[2] = TRANS('SMART_IN_SLA');
$slaIndicatorLabel[3] = TRANS('SMART_IN_SLA_TOLERANCE');
$slaIndicatorLabel[4] = TRANS('SMART_OUT_SLA');

$textSlaColumn = [
    'gray-circle.svg' => TRANS('SMART_NOT_IDENTIFIED'),
    'green-circle.svg' => TRANS('SMART_IN_SLA'),
    'yellow-circle.svg' => TRANS('SMART_IN_SLA_TOLERANCE'),
    'red-circle.svg' => TRANS('SMART_OUT_SLA'),
];



// $fromDashboard = (isset($_SESSION['s_app']) && $_SESSION['s_app'] == 'dashboard' ? true : false);
$fromDashboard = (isset($post['app_from']) && $post['app_from'] == "dashboard" ? true : false);


// $aliasAreasFilter = (isset($_SESSION['s_filter_is_requester_area']) && $_SESSION['s_filter_is_requester_area'] ? "ua.AREA" : "o.sistema");
$aliasAreasFilter = (isset($post['is_requester_area']) && $post['is_requester_area'] ? "ua.AREA" : "o.sistema");


if ($fromDashboard) {

    $render_custom_fields = $post['render_custom_fields'];

    $filter_areas = "";
    $areas_names = "";


    $u_areas = (isset($post['areas_filter']) && !empty($post['areas_filter']) ? $post['areas_filter'] : $_SESSION['s_uareas']);

    $array_areas_names = getUserAreasNames($conn, $u_areas);

    foreach ($array_areas_names as $area_name) {
        if (strlen((string)$areas_names))
            $areas_names .= ", ";
        $areas_names .= $area_name;
    }


    $clients_names = "";
    $filter_clients = (isset($post['clients_filter']) && !empty($post['clients_filter']) ? $post['clients_filter'] : "");

    if (!empty($filter_clients)) {
        $array_clients_names = getClientsNamesByIds($conn, $filter_clients, true);
        foreach ($array_clients_names as $client_name) {
            if (strlen((string)$clients_names))
                $clients_names .= ", ";
            $clients_names .= $client_name;
        }
    }


} else {
    $filter_clients = "";

    $filter_areas = "";
    $areas_names = "";
    if (isAreasIsolated($conn) && $_SESSION['s_nivel'] != 1) {
        /* Visibilidade isolada entre áreas para usuários não admin */
        $u_areas = $_SESSION['s_uareas'];
        $filter_areas = "1";

        $array_areas_names = getUserAreasNames($conn, $u_areas);

        foreach ($array_areas_names as $area_name) {
            if (strlen((string)$areas_names))
                $areas_names .= ", ";
            $areas_names .= $area_name;
        }
    }
}








// dump($post);
if (isset($post['simpleSearch']) && $post['simpleSearch'] == 1 && empty($post['ticket'])) {
    $_SESSION['flash'] = message('warning', '', TRANS('MSG_FILL_AT_LEAST_ONE_TICKET_NUMBER'), '');
    print "<script>redirect('simple_search_to_report.php');</script>";
    exit;
}

/* Para os casos da consulta simples por número do chamado */
if (isset($post['ticket']) && !empty($post['ticket'])) {
    
    
    $ignoreStatus = false;
    
    $maxNumberOfTickets = 30; /* número máximo de ocorrências para a consulta */
    $tmp = explode(',', $post['ticket']);
    
    $treatValues = array_map('intval', $tmp);
    $ticketIN = "";
    $i = 0;
    foreach ($treatValues as $ticketNumber) {
        if ($i < $maxNumberOfTickets) { /* Limitando a quantidade de chamados da consulta */
            if (strlen((string)$ticketIN)) $ticketIN .= ", ";
            $ticketIN .= $ticketNumber;
        }
        $i++;
    }
    $terms .= " AND o.numero IN ({$ticketIN}) ";
    
    $criterText = TRANS('TICKET_NUMBER') . ": {$ticketIN}<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
}




if (isset($post['current_month']) && !empty($post['current_month'])) {
    $date_no_time = date('01/m/Y');
    $data_abertura_from = date('Y-m-01') . " 00:00:00";
    $terms .= " AND o.oco_real_open_date >= '" . $data_abertura_from . "' ";
    $criterText = TRANS('SMART_MIN_DATE_OPENING') . ": " . $date_no_time . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post['data_abertura_from']) && !empty($post['data_abertura_from'])) {
    $data_abertura_from = "";

    $data_abertura_from = $post['data_abertura_from'] . " 00:00:00";
    $data_abertura_from = dateDB($data_abertura_from);

    $terms .= " AND o.oco_real_open_date >= '" . $data_abertura_from . "' ";
    $criterText = TRANS('SMART_MIN_DATE_OPENING') . ": " . dateScreen($data_abertura_from, 1) . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
}

if (isset($post['data_abertura_to']) && !empty($post['data_abertura_to'])) {
    $data_abertura_to = "";

    $data_abertura_to = $post['data_abertura_to'] . " 23:59:59";
    $data_abertura_to = dateDB($data_abertura_to);

    $terms .= " AND o.oco_real_open_date <= '" . $data_abertura_to . "' ";
    $criterText = TRANS('SMART_MAX_DATE_OPENING') . ": " . dateScreen($data_abertura_to,1) . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
}

if (isset($post['no_empty_response']) && $post['no_empty_response'] == 1) {
    $terms .= " AND o.data_atendimento IS NOT null ";
    $criterText = TRANS('SMART_HAS_FIRST_RESPONSE') ."<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post['empty_response']) && $post['empty_response'] == 1) {
    $terms .= " AND o.data_atendimento IS null AND s.stat_painel <> 3 ";
    $criterText = TRANS('SMART_HASNT_FIRST_RESPONSE') ."<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post['data_atendimento_from']) && !empty($post['data_atendimento_from'])) {
    $data_atendimento_from = "";

    $data_atendimento_from = $post['data_atendimento_from'] . " 00:00:00";
    $data_atendimento_from = dateDB($data_atendimento_from);

    $terms .= " AND o.data_atendimento >= '" . $data_atendimento_from . "' ";
    $criterText = TRANS('SMART_MIN_DATE_FIRST_RESPONSE') . ": " . dateScreen($data_atendimento_from, 1) . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
}
if (isset($post['data_atendimento_to']) && !empty($post['data_atendimento_to'])) {
    $data_atendimento_to = "";

    $data_atendimento_to = $post['data_atendimento_to'] . " 23:59:59";
    $data_atendimento_to = dateDB($data_atendimento_to);

    $terms .= " AND o.data_atendimento <= '" . $data_atendimento_to . "' ";
    $criterText = TRANS('SMART_MAX_DATE_FIRST_RESPONSE') . ": " . dateScreen($data_atendimento_to, 1) . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
}


/* Filtro exclusivo para listar chamados em progresso - Dashboard */
if (isset($post['em_progresso']) && !empty($post['em_progresso'])) {
    $terms .= " AND o.status NOT IN (1, 4, 12) AND s.stat_painel in (1) AND o.oco_scheduled = 0 ";
    $criterText = TRANS('CARDS_IN_PROGRESS') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
}

/* Filtro exclusivo para listar chamados encerrados no mês corrente - Dashboard */
if (isset($post['closed_current_month']) && !empty($post['closed_current_month'])) {
    $terms .= " AND o.data_fechamento >= '{$mes_start}' AND o.data_fechamento <= '{$hoje_end}' ";
    $criterText = TRANS('CLOSED_CURRENT_MONTH') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
}

/* Filtro exclusivo para listar a fila aberta de chamados - Dashboard */
if (isset($post['open_queue']) && !empty($post['open_queue'])) {
    $terms .= " AND s.stat_painel in (2) AND o.oco_scheduled = 0 ";
    $criterText = TRANS('QUEUE_OPEN_FOR_TREAT') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
}

/* Filtro exclusivo para listar chamados agendados - Dashboard */
if (isset($post['scheduled']) && !empty($post['scheduled'])) {
    $terms .= " AND oco_scheduled = 1 ";
    $criterText = TRANS('QUEUE_SCHEDULED') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
}

/* Filtro exclusivo para listar chamados que estão aguardando avaliação - Dashboard */
if (isset($post['waitingRate']) && !empty($post['waitingRate'])) {
    $dashboardTerms[] = $post['waitingRate'];

    $terms .= " AND tr.ticket IS NOT NULL AND tr.rate IS NULL ";

    $criterText = TRANS('WAITING_RATE') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
}





if (isset($post['closed']) && $post['closed'] == 1) {
    $terms .= " AND o.data_fechamento IS NOT null ";
    $criterText = TRANS('CARDS_CLOSED') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post['not_closed']) && $post['not_closed'] == 1) {
    $terms .= " AND s.stat_painel NOT IN (3) AND o.data_fechamento IS null ";
    $criterText = TRANS('CARDS_NOT_CLOSED') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post['data_fechamento_from']) && !empty($post['data_fechamento_from'])) {
    $data_fechamento_from = "";

    $data_fechamento_from = $post['data_fechamento_from'] . " 00:00:00";
    $data_fechamento_from = dateDB($data_fechamento_from);

    $terms .= " AND o.data_fechamento >= '" . $data_fechamento_from . "' ";
    $criterText = TRANS('SMART_MIN_DATE_CLOSURE') . ": " . dateScreen($data_fechamento_from, 1) . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
}
$data_fechamento_to = "";
if (isset($post['data_fechamento_to']) && !empty($post['data_fechamento_to'])) {

    $data_fechamento_to = $post['data_fechamento_to'] . " 23:59:59";
    $data_fechamento_to = dateDB($data_fechamento_to);

    $terms .= " AND o.data_fechamento <= '" . $data_fechamento_to . "' ";
    $criterText = TRANS('SMART_MAX_DATE_CLOSURE') . ": " . dateScreen($data_fechamento_to, 1) . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
}




if (isset($post['no_empty_contact_email']) && $post['no_empty_contact_email'] == 1) {
    $terms .= " AND ( o.contato_email != '' AND o.contato_email IS NOT NULL  ) ";
    $criterText = TRANS('CONTACT_EMAIL') . ": " . TRANS('SMART_NOT_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post['no_contact_email']) && $post['no_contact_email'] == 1) {
    $terms .= " AND ( o.contato_email = '' OR o.contato_email IS NULL ) ";
    $criterText = TRANS('CONTACT_EMAIL') . ": " . TRANS('SMART_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post['contact_email']) && !empty($post['contact_email'])) {
    
    
    $terms .= " AND o.contato_email = '" . noHtml($post['contact_email']) . "' ";
    
    $criterText = TRANS('CONTACT_EMAIL') . ": " . noHtml($post['contact_email']) . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
}





if ($fromDashboard) {

    if (!empty($filter_clients)) {

        $terms .= " AND o.client in ({$filter_clients}) ";
        $criterText = $clients_names;

        $clientLabel = TRANS('CLIENT');
        
        $criterText = $clientLabel . ": " . $criterText ."<br />";
        $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
    }
    
    
    $criterText = "";

    
    if (empty($post['areas_filter'])) {
        
        if ($isAdmin) {
            $criterText = TRANS("OCO_SEL_ANY");
        } else {
            $terms .= " AND (" . $aliasAreasFilter . " IN ({$u_areas}) OR ".$aliasAreasFilter." = '-1')";

            $sqlCriter = "SELECT sistema FROM sistemas WHERE sis_id in ({$u_areas}) ORDER BY sistema";
            $resCriter = $conn->query($sqlCriter);
            foreach ($resCriter->fetchAll() as $rowCriter) {
                if (strlen((string)$criterText)) $criterText .= ", ";
                $criterText .= $rowCriter['sistema'];
            }
            $criterText .= ", " . TRANS('SMART_EMPTY');
        }
    } else {

        if (!$isAdmin) {
            $terms .= " AND " . $aliasAreasFilter . " IN ({$u_areas}) ";
        } else {
            $terms .= " AND " . $aliasAreasFilter . " IN ({$post['areas_filter']}) ";
        }

        $sqlCriter = "SELECT sistema FROM sistemas WHERE sis_id in ({$u_areas}) ORDER BY sistema";
        $resCriter = $conn->query($sqlCriter);
        foreach ($resCriter->fetchAll() as $rowCriter) {
            if (strlen((string)$criterText)) $criterText .= ", ";
            $criterText .= $rowCriter['sistema'];
        }
    }
    

    // $areaLabel = ($_SESSION['s_filter_is_requester_area'] ? TRANS('REQUESTER_AREA') : TRANS('SERVICE_AREA'));
    $areaLabel = ($post['is_requester_area'] ? TRANS('REQUESTER_AREA') : TRANS('SERVICE_AREA'));
    
    $criterText = $areaLabel . ": " . $criterText ."<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} else {

    /* Se o isolamento de visibilidade entre áreas estiver habilitado */
    if (!empty($filter_areas)) {

        if (isset($post['no_empty_area']) && $post['no_empty_area'] == 1) {
            $terms .= " AND ( o.sistema IN ({$u_areas}) ) ";
            // $criterText = TRANS('SERVICE_AREA') . ": " . TRANS('SMART_NOT_EMPTY') . "<br />";
            $criterText = TRANS('SERVICE_AREA') . ": " . $areas_names . "<br />";
            $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
        
        } elseif (isset($post['no_area']) && $post['no_area'] == 1) {
            $terms .= " AND ( o.sistema = '-1' OR o.sistema = '0') ";
            $criterText = TRANS('SERVICE_AREA') . ": " . TRANS('SMART_EMPTY') . "<br />";
            $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
        
        } elseif (isset($post['area']) && !empty($post['area']) && !empty($post['area'][0])) {
            $areaIN = "";
            foreach ($post['area'] as $area) {
                if (strlen((string)$areaIN)) $areaIN .= ",";
                $areaIN .= $area;
            }
            $terms .= " AND o.sistema IN ({$areaIN}) ";
        
            $criterText = "";
            $sqlCriter = "SELECT sistema FROM sistemas WHERE sis_id in ({$areaIN}) ORDER BY sistema";
            $resCriter = $conn->query($sqlCriter);
            foreach ($resCriter->fetchAll() as $rowCriter) {
                if (strlen((string)$criterText)) $criterText .= ", ";
                $criterText .= $rowCriter['sistema'];
            }
            $criterText = TRANS('SERVICE_AREA') . ": " . $criterText ."<br />";
            $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
        } else {
            /* Se nada for informado para a área, então considera apenas as áreas do usuário e chamados sem área definida*/
            $terms .= " AND ( o.sistema IN ({$u_areas}) OR (o.sistema = '-1' OR o.sistema = '0') ) "; 
            $criterText = TRANS('SERVICE_AREA') . ": " . $areas_names . " " . TRANS('OPERATOR_OR') . " " . TRANS('SMART_EMPTY') . "<br />";

            $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
        }

    } else

    if (isset($post['no_empty_area']) && $post['no_empty_area'] == 1) {
        $terms .= " AND ( o.sistema != '-1' AND o.sistema != '0' ) ";
        $criterText = TRANS('SERVICE_AREA') . ": " . TRANS('SMART_NOT_EMPTY') . "<br />";
        $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

    } elseif (isset($post['no_area']) && $post['no_area'] == 1) {
        $terms .= " AND ( o.sistema = '-1' OR o.sistema = '0') ";
        $criterText = TRANS('SERVICE_AREA') . ": " . TRANS('SMART_EMPTY') . "<br />";
        $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

    } elseif (isset($post['area']) && !empty($post['area']) && !empty($post['area'][0])) {

        $areaIN = "";
        foreach ($post['area'] as $area) {
            if (strlen((string)$areaIN)) $areaIN .= ",";
            $areaIN .= $area;
        }
        $terms .= " AND o.sistema IN ({$areaIN}) ";

        $criterText = "";
        $sqlCriter = "SELECT sistema FROM sistemas WHERE sis_id in ({$areaIN}) ORDER BY sistema";
        $resCriter = $conn->query($sqlCriter);
        foreach ($resCriter->fetchAll() as $rowCriter) {
            if (strlen((string)$criterText)) $criterText .= ", ";
            $criterText .= $rowCriter['sistema'];
        }
        $criterText = TRANS('SERVICE_AREA') . ": " . $criterText ."<br />";
        $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
    }

}



/* Filtro por Cliente */
if (isset($post['no_empty_client']) && $post['no_empty_client'] == 1) {
    $terms .= " AND ( o.client IS NOT NULL ) ";
    $criterText = TRANS('CLIENT') . ": " . TRANS('SMART_NOT_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post['no_client']) && $post['no_client'] == 1) {
    $terms .= " AND ( o.client IS NULL) ";
    $criterText = TRANS('CLIENT') . ": " . TRANS('SMART_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post['client']) && !empty($post['client'])) {
    $clientIN = "";
    foreach ($post['client'] as $client) {
        if (strlen((string)$clientIN)) $clientIN .= ",";
        $clientIN .= $client;
    }
    $terms .= " AND o.client IN ({$clientIN}) ";

    $criterText = "";
    $sqlCriter = "SELECT nickname FROM clients WHERE id in ({$clientIN}) ORDER BY nickname";
    $resCriter = $conn->query($sqlCriter);
    foreach ($resCriter->fetchAll() as $rowCriter) {
        if (strlen((string)$criterText)) $criterText .= ", ";
        $criterText .= $rowCriter['nickname'];
    }
    $criterText = TRANS('CLIENT') . ": " . $criterText ."<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
}





if (isset($post['no_empty_problema']) && $post['no_empty_problema'] == 1) {
    $terms .= " AND ( o.problema != '-1' AND o.problema != '0' ) ";
    $criterText = TRANS('ISSUE_TYPE') . ": " . TRANS('SMART_NOT_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post['no_problema']) && $post['no_problema'] == 1) {
    $terms .= " AND ( o.problema = '-1' OR o.problema = '0') ";
    $criterText = TRANS('ISSUE_TYPE') . ": " . TRANS('SMART_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post['problema']) && !empty($post['problema'])) {
    $probIN = "";
    foreach ($post['problema'] as $problema) {
        if (strlen((string)$probIN)) $probIN .= ",";
        $probIN .= $problema;
    }
    $terms .= " AND o.problema IN ({$probIN}) ";

    $criterText = "";
    $sqlCriter = "SELECT problema FROM problemas WHERE prob_id in ({$probIN}) ORDER BY problema";
    $resCriter = $conn->query($sqlCriter);
    foreach ($resCriter->fetchAll() as $rowCriter) {
        if (strlen((string)$criterText)) $criterText .= ", ";
        $criterText .= $rowCriter['problema'];
    }
    $criterText = TRANS('ISSUE_TYPE') . ": " . $criterText ."<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
}



if (isset($post['no_empty_unidade']) && $post['no_empty_unidade'] == 1) {
    $terms .= " AND ( o.instituicao != '-1' AND o.instituicao != '0' AND o.instituicao IS NOT NULL ) ";
    $criterText = TRANS('COL_UNIT') . ": " . TRANS('SMART_NOT_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post['no_unidade']) && $post['no_unidade'] == 1) {
    $terms .= " AND ( o.instituicao = '-1' OR o.instituicao = '0' OR o.instituicao IS NULL ) ";
    $criterText = TRANS('COL_UNIT') . ": " . TRANS('SMART_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post['unidade']) && !empty($post['unidade'])) {
    $unitIN = "";
    
    if (is_array($post['unidade'])) {
        foreach ($post['unidade'] as $unidade) {
            if (strlen((string)$unitIN)) $unitIN .= ",";
            $unitIN .= $unidade;
        }
    } else {
        $unitIN = $post['unidade'];
    }
    
    
    $terms .= " AND o.instituicao IN ({$unitIN}) ";

    $criterText = "";
    $sqlCriter = "SELECT inst_nome FROM instituicao WHERE inst_cod in ({$unitIN}) ORDER BY inst_nome";
    $resCriter = $conn->query($sqlCriter);
    foreach ($resCriter->fetchAll() as $rowCriter) {
        if (strlen((string)$criterText)) $criterText .= ", ";
        $criterText .= $rowCriter['inst_nome'];
    }
    $criterText = TRANS('COL_UNIT') . ": " . $criterText ."<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
}



if (isset($post['no_empty_etiqueta']) && $post['no_empty_etiqueta'] == 1) {
    $terms .= " AND ( o.equipamento != '-1' AND o.equipamento != '0' AND o.equipamento IS NOT NULL AND o.equipamento != '' ) ";
    $criterText = TRANS('ASSET_TAG') . ": " . TRANS('SMART_NOT_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post['no_etiqueta']) && $post['no_etiqueta'] == 1) {
    $terms .= " AND ( o.equipamento = '-1' OR o.equipamento = '0' OR o.equipamento IS NULL OR o.equipamento = '' ) ";
    $criterText = TRANS('ASSET_TAG') . ": " . TRANS('SMART_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post['etiqueta']) && !empty($post['etiqueta'])) {
    
    $tmp = explode(',', $post['etiqueta']);
    // $treatValues = array_map('intval', $tmp);
    $treatValues = array_map('noHtml', $tmp);
    $tagIN = "";
    foreach ($treatValues as $tag) {
        if (strlen((string)$tagIN)) $tagIN .= ", ";
        $tag = trim($tag);
        $tagIN .= "'{$tag}'";
    }
    $terms .= " AND o.equipamento IN ({$tagIN}) ";
    
    $criterText = TRANS('ASSET_TAG') . ": {$tagIN}<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
}


if (isset($post['no_empty_departamento']) && $post['no_empty_departamento'] == 1) {
    $terms .= " AND ( o.local != '-1' AND o.local != '0' AND o.local IS NOT NULL AND o.local != '') ";
    $criterText = TRANS('DEPARTMENT') . ": " . TRANS('SMART_NOT_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post['no_departamento']) && $post['no_departamento'] == 1) {
    $terms .= " AND ( o.local = '-1' OR o.local = '0' OR o.local IS NULL OR o.local = '' ) ";
    $criterText = TRANS('DEPARTMENT') . ": " . TRANS('SMART_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post['departamento']) && !empty($post['departamento'])) {
    $localIN = "";
    foreach ($post['departamento'] as $departamento) {
        if (strlen((string)$localIN)) $localIN .= ",";
        $localIN .= $departamento;
    }
    $terms .= " AND o.local IN ({$localIN}) ";

    $criterText = "";
    $sqlCriter = "SELECT local FROM localizacao WHERE loc_id in ({$localIN}) ORDER BY local";
    $resCriter = $conn->query($sqlCriter);
    foreach ($resCriter->fetchAll() as $rowCriter) {
        if (strlen((string)$criterText)) $criterText .= ", ";
        $criterText .= $rowCriter['local'];
    }
    $criterText = TRANS('DEPARTMENT') . ": " . $criterText ."<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
}





if (isset($post['end_user_only']) && $post['end_user_only'] == 1) {
    $terms .= " AND ua.nivel = '3' ";
    $criterText = TRANS('SMART_OPENING_USER_TYPE') . ": " . TRANS('SMART_ONLY_BY_ENDUSER') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post['no_end_user']) && $post['no_end_user'] == 1) {
    $terms .= " AND ua.nivel in (1,2) ";
    $criterText = TRANS('SMART_OPENING_USER_TYPE') . ": " . TRANS('SMART_ONLY_BY_TECHNITIANS') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post['aberto_por']) && !empty($post['aberto_por'])) {
    $abertoPorIN = "";
    foreach ($post['aberto_por'] as $aberto_por) {
        if (strlen((string)$abertoPorIN)) $abertoPorIN .= ",";
        $abertoPorIN .= $aberto_por;
    }
    $terms .= " AND o.aberto_por IN ({$abertoPorIN}) ";

    $criterText = "";
    $sqlCriter = "SELECT nome FROM usuarios WHERE user_id in ({$abertoPorIN}) ORDER BY nome";
    $resCriter = $conn->query($sqlCriter);
    foreach ($resCriter->fetchAll() as $rowCriter) {
        if (strlen((string)$criterText)) $criterText .= ", ";
        $criterText .= $rowCriter['nome'];
    }
    $criterText = TRANS('OPENED_BY') . ": " . $criterText ."<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
}


if (isset($post['operator']) && !empty($post['operator'])) {
    $dashboardTerms[] = $post['operator'];
    $operatorIN = "";
    foreach ($post['operator'] as $operator) {
        if (strlen((string)$operatorIN)) $operatorIN .= ",";
        $operatorIN .= $operator;
    }
    // $terms .= " AND o.operador IN ({$operatorIN}) ";

    $criterText = "";
    $sqlCriter = "SELECT nome FROM usuarios WHERE user_id in ({$operatorIN}) ORDER BY nome";
    $resCriter = $conn->query($sqlCriter);
    foreach ($resCriter->fetchAll() as $rowCriter) {
        if (strlen((string)$criterText)) $criterText .= ", ";
        $criterText .= $rowCriter['nome'];
    }
    $criterText = TRANS('SMART_OPERATOR') . ": " . $criterText ."<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
}


if (isset($post['prioridade']) && !empty($post['prioridade'])) {
    $prioridadeIN = "";
    foreach ($post['prioridade'] as $prioridade) {
        if (strlen((string)$prioridadeIN)) $prioridadeIN .= ",";
        $prioridadeIN .= $prioridade;
    }
    $terms .= " AND o.oco_prior IN ({$prioridadeIN}) ";

    $criterText = "";
    $sqlCriter = "SELECT pr_desc FROM prior_atend WHERE pr_cod in ({$prioridadeIN}) ORDER BY pr_desc";
    $resCriter = $conn->query($sqlCriter);
    foreach ($resCriter->fetchAll() as $rowCriter) {
        if (strlen((string)$criterText)) $criterText .= ", ";
        $criterText .= $rowCriter['pr_desc'];
    }
    $criterText = TRANS('COL_PRIORITY') . ": " . $criterText ."<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
}


if (isset($post['time_freeze_status_only']) && $post['time_freeze_status_only'] == 1) {
    $terms .= " AND s.stat_time_freeze = 1 AND s.stat_id NOT IN (4,12) "; /* desconsidera os status fixos de encerramento e cancelamento */
    $criterText = TRANS('SMART_NOT_CLOSED_PAUSED_STATUS') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post['no_time_freeze_status']) && $post['no_time_freeze_status'] == 1) {
    $terms .= " AND s.stat_time_freeze = 0 AND s.stat_id NOT IN (4,12) ";
    $criterText = TRANS('SMART_NOT_CLOSED_RUNNING_STATUS') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post['status']) && !empty($post['status'])) {
    $ignoreStatus = false;
    $statusIN = "";
    foreach ($post['status'] as $status) {
        if (strlen((string)$statusIN)) $statusIN .= ",";
        $statusIN .= $status;
    }
    $terms .= " AND o.status IN ({$statusIN}) ";

    $criterText = "";
    $sqlCriter = "SELECT status FROM status WHERE stat_id in ({$statusIN}) ORDER BY status";
    $resCriter = $conn->query($sqlCriter);
    foreach ($resCriter->fetchAll() as $rowCriter) {
        if (strlen((string)$criterText)) $criterText .= ", ";
        $criterText .= $rowCriter['status'];
    }
    // $criterText = TRANS('COL_STATUS') . "Status: " . $criterText ."<br />";
    $criterText = TRANS('COL_STATUS') . ": " . $criterText ."<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
}


/* Exibição dos critérios para Avaliação do chamado */
if (isset($post['no_empty_rate']) && !empty($post['no_empty_rate'])) {

    $criterText = TRANS('SMART_ONLY_RATED') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
    $terms .= " ";
} elseif (isset($post['no_rate']) && !empty($post['no_rate'])) {

    $criterText = TRANS('SMART_ONLY_NOT_RATED') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
    $terms .= " ";
} elseif (isset($post['rate']) && !empty($post['rate'])) {
    $criterText = "";
    foreach ($post['rate'] as $res) {
        if (strlen((string)$criterText)) $criterText .= ", ";
        $criterText .= $rateLabels[$res];
    }

    $criterText = TRANS('SERVICE_RATE') . ": " . $criterText ."<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
    $terms .= " ";
} 






if (isset($post['response_sla']) && !empty($post['response_sla'])) {
    $criterText = "";
    foreach ($post['response_sla'] as $res) {
        if (strlen((string)$criterText)) $criterText .= ", ";
        $criterText .= $slaIndicatorLabel[$res];
    }

    $criterText = TRANS('RESPONSE_SLA') . ": " . $criterText ."<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
    $terms .= " ";
} 
if (isset($post['solution_sla']) && !empty($post['solution_sla'])) {
    $criterText = "";
    foreach ($post['solution_sla'] as $res) {
        if (strlen((string)$criterText)) $criterText .= ", ";
        $criterText .= $slaIndicatorLabel[$res];
    }

    $criterText = TRANS('SOLUTION_SLA') . ": " . $criterText ."<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
    $terms .= " ";
} 

/* Canais de solicitação - Opening Channels */
if (isset($post['open_channels_only']) && !empty($post['open_channels_only'])) {
    $channels = getChannels($conn, null, 'open');
    $channelIN = "";
    foreach ($channels as $channel) {
        if (strlen((string)$channelIN)) $channelIN .= ",";
        $channelIN .= $channel['id'];
    }

    $terms .= " AND o.oco_channel IN ({$channelIN}) ";

    $criterText = TRANS('SMART_ONLY_OPEN_CHANNELS');
    $criterText = TRANS('OPENING_CHANNEL') . ": " . $criterText ."<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post['system_channels_only']) && !empty($post['system_channels_only'])) { 
    $channels = getChannels($conn, null, 'restrict');
    $channelIN = "";
    foreach ($channels as $channel) {
        if (strlen((string)$channelIN)) $channelIN .= ",";
        $channelIN .= $channel['id'];
    }

    $terms .= " AND o.oco_channel IN ({$channelIN}) ";

    $criterText = TRANS('SMART_ONLY_SYSTEM_CHANNELS');
    $criterText = TRANS('OPENING_CHANNEL') . ": " . $criterText ."<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post['channel']) && !empty($post['channel'])) {
    $channelIN = "";
    foreach ($post['channel'] as $channel) {
        if (strlen((string)$channelIN)) $channelIN .= ",";
        $channelIN .= $channel;
    }
    $terms .= " AND o.oco_channel IN ({$channelIN}) ";

    $criterText = "";
    $sqlCriter = "SELECT name FROM channels WHERE id in ({$channelIN}) ORDER BY name";
    $resCriter = $conn->query($sqlCriter);
    foreach ($resCriter->fetchAll() as $rowCriter) {
        if (strlen((string)$criterText)) $criterText .= ", ";
        $criterText .= $rowCriter['name'];
    }
    $criterText = TRANS('OPENING_CHANNEL') . ": " . $criterText ."<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
}



$tagTerms = "";
/* Tags obrigatórias */
if (isset($post['has_tags']) && !empty($post['has_tags'])) {
    $input_tagsIN = "";
    $criterText = "";

    $mustHave = '';
    $mustHaveText = '('. TRANS('AT_LEAST_TAGS') .')';
    
    if (isset($post['must_have_tags']) && !empty($post['must_have_tags'])) {
        $mustHave = '+';
        $mustHaveText = '('. TRANS('MUST_HAVE_TAGS') .')';
    }
    
    if (!is_array($post['has_tags'])) {
        $singleTag = $post['has_tags'];
        
        $input_tagsIN .= "+\"$singleTag\"";
        $criterText .= $singleTag;
    } else
    foreach ($post['has_tags'] as $input_tag) {
        if (strlen((string)$input_tagsIN)) $input_tagsIN .= " ";
        $input_tagsIN .= "{$mustHave}\"$input_tag\"";

        if (strlen((string)$criterText)) $criterText .= ", ";
        $criterText .= $input_tag;
    }

    $tagTerms .= $input_tagsIN;

    $criterText = TRANS('INPUT_TAGS') . " {$mustHaveText}: " . $criterText ."<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";


    /* Tags de exclusão - Só será aplicada se existir o filtro de tag obrigatória*/
    if (isset($post['exclude_tags']) && !empty($post['exclude_tags'])) {
        $input_tagsIN = "";
        $criterText = "";
        foreach ($post['exclude_tags'] as $input_tag) {
            if (strlen((string)$input_tagsIN)) $input_tagsIN .= " ";
            $input_tagsIN .= "-\"$input_tag\"";

            if (strlen((string)$criterText)) $criterText .= ", ";
            $criterText .= $input_tag;
        }
        $tagTerms .= " " . $input_tagsIN;

        $criterText = TRANS('INPUT_TAGS_EXCLUDED') . ": " . $criterText ."<br />";
        $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
    }
}
if (strlen((string)$tagTerms)) {
    $terms .= " AND MATCH(oco_tag) AGAINST('{$tagTerms}' IN BOOLEAN MODE)";
}



if (isset($post['only_relatives']) && !empty($post['only_relatives'])) {

    $criterText = TRANS('SMART_ONLY_WITH_TICKETS_REFERENCED') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
    $terms .= " ";
} elseif (isset($post['no_relatives']) && !empty($post['no_relatives'])) {

    $criterText = TRANS('SMART_ONLY_WITHOUT_TICKETS_REFERENCED') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
    $terms .= " ";
} 


if (isset($post['never_rejected']) && !empty($post['never_rejected'])) {

    $criterText = TRANS('NEVER_REJECTED') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
    $terms .= " ";
} elseif (isset($post['has_been_rejected']) && !empty($post['has_been_rejected'])) {

    $criterText = TRANS('HAS_BEEN_REJECTED') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
    $terms .= " ";
} 


if (isset($post['only_attachments']) && !empty($post['only_attachments'])) {

    $criterText = TRANS('ONLY_TICKETS_WITH_ATTACHMENTS') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
    $terms .= " ";
} elseif (isset($post['no_attachments']) && !empty($post['no_attachments'])) {

    $criterText = TRANS('ONLY_TICKETS_WITHOUT_ATTACHMENTS') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
    $terms .= " ";
} 




/* Controle para evitar consultas sem critérios relevantes fora do dashboard */
if (empty(trim($terms)) && empty($dashboardTerms)) {
    $criterText = TRANS('SMART_WITHOUT_SEARCH_CRITERIA') . "<br />";
    $criteria[] = "<span class='{$badgeClassEmptySearch}'>{$criterText}</span>";

    /* Não permito a busca de ocorrencias sem ao menos um critério dentro dos campos oficiais */
    echo message('warning', '', TRANS('CHOOSE_AT_LEAST_ONE_CRITERIA'), '', '' , 1);
    exit;

}



/**
 * Consulta no banco a partir de todos os critérios diretos
 */

if ($ignoreStatus) {
    $sql = $QRY["ocorrencias_full_ini"] . " WHERE stat_ignored <> 1 {$terms} ORDER BY numero";
} else {
    $sql = $QRY["ocorrencias_full_ini"] . " WHERE 1 = 1 {$terms} ORDER BY numero";
}

// dump($sql); exit;

$sqlResult = $conn->query($sql);
$totalFiltered = $sqlResult->rowCount();



/**
 * Campos personalizados
 * Tipos de campos possíveis:
 * ["date", "datetime", "select", "select_multi", "number", "text", "textarea", "checkbox"]
 * 
 * Até o momento esses são os campos permitidos e tratados:
 * ["date", "datetime", "select", "select_multi", "number", "text", "textarea", "checkbox"]
 */

$custom_fields = [];
$custom_fields_full = [];
if ($render_custom_fields) {
    $types = ["date", "datetime", "select", "select_multi", "number", "text", "textarea", "checkbox"];
    $custom_fields = getCustomFields($conn, null, 'ocorrencias', $types); /* Apenas campos customizados ativos e que podem ser pesquisados */
    $custom_fields_full = getCustomFields($conn, null, 'ocorrencias'); /* Para montar a tabela de exibição, todos os campos ativos sao utilizados */
}

/* Montagem dos Critérios dos campos personalizados preenchidos */
$emptyPrefix = "no_";
$notEmptyPrefix = "no_empty_";
$minDatePrefix = "min_";
$maxDatePrefix = "max_";
$minNumberPrefix = "minNum_";
$maxNumberPrefix = "maxNum_";
$noRenderPrefix = "norender_";
$dontRender = [];

/** Armazenarei aqui os valores a serem checados por cada chamado */
$customTerms = [];

/* Bloco para identificação dos critérios de pesquisa sobre campos customizados */
foreach ($custom_fields as $cfield) {
    $criterText = "";
    
    /* Ver os campos que não devem ser renderizados */
    if (isset($post[$noRenderPrefix . $cfield['field_name']]) && $post[$noRenderPrefix . $cfield['field_name']] == 1) {
        $dontRender[] = $cfield['id'];
    }
    
    if (isset($post[$notEmptyPrefix . $cfield['field_name']]) && $post[$notEmptyPrefix . $cfield['field_name']] == 1) {
        /* Qualquer valor não vazio */
        $criterText = $cfield['field_label'] . ": " . TRANS('SMART_NOT_EMPTY') . "<br />";
        $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

        /* id - operador - valor de comparacao */
        $customTerms[$cfield['id']]['!='][] = '';

    } elseif (isset($post[$emptyPrefix . $cfield['field_name']]) && $post[$emptyPrefix . $cfield['field_name']] == 1) {
        /* Valor obrigatiamente vazio */
        $criterText = $cfield['field_label'] . ": " . TRANS('SMART_EMPTY') . "<br />";
        $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

        /* id - operador - valor de comparacao */
        $customTerms[$cfield['id']]['=='][] = '';

    } elseif (isset($post[$cfield['field_name']]) && !empty($post[$cfield['field_name']])) {
        /* Valor informado */
        
        if ($cfield['field_type'] == 'select' || $cfield['field_type'] == 'select_multi') {
            $fieldIN = [];
            foreach ($post[$cfield['field_name']] as $fieldValue) {
                $fieldIN[] = getCustomFieldValue($conn, $fieldValue);
            
                /* id - operador - valor de comparacao */
                $customTerms[$cfield['id']]['IN'][] = $fieldValue;
            }

        } else {
            /* Ver tratamento para cada tipo de campo - Datas não entram nesse laço */
            $fieldIN = $post[$cfield['field_name']];

            /* id - operador - valor de comparacao */
            // $customTerms[$cfield['id']]['=='][] = $fieldIN;

            /* Operador de comparação direta '===' */
            $customTerms[$cfield['id']]['==='][] = $fieldIN;
        }

        $criterText = (is_array($fieldIN) ? implode(", ", $fieldIN) : $fieldIN);

        $criterText = $cfield['field_label'] . ": " . $criterText ."<br />";
        $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

    } elseif (isset($post[$minDatePrefix . $cfield['field_name']]) && !empty($post[$minDatePrefix . $cfield['field_name']])) {
        /* Se tiver data mínima selecionada - Campos do tipo date ou datetime' */
        $criterText = $post[$minDatePrefix . $cfield['field_name']];
        $criterText = $cfield['field_label'] . " (" . TRANS('MIN_DATE') . "): " . $criterText ."<br />";
        $criterText2 = "";


        /* id - operador - valor de comparacao */
        $customTerms[$cfield['id']]['<='][] = $post[$minDatePrefix . $cfield['field_name']];
        
        
        /* Tem data final? */
        if (isset($post[$maxDatePrefix . $cfield['field_name']]) && !empty($post[$maxDatePrefix . $cfield['field_name']])) {
            $criterText2 = $post[$maxDatePrefix . $cfield['field_name']];

            $criterText2 = $cfield['field_label'] . " (" . TRANS('MAX_DATE') . "): " . $criterText2 ."<br />";

            /* id - operador - valor de comparacao */
            $customTerms[$cfield['id']]['>='][] = $post[$maxDatePrefix . $cfield['field_name']];
        }

        $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
        $criteria[] = "<span class='{$badgeClass}'>{$criterText2}</span>";
    } elseif (isset($post[$maxDatePrefix . $cfield['field_name']]) && !empty($post[$maxDatePrefix . $cfield['field_name']])) {
        /* Se tiver data máxima selecionada mas não tiver data mínima  */
        $criterText = $post[$maxDatePrefix . $cfield['field_name']];
        $criterText = $cfield['field_label'] . " (" . TRANS('MAX_DATE') . "): " . $criterText ."<br />";
        $criterText2 = "";
        
        $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

        /* id - operador - valor de comparacao */
        $customTerms[$cfield['id']]['>='][] = $post[$maxDatePrefix . $cfield['field_name']];
    
    
    }  elseif (isset($post[$minNumberPrefix . $cfield['field_name']]) && !empty($post[$minNumberPrefix . $cfield['field_name']])) {
        /* Se tiver data mínima selecionada - Campos do tipo date ou datetime' */
        $criterText = $post[$minNumberPrefix . $cfield['field_name']];
        $criterText = $cfield['field_label'] . " (" . TRANS('MIN_VALUE') . "): " . $criterText ."<br />";
        $criterText2 = "";


        /* id - operador - valor de comparacao */
        $customTerms[$cfield['id']]['<='][] = $post[$minNumberPrefix . $cfield['field_name']];
        
        
        /* Tem limite final? */
        if (isset($post[$maxNumberPrefix . $cfield['field_name']]) && !empty($post[$maxNumberPrefix . $cfield['field_name']])) {
            $criterText2 = $post[$maxNumberPrefix . $cfield['field_name']];

            $criterText2 = $cfield['field_label'] . " (" . TRANS('MAX_VALUE') . "): " . $criterText2 ."<br />";

            /* id - operador - valor de comparacao */
            $customTerms[$cfield['id']]['>='][] = $post[$maxNumberPrefix . $cfield['field_name']];
        }

        $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
        $criteria[] = "<span class='{$badgeClass}'>{$criterText2}</span>";

    } elseif (isset($post[$maxNumberPrefix . $cfield['field_name']]) && !empty($post[$maxNumberPrefix . $cfield['field_name']])) {
        /* Se tiver data máxima selecionada mas não tiver data mínima  */
        $criterText = $post[$maxNumberPrefix . $cfield['field_name']];
        $criterText = $cfield['field_label'] . " (" . TRANS('MAX_VALUE') . "): " . $criterText ."<br />";
        $criterText2 = "";
        
        $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

        /* id - operador - valor de comparacao */
        $customTerms[$cfield['id']]['>='][] = $post[$maxNumberPrefix . $cfield['field_name']];
    } 
}
/* Final da montagem dos critérios sobre os campos personalizados preenchidos */



$criterios = "";

?>
    <!-- <div class="row">
        <div class="col-12">Foram encontrados <span class="bold"><?= $totalFiltered; ?></span> registros de acordo com os seguintes <span class="bold">critérios de pesquisa:</span></div>
    </div> -->
    <div id="table_info"></div>
    <div id="div_criterios" class="row p-4">
        <div class="col-10">
            <?php
            foreach ($criteria as $badge) {
                // echo $badge . "&nbsp;";
                $criterios .= $badge . "&nbsp;";
            }
            ?> 
        </div>
        
    </div>
    <div class="display-buttons"></div>


    <div class="double-scroll">
        <table id="table_tickets_queue" class="stripe hover order-column row-border" border="0" cellspacing="0" width="100%">
            <thead>
                <tr class="header">
                    <td class='line'><?= TRANS('NUMBER_ABBREVIATE'); ?></td>
                    <td class='line client'><?= TRANS('CLIENT'); ?></td>
                    <td class='line area_solicitante'><?= TRANS('REQUESTER_AREA'); ?></td>
                    <td class='line area'><?= TRANS('SERVICE_AREA'); ?></td>
                    <td class='line problema'><?= TRANS('ISSUE_TYPE'); ?></td>
                    <td class='line aberto_por'><?= TRANS('OPENED_BY'); ?></td>
                    <td class='line canal'><?= TRANS('CHANNEL'); ?></td>
                    <td class='line contato'><?= TRANS('CONTACT'); ?></td>
                    <td class='line contato_email'><?= TRANS('CONTACT_EMAIL'); ?></td>
                    <td class='line telefone'><?= TRANS('COL_PHONE'); ?></td>
                    <td class='line departamento'><?= TRANS('DEPARTMENT'); ?></td>
                    <td class='line descricao truncate_flag truncate' style='max-width:15% !important; '><?= TRANS('DESCRIPTION'); ?></td>
                    <td class='line tech_description truncate_flag truncate' style='max-width:15% !important; '><?= TRANS('TXT_DESC_TEC_PROB'); ?></td>
                    <td class='line solution truncate_flag truncate' style='max-width:15% !important; '><?= TRANS('SOLUTION'); ?></td>
                    <td class='line funcionarios'><?= TRANS('WORKERS'); ?></td>

                    <td class='line data_abertura'><?= TRANS('OPENING_DATE'); ?></td>
                    <td class='line agendado'><?= TRANS('IS_SCHEDULED'); ?></td>
                    <td class='line agendado_para'><?= TRANS('FIELD_SCHEDULE_TO'); ?></td>
                    <td class='line data_atendimento'><?= TRANS('FIRST_RESPONSE'); ?></td>
                    <td class='line data_fechamento'><?= TRANS('FIELD_DATE_CLOSING'); ?></td>
                    <td class='line unidade'><?= TRANS('COL_UNIT'); ?></td>
                    <td class='line etiqueta'><?= TRANS('ASSET_TAG'); ?></td>
                    <td class='line status'><?= TRANS('COL_STATUS'); ?></td>
                    <td class='line tempo_absoluto'><?= TRANS('ABSOLUTE_TIME'); ?></td>
                    <td class='line tempo'><?= TRANS('FILTERED_TIME'); ?></td>
                    <td class='line duracao_abs' title="<?= TRANS('ABS_SERVICE_TIME'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover"><?= TRANS('COL_SERVICE_TIME_ABS'); ?></td>
                    <td class='line duracao_filtrado' title="<?= TRANS('SERVICE_TIME'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover"><?= TRANS('COL_SERVICE_TIME_FILTERED'); ?></td>
                    <td class='line prioridade'><?= TRANS('OCO_PRIORITY'); ?></td>
                    <td class='line rate'><?= TRANS('SERVICE_RATE'); ?></td>
                    <td class='line rejeicao'><?= TRANS('REJECTED_COUNT'); ?></td>
                    <td class='line sla'><?= TRANS('COL_SLAS'); ?></td>
                    <td class='line sla_resposta'><?= TRANS('CARDS_RESPONSE_SLA'); ?></td>
                    <td class='line sla_solucao'><?= TRANS('CARDS_SOLUTION_SLA'); ?></td>
                    <td class='line input_tags'><?= TRANS('INPUT_TAGS'); ?></td>

                    <?php
                        /* Campos customizados */
                        foreach ($custom_fields_full as $cfield) {

                            if (!in_array($cfield['id'], $dontRender)) {
                            ?>
                                <td class="line custom_field <?= $cfield['field_name']; ?>"><?= $cfield['field_label']; ?></td>
                            <?php
                            }
                        }
                    ?>
                </tr>
            </thead>
       
<?php


/** 
 * Iteração sobre os chamados 
 * */
foreach ($sqlResult->fetchAll() as $row){
    $nestedData = array(); 
    $showRecord = true;
    
    /* Operadores - Responsável e Auxiliares (se existirem) */
    $workers = getTicketWorkers($conn, $row['numero']);
    $listWorkers = '<li>'.$row['nome'].'</li>';
    if (!empty($workers)) {
        $listWorkers = "";
        foreach ($workers as $worker) {
            $listWorkers .= '<li>' . $worker['nome'] . '</li>';
        }
    }

    if (isset($post['operator']) && !empty($post['operator']) && !empty($workers)) {
        $showRecord = false;
        foreach ($post['operator'] as $operator) {
            if (in_array($operator, array_column($workers, 'user_id'))) {
                $showRecord = true;
                break;
            }
        }
    } elseif (isset($post['operator']) && !empty($post['operator'])) {
        $showRecord = false;
        
        if (in_array($row['operador_cod'], $post['operator'])) {
            $showRecord = true;
        }
    }


    if ($showRecord) {
        /* Sobre a avaliação dos chamados */
        if (isset($post['no_empty_rate']) && !empty($post['no_empty_rate'])) {
            $showRecord = isRated($conn, $row['numero']);
        }

        if (isset($post['no_rate']) && !empty($post['no_rate'])) {
            $showRecord = !isRated($conn, $row['numero']);
        }

        if (isset($post['rate']) && !empty($post['rate'])) {
            $showRecord = false;
            foreach ($post['rate'] as $res) {
                $rate = getTicketRate($conn, $row['numero']);
                if ($rate && $rate == $res) {
                    $showRecord = true;
                    break;
                }
            }
        } 
    }

    /* Caso o filtro seja por chamados aguardando aprovação */
    if ($showRecord && isset($post['waitingRate']) && $post['waitingRate'] == 1) {

        $dateFrom = subDaysFromDate(date('Y-m-d H:i:s'), $daysToApprove, $onlyBusinessDays);
        
        $showRecord = isWaitingRate($conn, $row['numero'], $doneStatus, $dateFrom);
    }


    /* Filtro por rejeitados: nunca rejeitados */
    if ($showRecord && isset($post['never_rejected']) && $post['never_rejected'] == 1) {
        $showRecord = !hasBeenRejected($conn, $row['numero']);
    }

    /* Filtro por rejeitados: já rejeitados */
    if ($showRecord && isset($post['has_been_rejected']) && $post['has_been_rejected'] == 1) {
        $showRecord = hasBeenRejected($conn, $row['numero']);
    }


    if ($showRecord) {

        /* CHECAGEM DE SUB-CHAMADOS */
        $sqlSubCall = "SELECT * FROM ocodeps WHERE dep_pai = " . $row['numero'] . " or dep_filho = " . $row['numero'] . "";
        $execSubCall = $conn->query($sqlSubCall);
        $regSub = $execSubCall->rowCount();
        if ($regSub > 0) {

            if (isset($post['no_relatives']) && $post['no_relatives'] == 1) {
                $showRecord = false;
            }

            #É CHAMADO PAI?
            $sqlSubCall = "SELECT * FROM ocodeps WHERE dep_pai = " . $row['numero'] . "";
            $execSubCall = $conn->query($sqlSubCall);
            $regSub = $execSubCall->rowCount();


            $comDeps = false;
            foreach ($execSubCall->fetchAll() as $rowSubPai) {
                $sqlStatus = "SELECT o.*, s.* FROM ocorrencias o, `status` s  WHERE o.numero=" . $rowSubPai['dep_filho'] . " and o.`status`=s.stat_id and s.stat_painel not in (3) ";
                $execStatus = $conn->query($sqlStatus);
                $regStatus = $execStatus->rowCount();
                if ($regStatus > 0) {
                    $comDeps = true;
                }
            }
            if ($comDeps) {
                $imgSub = "<img src='" . $imgsPath . "sub-ticket-red.svg' class='mb-1' height='10' data-title='" . TRANS('TICKET_WITH_RESTRICTIVE_RELATIONS') . "'>";
            } else {
                $imgSub = "<img src='" . $imgsPath . "sub-ticket-green.svg' class='mb-1' height='10' data-title='" . TRANS('TICKET_WITH_OPEN_RELATIONS') . "'>";
            }
        } else {
            if (isset($post['only_relatives']) && $post['only_relatives'] == 1) {
                $showRecord = false;
            }
            $imgSub = "";
        }
        /* FINAL DA CHEGAGEM DE SUB-CHAMADOS */
    }

    
    if ($showRecord) {
        /* CHECAGEM DE ANEXOS */
        $qryImg = "select * from imagens where img_oco = " . $row['numero'] . "";
        $execImg = $conn->query($qryImg);
        $regImg =  $execImg->rowCount();
        
        if ($regImg != 0) {
            
            if ($showRecord) {
                if (isset($post['no_attachments']) && !empty($post['no_attachments'])) {
                    $showRecord = false;
                }
            }
            
            $linkImg = "<a onClick=\"javascript:popup_wide('listFiles.php?COD=" . $row['numero'] . "')\"><img src='../../includes/icons/attach2.png'></a>";
            // $linkImg = "<a onClick=\"javascript:popup_wide('listFiles.php?COD=" . $row['numero'] . "')\"><i class='fas fa-paperclip'></i></a>";
        } else {

            if ($showRecord) {
                if (isset($post['only_attachments']) && !empty($post['only_attachments'])) {
                    $showRecord = false;
                }
            }

            $linkImg = "";
        }
        /* FINAL DA CHECAGEM DE ANEXOS */
    }



    if ($showRecord) {


        /* DESCRIÇÃO DO CHAMADO */
        // $texto = trim(noHtml($row['descricao']));
        // $texto = wordwrap($texto, 65, "\n", true);
        
        $texto = trim(noHtml($row['descricao']));
        $texto = wordwrap($texto, 110, "\n", true);
        $texto = "<pre>" . (new \Html2Text\Html2Text($texto))->getText() . "</pre>";
        // $texto = (new \Html2Text\Html2Text($texto))->getText();



        /* COR DO BADGE DA PRIORIDADE */
        if (!isset($row['cor'])) {
            $COR = '#CCCCCC';
        } else {
            $COR = $row['cor'];
        }

        $cor_font = "#000000";
        if (isset($row['cor_fonte']) && !empty($row['cor_fonte'])) {
            $cor_font = $row['cor_fonte'];
        }


        $referenceDate = (!empty($row['oco_real_open_date']) ? $row['oco_real_open_date'] : $row['data_abertura']);
        $dataAtendimento = $row['data_atendimento']; //data da primeira resposta ao chamado
        $dataFechamento = $row['data_fechamento'];

        /* NOVOS MÉTODOS PARA O CÁLCULO DE TEMPO VÁLIDO DE RESPOSTA E SOLUÇÃO */
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
        $ticketTimeInfo = getTicketTimeInfo($conn, $newWT, $newWTResponse, $row['numero'], $referenceDate, $dataAtendimento, $dataFechamento, $row['status_cod'], $objWT);

        /* Retorna os leds indicativos (bolinhas) para os tempos de resposta e solução */
        $ledSlaResposta = showLedSLA($ticketTimeInfo['response']['seconds'], $percLimit, $row['sla_resposta_tempo']);
        $ledSlaSolucao = showLedSLA($ticketTimeInfo['solution']['seconds'], $percLimit, $row['sla_solucao_tempo']);

        /* Texto sobre os SLAs - para serem imprimíveis */
        $textSlaResposta = $textSlaColumn[$ledSlaResposta];
        $textSlaSolucao = $textSlaColumn[$ledSlaSolucao];

        $isRunning = $ticketTimeInfo['running'];

        $colTVNew = $ticketTimeInfo['solution']['time'];
        if ($row['status_cod'] == 4) {
            $colTVNew = $iconTicketClosed . "&nbsp;" . $colTVNew;
        } elseif (isTicketFrozen($conn, $row['numero'])) {
            $colTVNew = $iconFrozen . "&nbsp;" . $colTVNew;
        } elseif (!$isRunning) {
            $colTVNew = $iconOutOfWorktime . "&nbsp;" . $colTVNew;
        }

        
        /* Checagem sobre o filtro de SLAs */
        // $showRecord = true;
        $responseResult = getSlaResult($ticketTimeInfo['response']['seconds'], $percLimit, $row['sla_resposta_tempo']);
        $solutionResult = getSlaResult($ticketTimeInfo['solution']['seconds'], $percLimit, $row['sla_solucao_tempo']);
        $absoluteTime = absoluteTime($referenceDate, (!empty($dataFechamento) ? $dataFechamento : date('Y-m-d H:i:s')))['inTime'];
        $absServiceTime = absoluteTime((!empty($dataAtendimento) ? $dataAtendimento : $referenceDate), (!empty($dataFechamento) ? $dataFechamento : date('Y-m-d H:i:s')))['inTime'];

        $solution_from_response_seconds = $ticketTimeInfo['solution']['seconds'] - $ticketTimeInfo['response']['seconds'];

        if ($solution_from_response_seconds != 0) {
            $solution_from_response_time = secToTime($solution_from_response_seconds)['verbose'];
        } else {
            $solution_from_response_time = $ticketTimeInfo['solution']['time'];
        }

    }

    /** 
     * Processamento para consulta sobre os campos personalizados
    */
    if ($showRecord && count($customTerms)) {
        foreach ($customTerms as $id => $op) {

            $isNumber = false;
            $isDate = false;
            $ticketFieldValues = getTicketCustomFields($conn, $row['numero'], $id);
            if ($ticketFieldValues['field_type'] == 'date') {
                /* campo de data */
                $isDate = true;
            } elseif ($ticketFieldValues['field_type'] == 'number') {
                /* campo numérico */
                $isNumber = true;
            }
            $ticketFieldValue = $ticketFieldValues['field_value_idx'];

            
            foreach ($op as $operation => $values) {

                if ($showRecord) {

                    $foundOne = false;
                    foreach ($values as $value) {

                        if ($operation == "!=" && $showRecord) {
                            /* não vazio */
                            $showRecord = (!empty($ticketFieldValue));

                        } elseif ($operation == "==" && $showRecord) {
                            /* vazio */
                            $showRecord = (empty($ticketFieldValue));

                        } elseif ($operation == "===" && $showRecord) {
                            /* Campos de comparação direta do valor - Tipo texto*/
                            $showRecord = ($ticketFieldValue == $value);

                        } elseif ($operation == "IN") {
                            /* valor do post */

                            $expMultiValues = (!empty($ticketFieldValue) ? explode(',', $ticketFieldValue) : []);
                            foreach ($expMultiValues as $SepValue) {
                                if ($SepValue == $value) {
                                    $foundOne = true;
                                }
                            }

                            $showRecord = $foundOne;
                            
                        } elseif ($operation == "<=" && $showRecord) {
                            /* A data pesquisada tem que ser menor ou igual à data gravada */

                            if ($isNumber) {
                                if (!empty($ticketFieldValue)) {
                                    $baseValue = "";

                                    if (filter_var($value, FILTER_VALIDATE_INT)) {
                                        $baseValue = $value;
                                    } else {
                                        $showRecord = false;
                                    }

                                    if (!($baseValue <= $ticketFieldValue)) {
                                        $showRecord = false;
                                    }
                                } else {
                                    $showRecord = false;
                                }
                            } elseif ($isDate) {
                                if (!empty($ticketFieldValue)) {
                                    $baseDate = "";
                                    if (isValidDate($value, "d/m/Y")) {
                                        $baseDate = dateDB($value);
                                    } else {
                                        $showRecord = false;
                                    }

                                    if (!(strtotime($baseDate) <= strtotime($ticketFieldValue))) {
                                        $showRecord = false;
                                    }
                                } else {
                                    $showRecord = false;
                                }
                            } else {
                                /* datetime */
                                if (!empty($ticketFieldValue)) {
                                    $baseDate = "";
                                    if (isValidDate($value, "d/m/Y H:i")) {
                                        $baseDate = dateDB($value);
                                    } else {
                                        $showRecord = false;
                                    }

                                    if (!(strtotime($baseDate) <= strtotime($ticketFieldValue))) {
                                        $showRecord = false;
                                    }
                                } else {
                                    $showRecord = false;
                                }
                            }
                            
                            
                            
                        } elseif ($operation == ">=" && $showRecord) {
                            
                            if ($isNumber) {
                                if (!empty($ticketFieldValue)) {
                                    $baseValue = "";

                                    if (filter_var($value, FILTER_VALIDATE_INT)) {
                                        $baseValue = $value;
                                    } else {
                                        $showRecord = false;
                                    }

                                    if (!($baseValue >= $ticketFieldValue)) {
                                        $showRecord = false;
                                    }
                                } else {
                                    $showRecord = false;
                                }
                            }
                            
                            
                            /* A data pesquisada tem que ser maior ou igual à data gravada */
                            elseif ($isDate) {
                                if (!empty($ticketFieldValue)) {
                                    $baseDate = "";
                                    if (isValidDate($value, "d/m/Y")) {
                                        $baseDate = dateDB($value . " 23:59:59");
                                    } else {
                                        $showRecord = false;
                                    }

                                    if (!(strtotime($baseDate) >= strtotime($ticketFieldValue))) {
                                        $showRecord = false;
                                    }
                                } else {
                                    $showRecord = false;
                                }
                            } else {
                                /* Datetime */
                                if (!empty($ticketFieldValue)) {
                                    $baseDate = "";
                                    if (isValidDate($value, "d/m/Y H:i")) {
                                        $baseDate = dateDB($value);
                                    } else {
                                        $showRecord = false;
                                    }

                                    if (!(strtotime($baseDate) >= strtotime($ticketFieldValue))) {
                                        $showRecord = false;
                                    }
                                } else {
                                    $showRecord = false;
                                }
                            }
                            
                        }
                    }
                }
            }
        }
    }
    /** Final do processamento sobre consulta por campos personalizados */



    if ($showRecord) {
        if (isset($post['response_sla']) && !empty($post['response_sla'])) {
            $showRecord = false;
            foreach ($post['response_sla'] as $res) {

                if ($res == $responseResult )
                    $showRecord = true;
            }
        }
    }
    

    if ($showRecord) {
        if (isset($post['solution_sla']) && !empty($post['solution_sla'])) {
            $showRecord = false;
            foreach ($post['solution_sla'] as $res) {
                if ($res == $solutionResult )
                    $showRecord = true;
            }
        } 
    }
    

    if ($showRecord) {

        $array_solution = [];
        $tech_description = "";
        $solution = "";
        if ($row['status_cod'] == 4) {
            $array_solution = getSolutionInfo($conn, $row['numero']);
            if (!empty($array_solution)) {
                $tech_description = $array_solution['problema'];
                $solution = $array_solution['solucao'];
            }
            
        }

        $tags = strToTags($row['oco_tag'], 3);

        $channel = ($row['oco_channel'] ? getChannels($conn, $row['oco_channel'])['name'] : '');

        $rateKey = getTicketRate($conn, $row['numero']);
        // $ticketRate = ($rateKey ? '<span class="badge text-white '.$rateClasses[$rateKey].'">'.$rateLabels[$rateKey].'</span>' : "");
        $isDone = ($config['conf_status_done'] == $row['status_cod'] ? true : false);
        $isRequester = ($_SESSION['s_uid'] == $row['aberto_por_cod'] ? true : false);
        $ratedInfo = getRatedInfo($conn, $row['numero']);
        // $isRejected = (!empty($ratedInfo) && empty($ratedInfo['rate']) && !$isDone);
        $isRejected = isRejected($conn, $row['numero']);

        $renderedRate = renderRate($rateKey, $isDone, $isRequester, $isRejected);

        $rejected_count = getRejectedCount($conn, $row['numero']);
        $col_rejected_count = ($rejected_count > 0 ? "<b>" . $rejected_count . "</b>": '');

        ?>
        <tr>
            <td class="line" data-sort="<?= $row['numero']; ?>"><span class="pointer" onClick="openTicketInfo('<?= $row['numero']; ?>')"><?= "{$imgSub}&nbsp;<b>" . $row['numero'] . "</b>"; ?></span></td>
            <td class="line"><?= "<b>" . $row['nickname'] . "</b>"; ?></td>
            <td class="line"><?= "<b>" . $row['area_solicitante'] . "</b>"; ?></td>
            <td class="line"><?= "<b>" . $row['area'] . "</b>"; ?></td>
            <td class="line"><?= $linkImg."&nbsp;".$row['problema']; ?></td>
            <td class="line"><?= "<b>" . $row['aberto_por'] . "</b>"; ?></td>
            <td class="line"><?= "<b>" . $channel . "</b>"; ?></td>
            <td class="line"><?= "<b>" . $row['contato'] . "</b>"; ?></td>
            <td class="line"><?= "<b>" . $row['contato_email'] . "</b>"; ?></td>
            <td class="line"><?= "<b>" . $row['telefone'] . "</b>"; ?></td>
            <td class="line"><?= "<b>" . $row['setor'] . "</b>"; ?></td>
            <td class="line"><?= $texto; ?></td>
            <td class="line"><?= $tech_description; ?></td>
            <td class="line"><?= $solution; ?></td>
            <td class="line"><?= "<b>" . $listWorkers . "</b>"; ?></td>
            <?php
                $mydate = strtotime($row['oco_real_open_date']);
            ?>
            <td class="line" data-sort="<?= $mydate; ?>"><?= "<b>" . dateScreen($row['oco_real_open_date']) . "</>"; ?></td>
            <td class="line"><?= "<b>" . transbool($row['oco_scheduled']) . "</>"; ?></td>
            <td class="line" data-sort="<?= $row['oco_scheduled_to']; ?>"><?= "<b>" . dateScreen($row['oco_scheduled_to']) . "</b>"; ?></td>
            <td class="line" data-sort="<?= $row['data_atendimento']; ?>"><?= "<b>" . dateScreen($row['data_atendimento']) . "</b>"; ?></td>
            <td class="line" data-sort="<?= $row['data_fechamento']; ?>"><?= "<b>" . dateScreen($row['data_fechamento']) . "</b>"; ?></td>
            <td class="line"><?= "<b>" . $row['unidade'] . "</b>"; ?></td>
            <td class="line"><?= "<b>" . $row['etiqueta'] . "</b>"; ?></td>
            <td class="line"><?= "<b>" . $row['chamado_status'] . "</b>"; ?></td>
            <td class="line"><?= $absoluteTime; ?></td>
            <td class="line" data-sort="<?= $ticketTimeInfo['solution']['seconds']; ?>"><?= $colTVNew; ?></td>
            <td class="line"><?= $absServiceTime; ?></td>
            <td class="line"><?= $solution_from_response_time; ?></td>
            <td class="line" data-sort="<?= $row['pr_atendimento']; ?>"><?= "<span class='badge p-2' style='color: " . $cor_font . "; background-color: " . $COR . "'>" . $row['pr_descricao'] . "</span>"; ?></td>
            <td class="line"><?= $renderedRate; ?></td>
            <td class="line"><?= $col_rejected_count; ?></td>
            <td class="line"><?= "<img height='20' src='" . $imgsPath . "" . $ledSlaResposta . "' title='" . TRANS('HNT_RESPONSE_LED') . "'>&nbsp;<img height='20' src='" . $imgsPath . "" . $ledSlaSolucao . "' title='" . TRANS('HNT_SOLUTION_LED') . "'>"; ?></td>
            <td class="line"><?= $textSlaResposta; ?></td>
            <td class="line"><?= $textSlaSolucao; ?></td>
            <td class="line"><?= $tags; ?></td>

            <?php
                /* Valores do Campos customizados */
                foreach ($custom_fields_full as $cfield) {

                    if (!in_array($cfield['id'], $dontRender)) {
                    
                        $cfield_values = getTicketCustomFields($conn, $row['numero'], $cfield['id']);

                        $showField = $cfield_values['field_value'];

                        if ($cfield['field_type'] == 'date') {
                            $showField = dateScreen($cfield_values['field_value'], 1);
                        } elseif ($cfield['field_type'] == 'datetime') {
                            $showField = dateScreen($cfield_values['field_value'], 0, "d/m/Y H:i");
                        }
                        ?>
                        <td class="line custom_field">
                            <?= $showField; ?>
                        </td>
                        <?php
                    }
                }
            ?>
            
        </tr>
        <?php
    } else {
        $totalFiltered--;
    }
}
?>
        </table>
        <div class="d-none" id="table_info_hidden">
            <div class="row"> <!-- d-none -->
                <div class="col-12"><?= TRANS('WERE_FOUND'); ?> <span class="bold"><?= $totalFiltered; ?></span> <?= TRANS('POSSIBLE_RECORDS_ACORDING_TO_FOLLOW'); ?> <span class="bold"><?= TRANS('SMART_SEARCH_CRITERIA'); ?>:</span></div>
            </div>
            <div class="row p-2 mt-2" id="divCriterios">
                <div class="col-10">
                    <?= $criterios; ?>
                </div>
            </div>

        </div>

    </div>
