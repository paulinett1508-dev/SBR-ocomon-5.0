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

$configExt = getConfigValues($conn);

$post = $_POST;

// $localAuth = AUTH_TYPE == "SYSTEM";
$localAuth = (isset($configExt['AUTH_TYPE']) && $configExt['AUTH_TYPE'] == 'LDAP' ? false : true); 

$screenNotification = "";
$exception = "";
$data = [];
$data['success'] = true;
$data['message'] = "";
$data['cod'] = (isset($post['cod']) ? intval($post['cod']) : "");
$data['action'] = $post['action'];
$data['field_id'] = "";

$data['current_pass'] = (isset($post['current_pass']) ? noHtml($post['current_pass']) : "");
$data['new_pass_1'] = (isset($post['new_pass_1']) ? noHtml($post['new_pass_1']) : "");
$data['new_pass_2'] = (isset($post['new_pass_2']) ? noHtml($post['new_pass_2']) : "");

$data['hash'] = (!empty($data['new_pass_1']) ? pass_hash($data['new_pass_1']) : "");


/* Validações */
if (!$localAuth) {
    $data['success'] = false; 
    $data['message'] = message('warning', 'Ooops!', TRANS('PASSWORD_CHANGEABLE_IF_LOCAL_AUTH'),'');
    echo json_encode($data);
    return false;
}


if ($data['action'] == "edit") {

    if (empty($data['current_pass']) || empty($data['new_pass_1']) || empty($data['new_pass_2'])) {
        $data['success'] = false; 
        $data['field_id'] = (empty($data['current_pass']) ? 'current_pass' : (empty($data['new_pass_1']) ? 'new_pass_1' : 'new_pass_2'));
        $data['message'] = message('warning', 'Ooops!', TRANS('MSG_EMPTY_DATA'),'');
        echo json_encode($data);
        return false;
    }

    if ($data['new_pass_1'] !== $data['new_pass_2']) {
        $data['success'] = false; 
        $data['field_id'] = "new_pass_1";
        $data['message'] = message('warning', 'Ooops!', TRANS('PASSWORDS_DOESNT_MATCH'),'');
        echo json_encode($data);
        return false;
    }

    
    $sql = "SELECT `password`, `hash` FROM usuarios WHERE user_id = :user_id ";

    try {
        $res = $conn->prepare($sql);

        $res->bindParam(':user_id', $data['cod'], PDO::PARAM_INT);
        $res->execute();

        if ($res->rowCount()) {

            $row = $res->fetch();

            /* Se tiver hash */
            if (!empty($row['hash'])) {
                if (!password_verify($data['current_pass'], $row['hash'])) {
                    $data['success'] = false; 
                    $data['field_id'] = "current_pass";
                    $data['message'] = message('warning', 'Ooops!', TRANS('ERR_LOGON'),'');
                    echo json_encode($data);
                    return false;
                }
            } elseif ($row['password'] !== $data['current_pass'] || empty($row['password'])) {
                $data['success'] = false; 
                $data['field_id'] = "current_pass";
                $data['message'] = message('warning', 'Ooops!', TRANS('ERR_LOGON'),'');
                echo json_encode($data);
                return false;
            }
        }
    }
    catch (Exception $e) {
        $exception .= "<hr>" . $e->getMessage();
    }
    
    
    // if (!csrf_verify($post)) {
    //     $data['success'] = false; 
    //     $data['message'] = message('warning', 'Ooops!', TRANS('FORM_ALREADY_SENT'),'');
    
    //     echo json_encode($data);
    //     return false;
    // }

    $sql = "UPDATE usuarios SET password = NULL, hash = '" . $data['hash'] . "' WHERE user_id = '" . $data['cod'] . "' ";

    try {
        $conn->exec($sql);
        $data['success'] = true; 


        /* Se a senha foi alterada - então checo se é necessário atualizar token */
        /* Checar se o usuário possui algum token de acesso para API */
        $sql = "SELECT app FROM access_tokens WHERE user_id = :user_id ";
        $res = $conn->prepare($sql);
        $res->bindParam(':user_id', $data['cod'], PDO::PARAM_INT);
        $res->execute();
        if ($res->rowCount()) {
            /* Gerar e gravar novo token de acesso à API */
            foreach ($res->fetchall() as $row) {
                /* Montar o Token */
                $tokenData = array(
                    "exp" => time() + (60 * 60 * 24 * 365),
                    "app" => $row['app']
                );

                /* Gerar o token (jwt) */
                $jwt = "";
                $jwt = (new AccessToken())->generate($data['cod'], $tokenData);

                $sql = "UPDATE access_tokens SET token = :token WHERE user_id = :user_id AND app = :app ";
                try {
                    $result = $conn->prepare($sql);
                    $result->bindParam(':token', $jwt, PDO::PARAM_STR);
                    $result->bindParam(':user_id', $data['cod'], PDO::PARAM_INT);
                    $result->bindParam(':app', $row['app'], PDO::PARAM_STR);
                    $result->execute();

                    if ($row['app'] == 'ticket_by_email') {
                        /* preciso atualizar nas configuracoes do app */
                        /* ATualiza o token na configuracao para abertura de chamados por e-mail */
                        $key_name_token = "API_TICKET_BY_MAIL_TOKEN";
                        $sql = "UPDATE config_keys SET key_value = :token WHERE key_name = :key_name ";
                        try {
                            $resConfig = $conn->prepare($sql);
                            $resConfig->bindParam(':token', $jwt, PDO::PARAM_STR);
                            $resConfig->bindParam(':key_name', $key_name_token, PDO::PARAM_STR);

                            $resConfig->execute();
                        } catch (Exception $e) {
                            $exception .= "<hr>" . $e->getMessage();
                        }
                    }
                }
                catch (Exception $e) {
                    $exception .= "<hr>" . $e->getMessage();
                }
            }
        }
        




        $data['message'] = TRANS('MSG_SUCCESS_EDIT');

        $_SESSION['flash'] = message('success', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    } catch (Exception $e) {
        $exception .= "<hr>" . $e->getMessage() . "<hr>" . $sql;
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_DATA_UPDATE');
        $_SESSION['flash'] = message('danger', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    }
}

echo json_encode($data);