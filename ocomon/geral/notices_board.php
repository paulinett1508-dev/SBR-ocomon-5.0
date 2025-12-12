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
    // $_SESSION['session_expired'] = 1;
    echo "<script>top.window.location = '../../index.php'</script>";
    $_SESSION['session_expired'] = 1;
    echo "<script>top.window.location = '../../index.php'</script>";
    exit;
}

require_once __DIR__ . "/" . "../../includes/include_geral_new.inc.php";
require_once __DIR__ . "/" . "../../includes/classes/ConnectPDO.php";

use includes\classes\ConnectPDO;

$conn = ConnectPDO::getInstance();

$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 2, 1);

$_SESSION['s_page_home'] = $_SERVER['PHP_SELF'];

$isAdmin = $_SESSION['s_nivel'] == 1;

$userAreas = explode(',', $_SESSION['s_uareas']);


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
    <link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap-select/dist/css/bootstrap-select.min.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/css/my_bootstrap_select.css" />

    <link rel="stylesheet" type="text/css" href="../../includes/components/fontawesome/css/all.min.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/datatables/datatables.min.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/summernote/summernote-bs4.css" />
    <!-- <link rel="stylesheet" type="text/css" href="../../includes/components/jodit/build/jodit.min.css" /> -->
    <!-- <link rel="stylesheet" type="text/css" href="../../includes/css/my_jodit.css" /> -->

    <link rel="stylesheet" type="text/css" href="../../includes/css/util.css" />

    <title>OcoMon&nbsp;<?= VERSAO; ?></title>

    <style>
        li.areas_target {
            line-height: 1.5em;
        }

        .dropdown-header {
            cursor: pointer !important;
            background: teal !important;
            color: white !important;
            /* font-weight: bold; */
        }

       
        .bshield {
            width: 35px;
            height: 35px;
            text-align: center;
            display: flex;
            justify-content: center;
            align-items: center;
        }

       
    </style>

</head>

<body>

    <div class="container">
        <div id="idLoad" class="loading" style="display:none"></div>
    </div>

    <div id="divResult"></div>


    <div class="container-fluid">
        <h4 class="my-4"><i class="fas fa-bell text-secondary"></i>&nbsp;<?= TRANS('TLT_BOARD_NOTICE'); ?></h4>
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

        $types = [];
        $types['warning'] = TRANS('TOAST_WARNING');
        $types['error'] = TRANS('TOAST_ERROR');
        $types['info'] = TRANS('TOAST_INFO');
        $types['success'] = TRANS('TOAST_SUCCESS');

        $badges = [];
        $badges['warning'] = '<span class="badge badge-warning bshield p-2 mb-2" title="'.TRANS('TOAST_WARNING').'"><i class="fas fa-exclamation-triangle fs-20 text-white"></i></span>';
        $badges['error'] = '<span class="badge badge-danger bshield p-2 mb-2" title="'.TRANS('TOAST_ERROR').'"><i class="fas fa-times fs-20 text-white"></i></span>';
        $badges['info'] = '<span class="badge badge-info bshield p-2 mb-2" title="'.TRANS('TOAST_INFO').'"><i class="fas fa-info fs-20 text-white"></i></span>';
        $badges['success'] = '<span class="badge badge-success bshield p-2 mb-2" title="'.TRANS('TOAST_SUCCESS').'"><i class="fas fa-check fs-20 text-white"></i></span>';

        /* Checa avisos expirados */
        $sql = "UPDATE avisos SET 
                    is_active = 0 
                WHERE expire_date < '" . date("Y-m-d") . "'
        ";
        $conn->exec($sql);


        $terms = "";
        $operator = "";
        if (!$isAdmin) {
            $terms = "((";
            foreach ($userAreas as $uarea) {
                $terms .= " {$operator} (FIND_IN_SET('{$uarea}', a.area) > 0)  ";
                $operator = "OR";
            }
            $terms .= ") OR a.area = -1 OR a.area IS NULL) AND ";
        }

        $query = "SELECT a.*, u.*, ar.* 
                    FROM 
                        usuarios u, avisos a 
                    LEFT JOIN sistemas ar ON a.area = ar.sis_id 
                    WHERE 
                        {$terms} a.origem = u.user_id ";

        if (isset($_GET['cod'])) {
            $query .= " AND a.aviso_id = '" . $_GET['cod'] . "'";
        }
        $query .= " ORDER BY u.nome";

        try {
            $resultado = $conn->query($query);
        } catch (Exception $e) {
            echo message('danger', 'Ooops!', $e->getMessage() . '<hr>' . $query, '');
            return false;
        }

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

            <button class="btn btn-sm btn-primary" id="idBtIncluir" name="new"><?= TRANS("ACT_NEW"); ?></button><br /><br />
            <?php
            if ($registros == 0) {
                echo message('info', '', TRANS('NO_RECORDS_FOUND'), '', '', true);
            } else {

            ?>
                <table id="table_lists" class="stripe hover order-column row-border" border="0" cellspacing="0" width="100%">

                    <thead>
                        <tr class="header">
                            <td class="line shield"><?= TRANS('COL_TYPE'); ?></td>
                            <td class="line title"><?= TRANS('TITLE'); ?></td>
                            <td class="line area" width="30%"><?= TRANS('NOTICE'); ?></td>
                            <td class="line subject"><?= TRANS('AUTHOR'); ?></td>
                            <td class="line subject"><?= TRANS('DESTINY_AREA'); ?></td>
                            <td class="line email"><?= TRANS('COL_TYPE'); ?></td>
                            <td class="line screen_profile"><?= TRANS('DATE'); ?></td>
                            <td class="line status"><?= TRANS('COL_PERSISTENT'); ?></td>
                            <td class="line wc_profile"><?= TRANS('COL_STATUS'); ?></td>
                            <td class="line status"><?= TRANS('ACTIVE_UNTIL'); ?></td>
                            <td class="line editar"><?= TRANS('BT_EDIT'); ?></td>
                            <td class="line remover"><?= TRANS('BT_REMOVE'); ?></td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php

                        foreach ($resultado->fetchall() as $row) {

                            // $lstatus = ($row['is_active'] == 0 ? TRANS('INACTIVE') : TRANS('ACTIVE'));
                            $lstatus = ($row['is_active'] == 0 ? "<span class='badge badge-danger p-2'>" . TRANS('INACTIVE_O') . "</span>" : "<span class='badge badge-success p-2'>" . TRANS('ACTIVE_O') . "</span>");
                            if ($row['is_active'] == "") {
                                // $lstatus = TRANS('MSG_NOT_DEFINED');
                                $lstatus = "<span class='badge badge secondary p-2'>" . TRANS('MSG_NOT_DEFINED') . "</span>";
                            }

                            $recorrent = ($row['is_recurrent'] ? '<span class="text-success"><i class="fas fa-check"></i></span>' : '');

                            $areaDestiny = (($row['area'] == "-1" or $row['area'] == "") ? TRANS('ALL_TREATERS_AREAS') : "");

                            if (empty($areaDestiny)) {
                                $sql = "SELECT sistema FROM sistemas WHERE sis_id IN (" . $row['area'] . ") ORDER BY sistema";
                                try {
                                    $res = $conn->query($sql);
                                    foreach ($res->fetchall() as $rowArea) {
                                        $areaDestiny .= '<li class="areas_target">' . $rowArea['sistema'] ?? '' . '</li>';
                                    }
                                } catch (Exception $e) {
                                    echo 'Erro: ', $e->getMessage(), "<br/>";
                                }
                            }

                            $noticeType = TRANS('MSG_NOT_DEFINED');
                            $badgeType = "";
                            foreach ($types as $key => $type) {
                                if ($row['status'] == $key) {
                                    $noticeType = $type;
                                    $badgeType = $badges[$key];
                                }
                            }

                        ?>
                            <tr>
                                <td class="line"><?= $badgeType; ?></td>
                                <td class="line"><?= trim($row['title']); ?></td>
                                <td class="line"><?= trim($row['avisos']); ?></td>
                                <td class="line"><?= $row['nome']; ?></td>
                                <td class="line"><?= $areaDestiny; ?></td>
                                <td class="line"><?= $noticeType; ?></td>
                                <td class="line" data-sort="<?= $row['data']; ?>"><?= dateScreen($row['data']); ?></td>
                                <td class="line"><?= $recorrent; ?></td>
                                <td class="line"><?= $lstatus; ?></td>
                                <td class="line" data-sort="<?= $row['expire_date']; ?>"><?= dateScreen($row['expire_date'], 1); ?></td>
                                <td class="line"><button type="button" class="btn btn-secondary btn-sm" onclick="redirect('<?= $_SERVER['PHP_SELF']; ?>?action=edit&cod=<?= $row['aviso_id']; ?>')"><?= TRANS('BT_EDIT'); ?></button></td>
                                <td class="line"><button type="button" class="btn btn-danger btn-sm" onclick="confirmDeleteModal('<?= $row['aviso_id']; ?>')"><?= TRANS('REMOVE'); ?></button></td>
                            </tr>

                        <?php
                        }
                        ?>
                    </tbody>
                </table>
            <?php
            }
        } else
		if ((isset($_GET['action'])  && ($_GET['action'] == "new")) && !isset($_POST['submit'])) {

            ?>
            <h6><?= TRANS('NEW_RECORD'); ?></h6>
            <form method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form">
                <?= csrf_input(); ?>
                <div class="form-group row my-4">

                    <label for="title" class="col-sm-2 col-md-2 col-form-label text-md-right"><?= TRANS('TITLE'); ?></label>
                    <div class="form-group col-md-10">
                        <input type="text" class="form-control" id="title" name="title" required />
                        <div class="invalid-feedback">
                            <?= TRANS('MANDATORY_FIELD'); ?>
                        </div>
                    </div>


                    <label for="notice" class="col-sm-2 col-md-2 col-form-label text-md-right"><?= TRANS('NOTICE'); ?></label>
                    <div class="form-group col-md-10">
                        <textarea class="form-control" id="notice" name="notice" required></textarea>
                        <div class="invalid-feedback">
                            <?= TRANS('MANDATORY_FIELD'); ?>
                        </div>
                        <small class="text-mute"><?= TRANS('HELPER_NOTICE'); ?></small>
                    </div>


                    <label for="type" class="col-md-2 col-form-label text-md-right"><?= TRANS('COL_TYPE'); ?></label>
                    <div class="form-group col-md-10">
                        <select class="form-control " id="type" name="type">

                            <?php
                            foreach ($types as $key => $type) {
                            ?>
                                <option value="<?= $key; ?>" <?= ($key == 'info' ? ' selected' : ''); ?>><?= $type; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>

                    <label for="area" class="col-sm-2 col-md-2 col-form-label text-md-right"><?= TRANS('DESTINY_AREA'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2" id="area" name="area[]" multiple="multiple">
                            <optgroup label="<?= TRANS('SERVICE_AREAS'); ?>" data-icon="fas fa-headset">
                                <?php
                                $areas = getAreas($conn, 0, 1, 1);
                                foreach ($areas as $area) {
                                    if ($isAdmin || in_array($area['sis_id'], $userAreas)) {
                                    ?>
                                        <option value="<?= $area['sis_id']; ?>"><?= $area['sistema']; ?></option>
                                    <?php
                                    }
                                }
                                ?>
                            </optgroup>
                            
                            <optgroup label="<?= TRANS('REQUESTER_AREAS'); ?>" data-icon="fas fa-user">
                                <?php
                                $areas = getAreas($conn, 0, 1, 0);
                                foreach ($areas as $area) {
                                    if ($isAdmin || in_array($area['sis_id'], $userAreas)) {
                                    ?>
                                        <option value="<?= $area['sis_id']; ?>"><?= $area['sistema']; ?></option>
                                    <?php
                                    }
                                }
                                ?>
                            </optgroup>

                        </select>
                    </div>

                    <label for="expire_date" class="col-md-2 col-form-label text-md-right"><?= TRANS('ACTIVE_UNTIL'); ?></label>
                    <div class="form-group col-md-4">
                        <input type="text" class="form-control" name="expire_date" id="expire_date" />
                    </div>


                    <label class="col-md-2 col-form-label text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_RECURRENT_NOTICE'); ?>"><?= firstLetterUp(TRANS('COL_PERSISTENT')); ?></label>
                    <div class="form-group col-md-4 ">
                        <div class="switch-field">
                            <?php
                            $yesChecked = "checked";
                            $noChecked = "";
                            ?>
                            <input type="radio" id="is_recurrent" name="is_recurrent" value="yes" <?= $yesChecked; ?> />
                            <label for="is_recurrent"><?= TRANS('YES'); ?></label>
                            <input type="radio" id="is_recurrent_no" name="is_recurrent" value="no" <?= $noChecked; ?> />
                            <label for="is_recurrent_no"><?= TRANS('NOT'); ?></label>
                        </div>
                    </div>

                    <div class="row w-100"></div>
                    <div class="form-group col-md-8 d-none d-md-block">
                    </div>
                    <div class="form-group col-12 col-md-2 ">

                        <input type="hidden" name="action" id="action" value="new">
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
        ?>
            <h6><?= TRANS('BT_EDIT'); ?></h6>
            <form method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form">
                <?= csrf_input(); ?>

                <div class="form-group row my-4">

                    <label for="author" class="col-md-2 col-form-label text-md-right"><?= TRANS('AUTHOR'); ?></label>
                    <div class="form-group col-md-4">

                        <input type="text" class="form-control" name="author" id="author" value="<?= getUserInfo($conn, $row['origem'])['nome']; ?>" disabled />
                    </div>

                    <label for="date_record" class="col-md-2 col-form-label text-md-right"><?= TRANS('DATE'); ?></label>
                    <div class="form-group col-md-4">

                        <input type="text" class="form-control" name="date_record" id="date_record" value="<?= dateScreen($row['data'], 1); ?>" disabled />
                    </div>

                    <label for="title" class="col-sm-2 col-md-2 col-form-label text-md-right"><?= TRANS('TITLE'); ?></label>
                    <div class="form-group col-md-10">
                        <input type="text" class="form-control" id="title" name="title" value="<?= $row['title']; ?>" required />
                        <div class="invalid-feedback">
                            <?= TRANS('MANDATORY_FIELD'); ?>
                        </div>
                    </div>

                    <label for="notice" class="col-sm-2 col-md-2 col-form-label text-md-right"><?= TRANS('NOTICE'); ?></label>
                    <div class="form-group col-md-10">
                        <textarea class="form-control" id="notice" name="notice" required><?= toHtml($row['avisos']); ?></textarea>
                        <div class="invalid-feedback">
                            <?= TRANS('MANDATORY_FIELD'); ?>
                        </div>
                    </div>

                    <label for="type" class="col-md-2 col-form-label text-md-right"><?= TRANS('COL_TYPE'); ?></label>
                    <div class="form-group col-md-10">
                        <select class="form-control " id="type" name="type">
                            <option value=""><?= TRANS('SEL_TYPE'); ?></option>
                            <?php
                            foreach ($types as $key => $type) {
                            ?>
                                <option value="<?= $key; ?>" <?= ($key == $row['status'] ? ' selected' : ''); ?>><?= $type; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>



                    <label for="area" class="col-sm-2 col-md-2 col-form-label text-md-right"><?= TRANS('DESTINY_AREA'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2" id="area" name="area[]" required multiple="multiple">

                            <optgroup label="<?= TRANS('SERVICE_AREAS'); ?>" data-icon="fas fa-headset">
                                <?php
                                $areas = getAreas($conn, 0, 1, 1);
                                foreach ($areas as $area) {
                                    if ($isAdmin || in_array($area['sis_id'], $userAreas)) {
                                    ?>
                                        <option value="<?= $area['sis_id']; ?>" <?= (isIn($area['sis_id'], $row['area']) ? ' selected' : ''); ?>><?= $area['sistema']; ?></option>
                                    <?php
                                    }
                                }
                                ?>
                            </optgroup>

                            <optgroup label="<?= TRANS('REQUESTER_AREAS'); ?>" data-icon="fas fa-user">
                                <?php
                                $areas = getAreas($conn, 0, 1, 0);
                                foreach ($areas as $area) {
                                    if ($isAdmin || in_array($area['sis_id'], $userAreas)) {
                                    ?>
                                        <option value="<?= $area['sis_id']; ?>" <?= (isIn($area['sis_id'], $row['area']) ? ' selected' : ''); ?>><?= $area['sistema']; ?></option>
                                    <?php
                                    }
                                }
                                ?>
                            </optgroup>
                        </select>
                    </div>

                    <label for="expire_date" class="col-md-2 col-form-label text-md-right"><?= TRANS('ACTIVE_UNTIL'); ?></label>
                    <div class="form-group col-md-4">
                        <?php
                        $expire_date = (!empty($row['expire_date']) ? dateScreen($row['expire_date'], 1) : "");
                        ?>
                        <input type="text" class="form-control" name="expire_date" id="expire_date" value="<?= $expire_date; ?>" />
                        <input type="hidden" name="expire_date_copy" id="expire_date_copy" value="<?= $expire_date; ?>" />
                    </div>



                    <label class="col-md-2 col-form-label text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_RECURRENT_NOTICE'); ?>"><?= firstLetterUp(TRANS('COL_PERSISTENT')); ?></label>
                    <div class="form-group col-md-4 ">
                        <div class="switch-field">
                            <?php
                            $yesChecked = ($row['is_recurrent'] == 1 ? "checked" : "");
                            $noChecked = (!($row['is_recurrent'] == 1) ? "checked" : "");
                            ?>
                            <input type="radio" id="is_recurrent" name="is_recurrent" value="yes" <?= $yesChecked; ?> />
                            <label for="is_recurrent"><?= TRANS('YES'); ?></label>
                            <input type="radio" id="is_recurrent_no" name="is_recurrent" value="no" <?= $noChecked; ?> />
                            <label for="is_recurrent_no"><?= TRANS('NOT'); ?></label>
                        </div>
                    </div>

                    <label class="col-md-2 col-form-label text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('RE_SEND'); ?>"><?= firstLetterUp(TRANS('RE_SEND')); ?></label>
                    <div class="form-group col-md-4 ">
                        <div class="switch-field">
                            <?php
                            $yesChecked = "";
                            $noChecked = "checked";
                            ?>
                            <input type="radio" id="resend" name="resend" value="yes" <?= $yesChecked; ?> />
                            <label for="resend"><?= TRANS('YES'); ?></label>
                            <input type="radio" id="resend_no" name="resend" value="no" <?= $noChecked; ?> />
                            <label for="resend_no"><?= TRANS('NOT'); ?></label>
                        </div>
                    </div>

                    <label class="col-md-2 col-form-label text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('COL_STATUS'); ?>"><?= firstLetterUp(TRANS('COL_STATUS')); ?></label>
                    <div class="form-group col-md-4 ">
                        <div class="switch-field">
                            <?php
                            $yesChecked = ($row['is_active'] == 1 ? "checked" : "");
                            $noChecked = (!($row['is_active'] == 1) ? "checked" : "");
                            ?>
                            <input type="radio" id="active_status" name="active_status" value="yes" <?= $yesChecked; ?> />
                            <label for="active_status"><?= TRANS('ACTIVE_O'); ?></label>
                            <input type="radio" id="active_status_no" name="active_status" value="no" <?= $noChecked; ?> />
                            <label for="active_status_no"><?= TRANS('INACTIVE_O'); ?></label>
                        </div>
                    </div>


                    <input type="hidden" name="cod" value="<?= $_GET['cod']; ?>">
                    <input type="hidden" name="action" id="action" value="edit">

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
        }
        ?>
    </div>

    <script src="../../includes/javascript/funcoes-3.0.js"></script>
    <script src="../../includes/components/jquery/jquery.js"></script>
    <script src="../../includes/components/jquery/datetimepicker/build/jquery.datetimepicker.full.min.js"></script>
    <script src="../../includes/components/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../includes/components/bootstrap-select/dist/js/bootstrap-select.min.js"></script>
    <script type="text/javascript" charset="utf8" src="../../includes/components/datatables/datatables.js"></script>
    <script src="../../includes/components/summernote/summernote-bs4.js"></script>
    <script src="../../includes/components/summernote/lang/summernote-pt-BR.min.js"></script>
    <!-- <script src="../../includes/components/jodit/build/jodit.min.js"></script> -->
    <script type="text/javascript">
        $(function() {

            $('#table_lists').DataTable({
                paging: true,
                deferRender: true,
                order: [1,'asc'],
                columnDefs: [{
                    searchable: false,
                    orderable: false,
                    targets: ['editar', 'remover', 'shield']
                }],
                "language": {
                    "url": "../../includes/components/datatables/datatables.pt-br.json"
                }
            });

            $(function() {
                $('[data-toggle="popover"]').popover()
            });

            $('.popover-dismiss').popover({
                trigger: 'focus'
            });

            var bar = '<?php print $_SESSION['s_formatBarMural']; ?>';
            if ($('#notice').length > 0 && bar == 1) {
                $('#notice').summernote({
                    // placeholder: 'Hello Bootstrap 4',
                    toolbar: [
                        ['style', ['style']],
                        ['font', ['bold', 'underline', 'clear']],
                        ['fontname', ['fontname']],
                        ['fontsize', ['fontsize']],
                        ['color', ['color']],
                        ['para', ['ul', 'ol', 'paragraph']],
                        ['table', ['table']],
                        ['insert', ['link']],
                        ['view', ['fullscreen']],
                    ],
                    lang: 'pt-BR', // default: 'en-US'
                    tabsize: 2,
                    // height: 100,
                    height: 100, // set editor height
                    minHeight: null, // set minimum height of editor
                    maxHeight: null, // set maximum height of editor
                    // focus: true // set focus to editable area after initializing summernote
                });
            }


            // if ($('#notice').length > 0 && bar == 1) {
            //     var editor = new Jodit('#notice' ,{
            //         "language": "pt_br",
            //         "uploader": {
            //             "insertImageAsBase64URI": true
            //         },
            //         height: 200,
            //         "defaultActionOnPaste": "insert_as_text",
            //         // "defaultActionOnPaste": "insert_clear_html",
            //         // "enter": "BR",
            //         "askBeforePasteHTML": true,
            //         "askBeforePasteFromWord": true,
            //         // "toolbarSticky": false,

            //         "buttons": "bold,italic,underline,strikethrough,eraser,ul,ol,indent,outdent,left,font,fontsize,paragraph,image,copyformat,paste,hr,preview,fullsize",
            //     });
            // }


            /* Idioma global para os calendários */
            $.datetimepicker.setLocale('pt-BR');
            /* Para campos personalizados - bind pela classe*/
            $('#expire_date').datetimepicker({
                timepicker: false,
                format: 'd/m/Y',
                minDate: '+1970/01/02',
                /* tomorrow */
                lazyInit: true
            });


            if ($('#active_status').length > 0) {
                if (!$('#active_status').is(':checked')) {
                    $('#expire_date').prop('disabled', true);
                }
            }


            $('[name="active_status"]').on('change', function() {
                if (!$('#active_status').is(':checked')) {
                    $('#expire_date').prop('disabled', true);
                } else {
                    $('#expire_date').prop('disabled', false);
                }
            });

            $.fn.selectpicker.Constructor.BootstrapVersion = '4';
            $('.sel2').selectpicker({
                /* placeholder */
                title: "<?= TRANS('ALL_TREATERS_AREAS', '', 1); ?>",

                liveSearch: true,
                liveSearchNormalize: true,
                liveSearchPlaceholder: "<?= TRANS('BT_SEARCH', '', 1); ?>",
                noneResultsText: "<?= TRANS('NO_RECORDS_FOUND', '', 1); ?> {0}",
                style: "",
                styleBase: "form-control input-select-multi",
            }).on('loaded.bs.select', enableBoostrapSelectOptgroup);


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

                $("#idSubmit").prop("disabled", true);
                $.ajax({
                    url: './notices_process.php',
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
                    } else {
                        $('#divResult').html('');
                        $('input, select, textarea').removeClass('is-invalid');
                        $("#idSubmit").prop("disabled", false);
                        var url = '<?= $_SERVER['PHP_SELF'] ?>';
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


        function dateBrToDate(dateBr) {
            var pieces = dateBr.split("/");
            return pieces[2] + '-' + pieces[1] + '-' + pieces[0];
            // let date = new Date(pieces[2] + '-' + pieces[1] + '-' + pieces[0]);
            // return date;
        }

        function today() {
            var date = new Date();

            var year = date.getFullYear().toString();
            var month = (date.getMonth() + 101).toString().substring(1);
            var day = (date.getDate() + 100).toString().substring(1);

            return year + '-' + month + '-' + day;
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
                url: './notices_process.php',
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


        /* Função para habilitar a seleção de todos os itens de um optgroup ao clicar no label */
        function enableBoostrapSelectOptgroup() {

            let that = $(this).data('selectpicker'),
                inner = that.$menu.children('.inner');

            // remove default event
            inner.off('click', '.divider, .dropdown-header');
            // add new event
            inner.on('click', '.divider, .dropdown-header', function(e) {
                // original functionality
                e.preventDefault();
                e.stopPropagation();
                if (that.options.liveSearch) {
                    that.$searchbox.trigger('focus');
                } else {
                    that.$button.trigger('focus');
                }

                // extended functionality
                let position0 = that.isVirtual() ? that.selectpicker.view.position0 : 0,
                    clickedData = that.selectpicker.current.data[$(this).index() + position0];

                // copied parts from changeAll function
                let selected = null;
                for (let i = 0, data = that.selectpicker.current.data, len = data.length; i < len; i++) {
                    let element = data[i];
                    if (element.type === 'option' && element.optID === clickedData.optID) {
                        if (selected === null) {
                            selected = !element.selected;
                        }
                        element.option.selected = selected;
                    }
                }
                that.setOptionStatus();
                that.$element.triggerNative('change');
            });
        }
    </script>
</body>

</html>