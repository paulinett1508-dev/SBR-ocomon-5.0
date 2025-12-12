<?php
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
 */ session_start();

if (!isset($_SESSION['s_logado']) || $_SESSION['s_logado'] == 0) {
    $_SESSION['session_expired'] = 1;
    echo "<script>top.window.location = '../../index.php'</script>";
    exit;
}

require_once __DIR__ . "/" . "../../includes/include_geral_new.inc.php";
require_once __DIR__ . "/" . "../../includes/classes/ConnectPDO.php";

use includes\classes\ConnectPDO;

$conn = ConnectPDO::getInstance();

$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 2, 1);

$_SESSION['s_page_ocomon'] = $_SERVER['PHP_SELF'];

$sess_client = (isset($_SESSION['s_rep_filters']['client']) ? $_SESSION['s_rep_filters']['client'] : '');
$sess_area = (isset($_SESSION['s_rep_filters']['area']) ? $_SESSION['s_rep_filters']['area'] : '-1');
$sess_d_ini = (isset($_SESSION['s_rep_filters']['d_ini']) ? $_SESSION['s_rep_filters']['d_ini'] : date('01/m/Y'));
$sess_d_fim = (isset($_SESSION['s_rep_filters']['d_fim']) ? $_SESSION['s_rep_filters']['d_fim'] : date('d/m/Y'));
$sess_state = (isset($_SESSION['s_rep_filters']['state']) ? $_SESSION['s_rep_filters']['state'] : 1);



$config = getConfig($conn);
$doneStatus = $config['conf_status_done'];

$filter_areas = "";
$areas_names = "";
if (isAreasIsolated($conn) && $_SESSION['s_nivel'] != 1) {
    /* Visibilidade isolada entre áreas para usuários não admin */
    $u_areas = $_SESSION['s_uareas'];
    $filter_areas = " AND sis_id IN ({$u_areas}) ";

    $array_areas_names = getUserAreasNames($conn, $u_areas);

    foreach ($array_areas_names as $area_name) {
        if (strlen((string)$areas_names))
            $areas_names .= ", ";
        $areas_names .= $area_name;
    }
}

$json = 0;
$json2 = 0;
$json3 = 0;
$json4 = 0;

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../../includes/css/estilos.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/jquery/datetimepicker/jquery.datetimepicker.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap/custom.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/fontawesome/css/all.min.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap-select/dist/css/bootstrap-select.min.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/css/my_bootstrap_select.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/css/switch_radio.css" />

    <style>
        .chart-container {
            position: relative;
            /* height: 100%; */
            max-width: 100%;
            margin-left: 10px;
            margin-right: 10px;
            margin-bottom: 30px;
        }
    </style>

    <title>OcoMon&nbsp;<?= VERSAO; ?></title>
</head>

<body>
    
    <div class="container">
        <div id="idLoad" class="loading" style="display:none"></div>
    </div>


    <div class="container">
        <h5 class="my-4"><i class="fas fa-ticket-alt text-secondary"></i>&nbsp;<?= TRANS('TTL_REP_QTD_CALL_AREA_PERIOD_PLUS'); ?></h5>
        <div class="modal" id="modal" tabindex="-1" style="z-index:9001!important">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div id="divDetails">
                    </div>
                </div>
            </div>
        </div>

        <?php
        if (isset($_SESSION['flash']) && !empty($_SESSION['flash'])) {
            echo $_SESSION['flash'];
            $_SESSION['flash'] = '';
        }


        if (!isset($_POST['action'])) {

        ?>
            <form method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form">
                <div class="form-group row my-4">


                    <label for="client" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CLIENT'); ?></label>
                    <div class="form-group col-md-10">
                        <select class="form-control bs-select" id="client" name="client">
                            <option value="" selected><?= TRANS('ALL'); ?></option>
                            <?php
                                $clients = getClients($conn);
                                foreach ($clients as $client) {
                                    ?>
                                    <option value="<?= $client['id']; ?>"
                                    <?= ($client['id'] == $sess_client ? ' selected' : ''); ?>
                                    ><?= $client['nickname']; ?></option>
                                    <?php
                                }
                            ?>
                        </select>
                    </div>
                    
                    
                    <label for="area" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('RESPONSIBLE_AREA'); ?></label>
                    <div class="form-group col-md-10">
                        <select class="form-control bs-select" id="area" name="area">
                            <option value="-1"><?= TRANS('ALL'); ?></option>
                            <?php
                            $sql = "SELECT * FROM sistemas WHERE sis_atende = 1 {$filter_areas} AND sis_status NOT IN (0) ORDER BY sistema";
                            $resultado = $conn->query($sql);
                            foreach ($resultado->fetchAll() as $rowArea) {
                                print "<option value='" . $rowArea['sis_id'] . "'";
                                echo ($rowArea['sis_id'] == $sess_area ? ' selected' : '');
                                print ">" . $rowArea['sistema'] . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <label for="d_ini" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('START_DATE'); ?></label>
                    <div class="form-group col-md-10">
                        <input type="text" class="form-control " id="d_ini" name="d_ini" value="<?= $sess_d_ini; ?>" autocomplete="off" required />
                    </div>

                    <label for="d_fim" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('END_DATE'); ?></label>
                    <div class="form-group col-md-10">
                        <input type="text" class="form-control " id="d_fim" name="d_fim" value="<?= $sess_d_fim; ?>" autocomplete="off" required />
                    </div>


                    <div class="row w-100"></div>
                    <div class="form-group col-md-8 d-none d-md-block">
                    </div>
                    <div class="form-group col-12 col-md-2 ">

                        <input type="hidden" name="action" value="search">
                        <button type="submit" id="idSubmit" name="submit" class="btn btn-primary btn-block"><?= TRANS('BT_SEARCH'); ?></button>
                    </div>
                    <div class="form-group col-12 col-md-2">
                        <button type="reset" class="btn btn-secondary btn-block" onClick="parent.history.back();"><?= TRANS('BT_CANCEL'); ?></button>
                    </div>
                    

                </div>
            </form>
            <?php
        } else {

            // var_dump($_POST);

            $hora_inicio = ' 00:00:00';
            $hora_fim = ' 23:59:59';
            $terms = "";

            $client = (isset($_POST['client']) && !empty($_POST['client']) ? $_POST['client'] : "");
            $_SESSION['s_rep_filters']['client'] = $client;
            $_SESSION['s_rep_filters']['area'] = $_POST['area'];
            $clientName = (!empty($client) ? getClients($conn, $client)['nickname']: "");
            $clausule = (!empty($client) ? " AND o.client IN ({$client}) " : "");
            $noneClient = TRANS('FILTERED_CLIENT') . ": " . TRANS('NONE_FILTER') . "&nbsp;&nbsp;";
            $terms = (!empty($client) ? TRANS('FILTERED_CLIENT') . ": {$clientName}&nbsp;&nbsp;" : $noneClient );

            if ((!isset($_POST['d_ini'])) || (!isset($_POST['d_fim']))) {
                $_SESSION['flash'] = message('info', '', 'O período deve ser informado', '');
                redirect($_SERVER['PHP_SELF']);
            } else {

                $d_ini = $_POST['d_ini'] . $hora_inicio;
                $d_ini = dateDB($d_ini);

                $d_fim = $_POST['d_fim'] . $hora_fim;
                $d_fim = dateDB($d_fim);

                if ($d_ini <= $d_fim) {

                    $_SESSION['s_rep_filters']['d_ini'] = $_POST['d_ini'];
                    $_SESSION['s_rep_filters']['d_fim'] = $_POST['d_fim'];

                    if (!empty($filter_areas)) {
                        /* Nesse caso o usuário só pode filtrar por áreas que faça parte */
                        if (!empty($_POST['area']) && ($_POST['area'] != -1)) {

                            $query_areas = "SELECT  * FROM sistemas WHERE sis_status NOT IN (0) AND sis_atende = 1 AND sis_id = '" . $_POST['area'] . "' ORDER BY sistema";
            
                            $getAreaName = "SELECT * from sistemas where sis_id = " . $_POST['area'] . "";
                            $exec = $conn->query($getAreaName);
                            $rowAreaName = $exec->fetch();
                            $nomeArea = $rowAreaName['sistema'];
                            $terms .= TRANS('FILTERED_AREA') . ": {$nomeArea}";
                        } else {
                            $query_areas = "SELECT  * FROM sistemas WHERE sis_status NOT IN (0) AND sis_atende = 1 AND sis_id IN ({$u_areas}) ORDER BY sistema";
                            $terms .= TRANS('FILTERED_AREA') . ": [" . $areas_names . "]";
                        }
                    } else
                    
                    
                    if (isset($_POST['area']) && (($_POST['area'] == -1) || empty($_POST['area']))) {
                        $query_areas = "SELECT  * FROM sistemas WHERE sis_status NOT IN (0) AND sis_atende = 1 ORDER BY sistema";
                        $terms .= TRANS('FILTERED_AREA') . ": " . TRANS('NONE_FILTER');
                    } else
                    if (isset($_POST['area']) && !empty($_POST['area']) && $_POST['area'] != -1) {
                        $query_areas = "SELECT  * FROM sistemas WHERE sis_id IN (" . $_POST['area'] . ") ORDER BY sistema";

                        $getAreaName = "SELECT sistema FROM sistemas WHERE sis_id = " . $_POST['area'] . " ";
                        $resGetArea = $conn->query($getAreaName);
                        $terms .= TRANS('FILTERED_AREA') . ": ". $resGetArea->fetch()['sistema'];
                    }
                    $exec_qry_areas = $conn->query($query_areas);
                    $exec_qry_areas_2 = $conn->query($query_areas);
                    $linhas = $exec_qry_areas->rowCount();
        
                    if ($linhas == 0) {
                        $_SESSION['flash'] = message('info', '', TRANS('MSG_NO_DATA_IN_PERIOD'), '');
                        redirect($_SERVER['PHP_SELF']);
                    } else {

                        $data = [];
                        $data2 = [];
                        $data3 = [];
                        $data4 = [];
                        $total = 0;

                        $totalOpened = 0;
                        $totalDone = 0;
                        $totalClosed = 0;
                        $totalStarted = 0;
                        $totalAutoClosed = 0;
                        $totalRated = 0;

                        $tOpened = 0;
                        $tDone = 0;
                        $tClosed = 0;
                        $tStarted = 0;
                        $tAutoClosed = 0;
                        $tRated = 0;

                        $totalGreat = 0;
                        $totalGood = 0;
                        $totalRegular = 0;
                        $totalBad = 0;
                        $totalNotRated = 0;

                        $tGreat = 0;
                        $tGood = 0;
                        $tRegular = 0;
                        $tBad = 0;
                        $tNotRated = 0;


                        ?>
                        <p><?= TRANS('TTL_PERIOD_FROM') . "&nbsp;" . dateScreen($d_ini, 1) . "&nbsp;" . TRANS('DATE_TO') . "&nbsp;" . dateScreen($d_fim, 1); ?></p>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <!-- table-hover -->
                                <caption><?= $terms; ?><span class="px-2 new_search" style="float:right" id="new_search"><?= TRANS('NEW_SEARCH'); ?></span></caption>
                                <thead>
                                    <tr class="header table-borderless">
                                        <td class="line"><?= mb_strtoupper(TRANS('SERVICE_AREA')); ?></td>
                                        <td class="line"><?= mb_strtoupper(TRANS('OPENED')); ?></td>
                                        <td class="line"><?= mb_strtoupper(TRANS('STARTED')); ?></td>
                                        <td class="line"><?= mb_strtoupper(TRANS('DONE')); ?></td>
                                        <td class="line"><?= mb_strtoupper(TRANS('COL_CLOSED')); ?></td>
                                        <td class="line"><?= mb_strtoupper(TRANS('AUTOMATIC_CLOSED')); ?></td>
                                        <td class="line"><?= mb_strtoupper(TRANS('RATED')); ?></td>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $i = 0;
                                    foreach ($exec_qry_areas->fetchall() as $row) {
                                        
                                        /**
                                         * Quantidade de chamados abertos no período
                                         */
                                        $sqlOpened = "SELECT 
                                                count(*) AS abertos, 
                                                s.sistema AS area
                                            FROM 
                                                ocorrencias AS o, 
                                                sistemas AS s, 
                                                `status` AS st
                                            WHERE 
                                                o.status = st.stat_id AND 
                                                st.stat_ignored <> 1 AND 
                                                o.sistema = s.sis_id AND 
                                                o.data_abertura >= '{$d_ini}' AND
                                                o.data_abertura <= '{$d_fim}' AND 
                                                s.sis_id IN ({$row['sis_id']}) 
                                                {$clausule}
                                            GROUP BY 
                                                area";
                                        $resOpened = $conn->query($sqlOpened);
                                        $rowOpened = $resOpened->fetch();
                        


                                        /**
                                         * Quantidade de chamados com atendimento iniciado no período
                                         */
                                        $sqlStarted = "SELECT 
                                                        count(*) AS iniciados, 
                                                        s.sistema AS area, 
                                                        s.sis_id
                                            FROM 
                                                ocorrencias AS o, 
                                                sistemas AS s, 
                                                `status` AS st
                                            WHERE 
                                                o.status = st.stat_id AND 
                                                st.stat_ignored <> 1 AND 
                                                o.sistema = s.sis_id AND 
                                                o.data_atendimento >= '{$d_ini}' AND
                                                o.data_atendimento <= '{$d_fim}' AND 
                                                s.sis_id IN ({$row['sis_id']})  
                                                {$clausule}
                                            GROUP BY 
                                                area, 
                                                s.sis_id";
                                        $resStarted = $conn->query($sqlStarted);
                                        $rowStarted = $resStarted->fetch();



                                        /** 
                                         * Quantidade de chamados concluídos (concluidos e encerrados) no período
                                         */
                                        $sqlDone = "SELECT 
                                                count(*) AS concluidos, 
                                                s.sistema AS area, 
                                                s.sis_id
                                            FROM 
                                                ocorrencias AS o, 
                                                sistemas AS s, 
                                                `status` AS st
                                            WHERE 
                                                o.status = st.stat_id AND 
                                                o.status IN (4, {$doneStatus}) AND
                                                st.stat_ignored <> 1 AND 
                                                o.sistema = s.sis_id AND 
                                                o.data_fechamento >= '{$d_ini}' AND
                                                o.data_fechamento <= '{$d_fim}' AND 
                                                s.sis_id IN ({$row['sis_id']})  
                                                {$clausule}
                                            GROUP BY 
                                                area, 
                                                s.sis_id";
                                        $resDone = $conn->query($sqlDone);
                                        $rowDone = $resDone->fetch();


                                        /** 
                                         * Quantidade de chamados encerrados no período 
                                         */
                                        $sqlClosed = "SELECT 
                                                        count(*) AS fechados, 
                                                        s.sistema AS area, 
                                                        s.sis_id
                                            FROM 
                                                ocorrencias AS o, 
                                                sistemas AS s, 
                                                `status` AS st
                                            WHERE 
                                                o.status = st.stat_id AND 
                                                o.status = 4 AND
                                                st.stat_ignored <> 1 AND 
                                                o.sistema = s.sis_id AND 
                                                o.data_fechamento >= '{$d_ini}' AND
                                                o.data_fechamento <= '{$d_fim}' AND 
                                                s.sis_id IN ({$row['sis_id']})  
                                                {$clausule}
                                            GROUP BY 
                                                area, 
                                                s.sis_id";
                                        $resClosed = $conn->query($sqlClosed);
                                        $rowClosed = $resClosed->fetch();
                        

                                        
                                        /** 
                                         * Quantidade de chamados encerrados, de forma automática, no período 
                                         */
                                        $sqlAutoClosed = "SELECT 
                                                        count(*) AS auto_fechados, 
                                                        s.sistema AS area, 
                                                        s.sis_id
                                            FROM 
                                                ocorrencias AS o, 
                                                tickets_rated tr,
                                                sistemas AS s, 
                                                `status` AS st
                                            WHERE 
                                                o.status = st.stat_id AND 
                                                o.status = 4 AND
                                                st.stat_ignored <> 1 AND 
                                                o.sistema = s.sis_id AND 
                                                o.numero = tr.ticket AND
                                                tr.rate IS NOT NULL AND
                                                tr.automatic_rate = 1 AND 
                                                tr.rate_date >= '{$d_ini}' AND 
                                                tr.rate_date <= '{$d_fim}' AND
                                                s.sis_id IN ({$row['sis_id']})  
                                                {$clausule}
                                            GROUP BY 
                                                area, 
                                                s.sis_id";
                                        $resAutoClosed = $conn->query($sqlAutoClosed);
                                        $rowAutoClosed = $resAutoClosed->fetch();



                                        /** 
                                         * Quantidade de chamados avaliados no período 
                                         */
                                        $sqlRated = "SELECT 
                                                        COUNT(tr.rate) AS avaliados, 
                                                        s.sistema AS area, 
                                                        s.sis_id
                                            FROM 
                                                ocorrencias AS o, 
                                                tickets_rated tr,
                                                sistemas AS s, 
                                                `status` AS st
                                            WHERE 
                                                o.status = st.stat_id AND 
                                                o.status = 4 AND
                                                st.stat_ignored <> 1 AND 
                                                o.sistema = s.sis_id AND 
                                                o.numero = tr.ticket AND
                                                tr.rate IS NOT NULL AND
                                                tr.rate_date >= '{$d_ini}' AND 
                                                tr.rate_date <= '{$d_fim}' AND
                                                s.sis_id IN ({$row['sis_id']})  
                                                {$clausule}
                                            GROUP BY 
                                                area, 
                                                s.sis_id";
                                        $resRated = $conn->query($sqlRated);
                                        $rowRated = $resRated->fetch();



                                        /**
                                         * Operações para contabilizar a quantidade de chamados em cada situação para cada área
                                         */
                                        $tOpened += $totalOpened += $rowOpened['abertos'] ?? 0;
                                        $tStarted += $totalStarted += $rowStarted['iniciados'] ?? 0;
                                        $tDone += $totalDone += $rowDone['concluidos'] ?? 0;
                                        $tClosed += $totalClosed += $rowClosed['fechados'] ?? 0;
                                        $tAutoClosed += $totalAutoClosed += $rowAutoClosed['auto_fechados'] ?? 0;
                                        $tRated += $totalRated += $rowRated['avaliados'] ?? 0;
                        
                                        $data[$i]['area'] = $row['sistema'];
                                        $data[$i]['abertos'] = $totalOpened;
                                        $data[$i]['iniciados'] = $totalStarted;
                                        $data[$i]['concluidos'] = $totalDone;
                                        $data[$i]['fechados'] = $totalClosed;
                                        $data[$i]['auto_fechados'] = $totalAutoClosed;
                                        $data[$i]['avaliados'] = $totalRated;
                                        

                                        $totalOpened = 0;
                                        $totalStarted = 0;
                                        $totalDone = 0;
                                        $totalClosed = 0;
                                        $totalAutoClosed = 0;
                                        $totalRated = 0;

                                        

                                        ?>
                                        <tr>
                                            <td class="line"><?= $data[$i]['area']; ?></td>
                                            <td class="line"><?= $data[$i]['abertos']; ?></td>
                                            <td class="line"><?= $data[$i]['iniciados']; ?></td>
                                            <td class="line"><?= $data[$i]['concluidos']; ?></td>
                                            <td class="line"><?= $data[$i]['fechados']; ?></td>
                                            <td class="line"><?= $data[$i]['auto_fechados']; ?></td>
                                            <td class="line"><?= $data[$i]['avaliados']; ?></td>
                                        </tr>
                                        <?php
                                        $i++;
                                    } /* Final do loop das áreas */

                                    /* Fora do loop das áreas */
                                    $data2['opened']['label'] = TRANS('OPENED');
                                    $data2['opened']['total'] = $tOpened;
                                    $data2['started']['label'] = TRANS('STARTED');
                                    $data2['started']['total'] = $tStarted;
                                    $data2['done']['label'] = TRANS('DONE');
                                    $data2['done']['total'] = $tDone;
                                    $data2['closed']['label'] = TRANS('COL_CLOSED');
                                    $data2['closed']['total'] = $tClosed;
                                    $data2['autoClosed']['label'] = TRANS('AUTOMATIC_CLOSED');
                                    $data2['autoClosed']['total'] = $tAutoClosed;
                                    $data2['rated']['label'] = TRANS('RATED');
                                    $data2['rated']['total'] = $tRated;

                                    $json = json_encode($data);
                                    $json2 = json_encode($data2);
                                    
                                    ?>
                                </tbody>
                                <tfoot>
                                    <tr class="header table-borderless">
                                        <td><?= TRANS('TOTAL'); ?></td>
                                        <td ><?= $tOpened; ?></td>
                                        <td ><?= $tStarted; ?></td>
                                        <td ><?= $tDone; ?></td>
                                        <td ><?= $tClosed; ?></td>
                                        <td ><?= $tAutoClosed; ?></td>
                                        <td ><?= $tRated; ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="chart-container">
                            <canvas id="canvasChart1"></canvas>
                        </div>
                        <div class="chart-container">
                            <canvas id="canvasChart2"></canvas>
                        </div>


                        <!-- Tabela de avaliações por área no período -->
                        <h5 class="my-4"><i class="fas fa-star-half-alt text-secondary"></i>&nbsp;<?= TRANS('TICKETS_BY_RATE'); ?></h5>
                        <p><?= TRANS('TTL_PERIOD_FROM') . "&nbsp;" . dateScreen($d_ini, 1) . "&nbsp;" . TRANS('DATE_TO') . "&nbsp;" . dateScreen($d_fim, 1); ?></p>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <caption><?= $terms; ?><span class="px-2 new_search" style="float:right" id="new_search2"><?= TRANS('NEW_SEARCH'); ?></span></caption>
                                <thead>
                                    <tr class="header table-borderless">
                                        <td class="line"><?= mb_strtoupper(TRANS('SERVICE_AREA')); ?></td>
                                        <td class="line"><?= mb_strtoupper(TRANS('ASSESSMENT_GREAT')); ?></td>
                                        <td class="line"><?= mb_strtoupper(TRANS('ASSESSMENT_GOOD')); ?></td>
                                        <td class="line"><?= mb_strtoupper(TRANS('ASSESSMENT_REGULAR')); ?></td>
                                        <td class="line"><?= mb_strtoupper(TRANS('ASSESSMENT_BAD')); ?></td>
                                        <td class="line"><?= mb_strtoupper(TRANS('NOT_RATED_IN_TIME')); ?></td>
                                    </tr>
                                </thead>
                                <tbody>
                            <?php
                            $i = 0;
                            foreach ($exec_qry_areas_2->fetchall() as $row) {
                                
                                /**
                                 * Avaliações dos chamados por área
                                 */
                                $sqlRates = "SELECT 
                                        s.sistema AS area, 
                                        tr.rate, count(tr.rate) as rates,
                                        s.sis_id
                                    FROM 
                                        ocorrencias AS o, 
                                        tickets_rated tr,
                                        sistemas AS s, 
                                        `status` AS st
                                    WHERE 
                                        o.status = st.stat_id AND 
                                        o.status = 4 AND
                                        st.stat_ignored <> 1 AND 
                                        o.sistema = s.sis_id AND 
                                        o.numero = tr.ticket AND
                                        tr.rate IS NOT NULL AND
                                        tr.rate_date >= '{$d_ini}' AND 
                                        tr.rate_date <= '{$d_fim}' AND
                                        s.sis_id IN ({$row['sis_id']})  
                                        {$clausule}
                                    GROUP BY 
                                        area, 
                                        tr.rate,
                                        s.sis_id";

                                $resRates = $conn->query($sqlRates);

                                foreach ($resRates->fetchAll() as $rowRates) {

                                    $totalGreat += ($rowRates['rate'] == 'great' ? $rowRates['rates'] : 0) ?? 0;
                                    $totalGood += ($rowRates['rate'] == 'good' ? $rowRates['rates'] : 0) ?? 0;
                                    $totalRegular += ($rowRates['rate'] == 'regular' ? $rowRates['rates'] : 0) ?? 0;
                                    $totalBad += ($rowRates['rate'] == 'bad' ? $rowRates['rates'] : 0) ?? 0;
                                    $totalNotRated += ($rowRates['rate'] == 'not_rated' ? $rowRates['rates'] : 0) ?? 0;

                                    $tGreat += ($rowRates['rate'] == 'great' ? $rowRates['rates'] : 0) ?? 0;
                                    $tGood += ($rowRates['rate'] == 'good' ? $rowRates['rates'] : 0) ?? 0;
                                    $tRegular += ($rowRates['rate'] == 'regular' ? $rowRates['rates'] : 0) ?? 0;
                                    $tBad += ($rowRates['rate'] == 'bad' ? $rowRates['rates'] : 0) ?? 0;
                                    $tNotRated += ($rowRates['rate'] == 'not_rated' ? $rowRates['rates'] : 0) ?? 0;
                                }
                                
                                $data3[$i]['area'] = $row['sistema'];
                                $data3[$i]['great'] = $totalGreat;
                                $data3[$i]['good'] = $totalGood;
                                $data3[$i]['regular'] = $totalRegular;
                                $data3[$i]['bad'] = $totalBad;
                                $data3[$i]['not_rated'] = $totalNotRated;

                                $totalGreat = 0;
                                $totalGood = 0;
                                $totalRegular = 0;
                                $totalBad = 0;
                                $totalNotRated = 0;


                                ?>
                                    <tr>
                                        <td class="line"><?= $data3[$i]['area']; ?></td>
                                        <td class="line"><?= $data3[$i]['great']; ?></td>
                                        <td class="line"><?= $data3[$i]['good']; ?></td>
                                        <td class="line"><?= $data3[$i]['regular']; ?></td>
                                        <td class="line"><?= $data3[$i]['bad']; ?></td>
                                        <td class="line"><?= $data3[$i]['not_rated']; ?></td>
                                    </tr>
                                <?php

                                $i++;
                            } /* Final do loop sobre os dados de avaliação dos chamados */
                            $json3 = json_encode($data3);
                       ?>
                                    
                                </tbody>

                                <tfoot>
                                    <tr class="header table-borderless">
                                        <td><?= TRANS('TOTAL'); ?></td>
                                        <td ><?= $tGreat; ?></td>
                                        <td ><?= $tGood; ?></td>
                                        <td ><?= $tRegular; ?></td>
                                        <td ><?= $tBad; ?></td>
                                        <td ><?= $tNotRated; ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <?php
                            /* Fora do loop das áreas */
                            $data4['great']['label'] = TRANS('ASSESSMENT_GREAT');
                            $data4['great']['total'] = $tGreat;
                            $data4['good']['label'] = TRANS('ASSESSMENT_GOOD');
                            $data4['good']['total'] = $tGood;
                            $data4['regular']['label'] = TRANS('ASSESSMENT_REGULAR');
                            $data4['regular']['total'] = $tRegular;
                            $data4['bad']['label'] = TRANS('ASSESSMENT_BAD');
                            $data4['bad']['total'] = $tBad;
                            $data4['not_rated']['label'] = TRANS('NOT_RATED_IN_TIME');
                            $data4['not_rated']['total'] = $tNotRated;

                            $json4 = json_encode($data4);
                        ?>


                        <div class="chart-container">
                            <canvas id="canvasChart3"></canvas>
                        </div>
                        <div class="chart-container">
                            <canvas id="canvasChart4"></canvas>
                        </div>
                        
                        <?php

                        // echo "<pre>{$sqlRates}</pre>";
                        // var_dump([
                        //     'Json normal' => "<pre>" .$json3 . "</pre>",
                        //     'Data' => $data3
                        // ]);
                    }
                } else {
                    $_SESSION['flash'] = message('info', '', TRANS('MSG_COMPARE_DATE'), '');
                    redirect($_SERVER['PHP_SELF']);
                }
            }
        }
        ?>
        <!-- Cores para o gráfico de avaliações dos atendimentos -->
        <input type="hidden" name="color-great" class="color-great" id="color-great"/>
        <input type="hidden" name="color-good" class="color-good" id="color-good"/>
        <input type="hidden" name="color-regular" class="color-regular" id="color-regular"/>
        <input type="hidden" name="color-bad" class="color-bad" id="color-bad"/>
        <input type="hidden" name="color-not-rated" class="color-not-rated" id="color-not-rated"/>
    </div>
    <script src="../../includes/javascript/funcoes-3.0.js"></script>
    <script src="../../includes/components/jquery/jquery.js"></script>
    <script src="../../includes/components/jquery/datetimepicker/build/jquery.datetimepicker.full.min.js"></script>
    <script src="../../includes/components/bootstrap/js/bootstrap.bundle.js"></script>
    <script type="text/javascript" src="../../includes/components/chartjs/dist/Chart.min.js"></script>
    <script type="text/javascript" src="../../includes/components/chartjs/chartjs-plugin-colorschemes/dist/chartjs-plugin-colorschemes.js"></script>
    <script type="text/javascript" src="../../includes/components/chartjs/chartjs-plugin-datalabels/chartjs-plugin-datalabels.min.js"></script>
    <script src="../../includes/components/bootstrap-select/dist/js/bootstrap-select.min.js"></script>

    <script type='text/javascript'>
        $(function() {

            $.fn.selectpicker.Constructor.BootstrapVersion = '4';
            $('.bs-select').selectpicker({
                /* placeholder */
                title: "<?= TRANS('ALL', '', 1); ?>",
                liveSearch: true,
                liveSearchNormalize: true,
                liveSearchPlaceholder: "<?= TRANS('BT_SEARCH', '', 1); ?>",
                noneResultsText: "<?= TRANS('NO_RECORDS_FOUND', '', 1); ?> {0}",
                style: "",
                styleBase: "form-control ",
            });

            $('.new_search').css('cursor', 'pointer').on('click', function(){
                window.history.back();
            });

            /* Idioma global para os calendários */
            $.datetimepicker.setLocale('pt-BR');
            
            /* Calendários de início e fim do período */
            $('#d_ini').datetimepicker({
                format: 'd/m/Y',
                onShow: function(ct) {
                    this.setOptions({
                        maxDate: $('#d_fim').datetimepicker('getValue')
                    })
                },
                timepicker: false
            });
            $('#d_fim').datetimepicker({
                format: 'd/m/Y',
                onShow: function(ct) {
                    this.setOptions({
                        minDate: $('#d_ini').datetimepicker('getValue')
                    })
                },
                timepicker: false
            });

            $('#idSubmit').on('click', function() {
                $('.loading').show();
            });

            if (<?= $json ?> != 0) {
                showChart('canvasChart1');
            }

            if (<?= $json2 ?> != 0) {
                showChart2('canvasChart2');
            }
            if (<?= $json3 ?> != 0) {
                showChart3('canvasChart3');
            }
            if (<?= $json4 ?> != 0) {
                showChart4('canvasChart4');
            }

        });


        function showChart(canvasID) {
            var ctx = $('#' + canvasID);
            var dataFromPHP = <?= $json; ?>;

            var areasVar = []; // X Axis Label
            var abertosVar = []; // Value and Y Axis basis
            var iniciadosVar = []; 
            var concluidosVar = []; 
            var fechadosVar = []; 
            var autoFechadosVar = []; 
            var avaliadosVar = []; 

            for (var i in dataFromPHP) {

                if (dataFromPHP[i].abertos || dataFromPHP[i].iniciados || dataFromPHP[i].concluidos || dataFromPHP[i].fechados || dataFromPHP[i].auto_fechados || dataFromPHP[i].avaliados) {
                    areasVar.push(dataFromPHP[i].area);
                    abertosVar.push(dataFromPHP[i].abertos);
                    iniciadosVar.push(dataFromPHP[i].iniciados);
                    concluidosVar.push(dataFromPHP[i].concluidos);
                    fechadosVar.push(dataFromPHP[i].fechados);
                    autoFechadosVar.push(dataFromPHP[i].auto_fechados);
                    avaliadosVar.push(dataFromPHP[i].avaliados);
                }
            }

            var myChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: areasVar,
                    datasets: [{
                        label: '<?= TRANS('OPENED','',1);?>',
                        data: abertosVar,
                        borderWidth: 2
                    }, {
                        label: '<?= TRANS('STARTED','',1)?>',
                        data: iniciadosVar,
                        borderWidth: 2
                    }, {
                        label: '<?= TRANS('DONE','',1);?>',
                        data: concluidosVar,
                        borderWidth: 2
                    }, {
                        label: '<?= TRANS('COL_CLOSED','',1)?>',
                        data: fechadosVar,
                        borderWidth: 2
                    }, {
                        label: '<?= TRANS('AUTOMATIC_CLOSED','',1)?>',
                        data: autoFechadosVar,
                        borderWidth: 2
                    }, {
                        label: '<?= TRANS('RATED','',1)?>',
                        data: avaliadosVar,
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    title: {
                        display: true,
                        text: '<?= TRANS('TTL_BOARD_GENERAL_CALL_PERIOD','',1);?>',
                    },
                    scales: {
                        xAxes: [
                        {
                            display: true,
                            stacked: true,
                        },
                        ],
                        yAxes: [{
                            stacked: true,
                            display: true,
                            ticks: {
                                beginAtZero: true
                            }
                        }]
                    },
                    plugins: {
                        colorschemes: {
                            scheme: 'brewer.Paired12'
                        },
                        datalabels: {
                            display: function(context) {
                                return context.dataset.data[context.dataIndex] >= 1; // or !== 0 or ...
                            },
                            // formatter: (value, ctx) => {
                            //     let sum = ctx.dataset._meta[0].abertosVar;
                            //     let percentage = (value * 100 / sum).toFixed(2) + "%";
                            //     return percentage;
                            // }
                        },
                    },
                }
            });
        }



        function showChart2(canvasID) {
            var ctx = $('#' + canvasID);
            var dataFromPHP = <?= $json2; ?>;

            var labels = [];
            var total = [];

            labels.push(dataFromPHP.opened.label);
            total.push(dataFromPHP.opened.total);
            labels.push(dataFromPHP.started.label);
            total.push(dataFromPHP.started.total);
            labels.push(dataFromPHP.done.label);
            total.push(dataFromPHP.done.total);
            labels.push(dataFromPHP.closed.label);
            total.push(dataFromPHP.closed.total);
            labels.push(dataFromPHP.autoClosed.label);
            total.push(dataFromPHP.autoClosed.total);
            labels.push(dataFromPHP.rated.label);
            total.push(dataFromPHP.rated.total);

            var myChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        label: '<?= TRANS('total','',1); ?>',
                        data: total,
                        // backgroundColor: classe,
                        // borderColor: borderClasse,
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    title: {
                        display: true,
                        text: '<?= TRANS('TTL_BOARD_GENERAL_CALL_PERIOD','',1); ?>',
                    },
                    scales: {
                        yAxes: [{
                            display: false,
                            ticks: {
                                beginAtZero: true
                            }
                        }]
                    },
                    plugins: {
                        colorschemes: {
                            scheme: 'brewer.Paired12'
                        },
                        // datalabels: {
                        //     display: function(context) {
                        //         return context.dataset.data[context.dataIndex] >= 1; // or !== 0 or ...
                        //     },
                        //     formatter: (value, ctx) => {
                        //         let sum = ctx.dataset._meta[0].total;
                        //         let percentage = (value * 100 / sum).toFixed(2) + "%";
                        //         return percentage;
                        //     }
                        // },
                    },
                }
            });
        }

        function showChart3(canvasID) {

            const color_great = $('#color-great').css('background-color').replace('rgb', 'rgba').replace(')', ',.8)');
            const color_good = $('#color-good').css('background-color').replace('rgb', 'rgba').replace(')', ',.8)');
            const color_regular = $('#color-regular').css('background-color').replace('rgb', 'rgba').replace(')', ',.8)');
            const color_bad = $('#color-bad').css('background-color').replace('rgb', 'rgba').replace(')', ',.8)');
            const color_not_rated = $('#color-not-rated').css('background-color').replace('rgb', 'rgba').replace(')', ',.8)');
            
            const color_great_border = $('#color-great').css('background-color').replace('rgb', 'rgba').replace(')', ',.5)');
            const color_good_border = $('#color-good').css('background-color').replace('rgb', 'rgba').replace(')', ',.5)');
            const color_regular_border = $('#color-regular').css('background-color').replace('rgb', 'rgba').replace(')', ',.5)');
            const color_bad_border = $('#color-bad').css('background-color').replace('rgb', 'rgba').replace(')', ',.5)');
            const color_not_rated_border = $('#color-not-rated').css('background-color').replace('rgb', 'rgba').replace(')', ',.5)');

            var ctx = $('#' + canvasID);
            var dataFromPHP = <?= $json3; ?>;

            var areasVar = []; // X Axis Label
            var greatVar = []; // Value and Y Axis basis
            var goodVar = []; 
            var regularVar = []; 
            var badVar = []; 
            var notRatedVar = []; 

            var classe = [];
            var borderClasse = [];

            for (var i in dataFromPHP) {

                if (dataFromPHP[i].great || dataFromPHP[i].good || dataFromPHP[i].regular || dataFromPHP[i].bad || dataFromPHP[i].not_rated) {
                    areasVar.push(dataFromPHP[i].area);
                    greatVar.push(dataFromPHP[i].great);
                    goodVar.push(dataFromPHP[i].good);
                    regularVar.push(dataFromPHP[i].regular);
                    badVar.push(dataFromPHP[i].bad);
                    notRatedVar.push(dataFromPHP[i].not_rated);
                }
            }

            var myChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: areasVar,
                    datasets: [{
                        label: '<?= TRANS('ASSESSMENT_GREAT','',1);?>',
                        data: greatVar,
                        borderWidth: 2,
                        backgroundColor: color_great,
                        borderColor: color_great_border,
                    }, {
                        label: '<?= TRANS('ASSESSMENT_GOOD','',1)?>',
                        data: goodVar,
                        borderWidth: 2,
                        backgroundColor: color_good,
                        borderColor: color_good_border,
                    }, {
                        label: '<?= TRANS('ASSESSMENT_REGULAR','',1);?>',
                        data: regularVar,
                        borderWidth: 2,
                        backgroundColor: color_regular,
                        borderColor: color_regular_border,
                    }, {
                        label: '<?= TRANS('ASSESSMENT_BAD','',1)?>',
                        data: badVar,
                        borderWidth: 2,
                        backgroundColor: color_bad,
                        borderColor: color_bad_border,
                    }, {
                        label: '<?= TRANS('NOT_RATED_IN_TIME','',1)?>',
                        data: notRatedVar,
                        borderWidth: 2,
                        backgroundColor: color_not_rated,
                        borderColor: color_not_rated_border,
                    }]
                },
                
                options: {
                    responsive: true,
                    title: {
                        display: true,
                        text: '<?= TRANS('TICKETS_BY_RATE','',1);?>',
                    },
                    scales: {
                        xAxes: [
                        {
                            display: true,
                            stacked: true,
                        },
                        ],
                        yAxes: [{
                            stacked: true,
                            display: true,
                            ticks: {
                                beginAtZero: true
                            }
                        }]
                    },
                    plugins: {
                        // colorschemes: {
                        //     scheme: 'brewer.Paired12'
                        // },
                        datalabels: {
                            display: function(context) {
                                return context.dataset.data[context.dataIndex] >= 1; // or !== 0 or ...
                            },
                            // formatter: (value, ctx) => {
                            //     let sum = ctx.dataset._meta[0].abertosVar;
                            //     let percentage = (value * 100 / sum).toFixed(2) + "%";
                            //     return percentage;
                            // }
                        },
                    },
                }
            });
        }

        function showChart4(canvasID) {

            const color_great = $('#color-great').css('background-color').replace('rgb', 'rgba').replace(')', ',.8)');
            const color_good = $('#color-good').css('background-color').replace('rgb', 'rgba').replace(')', ',.8)');
            const color_regular = $('#color-regular').css('background-color').replace('rgb', 'rgba').replace(')', ',.8)');
            const color_bad = $('#color-bad').css('background-color').replace('rgb', 'rgba').replace(')', ',.8)');
            const color_not_rated = $('#color-not-rated').css('background-color').replace('rgb', 'rgba').replace(')', ',.8)');
            
            const color_great_border = $('#color-great').css('background-color').replace('rgb', 'rgba').replace(')', ',.5)');
            const color_good_border = $('#color-good').css('background-color').replace('rgb', 'rgba').replace(')', ',.5)');
            const color_regular_border = $('#color-regular').css('background-color').replace('rgb', 'rgba').replace(')', ',.5)');
            const color_bad_border = $('#color-bad').css('background-color').replace('rgb', 'rgba').replace(')', ',.5)');
            const color_not_rated_border = $('#color-not-rated').css('background-color').replace('rgb', 'rgba').replace(')', ',.5)');

            var ctx = $('#' + canvasID);
            var dataFromPHP = <?= $json4; ?>;

            var labels = [];
            var total = [];

            labels.push(dataFromPHP.great.label);
            total.push(dataFromPHP.great.total);
            labels.push(dataFromPHP.good.label);
            total.push(dataFromPHP.good.total);
            labels.push(dataFromPHP.regular.label);
            total.push(dataFromPHP.regular.total);
            labels.push(dataFromPHP.bad.label);
            total.push(dataFromPHP.bad.total);
            labels.push(dataFromPHP.not_rated.label);
            total.push(dataFromPHP.not_rated.total);

            var myChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        label: '<?= TRANS('total','',1); ?>',
                        data: total,
                        backgroundColor: [
                            color_great, 
                            color_good, 
                            color_regular,
                            color_bad,
                            color_not_rated
                        ],
                        borderColor: [
                            color_great_border, 
                            color_good_border, 
                            color_regular_border,
                            color_bad_border,
                            color_not_rated_border
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    title: {
                        display: true,
                        text: '<?= TRANS('TICKETS_BY_RATE','',1); ?>',
                    },
                    scales: {
                        yAxes: [{
                            display: false,
                            ticks: {
                                beginAtZero: true
                            }
                        }]
                    },
                    plugins: {
                        colorschemes: {
                            scheme: 'brewer.Paired12'
                        },
                        datalabels: {
                            display: function(context) {
                                return context.dataset.data[context.dataIndex] >= 1; // or !== 0 or ...
                            },
                            formatter: (value, ctx) => {
                                let sum = ctx.dataset._meta[3].total;
                                let percentage = (value * 100 / sum).toFixed(2) + "%";
                                return percentage;
                            }
                        },
                    },
                }
            });
        }

   </script>
</body>

</html>