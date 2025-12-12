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


if (!isset($_POST['numero'])) {
    exit();
}

$exception = "";
$sent = false;
$numero = (int) $_POST['numero'];
$user = (int) $_SESSION['s_uid'];
$now = date("Y-m-d H:i:s");
$entry = TRANS('TXTAREA_IN_ATTEND_BY') . ' ' . $_SESSION['s_usuario'];
$data['success'] = true;
$data['message'] = "";


$data['entry'] = (isset($_POST['entry']) && !empty($_POST['entry']) ? noHtml($_POST['entry']) : "");

if (empty($data['entry'])) {
    $data['success'] = false;
    $data['message'] = message('warning', '', TRANS('MSG_EMPTY_DATA'), '');
    $data['field_id'] = "entry";
    echo json_encode($data);
    return false;
}


$row = getTicketData($conn, $numero);

if (!count($row)) {
    $data['success'] = false;
    $data['message'] = message('danger', '', TRANS('NO_RECORDS_FOUND'), '');
    echo json_encode($data);
    return false;
}

/* Informações sobre a área destino */
$rowAreaTo = getAreaInfo($conn, $row['sistema']);
/* Configurações de e-mail */
$rowconfmail = getMailConfig($conn);
/* E-mail de quem abriu o chamado */
$openerEmail = getOpenerEmail($conn, $numero);

//Checa se já existe algum registro de log - caso não existir grava o estado atual
$firstLog = firstLog($conn, $numero, 'NULL', 1);



/* Para pegar o estado da ocorrência antes da atualização e permitir a gravação do log de modificações com recordLog() */
/* Array para a funcao recordLog */
$arrayBeforePost = [];
$arrayBeforePost['operador_cod'] = $row['operador'];
$arrayBeforePost['status_cod'] = $row['status'];
$arrayBeforePost['oco_scheduled_to'] = $row['oco_scheduled_to'];



if (!empty($row['data_atendimento'])) {
    $sql = "UPDATE ocorrencias 
            SET 
                status = 2, operador = {$user}, oco_scheduled = 0, oco_scheduled_to = null 
                WHERE numero = '{$numero}'";
} else {
    $sql = "UPDATE ocorrencias 
            SET status = 2, operador = {$user}, data_atendimento = '{$now}', oco_scheduled = 0, oco_scheduled_to = null 
            WHERE numero = '{$numero}'";
}

try {
    $result = $conn->exec($sql);

    /* Tipo de assentamento: 2 - Edição para atendimento */
    $sql = "INSERT INTO assentamentos 
                (ocorrencia, assentamento, `data`, responsavel, tipo_assentamento) 
            values 
                ({$numero}, '" . $data['entry'] . "', '{$now}', {$user}, 2 )";

    try {
        $result = $conn->exec($sql);

    } catch (Exception $e) {
        $exception .= '<hr>' .$e->getMessage();
    }



} catch (Exception $e) {
    $exception .= '<hr>' .$e->getMessage();
    $data['success'] = false;
    $data['message'] = message('danger', '', TRANS('MSG_ERR_DATA_UPDATE') . $exception, '');
    echo json_encode($data);
    return false;
}

/* Gravação da data na tabela tickets_stages */
$stopTimeStage = insert_ticket_stage($conn, $numero, 'stop', 2);
$startTimeStage = insert_ticket_stage($conn, $numero, 'start', 2);



/* Array para a função recordLog */
$afterPost = [];
$afterPost['operador'] = $user;
$afterPost['status'] = 2;
$afterPost['agendadoPara'] = "";

/* Função que grava o registro de alterações do chamado */
$recordLog = recordLog($conn, $numero, $arrayBeforePost, $afterPost, 2);


/* Variáveis de ambiente para os e-mails */
$vars = array();
$vars = getEnvVarsValues($conn, $numero);
$mailSendMethod = 'send';
if ($rowconfmail['mail_queue']) {
    $mailSendMethod = 'queue';
}


if (isset($_POST['sendEmailToArea']) && $_POST['sendEmailToArea'] == 'true') {

    $event = "edita-para-area";
    $eventTemplate = getEventMailConfig($conn, $event);

    // $sent = send_mail($event, $rowAreaTo['email'], $rowconfmail, $eventTemplate, $vars);
    // if (!$sent) {
    //     $exception .= "<hr>" . TRANS('EMAIL_NOT_SENT');
    // }

    /* Disparo do e-mail (ou fila no banco) para a área de atendimento */
    $mail = (new Email())->bootstrap(
        transvars($eventTemplate['msg_subject'], $vars),
        transvars($eventTemplate['msg_body'], $vars),
        $rowAreaTo['email'],
        $eventTemplate['msg_fromname'],
        $numero
    );

    if (!$mail->{$mailSendMethod}()) {
        $exception .= "<hr>" . TRANS('EMAIL_NOT_SENT') . "<hr>" . $mail->message()->getText();
    }
}


if (isset($_POST['sendEmailToUser']) && $_POST['sendEmailToUser'] == 'true') {
    $event = "edita-para-usuario";
    $eventTemplate = getEventMailConfig($conn, $event);

    $recipient = "";
    if (!empty($row['contato_email'])) {
        $recipient = $row['contato_email'];
    } else {
        $recipient = $openerEmail;
    }

    // $sent = send_mail($event, $recipient, $rowconfmail, $eventTemplate, $vars);
    // if (!$sent) {
    //     $exception .= "<hr>" . TRANS('EMAIL_NOT_SENT');
    // }

    /* Disparo do e-mail (ou fila no banco) para a área de atendimento */
    $mail = (new Email())->bootstrap(
        transvars($eventTemplate['msg_subject'], $vars),
        transvars($eventTemplate['msg_body'], $vars),
        $recipient,
        $eventTemplate['msg_fromname'],
        $numero
    );

    if (!$mail->{$mailSendMethod}()) {
        $exception .= "<hr>" . TRANS('EMAIL_NOT_SENT') . "<hr>" . $mail->message()->getText();
    }
}

$_SESSION['flash'] = message('success', '', TRANS('TICKET_GOTTEN_IN') . $exception, '', '');
echo json_encode($data);
return true;


