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

$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 2, 2);
$_SESSION['s_page_invmon'] = $_SERVER['PHP_SELF'];

$json = 0;
$json2 = 0;
$json3 = 0;

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../../includes/css/estilos.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap/custom.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/fontawesome/css/all.min.css" />

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


    <div class="container-fluid">
        <h5 class="my-4"><i class="fas fa-laptop text-secondary"></i>&nbsp;<?= TRANS('TTL_COMP_X_SECTOR'); ?></h5>
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

        $sqlTotalCount = $QRY["total_equip"] . " WHERE comp_inst NOT IN (" . INST_TERCEIRA . ") AND comp_tipo_equip IN (1,2)";
        $resTotalCount = $conn->query($sqlTotalCount);
        
        $total = $resTotalCount->fetch()['total'];
        if ($total == 0) {
            echo message('info', 'Ooops!', TRANS('NO_RECORDS_FOUND'), '', '', 1);
            return;
        }

        // $query = "select count(l.local)as qtd, count(*)/" . $total . "*100 as porcento,
        // l.local as local, l.loc_id as tipo_local, t.tipo_nome as equipamento, t.tipo_cod as tipo
        // from equipamentos as c,
        // tipo_equip as t, localizacao as l where ((c.comp_tipo_equip = t.tipo_cod)
        // and (c.comp_local = l.loc_id) and (t.tipo_cod in (1,2)) " . $clausula2 . ") group by local,tipo order by qtd desc ,
        // local asc";

        $terms = "";
        $query = "SELECT count(*) as Quantidade, count(*)*100/" . $total . " as Percentual, 
                    T.tipo_nome AS Equipamento, T.tipo_cod AS tipo, F.fab_nome AS fabricante, 
                    M.marc_nome AS modelo, M.marc_tipo AS modelo_cod, L.local AS department, 
                    L.loc_id AS department_cod  
                FROM 
                    equipamentos AS C, tipo_equip AS T, 
                    marcas_comp AS M, fabricantes AS F, 
                    localizacao as L  
                WHERE 
                    C.comp_tipo_equip = T.tipo_cod AND C.comp_inst not in (" . INST_TERCEIRA . ") AND 
                    F.fab_cod = C.comp_fab AND C.comp_marca = M.marc_cod AND
                    C.comp_local = L.loc_id AND T.tipo_cod IN (1,2) 
                GROUP BY department, Equipamento
                ORDER BY Quantidade DESC, department ASC";

        

        $resultado = $conn->query($query);
        $linhas = $resultado->rowCount();

        if ($linhas == 0) {
            echo message('info', '', TRANS('MSG_NO_DATA_IN_PERIOD'), '');
            return;
        } 

        $data = [];
        // $data2 = [];
        // $data3 = [];

        ?>
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <caption><?= TRANS('TTL_GENERAL_BOARD'); ?></caption>
                <thead>
                    <tr class="header table-borderless">
                        <td class="line"><?= mb_strtoupper(TRANS('ISSUE_TYPE')); ?></td>
                        <td class="line"><?= mb_strtoupper(TRANS('COL_AMOUNT')); ?></td>
                        <td class="line"><?= mb_strtoupper(TRANS('SERVICE_AREA')); ?></td>
                    </tr>
                </thead>
                <tbody>
        <?php


        // $total = 0;
        $i = 1;
        foreach ($resultado->fetchall() as $row) {
            
            $data[] = $row;
            ?>
            <tr class=" table-borderless">
                <td class="line"><?= $row['department']; ?></td>
                <td class="line"><a href="equipments_list.php?comp_tipo_equip=<?= $row['tipo']; ?>"><?= $row['Equipamento'];?></a></td>
                <td class="line"><?= $row['Quantidade'];?></td>
                <td class="line"><?= round($row['Percentual'], 2);?>%</td>
            </tr>
            <?php
            $i++;
        }
        

        $json = json_encode($data);
        // $json2 = json_encode($data2);
        ?>
                
                    </tbody>
                    <tfoot>
                        <tr class="header table-borderless">
                            <td ><?= TRANS('total'); ?></td>
                            <td colspan="4"><?= $total; ?></td>
                        </tr>
                    </tfoot>
                </tbody>
            </table>
        </div>



        <div class="chart-container">
            <canvas id="canvasChart1"></canvas>
        </div>
        <!-- <div class="chart-container">
            <canvas id="canvasChart2"></canvas>
        </div> -->
        <!-- <div class="chart-container">
            <canvas id="canvasChart3"></canvas>
        </div> -->
        <?php
        
        ?>
    </div>
    <script src="../../includes/javascript/funcoes-3.0.js"></script>
    <script src="../../includes/components/jquery/jquery.js"></script>
    <!-- <script type="text/javascript" src="../../includes/components/jquery/jquery-ui-1.12.1/jquery-ui.js"></script> -->
    <script src="../../includes/components/bootstrap/js/bootstrap.min.js"></script>
    <script type="text/javascript" src="../../includes/components/chartjs/dist/Chart.min.js"></script>
    <script type="text/javascript" src="../../includes/components/chartjs/chartjs-plugin-colorschemes/dist/chartjs-plugin-colorschemes.js"></script>
    <script type="text/javascript" src="../../includes/components/chartjs/chartjs-plugin-datalabels/chartjs-plugin-datalabels.min.js"></script>
    <script type='text/javascript'>
        $(function() {
            

            if (<?= $json ?> != 0) {
                showChart('canvasChart1');
                // showChart2('canvasChart2');
            }

        });


        function showChart(canvasID) {
            var ctx = $('#' + canvasID);
            var dataFromPHP = <?= $json; ?>

            var labels = []; // X Axis Label
            var total = []; // Value and Y Axis basis

            for (var i in dataFromPHP) {
                // labels.push(dataFromPHP[i].Equipamento);
                labels.push(dataFromPHP[i].department);
                total.push(dataFromPHP[i].Quantidade);
            }

            var myChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        // label: 'SLA de Resposta',
                        data: total,
                        
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    title: {
                        display: true,
                        text: '<?= TRANS('TTL_COMP_X_SECTOR','',1)?>',
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
                            color: "#FFFFFF", 
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

        // function showChart2(canvasID) {
        //     var ctx2 = $('#' + canvasID);
        //     var dataFromPHP2 = <?= $json2; ?>

        //     var labels = []; // X Axis Label
        //     var total = []; // Value and Y Axis basis

        //     for (var i in dataFromPHP2) {
        //         labels.push(dataFromPHP2[i].label);
        //         total.push(dataFromPHP2[i].total);
        //     }

        //     var myChart2 = new Chart(ctx2, {
        //         type: 'doughnut',
        //         data: {
        //             labels: labels,
        //             datasets: [{
        //                 // label: 'SLA de Resposta',
        //                 data: total,
        //                 backgroundColor: [
        //                     'rgba(0, 128, 0, 0.8)',
        //                     'rgba(255, 255, 0, 0.8)',
        //                     'rgba(255, 0, 0, 0.8)',
        //                     'rgba(128, 128, 128, 0.8)',
        //                 ],
        //                 borderColor: [
        //                     'rgba(0, 128, 0, 1)',
        //                     'rgba(255, 255, 0, 1)',
        //                     'rgba(255, 0, 0, 1)',
        //                     'rgba(128, 128, 128, 0.1)',
        //                 ],
        //                 borderWidth: 2,
                        
        //             }]
        //         },
        //         options: {
        //             responsive: true,
        //             title: {
        //                 display: true,
        //                 text: '<?= TRANS('SOLUTION_SLA','',1)?>',
        //             },
        //             scales: {
        //                 yAxes: [{
        //                     display: false,
        //                     ticks: {
        //                         beginAtZero: true
        //                     }
        //                 }]
        //             },
        //             plugins: {
        //                 colorschemes: {
        //                     scheme: 'brewer.Paired12'
        //                 },
        //                 datalabels: {
        //                     display: function(context) {
        //                         return context.dataset.data[context.dataIndex] >= 1; // or !== 0 or ...
        //                     },
        //                     color: "#FFFFFF", 
        //                     formatter: (value, ctx2) => {
        //                         let sum = ctx2.dataset._meta[1].total;
        //                         let percentage = (value * 100 / sum).toFixed(2) + "%";
        //                         return percentage;
        //                     }
        //                 },
        //             },
        //         }
        //     });
        // }


    </script>
</body>

</html>