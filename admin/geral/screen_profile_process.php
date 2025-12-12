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

// var_dump($post); exit();

$erro = false;
$exception = "";
$mensagem = "";
$data = [];
// $data['success'] = true;
// $data['message'] = "";
// $data['cod'] = (isset($post['cod']) ? intval($post['cod']) : "");
// $data['action'] = $post['action'];
// $data['field_id'] = "";

// $data['profile_name'] = (isset($post['profile_name']) ? noHtml($post['profile_name']) : "");
// $data['allow_user_open'] =  (isset($post['allow_user_open']) ? ($post['allow_user_open'] == "yes" ? 1 : 0) : "");
// $data['area_to'] = (isset($post['area_to']) ? $post['area_to'] : "");

$data['field_client'] = (isset($post['field_client']) ? ($post['field_client'] == "yes" ? 1 : 0) : 0);
$data['field_area'] = (isset($post['field_area']) ? ($post['field_area'] == "yes" ? 1 : 0) : 0);
$data['field_issue'] = (isset($post['field_issue']) ? ($post['field_issue'] == "yes" ? 1 : 0) : 0);
$data['field_description'] = (isset($post['field_description']) ? ($post['field_description'] == "yes" ? 1 : 0) : 0);
$data['field_unit'] = (isset($post['field_unit']) ? ($post['field_unit'] == "yes" ? 1 : 0) : 0);
$data['field_tag_number'] = (isset($post['field_tag_number']) ? ($post['field_tag_number'] == "yes" ? 1 : 0) : 0);
$data['field_tag_check'] = (isset($post['field_tag_check']) ? ($post['field_tag_check'] == "yes" ? 1 : 0) : 0);
$data['field_tag_tickets'] = (isset($post['field_tag_tickets']) ? ($post['field_tag_tickets'] == "yes" ? 1 : 0) : 0);
$data['field_contact'] = (isset($post['field_contact']) ? ($post['field_contact'] == "yes" ? 1 : 0) : 0);
$data['field_contact_email'] = (isset($post['field_contact_email']) ? ($post['field_contact_email'] == "yes" ? 1 : 0) : 0);
$data['field_phone'] = (isset($post['field_phone']) ? ($post['field_phone'] == "yes" ? 1 : 0) : 0);
$data['field_department'] = (isset($post['field_department']) ? ($post['field_department'] == "yes" ? 1 : 0) : 0);
$data['field_load_department'] = (isset($post['field_load_department']) ? ($post['field_load_department'] == "yes" ? 1 : 0) : 0);
$data['field_search_dep_tags'] = (isset($post['field_search_dep_tags']) ? ($post['field_search_dep_tags'] == "yes" ? 1 : 0) : 0);
$data['field_operator'] = (isset($post['field_operator']) ? ($post['field_operator'] == "yes" ? 1 : 0) : 0);
$data['field_date'] = (isset($post['field_date']) ? ($post['field_date'] == "yes" ? 1 : 0) : 0);
$data['field_schedule'] = (isset($post['field_schedule']) ? ($post['field_schedule'] == "yes" ? 1 : 0) : 0);
$data['field_forward'] = (isset($post['field_forward']) ? ($post['field_forward'] == "yes" ? 1 : 0) : 0);
$data['field_status'] = (isset($post['field_status']) ? ($post['field_status'] == "yes" ? 1 : 0) : 0);
$data['field_replicate'] = (isset($post['field_replicate']) ? ($post['field_replicate'] == "yes" ? 1 : 0) : 0);
$data['field_attach_file'] = (isset($post['field_attach_file']) ? ($post['field_attach_file'] == "yes" ? 1 : 0) : 0);
$data['field_priority'] = (isset($post['field_priority']) ? ($post['field_priority'] == "yes" ? 1 : 0) : 0);
$data['field_send_mail'] = (isset($post['field_send_mail']) ? ($post['field_send_mail'] == "yes" ? 1 : 0) : 0);
$data['field_channel'] = (isset($post['field_channel']) ? ($post['field_channel'] == "yes" ? 1 : 0) : 0);

/* Checar se pelo menos um campo da listagem padrão foi selecionado */
$minDefaultFields = false;
foreach ($data as $key => $val) {
    if ($val == 1) {
        $minDefaultFields = true;
    }
}

$data['success'] = true;
$data['message'] = "";
$data['cod'] = (isset($post['cod']) ? intval($post['cod']) : "");
$data['action'] = $post['action'];
$data['field_id'] = "";

$data['profile_name'] = (isset($post['profile_name']) ? noHtml($post['profile_name']) : "");
$data['allow_user_open'] =  (isset($post['allow_user_open']) ? ($post['allow_user_open'] == "yes" ? 1 : 0) : "");
$data['is_default'] = (isset($post['is_default']) ? ($post['is_default'] == "yes" ? 1 : "") : "");
$data['area_to'] = (isset($post['area_to']) ? $post['area_to'] : "");
$data['opening_message'] = (isset($post['opening_message']) ? noHtml($post['opening_message']) : "");
$data['field_custom_ids'] = "";
$data['field_only_edition_ids'] = "";
$data['field_user_hidden_ids'] = "";




$data['field_client_required'] = (isset($post['field_client_required']) ? ($post['field_client_required'] == "on" ? 1 : 0) : 0);
$data['field_area_required'] = (isset($post['field_area_required']) ? ($post['field_area_required'] == "on" ? 1 : 0) : 0);
$data['field_issue_required'] = (isset($post['field_issue_required']) ? ($post['field_issue_required'] == "on" ? 1 : 0) : 0);
$data['field_description_required'] = (isset($post['field_description_required']) ? ($post['field_description_required'] == "on" ? 1 : 0) : 0);
$data['field_unit_required'] = (isset($post['field_unit_required']) ? ($post['field_unit_required'] == "on" ? 1 : 0) : 0);
$data['field_tag_number_required'] = (isset($post['field_tag_number_required']) ? ($post['field_tag_number_required'] == "on" ? 1 : 0) : 0);
$data['field_contact_required'] = (isset($post['field_contact_required']) ? ($post['field_contact_required'] == "on" ? 1 : 0) : 0);
$data['field_contact_email_required'] = (isset($post['field_contact_email_required']) ? ($post['field_contact_email_required'] == "on" ? 1 : 0) : 0);
$data['field_phone_required'] = (isset($post['field_phone_required']) ? ($post['field_phone_required'] == "on" ? 1 : 0) : 0);
$data['field_department_required'] = (isset($post['field_department_required']) ? ($post['field_department_required'] == "on" ? 1 : 0) : 0);
$data['field_forward_required'] = (isset($post['field_forward_required']) ? ($post['field_forward_required'] == "on" ? 1 : 0) : 0);
$data['field_attach_file_required'] = (isset($post['field_attach_file_required']) ? ($post['field_attach_file_required'] == "on" ? 1 : 0) : 0);


/* Para inserções sobre a obrigatoriedade dos campos */
$fields_required = [
	'conf_scr_client' => $data['field_client_required'],
	'conf_scr_area' => $data['field_area_required'],
	'conf_scr_prob' => $data['field_issue_required'],
	'conf_scr_desc' => $data['field_description_required'],
	'conf_scr_unit' => $data['field_unit_required'],
	'conf_scr_tag' => $data['field_tag_number_required'],
	'conf_scr_contact' => $data['field_contact_required'],
	'conf_scr_contact_email' => $data['field_contact_email_required'],
	'conf_scr_fone' => $data['field_phone_required'],
	'conf_scr_local' => $data['field_department_required'],
	'conf_scr_foward' => $data['field_forward_required'],
	'conf_scr_upload' => $data['field_attach_file_required']
];


/* Campos personalizados - apenas ativos*/
$dataCustom = [];
$sql = "SELECT * FROM custom_fields WHERE field_table_to = 'ocorrencias' AND field_active = 1";
try {
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        foreach ($res->fetchAll() as $cfield) {
            $dataCustom[] = $cfield;
        }
    }
}
catch (Exception $e) {
    $exception .= "<hr>" . $e->getMessage();
}

/* Composição dos IDs dos campos personalizados */
$fields_ids = [];
$fields_only_edition_ids = [];
$fields_user_hidden_ids = [];
if (count($dataCustom)) {
    foreach ($dataCustom as $cfield) {
        if (isset($post[$cfield['field_name']]) && $post[$cfield['field_name']] == "yes") {
            $fields_ids[] = $cfield['id'];

            if (isset($post['only_edition_' . $cfield['field_name']]) && $post['only_edition_' . $cfield['field_name']] == "on") {
                $fields_only_edition_ids[] = $cfield['id'];

                if (isset($post['hidden_' . $cfield['field_name']]) && $post['hidden_' . $cfield['field_name']] == "on") {
                    $fields_user_hidden_ids[] = $cfield['id'];
                }
            }
        }
    }
}
if (count($fields_ids)) {
    $data['field_custom_ids'] = implode(',', $fields_ids);
    $data['field_only_edition_ids'] = implode(',', $fields_only_edition_ids);
    $data['field_user_hidden_ids'] = implode(',', $fields_user_hidden_ids);
}


/* Checar se pelo menos um campo personalizado foi selecionado */
$minCustomFields = false;
if (!empty($data['field_custom_ids'])) {
    $minCustomFields = true;
}

$screenNotification = "";


/* Validações */
if ($data['action'] == "new" || $data['action'] == "edit") {

    if (empty($data['profile_name']) || empty($data['opening_message'])) {
        $data['success'] = false; 
        $data['field_id'] = (empty($data['profile_name']) ? 'profile_name' : 'opening_message');
        $data['message'] = message('warning', 'Ooops!', TRANS('MSG_EMPTY_DATA'),'');
        echo json_encode($data);
        return false;
    }

    if (!$minDefaultFields && !$minCustomFields) {
        $data['success'] = false; 
        $data['field_id'] = '';
        $data['message'] = message('warning', 'Ooops!', TRANS('AT_LEAST_FIELD'),'');
        echo json_encode($data);
        return false;
    }
}


if ($data['action'] == 'edit') {

    $sql = "SELECT conf_cod FROM configusercall WHERE conf_name = '" . $data['profile_name'] . "' AND 
            conf_cod <> '" . $data['cod'] . "' ";
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $data['success'] = false; 
        $data['field_id'] = "profile_name";
        $data['message'] = message('warning', '', TRANS('MSG_RECORD_EXISTS'), '');
        echo json_encode($data);
        return false;
    }

    if (!csrf_verify($post)) {
        $data['success'] = false; 
        $data['message'] = message('warning', 'Ooops!', TRANS('FORM_ALREADY_SENT'),'');
    
        echo json_encode($data);
        return false;
    }


    $sql = "SELECT conf_cod FROM configusercall WHERE conf_is_default = 1 AND conf_cod <> '" . $data['cod'] . "' ";
    $res = $conn->query($sql);
    if (!$res->rowCount()) {
        $data['is_default'] = 1;
    }

    if ($data['is_default'] == 1) {
        $sql = "UPDATE configusercall SET conf_is_default = NULL ";
        $conn->exec($sql);
    }
    
    

    $sql = "UPDATE configusercall SET 
				conf_name= '" . $data['profile_name'] . "', 
				conf_user_opencall= " . $data['allow_user_open'] . ", 
                conf_is_default = " . dbField($data['is_default']) . ",  
				conf_opentoarea = " . $data['area_to'] . ", 
				conf_scr_client = " . $data['field_client'] . ", 
				conf_scr_area = " . $data['field_area'] . ", conf_scr_prob = " . $data['field_issue'] . ", 
				conf_scr_desc = " . $data['field_description'] . ", conf_scr_unit = " . $data['field_unit'] . ", 
				conf_scr_tag = " . $data['field_tag_number'] . ", conf_scr_chktag = " . $data['field_tag_check'] . ", 
                conf_scr_chkhist = " . $data['field_tag_tickets'] . ", conf_scr_contact = " . $data['field_contact'] . ", 
                conf_scr_contact_email = " . $data['field_contact_email'] . ", 
				conf_scr_fone = " . $data['field_phone'] . ", conf_scr_local = " . $data['field_department'] . ", 
				conf_scr_btloadlocal = " . $data['field_load_department'] . ", conf_scr_searchbylocal = " . $data['field_search_dep_tags'] . " ,
				conf_scr_operator = " . $data['field_operator'] . ", conf_scr_date = " . $data['field_date'] . ", 
				conf_scr_schedule = " . $data['field_schedule'] . ", 
				conf_scr_foward = " . $data['field_forward'] . ", 
				conf_scr_status = " . $data['field_status'] . ", conf_scr_replicate = " . $data['field_replicate'] . " ,
				conf_scr_upload = " . $data['field_attach_file'] . " ,
				conf_scr_mail = " . $data['field_send_mail'] . ", conf_scr_msg = '" . $data['opening_message'] . "' ,
				conf_scr_prior = " . $data['field_priority'] . ",  
				conf_scr_channel = " . $data['field_channel'] . ",  
				conf_scr_custom_ids = " . dbField($data['field_custom_ids'],'text') . ", 
				cfields_only_edition = " . dbField($data['field_only_edition_ids'],'text') . ", 
				cfields_user_hidden = " . dbField($data['field_user_hidden_ids'],'text') . " 
				
				WHERE conf_cod=" . $data['cod'] . " ";
    try {
        $conn->exec($sql);
        $data['success'] = true; 
        $data['message'] = TRANS('MSG_SUCCESS_EDIT');




        $data['profile_cod'] = $data['cod'];
        /* Obrigatoriedade dos campos */
        $sql = "DELETE FROM screen_field_required WHERE profile_id = '" . $data['profile_cod'] . "' ";
        try {
            $conn->exec($sql);

            /* Foreach para inserir a obrigatoriedade dos campos da tela de abertura */
            foreach ($fields_required as $field_name => $required) {
                $sql = "INSERT INTO screen_field_required 
                        (
                            profile_id, 
                            field_name,
                            field_required
                        )
                        VALUES
                        (
                            '" . $data['profile_cod'] . "', 
                            '" . $field_name . "', 
                            '" . $required . "'
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
        catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage();
        }
        
        

        $_SESSION['flash'] = message('success', '', $data['message'], '');
        echo json_encode($data);
        return false;
    } catch (Exception $e) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_DATA_UPDATE');
        $_SESSION['flash'] = message('danger', '', $data['message'], '');
        echo json_encode($data);
        return false;
    }

} elseif ($data['action'] == 'new') {

    $sql = "SELECT conf_cod FROM configusercall WHERE conf_name = '" . $data['profile_name'] . "' ";
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $data['success'] = false; 
        $data['field_id'] = "profile_name";
        $data['message'] = message('warning', '', TRANS('MSG_RECORD_EXISTS'), '');
        echo json_encode($data);
        return false;
    }

    if (!csrf_verify($post)) {
        $data['success'] = false; 
        $data['message'] = message('warning', 'Ooops!', TRANS('FORM_ALREADY_SENT'),'');
    
        echo json_encode($data);
        return false;
    }


    $sql = "SELECT conf_cod FROM configusercall WHERE conf_is_default = 1 ";
    $res = $conn->query($sql);
    if (!$res->rowCount()) {
        $data['is_default'] = 1;
    }
    
    if ($data['is_default'] == 1) {
        $sql = "UPDATE configusercall SET conf_is_default = NULL ";
        $conn->exec($sql);
    }

    
    $sql = "INSERT INTO configusercall 
            (
                conf_name, conf_user_opencall, conf_is_default, conf_opentoarea, conf_scr_client, conf_scr_area, conf_scr_prob, conf_scr_desc, 
                conf_scr_unit, conf_scr_tag, conf_scr_chktag, conf_scr_chkhist, conf_scr_contact, conf_scr_contact_email, 
                conf_scr_fone, 
                conf_scr_local, conf_scr_btloadlocal, conf_scr_searchbylocal, conf_scr_operator, conf_scr_date, 
                conf_scr_schedule, conf_scr_foward, conf_scr_status, conf_scr_replicate, conf_scr_upload, 
                conf_scr_mail, conf_scr_msg, conf_scr_prior, conf_scr_channel, conf_scr_custom_ids, cfields_only_edition, 
                cfields_user_hidden
            ) 
            VALUES 
            (
                '" . $data['profile_name'] . "', " . $data['allow_user_open'] . ", " . dbField($data['is_default']) . ", " . $data['area_to'] . ", " . $data['field_client'] . ", " . $data['field_area'] . ", 
                " . $data['field_issue'] . ", " . $data['field_description'] . ", " . $data['field_unit'] . ", " . $data['field_tag_number'] . ", 
                " . $data['field_tag_check'] . ", " . $data['field_tag_tickets'] . ", " . $data['field_contact'] . ", 
                " . $data['field_contact_email'] . ", " . $data['field_phone'] . ", 
                " . $data['field_department'] . ", " . $data['field_load_department'] . ", " . $data['field_search_dep_tags'] . " , " . $data['field_operator'] . ", 
                " . $data['field_date'] . ", " . $data['field_schedule'] . ", " . $data['field_forward'] . ", " . $data['field_status'] . ", 
                " . $data['field_replicate'] . " ," . $data['field_attach_file'] . " , " . $data['field_send_mail'] . ", '" . $data['opening_message'] . "', 
                " . $data['field_priority'] . ", " . $data['field_channel'] . ", " . dbField($data['field_custom_ids'],'text') . ",
                " . dbField($data['field_only_edition_ids'],'text') . ", 
                " . dbField($data['field_user_hidden_ids'],'text') . " 
            )";


    try {
        $conn->exec($sql);
        $data['success'] = true; 
        $data['message'] = TRANS('MSG_SUCCESS_INSERT');
        
        $data['profile_cod'] = $conn->lastInsertId();
        
        /* Foreach para inserir a obrigatoriedade dos campos da tela de abertura */
        foreach ($fields_required as $field_name => $required) {
            $sql = "INSERT INTO screen_field_required 
                    (
                        profile_id, 
                        field_name,
                        field_required
                    )
                    VALUES
                    (
                        '" . $data['profile_cod'] . "', 
                        '" . $field_name . "', 
                        '" . $required . "'
                    )
                    ";
            try {
                $conn->exec($sql);
            }
            catch (Exception $e) {
                $exception .= "<hr>" . $e->getMessage();
            }
        }

        
        
        $_SESSION['flash'] = message('success', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    } catch (Exception $e) {
        $exception .= "<hr>" . $e->getMessage();
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_SAVE_RECORD');
        $_SESSION['flash'] = message('danger', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    }

} elseif ($data['action'] == 'delete') {


    $sql = "SELECT conf_cod FROM configusercall WHERE conf_cod = " . $data['cod'] . " AND conf_is_default = 1 ";
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $data['success'] = false; 
        $data['message'] = TRANS('CANT_REMOVE_DEFAULT_PROFILE');
        $_SESSION['flash'] = message('danger', '', $data['message'], '');
        echo json_encode($data);
        return false;
    }
    
    
    $sql = "SELECT * FROM sistemas where sis_screen='" . $data['cod'] . "'";
    $res = $conn->query($sql);
    $achou = $res->rowCount();

    if ($achou) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_CANT_DEL');
        $_SESSION['flash'] = message('danger', '', $data['message'], '');
        echo json_encode($data);
        return false;
    }


    $sql = "SELECT * FROM problemas where prob_profile_form = '" . $data['cod'] . "'";
    $res = $conn->query($sql);
    $achou = $res->rowCount();

    if ($achou) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_CANT_DEL');
        $_SESSION['flash'] = message('danger', '', $data['message'], '');
        echo json_encode($data);
        return false;
    }
    
    $sql =  "DELETE FROM configusercall WHERE conf_cod='".$data['cod']."'";

    try {
        $conn->exec($sql);
        $data['success'] = true; 
        $data['message'] = TRANS('OK_DEL');


        $sql = "DELETE FROM screen_field_required WHERE profile_id = '" . $data['cod'] . "' ";
        try {
            $conn->exec($sql);
        }
        catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage();
        }


        $_SESSION['flash'] = message('success', '', $data['message'] . $exception, '');
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

echo json_encode($data);