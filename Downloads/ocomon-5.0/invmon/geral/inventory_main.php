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

$data = [];
$data2 = [];
$json = 0;
$json2 = 0;

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

        <?php
        if (isset($_SESSION['flash']) && !empty($_SESSION['flash'])) {
            echo $_SESSION['flash'];
            $_SESSION['flash'] = '';
        }


        /**
         * Bloco de controle da visualização dos itens de inventário com base na configuração da área de atendimento
         */
        $termsTotalAllowedUnits = "";
        $termsAllowedUnits = "";
        $filteredUnitsNames = "";

        $termsTotalStockAllowedUnits = "";
        $termsStockAllowedUnits = "";
        if (($_SESSION['s_nivel'] != 1) && !empty($_SESSION['s_allowed_units'])) {
            
            $termsTotalAllowedUnits = " AND comp_inst IN (" . $_SESSION['s_allowed_units'] . ") ";
            $termsAllowedUnits = " AND C.comp_inst IN (" . $_SESSION['s_allowed_units'] . ") ";

            $termsTotalStockAllowedUnits = " AND estoq_tag_inst IN (" . $_SESSION['s_allowed_units'] . ") ";
            $termsStockAllowedUnits = " AND e.estoq_tag_inst IN (" . $_SESSION['s_allowed_units'] . ") ";

            $arrayFilteredUnitsNames = getAreaAllowedUnitsNames($conn, $_SESSION['s_area']);
            foreach ($arrayFilteredUnitsNames as $filteredUnit) {
                if (strlen((string)$filteredUnitsNames) > 0) {
                    $filteredUnitsNames .= ", ";
                } else {
                    $filteredUnitsNames .= TRANS('FILTERED_BY_FOLLOW_UNITS') . ':&nbsp;';
                }
                $filteredUnitsNames .= $filteredUnit['inst_nome'] . '(' . $filteredUnit['nickname']. ')';
            }
        }
        /* Final do Bloco de controle de visualização */


        /* Equipamentos */
        $sqlTotalCount = $QRY["total_equip"] . " WHERE comp_inst NOT IN (" . INST_TERCEIRA . ") {$termsTotalAllowedUnits}";
        $resTotalCount = $conn->query($sqlTotalCount);

        $total = $resTotalCount->fetch()['total'];
        if ($total == 0) {
            echo message('info', 'Ooops!', TRANS('NO_RECORDS_FOUND'), '', '', 1);
            // return;
        } else {
            $terms = "";
            $query = "SELECT count(*) as Quantidade, count(*)*100/" . $total . " as Percentual, 
                        T.tipo_nome as Equipamento, T.tipo_cod as tipo 
                    FROM equipamentos as C, tipo_equip as T 
                    WHERE C.comp_tipo_equip = T.tipo_cod and C.comp_inst not in (" . INST_TERCEIRA . ") 
                    {$termsAllowedUnits}
                    GROUP by C.comp_tipo_equip, T.tipo_nome, T.tipo_cod 
                    ORDER BY Quantidade desc, Equipamento";
            $resultado = $conn->query($query);
            $linhas = $resultado->rowCount();

            if ($linhas == 0) {
                echo message('info', '', TRANS('MSG_NO_DATA_IN_PERIOD'), '');
                return;
            }
            ?>
            <h5 class="my-4"><i class="fas fa-laptop text-secondary"></i>&nbsp;<?= TRANS('TTL_ESTAT_CAD_EQUIP'); ?></h5>
            <?php
                if (!empty($_SESSION['s_allowed_units'])) {
                    echo "<span class='small'>*". TRANS('RESULT_LIMITED_BY_PERMISSIONS')."</span>";
                }
            ?>
            
           
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <caption><?= TRANS('TTL_GENERAL_BOARD'); ?></caption>
                    <thead>
                        <tr class="header table-borderless">
                            <td class="line"><?= TRANS('COL_EQUIP'); ?></td>
                            <td class="line"><?= TRANS('COL_QTD'); ?></td>
                            <td class="line"><?= TRANS('PERCENTAGE'); ?></td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($resultado->fetchall() as $row) {
                            $data[] = $row;
                            ?>
                            <tr class="table-borderless">
                                <td class="line"><span id="type_<?= $row['tipo']; ?>" data-type="<?= $row['tipo']; ?>" class=" type-filter"><?= $row['Equipamento']; ?></span></td>
                                <td class="line"><?= $row['Quantidade']; ?></td>
                                <td class="line"><?= round($row['Percentual'], 2); ?>%</td>
                            </tr>
                            <?php
                        }
                        $json = json_encode($data);
                        ?>
                    </tbody>
                    <tfoot>
                        <tr class="header table-borderless">
                            <td><?= TRANS('total'); ?></td>
                            <td colspan="2"><?= $total; ?></td>
                        </tr>
                    </tfoot>
                    </tbody>
                </table>
            </div>
            <div class="chart-container">
                <canvas id="canvasChart1"></canvas>
            </div>
            <?php
        }

        /* Componentes avulsos - Descontinuados */
        $sqlTotalCount = "SELECT COUNT(*) AS total 
        FROM estoque
        WHERE 
            1 = 1
            {$termsTotalStockAllowedUnits}
        ";
        $resTotalCount = $conn->query($sqlTotalCount);

        $total = $resTotalCount->fetch()['total'];
        // if ($total == 0) {
        //     echo message('info', 'Ooops!', TRANS('NO_RECORDS_FOUND'), '', '', 1);
        //     return;
        // }

        if ($total) {
            $terms = "";
            $query = "SELECT count(*) as quantidade, count(*)*100/{$total} as percentual, 
                                i.item_nome as tipo, i.item_cod as tipo_cod 
                            FROM 
                                estoque as e, itens as i 
                            WHERE 
                                e.estoq_tipo = i.item_cod 
                                {$termsStockAllowedUnits} 
                            GROUP by tipo, tipo_cod 
                            ORDER BY quantidade desc, tipo";

            $resultado = $conn->query($query);
            $linhas = $resultado->rowCount();

            ?>
            <h5 class="my-4"><i class="fas fa-hdd text-secondary"></i>&nbsp;<?= TRANS('DETACHED_COMPONENTS'); ?>&nbsp;<span class="badge badge-warning text-small"><?= TRANS('DEPRECATED'); ?></span></h5>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <caption><?= TRANS('TTL_GENERAL_BOARD'); ?></caption>
                    <thead>
                        <tr class="header table-borderless">
                            <td class="line"><?= TRANS('COL_TYPE'); ?></td>
                            <td class="line"><?= TRANS('COL_QTD'); ?></td>
                            <td class="line"><?= TRANS('PERCENTAGE'); ?></td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // $total = 0;
                        foreach ($resultado->fetchall() as $row) {
                            $data2[] = $row;
                            ?>
                            <tr class=" table-borderless">
                                <td class="line"><?= $row['tipo']; ?></td>
                                <td class="line"><?= $row['quantidade']; ?></td>
                                <td class="line"><?= round($row['percentual'], 2); ?>%</td>
                            </tr>
                            <?php
                        }
                        $json2 = json_encode($data2);
                        ?>
                    </tbody>
                    <tfoot>
                        <tr class="header table-borderless">
                            <td><?= TRANS('total'); ?></td>
                            <td colspan="2"><?= $total; ?></td>
                        </tr>
                    </tfoot>
                    </tbody>
                </table>
            </div>
            <?php

            ?>
                <div class="chart-container">
                    <canvas id="canvasChart2"></canvas>
                </div>
            <?php
            
        }
        ?>

        
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

            
            $(".type-filter").addClass("pointer").on("click", function(){
                getAssetsData($(this).attr("id"));
            });


            if (<?= $json ?> != 0) {
                showChart('canvasChart1');
                showChart2('canvasChart2');
            }

        });


        function showChart(canvasID) {
            var ctx = $('#' + canvasID);
            var dataFromPHP = <?= $json; ?>

            var labels = []; // X Axis Label
            var total = []; // Value and Y Axis basis

            for (var i in dataFromPHP) {
                labels.push(dataFromPHP[i].Equipamento);
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
                        text: '<?= TRANS('TTL_ESTAT_CAD_EQUIP', '', 1) ?>',
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

        function showChart2(canvasID) {
            var ctx = $('#' + canvasID);
            var dataFromPHP = <?= $json2; ?>

            var labels = []; // X Axis Label
            var total = []; // Value and Y Axis basis

            for (var i in dataFromPHP) {
                labels.push(dataFromPHP[i].tipo);
                total.push(dataFromPHP[i].quantidade);
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
                        text: '<?= TRANS('DETACHED_COMPONENTS', '', 1) ?>',
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
                                let sum = ctx.dataset._meta[1].total;
                                let percentage = (value * 100 / sum).toFixed(2) + "%";
                                return percentage;
                            }
                        },
                    },
                }
            });
        }


        function getAssetsData(id) {

            let type = $("#" + id).attr("data-type");

            $.ajax({
                url: 'get_assets_filtered_data.php',
                method: 'POST',
                data: {
                    'type': type
                },
                dataType: 'json',

            }).done(function(data) {

                // cardsAjaxList(data.assetTypeFilter, e);
                cardsAjaxList(data.assetTypeFilter);
                
            }).fail(function() {
                // $('#divError').html('<p class="text-danger text-center"><?= TRANS('FETCH_ERROR'); ?></p>');
            });
            return false;
        }

        // function cardsAjaxList(arrayKeyData, e) {
        function cardsAjaxList(arrayKeyData) {

            var data = {};
            $.each(arrayKeyData, function(key, value) {
                data[key] = value;
            });

            // e.preventDefault();
            var loading = $(".loading");
            $(document).ajaxStart(function() {
                loading.show();
            });

            $(document).ajaxStop(function() {
                loading.hide();
            });

            popup_alerta_wide('./get_assets_filtered_list.php?' + $.param(data));
        }
    </script>
</body>

</html>