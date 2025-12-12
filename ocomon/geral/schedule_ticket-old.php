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

$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 2, 1);
$return = [];
$return['success'] = true;
$erro = false;
$exception = "";

/* Funcionários não acessarão o menu de agendamento */
if (!$_SESSION['s_can_route']) {
    exit;
}

exit;


if (!isset($_POST['numero']) || empty($_POST['numero']) || !isset($_POST['scheduleDate']) || empty($_POST['scheduleDate']) ) {
    $return['success'] = false;
    $return['message'] = message('warning', '', TRANS('MSG_EMPTY_DATA'), '');
    $return['field_id'] = "idDate_schedule";
    echo json_encode($return);
    return false;
}



$data['entry_schedule'] = (isset($_POST['entry_schedule']) && !empty($_POST['entry_schedule']) ? noHtml($_POST['entry_schedule']) : "");

if (empty($data['entry_schedule'])) {
    $data['success'] = false;
    $data['message'] = message('warning', '', TRANS('MSG_EMPTY_DATA'), '');
    $data['field_id'] = "entry_schedule";
    echo json_encode($data);
    return false;
}



$numero = (int) $_POST['numero'];
$scheduleDate = $_POST['scheduleDate'];

$dataHoje = new DateTime();
$scheduleDate = new DateTime(dateDB($scheduleDate));

if ($scheduleDate < $dataHoje) {
    $return['success'] = false;
    $return['message'] = message('warning', '', TRANS('DATE_NEEDS_TO_BE_IN_FUTURE'), '');
    $return['field_id'] = "idDate_schedule";
    echo json_encode($return);
    return false;
}

//Checa se já existe algum registro de log - caso não existir grava o estado atual
$firstLog = firstLog($conn, $numero,'NULL', 1);

$schedule_to = dateDB($_POST['scheduleDate']);

$config = getConfig($conn);
$rowconfmail = getMailConfig($conn);
// $rowLogado = getUserInfo($conn, $_SESSION['s_uid']);
$openerEmail = getOpenerEmail($conn, $numero);


$newStatus = $config['conf_schedule_status_2']; //Status para agendamento na edição


$sqlTicket = "SELECT * FROM ocorrencias WHERE numero = {$numero} ";
$resultTicket = $conn->query($sqlTicket);
$row = $resultTicket->fetch();

/* Informações sobre a área destino */
$rowAreaTo = getAreaInfo($conn, $row['sistema']);


/* Array para a funcao recordLog */
$arrayBeforePost = [];
$arrayBeforePost['status_cod'] = $row['status'];
$arrayBeforePost['oco_scheduled_to'] = $row['oco_scheduled_to'];



if ($row['status'] == 4 ) {
    /* Já encerrado */
    $return['message'] = "Chamado já encerrado";
    echo json_encode($return);
    // dump($return);
    return true;
}

$sql = "UPDATE ocorrencias SET oco_scheduled = 1, oco_scheduled_to = '{$schedule_to}', status = {$newStatus} WHERE numero = {$numero}";

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

/* Gravação da data na tabela tickets_stages */
$stopTimeStage = insert_ticket_stage($conn, $numero, 'stop', $newStatus);
$startTimeStage = insert_ticket_stage($conn, $numero, 'start', $newStatus);

$user = (int)$_SESSION['s_uid'];
$assent = TRANS('TICKET_SCHEDULED_IN_EDIT');

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
                {$user}, 7 
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

if (!$erro) {

    $_SESSION['flash'] = message('success', '', TRANS('TICKET_SCHEDULED_SUCCESS'), '', '');

    /* Array para a função recordLog */
    $afterPost = [];
    $afterPost['status'] = $newStatus;
    $afterPost['agendadoPara'] = $schedule_to;

    /* Função que grava o registro de alterações do chamado */
    $recordLog = recordLog($conn, $numero, $arrayBeforePost, $afterPost, 6);    
    
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


if (isset($_POST['sendEmailToArea']) && $_POST['sendEmailToArea'] == 'true') {
    $event = "agendamento-para-area";
    $eventTemplate = getEventMailConfig($conn, $event);

    // send_mail($event, $rowAreaTo['email'], $rowconfmail, $eventTemplate, $VARS);
    
    /* Disparo do e-mail (ou fila no banco) para a área de atendimento */
    $mail = (new Email())->bootstrap(
        transvars($eventTemplate['msg_subject'], $VARS),
        transvars($eventTemplate['msg_body'], $VARS),
        $rowAreaTo['email'],
        $eventTemplate['msg_fromname'],
        $numero
    );

    if (!$mail->{$mailSendMethod}()) {
        $mailNotification .= "<hr>" . TRANS('EMAIL_NOT_SENT') . "<hr>" . $mail->error->getMessage();
    }
}


if (isset($_POST['sendEmailToUser']) && $_POST['sendEmailToUser'] == 'true') {
    $event = "agendamento-para-usuario";
    $eventTemplate = getEventMailConfig($conn, $event);

    $recipient = "";
    if (!empty($row['contato_email'])) {
        $recipient = $row['contato_email'];
    } else {
        $recipient = $openerEmail;
    }

    // send_mail($event, $recipient, $rowconfmail, $eventTemplate, $VARS);

    /* Disparo do e-mail (ou fila no banco) para a área de atendimento */
    $mail = (new Email())->bootstrap(
        transvars($eventTemplate['msg_subject'], $VARS),
        transvars($eventTemplate['msg_body'], $VARS),
        $recipient,
        $eventTemplate['msg_fromname'],
        $numero
    );

    if (!$mail->{$mailSendMethod}()) {
        $mailNotification .= "<hr>" . TRANS('EMAIL_NOT_SENT') . "<hr>" . $mail->error->getMessage();
    }
}


$return['message'] = "Sucesso!";
echo json_encode($return);
// dump($return);
return true;

