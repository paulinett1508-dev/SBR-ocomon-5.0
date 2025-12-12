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
$data['action'] = $post['action'];
$data['field_id'] = "";


$data['app_name'] = (isset($post['app_name']) && !empty($post['app_name']) ? noHtml($post['app_name']) : "");
$data['user_id'] = (isset($post['user_id']) && !empty($post['user_id']) ? noHtml($post['user_id']) : "");
$data['system_user'] = (isset($post['system_user']) && !empty($post['system_user']) ? noHtml($post['system_user']) : "");
$data['user_id'] = (!empty($data['user_id'])) ? $data['user_id'] : $data['system_user'];
$data['lifespan'] = (isset($post['lifespan']) && !empty($post['lifespan']) ? noHtml($post['lifespan']) : "");

$app_ticket_by_email_token = 'API_TICKET_BY_MAIL_TOKEN';

/* Checagem de preenchimento dos campos obrigatórios*/
if ($data['action'] == "new" || $data['action'] == "edit") {

    if ($data['app_name'] == "") {
        $data['success'] = false;
        $data['field_id'] = "app_name";
    } elseif ($data['user_id'] == "") {
        $data['success'] = false;
        $data['field_id'] = "user_id";
    } elseif ($data['lifespan'] == "") {
        $data['success'] = false;
        $data['field_id'] = "lifespan";
    } 


    if ($data['success'] == false) {
        $data['message'] = message('warning', '', TRANS('MSG_EMPTY_DATA'), '');
        echo json_encode($data);
        return false;
    }

    if (!filter_var($data['lifespan'], FILTER_VALIDATE_INT)) {
        $data['message'] = message('warning', '', TRANS('MSG_ERROR_WRONG_FORMATTED'), '');
        echo json_encode($data);
        return false;
    }

    $userInfo = getUserInfo($conn, $data['user_id']);
}




/* Processamento */
if ($data['action'] == "new") {

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

    
    /* Montar o Token */
    $tokenData = array(
        "exp" => time() + (60 * 60 * 24 * $data['lifespan']),
        "app" => $data['app_name']
    );

    /* Gerar o token (jwt) para a autorização do usuário para abertura de chamados por email */
    $jwt = (new AccessToken())->generate($data['user_id'], $tokenData);


    /* Inserção do novo token */
    $sql = "INSERT INTO access_tokens (
        user_id, app, token
    ) VALUES (
        :user_id, :app, :jwt
    )";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
        $res->bindParam(':app', $data['app_name'], PDO::PARAM_STR);
        $res->bindParam(':jwt', $jwt, PDO::PARAM_STR);
        $res->execute();

        $data['message'] = TRANS('MSG_SUCCESS_INSERT');

        
    } catch (Exception $e) {
        $exception .= "<hr>" . $e->getMessage();
    }
} elseif ($data['action'] == "edit") {

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


    /* Remove o token atual */
    $sql = "DELETE FROM access_tokens WHERE id = '". $data['cod'] ."' ";
    try {
        $conn->exec($sql);

        /* Montar o Token */
        $tokenData = array(
            "exp" => time() + (60 * 60 * 24 * $data['lifespan']),
            "app" => $data['app_name']
        );

        /* Gerar o token (jwt) para a autorização do usuário para abertura de chamados por email */
        $jwt = (new AccessToken())->generate($data['user_id'], $tokenData);


        /* Inserção do novo token */
        $sql = "INSERT INTO access_tokens (
            user_id, app, token
        ) VALUES (
            :user_id, :app, :jwt
        )";
        try {
            $res = $conn->prepare($sql);
            $res->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
            $res->bindParam(':app', $data['app_name'], PDO::PARAM_STR);
            $res->bindParam(':jwt', $jwt, PDO::PARAM_STR);
            $res->execute();

            /* Se for o APP para abertura de chamados por e-mail: 
                ATualiza o token na configuracao para abertura de chamados por e-mail */
            if ($data['app_name'] == 'ticket_by_email') {
                $sql_config = "UPDATE config_keys SET key_value = :token WHERE key_name = :key_name ";
                try {
                    $res_config = $conn->prepare($sql_config);
                    $res_config->bindParam(':token', $jwt, PDO::PARAM_STR);
                    $res_config->bindParam(':key_name', $app_ticket_by_email_token, PDO::PARAM_STR);

                    $res_config->execute();
                } catch (Exception $e) {
                    $exception .= "<hr>" . $e->getMessage();
                }
            }
            

            $data['message'] = TRANS('MSG_SUCCESS_EDIT');
            
        } catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage();
        }

    }
    catch (Exception $e) {
        $exception .= "<hr>" . $e->getMessage();
    }
} elseif ($data['action'] == "delete") {

    $sql = "SELECT app FROM access_tokens WHERE id = '". $data['cod'] ."' ";
    $res = $conn->query($sql);
    $appName = $res->fetch()['app'];

    if ($appName == getConfigValue($conn, 'API_TICKET_BY_MAIL_APP')) {
        $data['success'] = false;
        $data['message'] = TRANS('MSG_CANT_DEL');
        $_SESSION['flash'] = message('danger', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    }

    $sql = "DELETE FROM access_tokens WHERE id = '". $data['cod'] ."'";
    try {
        $conn->exec($sql);
        $data['success'] = true;
        $data['message'] = TRANS('OK_DEL');
    }
    catch (Exception $e) {
        $exception .= "<hr>" . $e->getMessage();
    }

    $_SESSION['flash'] = message('success', '', $data['message'] . $exception, '');

    echo json_encode($data);
    return false;

}


$_SESSION['flash'] = message('success', '', $data['message'] . $exception, '');
echo json_encode($data);
return false;

echo json_encode($data);
