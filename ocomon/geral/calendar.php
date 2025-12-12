<?php
    session_start();
    require_once __DIR__ . "/" . "../../includes/include_geral_new.inc.php";
    require_once __DIR__ . "/" . "../../includes/classes/ConnectPDO.php";

    use includes\classes\ConnectPDO;
    $conn = ConnectPDO::getInstance();

    $isWorker = $_SESSION['s_can_get_routed'] == 1;
    $isAdmin = $_SESSION['s_nivel'] == 1;
    // $user_id = (!$isAdmin ? $_SESSION['s_uid'] : '');
    
    $home = (isset($_GET['home']) && $_GET['home'] == 1 ? 1 : 0);
    $user_id = ($home ? $_SESSION['s_uid'] : '');
    // $inScheduleScreen = false;

    $areas_to_filter = [];
    if (isAreasIsolated($conn) && $_SESSION['s_nivel'] != 1) {
        /* Visibilidade isolada entre áreas para usuários não admin */
        $areas_to_filter = explode(",", $_SESSION['s_uareas']);
    }
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap/custom.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/fontawesome/css/all.min.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap-select/dist/css/bootstrap-select.min.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/css/my_bootstrap_select.css" />
    <link href="../../includes/components/fullcalendar/lib/main.css" rel="stylesheet" />
	<link rel="stylesheet" type="text/css" href="../../includes/css/my_fullcalendar.css" />
    

    <style>
        .pointer {
            cursor: pointer;
        }
    </style>
    
    
    
</head>

<body>
    
<div class="container-fluid">
    <h4 class="my-4"><i class="fas fa-calendar-alt text-secondary"></i>&nbsp;<?= TRANS('CALENDAR'); ?></h4>

    
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
                        <?= TRANS('CLIENT'); ?>:
                    </div>
                    <div class="form-group col-md-3 small" id="client"></div>
                    
                    <div class="form-group col-md-3 font-weight-bold text-right">
                        <?= TRANS('DONE_DATE'); ?>:
                    </div>
                    <div class="form-group col-md-3 small" id="doneDate"></div>
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
        // if ($_SESSION['s_can_route'] == 1 && !$home) {
            ?>
            
            <div class="form-group row my-0">
                <div class="form-group col-md-4 mt-0 mb-0">
                    <select class="form-control " id="client-calendar" name="client-calendar">
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
                    <small class="form-text text-muted"><?= TRANS('HELPER_CLIENT_FILTER'); ?></small>
                </div>

                <div class="form-group col-md-4 mt-0 mb-0">
                    <select class="form-control " id="area-calendar" name="area-calendar">
                        <option value="" selected><?= TRANS('ALL'); ?></option>
                        <?php
                            $areas = getAreas($conn, 0, 1, 1, $areas_to_filter);
                            foreach ($areas as $area) {
                            ?>
                                <option value="<?= $area['sis_id']; ?>"><?= $area['sistema']; ?></option>
                            <?php
                            }
                        ?>
                    </select>
                    <small class="form-text text-muted"><?= TRANS('HELPER_AREA_FILTER'); ?></small>
                </div>
                
                <?php
                    // if ($inScheduleScreen) {
                        ?>
                            <div class="form-group col-md-4 mt-0 mb-0">
                                <select class="form-control " id="worker-calendar" name="worker-calendar">
                                </select>
                                <small class="form-text text-muted"><?= TRANS('HELPER_OPERATOR_FILTER'); ?></small>
                            </div>
                        <?php
                    // }
                ?>

            </div>

            <div class="form-group col-md-12 mt-4 mb-0">
                <div class="form-check form-check-inline">
                    <input class="form-check-input " type="checkbox" name="opened_in_month" value="ok" id="opened_in_month" checked>
                    <span class="badge badge-opened px-2">0</span>&nbsp;<legend class="col-form-label col-form-label-sm"><?= TRANS('OPENED_IN_MONTH'); ?></legend>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input " type="checkbox" name="closed_in_month" value="ok" id="closed_in_month" >
                    <span class="badge badge-closed px-2">0</span>&nbsp;<legend class="col-form-label col-form-label-sm"><?= TRANS('CLOSED_IN_MONTH'); ?></legend>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input " type="checkbox" name="scheduled_to_month" value="ok" id="scheduled_to_month" checked>
                    <span class="badge badge-scheduled px-2">0</span>&nbsp;<legend class="col-form-label col-form-label-sm"><?= TRANS('SCHEDULED_TO_MONTH'); ?></legend>
                </div>
			</div>
            

            <input type="hidden" name="is_worker" id="is_worker" value="<?= $isWorker; ?>"/>
            <input type="hidden" name="user_id" id="user_id" value="<?= $user_id; ?>"/>
            <input type="hidden" name="is_admin" id="is_admin" value="<?= $isAdmin; ?>"/>
            <input type="hidden" name="opened-colors" class="event-opened" id="opened-colors">
            <input type="hidden" name="closed-colors" class="event-closed" id="closed-colors">
            <input type="hidden" name="scheduled-colors" class="event-scheduled" id="scheduled-colors">
            <?php
        // }
    ?>
    <div id="calendar" class="calendar"></div>

</div>
    <script src="../../includes/components/jquery/jquery.js"></script>
    <script src="../../includes/components/bootstrap/js/bootstrap.bundle.js"></script>
    <script src="../../includes/components/bootstrap-select/dist/js/bootstrap-select.min.js"></script>
    <script src="../../includes/components/fullcalendar/lib/main.js"></script>
    <script src="../../includes/components/fullcalendar/lib/locales/pt-br.js"></script>
    <script src="./tickets_calendar.js"></script>
    <script>
        $(function(){

            let params = {};

            $.fn.selectpicker.Constructor.BootstrapVersion = '4';
            $('#worker-calendar, #area-calendar, #client-calendar').selectpicker({
				/* placeholder */
				title: "<?= TRANS('ALL', '', 1); ?>",
				liveSearch: true,
				liveSearchNormalize: true,
				liveSearchPlaceholder: "<?= TRANS('BT_SEARCH', '', 1); ?>",
				noneResultsText: "<?= TRANS('NO_RECORDS_FOUND', '', 1); ?> {0}",
				style: "",
				styleBase: "form-control ",
			});

            // loadWorkers($('#user_id').val());
            loadOperators();

            let user_id = (!isNaN(parseInt($('#user_id').val())) ?  parseInt($('#user_id').val()) : '');
            let isWorker = (!isNaN(parseInt($('#is_worker').val())) ?  parseInt($('#is_worker').val()) : '');
            
            let area = $('#area-calendar').val();
            params.area = area;

            let client = $('#client-calendar').val();
            params.client = client;

            var opened = ($('#opened_in_month').is(':checked') ? true : false);
            var closed = ($('#closed_in_month').is(':checked') ? true : false);
            var scheduled = ($('#scheduled_to_month').is(':checked') ? true : false);

            // params.worker_id = '';
            // if (isWorker) {
            //     params.worker_id = user_id;
            // }
            params.worker_id = $('#worker-calendar').val();
            // params.worker_id = $('#user_id').val();
            params.opened = opened;
            params.closed = closed;
            params.scheduled = scheduled;


            if ($('#client-calendar').length > 0){
                $('#client-calendar').on('change', function () {
                    $('#client-calendar').selectpicker('refresh');
                    client = $('#client-calendar').val();
                    params.client = client;
                    showCalendar('calendar', params);
                });
            }

            if ($('#area-calendar').length > 0){
                $('#area-calendar').on('change', function () {
                    
                    loadOperators($('#area-calendar').val());
                    
                    $('#area-calendar').selectpicker('refresh');
                    $('#worker-calendar').selectpicker('refresh');
                    area = $('#area-calendar').val();
                    params.area = area;
                    user_id = $('#worker-calendar').val();
                    params.worker_id = user_id;

                    showCalendar('calendar', params);
                });
            }


            if ($('#opened_in_month').length > 0) {
                $('#opened_in_month').on('click', function(){
                    opened = ($('#opened_in_month').is(':checked') ? true : false);
                    params.opened = opened;

                    console.log('client: ' + client);
                    console.log('params.client: ' + params.client);
                    showCalendar('calendar', params);
                })
            } else {
                opened = false;
            }

            if ($('#closed_in_month').length > 0) {
                $('#closed_in_month').on('click', function(){
                    closed = ($('#closed_in_month').is(':checked') ? true : false);
                    params.closed = closed;
                    showCalendar('calendar', params);
                })
            } else {
                closed = false;
            }

            if ($('#scheduled_to_month').length > 0) {
                $('#scheduled_to_month').on('click', function(){
                    scheduled = ($('#scheduled_to_month').is(':checked') ? true : false);
                    params.scheduled = scheduled;
                    showCalendar('calendar', params);
                })
            } else {
                scheduled = false;
            }

            $('#worker-calendar').on('change', function(){
                $('#worker-calendar').selectpicker('refresh');
                // showCalendar('calendar', {worker_id: $('#worker-calendar').val()});
                params.worker_id = $('#worker-calendar').val();
                showCalendar('calendar', params);
                
            });

            showCalendar('calendar', params);
            $(window).resize(function() {
                showCalendar('calendar', params);
            });
            
            
        });
        
        
        function goToTicketDetails() {
            let url = ($('#eventTicketUrl').val() ?? '');
            if (url != '') {
                window.open(url, '_blank','left=100,dependent=yes,width=900,height=600,scrollbars=yes,status=no,resizable=yes');
            }
        }

        function loadWorkers(user_id = '') {
			/* Exibir os usuários do tipo funcionário */
			if ($('#worker-calendar').length > 0) {

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
                        fromMenu : 1,
						// main_work_setted: 3,
					},
				}).done(function(response) {
					$('#worker-calendar').empty().append('<option value=""><?= TRANS('ALL'); ?></option>');
					$('#worker-calendar').append('<option data-divider="true"></option>');

					for (var i in response) {
                        var option = '<option data-content="<span class=\'badge px-2\' style=\'color: ' + response[i].bgcolor + '; background-color: ' + response[i].bgcolor + ' \'>0</span> ' + response[i].nome + '" value="' + response[i].user_id + '">' + response[i].user_id + '</option>';

                        $('#worker-calendar').append(option);

                        if (user_id != '') {
							$('#worker-calendar').selectpicker('refresh').selectpicker('val', user_id);
                        }
					}
					$('#worker-calendar').selectpicker('refresh');
				});
			}
		}

        function loadOperators(area_id = '', user_id = $('#worker-calendar').val()) { /* user_id = $('#worker-calendar').val() */
			/* Exibir os operadores */
			if ($('#worker-calendar').length > 0) {

				var loading = $(".loading");
				$(document).ajaxStart(function() {
					loading.show();
				});
				$(document).ajaxStop(function() {
					loading.hide();
				});

				$.ajax({
					url: './get_operators_by_my_areas.php',
					method: 'POST',
					dataType: 'json',
					data: {
                        area : area_id,
						// main_work_setted: 3,
					},
				}).done(function(response) {
					$('#worker-calendar').empty().append('<option value=""><?= TRANS('ALL'); ?></option>');
					$('#worker-calendar').append('<option data-divider="true"></option>');

					for (var i in response) {
                        var option = '<option data-content="<span class=\'badge px-2\' style=\'color: ' + response[i].bgcolor + '; background-color: ' + response[i].bgcolor + ' \'>0</span> ' + response[i].nome + '" value="' + response[i].user_id + '">' + response[i].user_id + '</option>';

                        $('#worker-calendar').append(option);

                        if (user_id == response[i].user_id) {
							$('#worker-calendar').selectpicker('refresh').selectpicker('val', user_id);
                        }
                        
					}
					$('#worker-calendar').selectpicker('refresh');
				});
			}
		}


        
    </script>

</body>

</html>