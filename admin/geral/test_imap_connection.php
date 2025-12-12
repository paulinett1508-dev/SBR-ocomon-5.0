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

if (!isset($_SESSION['s_logado']) || $_SESSION['s_logado'] == 0) {
    $_SESSION['session_expired'] = 1;
    echo "<script>top.window.location = '../../index.php'</script>";
    exit;
}

require_once __DIR__ . "/" . "../../includes/include_basics_only.php";

if (version_compare(phpversion(), '8.1', '<' )) {
    require __DIR__ . "/" . "../../ocomon/open_tickets_by_email/ddeboer_imap_older/vendor/autoload.php";
} else {
    require __DIR__ . "/" . "../../ocomon/open_tickets_by_email/ddeboer_imap/vendor/autoload.php";
}


use Ddeboer\Imap\Server;

$post = $_POST;

$erro = false;
$exception = "";
$data = [];
$data['success'] = true;
$data['message'] = "";
$data['cod'] = (isset($post['cod']) ? intval($post['cod']) : "");
$data['numero'] = (isset($post['numero']) ? intval($post['numero']) : "");
$data['action'] = $post['action'];
$data['field_id'] = "";


$data['allow_open_by_email'] = (isset($post['allow_open_by_email']) ? ($post['allow_open_by_email'] == "yes" ? 1 : 0) : 0);
$data['mail_account'] = (isset($post['mail_account']) ? noHtml($post['mail_account']) : "");
$data['imap_address'] = (isset($post['imap_address']) ? noHtml($post['imap_address']) : "");
$data['account_password'] = (isset($post['account_password']) ? $post['account_password'] : "");
$data['mail_port'] = (isset($post['mail_port']) ? noHtml($post['mail_port']) : "");
$data['ssl_cert'] = (isset($post['ssl_cert']) ? ($post['ssl_cert'] == "yes" ? 1 : 0) : 0);
$data['mailbox'] = (isset($post['mailbox']) ? noHtml($post['mailbox']) : "");
$data['subject_has'] = (isset($post['subject_has']) ? noHtml($post['subject_has']) : "");
$data['body_has'] = (isset($post['body_has']) ? noHtml($post['body_has']) : "");

$data['days_since'] = (isset($post['days_since']) ? noHtml($post['days_since']) : "1");
$data['days_since'] = (int)$data['days_since'];

$data['mark_seen'] = (isset($post['mark_seen']) ? ($post['mark_seen'] == "yes" ? 1 : 0) : 0);
$data['move_to'] = (isset($post['move_to']) ? noHtml($post['move_to']) : "");
$data['system_user'] = (isset($post['system_user']) ? noHtml($post['system_user']) : "");
$data['system_user_password'] = (isset($post['system_user_password']) ? $post['system_user_password'] : "");

$data['area'] = (isset($post['area']) ? noHtml($post['area']) : "");
$data['status'] = (isset($post['status']) ? noHtml($post['status']) : "");
$data['opening_channel'] = (isset($post['opening_channel']) ? noHtml($post['opening_channel']) : "");

/* Checagem de preenchimento dos campos obrigatórios para a testagem*/
if ($data['action'] == "edit") {

    if ($data['mail_account'] == "") {
        $data['success'] = false; 
        $data['field_id'] = "mail_account";
    } elseif ($data['account_password'] == "") {
        $data['success'] = false; 
        $data['field_id'] = "account_password";

        $data['message'] = message('warning', '', TRANS('TEST_CONNECTION_NEED_PASS'), '');
        echo json_encode($data);
        return false;
    } elseif ($data['imap_address'] == "") {
        $data['success'] = false; 
        $data['field_id'] = "imap_address";
    } elseif ($data['mail_port'] == "") {
        $data['success'] = false; 
        $data['field_id'] = "mail_port";
    } elseif ($data['mailbox'] == "") {
        $data['success'] = false; 
        $data['field_id'] = "mailbox";
    } elseif ($data['move_to'] == "") {
        $data['success'] = false; 
        $data['field_id'] = "move_to";
    } elseif ($data['system_user'] == "") {
        $data['success'] = false; 
        $data['field_id'] = "system_user";
    } /* elseif ($data['area'] == "") {
        $data['success'] = false; 
        $data['field_id'] = "area";
    } */ elseif ($data['status'] == "") {
        $data['success'] = false; 
        $data['field_id'] = "status";
    } elseif ($data['opening_channel'] == "") {
        $data['success'] = false; 
        $data['field_id'] = "opening_channel";
    } 

    
    if ($data['success'] == false) {
        $data['message'] = message('warning', '', TRANS('MSG_EMPTY_DATA'), '');
        echo json_encode($data);
        return false;
    }


    if (!filter_var($data['mail_account'], FILTER_VALIDATE_EMAIL)) {
        /* FILTER_VALIDATE_DOMAIN */
        $data['success'] = false; 
        $data['field_id'] = "mail_account";
        $data['message'] = message('warning', '', TRANS('WRONG_FORMATTED_URL'), '');
        echo json_encode($data);
        return false;
    }

    if (!filter_var($data['imap_address'], FILTER_VALIDATE_DOMAIN)) {
        /* FILTER_VALIDATE_DOMAIN */
        $data['success'] = false; 
        $data['field_id'] = "imap_address";
        $data['message'] = message('warning', '', TRANS('WRONG_FORMATTED_URL'), '');
        echo json_encode($data);
        return false;
    }
    
    if (!filter_var($data['mail_port'], FILTER_VALIDATE_INT)) {
        /* FILTER_VALIDATE_DOMAIN */
        $data['success'] = false; 
        $data['field_id'] = "mail_port";
        $data['message'] = message('warning', '', TRANS('MSG_ERROR_WRONG_FORMATTED'), '');
        echo json_encode($data);
        return false;
    }

    if (!filter_var($data['days_since'], FILTER_VALIDATE_INT) || $data['days_since'] < 1 || $data['days_since'] > 5) {
        /* FILTER_VALIDATE_DOMAIN */
        $data['success'] = false; 
        $data['field_id'] = "days_since";
        $data['message'] = message('warning', '', TRANS('ERROR_RANGE_DAYS_SINCE_TO_FETCH'), '');
        echo json_encode($data);
        return false;
    }

}

$cert = ($data['ssl_cert'] == 0 ? '/novalidate-cert' : '');
$server = new Server(
    $data['imap_address'],
    $data['mail_port'],
    '/imap/ssl' . $cert
);

try {
    $connection = $server->authenticate($data['mail_account'], $data['account_password']);
}
catch (Exception $e) {
    $exception .= "<hr>" . $e->getMessage();
    $data['success'] = false;
    $data['message'] = message('danger', '', TRANS('CONNECTION_ERROR') . $exception, '');
    echo json_encode($data);
    return false;
}


$hasMailbox = $connection->hasMailbox($data['mailbox']);

if ($hasMailbox) {
    try {
        $mailbox = $connection->getMailbox($data['mailbox']);
    } catch (Exception $e) {
        $exception .= "<hr>" . $e->getMessage();
        $data['success'] = false;
        $data['field_id'] = "mailbox";
        $data['message'] = message('danger', '', TRANS('MAILBOX_ERROR') . $exception, '');
        echo json_encode($data);
        return false;
    }
} else {
    $data['success'] = false;
    $data['field_id'] = "mailbox";
    $data['message'] = message('danger', '', TRANS('MAILBOX_ERROR') . $exception, '');
    echo json_encode($data);
    return false;
}


// try {
//     $mailboxTo = $connection->getMailbox($data['move_to']);
// }
// catch (Exception $e) {
//     $exception .= "<hr>" . $e->getMessage();
//     $data['success'] = false;
//     $data['field_id'] = "move_to";
//     $data['message'] = message('danger', '', TRANS('MAILBOX_ERROR') . $exception, '');
//     echo json_encode($data);
//     return false;
// }


$data['success'] = true;
$data['message'] = message('success', 'Yeaap!', TRANS('CONNECTION_SUCCESS') . $exception, '');
echo json_encode($data);
return true;



