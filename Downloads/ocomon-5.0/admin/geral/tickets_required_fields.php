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

use OcomonApi\WebControllers\FormFields;


$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 1);

$_SESSION['s_page_admin'] = $_SERVER['PHP_SELF'];

$entity = "ocorrencias";

$formfields = new FormFields($entity);
$fieldsNew = $formfields::getInstance($entity, "new");
$fieldsEdit = $formfields::getInstance($entity, "edit");
$fieldsClose = $formfields::getInstance($entity, "close");


?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../../includes/css/estilos.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/css/switch_radio.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap/custom.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/fontawesome/css/all.min.css" />

    <title>OcoMon&nbsp;<?= VERSAO; ?></title>
</head>

<body>

    <div class="container">
        <div id="idLoad" class="loading" style="display:none"></div>
    </div>

    <div id="divResult"></div>


    <div class="container-fluid">
        <h4 class="my-4"><i class="fas fa-chalkboard-teacher text-secondary"></i>&nbsp;<?= TRANS('TICKETS_REQUIRED_FIELDS'); ?></h4>
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

        if ((!isset($_GET['action'])) && !isset($_POST['submit'])) {

        ?>
            <button class="btn btn-sm btn-primary bt-update" id="idBtUpdate" name="edit"><?= TRANS("BT_EDIT"); ?></button><br /><br />
            <?php
            if (!$formfields->listEntity($entity)) {
                echo message('info', '', TRANS('NO_RECORDS_FOUND'), '', '', true);
            } else {
            ?>
				<div class="form-group row mt-1 mb-4">


					<h6 class="w-100 ml-5 p-4"><i class="fas fa-tasks text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('TEXT_TICKETS_REQUIRED_FIELDS')); ?></h6>


					<div class="w-100">
						<?= message('info', '', TRANS('MSG_TICKETS_REQUIRED_FIELDS'), '', '', true); ?>
					</div>

					<div class="form-group col-md-2 ">
					</div>
					<div class="form-group col-md-5 ">
                    <p class="bold"><?= TRANS("TICKETS_EDITING"); ?></p>
					</div>
					<div class="form-group col-md-5 ">
                    <p class="bold"><?= TRANS("TREATING_DONE_HEADER"); ?></p>
					</div>


					 <!-- Cliente -->
					 <label class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('CLIENT'); ?>"><?= firstLetterUp(TRANS('CLIENT')); ?></label>
					
                    <div class="form-group col-md-5 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($fieldsEdit->isRequired("client") ? "checked" : "");
							$noChecked = (!$fieldsEdit->isRequired("client") ? "checked" : "");
							?>
							<input type="radio" id="client_edit" name="client_edit" value="yes" <?= $yesChecked; ?> disabled/>
							<label for="client_edit"><?= TRANS('YES'); ?></label>
							<input type="radio" id="client_edit_no" name="client_edit" value="no" <?= $noChecked; ?> disabled/>
							<label for="client_edit_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>
                    <div class="form-group col-md-5 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($fieldsClose->isRequired("client") ? "checked" : "");
							$noChecked = (!$fieldsClose->isRequired("client") ? "checked" : "");
							?>
							<input type="radio" id="client_close" name="client_close" value="yes" <?= $yesChecked; ?> disabled/>
							<label for="client_close"><?= TRANS('YES'); ?></label>
							<input type="radio" id="client_close_no" name="client_close" value="no" <?= $noChecked; ?> disabled/>
							<label for="client_close_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>


                    <!-- Área responsável -->
					<label class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('RESPONSIBLE_AREA'); ?>"><?= firstLetterUp(TRANS('RESPONSIBLE_AREA')); ?></label>
					
                    <div class="form-group col-md-5 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($fieldsEdit->isRequired("area") ? "checked" : "");
							$noChecked = (!$fieldsEdit->isRequired("area") ? "checked" : "");
							?>
							<input type="radio" id="area_edit" name="area_edit" value="yes" <?= $yesChecked; ?> disabled/>
							<label for="area_edit"><?= TRANS('YES'); ?></label>
							<input type="radio" id="area_edit_no" name="area_edit" value="no" <?= $noChecked; ?> disabled/>
							<label for="area_edit_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>
                    <div class="form-group col-md-5 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($fieldsClose->isRequired("area") ? "checked" : "");
							$noChecked = (!$fieldsClose->isRequired("area") ? "checked" : "");
							?>
							<input type="radio" id="area_close" name="area_close" value="yes" <?= $yesChecked; ?> disabled/>
							<label for="area_close"><?= TRANS('YES'); ?></label>
							<input type="radio" id="area_close_no" name="area_close" value="no" <?= $noChecked; ?> disabled/>
							<label for="area_close_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

					

                    <!-- Tipo de problema -->
                    <label class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('ISSUE_TYPE'); ?>"><?= firstLetterUp(TRANS('ISSUE_TYPE')); ?></label>
					
                    <div class="form-group col-md-5 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($fieldsEdit->isRequired("issue") ? "checked" : "");
							$noChecked = (!$fieldsEdit->isRequired("issue") ? "checked" : "");
							?>
							<input type="radio" id="issue_edit" name="issue_edit" value="yes" <?= $yesChecked; ?> disabled/>
							<label for="issue_edit"><?= TRANS('YES'); ?></label>
							<input type="radio" id="issue_edit_no" name="issue_edit" value="no" <?= $noChecked; ?> disabled/>
							<label for="issue_edit_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>
                    <div class="form-group col-md-5 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($fieldsClose->isRequired("issue") ? "checked" : "");
							$noChecked = (!$fieldsClose->isRequired("issue") ? "checked" : "");
							?>
							<input type="radio" id="issue_close" name="issue_close" value="yes" <?= $yesChecked; ?> disabled/>
							<label for="issue_close"><?= TRANS('YES'); ?></label>
							<input type="radio" id="issue_close_no" name="issue_close" value="no" <?= $noChecked; ?> disabled/>
							<label for="issue_close_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>


                   <!-- Unidade -->
                   <label class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('COL_UNIT'); ?>"><?= firstLetterUp(TRANS('COL_UNIT')); ?></label>
					
                    <div class="form-group col-md-5 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($fieldsEdit->isRequired("unit") ? "checked" : "");
							$noChecked = (!$fieldsEdit->isRequired("unit") ? "checked" : "");
							?>
							<input type="radio" id="unit_edit" name="unit_edit" value="yes" <?= $yesChecked; ?> disabled/>
							<label for="unit_edit"><?= TRANS('YES'); ?></label>
							<input type="radio" id="unit_edit_no" name="unit_edit" value="no" <?= $noChecked; ?> disabled/>
							<label for="unit_edit_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>
                    <div class="form-group col-md-5 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($fieldsClose->isRequired("unit") ? "checked" : "");
							$noChecked = (!$fieldsClose->isRequired("unit") ? "checked" : "");
							?>
							<input type="radio" id="unit_close" name="unit_close" value="yes" <?= $yesChecked; ?> disabled/>
							<label for="unit_close"><?= TRANS('YES'); ?></label>
							<input type="radio" id="unit_close_no" name="unit_close" value="no" <?= $noChecked; ?> disabled/>
							<label for="unit_close_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

				
                   <!-- Etiqueta -->
                   <label class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('ASSET_TAG'); ?>"><?= firstLetterUp(TRANS('ASSET_TAG')); ?></label>
					
                    <div class="form-group col-md-5 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($fieldsEdit->isRequired("asset_tag") ? "checked" : "");
							$noChecked = (!$fieldsEdit->isRequired("asset_tag") ? "checked" : "");
							?>
							<input type="radio" id="asset_tag_edit" name="asset_tag_edit" value="yes" <?= $yesChecked; ?> disabled/>
							<label for="asset_tag_edit"><?= TRANS('YES'); ?></label>
							<input type="radio" id="asset_tag_edit_no" name="asset_tag_edit" value="no" <?= $noChecked; ?> disabled/>
							<label for="asset_tag_edit_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>
                    <div class="form-group col-md-5 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($fieldsClose->isRequired("asset_tag") ? "checked" : "");
							$noChecked = (!$fieldsClose->isRequired("asset_tag") ? "checked" : "");
							?>
							<input type="radio" id="asset_tag_close" name="asset_tag_close" value="yes" <?= $yesChecked; ?> disabled/>
							<label for="asset_tag_close"><?= TRANS('YES'); ?></label>
							<input type="radio" id="asset_tag_close_no" name="asset_tag_close" value="no" <?= $noChecked; ?> disabled/>
							<label for="asset_tag_close_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>



                   <!-- Contato -->
                   <label class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('CONTACT'); ?>"><?= firstLetterUp(TRANS('CONTACT')); ?></label>
					
                    <div class="form-group col-md-5 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($fieldsEdit->isRequired("contact") ? "checked" : "");
							$noChecked = (!$fieldsEdit->isRequired("contact") ? "checked" : "");
							?>
							<input type="radio" id="contact_edit" name="contact_edit" value="yes" <?= $yesChecked; ?> disabled/>
							<label for="contact_edit"><?= TRANS('YES'); ?></label>
							<input type="radio" id="contact_edit_no" name="contact_edit" value="no" <?= $noChecked; ?> disabled/>
							<label for="contact_edit_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>
                    <div class="form-group col-md-5 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($fieldsClose->isRequired("contact") ? "checked" : "");
							$noChecked = (!$fieldsClose->isRequired("contact") ? "checked" : "");
							?>
							<input type="radio" id="contact_close" name="contact_close" value="yes" <?= $yesChecked; ?> disabled/>
							<label for="contact_close"><?= TRANS('YES'); ?></label>
							<input type="radio" id="contact_close_no" name="contact_close" value="no" <?= $noChecked; ?> disabled/>
							<label for="contact_close_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>


                   <!-- Email de contato -->
                   <label class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('CONTACT_EMAIL'); ?>"><?= firstLetterUp(TRANS('CONTACT_EMAIL')); ?></label>
					
                    <div class="form-group col-md-5 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($fieldsEdit->isRequired("contact_email") ? "checked" : "");
							$noChecked = (!$fieldsEdit->isRequired("contact_email") ? "checked" : "");
							?>
							<input type="radio" id="contact_email_edit" name="contact_email_edit" value="yes" <?= $yesChecked; ?> disabled/>
							<label for="contact_email_edit"><?= TRANS('YES'); ?></label>
							<input type="radio" id="contact_email_edit_no" name="contact_email_edit" value="no" <?= $noChecked; ?> disabled/>
							<label for="contact_email_edit_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>
                    <div class="form-group col-md-5 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($fieldsClose->isRequired("contact_email") ? "checked" : "");
							$noChecked = (!$fieldsClose->isRequired("contact_email") ? "checked" : "");
							?>
							<input type="radio" id="contact_email_close" name="contact_email_close" value="yes" <?= $yesChecked; ?> disabled/>
							<label for="contact_email_close"><?= TRANS('YES'); ?></label>
							<input type="radio" id="contact_email_close_no" name="contact_email_close" value="no" <?= $noChecked; ?> disabled/>
							<label for="contact_email_close_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>


                   <!-- Telefone -->
                   <label class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('COL_PHONE'); ?>"><?= firstLetterUp(TRANS('COL_PHONE')); ?></label>
					
                    <div class="form-group col-md-5 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($fieldsEdit->isRequired("phone") ? "checked" : "");
							$noChecked = (!$fieldsEdit->isRequired("phone") ? "checked" : "");
							?>
							<input type="radio" id="phone_edit" name="phone_edit" value="yes" <?= $yesChecked; ?> disabled/>
							<label for="phone_edit"><?= TRANS('YES'); ?></label>
							<input type="radio" id="phone_edit_no" name="phone_edit" value="no" <?= $noChecked; ?> disabled/>
							<label for="phone_edit_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>
                    <div class="form-group col-md-5 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($fieldsClose->isRequired("phone") ? "checked" : "");
							$noChecked = (!$fieldsClose->isRequired("phone") ? "checked" : "");
							?>
							<input type="radio" id="phone_close" name="phone_close" value="yes" <?= $yesChecked; ?> disabled/>
							<label for="phone_close"><?= TRANS('YES'); ?></label>
							<input type="radio" id="phone_close_no" name="phone_close" value="no" <?= $noChecked; ?> disabled/>
							<label for="phone_close_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>



                   <!-- Departamento -->
                   <label class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('DEPARTMENT'); ?>"><?= firstLetterUp(TRANS('DEPARTMENT')); ?></label>
					
                    <div class="form-group col-md-5 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($fieldsEdit->isRequired("department") ? "checked" : "");
							$noChecked = (!$fieldsEdit->isRequired("department") ? "checked" : "");
							?>
							<input type="radio" id="department_edit" name="department_edit" value="yes" <?= $yesChecked; ?> disabled/>
							<label for="department_edit"><?= TRANS('YES'); ?></label>
							<input type="radio" id="department_edit_no" name="department_edit" value="no" <?= $noChecked; ?> disabled/>
							<label for="department_edit_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>
                    <div class="form-group col-md-5 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($fieldsClose->isRequired("department") ? "checked" : "");
							$noChecked = (!$fieldsClose->isRequired("department") ? "checked" : "");
							?>
							<input type="radio" id="department_close" name="department_close" value="yes" <?= $yesChecked; ?> disabled/>
							<label for="department_close"><?= TRANS('YES'); ?></label>
							<input type="radio" id="department_close_no" name="department_close" value="no" <?= $noChecked; ?> disabled/>
							<label for="department_close_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>


					<!-- ---------------------------------------- -->
					<div class="row w-100"></div>
					<div class="form-group col-md-10 d-none d-md-block">
					</div>
					<div class="form-group col-12 col-md-2 ">

						<input type="hidden" name="action" id="action" value="edit">
						<button class="btn btn-sm btn-primary bt-update" name="edit"><?= TRANS("BT_EDIT"); ?></button>
					</div>
					


				</div>             
            <?php
            }
        } else

		if ((isset($_GET['action']) && $_GET['action'] == "edit") && empty($_POST['submit'])) {

        ?>
			<form method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form">
				<?= csrf_input(); ?>
				<div class="form-group row my-4">


					<h6 class="w-100 mt-3 ml-5 p-4"><i class="fas fa-tasks text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('TEXT_TICKETS_REQUIRED_FIELDS')); ?></h6>

					<div class="w-100">
						<?= message('info', '', TRANS('MSG_TICKETS_REQUIRED_FIELDS'), '', '', true); ?>
					</div>
					
					<div class="form-group col-md-2 ">
					</div>
					
					<div class="form-group col-md-5 ">
                    <p class="bold"><?= TRANS("TICKETS_EDITING"); ?></p>
					</div>
					<div class="form-group col-md-5 ">
                    <p class="bold"><?= TRANS("TREATING_DONE_HEADER"); ?></p>
					</div>


					<!-- Cliente -->
					<label class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('CLIENT'); ?>"><?= firstLetterUp(TRANS('CLIENT')); ?></label>
					
                    <div class="form-group col-md-5 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($fieldsEdit->isRequired("client") ? "checked" : "");
							$noChecked = (!$fieldsEdit->isRequired("client") ? "checked" : "");
							?>
							<input type="radio" id="client_edit" name="client_edit" value="yes" <?= $yesChecked; ?> />
							<label for="client_edit"><?= TRANS('YES'); ?></label>
							<input type="radio" id="client_edit_no" name="client_edit" value="no" <?= $noChecked; ?> />
							<label for="client_edit_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>
                    <div class="form-group col-md-5 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($fieldsClose->isRequired("client") ? "checked" : "");
							$noChecked = (!$fieldsClose->isRequired("client") ? "checked" : "");
							?>
							<input type="radio" id="client_close" name="client_close" value="yes" <?= $yesChecked; ?> />
							<label for="client_close"><?= TRANS('YES'); ?></label>
							<input type="radio" id="client_close_no" name="client_close" value="no" <?= $noChecked; ?> />
							<label for="client_close_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>


                    <!-- Área responsável -->
					<label class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('RESPONSIBLE_AREA'); ?>"><?= firstLetterUp(TRANS('RESPONSIBLE_AREA')); ?></label>
					
                    <div class="form-group col-md-5 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($fieldsEdit->isRequired("area") ? "checked" : "");
							$noChecked = (!$fieldsEdit->isRequired("area") ? "checked" : "");
							?>
							<input type="radio" id="area_edit" name="area_edit" value="yes" <?= $yesChecked; ?> />
							<label for="area_edit"><?= TRANS('YES'); ?></label>
							<input type="radio" id="area_edit_no" name="area_edit" value="no" <?= $noChecked; ?> />
							<label for="area_edit_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>
                    <div class="form-group col-md-5 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($fieldsClose->isRequired("area") ? "checked" : "");
							$noChecked = (!$fieldsClose->isRequired("area") ? "checked" : "");
							?>
							<input type="radio" id="area_close" name="area_close" value="yes" <?= $yesChecked; ?> />
							<label for="area_close"><?= TRANS('YES'); ?></label>
							<input type="radio" id="area_close_no" name="area_close" value="no" <?= $noChecked; ?> />
							<label for="area_close_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

					

                    <!-- Tipo de problema -->
                    <label class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('ISSUE_TYPE'); ?>"><?= firstLetterUp(TRANS('ISSUE_TYPE')); ?></label>
					
                    <div class="form-group col-md-5 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($fieldsEdit->isRequired("issue") ? "checked" : "");
							$noChecked = (!$fieldsEdit->isRequired("issue") ? "checked" : "");
							?>
							<input type="radio" id="issue_edit" name="issue_edit" value="yes" <?= $yesChecked; ?> />
							<label for="issue_edit"><?= TRANS('YES'); ?></label>
							<input type="radio" id="issue_edit_no" name="issue_edit" value="no" <?= $noChecked; ?> />
							<label for="issue_edit_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>
                    <div class="form-group col-md-5 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($fieldsClose->isRequired("issue") ? "checked" : "");
							$noChecked = (!$fieldsClose->isRequired("issue") ? "checked" : "");
							?>
							<input type="radio" id="issue_close" name="issue_close" value="yes" <?= $yesChecked; ?> />
							<label for="issue_close"><?= TRANS('YES'); ?></label>
							<input type="radio" id="issue_close_no" name="issue_close" value="no" <?= $noChecked; ?> />
							<label for="issue_close_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>


                   <!-- Unidade -->
                   <label class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('COL_UNIT'); ?>"><?= firstLetterUp(TRANS('COL_UNIT')); ?></label>
					
                    <div class="form-group col-md-5 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($fieldsEdit->isRequired("unit") ? "checked" : "");
							$noChecked = (!$fieldsEdit->isRequired("unit") ? "checked" : "");
							?>
							<input type="radio" id="unit_edit" name="unit_edit" value="yes" <?= $yesChecked; ?> />
							<label for="unit_edit"><?= TRANS('YES'); ?></label>
							<input type="radio" id="unit_edit_no" name="unit_edit" value="no" <?= $noChecked; ?> />
							<label for="unit_edit_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>
                    <div class="form-group col-md-5 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($fieldsClose->isRequired("unit") ? "checked" : "");
							$noChecked = (!$fieldsClose->isRequired("unit") ? "checked" : "");
							?>
							<input type="radio" id="unit_close" name="unit_close" value="yes" <?= $yesChecked; ?> />
							<label for="unit_close"><?= TRANS('YES'); ?></label>
							<input type="radio" id="unit_close_no" name="unit_close" value="no" <?= $noChecked; ?> />
							<label for="unit_close_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

				
                   <!-- Etiqueta -->
                   <label class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('ASSET_TAG'); ?>"><?= firstLetterUp(TRANS('ASSET_TAG')); ?></label>
					
                    <div class="form-group col-md-5 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($fieldsEdit->isRequired("asset_tag") ? "checked" : "");
							$noChecked = (!$fieldsEdit->isRequired("asset_tag") ? "checked" : "");
							?>
							<input type="radio" id="asset_tag_edit" name="asset_tag_edit" value="yes" <?= $yesChecked; ?> />
							<label for="asset_tag_edit"><?= TRANS('YES'); ?></label>
							<input type="radio" id="asset_tag_edit_no" name="asset_tag_edit" value="no" <?= $noChecked; ?> />
							<label for="asset_tag_edit_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>
                    <div class="form-group col-md-5 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($fieldsClose->isRequired("asset_tag") ? "checked" : "");
							$noChecked = (!$fieldsClose->isRequired("asset_tag") ? "checked" : "");
							?>
							<input type="radio" id="asset_tag_close" name="asset_tag_close" value="yes" <?= $yesChecked; ?> />
							<label for="asset_tag_close"><?= TRANS('YES'); ?></label>
							<input type="radio" id="asset_tag_close_no" name="asset_tag_close" value="no" <?= $noChecked; ?> />
							<label for="asset_tag_close_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>



                   <!-- Contato -->
                   <label class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('CONTACT'); ?>"><?= firstLetterUp(TRANS('CONTACT')); ?></label>
					
                    <div class="form-group col-md-5 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($fieldsEdit->isRequired("contact") ? "checked" : "");
							$noChecked = (!$fieldsEdit->isRequired("contact") ? "checked" : "");
							?>
							<input type="radio" id="contact_edit" name="contact_edit" value="yes" <?= $yesChecked; ?> />
							<label for="contact_edit"><?= TRANS('YES'); ?></label>
							<input type="radio" id="contact_edit_no" name="contact_edit" value="no" <?= $noChecked; ?> />
							<label for="contact_edit_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>
                    <div class="form-group col-md-5 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($fieldsClose->isRequired("contact") ? "checked" : "");
							$noChecked = (!$fieldsClose->isRequired("contact") ? "checked" : "");
							?>
							<input type="radio" id="contact_close" name="contact_close" value="yes" <?= $yesChecked; ?> />
							<label for="contact_close"><?= TRANS('YES'); ?></label>
							<input type="radio" id="contact_close_no" name="contact_close" value="no" <?= $noChecked; ?> />
							<label for="contact_close_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>


                   <!-- Email de contato -->
                   <label class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('CONTACT_EMAIL'); ?>"><?= firstLetterUp(TRANS('CONTACT_EMAIL')); ?></label>
					
                    <div class="form-group col-md-5 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($fieldsEdit->isRequired("contact_email") ? "checked" : "");
							$noChecked = (!$fieldsEdit->isRequired("contact_email") ? "checked" : "");
							?>
							<input type="radio" id="contact_email_edit" name="contact_email_edit" value="yes" <?= $yesChecked; ?> />
							<label for="contact_email_edit"><?= TRANS('YES'); ?></label>
							<input type="radio" id="contact_email_edit_no" name="contact_email_edit" value="no" <?= $noChecked; ?> />
							<label for="contact_email_edit_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>
                    <div class="form-group col-md-5 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($fieldsClose->isRequired("contact_email") ? "checked" : "");
							$noChecked = (!$fieldsClose->isRequired("contact_email") ? "checked" : "");
							?>
							<input type="radio" id="contact_email_close" name="contact_email_close" value="yes" <?= $yesChecked; ?> />
							<label for="contact_email_close"><?= TRANS('YES'); ?></label>
							<input type="radio" id="contact_email_close_no" name="contact_email_close" value="no" <?= $noChecked; ?> />
							<label for="contact_email_close_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>


                   <!-- Telefone -->
                   <label class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('COL_PHONE'); ?>"><?= firstLetterUp(TRANS('COL_PHONE')); ?></label>
					
                    <div class="form-group col-md-5 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($fieldsEdit->isRequired("phone") ? "checked" : "");
							$noChecked = (!$fieldsEdit->isRequired("phone") ? "checked" : "");
							?>
							<input type="radio" id="phone_edit" name="phone_edit" value="yes" <?= $yesChecked; ?> />
							<label for="phone_edit"><?= TRANS('YES'); ?></label>
							<input type="radio" id="phone_edit_no" name="phone_edit" value="no" <?= $noChecked; ?> />
							<label for="phone_edit_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>
                    <div class="form-group col-md-5 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($fieldsClose->isRequired("phone") ? "checked" : "");
							$noChecked = (!$fieldsClose->isRequired("phone") ? "checked" : "");
							?>
							<input type="radio" id="phone_close" name="phone_close" value="yes" <?= $yesChecked; ?> />
							<label for="phone_close"><?= TRANS('YES'); ?></label>
							<input type="radio" id="phone_close_no" name="phone_close" value="no" <?= $noChecked; ?> />
							<label for="phone_close_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>



                   <!-- Departamento -->
                   <label class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('DEPARTMENT'); ?>"><?= firstLetterUp(TRANS('DEPARTMENT')); ?></label>
					
                    <div class="form-group col-md-5 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($fieldsEdit->isRequired("department") ? "checked" : "");
							$noChecked = (!$fieldsEdit->isRequired("department") ? "checked" : "");
							?>
							<input type="radio" id="department_edit" name="department_edit" value="yes" <?= $yesChecked; ?> />
							<label for="department_edit"><?= TRANS('YES'); ?></label>
							<input type="radio" id="department_edit_no" name="department_edit" value="no" <?= $noChecked; ?> />
							<label for="department_edit_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>
                    <div class="form-group col-md-5 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($fieldsClose->isRequired("department") ? "checked" : "");
							$noChecked = (!$fieldsClose->isRequired("department") ? "checked" : "");
							?>
							<input type="radio" id="department_close" name="department_close" value="yes" <?= $yesChecked; ?> />
							<label for="department_close"><?= TRANS('YES'); ?></label>
							<input type="radio" id="department_close_no" name="department_close" value="no" <?= $noChecked; ?> />
							<label for="department_close_no"><?= TRANS('NOT'); ?></label>
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
			</form>
        <?php
        }
        ?>
    </div>

    <script src="../../includes/javascript/funcoes-3.0.js"></script>
    <script src="../../includes/components/jquery/jquery.js"></script>
    <!-- <script type="text/javascript" src="../../includes/components/jquery/jquery-ui-1.12.1/jquery-ui.js"></script> -->
    <script src="../../includes/components/bootstrap/js/bootstrap.min.js"></script>
    <!-- <script type="text/javascript" charset="utf8" src="../../includes/components/datatables/datatables.js"></script> -->
    <script type="text/javascript">
        $(function() {


            $('#idSubmit').on('click', function(e) {
                e.preventDefault();
                var loading = $(".loading");
                $(document).ajaxStart(function() {
                    loading.show();
                });
                $(document).ajaxStop(function() {
                    loading.hide();
                });

                $.ajax({
                    url: './tickets_required_fields_process.php',
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

            $('.bt-update').on("click", function() {
                $('#idLoad').css('display', 'block');
                var url = '<?= $_SERVER['PHP_SELF'] ?>?action=edit';
                $(location).prop('href', url);
            });

            $('#bt-cancel').on('click', function() {
                var url = '<?= $_SERVER['PHP_SELF'] ?>';
                $(location).prop('href', url);
            });
        });


    
    </script>
</body>

</html>