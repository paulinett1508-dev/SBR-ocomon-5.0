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

use includes\classes\ConnectPDO;

$conn = ConnectPDO::getInstance();

$post = $_POST;


$erro = false;
$exception = "";
$screenNotification = "";
$data = [];
$data['success'] = true;
$data['message'] = "";
$data['cod'] = (isset($post['cod']) ? intval($post['cod']) : "");
$data['action'] = $post['action'];
$data['field_id'] = "";

$data['user_id'] = (isset($post['user_id']) ? noHtml($post['user_id']) : "");
$data['code'] = (isset($post['code']) ? noHtml($post['code']) : "");
$data['password'] = (isset($post['new_pass_1']) && !empty($post['new_pass_1']) ? $post['new_pass_1'] : "");
$data['password2'] = (isset($post['new_pass_2']) && !empty($post['new_pass_2']) ? $post['new_pass_2'] : "");
$data['hash'] = (!empty($data['password']) ? pass_hash($data['password']) : "");


if (empty($data['user_id'])) {
    $data['success'] = false; 
    $data['message'] = message('warning', 'Ooops!', TRANS('MSG_SOMETHING_GOT_WRONG'),'');
    echo json_encode($data);
    return false;
}

$user_data = getUsers($conn, $data['user_id']);
if (!count($user_data)) {
    $data['success'] = false; 
    $data['message'] = message('warning', 'Ooops!', TRANS('USERNAME_OR_EMAIL_NOT_FOUND'),'');
    echo json_encode($data);
    return false;
}


if (empty($user_data['forget']) || $user_data['forget'] != $data['code']) {
    $data['success'] = false; 
    $data['message'] = message('warning', 'Ooops!', TRANS('INVALID_LINK'),'');
    echo json_encode($data);
    return false;
}


/* Validações */
if (empty($data['password']) || empty($data['password2'])) {

    $data['success'] = false; 

    if (empty($data['password'])) {
        $data['field_id'] = 'new_pass_1';
    } elseif (empty($data['password2'])) {
        $data['field_id'] = 'new_pass_2';
    }
    
    $data['message'] = message('warning', 'Ooops!', TRANS('MSG_EMPTY_DATA'),'');
    echo json_encode($data);
    return false;
}

if ($data['password'] !== $data['password2']) {
    $data['success'] = false; 
    $data['field_id'] = "new_pass_1";
    $screenNotification .= TRANS('PASSWORDS_DOESNT_MATCH');
    $data['message'] = message('warning', 'Ooops!', $screenNotification,'');
    echo json_encode($data);
    return false;
}

$sql = "UPDATE usuarios SET 
            password = NULL, 
            hash = :hash,
            forget = null
        WHERE user_id = :user_id
        ";
try {
    $res = $conn->prepare($sql);
    $res->bindParam(':user_id', $data['user_id']);
    $res->bindParam(':hash', $data['hash']);
    $res->execute();
}
catch (Exception $e) {
    $exception .= "<hr>" . $e->getMessage();
    $data['success'] = false;
    $data['message'] = message('warning', 'Ooops!', TRANS('MSG_SOMETHING_GOT_WRONG'),'');
    echo json_encode($data);
    return false;
}

$data['success'] = true; 
$data['message'] = TRANS('PASSWORD_CHANGED');
$_SESSION['flash'] = message('success', '', $data['message'], '');
echo json_encode($data);
return false;

