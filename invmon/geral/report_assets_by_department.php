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


$categories = getAssetsCategories($conn);


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
        <h5 class="my-4"><i class="fas fa-door-closed text-secondary"></i>&nbsp;<?= TRANS('TTL_EQUIP_X_SECTOR'); ?></h5>
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
                                $clients = getClients($conn, null, null, $_SESSION['s_allowed_clients']);
                                foreach ($clients as $client) {
                                    ?>
                                    <option value="<?= $client['id']; ?>"><?= $client['nickname']; ?></option>
                                    <?php
                                }
                            ?>
                        </select>
                    </div>
                
                    <label for="asset_unit" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_UNIT'); ?></label>
                    <div class="form-group col-md-10">
                        <select class="form-control bs-select" id="asset_unit" name="asset_unit[]" multiple="multiple">
                            
                        </select>
                    </div>


                    <label for="asset_category" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CATEGORY'); ?></label>
                    <div class="form-group col-md-10">
                        <select class="form-control bs-select" id="asset_category" name="asset_category[]" multiple="multiple">
                            <?php
                                foreach ($categories as $category) {
                                    ?>
                                    <option value="<?= $category['id']; ?>"><?= $category['cat_name']; ?></option>
                                    <?php
                                }
                            ?>
                        </select>
                    </div>

                    <label for="asset_type" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_TYPE'); ?></label>
                    <div class="form-group col-md-10">
                        <select class="form-control bs-select" id="asset_type" name="asset_type[]" multiple="multiple">
                            
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
        }
        ?>

        <div id="divResult"></div>
        <div id="table" class="table-responsive"></div>
        <div class="chart-container" id="container01">
        </div>
    </div>
    <script src="../../includes/javascript/funcoes-3.0.js"></script>
    <script src="../../includes/components/jquery/jquery.js"></script>
    <script src="../../includes/components/jquery/datetimepicker/build/jquery.datetimepicker.full.min.js"></script>
    <script src="../../includes/components/bootstrap/js/bootstrap.bundle.js"></script>
    <script type="text/javascript" src="../../includes/components/chartjs/dist/Chart.min.js"></script>
    <script type="text/javascript" src="../../includes/components/chartjs/chartjs-plugin-colorschemes/dist/chartjs-plugin-colorschemes.js"></script>
    <script type="text/javascript" src="../../includes/components/chartjs/chartjs-plugin-datalabels/chartjs-plugin-datalabels.min.js"></script>
    <script src="../../includes/components/bootstrap-select/dist/js/bootstrap-select.min.js"></script>
    <script src="./js/default_chart_generate.js"></script>
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
                styleBase: "form-control input-select-multi",

            });

            $("#client").on('change', function() {
				loadUnits();
			});

            $("#asset_category").on('change', function() {
				loadAssetTypes();
			});
           
            

            $('#idSubmit').on('click', function() {
                var loading = $(".loading");
                $(document).ajaxStart(function() {
                    loading.show();
                });
                $(document).ajaxStop(function() {
                    loading.hide();
                });

                $.ajax({
					url: './report_assets_by_department_process.php',
					method: 'POST',
					data: $('#form').serialize(),
					dataType: 'json',
				}).done(function(data) {

					if (!data.success) {
						$('#divResult').html(data.message);
						$('input, select, textarea').removeClass('is-invalid');
						if (data.field_id != "") {
							$('#' + data.field_id).focus().addClass('is-invalid');
						}
                        $('#table').html('');
                        $('#container01').empty();
						$("#idSubmit").prop("disabled", false);
					} else {
                        /* Aqui ocorrerá as chamadadas para a montagem da tabela e também para os gráficos */

                        let table = '<table class="table table-striped table-bordered">';
                        table += '<caption>' + data.criteria + '</caption>';
                        table += '<thead>';
                            table += '<tr class="header table-borderless">';
                                table += '';
                                table += '<td class="line"><?= mb_strtoupper(TRANS('DEPARTMENT')); ?></td>';
                                table += '<td class="line"><?= mb_strtoupper(TRANS('COL_TYPE')); ?></td>';
                                table += '<td class="line"><?= mb_strtoupper(TRANS('COL_AMOUNT')); ?></td>';
                                table += '<td class="line"><?= mb_strtoupper(TRANS('PERCENTAGE')); ?></td>';
                            table += '</tr>';

                        table += '</thead>';
                        table += '</tbody>';
                        for (var i in data['table']) {

                            if (data['table'][i].asset_type !== undefined) {
                                table += '<tr>';
                                table += '<td class="line">' + data['table'][i].department + '(' + data['table'][i].asset_unit + ')</td>';
                                table += '<td class="line">' + data['table'][i].asset_type + '</td>';
                                    table += '<td class="line">' + data['table'][i].quantidade + '</td>';
                                    table += '<td class="line">' + data['table'][i].percentual + '</td>';
                                table += '</tr>';
                            }
                        }
                        table += '</tbody>';
                        table += '<tfoot>';
                            table += '<tr class="header table-borderless">';
                                table += '<td colspan="2"><?= TRANS('TOTAL'); ?></td>';
                                table += '<td>' + data.total + '</td>';
                                table += '<td></td>';
                            table += '</tr>';
                        table += '</tfoot>';
                        table += '</table>';

                        $('#table').html(table);



                        /* Aqui ocorrerá a montagem dos gráficos */
                        let canvas01 = '<canvas id="graph_01" class="mb-5"></canvas>'
                        let canvas02 = '<canvas id="graph_02" class="mb-5"></canvas>'
                        let canvas03 = '<canvas id="graph_03" class="mb-5"></canvas>'
                        let canvas04 = '<canvas id="graph_04" class="mb-5"></canvas>'
                        $('#container01').empty().append(canvas01);
                        $('#container01').append(canvas02);
                        $('#container01').append(canvas03);
                        $('#container01').append(canvas04);
                        
                        let instances = Object.keys(window.Chart.instances).length;

                        const chart_01 = report_assets_general(data, 'chart_01', 'department', 'graph_01');
                        const chart_02 = report_assets_general(data, 'chart_02', 'categorie', 'graph_02');
                        const chart_03 = report_assets_general(data, 'chart_03', 'client', 'graph_03');
                        const chart_04 = report_assets_general(data, 'chart_04', 'asset_unit', 'graph_04');
                        
                        addPercentageLabels(chart_01, (1 + instances));
                        addPercentageLabels(chart_02, (2 + instances));
                        addPercentageLabels(chart_03, (3 + instances));
                        addPercentageLabels(chart_04, (4 + instances));
                        /* Final na montagem dos gráficos */

						$('#divResult').html('');
						$('input, select, textarea').removeClass('is-invalid');
						$("#idSubmit").prop("disabled", false);
						return false;
					}
				});
				return false;
			});
        });


        /* Utilizo essa função para conseguir utilizar o datalabels dinamicamente em função das várias instancias do chart */
        function addPercentageLabels(chart, metaIndex) {
            chart.options.plugins.datalabels = {
                display: function(context) {
                    return context.dataset.data[context.dataIndex] >= 1; // or !== 0 or ...
                },
                formatter: (value, ctx) => {
                    let sum = ctx.dataset._meta[metaIndex-1].total;
                    let percentage = (value * 100 / sum).toFixed(2) + "%";
                    return percentage;
                },
            };
            chart.update();
        }



		function loadUnits(targetId = 'asset_unit') {

            var loading = $(".loading");
            $(document).ajaxStart(function() {
                loading.show();
            });
            $(document).ajaxStop(function() {
                loading.hide();
            });

            $.ajax({
                url: '../../ocomon/geral/get_units_by_client.php',
                method: 'POST',
                dataType: 'json',
                data: {
                    client: $("#client").val()
                },
            }).done(function(data) {
                $('#' + targetId).empty();
                // if (Object.keys(data).length > 1) {
                //     $('#' + targetId).append('<option value=""><?= TRANS("SEL_SELECT"); ?></option>');
                // }
                $.each(data, function(key, data) {
                    $('#' + targetId).append('<option value="' + data.inst_cod + '">' + data.inst_nome + '</option>');
                });

                $('#' + targetId).selectpicker('refresh');
                if ($('#parent_id').val() != '') {
                    $('#' + targetId).selectpicker('val', $('#parent_unit').val());
                    $('#' + targetId).selectpicker('refresh');
                }
            });
        }
		
        
        
        function loadAssetTypes(targetId = 'asset_type') {

            var loading = $(".loading");
            $(document).ajaxStart(function() {
                loading.show();
            });
            $(document).ajaxStop(function() {
                loading.hide();
            });

            $.ajax({
                url: '../../invmon/geral/get_asset_types_by_category.php',
                method: 'POST',
                dataType: 'json',
                data: {
                    asset_category: $("#asset_category").val()
                },
            }).done(function(data) {
                $('#' + targetId).empty();
                // if (Object.keys(data).length > 1) {
                //     $('#' + targetId).append('<option value=""><?= TRANS("SEL_SELECT"); ?></option>');
                // }
                $.each(data, function(key, data) {
                    $('#' + targetId).append('<option value="' + data.tipo_cod + '">' + data.tipo_nome + '</option>');
                });

                $('#' + targetId).selectpicker('refresh');
                if ($('#parent_id').val() != '') {
                    $('#' + targetId).selectpicker('val', $('#parent_unit').val());
                    $('#' + targetId).selectpicker('refresh');
                }
            });
        }





    </script>
</body>

</html>