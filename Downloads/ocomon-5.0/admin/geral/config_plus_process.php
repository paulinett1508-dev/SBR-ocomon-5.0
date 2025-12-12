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
require_once __DIR__ . "/" . "../../includes/classes/ConnectPDO.php";


use includes\classes\ConnectPDO;
use OcomonApi\Models\AccessToken;

$conn = ConnectPDO::getInstance();
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

$ticket_by_mail_app = "ticket_by_email";
$key_name_token = "API_TICKET_BY_MAIL_TOKEN";


/* Seção de dados referentes ao LDAP */
$data['auth_type_ldap'] = (isset($post['auth_type_ldap']) ? ($post['auth_type_ldap'] == "yes" ? 1 : 0) : 0);
$data['ldap_host'] = (isset($post['ldap_host']) ? noHtml($post['ldap_host']) : "");
$data['ldap_port'] = (isset($post['ldap_port']) ? (int)noHtml($post['ldap_port']) : 389);
$data['ldap_domain'] = (isset($post['ldap_domain']) ? noHtml($post['ldap_domain']) : "");
$data['ldap_basedn'] = (isset($post['ldap_basedn']) ? noHtml($post['ldap_basedn']) : "");
$data['ldap_field_fullname'] = (isset($post['ldap_field_fullname']) ? noHtml($post['ldap_field_fullname']) : "");
$data['ldap_field_email'] = (isset($post['ldap_field_email']) ? noHtml($post['ldap_field_email']) : "");
$data['ldap_field_phone'] = (isset($post['ldap_field_phone']) ? noHtml($post['ldap_field_phone']) : "");
$data['ldap_area'] = (isset($post['ldap_area']) ? noHtml($post['ldap_area']) : "");
$data['ldap_client'] = (isset($post['ldap_client']) ? noHtml($post['ldap_client']) : "");
$data['ldap_user'] = (isset($post['ldap_user']) ? noHtml($post['ldap_user']) : "");
$data['ldap_password'] = (isset($post['ldap_password']) ? noHtml($post['ldap_password']) : "");


/* Seção referente a abertura de chamados por e-mail */
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
$data['input_tags'] = (isset($post['input_tags']) ? noHtml($post['input_tags']) : "");


$idSystemUser = 1;
$userInfo = "";


if ($data['system_user']) {
    $userInfo = getUserInfo($conn, 0, $data['system_user']);
    if (count($userInfo)) {
        $idSystemUser = $userInfo['user_id'];
    }
    else {
        $data['success'] = false;
        $data['field_id'] = "system_user";

        $data['message'] = message('warning', '', TRANS('INVALID_USER'), '');
        echo json_encode($data);
        return false;
    }
}

$data['clientFromEmail'] = (isset($post['clientFromEmail']) ? noHtml($post['clientFromEmail']) : "");
$data['area'] = (isset($post['area']) ? noHtml($post['area']) : "");
$data['status'] = (isset($post['status']) ? noHtml($post['status']) : "");
$data['opening_channel'] = (isset($post['opening_channel']) ? noHtml($post['opening_channel']) : "");



/* Seção referente ao formulário de abertura de chamados sem autenticação */
$data['allow_unregister_open'] = (isset($post['allow_unregister_open']) ? ($post['allow_unregister_open'] == "yes" ? 1 : 0) : 0);
$data['user_blind_tickets'] = (isset($post['user_blind_tickets']) ? noHtml($post['user_blind_tickets']) : "");
$data['client_blind_tickets'] = (isset($post['client_blind_tickets']) ? noHtml($post['client_blind_tickets']) : "");
$data['screen_profile_blind_tickets'] = (isset($post['screen_profile_blind_tickets']) ? noHtml($post['screen_profile_blind_tickets']) : "");
$data['blind_status'] = (isset($post['blind_status']) ? noHtml($post['blind_status']) : "");
$data['blind_channel'] = (isset($post['blind_channel']) ? noHtml($post['blind_channel']) : "");
$data['blind_tags'] = (isset($post['blind_tags']) ? noHtml($post['blind_tags']) : "");
$data['captcha_case'] = (isset($post['captcha_case']) ? ($post['captcha_case'] == "yes" ? 1 : 0) : 0);


$data['user_blind_tickets'] = (!empty($data['user_blind_tickets']) ? getUserInfo($conn, $data['user_blind_tickets'])['user_id'] : '');
$data['screen_profile_blind_tickets'] = (!empty($data['screen_profile_blind_tickets']) ? getScreenInfo($conn, $data['screen_profile_blind_tickets'])['conf_cod'] : '');



/* Checagem de preenchimento dos campos obrigatórios*/
if ($data['action'] == "edit") {


    /* Autenticação via LDAP habilitada */
    if ($data['auth_type_ldap']) {
        if (empty($data['ldap_host'])) {
            $data['success'] = false;
            $data['field_id'] = "ldap_host";
        } elseif (empty($data['ldap_domain'])) {
            $data['success'] = false;
            $data['field_id'] = "ldap_domain";
        } elseif (empty($data['ldap_basedn'])) {
            $data['success'] = false;
            $data['field_id'] = "ldap_basedn";
        } elseif (empty($data['ldap_field_fullname'])) {
            $data['success'] = false;
            $data['field_id'] = "ldap_field_fullname";
        } elseif (empty($data['ldap_field_email'])) {
            $data['success'] = false;
            $data['field_id'] = "ldap_field_email";
        } elseif (empty($data['ldap_field_phone'])) {
            $data['success'] = false;
            $data['field_id'] = "ldap_field_phone";
        } elseif (empty($data['ldap_area'])) {
            $data['success'] = false;
            $data['field_id'] = "ldap_area";
        } 

        
    }



    /* Abertura por e-mail habilitada */
    if ($data['allow_open_by_email']) {
        if ($data['mail_account'] == "") {
            $data['success'] = false;
            $data['field_id'] = "mail_account";
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
        } elseif ($data['area'] == "") {
            $data['success'] = false;
            $data['field_id'] = "area";
        } elseif ($data['status'] == "") {
            $data['success'] = false;
            $data['field_id'] = "status";
        } elseif (!empty($data['input_tags'])) {
    
            $tooShortTag = false;
            $arrayTags = explode(',', $data['input_tags']);
            
            foreach ($arrayTags as $tag) {
                if (strlen((string)$tag) < 4)
                    $tooShortTag = true;
            }
        
            if ($tooShortTag) {
                $data['success'] = false; 
                $data['field_id'] = "input_tags";
                $data['message'] = message('warning', '', TRANS('ERROR_MIN_SIZE_OF_TAGNAME'), '');
                echo json_encode($data);
                return false;
            }
        
        } elseif ($data['opening_channel'] == "") {
            $data['success'] = false;
            $data['field_id'] = "opening_channel";
        } 
    }
    
    
    if ($data['allow_unregister_open']) {
        /* dados sobre a abertura de chamados sem usuário autenticado */
        if (empty($data['user_blind_tickets'])) {
            $data['success'] = false;
            $data['field_id'] = "user_blind_tickets";
        } elseif (empty($data['screen_profile_blind_tickets'])) {
            $data['success'] = false;
            $data['field_id'] = "screen_profile_blind_tickets";
        } elseif (empty($data['blind_status'])) {
            $data['success'] = false;
            $data['field_id'] = "blind_status";
        } elseif (empty($data['blind_channel'])) {
            $data['success'] = false;
            $data['field_id'] = "blind_channel";
        }
    }


    if (!empty($data['blind_tags'])) {

        $tooShortTag = false;
        $arrayTags = explode(',', $data['blind_tags']);
        
        foreach ($arrayTags as $tag) {
            if (strlen((string)$tag) < 4)
                $tooShortTag = true;
        }
    
        if ($tooShortTag) {
            $data['success'] = false; 
            $data['field_id'] = "blind_tags";
            $data['message'] = message('warning', '', TRANS('ERROR_MIN_SIZE_OF_TAGNAME'), '');
            echo json_encode($data);
            return false;
        }
    }



    if ($data['success'] == false) {
        $data['message'] = message('warning', '', TRANS('MSG_EMPTY_DATA'), '');
        echo json_encode($data);
        return false;
    }


    if (!empty($data['ldap_host'])) {
        if (!filter_var($data['ldap_host'], FILTER_VALIDATE_DOMAIN)) {
            /* FILTER_VALIDATE_DOMAIN */
            $data['success'] = false; 
            $data['field_id'] = "ldap_host";
            $data['message'] = message('warning', '', TRANS('WRONG_FORMATTED_URL'), '');
            echo json_encode($data);
            return false;
        }
    }
    
    if (!empty($data['mail_account'])) {
        if (!filter_var($data['mail_account'], FILTER_VALIDATE_EMAIL)) {
            /* FILTER_VALIDATE_DOMAIN */
            $data['success'] = false;
            $data['field_id'] = "mail_account";
            $data['message'] = message('warning', '', TRANS('WRONG_FORMATTED_URL'), '');
            echo json_encode($data);
            return false;
        }
    }
    
    if (!empty($data['imap_address'])) {
        if (!filter_var($data['imap_address'], FILTER_VALIDATE_DOMAIN)) {
            /* FILTER_VALIDATE_DOMAIN */
            $data['success'] = false;
            $data['field_id'] = "imap_address";
            $data['message'] = message('warning', '', TRANS('WRONG_FORMATTED_URL'), '');
            echo json_encode($data);
            return false;
        }
    }
    

    if (!empty($data['mail_port'])) {
        if (!filter_var($data['mail_port'], FILTER_VALIDATE_INT)) {
            /* FILTER_VALIDATE_DOMAIN */
            $data['success'] = false;
            $data['field_id'] = "mail_port";
            $data['message'] = message('warning', '', TRANS('MSG_ERROR_WRONG_FORMATTED'), '');
            echo json_encode($data);
            return false;
        }
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

/* Todas as informações que estiverem nesse array serão atualizadas no banco */
$dataUpd = [];

$dataUpd['AUTH_TYPE'] = ($data['auth_type_ldap'] ? 'LDAP' : 'SYSTEM');
$dataUpd['LDAP_HOST'] = $data['ldap_host'];
$dataUpd['LDAP_PORT'] = $data['ldap_port'];
$dataUpd['LDAP_DOMAIN'] = $data['ldap_domain'];
$dataUpd['LDAP_BASEDN'] = $data['ldap_basedn'];
$dataUpd['LDAP_FIELD_FULLNAME'] = $data['ldap_field_fullname'];
$dataUpd['LDAP_FIELD_EMAIL'] = $data['ldap_field_email'];
$dataUpd['LDAP_FIELD_PHONE'] = $data['ldap_field_phone'];
$dataUpd['LDAP_AREA_TO_BIND_NEWUSERS'] = $data['ldap_area'];
$dataUpd['LDAP_CLIENT_TO_BIND_NEWUSERS'] = $data['ldap_client'];

$dataUpd['ALLOW_OPEN_TICKET_BY_EMAIL'] = $data['allow_open_by_email'];
$dataUpd['MAIL_GET_ADDRESS'] = $data['mail_account'];
if (!empty($data['account_password']))
    $dataUpd['MAIL_GET_PASSWORD'] = $data['account_password'];
$dataUpd['MAIL_GET_IMAP_ADDRESS'] = $data['imap_address'];
$dataUpd['MAIL_GET_PORT'] = $data['mail_port'];
$dataUpd['MAIL_GET_CERT'] = $data['ssl_cert'];
$dataUpd['MAIL_GET_MAILBOX'] = $data['mailbox'];
$dataUpd['MAIL_GET_SUBJECT_CONTAINS'] = $data['subject_has'];
$dataUpd['MAIL_GET_BODY_CONTAINS'] = $data['body_has'];
$dataUpd['MAIL_GET_DAYS_SINCE'] = $data['days_since'];
$dataUpd['MAIL_GET_MARK_SEEN'] = $data['mark_seen'];
$dataUpd['MAIL_GET_MOVETO'] = $data['move_to'];
$dataUpd['API_TICKET_BY_MAIL_USER'] = $data['system_user'];
$dataUpd['API_TICKET_BY_MAIL_APP'] = $ticket_by_mail_app;
$dataUpd['API_TICKET_BY_MAIL_CLIENT'] = $data['clientFromEmail'];
$dataUpd['API_TICKET_BY_MAIL_AREA'] = $data['area'];
$dataUpd['API_TICKET_BY_MAIL_STATUS'] = $data['status'];
$dataUpd['API_TICKET_BY_MAIL_CHANNEL'] = $data['opening_channel'];
$dataUpd['API_TICKET_BY_MAIL_TAG'] = $data['input_tags'];

$dataUpd['ANON_OPEN_ALLOW'] = $data['allow_unregister_open'];
$dataUpd['ANON_OPEN_SCREEN_PFL'] = $data['screen_profile_blind_tickets'];
$dataUpd['ANON_OPEN_USER'] = $data['user_blind_tickets'];
$dataUpd['ANON_OPEN_CLIENT'] = $data['client_blind_tickets'];
$dataUpd['ANON_OPEN_STATUS'] = $data['blind_status'];
$dataUpd['ANON_OPEN_CHANNEL'] = $data['blind_channel'];
$dataUpd['ANON_OPEN_TAGS'] = $data['blind_tags'];
$dataUpd['ANON_OPEN_CAPTCHA_CASE'] = $data['captcha_case'];


/* Processamento */
if ($data['action'] == "edit") {

    /* Verificação de CSRF */
    if (!csrf_verify($post)) {
        $data['success'] = false;
        $data['message'] = message('warning', 'Ooops!', TRANS('FORM_ALREADY_SENT'), '');
        echo json_encode($data);
        return false;
    }

    /* Checa se o usuário para abertura de chamados já possui hash, caso contrário, cria */
    if (empty($userInfo['hash'])) {
        $hash = pass_hash($userInfo['password']);
        $sql = "UPDATE usuarios SET `password` = null, `hash` = '". $hash ."' WHERE user_id = :user_id ";
        
        try {
            $res = $conn->prepare($sql);
            $res->bindParam(':user_id', $userInfo['user_id']);
            $res->execute();
        }
        catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage();
        }
    }

    /* Atualização da configuração geral */
    foreach ($dataUpd as $key => $value) {
        
        /* Checar se a chave existe */
        $sqlFind = "SELECT id FROM config_keys WHERE key_name = '{$key}' ";
        $resFind = $conn->query($sqlFind);
        if ($resFind->rowCount()) {
            /* A chave já existe - update */

            $sql = "UPDATE config_keys SET 
                    key_value = " . dbField($value, 'text') . " 
                WHERE key_name = '{$key}';
            ";
            try {
                $conn->exec($sql);
                $data['success'] = true;
                $data['message'] = TRANS('MSG_SUCCESS_EDIT');
            } catch (Exception $e) {
                $data['success'] = false;
                $data['message'] = TRANS('MSG_ERR_SAVE_RECORD') . "<hr>" . $sql . "<hr>" . $e->getMessage();
                $_SESSION['flash'] = message('danger', '', $data['message'], '');
                echo json_encode($data);
                return false;
            }
        } else {
            /* Chave não existe - insert */
            $sql = "INSERT INTO config_keys 
                    (
                        key_name, key_value
                    )
                    VALUES 
                    (
                        '{$key}', " . dbField($value, 'text') . "
                    )
            ";
            try {
                $conn->exec($sql);
            }
            catch (Exception $e) {
                $exception .= "<hr>" . $e->getMessage();
            }
        }
        
    }


    /* Se alguma tag for nova, gravar na tabela de referência: input_tags */
    if (!empty($data['input_tags'])) {
        $arrayTags = explode(',', $data['input_tags']);
        saveNewTags($conn, $arrayTags);
    }

    /* Montar o Token */
    $tokenData = array(
        "exp" => time() + (60 * 60 * 24 * 365),
        "app" => $ticket_by_mail_app
    );

    /* Gerar o token (jwt) para a autorização do usuário para abertura de chamados por email */
    $jwt = (new AccessToken())->generate($idSystemUser, $tokenData);

    /* Remover os registros para o APP de abertura de chamados por e-mail pois a abertura por e-mails só é permitida para um user */
    $sql = "DELETE FROM access_tokens WHERE app = :app ";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':app', $ticket_by_mail_app, PDO::PARAM_STR);
        $res->execute();
    }
    catch (Exception $e) {
        $exception .= "<hr>" . $e->getMessage();
    }

    /* Inserção do novo token para abertura de chamados por e-mail */
    $sql = "INSERT INTO access_tokens (
        user_id, app, token
    ) VALUES (
        :user_id, :app, :jwt
    )";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':user_id', $idSystemUser, PDO::PARAM_INT);
        $res->bindParam(':app', $ticket_by_mail_app, PDO::PARAM_STR);
        $res->bindParam(':jwt', $jwt, PDO::PARAM_STR);
        $res->execute();

        /* ATualiza o token na configuracao para abertura de chamados por e-mail */
        $sql = "UPDATE config_keys SET key_value = :token WHERE key_name = :key_name ";
        try {
            $res = $conn->prepare($sql);
            $res->bindParam(':token', $jwt, PDO::PARAM_STR);
            $res->bindParam(':key_name', $key_name_token, PDO::PARAM_STR);

            $res->execute();
        } catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage();
        }
    } catch (Exception $e) {
        $exception .= "<hr>" . $e->getMessage();
    }
    /* Final do processo relacionado ao token para abertura de chamados por email */
}

if (!empty($exception)) {
    $data['message'] = $data['message'] . "<hr>" . $exception;
}

$_SESSION['flash'] = message('success', '', $data['message'], '');
echo json_encode($data);
return false;

echo json_encode($data);
