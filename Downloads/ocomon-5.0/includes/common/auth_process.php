<?php /*                        Copyright 2023 Flávio Ribeiro

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
session_start();

include "../../includes/include_geral_new.inc.php";

require_once __DIR__ . "/" . "../../includes/classes/ConnectPDO.php";
use includes\classes\ConnectPDO;
$conn = ConnectPDO::getInstance();

// $post = $_POST;
$post = filter_input_array(INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

$screenNotification = "";
$exception = "";
$now = date("Y-m-d H:i:s");
$data = [];
$data['success'] = true;
$data['message'] = "";
$data['field_id'] = "";

$data['user'] = (isset($post['user']) ? noHtml($post['user']) : "");
$data['pass'] = (isset($post['pass']) ? $post['pass'] : "");
$data['remember_user'] = (isset($post['remember_user']) && $post['remember_user'] == "1" ? true : false);

$_SESSION['session_expired'] = 0;
$data['max_tries'] = 5; /* Número de tentativas mal sucedidas até o bloqueio */
$data['time_to_wait'] = 60; /* Tempo de espera, em segundos, após o número máximo de tentativas ser atingido*/

/* Validações */
if (empty($data['user']) || empty($data['pass'])) {
    $data['success'] = false; 
    $data['field_id'] = (empty($data['user']) ? 'user' : 'pass');
    $data['message'] = message('warning', 'Ooops!', TRANS('MSG_EMPTY_DATA'),'');
    echo json_encode($data);
    return false;
}

if (!valida(TRANS('FIELD_USER'), $data['user'], 'MAIL', 1, $ERRO) && !valida(TRANS('FIELD_USER'), $data['user'], 'USUARIO', 1, $ERRO)) {
    $data['success'] = false; 
    $data['field_id'] = "user";
    $data['message'] = message('warning', '', $ERRO, '');
    echo json_encode($data);
    return false;
}

$authType = getConfigValue($conn, 'AUTH_TYPE');
$authType = (!empty($authType) ? $authType : 'SYSTEM');


if ($authType == "SYSTEM") {

    /* Controle de quantidade de tentativas de acesso sem sucesso */
    if (isset($_SESSION['attempt']) && $_SESSION['attempt']['try'] > $data['max_tries']) {

        if (time() < $_SESSION['attempt']['time'] + $data['time_to_wait']) {
            $data['success'] = false;
            $data['message'] = message('warning', 'Ooops!', TRANS('EXCEEDED_OF_LOGON_ATTEMPTS'), '');
            echo json_encode($data);
            return false;
        } else {
            $_SESSION['attempt']['try'] = 1;
        }
    }

    /* Validação de user e pass */
    if (!(pass($conn, $data['user'], $data['pass']))) {

        /* Contabilização das tentativas de acesso */
        if (!isset($_SESSION['attempt'])) {
            $_SESSION['attempt']['try'] = 1;
            $_SESSION['attempt']['time'] = time();
        } else {
            $_SESSION['attempt']['try'] = $_SESSION['attempt']['try'] + 1;
            $_SESSION['attempt']['time'] = time();
        }

        $data['success'] = false;
        $data['field_id'] = (empty($data['user']) ? 'user' : 'pass');
        $data['message'] = message('danger', 'Ooops!', TRANS('ERR_LOGON') . ".<hr>" . TRANS('TRY') . " " . $_SESSION['attempt']['try'], '');
        echo json_encode($data);
        return false;
    }

} elseif ($authType == "LDAP") {

    /* Controle de quantidade de tentativas de acesso sem sucesso */
    if (isset($_SESSION['attempt']) && $_SESSION['attempt']['try'] > $data['max_tries']) {
        if (time() < $_SESSION['attempt']['time'] + $data['time_to_wait']) {
            $data['success'] = false;
            $data['message'] = message('warning', 'Ooops!', TRANS('EXCEEDED_OF_LOGON_ATTEMPTS'), '');
            echo json_encode($data);
            return false;
        } else {
            $_SESSION['attempt']['try'] = 1;
        }
    }
    
    
    /* Validação de user e pass - LDAP */
    $configValues = getConfigValues($conn);
    $ldapConfig = [
        'LDAP_HOST' => $configValues['LDAP_HOST'] ?? '',
        'LDAP_PORT' => $configValues['LDAP_PORT'] ?? '',
        'LDAP_DOMAIN' => $configValues['LDAP_DOMAIN'] ?? '',
        'LDAP_BASEDN' => $configValues['LDAP_BASEDN'] ?? '',
        'LDAP_FIELD_FULLNAME' => $configValues['LDAP_FIELD_FULLNAME'] ?? '',
        'LDAP_FIELD_EMAIL' => $configValues['LDAP_FIELD_EMAIL'] ?? '',
        'LDAP_FIELD_PHONE' => $configValues['LDAP_FIELD_PHONE'] ?? '',
    ];
    if (!passLdap($data['user'], $data['pass'], $ldapConfig)) {

        /* Contabilização das tentativas de acesso */
        if (!isset($_SESSION['attempt'])) {
            $_SESSION['attempt']['try'] = 1;
            $_SESSION['attempt']['time'] = time();
        } else {
            $_SESSION['attempt']['try'] = $_SESSION['attempt']['try'] + 1;
            $_SESSION['attempt']['time'] = time();
        }

        $data['success'] = false;
        $data['field_id'] = (empty($data['user']) ? 'user' : 'pass');
        $data['message'] = message('danger', 'Ooops!', TRANS('ERR_LOGON_LDAP'), '');
        echo json_encode($data);
        return false;
    }

    /* Checa se o usuário existe na base local */
    if (!isLocalUser($conn, $data['user'])) {
        
        /* Caso o usuário não exista localmente, deverá ser criado */
        /* getUserLdapData() */
        $userData = getUserLdapData($data['user'], $data['pass'], $ldapConfig);
        
        $data['hash'] = pass_hash(md5($data['pass']));
        $data['fullname'] = $userData['LDAP_FIELD_FULLNAME'] ?? '';
        $data['email'] = $userData['LDAP_FIELD_EMAIL'] ?? '';
        $data['phone'] = $userData['LDAP_FIELD_PHONE'] ?? '';

        $data['client_to_bind'] = (array_key_exists('LDAP_CLIENT_TO_BIND_NEWUSERS', $configValues) ? $configValues['LDAP_CLIENT_TO_BIND_NEWUSERS'] : "");

        $sql = "INSERT INTO usuarios 
                (
                login, user_client, nome, hash, data_inc, data_admis, email, 
                fone, nivel, AREA, user_admin
                ) 
                VALUES 
                (
                    '" . $data['user'] . "', 
                    " . dbField($data['client_to_bind']) . ",
                    '" . $data['fullname'] . "', '" . $data['hash'] . "', 
                    '" . $now . "', 
                    NULL, '" . $data['email'] . "', '" . $data['phone'] . "', '3', 
                    '" . $configValues['LDAP_AREA_TO_BIND_NEWUSERS'] . "', 0 
                )";
        try {
            $conn->exec($sql);
            // $data['success'] = true;
            // $data['message'] = message('success', '', TRANS('LDAP_SUCCESS_NEW_USER'), '');
            // echo json_encode($data);
            // return false;
        }
        catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage();
            $data['success'] = false;
            $data['field_id'] = (empty($data['user']) ? 'user' : 'pass');
            $data['message'] = message('danger', 'Ooops!', TRANS('LDAP_FAIL_NEW_USER'), '');
            echo json_encode($data);
            return false;
        }
    }


    
} else {
    $data['success'] = false;
    $data['field_id'] = (empty($data['user']) ? 'user' : 'pass');
    $data['message'] = message('danger', 'Ooops!', TRANS('AUTH_TYPE_NOT_IDENTIFIED'), '');
    echo json_encode($data);
    return false;
}

$userInfo = getUserInfo($conn, 0, $data['user']); 
$userClient = (!empty($userInfo['user_client']) ? getClients($conn, $userInfo['user_client']) : '');

/* Permissão de acesso */
if ($userInfo['nivel'] > 3 || (!empty($userClient) && $userClient['is_active'] == 0)) {
    $data['success'] = false; 
    $data['field_id'] = "user";
    $data['message'] = message('warning', '', TRANS('ERR_LOGON'), '');
    echo json_encode($data);
    return false;
}



$_SESSION['attempt']['try'] = 0;

/* Cookie para o nome de usuário : 30 dias */
if ($data['remember_user']) {
    setcookie("oc_login", $data['user'], time() + 60 * 60 * 24 * 30, "/");
} else {
    setcookie("oc_login", "", time() - 60 * 60 * 24 * 30, "/");
}


$firstLogon = ($userInfo['last_logon'] == "" ? true : false);
updateLastLogon($conn, $userInfo['user_id']);

$area = $userInfo['area_id'];
$secondaryAreas = "";
$secondaryAreas .= getUserAreas($conn, $userInfo['user_id']); /* apenas secundárias */
$allAreas = (!empty($secondaryAreas) ? $area . "," . $secondaryAreas : $area);

$mod_tickets = getModuleAccess($conn, 1, $allAreas);
$mod_inventory = getModuleAccess($conn, 2, $allAreas);

$modulos = "";
if ($mod_tickets)
    $modulos = '1';
if ($mod_inventory) {
    if (strlen((string)$modulos))
        $modulos .= ",";
    $modulos .= '2';
}
$_SESSION['s_permissoes'] = $modulos;

$config = getConfig($conn);

$_SESSION['s_logado'] = 1;
$_SESSION['csrf_token'] = "";
$_SESSION['s_usuario'] = $data['user'];
$_SESSION['s_usuario_nome'] = $userInfo['nome'];
$_SESSION['s_uid'] = $userInfo['user_id'];
$_SESSION['s_nivel_real'] = $userInfo['nivel'];
$_SESSION['s_nivel'] = $userInfo['nivel'];
$_SESSION['s_nivel_desc'] = $userInfo['nivel'];
$_SESSION['s_area'] = $userInfo['area_id'];
$_SESSION['s_uareas'] = $allAreas;
$_SESSION['s_opening_mode'] = $userInfo['opening_mode']; /* 1: clássico | 2: dinâmico */

$_SESSION['s_area_admin'] = $userInfo['user_admin'];
$_SESSION['s_can_route'] = $userInfo['can_route'];
$_SESSION['s_can_get_routed'] = $userInfo['can_get_routed'];
$_SESSION['s_ocomon'] = $mod_tickets;
$_SESSION['s_invmon'] = $mod_inventory;

/* Variáveis básicas para os relatórios */
$_SESSION['s_rep_filters']['client'] = '';
$_SESSION['s_rep_filters']['area'] = $userInfo['area_id'];
$_SESSION['s_rep_filters']['d_ini'] = date("01/m/Y");
$_SESSION['s_rep_filters']['d_fim'] = date("d/m/Y");
$_SESSION['s_rep_filters']['state'] = 1;
$_SESSION['s_rep_filters']['cat1'] = -1;
$_SESSION['s_rep_filters']['cat2'] = -1;
$_SESSION['s_rep_filters']['cat3'] = -1;

    

/** 
* Limitação de visualização de itens do inventário a unidades específicas 
* Vazio indica que não há limitação de visualização
*/
$allowedUnits = getAreaAllowedUnits($conn, $userInfo['area_id']);
if ($userInfo['nivel'] != 1 && !empty($allowedUnits)) {
    $_SESSION['s_allowed_units'] = implode(',', array_column($allowedUnits, 'unit_id'));
} else {
    $_SESSION['s_allowed_units'] = "";
}

$allowedClients = implode(',', array_column(getAreaAllowedClients($conn, $_SESSION['s_area']), 'inst_client'));
if ($userInfo['nivel'] != 1 && !empty($allowedClients)) {
    $_SESSION['s_allowed_clients'] = $allowedClients;
} else {
    $_SESSION['s_allowed_clients'] = "";
}


$defaultScreenProfile = getDefaultScreenProfile($conn);
$_SESSION['s_screen'] = $userInfo['sis_screen'] ?? $defaultScreenProfile ; 

// $_SESSION['s_screen'] = $userInfo['sis_screen'];
$_SESSION['s_wt_areas'] = $config['conf_wt_areas']; //1: origem , 2: destino

$_SESSION['s_formatBarOco'] = 0;
$_SESSION['s_formatBarMural'] = 0;

if (strpos($config['conf_formatBar'], '%oco%')) {
    $_SESSION['s_formatBarOco'] = 1;
}
if (strpos($config['conf_formatBar'], '%mural%')) {
    $_SESSION['s_formatBarMural'] = 1;
}

$_SESSION['s_language'] = (!empty($userInfo['language']) ? $userInfo['language'] : $config['conf_language']);
$_SESSION['s_date_format'] = $config['conf_date_format'];
$_SESSION['s_paging_full'] = 0;
$_SESSION['s_page_size'] = $config['conf_page_size'];
$_SESSION['s_allow_reopen'] = $config['conf_allow_reopen'];
$_SESSION['s_ocomon_site'] = $config['conf_ocomon_site'];

$_SESSION['s_colorDestaca'] = "#CCCCCC";
$_SESSION['s_colorMarca'] = "#FFFFCC";
$_SESSION['s_colorLinPar'] = "#E3E1E1";
$_SESSION['s_colorLinImpar'] = "#F6F6F6";



/* Generate hash if it doesnt exist - versões anteriores à versão 4.x*/
if (empty($userInfo['hash'])) {
    $sql = "UPDATE usuarios SET 
        password = null, hash = '" . pass_hash($data['pass']) . "' WHERE user_id = :user_id
    ";
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':user_id', $userInfo['user_id'], PDO::PARAM_INT);
        $res->execute();
    }
    catch (Exception $e) {
        $exception .= "<hr>" . $e->getMessage();
    }
}


$data['success'] = true; 
$message = ($firstLogon ? TRANS('MSG_WELCOME') : TRANS('MSG_WELCOME_BACK'));
$_SESSION['flash'] = message('success', TRANS('MSG_HELLO') . " " . firstLetterUp(firstWord($userInfo['nome'])) . "!", $message . $exception, '');
echo json_encode($data);
