<?php

require __DIR__ . "/" . "../../../includes/config.inc.php";
require __DIR__ . "/" . "../../../includes/functions/functions.php";
require __DIR__ . "/" . "../../../includes/functions/dbFunctions.php";
require __DIR__ . "/" . "../../../includes/classes/ConnectPDO.php";

use includes\classes\ConnectPDO;
$conn = ConnectPDO::getInstance();


$configGlobal = getConfig($conn);
$apiAddress = $configGlobal['conf_ocomon_site'] . "/api/ocomon_api";
/* Configurações estendidas */
$configs = getConfigValues($conn);


/* GET E-MAILS AND OPEN TICKETS */
define('ALLOW_OPEN_TICKET_BY_EMAIL', $configs['ALLOW_OPEN_TICKET_BY_EMAIL']);
define('MAIL_GET_ADDRESS', $configs['MAIL_GET_ADDRESS']);
define('MAIL_GET_IMAP_ADDRESS', $configs['MAIL_GET_IMAP_ADDRESS']);
define('MAIL_GET_PORT', $configs['MAIL_GET_PORT']);
define('MAIL_GET_PASSWORD', getConfigValue($conn, 'MAIL_GET_PASSWORD'));
define('MAIL_GET_CERT', $configs['MAIL_GET_CERT']);

define('MAIL_GET_MAILBOX', $configs['MAIL_GET_MAILBOX']);
define('MAIL_GET_MARK_SEEN', $configs['MAIL_GET_MARK_SEEN']);
define('MAIL_GET_MOVETO', $configs['MAIL_GET_MOVETO']);
define('MAIL_GET_SUBJECT_CONTAINS', $configs['MAIL_GET_SUBJECT_CONTAINS']);
define('MAIL_GET_BODY_CONTAINS', $configs['MAIL_GET_BODY_CONTAINS']);
define('MAIL_GET_DAYS_SINCE', $configs['MAIL_GET_DAYS_SINCE']);


/* API CONNECTION */
// define('API_OCOMON_ADDRESS', $configs['API_OCOMON_ADDRESS']);
define('API_OCOMON_ADDRESS', $apiAddress);
define('API_USERNAME', $configs['API_TICKET_BY_MAIL_USER']);
define('API_APP', $configs['API_TICKET_BY_MAIL_APP']);
define('API_TOKEN', getConfigValue($conn, 'API_TICKET_BY_MAIL_TOKEN'));
define('API_TICKET_BY_MAIL_CHANNEL', $configs['API_TICKET_BY_MAIL_CHANNEL']);
define('API_TICKET_BY_MAIL_CLIENT', $configs['API_TICKET_BY_MAIL_CLIENT']);
define('API_TICKET_BY_MAIL_AREA', $configs['API_TICKET_BY_MAIL_AREA']);
define('API_TICKET_BY_MAIL_STATUS', $configs['API_TICKET_BY_MAIL_STATUS']);
define('API_TICKET_BY_MAIL_TAG', $configs['API_TICKET_BY_MAIL_TAG']);

