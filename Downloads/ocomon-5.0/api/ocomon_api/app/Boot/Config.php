<?php

require_once __DIR__ . "/" . "../../vendor/coffeecode/datalayer/src/Connect.php";
require_once __DIR__ . "/" . "../../vendor/coffeecode/datalayer/src/CrudTrait.php";
require_once __DIR__ . "/" . "../../vendor/coffeecode/datalayer/src/DataLayer.php";
require_once __DIR__ . "/" . "../Models/Config.php";
require_once __DIR__ . "/" . "../Models/MailConfig.php";


// use CoffeeCode\DataLayer\DataLayer;
use OcomonApi\Models\Config;
use OcomonApi\Models\MailConfig;

$config = (new Config())->findById(1);

$apiAddress = $config->data()->conf_ocomon_site . "/api/ocomon_api/";

$mailConfig = (new MailConfig())->findById(1);

/**
 * PROJECT URLs
 */
define("CONF_URL_BASE", $apiAddress);
define("CONF_URL_TEST", $apiAddress);

/**
 * UPLOAD
 */
define("CONF_UPLOAD_DIR", "storage");


/**
 * PASSWORD - HASH
*/
define("CONF_PASSWD_ALGO", PASSWORD_DEFAULT);
define("CONF_PASSWD_OPTION", ["cost => 10"]);


/* E-mail SMTP*/
define("CONF_MAIL_SEND", $mailConfig->mail_send);
define("CONF_MAIL_HOST", $mailConfig->mail_host);
define("CONF_MAIL_PORT", $mailConfig->mail_port);
define("CONF_MAIL_USER", $mailConfig->mail_user);
define("CONF_MAIL_PASS", $mailConfig->mail_pass);
define("CONF_MAIL_SENDER", ["name" => $mailConfig->mail_from_name, "address" => $mailConfig->mail_from]);
define("CONF_MAIL_SUPPORT", $mailConfig->mail_from);

define("CONF_MAIL_OPTION_LANG", "br");
define("CONF_MAIL_OPTION_HTML", $mailConfig->mail_ishtml);
define("CONF_MAIL_OPTION_AUTH", $mailConfig->mail_isauth);
define("CONF_MAIL_OPTION_SECURE", $mailConfig->mail_secure);
define("CONF_MAIL_OPTION_CHARSET", "utf-8");