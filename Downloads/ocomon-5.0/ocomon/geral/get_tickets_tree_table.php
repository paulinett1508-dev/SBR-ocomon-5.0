<?php session_start();
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
*/

if (!isset($_SESSION['s_logado']) || $_SESSION['s_logado'] == 0) {
    $_SESSION['session_expired'] = 1;
    echo "<script>top.window.location = '../../index.php'</script>";
    exit;
}

require_once __DIR__ . "/" . "../../includes/include_basics_only.php";
require_once __DIR__ . "/" . "../../includes/classes/ConnectPDO.php";
require_once __DIR__ . "/" . "../../includes/classes/worktime/Worktime.php";
include_once __DIR__ . "/" . "../../includes/functions/getWorktimeProfile.php";

$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 2, 1);

use includes\classes\ConnectPDO;
$conn = ConnectPDO::getInstance();

$uareas = $_SESSION['s_uareas'];
$post = (isset($_POST) ? $_POST : '');

$imgsPath = "../../includes/imgs/";
$iconFrozen = "<span class='text-oc-teal' title='" . TRANS('HNT_TIMER_STOPPED') . "'><i class='fas fa-pause fa-lg'></i></span>";
$iconOutOfWorktime = "<span class='text-oc-teal' title='" . TRANS('HNT_TIMER_OUT_OF_WORKTIME') . "'><i class='fas fa-pause fa-lg'></i></i></span>";
$iconTicketClosed = "<span class='text-oc-teal' title='" . TRANS('HNT_TICKET_CLOSED') . "'><i class='fas fa-check fa-lg'></i></i></span>";
$config = getConfig($conn);
$percLimit = $config['conf_sla_tolerance']; 

$calc_slas = (isset($post['calc_slas']) && $post['calc_slas'] == 'on' ? true : false);

$options = [
    'area' => [
        'label' => TRANS('SERVICE_AREA'),
        'table' => 'sistemas',
        'field_id' => 'sis_id',
        'field_name' => 'sistema',
        'table_reference' => 'ocorrencias',
        'table_reference_alias' => 'o',
        'field_reference' => 'sistema',
        'sql_alias' => 'o.sistema',
        'alias' => 'ar',
        'value' => ''
    ],
    'status' => [
        'label' => TRANS('COL_STATUS'),
        'table' => 'status',
        'field_id' => 'stat_id',
        'field_name' => 'status',
        'table_reference' => 'ocorrencias',
        'table_reference_alias' => 'o',
        'field_reference' => 'status',
        'sql_alias' => 'o.status',
        'alias' => 'st',
        'value' => ''
    ],
    'client' => [
        'label' => TRANS('CLIENT'),
        'table' => 'clients',
        'field_id' => 'id',
        'field_name' => 'nickname',
        'table_reference' => 'ocorrencias',
        'table_reference_alias' => 'o',
        'field_reference' => 'client',
        'sql_alias' => 'cl.id',
        'alias' => 'cl',
        'value' => ''
    ],
    'requester_area' => [
        'label' => TRANS('REQUESTER_AREA'),
        'table' => 'sistemas',
        'field_id' => 'sis_id',
        'field_name' => 'sistema',
        'table_reference' => 'usuarios',
        'table_reference_alias' => 'ua',
        'field_reference' => 'AREA',
        'sql_alias' => 'asol.sis_id',
        'alias' => 'uar',
        'value' => ''
    ],
    'priority' => [
        'label' => TRANS('COL_PRIORITY'),
        'table' => 'prior_atend',
        'field_id' => 'pr_cod',
        'field_name' => 'pr_desc',
        'table_reference' => 'ocorrencias',
        'table_reference_alias' => 'o',
        'field_reference' => 'oco_prior',
        'sql_alias' => 'o.oco_prior',
        'alias' => 'pr',
        'value' => ''
    ],
    'issue_type' => [
        'label' => TRANS('ISSUE_TYPE'),
        'table' => 'problemas',
        'field_id' => 'prob_id',
        'field_name' => 'problema',
        'table_reference' => 'ocorrencias',
        'table_reference_alias' => 'o',
        'field_reference' => 'problema',
        'sql_alias' => 'p.prob_id',
        'alias' => 'prob',
        'value' => ''
    ],
    'department' => [
        'label' => TRANS('DEPARTMENT'),
        'table' => 'localizacao',
        'field_id' => 'loc_id',
        'field_name' => 'local',
        'table_reference' => 'ocorrencias',
        'table_reference_alias' => 'o',
        'field_reference' => 'local',
        'sql_alias' => 'l.loc_id',
        'alias' => 'loc',
        'value' => ''
    ],
    'unit' => [
        'label' => TRANS('COL_UNIT'),
        'table' => 'instituicao',
        'field_id' => 'inst_cod',
        'field_name' => 'inst_nome',
        'table_reference' => 'ocorrencias',
        'table_reference_alias' => 'o',
        'field_reference' => 'instituicao',
        'sql_alias' => 'i.inst_cod',
        'alias' => 'un',
        'value' => ''
    ],
    'opened_by' => [
        'label' => TRANS('OPENED_BY'),
        'table' => 'usuarios',
        'field_id' => 'user_id',
        'field_name' => 'nome',
        'table_reference' => 'ocorrencias',
        'table_reference_alias' => 'o',
        'field_reference' => 'aberto_por',
        'sql_alias' => 'o.aberto_por',
        'alias' => 'ua',
        'value' => ''
    ]
];


if (!empty($post)) {

    /* Níveis possíveis de agrupamento */
    $groups = [
        'group_1' => '',
        'group_2' => '',
        'group_3' => '',
        'group_4' => '',
        'group_5' => '',
    ];

    foreach ($groups as $key => $group) {
        if (!empty($post[$key])) {
            $groups[$key] = $post[$key];
        } else {
            unset($groups[$key]);
        }
    }

    $table_id = $post['params'];
    $params = [];
    if (isset($post['params']) && !empty($post['params'])) {

        /* Tratamento quando o último parâmetro for de valor nulo */
        if (substr($post['params'], -1) == '-') {
            $post['params'] .= '0';
        }
        $params = explode('--', str_replace('---','-0--', $post['params']));
    }


    $tmp = [];
    /** Adicionando o valor para pesquisa no array principal $options */
    foreach ($params as $param) {
        $tmp = explode('-' , $param);
        $options[$tmp[0]]['value'] = (array_key_exists(1, $tmp) ? $tmp[1] : '0');
    }


    /* Monta os termos de pesquisa para a consulta SQL que exibirá a tabela de chamados */
    $sql_terms = "";
    foreach ($options as $key => $value) {
        if ($value['value'] !== '') {

            $sql_terms .= ($value['value'] == 0 ? "AND {$value['sql_alias']} IS NULL " : "AND {$value['sql_alias']}={$value['value']} ");
        }
    }


    $sql = $QRY["ocorrencias_full_ini"] . " WHERE 
            stat_ignored <> 1 AND 
            o.sistema IN ({$uareas}) AND 
            s.stat_painel IN (1,2) 
            {$sql_terms}
    ";

    /* Só enviará dados se for o último nível do agrupamento selecionado */
    if (count($params) == count($groups)) {

        try {
            $res = $conn->query($sql);

        ?>
        <div id="tables">
            <!-- Listagem dos chamados -->
            <table id="table<?= $table_id; ?>" class="lista_agrupamento stripe hover order-column row-border" border="0" cellspacing="0" width="100%">
                <thead>
                    <tr class="header">
                        <th class="line"><?= TRANS('NUMBER_ABBREVIATE'); ?> / <?= TRANS('AREA'); ?></th>
                        <th class="line"><?= TRANS('ISSUE_TYPE'); ?></th>
                        <th class="line"><?= TRANS('CLIENT'); ?><br /><?= TRANS('CONTACT'); ?><br /><?= TRANS('DEPARTMENT'); ?></th>
                        <th class="line"><?= TRANS('REQUESTER_AREA'); ?><br /><?= TRANS('DESCRIPTION'); ?></th>
                        <th class="line"><?= TRANS('OPENING_DATE'); ?></th>
                        <th class="line abs_time"><?= TRANS('ABSOLUTE_TIME'); ?></th>
                        <th class="line"><?= TRANS('COL_STATUS'); ?></th>
                        <th class="line"><?= TRANS('COL_PRIORITY'); ?></th>
                        
                        <?php
                            if ($calc_slas) {
                                ?>
                                    <th class="line slas"><?= TRANS('COL_SLAS'); ?></th>
                                <?php
                            }
                        ?>
                    </tr>
                </thead>
                <tbody>
                    <?php

                    foreach ($res->fetchall() as $rowDetail) { /* registros */
                    ?>
                        <tr>
                    <?php

                        $qryImg = "SELECT * FROM imagens WHERE img_oco = {$rowDetail['numero']}";
                        $execImg = $conn->query($qryImg);
                        $rowTela = $execImg->fetch();
                        $regImg = $execImg->rowCount();
                        if ($regImg != 0) {
                            $linkImg = "<a onClick=\"javascript:popup_wide('./listFiles.php?COD=" . $rowDetail['numero'] . "')\"><img src='../../includes/icons/attach2.png'></a>";
                        } else $linkImg = "";

                        $sqlSubCall = "SELECT * FROM ocodeps WHERE dep_pai = {$rowDetail['numero']} OR dep_filho = {$rowDetail['numero']} ";
                        $execSubCall = $conn->query($sqlSubCall);
                        $regSub = $execSubCall->rowCount();
                        if ($regSub > 0) {
                            #É CHAMADO PAI?
                            $_sqlSubCall = "SELECT * FROM ocodeps WHERE dep_pai = {$rowDetail['numero']}";
                            $_execSubCall = $conn->query($_sqlSubCall);
                            $_regSub = $_execSubCall->rowCount();
                            $comDeps = false;
                            foreach ($_execSubCall->fetchall() as $rowSubPai) {
                                $_sqlStatus = "SELECT 
                                                    o.*, s.* 
                                                FROM 
                                                    ocorrencias o, 
                                                    `status` s  
                                                WHERE 
                                                    o.numero = {$rowSubPai['dep_filho']} AND 
                                                    o.`status` = s.stat_id AND 
                                                    s.stat_painel NOT IN (3) AND 
                                                    s.stat_ignored <> 1
                                                ";
                                $_execStatus = $conn->query($_sqlStatus);
                                $_regStatus = $_execStatus->rowCount();
                                if ($_regStatus > 0) {
                                    $comDeps = true;
                                }
                            }
                            if ($comDeps) {
                                $imgSub = "<img src='" . $imgsPath . "sub-ticket-red.svg' class='mb-1' height='10' title='" . TRANS('TICKET_WITH_RESTRICTIVE_RELATIONS') . "'>";
                            } else
                                $imgSub = "<img src='" . $imgsPath . "sub-ticket-green.svg' class='mb-1' height='10' title='" . TRANS('TICKET_WITH_OPEN_RELATIONS') . "'>";
                        } else
                            $imgSub = "";

                        

                        $lastEntryNotification = '';
                        $lastEntry = getLastEntry($conn, $rowDetail['numero']);
                        if (!empty($lastEntry['numero'])) {
                            $responsible = getUserInfo($conn, $lastEntry['responsavel']);
                            $dateLastEntry = dateScreen($lastEntry['data']);
                            $title = ($responsible['nome'] ?? '');
                            $content =  $lastEntry['assentamento'] . '<hr>' . $dateLastEntry;
                            if ($lastEntry['responsavel'] == $rowDetail['aberto_por_cod']) {
                                /* Assentamento realizado pelo solicitante */
                                $lastEntryNotification = "<span class='badge badge-warning ticket-interaction p-2 mb-2' data-content='{$content}' title='{$title}'><i class='fas fa-user-edit fs-16 text-secondary'></i></span><br/>";
                            } else {
                                /* Se o assentamento tiver sido feito por um operador */
                                $lastEntryNotification = "<span class='badge badge-info ticket-interaction p-2 mb-2' data-content='{$content}' title='{$title}'><i class='fas fa-check fs-16 text-white'></i></span><br/>";
                            }
                        } else {
                            /* Sem nenhum assentamento */
                            $lastEntryNotification = '<span class="badge badge-danger ticket-interaction p-2 mb-2" title="'.TRANS('NO_INTERACTION_YET').'"><i class="fas fa-clock fs-16 text-white"></i></span><br/>';
                        }
                        
                        $clientName = (!empty($rowDetail['nickname']) ? "<b>" . $rowDetail['nickname'] . "</b><br /><br />" : "");
                        $departmentName = (!empty($rowDetail['setor']) ? "<b>" . noHtml($rowDetail['setor']) . "</b><br /><br />" : "");

                        $texto = trim(noHtml($rowDetail['descricao']));
                        if (strlen((string)$texto) > 200) {
                            // $texto = substr($texto, 0, 195) . " ..... ";
                        };

                        if (!isset($rowDetail['cor'])) {
                            $COR = '#CCCCCC';
                        } else {
                            $COR = $rowDetail['cor'];
                        }
                
                        $cor_font = "#000000";
                        if (isset($rowDetail['cor_fonte']) && !empty($rowDetail['cor_fonte'])) {
                            $cor_font = $rowDetail['cor_fonte'];
                        }

                        $absoluteTime = absoluteTime($rowDetail['data_abertura'], date('Y-m-d H:i:s'));


                        if ($calc_slas) {
                            /** Trecho sobre o tempo filtrado e SLAs */
                            $referenceDate = (!empty($rowDetail['oco_real_open_date']) ? $rowDetail['oco_real_open_date'] : $rowDetail['data_abertura']);
                            $dataAtendimento = $rowDetail['data_atendimento']; //data da primeira resposta ao chamado
                            $dataFechamento = $rowDetail['data_fechamento'];
                        
                            /* NOVOS MÉTODOS PARA O CÁLCULO DE TEMPO VÁLIDO DE RESPOSTA E SOLUÇÃO */
                            $holidays = getHolidays($conn);
                            $profileCod = getProfileCod($conn, $_SESSION['s_wt_areas'], $rowDetail['numero']);
                            $worktimeProfile = getWorktimeProfile($conn, $profileCod);
                        
                            /* Objeto para o cálculo de Tempo válido de SOLUÇÃO - baseado no perfil de jornada de trabalho e nas etapas em cada status */
                            $newWT = new WorkTime( $worktimeProfile, $holidays );
                            
                            /* Objeto para o cálculo de Tempo válido de RESPOSTA baseado no perfil de jornada de trabalho e nas etapas em cada status */
                            $newWTResponse = new WorkTime( $worktimeProfile, $holidays );
                        
                            /* Objeto para checagem se o momento atual está coberto pelo perfil de jornada associado */
                            $objWT = new Worktime( $worktimeProfile, $holidays );
                        
                            /* Realiza todas as checagens necessárias para retornar os tempos de resposta e solução para o chamado */
                            $ticketTimeInfo = getTicketTimeInfo($conn, $newWT, $newWTResponse, $rowDetail['numero'], $referenceDate, $dataAtendimento, $dataFechamento, $rowDetail['status_cod'], $objWT);
                        
                            /* Retorna os leds indicativos (bolinhas) para os tempos de resposta e solução */
                            $ledSlaResposta = showLedSLA($ticketTimeInfo['response']['seconds'], $percLimit, $rowDetail['sla_resposta_tempo']);
                            $ledSlaSolucao = showLedSLA($ticketTimeInfo['solution']['seconds'], $percLimit, $rowDetail['sla_solucao_tempo']);
                            
                            // $isRunning = $ticketTimeInfo['running'];
                        
                            // $colTVNew = $ticketTimeInfo['solution']['time'];
                            // if (isTicketFrozen($conn, $rowDetail['numero'])) {
                            //     $colTVNew = $iconFrozen . "&nbsp;" . $colTVNew;
                            // } elseif (!$isRunning) {
                            //     $colTVNew = $iconOutOfWorktime . "&nbsp;" . $colTVNew;
                            // }
                            /* Final do trecho sobre o tempo filtrado e SLAs */
                        }

                        
                    
                        ?>
                            <td class="line" data-sort="<?= $rowDetail['numero']; ?>"><?= $lastEntryNotification; ?><b><a onClick=openTicketInfo(<?= $rowDetail['numero']; ?>)><?= $rowDetail['numero']; ?></a></b><?= $imgSub; ?><br /><?= $rowDetail['area']; ?></td>
                            <td class="line"><?= $linkImg; ?>&nbsp;<?= $rowDetail['problema']; ?></td>
                            <td class="line"><b><?= $clientName; ?></b><?= $rowDetail['contato']; ?><br /><b><?= $departmentName; ?></b></td>
                            <td class="line"><b><?= $rowDetail['area_solicitante']; ?></b><br /><?= $texto; ?></td>
                            <td class="line" data-sort="<?= $rowDetail['data_abertura']; ?>"><?= dateScreen($rowDetail['data_abertura']); ?></td>
                            <td class="line" data-sort="<?= $absoluteTime['inSeconds']; ?>"><?= $absoluteTime['inTime']; ?></td>
                            <td class="line"><?= noHtml($rowDetail['chamado_status']); ?></td>
                            <td class="line" data-sort="<?= $rowDetail['pr_atendimento']; ?>"><?= "<span class='badge p-2' style='color: " . $cor_font . "; background-color: " . $COR . "'>" . $rowDetail['pr_descricao'] . "</span>"; ?></td>
                            <?php
                                if ($calc_slas) {
                                    ?>
                                        <td class="line"><?= "<img height='20' src='" . $imgsPath . "" . $ledSlaResposta . "' title='" . TRANS('HNT_RESPONSE_LED') . "'>&nbsp;<img height='20' src='" . $imgsPath . "" . $ledSlaSolucao . "' title='" . TRANS('HNT_SOLUTION_LED') . "'>"; ?></td>
                                    <?php
                                }
                            ?>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <?php


        }
        catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage();
        }
    }
}



