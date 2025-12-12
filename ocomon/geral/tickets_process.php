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

use OcomonApi\Support\Email;
use includes\classes\ConnectPDO;
use OcomonApi\WebControllers\FormFields;

$conn = ConnectPDO::getInstance();
$formfield = new FormFields();
$fieldsNew = $formfield::getInstance("ocorrencias", "new");
$fieldsEdit = $formfield::getInstance("ocorrencias", "edit");
$fieldsClose = $formfield::getInstance("ocorrencias", "close");

$post = $_POST;
// var_dump($post);
$data = [];
$hasFile = false;
// $totalFiles = ($_FILES ? count($_FILES['anexo']['name']) : 0);
$totalFiles = ($_FILES && array_key_exists('anexo', $_FILES) ? count($_FILES['anexo']['name']) : 0);


if ($totalFiles) {
    /** Checagem se há anexos a fim de validar caso o campo seja de preenchimento obrigatório */
    /* Removendo o indice 'files' que pode existir em alguns casos enviado pelo Summernote */
    unset($_FILES['files']);

    foreach ($_FILES as $anexo) {
        $file = array();
        for ($i = 0; $i < $totalFiles; $i++) {
            
            if (!empty($anexo['name'][$i])) {
                $hasFile = true;
            }
        }
    }
}

// var_dump($post); exit;
// var_dump($_FILES); exit;

$now = date("Y-m-d H:i:s");



$config = getConfig($conn);
$rowconfmail = getMailConfig($conn);
$rowLogado = getUserInfo($conn, $_SESSION['s_uid']);

$defaultChannel = getDefaultChannel($conn);
$defaultChannel = (!empty($defaultChannel) ? $defaultChannel['id'] : 1);



$data['profile_id'] = (isset($post['profile_id']) && !empty($post['profile_id']) ? $post['profile_id'] : $_SESSION['s_screen']);

$qry_profile_screen = $QRY["useropencall_custom"];
// $qry_profile_screen .= " AND  c.conf_cod = '" . $_SESSION['s_screen'] . "'";
$qry_profile_screen .= " AND  c.conf_cod = '" . $data['profile_id'] . "'";
$res_screen = $conn->query($qry_profile_screen);
$screen = $res_screen->fetch(PDO::FETCH_ASSOC);


$sqlProfileScreenGlobal = $QRY["useropencall"];
$resScreenGlobal = $conn->query($sqlProfileScreenGlobal);
$screenGlobal = $resScreenGlobal->fetch(PDO::FETCH_ASSOC);


$recordFile = [];
$erro = false;
$exception = "";
$screenNotification = "";
$mailSent = false;
$mailNotification = "";
$data['success'] = true;
$data['message'] = "";
$data['cod'] = (isset($post['cod']) ? intval($post['cod']) : "");
$data['numero'] = (isset($post['numero']) ? intval($post['numero']) : "");
$data['action'] = $post['action'];
$data['field_id'] = "";


$doneStatus = 4;
$data['entry_type'] = 15;
$data['operation_type'] = 4;

$isRequester = (isset($post['is_requester']) && $post['is_requester']);
$timeToClose = $config['conf_time_to_close_after_done'];

/* Se não for o próprio solicitante que estiver encerrando e a configuração de tempo para avaliar for maior que 0 */
$needToBeRated = !$isRequester && $timeToClose > 0;
if ($needToBeRated) {
    $doneStatus = $config['conf_status_done'];
    $data['entry_type'] = 16;
    $data['operation_type'] = 9;
}

$data['format_bar'] = hasFormatBar($config, '%oco%');

$data['client'] = (isset($post['client']) ? noHtml($post['client']) : "");
if ($_SESSION['s_nivel'] == 3) {
    /* Se for usuário-final, será abertura, pega o cliente configurado para o usuário */
    $endUserInfo = getUserInfo($conn, $_SESSION['s_uid']);
    $data['client'] = (!empty($endUserInfo['user_client']) ? $endUserInfo['user_client'] : "");
}


$data['sistema'] = (isset($post['sistema']) && !empty($post['sistema']) ? noHtml($post['sistema']) : "-1");
$data['area_destino'] = $screen['conf_opentoarea'];
$data['problema'] = (isset($post['problema']) && !empty($post['problema']) ? noHtml($post['problema']) : "-1");
$data['radio_prob'] = (isset($post['radio_prob']) ? noHtml($post['radio_prob']) : $data['problema']);

$data['descricao'] = (isset($post['descricao']) ? $post['descricao'] : "");
$data['descricao'] = ($data['format_bar'] ? $data['descricao'] : noHtml($data['descricao']));

$data['unidade'] = (isset($post['instituicao']) && !empty($post['instituicao']) ? noHtml($post['instituicao']) : "-1");
$data['etiqueta'] = (isset($post['equipamento']) ? noHtml($post['equipamento']) : "");
$data['department'] = (isset($post['local']) && !empty($post['local']) ? noHtml($post['local']) : "-1");

$data['aberto_por'] = (isset($_SESSION['s_uid']) ? intval($_SESSION['s_uid']) : "");

$data['logado'] = (isset($_SESSION['s_uid']) ? intval($_SESSION['s_uid']) : "");

$data['input_tags'] = (isset($post['input_tags']) && !empty($post['input_tags']) ? noHtml($post['input_tags']) : "");

$data['forward'] = (isset($post['foward']) && !empty($post['foward'] && $post['foward'] != "-1") ? noHtml($post['foward']) : $_SESSION['s_uid']);
$data['operator'] = $data['forward'];

$data['contato'] = (isset($post['contato']) ? noHtml($post['contato']) : "");
$data['contato_email'] = (isset($post['contato_email']) ? noHtml($post['contato_email']) : "");
$data['telefone'] = (isset($post['telefone']) ? noHtml($post['telefone']) : "");
$data['channel'] = (isset($post['channel']) ? noHtml($post['channel']) : "");
$data['prioridade'] = (isset($post['prioridade']) && !empty($post['prioridade']) ? intval($post['prioridade']) : "-1");
$data['father'] = ((isset($post['pai']) ? intval($post['pai']) : ""));


/* Data para agendamento */
$data['is_scheduled'] = 0;
$data['schedule_to'] = (isset($post['date_schedule']) ? noHtml($post['date_schedule']) : "");
$data['date_schedule_typed'] = $data['schedule_to'];
if ($data['schedule_to'] != "") {
    $data['schedule_to'] = dateDB($data['schedule_to']);
    $data['is_scheduled'] = 1;
}


$data['mail_area'] = (isset($post['mailAR']) ? $post['mailAR'] : "");

$data['mail_operador'] = (isset($post['mailOP']) ? $post['mailOP'] : "");
$data['mail_usuario'] = (isset($post['mailUS']) ? $post['mailUS'] : "");

$data['sla_out'] = (isset($post['sla_out']) ? $post['sla_out'] : 0); /* action = close */
$data['justificativa'] = (isset($post['justificativa']) && !empty($post['justificativa']) ? noHtml($post['justificativa']) : "");
// $data['justificativa'] = ($data['format_bar'] ? $data['justificativa'] : noHtml($data['justificativa']));
$data['script_solution'] = (isset($post['script_sol']) ? noHtml($post['script_sol']) : "");

$data['technical_description'] = (isset($post['descProblema']) && !empty($post['descProblema']) ? noHtml($post['descProblema']) : "");
// $data['technical_description'] = ($data['format_bar'] ? $data['technical_description'] : noHtml($data['technical_description']));


$data['technical_solution'] = (isset($post['descSolucao']) && !empty($post['descSolucao']) ? noHtml($post['descSolucao']) : "");
// $data['technical_solution'] = ($data['format_bar'] ? $data['technical_solution'] : noHtml($data['technical_solution']));

$data['global_uri'] = "";

$data['entry_privated'] = (isset($post['check_asset_privated']) ? noHtml($post['check_asset_privated']) : 0);
$data['data_atendimento'] = (isset($post['data_atend']) ? noHtml($post['data_atend']) : "");
// $data['old_status'] = (isset($post['oldStatus']) ? noHtml($post['oldStatus']) : noHtml($post['status']));

$data['entry'] = (isset($post['assentamento']) && !empty($post['assentamento']) ? noHtml($post['assentamento']) : "");
// $data['entry'] = ($data['format_bar'] ? $data['entry'] : noHtml($data['entry']));



$data['first_response'] = (isset($post['resposta']) ? noHtml($post['resposta']) : "");
$data['total_files_to_deal'] = (isset($post['cont']) ? noHtml($post['cont']) : 0);
$data['total_relatives_to_deal'] = (isset($post['contSub']) ? noHtml($post['contSub']) : 0);
$data['total_entries_to_deal'] = (isset($post['total_asset']) ? noHtml($post['total_asset']) : 0);


/* Canal padrão caso não seja informado */
if (empty($data['channel'])) {
    $data['channel'] = $defaultChannel;
}


/* Informações sobre a área destino */
$rowAreaTo = ($data['sistema'] != '-1' ? getAreaInfo($conn, $data['sistema']) : getAreaInfo($conn, $screen['conf_opentoarea']));



/* Para pegar o estado da ocorrência antes da atualização e permitir a gravação do log de modificações */
$arrayBeforePost = "";
if (!empty($data['numero'])) {
    $qryfull = $QRY["ocorrencias_full_ini"]." WHERE o.numero = '" . $data['numero'] . "' ";
    $execfull = $conn->query($qryfull);
    $arrayBeforePost = $execfull->fetch();
}

/* Tratando de acordo com os actions */
if ($data['action'] == "open") {
    $data['status'] = 1; /* Aguardando atendimento */
    // if ($data['forward'] != "-1") {
    if ($data['forward'] != $_SESSION['s_uid']) {
        $data['status'] = $config['conf_foward_when_open'];
    }

    if ($data['is_scheduled']) {
        $data['status'] =  $config['conf_schedule_status'];
    }

    $data['aberto_por'] = (isset($_SESSION['s_uid']) ? intval($_SESSION['s_uid']) : "");

} elseif ($data['action'] == "edit") {
    

    $data['status'] = (isset($post['status']) && !empty($post['status']) ? noHtml($post['status']) : "-1");
    $data['old_status'] = (isset($post['oldStatus']) ? noHtml($post['oldStatus']) : $data['status']);

    /* Se o chamado estiver encerrado não permito que o status seja alterado */
    $data['status'] = ($data['old_status'] == 4 ? 4 : $data['status']);


    $data['aberto_por'] = $arrayBeforePost['aberto_por_cod'];


    $qryGlobalUri = "SELECT * FROM global_tickets WHERE gt_ticket = '" . $data['numero'] . "' ";
    $resGlobalUri = $conn->query($qryGlobalUri);
    $rowGlobalUri = $resGlobalUri->fetch();
    $data['global_uri'] = (!empty($rowGlobalUri['gt_id']) ? $rowGlobalUri['gt_id'] : "");

} elseif ($data['action'] == "close") {
    $data['status'] = $doneStatus; /* Encerrado ou concluído*/

    $data['aberto_por'] = $arrayBeforePost['aberto_por_cod'];

    getGlobalTicketRatingId($conn, $data['numero']);

    $qryGlobalUri = "SELECT * FROM global_tickets WHERE gt_ticket = '" . $data['numero'] . "' ";
    $resGlobalUri = $conn->query($qryGlobalUri);
    $rowGlobalUri = $resGlobalUri->fetch();
    $data['global_uri'] = (!empty($rowGlobalUri['gt_id']) ? $rowGlobalUri['gt_id'] : "");
}

$tooShortTag = false;
if (!empty($data['input_tags'])) {
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
}




/* Checagem de preenchimento dos campos obrigatórios*/
if ($data['action'] == "open") {

    /* Recebe os valores de obrigatorieda para cada campo onde se aplica */
	$required_fields = getFormRequiredInfo($conn, $data['profile_id']);

    if ($screen['conf_scr_client'] == '1' &&  empty($data['client']) && (!count($required_fields) || $required_fields['conf_scr_client'])) {
        $data['success'] = false; 
        $data['field_id'] = "client";
    } elseif ($screen['conf_scr_area'] == '1' && $data['sistema'] == "-1" && (!count($required_fields) || $required_fields['conf_scr_area'])) {
        $data['success'] = false; 
        $data['field_id'] = "idArea";
    } elseif ($screen['conf_scr_prob'] == '1' && $data['problema'] == "-1" && (!count($required_fields) || $required_fields['conf_scr_prob'])) {
        $data['success'] = false; 
        $data['field_id'] = "idProblema";
    } elseif ($screen['conf_scr_desc'] == '1' && $data['descricao'] == "" && (!count($required_fields) || $required_fields['conf_scr_desc'])) {
        $data['success'] = false; 
        $data['field_id'] = "idDescricao";
    // } elseif ($screen['conf_scr_unit'] && $data['unidade'] == "-1"  && $fieldsNew->isRequired("unit")) {
    } elseif ($screen['conf_scr_unit'] && $data['unidade'] == "-1"  && (!count($required_fields) || $required_fields['conf_scr_unit'])) {
        $data['success'] = false; 
        $data['field_id'] = "idUnidade";
    // } elseif ($screen['conf_scr_tag'] && $data['etiqueta'] == ""  && $fieldsNew->isRequired("asset_tag")) {
    } elseif ($screen['conf_scr_tag'] && $data['etiqueta'] == ""  && (!count($required_fields) || $required_fields['conf_scr_tag'])) {
        $data['success'] = false; 
        $data['field_id'] = "idEtiqueta";
    // } elseif ($screen['conf_scr_contact'] == '1' && $data['contato'] == ""  && $fieldsNew->isRequired("contact")) {
    } elseif ($screen['conf_scr_contact'] == '1' && $data['contato'] == ""  && (!count($required_fields) || $required_fields['conf_scr_contact'])) {
        $data['success'] = false; 
        $data['field_id'] = "contato";
    // } elseif ($screen['conf_scr_contact_email'] == '1' && $data['contato_email'] == ""  && $fieldsNew->isRequired("contact_email")) {
    } elseif ($screen['conf_scr_contact_email'] == '1' && $data['contato_email'] == ""  && (!count($required_fields) || $required_fields['conf_scr_contact_email'])) {
        $data['success'] = false; 
        $data['field_id'] = "contato_email";
    // } elseif ($screen['conf_scr_fone'] == '1' && $data['telefone'] == ""  && $fieldsNew->isRequired("phone")) {
    } elseif ($screen['conf_scr_fone'] == '1' && $data['telefone'] == ""  && (!count($required_fields) || $required_fields['conf_scr_fone'])) {
        $data['success'] = false; 
        $data['field_id'] = "idTelefone";
    // } elseif ($screen['conf_scr_local'] == '1' && $data['department'] == "-1"  && $fieldsNew->isRequired("department")) {
    } elseif ($screen['conf_scr_local'] == '1' && $data['department'] == "-1"  && (!count($required_fields) || $required_fields['conf_scr_local'])) {
        $data['success'] = false; 
        $data['field_id'] = "idLocal";
    } elseif ($screen['conf_scr_upload'] == '1' && !$hasFile  && (!count($required_fields) || (isset($required_fields['conf_scr_upload']) && $required_fields['conf_scr_upload']))) {
        $data['success'] = false; 
        $data['field_id'] = "idInputFile";
    } 
    elseif ($screen['conf_scr_foward'] == '1' && $data['forward'] == $_SESSION['s_uid'] && (!count($required_fields) || $required_fields['conf_scr_foward'])) {
        $data['success'] = false; 
        $data['field_id'] = "idFoward";
    }


    if ($data['success'] == false) {
        $data['message'] = message('warning', '', TRANS('MSG_EMPTY_DATA'), '');
        echo json_encode($data);
        return false;
    }

    if ($data['contato_email'] != "" && !filter_var($data['contato_email'], FILTER_VALIDATE_EMAIL)) {
        $data['success'] = false; 
        $data['field_id'] = "contato_email";
        $data['message'] = message('warning', '', TRANS('WRONG_FORMATTED_URL'), '');
        echo json_encode($data);
        return false;
    }


    if ($data['is_scheduled']) {
        if (!isValidDate($data['date_schedule_typed'], 'd/m/Y H:i')) {
            $data['success'] = false; 
            $data['field_id'] = "idDate_schedule";
            $data['message'] = message('warning', '', TRANS('BAD_FIELD_FORMAT'), '');
            echo json_encode($data);
            return false;
        }
    
        $today = new DateTime();
        $schedule_to = new DateTime($data['schedule_to']);
        if ($today > $schedule_to) {
            $data['success'] = false; 
            $data['field_id'] = "idDate_schedule";
            $data['message'] = message('warning', '', TRANS('DATE_NEEDS_TO_BE_IN_FUTURE'), '');
            echo json_encode($data);
            return false;
        }
    }
}


/* Validações na edição */
if ($data['action'] == "edit") {

    if (empty($data['client']) && $fieldsEdit->isRequired("client")) {
        $data['success'] = false; 
        $data['field_id'] = "client";
    } elseif ($data['sistema'] == "-1"  && $fieldsEdit->isRequired("area")) {
        $data['success'] = false; 
        $data['field_id'] = "idArea";
    } elseif ($data['problema'] == "-1"  && $fieldsEdit->isRequired("issue")) {
        $data['success'] = false; 
        $data['field_id'] = "idProblema";
    } elseif ($data['unidade'] == "-1"  && $fieldsEdit->isRequired("unit")) {
        $data['success'] = false; 
        $data['field_id'] = "idUnidade";
    } elseif ($data['etiqueta'] == ""  && $fieldsEdit->isRequired("asset_tag")) {
        $data['success'] = false; 
        $data['field_id'] = "idEtiqueta";
    } elseif ($data['contato'] == "" && $fieldsEdit->isRequired("contact")) {
        $data['success'] = false; 
        $data['field_id'] = "contato";
    } elseif ($data['contato_email'] == ""  && $fieldsEdit->isRequired("contact_email")) {
        $data['success'] = false; 
        $data['field_id'] = "contato_email";
    } elseif ($data['telefone'] == ""  && $fieldsEdit->isRequired("phone")) {
        $data['success'] = false; 
        $data['field_id'] = "idTelefone";
    } elseif ($data['department'] == "-1"  && $fieldsEdit->isRequired("department")) {
        $data['success'] = false; 
        $data['field_id'] = "idLocal";
    } elseif ($data['entry'] == "" && $data['action'] == "edit") {
        $data['success'] = false; 
        $data['field_id'] = "idAssentamento";
    } elseif ($data['technical_description'] == "" && $data['action'] == "close") {
        $data['success'] = false; 
        $data['field_id'] = "idDescProblema";
    } elseif ($data['technical_solution'] == "" && $data['action'] == "close") {
        $data['success'] = false; 
        $data['field_id'] = "idDescSolucao";
    } elseif ($data['justificativa'] == "" && $data['sla_out'] == 1 && $config['conf_desc_sla_out'] && $data['action'] == "close") {
        $data['success'] = false; 
        $data['field_id'] = "idJustificativa";
    }

    if ($data['success'] == false) {
        $data['message'] = message('warning', '', TRANS('MSG_EMPTY_DATA'), '');
        echo json_encode($data);
        return false;
    }

    if ($data['contato_email'] != "" && !filter_var($data['contato_email'], FILTER_VALIDATE_EMAIL)) {
        $data['success'] = false; 
        $data['field_id'] = "contato_email";
        $data['message'] = message('warning', '', TRANS('WRONG_FORMATTED_URL'), '');
        echo json_encode($data);
        return false;
    }
}

/* Validações no encerramento */
if ($data['action'] == "close") {

    if (empty($data['client'])  && $fieldsClose->isRequired("client")) {
        $data['success'] = false; 
        $data['field_id'] = "client";
    } elseif ($data['sistema'] == "-1"  && $fieldsClose->isRequired("area")) {
        $data['success'] = false; 
        $data['field_id'] = "idArea";
    } elseif ($data['problema'] == "-1"  && $fieldsClose->isRequired("issue")) {
        $data['success'] = false; 
        $data['field_id'] = "idProblema";
    } elseif ($data['unidade'] == "-1"  && $fieldsClose->isRequired("unit")) {
        $data['success'] = false; 
        $data['field_id'] = "idUnidade";
    } elseif ($data['etiqueta'] == ""  && $fieldsClose->isRequired("asset_tag")) {
        $data['success'] = false; 
        $data['field_id'] = "idEtiqueta";
    } elseif ($data['contato'] == ""  && $fieldsClose->isRequired("contact")) {
        $data['success'] = false; 
        $data['field_id'] = "contato";
    } elseif ($data['contato_email'] == ""  && $fieldsClose->isRequired("contact_email")) {
        $data['success'] = false; 
        $data['field_id'] = "contato_email";
    } elseif ($data['telefone'] == ""  && $fieldsClose->isRequired("phone")) {
        $data['success'] = false; 
        $data['field_id'] = "idTelefone";
    } elseif ($data['department'] == "-1"  && $fieldsClose->isRequired("department")) {
        $data['success'] = false; 
        $data['field_id'] = "idLocal";
    } /* elseif ($data['entry'] == "" && $data['action'] == "close") {
        $data['success'] = false; 
        $data['field_id'] = "idAssentamento";
    } */ elseif ($data['technical_description'] == "" && $data['action'] == "close") {
        $data['success'] = false; 
        $data['field_id'] = "idDescProblema";
    } elseif ($data['technical_solution'] == "" && $data['action'] == "close") {
        $data['success'] = false; 
        $data['field_id'] = "idDescSolucao";
    } elseif ($data['justificativa'] == "" && $data['sla_out'] == 1 && $config['conf_desc_sla_out'] && $data['action'] == "close") {
        $data['success'] = false; 
        $data['field_id'] = "idJustificativa";
    }

    if ($data['success'] == false) {
        $data['message'] = message('warning', '', TRANS('MSG_EMPTY_DATA'), '');
        echo json_encode($data);
        return false;
    }

    if ($data['contato_email'] != "" && !filter_var($data['contato_email'], FILTER_VALIDATE_EMAIL)) {
        $data['success'] = false; 
        $data['field_id'] = "contato_email";
        $data['message'] = message('warning', '', TRANS('WRONG_FORMATTED_URL'), '');
        echo json_encode($data);
        return false;
    }
}


/* Tratar e validar os campos personalizados - todos os actions */
$dataCustom = [];
$fields_ids = [];
$fields_only_edition_ids = [];
/* No caso de abertura, restringe aos campos extras existentes no perfil de tela */
if ($screen['conf_scr_custom_ids'] || $data['action'] != 'open') { 
    
    // $fields_ids = explode(',', $screen['conf_scr_custom_ids']);
    $fields_ids = ($screen['conf_scr_custom_ids'] ? explode(',', $screen['conf_scr_custom_ids']) : []);
    $fields_only_edition_ids = ($screen['cfields_only_edition'] ? explode(',', $screen['cfields_only_edition']) : []);
    
    $sql = "SELECT * FROM custom_fields 
            WHERE 
                field_table_to = 'ocorrencias' AND 
                field_active = 1 
            ORDER BY 
                field_order, field_name";
    try {
        $res = $conn->query($sql);
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $cfield) {
                
                if ($data['action'] != 'open' || in_array($cfield['id'], $fields_ids) ) {

                    /* Seleção multipla vazia */
                    if (($cfield['field_type'] == 'select_multi') && !isset($post[$cfield['field_name']])) {
                        $post[$cfield['field_name']] = '';
                    }

                    
                    $dataCustom[] = $cfield; /* Guardado para a área de inserção/atualização */
                    
                    /* Para possibilitar o Controle de acordo com a opção global conf_cfield_only_opened */
                    $field_value = [];
                    $field_value['field_id'] = "";
                    if ($data['action'] != 'open') {
                        $field_value = getTicketCustomFields($conn, $data['numero'], $cfield['id']);
                    }
                    
                    /* Controle de acordo com a opção global conf_cfield_only_opened */
                    if ($data['action'] == 'open' || !$config['conf_cfield_only_opened'] || !empty($field_value['field_id'])) {

                        // if (empty($post[$cfield['field_name']]) && $cfield['field_required']) {
                        if (empty($post[$cfield['field_name']]) && $cfield['field_required'] && !in_array($cfield['id'], $fields_only_edition_ids)) {
                            $data['success'] = false;
                            $data['field_id'] = $cfield['field_name'];
                            $data['message'] = message('warning', '', TRANS('MSG_EMPTY_DATA'), '');
                            echo json_encode($data);
                            return false;
                        }

                        if ($cfield['field_type'] == 'number') {
                            if ($post[$cfield['field_name']] != "" && !filter_var($post[$cfield['field_name']], FILTER_VALIDATE_INT)) {
                                $data['success'] = false; 
                                $data['field_id'] = $cfield['field_name'];
                            }
                        } elseif ($cfield['field_type'] == 'date') {
                            if (isset($post[$cfield['field_name']]) && $post[$cfield['field_name']] != "" && !isValidDate($post[$cfield['field_name']], 'd/m/Y')) {
                                $data['success'] = false; 
                                $data['field_id'] = $cfield['field_name'];
                            }
                        } elseif ($cfield['field_type'] == 'datetime') {
                            if (isset($post[$cfield['field_name']]) && $post[$cfield['field_name']] != "" && !isValidDate($post[$cfield['field_name']], 'd/m/Y H:i')) {
                                $data['success'] = false; 
                                $data['field_id'] = $cfield['field_name'];
                            }
                        } elseif ($cfield['field_type'] == 'time') {
                            if (isset($post[$cfield['field_name']]) && $post[$cfield['field_name']] != "" && !isValidDate($post[$cfield['field_name']], 'H:i')) {
                                $data['success'] = false; 
                                $data['field_id'] = $cfield['field_name'];
                            }
                        } elseif ($cfield['field_type'] == 'checkbox') {
                            // if ($post[$cfield['field_name']] != "") {
                            //     $data['success'] = false; 
                            //     $data['field_id'] = $cfield['field_name'];
                            // }
                        // } elseif ($post[$cfield['field_name']] != "" && $cfield['field_type'] == 'text' && !empty($cfield['field_mask'] && $cfield['field_mask_regex'])) {
                        } elseif (array_key_exists($cfield['field_name'], $post) && $post[$cfield['field_name']] != "" && $cfield['field_type'] == 'text' && !empty($cfield['field_mask'] && $cfield['field_mask_regex'])) {
                            
                            /* Validar a expressão regular */
                            if (!preg_match('/' . $cfield['field_mask'] . '/i', $post[$cfield['field_name']])) {
                                $data['success'] = false; 
                                $data['field_id'] = $cfield['field_name'];
                            }
                        }
                        
                        if (!$data['success']) {
                            $data['message'] = message('warning', 'Ooops!', TRANS('BAD_FIELD_FORMAT'),'');
                            echo json_encode($data);
                            return false;
                        }
                    }
                }
            }
        }
    }
    catch (Exception $e) {
        $exception .= "<hr>" . $e->getMessage();
    }
}

// var_dump($post); exit;
// var_dump([
//     'dataCustom' => $dataCustom,
// ]); exit;

/* Checagens para upload de arquivos - vale para todos os actions */
// $totalFiles = ($_FILES ? count($_FILES['anexo']['name']) : 0);
$filesClean = [];


if ($totalFiles > $config['conf_qtd_max_anexos']) {

    $data['success'] = false; 
    $data['message'] = message('warning', 'Ooops!', 'Too many files','');
    echo json_encode($data);
    return false;
}

$uploadMessage = "";
$emptyFiles = 0;
/* Testa os arquivos enviados para montar os índices do filesClean*/
if ($totalFiles) {

    /* Removendo o indice 'files' que pode existir em alguns casos enviado pelo Summernote */
    unset($_FILES['files']);

    foreach ($_FILES as $anexo) {
        $file = array();
        for ($i = 0; $i < $totalFiles; $i++) {
            /* fazer o que precisar com cada arquivo */
            /* acessa:  $anexo['name'][$i] $anexo['type'][$i] $anexo['tmp_name'][$i] $anexo['size'][$i]*/
            
            if (!empty($anexo['name'][$i])) {
                $file['name'] =  $anexo['name'][$i];
                $file['type'] =  $anexo['type'][$i];
                $file['tmp_name'] =  $anexo['tmp_name'][$i];
                $file['error'] =  $anexo['error'][$i];
                $file['size'] =  $anexo['size'][$i];
    
                $upld = upload('anexo', $config, $config['conf_upld_file_types'], $file);
                if ($upld == "OK") {
                    $recordFile[$i] = true;
                    $filesClean[] = $file;
                } else {
                    $recordFile[$i] = false;
                    $uploadMessage .= $upld;
    
                    // $data['success'] = false; 
                    // $data['field_id'] = "idInputFile";
                    // $data['message'] = message('warning', 'Ooops!', $uploadMessage, '');
                    // echo json_encode($data);
                    // return false;                
                }
            } else {
                $emptyFiles++;
            }
        }
    }
    $totalFiles -= $emptyFiles;

    if (strlen((string)$uploadMessage) > 0) {
        $data['success'] = false; 
        $data['field_id'] = "idInputFile";
        $data['message'] = message('warning', 'Ooops!', $uploadMessage, '');
        echo json_encode($data);
        return false;                
    }
}


/* Processamento - Abertura */
if ($data['action'] == "open") {

    /* Verificação de CSRF */
    if (!csrf_verify($post)) {
        $data['success'] = false; 
        $data['message'] = message('warning', 'Ooops!', TRANS('FORM_ALREADY_SENT'),'');
        echo json_encode($data);
        return false;
    }

    $data['sistema'] = ($data['sistema'] == '-1' ? $data['area_destino'] : $data['sistema']);
    
	$sql = "INSERT INTO ocorrencias 
        (
            client,
            problema, descricao, instituicao, equipamento, 
            sistema, contato, contato_email, telefone, local, 
            operador, data_abertura, data_fechamento, status, data_atendimento, 
            aberto_por, oco_scheduled, oco_scheduled_to, 
            oco_real_open_date, date_first_queued, oco_prior, oco_channel, 
            profile_id
        )
		VALUES 
        (
            " . dbField($data['client']) . ",
            '" . $data['radio_prob'] . "', :descricao, " . dbField($data['unidade']) . ", '" . $data['etiqueta'] . "',
            '" . $data['sistema'] . "', '" . $data['contato'] . "', '" . $data['contato_email'] . "', '" . $data['telefone'] . "', '" . $data['department'] . "',
            '" . $data['operator'] . "', '{$now}', null, '" . $data['status'] . "', null,
            '" . $data['aberto_por'] . "', '" . $data['is_scheduled'] . "', " . dbField($data['schedule_to'],'date') . ", 
            '{$now}', null, '" . $data['prioridade'] . "', '" . $data['channel'] . "',
            " . dbField($data['profile_id']) . "
        )";
		
    try {
        $res = $conn->prepare($sql);
        $res->bindParam(':descricao', $data['descricao'], PDO::PARAM_STR);
        $res->execute();

        $data['numero'] = $conn->lastInsertId();
        $data['global_uri'] = random64();

        /* Gravação da data na tabela tickets_stages */
        $timeStage = insert_ticket_stage($conn, $data['numero'], 'start', $data['status']);





        /* Inserção dos campos personalizados */
        if (count($dataCustom)) {
            foreach ($dataCustom as $cfield) {
                
                if ($cfield['field_type'] == 'checkbox' && !isset($post[$cfield['field_name']])) {
                    $data[$cfield['field_name']] = '';
                } elseif (!array_key_exists($cfield['field_name'], $post)){
                    /* Campos que estarão vazios no formulário de entrada - só estarão disponíveis na edição */
                    $data[$cfield['field_name']] = '';
                } else {
                    $data[$cfield['field_name']] = (is_array($post[$cfield['field_name']]) ? noHtml(implode(',', $post[$cfield['field_name']])) :  noHtml($post[$cfield['field_name']]) );
                }
                
                $isFieldKey = ($cfield['field_type'] == 'select' || $cfield['field_type'] == 'select_multi' ? 1 : 'null') ;

                /* Tratar data */
                if ($cfield['field_type'] == 'date' && !empty($data[$cfield['field_name']])) {
                    $data[$cfield['field_name']] = dateDB($data[$cfield['field_name']]);
                } elseif ($cfield['field_type'] == 'datetime' && !empty($data[$cfield['field_name']])) {
                    $data[$cfield['field_name']] = dateDB($data[$cfield['field_name']]);
                }
                
                $sqlIns = "INSERT INTO 
                            tickets_x_cfields (ticket, cfield_id, cfield_value, cfield_is_key) 
                            VALUES 
                            ('" . $data['numero'] . "', '" . $cfield['id'] . "', " . dbField($data[$cfield['field_name']],'text') . ", " . $isFieldKey . ")
                            ";
                try {
                    $resIns = $conn->exec($sqlIns);
                }
                catch (Exception $e) {
                    $exception .= "<hr>" . $e->getMessage() . "<hr>" . $sqlIns;
                }
            }
        }





        
        /* Grava a uri global */
        $qryGlobalUri = "INSERT INTO global_tickets (gt_ticket, gt_id) values (" . $data['numero'] . ", '" . $data['global_uri'] . "')";
        $conn->exec($qryGlobalUri);

        /* Primeiro registro do log de modificações da ocorrência */
        $firstLog = firstLog($conn, $data['numero'], 0); 

        /* Se for um subchamado */
        if (!empty($data['father'])) {
            $sqlDep = "INSERT INTO ocodeps (dep_pai, dep_filho) values (" . $data['father'] . ", " . $data['numero'] . ")";
            try {
                $conn->exec($sqlDep);

                $entryMessage = TRANS('ENTRY_SUBTICKET_OPENED') . " " . $data['numero'];

                /* Gravar assentamento no chamado pai */
                $sqlSubTicket = "INSERT INTO assentamentos 
                (
                    ocorrencia, assentamento, data, responsavel, asset_privated, tipo_assentamento
                )
                VALUES 
                (
                    " . $data['father'] . ", 
                    '" . $entryMessage . "',
                    '" . $now . "', 
                    '" . $data['logado'] . "', 
                    0,
                    10
                )";

                try {
                    $conn->exec($sqlSubTicket);
                } catch (Exception $e) {
                    $exception .= "<hr>" . $e->getMessage();
                }
            }
            catch (Exception $e) {
                $exception .= "<hr>" . $e->getMessage();
            }
        }

        $data['success'] = true; 


        if (!empty($uploadMessage)) {
            $data['message'] = $data['message'] . "<br />" . $uploadMessage;
        }
        
        
    } catch (Exception $e) {
        $exception .= "<hr>" . $e->getMessage();
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_SAVE_RECORD') . "<hr>" . $sql . $exception;
        $_SESSION['flash'] = message('danger', '', $data['message'], '');
        echo json_encode($data);
        return false;
    }

} elseif ($data['action'] == 'edit') {

    if (!csrf_verify($post)) {
        $data['success'] = false; 
        $data['message'] = message('warning', 'Ooops!', TRANS('FORM_ALREADY_SENT'),'');
    
        echo json_encode($data);
        return false;
    }

    /* Insere o primeiro registro de log caso não exista - chamados anteriores a versao 3.0 */
    $firstLog = firstLog($conn, $data['numero']);

    $terms = "";
    $newStatus = false;
    if ($data['status'] != $data['old_status'] && $data['old_status'] != 4) {
        /* Status alterado - relevante em função do registro de mudança na tabela tickets_stages e para tirar de agendamento*/
        $newStatus = true;
        $terms .= " oco_scheduled = 0, ";
    }
    
    if (!empty($data['first_response'])) {
        $terms .= " data_atendimento = '" . $now . "', ";
    }

    $sql = "UPDATE ocorrencias SET 
    
                client = " . dbField($data['client']) . ", 
                operador = " . dbField($data['operator']) . ", 
                problema = '" . $data['radio_prob'] . "', 
                instituicao = " . dbField($data['unidade']) . ", 
                equipamento = " . dbField($data['etiqueta'],'text') . ", 
                sistema = '" . $data['sistema'] . "', 
                local = '" . $data['department'] . "', 
                status = '" . $data['status'] . "', 
                {$terms} 
                contato = '" . noHtml($data['contato']) . "', 
                contato_email = '" . noHtml($data['contato_email']) . "', 
                telefone = '" . noHtml($data['telefone']) . "', 
                oco_prior = '" . $data['prioridade'] . "',  
                oco_channel = '" . $data['channel'] . "', 
                oco_tag = " . dbField($data['input_tags'],'text') . "
            WHERE 
                numero = '" . $data['numero'] . "'";
            
    try {
        $conn->exec($sql);

        if ($newStatus) {
            /* Gravação da data na tabela tickets_stages */
            $stopTimeStage = insert_ticket_stage($conn, $data['numero'], 'stop', $data['status']);
            $startTimeStage = insert_ticket_stage($conn, $data['numero'], 'start', $data['status']);
        }

        $data['success'] = true; 
        $data['message'] = TRANS('MSG_SUCCESS_EDIT');



        /* Atualização ou inserção dos campos personalizados */
        if (count($dataCustom)) {
            foreach ($dataCustom as $cfield) {
                
                
                /* Para possibilitar o Controle de acordo com a opção global conf_cfield_only_opened */
                $field_value = [];
                $field_value = getTicketCustomFields($conn, $data['numero'], $cfield['id']);
                

                /* Controle de acordo com a opção global conf_cfield_only_opened */
                if (!$config['conf_cfield_only_opened'] || !empty($field_value['field_id'])) {


                    if ($cfield['field_type'] == 'checkbox' && !isset($post[$cfield['field_name']])) {
                        $data[$cfield['field_name']] = '';
                    } else {
                        $data[$cfield['field_name']] = (is_array($post[$cfield['field_name']]) ? noHtml(implode(',', $post[$cfield['field_name']])) :  noHtml($post[$cfield['field_name']]) );
                    }

                    $isFieldKey = ($cfield['field_type'] == 'select' || $cfield['field_type'] == 'select_multi' ? 1 : 'null') ;

                    /* Tratar data */
                    if ($cfield['field_type'] == 'date' && !empty($data[$cfield['field_name']])) {
                        $data[$cfield['field_name']] = dateDB($data[$cfield['field_name']]);
                    } elseif ($cfield['field_type'] == 'datetime' && !empty($data[$cfield['field_name']])) {
                        $data[$cfield['field_name']] = dateDB($data[$cfield['field_name']]);
                    }
                    

                    /* Preciso identificar se o campo já existe para o chamado - caso contrário, é inserção */
                    $sql = "SELECT id FROM tickets_x_cfields 
                            WHERE ticket = '" . $data['numero'] . "' AND cfield_id = '" . $cfield['id'] . "' ";
                    try {
                        $res = $conn->query($sql);
                        if (!$res->rowCount() && !$config['conf_cfield_only_opened']) {
                            
                            /* Nesse caso preciso inserir */
                            $sqlIns = "INSERT INTO 
                                tickets_x_cfields (ticket, cfield_id, cfield_value, cfield_is_key) 
                                VALUES 
                                ('" . $data['numero'] . "', '" . $cfield['id'] . "', " . dbField($data[$cfield['field_name']],'text') . ", " . $isFieldKey . ")
                                ";
                            try {
                                $resIns = $conn->exec($sqlIns);
                            }
                            catch (Exception $e) {
                                $exception .= "<hr>" . $e->getMessage() . "<hr>" . $sqlIns;
                            }

                        } else {
                            
                            /* Nesse caso preciso Atualizar */
                            $sqlUpd = "UPDATE
                                            tickets_x_cfields 
                                        SET
                                            cfield_value =  " . dbField($data[$cfield['field_name']], 'text') . "
                                        WHERE
                                            ticket = '" . $data['numero'] . "' AND 
                                            cfield_id = '" . $cfield['id'] . "'
                                        ";
                            try {
                                $resIns = $conn->exec($sqlUpd);
                            } catch (Exception $e) {
                                $exception .= "<hr>" . $e->getMessage() . "<hr>" . $sqlUpd;
                            }
                        }
                    }
                    catch (Exception $e) {
                        $exception .= "<hr>" . $e->getMessage();
                    }
                }
            }
        }



        
        /* Array para a função recordLog */
        $afterPost = [];
        $afterPost['prioridade'] = $data['prioridade'];
        $afterPost['area'] = $data['sistema'];
        $afterPost['problema'] = $data['radio_prob'];
        $afterPost['unidade'] = $data['unidade'];
        $afterPost['etiqueta'] = $data['etiqueta'];
        $afterPost['contato'] = $data['contato'];
        $afterPost['contato_email'] = $data['contato_email'];
        $afterPost['telefone'] = $data['telefone'];
        $afterPost['departamento'] = $data['department'];
        $afterPost['operador'] = $data['operator'];
        $afterPost['status'] = $data['status'];
        $afterPost['cliente'] = $data['client'];
        
        /* Função que grava o registro de alterações do chamado */
        $recordLog = recordLog($conn, $data['numero'], $arrayBeforePost, $afterPost, 1);


        /* Se alguma tag for nova, gravar na tabela de referência: input_tags */
        if (!empty($data['input_tags'])) {
            $arrayTags = explode(',', $data['input_tags']);
            saveNewTags($conn, $arrayTags);
        }

        
    } catch (Exception $e) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_DATA_UPDATE') . "<br />". $sql . "<br />" . $e->getMessage();
        $_SESSION['flash'] = message('danger', 'Ooops!', $data['message'], '');
        echo json_encode($data);
        return false;
    }

} elseif ($data['action'] == 'close') {

    if (!csrf_verify($post)) {
        $data['success'] = false; 
        $data['message'] = message('warning', 'Ooops!', TRANS('FORM_ALREADY_SENT'),'');
    
        echo json_encode($data);
        return false;
    }

    /* Insere o primeiro registro de log caso não exista - chamados anteriores a versao 3.0 */
    $firstLog = firstLog($conn, $data['numero']);

    $terms = "";
    if (empty($data['data_atendimento'])) {
        $terms .= " data_atendimento = '" . $now . "', ";
    }

    $sql = "UPDATE ocorrencias SET 
    
                client = " . dbField($data['client']) . ", 
                operador = " . dbField($data['operator']) . ", 
                problema = '" . $data['radio_prob'] . "', 
                instituicao = " . dbField($data['unidade']) . ", 
                equipamento = " . dbField($data['etiqueta'],'text') . ", 
                sistema = '" . $data['sistema'] . "', 
                local = '" . $data['department'] . "', 
                data_fechamento = '" . $now . "', 
                status = {$data['status']}, 
                oco_scheduled = 0, 
                {$terms} 
                contato = '" . noHtml($data['contato']) . "', 
                contato_email = '" . noHtml($data['contato_email']) . "', 
                oco_channel = '" . $data['channel'] . "', 
                telefone = '" . noHtml($data['telefone']) . "', 
                oco_prior = '" . $data['prioridade'] . "', 
                oco_script_sol = " . dbField($data['script_solution']) . ", 
                oco_tag = " . dbField($data['input_tags'],'text') . "
            WHERE 
                numero = '" . $data['numero'] . "'";

    try {
        $conn->exec($sql);

        /* Gravação da data na tabela tickets_stages */
        /* A primeira entrada serve apenas para gravar a conclusão do status anterior ao encerramento */
        $stopTimeStage = insert_ticket_stage($conn, $data['numero'], 'stop', $data['status']);
        /* As duas próximas entradas servem para lançar o status de encerramento - o tempo nao será contabilizado */
        $stopTimeStage = insert_ticket_stage($conn, $data['numero'], 'start', $data['status']);
        $stopTimeStage = insert_ticket_stage($conn, $data['numero'], 'stop', $data['status']);

        $data['success'] = true; 
        $data['message'] = TRANS('MSG_OCCO_FINISH_SUCCESS');


        /**
         * Inserção de registro no tickets_rated, caso não exista
         * Se o operador responsável for também o solicitante não é inserido o registro
         */
        // if ($arrayBeforePost['aberto_por_cod'] != $data['operator']) {
        if ($needToBeRated) {
            $sql = "SELECT ticket FROM tickets_rated WHERE ticket = {$data['numero']}";
            $res = $conn->query($sql);
            if (!$res->rowCount()) {
                $sql = "INSERT INTO tickets_rated
                (
                    ticket, rate, rate_date, automatic_rate, rejected_count
                )
                VALUES
                (
                    {$data['numero']}, NULL, NULL, 0, 0
                )
                ";
                try {
                    $res = $conn->exec($sql);
                }
                catch (Exception $e) {
                    $exception .= "<hr>" . $e->getMessage();
                }
            }
        }


         /* Atualização ou inserção dos campos personalizados */
         if (count($dataCustom)) {
            foreach ($dataCustom as $cfield) {
                
                
                /* Para possibilitar o Controle de acordo com a opção global conf_cfield_only_opened */
                $field_value = [];
                $field_value = getTicketCustomFields($conn, $data['numero'], $cfield['id']);
                

                /* Controle de acordo com a opção global conf_cfield_only_opened */
                if (!$config['conf_cfield_only_opened'] || !empty($field_value['field_id'])) {

                    if ($cfield['field_type'] == 'checkbox' && !isset($post[$cfield['field_name']])) {
                        $data[$cfield['field_name']] = '';
                    } else {
                        $data[$cfield['field_name']] = (is_array($post[$cfield['field_name']]) ? noHtml(implode(',', $post[$cfield['field_name']])) :  noHtml($post[$cfield['field_name']]) );
                    }
                    
                    $isFieldKey = ($cfield['field_type'] == 'select' || $cfield['field_type'] == 'select_multi' ? 1 : 'null') ;

                    /* Tratar data */
                    if ($cfield['field_type'] == 'date' && !empty($data[$cfield['field_name']])) {
                        $data[$cfield['field_name']] = dateDB($data[$cfield['field_name']]);
                    } elseif ($cfield['field_type'] == 'datetime' && !empty($data[$cfield['field_name']])) {
                        $data[$cfield['field_name']] = dateDB($data[$cfield['field_name']]);
                    }
                    

                    /* Preciso identificar se o campo já existe para o chamado - caso contrário, é inserção */
                    $sql = "SELECT id FROM tickets_x_cfields 
                            WHERE ticket = '" . $data['numero'] . "' AND cfield_id = '" . $cfield['id'] . "' ";
                    try {
                        $res = $conn->query($sql);
                        if (!$res->rowCount() && !$config['conf_cfield_only_opened']) {
                            
                            /* Nesse caso preciso inserir */
                            $sqlIns = "INSERT INTO 
                                tickets_x_cfields (ticket, cfield_id, cfield_value, cfield_is_key) 
                                VALUES 
                                ('" . $data['numero'] . "', '" . $cfield['id'] . "', " . dbField($data[$cfield['field_name']],'text') . ", " . $isFieldKey . ")
                                ";
                            try {
                                $resIns = $conn->exec($sqlIns);
                            }
                            catch (Exception $e) {
                                $exception .= "<hr>" . $e->getMessage() . "<hr>" . $sqlIns;
                            }

                        } else {
                            
                            /* Nesse caso preciso Atualizar */
                            $sqlUpd = "UPDATE
                                            tickets_x_cfields 
                                        SET
                                            cfield_value =  " . dbField($data[$cfield['field_name']], 'text') . "
                                        WHERE
                                            ticket = '" . $data['numero'] . "' AND 
                                            cfield_id = '" . $cfield['id'] . "'
                                        ";
                            try {
                                $resIns = $conn->exec($sqlUpd);
                            } catch (Exception $e) {
                                $exception .= "<hr>" . $e->getMessage() . "<hr>" . $sqlUpd;
                            }
                        }
                    }
                    catch (Exception $e) {
                        $exception .= "<hr>" . $e->getMessage();
                    }
                }
            }
        }



        
        /* Array para a função recordLog */
        $afterPost = [];
        $afterPost['prioridade'] = $data['prioridade'];
        $afterPost['area'] = $data['sistema'];
        $afterPost['problema'] = $data['radio_prob'];
        $afterPost['unidade'] = $data['unidade'];
        $afterPost['etiqueta'] = $data['etiqueta'];
        $afterPost['contato'] = $data['contato'];
        $afterPost['contato_email'] = $data['contato_email'];
        $afterPost['telefone'] = $data['telefone'];
        $afterPost['departamento'] = $data['department'];
        $afterPost['operador'] = $data['operator'];
        $afterPost['status'] = $data['status'];
        $afterPost['cliente'] = $data['client'];

        
        /* Função que grava o registro de alterações do chamado */
        $recordLog = recordLog($conn, $data['numero'], $arrayBeforePost, $afterPost, $data['operation_type']);

        /* Se alguma tag for nova, gravar na tabela de referência: input_tags */
        if (!empty($data['input_tags'])) {
            $arrayTags = explode(',', $data['input_tags']);
            saveNewTags($conn, $arrayTags);
        }
        
    } catch (Exception $e) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_DATA_UPDATE') . "<br />". $sql;
        $_SESSION['flash'] = message('danger', '', $data['message'], '');
        echo json_encode($data);
        return false;
    }
}


if (!empty($data['entry']) || !empty($data['technical_description'])) {

    /* Trata a visibilidade dos assentamentos */
    $queryCleanAssets = "UPDATE assentamentos SET asset_privated = 0 WHERE ocorrencia = " . $data['numero'] . "";
    try {
        $conn->exec($queryCleanAssets);
    } catch (Exception $e) {
        // echo 'Erro: ', $e->getMessage(), "<br/>";
        // $erro = true;
    }
    for ($i = 1; $i <= $post['total_asset']; $i++) {
        if (isset($post['asset' . $i])) {
            $queryUpdateAsset = "UPDATE assentamentos SET asset_privated = 1 WHERE numero = " . $post['asset' . $i] . "";

            try {
                $conn->exec($queryUpdateAsset);
            } catch (Exception $e) {
                // echo 'Erro: ', $e->getMessage(), "<br/>";
                // $erro = true;
            }
        }
    }


    /* Inserção de assentamento com as tags inseridas/removidas do chamado : 
        caso as tags atuais sejam diferentes das que existiam*/
    if ($arrayBeforePost['oco_tag'] != $data['input_tags']) {
        
        $textRemoved = "";
        $textAdded = "";
        $removedTags = tagsRemoved($arrayBeforePost['oco_tag'],$data['input_tags']);
        $addedTags = tagsAdded($arrayBeforePost['oco_tag'],$data['input_tags']);
        
        if (strlen((string)$removedTags))
            $textRemoved = TRANS("REMOVED_TAGS") .": " . strToTags($removedTags, 3, 'danger', '');

        if (strlen((string)$addedTags)) {
            if (strlen((string)$textRemoved)) $textRemoved .= "<br />";
            $textAdded = TRANS("ADDED_TAGS") .": " . strToTags($addedTags, 3);
        }

        $entryTags = $textRemoved . $textAdded;
        
        $sqlTags = "INSERT INTO assentamentos 
        (
            ocorrencia, assentamento, data, responsavel, asset_privated, tipo_assentamento
        )
        VALUES 
        (
            " . $data['numero'] . ", 
            '" . $entryTags . "',
            '" . $now . "', 
            '" . $data['logado'] . "', 
            1, 
            12
        )";

        try {
            $conn->exec($sqlTags);
        } catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage();
        }
    }




    /* action edit */
    if (!empty($data['entry'])) {
        /* Adiciona o assentamento */
        $sqlEntry = "INSERT INTO assentamentos 
        (
            ocorrencia, assentamento, data, responsavel, asset_privated, tipo_assentamento
        )
        VALUES 
        (
            :numero,
            :entry,
            :date,
            :logged,
            :privated,
            1
        )";

        try {
            $res = $conn->prepare($sqlEntry);
            $res->bindParam(':numero', $data['numero'], PDO::PARAM_INT);
            $res->bindParam(':entry', $data['entry'], PDO::PARAM_STR);
            $res->bindParam(':date', $now, PDO::PARAM_STR);
            $res->bindParam(':logged', $data['logado'], PDO::PARAM_INT);
            $res->bindParam(':privated', $data['entry_privated'], PDO::PARAM_INT);
            $res->execute();
        } catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage();
        }

    }

    /* action close */
    if (!empty($data['technical_description'])) {
        /* Adiciona a descrição técnica como assentamento */
        $sqlEntry = "INSERT INTO assentamentos 
        (
            ocorrencia, assentamento, data, responsavel, asset_privated, tipo_assentamento
        )
        VALUES 
        (
            :numero,
            :tech_description,
            :date,
            :logged,
            0,
            4
        )";

        try {
            $res = $conn->prepare($sqlEntry);
            $res->bindParam(':numero', $data['numero'], PDO::PARAM_INT);
            $res->bindParam(':tech_description', $data['technical_description'], PDO::PARAM_STR);
            $res->bindParam(':date', $now, PDO::PARAM_STR);
            $res->bindParam(':logged', $data['logado'], PDO::PARAM_INT);
            $res->execute();
        } catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage();
        }

        $sqlEntry = "INSERT INTO assentamentos 
        (
            ocorrencia, assentamento, data, responsavel, asset_privated, tipo_assentamento
        )
        VALUES 
        (
            :numero,
            :tech_solution,
            :date,
            :logged,
            0,
            5
        )";

        try {
            // $conn->exec($sqlEntry);
            $res = $conn->prepare($sqlEntry);
            $res->bindParam(':numero', $data['numero'], PDO::PARAM_INT);
            $res->bindParam(':tech_solution', $data['technical_solution'], PDO::PARAM_STR);
            $res->bindParam(':date', $now, PDO::PARAM_STR);
            $res->bindParam(':logged', $data['logado'], PDO::PARAM_INT);
            $res->execute();
        } catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage();
        }


        $sqlSolution = "INSERT INTO solucoes 
        (
            numero, problema, solucao, data, responsavel
        ) 
        VALUES 
        (
            :numero,
            :technical_description,
            :technical_solution,
            :date,
            :logged
        )";

        try {
            $res = $conn->prepare($sqlSolution);
            $res->bindParam(':numero', $data['numero'], PDO::PARAM_INT);
            $res->bindParam(':technical_description', $data['technical_description'], PDO::PARAM_STR);
            $res->bindParam(':technical_solution', $data['technical_solution'], PDO::PARAM_STR);
            $res->bindParam(':date', $now, PDO::PARAM_STR);
            $res->bindParam(':logged', $data['logado'], PDO::PARAM_INT);
            $res->execute();
        } catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage();
        }
    }

    if (!empty($data['justificativa'])) {
        
        $sqlJustify = "INSERT INTO assentamentos 
        (
            ocorrencia, assentamento, data, responsavel, asset_privated, tipo_assentamento
        )
        VALUES 
        (
            :numero,
            :justificativa,
            :date,
            :logged,
            0,
            3
        )";

        try {
            $res = $conn->prepare($sqlJustify);
            $res->bindParam(':numero', $data['numero'], PDO::PARAM_INT);
            $res->bindParam(':justificativa', $data['justificativa'], PDO::PARAM_STR);
            $res->bindParam(':date', $now, PDO::PARAM_STR);
            $res->bindParam(':logged', $data['logado'], PDO::PARAM_INT);
            $res->execute();

        } catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage();
        }
    }
}


/* Upload de arquivos - Todos os actions */
foreach ($filesClean as $attach) {
    $fileinput = $attach['tmp_name'];
    $tamanho = getimagesize($fileinput);
    $tamanho2 = filesize($fileinput);

    if (!$tamanho) {
        /* Nâo é imagem */
        unset ($tamanho);
        $tamanho = [];
        $tamanho[0] = "";
        $tamanho[1] = "";
    }

    if (chop($fileinput) != "") {
        // $fileinput should point to a temp file on the server
        // which contains the uploaded file. so we will prepare
        // the file for upload with addslashes and form an sql
        // statement to do the load into the database.
        // $file = addslashes(fread(fopen($fileinput, "r"), 10000000));
        $file = addslashes(fread(fopen($fileinput, "r"), $config['conf_upld_size']));
        $sqlFile = "INSERT INTO imagens (img_nome, img_oco, img_tipo, img_bin, img_largura, img_altura, img_size) values " .
            "('" . noSpace($attach['name']) . "'," . $data['numero'] . ", '" . $attach['type'] . "', " .
            "'" . $file . "', " . dbField($tamanho[0]) . ", " . dbField($tamanho[1]) . ", " . dbField($tamanho2) . ")";
        // now we can delete the temp file
        unlink($fileinput);
    }
    try {
        $exec = $conn->exec($sqlFile);
    }
    catch (Exception $e) {
        $data['message'] = $data['message'] . "<hr>" . TRANS('MSG_ERR_NOT_ATTACH_FILE');
        $exception .= "<hr>" . $e->getMessage();
    }
}
/* Final do upload de arquivos */



//Exclui os anexos marcados - Action edit || close
if ( $data['total_files_to_deal'] > 0 ) {
    for ($j = 1; $j <= $data['total_files_to_deal']; $j++) {
        if (isset($post['delImg'][$j])) {
            $qryDel = "DELETE FROM imagens WHERE img_cod = " . $post['delImg'][$j] . "";

            try {
                $conn->exec($qryDel);
            } catch (Exception $e) {
                // echo 'Erro: ', $e->getMessage(), "<br/>";
                // $erro = true;
                $exception .= "<hr>" . $e->getMessage();
            }
        }
    }
}



$isPai = 0;

if ( $data['total_relatives_to_deal'] > 0 ) {
    /* Checa se um dos vínculos é chamado pai */
    for ($j = 1; $j <= $data['total_relatives_to_deal']; $j++) {

        if (!empty($post['delSub'][$j])) {
            $sql = "SELECT * FROM ocodeps WHERE dep_pai = " . $post['delSub'][$j] . " AND dep_filho = " . $data['numero'] . " ";
            try {
                $result = $conn->query($sql);
            } catch (Exception $e) {
                $exception .= "<hr>" . $e->getMessage();
            }
            $isPai = $result->rowCount();
        }
    }

    /* Remove chamado pai */
    if ($isPai) {
        $rowPai = $result->fetch();
        $qryDel = "DELETE FROM ocodeps WHERE dep_filho = " . $data['numero'] . " and dep_pai = " . $rowPai['dep_pai'] . "";
        try {
            $conn->exec($qryDel);
        }
        catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage();
        }
    }

    // Remove subchamados
    for ($j = 1; $j <= $data['total_relatives_to_deal']; $j++) {
        if (isset($post['delSub'][$j])) {

            $qryDel = "DELETE FROM ocodeps WHERE dep_pai = " . $data['numero'] . " and dep_filho = " . $post['delSub'][$j] . "";
            try {
                $conn->exec($qryDel);

                /* Inserir assentamento no chamado ex-pai */
                $entryMessage = TRANS('TICKET_RELATION_REMOVED') . " " . $post['delSub'][$j];

                /* Gravar assentamento no chamado pai */
                $sqlSubTicket = "INSERT INTO assentamentos 
                (
                    ocorrencia, assentamento, data, responsavel, asset_privated, tipo_assentamento
                )
                VALUES 
                (
                    " . $data['numero'] . ", 
                    '" . $entryMessage . "',
                    '" . $now . "', 
                    '" . $data['logado'] . "', 
                    0,
                    11
                )";

                try {
                    $conn->exec($sqlSubTicket);
                } catch (Exception $e) {
                    $exception .= "<hr>" . $e->getMessage();
                }

                /* Inserir assentamento no chamado ex-filho */
                $entryMessage = TRANS('TICKET_RELATION_REMOVED') . " " . $data['numero'];

                /* Gravar assentamento no chamado filho */
                $sqlSubTicket = "INSERT INTO assentamentos 
                (
                    ocorrencia, assentamento, data, responsavel, asset_privated, tipo_assentamento
                )
                VALUES 
                (
                    " . $post['delSub'][$j] . ", 
                    '" . $entryMessage . "',
                    '" . $now . "', 
                    '" . $data['logado'] . "', 
                    0,
                    11
                )";

                try {
                    $conn->exec($sqlSubTicket);
                } catch (Exception $e) {
                    $exception .= "<hr>" . $e->getMessage();
                }

            } catch (Exception $e) {
                $exception .= "<hr>" . $e->getMessage();
            }
        }
    }
}



/* Variáveis de ambiente para envio de e-mail: todos os actions */
$VARS = getEnvVarsValues($conn, $data['numero']);

$mailSendMethod = 'send';
if ($rowconfmail['mail_queue']) {
    $mailSendMethod = 'queue';
}

/* envio de e-mails */
if ($data['action'] == "open") {

    if (!empty($data['mail_area']) || !$screen['conf_scr_mail']) {
        $event = "abertura-para-area";
        $eventTemplate = getEventMailConfig($conn, $event);

        /* Disparo do e-mail (ou fila no banco) para a área de atendimento */
        $mail = (new Email())->bootstrap(
            transvars($eventTemplate['msg_subject'], $VARS),
            transvars($eventTemplate['msg_body'], $VARS),
            $rowAreaTo['email'],
            $eventTemplate['msg_fromname'],
            $data['numero']
        );

        if (!$mail->{$mailSendMethod}()) {
            $mailNotification .= "<hr>" . TRANS('EMAIL_NOT_SENT') . "<hr>" . $mail->message()->getText();
        }
    }

    
    /**
     * O e-mail para o operador será enviado pelo script de agendamento schedule_ticket.php
     */

    /* Email para o usuario */
    if (!empty($data['mail_usuario']) || $rowLogado['area_atende'] == 0) {
        
        $event = 'abertura-para-usuario';
        $eventTemplate = getEventMailConfig($conn, $event);

        $rowMailUser = getUserInfo($conn, $data['aberto_por']);
        
        $recipient = "";
        if (!empty($data['contato_email'])) {
            $recipient = $data['contato_email'];
        } else {
            $recipient = $rowMailUser['email'];
        }
        

        /* Disparo do e-mail (ou fila no banco) para a área de atendimento */
        $mail = (new Email())->bootstrap(
            transvars($eventTemplate['msg_subject'], $VARS),
            transvars($eventTemplate['msg_body'], $VARS),
            $recipient,
            $eventTemplate['msg_fromname'],
            $data['numero']
        );

        // if (!$mail->queue()) {
        if (!$mail->{$mailSendMethod}()) {
            $mailNotification .= "<hr>" . TRANS('EMAIL_NOT_SENT') . "<hr>" . $mail->message()->getText();
        }
    }




    if (!empty($screen['conf_scr_msg'])) {
        $mensagem = str_replace("%numero%", $data['numero'], $screen['conf_scr_msg']);
    } else
        $mensagem = str_replace("%numero%", $data['numero'], $screenGlobal['conf_scr_msg']);

    $data['message'] = $mensagem;
}

/* envio de e-mails */
if ($data['action'] == "edit") {

    if (!empty($data['mail_area'])) {
        $event = "edita-para-area";
        $eventTemplate = getEventMailConfig($conn, $event);

        /* Disparo do e-mail (ou fila no banco) para a área de atendimento */
        $mail = (new Email())->bootstrap(
            transvars($eventTemplate['msg_subject'], $VARS),
            transvars($eventTemplate['msg_body'], $VARS),
            $rowAreaTo['email'],
            $eventTemplate['msg_fromname'],
            $data['numero']
        );

        // if (!$mail->queue()) {
        if (!$mail->{$mailSendMethod}()) {
            $mailNotification .= "<hr>" . TRANS('EMAIL_NOT_SENT') . "<hr>" . $mail->message()->getText();
            $data['success'] = true;
        }
    }

    if (!empty($data['mail_operador'])) {
        $event = "edita-para-operador";
        $eventTemplate = getEventMailConfig($conn, $event);

        $sqlMailOper = "SELECT nome, email FROM usuarios WHERE user_id ='" . $data['operator'] . "'";
        $execMailOper = $conn->query($sqlMailOper);
        $rowMailOper = $execMailOper->fetch();

        $VARS['%operador%'] = $rowMailOper['nome'];

        /* Disparo do e-mail (ou fila no banco) para a área de atendimento */
        $mail = (new Email())->bootstrap(
            transvars($eventTemplate['msg_subject'], $VARS),
            transvars($eventTemplate['msg_body'], $VARS),
            $rowMailOper['email'],
            $eventTemplate['msg_fromname'],
            $data['numero']
        );

        // if (!$mail->queue()) {
        if (!$mail->{$mailSendMethod}()) {
            $mailNotification .= "<hr>" . TRANS('EMAIL_NOT_SENT') . "<hr>" . $mail->message()->getText();
        }

    }

    if (!empty($data['mail_usuario'])) {
        
        $event = 'edita-para-usuario';
        $eventTemplate = getEventMailConfig($conn, $event);

        $rowMailUser = getUserInfo($conn, $data['aberto_por']);
        
        $recipient = "";
        if (!empty($data['contato_email'])) {
            $recipient = $data['contato_email'];
        } else {
            $recipient = $rowMailUser['email'];
        }
        
        /* Disparo do e-mail (ou fila no banco) para a área de atendimento */
        $mail = (new Email())->bootstrap(
            transvars($eventTemplate['msg_subject'], $VARS),
            transvars($eventTemplate['msg_body'], $VARS),
            $recipient,
            $eventTemplate['msg_fromname'],
            $data['numero']
        );

        // if (!$mail->queue()) {
        if (!$mail->{$mailSendMethod}()) {
            $mailNotification .= "<hr>" . TRANS('EMAIL_NOT_SENT') . "<hr>" . $mail->message()->getText();
        }
    }
    
}

/* envio de e-mails */
if ($data['action'] == "close") {

    /**
     * Ver regras para o envio de email com o link para avaliação:
     * 1: O link deve ir para o solicitante apenas;
     * 2: o solicitante não pode ser o próprio responsável pelo encerramento;
     * 3: Se o chamado foi aberto por email, via api, enviar o email para o endereço de contato;
     * 3.1: Se o chamado foi aberto sem autenticação, enviar o email para o endereço de contato;
     * 4: Será necessário criar um evento específico para a mensagem a ser enviada sobre a avaliação;
     * 5: A variável de ambiente deverá existir apenas no contexto do evento a ser criado e não poderá ser utilizada em nenhum
     * outro evento (mesmo de encerramento)
     * 6: Será necessário enviar uma URL via email, contendo o id do chamado e o rating_id do chamado
     * 7: Quem tiver a url com o id e o rating_id do chamado poderá realizar a avaliação
     */

    $rating_id = $rowGlobalUri['gt_rating_id'];
    $ratingUrl = $VARS['%linkglobal%'] . '&rating_id=' . $rating_id;

    if (!empty($data['mail_area'])) {
        $event = "encerra-para-area";
        $eventTemplate = getEventMailConfig($conn, $event);

        /* Disparo do e-mail (ou fila no banco) para a área de atendimento */
        $mail = (new Email())->bootstrap(
            transvars($eventTemplate['msg_subject'], $VARS),
            transvars($eventTemplate['msg_body'], $VARS),
            $rowAreaTo['email'],
            $eventTemplate['msg_fromname'],
            $data['numero']
        );

        if (!$mail->{$mailSendMethod}()) {
            $mailNotification .= "<hr>" . TRANS('EMAIL_NOT_SENT') . "<hr>" . $mail->message()->getText();
        }
    }


    $rowMailUser = getUserInfo($conn, $data['aberto_por']);

    if (!empty($data['mail_usuario'])) {

        $VARS['%rating_url%'] = TRANS('RATING_NOT_APPLICABLE'); 

        // if (($data['aberto_por'] != $data['logado']) OR (!empty($data['contato_email']) && $rowMailUser['email'] != $data['contato_email'])) {
        if (($needToBeRated) OR (!empty($data['contato_email']) && $rowMailUser['email'] != $data['contato_email'])) {
            /* Essa variável só existirá nesse contexto */
            $VARS['%rating_url%'] = $ratingUrl; 
        }
        
        $event = 'encerra-para-usuario';
		$eventTemplate = getEventMailConfig($conn, $event);

        

        $recipient = "";
        if (!empty($data['contato_email'])) {
            $recipient = $data['contato_email'];
        } else {
            $recipient = $rowMailUser['email'];
        }
        
        /* Disparo do e-mail (ou fila no banco) para o contato */
        $mail = (new Email())->bootstrap(
            transvars($eventTemplate['msg_subject'], $VARS),
            transvars($eventTemplate['msg_body'], $VARS),
            $recipient,
            $eventTemplate['msg_fromname'],
            $data['numero']
        );

        if (!$mail->{$mailSendMethod}()) {
            $mailNotification .= "<hr>" . TRANS('EMAIL_NOT_SENT') . "<hr>" . $mail->message()->getText();
        }
    }

    /** Solicitar avaliacao - sempre será enviado quando o chamado não for concluído pelo próprio solicitante */
    $VARS['%rating_url%'] = TRANS('RATING_NOT_APPLICABLE'); 

    // if (($data['aberto_por'] != $data['logado']) OR (!empty($data['contato_email']) && $rowMailUser['email'] != $data['contato_email'])) {
    if (($needToBeRated) OR (!empty($data['contato_email']) && $rowMailUser['email'] != $data['contato_email'])) {
        /* Essa variável só existirá nesse contexto */
        $VARS['%rating_url%'] = $ratingUrl; 
    
        $event = 'solicita-avaliacao';
        $eventTemplate = getEventMailConfig($conn, $event);
    
        $recipient = "";
        if (!empty($data['contato_email'])) {
            $recipient = $data['contato_email'];
        } else {
            $recipient = $rowMailUser['email'];
        }
        
        /* Disparo do e-mail (ou fila no banco) para o contato */
        $mail = (new Email())->bootstrap(
            transvars($eventTemplate['msg_subject'], $VARS),
            transvars($eventTemplate['msg_body'], $VARS),
            $recipient,
            $eventTemplate['msg_fromname'],
            $data['numero']
        );
    
        if (!$mail->{$mailSendMethod}()) {
            $mailNotification .= "<hr>" . TRANS('EMAIL_NOT_SENT') . "<hr>" . $mail->message()->getText();
        }
    }
    
}


$_SESSION['flash'] = message('success', '', $data['message'] . $exception . $mailNotification, '');
echo json_encode($data);
return true;
