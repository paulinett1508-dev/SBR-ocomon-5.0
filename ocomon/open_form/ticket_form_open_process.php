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


require_once __DIR__ . "/" . "../../includes/include_basics_only.php";
require_once __DIR__ . "/" . "../../includes/classes/ConnectPDO.php";


use OcomonApi\Support\Email;
use includes\classes\ConnectPDO;

$conn = ConnectPDO::getInstance();



$configExt = getConfigValues($conn);

if (!$configExt['ANON_OPEN_ALLOW']) {
	echo "<script>top.window.location = '../../index.php'</script>";
	exit();
}


$post = $_POST;
$now = date("Y-m-d H:i:s");

$config = getConfig($conn);
$rowconfmail = getMailConfig($conn);

$qry_profile_screen = $QRY["useropencall_custom"];
$qry_profile_screen .= " AND  c.conf_cod = '" . $configExt['ANON_OPEN_SCREEN_PFL'] . "'";
$res_screen = $conn->query($qry_profile_screen);
$screen = $res_screen->fetch(PDO::FETCH_ASSOC);





$sqlProfileScreenGlobal = $QRY["useropencall"];
$resScreenGlobal = $conn->query($sqlProfileScreenGlobal);
$screenGlobal = $resScreenGlobal->fetch(PDO::FETCH_ASSOC);


$recordFile = false;
$erro = false;
$exception = "";
$screenNotification = "";
$mailSent = false;
$mailNotification = "";

$checkCaptchaCase = $configExt['ANON_OPEN_CAPTCHA_CASE'];



$hasFile = false;
$totalFiles = ($_FILES ? count($_FILES['anexo']['name']) : 0);

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




$data = [];
$data['success'] = true;
$data['message'] = "";
$data['cod'] = (isset($post['cod']) ? intval($post['cod']) : "");
$data['numero'] = (isset($post['numero']) ? intval($post['numero']) : "");
$data['action'] = $post['action'];
$data['field_id'] = "";

$data['profile_id'] = $configExt['ANON_OPEN_SCREEN_PFL'];

$data['captcha'] = (isset($post['captcha']) ? noHtml($post['captcha']) : "");

$data['format_bar'] = hasFormatBar($config, '%oco%');
$data['sistema'] = $screen['conf_opentoarea'];
$data['area_destino'] = $screen['conf_opentoarea'];
$data['problema'] = (isset($post['problema']) && !empty($post['problema']) ? noHtml($post['problema']) : "-1");
$data['radio_prob'] = (isset($post['radio_prob']) ? noHtml($post['radio_prob']) : $data['problema']);

$data['descricao'] = (isset($post['descricao']) ? $post['descricao'] : "");
$data['descricao'] = ($data['format_bar'] ? $data['descricao'] : noHtml($data['descricao']));

$data['unidade'] = (isset($post['instituicao']) ? noHtml($post['instituicao']) : "-1");
$data['etiqueta'] = (isset($post['equipamento']) ? noHtml($post['equipamento']) : "");
$data['department'] = (isset($post['local']) && !empty($post['local']) ? noHtml($post['local']) : "-1");

$data['aberto_por'] = $configExt['ANON_OPEN_USER'];

$data['logado'] = $configExt['ANON_OPEN_USER'];

$data['input_tags'] = $configExt['ANON_OPEN_TAGS'];

$data['operator'] = $configExt['ANON_OPEN_USER'];

$data['contato'] = (isset($post['contato']) ? noHtml($post['contato']) : "");
$data['contato_email'] = (isset($post['contato_email']) ? noHtml($post['contato_email']) : "");
$data['telefone'] = (isset($post['telefone']) ? noHtml($post['telefone']) : "");
$data['channel'] = $configExt['ANON_OPEN_CHANNEL'];
$data['prioridade'] = (isset($post['prioridade']) ? intval($post['prioridade']) : getDefaultPriority($conn)['pr_cod']);



/* Data para agendamento */
$data['is_scheduled'] = 0;
$data['schedule_to'] = (isset($post['date_schedule']) ? noHtml($post['date_schedule']) : "");
$data['date_schedule_typed'] = $data['schedule_to'];
if ($data['schedule_to'] != "") {
    $data['schedule_to'] = dateDB($data['schedule_to']);
    $data['is_scheduled'] = 1;
}


$data['global_uri'] = "";


/* Informações sobre a área destino */
$rowAreaTo = getAreaInfo($conn, $data['sistema']);


/* Tratando de acordo com os actions */
if ($data['action'] == "open") {
    $data['status'] = $configExt['ANON_OPEN_STATUS'];

    // if ($data['is_scheduled']) {
    //     $data['status'] =  $config['conf_schedule_status'];
    // }

    $data['aberto_por'] = $configExt['ANON_OPEN_USER'];
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
	$required_fields = getFormRequiredInfo($conn, $configExt['ANON_OPEN_SCREEN_PFL']);

    if ($screen['conf_scr_area'] == '1' && $data['sistema'] == "-1" && (!count($required_fields) || $required_fields['conf_scr_area'])) {
        $data['success'] = false; 
        $data['field_id'] = "idArea";
    } elseif ($screen['conf_scr_prob'] == '1' && $data['problema'] == "-1" && (!count($required_fields) || $required_fields['conf_scr_prob'])) {
        $data['success'] = false; 
        $data['field_id'] = "idProblema";
    } elseif ($screen['conf_scr_desc'] == '1' && $data['descricao'] == "" && (!count($required_fields) || $required_fields['conf_scr_desc'])) {
        $data['success'] = false; 
        $data['field_id'] = "idDescricao";
    } elseif ($screen['conf_scr_unit'] && $data['unidade'] == "-1" && (!count($required_fields) || $required_fields['conf_scr_unit'])) {
        $data['success'] = false; 
        $data['field_id'] = "idUnidade";
    } elseif ($screen['conf_scr_tag'] && $data['etiqueta'] == "" && (!count($required_fields) || $required_fields['conf_scr_tag'])) {
        $data['success'] = false; 
        $data['field_id'] = "idEtiqueta";
    } elseif ($screen['conf_scr_contact'] == '1' && $data['contato'] == "" && (!count($required_fields) || $required_fields['conf_scr_contact'])) {
        $data['success'] = false; 
        $data['field_id'] = "contato";
    } elseif ($screen['conf_scr_contact_email'] == '1' && $data['contato_email'] == "" && (!count($required_fields) || $required_fields['conf_scr_contact_email'])) {
        $data['success'] = false; 
        $data['field_id'] = "contato_email";
    } elseif ($screen['conf_scr_fone'] == '1' && $data['telefone'] == "" && (!count($required_fields) || $required_fields['conf_scr_fone'])) {
        $data['success'] = false; 
        $data['field_id'] = "idTelefone";
    } elseif ($screen['conf_scr_local'] == '1' && $data['department'] == "-1" && (!count($required_fields) || $required_fields['conf_scr_local'])) {
        $data['success'] = false; 
        $data['field_id'] = "idLocal";
    } elseif ($screen['conf_scr_upload'] == '1' && !$hasFile  && (!count($required_fields) || $required_fields['conf_scr_upload'])) {
        $data['success'] = false; 
        $data['field_id'] = "idInputFile";
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


/* Tratar os campos personalizados - todos os actions */
$dataCustom = [];
$fields_ids = [];
$fields_only_edition_ids = [];

if ($screen['conf_scr_custom_ids']) { 
    
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
                
                if (in_array($cfield['id'], $fields_ids) ) {
                    
                    /* Seleção multipla vazia */
                    if (($cfield['field_type'] == 'select_multi') && !isset($post[$cfield['field_name']])) {
                        $post[$cfield['field_name']] = '';
                    }
                    
                    $dataCustom[] = $cfield;
                    
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
                        if ($post[$cfield['field_name']] != "" && !isValidDate($post[$cfield['field_name']], 'd/m/Y')) {
                            $data['success'] = false; 
                            $data['field_id'] = $cfield['field_name'];
                        }
                    } elseif ($cfield['field_type'] == 'datetime') {
                        if ($post[$cfield['field_name']] != "" && !isValidDate($post[$cfield['field_name']], 'd/m/Y H:i')) {
                            $data['success'] = false; 
                            $data['field_id'] = $cfield['field_name'];
                        }
                    } elseif ($cfield['field_type'] == 'time') {
                        if ($post[$cfield['field_name']] != "" && !isValidDate($post[$cfield['field_name']], 'H:i')) {
                            $data['success'] = false; 
                            $data['field_id'] = $cfield['field_name'];
                        }
                    } elseif ($cfield['field_type'] == 'checkbox') {
                        // if ($post[$cfield['field_name']] != "") {
                        //     $data['success'] = false; 
                        //     $data['field_id'] = $cfield['field_name'];
                        // }
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
    catch (Exception $e) {
        $exception .= "<hr>" . $e->getMessage();
    }
}


if (empty($data['captcha'])) {
    $data['success'] = false; 
    $data['field_id'] = "captcha";
    $data['message'] = message('warning', '', TRANS('WRONG_CAPTCHA'), '');
    echo json_encode($data);
    return false;
} 

if ($checkCaptchaCase) {
    if ($data['captcha'] != $_SESSION['captcha']) {
        $data['success'] = false; 
        $data['field_id'] = "captcha";
        $data['message'] = message('warning', '', TRANS('WRONG_CAPTCHA'), '');
        echo json_encode($data);
        return false;
    }
} else {
    if (strtolower($data['captcha']) != strtolower($_SESSION['captcha'])) {
        $data['success'] = false; 
        $data['field_id'] = "captcha";
        $data['message'] = message('warning', '', TRANS('WRONG_CAPTCHA'), '');
        echo json_encode($data);
        return false;
    }
}


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
/* Testa os arquivos enviados para montar os índices do recordFile*/
if ($totalFiles) {

    /* Removendo o indice 'files' que pode existir em alguns casos */
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
            sistema, contato, contato_email, telefone, `local`, 
            operador, data_abertura, data_fechamento, `status`, data_atendimento, 
            aberto_por, oco_scheduled, oco_scheduled_to, 
            oco_real_open_date, date_first_queued, oco_prior, oco_channel, oco_tag, 
            profile_id 
        )
		VALUES 
        (
            " . dbField($configExt['ANON_OPEN_CLIENT']) . ",
            '" . $data['radio_prob'] . "', :descricao, '" . $data['unidade'] . "', '" . $data['etiqueta'] . "',
            '" . $data['sistema'] . "', '" . $data['contato'] . "', '" . $data['contato_email'] . "', '" . $data['telefone'] . "', '" . $data['department'] . "',
            '" . $data['operator'] . "', '{$now}', null, '" . $data['status'] . "', null,
            '" . $data['aberto_por'] . "', '" . $data['is_scheduled'] . "', " . dbField($data['schedule_to'],'date') . ", 
            '{$now}', null, '" . $data['prioridade'] . "', '" . $data['channel'] . "', " . dbField($data['input_tags'], 'text') . ", 
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
                
                // $data[$cfield['field_name']] = noHtml($post[$cfield['field_name']]) ?? '';
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
        $firstLog = firstLog($conn, $data['numero'], 0, 1); 

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


// $data['global_access_uri'] = getGlobalUri($conn, $data['numero']);

/* Variáveis de ambiente para envio de e-mail: todos os actions */
$VARS = getEnvVarsValues($conn, $data['numero']);

$data['global_access_uri'] = $VARS['%url%'];



$mailSendMethod = 'send';
if ($rowconfmail['mail_queue']) {
    $mailSendMethod = 'queue';
}

/* envio de e-mails */
if ($data['action'] == "open") {

    /* E-mail para a área de atendimento */
    $event = "abertura-para-area";
    $eventTemplate = getEventMailConfig($conn, $event);


    $addTicketUri = TRANS('URI_TO_VIEW_TICKET') . ": " . $data['global_access_uri'];


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


    if (!empty($data['contato_email'])) {
        
        $event = 'abertura-para-usuario';
        $eventTemplate = getEventMailConfig($conn, $event);

        $rowMailUser = getUserInfo($conn, $data['aberto_por']);
        
        $recipient = $data['contato_email'];

        /* Disparo do e-mail (ou fila no banco) para endereço de contato */
        $mail = (new Email())->bootstrap(
            transvars($eventTemplate['msg_subject'], $VARS),
            transvars($eventTemplate['msg_body'], $VARS) . "<br/>" . $addTicketUri,
            $recipient,
            $eventTemplate['msg_fromname'],
            $data['numero']
        );

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




$_SESSION['flash'] = message('success', '', $data['message'] . $exception . $mailNotification, '');
echo json_encode($data);
return false;
