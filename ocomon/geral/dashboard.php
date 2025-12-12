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

$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 2, 1);

/* Variáveis de sessão para a atualização dos gráficos de acordo com o filtro */
$_SESSION['dash_filter_areas'] = "";
$_SESSION['dash_filter_clients'] = "";
$_SESSION['requester_areas'] = "";

$isAdmin = $_SESSION['s_nivel'] == 1;

$allAreasInfo = getAreas($conn, 0, 1, null);
$arrayAllAreas = [];
foreach ($allAreasInfo as $sigleArea) {
    $arrayAllAreas[] = $sigleArea['sis_id'];
}
$allAreas = implode(",", $arrayAllAreas);

$u_areas = ($isAdmin ? $allAreas : $_SESSION['s_uareas']);
$array_uareas = explode(",", $u_areas);



?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- <link rel="stylesheet" type="text/css" href="../../includes/css/estilos.css" /> -->
    <link rel="stylesheet" type="text/css" href="../../includes/css/loading.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/css/switch_radio.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap/custom.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/fontawesome/css/all.min.css" />
    <link rel="stylesheet" href="../../includes/components/jquery/dynamic-seo-tag-cloud/jquery.tagcloud.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap-select/dist/css/bootstrap-select.min.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/css/my_bootstrap_select.css" />

    <title>OcoMon&nbsp;<?= VERSAO; ?></title>

    <style>
        canvas {
            -moz-user-select: none;
            -webkit-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }

        .pointer {
            cursor: pointer;
        }

        .dropdown-header {
            cursor: pointer !important;
            background: teal !important;
            color: white !important;
        }

        .dash-form-filter {
            display: none;
            background-color: white;
        }

        .filter-handler {
            cursor: pointer;
        }

        .filter-handler:before {
            font-family: "Font Awesome\ 5 Free";
            content: "\f0b0";
            font-weight: 900;
            font-size: 16px;
        }

        .filter-handler-invert {
            -webkit-transform: rotate(180deg);
            transform: rotate(180deg);
            right: 15px;
        }

        .chart-container {
            position: relative;
            height: 100%;
            width: 100%;
            margin-left: 10px;
            margin-right: 10px;
        }

        .side-cards {
            max-width: calc(16.7%);
            height: 180%;
            float: right;
        }


        .icon-toogle {
            position: absolute;
            top: 0;
            right: 5px;
            z-index: 1;
            cursor: pointer;
        }

        .icon-show-graph {
            position: absolute;
            top: 0;
            left: 5px;
            z-index: 1;
            cursor: pointer;
        }

        .icon-expand:before {
            font-family: "Font Awesome\ 5 Free";
            /* content: "\f065"; */
            content: "\f30b";
            font-weight: 900;
            font-size: 16px;
        }

        .icon-collapse:before {
            font-family: "Font Awesome\ 5 Free";
            /* content: "\f066"; */
            content: "\f30a";
            font-weight: 900;
            font-size: 16px;
        }

        .icon-view-graph:before {
            font-family: "Font Awesome\ 5 Free";
            /* content: "\f065"; */
            content: "\f06e";
            font-weight: 900;
            font-size: 12px;
        }

        .icon-toogle-card:before {
            font-family: "Font Awesome\ 5 Free";
            content: "\f362";
            font-weight: 900;
            font-size: 12px;
        }

        .flex-container {
            display: flex;
            position: relative;
        }

        .flex-child {
            display: flex;
            max-width: calc(100%);
            flex: 1;
            position: relative;
        }

        .flex-cloudtag {
            display: flex;
            min-width: calc(100%);
            max-width: calc(100%);
            flex: 1;
            position: relative;
        }

        .cloud-container {
            flex: 1;
            min-width: calc(100%);
            max-width: calc(100%);
        }

        .flex-child-child {
            max-width: calc(100%/2);
            flex: 1;
            padding-right: 5px;
            padding-bottom: 5px;
            position: relative;
        }

        .flex-child-child-fullspace {
            max-width: calc(100%);
            flex: 1;
            padding-right: 5px;
            padding-bottom: 5px;
            position: relative;
        }


        .modal-1000 {
            max-width: 900px;
            margin: 30px auto;
        }


        @media only screen and (max-width: 768px) {

            .flex-container,
            .flex-child,
            .flex-child-child,
            .side-cards {
                display: block;
                max-width: 100%;
            }

            .icon-toogle {
                display: none;
            }
        }
    </style>
</head>

<body>

    <div class="container">
        <div id="idLoad" class="loading" style="display:none"></div>
    </div>

    <div class="container-fluid">

        <div class="dash-form-filter" id="dash-form-filter">
            <form method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form" onSubmit="return false;">


                <div class="form-group row my-4">
                    <h5 class="w-100 mt-2 ml-5 p-4"><i class="fas fa-filter text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('CONTENT_FILTER')); ?></h5>


                    <label for="client" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CLIENT'); ?></label>
                    <div class="form-group col-md-10">

                        <select class="form-control bs-select " id="client" name="client[]" multiple="multiple">
                            <?php
                            $clients = getClients($conn);
                            foreach ($clients as $client) {  
                                ?>
                                    <option value="<?= $client['id']; ?>" ><?= $client['nickname']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
                    </div>


                    <label for="area" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('AREA'); ?></label>
                    <div class="form-group col-md-10">

                        <select class="form-control sel2 " id="area" name="area[]" multiple="multiple">
                            <optgroup label="<?= TRANS('SERVICE_AREAS'); ?>" data-icon="fas fa-headset">
                            <?php
                            $areas = getAreas($conn, 0, 1, 1);
                            foreach ($areas as $rowArea) {  

                                if (in_array($rowArea['sis_id'], $array_uareas)) {
                                ?>
                                    <option value="<?= $rowArea['sis_id']; ?>" ><?= $rowArea['sistema']; ?></option>
                                <?php
                                }
                            }
                            ?>
                            </optgroup>
                            <optgroup label="<?= TRANS('REQUESTER_AREAS'); ?>" data-icon="fas fa-user">
                            <?php
                            $areas = getAreas($conn, 0, 1, 0);
                            foreach ($areas as $rowArea) {  

                                if (in_array($rowArea['sis_id'], $array_uareas)) {
                                ?>
                                    <option value="<?= $rowArea['sis_id']; ?>" ><?= $rowArea['sistema']; ?></option>
                                <?php
                                }
                            }
                            ?>
                            </optgroup>
                        </select>
                    </div>

                    <label class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('REQUESTER_AREAS'); ?></label>
                    <div class="form-group col-md-4 switch-field">
                        <?php
                        $yesChecked = "";
                        $noChecked = "checked"
                        ?>
                        <input type="radio" id="requester_areas" name="requester_areas" value="yes" <?= $yesChecked; ?> />
                        <label for="requester_areas"><?= TRANS('YES'); ?></label>
                        <input type="radio" id="requester_areas_no" name="requester_areas" value="no" <?= $noChecked; ?> />
                        <label for="requester_areas_no"><?= TRANS('NOT'); ?></label>
                    </div>


                    <label class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_RENDER_CUSTOM_FIELDS'); ?>"><?= TRANS('RENDER_CUSTOM_FIELDS'); ?></label>
                    <div class="form-group col-md-4 switch-field">
                        <?php
                        $yesChecked = "";
                        $noChecked = "checked"
                        ?>
                        <input type="radio" id="render_custom_fields" name="render_custom_fields" value="yes" <?= $yesChecked; ?> />
                        <label for="render_custom_fields"><?= TRANS('YES'); ?></label>
                        <input type="radio" id="render_custom_fields_no" name="render_custom_fields" value="no" <?= $noChecked; ?> />
                        <label for="render_custom_fields_no"><?= TRANS('NOT'); ?></label>
                    </div>

                    <div class="row w-100"></div>
                    <div class="form-group col-md-8 d-none d-md-block">
                    </div>

                    <input type="hidden" name="app_from" value="dashboard" id="app_from"/>
                    <div class="form-group col-12 col-md-2 ">
                        <button type="submit" id="idSearch" class="btn btn-primary btn-block"><?= TRANS('BT_FILTER'); ?></button>
                    </div>
                    <div class="form-group col-12 col-md-2">
                        <button type="reset" id="idReset" class="btn btn-secondary btn-block text-nowrap"><?= TRANS('COL_DEFAULT'); ?></button>
                    </div>
                </div>


                <!-- Cores para o gráfico de avaliações dos atendimentos -->
                <input type="hidden" name="color-great" class="color-great" id="color-great"/>
                <input type="hidden" name="color-good" class="color-good" id="color-good"/>
                <input type="hidden" name="color-regular" class="color-regular" id="color-regular"/>
                <input type="hidden" name="color-bad" class="color-bad" id="color-bad"/>
                <input type="hidden" name="color-not-rated" class="color-not-rated" id="color-not-rated"/>


            </form>
        </div>
        <div class='toogle-form-filter' id="toogle-form-filter">
            <!-- <button type="button" class="filter-handler btn btn-oc-teal btn-block"></button> -->
            <div class="filter-handler btn btn-oc-teal btn-block" id="filter-handler"></div>
        </div>

        <div class="modal" tabindex="-1" id="modalDefault">
            <!-- <div class="modal-dialog modal-xl"> -->
            <div class="modal-dialog modal-1000">
                <div class="modal-content">
                    <div id="divShowGraph" class="p-3">
                        <!-- <canvas id="canvasModal"></canvas> -->
                    </div>
                </div>
            </div>
        </div>


        <!-- Cards do topo -->
        <div id="top-cards" class="mt-2">

            <div class="row no-gutters">
                <div class="col-md-2">
                    <div class="card">
                        <div class="card-header bg-primary">
                            <small><span class="badge badge-warning mb-2"><?= TRANS('CARDS_TODAY'); ?></span></small>
                            <h6 class="text-center text-white text-nowrap"><i class="fas fa-plus-square"></i>&nbsp;<?= TRANS('CARDS_OPENED'); ?></h6>
                            <h5 class="text-center text-white"><span id="badgeAbertos" class="badge badge-light">0</span></h5>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card">
                        <div class="card-header bg-success">
                            <small><span class="badge badge-warning mb-2"><?= TRANS('CARDS_TODAY'); ?></span></small>
                            <h6 class="text-center text-white text-nowrap"><i class="fas fa-check"></i>&nbsp;<?= TRANS('CARDS_CLOSED'); ?></h6>
                            <h5 class="text-center text-white"><span id="badgeFechados" class="badge badge-light">0</span></h5>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card">
                        <div class="card-header bg-info">
                            <small><span class="badge badge-warning mb-2"><?= TRANS('CARDS_NOW'); ?></span></small>
                            <h6 class="text-center text-white text-nowrap"><i class="fas fa-user-check"></i>&nbsp;<?= TRANS('CARDS_IN_PROGRESS'); ?></h6>
                            <h5 class="text-center text-white"><span id="badgeEmProgresso" class="badge badge-light">0</span></h5>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card">
                        <div class="card-header bg-danger">
                            <small><span class="badge badge-warning mb-2"><?= TRANS('CARDS_NOW'); ?></span></small>
                            <h6 class="text-center text-white text-nowrap"><i class="fas fa-clock"></i>&nbsp;<?= TRANS('CARDS_WAITING_RESPONSE'); ?></h6>
                            <h5 class="text-center text-white"><span id="badgeAguardandoResposta" class="badge badge-light">0</span></h5>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card">
                        <div class="card-header bg-primary">
                            <small><span class="badge badge-warning mb-2"><?= TRANS('CARDS_IN_THIS_MONTH'); ?></span></small>
                            <h6 class="text-center text-white text-nowrap"><i class="fas fa-plus-square"></i>&nbsp;<?= TRANS('CARDS_OPENED'); ?></h6>
                            <h5 class="text-center text-white"><span id="badgeAbertosMes" class="badge badge-light">0</span></h5>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card">
                        <div class="card-header bg-success">
                            <small><span class="badge badge-warning mb-2"><?= TRANS('CARDS_IN_THIS_MONTH'); ?></span></small>
                            <h6 class="text-center text-white text-nowrap"><i class="fas fa-check"></i>&nbsp;<?= TRANS('CARDS_CLOSED'); ?></h6>
                            <h5 class="text-center text-white"><span id="badgeFechadosMes" class="badge badge-light">0</span></h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cards Laterais -->
        <div class="side-cards" id="side-cards">
            <div class="row">

                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-oc-wine">
                            <small><span class="badge badge-warning mb-2"><?= TRANS('CARDS_NOW'); ?></span></small>
                            <h6 class="text-center text-white text-nowrap"><i class="fas fa-list-ul"></i>&nbsp;<?= TRANS('CARDS_OPENED_QUEUE'); ?></h6>
                            <h5 class="text-center text-white"><span id="badgeFilaGeral" class="badge badge-light">0</span></h5>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-oc-teal">
                            <small><span class="badge badge-warning mb-2"><?= TRANS('CARDS_NOW'); ?></span></small>
                            <h6 class="text-center text-white text-nowrap"><i class="fas fa-calendar-alt"></i>&nbsp;<?= TRANS('QUEUE_SCHEDULED'); ?></h6>
                            <h5 class="text-center text-white"><span id="badgeAgendados" class="badge badge-light">0</span></h5>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-oc-orange">
                            <small><span class="badge badge-warning mb-2"><?= TRANS('CARDS_NOW'); ?></span></small>
                            <h6 class="text-center text-white text-nowrap"><i class="fas fa-star-half-alt"></i>&nbsp;<?= TRANS('WAITING_RATE'); ?></h6>
                            <h5 class="text-center text-white"><span id="badgeWaitingRate" class="badge badge-light">0</span></h5>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card flip frente">
                        <div class="card-header front bg-info">
                            <div class="icon-toogle" data-toggle="popover" data-trigger="hover" data-placement="left" title="<?= TRANS('CARDS_TURN'); ?>">
                                <span class="icon-toogle-card text-white toogle-sla-open"></span>
                            </div>
                            <small><span class="badge badge-warning mb-2"><?= TRANS('CARDS_NOW'); ?>: <?= TRANS('CARDS_NOT_CLOSED'); ?></span></small>
                            <h6 class="text-center text-white text-nowrap"><i class="fas fa-handshake"></i>&nbsp;<span class='sla-response' id="span-sla-open"><?= TRANS('CARDS_RESPONSE_SLA'); ?></span></h6>
                            <h5 class="text-center text-white"><span id="badgeResponseGreen" class="badge badge-light">0</span></h5>
                        </div>

                        <div class="card-header back bg-info">
                            <div class="icon-toogle" data-toggle="popover" data-trigger="hover" data-placement="left" title="<?= TRANS('CARDS_TURN'); ?>">
                                <span class="icon-toogle-card text-white toogle-sla-open"></span>
                            </div>
                            <small><span class="badge badge-warning mb-2"><?= TRANS('CARDS_NOW'); ?>: <?= TRANS('CARDS_NOT_CLOSED'); ?></span></small>
                            <h6 class="text-center text-white text-nowrap"><i class="fas fa-handshake"></i>&nbsp;<span class='sla-solution'><?= TRANS('CARDS_SOLUTION_SLA'); ?></span></h6>
                            <h5 class="text-center text-white"><span id="badgeSolutionGreen" class="badge badge-light">0</span></h5>
                        </div>
                    </div>
                </div>
                <div class="col-12 ">
                    <div class="card flip frente">

                        <div class="card-header bg-oc-wine front">
                            <div class="icon-toogle" data-toggle="popover" data-trigger="hover" data-placement="left" title="<?= TRANS('CARDS_TURN'); ?>">
                                <span class="icon-toogle-card text-white toogle-sla-open"></span>
                            </div>
                            <small><span class="badge badge-warning mb-2"><?= TRANS('CARDS_NOW'); ?>: <?= TRANS('CARDS_NOT_CLOSED'); ?></span></small>
                            <h6 class="text-center text-white text-nowrap"><i class="fas fa-clock"></i>&nbsp;<?= TRANS('CARDS_RESPONSE_AVG'); ?></h6>
                            <h5 class="text-center text-white"><span id="badgeAvgFilteredResponseTime" class="badge badge-light">0</span></h5>
                        </div>

                        <div class="card-header bg-oc-wine back">
                            <div class="icon-toogle" data-toggle="popover" data-trigger="hover" data-placement="left" title="<?= TRANS('CARDS_TURN'); ?>">
                                <span class="icon-toogle-card text-white toogle-sla-open"></span>
                            </div>
                            <small><span class="badge badge-warning mb-2"><?= TRANS('CARDS_NOW'); ?>: <?= TRANS('CARDS_NOT_CLOSED'); ?></span></small>
                            <h6 class="text-center text-white text-nowrap"><i class="fas fa-clock"></i>&nbsp;<?= TRANS('CARDS_RESPONSE_AVG'); ?></h6>
                            <h5 class="text-center text-white"><span id="badgeAvgAbsResponseTime" class="badge badge-light">0</span></h5>
                        </div>

                    </div>
                </div>

                <div class="col-12 ">
                    <div class="card flip frente">
                        <div class="card-header bg-oc-wine front">
                            <div class="icon-toogle" data-toggle="popover" data-trigger="hover" data-placement="left" title="<?= TRANS('CARDS_TURN'); ?>">
                                <span class="icon-toogle-card text-white toogle-sla-open"></span>
                            </div>
                            <small><span class="badge badge-warning mb-2"><?= TRANS('CARDS_NOW'); ?>: <?= TRANS('CARDS_NOT_CLOSED'); ?></span></small>
                            <h6 class="text-center text-white text-nowrap"><i class="fas fa-clock"></i>&nbsp;<?= TRANS('CARDS_LIFESPAN_AVG'); ?></h6>
                            <h5 class="text-center text-white"><span id="badgeAvgFilteredSolutionTime" class="badge badge-light">0</span></h5>
                        </div>
                        <div class="card-header bg-oc-wine back">
                            <div class="icon-toogle" data-toggle="popover" data-trigger="hover" data-placement="left" title="<?= TRANS('CARDS_TURN'); ?>">
                                <span class="icon-toogle-card text-white toogle-sla-open"></span>
                            </div>
                            <small><span class="badge badge-warning mb-2"><?= TRANS('CARDS_NOW'); ?>: <?= TRANS('CARDS_NOT_CLOSED'); ?></span></small>
                            <h6 class="text-center text-white text-nowrap"><i class="fas fa-clock"></i>&nbsp;<?= TRANS('CARDS_LIFESPAN_AVG'); ?></h6>
                            <h5 class="text-center text-white"><span id="badgeAvgAbsSolutionTime" class="badge badge-light">0</span></h5>
                        </div>
                    </div>
                </div>



                <div class="col-12">
                    <div class="card flip frente">
                        <div class="card-header front bg-oc-wine">
                            <div class="icon-toogle" data-toggle="popover" data-trigger="hover" data-placement="left" title="<?= TRANS('CARDS_TURN'); ?>">
                                <span class="icon-toogle-card text-white toogle-sla-open"></span>
                            </div>
                            <small><span class="badge badge-warning mb-2"><?= TRANS('CARDS_NOW'); ?></span></small>
                            <h6 class="text-center text-white text-nowrap"><i class="fas fa-pause"></i>&nbsp;<?= TRANS('CARDS_PAUSED'); ?></h6>
                            <h5 class="text-center text-white"><span id="badgeFrozenByStatus" class="badge badge-light">0</span></h5>
                        </div>

                        <div class="card-header back bg-oc-wine">
                            <div class="icon-toogle" data-toggle="popover" data-trigger="hover" data-placement="left" title="<?= TRANS('CARDS_TURN'); ?>">
                                <span class="icon-toogle-card text-white toogle-sla-open"></span>
                            </div>
                            <small><span class="badge badge-warning mb-2"><?= TRANS('CARDS_NOW'); ?></span></small>
                            <h6 class="text-center text-white text-nowrap"><i class="fas fa-pause"></i>&nbsp;<?= TRANS('CARDS_PAUSED'); ?></h6>
                            <h5 class="text-center text-white"><span id="badgeFrozenByWorktime" class="badge badge-light">0</span></h5>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card flip frente">
                        <div class="card-header front bg-info">
                            <div class="icon-toogle" data-toggle="popover" data-trigger="hover" data-placement="left" title="<?= TRANS('CARDS_TURN'); ?>">
                                <span class="icon-toogle-card text-white toogle-sla-open"></span>
                            </div>
                            <small><span class="badge badge-warning mb-2"><?= TRANS('CARDS_GENERAL'); ?>: <?= TRANS('CARDS_NOT_CLOSED'); ?></span></small>
                            <h6 class="text-center text-white text-nowrap"><i class="fas fa-ticket-alt"></i>&nbsp;<?= TRANS('CARDS_OLDER'); ?></h6>
                            <h5 class="text-center text-white"><span id="badgeOlderTicket" class="badge badge-light">0</span></h5>
                        </div>

                        <div class="card-header back bg-info">
                            <div class="icon-toogle" data-toggle="popover" data-trigger="hover" data-placement="left" title="<?= TRANS('CARDS_TURN'); ?>">
                                <span class="icon-toogle-card text-white toogle-sla-open"></span>
                            </div>
                            <small><span class="badge badge-warning mb-2"><?= TRANS('CARDS_GENERAL'); ?>: <?= TRANS('CARDS_NOT_CLOSED'); ?></span></small>
                            <h6 class="text-center text-white text-nowrap"><i class="fas fa-ticket-alt"></i>&nbsp;<?= TRANS('CARDS_NEWER'); ?></h6>
                            <h5 class="text-center text-white"><span id="badgeNewerTicket" class="badge badge-light">0</span></h5>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card flip frente">
                        <div class="card-header front bg-info">
                            <!-- bg-danger -->
                            <div class="icon-toogle" data-toggle="popover" data-trigger="hover" data-placement="left" title="<?= TRANS('CARDS_TURN'); ?>">
                                <span class="icon-toogle-card text-white toogle-sla-open"></span>
                            </div>
                            <small><span class="badge badge-warning mb-2"><?= TRANS('CARDS_IN_THIS_MONTH'); ?>: <?= TRANS('CARDS_CLOSED'); ?></span></small>
                            <h6 class="text-center text-white text-nowrap"><i class="fas fa-handshake"></i>&nbsp;<?= TRANS('CARDS_SOLUTION_SLA'); ?></h6>
                            <h5 class="text-center text-white"><span id="badgeDoneSolutionGreen" class="badge badge-light">0</span></h5>
                        </div>

                        <div class="card-header back bg-info">
                            <div class="icon-toogle" data-toggle="popover" data-trigger="hover" data-placement="left" title="<?= TRANS('CARDS_TURN'); ?>">
                                <span class="icon-toogle-card text-white toogle-sla-open"></span>
                            </div>
                            <small><span class="badge badge-warning mb-2"><?= TRANS('CARDS_IN_THIS_MONTH'); ?>: <?= TRANS('CARDS_CLOSED'); ?></span></small>
                            <h6 class="text-center text-white text-nowrap"><i class="fas fa-handshake"></i>&nbsp;<?= TRANS('CARDS_RESPONSE_SLA'); ?></h6>
                            <h5 class="text-center text-white"><span id="badgeDoneResponseGreen" class="badge badge-light">0</span></h5>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card flip frente">
                        <div class="card-header front bg-info">
                            <!-- bg-danger -->
                            <div class="icon-toogle" data-toggle="popover" data-trigger="hover" data-placement="left" title="<?= TRANS('CARDS_TURN'); ?>">
                                <span class="icon-toogle-card text-white toogle-sla-open"></span>
                            </div>
                            <small><span class="badge badge-warning mb-2"><?= TRANS('CARDS_TODAY'); ?>: <?= TRANS('CARDS_CLOSED'); ?></span></small>
                            <h6 class="text-center text-white text-nowrap"><i class="fas fa-handshake"></i>&nbsp;<?= TRANS('CARDS_SOLUTION_SLA'); ?></h6>
                            <h5 class="text-center text-white"><span id="badgeDoneTodaySolutionGreen" class="badge badge-light">0</span></h5>
                        </div>

                        <div class="card-header back bg-info">
                            <div class="icon-toogle" data-toggle="popover" data-trigger="hover" data-placement="left" title="<?= TRANS('CARDS_TURN'); ?>">
                                <span class="icon-toogle-card text-white toogle-sla-open"></span>
                            </div>
                            <small><span class="badge badge-warning mb-2"><?= TRANS('CARDS_TODAY'); ?>: <?= TRANS('CARDS_CLOSED'); ?></span></small>
                            <h6 class="text-center text-white text-nowrap"><i class="fas fa-handshake"></i>&nbsp;<?= TRANS('CARDS_RESPONSE_SLA'); ?></h6>
                            <h5 class="text-center text-white"><span id="badgeDoneTodayResponseGreen" class="badge badge-light">0</span></h5>
                        </div>
                    </div>
                </div>

            </div>
            

        </div>

        
        <!-- ------------------------------------------------------------- --
            Seção de gráficos
        --------------------------------------------------------------------->

        <div class="flex-container">

            <div class="icon-toogle" data-toggle="popover" data-trigger="hover" data-placement="left" title="<?= TRANS('INCREASE_OR_DECREASE_VIEW_PANEL'); ?>">
                <span class="icon-expand text-secondary" id="toogle-side-cards"></span>
            </div>

            <div class="flex-child">

                <div class="flex-child-child">
                    <div class="icon-show-graph" id="first_graph" data-toggle="popover" data-trigger="hover" data-placement="left" title="<?= TRANS('SHOW_CHART'); ?>">
                        <span class="icon-view-graph text-oc-teal"></span>
                    </div>
                    <div class="card">
                        <div class="card-header bg-light">
                            <div class="chart-container" id="container01">
                                <canvas id="graph_01"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex-child-child">
                    <div class="icon-show-graph" id="second_graph" data-toggle="popover" data-trigger="hover" data-placement="left" title="<?= TRANS('SHOW_CHART'); ?>">
                        <!-- <div class="icon-show-graph" id="second_graph" title="<?= TRANS('SHOW_CHART'); ?>"> -->
                        <span class="icon-view-graph text-oc-teal"></span>
                    </div>
                    <div class="card">
                        <div class="card-header bg-light">
                            <div class="chart-container" id="container02">
                                <canvas id="graph_02"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex-container">
            <div class="flex-child">
                <div class="flex-child-child">
                    <div class="icon-show-graph" id="third_graph" data-toggle="popover" data-trigger="hover" data-placement="left" title="<?= TRANS('SHOW_CHART'); ?>">
                        <span class="icon-view-graph text-oc-teal"></span>
                    </div>
                    <div class="card">
                        <div class="card-header bg-light">
                            <div class="chart-container" id="container03">
                                <canvas id="graph_03"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex-child-child">
                    <div class="icon-show-graph" id="fourth_graph" data-toggle="popover" data-trigger="hover" data-placement="left" title="<?= TRANS('SHOW_CHART'); ?>">
                        <span class="icon-view-graph text-oc-teal"></span>
                    </div>
                    <div class="card">
                        <div class="card-header bg-light">
                            <div class="chart-container" id="container04">
                                <canvas id="graph_04"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex-container">
            <div class="flex-child">
                <div class="flex-child-child">
                    <div class="icon-show-graph" id="fifth_graph" data-toggle="popover" data-trigger="hover" data-placement="left" title="<?= TRANS('SHOW_CHART'); ?>">
                        <span class="icon-view-graph text-oc-teal"></span>
                    </div>
                    <div class="card">
                        <div class="card-header bg-light">
                            <div class="chart-container" id="container05">
                                <canvas id="graph_05"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex-child-child">
                    <div class="icon-show-graph" id="sixth_graph" data-toggle="popover" data-trigger="hover" data-placement="left" title="<?= TRANS('SHOW_CHART'); ?>">
                        <span class="icon-view-graph text-oc-teal"></span>
                    </div>
                    <div class="card">
                        <div class="card-header bg-light">
                            <div class="chart-container" id="container06">
                                <canvas id="graph_06"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex-container">
            <div class="flex-child">
                <div class="flex-child-child">
                    <div class="icon-show-graph" id="seventh_graph" data-toggle="popover" data-trigger="hover" data-placement="left" title="<?= TRANS('SHOW_CHART'); ?>">
                        <span class="icon-view-graph text-oc-teal"></span>
                    </div>
                    <div class="card">
                        <div class="card-header bg-light">
                            <div class="chart-container" id="container07">
                                <canvas id="graph_07"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex-child-child">
                    <div class="icon-show-graph" id="eightth_graph" data-toggle="popover" data-trigger="hover" data-placement="left" title="<?= TRANS('SHOW_CHART'); ?>">
                        <span class="icon-view-graph text-oc-teal"></span>
                    </div>
                    <div class="card">
                        <div class="card-header bg-light">
                            <div class="chart-container" id="container08">
                                <canvas id="graph_08"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Novos gráficos por cliente -->

        <div class="flex-container">
            <div class="flex-child">
                <div class="flex-child-child">
                    <div class="icon-show-graph" id="nineth_graph" data-toggle="popover" data-trigger="hover" data-placement="left" title="<?= TRANS('SHOW_CHART'); ?>">
                        <span class="icon-view-graph text-oc-teal"></span>
                    </div>
                    <div class="card">
                        <div class="card-header bg-light">
                            <div class="chart-container" id="container09">
                                <canvas id="graph_09"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex-child-child">
                    <div class="icon-show-graph" id="tenth_graph" data-toggle="popover" data-trigger="hover" data-placement="left" title="<?= TRANS('SHOW_CHART'); ?>">
                        <span class="icon-view-graph text-oc-teal"></span>
                    </div>
                    <div class="card">
                        <div class="card-header bg-light">
                            <div class="chart-container" id="container10">
                                <canvas id="graph_10"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="flex-container">
            <div class="flex-child">
                <div class="flex-child-child">
                    <div class="icon-show-graph" id="eleventh_graph" data-toggle="popover" data-trigger="hover" data-placement="left" title="<?= TRANS('SHOW_CHART'); ?>">
                        <span class="icon-view-graph text-oc-teal"></span>
                    </div>
                    <div class="card">
                        <div class="card-header bg-light">
                            <div class="chart-container" id="container11">
                                <canvas id="graph_11"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex-child-child">
                    <div class="icon-show-graph" id="twelveth_graph" data-toggle="popover" data-trigger="hover" data-placement="left" title="<?= TRANS('SHOW_CHART'); ?>">
                        <span class="icon-view-graph text-oc-teal"></span>
                    </div>
                    <div class="card">
                        <div class="card-header bg-light">
                            <div class="chart-container" id="container12">
                                <canvas id="graph_12"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex-container">
            <div class="flex-child">
                <div class="flex-child-child">
                    <div class="icon-show-graph" id="thirteenth_graph" data-toggle="popover" data-trigger="hover" data-placement="left" title="<?= TRANS('SHOW_CHART'); ?>">
                        <span class="icon-view-graph text-oc-teal"></span>
                    </div>
                    <div class="card">
                        <div class="card-header bg-light">
                            <div class="chart-container" id="container13">
                                <canvas id="graph_13"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex-child-child">
                    <div class="icon-show-graph" id="fourteenth_graph" data-toggle="popover" data-trigger="hover" data-placement="left" title="<?= TRANS('SHOW_CHART'); ?>">
                        <span class="icon-view-graph text-oc-teal"></span>
                    </div>
                    <div class="card">
                        <div class="card-header bg-light">
                            <div class="chart-container" id="container14">
                                <canvas id="graph_14"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>





        <!-- Área da nuvem de tags -->
        <div class="flex-container">
            <div class="flex-cloudtag">
                <div class="flex-child-child-fullspace">
                    <div class="icon-show-graph" id="show_tag_cloud" data-toggle="popover" data-trigger="hover" data-placement="left" title="<?= TRANS('SHOW_CHART'); ?>">
                        <span class="icon-view-graph text-oc-teal"></span>
                    </div>
                    <div class="card">
                        <div class="card-header bg-light ">
                            <div class="cloud-container" id="container-cloud">
                                <div id="tag_cloud"></div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <?php
        /* Se o isolamento de visibilidade entre áreas estiver habilitado e não for user admin */
        $area = null;
        if (isAreasIsolated($conn) && $_SESSION['s_nivel'] != 1) {
            $area = $_SESSION['s_uareas'];
        ?>
            <div class="flex-container">
                <div class="flex-child">
                    <small class="mt-4 text-secondary">(<?= TRANS('SHOWN_ONLY_YOUR_AREAS_DATA'); ?>)</small>
                </div>
            </div>
        <?php
        }
        $startDate = date("Y-m-01");
        $endDate = date("Y-m-d");
        ?>
        <!-- Para a consulta de tags individuais na nuvem de tags -->
        <input type="hidden" name="startDate" id="startDate" value="<?= $startDate; ?>">
        <input type="hidden" name="endDate" id="endDate" value="<?= $endDate; ?>">
        <!-- <input type="hidden" name="area" id="area" value="<?= $area; ?>"> -->



        <script src="../../includes/javascript/funcoes-3.0.js"></script>
        <script src="../../includes/components/jquery/jquery.js"></script>
        <script src="../../includes/components/jquery/jquery-flip/dist/jquery.flip.js"></script>
        <script src="../../includes/components/bootstrap/js/popper.min.js"></script>
        <script src="../../includes/components/bootstrap/js/bootstrap.min.js"></script>
        <script type="text/javascript" src="../../includes/components/chartjs/dist/Chart.min.js"></script>
        <script type="text/javascript" src="../../includes/components/chartjs/chartjs-plugin-colorschemes/dist/chartjs-plugin-colorschemes.js"></script>
        <script type="text/javascript" src="../../includes/components/chartjs/chartjs-plugin-datalabels/chartjs-plugin-datalabels.min.js"></script>
        <script src="../../includes/components/jquery/dynamic-seo-tag-cloud/jquery.tagcloud.js" type="text/javascript" charset="utf-8"></script>
        <script src="../../includes/components/bootstrap-select/dist/js/bootstrap-select.min.js"></script>



        <script src="ajax/tickets_x_status.js"></script>
        <script src="ajax/scheduled_tickets_x_workers.js"></script>
        <script src="ajax/tickets_x_area_months.js"></script>
        <script src="ajax/tickets_x_area_curr_month.js"></script>
        <script src="ajax/tickets_x_area.js"></script>
        <script src="ajax/tickets_area_close_months.js"></script>
        <script src="ajax/tickets_open_close_months.js"></script>
        <script src="ajax/tickets_operadores_close_months.js"></script>
        <script src="ajax/tickets_x_clients.js"></script>
        <script src="ajax/tickets_x_client_curr_month.js"></script>
        <script src="ajax/tickets_x_clients_months.js"></script>
        <script src="ajax/tickets_client_close_months.js"></script>
        <script src="ajax/top_ten_type_of_issues.js"></script>
        <script src="ajax/tickets_x_rates.js"></script>
        <script src="chartTagsCloud.js"></script>
        <script>
            $(function() {


                /* Para simular o comportamento responsivo da nuvem de tags */
                $(window).resize(function() {
                    tagsCloud('tag_cloud');
                });


                $.fn.selectpicker.Constructor.BootstrapVersion = '4';
                $('.sel2').selectpicker({
                    /* placeholder */
                    title: "<?= TRANS('OCO_SEL_ANY', '', 1); ?>",
                    liveSearch: true,
                    liveSearchNormalize: true,
                    liveSearchPlaceholder: "<?= TRANS('BT_SEARCH', '', 1); ?>",
                    noneResultsText: "<?= TRANS('NO_RECORDS_FOUND', '', 1); ?> {0}",
                    style: "",
                    styleBase: "form-control input-select-multi",
                }).on('loaded.bs.select', enableBoostrapSelectOptgroup);
                
                $('.bs-select').selectpicker({
                    /* placeholder */
                    title: "<?= TRANS('OCO_SEL_ANY', '', 1); ?>",
                    actionsBox: true,
                    deselectAllText: "<?= TRANS('DESELECT_ALL', '', 1); ?>",
                    selectAllText: "<?= TRANS('SELECT_ALL', '', 1); ?>",
                    liveSearch: true,
                    liveSearchNormalize: true,
                    liveSearchPlaceholder: "<?= TRANS('BT_SEARCH', '', 1); ?>",
                    noneResultsText: "<?= TRANS('NO_RECORDS_FOUND', '', 1); ?> {0}",
                    style: "",
                    styleBase: "form-control input-select-multi",
                });


                $('#toogle-form-filter').on('click', function() {

                    if ($('#dash-form-filter').css('display') == 'none') {
                        // $('#filter-handler').addClass('filter-handler-invert');
                        $('#dash-form-filter').slideDown();
                        $('#app_from').focus();
                    } else {
                        // $('#filter-handler').removeClass('filter-handler-invert');
                        $('#dash-form-filter').slideUp();
                        $('#toogle-form-filter').focusout();
                        $('#app_from').focus();
                    }
                });



                /* Filtro de pesquisa */
                $('#idSearch').on('click', function() {
                    getTopCardsData();
                    getSideCardsData();

                    var loading = $(".loading");
                    $(document).ajaxStart(function() {
                        loading.show();
                    });
                    $(document).ajaxStop(function() {
                        loading.hide();
                    });
                    $.ajax({
                        url: 'update_dashboard_session.php',
                        method: 'POST',
                        data: $('#form').serialize(),
                    }).done(function(data) {

                        let canvas01 = '<canvas id="graph_01"></canvas>'
                        $('#container01').empty().append(canvas01);
                        tickets_x_status('graph_01');

                        let canvas02 = '<canvas id="graph_02"></canvas>'
                        $('#container02').empty().append(canvas02);
                        scheduled_tickets_x_workers('graph_02'); //tickets_x_area_months

                        let canvas03 = '<canvas id="graph_03"></canvas>'
                        $('#container03').empty().append(canvas03);
                        tickets_x_area_months('graph_03'); //scheduled_tickets_x_workers

                        let canvas04 = '<canvas id="graph_04"></canvas>'
                        $('#container04').empty().append(canvas04);
                        tickets_x_area_curr_month('graph_04');

                        let canvas05 = '<canvas id="graph_05"></canvas>'
                        $('#container05').empty().append(canvas05);
                        tickets_x_area('graph_05');

                        let canvas06 = '<canvas id="graph_06"></canvas>'
                        $('#container06').empty().append(canvas06);
                        tickets_area_close_months('graph_06');

                        let canvas07 = '<canvas id="graph_07"></canvas>'
                        $('#container07').empty().append(canvas07);
                        tickets_open_close_months('graph_07');

                        let canvas08 = '<canvas id="graph_08"></canvas>'
                        $('#container08').empty().append(canvas08);
                        tickets_operadores_close_months('graph_08');

                        let canvas09 = '<canvas id="graph_09"></canvas>'
                        $('#container09').empty().append(canvas09);
                        tickets_x_clients('graph_09');

                        let canvas10 = '<canvas id="graph_10"></canvas>'
                        $('#container10').empty().append(canvas10);
                        tickets_x_client_curr_month('graph_10');

                        let canvas11 = '<canvas id="graph_11"></canvas>'
                        $('#container11').empty().append(canvas11);
                        tickets_x_clients_months('graph_11');

                        let canvas12 = '<canvas id="graph_12"></canvas>'
                        $('#container12').empty().append(canvas12);
                        tickets_client_close_months('graph_12');

                        let canvas13 = '<canvas id="graph_13"></canvas>'
                        $('#container13').empty().append(canvas13);
                        top_ten_type_of_issues('graph_13');
                        
                        let canvas14 = '<canvas id="graph_14"></canvas>'
                        $('#container14').empty().append(canvas14);
                        tickets_x_rates('graph_14');

                        tagsCloud('tag_cloud');

                    });
                });

                $("#idReset").click(function(e) {

                    e.preventDefault();
                    $("#form").trigger('reset');

                    $('.sel2').selectpicker('render');
                    $('.bs-select').selectpicker('render');
                });


                tickets_x_status('graph_01');
                scheduled_tickets_x_workers('graph_02');
                tickets_x_area_months('graph_03');
                tickets_x_area_curr_month('graph_04');
                tickets_x_area('graph_05');
                tickets_area_close_months('graph_06');
                tickets_open_close_months('graph_07');
                tickets_operadores_close_months('graph_08');
                tickets_x_clients('graph_09');
                tickets_x_client_curr_month('graph_10');
                tickets_x_clients_months('graph_11');
                tickets_client_close_months('graph_12');
                top_ten_type_of_issues('graph_13');
                tickets_x_rates('graph_14');


                $(".flip").flip({
                    trigger: 'manual'
                });


                $(function() {
                    $('[data-toggle="popover"]').popover({
                        html: true
                    })
                });
                $('.popover-dismiss').popover({
                    trigger: 'focus'
                });

                $('#first_graph').off().on('click', function() {
                    showGraphInModal(tickets_x_status);
                });
                $('#second_graph').off().on('click', function() {
                    showGraphInModal(scheduled_tickets_x_workers); //tickets_x_area_months
                });
                $('#third_graph').off().on('click', function() {
                    showGraphInModal(tickets_x_area_months); //scheduled_tickets_x_workers
                });
                $('#fourth_graph').off().on('click', function() {
                    showGraphInModal(tickets_x_area_curr_month);
                });
                $('#fifth_graph').off().on('click', function() {
                    showGraphInModal(tickets_x_area);
                });
                $('#sixth_graph').off().on('click', function() {
                    showGraphInModal(tickets_area_close_months);
                });
                $('#seventh_graph').off().on('click', function() {
                    showGraphInModal(tickets_open_close_months);
                });
                $('#eightth_graph').off().on('click', function() {
                    showGraphInModal(tickets_operadores_close_months);
                });
                $('#nineth_graph').off().on('click', function() {
                    showGraphInModal(tickets_x_clients);
                });
                $('#tenth_graph').off().on('click', function() {
                    showGraphInModal(tickets_x_client_curr_month);
                });
                $('#eleventh_graph').off().on('click', function() {
                    showGraphInModal(tickets_x_clients_months);
                });
                $('#twelveth_graph').off().on('click', function() {
                    showGraphInModal(tickets_client_close_months);
                });
                $('#show_tag_cloud').off().on('click', function() {
                    showGraphInModal(tagsCloud, true);
                });
                $('#thirteenth_graph').off().on('click', function() {
                    showGraphInModal(top_ten_type_of_issues);
                });
                $('#fourteenth_graph').off().on('click', function() {
                    showGraphInModal(tickets_x_rates);
                });




                /*  $('.icon-show-graph').on('click', function() {
                     console.log($(this).data('graph'));
                     showGraphInModal($(this).data('graph'));
                 }); */


                $('#toogle-side-cards').on('click', function() {
                    $('#side-cards').toggle('slow');
                    if ($('#toogle-side-cards').hasClass('icon-collapse')) {
                        $('#toogle-side-cards').addClass('icon-expand');
                        $('#toogle-side-cards').removeClass('icon-collapse');
                    } else {
                        $('#toogle-side-cards').addClass('icon-collapse');
                        $('#toogle-side-cards').removeClass('icon-expand');
                    }
                    /* coloquei um delay em função do tempo de execução do efeito toggle */
                    setTimeout(tagsCloud, 1000, 'tag_cloud');
                });

                $('.icon-toogle-card').on('click', function() {
                    if ($(this).parents().eq(2).hasClass('frente')) {
                        $(this).parents().eq(2).addClass('costas');
                        $(this).parents().eq(2).removeClass('frente');
                        $(this).parents().eq(2).flip(true);
                    } else {
                        $(this).parents().eq(2).addClass('frente');
                        $(this).parents().eq(2).removeClass('costas');
                        $(this).parents().eq(2).flip(false);
                    }
                });


                tagsCloud('tag_cloud');
                updateScheduled();
                getTopCardsData();
                getSideCardsData();
                // updateApproval();

                setInterval(function() {
                    updateScheduled();
                    getTopCardsData();
                    tagsCloud('tag_cloud');
                    
                }, 60000); //a cada 1 minuto

                setInterval(function() {
                    getSideCardsData();
                    // updateApproval();
                }, 120000); //a cada 2 minutos

            });

            function showGraphInModal(funcao, div = null) {

                var loading = $(".loading");
                $(document).ajaxStart(function() {
                    loading.show();
                });
                $(document).ajaxStop(function() {
                    loading.hide();
                });

                $('.canvas-modal').remove();

                if (div != null) {
                    var fieldHTML = '<div class="canvas-modal" id="canvasModal"></div>';
                } else {
                    var fieldHTML = '<canvas class="canvas-modal" id="canvasModal"></canvas>';
                }

                $('#divShowGraph').append(fieldHTML);

                funcao('canvasModal');
                $('#modalDefault').modal();
            }


            function getTopCardsData() {
                var loading = $(".loading");
                $(document).ajaxStart(function() {
                    loading.show();
                });
                $(document).ajaxStop(function() {
                    loading.hide();
                });
                $.ajax({
                    url: 'get_top_cards_data.php',
                    method: 'POST',
                    data: $('#form').serialize(),
                    dataType: 'json',

                }).done(function(data) {

                    // console.log(data);

                    $('#badgeAbertos').empty();
                    $('#badgeAbertos').html(data.abertosHoje);
                    $('#badgeAbertos').addClass('pointer');
                    $('#badgeAbertos').off('click');
                    $('#badgeAbertos').on('click', function(e) {
                        cardsAjaxList(data.abertosHojeFilter, e);
                    });

                    $('#badgeFechados').empty();
                    $('#badgeFechados').html(data.fechadosHoje);
                    $('#badgeFechados').addClass('pointer');
                    $('#badgeFechados').off('click');
                    $('#badgeFechados').on('click', function(e) {
                        cardsAjaxList(data.fechadosHojeFilter, e);
                    });

                    $('#badgeEmProgresso').empty();
                    $('#badgeEmProgresso').html(data.emProgresso + ' <small><mark>(' + data.percEmProgresso + '%)</mark></small>');
                    $('#badgeEmProgresso').addClass('pointer');
                    $('#badgeEmProgresso').off('click');
                    $('#badgeEmProgresso').on('click', function(e) {
                        cardsAjaxList(data.emProgressoFilter, e);
                    });

                    $('#badgeAguardandoResposta').empty();
                    $('#badgeAguardandoResposta').html(data.semResposta + ' <small><mark>(' + data.percSemResposta + '%)</mark></small>');
                    $('#badgeAguardandoResposta').addClass('pointer');
                    $('#badgeAguardandoResposta').off('click');
                    $('#badgeAguardandoResposta').on('click', function(e) {
                        cardsAjaxList(data.semRespostaFilter, e);
                    });

                    $('#badgeAbertosMes').empty();
                    $('#badgeAbertosMes').html(data.abertosMes);
                    $('#badgeAbertosMes').addClass('pointer');
                    $('#badgeAbertosMes').off('click');
                    $('#badgeAbertosMes').on('click', function(e) {
                        cardsAjaxList(data.abertosMesFilter, e);
                    });

                    $('#badgeFechadosMes').empty();
                    $('#badgeFechadosMes').html(data.fechadosMes);
                    $('#badgeFechadosMes').addClass('pointer');
                    $('#badgeFechadosMes').off('click');
                    $('#badgeFechadosMes').on('click', function(e) {
                        cardsAjaxList(data.fechadosMesFilter, e);
                    });


                }).fail(function(data) {
                    // $('#divError').html('<p class="text-danger text-center"><?= TRANS('FETCH_ERROR'); ?></p>');
                    // console.log(data);
                });
                return false;
            }


            function getSideCardsData() {
                $.ajax({
                    url: 'get_side_cards_data.php',
                    method: 'POST',
                    data: $('#form').serialize(),
                    dataType: 'json',

                }).done(function(data) {

                    // console.log(data);

                    $('#badgeAgendados').empty();
                    $('#badgeAgendados').html(data.agendados);
                    $('#badgeAgendados').addClass('pointer');
                    $('#badgeAgendados').off('click');
                    $('#badgeAgendados').on('click', function(e) {
                        cardsAjaxList(data.agendadosFilter, e);
                    });


                    $('#badgeWaitingRate').empty();
                    $('#badgeWaitingRate').html(data.waitingRate);
                    $('#badgeWaitingRate').addClass('pointer');
                    $('#badgeWaitingRate').off('click');
                    $('#badgeWaitingRate').on('click', function(e) {
                        cardsAjaxList(data.waitingRateFilter, e);
                    });


                    $('#badgeFilaGeral').empty();
                    $('#badgeFilaGeral').html(data.filaGeral + ' <small><mark>(' + data.percFilaGeral + '%)</mark></small>');
                    $('#badgeFilaGeral').addClass('pointer');
                    $('#badgeFilaGeral').off('click');
                    $('#badgeFilaGeral').on('click', function(e) {
                        cardsAjaxList(data.filaGeralFilter, e);
                    });


                    if (data.percResponseGreen >= 80) {
                        $('#badgeResponseGreen').parents().eq(1).removeClass('bg-info bg-oc-orange bg-success bg-danger');
                        $('#badgeResponseGreen').parents().eq(1).addClass('bg-success');
                    } else if (data.percResponseGreen >= 70) {
                        $('#badgeResponseGreen').parents().eq(1).removeClass('bg-info bg-oc-orange bg-success bg-danger');
                        $('#badgeResponseGreen').parents().eq(1).addClass('bg-oc-orange');
                    } else {
                        $('#badgeResponseGreen').parents().eq(1).removeClass('bg-info bg-oc-orange bg-success bg-danger');
                        $('#badgeResponseGreen').parents().eq(1).addClass('bg-danger');
                    }
                    $('#badgeResponseGreen').html(data.percResponseGreen + '%');

                    if (data.percSolutionGreen >= 80) {
                        $('#badgeSolutionGreen').parents().eq(1).removeClass('bg-info bg-oc-orange bg-success bg-danger');
                        $('#badgeSolutionGreen').parents().eq(1).addClass('bg-success');
                    } else if (data.percSolutionGreen >= 70) {
                        $('#badgeSolutionGreen').parents().eq(1).removeClass('bg-info bg-oc-orange bg-success bg-danger');
                        $('#badgeSolutionGreen').parents().eq(1).addClass('bg-oc-orange');
                    } else {
                        $('#badgeSolutionGreen').parents().eq(1).removeClass('bg-info bg-oc-orange bg-success bg-danger');
                        $('#badgeSolutionGreen').parents().eq(1).addClass('bg-danger');
                    }
                    $('#badgeSolutionGreen').html(data.percSolutionGreen + '%');

                    $('#badgeAvgFilteredResponseTime').html(data.openAvgFilteredResponseTime + ' <small><mark>(<?= TRANS("CARDS_FILTERED_TIME", '', 1); ?>)</mark></small>');
                    $('#badgeAvgFilteredSolutionTime').html(data.openAvgFilteredSolutionTime + ' <small><mark>(<?= TRANS("CARDS_FILTERED_TIME", '', 1); ?>)</mark></small>');
                    $('#badgeAvgAbsResponseTime').html(data.openAvgAbsResponseTime + ' <small><mark>(<?= TRANS("CARDS_ABSOLUTE_TIME", '', 1); ?>)</mark></small>');
                    $('#badgeAvgAbsSolutionTime').html(data.openAvgAbsSolutionTime + ' <small><mark>(<?= TRANS("CARDS_ABSOLUTE_TIME", '', 1); ?>)</mark></small>');


                    $('#badgeFrozenByStatus').empty();
                    $('#badgeFrozenByStatus').html(data.frozenByStatus + ' <small><mark>(<?= TRANS("CARDS_DUE_STATUS", '', 1); ?>)</mark></small>');
                    $('#badgeFrozenByStatus').addClass('pointer');
                    $('#badgeFrozenByStatus').off('click');
                    $('#badgeFrozenByStatus').on('click', function(e) {
                        cardsAjaxList(data.frozenByStatusFilter, e);
                    });


                    $('#badgeFrozenByWorktime').html(data.frozenByWorktime + ' <small><mark>(<?= TRANS("CARDS_DUE_WORKTIME", '', 1); ?>)</mark></small>');


                    $('#badgeOlderTicket').empty();
                    $('#badgeOlderTicket').html('<?= TRANS("NUMBER_ABBREVIATE", '', 1); ?> ' + data.olderTicket + ' <small><mark>( ' + data.olderAge + ' )</mark></small>');
                    $('#badgeOlderTicket').addClass('pointer');
                    $('#badgeOlderTicket').off('click');
                    $('#badgeOlderTicket').on('click', function(e) {
                        cardsAjaxList(data.olderTicketFilter, e);
                    });


                    $('#badgeNewerTicket').empty();
                    $('#badgeNewerTicket').html('<?= TRANS("NUMBER_ABBREVIATE", '', 1); ?> ' + data.newerTicket + ' <small><mark>( ' + data.newerAge + ' )</mark></small>');
                    $('#badgeNewerTicket').addClass('pointer');
                    $('#badgeNewerTicket').off('click');
                    $('#badgeNewerTicket').on('click', function(e) {
                        cardsAjaxList(data.newerTicketFilter, e);
                    });

                    if (data.percDoneSolutionGreen >= 80) {
                        $('#badgeDoneSolutionGreen').parents().eq(1).removeClass('bg-info bg-oc-orange bg-success bg-danger');
                        $('#badgeDoneSolutionGreen').parents().eq(1).addClass('bg-success');
                    } else if (data.percDoneSolutionGreen >= 70) {
                        $('#badgeDoneSolutionGreen').parents().eq(1).removeClass('bg-info bg-oc-orange bg-success bg-danger');
                        $('#badgeDoneSolutionGreen').parents().eq(1).addClass('bg-oc-orange');
                    } else {
                        $('#badgeDoneSolutionGreen').parents().eq(1).removeClass('bg-info bg-oc-orange bg-success bg-danger');
                        $('#badgeDoneSolutionGreen').parents().eq(1).addClass('bg-danger');
                    }
                    $('#badgeDoneSolutionGreen').html(data.percDoneSolutionGreen + '%');

                    if (data.percDoneResponseGreen >= 80) {
                        $('#badgeDoneResponseGreen').parents().eq(1).removeClass('bg-info bg-oc-orange bg-success bg-danger');
                        $('#badgeDoneResponseGreen').parents().eq(1).addClass('bg-success');
                    } else if (data.percDoneResponseGreen >= 70) {
                        $('#badgeDoneResponseGreen').parents().eq(1).removeClass('bg-info bg-oc-orange bg-success bg-danger');
                        $('#badgeDoneResponseGreen').parents().eq(1).addClass('bg-oc-orange');
                    } else {
                        $('#badgeDoneResponseGreen').parents().eq(1).removeClass('bg-info bg-oc-orange bg-success bg-danger');
                        $('#badgeDoneResponseGreen').parents().eq(1).addClass('bg-danger');
                    }
                    $('#badgeDoneResponseGreen').html(data.percDoneResponseGreen + '%');

                    if (data.percDoneTodayResponseGreen >= 80) {
                        $('#badgeDoneTodayResponseGreen').parents().eq(1).removeClass('bg-info bg-oc-orange bg-success bg-danger');
                        $('#badgeDoneTodayResponseGreen').parents().eq(1).addClass('bg-success');
                    } else if (data.percDoneTodayResponseGreen >= 70) {
                        $('#badgeDoneTodayResponseGreen').parents().eq(1).removeClass('bg-info bg-oc-orange bg-success bg-danger');
                        $('#badgeDoneTodayResponseGreen').parents().eq(1).addClass('bg-oc-orange');
                    } else {
                        $('#badgeDoneTodayResponseGreen').parents().eq(1).removeClass('bg-info bg-oc-orange bg-success bg-danger');
                        $('#badgeDoneTodayResponseGreen').parents().eq(1).addClass('bg-danger');
                    }
                    $('#badgeDoneTodayResponseGreen').html(data.percDoneTodayResponseGreen + '%');

                    if (data.percDoneTodaySolutionGreen >= 80) {
                        $('#badgeDoneTodaySolutionGreen').parents().eq(1).removeClass('bg-info bg-oc-orange bg-success bg-danger');
                        $('#badgeDoneTodaySolutionGreen').parents().eq(1).addClass('bg-success');
                    } else if (data.percDoneTodaySolutionGreen >= 70) {
                        $('#badgeDoneTodaySolutionGreen').parents().eq(1).removeClass('bg-info bg-oc-orange bg-success bg-danger');
                        $('#badgeDoneTodaySolutionGreen').parents().eq(1).addClass('bg-oc-orange');
                    } else {
                        $('#badgeDoneTodaySolutionGreen').parents().eq(1).removeClass('bg-info bg-oc-orange bg-success bg-danger');
                        $('#badgeDoneTodaySolutionGreen').parents().eq(1).addClass('bg-danger');
                    }
                    $('#badgeDoneTodaySolutionGreen').html(data.percDoneTodaySolutionGreen + '%');

                }).fail(function() {
                    // $('#divError').html('<p class="text-danger text-center"><?= TRANS('FETCH_ERROR'); ?></p>');
                    console.log(data);
                });
                return false;
            }


            function cardsAjaxList(arrayKeyData, e) {

                var data = {};
                $.each(arrayKeyData, function(key, value) {
                    // data[key] = encodeURIComponent(value);
                    data[key] = value;
                });

                e.preventDefault();
                var loading = $(".loading");
                $(document).ajaxStart(function() {
                    loading.show();
                });

                $(document).ajaxStop(function() {
                    loading.hide();
                });

                popup_alerta_wide('./get_card_tickets.php?' + $.param(data));
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

            /* Roda a aprovação automática para chamados que não foram avaliados dentro do prazo configurado */
            // function updateApproval() {
            //     $.ajax({
            //         url: 'update_auto_approval.php',
            //         method: 'POST'
            //     });
            //     return false;
            // }


            /* Função para habilitar a seleção de todos os itens de um optgroup ao clicar no label */
            function enableBoostrapSelectOptgroup() {

                let that = $(this).data('selectpicker'),
                    inner = that.$menu.children('.inner');

                // remove default event
                inner.off('click', '.divider, .dropdown-header');
                // add new event
                inner.on('click', '.divider, .dropdown-header', function(e) {
                    // original functionality
                    e.preventDefault();
                    e.stopPropagation();
                    if (that.options.liveSearch) {
                        that.$searchbox.trigger('focus');
                    } else {
                        that.$button.trigger('focus');
                    }

                    // extended functionality
                    let position0 = that.isVirtual() ? that.selectpicker.view.position0 : 0,
                        clickedData = that.selectpicker.current.data[$(this).index() + position0];

                    // copied parts from changeAll function
                    let selected = null;
                    for (let i = 0, data = that.selectpicker.current.data, len = data.length; i < len; i++) {
                        let element = data[i];
                        if (element.type === 'option' && element.optID === clickedData.optID) {
                            if (selected === null) {
                                selected = !element.selected;
                            }
                            element.option.selected = selected;
                        }
                    }
                    that.setOptionStatus();
                    that.$element.triggerNative('change');
                });
            }
        </script>
</body>

</html>