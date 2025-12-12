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
require_once __DIR__ . "/" . "../../includes/components/html2text-master/vendor/autoload.php";

use includes\classes\ConnectPDO;

$conn = ConnectPDO::getInstance();

$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 2, 1);

/* Para manter a compatibilidade com versões antigas */
$table = getTableCompat($conn);
$sysConfig = getConfig($conn);
$mailConfig = getMailConfig($conn);
$isAdmin = $_SESSION['s_nivel'] == 1;

$formatBar = $_SESSION['s_formatBarOco'];

$version4 = $sysConfig['conf_updated_issues'];

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OcoMon&nbsp;<?= VERSAO; ?></title>

    <link rel="stylesheet" type="text/css" href="../../includes/css/estilos.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/jquery/datetimepicker/jquery.datetimepicker.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap/custom.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/fontawesome/css/all.min.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/jquery/jquery.amsify.suggestags-master/css/amsify.suggestags.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap-select/dist/css/bootstrap-select.min.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/css/my_bootstrap_select.css" />

    <style>
        .oc-cursor {
            cursor: pointer;
        }

        #iframeLoad {
            border: 1px solid lightgray !important;
            overflow: scroll !important;
        }
    </style>


</head>

<body>
    <?php


    if (!isset($_POST['submit'])) {


        if (!isset($_GET['numero'])) {
            exit();
        }
        $numero = (int)$_GET['numero'];

        $row = getTicketData($conn, $numero);
        $issue_selected_data = [];
        $issue_selected = "";


        $isRequester = false;
        if ($_SESSION['s_uid'] == $row['aberto_por']) {
            $isRequester = true;
        }

        $isDone = $sysConfig['conf_status_done'] == $row['status'];
        $isClosed = $row['status'] == 4;
        $isRejected = $sysConfig['conf_status_done_rejected'] == $row['status'];


        /** Chamados concluídos não podem mais ser editados */
        if ($isDone || $isClosed) {
            echo message('danger', 'Ooops!', TRANS('MSG_TICKET_DONE_CANT_BE_EDITED'), '', '', 1);
            exit;
        }

        /* Checagem para saber se o usuário logado pode tratar o chamado (edição e encerramento) */
        $allowTreat = false;
        if ($_SESSION['s_nivel'] != 1) {
            if ($_SESSION['s_nivel'] > 2) {
                /* Somente-abertura não pode tratar */
                $allowTreat = false;
            } elseif ($isRequester && !$sysConfig['conf_allow_op_treat_own_ticket']) {
                /* Se for operador solicitante, então depende da configuração 
                sobre se o usuário operador pode tratar chamados abertos por ele mesmo*/
                $allowTreat = false;
            } else {
                $allowTreat = true;
            }
        } else {
            /* Admin sempre pode tratar */
            $allowTreat = true;
        }

        if (!$allowTreat) {
            redirect('ticket_show.php?numero=' . $numero);
            exit;
        }

        /* Controle para evitar acesso ao chamado por usuarios operadores que não fazem parte da area do chamado */
        if (!$isAdmin) {
            $uareas = explode(',', $_SESSION['s_uareas']);
            if (!in_array($row['sistema'], $uareas)) {
                ?>
                    <p class="p-3 m-4 text-center"></p>
                <?php
                echo message('danger', 'Ooops!', '<hr />'.TRANS('MSG_TICKET_NOT_ALLOWED_TO_BE_VIEWED'), '', '', true);
                exit;
            }
        }


        if (!empty($row['problema']) && $row['problema'] != "-1") {
            $issue_selected_data = getIssueById($conn, $row['problema']);
            $issue_selected = $issue_selected_data['problema'];
        }

        /* ASSENTAMENTOS */
        if ($_SESSION['s_nivel'] < 3) {
            $entries = getTicketEntries($conn, $numero, true);
        } else
            $entries = getTicketEntries($conn, $numero);

        $assentamentos = count($entries);

        /* ARQUIVOS */
        $files = getTicketFiles($conn, $numero);
        $hasFiles = count($files);

        /* Checagem para identificar chamados relacionados */
        $relatives = getTicketRelatives($conn, $numero);
        $hasRelatives = count($relatives);


        $hiddenStatus = [];
        $closed = false;
        $hadFirstResponse = false;
        if ($row['status'] == 4) {
            $closed = true;
        } else {
            $hiddenStatus[] = 4;
        }

        if (!empty($row['data_atendimento'])) {
            $hadFirstResponse = true;
        }

        /* Status que não serão selecionaveis na edição */
        if ($sysConfig['conf_status_done'] && $sysConfig['conf_status_done'] != $row['status']) {
            $hiddenStatus[] = (int)$sysConfig['conf_status_done'];
        }
        if ($sysConfig['conf_status_done_rejected'] && $sysConfig['conf_status_done_rejected'] != $row['status'] && $sysConfig['conf_status_done_rejected'] != 1) {
            $hiddenStatus[] = (int)$sysConfig['conf_status_done_rejected'];
        }


    ?>
        <div class="container">
            <div id="idLoad" class="loading" style="display:none"></div>
            <div id="loading" class="loading" style="display:none"></div>
        </div>

        <!-- Mensagens de retorno -->
        <div id="divResult"></div>

        <div class="container-fluid">


            <div class="modal" tabindex="-1" id="modalDefault">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div id="divModalDetails" class="p-3"></div>
                    </div>
                </div>
            </div>

            <div class="modal" id="modalIframe" tabindex="-1" style="z-index:9001!important">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div id="divDetails" style="position:relative">
                            <iframe id="iframeLoad"  frameborder="0" style="position:absolute;top:0px;width:95%;height:100vh;"></iframe>
                        </div>
                    </div>
                </div>
            </div>


            <h5 class="my-4"><i class="fas fa-edit text-secondary"></i>&nbsp;<?= TRANS('TICKET_EDIT_TITLE') . "&nbsp;<span class='badge badge-secondary pt-2'>" . $numero . "</span>"; ?></h5>

            <form name="form" id="form" method="post" action="<?= $_SERVER['PHP_SELF']; ?>" enctype="multipart/form-data">
                <!-- onSubmit="return valida();" -->

                <?= csrf_input(); ?>

                <input type="hidden" name="MAX_FILE_SIZE" value="<?= $sysConfig['conf_upld_size']; ?>" />


                <div class="form-group row my-4">


                    <!-- Cliente -->
                    <label for="client" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CLIENT'); ?></label>
                    <div class="form-group col-md-10">
                        <select class="form-control " id="client" name="client">

                            <option value="" selected><?= TRANS('SEL_SELECT'); ?></option>
                            <?php
                            $ticketClient = getClientByTicket($conn, $numero);
                            $clients = getClients($conn);
                            foreach ($clients as $client) {
                                ?>
                                    <option value="<?= $client['id']; ?>"
                                    <?= ($client['id'] == $ticketClient['id'] ? " selected" : ""); ?>
                                    ><?= $client['nickname']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Área de atendimento -->
                    <label for="idArea" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('RESPONSIBLE_AREA'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control " id="idArea" name="sistema">

                            <option value="-1" selected><?= TRANS('SEL_AREA'); ?></option>
                            <?php
                            $areasTo = getAreasToOpen($conn, $_SESSION['s_uareas']);
                            foreach ($areasTo as $areaTo) {
                            ?>
                                <option value="<?= $areaTo['sis_id']; ?>" <?= ($areaTo['sis_id'] == $row['sistema'] ? " selected" : ""); ?>><?= $areaTo['sistema']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>



                    <!-- Tipo de problema -->
                    <label for="idProblema" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('ISSUE_TYPE'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control " id="idProblema" name="problema">

                            <option value="" selected><?= TRANS('ISSUE_TYPE'); ?></option>
                            <?php
                            // $issues = getIssuesByArea($conn);
                            $issues = ($version4 ? getIssuesByArea4($conn) : getIssuesByArea($conn));
                            foreach ($issues as $issue) {
                                ?>
                                <option value="<?= $issue['prob_id']; ?>" 
                                    <?php
                                        if ($issue['prob_id'] == $row['problema']) {
                                            echo " selected";
                                        } elseif ($issue['problema'] == $issue_selected) {
                                            echo " selected";
                                        }
                                    ?>><?= $issue['problema']; ?></option>
                                <?php
                                //($issue['problema'] == $issue_selected ? " selected" : ""); 
                                //($issue['prob_id'] == $row['problema'] ? " selected" : "")
                            }
                            ?>
                        </select>
                    </div>
                    <input type="hidden" name="selected_issue" id="selected_issue" value="<?= $row['problema']; ?>">
                    <!-- Lista de tipos de problemas do mesmo tipo e categorias -->
                    <div id="issueCategories"></div>
                    <!-- Descrição do tipo de problema selecionado -->
                    <div id="issueDescription"></div>


                    <!-- Descrição -->
                    <?php
                        $description = trim(noHtml($row['descricao']));
                        $description = (new \Html2Text\Html2Text($description))->getText();
                    ?>
                    <label for="idDescricao" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('DESCRIPTION'); ?></label>
                    <div class="form-group col-md-10">
                        <textarea class="form-control " id="idDescricao" name="descricao" rows="4" disabled><?= $description; ?></textarea>
                    </div>


                    <!-- Tags/Labels -->
                    <label for="input_tags" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('INPUT_TAGS'); ?></label>
                    <div class="form-group col-md-10">
                        <input type="text" class="form-control " id="input_tags" name="input_tags" value="<?= $row['oco_tag']; ?>" placeholder="<?= TRANS('ADD_OR_REMOVE_INPUT_TAGS'); ?>" />
                        <div class="invalid-feedback">
                            <?= TRANS('ERROR_MIN_SIZE_OF_TAGNAME'); ?>
                        </div>
                    </div>


                    <!-- Unidade -->
                    <input type="hidden" name="unitDb" id="unitDb" value="<?= $row['instituicao']; ?>">
                    <label for="idUnidade" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_UNIT'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control " id="idUnidade" name="instituicao">
                            <option value="-1" selected><?= TRANS('SEL_UNIT'); ?></option>
                            <?php
                            $units = getUnits($conn);
                            foreach ($units as $unit) {
                            ?>
                                <option value="<?= $unit['inst_cod']; ?>" <?= ($unit['inst_cod'] == $row['instituicao'] ? " selected" : ""); ?>><?= $unit['inst_nome']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Etiqueta -->
                    <label for="idEtiqueta" class="col-md-2 col-form-label col-form-label-sm text-md-right text-nowrap"><?= TRANS('FIELD_TAG_EQUIP'); ?></label>
                    <div class="form-group col-md-4">
                        <div class="input-group">

                            <div class="input-group-prepend">
                                <div class="input-group-text">
                                    <a href="javascript:void(0);" data-pop="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('BT_GET_TAG_INFO_HELPER'); ?>." onClick="checa_etiqueta()"><i class="fa fa-sliders-h"></i></a>
                                </div>
                            </div>
                            <input type="text" class="form-control " id="idEtiqueta" name="equipamento" value="<?= $row['equipamento']; ?>" placeholder="<?= TRANS('FIELD_TAG_EQUIP'); ?>" />
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <a href="javascript:void(0);" data-pop="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('BT_GET_TICKETS_FROM_TAG_HELPER'); ?>." onClick="checa_chamados()"><i class="fa fa-history"></i></a>
                                </div>
                            </div>

                        </div>
                    </div>


                    <!-- Contato -->
                    <label for="contato" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CONTACT') ?></label>
                    <div class="form-group col-md-4">
                        <input type="text" class="form-control " id="contato" name="contato" list="contatos" autocomplete="off" value="<?= $row['contato']; ?>" placeholder="<?= TRANS('CONTACT') ?>" />
                    </div>
                    <datalist id="contatos"></datalist>


                    <!-- Contato email -->
                    <label for="contato_email" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CONTACT_EMAIL') ?></label>
                    <div class="form-group col-md-4">
                        <input type="email" class="form-control " id="contato_email" name="contato_email" list="contatos_emails" value="<?= $row['contato_email']; ?>" autocomplete="off" placeholder="<?= TRANS('CONTACT_EMAIL_PLACEHOLDER') ?>" />
                    </div>
                    <datalist id="contatos_emails"></datalist>


                    <!-- Telefone -->
                    <label for="idTelefone" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_PHONE'); ?></label>
                    <div class="form-group col-md-4">
                        <input type="tel" class="form-control " id="idTelefone" name="telefone" value="<?= $row['telefone']; ?>" placeholder="<?= TRANS('COL_PHONE'); ?>" />
                    </div>
                    <label for="idLocal" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('DEPARTMENT'); ?></label>


                    <!-- Departamento -->
                    <input type="hidden" name="localDb" id="localDb" value="<?= $row['local']; ?>">
                    <div class="form-group col-md-4">
                        <select class="form-control " name="local" id="idLocal">
                            <option value="-1"><?= TRANS('SEL_DEPARTMENT'); ?></option>
                            <?php
                            $departments = getDepartments($conn);
                            foreach ($departments as $department) {
                            ?>
                                <option value="<?= $department['loc_id']; ?>" <?= ($department['loc_id'] == $row['local'] ? " selected" : ""); ?>><?= $department['local']; ?> - <?= $department['pred_desc']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Responsável -->
                    <label for="idFoward" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('TECHNICIAN'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control " id="idFoward" name="foward">
                            <!-- <option value=""><?= $_SESSION['s_usuario_nome']; ?></option> -->

                            <?php

                            /* Checa se o usuário logado é o própio solicitante */
                            $isTheRequester = ($_SESSION['s_uid'] == $row['aberto_por'] ? true : false);

                            $responsible = getUserInfo($conn, $row['operador']);
                            $avoidDuplicate = "";

                            /* Se for o próprio solicitante logado então não altera o responsável do chamado */
                            if ($isTheRequester) {
                            ?>
                                <option value="<?= $row['operador']; ?>"><?= $responsible['nome']; ?></option>
                            <?php
                                $avoidDuplicate = $row['operador'];
                            } else {
                                /* Permite a alteração do responsável de acordo com o editor - mas não vem selecionado*/
                            ?>
                                <option value=""><?= $_SESSION['s_usuario_nome']; ?></option>
                                <?php
                                $avoidDuplicate = $_SESSION['s_uid'];
                            }

                            $users = getUsersByArea($conn, $row['sistema']);
                            foreach ($users as $user) {
                                if ($user['user_id'] != $avoidDuplicate) {
                                ?>
                                    <option value="<?= $user['user_id']; ?>" <?= ($user['user_id'] == $row['operador'] ? " selected" : ""); ?>><?= $user['nome']; ?></option>
                            <?php
                                }
                            }
                            ?>
                        </select>
                    </div>



                    <!-- Arquivos anexos -->
                    <label class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('ATTACH_FILE'); ?></label>

                    <div class="form-group col-md-4">
                        <div class="field_wrapper" id="field_wrapper">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <div class="input-group-text">
                                        <a href="javascript:void(0);" class="add_button" title="<?= TRANS('TO_ATTACH_ANOTHER'); ?>"><i class="fa fa-plus"></i></a>
                                    </div>
                                </div>
                                <!-- <input type="file" class="form-control  " name="anexo[]" /> -->
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input custom-file-input-sm" id="idInputFile" name="anexo[]" id="inputGroupFile01" aria-describedby="inputGroupFileAddon01" lang="br">
                                    <label class="custom-file-label text-truncate" for="inputGroupFile01"><?= TRANS('CHOOSE_FILE'); ?></label>
                                </div>
                            </div>
                        </div>
                    </div>



                    <!-- Prioridade -->
                    <label for="idPrioridade" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('OCO_PRIORITY'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control " id="idPrioridade" name="prioridade">
                            <?php
                            $priorities = getPriorities($conn);
                            foreach ($priorities as $priority) {
                            ?>
                                <option value="<?= $priority['pr_cod']; ?>" <?= ($priority['pr_cod'] == $row['oco_prior'] ? " selected" : ""); ?>><?= $priority['pr_desc']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>


                    <?php

                    $habilita = "disabled";
                    if ($row['contato_email'] && !empty($row['contato_email'])) {
                        $habilita = "";
                    }

                    /* Só exibirá as opçoes de envio caso o envio de emails esteja habilitado no sistema */
                    if ($mailConfig['mail_send']) {
                    ?>
                        <label class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('OCO_FIELD_SEND_MAIL_TO'); ?></label>
                        <div class="form-group col-md-4">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input " type="checkbox" name="mailAR" value="ok" id="defaultCheck1" checked>
                                <legend class="col-form-label col-form-label-sm"><?= TRANS('RESPONSIBLE_AREA'); ?></legend>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input " type="checkbox" name="mailOP" value="ok" id="mailOP">
                                <legend class="col-form-label col-form-label-sm"><?= TRANS('TECHNICIAN'); ?></legend>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input " type="checkbox" name="mailUS" value="ok" <?= $habilita; ?> id="mailUS">
                                <legend class="col-form-label col-form-label-sm"><?= TRANS('CONTACT'); ?></legend>
                            </div>
                        </div>
                    <?php
                    }



                    if ($closed) {
                        $oldStatus = 4;
                        $data_fechamento = dateScreen($row['data_fechamento']);
                    ?>
                        <label for="data_fechamento" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_DATE_CLOSING'); ?></label>
                        <div class="form-group col-md-4">
                            <input type="text" class="form-control  " readonly id="data_fechamento" name="data_fechamento" value="<?= $data_fechamento; ?>" />
                        </div>
                    <?php
                    }



                    $os_DataAbertura = dateScreen($row['data_abertura']);
                    ?>
                    <label for="data_abertura" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('OPENING_DATE'); ?></label>
                    <div class="form-group col-md-4">
                        <input type="text" class="form-control  " readonly id="data_abertura" name="data_abertura" value="<?= $os_DataAbertura; ?>" />
                    </div>

                    <div class="w-100"></div>

                    <label for="idAssentamento" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('TICKET_ENTRY'); ?><br /><span><input type="checkbox" name="check_asset_privated" value="1">&nbsp;<?= TRANS('CHECK_ASSET_PRIVATED'); ?></span></label>
                    <div class="form-group col-md-10">
                        <textarea class="form-control " id="idAssentamento" name="assentamento" required rows="4" placeholder="<?= TRANS('PLACEHOLDER_ASSENT'); ?>"></textarea>
                        <small class="form-text text-muted">
                            <?= TRANS('ENTRY_HELPER'); ?>.
                        </small>
                    </div>

                    <?php
                    if (!$hadFirstResponse) {
                    ?>
                        <label class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIRST_RESPONSE'); ?></label>
                        <div class="form-group col-md-10">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input " type="checkbox" name="resposta" value="ok" id="idResposta" checked>
                                <legend class="col-form-label col-form-label-sm"><?= TRANS('HNT_NOT_MARK_OPT_FIRST_REPLY_CALL'); ?></legend>
                            </div>
                        </div>
                    <?php
                    }

                    $able = "";
                    // if ($closed || $isDone || $isRejected) {
                    if ($closed || $isDone) {
                        $able = " disabled";
                    }
                    ?>

                    <label for="idStatus" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_STATUS'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control  " id="idStatus" name="status" <?= $able; ?>>
                            <?php
                            if ($row['status'] == 4) {
                                $listStatus = getStatus($conn, 0, '1,2,3', '0,1');
                            } else {
                                $listStatus = getStatus($conn, 0, '1,2,3', '0,1', $hiddenStatus);
                            }

                            foreach ($listStatus as $status) {
                            ?>
                                <option value="<?= $status['stat_id']; ?>" <?= ($status['stat_id'] == $row['status'] ? " selected" : ""); ?>><?= $status['status']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                        <?php
                            // if ($closed || $isDone || $isRejected) {
                            if ($closed || $isDone) {
                                ?>
                                    <input type="hidden" name="status" value="<?= $row['status']; ?>">
                                <?php
                            }
                        ?>
                    </div>

                    <!-- Canal de atendimento -->
                    <?php
                    $restrictChannel = false;
                    if ($row['oco_channel']) {
                        $restrictChannel = (isSystemChannel($conn, $row['oco_channel']) ? true : false);
                    }
                    ?>
                    <label for="channel" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('OPENING_CHANNEL'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control  " id="channel" name="channel">
                            <?php
                            $channels = ($restrictChannel ? getChannels($conn, $row['oco_channel']) : getChannels($conn, null, 'open'));
                            if ($restrictChannel) {
                            ?>
                                <option value="<?= $channels['id']; ?>"><?= $channels['name']; ?></option>
                            <?php
                            } else
                                foreach ($channels as $channel) {
                                    print "<option value=" . $channel["id"] . "";
                                    if ($channel['id'] == $row['oco_channel']) {
                                        print " selected";
                                    }
                                    print ">" . $channel["name"] . "</option>";
                                }
                            ?>
                        </select>
                    </div>
                    <?php




                    /* Campos personalizados */
                    $labelColSize = 2;
                    $fieldColSize = 4;
                    $fieldRowSize = 10;
                    $custom_fields = getCustomFields($conn, null, 'ocorrencias');

                    if (!empty($custom_fields) && (!$sysConfig['conf_cfield_only_opened'] || hasCustomFields($conn, $row['numero']))) {
                    ?>
                        <div class="w-100">
                            <p class="h6 text-center font-weight-bold mt-2 mb-4 text-secondary"><?= TRANS('EXTRA_FIELDS'); ?></p>
                        </div>
                        <?php
                    }

                    foreach ($custom_fields as $cfield) {
                        
                        $maskType = ($cfield['field_mask_regex'] ? 'regex' : 'mask');
                    	$fieldMask = "data-inputmask-" . $maskType . "=\"" . $cfield['field_mask'] . "\"";
                        $inlineAttributes = keyPairsToHtmlAttrs($cfield['field_attributes']);
                        $field_value = getTicketCustomFields($conn, $row['numero'], $cfield['id']);

                        /* Controle de acordo com a opção global conf_cfield_only_opened */
                        if (!$sysConfig['conf_cfield_only_opened'] || !empty($field_value['field_id'])) {
                        ?>
                            <?= ($cfield['field_type'] == 'textarea' ? '<div class="w-100"></div>'  : ''); ?>
                            <label for="<?= $cfield['field_name']; ?>" class="col-sm-<?= $labelColSize; ?> col-md-<?= $labelColSize; ?> col-form-label col-form-label-sm text-md-right " title="<?= $cfield['field_title']; ?>" data-pop="popover" data-placement="top" data-trigger="hover" data-content="<?= $cfield['field_description']; ?>"><?= $cfield['field_label']; ?></label>
                            <div class="form-group col-md-<?= ($cfield['field_type'] == 'textarea' ? $fieldRowSize  : $fieldColSize); ?>">
                                <?php
                                if ($cfield['field_type'] == 'select') {
                                ?>
                                    <select class="form-control custom_field_select" name="<?= $cfield['field_name']; ?>" id="<?= $cfield['field_name']; ?>" <?= $inlineAttributes; ?>>
                                        <?php

                                        $options = [];
                                        $options = getCustomFieldOptionValues($conn, $cfield['id']);
                                        ?>
                                        <option value=""><?= TRANS('SEL_SELECT'); ?></option>
                                        <?php
                                        foreach ($options as $cfieldValues) {
                                        ?>
                                            <option value="<?= $cfieldValues['id']; ?>" <?= ($cfieldValues['id'] == $field_value['field_value_idx'] ? " selected" : ""); ?>><?= $cfieldValues['option_value']; ?></option>
                                        <?php
                                        }
                                        ?>
                                    </select>
                                <?php
                                } elseif ($cfield['field_type'] == 'select_multi') {
                                ?>
                                    <select class="form-control custom_field_select_multi" name="<?= $cfield['field_name']; ?>[]" id="<?= $cfield['field_name']; ?>" multiple="multiple" <?= $inlineAttributes; ?>>
                                        <?php

                                        $options = [];
                                        $options = getCustomFieldOptionValues($conn, $cfield['id']);
                                        $defaultSelections = explode(',', (string)$field_value['field_value_idx']);

                                        ?>
                                        <option value=""><?= TRANS('SEL_SELECT'); ?></option>
                                        <?php
                                        foreach ($options as $cfieldValues) {
                                        ?>
                                            <option value="<?= $cfieldValues['id']; ?>" <?= (in_array($cfieldValues['id'], $defaultSelections) ? ' selected' : ''); ?>><?= $cfieldValues['option_value']; ?></option>
                                        <?php
                                        }
                                        ?>
                                    </select>
                                <?php
                                } elseif ($cfield['field_type'] == 'number') {
                                ?>
                                    <input class="form-control custom_field_number" type="number" name="<?= $cfield['field_name']; ?>" id="<?= $cfield['field_name']; ?>" value="<?= $field_value['field_value']; ?>" placeholder="<?= $cfield['field_placeholder']; ?>" <?= $inlineAttributes; ?>>
                                <?php
                                } elseif ($cfield['field_type'] == 'checkbox') {
                                    $checked_checkbox = ($field_value['field_value'] == "on" ? " checked" : "");
                                ?>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input custom_field_checkbox" type="checkbox" name="<?= $cfield['field_name']; ?>" id="<?= $cfield['field_name']; ?>" <?= $checked_checkbox; ?> placeholder="<?= $cfield['field_placeholder']; ?>" <?= $inlineAttributes; ?>>
                                        <legend class="col-form-label col-form-label-sm"><?= $cfield['field_placeholder']; ?></legend>
                                    </div>
                                <?php
                                } elseif ($cfield['field_type'] == 'textarea') {
                                ?>
                                    <textarea class="form-control custom_field_textarea" name="<?= $cfield['field_name']; ?>" id="<?= $cfield['field_name']; ?>" placeholder="<?= $cfield['field_placeholder']; ?>" <?= $inlineAttributes; ?>><?= $field_value['field_value']; ?></textarea>
                                <?php
                                } elseif ($cfield['field_type'] == 'date') {
                                ?>
                                    <input class="form-control custom_field_date" type="text" name="<?= $cfield['field_name']; ?>" id="<?= $cfield['field_name']; ?>" value="<?= dateScreen($field_value['field_value'], 1); ?>" placeholder="<?= $cfield['field_placeholder']; ?>" <?= $inlineAttributes; ?> autocomplete="off">
                                <?php
                                } elseif ($cfield['field_type'] == 'time') {
                                ?>
                                    <input class="form-control custom_field_time" type="text" name="<?= $cfield['field_name']; ?>" id="<?= $cfield['field_name']; ?>" value="<?= $field_value['field_value']; ?>" placeholder="<?= $cfield['field_placeholder']; ?>" <?= $inlineAttributes; ?> autocomplete="off">
                                <?php
                                } elseif ($cfield['field_type'] == 'datetime') {
                                ?>
                                    <input class="form-control custom_field_datetime" type="text" name="<?= $cfield['field_name']; ?>" id="<?= $cfield['field_name']; ?>" value="<?= dateScreen($field_value['field_value'], 0, 'd/m/Y H:i'); ?>" placeholder="<?= $cfield['field_placeholder']; ?>" <?= $inlineAttributes; ?> autocomplete="off">
                                <?php
                                } else {
                                ?>
                                    <input class="form-control custom_field_text" type="text" name="<?= $cfield['field_name']; ?>" id="<?= $cfield['field_name']; ?>" value="<?= $field_value['field_value']; ?>" placeholder="<?= $cfield['field_placeholder']; ?>" <?= $fieldMask; ?> <?= $inlineAttributes; ?> autocomplete="off">
                                <?php
                                }
                                ?>
                            </div>

                    <?php
                            /* Fim do controle de acordo com a configuração global */
                        }
                    }
                    ?>
                    <div class="w-100"></div>
                    <?php
                    /* Fim dos campos personalizados */






                    /* $colLabel = "col-sm-2 text-md-right font-weight-bold p-2";
                    $colsDefault = "small text-break border-bottom rounded p-2 bg-white"; */ /* border-secondary */
                    $colLabel = "col-sm-2 text-md-right font-weight-bold p-2";
                    $colsDefault = " text-break p-2 bg-white"; /* border-secondary */
                    $colContent = $colsDefault . " col-sm-3 col-md-3";
                    $colContentLine = $colsDefault . " col-sm-9";

                    /* ABAS */

                    $classDisabledAssent = ($assentamentos > 0 ? '' : ' disabled');
                    $ariaDisabledAssent = ($assentamentos > 0 ? '' : ' true');
                    $classDisabledFiles = ($hasFiles > 0 ? '' : ' disabled');
                    $ariaDisabledFiles = ($hasFiles > 0 ? '' : ' true');
                    $classDisabledSubs = ($hasRelatives > 0 ? '' : ' disabled');
                    $ariaDisabledSubs = ($hasRelatives > 0 ? '' : ' true');

                    ?>
                    <div class="row my-2 w-100">
                        <div class="<?= $colLabel; ?> my-auto"><span class="badge badge-danger oc-cursor " data-toggle="collapse" data-target="#divListagens" data-pop="popover" data-placement="top" data-content="<?= TRANS('SHOW_HIDE_LISTS'); ?>" data-trigger="hover" id="oc_plus_minus"><i class="fas fa-minus"></i></span><!-- <button class="btn btn-oc-wine" type="button" data-toggle="collapse" data-target="#divListagens">Teste de Collapse</button> -->
                        </div>
                        <div class="<?= $colContentLine; ?>">
                            <ul class="nav nav-pills " id="pills-tab" role="tablist">
                                <li class="nav-item" role="assentamentos">
                                    <a class="nav-link active <?= $classDisabledAssent; ?>" id="divAssentamentos-tab" data-toggle="pill" href="#divAssentamentos" role="tab" aria-controls="divAssentamentos" aria-selected="true" aria-disabled="<?= $ariaDisabledAssent; ?>"><i class="fas fa-comment-alt"></i>&nbsp;<?= TRANS('TICKET_ENTRIES'); ?>&nbsp;<span class="badge badge-light"><?= $assentamentos; ?></span></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?= $classDisabledFiles; ?>" id="divFiles-tab" data-toggle="pill" href="#divFiles" role="tab" aria-controls="divFiles" aria-selected="true" aria-disabled="<?= $ariaDisabledFiles; ?>"><i class="fas fa-paperclip"></i>&nbsp;<?= TRANS('FILES'); ?>&nbsp;<span class="badge badge-light"><?= $hasFiles; ?></span></a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?= $classDisabledSubs; ?>" id="divSubs-tab" data-toggle="pill" href="#divSubs" role="tab" aria-controls="divSubs" aria-selected="true" aria-disabled="<?= $ariaDisabledSubs; ?>"><i class="fas fa-stream"></i>&nbsp;<?= TRANS('TICKETS_REFERENCED'); ?>&nbsp;<span class="badge badge-light"><?= $hasRelatives; ?></span></a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <!-- FINAL DAS ABAS -->



                    <div class="container collapse show" id="divListagens">
                        <div class="tab-content" id="pills-tabContent">
                            <?php
                            /* LISTAGEM DE ASSENTAMENTOS */
                            $printCont = 0;
                            if ($assentamentos) {
                            ?>
                                <div class="tab-pane fade show active" id="divAssentamentos" role="tabpanel" aria-labelledby="divAssentamentos-tab">
                                    <div class="row ">

                                        <div class="col-sm-12 border-bottom rounded p-0 bg-white " id="assentamentos">
                                            <!-- collapse -->
                                            <table class="table  table-hover table-striped rounded">
                                                <!-- table-responsive -->
                                                <thead class="text-white" style="background-color: #48606b;">
                                                    <tr>
                                                        <th scope="col"><?= TRANS('CHECK_ASSET_PRIVATED'); ?></th>
                                                        <th scope="col"><?= TRANS('AUTHOR'); ?></th>
                                                        <th scope="col"><?= TRANS('DATE'); ?></th>
                                                        <th scope="col"><?= TRANS('COL_TYPE'); ?></th>
                                                        <th scope="col"><?= TRANS('TICKET_ENTRY'); ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    // $printCont = 0;
                                                    $i = 1;
                                                    foreach ($entries as $rowAsset) {
                                                        $printCont++;
                                                        $transAssetText = "";
                                                        $checked = "";
                                                        if ($rowAsset['asset_privated'] == 1) {
                                                            $transAssetText = TRANS('CHECK_ASSET_PRIVATED');
                                                            $checked = " checked";
                                                        } else $transAssetText = "";
                                                        $author = $rowAsset['nome'] ?? TRANS('AUTOMATIC_PROCESS');
                                                    ?>
                                                        <tr>
                                                            <!-- <th scope="row"><?= $i; ?></th> -->
                                                            <th><input type="checkbox" name="asset<?= $printCont; ?>" value="<?= $rowAsset['numero']; ?>" <?= $checked; ?>></th>
                                                            <td><?= $author; ?></td>
                                                            <td><?= formatDate($rowAsset['data']); ?></td>
                                                            <td><?= getEntryType($rowAsset['tipo_assentamento']); ?></td>
                                                            <td><?= nl2br($rowAsset['assentamento']); ?></td>
                                                        </tr>
                                                    <?php
                                                        $i++;
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php
                            }
                            /* FINAL DA LISTAGEM DE ASSENTAMENTOS */


                            /* TRECHO PARA EXIBIÇÃO DA LISTAGEM DE ARQUIVOS ANEXOS */
                            $cont = 0;
                            if ($hasFiles) {
                            ?>
                                <div class="tab-pane fade" id="divFiles" role="tabpanel" aria-labelledby="divFiles-tab">
                                    <div class="row my-2">

                                        <div class="col-sm-12 border-bottom rounded p-0 bg-white " id="files">
                                            <!-- collapse -->
                                            <table class="table  table-hover table-striped rounded">
                                                <!-- table-responsive -->
                                                <!-- <thead class="bg-secondary text-white"> -->
                                                <thead class=" text-white" style="background-color: #48606b;">
                                                    <tr>
                                                        <th scope="col">#</th>
                                                        <th scope="col"><?= TRANS('COL_TYPE'); ?></th>
                                                        <th scope="col"><?= TRANS('SIZE'); ?></th>
                                                        <th scope="col"><?= TRANS('FILE'); ?></th>
                                                        <th scope="col"><?= TRANS('REMOVE'); ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $i = 1;
                                                    // $cont = 0;
                                                    foreach ($files as $rowFiles) {
                                                        $cont++;
                                                        $size = round($rowFiles['img_size'] / 1024, 1);
                                                        $rowFiles['img_tipo'] . "](" . $size . "k)";

                                                        if (isImage($rowFiles["img_tipo"])) {

                                                            $viewImage = "&nbsp;<a onClick=\"javascript:popupWH('../../includes/functions/showImg.php?" .
                                                                "file=" . $row['numero'] . "&cod=" . $rowFiles['img_cod'] . "'," . $rowFiles['img_largura'] . "," . $rowFiles['img_altura'] . ")\" " .
                                                                "title='" . TRANS('VIEW') . "'><i class='fa fa-search'></i></a>";
                                                        } else {
                                                            $viewImage = "";
                                                        }
                                                    ?>
                                                        <tr>
                                                            <th scope="row"><?= $i; ?></th>
                                                            <td><?= $rowFiles['img_tipo']; ?></td>
                                                            <td><?= $size; ?>k</td>
                                                            <td><a onClick="redirect('../../includes/functions/download.php?file=<?= $numero; ?>&cod=<?= $rowFiles['img_cod']; ?>')" title="Download the file"><?= $rowFiles['img_nome']; ?></a><?= $viewImage; ?></i></td>
                                                            <td><input type="checkbox" name="delImg[<?= $cont; ?>]" value="<?= $rowFiles['img_cod']; ?>">&nbsp;<span class="align-top"><i class="fas fa-trash-alt text-danger"></i></span></td>

                                                        </tr>
                                                    <?php
                                                        $i++;
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php
                            }
                            /* FINAL DO TRECHO DE LISTAGEM DE ARQUIVOS ANEXOS*/

                            // LISTAGEM DE CHAMADOS VINCULADOS (PAI OU FILHOS)
                            $contSub = 0;
                            if ($hasRelatives) {
                            ?>
                                <div class="tab-pane fade" id="divSubs" role="tabpanel" aria-labelledby="divSubs-tab">
                                    <div class="row my-2">

                                        <div class="col-sm-12 border-bottom rounded p-0 bg-white " id="subs">
                                            <!-- collapse -->
                                            <table class="table  table-hover table-striped rounded">
                                                <!-- table-responsive -->
                                                <!-- <thead class="bg-secondary text-white"> -->
                                                <thead class=" text-white" style="background-color: #48606b;">
                                                    <tr>
                                                        <th scope="col"><?= TRANS('TICKET_NUMBER'); ?></th>
                                                        <th scope="col"><?= TRANS('AREA'); ?></th>
                                                        <th scope="col"><?= TRANS('ISSUE_TYPE'); ?></th>
                                                        <th scope="col"><?= TRANS('CONTACT') . "<br />" . TRANS('COL_PHONE'); ?></th>
                                                        <th scope="col"><?= TRANS('DEPARTMENT') . "<br />" . TRANS('DESCRIPTION'); ?></th>
                                                        <th scope="col"><?= TRANS('FIELD_LAST_OPERATOR') . "<br />" . TRANS('COL_STATUS'); ?></th>
                                                        <th scope="col"><?= TRANS('REMOVE_RELATIONSHIP'); ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $comDeps = false;
                                                    $i = 1;
                                                    $key = "";
                                                    $label = "";
                                                    // $contSub = 0;
                                                    foreach ($relatives as $rowSubPai) {
                                                        $contSub++;
                                                        $key = $rowSubPai['dep_filho'];
                                                        $label = "<span class='badge badge-oc-wine p-2 mt-2'>" . TRANS('CHILD_TICKET') . "</span>";
                                                        // $comDeps = false;
                                                        if ($rowSubPai['dep_pai'] != $numero) {
                                                            $key = $rowSubPai['dep_pai'];
                                                            $label = "<span class='badge badge-oc-teal p-2 mt-2'>" . TRANS('PARENT_TICKET') . "</span>";
                                                        }

                                                        $qryDetail = $QRY["ocorrencias_full_ini"] . " WHERE  o.numero = " . $key . " ";
                                                        $execDetail = $conn->query($qryDetail);
                                                        $rowDetail = $execDetail->fetch();

                                                        $texto = trim(noHtml($rowDetail['descricao']));
                                                        if (strlen((string)$texto) > 200) {
                                                            $texto = substr($texto, 0, 195) . " ..... ";
                                                        };

                                                    ?>
                                                        <!-- <tr onClick="showSubsDetails(<?= $rowDetail['numero']; ?>)" style="cursor: pointer;"> -->
                                                        <tr>
                                                            <th scope="row"><a href="ticket_show.php?numero=<?= $rowDetail['numero']; ?>"><?= $rowDetail['numero']; ?></a>&nbsp;<?= $label; ?></th>
                                                            <td><?= $rowDetail['area']; ?></td>
                                                            <td><?= $rowDetail['problema']; ?></td>
                                                            <td><?= $rowDetail['contato'] . "<br/>" . $rowDetail['telefone']; ?></td>
                                                            <td><?= $rowDetail['setor'] . "<br/>" . $texto; ?></td>
                                                            <td><?= $rowDetail['nome'] . "<br/>" . $rowDetail['chamado_status']; ?></td>
                                                            <td><input type="checkbox" name="delSub[<?= $contSub; ?>]" value="<?= $key ?>" />&nbsp;<span class="align-top"><i class="fas fa-trash-alt text-danger"></i></span></td>
                                                        </tr>
                                                    <?php
                                                        $i++;
                                                    }
                                                    ?>

                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php
                            }
                            ?>
                            <!-- FINAL DA LISTAGEM DE CHAMADOS VINCULADOS -->

                        </div>
                    </div>

                    <?php
                    if ($version4) {
                    ?>
                        <input type="hidden" name="url_process" id="url_process" value="get_issues_by_area4.php" />
                    <?php
                    } else {
                    ?>
                        <input type="hidden" name="url_process" id="url_process" value="get_issues_by_area.php" />
                    <?php
                    }
                    ?>


                    <input type='hidden' name='numero' value='<?= $numero; ?>' />
                    <input type='hidden' name='cont' value='<?= $cont; ?>' />
                    <!-- arquivos -->
                    <input type='hidden' name='contSub' value='<?= $contSub; ?>' />
                    <input type='hidden' name='oldStatus' value='<?= $row['status']; ?>' />
                    <input type='hidden' name='data_atend' value='<?= $row['data_atendimento']; ?>' />
                    <!-- <input type='hidden' name='abertopor' value='<?= $rowmail['user_id']; ?>' /> -->
                    <input type='hidden' name='total_asset' value='<?= $printCont; ?>' />
                    <input type="hidden" name="submit" value="" />

                    <input type="hidden" name="action" value="edit" />



                    <div class="w-100"></div>
                    <div class="form-group col-md-8 d-none d-md-block">
                    </div>
                    <div class="form-group col-12 col-md-2 ">
                        <button type="button" id="idSubmit" class="btn btn-primary btn-block"><?= TRANS('BT_OK'); ?></button>
                    </div>
                    <div class="form-group col-12 col-md-2">
                        <button type="reset" class="btn btn-secondary btn-block" onClick="parent.history.back();"><?= TRANS('BT_CANCEL'); ?></button>
                    </div>
                </div>
            </form>
        </div>

    <?php
    }


    ?>
    <script src="../../includes/javascript/funcoes-3.0.js"></script>
    <script src="../../includes/components/jquery/jquery.js"></script>
    <script src="../../includes/components/jquery/datetimepicker/build/jquery.datetimepicker.full.min.js"></script>
    <script src="../../includes/components/jquery/jquery.initialize.min.js"></script>
    <script src="../../includes/components/bootstrap/js/bootstrap.bundle.js"></script>
    <script src="../../includes/components/jquery/jquery.amsify.suggestags-master/js/jquery.amsify.suggestags.js"></script>
    <script src="../../includes/components/bootstrap-select/dist/js/bootstrap-select.min.js"></script>
    <script src="../../includes/components/Inputmask-5.x/dist/jquery.inputmask.min.js"></script>
    <script src="../../includes/components/Inputmask-5.x/dist/bindings/inputmask.binding.js"></script>
    <script type="text/javascript">
        $(function() {

            $('input[name="input_tags"]').amsifySuggestags({
                type: 'bootstrap',
                defaultTagClass: 'badge bg-primary text-white p-2',
                tagLimit: 20,
                printValues: false,
                showPlusAfter: 10,

                suggestionsAction: {

                    timeout: 5,
                    minChars: 2,
                    minChange: -1,
                    delay: 100,
                    type: 'POST',
                    // url : '/ocomon/ocomon-desenv/ocomon/geral/tag_suggestions.php',
                    url: './tag_suggestions.php',
                    beforeSend: function() {
                        // console.info('beforeSend');
                    },
                    success: function(data) {
                        // console.info(data);
                    },
                    error: function() {
                        // console.info('error');
                    },
                    complete: function(data) {
                        // console.info('complete');
                    }
                }
            });


            var maxField = <?= $sysConfig['conf_qtd_max_anexos']; ?>; //Input fields increment limitation
            var addButton = $('.add_button'); //Add button selector
            var wrapper = $('.field_wrapper'); //Input field wrapper

            var fieldHTML = '<div class="input-group my-1 d-block"><div class="input-group-prepend"><div class="input-group-text"><a href="javascript:void(0);" class="remove_button"><i class="fa fa-minus"></i></a></div><div class="custom-file"><input type="file" class="custom-file-input" name="anexo[]"  aria-describedby="inputGroupFileAddon01" lang="br"><label class="custom-file-label text-truncate" for="inputGroupFile01"><?= TRANS('CHOOSE_FILE'); ?></label></div></div></div></div>';

            var x = 1; //Initial field counter is 1

            //Once add button is clicked
            $(addButton).click(function() {
                //Check maximum number of input fields
                if (x < maxField) {
                    x++; //Increment field counter
                    $(wrapper).append(fieldHTML); //Add field html
                }
            });

            //Once remove button is clicked
            $(wrapper).on('click', '.remove_button', function(e) {
                e.preventDefault();
                $(this).parent('div').parent('div').parent('div').remove(); //Remove field html
                x--; //Decrement field counter
            });

            // console.log('idProblema: ' + $("#idProblema").val());
            // console.log('selected_issue: ' + $("#selected_issue").val());


 			loadUnits();

			$("#client").on('change', function() {
				loadUnits();
			});

			$("#idUnidade").on('change', function() {
				loadDepartments();
			});
            
            
            showIssuesByArea($('#selected_issue').val() ?? '');

            if ($("#idProblema").length > 0) {
                showSelectedIssue();
                showIssueDescription($("#idProblema").val());
            }

            /* Load issues type and operators */
            if ($("#idArea").length > 0) {
                $("#idArea").off().on("change", function() {
                    showIssuesByArea($('#idProblema').val() ?? '');
                    if ($('#idFoward').length > 0) {
                        loadOperators();
                    }
                    if ($("#idProblema").length > 0) {
                        showSelectedIssue();
                        showIssueDescription($("#idProblema").val());
                    }
                    if ($('#mailOP').length > 0)
                        $('#mailOP').prop('disabled', true).prop('checked', false);
                });
            }

            /* Show selected issue */
            if ($("#idProblema").length > 0) {
                $("#idProblema").off().on("change", function() {
                    showSelectedIssue();
                    showIssueDescription($("#idProblema").val());
                });
            }

            if ($("#idProblema").length > 0) {
                /* Adicionei o mutation observer em função dos elementos que são adicionados após o carregamento do DOM */
                var obsRadio = $.initialize(".radio_prob", function() {
                    $(".radio_prob").off().on('click', function() {
                        showIssueDescription($(this).val());
                    });
                }, {
                    target: document.getElementById('form')
                }); /* o target limita o scopo do observer */
            }


            if ($("#load_department").length > 0) {

                $("#load_department").on('click', function() {
                    loadDepartment();
                });
            }

            if ($('#idFoward').length > 0) {
                $('#idFoward').on('change', function() {
                    toogleMailOperator();
                });
            }



            /* Autocompletar os nomes dos contatos */
            if ($('#contatos').length > 0) {
                $.ajax({
                    url: './get_contacts_names.php',
                    method: 'POST',
                    dataType: 'json',
                }).done(function(response) {
                    for (var i in response) {
                        var option = '<option value="' + response[i].contato + '"/>';
                        $('#contatos').append(option);
                    }
                });
            }

            /* Autocompletar os emails dos contatos */
            if ($('#contatos_emails').length > 0) {
                $.ajax({
                    url: './get_contacts_emails.php',
                    method: 'POST',
                    dataType: 'json',
                }).done(function(response) {
                    for (var i in response) {
                        var option = '<option value="' + response[i].contato_email + '"/>';
                        $('#contatos_emails').append(option);
                    }
                });
            }

            if ($('#contato_email').length > 0) {
                $('#contato_email').on('blur', function() {
                    if ($('#contato_email').val() != '') {
                        $('#mailUS').prop('disabled', false);
                    } else {
                        $('#mailUS').prop('disabled', true).prop('checked', false);
                    }
                });
            }


            $('input, select, textarea').on('blur', function() {
                if ($(this).val() != '') {
                    $(this).removeClass('is-invalid');
                }
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

                // for (instance in CKEDITOR.instances) {
                // 	CKEDITOR.instances[instance].updateElement();
                // }

                var form = $('form').get(0);
                // disabled the submit button
                $("#idSubmit").prop("disabled", true);

                $.ajax({
                    url: './tickets_process.php',
                    method: 'POST',

                    data: new FormData(form),
                    dataType: 'json',

                    cache: false,
                    processData: false,
                    contentType: false,
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
                        var url = 'ticket_show.php?numero=' + response.numero;
                        $(location).prop('href', url);
                        return true;
                    }
                });
                return false;
            });


            $('#oc_plus_minus').on('click', function() {
                //  console.log($(this).children().prop('class'));
                if ($(this).children().hasClass("fa-minus")) {
                    $(this).children().removeClass('fa-minus');
                    $(this).children().addClass('fa-plus');

                    $(this).removeClass('badge-danger');
                    $(this).addClass('badge-success');
                } else {
                    $(this).children().removeClass('fa-plus');
                    $(this).children().addClass('fa-minus');
                    $(this).removeClass('badge-success');
                    $(this).addClass('badge-danger');
                }
            });

            /* Adicionei o mutation observer em função dos elementos que são adicionados após o carregamento do DOM */
            var obs = $.initialize(".custom-file-input", function() {
                $('.custom-file-input').on('change', function() {
                    let fileName = $(this).val().split('\\').pop();
                    $(this).next('.custom-file-label').addClass("selected").html(fileName);
                });

            }, {
                target: document.getElementById('field_wrapper')
            }); /* o target limita o scopo do observer */


            $(function() {
                $('[data-pop="popover"]').popover({
                    html: true
                })
            });

            $('.popover-dismiss').popover({
                trigger: 'focus'
            });

            /* Para campos personalizados - bind pela classe*/
            $.fn.selectpicker.Constructor.BootstrapVersion = '4';
			$('.custom_field_select_multi').selectpicker({
				/* placeholder */
				title: "<?= TRANS('SEL_SELECT', '', 1); ?>",
				liveSearch: true,
				liveSearchNormalize: true,
				liveSearchPlaceholder: "<?= TRANS('BT_SEARCH', '', 1); ?>",
				noneResultsText: "<?= TRANS('NO_RECORDS_FOUND', '', 1); ?> {0}",
				style: "",
				styleBase: "form-control input-select-multi",
			});

            $('#client').selectpicker({
				/* placeholder */
				title: "<?= TRANS('SEL_SELECT', '', 1); ?>",
				liveSearch: true,
				liveSearchNormalize: true,
				liveSearchPlaceholder: "<?= TRANS('BT_SEARCH', '', 1); ?>",
				noneResultsText: "<?= TRANS('NO_RECORDS_FOUND', '', 1); ?> {0}",
				style: "",
				styleBase: "form-control input-select-multi",
			});

            $('#idProblema').selectpicker({
				/* placeholder */
				title: "<?= TRANS('ISSUE_TYPE', '', 1); ?>",
				liveSearch: true,
				liveSearchNormalize: true,
				liveSearchPlaceholder: "<?= TRANS('BT_SEARCH', '', 1); ?>",
				noneResultsText: "<?= TRANS('NO_RECORDS_FOUND', '', 1); ?> {0}",
				style: "",
				styleBase: "form-control input-select-multi",
			});

            $('#idLocal').selectpicker({
                /* placeholder */
                title: "<?= TRANS('DEPARTMENT', '', 1); ?>",
                liveSearch: true,
                liveSearchNormalize: true,
                liveSearchPlaceholder: "<?= TRANS('BT_SEARCH', '', 1); ?>",
                noneResultsText: "<?= TRANS('NO_RECORDS_FOUND', '', 1); ?> {0}",
                style: "",
                styleBase: "form-control input-select-multi",
            });


            /* Idioma global para os calendários */
            $.datetimepicker.setLocale('pt-BR');
            /* Para campos personalizados - bind pela classe*/
            $('.custom_field_date').datetimepicker({
                timepicker: false,
                format: 'd/m/Y',
                lazyInit: true
            });

            $('.custom_field_datetime').datetimepicker({
                timepicker: true,
                format: 'd/m/Y H:i',
                step: 30,
                // minDate: 0,
                lazyInit: true
            });

            $('.custom_field_time').datetimepicker({
                datepicker: false,
                format: 'H:i',
                step: 30,
                lazyInit: true
            });

        });


        function checa_etiqueta() {
            var inst = document.getElementById('idUnidade');
            var inv = document.getElementById('idEtiqueta');
            if (inst != null && inv != null) {
                if (inst.value == 'null' || !inv.value) {
                    /* var msg = '<?php print TRANS('MSG_UNIT_TAG'); ?>!'
                    window.alert(msg); */
                    $("#divModalDetails").html('<div class="modal-header bg-light"><h5 class="modal-title"><?php print TRANS('WARNING'); ?></h5><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button></div><div class="modal-body"><p><?php print TRANS('FILL_UNIT_TAG'); ?></p></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal"><?php print TRANS('LINK_CLOSE'); ?></button></div>');
                    $('#modalDefault').modal();
                } else {
                    getAssetInfoByUnitAndTag(inst.value, inv.value);
                }
            }
            return false;
        }

        function getAssetInfoByUnitAndTag(unit, tag) {
			$.ajax({
				url: '../../invmon/geral/get_asset_id_by_unit_and_tag.php',
				method: 'POST',
				dataType: 'json',
				data: {
					asset_unit: unit,
					asset_tag: tag,
				},
			}).done(function(response) {
				if (response.success == true) {
					
					if (response.asset_id != '') {
						// popup_alerta_wide('../../invmon/geral/asset_show.php?asset_id=' + response.asset_id);

                        let location = '../../invmon/geral/asset_show.php?asset_id=' + response.asset_id;
                        $("#iframeLoad").attr('src',location)
                        $('#modalIframe').modal();

					} else {
						$('#divResult').html(response.message);
					}
					
				} else {
					$('#divResult').html(response.message);
				}
			});
		}

        /**
         * Funções
         */
        function showIssuesByArea(selected_id = '') {
            /* Exibir os tipos de problemas de acordo com a selecao da área de atendimento */
            if ($('#idProblema').length > 0) {

                var loading = $(".loading");
                $(document).ajaxStart(function() {
                    loading.show();
                });
                $(document).ajaxStop(function() {
                    loading.hide();
                });

                $.ajax({
                    // url: './get_issues_by_area.php',
                    url: $('#url_process').val(),
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        area: $('#idArea').val(),
                        // issue_selected: $('#issue_selected').val() ?? '',
                        issue_selected: $('#idProblema').val() ?? '',
                        real_issue_id: selected_id,
                    },
                }).done(function(response) {
                    $('#idProblema').empty().append('<option value=""><?= TRANS('ISSUE_TYPE'); ?></option>');
                    for (var i in response) {
                        var option = '<option value="' + response[i].prob_id + '">' + response[i].problema + '</option>';
                        $('#idProblema').append(option);

                        if (selected_id !== '') {
                            if ($("#idProblema").find('option[value="' + selected_id + '"]').length === 0) {
                                $('#idProblema').val("").change();
                            } else {
                                $('#idProblema').val(selected_id).change();
                            }
                        } else
                        if ($('#issue_selected').val() != '') {
                            $('#idProblema').val($('#issue_selected').val()).change();
                        }
                    }
                    $('#idProblema').selectpicker('refresh').selectpicker('val', selected_id);
                });
            }
        }

        function showSelectedIssue() {

            if ($('#idProblema').length > 0) {
                var loading = $(".loading");
                $(document).ajaxStart(function() {
                    loading.show();
                });
                $(document).ajaxStop(function() {
                    loading.hide();
                });

                $.ajax({
                    url: './get_issue_detailed.php',
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        area: $('#idArea').val() ?? '',
                        issue_selected: $('#idProblema').val() ?? '',
                    },
                }).done(function(response) {

                    if (response.length > 0) {
                        $('#issueCategories').addClass("form-group col-md-12");
                        $('#issueCategories').empty();

                        var html = '<table class="table table-striped table-hover">';
                        html += '<thead bg-secondary">';
                        html += '<tr class="header">';
                        html += '<td><?= TRANS('ISSUE_TYPE'); ?></td>';
                        html += '<td><?= TRANS('COL_SLA'); ?></td>';
                        html += '<td><?= $sysConfig['conf_prob_tipo_1']; ?></td>';
                        html += '<td><?= $sysConfig['conf_prob_tipo_2']; ?></td>';
                        html += '<td><?= $sysConfig['conf_prob_tipo_3']; ?></td>';
                        html += '</tr>';
                        html += '</thead>';
                        for (var i in response) {
                            html += '<tr>';
                            html += '<td>';
                            html += '<input type="radio" class="radio_prob" id="idRadioProb' + response[i].prob_id + '" name="radio_prob" value="' + response[i].prob_id + '"';
                            if (response[i].prob_id == $("#idProblema").val()) {
                                html += ' checked';
                            } else if (response[i].prob_id == $("#selected_issue").val()) {
                                html += ' checked';
                            }
                            html += '> ';
                            html += response[i].problema;
                            html += '</td>';
                            html += '<td>' + (response[i].slas_desc ?? '') + '</td>';
                            html += '<td>' + (response[i].probt1_desc ?? '') + '</td>';
                            html += '<td>' + (response[i].probt2_desc ?? '') + '</td>';
                            html += '<td>' + (response[i].probt3_desc ?? '') + '</td>';
                            html += '</tr>';
                        }
                        html += '</table>';
                        $('#issueCategories').append(html);
                    } else {
                        $('#issueCategories').removeClass("form-group col-md-12");
                        $('#issueCategories').empty();
                    }
                });
            }
        }


        function showIssueDescription(val) {

            var loading = $(".loading");
            $(document).ajaxStart(function() {
                loading.show();
            });
            $(document).ajaxStop(function() {
                loading.hide();
            });

            $.ajax({
                url: './get_issue_description.php',
                method: 'POST',
                dataType: 'json',
                data: {
                    prob_id: val,
                },
            }).done(function(response) {
                if (response.description != '') {
                    $("#issueDescription").addClass("form-group col-md-12");
                } else {
                    $("#issueDescription").removeClass("form-group col-md-12");
                    $("#issueDescription").empty();
                }
                $("#issueDescription").empty().html(response.description);
            });
        }



	    function loadUnits() {

			if ($("#idUnidade").length > 0) {
				var loading = $(".loading");
				$(document).ajaxStart(function() {
					loading.show();
				});
				$(document).ajaxStop(function() {
					loading.hide();
				});

				$.ajax({
					url: './get_units_by_client.php',
					method: 'POST',
					dataType: 'json',
					data: {
						client: $("#client").val()
					},
				}).done(function(data) {
					$('#idUnidade').empty();
                    $('#idUnidade').append('<option value=""><?= TRANS("SEL_SELECT"); ?></option>');
					$.each(data, function(key, data) {
						$('#idUnidade').append('<option value="' + data.inst_cod + '">' + data.inst_nome + '</option>');
					});

                    let unitDb = $('#unitDb').val();
                    if (unitDb != "") {
                        var found = false;
                        for (i in data) {
                            if (data[i].inst_cod == unitDb) {
                                found = true;
                                $('#idUnidade').val(unitDb);
                                break;
                            }
                        }
                        if (!found) {
                            $('#idUnidade').val("");
                        }
                    } else
                    {
                        $('#idUnidade').val("");
                    }
					loadDepartments();
				});
			} else if ($("#idLocal").length > 0) {
				loadDepartments();
			}
		}

		function loadDepartments() {

			if ($("#idLocal").length > 0) {
				var loading = $(".loading");
				$(document).ajaxStart(function() {
					loading.show();
				});
				$(document).ajaxStop(function() {
					loading.hide();
				});

				$.ajax({
					url: './get_departments_by_client_unit.php',
					method: 'POST',
					dataType: 'json',
					data: {
						client: $("#client").val(),
						unit: $("#idUnidade").val()
					},
				}).done(function(data) {
					$('#idLocal').empty();
					if (Object.keys(data).length > 1) {
						$('#idLocal').append('<option value=""><?= TRANS("SEL_SELECT"); ?></option>');
					}
					$.each(data, function(key, data) {

						let unit = "";
						if (data.unidade != null) {
							unit = ' (' + data.unidade + ')';
						}
						$('#idLocal').append('<option value="' + data.loc_id + '">' + data.local + unit + '</option>');
					});
					$('#idLocal').selectpicker('refresh');
					$('#idLocal').selectpicker('val', $("#localDb").val());
				});
			}
		}


        function loadDepartment() {
            var loading = $(".loading");
            $(document).ajaxStart(function() {
                loading.show();
            });
            $(document).ajaxStop(function() {
                loading.hide();
            });

            $.ajax({
                url: './get_department_by_unit_and_tag.php',
                method: 'POST',
                dataType: 'json',
                data: {
                    unit: $("#idUnidade").val(),
                    tag: $("#idEtiqueta").val()
                },
            }).done(function(response) {
                if (response.department != "") {
                    $('#idLocal').val(response.department).change();
                    $('#idLocal').selectpicker('refresh');
                }
            });
        }


        function loadOperators() {
            var loading = $(".loading");
            $(document).ajaxStart(function() {
                loading.show();
            });
            $(document).ajaxStop(function() {
                loading.hide();
            });

            $.ajax({
                url: './get_operators_by_area.php',
                method: 'POST',
                dataType: 'json',
                data: {
                    area: $("#idArea").val(),
                },
            }).done(function(response) {
                // console.log(response);
                $('#idFoward').empty().append('<option value=""><?= TRANS('FORWARD_TICKET_TO'); ?></option>');
                for (var i in response) {

                    var selected = "";
                    if (response[i].user_id == "<?= $_SESSION['s_uid']; ?>") {
                        selected = " selected";
                    }
                    var option = '<option value="' + response[i].user_id + '"' + selected + '>' + response[i].nome + '</option>';
                    $('#idFoward').append(option);
                }
            });
        }


        function toogleMailOperator() {
            if ($('#idFoward').length > 0) {
                if ($("#idFoward").val() != '') {

                    if ($('#mailOP').length > 0)
                        $('#mailOP').prop('disabled', false);
                } else {
                    if ($('#mailOP').length > 0)
                        $('#mailOP').prop('disabled', true).prop('checked', false);
                }
            }
        }


        function checa_chamados() {
            var inst = document.getElementById('idUnidade');
            var inv = document.getElementById('idEtiqueta');
            if (inst != null && inv != null) {
                if (inst.value == 'null' || !inv.value) {
                    $("#divModalDetails").html('<div class="modal-header bg-light"><h5 class="modal-title"><?php print TRANS('WARNING'); ?></h5><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button></div><div class="modal-body"><p><?php print TRANS('FILL_UNIT_TAG'); ?></p></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal"><?php print TRANS('LINK_CLOSE'); ?></button></div>');
                    $('#modalDefault').modal();
                } else {

                    // $("#divModalDetails").load('./get_tickets_by_unit_and_tag.php?unit=' + inst.value + '&tag=' + inv.value);
                    // $('#modalDefault').modal();

                    let location = './get_tickets_by_unit_and_tag.php?unit=' + inst.value + '&tag=' + inv.value;
                    $("#iframeLoad").attr('src',location)
                    $('#modalIframe').modal();
                }
            }
                    
            return false;
        }


        function checa_por_local() {
            //var local = document.form.local.value;
            var local = document.getElementById('idLocal');
            if (local != null) {
                if (local.value == -1) {

                    $("#divModalDetails").html('<div class="modal-header bg-light"><h5 class="modal-title"><?php print TRANS('WARNING'); ?></h5><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button></div><div class="modal-body"><p><?php print TRANS('FILL_LOCATION'); ?></p></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal"><?php print TRANS('LINK_CLOSE'); ?></button></div>');
                    $('#modalDefault').modal();
                } else
                    popup_alerta('../../invmon/geral/equipments_list.php?comp_local=' + local.value + '&popup=' + true);
            }
            return false;
        }

        function showSubsDetails(cod) {
            $("#divModalDetails").load('ticket_show.php?numero=' + cod);
            $('#modalDefault').modal();
        }
    </script>
</body>

</html>