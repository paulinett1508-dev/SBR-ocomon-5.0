<?php
/*      Copyright 2023 Flávio Ribeiro

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

include (__DIR__ . "/" . "../../includes/config.inc.php");
require_once (__DIR__ . "/" . "../../includes/functions/functions.php");
require_once (__DIR__ . "/" . "../../includes/functions/dbFunctions.php");
require_once __DIR__ . "/" . "../../includes/classes/ConnectPDO.php";

use includes\classes\ConnectPDO;

$conn = ConnectPDO::getInstance();

$exception = "";
$config = getConfig($conn);
$doneStatus = $config['conf_status_done'];
$daysToApprove = $config['conf_time_to_close_after_done'];
$onlyBusinessDays = ($config['conf_only_weekdays_to_count_after_done'] ? true : false);


$automaticRate = $config['conf_rate_after_deadline'];
$entry = TRANS('TICKET_AUTO_VALIDATED');

$dateFrom = subDaysFromDate(date('Y-m-d H:i:s'), $daysToApprove, $onlyBusinessDays);


/** Pré-filtro de chamados elegíveis para aprovação de forma automática 
 * Não é definitivo pois é necessário tratar casos onde o resultado da função "subDaysFromDate" não é preciso
 *  (em função de aberturas realizadas em finais de semana e a configuração for apenas para dias uteis)
*/
$sql = "SELECT 
            o.numero
        FROM 
            ocorrencias o, 
            tickets_rated tr
        WHERE
            o.numero = tr.ticket AND
            o.`operador` <> o.`aberto_por` AND
            o.`status` = {$doneStatus} AND
            o.`data_fechamento` IS NOT NULL AND
            o.`data_fechamento` <= '{$dateFrom}' AND
            tr.rate IS NULL";

// dump($sql); exit;

$res = $conn->query($sql);
$tickets = [];
if ($res->rowCount()) {
    foreach ($res->fetchAll() as $rowTicket) {
        $tickets[] = $rowTicket['numero'];
    }
}

// var_dump($tickets); exit;
// var_dump($tickets);

foreach ($tickets as $ticket) {
    
    $sql = "SELECT 
                numero,
                data_fechamento
            FROM
                ocorrencias
            WHERE 
                numero = {$ticket} AND
                data_fechamento IS NOT NULL";
    
    $res = $conn->query($sql);

    if ($res->rowCount()) {
        $row = $res->fetch();
        
        /** Aqui cada ticket elegível é checado individualmente para se ter certeza que pode ser aprovado automaticamente */
        /** Data limite para a aprovação e avaliação do chamado */
        $deadlineToApprove = addDaysToDate($row['data_fechamento'], $daysToApprove, $onlyBusinessDays);

        $canBeAutoApproved = $deadlineToApprove < date("Y-m-d H:i:s");

        // var_dump([
        //     'ticket' => $row['numero'],
        //     'data máxima' => $deadlineToApprove,
        //     'hoje' => date("Y-m-d H:i:s"),
        //     'Pode ser auto aprovado?' => $canBeAutoApproved
        // ]);exit;

        if ($canBeAutoApproved) {

            /* Atualiza o status */
            $sql = "UPDATE 
                        ocorrencias SET `status`= 4 
                    WHERE
                        numero = {$row['numero']}
            ";

            try {
                $res = $conn->exec($sql);
                /* Atualiza ou insere a avaliação do chamado */
                if (hasRatingRow($conn, $row['numero'])) {
                    $sqlRating = "UPDATE 
                                    tickets_rated
                                SET 
                                    rate = '{$automaticRate}',
                                    rate_date = NOW(),
                                    automatic_rate = 1
                                WHERE 
                                    ticket = {$row['numero']}
                                    ";
                } else {
                    $sqlRating = "INSERT INTO tickets_rated
                                    (
                                        ticket, 
                                        rate, 
                                        rate_date,
                                        automatic_rate 
                                    )
                                    VALUES
                                    (
                                        {$row['numero']},
                                        '{$automaticRate}',
                                        NOW(),
                                        1
                                    )";
                }
                //  (SELECT data_fechamento FROM ocorrencias WHERE numero = {$row['numero']}),

                try {
                    $resRating = $conn->exec($sqlRating);

                    /* Assentamento - Comentário */
                    $sqlEntry = "INSERT INTO assentamentos 
                                    (ocorrencia, assentamento, `data`, responsavel, tipo_assentamento) 
                                    VALUES 
                                    (" . $row['numero'] . ", '{$entry}', NOW(), 0 , 13 )"; //tratar usuario 0 (zero) para ser do sistema
                    try {
                        $resultAssent = $conn->exec($sqlEntry);


                        /* Arrays para a função recordLog */
                        $arrayBeforePost = [];
                        $arrayBeforePost['status_cod'] = $doneStatus;
                        $afterPost = [];
                        $afterPost['status'] = 4;

                        /* Função que grava o registro de alterações do chamado */
                        $recordLog = recordLog($conn, $row['numero'], $arrayBeforePost, $afterPost, 7, 0);

                        /* A primeira entrada serve apenas para gravar a conclusão do status anterior ao encerramento */
                        $stopTimeStage = insert_ticket_stage($conn, $row['numero'], 'stop', 4);

                        $startTimeStage = insert_ticket_stage($conn, $row['numero'], 'start', 4, date('Y-m-d H:i:s'));
                        $stopTimeStage = insert_ticket_stage($conn, $row['numero'], 'stop', 4, date('Y-m-d H:i:s'));
                    }
                    catch (Exception $e) {
                        $exception .= "<hr>" . $e->getMessage();
                    }

                }
                catch (Exception $e) {
                    $exception .= "<hr>" . $e->getMessage();
                }
                
            }
            catch (Exception $e) {
                $exception .= "<hr>" . $e->getMessage();
            }            
        }
    }
}
    
echo $exception;
echo date('Y-m-d H:i:s');

return true;
