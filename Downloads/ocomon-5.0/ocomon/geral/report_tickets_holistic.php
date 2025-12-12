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
        }
    </style>

    <title>OcoMon&nbsp;<?= VERSAO; ?></title>
</head>

<body>
    
    <div class="container">
        <div id="idLoad" class="loading" style="display:none"></div>
    </div>


    <div class="container">
        <h5 class="my-4"><i class="fas fa-ticket-alt text-secondary"></i>&nbsp;<?= TRANS('TICKETS_IN_PERIOD'); ?></h5>
        <div class="modal" tabindex="-1" style="z-index:9001!important" id="modalReport">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title" id="reportTitle"><i class="fas fa-ticket-alt"></i>&nbsp;<?= TRANS('TICKETS_IN_PERIOD'); ?></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <table class="table  table-hover table-striped rounded">
                            <thead class="text-white" style="background-color: #48606b;">
                                <tr>
                                    <th scope="col"><?= TRANS('OPENED'); ?></th>
                                    <th scope="col"><?= TRANS('FIRST_RESPONSE_IN_PERIOD'); ?></th>
                                    <th scope="col"><?= TRANS('CARDS_CLOSED'); ?></th>
                                    <th scope="col"><?= TRANS('CLOSED'); ?></th>
                                    <th scope="col"><?= TRANS('AUTOMATIC_CLOSED'); ?></th>
                                    <th scope="col"><?= TRANS('RATED'); ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td id="opened"></td>
                                        <td id="first_response"></td>
                                        <td id="done"></td>
                                        <td id="closed"></td>
                                        <td id="auto_closed"></td>
                                        <td id="rated"></td>
                                    </tr>
                                </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <?php
        if (isset($_SESSION['flash']) && !empty($_SESSION['flash'])) {
            echo $_SESSION['flash'];
            $_SESSION['flash'] = '';
        }

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
                                    <option value="<?= $client['id']; ?>"><?= $client['nickname']; ?></option>
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
                                echo ($rowArea['sis_id'] == $_SESSION['s_area'] ? ' selected' : '');
                                print ">" . $rowArea['sistema'] . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <label for="d_ini" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('START_DATE'); ?></label>
                    <div class="form-group col-md-10">
                        <input type="text" class="form-control " id="d_ini" name="d_ini" value="<?= date("01/m/Y"); ?>" autocomplete="off" required />
                    </div>

                    <label for="d_fim" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('END_DATE'); ?></label>
                    <div class="form-group col-md-10">
                        <input type="text" class="form-control " id="d_fim" name="d_fim" value="<?= date("d/m/Y"); ?>" autocomplete="off" required />
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
            <div id="divResult"></div>
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


			$('#idSubmit').on('click', function(e) {
				e.preventDefault();
				var loading = $(".loading");
				$(document).ajaxStart(function() {
					loading.show();
				});
				$(document).ajaxStop(function() {
					loading.hide();
				});

				$.ajax({
					url: './report_tickets_holistic_process.php',
					method: 'POST',
					data: $('#form').serialize(),
					dataType: 'json',
				}).done(function(response) {

					if (!response.success) {
						$('#divResult').html(response.message);
						$('input, select, textarea').removeClass('is-invalid');
						if (response.field_id != "") {
							$('#' + response.field_id).focus().addClass('is-invalid');
						}
						$("#idSubmit").prop("disabled", false);
					} else {
						$('#divResult').html('');
						$('input, select, textarea').removeClass('is-invalid');
						$("#idSubmit").prop("disabled", false);
						// var url = '<?= $_SERVER['PHP_SELF'] ?>';
						// $(location).prop('href', url);
						// return false;

                        $('#opened').empty().text(response.opened);
                        $('#first_response').empty().text(response.started);
                        $('#done').empty().text(response.done);
                        $('#closed').empty().text(response.closed);
                        $('#auto_closed').empty().text(response.auto_closed);
                        $('#rated').empty().text(response.rated);

                        $('#modalReport').modal();


					}
				});
				return false;
			});
        });

    </script>
</body>

</html>