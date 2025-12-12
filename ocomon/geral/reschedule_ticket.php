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

$config = getConfig($conn);
$mailConfig = getMailConfig($conn);

if (isset($_POST['numero']) && !empty($_POST['numero'])) {
    $COD = $_POST['numero'];
} else
if (isset($_GET['numero']) && !empty($_GET['numero'])) {
    $COD = $_GET['numero'];
} else {
    echo message('warning', 'Ooops!', TRANS('MSG_ERR_NOT_EXECUTE'), '', '', 1);
    return;
}


$area = (isset($_GET['area']) && !empty($_GET['area']) ? noHtml($_GET['area']) : "");

$query = $QRY["ocorrencias_full_ini"] . " where numero in (" . $COD . ") order by numero";


$resultado = $conn->query($query);
$row = $resultado->fetch();

if (empty($row['updated_at']) || $row['status_cod'] == 4) {
    header("Location: ./ticket_show.php?numero=" . $COD);
    return;
}

$dateScheduleTo = dateScreen($row['oco_scheduled_to'], 0, 'd/m/Y H:i');


$ticketAuxWorkers = getTicketWorkers($conn, $COD, 2);
$selectAuxWorkers = [];

if (!empty($ticketAuxWorkers)) {
    foreach ($ticketAuxWorkers as $aux) {
        $selectAuxWorkers[] = $aux['user_id'];
    }
}
$selectAuxWorkersJs = json_encode($selectAuxWorkers);

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../../includes/css/estilos.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/jquery/datetimepicker/jquery.datetimepicker.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap/custom.css" /> <!-- custom bootstrap v4.5 -->
    <link rel="stylesheet" type="text/css" href="../../includes/components/fontawesome/css/all.min.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap-select/dist/css/bootstrap-select.min.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/css/my_bootstrap_select.css" />
    <link href="../../includes/components/fullcalendar/lib/main.css" rel="stylesheet" />
	<link rel="stylesheet" type="text/css" href="../../includes/css/my_fullcalendar.css" />

	<title>OcoMon&nbsp;<?= VERSAO; ?></title>

    <style>
        .modal-1000 {
            max-width: 1000px;
            margin: 30px auto;
        }

        .canvas-calendar {
            width: 90%;
            margin: 30px auto;
            /* height: 60vh; */
            height: auto;
        }
    </style>
</head>

<body>
	
	<div class="container">
		<div id="idLoad" class="loading" style="display:none"></div>
	</div>


	<div class="container-fluid">
		<!-- <h5 class="my-4"><i class="fas fa-book text-secondary"></i>&nbsp;<?= TRANS('TLT_ADMIN_LOAN'); ?></h5> -->
		<div class="modal" id="modal" tabindex="-1" style="z-index:9001!important">
			<div class="modal-dialog modal-xl">
				<div class="modal-content">
					<div id="divDetails">
					</div>
				</div>
			</div>
		</div>

        <!-- Modal para exibição do calendário -->
        <div class="modal fade " id="modalCalendar" tabindex="-1" style="z-index:2001!important" role="dialog" aria-labelledby="mymodalCalendar" aria-hidden="true">
            <!-- <div class="modal-dialog modal-xl" role="document"> -->
            <div class="modal-dialog modal-1000" role="document">
                <div class="modal-content">
                    <div class="modal-header text-center bg-light">
                        <h4 class="modal-title w-100 font-weight-bold text-secondary"><i class="fas fa-calendar-alt"></i>&nbsp;<?= TRANS('WORKER_CALENDAR_TITLE'); ?></h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="form-group col-md-4 mt-4 mb-0">
                        <select class="form-control " id="worker-calendar" name="worker-calendar">
                        </select>
                        <small class="form-text text-muted"><?= TRANS('HELPER_WORKER_FILTER'); ?></small>
                    </div>

                    <div class="row mt-4 canvas-calendar" id="idLoadCalendar">
                        <!-- Conteúdo carregado via ajax -->
                    </div>


                    <div class="modal-footer d-flex justify-content-end bg-light">
                        <button id="cancelCalendar" class="btn btn-secondary" data-dismiss="modal" aria-label="Close"><?= TRANS('BT_CLOSE'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <!-- FINAL DA MODAL DE CALENDÁRIO -->


        <!-- Modal de detalhes do evento clicado no calendário -->
        <div class="modal fade child-modal" id="modalEvent" tabindex="-1" style="z-index:9002!important" role="dialog" aria-labelledby="mymodalEvent" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header text-center bg-light">

                        <h4 class="modal-title w-100 font-weight-bold text-secondary"><i class="fas fa-calendar-check"></i>&nbsp;<?= TRANS('SCHEDULING_DETAILS'); ?></h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <input type="hidden" name="eventTicketId" id="eventTicketId">
                    <input type="hidden" name="eventTicketUrl" id="eventTicketUrl">
                    
                    <div class="row mx-2 mt-4">
                        <div class="form-group col-md-3 font-weight-bold text-right">
                            <?= TRANS('TICKET_NUMBER'); ?>:
                        </div>
                        <div class="form-group col-md-3 pointer'"><span class="badge badge-secondary p-2 pointer" id="calTicketNum" onclick="goToTicketDetails()"></span></div>

                        <div class="form-group col-md-3 font-weight-bold text-right">
                            <?= TRANS('COL_STATUS'); ?>:
                        </div>
                        <div class="form-group col-md-3 small" id="status"></div>
                    </div>

                    <div class="row mx-2">
                        <div class="form-group col-md-3 font-weight-bold text-right">
                            <?= TRANS('OPENING_DATE'); ?>:
                        </div>
                        <div class="form-group col-md-3 small" id="openingDate"></div>
                        
                        <div class="form-group col-md-3 font-weight-bold text-right">
                            <?= TRANS('FIELD_SCHEDULE_TO'); ?>:
                        </div>
                        <div class="form-group col-md-3 small" id="scheduledTo"></div>
                    </div>

                    <div class="row mx-2">
                        <div class="form-group col-md-3 font-weight-bold text-right">
                            <?= TRANS('OPENED_BY'); ?>:
                        </div>
                        <div class="form-group col-md-3 small" id="openedBy"></div>
                        
                        <div class="form-group col-md-3 font-weight-bold text-right">
                            <?= TRANS('DEPARTMENT'); ?>:
                        </div>
                        <div class="form-group col-md-3 small" id="department"></div>
                    </div>

                    <div class="row mx-2">
                        <div class="form-group col-md-3 font-weight-bold text-right">
                            <?= TRANS('REQUESTER_AREA'); ?>:
                        </div>
                        <div class="form-group col-md-3 small" id="requesterArea"></div>
                        
                        <div class="form-group col-md-3 font-weight-bold text-right">
                            <?= TRANS('RESPONSIBLE_AREA'); ?>:
                        </div>
                        <div class="form-group col-md-3 small" id="responsibleArea"></div>
                    </div>

                    <div class="row mx-2">
                        <div class="form-group col-md-3 font-weight-bold text-right">
                            <?= TRANS('ISSUE_TYPE'); ?>:
                        </div>
                        <div class="form-group col-md-3 small" id="issueType"></div>
                        
                        <div class="form-group col-md-3 font-weight-bold text-right">
                            <?= TRANS('OCO_RESP'); ?>:
                        </div>
                        <div class="form-group col-md-3 small" id="operator"></div>
                    </div>

                    <div class="row mx-2">
                        <div class="form-group col-md-3 font-weight-bold text-right">
                            <?= TRANS('WORKERS'); ?>:
                        </div>
                        <div class="form-group col-md-9 small" id="workers"></div>
                    </div>

                    <div class="row mx-2">
                        <div class="form-group col-md-3 font-weight-bold text-right">
                            <?= TRANS('DESCRIPTION'); ?>:
                        </div>
                        <div class="form-group col-md-9 small" id="description"></div>
                    </div>

                    <div class="modal-footer d-flex justify-content-end bg-light">
                        <button id="cancelEventDetails" class="btn btn-secondary" data-dismiss="modal" aria-label="Close"><?= TRANS('BT_CLOSE'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <!-- FINAL DA MODAL DE EVENTOS DO CALENDÁRIO -->


		<?php
		if (isset($_SESSION['flash']) && !empty($_SESSION['flash'])) {
			echo $_SESSION['flash'];
			$_SESSION['flash'] = '';
		}



        if ($_SESSION['s_can_route']) {
            $title1 = TRANS('SCHEDULE_OR_ROUTE_TICKET');
            $title2 = TRANS('SCHEDULE_OR_ROUTE_TICKET_HELPER');
        } else {
            $title1 = TRANS('SCHEDULE_TICKET');
            $title2 = TRANS('SCHEDULE_TICKET_HELPER');
        }

		?>

        <div class="modal-content">
            <div id="divResultSchedule"></div>
            <div class="modal-header text-center bg-light">

                <h4 class="modal-title w-100 font-weight-bold text-secondary"><i class="fas fa-calendar-alt"></i>&nbsp;<?= $title1; ?></h4>
                
            </div>
            
            <div class="row p-3">
                <div class="col">
                    <p><?= $title2; ?>.</p>
                </div>
            </div>


            <?php
                if ($_SESSION['s_can_route']) {

                    if (!empty($area)) {
                        $workers = getUsersByArea($conn, $area, true, null, true);
                    } else {
                        $workers = getUsers($conn, null, ['1,2'], null, true);
                    }

                    ?>
                    <div class="row mx-2">
                        <div class="form-group col-md-6">
                            <select class="form-control" name="main_worker" id="main_worker">
                                <option value=""><?= TRANS('MAIN_WORKER'); ?></option>
                                <?php
                                    foreach ($workers as $worker) {
                                        ?>
                                            <option value="<?= $worker['user_id']; ?>"
                                            <?= ($worker['user_id'] == $row['main_worker'] ? " selected" : ""); ?>
                                            ><?= $worker['nome']; ?></option>
                                        <?php
                                    }
                                ?>
                            </select>
                            <small class="form-text text-muted" id="loadCalendar"><?= TRANS('HELPER_WORKER_LABEL'); ?> <a href="#"  class="text-primary"><?= TRANS('CALENDAR'); ?></a></small>
                        </div>
                        <div class="form-group col-md-6">
                            <select class="form-control sel-multi" name="aux_worker[]" id="aux_worker" multiple="multiple" >
                            <?php
                                
                                $ticketAuxWorkers = getTicketWorkers($conn, $COD, 2);
                                foreach ($workers as $worker) {

                                    $selected = "";
                                    foreach ($ticketAuxWorkers as $ticketAuxWorker) {
                                        if ($worker['user_id'] == $ticketAuxWorker['user_id']) {
                                            $selected = "selected";
                                        }
                                    }
                                    echo "<option value='".$worker['user_id']."' {$selected}>".$worker['nome']."</option>";
                                }
                            ?>
                            </select>
                            <small class="form-text text-muted"><?= TRANS('HELPER_AUX_WORKER_LABEL'); ?></small>
                        </div>
                    </div>

                    <?php
                } else {
                    ?>
                        <input type="hidden" name="main_worker" id="main_worker">
                        <input type="hidden" name="aux_worker" id="aux_worker">
                        <input type="hidden" name="area" id="area" value="<?= $area; ?>">
                    <?php
                }
            ?>


            <div class="row mx-2">

                <div class="form-group col-md-12">
                    <input type="text" class="form-control " id="idDate_schedule" name="date_schedule" placeholder="<?= TRANS('DATE_TO_SCHEDULE'); ?>" value="<?= $dateScheduleTo; ?>" autocomplete="off" />
                </div>
            </div>

            <!-- Assentamento -->
            <div class="row mx-2">
                <div class="form-group col-md-12">
                    <textarea class="form-control " name="entry_schedule" id="entry_schedule" placeholder="<?= TRANS('PLACEHOLDER_ASSENT'); ?>"></textarea>
                </div>
            </div>

            <?php
            /* Só exibe as opções de envio de e-mail se o envio estiver habilitado nas configurações do sistema */
            if ($mailConfig['mail_send']) {
            ?>
                <div class="row mx-2">
                    <div class="col"><i class="fas fa-envelope text-secondary"></i>&nbsp;<?= TRANS('OCO_FIELD_SEND_MAIL_TO'); ?>:</div>
                </div>
                <div class="row mx-2">
                    <div class="form-group col-md-12">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input " type="checkbox" name="mailAR" value="ok" id="idMailToArea" checked>
                            <legend class="col-form-label col-form-label-sm"><?= TRANS('RESPONSIBLE_AREA'); ?></legend>
                        </div>

                        <?php
                        if (getOpenerLevel($conn, $row['numero']) == 3 || !empty($row['contato_email'])) { /* Se foi aberto pelo usuário final ou se tem e-mail de contato */
                        ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input " type="checkbox" name="mailUS" value="ok" id="idMailToUser">
                                <legend class="col-form-label col-form-label-sm"><?= TRANS('CONTACT'); ?></legend>
                            </div>
                        <?php
                        }
                        ?>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input " type="checkbox" name="mailWorkers" value="ok" id="mailWorkers" disabled>
                            <legend class="col-form-label col-form-label-sm"><?= TRANS('WORKERS'); ?></legend>
                        </div>
                    </div>
                </div>
            <?php
            }
            ?>

            <input type="hidden" name="ticket" id="ticket" value="<?= $row['numero']; ?>">
            <div class="modal-footer d-flex justify-content-end bg-light">
                <button id="updateSchedule" class="btn btn-primary"><?= TRANS('BT_UPDATE'); ?></button>
                <button id="cancelUpdate" class="btn btn-secondary" data-dismiss="modal" aria-label="Close"><?= TRANS('BT_CANCEL'); ?></button>
            </div>
        </div>

	</div>

    <script src="../../includes/javascript/funcoes-3.0.js"></script>
    <script src="../../includes/components/jquery/jquery.js"></script>
    <script src="../../includes/components/jquery/plentz-jquery-maskmoney/dist/jquery.maskMoney.min.js"></script>
    <script src="../../includes/components/jquery/jquery.initialize.min.js"></script>
    <script src="../../includes/components/jquery/datetimepicker/build/jquery.datetimepicker.full.min.js"></script>
    <script src="../../includes/components/bootstrap/js/bootstrap.bundle.js"></script>
    <script src="../../includes/components/bootstrap-select/dist/js/bootstrap-select.min.js"></script>
    <script src="../../includes/components/fullcalendar/lib/main.js"></script>
    <script src="../../includes/components/fullcalendar/lib/locales/pt-br.js"></script>
    <script src="./tickets_calendar.js"></script>
	<script type="text/javascript">
		$(function() {

            $('[data-toggle="popover"]').popover({
                html: true,
                container: 'body',
                placement: 'top',
                trigger: 'hover'
            });


            $(".popover-dismiss").popover({
                trigger: "focus",
            });

            
            /* Idioma global para os calendários */
			$.datetimepicker.setLocale('pt-BR');

            $.fn.selectpicker.Constructor.BootstrapVersion = '4';
            $('#main_worker').selectpicker({
				/* placeholder */
				title: "<?= TRANS('MAIN_WORKER', '', 1); ?>",
				liveSearch: true,
				liveSearchNormalize: true,
				liveSearchPlaceholder: "<?= TRANS('BT_SEARCH', '', 1); ?>",
				noneResultsText: "<?= TRANS('NO_RECORDS_FOUND', '', 1); ?> {0}",
				style: "",
				styleBase: "form-control ",
			});
            $('#worker-calendar').selectpicker({
				/* placeholder */
				title: "<?= TRANS('ALL', '', 1); ?>",
				liveSearch: true,
				liveSearchNormalize: true,
				liveSearchPlaceholder: "<?= TRANS('BT_SEARCH', '', 1); ?>",
				noneResultsText: "<?= TRANS('NO_RECORDS_FOUND', '', 1); ?> {0}",
				style: "",
				styleBase: "form-control ",
			});

            $('.sel-multi').selectpicker({
                /* placeholder */
                title: "<?= TRANS('AUX_WORKERS', '', 1); ?>",
                liveSearch: true,
                liveSearchNormalize: true,
                liveSearchPlaceholder: "<?= TRANS('BT_SEARCH', '', 1); ?>",
                noneResultsText: "<?= TRANS('NO_RECORDS_FOUND', '', 1); ?> {0}",
                maxOptions: 5,
                maxOptionsText: "<?= TRANS('TEXT_MAX_OPTIONS', '', 1); ?>",
                style: "",
                styleBase: "form-control input-select-multi",
            });

            $('#idDate_schedule').datetimepicker({
                timepicker: true,
                format: 'd/m/Y H:i',
				step: 30,
				minDate: 0,
                lazyInit: true
            });


            $('#loadCalendar').on('click', function(){
                showModalCalendar($('#main_worker').val());
            });

            // loadWorkers();
            
            $('#worker-calendar').on('change', function(){
                $('#worker-calendar').selectpicker('refresh');
                // showCalendar('idLoadCalendar', $('#worker-calendar').val());
                showCalendar('idLoadCalendar', {
                    worker_id: $('#worker-calendar').val(),
                    opened: false,
                    scheduled: true
                });
            });

            $('#time_to_execute').on('change', function(){
                if (!Number.isInteger(parseInt($('#time_to_execute').val())) || parseInt($('#time_to_execute').val()) < 1) {
                    $('#time_to_execute').val(1);
                }
            })


            controlAuxWorkersSelect();
            controlEmailOptions();
            $('#main_worker').on('change', function(){
                controlEmailOptions();
                controlAuxWorkersSelect();
            });


            /* when using a modal within a modal, add this class on the child modal */
            $(document).find('.child-modal').on('hidden.bs.modal', function () {
                // console.log('hiding child modal');
                $('body').addClass('modal-open');
            });


            $('#updateSchedule').on('click', function(e){
                e.preventDefault();
                getScheduleData($('#ticket').val());
            });

            $('#cancelUpdate').on('click', function(e){
                e.preventDefault();

                let baseUrl = "./ticket_show.php";
                let params = "?numero=" + $('#ticket').val();
                let url = baseUrl + params;
                $(location).prop('href', url);
				return true;
            });



		});

        /** 
         * Funções
         */

        function showModalCalendar(selected_worker) {
            loadWorkersToCalendar(selected_worker);
            $('#modalCalendar').modal();

            $('#modalCalendar').on('shown.bs.modal', function () {
                showCalendar('idLoadCalendar', {
                    worker_id: $('#worker-calendar').val(),
                    opened: false,
                    scheduled: true
                });
            });
        }


        function loadWorkers() {
			/* Exibir os usuários do tipo funcionário */
			if ($('#main_worker').length > 0) {

				var loading = $(".loading");
				$(document).ajaxStart(function() {
					loading.show();
				});
				$(document).ajaxStop(function() {
					loading.hide();
				});

				$.ajax({
					url: './get_workers_list.php',
					method: 'POST',
					dataType: 'json',
					data: {
						// main_work_setted: 3,
                        area: $('#area').val(),
					},
				}).done(function(response) {
					$('#main_worker').empty().append('<option value=""><?= TRANS('MAIN_WORKER'); ?></option>');
					for (var i in response) {
						var option = '<option value="' + response[i].user_id + '">' + response[i].nome + '</option>';
						$('#main_worker').append(option);
					}

					$('#main_worker').selectpicker('refresh');
                    
                    /* Traz selecionado o funcionário responsável */
                    if ($('#has_main_worker').val() != '') {
                        $('#main_worker').selectpicker('val', $('#has_main_worker').val());
                        $('#main_worker').prop('disabled', true).selectpicker('refresh');
                        // $('#main_worker').selectpicker('refresh');
                    }

                    $('#worker-calendar').selectpicker('refresh').selectpicker('val', $('#main_worker').val());
				});
			}
		}

        function loadAuxWorkers(selected) {
			/* Exibir os usuário do tipo funcionário que não são responsáveis pelo chamado */
			if ($('#aux_worker').length > 0) {

				var loading = $(".loading");
				$(document).ajaxStart(function() {
					loading.show();
				});
				$(document).ajaxStop(function() {
					loading.hide();
				});

				$.ajax({
					url: './get_workers_list.php',
					method: 'POST',
					dataType: 'json',
					data: {
						main_work_setted: $('#main_worker').val(),
                        area: $('#area').val(),
                        // ticket: $('#ticket').val(),
					},
				}).done(function(response) {
					
                    // console.log('Funcionarios aux: ' + response.aux_workers);
                    
                    $('#aux_worker').empty().append('<option value=""><?= TRANS('AUX_WORKERS'); ?></option>');
					for (var i in response) {
						var option = '<option value="' + response[i].user_id + '">' + response[i].nome + '</option>';
						$('#aux_worker').append(option);
					}
					$('#aux_worker').selectpicker('refresh');
                        
                    /* Seleciona os funcionarios */
                    if (selected != '') {
                        $('#aux_worker').selectpicker('val', selected);
                    }
				});
			}
		}


        function loadWorkersToCalendar(selected_worker) {
			/* Exibir os usuários do tipo funcionário */

            if (selected_worker == '') {
                selected_worker = $('#main_worker').val();
            }

			if ($('#idLoadCalendar').length > 0) {

				var loading = $(".loading");
				$(document).ajaxStart(function() {
					loading.show();
				});
				$(document).ajaxStop(function() {
					loading.hide();
				});

				$.ajax({
					url: './get_workers_list.php',
					method: 'POST',
					dataType: 'json',
					data: {
                        area: $('#area').val()
						// main_work_setted: 3,
					},
				}).done(function(response) {
					$('#worker-calendar').empty().append('<option value=""><?= TRANS('ALL'); ?></option>');
                    $('#worker-calendar').append('<option data-divider="true"></option>');
					var select = '';
                    for (var i in response) {
						
                        // var option = '<option style=" color: ' + response[i].bgcolor + ';" value="' + response[i].user_id + '">' + response[i].nome + '</option>';
                        var option = '<option data-content="<span class=\'badge px-2\' style=\'color: ' + response[i].bgcolor + '; background-color: ' + response[i].bgcolor + ' \'>0</span> ' + response[i].nome + '" value="' + response[i].user_id + '">' + response[i].user_id + '</option>';
                        
                        $('#worker-calendar').append(option);
					}

                    $('#worker-calendar').selectpicker('refresh').selectpicker('val', selected_worker);
				});
			}
		}


        function controlAuxWorkersSelect() {
            let main_worker = $('#main_worker').val();
            let auxWorkersSelected = JSON.parse('<?= $selectAuxWorkersJs; ?>');
            
            if (main_worker != '') {
                $('#aux_worker').prop('disabled', false);
                loadAuxWorkers(auxWorkersSelected);
            } else {
                $('#aux_worker').prop('disabled', true);
                $('#aux_worker').selectpicker('refresh').selectpicker('val', auxWorkersSelected);
            }
        }

        function controlEmailOptions(){
            if ($('#has_main_worker').val() != '' || $('#main_worker').val() != '') {
                $('#mailWorkers').prop('disabled', false);
            } else {
                $('#mailWorkers').prop('checked', false).prop('disabled', true);
            }
        }


        function scheduleTicket(id) {
            $('#modalSchedule').modal();
            $('#j_param_id').html(id);

            $('#confirmSchedule').html('<a class="btn btn-primary" onclick="getScheduleData(' + id + ')"><?= TRANS('TO_SCHEDULE'); ?></a>');
        }



        function getScheduleData(numero) {
            if ($('#idMailToArea').length > 0) {
                var sendEmailToArea = ($('#idMailToArea').is(':checked') ? true : false);
            } else {
                var sendEmailToArea = false;
            }

            if ($('#idMailToUser').length > 0) {
                var sendEmailToUser = ($('#idMailToUser').is(':checked') ? true : false);
            } else {
                var sendEmailToUser = false;
            }

            if ($('#mailWorkers').length > 0) {
                var sendEmailToWorkers = ($('#mailWorkers').is(':checked') ? true : false);
            } else {
                var sendEmailToWorkers = false;
            }

            var loading = $(".loading");
            $(document).ajaxStart(function() {
                loading.show();
            });
            $(document).ajaxStop(function() {
                loading.hide();
            });

            $.ajax({
                url: 'schedule_ticket.php',
                method: 'POST',
                dataType: 'json',
                data: {
                    'isUpdate': true,
                    'numero': numero,
                    'scheduleDate': $('#idDate_schedule').val(),
                    'entry_schedule': $('#entry_schedule').val(),
                    'main_worker': $('#main_worker').val(),
                    'aux_worker': $('#aux_worker').val(),
                    'sendEmailToArea': sendEmailToArea,
                    'sendEmailToUser': sendEmailToUser,
                    'sendEmailToWorkers': sendEmailToWorkers
                },
            }).done(function(response) {
                if (!response.success) {
                    if (response.field_id != "") {
                        $('#' + response.field_id).focus().addClass('is-invalid');
                    }
                    $('#divResultSchedule').html(response.message);
                } else {
                    $('#modalSchedule').modal('hide');

                    // location.reload();
                    let baseUrl = "./ticket_show.php";
                    let params = "?numero=" + $('#ticket').val();
                    let url = baseUrl + params;
                    $(location).prop('href', url);
                    return true;
                }
            });
            return false;
        }

        function goToTicketDetails() {
            let url = ($('#eventTicketUrl').val() ?? '');
            if (url != '') {
                window.open(url, '_blank','left=100,dependent=yes,width=900,height=600,scrollbars=yes,status=no,resizable=yes');
            }
        }



	</script>
</body>

</html>