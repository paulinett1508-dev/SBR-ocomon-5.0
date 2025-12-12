<?php
/* Copyright 2023 Flávio Ribeiro

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
 */ session_start();

if (!isset($_SESSION['s_logado']) || $_SESSION['s_logado'] == 0) {
    $_SESSION['session_expired'] = 1;
    echo "<script>top.window.location = '../../index.php'</script>";
    exit;
}

require_once __DIR__ . "/" . "../../includes/include_geral_new.inc.php";
require_once __DIR__ . "/" . "../../includes/classes/ConnectPDO.php";

use includes\classes\ConnectPDO;

$conn = ConnectPDO::getInstance();

$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 1);

$_SESSION['s_page_admin'] = $_SERVER['PHP_SELF'];

/* Configurações estendidas */
$configs = getConfigValues($conn);

/* Configuraçoes básicas */
$basicConfig = getConfig($conn);
if (!$basicConfig['conf_updated_issues']) {
	redirect('update_issues_areas.php');
	exit;
}


$statusList = getStatus($conn, 0, '1,2');
$panels = [
	'1' => TRANS('PANEL_UPPER'),
	'2' => TRANS('PANEL_MAIN'),
	'3' => TRANS('HIDDEN_PANEL'),
];
$timeFreeze = [
	'0' => TRANS('HAS_NOT_TIME_FREEZE'),
	'1' => TRANS('HAS_TIME_FREEZE')
];


/**
 * Chamados abertos por e-mail
*/

/* Nome de usuário para a abertura de chamados automáticos */
if ($configs['API_TICKET_BY_MAIL_USER']){
    $userToOpenTickets = getUserInfo($conn, 1, $configs['API_TICKET_BY_MAIL_USER'])['login'];
} else {
    $userToOpenTickets = "admin";
}

/* Canal da Solicitação para chamados abertos por e-mail*/
$channelToOpenTickets = getChannels($conn, $configs['API_TICKET_BY_MAIL_CHANNEL'])['name'];
/* Cliente de entrada para chamados abertos por e-mail */
$clientToOpenTickets = TRANS('FILL_EMPTY');
if (isset($configs['API_TICKET_BY_MAIL_CLIENT']) && $configs['API_TICKET_BY_MAIL_CLIENT'])
    $clientToOpenTickets = getClients($conn, $configs['API_TICKET_BY_MAIL_CLIENT'])['nickname'];
/* Área de entrada para chamados abertos por e-mail */
$areaToOpenTickets = "";
if ($configs['API_TICKET_BY_MAIL_AREA'])
    $areaToOpenTickets = getAreaInfo($conn, $configs['API_TICKET_BY_MAIL_AREA'])['area_name'];
/* Status de entrada para chamados abertos por e-mail */
$statusToOpenTickets = "";
if ($configs['API_TICKET_BY_MAIL_STATUS'])
    $statusToOpenTickets = getStatusById($conn, $configs['API_TICKET_BY_MAIL_STATUS'])['status'];

/**
 * Chamados abertos sem autenticação
 */

/* Nome de usuário para a abertura de chamados sem autenticação */
$userToOpenBlindTickets = "";
if ($configs['ANON_OPEN_USER']){
    $userToOpenBlindTickets = getUserInfo($conn, $configs['ANON_OPEN_USER'])['login'];
}

/* Cliente para a abertura de chamados sem autenticação */
$clientToOpenBlindTickets['nickname'] = TRANS('FILL_EMPTY');
$clientToOpenBlindTickets['id'] = "";
if (isset($configs['ANON_OPEN_CLIENT']) && $configs['ANON_OPEN_CLIENT']){
    $clientToOpenBlindTickets = getClients($conn, $configs['ANON_OPEN_CLIENT']);
}

/* Perfil de tela de abertura de chamados sem autenticação */
$screenProfileBlindTickets = "";
if ($configs['ANON_OPEN_SCREEN_PFL']){
    $screenProfileBlindTickets = getScreenInfo($conn, $configs['ANON_OPEN_SCREEN_PFL'])['conf_name'];
}
/* Status de entrada para chamados abertos sem autenticação*/
$statusToOpenBlindTickets = "";
if ($configs['ANON_OPEN_STATUS'])
    $statusToOpenBlindTickets = getStatusById($conn, $configs['ANON_OPEN_STATUS'])['status'];

/* Canal da Solicitação para chamados abertos sem autenticação*/
$channelToOpenBlindTickets = "";
if ($configs['ANON_OPEN_CHANNEL'])
    $channelToOpenBlindTickets = getChannels($conn, $configs['ANON_OPEN_CHANNEL'])['name'];

$ldapClientToNewUsers = TRANS('FILL_EMPTY');
if (isset($configs['LDAP_CLIENT_TO_BIND_NEWUSERS']) && $configs['LDAP_CLIENT_TO_BIND_NEWUSERS'])
    $ldapClientToNewUsers = getClients($conn, $configs['LDAP_CLIENT_TO_BIND_NEWUSERS'])['nickname'];

$ldapAreaToNewUsers = "";
if (isset($configs['LDAP_AREA_TO_BIND_NEWUSERS']) && $configs['LDAP_AREA_TO_BIND_NEWUSERS'])
    $ldapAreaToNewUsers = getAreaInfo($conn, $configs['LDAP_AREA_TO_BIND_NEWUSERS'])['area_name'];

    

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../../includes/css/estilos.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/css/my_datatables.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/css/switch_radio.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap/custom.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/fontawesome/css/all.min.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/jquery/jquery.amsify.suggestags-master/css/amsify.suggestags.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap-select/dist/css/bootstrap-select.min.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/css/my_bootstrap_select.css" />

    <style>
        hr.thick {
            border: 1px solid;
            color: #CCCCCC !important;
            /* border-radius: 5px; */
        }
    </style>

    <title>OcoMon&nbsp;<?= VERSAO; ?></title>
</head>

<body>
    
    <div class="container">
        <div id="idLoad" class="loading" style="display:none"></div>
    </div>

    <div id="divResult"></div>


    <div class="container-fluid bg-light">
        <h4 class="my-4"><i class="fas fa-cogs text-secondary"></i>&nbsp;<?= TRANS('CONFIG_PLUS'); ?></h4>
        
        <div class="modal" id="modal" tabindex="-1" style="z-index:9001!important">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div id="divDetails">
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modalLdap" tabindex="-1" style="z-index:2001!important" role="dialog" aria-labelledby="myModalLdap" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div id="divResultLdap"></div>
                    <div class="modal-header text-center bg-light">

                        <h4 class="modal-title w-100 font-weight-bold text-secondary"><i class="fas fa-link"></i>&nbsp;<?= TRANS('LDAP_CONNECTION_TEST'); ?></h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    
                    <div class="row p-3">
                        <div class="col">
                            <p><?= TRANS('LDAP_TYPE_USERNAME_AND_PASS'); ?></p>
                        </div>
                    </div>

                    <div class="row mx-2">
                        <div class="form-group col-md-12">
                            <input type="text" class="form-control " id="ldap_user" name="ldap_user" placeholder="<?= TRANS('COL_LOGIN'); ?>" value="" autocomplete="off" />
                        </div>
                        <div class="form-group col-md-12">
                            <input type="password" class="form-control " id="ldap_password" name="ldap_password" placeholder="<?= TRANS('PASSWORD'); ?>" value="" autocomplete="off" />
                        </div>
                    </div>

                    <div class="modal-footer d-flex justify-content-end bg-light">
                        <button id="confirmLdapTest" class="btn "><?= TRANS('BT_LDAP_TEST'); ?></button>
                        <button id="cancelLdapTest" class="btn btn-secondary" data-dismiss="modal" aria-label="Close"><?= TRANS('BT_CANCEL'); ?></button>
                    </div>
                </div>
            </div>
        </div>


        <div class="modal fade" id="modalAlertLdap" tabindex="-1" style="z-index:2001!important" role="dialog" aria-labelledby="myModalAlertLdap" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div id="divResultAlertLdap"></div>
                    <div class="modal-header text-center bg-secondary">

                        <h4 class="modal-title w-100 font-weight-bold text-white"><i class="fas fa-exclamation-circle"></i>&nbsp;<?= TRANS('TXT_IMPORTANT'); ?></h4>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    
                    <div class="row p-3 mt-3">
                        <div class="col">
                            <p><?= TRANS('ALERT_BF_SET_TO_LDAP'); ?></p>
                        </div>
                    </div>

                    <div class="modal-footer d-flex justify-content-end bg-light">
                        <button id="closeMessage" class="btn btn-secondary" data-dismiss="modal" aria-label="Close"><?= TRANS('BT_CLOSE'); ?></button>
                    </div>
                </div>
            </div>
        </div>

        <?php
        if (isset($_SESSION['flash']) && !empty($_SESSION['flash'])) {
            echo $_SESSION['flash'];
            $_SESSION['flash'] = '';
        }

        $registros = 1;
        /* Configuraçoes para envio de e-mails */
        $config = getMailConfig($conn);


        /* Classes para o grid */
        $colLabel = "col-sm-3 text-md-right font-weight-bold p-2 mb-4";
        $colsDefault = "small text-break border-bottom rounded p-2 bg-white"; /* border-secondary */
        $colContent = $colsDefault . " col-sm-9 col-md-9 ";
        $colContentLine = $colsDefault . " col-sm-9";
        /* Duas colunas */
        $colLabel2 = "col-sm-3 text-md-right font-weight-bold p-2 mb-4";
        $colContent2 = $colsDefault . " col-sm-3 col-md-3";

        if ((!isset($_GET['action'])) && !isset($_POST['submit'])) {

        ?>
            <button class="btn btn-sm btn-primary bt-edit" id="idBtEdit" name="edit"><?= TRANS("BT_EDIT"); ?></button><br />
            <?php
            if ($registros == 0) {
                echo message('info', '', TRANS('NO_RECORDS_FOUND'), '', '', true);
            } else {
            ?>
                
                <!-- local base or LDAP -->
                <h5 class="w-100 mt-4 "><i class="fas fa-database text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('CONFIG_AUTHENTICATION_BASE')); ?></h5>
                <p class="border-bottom text-secondary font-weight-bold mt-5 ml-2"><?= TRANS('CONFIG_LDAP'); ?></p>
                <div class="row my-2">
                    <div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('ENABLE_LDAP')); ?></div>
                    <div class="<?= $colContent; ?>">
                        <?php
                        $yesChecked = (isset($configs['AUTH_TYPE']) && $configs['AUTH_TYPE'] == 'LDAP' ? "checked" : "");
                        $noChecked =  (!isset($configs['AUTH_TYPE']) || $configs['AUTH_TYPE'] != 'LDAP' ? "checked" : "");
                        ?>
                        <div class="switch-field">
                            <input type="radio" id="auth_type_ldap" name="auth_type_ldap" value="yes" <?= $yesChecked; ?> disabled />
                            <label for="auth_type_ldap"><?= TRANS('YES'); ?></label>
                            <input type="radio" id="auth_type_ldap_no" name="auth_type_ldap" value="no" <?= $noChecked; ?> disabled />
                            <label for="auth_type_ldap_no"><?= TRANS('NOT'); ?></label>
                        </div>
                    </div>
                    <div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('LDAP_HOST')); ?></div>
                    <div class="<?= $colContent; ?>"><?= $configs['LDAP_HOST'] ?? ''; ?></div>
                    <div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('LDAP_PORT')); ?></div>
                    <div class="<?= $colContent; ?>"><?= $configs['LDAP_PORT'] ?? 389; ?></div>
                    <div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('LDAP_DOMAIN')); ?></div>
                    <div class="<?= $colContent; ?>"><?= $configs['LDAP_DOMAIN'] ?? ''; ?></div>
                    <div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('LDAP_BASEDN')); ?></div>
                    <div class="<?= $colContent; ?>"><?= $configs['LDAP_BASEDN'] ?? ''; ?></div>
                    <div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('LDAP_FIELD_FULLNAME')); ?></div>
                    <div class="<?= $colContent; ?>"><?= $configs['LDAP_FIELD_FULLNAME'] ?? 'name'; ?></div>
                    <div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('LDAP_FIELD_EMAIL')); ?></div>
                    <div class="<?= $colContent; ?>"><?= $configs['LDAP_FIELD_EMAIL'] ?? 'mail'; ?></div>
                    <div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('LDAP_FIELD_PHONE')); ?></div>
                    <div class="<?= $colContent; ?>"><?= $configs['LDAP_FIELD_PHONE'] ?? 'telephonenumber'; ?></div>
                    <div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('LDAP_CLIENT_TO_BIND_NEWUSERS')); ?></div>
                    <div class="<?= $colContent; ?>"><?= $ldapClientToNewUsers; ?></div>
                    <div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('LDAP_AREA_TO_BIND_NEWUSERS')); ?></div>
                    <div class="<?= $colContent; ?>"><?= $ldapAreaToNewUsers; ?></div>
                
                </div>
                
                <h5 class="w-100 mt-4 "><i class="fas fa-magic text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('AUTO_OPEN_TICKET_BY_EMAIL')); ?></h5>
                <p class="border-bottom text-secondary font-weight-bold mt-5 ml-2"><?= TRANS('CONNECTION'); ?></p>
                <div class="row my-2">
                    <div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('ALLOW_AUTO_OPEN_TICKET_BY_EMAIL')); ?></div>
                    <div class="<?= $colContent; ?>">
                        <?php
                        $yesChecked = ($configs['ALLOW_OPEN_TICKET_BY_EMAIL'] == 1 ? "checked" : "");
                        $noChecked = ($configs['ALLOW_OPEN_TICKET_BY_EMAIL'] == 0 ? "checked" : "");
                        ?>
                        <div class="switch-field">
                            <input type="radio" id="allow_open_by_email" name="allow_open_by_email" value="yes" <?= $yesChecked; ?> disabled />
                            <label for="allow_open_by_email"><?= TRANS('YES'); ?></label>
                            <input type="radio" id="allow_open_by_email_no" name="allow_open_by_email" value="no" <?= $noChecked; ?> disabled />
                            <label for="allow_open_by_email_no"><?= TRANS('NOT'); ?></label>
                        </div>
                    </div>
                    <div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('MAIL_ADDRESS_TO_FETCH')); ?></div>
                    <div class="<?= $colContent; ?>"><?= $configs['MAIL_GET_ADDRESS']; ?></div>
                    <div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('IMAP_ADDRESS_TO_FETCH')); ?></div>
                    <div class="<?= $colContent; ?>"><?= $configs['MAIL_GET_IMAP_ADDRESS']; ?></div>
                    <div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('PORT_TO_FETCH')); ?></div>
                    <div class="<?= $colContent; ?>"><?= $configs['MAIL_GET_PORT']; ?></div>
                    <div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('HAS_SSL_CERT')); ?></div>
                    <div class="<?= $colContent; ?>">
                        <?php
                        $yesChecked = ($configs['MAIL_GET_CERT'] == 1 ? "checked" : "");
                        $noChecked = ($configs['MAIL_GET_CERT'] == 0 ? "checked" : "");
                        ?>
                        <div class="switch-field">
                            <input type="radio" id="mail_cert" name="mail_cert" value="yes" <?= $yesChecked; ?> disabled />
                            <label for="mail_cert"><?= TRANS('YES'); ?></label>
                            <input type="radio" id="mail_cert_no" name="mail_cert" value="no" <?= $noChecked; ?> disabled />
                            <label for="mail_cert_no"><?= TRANS('NOT'); ?></label>
                        </div>
                    </div>
                </div>

                <p class="border-bottom text-secondary font-weight-bold mt-5 ml-2"><?= TRANS('MESSAGE_TREATMENT'); ?></p>
                <div class="row my-2">
                    <div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('MAILBOX_TO_FETCH')); ?></div>
                    <div class="<?= $colContent; ?>"><?= $configs['MAIL_GET_MAILBOX']; ?></div>
                    <div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('SUBJECT_FILTER_CONTAINS')); ?></div>
                    <div class="<?= $colContent; ?>"><?= $configs['MAIL_GET_SUBJECT_CONTAINS']; ?></div>
                    <div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('BODY_FILTER_CONTAINS')); ?></div>
                    <div class="<?= $colContent; ?>"><?= $configs['MAIL_GET_BODY_CONTAINS']; ?></div>
                    <div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('DAYS_SINCE_TO_FETCH')); ?></div>
                    <div class="<?= $colContent; ?>"><?= $configs['MAIL_GET_DAYS_SINCE']; ?></div>
                    <div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('MARK_FETCHED_AS_SEEN')); ?></div>
                    <div class="<?= $colContent; ?>">
                        <?php
                        $yesChecked = ($configs['MAIL_GET_MARK_SEEN'] == 1 ? "checked" : "");
                        $noChecked = ($configs['MAIL_GET_MARK_SEEN'] == 0 ? "checked" : "");
                        ?>
                        <div class="switch-field">
                            <input type="radio" id="mark_seen" name="mark_seen" value="yes" <?= $yesChecked; ?> disabled />
                            <label for="mark_seen"><?= TRANS('YES'); ?></label>
                            <input type="radio" id="mark_seen_no" name="mark_seen" value="no" <?= $noChecked; ?> disabled />
                            <label for="mark_seen_no"><?= TRANS('NOT'); ?></label>
                        </div>
                    </div>
                    <div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('MAILBOX_TO_MOVE_TO')); ?></div>
                    <div class="<?= $colContent; ?>"><?= $configs['MAIL_GET_MOVETO']; ?></div>
                </div>

                <p class="border-bottom text-secondary font-weight-bold mt-5 ml-2"><?= TRANS('TICKETS_TREATMENT'); ?></p>
                <div class="row my-2">
                    <div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('SYSTEM_USER_TO_OPEN_TICKETS')); ?></div>
                    <div class="<?= $colContent; ?>"><?= $userToOpenTickets; ?></div>
                    <div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('CLIENT')); ?></div>
                    <div class="<?= $colContent; ?>"><?= $clientToOpenTickets; ?></div>
                    <div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('SERVICE_AREA')); ?></div>
                    <div class="<?= $colContent; ?>"><?= $areaToOpenTickets; ?></div>
                    <div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('COL_STATUS')); ?></div>
                    <div class="<?= $colContent; ?>"><?= $statusToOpenTickets; ?></div>
                    <div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('INPUT_TAGS')); ?></div>
                    <div class="<?= $colContent; ?>"><?= strToTags($configs['API_TICKET_BY_MAIL_TAG']); ?></div>
                    <div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('OPENING_CHANNEL')); ?></div>
                    <div class="<?= $colContent; ?>"><?= $channelToOpenTickets; ?></div>
                </div>


                <h5 class="w-100 mt-5 mb-4"><i class="fas fa-ghost text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('UNREGISTERED_OPENING')); ?></h5>
                <div class="row my-2">
                    <div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('ALLOW_AUTO_UNREGISTERED_OPENING')); ?></div>
                    <div class="<?= $colContent; ?>">
                        <?php
                        $yesChecked = ($configs['ANON_OPEN_ALLOW'] == 1 ? "checked" : "");
                        $noChecked = (!$configs['ANON_OPEN_ALLOW'] == 1 ? "checked" : "");
                        ?>
                        <div class="switch-field">
                            <input type="radio" id="allow_unregister_open" name="allow_unregister_open" value="yes" <?= $yesChecked; ?> disabled />
                            <label for="allow_unregister_open"><?= TRANS('YES'); ?></label>
                            <input type="radio" id="allow_unregister_open_no" name="allow_unregister_open" value="no" <?= $noChecked; ?> disabled />
                            <label for="allow_unregister_open_no"><?= TRANS('NOT'); ?></label>
                        </div>
                    </div>
                    <div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('SYSTEM_USER_TO_OPEN_BLIND_TICKETS')); ?></div>
                    <div class="<?= $colContent; ?>"><?= $userToOpenBlindTickets; ?></div>
                    <div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('CLIENT')); ?></div>
                    <div class="<?= $colContent; ?>"><?= $clientToOpenBlindTickets['nickname']; ?></div>
                    <div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('SCREEN_NAME')); ?></div>
                    <div class="<?= $colContent; ?>"><?= $screenProfileBlindTickets; ?></div>
                    <div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('COL_STATUS')); ?></div>
                    <div class="<?= $colContent; ?>"><?= $statusToOpenBlindTickets; ?></div>
                    <div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('OPENING_CHANNEL')); ?></div>
                    <div class="<?= $colContent; ?>"><?= $channelToOpenBlindTickets; ?></div>
                    <div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('INPUT_TAGS')); ?></div>
                    <div class="<?= $colContent; ?>"><?= strToTags($configs['ANON_OPEN_TAGS']); ?></div>
                    <div class="<?= $colLabel; ?>"><?= firstLetterUp(TRANS('CAPTCHA_CASE_SENSITIVE')); ?></div>
                    <div class="<?= $colContent; ?>">
                        <?php
                        $yesChecked = ($configs['ANON_OPEN_CAPTCHA_CASE'] == 1 ? "checked" : "");
                        $noChecked = (!$configs['ANON_OPEN_CAPTCHA_CASE'] == 1 ? "checked" : "");
                        ?>
                        <div class="switch-field">
                            <input type="radio" id="captcha_case" name="captcha_case" value="yes" <?= $yesChecked; ?> disabled />
                            <label for="captcha_case"><?= TRANS('YES'); ?></label>
                            <input type="radio" id="captcha_case_no" name="captcha_case" value="no" <?= $noChecked; ?> disabled />
                            <label for="captcha_case_no"><?= TRANS('NOT'); ?></label>
                        </div>
                    </div>
                </div>
                
                
                <div class="row w-100">
                    <div class="col-md-10 d-none d-md-block">
                    </div>
                    <div class="col-12 col-md-2 ">
                        <button class="btn btn-primary bt-edit " name="edit"><?= TRANS("BT_EDIT"); ?></button>
                    </div>
                </div>

            <?php
            }
        } else
		if ((isset($_GET['action'])  && ($_GET['action'] == "edit")) && !isset($_POST['submit'])) {

            ?>
            <form method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form">
                <?= csrf_input(); ?>

                <?= alertRequiredModule('curl'); ?>
                <?= alertRequiredModule('imap'); ?>
                <?= alertRequiredModule('gd'); ?>
                <?= alertRequiredModule('ldap'); ?>

                
                
                <!-- Seção para configuração do LDAP -->
                <div class="form-group row mt-2 mb-4">

                    <h6 class="w-100 mt-5 ml-4 mb-4"><i class="fas fa-database text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('CONFIG_AUTHENTICATION_BASE')); ?></h6>

                    <div class="form-group col-md-12 mt-4">
                        <p class="border-bottom text-secondary font-weight-bold ml-4"><?= TRANS('CONFIG_LDAP'); ?></p>
                    </div>
                    <div class="w-100"></div>

                    <label class="col-md-3 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_ENABLE_LDAP'); ?>"><?= firstLetterUp(TRANS('ENABLE_LDAP')); ?></label>
                    <div class="form-group col-md-9">

                        <div class="switch-field">
                            <?php
                            $yesChecked = (isset($configs['AUTH_TYPE']) && $configs['AUTH_TYPE'] == 'LDAP' ? "checked" : "");
                            $noChecked =  (!isset($configs['AUTH_TYPE']) || $configs['AUTH_TYPE'] != 'LDAP' ? "checked" : "");
                            ?>
                            <input type="radio" id="auth_type_ldap" name="auth_type_ldap" value="yes" <?= $yesChecked; ?> />
                            <label for="auth_type_ldap"><?= TRANS('YES'); ?></label>
                            <input type="radio" id="auth_type_ldap_no" name="auth_type_ldap" value="no" <?= $noChecked; ?> />
                            <label for="auth_type_ldap_no"><?= TRANS('NOT'); ?></label>
                        </div>
                    </div>
                    <label for="ldap_host" class="col-md-3 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_LDAP_HOST'); ?>"><?= firstLetterUp(TRANS('LDAP_HOST')); ?></label>
                    <div class="form-group col-md-9">
                        <input type="text" class="form-control" name="ldap_host" id="ldap_host" value="<?= $configs['LDAP_HOST'] ?? ''; ?>" placeholder="<?= TRANS('LDAP_HOST'); ?>" />
                    </div>
                    <label for="ldap_port" class="col-md-3 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_LDAP_PORT'); ?>"><?= firstLetterUp(TRANS('LDAP_PORT')); ?></label>
                    <div class="form-group col-md-9">
                        <input type="number" class="form-control" name="ldap_port" id="ldap_port" value="<?= $configs['LDAP_PORT'] ?? 389; ?>" placeholder="<?= TRANS('LDAP_PORT'); ?>" />
                    </div>
                    <label for="ldap_domain" class="col-md-3 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_LDAP_DOMAIN'); ?>"><?= firstLetterUp(TRANS('LDAP_DOMAIN')); ?></label>
                    <div class="form-group col-md-9">
                        <input type="text" class="form-control" name="ldap_domain" id="ldap_domain" value="<?= $configs['LDAP_DOMAIN'] ?? ''; ?>" placeholder="<?= TRANS('LDAP_DOMAIN'); ?>" />
                    </div>
                    <label for="ldap_basedn" class="col-md-3 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_LDAP_BASEDN'); ?>"><?= firstLetterUp(TRANS('LDAP_BASEDN')); ?></label>
                    <div class="form-group col-md-9">
                        <input type="text" class="form-control" name="ldap_basedn" id="ldap_basedn" value="<?= $configs['LDAP_BASEDN'] ?? ''; ?>" placeholder="<?= TRANS('LDAP_BASEDN'); ?>" />
                    </div>

                    <label for="ldap_field_fullname" class="col-md-3 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_LDAP_FIELD_FULLNAME'); ?>"><?= firstLetterUp(TRANS('LDAP_FIELD_FULLNAME')); ?></label>
                    <div class="form-group col-md-9">
                        <input type="text" class="form-control" name="ldap_field_fullname" id="ldap_field_fullname" value="<?= $configs['LDAP_FIELD_FULLNAME'] ?? 'name'; ?>" placeholder="<?= TRANS('LDAP_FIELD_FULLNAME'); ?>" />
                    </div>
                    <label for="ldap_field_email" class="col-md-3 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_LDAP_FIELD_EMAIL'); ?>"><?= firstLetterUp(TRANS('LDAP_FIELD_EMAIL')); ?></label>
                    <div class="form-group col-md-9">
                        <input type="text" class="form-control" name="ldap_field_email" id="ldap_field_email" value="<?= $configs['LDAP_FIELD_EMAIL'] ?? 'mail'; ?>" placeholder="<?= TRANS('LDAP_FIELD_EMAIL'); ?>" />
                    </div>
                    <label for="ldap_field_phone" class="col-md-3 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_LDAP_FIELD_PHONE'); ?>"><?= firstLetterUp(TRANS('LDAP_FIELD_PHONE')); ?></label>
                    <div class="form-group col-md-9">
                        <input type="text" class="form-control" name="ldap_field_phone" id="ldap_field_phone" value="<?= $configs['LDAP_FIELD_PHONE'] ?? 'telephonenumber'; ?>" placeholder="<?= TRANS('LDAP_FIELD_PHONE'); ?>" />
                    </div>

                    <label for="ldap_client" class="col-md-3 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_LDAP_CLIENT_TO_BIND_NEWUSERS'); ?>"><?= firstLetterUp(TRANS('LDAP_CLIENT_TO_BIND_NEWUSERS')); ?></label>
                    <div class="form-group col-md-9">

                        <SELECT class="form-control bs-select" name="ldap_client" id="ldap_client" >
                            <option value=""><?= TRANS('FILL_EMPTY'); ?></option>
                        <?php
                            $clients = getClients($conn, null, 2);
                            foreach ($clients as $client) {
                                ?>
                                <option value="<?= $client['id']; ?>"
                                    <?= (isset($configs['LDAP_CLIENT_TO_BIND_NEWUSERS']) && $client['id'] == $configs['LDAP_CLIENT_TO_BIND_NEWUSERS'] ? " selected" : ""); ?>>
                                    <?= $client['nickname']; ?>
                                </option>
                                <?php
                            }
                        ?>
                        </SELECT>
                    </div>


                    <label for="ldap_area" class="col-md-3 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_LDAP_AREA_TO_BIND_NEWUSERS'); ?>"><?= firstLetterUp(TRANS('LDAP_AREA_TO_BIND_NEWUSERS')); ?></label>
                    <div class="form-group col-md-9">

                        <SELECT class="form-control bs-select" name="ldap_area" id="ldap_area" >
                            <option value=""><?= TRANS('SEL_SELECT'); ?></option>
                        <?php
                            $areas = getAreas($conn, 0, 1, 0);
                            foreach ($areas as $area) {
                                ?>
                                <option value="<?= $area['sis_id']; ?>"
                                    <?= (isset($configs['LDAP_AREA_TO_BIND_NEWUSERS']) && $area['sis_id'] == $configs['LDAP_AREA_TO_BIND_NEWUSERS'] ? " selected" : ""); ?>>
                                    <?= $area['sistema']; ?>
                                </option>
                                <?php
                            }
                        ?>
                        </SELECT>
                    </div>

                    <label class="col-md-3 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('BT_LDAP_TEST'); ?>"><?= firstLetterUp(TRANS('BT_LDAP_TEST')); ?></label>
                    <div class="form-group col-md-9">
                        <input type="button" class="btn btn-success" name="testLdap" id="testLdap" value="<?= TRANS('BT_LDAP_TEST'); ?>" disabled>
                    </div>
                    
                    <div class="w-100"></div>


                </div>
                
                <!-- Seção para abertura de chamados por e-mail -->
                <div class="form-group row mt-2 mb-4">

                    <h6 class="w-100 mt-5 ml-4 mb-4"><i class="fas fa-magic text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('AUTO_OPEN_TICKET_BY_EMAIL')); ?></h6>
                    <?= message('info', TRANS('TXT_IMPORTANT'), '<hr>' . TRANS('HELPER_TASK_SCHEDULER'), '', '', true); ?>

                    <div class="form-group col-md-12 mt-4">
                        <p class="border-bottom text-secondary font-weight-bold ml-4"><?= TRANS('CONNECTION'); ?></p>
                    </div>
                    <div class="w-100"></div>

                    <label class="col-md-3 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('ALLOW_AUTO_OPEN_TICKET_BY_EMAIL'); ?>"><?= firstLetterUp(TRANS('ALLOW_AUTO_OPEN_TICKET_BY_EMAIL')); ?></label>
                    <div class="form-group col-md-9">

                        <div class="switch-field">
                            <?php
                            $yesChecked = ($configs['ALLOW_OPEN_TICKET_BY_EMAIL'] == 1 ? "checked" : "");
                            $noChecked = ($configs['ALLOW_OPEN_TICKET_BY_EMAIL'] == 0 ? "checked" : "");
                            ?>
                            <input type="radio" id="allow_open_by_email" name="allow_open_by_email" value="yes" <?= $yesChecked; ?> />
                            <label for="allow_open_by_email"><?= TRANS('YES'); ?></label>
                            <input type="radio" id="allow_open_by_email_no" name="allow_open_by_email" value="no" <?= $noChecked; ?> />
                            <label for="allow_open_by_email_no"><?= TRANS('NOT'); ?></label>
                        </div>
                    </div>

                    <label for="mail_account" class="col-md-3 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_MAIL_ADDRESS_TO_FETCH'); ?>"><?= firstLetterUp(TRANS('MAIL_ADDRESS_TO_FETCH')); ?></label>
                    <div class="form-group col-md-3">
                        <input type="text" class="form-control" name="mail_account" id="mail_account" required value="<?= $configs['MAIL_GET_ADDRESS']; ?>" placeholder="<?= TRANS('MAIL_ADDRESS_TO_FETCH'); ?>" />
                    </div>
                    <label for="account_password" class="col-md-3 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('MAIL_PASS_TO_FETCH'); ?>"><?= firstLetterUp(TRANS('MAIL_PASS_TO_FETCH')); ?></label>
                    <div class="form-group col-md-3">
                        <input type="password" class="form-control" name="account_password" id="account_password" value="" placeholder="<?= TRANS('PASSWORD_EDIT_PLACEHOLDER'); ?>" />
                    </div>

                    <label for="imap_address" class="col-md-3 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('IMAP_ADDRESS_TO_FETCH'); ?>"><?= firstLetterUp(TRANS('IMAP_ADDRESS_TO_FETCH')); ?></label>
                    <div class="form-group col-md-9">
                        <input type="text" class="form-control" name="imap_address" id="imap_address" required value="<?= $configs['MAIL_GET_IMAP_ADDRESS']; ?>" placeholder="<?= TRANS('IMAP_ADDRESS_TO_FETCH'); ?>" />
                    </div>


                    <label for="mail_port" class="col-md-3 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('PORT_TO_FETCH'); ?>"><?= firstLetterUp(TRANS('PORT_TO_FETCH')); ?></label>
                    <div class="form-group col-md-9">
                        <input type="text" class="form-control" name="mail_port" id="mail_port" required value="<?= $configs['MAIL_GET_PORT']; ?>" placeholder="<?= TRANS('PORT_TO_FETCH'); ?>" />
                    </div>

                    <label class="col-md-3 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_HAS_SSL_CERT'); ?>"><?= firstLetterUp(TRANS('HAS_SSL_CERT')); ?></label>
                    <div class="form-group col-md-9">

                        <div class="switch-field">
                            <?php
                            $yesChecked = ($configs['MAIL_GET_CERT'] == 1 ? "checked" : "");
                            $noChecked = ($configs['MAIL_GET_CERT'] == 0 ? "checked" : "");
                            ?>
                            <input type="radio" id="ssl_cert" name="ssl_cert" value="yes" <?= $yesChecked; ?> />
                            <label for="ssl_cert"><?= TRANS('YES'); ?></label>
                            <input type="radio" id="ssl_cert_no" name="ssl_cert" value="no" <?= $noChecked; ?> />
                            <label for="ssl_cert_no"><?= TRANS('NOT'); ?></label>
                        </div>
                    </div>


                    <label class="col-md-3 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('TEST_CONNECTION'); ?>"><?= firstLetterUp(TRANS('TEST_CONNECTION')); ?></label>
                    <div class="form-group col-md-9">
                        <input type="button" class="btn btn-success" name="testConnection" id="testConnection" value="<?= TRANS('TEST_CONNECTION'); ?>">
                    </div>
                    
                    <div class="w-100"></div>


                    <div class="form-group col-md-12 mt-4">
                        <p class="border-bottom text-secondary font-weight-bold ml-4"><?= TRANS('MESSAGE_TREATMENT'); ?></p>
                    </div>
                    <div class="w-100"></div>

                    <label for="mailbox" class="col-md-3 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_MAILBOX_TO_FETCH'); ?>"><?= firstLetterUp(TRANS('MAILBOX_TO_FETCH')); ?></label>
                    <div class="form-group col-md-9">
                        <input type="text" class="form-control" name="mailbox" id="mailbox" required value="<?= $configs['MAIL_GET_MAILBOX']; ?>" placeholder="<?= TRANS('MAILBOX_TO_FETCH'); ?>" />
                    </div>
                    <label for="subject_has" class="col-md-3 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_SUBJECT_FILTER_CONTAINS'); ?>"><?= firstLetterUp(TRANS('SUBJECT_FILTER_CONTAINS')); ?></label>
                    <div class="form-group col-md-9">
                        <input type="text" class="form-control" name="subject_has" id="subject_has" value="<?= $configs['MAIL_GET_SUBJECT_CONTAINS']; ?>" placeholder="<?= TRANS('SUBJECT_FILTER_CONTAINS'); ?>" />
                    </div>
                    <label for="body_has" class="col-md-3 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_BODY_FILTER_CONTAINS'); ?>"><?= firstLetterUp(TRANS('BODY_FILTER_CONTAINS')); ?></label>
                    <div class="form-group col-md-9">
                        <input type="text" class="form-control" name="body_has" id="body_has" value="<?= $configs['MAIL_GET_BODY_CONTAINS']; ?>" placeholder="<?= TRANS('BODY_FILTER_CONTAINS'); ?>" />
                    </div>

                    <label for="days_since" class="col-md-3 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_DAYS_SINCE_TO_FETCH'); ?>"><?= firstLetterUp(TRANS('DAYS_SINCE_TO_FETCH')); ?></label>
                    <div class="form-group col-md-9">
                        <input type="number" class="form-control" name="days_since" id="days_since" min="1" max="5" required value="<?= $configs['MAIL_GET_DAYS_SINCE']; ?>" placeholder="<?= TRANS('DAYS_SINCE_TO_FETCH'); ?>" />
                    </div>

                    <label class="col-md-3 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_MARK_FETCHED_AS_SEEN'); ?>"><?= firstLetterUp(TRANS('MARK_FETCHED_AS_SEEN')); ?></label>
                    <div class="form-group col-md-9">

                        <div class="switch-field">
                            <?php
                            $yesChecked = ($configs['MAIL_GET_MARK_SEEN'] == 1 ? "checked" : "");
                            $noChecked = ($configs['MAIL_GET_MARK_SEEN'] == 0 ? "checked" : "");
                            ?>
                            <input type="radio" id="mark_seen" name="mark_seen" value="yes" <?= $yesChecked; ?> />
                            <label for="mark_seen"><?= TRANS('YES'); ?></label>
                            <input type="radio" id="mark_seen_no" name="mark_seen" value="no" <?= $noChecked; ?> />
                            <label for="mark_seen_no"><?= TRANS('NOT'); ?></label>
                        </div>
                    </div>

                    <label for="move_to" class="col-md-3 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_MAILBOX_TO_MOVE_TO'); ?>"><?= firstLetterUp(TRANS('MAILBOX_TO_MOVE_TO')); ?></label>
                    <div class="form-group col-md-9">
                        <input type="text" class="form-control" name="move_to" id="move_to" required value="<?= $configs['MAIL_GET_MOVETO']; ?>" placeholder="<?= TRANS('MAILBOX_TO_MOVE_TO'); ?>" />
                    </div>


                    <div class="form-group col-md-12 mt-4">
                        <p class="border-bottom text-secondary font-weight-bold ml-4"><?= TRANS('TICKETS_TREATMENT'); ?></p>
                    </div>
                    <div class="w-100"></div>
                    <label for="system_user" class="col-md-3 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_SYSTEM_USER_TO_OPEN_TICKETS'); ?>"><?= firstLetterUp(TRANS('SYSTEM_USER_TO_OPEN_TICKETS')); ?></label>
                    <div class="form-group col-md-9">

                        <SELECT class="form-control bs-select" name="system_user" id="system_user" required>
                        <?php
                            $users = getUsers($conn, null, [1,2]);
                            foreach ($users as $user) {
                                ?>
                                <option value="<?= $user['login']; ?>"
                                    <?= ($user['login'] == $userToOpenTickets ? " selected" : ""); ?>
                                >
                                    <?= $user['login']; ?>
                                </option>

                                <?php
                            }
                        ?>
                        </SELECT>
                    </div>

                    <label for="clientFromEmail" class="col-md-3 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_AUTO_TICKETING_CLIENT'); ?>"><?= firstLetterUp(TRANS('CLIENT')); ?></label>
                    <div class="form-group col-md-9">

                        <SELECT class="form-control bs-select" name="clientFromEmail" id="clientFromEmail" >
                            <option value=""><?= TRANS('FILL_EMPTY'); ?></option>
                        <?php
                            $clients = getClients($conn, null, 2);
                            foreach ($clients as $client) {
                                ?>
                                <option value="<?= $client['id']; ?>"
                                    <?= (isset($configs['API_TICKET_BY_MAIL_CLIENT']) && $client['id'] == $configs['API_TICKET_BY_MAIL_CLIENT'] ? " selected" : ""); ?>
                                >
                                    <?= $client['nickname']; ?>
                                </option>

                                <?php
                            }
                        ?>
                        </SELECT>
                    </div>

                    <label for="area" class="col-md-3 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_AUTO_TICKETING_AREA'); ?>"><?= firstLetterUp(TRANS('SERVICE_AREA')); ?></label>
                    <div class="form-group col-md-9">

                        <SELECT class="form-control bs-select" name="area" id="area" required>
                            <option value=""><?= TRANS('SEL_SELECT'); ?></option>
                        <?php
                            $areas = getAreas($conn, 0, 1, 1);
                            foreach ($areas as $area) {
                                ?>
                                <option value="<?= $area['sis_id']; ?>"
                                    <?= ($area['sis_id'] == $configs['API_TICKET_BY_MAIL_AREA'] ? " selected" : ""); ?>
                                >
                                    <?= $area['sistema']; ?>
                                </option>

                                <?php
                            }
                        ?>
                        </SELECT>
                    </div>

                    <label for="status" class="col-md-3 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_AUTO_TICKETING_STATUS'); ?>"><?= firstLetterUp(TRANS('COL_STATUS')); ?></label>
                    <div class="form-group col-md-9">

                        <SELECT class="form-control bs-select" name="status" id="status" required>
                        <?php
                            // $statusList = getStatus($conn);
							$subtext = "";
                            foreach ($statusList as $status) {
								$subtext = $panels[$status['stat_painel']] . "&nbsp;|&nbsp;" . $timeFreeze[$status['stat_time_freeze']];
                                ?>
                                <option data-subtext="<?= $subtext; ?>" value="<?= $status['stat_id']; ?>"
                                    <?= ($status['stat_id'] == $configs['API_TICKET_BY_MAIL_STATUS'] ? " selected" : ""); ?>
                                >
                                    <?= $status['status']; ?>
                                </option>

                                <?php
                            }
                        ?>
                        </SELECT>
                    </div>


                    <label for="input_tags" class="col-md-3 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_AUTOMATIC_INPUT_TAGS'); ?>"><?= firstLetterUp(TRANS('INPUT_TAGS')); ?></label>
                    <div class="form-group col-md-9">
                        <input type="text" class="form-control" name="input_tags" id="input_tags" value="<?= $configs['API_TICKET_BY_MAIL_TAG']; ?>" placeholder="<?= TRANS('ADD_OR_REMOVE_INPUT_TAGS'); ?>" />
                        <div class="invalid-feedback">
							<?= TRANS('ERROR_MIN_SIZE_OF_TAGNAME'); ?>
						</div>
                    </div>


                    <label for="opening_channel" class="col-md-3 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('OPENING_CHANNEL'); ?>"><?= firstLetterUp(TRANS('OPENING_CHANNEL')); ?></label>
                    <div class="form-group col-md-9">

                        <SELECT class="form-control bs-select" name="opening_channel" id="opening_channel" required>
                        <?php
                            $channels = getChannels($conn, null, 'restrict');
                            foreach ($channels as $channel) {
                                ?>
                                <option value="<?= $channel['id']; ?>"
                                    <?= ($channel['id'] == $configs['API_TICKET_BY_MAIL_CHANNEL'] ? " selected" : ""); ?>
                                >
                                    <?= $channel['name']; ?>
                                </option>

                                <?php
                            }
                        ?>
                        </SELECT>
                    </div>

                </div>

                
                <!-- Seção para chamados sem autenticação -->
                <div class="form-group row mt-2 mb-4">

                    <!-- Abertura de chamados sem autenticação de usuário -->
                    <h6 class="w-100 mt-5 ml-4 mb-4" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_AUTO_UNREGISTERED_OPENING'); ?>"><i class="fas fa-ghost text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('UNREGISTERED_OPENING')); ?></h6>

                    <label class="col-md-3 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('ALLOW_AUTO_UNREGISTERED_OPENING'); ?>"><?= firstLetterUp(TRANS('ALLOW_AUTO_UNREGISTERED_OPENING')); ?></label>
                    <div class="form-group col-md-9">

                        <div class="switch-field">
                            <?php
                            $yesChecked = ($configs['ANON_OPEN_ALLOW'] == 1 ? "checked" : "");
                            $noChecked = (!$configs['ANON_OPEN_ALLOW'] == 1 ? "checked" : "");
                            ?>
                            <input type="radio" id="allow_unregister_open" name="allow_unregister_open" value="yes" <?= $yesChecked; ?> />
                            <label for="allow_unregister_open"><?= TRANS('YES'); ?></label>
                            <input type="radio" id="allow_unregister_open_no" name="allow_unregister_open" value="no" <?= $noChecked; ?> />
                            <label for="allow_unregister_open_no"><?= TRANS('NOT'); ?></label>
                        </div>
                    </div>

                    <!-- Usuário para abertura de chamados sem autenticação -->
                    <label for="user_blind_tickets" class="col-md-3 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('SYSTEM_USER_TO_OPEN_BLIND_TICKETS'); ?>"><?= firstLetterUp(TRANS('SYSTEM_USER_TO_OPEN_BLIND_TICKETS')); ?></label>
                    <div class="form-group col-md-9">

                        <SELECT class="form-control bs-select" name="user_blind_tickets" id="user_blind_tickets" required>
                            <option value=""><?= TRANS('SEL_SELECT'); ?></option>
                        <?php
                            $users = getUsers($conn, null, [1,2,3]);
                            foreach ($users as $user) {
                                ?>
                                <option value="<?= $user['user_id']; ?>"
                                    <?= ($user['login'] == $userToOpenBlindTickets ? " selected" : ""); ?>
                                >
                                    <?= $user['login']; ?>
                                </option>

                                <?php
                            }
                        ?>
                        </SELECT>
                    </div>

                    <!-- Cliente para abertura de chamados sem autenticação -->
                    <label for="client_blind_tickets" class="col-md-3 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('CLIENT'); ?>"><?= firstLetterUp(TRANS('CLIENT')); ?></label>
                    <div class="form-group col-md-9">

                        <SELECT class="form-control bs-select" name="client_blind_tickets" id="client_blind_tickets" required>
                            <option value=""><?= TRANS('FILL_EMPTY'); ?></option>
                        <?php
                            $clients = getClients($conn, null, 2);
                            foreach ($clients as $client) {
                                ?>
                                <option value="<?= $client['id']; ?>"
                                    <?= ($client['id'] == $clientToOpenBlindTickets['id'] ? " selected" : ""); ?>
                                >
                                    <?= $client['nickname']; ?>
                                </option>

                                <?php
                            }
                        ?>
                        </SELECT>
                    </div>

                    <!-- Perfil de tela de abertura para o formulário de abertura de chamados sem autenticação -->
                    <label for="screen_profile_blind_tickets" class="col-md-3 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('SCREEN_NAME'); ?>"><?= firstLetterUp(TRANS('SCREEN_NAME')); ?></label>
                    <div class="form-group col-md-9">

                        <SELECT class="form-control bs-select" name="screen_profile_blind_tickets" id="screen_profile_blind_tickets">
                            <option value=""><?= TRANS('SEL_SELECT'); ?></option>
                        <?php
                            $profiles = getScreenProfiles($conn);
                            foreach ($profiles as $profile) {
                                ?>
                                <option value="<?= $profile['conf_cod']; ?>"
                                    <?= ($profile['conf_name'] == $screenProfileBlindTickets ? " selected" : ""); ?>
                                >
                                    <?= $profile['conf_name']; ?>
                                </option>

                                <?php
                            }
                        ?>
                        </SELECT>
                    </div>


                    <!-- Status para chamados abertos sem usuário autenticado -->
                    <label for="blind_status" class="col-md-3 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('COL_STATUS'); ?>"><?= firstLetterUp(TRANS('COL_STATUS')); ?></label>
                    <div class="form-group col-md-9">

                        <SELECT class="form-control bs-select" name="blind_status" id="blind_status" required>
                        <?php
                            // $statusList = getStatus($conn, 0, '1,2');
                            $subtext = "";
                            foreach ($statusList as $status) {
								$subtext = $panels[$status['stat_painel']] . "&nbsp;|&nbsp;" . $timeFreeze[$status['stat_time_freeze']];
                                ?>
                                <option data-subtext="<?= $subtext; ?>" value="<?= $status['stat_id']; ?>"
                                    <?= ($status['stat_id'] == $configs['ANON_OPEN_STATUS'] ? " selected" : ""); ?>
                                >
                                    <?= $status['status']; ?>
                                </option>

                                <?php
                            }
                        ?>
                        </SELECT>
                    </div>

                    <label for="blind_channel" class="col-md-3 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('OPENING_CHANNEL'); ?>"><?= firstLetterUp(TRANS('OPENING_CHANNEL')); ?></label>
                    <div class="form-group col-md-9">

                        <SELECT class="form-control bs-select" name="blind_channel" id="blind_channel">
                            <option value=""><?= TRANS('SEL_SELECT'); ?></option>
                        <?php
                            $channels = getChannels($conn, null);
                            foreach ($channels as $channel) {
                                ?>
                                <option value="<?= $channel['id']; ?>"
                                    <?= ($channel['id'] == $configs['ANON_OPEN_CHANNEL'] ? " selected" : ""); ?>
                                >
                                    <?= $channel['name']; ?>
                                </option>

                                <?php
                            }
                        ?>
                        </SELECT>
                    </div>

                    <label for="blind_tags" class="col-md-3 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_AUTOMATIC_INPUT_TAGS'); ?>"><?= firstLetterUp(TRANS('INPUT_TAGS')); ?></label>
                    <div class="form-group col-md-9">
                        <input type="text" class="form-control" name="blind_tags" id="blind_tags" value="<?= $configs['ANON_OPEN_TAGS']; ?>" placeholder="<?= TRANS('ADD_OR_REMOVE_INPUT_TAGS'); ?>" />
                        <div class="invalid-feedback">
							<?= TRANS('ERROR_MIN_SIZE_OF_TAGNAME'); ?>
						</div>
                    </div>

                    <label class="col-md-3 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('CAPTCHA_CASE_SENSITIVE'); ?>"><?= firstLetterUp(TRANS('CAPTCHA_CASE_SENSITIVE')); ?></label>
                    <div class="form-group col-md-9">

                        <div class="switch-field">
                            <?php
                            $yesChecked = ($configs['ANON_OPEN_CAPTCHA_CASE'] == 1 ? "checked" : "");
                            $noChecked = (!$configs['ANON_OPEN_CAPTCHA_CASE'] == 1 ? "checked" : "");
                            ?>
                            <input type="radio" id="captcha_case" name="captcha_case" value="yes" <?= $yesChecked; ?> />
                            <label for="captcha_case"><?= TRANS('YES'); ?></label>
                            <input type="radio" id="captcha_case_no" name="captcha_case" value="no" <?= $noChecked; ?> />
                            <label for="captcha_case_no"><?= TRANS('NOT'); ?></label>
                        </div>
                    </div>



                    <!-- ---------------------------------------- -->
                    <div class="row w-100"></div>
                    <div class="form-group col-md-8 d-none d-md-block">
                    </div>
                    <div class="form-group col-12 col-md-2 ">

                        <input type="hidden" name="action" id="action" value="edit">
                        <button type="submit" id="idSubmit" name="submit" class="btn btn-primary btn-block"><?= TRANS('BT_OK'); ?></button>
                    </div>
                    <div class="form-group col-12 col-md-2">
                        <button type="reset" class="btn btn-secondary btn-block" onClick="parent.history.back();"><?= TRANS('BT_CANCEL'); ?></button>
                    </div>


                </div>
            </div>
            </form>
            <?php
        }
    ?>
</div>

<script src="../../includes/javascript/funcoes-3.0.js"></script>
<script src="../../includes/components/jquery/jquery.js"></script>
<!-- <script src="../../includes/components/bootstrap/js/bootstrap.min.js"></script> -->
<script src="../../includes/components/bootstrap/js/bootstrap.bundle.js"></script>
<script src="../../includes/components/jquery/jquery.amsify.suggestags-master/js/jquery.amsify.suggestags.js"></script>
<script src="../../includes/components/bootstrap-select/dist/js/bootstrap-select.min.js"></script>

<script type="text/javascript">
    $(function() {

        $(function() {
            $('[data-toggle="popover"]').popover()
        });

        $('.popover-dismiss').popover({
            trigger: 'focus'
        });

        $.fn.selectpicker.Constructor.BootstrapVersion = '4';
        $('.bs-select').selectpicker({
            /* placeholder */
            title: "<?= TRANS('SEL_SELECT', '', 1); ?>",
            liveSearch: true,
            showSubtext: true,
            // actionsBox: true,
            liveSearchNormalize: true,
            liveSearchPlaceholder: "<?= TRANS('BT_SEARCH', '', 1); ?>",
            noneResultsText: "<?= TRANS('NO_RECORDS_FOUND', '', 1); ?> {0}",
            style: "",
            styleBase: "form-control input-select-multi",
        });


        enableBtLdapTest();
        $('input').on('change', function(){
            enableBtLdapTest();
        });

        $('#testLdap').on('click', function(e){
            e.preventDefault();
            $('#modalLdap').modal();
            $('#confirmLdapTest').html('<a class="btn btn-primary" onclick="doLdapTest()"><?= TRANS('BT_LDAP_TEST'); ?></a>');
        });

        $('#modalLdap').on('hidden.bs.modal', function(){
            $('#divResultLdap').empty();
            $('#ldap_user').val('');
            $('#ldap_password').val('');
        });


        $('#auth_type_ldap').on('change', function(){
            if($(this).val() == 'yes'){
               $('#modalAlertLdap').modal();
            }
        });


        $('#testConnection').on('click', function(e) {
            e.preventDefault();
            var loading = $(".loading");
            $(document).ajaxStart(function() {
                loading.show();
            });
            $(document).ajaxStop(function() {
                loading.hide();
            });
            $("#testConnection").prop("disabled", true);
            $("#idSubmit").prop("disabled", true);
            $("#testConnection").val('<?= TRANS('WAIT'); ?>');

            $.ajax({
                url: './test_imap_connection.php',
                method: 'POST',
                data: $('#form').serialize(),
                dataType: 'json',
            }).done(function(response) {

                if (!response.success) {
                    $('#divResult').html(response.message);
                    $('input, select, textarea').removeClass('is-invalid');
                    if (response.field_id != "") {
                        $('#' + response.field_id).focus().addClass('is-invalid');
                    }
                    $("#testConnection").prop("disabled", false);
                    $("#idSubmit").prop("disabled", false);
                    $("#testConnection").val('<?= TRANS('TEST_CONNECTION'); ?>');
                } else {
                    $('#divResult').html(response.message);
                    $('input, select, textarea').removeClass('is-invalid');
                    $("#testConnection").prop("disabled", false);
                    $("#idSubmit").prop("disabled", false);
                    $("#testConnection").val('<?= TRANS('TEST_CONNECTION'); ?>');
                    return false;
                }
            });
            return false;
        });


        $('#idSubmit').on('click', function(e) {
            e.preventDefault();
            var loading = $(".loading");
            $(document).ajaxStart(function() {
                loading.show();
            });
            $(document).ajaxStop(function() {
                loading.hide();
            });
            $("#idSubmit").prop("disabled", true);
            $("#testConnection").prop("disabled", true);
            $.ajax({
                url: './config_plus_process.php',
                method: 'POST',
                data: $('#form').serialize(),
                dataType: 'json',
            }).done(function(response) {

                if (!response.success) {
                    $('#divResult').html(response.message);
                    $('input, select, textarea').removeClass('is-invalid');
                    if (response.field_id != "") {
                        $('#' + response.field_id).focus().addClass('is-invalid');
                    }
                    $("#idSubmit").prop("disabled", false);
                    $("#testConnection").prop("disabled", false);
                } else {
                    $('#divResult').html('');
                    $('input, select, textarea').removeClass('is-invalid');
                    $("#idSubmit").prop("disabled", false);
                    $("#testConnection").prop("disabled", false);
                    var url = '<?= $_SERVER['PHP_SELF'] ?>';
                    $(location).prop('href', url);
                    return false;
                }
            });
            return false;
        });

        $('.bt-edit').on("click", function() {
            $('#idLoad').css('display', 'block');
            var url = '<?= $_SERVER['PHP_SELF'] ?>?action=edit';
            $(location).prop('href', url);
        });

        $('#bt-cancel').on('click', function() {
            var url = '<?= $_SERVER['PHP_SELF'] ?>';
            $(location).prop('href', url);
        });

        $('input[name="input_tags"], input[name="blind_tags"]').amsifySuggestags({
            type : 'bootstrap',
            defaultTagClass: 'badge bg-primary text-white p-2',
            tagLimit: 20,
            printValues: false,
            showPlusAfter: 10,
            
            suggestionsAction : {
                
                timeout: 5,
                minChars: 2,
                minChange: -1,
                delay: 100,
                type: 'POST',
                url : '../../ocomon/geral/tag_suggestions.php',
                beforeSend : function() {
                    // console.info('beforeSend');
                },
                success: function(data) {
                    // console.info(data);
                },
                error: function() {
                    // console.info('error');
                },
                complete: function(data) {
                    // console.info('complete');
                }
            }
        });


    });

    
    function enableBtLdapTest () {
        if ($('#ldap_host').val() != '' && 
            $('#ldap_domain').val() != '' && 
            $('#ldap_basedn').val() != '' && 
            $('#ldap_field_fullname').val() != '' && 
            $('#ldap_field_email').val() != '' && 
            $('#ldap_field_phone').val() != ''
        ) {
            $('#testLdap').prop('disabled', false);
        } else {
            $('#testLdap').prop('disabled', true);
        }
    }
    
    function doLdapTest() {
        var loading = $(".loading");
        $(document).ajaxStart(function() {
            loading.show();
        });
        $(document).ajaxStop(function() {
            loading.hide();
        });
        $("#confirmLdapTest").prop("disabled", true);
        $("#idSubmit").prop("disabled", true);
        $("#confirmLdapTest").val('<?= TRANS('WAIT'); ?>');

        $.ajax({
            url: './test_ldap_settings.php',
            method: 'POST',
            data: $('#form').serialize()+'&ldap_user='+$('#ldap_user').val()+'&ldap_password='+$('#ldap_password').val(),
            dataType: 'json',
        }).done(function(response) {

            if (!response.success) {
                $('#divResultLdap').html(response.message);
                $('input, select, textarea').removeClass('is-invalid');
                if (response.field_id != "") {
                    $('#' + response.field_id).focus().addClass('is-invalid');
                }
                $("#confirmLdapTest").prop("disabled", false);
                $("#idSubmit").prop("disabled", false);
                $("#confirmLdapTest").val('<?= TRANS('BT_LDAP_TEST'); ?>');
            } else {
                $('#divResultLdap').html(response.message);
                $('input, select, textarea').removeClass('is-invalid');
                $("#confirmLdapTest").prop("disabled", false);
                $("#idSubmit").prop("disabled", false);
                $("#confirmLdapTest").val('<?= TRANS('BT_LDAP_TEST'); ?>');
                return false;
            }
        });
        return false;
    };
</script>
</body>

</html>