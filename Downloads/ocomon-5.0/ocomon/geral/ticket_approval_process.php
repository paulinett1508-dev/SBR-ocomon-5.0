<?php session_start();
 /*                        Copyright 2023 Flávio Ribeiro

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

require_once __DIR__ . "/" . "../../includes/include_basics_only.php";
require_once __DIR__ . "/" . "../../includes/classes/ConnectPDO.php";

use OcomonApi\Support\Email;
use includes\classes\ConnectPDO;

$conn = ConnectPDO::getInstance();
$post = (isset($_POST) ? $_POST : []);

if (!isset($post['numero'])) {
    return false;
}

$ticket_id = (isset($post['ticket_id']) && !empty($post['ticket_id']) && $post['ticket_id'] == getGlobalTicketId($conn, $post['numero']) ? noHtml($post['ticket_id']) : '');

$rating_id = (isset($post['rating_id']) && !empty($post['rating_id']) && $post['rating_id'] == getGlobalTicketRatingId($conn, $post['numero']) ? noHtml($post['rating_id']) : '');

$bypassAuth = ($ticket_id && $rating_id);

if ((!isset($_SESSION['s_logado']) || $_SESSION['s_logado'] == 0) && (!$bypassAuth)) {
    $_SESSION['session_expired'] = 1;
    echo "<script>top.window.location = '../../index.php'</script>";
    exit;
}

if (!$bypassAuth)
    $auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 3, 1);


$data = [];
$rateTypes = [];
$exception = "";
$mailNotification = "";
$data['success'] = true;
$data['message'] = "";
$user = (isset($_SESSION['s_uid']) ? (int)$_SESSION['s_uid'] : '');

$data['numero'] = (int) $post['numero'];

$config = getConfig($conn);
$rowconfmail = getMailConfig($conn);

$ticketInfo = getTicketData($conn, $data['numero']);

$hasRatingRow = hasRatingRow($conn, $data['numero']);

$onlyBusinessDays = ($config['conf_only_weekdays_to_count_after_done'] ? true : false);


/* Informações sobre a área destino */
$infoAreaTo = ($ticketInfo['sistema'] != '-1' ? getAreaInfo($conn, $ticketInfo['sistema']) : []);

/* Apenas o solicitante pode validar e avaliar o atendimento */
if ($user != $ticketInfo['aberto_por'] && !$bypassAuth) {
    return false;
}

if ($bypassAuth && !$user) {
    $user = $ticketInfo['aberto_por'];
}

if (isRated($conn, $data['numero'])) {
    $data['success'] = false;
    $data['message'] = message('warning', '', TRANS('MSG_TICKET_ALREADY_APPROVED_AND_RATED'), '');
    echo json_encode($data);
    return false;
}

// if ($config['conf_closing_mode'] != 2) {
//     return false;
// }

$doneStatus = $config['conf_status_done'];
if ($ticketInfo['status'] != $doneStatus) {
    return false;
}

/* Checar o limite de tempo para a validacao do atendimento */
if (!empty($ticketInfo['data_fechamento'])) {
    
    $deadlineToApprove = addDaysToDate($ticketInfo['data_fechamento'], $config['conf_time_to_close_after_done'], $onlyBusinessDays);
    
    if (date('Y-m-d H:i:s') > $deadlineToApprove) {
        $data['success'] = false;
        $data['message'] = message('warning', '', TRANS('MSG_EXPIRED_TIME_TO_APPROVE'), '');
        echo json_encode($data);
        return false;
    }
    
    // $dateA = new DateTime($ticketInfo['data_fechamento']);
    // $dateB = new DateTime();

    // if ($dateA->diff($dateB)->days > $config['conf_time_to_close_after_done']) {
    //     $data['success'] = false;
    //     $data['message'] = message('warning', '', TRANS('MSG_EXPIRED_TIME_TO_APPROVE'), '');
    //     echo json_encode($data);
    //     return false;
    // }
}

$data['approved'] = (isset($post['approved']) && $post['approved'] == "true" ? 1 : 0);
$data['new_status'] = ($data['approved'] ? 4 : (int)$config['conf_status_done_rejected']);
$data['entry_type'] = ($data['approved'] ? 13 : 14);
$data['operation_type'] = ($data['approved'] ? 7 : 8);
$data['msg_string'] = ($data['approved'] ? 'MSG_SERVICE_APPROVED_AND_RATED' : 'MSG_SERVICE_REJECTED');
$data['msg_style'] = ($data['approved'] ? 'success' : 'info');
$data['service_done_comment'] = (isset($post['service_done_comment']) && !empty($post['service_done_comment']) ? noHtml($post['service_done_comment']) : '');

$rateTypes['great'] = (isset($post['rating_great']) && $post['rating_great'] == "true" ? 1 : 0);
$rateTypes['good'] = (isset($post['rating_good']) && $post['rating_good'] == "true" ? 1 : 0);
$rateTypes['regular'] = (isset($post['rating_regular']) && $post['rating_regular'] == "true" ? 1 : 0);
$rateTypes['bad'] = (isset($post['rating_bad']) && $post['rating_bad'] == "true" ? 1 : 0);

$data['rate'] = 'great'; /* Padrão */
$data['auto_rate'] = 1;
foreach ($rateTypes as $key => $rate) {
    if ($rate == 1) {
        $data['rate'] = $key;
        $data['auto_rate'] = 0;
        break;
    }
}


if (empty($data['service_done_comment'])) {
    $data['success'] = false;
    $data['message'] = message('warning', '', TRANS('MSG_EMPTY_DATA'), '');
    $data['field_id'] = "service_done_comment";
    echo json_encode($data);
    return false;
}


// var_dump([
//     'post[]' => $post,
//     'user' => $user,
//     'data[]' => $data,
// ]); exit;


//Checa se já existe algum registro de log - caso não existir grava o estado atual
$firstLog = firstLog($conn, $data['numero'],'NULL', 1);


/* Array para a funcao recordLog */
$arrayBeforePost = [];
$arrayBeforePost['status_cod'] = $ticketInfo['status'];

$terms = "";
if (!$data['approved']) {
    $terms = ", data_fechamento = NULL ";
}

$sql = "UPDATE ocorrencias SET `status`= {$data['new_status']} {$terms} WHERE numero = {$data['numero']}";
try {
    $conn->exec($sql);

    if (!$data['approved']) {
        $qryDelSolution = "DELETE FROM solucoes WHERE numero = {$data['numero']}";
        $conn->exec($qryDelSolution);

        
        if ($hasRatingRow) {
            /* update do contador da rejeição */
            $sql = "UPDATE tickets_rated SET 
                        rejected_count = rejected_count + 1 
                    WHERE ticket = {$data['numero']};
            ";
        } else {
            /* Gravação da rejeicao */
            $sql = "INSERT INTO tickets_rated 
                (ticket, rate, automatic_rate, rejected_count) 
            VALUES ({$data['numero']}, null, 0, 1 )";
        }
        
        try {
            $conn->exec($sql);
        }
        catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage();
        }

    } else {
        
        if ($hasRatingRow) {
            /* Atualização da avaliação */
            $sql = "UPDATE tickets_rated SET
                        rate = '{$data['rate']}', 
                        rate_date = NOW(),
                        automatic_rate = {$data['auto_rate']}
                    WHERE ticket = {$data['numero']} ";
        } else {
            /* Gravação da avaliação */
            $sql = "INSERT INTO tickets_rated 
            (ticket, rate, rate_date, automatic_rate) 
            VALUES ({$data['numero']}, '{$data['rate']}', NOW(), {$data['auto_rate']}) ";
        }
        
        
        try {
            $conn->exec($sql);
        }
        catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage();
        }
    }
    
    
    
    $sql = "INSERT INTO assentamentos 
                (ocorrencia, assentamento, `data`, responsavel, tipo_assentamento) 
            values 
                ({$data['numero']}, '{$data['service_done_comment']}', '".date('Y-m-d H:i:s')."', {$user}, {$data['entry_type']} )";

    try {
        $result = $conn->exec($sql);
    }
    catch (Exception $e) {
        $exception .= "<hr>" . $e->getMessage();
    }


    /* Gravação da data na tabela tickets_stages */
    /* A primeira entrada serve apenas para gravar a conclusão do status anterior ao encerramento */
    $stopTimeStage = insert_ticket_stage($conn, $data['numero'], 'stop', $data['new_status']);
    /* As duas próximas entradas servem para lançar o status de encerramento - o tempo nao será contabilizado */
    $stopTimeStage = insert_ticket_stage($conn, $data['numero'], 'start', $data['new_status']);
    
    if ($data['new_status'] == 4) {
        $stopTimeStage = insert_ticket_stage($conn, $data['numero'], 'stop', $data['new_status']);
    }

    /* Array para a função recordLog */
    $afterPost = [];
    $afterPost['status'] = $data['new_status'];

    /* Função que grava o registro de alterações do chamado */
    $recordLog = recordLog($conn, $data['numero'], $arrayBeforePost, $afterPost, $data['operation_type'], $user);

}
catch (Exception $e) {
    $exception .= "<hr>" . $e->getMessage();
    $data['success'] = false;
    $data['message'] = message('danger', '', TRANS('MSG_ERR_DATA_UPDATE') . $exception, '');
    echo json_encode($data);
    return false;
}

/* Variáveis de ambiente para envio de e-mail */
$VARS = getEnvVarsValues($conn, $data['numero']);

if (!$data['approved'] && !empty($infoAreaTo)) {
    /** 
     * Enviar email para a área
    */

    $mailSendMethod = 'send';
    if ($rowconfmail['mail_queue']) {
        $mailSendMethod = 'queue';
    }

    $event = "rejeitado-para-area";
    $eventTemplate = getEventMailConfig($conn, $event);

    /* Disparo do e-mail (ou fila no banco) para a área de atendimento */
    $mail = (new Email())->bootstrap(
        transvars($eventTemplate['msg_subject'], $VARS),
        transvars($eventTemplate['msg_body'], $VARS),
        $infoAreaTo['email'],
        $eventTemplate['msg_fromname'],
        $data['numero']
    );

    if (!$mail->{$mailSendMethod}()) {
        $mailNotification .= "<hr>" . TRANS('EMAIL_NOT_SENT') . "<hr>" . $mail->message()->getText();
    }
}

if (!$data['approved']) {
    /** Envia email para o operador responsavel */
    $mailSendMethod = 'send';
    if ($rowconfmail['mail_queue']) {
        $mailSendMethod = 'queue';
    }

    $event = "rejeitado-para-operador";
    $eventTemplate = getEventMailConfig($conn, $event);

    /* Disparo do e-mail (ou fila no banco) para a área de atendimento */
    $mail = (new Email())->bootstrap(
        transvars($eventTemplate['msg_subject'], $VARS),
        transvars($eventTemplate['msg_body'], $VARS),
        $infoAreaTo['email'],
        $eventTemplate['msg_fromname'],
        $data['numero']
    );

    if (!$mail->{$mailSendMethod}()) {
        $mailNotification .= "<hr>" . TRANS('EMAIL_NOT_SENT') . "<hr>" . $mail->message()->getText();
    }

}


$_SESSION['flash'] = message($data['msg_style'], '', TRANS($data['msg_string']) . $exception . $mailNotification, '', '');
echo json_encode($data);
return true;