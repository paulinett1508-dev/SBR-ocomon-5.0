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


$config = getConfig($conn);
$configExt = getConfigValues($conn);

$isAdmin = $_SESSION['s_nivel'] == 1;
$files = array();
$files = getDirFileNames('../../includes/languages/');

if (!defined('ALLOWED_LANGUAGES')) {
    $langLabels = [
        'pt_BR.php' => TRANS('LANG_PT_BR'),
        'en.php' => TRANS('LANG_EN'),
        'es_ES.php' => TRANS('LANG_ES_ES')
    ];
} else {
    $langLabels = ALLOWED_LANGUAGES;
}

array_multisort($langLabels, SORT_LOCALE_STRING);


$sqlUserLang = "SELECT upref_lang FROM uprefs WHERE upref_uid = " . $_SESSION['s_uid'] . "";
$execUserLang = $conn->query($sqlUserLang);
$rowUL = $execUserLang->fetch();
$hasUL = $execUserLang->rowcount();

$areaAdmin = 0;
$user_id = "";
// $localAuth = AUTH_TYPE == "SYSTEM";
$localAuth = (isset($configExt['AUTH_TYPE']) && $configExt['AUTH_TYPE'] == 'LDAP' ? false : true); 


if (isset($_GET['action']) && $_GET['action'] == 'profile') {
    $auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 3);
    $user_id = $_SESSION['s_uid'];
    $_SESSION['s_page_admin'] = $_SERVER['PHP_SELF'];
} else {
    if (isset($_SESSION['s_area_admin']) && $_SESSION['s_area_admin'] == '1' && $_SESSION['s_nivel'] != '1') {
        $areaAdmin = 1;
    }

    if ($areaAdmin) {
        $auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 3);
    } else {
        $auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 1);

        if (!$config['conf_updated_issues']) {
            redirect('update_issues_areas.php');
            exit;
        }
    }

    $_SESSION['s_page_admin'] = $_SERVER['PHP_SELF'];
}


?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../../includes/css/estilos.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/css/my_datatables.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/css/switch_radio.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/jquery/datetimepicker/jquery.datetimepicker.css" />

    <link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap/custom.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/fontawesome/css/all.min.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/datatables/datatables.min.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap-select/dist/css/bootstrap-select.min.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/css/my_bootstrap_select.css" />

    <style>
        
        li.area_admins {
			line-height: 1.5em;
		}

		td.admins {
			min-width: 15%;
		}
        .container-switch {
			position: relative;
		}

		.switch-next-checkbox {
			position: absolute;
			top: 0;
			left: 140px;
			z-index: 1;
		}
        
        .chart-container {
            position: relative;
            /* height: 100%; */
            max-width: 100%;
            margin-left: 10px;
            margin-right: 10px;
            margin-bottom: 30px;
        }
    </style>

    <title>OcoMon&nbsp;<?= VERSAO; ?></title>
</head>

<body>


    <div class="container">
        <div id="idLoad" class="loading" style="display:none"></div>
    </div>

    <div id="divResult"></div>


    <div class="container-fluid">
        <h5 class="my-4"><i class="fas fa-user-friends text-secondary"></i>&nbsp;<?= TRANS('MNL_USUARIOS'); ?></h5>
        <div class="modal" id="modal" tabindex="-1" style="z-index:9001!important">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div id="divDetails">
                    </div>
                </div>
            </div>
        </div>

        <?php
        if (isset($_SESSION['flash']) && !empty($_SESSION['flash'])) {
            echo $_SESSION['flash'];
            $_SESSION['flash'] = '';
        }

        $user_id = (!empty($user_id) ? $user_id : (isset($_GET['cod']) ? (int)$_GET['cod'] : ""));


        $query = "SELECT u.*, n.*,s.*, cl.* FROM usuarios u 
                    LEFT JOIN sistemas AS s ON u.AREA = s.sis_id
                    LEFT JOIN nivel AS n ON n.nivel_cod = u.nivel
                    LEFT JOIN clients AS cl ON cl.id = u.user_client 
                WHERE 1 = 1 ";


        if ($areaAdmin) {

            $userManageableAreas = getManagedAreasByUser($conn, $_SESSION['s_uid']);
            $csvAreas = "";
            foreach ($userManageableAreas as $mArea) {
                if (strlen((string)$csvAreas) > 0) 
                    $csvAreas .= ',';
                $csvAreas .= $mArea['sis_id'];
            }
            // $query .= " AND s.sis_id = " . $_SESSION['s_area'] . " ";
            $query .= " AND s.sis_id IN ({$csvAreas}) ";
        }

        // if (isset($_GET['cod'])) {
        if (!empty($user_id)) {
            $query .= " AND u.user_id = '" . $user_id . "' ";
        }
        $query .= "ORDER BY u.nome";
        $resultado = $conn->query($query);
        $registros = $resultado->rowCount();

        if ((!isset($_GET['action'])) && !isset($_POST['submit'])) {

        ?>
            <!-- Modal -->
            <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-light">
                            <h5 class="modal-title" id="exampleModalLabel"><i class="fas fa-exclamation-triangle text-secondary"></i>&nbsp;<?= TRANS('REMOVE'); ?></h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <?= TRANS('CONFIRM_REMOVE'); ?> <span class="j_param_id"></span>?
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= TRANS('BT_CANCEL'); ?></button>
                            <button type="button" id="deleteButton" class="btn"><?= TRANS('BT_OK'); ?></button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="deleteTmpModal" tabindex="-1" role="dialog" aria-labelledby="modalTitle" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-light">
                            <h5 class="modal-title" id="modalTitle"><i class="fas fa-exclamation-triangle text-secondary"></i>&nbsp;<?= TRANS('REMOVE'); ?></h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <?= TRANS('CONFIRM_REMOVE'); ?> <span class="j_param_id"></span>?
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= TRANS('BT_CANCEL'); ?></button>
                            <button type="button" id="deleteTmpButton" class="btn"><?= TRANS('BT_OK'); ?></button>
                        </div>
                    </div>
                </div>
            </div>

            <button class="btn btn-sm btn-primary" id="idBtIncluir" name="new"><?= TRANS("ACT_NEW"); ?></button><br /><br />
            <?php
            if ($registros == 0) {
                echo message('info', '', TRANS('NO_RECORDS_FOUND'), '', '', true);
            } else {

            ?>
                <table id="table_users" class="stripe hover order-column row-border" border="0" cellspacing="0" width="100%">
                    <thead>
                        <tr class="header">
                            <td class="line name"><?= TRANS('COL_NAME'); ?></td>
                            <td class="line login"><?= TRANS('OPT_LOGIN_NAME'); ?></td>
                            <td class="line client_name"><?= TRANS('CLIENT_NAME'); ?></td>
                            <td class="line admin"><?= TRANS('MANAGER'); ?></td>
                            <td class="line area"><?= TRANS('AREA'); ?></td>
                            <td class="line email"><?= TRANS('COL_EMAIL'); ?></td>
                            <td class="line level"><?= TRANS('LEVEL'); ?></td>
                            <td class="line last_logon"><?= TRANS('LAST_LOGON'); ?></td>
                            <td class="line editar"><?= TRANS('BT_EDIT'); ?></td>
                            <td class="line remover"><?= TRANS('BT_REMOVE'); ?></td>
                        </tr>
                    </thead>
                </table>
                <div class="chart-container">
                    <canvas id="canvasChart1"></canvas>
                </div>


                <?php
                if (!$areaAdmin) {
                ?>
                    <h6 class="my-4"><?= TRANS('WAITING_CONFIRMATION'); ?></h6>
                    <table id="table_users_tmp" class="stripe hover order-column row-border" border="0" cellspacing="0" width="100%">
                        <thead>
                            <tr class="header">
                                <td class="line name"><?= TRANS('COL_NAME'); ?></td>
                                <td class="line login"><?= TRANS('COL_LOGIN'); ?></td>
                                <td class="line email"><?= TRANS('COL_EMAIL'); ?></td>
                                <td class="line email"><?= TRANS('DATE'); ?></td>
                                <td class="line editar"><?= TRANS('BT_OK'); ?></td>
                                <td class="line remover"><?= TRANS('BT_REMOVE'); ?></td>
                            </tr>
                        </thead>
                    </table>
            <?php
                }
            }
        } else
		if ((isset($_GET['action'])  && ($_GET['action'] == "new")) && !isset($_POST['submit'])) {

            ?>
            <h6><?= TRANS('NEW_RECORD'); ?></h6>
            <form method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form">
                <?= csrf_input(); ?>
                <div class="form-group row my-4">

                    <label for="login_name" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_LOGIN'); ?></label>
                    <div class="form-group col-md-4">
                        <input type="text" class="form-control" id="login_name" name="login_name" required />
                    </div>

                    <label for="fullname" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('FULLNAME'); ?></label>
                    <div class="form-group col-md-4 ">
                        <input type="text" class="form-control " id="fullname" name="fullname" required />
                    </div>

                    


                    <label for="password" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('PASSWORD'); ?></label>
                    <div class="form-group col-md-4">
                        <input type="password" class="form-control " id="password" name="password" required />
                    </div>

                    <label for="password2" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('RETYPE_PASS'); ?></label>
                    <div class="form-group col-md-4">
                        <input type="password" class="form-control " id="password2" name="password2" required />
                    </div>

                    
                    <label for="level" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('LEVEL'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control" name="level" id="level" required>
                            <option value=""><?= TRANS('SEL_LEVEL'); ?></option>
                            <?php
                            if ($areaAdmin) {
                                $sql = "SELECT * FROM nivel WHERE nivel_cod = '" . $_SESSION['s_nivel'] . "' ORDER BY nivel_nome ";
                            } else {
                                $sql = "SELECT * FROM nivel WHERE nivel_cod <> 5 ORDER BY nivel_nome";
                            }
                            $res = $conn->query($sql);
                            foreach ($res->fetchall() as $row) {
                            ?>
                                <option value='<?= $row['nivel_cod']; ?>'><?= $row['nivel_nome']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>
                    

                    <input type="hidden" name="user_client_db" id="user_client_db" value="">
                    <label for="user_client" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CLIENT_NAME'); ?></label>
                    <div class="form-group col-md-4 ">
                        <select class="form-control" id="user_client" name="user_client">
                            <option id="user_client_sel_level" value=""><?= TRANS('SEL_LEVEL_FIRST'); ?></option>
                        </select>
                    </div>


                    <label for="subscribe_date" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_SUBSCRIBE_DATE'); ?></label>
                    <div class="form-group col-md-4 ">
                        <input type="text" class="form-control " id="subscribe_date" name="subscribe_date" value="<?= date("d/m/Y H:i:s"); ?>" required readonly />
                    </div>

                    <label for="hire_date" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('HIRE_DATE'); ?></label>
                    <div class="form-group col-md-4 ">
                        <input type="text" class="form-control " id="hire_date" name="hire_date" value="" />
                    </div>

                    <label for="email" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_EMAIL'); ?></label>
                    <div class="form-group col-md-4 ">
                        <input type="email" class="form-control " id="email" name="email" required />
                    </div>

                    <label for="phone" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_PHONE'); ?></label>
                    <div class="form-group col-md-4 ">
                        <input type="tel" class="form-control " id="phone" name="phone" required />
                    </div>

                    <label for="primary_area" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('PRIMARY_AREA'); ?></label>
                    <div class="form-group col-md-4 ">
                        <select class="form-control" id="primary_area" name="primary_area" required>
                            <option id="sel_areas" value=""><?= TRANS('LOADING'); ?></option>
                        </select>
                    </div>

                    <label class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('AREA_MANAGER'); ?></label>
                    <div class="form-group col-md-4 switch-field">
                        <input type="radio" id="area_admin" name="area_admin" value="yes" />
                        <label for="area_admin"><?= TRANS('YES'); ?></label>
                        <input type="radio" id="area_admin_no" name="area_admin" value="no" checked />
                        <label for="area_admin_no"><?= TRANS('NOT'); ?></label>
                    </div>

                    <!-- Seção para definição se o usuário pode encaminhar e receber chamados encaminhados -->
                    <label class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_CAN_ROUTE'); ?>"><?= TRANS('CAN_ROUTE'); ?></label>
                    <div class="form-group col-md-4 switch-field">
                        <input type="radio" id="can_route" name="can_route" value="yes" checked />
                        <label for="can_route"><?= TRANS('YES'); ?></label>
                        <input type="radio" id="can_route_no" name="can_route" value="no"  />
                        <label for="can_route_no"><?= TRANS('NOT'); ?></label>
                    </div>

                    <label class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_CAN_GET_ROUTED'); ?>"><?= TRANS('CAN_GET_ROUTED'); ?></label>
                    <div class="form-group col-md-4 switch-field">
                        
                        <input type="radio" id="can_get_routed" name="can_get_routed" value="yes" checked />
                        <label for="can_get_routed"><?= TRANS('YES'); ?></label>
                        <input type="radio" id="can_get_routed_no" name="can_get_routed" value="no"  />
                        <label for="can_get_routed_no"><?= TRANS('NOT'); ?></label>
                    </div>

                    <label for="bgcolor" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_USER_BGCOLOR'); ?>"><?= TRANS('COL_BG_COLOR'); ?></label>
                    <div class="form-group col-md-4 ">
                        <input type="color" class="form-control " id="bgcolor" name="bgcolor" value="<?= (isset($row['user_bgcolor']) ? $row['user_bgcolor'] : "#3A4D56"); ?>" />
                    </div>
                    <label for="textcolor" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_USER_TEXTCOLOR'); ?>"><?= TRANS('FONT_COLOR'); ?></label>
                    <div class="form-group col-md-4 ">
                        <input type="color" class="form-control " id="textcolor" name="textcolor" value="<?= (isset($row['user_textcolor']) ? $row['user_textcolor'] : "#FFFFFF"); ?>" />
                    </div>


                </div>

                <div class="form-group row my-4" id="div_secondary_areas"></div>
                <div class="form-group row my-4" id="div_manageble_areas"></div>

                <div class="form-group row my-4">
                    <div class="row w-100"></div>
                    <div class="form-group col-md-8 d-none d-md-block">
                    </div>
                    <div class="form-group col-12 col-md-2 ">

                        <input type="hidden" name="action" id="action" value="new">
                        <input type="hidden" name="isAdmin" id="isAdmin" value="<?= $isAdmin; ?>">
                        <button type="submit" id="idSubmit" name="submit" class="btn btn-primary btn-block"><?= TRANS('BT_OK'); ?></button>
                    </div>
                    <div class="form-group col-12 col-md-2">
                        <button type="reset" class="btn btn-secondary btn-block" onClick="parent.history.back();"><?= TRANS('BT_CANCEL'); ?></button>
                    </div>
                </div>
            </form>
        <?php
        } else

		if ((isset($_GET['action']) && $_GET['action'] == "edit") && empty($_POST['submit'])) {

            $row = $resultado->fetch();
            $onlyOpen = $row['nivel'] == 3;
            $userInfo = getUserInfo($conn, $user_id);

            $editable = (!$isAdmin ? ' disabled' : '');
        ?>
            <h6><?= TRANS('BT_EDIT'); ?></h6>
            <form method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form">
                <?= csrf_input(); ?>
                <div class="form-group row my-4">


                    <label for="login_name" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_LOGIN'); ?></label>
                    <div class="form-group col-md-4">
                        <input type="text" class="form-control " id="login_name" name="login_name" value="<?= (isset($row['login']) ? $row['login'] : ""); ?>" required readonly />
                    </div>

                    <label for="fullname" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('FULLNAME'); ?></label>
                    <div class="form-group col-md-4 ">
                        <input type="text" class="form-control " id="fullname" name="fullname" value="<?= (isset($row['nome']) ? $row['nome'] : ""); ?>" required />
                    </div>
                    
                    <div class="w-100"></div>

                    <label for="password" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('PASSWORD'); ?></label>
                    <div class="form-group col-md-4">
                        <input type="password" class="form-control " id="password" name="password" placeholder="<?= TRANS('PASSWORD_EDIT_PLACEHOLDER'); ?>" />
                    </div>

                    <label for="password2" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('RETYPE_PASS'); ?></label>
                    <div class="form-group col-md-4">
                        <input type="password" class="form-control " id="password2" name="password2" placeholder="<?= TRANS('PASSWORD_EDIT_PLACEHOLDER'); ?>" />
                    </div>

                    

                    <label for="level" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('LEVEL'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control" name="level" id="level" required>
                            <option value=""><?= TRANS('SEL_LEVEL'); ?></option>
                            <?php
                            if ($areaAdmin) {
                                $sql = "SELECT * FROM nivel WHERE nivel_cod in (" . $_SESSION['s_nivel'] . " , 5) ORDER BY nivel_nome ";
                            } else {
                                $sql = "SELECT * FROM nivel WHERE nivel_cod NOT IN (4) ORDER BY nivel_nome";
                            }
                            $res = $conn->query($sql);
                            foreach ($res->fetchall() as $rowLevel) {
                            ?>
                                <option value='<?= $rowLevel['nivel_cod']; ?>' <?= ($rowLevel['nivel_cod'] == $row['nivel'] ? 'selected' : ''); ?>><?= $rowLevel['nivel_nome']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>

                    
                    <input type="hidden" name="user_client_db" id="user_client_db" value="<?= $userInfo['user_client']; ?>">
                    <label for="user_client" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CLIENT_NAME'); ?></label>
                    <div class="form-group col-md-4 ">
                        <select class="form-control" id="user_client" name="user_client" required>
                            <option value=""><?= TRANS('SEL_SELECT'); ?></option>
                        </select>
                    </div>
                            

                    <div class="w-100"></div>

                    <label for="subscribe_date" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_SUBSCRIBE_DATE'); ?></label>
                    <div class="form-group col-md-4 ">
                        <input type="text" class="form-control " id="subscribe_date" name="subscribe_date" value="<?= (isset($row['data_inc']) ? dateScreen($row['data_inc'], 1) : ""); ?>" required readonly />
                    </div>

                    <label for="hire_date" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('HIRE_DATE'); ?></label>
                    <div class="form-group col-md-4 ">
                        <input type="text" class="form-control " id="hire_date" name="hire_date" value="<?= (isset($row['data_admis']) ? dateScreen($row['data_admis'], 1) : ""); ?>" />
                    </div>

                    <label for="email" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_EMAIL'); ?></label>
                    <div class="form-group col-md-4 ">
                        <input type="email" class="form-control " id="email" name="email" value="<?= (isset($row['email']) ? $row['email'] : ""); ?>" required />
                    </div>

                    <label for="phone" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_PHONE'); ?></label>
                    <div class="form-group col-md-4 ">
                        <input type="tel" class="form-control " id="phone" name="phone" value="<?= (isset($row['fone']) ? $row['fone'] : ""); ?>" required />
                    </div>

                    <label for="primary_area" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('PRIMARY_AREA'); ?></label>
                    <div class="form-group col-md-4 ">
                        <select class="form-control" id="primary_area" name="primary_area" required>
                            <option id="sel_areas" value=""><?= TRANS('LOADING'); ?></option>

                        </select>
                    </div>

                    <label class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_AREA_ADMINS_USERS'); ?>"><?= TRANS('AREA_MANAGER'); ?></label>
                    <div class="form-group col-md-4 switch-field">
                        <?php
                        $disabled = ($areaAdmin ? ' disabled' : '');
                        $yesChecked = ($row['user_admin'] == 1 ? "checked" : "");
                        $noChecked = ($row['user_admin'] == 0 ? "checked" : "");
                        ?>
                        <input type="radio" id="area_admin" name="area_admin" value="yes" <?= $yesChecked; ?> <?= $disabled; ?> />
                        <label for="area_admin"><?= TRANS('YES'); ?></label>
                        <input type="radio" id="area_admin_no" name="area_admin" value="no" <?= $noChecked; ?> <?= $disabled; ?> />
                        <label for="area_admin_no"><?= TRANS('NOT'); ?></label>
                    </div>


                    <!-- Seção para definição se o usuário pode encaminhar e receber chamados encaminhados -->
                    <label class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_CAN_ROUTE'); ?>"><?= TRANS('CAN_ROUTE'); ?></label>
                    <div class="form-group col-md-4 switch-field">
                        <?php
                        $disabled = ($row['nivel'] != 2 ? ' disabled' : '');
                        $yesChecked = ($row['can_route'] == 1 ? "checked" : "");
                        $noChecked = ($row['can_route'] == 0 || $row['can_route'] == '' ? "checked" : "");
                        ?>
                        <input type="radio" id="can_route" name="can_route" value="yes" <?= $yesChecked; ?> <?= $editable; ?> />
                        <label for="can_route"><?= TRANS('YES'); ?></label>
                        <input type="radio" id="can_route_no" name="can_route" value="no" <?= $noChecked; ?> <?= $editable; ?> />
                        <label for="can_route_no"><?= TRANS('NOT'); ?></label>
                    </div>

                    <label class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_CAN_GET_ROUTED'); ?>"><?= TRANS('CAN_GET_ROUTED'); ?></label>
                    <div class="form-group col-md-4 switch-field">
                        <?php
                        $disabled = ($row['nivel'] != 2 && $row['nivel'] != 1 ? ' disabled' : '');
                        $yesChecked = ($row['can_get_routed'] == 1 ? "checked" : "");
                        $noChecked = ($row['can_get_routed'] == 0 || $row['can_get_routed'] == '' ? "checked" : "");
                        ?>
                        <input type="radio" id="can_get_routed" name="can_get_routed" value="yes" <?= $yesChecked; ?> <?= $disabled; ?> />
                        <label for="can_get_routed"><?= TRANS('YES'); ?></label>
                        <input type="radio" id="can_get_routed_no" name="can_get_routed" value="no" <?= $noChecked; ?> <?= $disabled; ?> />
                        <label for="can_get_routed_no"><?= TRANS('NOT'); ?></label>
                    </div>


                    <label for="bgcolor" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_USER_BGCOLOR'); ?>"><?= TRANS('COL_BG_COLOR'); ?></label>
                    <div class="form-group col-md-4 ">
                        <input type="color" class="form-control " id="bgcolor" name="bgcolor" value="<?= (isset($row['user_bgcolor']) ? $row['user_bgcolor'] : ""); ?>" />
                    </div>
                    <label for="textcolor" class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_USER_TEXTCOLOR'); ?>"><?= TRANS('FONT_COLOR'); ?></label>
                    <div class="form-group col-md-4 ">
                        <input type="color" class="form-control " id="textcolor" name="textcolor" value="<?= (isset($row['user_textcolor']) ? $row['user_textcolor'] : ""); ?>" />
                    </div>


                </div>


                <div class="form-group row my-4" id="div_secondary_areas"></div>
                <div class="form-group row my-4" id="div_manageble_areas"></div>


                <div class="form-group row my-4">

                    <!-- <input type="hidden" name="cod" id="cod" value="<?= (int)$_GET['cod']; ?>"> -->
                    <input type="hidden" name="cod" id="cod" value="<?= $user_id; ?>">
                    <input type="hidden" name="area" id="idArea" value="<?= $row['sis_id']; ?>">
                    <input type="hidden" name="action" id="action" value="edit">
                    <input type="hidden" name="isAdmin" id="isAdmin" value="<?= $isAdmin; ?>">


                    <div class="row w-100"></div>
                    <div class="form-group col-md-8 d-none d-md-block">
                    </div>
                    <div class="form-group col-12 col-md-2 ">
                        <button type="submit" id="idSubmit" name="submit" value="edit" class="btn btn-primary btn-block"><?= TRANS('BT_OK'); ?></button>
                    </div>
                    <div class="form-group col-12 col-md-2">
                        <button type="reset" class="btn btn-secondary btn-block" onClick="parent.history.back();"><?= TRANS('BT_CANCEL'); ?></button>
                    </div>

                </div>
            </form>
        <?php
        } else 
        if ((isset($_GET['action']) && $_GET['action'] == "profile") && empty($_POST['submit'])) {

            $row = $resultado->fetch();

            $editable = (!$isAdmin ? ' disabled' : '');
        ?>
            <h6><?= TRANS('MY_PROFILE'); ?></h6>
            <form method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form">
                <?= csrf_input(); ?>
                <div class="form-group row my-4">


                    <label for="client" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CLIENT'); ?></label>
                    <div class="form-group col-md-4">
                        <input type="text" class="form-control " id="client" name="client" value="<?= (isset($row['nickname']) ? $row['nickname'] : ""); ?>" readonly />
                    </div>
                    <div class="w-100"></div>

                    <label for="login_name" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_LOGIN'); ?></label>
                    <div class="form-group col-md-4">
                        <input type="text" class="form-control " id="login_name" name="login_name" value="<?= (isset($row['login']) ? $row['login'] : ""); ?>" readonly />
                    </div>

                    <label for="change_pas" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('PASSWORD'); ?></label>
                    <div class="form-group col-md-4 ">
                        <?php
                        $enableChangePass = (!$localAuth ? " disabled" : "");
                        ?>
                        <button class="btn btn-sm btn-primary" id="change_pass" name="change_pass" <?= $enableChangePass; ?>><?= TRANS('BT_ALTER'); ?></button>
                    </div>

                    <div class="w-100"></div>

                    <label for="fullname" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('FULLNAME'); ?></label>
                    <div class="form-group col-md-4 ">
                        <input type="text" class="form-control " id="fullname" name="fullname" value="<?= (isset($row['nome']) ? $row['nome'] : ""); ?>" required />
                    </div>


                    <label for="level" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('LEVEL'); ?></label>
                    <div class="form-group col-md-4">

                        <div class="input-group">
                            <?php
                                $textChange = '<hr>' . TRANS('CLICK_TO_CHANGE');
                                $changeLevel = '';
                                /* Indicador do tipo de navegação */
                                if ($_SESSION['s_nivel_real'] == 1) {
                                    $changeLevel = ($_SESSION['s_nivel'] == 1 ? '<span id="change_level" title="'.TRANS('MSG_ADMIN_LEVEL_NAVIGATION') . $textChange . '" data-toggle="popover" data-content="" data-placement="left" data-trigger="hover"><i class="fa fa-user-cog"></i></span>' : '&nbsp;&nbsp;<span id="change_level" title="'.TRANS('MSG_OPERATOR_LEVEL_NAVIGATION') . $textChange . '" data-toggle="popover" data-content="" data-placement="left" data-trigger="hover"><i class="fa fa-user-edit"></i></span>');
                                }
                            ?>

                            <div class="input-group-prepend">
                                <div class="input-group-text">
                                    <?= $changeLevel; ?>
                                </div>
                            </div>

                            <select class="form-control" name="level" id="level" required <?= $editable; ?>>
                                <option value=""><?= TRANS('SEL_LEVEL'); ?></option>
                                <?php
                                if ($areaAdmin) {
                                    $sql = "SELECT * FROM nivel WHERE nivel_cod in (" . $_SESSION['s_nivel'] . " , 5) ORDER BY nivel_nome ";
                                } else {
                                    $sql = "SELECT * FROM nivel WHERE nivel_cod NOT IN (4) ORDER BY nivel_nome";
                                }
                                $res = $conn->query($sql);
                                foreach ($res->fetchall() as $rowLevel) {
                                ?>
                                    <option value='<?= $rowLevel['nivel_cod']; ?>' <?= ($rowLevel['nivel_cod'] == $row['nivel'] ? 'selected' : ''); ?>><?= $rowLevel['nivel_nome']; ?></option>
                                <?php
                                }
                                ?>
                            </select>
                        </div>
                    </div>


                    <label for="subscribe_date" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_SUBSCRIBE_DATE'); ?></label>
                    <div class="form-group col-md-4 ">
                        <input type="text" class="form-control " id="subscribe_date" name="subscribe_date" value="<?= (isset($row['data_inc']) ? dateScreen($row['data_inc'], 1) : ""); ?>" required readonly />
                    </div>

                    <label for="hire_date" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('HIRE_DATE'); ?></label>
                    <div class="form-group col-md-4 ">
                        <input type="text" class="form-control " id="hire_date" name="hire_date" value="<?= (isset($row['data_admis']) ? dateScreen($row['data_admis'], 1) : ""); ?>" <?= $editable; ?> />
                    </div>

                    <label for="email" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_EMAIL'); ?></label>
                    <div class="form-group col-md-4 ">
                        <input type="email" class="form-control " id="email" name="email" value="<?= (isset($row['email']) ? $row['email'] : ""); ?>" required />
                    </div>

                    <label for="phone" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_PHONE'); ?></label>
                    <div class="form-group col-md-4 ">
                        <input type="tel" class="form-control " id="phone" name="phone" value="<?= (isset($row['fone']) ? $row['fone'] : ""); ?>" required />
                    </div>

                    <label for="primary_area" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('PRIMARY_AREA'); ?></label>
                    <div class="form-group col-md-4 ">
                        <select class="form-control" id="primary_area" name="primary_area" <?= $editable; ?>>
                            <option id="sel_areas" value=""><?= TRANS('LOADING'); ?></option>

                        </select>
                    </div>

                    <label class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_AREA_ADMINS_USERS'); ?>"><?= TRANS('AREA_MANAGER'); ?></label>
                    <div class="form-group col-md-4 switch-field">
                        <?php
                        // $disabled = ' disabled';
                        $yesChecked = ($row['user_admin'] == 1 ? "checked" : "");
                        $noChecked = ($row['user_admin'] == 0 ? "checked" : "");
                        ?>
                        <input type="radio" id="area_admin" name="area_admin" value="yes" <?= $yesChecked; ?> <?= $editable; ?> />
                        <label for="area_admin"><?= TRANS('YES'); ?></label>
                        <input type="radio" id="area_admin_no" name="area_admin" value="no" <?= $noChecked; ?> <?= $editable; ?> />
                        <label for="area_admin_no"><?= TRANS('NOT'); ?></label>
                    </div>

                    <!-- Seção para definição se o usuário pode encaminhar e receber chamados encaminhados -->
                    <label class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_CAN_ROUTE'); ?>"><?= TRANS('CAN_ROUTE'); ?></label>
                    <div class="form-group col-md-4 switch-field">
                        <?php
                        // $disabled = ($_SESSION['s_nivel'] != 1 ? ' disabled' : '');
                        $yesChecked = ($row['can_route'] == 1 ? "checked" : "");
                        $noChecked = ($row['can_route'] == 0 || $row['can_route'] == '' ? "checked" : "");
                        ?>
                        <input type="radio" id="can_route" name="can_route" value="yes" <?= $yesChecked; ?> <?= $editable; ?> />
                        <label for="can_route"><?= TRANS('YES'); ?></label>
                        <input type="radio" id="can_route_no" name="can_route" value="no" <?= $noChecked; ?> <?= $editable; ?> />
                        <label for="can_route_no"><?= TRANS('NOT'); ?></label>
                    </div>

                    <label class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_CAN_GET_ROUTED'); ?>"><?= TRANS('CAN_GET_ROUTED'); ?></label>
                    <div class="form-group col-md-4 switch-field">
                        <?php
                        $disabled = ($row['nivel'] != 2 && $row['nivel'] != 1 ? ' disabled' : '');
                        $yesChecked = ($row['can_get_routed'] == 1 ? "checked" : "");
                        $noChecked = ($row['can_get_routed'] == 0 || $row['can_get_routed'] == '' ? "checked" : "");
                        ?>
                        <input type="radio" id="can_get_routed" name="can_get_routed" value="yes" <?= $yesChecked; ?> <?= $editable; ?> />
                        <label for="can_get_routed"><?= TRANS('YES'); ?></label>
                        <input type="radio" id="can_get_routed_no" name="can_get_routed" value="no" <?= $noChecked; ?> <?= $editable; ?> />
                        <label for="can_get_routed_no"><?= TRANS('NOT'); ?></label>
                    </div>


                    <label for="lang" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('MNL_LANG'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control" id="lang" name="lang">
                            <option value=""><?= TRANS('SYSTEM_DEFAULT'); ?></option>
                            <?php

                            foreach ($langLabels as $key => $label) {
                                if (in_array($key, $files)) {
                                    echo '<option value="' . $key . '"';
                                    echo ($rowUL && $key == $rowUL['upref_lang'] ? ' selected' : '') . '>' . $label;
                                    echo '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>


                <div class="form-group row my-4" id="div_secondary_areas"></div>
                <div class="form-group row my-4" id="div_manageble_areas"></div>


                <div class="form-group row my-4">

                    <input type="hidden" name="cod" id="cod" value="<?= $user_id; ?>">
                    <input type="hidden" name="password" id="password" value="">
                    <input type="hidden" name="password2" id="password2" value="">
                    <input type="hidden" name="area" id="idArea" value="<?= $row['sis_id']; ?>">
                    <input type="hidden" name="action" id="action" value="profile">
                    <input type="hidden" name="isAdmin" id="isAdmin" value="<?= $isAdmin; ?>">


                    <div class="row w-100"></div>
                    <div class="form-group col-md-8 d-none d-md-block">
                    </div>
                    <div class="form-group col-12 col-md-2 ">
                        <button type="submit" id="idSubmit" name="submit" value="profile" class="btn btn-primary btn-block"><?= TRANS('BT_OK'); ?></button>
                    </div>
                    <div class="form-group col-12 col-md-2">
                        <button type="reset" class="btn btn-secondary btn-block" onClick="parent.history.back();"><?= TRANS('BT_CANCEL'); ?></button>
                    </div>

                </div>
            </form>
        <?php
        }
        ?>
    </div>

    <script src="../../includes/javascript/funcoes-3.0.js"></script>
    <script src="../../includes/components/jquery/jquery.js"></script>
    <script src="../../includes/components/jquery/datetimepicker/build/jquery.datetimepicker.full.min.js"></script>

    <script src="../../includes/components/jquery/MHS/jquery.md5.min.js"></script>
    <script src="../../includes/components/jquery/jquery.initialize.min.js"></script>
    <script src="../../includes/components/bootstrap/js/bootstrap.bundle.js"></script>
	<script src="../../includes/components/bootstrap-select/dist/js/bootstrap-select.min.js"></script>
    <script type="text/javascript" src="../../includes/components/chartjs/dist/Chart.min.js"></script>
    <script type="text/javascript" charset="utf8" src="../../includes/components/datatables/datatables.js"></script>
    <script type="text/javascript" src="../../includes/components/chartjs/chartjs-plugin-colorschemes/dist/chartjs-plugin-colorschemes.js"></script>
    <script type="text/javascript" src="../../includes/components/chartjs/chartjs-plugin-datalabels/chartjs-plugin-datalabels.min.js"></script>

    <script src="./ajax/user_x_level.js"></script>
    <script type="text/javascript">
        $(function() {

            if ($('#canvasChart1').length)
                showTotalGraph();

            $(function() {
                $('[data-toggle="popover"]').popover({
                    html: true
                });
            });

            $('.popover-dismiss').popover({
                trigger: 'focus'
            });


            $.fn.selectpicker.Constructor.BootstrapVersion = '4';
			$('#user_client').selectpicker({
				/* placeholder */
				// noneSelectedText: 'teste',
				liveSearch: true,
				liveSearchNormalize: true,
				liveSearchPlaceholder: "<?= TRANS('BT_SEARCH', '', 1); ?>",
				noneResultsText: "<?= TRANS('NO_RECORDS_FOUND', '', 1); ?> {0}",
				style: "",
				styleBase: "form-control ",
			});

            /* Idioma global para os calendários */
            $.datetimepicker.setLocale('pt-BR');

            $('#hire_date').datetimepicker({
                timepicker: false,
                format: 'd/m/Y',
                lazyInit: true
            });



            if ($('#change_pass').length > 0) {
                $('#change_pass').on('click', function(e) {
                    e.preventDefault();
                    $('#divDetails').html('');
                    $("#divDetails").load('../../includes/common/change_pass.php');
                    $('#modal').modal();
                });
            }

            if ($('#change_level').length > 0) {
                $('#change_level').on('click', function() {
                    toggleUserLevel();
                }).css({ cursor: "pointer"});
            }

            loadClients();
            controlRoutingRadio();

            $.ajax({
                url: 'get_possible_areas.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    'level': $('#level').val()
                },
                success: function(data) {

                    var area = (typeof($('#idArea') !== 'undefined') ? $('#idArea').val() : "");
                    $('#sel_areas').text('<?= TRANS('SEL_AREA'); ?>');
                    if ($('#level').val() == "") {
                        $('#sel_areas').text('<?= TRANS('SEL_LEVEL_FIRST'); ?>');
                    } else {
                        $.each(data, function(key, data) {
                            $('#primary_area').append('<option value="' + data.sis_id + '"' + (data.sis_id == area ? 'selected' : '') + '>' + data.sistema + '</option>');
                        });
                    }
                }
            });

            $.ajax({
                url: 'get_secondary_areas.php',
                type: 'POST',
                data: {
                    // 'primary_area': $('#primary_area').val(), 
                    'primary_area': (typeof($('#idArea') !== 'undefined') ? $('#idArea').val() : ""),
                    'level': $('#level').val(),
                    'cod': (typeof $('#cod') !== 'undefined' ? $('#cod').val() : ""),
                    'action': $('#action').val(),
                    'setAdmin': $('#area_admin').is(':checked')
                },
                success: function(data) {
                    $('#div_secondary_areas').html(data);
                }
            });

            /* Vou ter que utilizar o observer para poder realizar esse controle */
            if ($('#div_secondary_areas').length > 0) {
                var obsClient = $.initialize(".switch-next-checkbox, .container-switch", function() {
                    
                    $(function() {
						$('[data-toggle="popover"]').popover({
							html: true
						});
					});

					$('.popover-dismiss').popover({
						trigger: 'focus'
					});

                    controlSetAreaAdmin()

                    $.each($('.switch-next-checkbox'), function(index, el) {
                        // controlSetAreaAdmin()
                        var group_parent = $(this).parent(); 
                        var enabled = group_parent.find('input:first').is(':checked') && $('#area_admin').is(':checked');

                        if (enabled) {
                            $(this).prop('disabled', false);
                        } else {
                            $(this).prop('disabled', true);
                        }
                    })

                    $('.container-switch').on('click', 'input', function() {

						var group_parent = $(this).parents(); //object
						var last_checkbox_id = group_parent.find('input:last').attr('id');
						var last_checkbox_name = group_parent.find('input:last').attr('name');

						if ($(this).val() == "no") {
							$('input[name="'+last_checkbox_name +'"]').prop('checked', false).prop('disabled', true);
							
						} else if ($('#area_admin').is(':checked')) {
							$('input[name="'+last_checkbox_name +'"]').prop('disabled', false);
						}
					});

					$('input[name^="setAdmin"]').on('change', function() {
						controlInputAreaAdmin($(this));
					})


                }, {
                    target: document.getElementById('div_secondary_areas')
                }); /* o target limita o scopo do observer */
            }


            $.ajax({
                url: 'get_areas_to_set_admin.php',
                type: 'POST',
                data: {
                    'primary_area': (typeof($('#idArea') !== 'undefined') ? $('#idArea').val() : ""),
                    'level': $('#level').val(),
                    'cod': (typeof $('#cod') !== 'undefined' ? $('#cod').val() : ""),
                    'action': $('#action').val(),
                    'setAdmin': $('#area_admin').is(':checked')
                },
                success: function(data) {
                    $('#div_manageble_areas').html(data);
                }
            });

            $('#level').on("change", function() {

                loadClients();
                controlRoutingRadio();
                $.ajax({
                    url: 'get_possible_areas.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        'level': $(this).val()
                    },
                    success: function(data) {

                        $('#primary_area').empty();

                        if ($('#level').val() == "") {
                            $('#primary_area').append('<option value="" selected id="sel_areas">' + '<?= TRANS('SEL_LEVEL_FIRST'); ?>' + '</option>');
                        } else {
                            $('#primary_area').append('<option value="" selected id="sel_areas">' + '<?= TRANS('SEL_AREA'); ?>' + '</option>');
                            $.each(data, function(key, data) {
                                $('#primary_area').append('<option value="' + data.sis_id + '">' + data.sistema + '</option>');
                            });
                        }
                    }
                });
            });


            $('#primary_area, #level, [name="area_admin"]').on("change", function() {
                $.ajax({
                    url: 'get_secondary_areas.php',
                    type: 'POST',
                    data: {
                        'primary_area': $('#primary_area').val(),
                        'level': $('#level').val(),
                        'cod': (typeof $('#cod') !== 'undefined' ? $('#cod').val() : ""),
                        'setAdmin': $('#area_admin').is(':checked')
                    },
                    success: function(data) {
                        $('#div_secondary_areas').html(data);
                    }
                });

                $.ajax({
                    url: 'get_areas_to_set_admin.php',
                    type: 'POST',
                    data: {
                        'primary_area': $('#primary_area').val(),
                        'level': $('#level').val(),
                        'cod': (typeof $('#cod') !== 'undefined' ? $('#cod').val() : ""),
                        'setAdmin': $('#area_admin').is(':checked')
                    },
                    success: function(data) {
                        $('#div_manageble_areas').html(data);
                    }
                });
            });

            $('[name="area_admin"]').on('change', function() {
                // console.log($(this).val());
                $.ajax({
                    url: 'get_areas_to_set_admin.php',
                    type: 'POST',
                    data: {
                        'primary_area': $('#primary_area').val(),
                        'level': $('#level').val(),
                        'cod': (typeof $('#cod') !== 'undefined' ? $('#cod').val() : ""),
                        'setAdmin': ($(this).val() == "yes" ? "true": false)
                    },
                    success: function(data) {
                        $('#div_manageble_areas').html(data);
                    }
                });
                
			});


            var dataTable = $('#table_users').DataTable({
                "processing": true,
                "serverSide": true,
                deferRender: true,
                columnDefs: [{
                    searchable: false,
                    orderable: false,
                    targets: ['editar', 'remover']
                }, ],
                "ajax": {
                    url: "users-grid-data.php", // json datasource
                    type: "post", // method  , by default get
                    data: {
                        "areaAdmin": '<?= $areaAdmin ?>'
                    },
                    error: function() { // error handling
                        $(".users-grid-error").html("");
                        $("#users-grid").append('<tbody class="users-grid-error"><tr><th colspan="3">Informações indisponíveis no momento</th></tr></tbody>');
                        $("#users-grid_processing").css("display", "none");
                    }
                },
                "language": {
                    "url": "../../includes/components/datatables/datatables.pt-br.json"
                }
            });


            if ($('#table_users_tmp').length) {
                var dataTableTmp = $('#table_users_tmp').DataTable({
                    "processing": true,
                    "serverSide": true,
                    deferRender: true,
                    columnDefs: [{
                        searchable: false,
                        orderable: false,
                        targets: ['editar', 'remover']
                    }, ],
                    "ajax": {
                        url: "userstmp_grid_data.php", // json datasource
                        type: "post", // method  , by default get
                        data: {
                            "areaAdmin": '<?= $areaAdmin ?>'
                        },
                        error: function() { // error handling
                            $(".users-grid-error").html("");
                            $("#users-grid").append('<tbody class="users-grid-error"><tr><th colspan="3">Informações indisponíveis no momento</th></tr></tbody>');
                            $("#users-grid_processing").css("display", "none");
                        }
                    },
                    "language": {
                        "url": "../../includes/components/datatables/datatables.pt-br.json"
                    }
                });
            }

            $('input, select, textarea').on('change', function() {
                $(this).removeClass('is-invalid');
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

                let password = ($('#password').val() != "" ? $.MD5($('#password').val()) : "");
                let password2 = ($('#password2').val() != "" ? $.MD5($('#password2').val()) : "");

                let form = $('#form').serialize();
                form = removeParam('password', form);
                form = removeParam('password2', form);
                form += "&password=" + password + "&password2=" + password2;

                $("#idSubmit").prop("disabled", true);
                $.ajax({
                    url: './users_process.php',
                    method: 'POST',
                    // data: $('#form').serialize(),
                    data: form,
                    dataType: 'json',
                }).done(function(response) {

                    // console.log(response);
                    if (!response.success) {
                        $('#divResult').html(response.message);
                        $('input, select, textarea').removeClass('is-invalid');
                        if (response.field_id != "") {
                            $('#' + response.field_id).focus().addClass('is-invalid');
                        }
                        $("#idSubmit").prop("disabled", false);
                    } else {
                        $('#divResult').html('');
                        $('input, select, textarea').removeClass('is-invalid');
                        $("#idSubmit").prop("disabled", false);

                        if (response.profile) {

                            /* window.top.location.reload(true); */
                            /* window.location.reload(true); */
                            window.top.location.reload(true);
                            return true;
                        } else {
                            var url = '<?= $_SERVER['PHP_SELF'] ?>';
                        }

                        $(location).prop('href', url);
                        return false;
                    }
                });
                return false;
            });

            $('#idBtIncluir').on("click", function() {
                $('#idLoad').css('display', 'block');
                var url = '<?= $_SERVER['PHP_SELF'] ?>?action=new';
                $(location).prop('href', url);
            });

            $('#bt-cancel').on('click', function() {
                var url = '<?= $_SERVER['PHP_SELF'] ?>';
                $(location).prop('href', url);
            });
        });


        function loadClients() {
            $.ajax({
                url: 'get_clients_by_user_level.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    'level': $('#level').val(),
                    'clientDb': $('#user_client_db').val()
                },
                success: function(data) {

                    var clientDb = (typeof($('#user_client_db') !== 'undefined') ? $('#user_client_db').val() : "");
                    $('#user_client').empty();

                    if ($('#level').val() == "") {
                        $('#user_client').append('<option value=""><?= TRANS("SEL_LEVEL_FIRST"); ?></option>');
                        $('#user_client').selectpicker('refresh');
                        $('#user_client').selectpicker('val', "");
                    }
                    else {
                        
                        if (Object.keys(data).length > 1) {
                            $('#user_client').append('<option value=""><?= TRANS("SEL_SELECT"); ?></option>');
                        }
                        
                        $.each(data, function(key, data) {
                            $('#user_client').append('<option value="' + data.id + '"' + (data.id == clientDb ? 'selected' : '') + '>' + data.nickname + '</option>');
                        });
                        
                        $('#user_client').selectpicker('refresh');
                        
                        if (Object.keys(data).length == 1) {
                            $('#user_client').selectpicker('val', data[0].id);
                        } else
                        if (clientDb != "") {

                            var found = false;

                            for (i in data) {
                                if (data[i].id == clientDb) {
                                    found = true;
                                    $('#user_client').selectpicker('val', clientDb);
                                    break;
                                }
                            }
                            
                            if (!found) {
                                $('#user_client').selectpicker('val', "");
                            }
                            
                        } else
                        {
                            $('#user_client').selectpicker('val', "");
                        }
                    }
                }
            });
        }

        function confirmDeleteModal(id) {
            $('#deleteModal').modal();
            $('#deleteButton').html('<a class="btn btn-danger" onclick="deleteData(' + id + ')"><?= TRANS('REMOVE'); ?></a>');
        }

        function deleteData(id) {

            var loading = $(".loading");
            $(document).ajaxStart(function() {
                loading.show();
            });
            $(document).ajaxStop(function() {
                loading.hide();
            });

            $.ajax({
                url: './users_process.php',
                method: 'POST',
                data: {
                    cod: id,
                    action: 'delete'
                },
                dataType: 'json',
            }).done(function(response) {
                var url = '<?= $_SERVER['PHP_SELF'] ?>';
                $(location).prop('href', url);
                return false;
            });
            return false;
            // $('#deleteModal').modal('hide'); // now close modal
        }



        function controlInputAreaAdmin(el) {
			if (el.is(':checked')) {
				$.each($('.switch-next-checkbox'), function(index, el) {
					$('input[name^="areaAdmin"]').prop('checked', false);
				});
				// $(el).prop('checked', true).prop('disabled', true);
			}
		}


        function controlSetAreaAdmin () {
			$.each($('.switch-next-checkbox'), function(index, el) {
				var group_parent = $(this).parent(); 

				/* Radio: ID da opção SIM */
				var first_checkbox_id = group_parent.find('input:first').attr('id');

				/* Se a opção está marcada como SIM */
				var enabled = group_parent.find('input:first').is(':checked');
				
				/* checkbox "gerente" */
				var last_checkbox_id = $(this).find('input:last').attr('id');
				var last_checkbox_name = $(this).find('input:last').attr('name');

                // console.log('first_checkbox_id: ' + $('#'+first_checkbox_id).val())
                // console.log('first_checkbox_id: ' + first_checkbox_id)
                console.log('last_checkbox_name: ' + last_checkbox_name)
                console.log('last_checkbox_id: ' + last_checkbox_id)
				

				if (!enabled && $('#area_admin').is(':checked')) {
					$('#' + last_checkbox_id).prop('checked', false).prop('disabled', true);
				} else {
					$('#' + last_checkbox_id).prop('disabled', false);
				}
			});
		}



        function confirmDeleteModalTmp(id) {
            $('#deleteTmpModal').modal();
            $('#deleteTmpButton').html('<a class="btn btn-danger" onclick="deleteTmpData(' + id + ')"><?= TRANS('REMOVE'); ?></a>');
        }

        function deleteTmpData(id) {

            var loading = $(".loading");
            $(document).ajaxStart(function() {
                loading.show();
            });
            $(document).ajaxStop(function() {
                loading.hide();
            });

            $.ajax({
                url: './new_user_confirm_process.php',
                method: 'POST',
                data: {
                    cod: id,
                    action: 'delete',
                },
                dataType: 'json',
            }).done(function(response) {
                var url = '<?= $_SERVER['PHP_SELF'] ?>';
                $(location).prop('href', url);
                return false;
            });
            return false;
            // $('#deleteModal').modal('hide'); // now close modal
        }

        function confirmUser(id) {

            var loading = $(".loading");
            $(document).ajaxStart(function() {
                loading.show();
            });
            $(document).ajaxStop(function() {
                loading.hide();
            });

            $.ajax({
                url: './new_user_confirm_process.php',
                method: 'POST',
                data: {
                    cod: id,
                    action: 'adminconfirm',
                },
                dataType: 'json',
            }).done(function(response) {
                var url = '<?= $_SERVER['PHP_SELF'] ?>';
                $(location).prop('href', url);
                return false;
            });
            return false;
            // $('#deleteModal').modal('hide'); // now close modal
        }

        function toggleUserLevel() {

            var loading = $(".loading");
            $(document).ajaxStart(function() {
                loading.show();
            });
            $(document).ajaxStop(function() {
                loading.hide();
            });

            $.ajax({
                url: '../../admin/geral/toggleUserLevel.php',
                method: 'POST',
                dataType: 'json',
                // data: {
                // 	prob_id: val,
                // },
            }).done(function(response) {
                window.top.location.reload(true);
                // console.log(response);
            });
        }

        function controlRoutingRadio() {
            let isOperator = $('#level').val() == '2';
            let isAdmin = $('#level').val() == '1';
            let action = $('#action').val();


            console.log('isAdmin: ' + $('#isAdmin').val())

            if (!isOperator && !isAdmin) {
                $('#can_get_routed').prop('checked', false).prop('disabled', true);
				$('#can_get_routed_no').prop('checked', true).prop('disabled', true);
                
                $('#can_route').prop('checked', false).prop('disabled', true);
				$('#can_route_no').prop('checked', true).prop('disabled', true);
                
            } else if (isOperator) {

                if (action == 'new') {
                    $('#can_get_routed').prop('checked', true);
                    $('#can_get_routed_no').prop('checked', false);

                    $('#can_route').prop('checked', true);
				    $('#can_route_no').prop('checked', false);
                }


                if ($('#isAdmin').val() == 1) {
                    $('#can_get_routed').prop('disabled', false);
                    $('#can_get_routed_no').prop('disabled', false);

                    $('#can_route').prop('disabled', false);
                    $('#can_route_no').prop('disabled', false);
                } else {
                    $('#can_get_routed').prop('disabled', true);
                    $('#can_get_routed_no').prop('disabled', true);

                    $('#can_route').prop('disabled', true);
                    $('#can_route_no').prop('disabled', true);
                }
                
            } else if (isAdmin) {
                
                if ($('#isAdmin').val() == 1) {
                    $('#can_get_routed').prop('disabled', false);
                    $('#can_get_routed_no').prop('disabled', false);

                    $('#can_route').prop('checked', true).prop('disabled', true);
				    $('#can_route_no').prop('checked', false).prop('disabled', true);
                } else {
                    $('#can_get_routed').prop('disabled', true);
                    $('#can_get_routed_no').prop('disabled', true);

                    $('#can_route').prop('disabled', true);
                    $('#can_route_no').prop('disabled', true);
                }
                
                // $('#can_get_routed').prop('disabled', false);
                // $('#can_get_routed_no').prop('disabled', false);
                
                // $('#can_route').prop('checked', true).prop('disabled', true);
				// $('#can_route_no').prop('checked', false).prop('disabled', true);
            }
        }
    </script>
</body>

</html>