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

$isAdmin = $_SESSION['s_nivel'] == 1;
$config = getConfig($conn);

$post = $_POST; 

// var_dump($post);
$exception = "";

$erro = false;
$screenNotification = "";
$data = [];
$data['success'] = true;
$data['message'] = "";
$data['cod'] = (isset($post['cod']) ? intval($post['cod']) : "");
$data['action'] = $post['action'];
$data['field_id'] = "";
$data['profile'] = false; 


$data['login_name'] = (isset($post['login_name']) ? noHtml($post['login_name']) : "");
$data['user_client'] = (isset($post['user_client']) ? noHtml($post['user_client']) : "");
$data['password'] = (isset($post['password']) && !empty($post['password']) ? $post['password'] : "");
$data['password2'] = (isset($post['password2']) && !empty($post['password2']) ? $post['password2'] : "");


$data['hash'] = (!empty($data['password']) ? pass_hash($data['password']) : "");


$data['fullname'] = (isset($post['fullname']) ? noHtml($post['fullname']) : "");
$data['level'] = (isset($post['level']) ? noHtml($post['level']) : "");
$data['subscribe_date'] = (isset($post['subscribe_date']) ? dateDB(noHtml($post['subscribe_date']),1) : "");
$data['hire_date'] = (isset($post['hire_date']) ? dateDB(noHtml($post['hire_date']),1) : "");
$data['email'] = (isset($post['email']) ? noHtml($post['email']) : "");
$data['phone'] = (isset($post['phone']) ? noHtml($post['phone']) : "");
$data['primary_area'] = (isset($post['primary_area']) ? noHtml($post['primary_area']) : "");

$data['area_admin'] = (isset($post['area_admin']) ? ($post['area_admin'] == "yes" ? 1 : 0) : 0);


/** 
 * Áreas que o usuário será gerente além da sua área primária
*/
$areas_to_admin = [];
/** 
 * manageble_area: se $data['level'] == 3 
 * valores possíveis:
 *  key => 'yes'
 */
/** 
 * setAdmin: se $data['level'] < 3 
 * valores possíveis:
 *  key => 'on'
 */
if ($data['area_admin'] && !empty($data['level'])) {
    if ($data['level'] == 3) {
        if (isset($post['manageble_area']) && !empty($post['manageble_area'])) {
            foreach ($post['manageble_area'] as $key => $value) {
                if ($value == "yes") {
                    $areas_to_admin[] = $key;
                }
            }
        }
    } elseif ($data['level'] < 3) {
        if (isset($post['setAdmin']) && !empty($post['setAdmin'])) {
            foreach ($post['setAdmin'] as $key => $value) {
                if ($value == "on") {
                    $areas_to_admin[] = $key;
                }
            }
        }
    }
}


$data['can_route'] = (isset($post['can_route']) ? ($post['can_route'] == "yes" ? 1 : 0) : 0);
$data['can_get_routed'] = (isset($post['can_get_routed']) ? ($post['can_get_routed'] == "yes" ? 1 : 0) : 0);

/* Apenas usuários operadores ou administradores podem encaminhar ou receber chamados encaminhados  - */
if ($data['level'] != 2 && $data['level'] != 1) {
    $data['can_route'] = 0;
    $data['can_get_routed'] = 0;
}

/* Todos os administradores podem encaminhar chamados */
if ($data['level'] == 1) {
    $data['can_route'] = 1;
}

$data['bgcolor'] = (isset($post['bgcolor']) ? noHtml($post['bgcolor']) : "#3A4D56");
$data['textcolor'] = (isset($post['textcolor']) ? noHtml($post['textcolor']) : "#FFFFFF");


$data['lang'] = (isset($post['lang']) ? noHtml(getLastPartOfPath($post['lang'])) : "");

/* Áreas secundárias */
$secondary_areas = [];
if (isset($post['secondary_area']) && !empty($post['secondary_area'])) {
    foreach ($post['secondary_area'] as $key => $value) {
        if ($value == "yes") {
            $secondary_areas[] = $key;
        }
    }
}


/* Validações actions: new | edit */
if ($data['action'] == "new" || $data['action'] == "edit") {


    if (empty($data['login_name']) || empty($data['fullname']) || 
        empty($data['level']) || empty($data['user_client']) || empty($data['email']) || 
        empty($data['phone']) || empty($data['primary_area']) ||
        (empty($data['password']) && $data['action'] == "new")) {
        
        $data['success'] = false; 

        
        if (empty($data['login_name'])) {
            $data['field_id'] = 'login_name';
        }
        elseif (empty($data['fullname'])) {
            $data['field_id'] = 'fullname';
        }
        elseif (empty($data['password']) && $data['action'] == "new") {
            $data['field_id'] = 'password';
        }
        elseif (empty($data['password2']) && !empty($data['password'])) {
            $data['field_id'] = 'password2';
        }
        elseif (empty($data['level'])) {
            $data['field_id'] = 'level';
        }
        elseif (empty($data['user_client'])) {
            $data['field_id'] = 'user_client';
        }
        elseif (empty($data['email'])) {
            $data['field_id'] = 'email';
        }
        elseif (empty($data['phone'])) {
            $data['field_id'] = 'phone';
        }
        elseif (empty($data['primary_area'])) {
            $data['field_id'] = 'primary_area';
        }
        
        $data['message'] = message('warning', 'Ooops!', TRANS('MSG_EMPTY_DATA'),'');
        echo json_encode($data);
        return false;
    }

    if ($data['password'] !== $data['password2']) {
        $data['success'] = false; 
        $data['field_id'] = "password";
        $screenNotification .= TRANS('PASSWORDS_DOESNT_MATCH');
        $data['message'] = message('warning', 'Ooops!', $screenNotification,'');
        echo json_encode($data);
        return false;
    }

    if (!valida('Usuário', $data['login_name'], 'MAIL', 1, $screenNotification) && !valida('Usuário', $data['login_name'], 'USUARIO', 1, $screenNotification)) {
        $data['success'] = false; 
        $data['field_id'] = "login_name";
        $data['message'] = message('warning', 'Ooops!', $screenNotification,'');
        echo json_encode($data);
        return false;
    }
    if (!valida('E-mail', $data['email'], 'MAIL', 1, $screenNotification)) {
        $data['success'] = false; 
        $data['field_id'] = "email";
        $data['message'] = message('warning', 'Ooops!', $screenNotification,'');
        echo json_encode($data);
        return false;
    }
}

/* Validações action: profile */
if ($data['action'] == "profile") {

    // $data['success'] = false; 
    if (empty($data['fullname'])) {
        $data['success'] = false;
        $data['field_id'] = 'fullname';
    }
    elseif ($isAdmin && empty($data['level'])) {
        $data['success'] = false;
        $data['field_id'] = 'level';
    }
    elseif (empty($data['email'])) {
        $data['success'] = false;
        $data['field_id'] = 'email';
    }
    elseif (empty($data['phone'])) {
        $data['success'] = false;
        $data['field_id'] = 'phone';
    }
    elseif ($isAdmin && empty($data['primary_area'])) {
        $data['success'] = false;
        $data['field_id'] = 'primary_area';
    }

    if (!$data['success']) {
        $data['message'] = message('warning', 'Ooops!', TRANS('MSG_EMPTY_DATA'), '');
        echo json_encode($data);
        return false;
    }
        
        
    
    if (!valida('E-mail', $data['email'], 'MAIL', 1, $screenNotification)) {
        $data['success'] = false; 
        $data['field_id'] = "email";
        $data['message'] = message('warning', 'Ooops!', $screenNotification,'');
        echo json_encode($data);
        return false;
    }
}


if ($data['action'] == 'edit') {

    if (!csrf_verify($post)) {
        $data['success'] = false; 
        $data['message'] = message('warning', 'Ooops!', TRANS('FORM_ALREADY_SENT'),'');
    
        echo json_encode($data);
        return false;
    }

    $terms = "";
    if (!empty($data['password'])) {

        if ($data['password'] !== $data['password2']) {
            $data['success'] = false; 
            $data['field_id'] = "password";
            $screenNotification .= TRANS('PASSWORDS_DOESNT_MATCH');
            $data['message'] = message('warning', 'Ooops!', $screenNotification,'');
            echo json_encode($data);
            return false;
        }
        // $terms = " password = '" . $data['password'] . "', ";
        $terms = " password = null, ";
        $terms .= " hash = '" . $data['hash'] . "', ";

    }


    $sql = "UPDATE usuarios SET 
				nome= '" . $data['fullname'] . "', 
                user_client = " . dbField($data['user_client']) . ", 
                {$terms}
				data_admis = " . dbField($data['hire_date'],'date') . ", 
				email = '" . $data['email'] . "', 
				fone = '" . $data['phone'] . "', 
				nivel = '" . $data['level'] . "', 
				AREA = '" . $data['primary_area'] . "', 
				user_admin = " . $data['area_admin'] . ",
                can_route = {$data['can_route']},
                can_get_routed = {$data['can_get_routed']},
                user_bgcolor = '" . $data['bgcolor'] . "',
                user_textcolor = '" . $data['textcolor'] . "'
				
				WHERE user_id = " . $data['cod'] . " ";
    try {
        $conn->exec($sql);

        /* Se a senha foi alterada - então checo se é necessário atualizar token */
        if (!empty($data['password'])){
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
        }


        /**
         * Só poderá alterar áreas secundárias e gerência de áreas se for admin
         */
        if ($isAdmin) {
            /* Áreas secundárias */
            $sqlDel = "DELETE FROM usuarios_areas WHERE uarea_uid = " . $data['cod'] . " ";
            try {
                $conn->exec($sqlDel);
                foreach ($secondary_areas as $area) {
                    $sql = "INSERT INTO usuarios_areas (uarea_cod, uarea_uid, uarea_sid) VALUES (null, " . $data['cod'] . ", {$area})";
                    $conn->exec($sql);
                }
            }
            catch (Exception $e) {
                echo 'Erro: ', $e->getMessage(), "<br/>";
                $erro = true;
            }


            /* Áreas para gerenciar */
            $sqlDel = "DELETE FROM users_x_area_admin WHERE user_id = " . $data['cod'] . " ";
            try {
                $conn->exec($sqlDel);
                foreach ($areas_to_admin as $area) {
                    $sql = "INSERT INTO users_x_area_admin (id, user_id, area_id) VALUES (null, " . $data['cod'] . ", {$area})";
                    $conn->exec($sql);
                }
            }
            catch (Exception $e) {
                $exception .= "<hr>" . $e->getMessage();
            }
        }



        $data['success'] = true; 
        $data['message'] = TRANS('MSG_SUCCESS_EDIT');
        $_SESSION['flash'] = message('success', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    } catch (Exception $e) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_DATA_UPDATE') . "<br />". $sql;
        $_SESSION['flash'] = message('danger', '', $data['message'], '');
        echo json_encode($data);
        return false;
    }

} elseif ($data['action'] == 'profile') {

    if (!csrf_verify($post)) {
        $data['success'] = false; 
        $data['message'] = message('warning', 'Ooops!', TRANS('FORM_ALREADY_SENT'),'');
    
        echo json_encode($data);
        return false;
    }

    $terms = "";
    if ($isAdmin) {
        $terms .= " data_admis = " . dbField($data['hire_date'],'date') . ", ";
        $terms .= " nivel = " . $data['level'] . ", ";
        $terms .= " AREA = " . $data['primary_area'] . ", ";
        $terms .= " user_admin = " . $data['area_admin'] . ", ";
        $terms .= " can_get_routed = " . $data['can_get_routed'] . ", ";
    }

    $sql = "UPDATE usuarios SET 
                {$terms} 
				nome= '" . $data['fullname'] . "', 
				email = '" . $data['email'] . "', 
				fone = '" . $data['phone'] . "' 
			WHERE user_id = " . $_SESSION['s_uid'] . " ";
    try {
        $conn->exec($sql);

        if ($isAdmin) {
            $sqlDel = "DELETE FROM usuarios_areas WHERE uarea_uid = " . $_SESSION['s_uid'] . " ";
            try {
                $conn->exec($sqlDel);
                foreach ($secondary_areas as $area) {
                    $sql = "INSERT INTO usuarios_areas (uarea_cod, uarea_uid, uarea_sid) VALUES (null, " . $_SESSION['s_uid'] . ", {$area})";
                    $conn->exec($sql);
                }
            }
            catch (Exception $e) {
                echo 'Erro: ', $e->getMessage(), "<br/>";
                $erro = true;
            }

            /* Áreas para gerenciar */
            $sqlDel = "DELETE FROM users_x_area_admin WHERE user_id = " . $_SESSION['s_uid'] . " ";
            try {
                $conn->exec($sqlDel);
                foreach ($areas_to_admin as $area) {
                    $sql = "INSERT INTO users_x_area_admin (id, user_id, area_id) VALUES (null, " . $_SESSION['s_uid'] . ", {$area})";
                    $conn->exec($sql);
                }
            }
            catch (Exception $e) {
                $exception .= "<hr>" . $e->getMessage();
            }
        }
        
        $data['success'] = true; 
        $data['profile'] = true; 

        $_SESSION["s_usuario_nome"] = $data['fullname'];
        $_SESSION["s_area_admin"] = $data['area_admin'];
        

        $data['message'] = TRANS('MSG_SUCCESS_EDIT');
        $_SESSION['flash'] = message('success', '', $data['message'], '');
        // echo json_encode($data);
        // return false;
    } catch (Exception $e) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_DATA_UPDATE') . "<br />". $sql;
        $_SESSION['flash'] = message('danger', '', $data['message'], '');
        echo json_encode($data);
        return false;
    }

} elseif ($data['action'] == 'new') {

    $sql = "SELECT login FROM usuarios WHERE login = '" . $data['login_name'] . "'";
    $res = $conn->query($sql);
    $found = $res->rowCount();

    if ($found) {
        $data['success'] = false; 
        $data['message'] = message('warning', 'Ooops!', TRANS('USERNAME_ALREADY_EXISTS'),'');
    
        echo json_encode($data);
        return false;
    }

    if (!csrf_verify($post)) {
        $data['success'] = false; 
        $data['message'] = message('warning', 'Ooops!', TRANS('FORM_ALREADY_SENT'),'');
    
        echo json_encode($data);
        return false;
    }
    

    $sql = "INSERT INTO usuarios 
            (
                login, nome, user_client, hash, data_inc, data_admis, email, 
                fone, nivel, AREA, user_admin, can_route, can_get_routed, user_bgcolor, user_textcolor
            ) 
            VALUES 
            (
                '" . $data['login_name'] . "', '" . $data['fullname'] . "', " . dbField($data['user_client']) . ", '" . $data['hash'] . "', 
                '" . $data['subscribe_date'] . "', 
                " . dbField($data['hire_date'],'date') . ", '" . $data['email'] . "', '" . $data['phone'] . "', '" . $data['level'] . "', 
                '" . $data['primary_area'] . "', " . $data['area_admin'] . ", {$data['can_route']}, {$data['can_get_routed']},
                '{$data['bgcolor']}', '{$data['textcolor']}' 
            )";


    try {
        $conn->exec($sql);
        $uid = $conn->lastInsertId();

        foreach ($secondary_areas as $area) {
            $sql = "INSERT INTO usuarios_areas (uarea_cod, uarea_uid, uarea_sid) VALUES (null, {$uid}, {$area})";
            $conn->exec($sql);
        }


        /* Áreas para gerenciar */
        foreach ($areas_to_admin as $area) {
            $sql = "INSERT INTO users_x_area_admin (id, user_id, area_id) VALUES (null, {$uid}, {$area})";
            try {
                $conn->exec($sql);
            }
            catch (Exception $e) {
                $exception .= "<hr>" . $e->getMessage();
            }
        }
        
    

        $data['success'] = true; 
        $data['message'] = TRANS('MSG_SUCCESS_INSERT');
        $_SESSION['flash'] = message('success', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    } catch (Exception $e) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_SAVE_RECORD') . "<br/>" . $sql;
        $_SESSION['flash'] = message('danger', '', $data['message'], '');
        echo json_encode($data);
        return false;
    }

} elseif ($data['action'] == 'delete') {

    /* Não permitir a exclusão do próprio usuario autenticado */
    if ($_SESSION['s_uid'] == $data['cod']) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_CANT_DEL_SYSTEM_REGISTER');
        $_SESSION['flash'] = message('danger', '', $data['message'], '');
        echo json_encode($data);
        return false;
    }

    $sql = "SELECT * FROM ocorrencias WHERE aberto_por = '" . $data['cod'] . "' OR operador='" . $data['cod'] . "'";
    $res = $conn->query($sql);
    $achou = $res->rowCount();

    if ($achou) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_CANT_DEL');
        $_SESSION['flash'] = message('danger', '', $data['message'], '');
        echo json_encode($data);
        return false;
    }

    $sql = "SELECT id FROM access_tokens WHERE user_id = '" . $data['cod'] . "'";
    $res = $conn->query($sql);
    $achou = $res->rowCount();

    if ($achou) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_CANT_DEL');
        $_SESSION['flash'] = message('danger', '', $data['message'], '');
        echo json_encode($data);
        return false;
    }


    
    $sql =  "DELETE FROM usuarios WHERE user_id = '".$data['cod']."'";

    try {
        $conn->exec($sql);
        $data['success'] = true; 
        $data['message'] = TRANS('OK_DEL');


        $sql = "DELETE FROM usuarios_areas WHERE uarea_uid = " . $data['cod'] . " ";
        try {
            $conn->exec($sql);
        }
        catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage() . "<hr>" . $sql;
        }

        $sql = "DELETE FROM users_x_area_admin WHERE user_id = " . $data['cod'] . " ";
        try {
            $conn->exec($sql);
        }
        catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage() . "<hr>" . $sql;
        }

        /* Excluir da user_notices também */
        $sql = "DELETE FROM user_notices WHERE user_id = '" . $data['cod'] . "'";
        try {
            $conn->exec($sql);
        }
        catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage() . "<hr>" . $sql;
        }


        $_SESSION['flash'] = message('success', '', $data['message'], '');
        echo json_encode($data);
        return false;
    } catch (Exception $e) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_DATA_REMOVE');
        $_SESSION['flash'] = message('danger', '', $data['message'], '');
        echo json_encode($data);
        return false;
    }
}


if (!empty($data['lang'])) {


    $sqlUserLang = "SELECT upref_lang FROM uprefs WHERE upref_uid = '" . $_SESSION['s_uid'] . "'";
    $hasUL = "";
    
    try {
        $execUserLang = $conn->query($sqlUserLang);
        $hasUL = $execUserLang->rowcount();
        $rowUL = $execUserLang->fetch();

        if (!empty($hasUL)) {
            $qry = "UPDATE uprefs SET upref_lang =  " . dbField($data['lang'], 'text') . " WHERE upref_uid = " . $_SESSION['s_uid'] . "";
        } else {
            $qry = "INSERT INTO uprefs (upref_uid, upref_lang) values (" . $_SESSION['s_uid'] . ", " . dbField($data['lang'], 'text') . ")";
        }

        try {
            $conn->exec($qry);
            $_SESSION['s_language'] = $data['lang'];
        }
        catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage();
        }
    }
    catch (Exception $e) {
        $exception .= "<hr>" . $e->getMessage();
    }
    
    
} else {
    $qry = "DELETE FROM uprefs WHERE upref_uid = '" . $_SESSION['s_uid'] . "' ";
    try {
        $conn->exec($qry);
    }
    catch (Exception $e) {
        $exception .= "<hr>" . $e->getMessage();
    }

    // $_SESSION['s_language'] = $config['conf_language'];
    $_SESSION['s_language'] = noHtml(getLastPartOfPath($config['conf_language']));
}


echo json_encode($data);