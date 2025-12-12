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


$json = 0;
$json2 = 0;

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
        <h5 class="my-4"><i class="fas fa-user-tie text-secondary"></i>&nbsp;<?= TRANS('REPORT_CLIENTS'); ?></h5>
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

            $hora_inicio = ' 00:00:00';
            $hora_fim = ' 23:59:59';
            $terms = "";

            $client = (isset($_POST['client']) && !empty($_POST['client']) ? $_POST['client'] : null);
            $_SESSION['s_rep_filters']['client'] = $client;
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

                    if ($client) {
                        $clients[] = getClients($conn, $client);
                    } else
                        $clients = getClients($conn);

        
                    if (count($clients) == 0) {
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
                                        <td class="line"><?= mb_strtoupper(TRANS('CLIENT')); ?></td>
                                        <td class="line"><?= mb_strtoupper(TRANS('OPENED')); ?></td>
                                        <td class="line"><?= mb_strtoupper(TRANS('COL_CLOSED')); ?></td>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $data = [];
                                    $total = 0;

                                    $totalAbertos = 0;
                                    $totalFechados = 0;
                                    $tAbertos = 0;
                                    $tFechados = 0;
                                    $i = 0;
                                    foreach ($clients as $row) {
                                        
                                        $query_ab_sw = "SELECT count(*) AS abertos, cl.nickname AS cliente
                                            FROM ocorrencias AS o, clients cl, `status` AS st
                                            WHERE 
                                            o.status = st.stat_id AND st.stat_ignored <> 1 AND 
                                            o.client = cl.id AND 
                                            o.data_abertura >= '" . $d_ini . "' AND
                                            o.data_abertura <= '" . $d_fim . "' and o.client in (" . $row['id'] . ") 
                                            {$clausule}
                                            GROUP BY cliente";
                                        $exec_ab_sw = $conn->query($query_ab_sw);
                                        $row_ab_sw = $exec_ab_sw->fetch();
                        
                                        $query_fe_sw = "SELECT count(*) AS fechados, cl.nickname AS cliente, cl.id
                                            FROM ocorrencias AS o, clients cl, `status` AS st
                                            WHERE 
                                            o.status = st.stat_id AND st.stat_ignored <> 1 AND 
                                            o.client = cl.id AND o.data_fechamento >= '" . $d_ini . "' AND
                                            o.data_fechamento <= '" . $d_fim . "' and o.client in (" . $row['id'] . ")  
                                            {$clausule}
                                            GROUP BY cliente, cl.id";
                                        $exec_fe_sw = $conn->query($query_fe_sw);
                                        $row_fe_sw = $exec_fe_sw->fetch();
                        
                                        // $tAbertos += $totalAbertos += $tt_ab = $row_ab_sw['abertos'];
                                        // $tFechados += $totalFechados += $tt_fe = $row_fe_sw['fechados'];
                                        $tAbertos += $totalAbertos += $row_ab_sw['abertos'] ?? 0;
                                        $tFechados += $totalFechados += $row_fe_sw['fechados'] ?? 0;
                        
                                        $data[$i]['cliente'] = $row['nickname'];
                                        $data[$i]['abertos'] = $totalAbertos;
                                        $data[$i]['fechados'] = $totalFechados;

                                        $totalAbertos = 0;
                                        $totalFechados = 0;
                                        ?>
                                        <tr>
                                            <td class="line"><?= $data[$i]['cliente']; ?></td>
                                            <td class="line"><?= $data[$i]['abertos']; ?></td>
                                            <td class="line"><?= $data[$i]['fechados']; ?></td>
                                        </tr>
                                        <?php
                                        $i++;
                                    }


                                    $json = json_encode($data);
                                    ?>
                                </tbody>
                                <tfoot>
                                    <tr class="header table-borderless">
                                        <td><?= TRANS('TOTAL'); ?></td>
                                        <td ><?= $tAbertos; ?></td>
                                        <td ><?= $tFechados; ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="chart-container">
                            <canvas id="canvasChart1"></canvas>
                        </div>
                        
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
            var ctx = $('#' + canvasID);
            var dataFromPHP = <?= $json; ?>;

            var clientesVar = []; // X Axis Label
            var abertosVar = []; // Value and Y Axis basis
            var fechadosVar = []; // Value and Y Axis basis

            for (var i in dataFromPHP) {
                clientesVar.push(dataFromPHP[i].cliente);
                abertosVar.push(dataFromPHP[i].abertos);
                fechadosVar.push(dataFromPHP[i].fechados);
            }

            var myChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: clientesVar,
                    datasets: [{
                        label: '<?= TRANS('OPENED','',1);?>',
                        data: abertosVar,
                        borderWidth: 2
                    }, {
                        label: '<?= TRANS('COL_CLOSED','',1)?>',
                        data: fechadosVar,
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    title: {
                        display: true,
                        text: '<?= TRANS('REPORT_CLIENTS','',1);?>',
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

   </script>
</body>

</html>