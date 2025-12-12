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

$filter_areas = "";
$areas_names = "";
if (isAreasIsolated($conn) && $_SESSION['s_nivel'] != 1) {
    /* Visibilidade isolada entre áreas para usuários não admin */
    $u_areas = $_SESSION['s_uareas'];
    $filter_areas = " AND sis_id IN ({$u_areas}) ";
}

$ratingLabels = ratingLabels();
$ratingClasses = ratingClasses();


$json = 0;
$json2 = 0;

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../../includes/css/estilos.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/css/switch_radio.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/jquery/datetimepicker/jquery.datetimepicker.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap/custom.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/fontawesome/css/all.min.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap-select/dist/css/bootstrap-select.min.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/css/my_bootstrap_select.css" />

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
        <h5 class="my-4"><i class="fas fa-star-half-alt text-secondary"></i>&nbsp;<?= TRANS('REPORT_TICKETS_BY_RATE'); ?></h5>
        <div class="modal" id="modal" tabindex="-1" style="z-index:9001!important">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div id="divDetails">
                    </div>
                </div>
            </div>
        </div>

        <!-- Cores para o gráfico de avaliações dos atendimentos -->
        <input type="hidden" name="color-great" class="color-great" id="color-great"/>
        <input type="hidden" name="color-good" class="color-good" id="color-good"/>
        <input type="hidden" name="color-regular" class="color-regular" id="color-regular"/>
        <input type="hidden" name="color-bad" class="color-bad" id="color-bad"/>
        <input type="hidden" name="color-not-rated" class="color-not-rated" id="color-not-rated"/>

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


            $terms = "";
            $hora_inicio = ' 00:00:00';
            $hora_fim = ' 23:59:59';

            $client = (isset($_POST['client']) && !empty($_POST['client']) ? $_POST['client'] : "");
            $_SESSION['s_rep_filters']['client'] = $client;
            $_SESSION['s_rep_filters']['area'] = $_POST['area'];
            $clientName = (!empty($client) ? getClients($conn, $client)['nickname']: "");
            $clausule = (!empty($client) ? " AND o.client IN ({$client}) " : "");
            $noneClient = TRANS('FILTERED_CLIENT') . ": " . TRANS('NONE_FILTER') . "&nbsp;&nbsp;";
            $terms = (!empty($client) ? TRANS('FILTERED_CLIENT') . ": {$clientName}&nbsp;&nbsp;" : $noneClient );

            
            $query = "SELECT 
                        tr.rate, count(tr.rate) as quantidade,
                        sistemas.sistema as area
                    FROM 
                        usuarios ua, `status`, sistemas, ocorrencias o
                        LEFT JOIN tickets_rated tr ON tr.ticket = o.numero
                    WHERE
                        o.aberto_por = ua.user_id AND 
                        o.status = `status`.stat_id AND 
                        `status`.stat_ignored <> 1 AND
                        sistemas.sis_id = o.sistema  AND
                        o.`status` = 4 AND
                        o.`data_fechamento` IS NOT NULL AND
                        tr.rate IS NOT NULL
                    ";
            
            $queryToChart = "SELECT 
                                tr.rate, count(tr.rate) as quantidade
                            FROM
                                `status`, sistemas, ocorrencias o LEFT JOIN tickets_rated tr ON o.numero = tr.ticket
                            WHERE
                                `status`.stat_ignored <> 1 AND 
                                o.`status` = `status`.stat_id AND
                                sistemas.sis_id = o.sistema AND
                                tr.rate IS NOT NULL
                            ";

            if (!empty($filter_areas)) {
                /* Nesse caso o usuário só pode filtrar por áreas que faça parte */
                if (!empty($_POST['area']) && ($_POST['area'] != -1)) {
                    $query .= " AND o.sistema = " . $_POST['area'] . "";
                    $queryToChart .= " AND o.sistema = " . $_POST['area'] . "";

                    $getAreaName = "SELECT * from sistemas where sis_id = " . $_POST['area'] . "";
                    $exec = $conn->query($getAreaName);
                    $rowAreaName = $exec->fetch();
                    $nomeArea = $rowAreaName['sistema'];
                    $terms .= TRANS('FILTERED_AREA') . ": {$nomeArea}";
                } else {
                    $query .= " AND o.sistema IN ({$u_areas}) ";
                    $queryToChart .= " AND o.sistema IN ({$u_areas}) ";
                    $terms .= TRANS('FILTERED_AREA') . ": [" . $areas_names . "]";
                }
            } else

            if (isset($_POST['area']) && (!empty($_POST['area'])) && ($_POST['area'] != -1) && (($_POST['area'] == $_SESSION['s_area']) || ($_SESSION['s_nivel'] == 1))) {
                $query .= " AND o.sistema = " . $_POST['area'] . "";
                $queryToChart .= " AND o.sistema = " . $_POST['area'] . "";
                $getAreaName = "SELECT * from sistemas where sis_id = '" . $_POST['area'] . "'";
                $exec = $conn->query($getAreaName);
                $rowAreaName = $exec->fetch();
                $nomeArea = $rowAreaName['sistema'];

                $terms .= TRANS('FILTERED_AREA') . ": {$nomeArea}";
            } else
            if ($_SESSION['s_nivel'] != 1) {
                $_SESSION['flash'] = message('info', '', TRANS('MSG_CONSULT_FOR_YOU_AREA'), '');
                redirect($_SERVER['PHP_SELF']);
            } else {
                $nomeArea = TRANS('NONE_FILTER');
                $terms .= TRANS('FILTERED_AREA') . ": {$nomeArea}";
            }


            if ((!isset($_POST['d_ini'])) || (!isset($_POST['d_fim']))) {
                $_SESSION['flash'] = message('info', '', TRANS('MSG_ALERT_PERIOD'), '');
                redirect($_SERVER['PHP_SELF']);
            } else {

                $d_ini = $_POST['d_ini'] . $hora_inicio;
                $d_ini = dateDB($d_ini);

                $d_fim = $_POST['d_fim'] . $hora_fim;
                $d_fim = dateDB($d_fim);

                if (isset($_POST['mesAtual'])) {
                    $d_ini = date("Y-m-01 00:00:00");
                    $d_fim = date("Y-m-d H:i:s");
                }

                if ($d_ini <= $d_fim) {

                    $_SESSION['s_rep_filters']['d_ini'] = $_POST['d_ini'];
                    $_SESSION['s_rep_filters']['d_fim'] = $_POST['d_fim'];
                    /* Query apenas para retornar os dados para o gráfico 1 - o agrupamento é diferente para a listagem */
                    $queryChart = $queryToChart . " AND o.data_fechamento >= '{$d_ini}' AND o.data_fechamento <= '{$d_fim}' {$clausule}
                    GROUP BY rate ORDER BY rate, quantidade desc  ";


                    $resultadoChart = $conn->query($queryChart);


                    $query .= " AND o.data_fechamento >= '{$d_ini}' AND o.data_fechamento <= '{$d_fim}'  
                                {$clausule}
                                GROUP BY rate, sistemas.sistema ORDER BY rate, quantidade desc  ";
                    $resultado = $conn->query($query);
                    $linhas = $resultado->rowCount();

                    if ($linhas == 0) {
                        $_SESSION['flash'] = message('info', '', TRANS('MSG_NO_DATA_IN_PERIOD'), '');
                        redirect($_SERVER['PHP_SELF']);
                    } else {

                        ?>
                        <p><?= TRANS('TTL_PERIOD_FROM') . "&nbsp;" . dateScreen($d_ini, 1) . "&nbsp;" . TRANS('DATE_TO') . "&nbsp;" . dateScreen($d_fim, 1); ?></p>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <!-- table-hover -->
                                <caption><?= $terms; ?><span class="px-2" style="float:right" id="new_search"><?= TRANS('NEW_SEARCH'); ?></span></caption>
                                <thead>
                                    <tr class="header table-borderless">
                                        <td class="line"><?= mb_strtoupper(TRANS('COL_STATUS')); ?></td>
                                        <td class="line"><?= mb_strtoupper(TRANS('SERVICE_AREA')); ?></td>
                                        <td class="line"><?= mb_strtoupper(TRANS('COL_AMOUNT')); ?></td>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $data = [];
                                    $data2 = [];
                                    
                                    $total = 0;
                                    foreach ($resultado->fetchall() as $row) {
                                        // $data[] = $row;
                                        ?>
                                        <tr>
                                            <td class="line"><?= $ratingLabels[$row['rate']]; ?></td>
                                            <td class="line"><?= $row['area']; ?></td>
                                            <td class="line"><?= $row['quantidade']; ?></td>
                                        </tr>
                                        <?php
                                        $total += $row['quantidade'];
                                    }

                                    foreach ($resultadoChart->fetchall() as $rowDataChart) {
                                        // $data[] = $rowDataChart;
                                        $data[] = [
                                            'rate' => $ratingLabels[$rowDataChart['rate']],
                                            'quantidade' => $rowDataChart['quantidade'],
                                            'classe' => $ratingClasses[$rowDataChart['rate']]
                                        ];
                                    }
                                    // foreach ($resultadoChart2->fetchall() as $rowDataChart2) {
                                    //     $data2[] = $rowDataChart2;
                                    // }
                                    
                                    $json = json_encode($data);
                                    // $json2 = json_encode($data2);
                                    ?>
                                </tbody>
                                <tfoot>
                                    <tr class="header table-borderless">
                                        <td colspan="2"><?= TRANS('TOTAL'); ?></td>
                                        <td><?= $total; ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="chart-container">
                            <canvas id="canvasChart1"></canvas>
                        </div>
                        <!-- <div class="chart-container">
                            <canvas id="canvasChart2"></canvas>
                        </div> -->
                        <?php
                        // var_dump([
                        //     'Query' => $query,
                        //     'Data' => $data,
                        //     'Json normal' => $json,
                        // ]);
                    }
                } else {
                    $_SESSION['flash'] = message('info', '', TRANS('MSG_COMPARE_DATE'), '');
                    redirect($_SERVER['PHP_SELF']);
                }
            }
        }
        ?>
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

            $('#new_search').css('cursor', 'pointer').on('click', function(){
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

        });


        function showChart(canvasID) {

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

            let bgColors = {};
            bgColors['color-great'] = color_great;
            bgColors['color-good'] = color_good;
            bgColors['color-regular'] = color_regular;
            bgColors['color-bad'] = color_bad;
            bgColors['color-not-rated'] = color_not_rated;

            let borderColors = {};
            borderColors['color-great'] = color_great_border;
            borderColors['color-good'] = color_good_border;
            borderColors['color-regular'] = color_regular_border;
            borderColors['color-bad'] = color_bad_border;
            borderColors['color-not-rated'] = color_not_rated_border;
            
            
            var ctx = $('#' + canvasID);
            var dataFromPHP = <?= $json; ?>

            var labels = []; // X Axis Label
            var total = []; // Value and Y Axis basis
            var classe = [];
            var borderClasse = [];

            for (var i in dataFromPHP) {
                // console.log(dataFromPHP[i]);
                // labels.push(dataFromPHP[i].operador);
                labels.push(dataFromPHP[i].rate);
                total.push(dataFromPHP[i].quantidade);

                if (dataFromPHP[i].classe !== undefined) {
                    classe.push(bgColors[dataFromPHP[i].classe]);
                    borderClasse.push(borderColors[dataFromPHP[i].classe]);
                }
            }

            var myChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        label: '<?= TRANS('total','',1); ?>',
                        data: total,
                        backgroundColor: classe,
                        borderColor: borderClasse,
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
                        /* colorschemes: {
                            scheme: 'brewer.Paired12'
                        }, */
                        datalabels: {
                            display: function(context) {
                                return context.dataset.data[context.dataIndex] >= 1; // or !== 0 or ...
                            },
                            formatter: (value, ctx) => {
                                let sum = ctx.dataset._meta[0].total;
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