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

$areaAdmin = 0;
$areaId = (isset($_GET['area']) && !empty($_GET['area']) ? $_GET['area'] : '');

$action = '';
$allowedActions = ['edit', 'delete'];
if (isset($_GET['action']) && in_array($_GET['action'], $allowedActions)) {
    $action = $_GET['action'];
}

if (empty($areaId)) {
    echo message('danger', 'Ooops', TRANS('MSG_ERR_NOT_EXECUTE'), '', '', true);
    exit;
}

$areaInfo = getAreaInfo($conn, $areaId);


$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 1);

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../../includes/css/estilos.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/css/my_datatables.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap/custom.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/fontawesome/css/all.min.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/datatables/datatables.min.css" />

    <title>OcoMon&nbsp;<?= VERSAO; ?></title>

    <style>
        .issue-shown:before {
            font-family: "Font Awesome\ 5 Free";
            /* content: "\f146"; */
            content: "\f06e";
            font-weight: 900;
            font-size: 16px;
        }

        .issue-hidden:before {
            font-family: "Font Awesome\ 5 Free";
            /* content: "\f0fe"; */
            content: "\f070";
            font-weight: 900;
            font-size: 16px;
        }

        .area-ban:before {
            font-family: "Font Awesome\ 5 Free";
            content: "\f05e";
            font-weight: 900;
            font-size: 16px;
        }

        .help-tip {
			cursor: help;
		}

       
    </style>


</head>

<body>


    <div class="container">
        <div id="idLoad" class="loading" style="display:none"></div>
    </div>

    <div id="divResult"></div>


    <div class="container-fluid">
        <h4 class="my-4 help-tip" title="<?= TRANS('PROBLEM_TYPES_PER_AREA'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('TEXT_ISSUES_BY_AREA'); ?>"><i class="fas fa-exclamation-circle text-secondary"></i>&nbsp;<?= TRANS('PROBLEM_TYPES_PER_AREA'); ?></h4>
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

        $config = getConfig($conn);

        $query = "SELECT * FROM problemas as p 
                    LEFT JOIN sistemas as s on p.prob_area = s.sis_id 
                    LEFT JOIN sla_solucao as sl on sl.slas_cod = p.prob_sla 
                    LEFT JOIN prob_tipo_1 as pt1 on pt1.probt1_cod = p.prob_tipo_1 
                    LEFT JOIN prob_tipo_2 as pt2 on pt2.probt2_cod = p.prob_tipo_2 
                    LEFT JOIN prob_tipo_3 as pt3 on pt3.probt3_cod = p.prob_tipo_3 
                WHERE 
                    (p.prob_area = '{$areaId}' OR p.prob_area IS NULL OR p.prob_area = '-1')
                    ";

        $COD = (isset($_GET['cod']) && !empty($_GET['cod']) ? noHtml($_GET['cod']) : '');
        if (!empty($COD)) {
            $query .= " AND p.prob_id = '{$COD}' ";
        }

        $query .= " ORDER BY s.sistema, p.problema";
        $resultado = $conn->query($query);
        $registros = $resultado->rowCount();

        if ((!isset($_GET['action'])) && !isset($_POST['submit'])) {

        ?>
            <!-- Modais -->
            <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-light">
                            <h5 class="modal-title" id="exampleModalLabel"><i class="fas fa-exclamation-triangle text-secondary"></i>&nbsp;<?= TRANS('REMOVE'); ?></h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <?= TRANS('CONFIRM_REMOVE'); ?> <span class="j_param_id"></span>?
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= TRANS('BT_CANCEL'); ?></button>
                            <button type="button" id="deleteButton" class="btn"><?= TRANS('BT_OK'); ?></button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="addExceptionModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-light">
                            <h5 class="modal-title" id="labelAddExceptionModal"><i class="fas fa-exclamation-triangle text-secondary"></i>&nbsp;<?= TRANS('HIDE_TYPE_OF_ISSUE'); ?></h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <?= TRANS('CONFIRM_HIDE_TYPE_OF_ISSUE'); ?>?
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= TRANS('BT_CANCEL'); ?></button>
                            <button type="button" id="addExceptionButton" class="btn"><?= TRANS('BT_OK'); ?></button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="removeExceptionModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-light">
                            <h5 class="modal-title" id="labelRemoveExceptionModal"><i class="fas fa-exclamation-triangle text-secondary"></i>&nbsp;<?= TRANS('SHOW_TYPE_OF_ISSUE'); ?></h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <?= TRANS('CONFIRM_SHOW_TYPE_OF_ISSUE'); ?>?
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= TRANS('BT_CANCEL'); ?></button>
                            <button type="button" id="removeExceptionButton" class="btn "><?= TRANS('BT_OK'); ?></button>
                        </div>
                    </div>
                </div>
            </div>

            <?php
            // TRANS('TEXT_ISSUES_BY_AREA') . '<hr>' . 
            echo message('info', '', TRANS('SELECTED_AREA') . ': <b>' . $areaInfo['area_name'] . '</b>', '', '', true, 'fas fa-arrow-right');
            ?>
            <button class="btn btn-sm btn-primary" id="idBtIncluir" name="new"><?= TRANS("ACT_NEW"); ?></button><br /><br />
            <?= TRANS('MANAGE_RELATED_ITENS'); ?>:&nbsp;<button class="btn btn-sm btn-secondary manage" data-location="cat_prob1" name="probtp1"><?= $config['conf_prob_tipo_1']; ?></button>
            <button class="btn btn-sm btn-secondary manage" data-location="cat_prob2" name="probtp2"><?= $config['conf_prob_tipo_2']; ?></button>
            <button class="btn btn-sm btn-secondary manage" data-location="cat_prob3" name="probtp3"><?= $config['conf_prob_tipo_3']; ?></button>
            <br /><br />
            <?php
            if ($registros == 0) {
                echo message('info', '', TRANS('NO_RECORDS_FOUND'), '', '', true);
            } else {

            ?>
                <table id="table_lists" class="stripe hover order-column row-border" border="0" cellspacing="0" width="100%">

                    <thead>
                        <tr class="header">
                            <td class="line issue_type"><?= TRANS('ISSUE_TYPE'); ?></td>
                            <td class="line description" width="20%"><?= TRANS('DESCRIPTION'); ?></td>
                            <td class="line area"><?= TRANS('AREA'); ?></td>
                            <td class="line sla"><?= TRANS('COL_SLA'); ?></td>
                            <td class="line tipo_1"><?= $config['conf_prob_tipo_1']; ?></td>
                            <td class="line tipo_2"><?= $config['conf_prob_tipo_2']; ?></td>
                            <td class="line tipo_3"><?= $config['conf_prob_tipo_3']; ?></td>
                            <td class="line prob_active"><?= TRANS('ACTIVE_O'); ?></td>
                            <td class="line editar"><?= TRANS('VISIBILITY'); ?></td>
                            <td class="line editar"><?= TRANS('BT_EDIT'); ?></td>
                            <td class="line remover"><?= TRANS('BT_REMOVE'); ?></td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php

                        foreach ($resultado->fetchall() as $row) {

                            
                            // $tr_class = '';
                            $active = ($row['prob_active'] ? '<span class="text-success"><i class="fas fa-check"></i></span>' : '');
                            $tr_class = (empty($active) ? 'table-danger': '');

                            /* Exceções para áreas de atendimento */
                            $exception_areas = [];
                            $button_class = 'text-secondary area-ban';
                            
                            $hint = '';
                            $exception_function = '';
                            $disable = ' disabled';


                            if (empty($row['sistema']) || $row['sistema'] == '-1') {
                                
                                /* Permite alteração de visibilidade apenas para tipos de problemas ativos no sistema */
                                if (!empty($active)) {
                                    $disable = '';
                                    $button_class = 'text-primary issue-shown';
                                    $hint = TRANS('DONT_SHOW_TO_THIS_AREA');
                                    $exception_function = 'confirmAddExceptionModal';

                                    if (!empty($row['prob_not_area'])) {
                                        $exception_areas = explode(',', $row['prob_not_area']);

                                        if (in_array($areaId, $exception_areas)) {
                                            $button_class = 'text-danger issue-hidden';
                                            $hint = TRANS('SHOW_AGAIN_TO_THIS_AREA');
                                            $exception_function = 'confirmRemoveExceptionModal';
                                            $tr_class = 'table-danger';
                                        }
                                    }
                                }
                                
                            }

                        ?>
                            <tr class='<?= $tr_class; ?>'>
                                <td class="line"><?= $row['problema']; ?></td>
                                <td class="line"><?= $row['prob_descricao']; ?></td>
                                <td class="line"><?= (!empty($row['sistema']) ? $row['sistema'] : TRANS('ALL')); ?></td>
                                <td class="line"><?= ($row['slas_desc'] == '' ? TRANS('MSG_NOT_DEFINED') : $row['slas_desc']); ?></td>
                                <td class="line"><?= ($row['probt1_desc'] == '' ? '<span class="text-danger"><i class="fas fa-ban"></i></span>' : $row['probt1_desc']); ?></td>
                                <td class="line"><?= ($row['probt2_desc'] == '' ? '<span class="text-danger"><i class="fas fa-ban"></i></span>' : $row['probt2_desc']); ?></td>
                                <td class="line"><?= ($row['probt3_desc'] == '' ? '<span class="text-danger"><i class="fas fa-ban"></i></span>' : $row['probt3_desc']); ?></td>
                                <td class="line"><?= $active; ?></td>
                                <td class="line"><button type="button" class="btn btn-sm <?= $button_class; ?>"  data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= $hint; ?>" onclick="<?= $exception_function; ?>('<?= $areaId; ?>', '<?= $row['prob_id']; ?>')" <?= $disable; ?>></button></td>
                                <td class="line"><button type="button" class="btn btn-secondary btn-sm" onclick="redirect('types_of_issues.php?action=edit&cod=<?= $row['prob_id']; ?>')"><?= TRANS('BT_EDIT'); ?></button></td>
                                <td class="line"><button type="button" class="btn btn-danger btn-sm" onclick="confirmDeleteModal('<?= $row['prob_id']; ?>', <?= $areaId; ?>)"><?= TRANS('REMOVE'); ?></button></td>
                            </tr>

                        <?php
                        }
                        ?>
                    </tbody>
                </table>
            <?php
            }
        }
        ?>
    </div>

    <script src="../../includes/javascript/funcoes-3.0.js"></script>
    <script src="../../includes/components/jquery/jquery.js"></script>
    <!-- <script type="text/javascript" src="../../includes/components/jquery/jquery-ui-1.12.1/jquery-ui.js"></script> -->
    <script src="../../includes/components/bootstrap/js/bootstrap.bundle.js"></script>
    <script type="text/javascript" charset="utf8" src="../../includes/components/datatables/datatables.js"></script>
    <script type="text/javascript">
        $(function() {


            $(function() {
                $('[data-toggle="popover"]').popover({
                    html: true
                })
            });

            $('.popover-dismiss').popover({
                trigger: 'focus'
            });

            $('#table_lists').DataTable({
                paging: true,
                deferRender: true,
                columnDefs: [{
                    searchable: false,
                    orderable: false,
                    targets: ['editar', 'remover']
                }],
                "language": {
                    "url": "../../includes/components/datatables/datatables.pt-br.json"
                }
            });

            $('.manage').on('click', function() {
                loadInModal($(this).attr('data-location'));
            });


            loadCat1();
            loadCat2();
            loadCat3();

            $('.manage_categories').on('click', function() {
                loadInPopup($(this).attr('data-location'), $(this).attr('data-params'));
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

                $("#idSubmit").prop("disabled", true);
                $.ajax({
                    url: './issues_types_process.php',
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
                        var url = '<?= $_SERVER['PHP_SELF'] ?>';
                        $(location).prop('href', url);
                        return false;
                    }
                });
                return false;
            });

            $('#idBtIncluir').on("click", function() {
                $('#idLoad').css('display', 'block');
                var url = 'types_of_issues.php?action=new';
                $(location).prop('href', url);
            });

            $('#bt-cancel').on('click', function() {
                var url = '<?= $_SERVER['PHP_SELF'] ?>';
                $(location).prop('href', url);
            });
        });

        function bypassCsrf() {
            $('#bypassCsrf').val(1);
        }

        function loadCat1(selected_id = '') {
            $.ajax({
                url: './get_issue_categories.php',
                method: 'POST',
                data: {
                    cat_type: 1
                },
                dataType: 'json',
            }).done(function(response) {
                $('#tipo_1').empty().append('<option value=""><?= TRANS('SEL_TYPE'); ?></option>');
                for (var i in response) {

                    var option = '<option value="' + response[i].probt1_cod + '">' + response[i].probt1_desc + '</option>';
                    $('#tipo_1').append(option);

                    if (selected_id !== '') {
                        $('#tipo_1').val(selected_id).change();
                    } else
                    if ($('#cat1_selected').val() != '') {
                        $('#tipo_1').val($('#cat1_selected').val()).change();
                    }
                }
            });
        }

        function loadCat2(selected_id = '') {
            $.ajax({
                url: './get_issue_categories.php',
                method: 'POST',
                data: {
                    cat_type: 2
                },
                dataType: 'json',
            }).done(function(response) {
                $('#tipo_2').empty().append('<option value=""><?= TRANS('SEL_TYPE'); ?></option>');
                for (var i in response) {

                    var option = '<option value="' + response[i].probt2_cod + '">' + response[i].probt2_desc + '</option>';
                    $('#tipo_2').append(option);

                    if (selected_id !== '') {
                        $('#tipo_2').val(selected_id).change();
                    } else
                    if ($('#cat2_selected').val() != '') {
                        $('#tipo_2').val($('#cat2_selected').val()).change();
                    }
                }
            });
        }

        function loadCat3(selected_id = '') {
            $.ajax({
                url: './get_issue_categories.php',
                method: 'POST',
                data: {
                    cat_type: 3
                },
                dataType: 'json',
            }).done(function(response) {
                $('#tipo_3').empty().append('<option value=""><?= TRANS('SEL_TYPE'); ?></option>');
                for (var i in response) {

                    var option = '<option value="' + response[i].probt3_cod + '">' + response[i].probt3_desc + '</option>';
                    $('#tipo_3').append(option);

                    if (selected_id !== '') {
                        $('#tipo_3').val(selected_id).change();
                    } else
                    if ($('#cat3_selected').val() != '') {
                        $('#tipo_3').val($('#cat3_selected').val()).change();
                    }
                }
            });
        }


        function confirmAddExceptionModal(areaId, issueId) {
            $('#addExceptionModal').modal();
            $('#addExceptionButton').html('<a class="btn btn-danger" onclick="addException(' + areaId + ', ' + issueId + ')"><?= TRANS('BT_OK'); ?></a>');
        }

        function addException(areaId, issueId) {

            var loading = $(".loading");
            $(document).ajaxStart(function() {
                loading.show();
            });
            $(document).ajaxStop(function() {
                loading.hide();
            });

            $.ajax({
                url: './issues_by_area_process.php',
                method: 'POST',
                data: {
                    area: areaId,
                    issue: issueId,
                    action: 'add_exception'
                },
                dataType: 'json',
            }).done(function(response) {
                var url = '<?= $_SERVER['PHP_SELF']; ?>?area=' + areaId;
                $(location).prop('href', url);
                return false;
            });
            return false;
        }

        function confirmRemoveExceptionModal(areaId, issueId) {
            $('#removeExceptionModal').modal();
            $('#removeExceptionButton').html('<a class="btn btn-primary" onclick="removeException(' + areaId + ', ' + issueId + ')"><?= TRANS('BT_OK'); ?></a>');
        }

        function removeException(areaId, issueId) {

            var loading = $(".loading");
            $(document).ajaxStart(function() {
                loading.show();
            });
            $(document).ajaxStop(function() {
                loading.hide();
            });

            $.ajax({
                url: './issues_by_area_process.php',
                method: 'POST',
                data: {
                    area: areaId,
                    issue: issueId,
                    action: 'remove_exception'
                },
                dataType: 'json',
            }).done(function(response) {
                var url = '<?= $_SERVER['PHP_SELF']; ?>?area=' + areaId;
                $(location).prop('href', url);
                return false;
            });
            return false;
        }



        function confirmDeleteModal(id, areaId) {
            $('#deleteModal').modal();
            $('#deleteButton').html('<a class="btn btn-danger" onclick="deleteData(' + id + ', ' + areaId + ')"><?= TRANS('REMOVE'); ?></a>');
        }

        function deleteData(id, areaId) {

            var loading = $(".loading");
            $(document).ajaxStart(function() {
                loading.show();
            });
            $(document).ajaxStop(function() {
                loading.hide();
            });

            $.ajax({
                url: './issues_types_process.php',
                method: 'POST',
                data: {
                    cod: id,
                    action: 'delete'
                },
                dataType: 'json',
            }).done(function(response) {
                var url = '<?= $_SERVER['PHP_SELF'] ?>?area=' + areaId;
                $(location).prop('href', url);
                return false;
            });
            return false;
            // $('#deleteModal').modal('hide'); // now close modal
        }

        function loadInModal(pageBase) {
            let url = pageBase + '.php';
            $(location).prop('href', url);
            // $("#divDetails").load(url);
            // $('#modal').modal();
        }

        function loadInPopup(pageBase, params) {
            let url = pageBase + '.php?' + params;
            x = window.open(url, '', 'dependent=yes,width=800,scrollbars=yes,statusbar=no,resizable=yes');
            x.moveTo(window.parent.screenX + 100, window.parent.screenY + 100);
        }
    </script>
</body>

</html>