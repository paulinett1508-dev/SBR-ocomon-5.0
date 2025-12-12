<?php session_start();
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


require_once __DIR__ . "/" . "../../includes/include_geral_new.inc.php";
require_once __DIR__ . "/" . "../../includes/classes/ConnectPDO.php";

use OcomonApi\Support\Email;
use includes\classes\ConnectPDO;

$conn = ConnectPDO::getInstance();

$post = $_POST;


$erro = false;
$exception = "";
$screenNotification = "";
$mailNotification = "";
$data = [];
$data['success'] = true;
$data['message'] = "";
$data['cod'] = (isset($post['cod']) ? intval($post['cod']) : "");
$data['action'] = $post['action'];
$data['field_id'] = "";

$data['login_name'] = (isset($post['login_name']) ? noHtml($post['login_name']) : "");
$data['email'] = (isset($post['email']) ? noHtml($post['email']) : "");
$data['user_id'] = "";
$userData = [];

$row_config = getConfig($conn);


/* Validações */
if (empty($data['login_name']) && empty($data['email'])) {

    $data['success'] = false;
    $data['field_id'] = 'login_name';
    $data['message'] = message('warning', 'Ooops!', TRANS('FILL_USERNAME_OR_EMAIL'), '');
    echo json_encode($data);
    return false;
}

if ($data['login_name'] && $data['email']) {

    $data['success'] = false;
    $data['field_id'] = 'login_name';
    $data['message'] = message('warning', 'Ooops!', TRANS('FILL_ONLY_ONE_OF_THE_FIELDS'), '');
    echo json_encode($data);
    return false;
}


if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    $data['success'] = false;
    $data['field_id'] = 'email';
    $data['message'] = message('warning', 'Ooops!', TRANS('WRONG_FORMATTED_URL'), '');
    echo json_encode($data);
    return false;
}


if (!empty($data['login_name'])) {
    $sql = "SELECT user_id, nome, email FROM usuarios WHERE login = :user ";
    $res = $conn->prepare($sql);
    $res->bindParam(':user', $data['login_name']);
    $res->execute();

    if (!$res->rowCount()) {
        $data['success'] = false;
        $data['field_id'] = 'login_name';
        $data['message'] = message('warning', 'Ooops!', TRANS('USERNAME_OR_EMAIL_NOT_FOUND'), '');
        echo json_encode($data);
        return false;
    }
    $userData = $res->fetch();
    $data['user_id'] = $userData['user_id'];
    $data['name'] = $userData['nome'];
    $data['mail_to'] = $userData['email'];
}

if (!empty($data['email'])) {
    $sql = "SELECT user_id, nome, email FROM usuarios WHERE email = :email ";
    $res = $conn->prepare($sql);
    $res->bindParam(':email', $data['email']);
    $res->execute();

    if (!$res->rowCount()) {
        $data['success'] = false;
        $data['field_id'] = 'email';
        $data['message'] = message('warning', 'Ooops!', TRANS('USERNAME_OR_EMAIL_NOT_FOUND'), '');
        echo json_encode($data);
        return false;
    } elseif ($res->rowCount() > 1) {
        $data['success'] = false;
        $data['field_id'] = 'email';
        $data['message'] = message('warning', 'Ooops!', TRANS('EMAIL_TO_OTHER_ACCOUNTS'), '');
        echo json_encode($data);
        return false;
    }
    $userData = $res->fetch();
    $data['user_id'] = $userData['user_id'];
    $data['name'] = $userData['nome'];
    $data['mail_to'] = $userData['email'];
}



$data['rand'] = md5(uniqid(rand(), true));
$data['forget_link'] = $row_config['conf_ocomon_site'] . '/setNewPass.php?code=' . $data['user_id'] . '|' . $data['rand'];


if (!csrf_verify($post)) {
    
    $data = [];
    $data['success'] = false;
    $data['message'] = message('warning', 'Ooops!', TRANS('FORM_ALREADY_SENT'), '');

    echo json_encode($data);
    return false;
}


$sql = "UPDATE usuarios SET forget = :access_code WHERE user_id = :user_id ";
try {
    $res = $conn->prepare($sql);
    $res->bindParam(':access_code', $data['rand']);
    $res->bindParam(':user_id', $data['user_id']);

    $res->execute();


    $VARS = array();
    $VARS['%usuario%'] = explode(' ', $data['name'])[0];
    $VARS['%site%'] = "<a href='" . $row_config['conf_ocomon_site'] . "'>" . $row_config['conf_ocomon_site'] . "</a>";
    $VARS['%forget_link%'] = $data['forget_link'];

    $rowconf = getMailConfig($conn);
    $event = 'forget-password';
    $eventTemplate = getEventMailConfig($conn, $event);

    /* Disparo do e-mail para o usuário */
    $mail = (new Email())->bootstrap(
        transvars($eventTemplate['msg_subject'], $VARS),
        transvars($eventTemplate['msg_body'], $VARS),
        $data['mail_to'],
        $eventTemplate['msg_fromname'],
    );

    if (!$mail->send()) {
        $mailNotification .= "<hr>" . TRANS('EMAIL_NOT_SENT') . "<hr>" . $mail->message()->getText();
    }
} catch (Exception $e) {
    $data = [];
    $exception .= "<hr>" . $e->getMessage();
    $data['success'] = false;
    $data['message'] = message('danger', 'Ooops!', TRANS('MSG_SOMETHING_GOT_WRONG'),'');
    $_SESSION['flash'] = message('success', '', $data['message'], '');
    echo json_encode($data);
    return false;
}

$data = [];

$data['success'] = true;
$data['message'] = TRANS('PASS_RECOVERY_REQUEST_DONE');
$_SESSION['flash'] = message('success', '', $data['message'] . $exception . $mailNotification, '');


echo json_encode($data);
return false;

