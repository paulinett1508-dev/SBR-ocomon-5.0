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
    // $_SESSION['session_expired'] = 1;
    echo "<script>top.window.location = '../../index.php'</script>";
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
$sess_state = (isset($_SESSION['s_rep_filters']['state']) ? $_SESSION['s_rep_filters']['state'] : 1);

$postStartDate = "";
$postEndDate = "";

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

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- <link rel="stylesheet" type="text/css" href="../../includes/css/estilos.css" /> -->
    <link rel="stylesheet" type="text/css" href="../../includes/components/jquery/datetimepicker/jquery.datetimepicker.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap/custom.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/fontawesome/css/all.min.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap-select/dist/css/bootstrap-select.min.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/css/my_bootstrap_select.css" />
    <link rel="stylesheet" href="../../includes/components/jquery/dynamic-seo-tag-cloud/jquery.tagcloud.css" />

    <style>
        .chart-container {
            position: relative;
            /* height: 100%; */
            max-width: 100%;
            margin-top: 30px;
            margin-left: 10px;
            margin-right: 10px;
            margin-bottom: 30px;
        }

        .footer-info {
            margin-top: 30px;
            margin-left: 10px;
            color: gray;
            font-size: 0.8em;
        }
    </style>

    <title>OcoMon&nbsp;<?= VERSAO; ?></title>
</head>

<body>

    <div class="container">
        <div id="idLoad" class="loading" style="display:none"></div>
    </div>


    <div class="container">
        <h5 class="my-4"><i class="fas fa-hashtag text-secondary"></i>&nbsp;<?= TRANS('TAGGING_CLOUD_REPORT'); ?></h5>
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

            $client = (isset($_POST['client']) && !empty($_POST['client']) ? $_POST['client'] : null);
            $_SESSION['s_rep_filters']['client'] = $client;
            $_SESSION['s_rep_filters']['area'] = $_POST['area'];
            $clientName = (!empty($client) ? getClients($conn, $client)['nickname']: "");
            $clausule = (!empty($client) ? " AND o.client IN ({$client}) " : "");
            $noneClient = TRANS('FILTERED_CLIENT') . ": " . TRANS('NONE_FILTER') . "&nbsp;&nbsp;";
            $criterio = (!empty($client) ? TRANS('FILTERED_CLIENT') . ": {$clientName}&nbsp;&nbsp;" : $noneClient );

            $postStartDate = $_POST['d_ini'];
            $postEndDate = $_POST['d_fim'];

            $startDate = dateDB($_POST['d_ini'] . $hora_inicio);
            $endDate = dateDB($_POST['d_fim'] . $hora_fim);
            $area = ($_POST['area'] != '-1' ? $_POST['area'] : null);

            if ($startDate >= $endDate) {
                $_SESSION['flash'] = message('info', '', TRANS('MSG_COMPARE_DATE'), '');
                redirect($_SERVER['PHP_SELF']);
                exit;
            }

            $_SESSION['s_rep_filters']['d_ini'] = $_POST['d_ini'];
            $_SESSION['s_rep_filters']['d_fim'] = $_POST['d_fim'];


            if (!$area) {
                if (isAreasIsolated($conn) && $_SESSION['s_nivel'] != 1) {
                    /* Visibilidade isolada entre áreas para usuários não admin */
                    $area = $_SESSION['s_uareas'];
                } else {
                    $areas_names .= TRANS('NONE_FILTER');
                }
            } else {
                $areas_names = getAreaInfo($conn, $area)['area_name'];
            }

            ?>
            <input type="hidden" name="startDate" id="startDate" value="<?= $postStartDate; ?>">
            <input type="hidden" name="endDate" id="endDate" value="<?= $postEndDate; ?>">
            <input type="hidden" name="area" id="area" value="<?= $area; ?>">
            <input type="hidden" name="client" id="client" value="<?= $client; ?>">
        
            <div class="chart-container">
                <ul id="myTagCloud">
                    <?php
                    $none = true;
                    foreach (getTagsList($conn) as $tag) {
                        $tagCount = getTagCount($conn, $tag['tag_name'], $startDate, $endDate, $area, false, $client);
                        if ($tagCount) {
                            $none = false;
                        ?>
                            <li data-weight="<?= $tagCount; ?>">
                                <!-- <?= $tag['tag_name']; ?> -->
                                <?= $tag['tag_name']; ?>: <?= $tagCount; ?>
                            </li>
                        <?php
                        }
                    }
                    if ($none) {
                        $_SESSION['flash'] = message('warning', '', TRANS('NO_RECORDS_FOUND'), '');
                        redirect($_SERVER['PHP_SELF']);
                        exit;
                    }
                    ?>
                </ul>
                <div class="footer-info">
                    <?= TRANS('TTL_PERIOD_FROM') . "&nbsp;" . $postStartDate . "&nbsp;" . TRANS('DATE_TO') . "&nbsp;" . $postEndDate ; ?><br/>
                    <?= $criterio . TRANS('FILTERED_AREA') . ": ". $areas_names; ?>
                    <span class="px-2" style="float:right" id="new_search"><?= TRANS('NEW_SEARCH'); ?></span>
                </div>
            </div>
            <?php

        }
        ?>
    </div>
    <script src="../../includes/javascript/funcoes-3.0.js"></script>
    <script src="../../includes/components/jquery/jquery.js"></script>
    <script src="../../includes/components/jquery/datetimepicker/build/jquery.datetimepicker.full.min.js"></script>
    <script src="../../includes/components/bootstrap/js/bootstrap.bundle.js"></script>
    <script src="../../includes/components/jquery/dynamic-seo-tag-cloud/jquery.tagcloud.js" type="text/javascript" charset="utf-8"></script>
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


            if ($("#myTagCloud").length > 0) {
                $('#myTagCloud').tagCloud({
                    container: {
                        width: $('.chart-container').innerWidth(),
                        // fontFamily: '"Times New Roman", Times, serif',
                        backgroundColor: '#fafaf8'
                    },
                    tag: {
                        // format:      '<a href="{tag.link}">{tag.name}</a>: {tag.weight}',
                        maxFontSize: 45,    // max font size in pixels
                        minFontSize: 10,     // min font size in pixels
                        textShadow:  true,  // text shadow, enabled for better visibility
                        // padding:          '3px 10px', // tag padding
                        // borderRadius:     '5px',      // border radius
                        // color:            'auto',     // automatic text color, black for light background,
                    },
                });

                $(".jqTcTag").off().on("click", function() {

                    /* Ajustando o data-name */
                    let tagName = encodeURIComponent($(this).attr("data-name").trim().split(':')[0]);

                    let postStartDate = encodeURIComponent($('#startDate').val());
                    let postEndDate = encodeURIComponent($('#endDate').val());
                    let area = encodeURIComponent($('#area').val());
                    
                    let hasClient = $('#client').val() != '' ? '&client[]=' + encodeURIComponent($('#client').val()) : '';

                    popup_alerta_wide('../../ocomon/geral/get_card_tickets.php?has_tags=' + tagName + '&data_abertura_from=' + postStartDate + '&data_abertura_to=' + postEndDate + '&area[]=' + area + hasClient);
                }).css('cursor', 'pointer');
            }

        });
    </script>
</body>

</html>