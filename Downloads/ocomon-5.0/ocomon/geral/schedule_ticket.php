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

require_once __DIR__ . "/" . "../../includes/include_basics_only.php";
require_once __DIR__ . "/" . "../../includes/classes/ConnectPDO.php";

use OcomonApi\Support\Email;
use includes\classes\ConnectPDO;

$conn = ConnectPDO::getInstance();

$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 3, 1);
$return = [];
$return['success'] = true;
$erro = false;
$exception = "";
$now = date('Y-m-d H:i:s');


$post = $_POST;

if (!isset($post['numero']) || empty($post['numero'])) {
    exit;
}

$ticketWorkers = getTicketWorkers($conn, $post['numero']);
$hasWorker = (empty($ticketWorkers) ? false : true);


$data['action'] = (isset($post['action']) && !empty($post['action']) ? noHtml($post['action']) : '');
$isUpdate = (isset($post['isUpdate']) ? $post['isUpdate'] : false);

$data['main_worker'] = (isset($post['main_worker']) && $post['main_worker'] != "" ? $post['main_worker'] : "");
$data['aux_worker'] = (isset($post['aux_worker']) && $post['aux_worker'] != [] ? $post['aux_worker'] : []);

$data['first_response'] = (isset($post['first_response']) ? ($post['first_response'] == "true" ? 1 : 0) : 0);



/* Apenas uma das duas informações podem estar vazias */
if ($data['action'] != 'open' && (!isset($post['scheduleDate']) || empty($post['scheduleDate'])) && empty($data['main_worker'])) {
    $return['success'] = false;
    $return['message'] = message('warning', '', TRANS('MSG_EMPTY_DATA'), '');
    $return['field_id'] = "idDate_schedule";
    echo json_encode($return);
    return false;
}

$data['entry_schedule'] = (isset($post['entry_schedule']) && !empty($post['entry_schedule']) ? noHtml($post['entry_schedule']) : "");



if ($data['action'] == 'open') {
    
    if (isset($post['scheduleDate']) && !empty($post['scheduleDate']) && $data['main_worker']) {
        $data['entry_schedule'] = TRANS('MSG_TICKET_IS_SCHEDULED_TO_WORKER');
    } elseif(isset($post['scheduleDate']) && !empty($post['scheduleDate'])) {
        $data['entry_schedule'] = TRANS('MSG_SCHEDULED_AT_OPENING');
    } elseif ($data['main_worker']) {
        $data['entry_schedule'] = TRANS('MSG_ROUTED_AT_OPENING');
    }
} elseif (empty($data['entry_schedule'])) {
    $data['success'] = false;
    $data['message'] = message('warning', '', TRANS('MSG_EMPTY_DATA'), '');
    $data['field_id'] = "entry_schedule";
    echo json_encode($data);
    return false;
}

$numero = (int) $post['numero'];

if (!empty($post['scheduleDate'])) {
    $scheduleDate = $post['scheduleDate'];
    $dataHoje = new DateTime();
    $scheduleDate = new DateTime(dateDB($scheduleDate));
    
    if ($scheduleDate < $dataHoje) {
        $return['success'] = false;
        $return['message'] = message('warning', '', TRANS('DATE_NEEDS_TO_BE_IN_FUTURE'), '');
        $return['field_id'] = "idDate_schedule";
        echo json_encode($return);
        return false;
    }

    $schedule_to = dateDB($post['scheduleDate']);

}


//Checa se já existe algum registro de log - caso não existir grava o estado atual
$firstLog = firstLog($conn, $numero,'NULL', 1);

$config = getConfig($conn);
$rowconfmail = getMailConfig($conn);
// $rowLogado = getUserInfo($conn, $_SESSION['s_uid']);
$openerEmail = getOpenerEmail($conn, $numero);


/* Ver se o chamado está sendo encaminhado para operador */
if (!empty($data['main_worker'])) {

    if (empty($config['conf_status_scheduled_to_worker'])) {
        $return['success'] = false;
        $return['message'] = message('warning', '', TRANS('NEED_TO_CONFIG_SCHED_TO_WORKER_STATUS'), '');
        echo json_encode($return);
        return false;
    }

    if (!empty($post['scheduleDate'])){
        /* Agendado para o operador */
        $newStatus = $config['conf_status_scheduled_to_worker'];
    } elseif ($data['action'] == 'open') {
        $newStatus = $config['conf_foward_when_open'];
    } else {
        $newStatus = $config['conf_status_in_worker_queue'];
    }
} elseif ($data['action'] == 'open') {
    $newStatus = $config['conf_schedule_status']; //Status para agendamento na abertura
} else {
    $newStatus = $config['conf_schedule_status_2']; //Status para agendamento na edição
}
    


$sqlTicket = "SELECT * FROM ocorrencias WHERE numero = {$numero} ";
$resultTicket = $conn->query($sqlTicket);
$row = $resultTicket->fetch();

/* Informações sobre a área destino */
$rowAreaTo = getAreaInfo($conn, $row['sistema']);


/* Array para a funcao recordLog */
$arrayBeforePost = [];
$arrayBeforePost['status_cod'] = $row['status'];
$arrayBeforePost['oco_scheduled_to'] = $row['oco_scheduled_to'];
$arrayBeforePost['operador_cod'] = $row['operador'];



if ($row['status'] == 4 ) {
    /* Já encerrado */
    $return['message'] = TRANS('HNT_TICKET_CLOSED');
    echo json_encode($return);
    return true;
}


if (!empty($post['scheduleDate'])) {
    /* Agendamento */
    $sql = "UPDATE ocorrencias SET oco_scheduled = 1, oco_scheduled_to = '{$schedule_to}', `status` = {$newStatus} WHERE numero = {$numero}";

    try {
        $result = $conn->exec($sql);
    }
    catch (Exception $e) {
        $erro = true;
        $return['message'] = $e->getMessage();
        echo json_encode($return);
        return true;
    }
} elseif (!empty($data['main_worker'])) {
    /* Apenas encaminhamento do chamado */
    $sql = "UPDATE ocorrencias SET `status` = {$newStatus}, `operador` = '" . $data['main_worker'] . "' WHERE numero = {$numero}";

    try {
        $result = $conn->exec($sql);
    }
    catch (Exception $e) {
        $erro = true;
        $return['message'] = $e->getMessage();
        $return['sql'] = $sql;
        echo json_encode($return);
        return true;
    }
}


/**
 * Opção de configuração para definir se o encaminhamento será marcado como primeira resposta
 */
$set_response = $config['set_response_at_routing'];

/* Demanda para marcar como primeira resposta */
if (empty($row['data_atendimento']) && ($set_response == 'always' || $set_response == 'choice')) {
    
    if (($set_response == 'choice' && $data['first_response']) || ($set_response == 'always')) {
        $sql = "UPDATE ocorrencias SET data_atendimento = '{$now}' WHERE numero = {$numero}";
        try {
            $res = $conn->exec($sql);
        } catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage();
        }
    }
}



/* Inserções sobre agendamento para operador */
if (!empty($data['main_worker'])) {
    /* Novas tabelas: tickets_x_workers, tickets_extended */

    if (!$hasWorker) {

        $sql = "INSERT INTO `ticket_x_workers` 
                    (ticket, user_id, main_worker, assigned_at) 
                VALUES 
                    ({$numero}, {$data['main_worker']}, 1, '{$now}') ";
        
        try {
            $res = $conn->exec($sql);

            if (!empty($data['aux_worker'])) {
                foreach ($data['aux_worker'] as $aux) {
                    $sql = "INSERT INTO `ticket_x_workers` 
                                (ticket, user_id, main_worker, assigned_at) 
                            VALUES 
                                ({$numero}, {$aux}, 0, '{$now}') ";
                    
                    try {
                        $res = $conn->exec($sql);
                    }
                    catch (Exception $e) {
                        $exception .= "<hr>" . $e->getMessage();
                    }
                }
            }
        } catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage();
        }

        /* Inserção dos dados estendidos da ocorrência */
        if (!empty($data['main_worker'])) {
            $sql = "INSERT INTO `tickets_extended`
                        (ticket, main_worker) 
                    VALUES 
                        (
                            {$numero}, 
                            {$data['main_worker']} 
                        )";

            try {
                $res = $conn->exec($sql);
            } catch (Exception $e) {
                $exception .= "<hr>" . $e->getMessage();
            }
                        
        }
    } elseif ($isUpdate) {
        
        /* Atualização dos dados estendidos */
        $sql = "UPDATE 
                    `tickets_extended` 
                SET 
                    main_worker = {$data['main_worker']}
                WHERE ticket = {$numero} ";

        try {
            $res = $conn->exec($sql);
        } catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage();
        }
    
        /* Atualização dos operadors auxiliares - Parte 1 */
        $sql = "DELETE FROM ticket_x_workers WHERE ticket = {$numero}";
        try {
            $res = $conn->exec($sql);
        } catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage();
        }

        /* Atualização dos operadors alocados - Responsável */
        $sql = "INSERT INTO 
                    `ticket_x_workers` 
                    (ticket, user_id, main_worker, assigned_at) 
                VALUES 
                    ({$numero}, {$data['main_worker']}, 1, '{$now}')";
        try {
            $res = $conn->exec($sql);
        } catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage();
        }

        /* Atualização dos operadors alocados - Auxiliares */
        if (!empty($data['aux_worker'])) {
            foreach ($data['aux_worker'] as $aux) {
                $sql = "INSERT INTO 
                            `ticket_x_workers` 
                            (ticket, user_id, main_worker, assigned_at) 
                        VALUES 
                            ({$numero}, {$aux}, 0, '{$now}')";
                try {
                    $res = $conn->exec($sql);
                } catch (Exception $e) {
                    $exception .= "<hr>" . $e->getMessage();
                }
            }
        }
    }
}


$user = (int)$_SESSION['s_uid'];

if (!empty($data['main_worker']) || !empty($post['scheduleDate'])) {
    // /**
    //  * Opção de configuração para definir se o encaminhamento será marcado como primeira resposta
    //  */
    // $set_response = $config['set_response_at_routing'];

    // /* Demanda para marcar como primeira resposta */
    // if (!empty($data['main_worker']) && empty($row['data_atendimento']) && ($set_response == 'always' || $set_response == 'choice')) {
        
    //     if (($set_response == 'choice' && $data['first_response']) || ($set_response == 'always')) {
    //         $sql = "UPDATE ocorrencias SET data_atendimento = '{$now}' WHERE numero = {$numero}";
    //         try {
    //             $res = $conn->exec($sql);
    //         } catch (Exception $e) {
    //             $exception .= "<hr>" . $e->getMessage();
    //         }
    //     }
    // }

    /* Gravação da data na tabela tickets_stages */
    $stopTimeStage = insert_ticket_stage($conn, $numero, 'stop', $newStatus);
    $startTimeStage = insert_ticket_stage($conn, $numero, 'start', $newStatus);

    if (!empty($post['scheduleDate'])) {
        $entryType = 7;
    } else {
        $entryType = 17;
    }

    /* Tipo de assentamento: 7 - Agendado na edição */
    $sql = "INSERT INTO assentamentos 
            (
                ocorrencia, 
                assentamento, 
                `data`, 
                responsavel, 
                tipo_assentamento
            ) 
                values 
            (
                ".$numero.", 
                '" . $data['entry_schedule'] . "', 
                '".date('Y-m-d H:i:s')."', 
                {$user}, {$entryType} 
            )";

    try {
        $result = $conn->exec($sql);
    }
    catch (Exception $e) {
        $erro = true;
        $return['message'] = $e->getMessage();
        echo json_encode($return);
        // dump($return);
        return true;
    }

}

if (!$erro) {

    /* Array para a função recordLog */
    $afterPost = [];
    $afterPost['status'] = $newStatus;

    $msg = "";
    if (!empty($post['scheduleDate'])) {

        $msg = TRANS('TICKET_SCHEDULED_SUCCESS');
        $operationType = 6;
        $afterPost['agendadoPara'] = $schedule_to;
        /* Função que grava o registro de alterações do chamado */
        $recordLog = recordLog($conn, $numero, $arrayBeforePost, $afterPost, $operationType); 
        
        
    } elseif (!empty($data['main_worker'])) {
        
        $msg = TRANS('TICKET_ASSIGNED_TO_WORKER_SUCCESSFULLY');
        $operationType = 5;
        $afterPost['operador'] = $data['main_worker'];
        /* Função que grava o registro de alterações do chamado */
        $recordLog = recordLog($conn, $numero, $arrayBeforePost, $afterPost, $operationType); 
    }
    
    if (!empty($msg)) {
        $_SESSION['flash'] = message('success', '', $msg . $exception, '', '');
    }

} else {
    $_SESSION['flash'] = message('danger', '', $return['message'], '', '');
}


/* Variáveis de ambiente para os e-mails */
$VARS = array();
$VARS = getEnvVarsValues($conn, $numero);
$mailSendMethod = 'send';
if ($rowconfmail['mail_queue']) {
    $mailSendMethod = 'queue';
}


if (isset($post['sendEmailToArea']) && $post['sendEmailToArea'] == 'true' && $data['action'] != 'open') {
    $event = "agendamento-para-area";
    $eventTemplate = getEventMailConfig($conn, $event);

    /* Disparo do e-mail (ou fila no banco) para a área de atendimento */
    $mail = (new Email())->bootstrap(
        transvars($eventTemplate['msg_subject'], $VARS),
        transvars($eventTemplate['msg_body'], $VARS),
        $rowAreaTo['email'],
        $eventTemplate['msg_fromname'],
        $numero
    );

    if (!$mail->{$mailSendMethod}()) {
        $mailNotification .= "<hr>" . TRANS('EMAIL_NOT_SENT') . "<hr>" . $mail->message()->getText();
    }
}


if (isset($post['sendEmailToUser']) && $post['sendEmailToUser'] == 'true') {
    $event = "agendamento-para-usuario";
    $eventTemplate = getEventMailConfig($conn, $event);

    $recipient = "";
    if (!empty($row['contato_email'])) {
        $recipient = $row['contato_email'];
    } else {
        $recipient = $openerEmail;
    }

    /* Disparo do e-mail (ou fila no banco) para o usuário */
    $mail = (new Email())->bootstrap(
        transvars($eventTemplate['msg_subject'], $VARS),
        transvars($eventTemplate['msg_body'], $VARS),
        $recipient,
        $eventTemplate['msg_fromname'],
        $numero
    );

    if (!$mail->{$mailSendMethod}()) {
        $mailNotification .= "<hr>" . TRANS('EMAIL_NOT_SENT') . "<hr>" . $mail->message()->getText();
    }
}


if (isset($post['sendEmailToWorkers']) && $post['sendEmailToWorkers'] == 'true') {
    
    $workersData = getTicketWorkers($conn, $numero);
    
    if (!empty($workersData)) {
        $event = "agendamento-para-operador";
        $eventTemplate = getEventMailConfig($conn, $event);

        foreach ($workersData as $worker) {

            /* Injeto os valores das variáveis específicas para esse evento */
            $VARS['%funcionario%'] = $worker['nome'];
            $VARS['%funcionario_email%'] = $worker['email'];
            
            /* Disparo do e-mail (ou fila no banco) para cada operador */
            $mail = (new Email())->bootstrap(
                transvars($eventTemplate['msg_subject'], $VARS),
                transvars($eventTemplate['msg_body'], $VARS),
                $worker['email'],
                $worker['nome'],
                $numero
            );

            if (!$mail->{$mailSendMethod}()) {
                $mailNotification .= "<hr>" . TRANS('EMAIL_NOT_SENT') . "<hr>" . $mail->message()->getText();
            }
        }
    }
}

$return['message'] = "Sucesso!";
echo json_encode($return);
// dump($return);
return true;

