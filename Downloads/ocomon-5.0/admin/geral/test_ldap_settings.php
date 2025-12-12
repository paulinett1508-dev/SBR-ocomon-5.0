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


// $data['auth_type_ldap'] = (isset($post['auth_type_ldap']) ? ($post['auth_type_ldap'] == "yes" ? 1 : 0) : 0);
$data['ldap_host'] = (isset($post['ldap_host']) ? noHtml($post['ldap_host']) : "");
$data['ldap_port'] = (isset($post['ldap_port']) ? (int)noHtml($post['ldap_port']) : 389);
$data['ldap_domain'] = (isset($post['ldap_domain']) ? noHtml($post['ldap_domain']) : "");
$data['ldap_basedn'] = (isset($post['ldap_basedn']) ? noHtml($post['ldap_basedn']) : "");
$data['ldap_field_fullname'] = (isset($post['ldap_field_fullname']) ? noHtml($post['ldap_field_fullname']) : "");
$data['ldap_field_email'] = (isset($post['ldap_field_email']) ? noHtml($post['ldap_field_email']) : "");
$data['ldap_field_phone'] = (isset($post['ldap_field_phone']) ? noHtml($post['ldap_field_phone']) : "");
$data['ldap_user'] = (isset($post['ldap_user']) ? noHtml($post['ldap_user']) : "");
$data['ldap_password'] = (isset($post['ldap_password']) ? noHtml($post['ldap_password']) : "");


/* Checagem de preenchimento dos campos obrigatórios para a testagem*/
if ($data['action'] == "edit") {

    if ($data['ldap_user'] == "") {
        $data['success'] = false; 
        $data['field_id'] = "ldap_user";
    } elseif ($data['ldap_password'] == "") {
        $data['success'] = false; 
        $data['field_id'] = "ldap_password";
    } elseif ($data['ldap_host'] == "") {
        $data['success'] = false; 
        $data['field_id'] = "ldap_host";
    } elseif ($data['ldap_port'] == "") {
        $data['success'] = false; 
        $data['field_id'] = "ldap_port";
    } elseif ($data['ldap_domain'] == "") {
        $data['success'] = false; 
        $data['field_id'] = "ldap_domain";
    } elseif ($data['ldap_basedn'] == "") {
        $data['success'] = false; 
        $data['field_id'] = "ldap_basedn";
    } elseif ($data['ldap_field_fullname'] == "") {
        $data['success'] = false; 
        $data['field_id'] = "ldap_field_fullname";
    } elseif ($data['ldap_field_email'] == "") {
        $data['success'] = false; 
        $data['field_id'] = "ldap_field_email";
    } elseif ($data['ldap_field_phone'] == "") {
        $data['success'] = false; 
        $data['field_id'] = "ldap_field_phone";
    }

    
    if ($data['success'] == false) {
        $data['message'] = message('warning', '', TRANS('MSG_EMPTY_DATA'), '');
        echo json_encode($data);
        return false;
    }

    if (!filter_var($data['ldap_host'], FILTER_VALIDATE_DOMAIN)) {
        /* FILTER_VALIDATE_DOMAIN */
        $data['success'] = false; 
        $data['field_id'] = "ldap_host";
        $data['message'] = message('warning', '', TRANS('WRONG_FORMATTED_URL'), '');
        echo json_encode($data);
        return false;
    }
    
    if (!filter_var($data['ldap_port'], FILTER_VALIDATE_INT)) {
        /* FILTER_VALIDATE_DOMAIN */
        $data['success'] = false; 
        $data['field_id'] = "ldap_port";
        $data['message'] = message('warning', '', TRANS('MSG_ERROR_WRONG_FORMATTED'), '');
        echo json_encode($data);
        return false;
    }
    
}

$ldapConfig = [
    'LDAP_HOST' => $data['ldap_host'],
    'LDAP_PORT' => $data['ldap_port'],
    'LDAP_DOMAIN' => $data['ldap_domain'],
    'LDAP_BASEDN' => $data['ldap_basedn'],
    'LDAP_FIELD_FULLNAME' => $data['ldap_field_fullname'],
    'LDAP_FIELD_EMAIL' => $data['ldap_field_email'],
    'LDAP_FIELD_PHONE' => $data['ldap_field_phone'],
];

if (passLdap($data['ldap_user'], $data['ldap_password'], $ldapConfig)) {

    $showUserData = "<hr>";
    $userData = getUserLdapData($data['ldap_user'], $data['ldap_password'], $ldapConfig);
    
    if (empty($userData)) {
        $showUserData .= TRANS('NO_LDAP_DATA_RETRIEVED');
    }
    
    foreach ($userData as $key => $uData) {
        if ($key != 'password' && $key != 'username')
            $showUserData.= TRANS($key) . ": " .$uData . "<br/>";
    }
    
    $data['message'] = message('success', '', TRANS('LDAP_SUCCESS_AUTHENTICATION') . $showUserData, '');
    echo json_encode($data);
    return true;

} else {
    $data['success'] = false; 
    $data['field_id'] = "ldap_user";
    $data['message'] = message('danger', '', TRANS('ERR_LOGON_LDAP'), '');
    echo json_encode($data);
    return false;
}




