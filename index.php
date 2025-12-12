<?php
/*                        Copyright 2023 Flávio Ribeiro

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

is_file("./includes/config.inc.php")
    or die("Você precisa configurar o arquivo config.inc.php em OCOMON/INCLUDES/para iniciar o uso do OCOMON!<br>Leia o arquivo <a href='LEIAME.md'>LEIAME.md</a> para obter as principais informações sobre a instalação do OCOMON!" .
        "<br><br>You have to configure the config.inc.php file in OCOMON/INCLUDES/ to start using Ocomon!<br>Read the file <a href='LEIAME.md'>LEIAME.md</a> to get the main informations about the Ocomon Installation!");

if (version_compare(phpversion(), '7.0', '<')) {
    session_start();
    session_destroy();
    echo "A versão mínima do PHP deve ser a 7.x. Será necessário atualizar o PHP para poder utilizar o OcoMon.<hr>";
    echo "OcoMon needs at least PHP 7.x to run properly.";
    return;
}

if (!function_exists('mb_internal_encoding')) {
    /* Não possui o módulo mbstring */
    session_start();
    session_destroy();
    echo "É necessário instalar o módulo mbstring no seu PHP para que o OcoMon funcione adequadamente.<hr>";
    echo "You need to install mbstring PHP module in order to OcoMon runs properly.";
    return;
}

session_start();

include "PATHS.php";
require_once "includes/functions/functions.php";
require_once "includes/functions/dbFunctions.php";
include_once "includes/queries/queries.php";
require_once "" . $includesPath . "config.inc.php";
include_once "" . $includesPath . "versao.php";

require_once __DIR__ . "/" . "includes/classes/ConnectPDO.php";

use includes\classes\ConnectPDO;

$conn = ConnectPDO::getInstance();


if (!isset($_SESSION['s_logado']) || $_SESSION['s_logado'] != 1) {
    redirect('./login.php');
    exit;
}

if (!isset($_SESSION['s_language'])) {
    $_SESSION['s_language'] = "pt_BR.php";
}

if (!isset($_SESSION['s_usuario'])) {
    $_SESSION['s_usuario'] = "";
}
if (!isset($_SESSION['s_usuario_nome'])) {
    $_SESSION['s_usuario_nome'] = "";
}

if (!isset($_SESSION['s_logado'])) {
    $_SESSION['s_logado'] = "";
}

if (!isset($_SESSION['s_nivel'])) {
    $_SESSION['s_nivel'] = "";
}

$uName = $_SESSION['s_usuario_nome'];
if (!empty($uName)) {
    $hnt = TRANS('HNT_LOGOFF');
}

$screen = getScreenInfo($conn, 1);
// $mailConfig = getMailConfig($conn);
// $configExt = getConfigValues($conn);

$marca = "HOME";

$rootPath = "./";
$ocomonPath = "./ocomon/geral/";
$invmonPath = "./invmon/geral/";
$adminPath = "./admin/geral/";

/* Páginas que serão carregadas por padrão em cada aba */
$simplesHome = (isset($_SESSION['s_page_simples']) ? $_SESSION['s_page_simples'] : $ocomonPath . "tickets_main_user.php?action=listall");
$homeHome = (isset($_SESSION['s_page_home']) ? $_SESSION['s_page_home'] : "home.php");
$ocoHome = (isset($_SESSION['s_page_ocomon']) ? $_SESSION['s_page_ocomon'] : $ocomonPath . "tickets_main.php");
$invHome = (isset($_SESSION['s_page_invmon']) ? $_SESSION['s_page_invmon'] : $invmonPath . "inventory_main.php");
$admHome = (isset($_SESSION['s_page_admin']) ? $_SESSION['s_page_admin'] : $adminPath . "users.php");
$admAreaHome = $adminPath . "users.php";

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= TRANS('TTL_OCOMON'); ?>">
    <title>OcoMon&nbsp;<?= VERSAO; ?></title>

    <!-- using local links -->
    <link rel="stylesheet" href="./includes/components/bootstrap/custom.css">
    <link rel="stylesheet" href="./includes/components/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="./includes/components/malihu-custom-scrollbar/jquery.mCustomScrollbar.min.css">
    <link rel="stylesheet" href="./includes/components/sidebar/css/main.css">
    <link rel="stylesheet" href="./includes/components/sidebar/css/sidebar-themes.css">
    <link rel="stylesheet" type="text/css" href="./includes/css/estilos.css" />
    <link rel="stylesheet" type="text/css" href="./includes/css/estilos_custom.css" />
    <link rel="stylesheet" type="text/css" href="./includes/css/index_css.css" />
    <link rel="stylesheet" type="text/css" href="./includes/css/util.css" />
    <link rel="shortcut icon" href="./includes/icons/favicon.ico">

</head>

<body>

    <?php
    if (isPHPOlder()) {
        echo message('danger', 'Ooops!', TRANS('ERROR_PHP_VERSION'), '', '', 1);
        session_destroy();
        return;
    }

    $missingModule = alertRequiredModule('pdo');
    if (strlen((string)$missingModule)) {
        echo $missingModule;
        session_destroy();
        return;
    }
    
    $missingModule = alertRequiredModule('pdo_mysql');
    if (strlen((string)$missingModule)) {
        echo $missingModule;
        session_destroy();
        return;
    }

    $missingModule = alertRequiredModule('mbstring');
    if (strlen((string)$missingModule)) {
        echo $missingModule;
        session_destroy();
        return;
    }


    ?>
    <input type="hidden" name="s_logado" id="s_logado" value="<?= $_SESSION['s_logado']; ?>">
    <?php
    if ($_SESSION['s_logado']) {

        $userProfileIcon = ($_SESSION['s_nivel'] == 1 ? "fa fa-user-cog" : ($_SESSION['s_nivel'] == 2 ? "fa fa-user-edit" : "fa fa-user"));

        $textProfile = "";
        $profile = '&nbsp;&nbsp;<span id="profile" title="'.TRANS('MY_PROFILE') . $textProfile . '" data-toggle="popover" data-content="" data-placement="left" data-trigger="hover"><i class="' . $userProfileIcon . ' fs-13"></i></span>';


        $userName = '<span>' . $_SESSION["s_usuario_nome"] . '</span>';
        ?>
        <header>
            <div class="topo topo-color fixed-top " style="z-index:4;">

                <div id="header_logo">
                    <span class="logo"><img src="MAIN_LOGO.svg" width="240" class=""></span>
                </div>
                <div id="header_elements" class=" fs-14">
                    <span class=" d-none d-sm-block align-items-center"> <?=  $userName . $profile . "&nbsp;&nbsp;|&nbsp;&nbsp;"; ?>
                        <a class="text-danger" href="<?= $commonPath; ?>logout.php" title="<?= $hnt ?>" data-toggle="popover" data-content="" data-placement="left" data-trigger="hover"><i class="fas fa-sign-out-alt fs-18"></i></a>
                    </span>

                    <span class="d-block d-sm-none text-right">
                        <a class="text-danger" href="<?= $commonPath; ?>logout.php" title="<?= $hnt ?>" data-toggle="popover" data-content="" data-placement="left" data-trigger="hover"><i class="fas fa-sign-out-alt fs-18"></i></a>
                    </span>
                </div>

                <div class="barra">
                    <input type="hidden" name="s_nivel" id="s_nivel" value="<?= $_SESSION['s_nivel']; ?>">
                    <?php

                    if (empty($_SESSION['s_permissoes']) && $_SESSION['s_nivel'] != 1) {
                        print "&nbsp;";
                        print "&nbsp;";
                        print "&nbsp;";
                        print "&nbsp;";
                        print "&nbsp;";
                    } else {

                        print "<a class='barra td-barra' id='HOME' onclick=\"loadPage('menu-sidebar.php?menu=hom #sidebar-loaded',loadMenu()); loadPageContent('hom');\" >&nbsp;" . TRANS('MNS_HOME') . "&nbsp;</a>";

                        if ($_SESSION['s_nivel'] < 3) {

                            if (($_SESSION['s_ocomon'] == 1) && !isIn($_SESSION['s_area'], $screen['conf_ownarea_2'])) {
                                print "<a class='barra td-barra' id='OCOMON' onclick=\"loadPage('menu-sidebar.php?menu=oco #sidebar-loaded',loadMenu()); loadPageContent('oco'); \">&nbsp;" . TRANS('TICKETS') . "&nbsp;</a>";
                            } elseif (($_SESSION['s_ocomon'] == 1) && isIn($_SESSION['s_area'], $screen['conf_ownarea_2'])) {
                                print "<a class='barra td-barra' id='OCOMON' onclick=\"loadPage('menu-sidebar.php?menu=oco #sidebar-loaded',loadMenu()); loadPageContent('hom'); \">&nbsp;" . TRANS('TICKETS') . "&nbsp;</a>";
                            } else {
                                print "&nbsp;" . TRANS('TICKETS') . "&nbsp;";
                            }
                        }

                        if ($_SESSION['s_invmon'] == 1) {
                            print "<a class='barra td-barra' id='INVMON' onclick=\"loadPage('menu-sidebar.php?menu=inv #sidebar-loaded',loadMenu()); loadPageContent('inv'); \">&nbsp;" . TRANS('INVENTORY') . "&nbsp;</a>";
                        } else {
                            print "&nbsp;";
                        }

                        if ($_SESSION['s_nivel'] == 1 || (isset($_SESSION['s_area_admin']) && $_SESSION['s_area_admin'] == '1')) {
                            print "<a class='barra td-barra' id='ADMIN' onclick=\"loadPage('menu-sidebar.php?menu=adm #sidebar-loaded',loadMenu()); loadPageContent('admin'); \">&nbsp;" . TRANS('ADMIN') . "&nbsp;</a>";
                        } else {
                            print "&nbsp;";
                        }

                    ?>
                        <span data-toggle="popover" data-content="<?= TRANS('MENU_SHOW_HIDE'); ?>" data-trigger="hover" data-placement="right">
                            <a href="#" class="td-barra toggle-sidebar"><i class="fas fa-bars"></i></a>
                        </span>

                        <span class=" d-none d-sm-block td-barra-right" id="current_date"><?= TRANS(date("l")) . ",&nbsp;" . (dateScreen(date("Y/m/d H:i:s"), 0, 'd/m/Y H:i')); ?></span>


                    <?php
                    }
                    ?>
                </div> <!-- barra -->
            </div> <!-- topo -->
        </header>

        <!-- <div class="page-wrapper default-theme sidebar-bg bg1 toggled"> -->
        <div class="page-wrapper theme ocomon-theme toggled border-radius-on">
            <!-- default-theme legacy-theme chiller-theme ice-theme cool-theme light-theme -->
            <nav id="sidebar" class="sidebar-wrapper">
                <!-- the menu will be loaded dynamicaly -->
                <input type="hidden" name="defaultPageHome" id="defaultPageHome" value="<?= $homeHome; ?>">
                <input type="hidden" name="defaultPageOcomon" id="defaultPageOcomon" value="<?= $ocoHome; ?>">
                <input type="hidden" name="defaultPageInvmon" id="defaultPageInvmon" value="<?= $invHome; ?>">
                <input type="hidden" name="defaultPageAdmin" id="defaultPageAdmin" value="<?= $admHome; ?>">
                <input type="hidden" name="defaultPageAdminArea" id="defaultPageAdminArea" value="<?= $admAreaHome; ?>">
            </nav>

            <main class="page-content  pt-2">
                <div id="overlay" class="overlay"></div>
                <iframe id="iframeMain" class="iframeMain" frameborder="0"></iframe><!-- scrolling="no" -->
            </main>
            <!-- page-content" -->
        </div>


        <!-- FOOTER -->
        <div class="fixed-bottom toggle-footer cursor_to_down" id="footer_fixed">
            <!-- style="margin-top:50px;" -->
            <div class=" fixed-bottom border-top bg-light text-center footer-content p-2" style="z-index:4; ">
                <!-- w3-card  -->
                <div class="footer-text">

                    <a href="https://ocomonphp.sourceforge.io/" target="_blank">
                        OcoMon
                    </a>&nbsp;-&nbsp;
                    <?= TRANS('OCOMON_ABSTRACT'); ?><br />
                    <?= TRANS('COL_VERSION') . ": " . VERSAO . " - " . TRANS('MNS_MSG_LIC') . " GPL"; ?>
                </div>
            </div>
        </div>
    <?php
    }
    ?>
    <!-- page-wrapper -->
    <script src="./includes/components/jquery/jquery.js"></script>
    <script src="./includes/components/jquery/jquery.initialize.min.js"></script>
    <!-- <script src="./includes/components/jquery/MHS/jquery.md5.min.js"></script> -->
    <script src="./includes/components/bootstrap/js/bootstrap.bundle.js"></script>
    <script src="./includes/components/malihu-custom-scrollbar/jquery.mCustomScrollbar.concat.min.js"></script>
    <script src="./includes/javascript/funcoes-3.0.js"></script>
    <script src="./includes/components/sidebar/js/main.js"></script>

    <script>
        $(function() {


            $('#forgot_pass').on('click', function() {
                requireAccessRecovery();
            }).css({
                cursor: "pointer"
            });

            if ($('#registerToOpen').length > 0) {
                $('#registerToOpen').on('click', function() {
                    autosubscribeform();
                }).css({
                    cursor: "pointer"
                });
            }

            if ($('#openBlindTicket').length > 0) {
                $('#openBlindTicket').on('click', function() {
                    var url = './ocomon/open_form/ticket_form_open.php';
                    $(location).prop('href', url);
                    // return false;
                }).css({
                    cursor: "pointer"
                });
            }

            

            $('#profile').on('click', function() {
                $("#iframeMain").attr("src", "./admin/geral/users.php?action=profile");
            }).css({ cursor: "pointer"});


            setInterval(function() {
                showCurrentDate();
            }, 50000);
        });

        function autosubscribeform() {
            let location = 'newUser.php';
            $("#divDetails").load(location);
            $('#modal').modal();
        }

        function requireAccessRecovery() {
            let location = './includes/common/require_access_recovery.php';
            $("#divDetails").load(location);
            $('#modal').modal();
        }

        function showCurrentDate() {
            $.ajax({
                url: 'currentDate.php',
                method: 'POST',
                dataType: 'json',

            }).done(function(data) {
                $("#current_date").empty();
                $("#current_date").html(data);
            }).fail(function() {
                // $('#divError').html('<p class="text-danger text-center"><?= TRANS('FETCH_ERROR'); ?></p>');
            });
            return false;
        }
    </script>

</body>

</html>