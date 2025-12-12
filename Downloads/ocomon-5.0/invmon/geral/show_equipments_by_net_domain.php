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

exit;

require_once __DIR__ . "/" . "../../includes/include_geral_new.inc.php";
require_once __DIR__ . "/" . "../../includes/classes/ConnectPDO.php";

use includes\classes\ConnectPDO;

$conn = ConnectPDO::getInstance();

$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 2, 2);

$_SESSION['s_page_invmon'] = $_SERVER['PHP_SELF'];

$json = 0;
$json2 = 0;
$json3 = 0;
$json4 = 0;
$json5 = 0;

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../../includes/css/estilos.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap/custom.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/fontawesome/css/all.min.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap-select/dist/css/bootstrap-select.min.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/css/my_bootstrap_select.css" />


    <style>
        .chart-container {
            position: relative;
            max-width: 100%;
            margin-left: 10px;
            margin-right: 10px;
            margin-bottom: 30px;
        }

        .search_terms {
            margin-left: 10px;
        }
        .search_terms::after {
            content: '\A\A'; white-space: pre;
        }

        caption {
            line-height:0.7em;
        }
    </style>

    <title>OcoMon&nbsp;<?= VERSAO; ?></title>
</head>

<body>
    
    <div class="container">
        <div id="idLoad" class="loading" style="display:none"></div>
    </div>


    <div class="container">
        <h5 class="my-4"><i class="fas fa-network-wired text-secondary"></i>&nbsp;<?= TRANS('TTL_EQUIP_X_DOMAIN'); ?></h5>
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
                    <label for="equipment_type" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_TYPE'); ?></label>
                    <div class="form-group col-md-10">
                        <select class="form-control sel2" id="equipment_type" name="equipment_type[]" multiple="multiple">
                            <?php
                            $sql = "SELECT * FROM tipo_equip ORDER BY tipo_nome";
                            $resultado = $conn->query($sql);
                            foreach ($resultado->fetchAll() as $row) {
                                ?>
                                <option value="<?= $row['tipo_cod']; ?>"><?= $row['tipo_nome']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
                    </div>

                    <label for="unit" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_UNIT'); ?></label>
                    <div class="form-group col-md-10">
                        <select class="form-control sel2" id="unit" name="unit[]" multiple="multiple">
                            <?php
                            $units = getUnits($conn, null, null, null, $_SESSION['s_allowed_units']);
                            foreach ($units as $row) {
                                ?>
                                <option value="<?= $row['inst_cod']; ?>"><?= $row['inst_nome']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
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


            $search_terms = "";
            $query_filter = "";
            
            /* T.tipo_nome AS equipamento, T.tipo_cod AS tipo, L.local AS department, 
                    L.loc_id AS department_cod, L.loc_dominio AS domain_cod, 
                    D.dom_desc AS domain_name, 
                    I.inst_cod as unit_cod, I.inst_nome as unit_name  */
            $query_base = "SELECT count(*) as quantidade,  
                    T.tipo_nome AS equipamento, L.loc_dominio AS domain_cod, 
                    D.dom_desc AS domain_name 
                FROM 
                    equipamentos AS C, tipo_equip AS T, 
                    localizacao AS L, instituicao AS I, 
                    dominios as D 
                WHERE 
                    C.comp_tipo_equip = T.tipo_cod AND 
                    C.comp_inst = I.inst_cod AND 
                    C.comp_local = L.loc_id AND 
                    L.loc_dominio = D.dom_cod {$termsAllowedUnits}
            ";

            $queryRules = " FROM 
                    equipamentos AS C, tipo_equip AS T, 
                    localizacao AS L, instituicao AS I, 
                    dominios as D 
                WHERE 
                    C.comp_tipo_equip = T.tipo_cod AND 
                    C.comp_inst = I.inst_cod AND 
                    C.comp_local = L.loc_id AND 
                    L.loc_dominio = D.dom_cod {$termsAllowedUnits}
            ";
                
                
                
            
            
            /* Filtro por tipo de equipamento */
            if (isset($_POST['equipment_type']) and (!empty($_POST['equipment_type']))) {
                
                $equipment_type_names = "";
                $fieldIn = "";
                foreach ($_POST['equipment_type'] as $field) {
                    if (strlen((string)$fieldIn)) $fieldIn .= ",";
                    $fieldIn .= $field;
                }
                
                $query_filter .= " AND T.tipo_cod IN ({$fieldIn}) ";
                
                $getTypeName = "SELECT * from tipo_equip where tipo_cod IN ({$fieldIn}) ";
                $exec = $conn->query($getTypeName);
                foreach ($exec->fetchall() as $rowName) {
                    if (strlen((string)$equipment_type_names) > 0) $equipment_type_names .= ", ";
                    $equipment_type_names .= $rowName['tipo_nome'];
                }

                $search_terms .= "<span class='search_terms'>" . TRANS('FILTERED_EQUIPMENT_TYPE') . ": {$equipment_type_names}</span>";
            } else {
                $equipment_type_names = TRANS('NONE_FILTER');
                $search_terms .= "<span class='search_terms'>" . TRANS('FILTERED_EQUIPMENT_TYPE') . ": {$equipment_type_names}</span>";
            }


            /* Filtro por unidade */
            if (isset($_POST['unit']) and (!empty($_POST['unit']))) {
                
                $unit_names = "";
                $fieldIn = "";
                foreach ($_POST['unit'] as $field) {
                    if (strlen((string)$fieldIn)) $fieldIn .= ",";
                    $fieldIn .= $field;
                }
                
                $query_filter .= " AND C.comp_inst IN ({$fieldIn}) ";
                
                $getTypeName = "SELECT * from instituicao where inst_cod IN ({$fieldIn}) ";
                $exec = $conn->query($getTypeName);
                foreach ($exec->fetchall() as $rowName) {
                    if (strlen((string)$unit_names)) $unit_names .= ", ";
                    $unit_names .= $rowName['inst_nome'];
                }

                $search_terms .= "<span class='search_terms'>" . TRANS('FILTERED_UNIT') . ": {$unit_names}</span>";
            } else {
                $unit_names = TRANS('NONE_FILTER');
                $search_terms .= "<span class='search_terms'>" . TRANS('FILTERED_UNIT') . ": {$unit_names}</span>";
            }

            if (!empty($_SESSION['s_allowed_units'])) {
                $search_terms .= "<span class='search_terms'>*". TRANS('RESULT_LIMITED_BY_PERMISSIONS')."</span>";
            }


            /* Query apenas para retornar os dados para o gráfico 1 - o agrupamento é diferente para a listagem */
            $queryChart1 = "SELECT count(*) as quantidade,  
                    D.dom_desc AS domain_name ";
            $queryChart1 .= $queryRules . $query_filter . 
                        " GROUP BY domain_name ORDER BY quantidade DESC, domain_name";
            $resultadoChart1 = $conn->query($queryChart1);

            // /* Query apenas para retornar os dados para o gráfico 2 - o agrupamento é diferente para a listagem */
            // $queryChart2 = $query_base . $query_filter . 
            //             " GROUP BY hdd_capacidade ORDER BY quantidade DESC, hdd_capacidade";
            // $resultadoChart2 = $conn->query($queryChart2);

            // /* Query apenas para retornar os dados para o gráfico 3 - o agrupamento é diferente para a listagem */
            // $queryChart3 = $query_base . $query_filter . 
            //             " GROUP BY hdd_fabricante ORDER BY quantidade DESC, hdd_fabricante";
            // $resultadoChart3 = $conn->query($queryChart3);

            // /* Query apenas para retornar os dados para o gráfico 4 - o agrupamento é diferente para a listagem */
            // $queryChart4 = $query_base . $query_filter . 
            //             " GROUP BY hdd_modelo ORDER BY quantidade DESC, hdd_modelo";
            // $resultadoChart4 = $conn->query($queryChart4);

            // /* Query apenas para retornar os dados para o gráfico 5 - o agrupamento é diferente para a listagem */
            // $queryChart5 = $query_base . $query_filter . 
            //             " GROUP BY unit_name ORDER BY unit_name, quantidade DESC, hdd_full";
            // $resultadoChart5 = $conn->query($queryChart5);


            $query_base .= $query_filter;
            $query_base .= " GROUP BY domain_name, domain_cod, equipamento 
                        ORDER BY equipamento, quantidade DESC, domain_name";
            $resultado = $conn->query($query_base);
            $linhas = $resultado->rowCount();

            if ($linhas == 0) {
                $_SESSION['flash'] = message('info', '', TRANS('NO_RECORDS_FOUND'), '');
                redirect($_SERVER['PHP_SELF']);
            } else {

                ?>
                
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <!-- table-hover -->
                        <caption><?= $search_terms; ?></caption>
                        <thead>
                            <tr class="header table-borderless">
                                <td class="line"><?= mb_strtoupper(TRANS('COL_TYPE')); ?></td>
                                <td class="line"><?= mb_strtoupper(TRANS('NET_DOMAIN')); ?></td>
                                <td class="line"><?= mb_strtoupper(TRANS('COL_AMOUNT')); ?></td>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $data = [];
                            $data2 = [];
                            $data3 = [];
                            $data4 = [];
                            $data5 = [];
                            
                            $total = 0;
                            foreach ($resultado->fetchall() as $row) {
                                ?>
                                <tr class=" table-borderless">
                                    
                                    <td class="line"><?= $row['equipamento'];?></td>
                                    <td class="line"><?= $row['domain_name'];?></td>
                                    <td class="line"><?= $row['quantidade'];?></td>
                                </tr>
                                <?php
                                $total += $row['quantidade'];
                            }

                            foreach ($resultadoChart1->fetchall() as $rowDataChart1) {
                                $data[] = $rowDataChart1;
                            }
                            /* foreach ($resultadoChart2->fetchall() as $rowDataChart2) {
                                $data2[] = $rowDataChart2;
                            }
                            foreach ($resultadoChart3->fetchall() as $rowDataChart3) {
                                $data3[] = $rowDataChart3;
                            }
                            foreach ($resultadoChart4->fetchall() as $rowDataChart4) {
                                $data4[] = $rowDataChart4;
                            }
                            foreach ($resultadoChart5->fetchall() as $rowDataChart5) {
                                $data5[] = $rowDataChart5;
                            } */

                            
                            $json = json_encode($data);
                            /* $json2 = json_encode($data2);
                            $json3 = json_encode($data3);
                            $json4 = json_encode($data4);
                            $json5 = json_encode($data5); */
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
                </div>
                <div class="chart-container">
                    <canvas id="canvasChart3"></canvas>
                </div>
                <div class="chart-container">
                    <canvas id="canvasChart4"></canvas>
                </div>
                <div class="chart-container">
                    <canvas id="canvasChart5"></canvas>
                </div> -->

                
                <?php
                
            }
                
            
        }
        ?>
    </div>
    <script src="../../includes/javascript/funcoes-3.0.js"></script>
    <script src="../../includes/components/jquery/jquery.js"></script>
    <script src="../../includes/components/bootstrap/js/bootstrap.bundle.js"></script>
    <script src="../../includes/components/bootstrap-select/dist/js/bootstrap-select.min.js"></script>
    <script type="text/javascript" src="../../includes/components/chartjs/dist/Chart.min.js"></script>
    <script type="text/javascript" src="../../includes/components/chartjs/chartjs-plugin-colorschemes/dist/chartjs-plugin-colorschemes.js"></script>
    <script type="text/javascript" src="../../includes/components/chartjs/chartjs-plugin-datalabels/chartjs-plugin-datalabels.min.js"></script>
    <script type='text/javascript'>
        $(function() {
            
            $('.sel2').selectpicker({
				/* placeholder */
				title: "<?= TRANS('OCO_SEL_ANY', '', 1); ?>",
				liveSearch: true,
				liveSearchNormalize: true,
				liveSearchPlaceholder: "<?= TRANS('BT_SEARCH', '', 1); ?>",
				noneResultsText: "<?= TRANS('NO_RECORDS_FOUND', '', 1); ?> {0}",
                maxOptions: 5,
                maxOptionsText: "<?= TRANS('TEXT_MAX_OPTIONS', '', 1); ?>",
				style: "",
				styleBase: "form-control input-select-multi",
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
            var dataFromPHP = <?= $json; ?>

            var labels = []; // X Axis Label
            var total = []; // Value and Y Axis basis

            for (var i in dataFromPHP) {
                // console.log(dataFromPHP[i]);
                labels.push(dataFromPHP[i].domain_name);
                total.push(dataFromPHP[i].quantidade);
            }

            var myChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        label: '<?= TRANS('total','',1); ?>',
                        data: total,
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    title: {
                        display: true,
                        text: '<?= TRANS('DISTRIBUTION_BY_DOMAIN','',1); ?>',
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