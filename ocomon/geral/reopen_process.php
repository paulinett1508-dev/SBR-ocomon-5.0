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

$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 3, 1);

$config = getConfig($conn);
if (!$config['conf_allow_reopen']) {
    return false;
}

if (!isset($_POST['numero'])) {
    return false;
}

$numero = (int) $_POST['numero'];

$sqlTicket = "SELECT * FROM ocorrencias WHERE numero = {$numero} AND status = 4 ";
$resultTicket = $conn->query($sqlTicket);
if ($resultTicket->rowCount() == 0) {
    $_SESSION['flash'] = message('danger', '', TRANS('MSG_TICKET_CANT_REOPEN'), '', '');
    $data['success'] = false;
    $data['message'] = message('danger', '', TRANS('MSG_TICKET_CANT_REOPEN'), '', '');
    echo json_encode($data);
    return false;
}
$row = $resultTicket->fetch();

if (!count($row)) {
    $data['success'] = false;
    $data['message'] = message('danger', '', TRANS('NO_RECORDS_FOUND'), '');
    echo json_encode($data);
    return false;
}




/* Apenas o solicitante ou o responsável técnico podem reabrir o chamado */
if ($_SESSION['s_uid'] != $row['aberto_por'] && $_SESSION['s_uid'] != $row['operador']) {
    return false;
}

/* Avalia se está no prazo para reabrir */
if ($config['conf_reopen_deadline']) {
    $date1 = new DateTime($row['data_fechamento']);
    $date2 = new DateTime();

    if ($date1->diff($date2)->days > $config['conf_reopen_deadline']) {
        return false;
    }
}

$data['reopen_entry'] = (isset($_POST['reopen_entry']) && !empty($_POST['reopen_entry']) ? noHtml($_POST['reopen_entry']) : "");

if (empty($data['reopen_entry'])) {
    $data['success'] = false;
    $data['message'] = message('warning', '', TRANS('MSG_EMPTY_DATA'), '');
    $data['field_id'] = "reopen_entry";
    echo json_encode($data);
    return false;
}



$exception = "";
$sent = false;
$data['success'] = true;
$data['message'] = "";
$user = (int)$_SESSION['s_uid'];
// $entry = TRANS('TICKET_REOPENED_BY') . ' ' . $_SESSION['s_usuario'];
$entry = $data['reopen_entry'];

/* Informações sobre a área destino */
$rowAreaTo = getAreaInfo($conn, $row['sistema']);
/* Configurações de e-mail */
$rowconfmail = getMailConfig($conn);
/* E-mail de quem abriu o chamado */
$openerEmail = getOpenerEmail($conn, $numero);

//Checa se já existe algum registro de log - caso não existir grava o estado atual
$firstLog = firstLog($conn, $numero,'NULL', 1);


/* Array para a funcao recordLog */
$arrayBeforePost = [];
$arrayBeforePost['operador_cod'] = $row['operador'];
$arrayBeforePost['status_cod'] = $row['status'];


$reopeningStatus = $config['conf_status_reopen'] ?? 1;


$sql = "UPDATE ocorrencias SET `status`= {$reopeningStatus}, data_fechamento = NULL WHERE numero = " . $numero . "";
try {
    $conn->exec($sql);
    $qryDelSolution = "DELETE FROM solucoes WHERE numero = " . $numero . "";
    $conn->exec($qryDelSolution);

    /* Tipo de assentamento: 9 - reabertura */
    $sql = "INSERT INTO assentamentos 
                (ocorrencia, assentamento, `data`, responsavel, tipo_assentamento) 
            values 
                ({$numero}, '{$entry}', '".date('Y-m-d H:i:s')."', {$user}, 9 )";

    try {
        $result = $conn->exec($sql);
    }
    catch (Exception $e) {
        $exception .= "<hr>" . $e->getMessage();
    }

    /* Gravação da data na tabela tickets_stages */
    $stopTimeStage = insert_ticket_stage($conn, $numero, 'stop', $reopeningStatus);
    $startTimeStage = insert_ticket_stage($conn, $numero, 'start', $reopeningStatus);

    /* Array para a função recordLog */
    $afterPost = [];
    $afterPost['operador'] = $user;
    $afterPost['status'] = 1;

    /* Função que grava o registro de alterações do chamado */
    $recordLog = recordLog($conn, $numero, $arrayBeforePost, $afterPost, 3);


}
catch (Exception $e) {
    $exception .= "<hr>" . $e->getMessage();
    $data['success'] = false;
    $data['message'] = message('danger', '', TRANS('MSG_ERR_DATA_UPDATE') . $exception, '');
    echo json_encode($data);
    return false;
}


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

$_SESSION['flash'] = message('success', '', TRANS('MSG_TICKET_REOPENED_SUCCESSFULY') . $exception, '', '');
echo json_encode($data);
return true;