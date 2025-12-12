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
$sess_cat1 = (isset($_SESSION['s_rep_filters']['cat1']) ? $_SESSION['s_rep_filters']['cat1'] : -1);
$sess_cat2 = (isset($_SESSION['s_rep_filters']['cat2']) ? $_SESSION['s_rep_filters']['cat2'] : -1);
$sess_cat3 = (isset($_SESSION['s_rep_filters']['cat3']) ? $_SESSION['s_rep_filters']['cat3'] : -1);



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
        <h5 class="my-4"><i class="fas fa-tags text-secondary"></i>&nbsp;<?= TRANS('PROBLEM_TYPES_CATEGORIES'); ?></h5>
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

        $qry_config = "SELECT * FROM config ";
        $exec_config = $conn->query($qry_config);
        $row_config = $exec_config->fetch();
        $criterio = "";



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


                    <label for="cat1" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= $row_config['conf_prob_tipo_1']; ?></label>
                    <div class="form-group col-md-10">
                        <select class="form-control sel2" id="cat1" name="cat1">
                            <option value="-1"><?= TRANS('ALL'); ?></option>
                            <?php
                            $sql = "SELECT * FROM prob_tipo_1 ORDER BY probt1_desc";
                            $resultado = $conn->query($sql);
                            foreach ($resultado->fetchAll() as $rowCat1) {
                                print "<option value='" . $rowCat1['probt1_cod'] . "'";
                                echo ($rowCat1['probt1_cod'] == $sess_cat1 ? ' selected' : '');
                                print ">" . $rowCat1['probt1_desc'] . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <label for="cat2" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= $row_config['conf_prob_tipo_2']; ?></label>
                    <div class="form-group col-md-10">
                        <select class="form-control sel2" id="cat2" name="cat2">
                            <option value="-1"><?= TRANS('ALL'); ?></option>
                            <?php
                            $sql = "SELECT * FROM prob_tipo_2 ORDER BY probt2_desc";
                            $resultado = $conn->query($sql);
                            foreach ($resultado->fetchAll() as $rowCat2) {
                                print "<option value='" . $rowCat2['probt2_cod'] . "'";
                                echo ($rowCat2['probt2_cod'] == $sess_cat2 ? ' selected' : '');
                                print ">" . $rowCat2['probt2_desc'] . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <label for="cat3" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= $row_config['conf_prob_tipo_3']; ?></label>
                    <div class="form-group col-md-10">
                        <select class="form-control sel2" id="cat3" name="cat3">
                            <option value="-1"><?= TRANS('ALL'); ?></option>
                            <?php
                            $sql = "SELECT * FROM prob_tipo_3 ORDER BY probt3_desc";
                            $resultado = $conn->query($sql);
                            foreach ($resultado->fetchAll() as $rowCat3) {
                                print "<option value='" . $rowCat3['probt3_cod'] . "'";
                                echo ($rowCat3['probt3_cod'] == $sess_cat3 ? ' selected' : '');
                                print ">" . $rowCat3['probt3_desc'] . "</option>";
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
            $criterio = "";

            $client = (isset($_POST['client']) && !empty($_POST['client']) ? $_POST['client'] : "");
            $_SESSION['s_rep_filters']['client'] = $client;
            $_SESSION['s_rep_filters']['area'] = $_POST['area'];
            $_SESSION['s_rep_filters']['cat1'] = $_POST['cat1'];
            $_SESSION['s_rep_filters']['cat2'] = $_POST['cat2'];
            $_SESSION['s_rep_filters']['cat3'] = $_POST['cat3'];
            $clientName = (!empty($client) ? getClients($conn, $client)['nickname']: "");
            $clausule = (!empty($client) ? " AND o.client IN ({$client}) " : "");
            $noneClient = TRANS('FILTERED_CLIENT') . ": " . TRANS('NONE_FILTER') . "&nbsp;&nbsp;";
            $criterio = (!empty($client) ? TRANS('FILTERED_CLIENT') . ": {$clientName}&nbsp;&nbsp;" : $noneClient );

            $typeFields = "";
            $query = "SELECT count(*)  AS quantidade, s.sistema AS area, s.sis_id,  p.problema as problema, pt1.*, pt2.*, pt3.* 
                        FROM ocorrencias AS o, status AS st, sistemas AS s, problemas as p 
                        LEFT JOIN prob_tipo_1 as pt1 on pt1.probt1_cod = p.prob_tipo_1 
                        LEFT JOIN prob_tipo_2 as pt2 on pt2.probt2_cod = p.prob_tipo_2 
                        LEFT JOIN prob_tipo_3 as pt3 on pt3.probt3_cod = p.prob_tipo_3 
                        WHERE 
                            o.status = st.stat_id AND 
                            st.stat_ignored <> 1 AND 
                            o.sistema = s.sis_id AND 
                            o.problema = p.prob_id ";

            $queryFields = "";
            $queryRules = " FROM ocorrencias AS o, status AS st, sistemas AS s, problemas as p 
            LEFT JOIN prob_tipo_1 as pt1 on pt1.probt1_cod = p.prob_tipo_1 
            LEFT JOIN prob_tipo_2 as pt2 on pt2.probt2_cod = p.prob_tipo_2 
            LEFT JOIN prob_tipo_3 as pt3 on pt3.probt3_cod = p.prob_tipo_3 
            WHERE o.status = st.stat_id AND st.stat_ignored <> 1 AND o.sistema = s.sis_id AND o.problema = p.prob_id ";
            $queryGroups = "";


            if (!empty($filter_areas)) {
                /* Nesse caso o usuário só pode filtrar por áreas que faça parte */
                if (!empty($_POST['area']) && ($_POST['area'] != -1)) {
                    $query .= " AND o.sistema = " . $_POST['area'] . "";
                    $queryRules .= " AND o.sistema = " . $_POST['area'] . "";

                    $getAreaName = "SELECT * from sistemas where sis_id = " . $_POST['area'] . "";
                    $exec = $conn->query($getAreaName);
                    $rowAreaName = $exec->fetch();
                    $nomeArea = $rowAreaName['sistema'];
                    $criterio .= TRANS('FILTERED_AREA') . ": {$nomeArea}";
                } else {
                    $query .= " AND o.sistema IN ({$u_areas}) ";
                    $queryRules .= " AND o.sistema IN ({$u_areas}) ";
                    $criterio .= TRANS('FILTERED_AREA') . ": [" . $areas_names . "]";
                }
            } else

            if (isset($_POST['area']) && !empty($_POST['area']) && ($_POST['area'] != -1)) {
                $query .= " AND o.sistema = '" . $_POST['area'] . "'";
                $queryRules .= " AND o.sistema = '" . $_POST['area'] . "'";
                $qry_criterio = "SELECT sistema FROM sistemas WHERE sis_id = " . $_POST['area'] . " ";
                $exec_criterio = $conn->query($qry_criterio);
                $row_criterio = $exec_criterio->fetch();
                $criterio .= TRANS('FILTERED_AREA') . ": " . $row_criterio['sistema'] . ",";
            }

            if (isset($_POST['cat1']) && ($_POST['cat1'] != -1)) {
                $query .= " AND pt1.probt1_cod = '" . $_POST['cat1'] . "' ";
                $queryRules .= " AND pt1.probt1_cod = '" . $_POST['cat1'] . "' ";
        
                $qry_criterio = "SELECT probt1_desc FROM prob_tipo_1 WHERE probt1_cod = " . $_POST['cat1'] . " ";
                $exec_criterio = $conn->query($qry_criterio);
                $row_criterio = $exec_criterio->fetch();
                $criterio .= " " . $row_config['conf_prob_tipo_1'] . ": " . $row_criterio['probt1_desc'] . ",";
            }

            if (isset($_POST['cat2']) && ($_POST['cat2'] != -1)) {
                $query .= " AND pt2.probt2_cod = '" . $_POST['cat2'] . "' ";
                $queryRules .= " AND pt2.probt2_cod = '" . $_POST['cat2'] . "' ";
                $qry_criterio = "SELECT probt2_desc FROM prob_tipo_2 WHERE probt2_cod = " . $_POST['cat2'] . " ";
                $exec_criterio = $conn->query($qry_criterio);
                $row_criterio = $exec_criterio->fetch();
                $criterio .= " " . $row_config['conf_prob_tipo_2'] . ": " . $row_criterio['probt2_desc'] . ",";
            }

            if (isset($_POST['cat3']) && ($_POST['cat3'] != -1)) {
                $query .= " AND pt3.probt3_cod = '" . $_POST['cat3'] . "' ";
                $queryRules .= " AND pt3.probt3_cod = '" . $_POST['cat3'] . "' ";
                $qry_criterio = "SELECT probt3_desc FROM prob_tipo_3 WHERE probt3_cod = " . $_POST['cat3'] . " ";
                $exec_criterio = $conn->query($qry_criterio);
                $row_criterio = $exec_criterio->fetch();
                $criterio .= " " . $row_config['conf_prob_tipo_3'] . ": " . $row_criterio['probt3_desc'] . ",";
            }

            if (strlen((string)$criterio) == 0) {
                $criterio = TRANS('NONE_FILTER');
            } else {
                $criterio = substr($criterio, 0, -1);
            }

            $criterio .= ", " . TRANS('ONLY_CLOSED_IN_THE_PERIOD');


            if ((!isset($_POST['d_ini'])) || (!isset($_POST['d_fim']))) {
                $_SESSION['flash'] = message('info', '', TRANS('MSG_ALERT_PERIOD'), '');
                // echo "<script>redirect('" . $_SERVER['PHP_SELF'] . "')</script>";
                redirect($_SERVER['PHP_SELF']);
            } else {

                $_SESSION['s_rep_filters']['d_ini'] = $_POST['d_ini'];
                $_SESSION['s_rep_filters']['d_fim'] = $_POST['d_fim'];

                $d_ini = $_POST['d_ini'] . $hora_inicio;
                $d_ini = dateDB($d_ini);

                $d_fim = $_POST['d_fim'] . $hora_fim;
                $d_fim = dateDB($d_fim);

                if ($d_ini <= $d_fim) {

                    /* Query apenas para retornar os dados para o gráfico 1 - o agrupamento é diferente para a listagem */
                    $queryFields = "SELECT count(*) AS quantidade,  pt1.* ";
                    $queryChart = $queryFields.$queryRules . " AND o.data_fechamento >= '" . $d_ini . "' AND o.data_fechamento <= '" . $d_fim . "' 
                    AND o.data_atendimento IS NOT NULL 
                    {$clausule}
                    GROUP BY 
                        pt1.probt1_cod, pt1.probt1_desc

                    ORDER BY pt1.probt1_desc, quantidade desc "; /* , pt2.probt2_desc, pt3.probt3_desc, */
                    $resultadoChart = $conn->query($queryChart);

                    /* Query apenas para retornar os dados para o gráfico 2 - o agrupamento é diferente para a listagem */
                    $queryFields = "SELECT count(*) AS quantidade, pt2.* ";
                    $queryChart2 = $queryFields.$queryRules . " AND o.data_fechamento >= '" . $d_ini . "' AND o.data_fechamento <= '" . $d_fim . "' AND o.data_atendimento IS NOT NULL 
                    {$clausule}
                    GROUP  BY  

                        pt2.probt2_cod, pt2.probt2_desc 

                    ORDER BY pt2.probt2_desc, quantidade desc "; /* pt3.probt3_desc,  */
                    $resultadoChart2 = $conn->query($queryChart2);

                    /* Query apenas para retornar os dados para o gráfico 3 - o agrupamento é diferente para a listagem */
                    $queryFields = "SELECT count(*)  AS quantidade, pt3.* ";
                    $queryChart3 = $queryFields.$queryRules . " AND o.data_fechamento >= '" . $d_ini . "' AND o.data_fechamento <= '" . $d_fim . "' AND o.data_atendimento IS NOT NULL 
                    {$clausule}
                    GROUP  BY 
                    pt3.probt3_cod, pt3.probt3_desc 

                    ORDER BY pt3.probt3_desc, quantidade desc ";
                    $resultadoChart3 = $conn->query($queryChart3);

                    $query .= " AND o.data_fechamento >= '" . $d_ini . "' AND o.data_fechamento <= '" . $d_fim . "' 
                                AND o.data_atendimento IS NOT NULL 
                                {$clausule}
                                GROUP  BY 
                                s.sistema, s.sis_id, p.problema, 
                                pt1.probt1_cod, pt1.probt1_desc, 
                                pt2.probt2_cod, pt2.probt2_desc, 
                                pt3.probt3_cod, pt3.probt3_desc 
                                ORDER BY pt1.probt1_desc, pt2.probt2_desc, pt3.probt3_desc, quantidade desc, area ";
                    $resultado = $conn->query($query);
                    $linhas = $resultado->rowCount();

                    // dump($query);
                    // var_dump([
                    //     'Query' => $query,
                    // ]); exit();


                    if ($linhas == 0) {
                        $_SESSION['flash'] = message('info', '', TRANS('MSG_NO_DATA_IN_PERIOD'), '');
                        // echo "<script>redirect('" . $_SERVER['PHP_SELF'] . "')</script>";
                        redirect($_SERVER['PHP_SELF']);
                    } else {

                        ?>
                        <p><?= TRANS('TTL_PERIOD_FROM') . "&nbsp;" . dateScreen($d_ini, 1) . "&nbsp;" . TRANS('DATE_TO') . "&nbsp;" . dateScreen($d_fim, 1); ?></p>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <!-- table-hover -->
                                <caption><?= $criterio; ?><span class="px-2" style="float:right" id="new_search"><?= TRANS('NEW_SEARCH'); ?></span></caption>
                                <thead>
                                    <tr class="header table-borderless">
                                        <td class="line"><?= mb_strtoupper($row_config['conf_prob_tipo_1']); ?></td>
                                        <td class="line"><?= mb_strtoupper($row_config['conf_prob_tipo_2']); ?></td>
                                        <td class="line"><?= mb_strtoupper($row_config['conf_prob_tipo_3']); ?></td>
                                        <td class="line"><?= mb_strtoupper(TRANS('COL_QTD')); ?></td>
                                        <td class="line"><?= mb_strtoupper(TRANS('SERVICE_AREA')); ?></td>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $data = [];
                                    $data2 = [];
                                    $data3 = [];
                                    
                                    $total = 0;
                                    foreach ($resultado->fetchall() as $row) {
                                        // $data[] = $row;
                                        ?>
                                        <tr>
                                            <td class="line"><?= $row['probt1_desc']; ?></td>
                                            <td class="line"><?= $row['probt2_desc']; ?></td>
                                            <td class="line"><?= $row['probt3_desc']; ?></td>
                                            <td class="line"><?= $row['quantidade']; ?></td>
                                            <td class="line"><?= $row['area']; ?></td>
                                        </tr>
                                        <?php
                                        $total += $row['quantidade'];
                                    }

                                    foreach ($resultadoChart->fetchall() as $rowDataChart) {
                                        $data[] = $rowDataChart;
                                    }
                                    foreach ($resultadoChart2->fetchall() as $rowDataChart2) {
                                        $data2[] = $rowDataChart2;
                                    }
                                    foreach ($resultadoChart3->fetchall() as $rowDataChart3) {
                                        $data3[] = $rowDataChart3;
                                    }
                                    
                                    $json = json_encode($data);
                                    $json2 = json_encode($data2);
                                    $json3 = json_encode($data3);
                                    ?>
                                </tbody>
                                <tfoot>
                                    <tr class="header table-borderless">
                                        <td colspan="3"><?= TRANS('TOTAL'); ?></td>
                                        <td colspan="2"><?= $total; ?></td>
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
                        <div class="chart-container">
                            <canvas id="canvasChart3"></canvas>
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
                    // echo "<script>redirect('" . $_SERVER['PHP_SELF'] . "')</script>";
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
                showChart2('canvasChart2');
                showChart3('canvasChart3');
            }

        });


        function showChart(canvasID) {
            var ctx = $('#' + canvasID);
            var dataFromPHP = <?= $json; ?>

            var labels = []; // X Axis Label
            var total = []; // Value and Y Axis basis

            for (var i in dataFromPHP) {
                // console.log(dataFromPHP[i]);
                labels.push(dataFromPHP[i].probt1_desc);
                total.push(dataFromPHP[i].quantidade);
            }

            var myChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        label: '<?= TRANS('total','',1); ?>',
                        data: total,
                        // backgroundColor: [
                        //     'rgba(255, 99, 132, 0.2)',
                        //     'rgba(54, 162, 235, 0.2)',
                        //     'rgba(255, 206, 86, 0.2)',
                        //     'rgba(75, 192, 192, 0.2)',
                        //     'rgba(153, 102, 255, 0.2)',
                        //     'rgba(255, 159, 64, 0.2)'
                        // ],
                        // borderColor: [
                        //     'rgba(255, 99, 132, 1)',
                        //     'rgba(54, 162, 235, 1)',
                        //     'rgba(255, 206, 86, 1)',
                        //     'rgba(75, 192, 192, 1)',
                        //     'rgba(153, 102, 255, 1)',
                        //     'rgba(255, 159, 64, 1)'
                        // ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    title: {
                        display: true,
                        text: '<?= $row_config['conf_prob_tipo_1'] ?>',
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

        function showChart2(canvasID) {
            var ctx2 = $('#' + canvasID);
            var dataFromPHP2 = <?= $json2; ?>

            var labels = []; // X Axis Label
            var total = []; // Value and Y Axis basis

            for (var i in dataFromPHP2) {
                // console.log(dataFromPHP2[i]);
                // labels.push(dataFromPHP2[i].operador);
                labels.push(dataFromPHP2[i].probt2_desc);
                total.push(dataFromPHP2[i].quantidade);
            }

            var myChart2 = new Chart(ctx2, {
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
                        text: '<?= $row_config['conf_prob_tipo_2'] ?>',
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
                            formatter: (value, ctx2) => {
                                let sum = ctx2.dataset._meta[1].total;
                                let percentage = (value * 100 / sum).toFixed(2) + "%";
                                return percentage;
                            }
                        },
                    },
                }
            });
        }

        function showChart3(canvasID) {
            var ctx3 = $('#' + canvasID);
            var dataFromPHP3 = <?= $json3; ?>;

            var labels = []; // X Axis Label
            var total = []; // Value and Y Axis basis

            for (var i in dataFromPHP3) {
                // console.log(dataFromPHP3[i]);
                // labels.push(dataFromPHP3[i].operador);
                labels.push(dataFromPHP3[i].probt3_desc);
                total.push(dataFromPHP3[i].quantidade);
            }

            var myChart3 = new Chart(ctx3, {
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
                        text: '<?= $row_config['conf_prob_tipo_3'] ?>',
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
                            formatter: (value, ctx3) => {
                                let sum = ctx3.dataset._meta[2].total;
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