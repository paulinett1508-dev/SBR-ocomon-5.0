<?php session_start();
/*  Copyright 2023 Flávio Ribeiro

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
*/

if (!isset($_SESSION['s_logado']) || $_SESSION['s_logado'] == 0) {
    $_SESSION['session_expired'] = 1;
    echo "<script>top.window.location = '../../index.php'</script>";
    exit;
}

require_once __DIR__ . "/" . "../../includes/include_geral_new.inc.php";
require_once __DIR__ . "/" . "../../includes/classes/ConnectPDO.php";

use includes\classes\ConnectPDO;

$conn = ConnectPDO::getInstance();

$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 3, 1);

$_SESSION['s_page_home'] = $_SERVER['PHP_SELF'];
$areaAdmin = $_SESSION['s_area_admin'] == 1;

$imgsPath = "../../includes/imgs/";


?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../../includes/css/estilos.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/css/switch_radio.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/css/my_datatables.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap/custom.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/fontawesome/css/all.min.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/jquery/toast-bootstrap-notify/dist/css/notify.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/datatables/datatables.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/datatables/Responsive-2.2.5/css/responsive.dataTables-custom.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/css/util.css" />

    <title>OcoMon&nbsp;<?= VERSAO; ?></title>

    <style>


        .truncate {
            max-width: 100px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: pointer;
        }

        .table_lines {
            cursor: pointer;
        }
    </style>
</head>

<body>
    
    <div class="container">
        <div id="idLoad" class="loading" style="display:none"></div>
    </div>



    <div class="container-fluid">


        <div class="modal" tabindex="-1" id="modalDefault">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div id="divPageDetails" class="p-3"></div>
                </div>
            </div>
        </div>

        <?php
            if (isset($_SESSION['flash']) && !empty($_SESSION['flash'])) {
                echo $_SESSION['flash'];
                $_SESSION['flash'] = '';
            }
        
        
            $titleInactives = ($areaAdmin ? 'QUEUE_INACTIVE_OPEN_BY_MANAGED_AREAS' : 'QUEUE_INACTIVE_OPEN_BY_MY_AREA');
            $titleClosures = ($areaAdmin ? 'QUEUE_MY_MANAGED_AREAS_CLOSURES' : 'QUEUE_MY_AREA_CLOSURES');
            $titleActives = ($areaAdmin ? 'QUEUE_ACTIVE_OPEN_BY_MANAGED_AREAS' : 'QUEUE_ACTIVE_OPEN_BY_MY_AREA');
        
        ?>


        <div id="divMyInactiveTickets" class="mt-2">
            <div class="accordion" id="accordionMyInactiveTickets">
                <div class="card">
                    <div class="card-header bg-oc-teal" id="showMyInactiveTickets">
                        <button id="idBtnMyInactiveTickets" class="btn btn-block text-center text-white" type="button" data-toggle="collapse" data-target="#listagemMyInactive" aria-expanded="true" aria-controls="listagemMyInactive" onclick="this.blur();">
                            <h4><i class="fas fa-archive"></i>&nbsp;<?= TRANS($titleInactives); ?>&nbsp;<span id="idTotalInactive" class="badge badge-light"></span></h4>
                        </button>
                    </div>

                    <div id="listagemMyInactive" class="collapse " aria-labelledby="showMyInactiveTickets" data-parent="#accordionMyInactiveTickets">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12 ">
                                    <h5 class=""><?= TRANS('LASTS'); ?><span class="badge badge-light" id="idTotalMonthsInactives"></span><?= strtolower(TRANS('MONTHS')); ?></h5>
                                    <table id="table_my_inactive" class="table stripe hover order-column row-border" width="100%">
                                        <thead>
                                            <tr class="header">
                                                <td class='line'></td>
                                                <td class='line'><?= TRANS('NUMBER_ABBREVIATE'); ?> / <?= TRANS('AREA'); ?></td>
                                                <td class='line' style='max-width:15%'><?= TRANS('ISSUE_TYPE'); ?></td>
                                                <td class='line'><?= TRANS('REQUESTER'); ?></td>
                                                <td class='line truncate_flag truncate descricao description'><?= TRANS('DEPARTMENT'); ?> / <?= TRANS('DESCRIPTION'); ?></td>
                                                <td class='line'><?= TRANS('COL_STATUS'); ?></td>
                                                <td class='line'><?= TRANS('FILTERED_TIME'); ?></td>
                                                <td class='line'><?= TRANS('SERVICE_RATE'); ?></td>
                                                <td class='line'><?= TRANS('COL_SLA'); ?></td>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
        <?php
        if ($_SESSION['s_nivel'] < 3) {
            ?>
            <div id="divMyClosure">
                <div class="accordion" id="accordionMyClosure">
                    <div class="card">
                        <div class="card-header bg-success" id="showMyClosure"> <!-- #3D9970; -->
                            <button id="idBtnMyClosure" class="btn btn-block text-center text-white" type="button" data-toggle="collapse" data-target="#listagemMyClosure" aria-expanded="true" aria-controls="listagemMyClosure" onclick="this.blur();">
                                <h4><i class="fas fa-check"></i>&nbsp;<?= TRANS($titleClosures); ?>&nbsp;<span id="idTotalMyClosure" class="badge badge-light"></span></h4>
                            </button>
                        </div>

                        <div id="listagemMyClosure" class="collapse" aria-labelledby="showMyClosure" data-parent="#accordionMyClosure">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-12 ">
                                        <h5 class=""><?= TRANS('LASTS'); ?><span class="badge badge-light" id="idTotalMonthsClosure"></span><?= strtolower(TRANS('MONTHS')); ?></h5>
                                        <table id="table_my_closure" class="table stripe hover order-column row-border" width="100%">
                                            <thead>
                                                <tr class="header">
                                                    <td class='line'></td>
                                                    <td class='line'><?= TRANS('NUMBER_ABBREVIATE'); ?> / <?= TRANS('AREA'); ?></td>
                                                    <td class='line' style='max-width:15%'><?= TRANS('ISSUE_TYPE'); ?></td>
                                                    <td class='line'><?= TRANS('REQUESTER'); ?></td>
                                                    <td class='line truncate_flag truncate descricao description'><?= TRANS('DEPARTMENT'); ?> / <?= TRANS('DESCRIPTION'); ?></td>
                                                    <td class='line'><?= TRANS('COL_STATUS'); ?></td>
                                                    <td class='line'><?= TRANS('FILTERED_TIME'); ?></td>
                                                    <td class='line'><?= TRANS('SERVICE_RATE'); ?></td>
                                                    <td class='line'><?= TRANS('COL_SLA'); ?></td>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }
        ?>
        

        <?php
            if ($areaAdmin) {
        ?>
            <!-- Pendentes de validação / avaliação -->
            <div id="divNeedApproval">
                <!-- class="mt-2" -->
                <div class="accordion" id="accordionNeedApproval">
                    <div class="card">
                        <div class="card-header bg-oc-orange" id="showNeedApproval">
                            <!-- style="background-color: teal;" -->
                            <button id="idBtnNeedApproval" class="btn btn-block text-center text-white" type="button" data-toggle="collapse" data-target="#listNeedApproval" aria-expanded="true" aria-controls="listNeedApproval" onclick="this.blur();">
                                <h4><i class="fas fa-star-half-alt"></i>&nbsp;<?= TRANS('QUEUE_NEED_APPROVAL_BY_MY_AREAS'); ?>&nbsp;<span id="idTotalNeedApproval" class="badge badge-light"></span></h4>
                            </button>
                        </div>

                        <div id="listNeedApproval" class="collapse" aria-labelledby="showNeedApproval" data-parent="#accordionNeedApproval">
                            <div class="card-body" id="idCardNeedApproval">
                                <div class="row">
                                    <div class="col-12 ">
                                        <table id="table_need_approval" class="table stripe hover order-column row-border" width="100%">
                                            <thead>
                                                <tr class="header">
                                                    <td class='line'></td>
                                                    <td class='line'><?= TRANS('NUMBER_ABBREVIATE'); ?> / <?= TRANS('AREA'); ?></td>
                                                    <td class='line' style='max-width:15%'><?= TRANS('ISSUE_TYPE'); ?></td>
                                                    <td class='line'><?= TRANS('CONTACT'); ?> / <?= TRANS('COL_PHONE'); ?></td>
                                                    <td class='line truncate_flag truncate descricao description'><?= TRANS('DEPARTMENT'); ?> / <?= TRANS('DESCRIPTION'); ?></td>
                                                    <td class='line'><?= TRANS('COL_STATUS'); ?></td>
                                                    <td class='line'><?= TRANS('FILTERED_TIME'); ?></td>
                                                    <td class='line'><?= TRANS('DEADLINE_TO_APPROVE'); ?></td>
                                                    <td class='line'><?= TRANS('COL_SLA'); ?></td>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php
            }
        ?>
        

        <div id="divMyOpenTickets">
            <div class="accordion" id="accordionMyTickets">
                <div class="card">
                    <div class="card-header bg-oc-wine" id="showMyTickets">
                        <button id="idBtnMyTickets" class="btn btn-block text-center text-white" type="button" data-toggle="collapse" data-target="#listagemMyTickets" aria-expanded="true" aria-controls="listagemMyTickets" onclick="this.blur();">
                            <h4><i class="fas fa-list-alt"></i>&nbsp;<?= TRANS($titleActives); ?>&nbsp;<span id="idTotalMyTickets" class="badge badge-light"></span></h4>
                        </button>
                    </div>

                    <div id="listagemMyTickets" class="collapse show" aria-labelledby="showMyTickets" data-parent="#accordionMyTickets">
                        <div class="card-body" id="idCardMyTickets">
                            <div class="row">
                                <div class="col-12 ">
                                    <table id="table_my_tickets" class="table stripe hover order-column row-border" width="100%">
                                        <thead>
                                            <tr class="header">
                                                <td class='line'></td>
                                                <td class='line'><?= TRANS('NUMBER_ABBREVIATE'); ?> / <?= TRANS('AREA'); ?></td>
                                                <td class='line' style='max-width:15%'><?= TRANS('ISSUE_TYPE'); ?></td>
                                                <td class='line'><?= TRANS('REQUESTER'); ?></td>
                                                <td class='line truncate_flag truncate descricao description'><?= TRANS('DEPARTMENT'); ?> / <?= TRANS('DESCRIPTION'); ?></td>
                                                <td class='line'><?= TRANS('COL_STATUS'); ?></td>
                                                <td class='line'><?= TRANS('FILTERED_TIME'); ?></td>
                                                <td class='line'><?= TRANS('OCO_PRIORITY'); ?></td>
                                                <td class='line'><?= TRANS('COL_SLA'); ?></td>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="../../includes/javascript/funcoes-3.0.js"></script>
    <script src="../../includes/components/jquery/jquery.js"></script>
    <script type="text/javascript" charset="utf8" src="../../includes/components/datatables/datatables.js"></script>
    <script type="text/javascript" charset="utf8" src="../../includes/components/datatables/Responsive-2.2.5/js/dataTables.responsive.min.js"></script>
    <script type="text/javascript" charset="utf8" src="../../includes/components/datatables/Scroller-2.0.2/js/dataTables.scroller.js"></script>
    <script src="../../includes/components/bootstrap/js/popper.min.js"></script>
    <script src="../../includes/components/bootstrap/js/bootstrap.min.js"></script>
    <script src="../../includes/components/jquery/toast-bootstrap-notify/dist/js/notify.js"></script>
    <script>
        $(function() {

            $(function() {
                $('[data-toggle="popover"]').popover()
            });

            $('.popover-dismiss').popover({
                trigger: 'focus'
            });

            if ($('#table_my_tickets').length > 0) {
                var dataTableMyTickets = $('#table_my_tickets').DataTable({
                    "responsive": {
                        details: {
                            type: 'column',
                            renderer: function(api, rowIdx, columns) {
                                var data = $.map(columns, function(col, i) {
                                    return col.hidden ?
                                        '<tr data-dt-row="' + col.rowIndex + '" data-dt-column="' + col.columnIndex + '">' +
                                        '<td>' + col.title + ':' + '</td> ' +
                                        '<td>' + col.data + '</td>' +
                                        '</tr>' :
                                        '';
                                }).join('');
                                return data ? $('<table/>').append(data) : false;
                            }
                            // renderer: $.fn.dataTable.Responsive.renderer.tableAll()
                        }
                    },
                    columnDefs: [{
                            className: 'control',
                            orderable: false,
                            targets: 0
                        },
                        {
                            className: 'truncate truncate_flag descricao description',
                            targets: [
                                'descricao'
                            ],
                        },
                        {
                            className: 'table_lines',
                            targets: '_all'
                        },
                        {
                            targets: [6, 7, 8],
                            orderable: false
                        }
                    ],

                    order: [1, 'desc'],
                    "processing": true,
                    "serverSide": true,
                    "ajax": {
                        url: "get_my_area_open_tickets.php", // json datasource
                        type: "post", // method  , by default get
                        /* data: {
                            user_id: 1 
                        }, */

                        "dataSrc": function(json) { //aqui consigo trabalhar no response

                            if (json.recordsTotal == 0) {
                                $("#listagemMyTickets").collapse('hide');
                                $("#idBtnMyTickets").attr('data-toggle', '');
                            } else {
                                $("#idBtnMyTickets").attr('data-toggle', 'collapse');
                            }
                            $('#idTotalMyTickets').html(json.recordsTotal);
                            // You can also modify `json.data` if required
                            return json.data;

                        },

                        error: function() { // error handling
                            $(".users-grid-error").html("");
                            $("#users-grid").append('<tbody class="users-grid-error"><tr><th colspan="3">Informações indisponíveis no momento</th></tr></tbody>');
                            $("#users-grid_processing").css("display", "none");
                        }
                    },
                    // scrollY: 200,
                    deferRender: true,
                    // scroller: {
                    //     loadingIndicator: true
                    // },
                    "language": {
                        "url": "../../includes/components/datatables/datatables.pt-br.json"
                    }
                });
            }


        /* Redirecionamento para a tela de detalhes da ocorrencia */
            $('#table_my_tickets').on('click', 'td', function() {

                var idFull = dataTableMyTickets.row(this).id();
                var ticket = formatRowId(idFull, 'id_');
                var colIndex = $(this).index() + 1; /* coluna */

                //Quando for a primeira coluna (do responsivo) não há redirecionamento
                if (colIndex != 1) {
                    redirect('ticket_show.php?numero=' + ticket);
                }
            });

            /* Popover da descriçao do chamado */
            $('#table_my_tickets').on('mouseover', 'td', function() {

                if ($(this).hasClass('description')) {
                    /* Popover */
                    let content = dataTableMyTickets.cell(this).data();
                
                    $(this).attr('data-content', content);
                    $(this).popover({
                        html:true
                    });
                    $(this).popover('update');
                    $(this).popover('show');
                }
            });            

            if ($('#table_my_inactive').length > 0) {
                var dataTableMyInactive = $('#table_my_inactive').DataTable({
                    "responsive": {
                        details: {
                            type: 'column',
                            renderer: function(api, rowIdx, columns) {
                                var data = $.map(columns, function(col, i) {
                                    return col.hidden ?
                                        '<tr data-dt-row="' + col.rowIndex + '" data-dt-column="' + col.columnIndex + '">' +
                                        '<td>' + col.title + ':' + '</td> ' +
                                        '<td>' + col.data + '</td>' +
                                        '</tr>' :
                                        '';
                                }).join('');
                                return data ? $('<table/>').append(data) : false;
                            }
                            // renderer: $.fn.dataTable.Responsive.renderer.tableAll()
                        }
                    },
                    columnDefs: [{
                            className: 'control',
                            orderable: false,
                            targets: 0
                        },
                        {
                            className: 'truncate truncate_flag descricao description',
                            targets: [
                                'descricao'
                            ],
                        },
                        {
                            className: 'table_lines',
                            targets: '_all'
                        },
                        {
                            targets: [6, 7, 8],
                            orderable: false
                        }
                    ],

                    order: [1, 'desc'],
                    "processing": true,
                    "serverSide": true,
                    "ajax": {
                        url: "get_my_area_inactive_tickets.php", // json datasource
                        type: "post", // method  , by default get

                        "dataSrc": function(json) { //aqui consigo trabalhar no response

                            if (json.recordsTotal == 0) {
                                $("#listagemMyInactive").collapse('hide');
                                $("#idBtnMyInactiveTickets").attr('data-toggle', '');
                            } else {
                                $("#idBtnMyInactiveTickets").attr('data-toggle', 'collapse');
                            }
                            $('#idTotalInactive').html(json.recordsTotal);
                            $('#idTotalMonthsInactives').html(json.totalMonths);
                            // You can also modify `json.data` if required
                            return json.data;

                        },
                        error: function() { // error handling
                            $(".users-grid-error").html("");
                            $("#users-grid").append('<tbody class="users-grid-error"><tr><th colspan="3">Informações indisponíveis no momento</th></tr></tbody>');
                            $("#users-grid_processing").css("display", "none");
                        }
                    },
                    
                    "language": {
                        "url": "../../includes/components/datatables/datatables.pt-br.json"
                    }
                });
            }



            /* Redirecionamento para a tela de detalhes da ocorrencia */
            $('#table_my_inactive').on('click', 'td', function() {

                var idFull = dataTableMyInactive.row(this).id();
                var ticket = formatRowId(idFull, 'id_');
                var colIndex = $(this).index() + 1; /* coluna */

                //Quando for a primeira coluna (do responsivo) não há redirecionamento
                if (colIndex != 1) {
                    redirect('ticket_show.php?numero=' + ticket);
                }
            });

            /* Popover da descriçao do chamado */
            $('#table_my_inactive').on('mouseover', 'td', function() {

                if ($(this).hasClass('description')) {
                    /* Popover */
                    let content = dataTableMyInactive.cell(this).data();
                
                    $(this).attr('data-content', content);
                    $(this).popover({
                        html:true
                    });
                    $(this).popover('update');
                    $(this).popover('show');
                }
            });            


            /* ENCERRADOS PELO OPERADOR LOGADO */
            if ($('#table_my_closure').length > 0) {
                var dataTableMyClosure = $('#table_my_closure').DataTable({
                    "responsive": {
                        details: {
                            type: 'column',
                            renderer: function(api, rowIdx, columns) {
                                var data = $.map(columns, function(col, i) {
                                    return col.hidden ?
                                        '<tr data-dt-row="' + col.rowIndex + '" data-dt-column="' + col.columnIndex + '">' +
                                        '<td>' + col.title + ':' + '</td> ' +
                                        '<td>' + col.data + '</td>' +
                                        '</tr>' :
                                        '';
                                }).join('');
                                return data ? $('<table/>').append(data) : false;
                            }
                            // renderer: $.fn.dataTable.Responsive.renderer.tableAll()
                        }
                    },
                    columnDefs: [{
                            className: 'control',
                            orderable: false,
                            targets: 0
                        },
                        {
                            className: 'truncate truncate_flag descricao description',
                            targets: [
                                'descricao'
                            ],
                        },
                        {
                            className: 'table_lines',
                            targets: '_all'
                        },
                        {
                            targets: [6, 7, 8],
                            orderable: false
                        }
                    ],

                    order: [1, 'desc'],
                    "processing": true,
                    "serverSide": true,
                    "ajax": {
                        url: "get_my_area_closure_tickets.php", // json datasource
                        type: "post",

                        "dataSrc": function(json) { //aqui consigo trabalhar no response
                            if (json.recordsTotal == 0) {
                                $("#listagemMyClosure").collapse('hide');
                                $("#idBtnMyClosure").attr('data-toggle', '');
                            } else {
                                $("#idBtnMyClosure").attr('data-toggle', 'collapse');
                            }
                            $('#idTotalMyClosure').html(json.recordsTotal);
                            $('#idTotalMonthsClosure').html(json.totalMonths);
                            // You can also modify `json.data` if required
                            return json.data;
                        },
                        error: function() { // error handling
                            $(".users-grid-error").html("");
                            $("#users-grid").append('<tbody class="users-grid-error"><tr><th colspan="3">Informações indisponíveis no momento</th></tr></tbody>');
                            $("#users-grid_processing").css("display", "none");
                        }
                    },
                    // rowId: 'id',

                    // dom: "frtiS",
                    // scrollY: 200,
                    deferRender: true,
                    // scroller: {
                    //     loadingIndicator: true
                    // },
                    "language": {
                        "url": "../../includes/components/datatables/datatables.pt-br.json"
                    }
                });
            }
            


            /* $("#table_my_closure tbody").on("click", ".truncate_flag", function() {
                var index = $(this).index() + 1; //coluna
                $('table tr td:nth-child(' + index  + ')').toggleClass("truncate");
            }); */


            if ($('#table_my_closure').length > 0) {
                /* Redirecionamento para a tela de detalhes da ocorrencia */
                $('#table_my_closure').on('click', 'td', function() {

                    var idFull = dataTableMyClosure.row(this).id();
                    var ticket = formatRowId(idFull, 'id_');
                    var colIndex = $(this).index() + 1; /* coluna */

                    //Quando for a primeira coluna (do responsivo) não há redirecionamento
                    if (colIndex != 1) {
                        redirect('ticket_show.php?numero=' + ticket);
                    }
                });

                /* Popover da descriçao do chamado */
                $('#table_my_closure').on('mouseover', 'td', function() {

                    if ($(this).hasClass('description')) {
                        /* Popover */
                        let content = dataTableMyClosure.cell(this).data();
                    
                        $(this).attr('data-content', content);
                        $(this).popover({
                            html:true
                        });
                        $(this).popover('update');
                        $(this).popover('show');
                    }
                });            
            }
            



            /* Tabela de aguardando aprovação */
            if ($('#table_need_approval').length > 0) {
                var dataTableNeedApproval = $('#table_need_approval').DataTable({
                    "responsive": {
                        details: {
                            type: 'column',
                            renderer: function(api, rowIdx, columns) {
                                var data = $.map(columns, function(col, i) {
                                    return col.hidden ?
                                        '<tr data-dt-row="' + col.rowIndex + '" data-dt-column="' + col.columnIndex + '">' +
                                        '<td>' + col.title + ':' + '</td> ' +
                                        '<td>' + col.data + '</td>' +
                                        '</tr>' :
                                        '';
                                }).join('');
                                return data ? $('<table/>').append(data) : false;
                            }
                            // renderer: $.fn.dataTable.Responsive.renderer.tableAll()
                        }
                    },
                    columnDefs: [{
                            className: 'control',
                            orderable: false,
                            targets: 0
                        },
                        {
                            className: 'truncate truncate_flag descricao description',
                            targets: ['descricao'],
                            /* render: function ( targets, type, row ) {
                                return '$'+ targets;
                            } */
                            // render: $.fn.dataTable.render.text() //buidin helper
                        },
                        {
                            className: 'table_lines',
                            targets: '_all'
                        },
                        {
                            targets: [6, 7, 8],
                            orderable: false
                        }
                    ],

                    order: [1, 'desc'],
                    "processing": true,
                    "serverSide": true,
                    "ajax": {
                        url: "get_need_my_area_approval.php", // json datasource
                        type: "post", // method  , by default get
                        /* data: {
                            user_id: 1 
                        }, */

                        "dataSrc": function(json) { //aqui consigo trabalhar no response

                            if (json.recordsTotal == 0) {
                                $("#listNeedApproval").collapse('hide');
                                $("#idBtnNeedApproval").attr('data-toggle', '');
                            } else {
                                $("#idBtnNeedApproval").attr('data-toggle', 'collapse');
                            }

                            if (json.recordsTotal == 0) {
                                $('#divNeedApproval').hide();
                            } else {
                                $('#divNeedApproval').show();
                            }
                            $('#idTotalNeedApproval').html(json.recordsTotal);
                            // You can also modify `json.data` if required
                            return json.data;

                        },

                        error: function() { // error handling
                            $(".users-grid-error").html("");
                            $("#users-grid").append('<tbody class="users-grid-error"><tr><th colspan="3">Informações indisponíveis no momento</th></tr></tbody>');
                            $("#users-grid_processing").css("display", "none");
                        }
                    },
                    deferRender: true,
                    "language": {
                        "url": "../../includes/components/datatables/datatables.pt-br.json"
                    }
                });
            }  


            if ($('#table_need_approval').length > 0) {
                /* Redirecionamento para a tela de detalhes da ocorrencia */
                $('#table_need_approval').on('click', 'td', function() {

                    var idFull = dataTableNeedApproval.row(this).id();
                    var ticket = formatRowId(idFull, 'id_');
                    var colIndex = $(this).index() + 1; /* coluna */

                    //Quando for a primeira coluna (do responsivo) não há redirecionamento
                    if (colIndex != 1) {
                        redirect('ticket_show.php?numero=' + ticket);
                    }
                });

                /* Popover da descriçao do chamado */
                $('#table_need_approval').on('mouseover', 'td', function() {

                    if ($(this).hasClass('description')) {
                        /* Popover */
                        let content = dataTableNeedApproval.cell(this).data();
                    
                        $(this).attr('data-content', content);
                        $(this).popover({
                            html:true
                        });
                        $(this).popover('update');
                        $(this).popover('show');
                    }
                });            
            }



            /* Remoção dos popovers */
            $('#table_my_inactive,#table_my_closure,#table_my_tickets,#table_need_approval').on('mouseout', 'td', function() {
                $(this).popover('dispose');
                $('.popover').remove();
            });

            /* Popovers para os indicadores de interação com o chamado (primeira coluna) */
            $('#table_my_inactive,#table_my_closure,#table_my_tickets,#table_need_approval').on('mouseover', '.ticket-interaction', function() {

                let content = $(this).attr('data-content');
                
                $(this).attr('data-content', content);
                $(this).popover({
                    html:true
                });
                $(this).popover('update');
                $(this).popover('show');

            });            

            

            updateScheduled();
            getNotices();

            setInterval(function() {
                dataTableMyTickets.ajax.reload(null, false); // user paging is not reset on reload
                dataTableMyInactive.ajax.reload(null, false); // user paging is not reset on reload

                if ($('#table_my_closure').length > 0) {
                    dataTableMyClosure.ajax.reload(null, false); // user paging is not reset on reload
                }
                
                updateScheduled();
                getNotices();
                check_warranties();
            }, 60000); //a cada 1 minuto

        });


        function getNotices() {
            $.ajax({
                url: 'get_notices.php',
                method: 'POST',
                dataType: 'json',

            }).done(function(data) {
                
                let notice_ids = [];
                for (var i in data) {
                    let title = '<?= TRANS('NOTIFICATION_FROM_NOTICES_BOARD'); ?>';
                    notice_ids.push(data[i].aviso_id);
                    if ((data[i].title != null)) {
                        title = data[i].title;
                    }
                    notify(data[i].status, title, data[i].avisos + ' ' + data[i].formatted_date);
                }
                
                $.ajax({
                    url: 'notices_process.php',
                    method: 'POST',
                    dataType: 'json',
                    data: {'notice_ids' : notice_ids, 'action' : 'shown_notices'}
                }).done(function(data) {
                    console.log(data);
                });
                return false;

            }).fail(function() {
                // $('#divError').html('<p class="text-danger text-center"><?= TRANS('FETCH_ERROR'); ?></p>');
            });
            return false;
        }


        /* Roda a checagem de data para chamados agendados entrarem na fila geral de atendimento */
        function updateScheduled() {
            $.ajax({
                url: 'update_scheduled_tickets.php',
                method: 'POST',
                data: {
                    'numero': 1
                },
            });
            return false;
        }


        /* Roda a checagem de vencimentos das garantias de equipamentos */
        function check_warranties() {
            $.ajax({
                url: 'check_expiring_warranties.php',
                method: 'POST'
            });
            return false;
        }


        /* Ajusta o valor de ID das linhas para inteiro = numero do chamado */
        function formatRowId(fullId, prefix) {
            var id = fullId.split(prefix)[1];
            return parseInt(id);
        }

        function loadPageInModal(page) {
            $("#divPageDetails").load(page);
            $('#modalDefault').modal();
        }
    </script>
</body>

</html>