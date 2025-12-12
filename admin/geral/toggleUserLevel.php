<?php
session_start();

require_once __DIR__ . "/" . "../../includes/include_geral_new.inc.php";

if ($_SESSION['s_nivel_real'] == 1) {

    $_SESSION['s_nivel'] = ($_SESSION['s_nivel'] == 1 ? 2 : 1 );
}

$message = ($_SESSION['s_nivel'] == 1 ? TRANS('MSG_ADMIN_LEVEL_NAVIGATION') : TRANS('MSG_OPERATOR_LEVEL_NAVIGATION'));

$_SESSION['flash'] = message('success', '', $message, '');

echo json_encode($_SESSION);
return false;