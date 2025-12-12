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

$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 1);

$_SESSION['s_page_admin'] = $_SERVER['PHP_SELF'];

$conn = ConnectPDO::getInstance();

$config = getConfig($conn);
$configExt = getConfigValues($conn);

/* Não permitir desativação ou remoção do cliente padrão do sistema */
$systemClient = [1];

/* Chaves Definidas na tipagem no banco de dados */
$docTypes = array(
    'cnpj' => TRANS('CNPJ'),
    'cpf' => TRANS('CPF'),
    'outro' => TRANS('OTHER')
);

$maskTypes = array(
    'cnpj' => '\d{2}\.\d{3}\.\d{3}\/\d{4}\-\d{2}',
    'cpf' => '\d{3}\-\d{2}',
    'outro' => ''
);

$clientStatus = getClientsStatus($conn);

$clientTypes = getClientsTypes($conn);

$requesterAreas = getAreas($conn, 0, 1, 0);





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
    <link rel="stylesheet" type="text/css" href="../../includes/components/datatables/datatables.min.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap-select/dist/css/bootstrap-select.min.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/css/my_bootstrap_select.css" />

    <title>OcoMon&nbsp;<?= VERSAO; ?></title>
</head>

<body>


    <div class="container">
        <div id="idLoad" class="loading" style="display:none"></div>
    </div>

    <div id="divResult"></div>


    <div class="container-fluid">
        <!-- fas fa house-user -->
        <h5 class="my-4"><i class="fas fa-user-tie text-secondary"></i>&nbsp;<?= TRANS('CLIENTS'); ?></h5>
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

        $clients = getClients($conn, (isset($_GET['cod']) ? (int)$_GET['cod'] : null));

        $registros = count($clients);

        
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
                <table id="table_clients" class="stripe hover order-column row-border" border="0" cellspacing="0" width="100%">
                    <thead>
                        <tr class="header">
                            <td class="line fullname"><?= TRANS('COL_NAME'); ?></td>
                            <td class="line nickname"><?= TRANS('ALIAS'); ?></td>
                            <td class="line client_type"><?= TRANS('CLIENT_TYPE'); ?></td>
                            <td class="line document_type"><?= TRANS('DOCUMENT_TYPE'); ?></td>
                            <td class="line document_value"><?= TRANS('DOCUMENT_VALUE'); ?></td>
                            <td class="line contact_name"><?= TRANS('CONTACT'); ?></td>
                            <td class="line contact_email"><?= TRANS('CONTACT_EMAIL'); ?></td>
                            <td class="line contact_phone"><?= TRANS('CONTACT_PHONE'); ?></td>
                            <td class="line editar"><?= TRANS('BT_EDIT'); ?></td>
                            <td class="line remover"><?= TRANS('BT_REMOVE'); ?></td>
                        </tr>
                    </thead>
                    <?php
                    foreach ($clients as $client) {
                        ?>
                            <tr>
                                <td class="line"><?= $client['fullname']; ?></td>
                                <td class="line"><?= $client['nickname']; ?></td>
                                <?php
                                    $clientType = "";
                                    if (!empty($client['type'])) {
                                        $clientType = getClientsTypes($conn, $client['type'])['type_name'];
                                    }

                                    // $areaName = "";
                                    // if (!empty($client['area'])) {
                                    //     $areaName = getAreaInfo($conn, $client['area'])['area_name'];
                                    // }
                                ?>
                                <td class="line"><?= $clientType; ?></td>
                                <td class="line"><?= $client['document_type']; ?></td>
                                <td class="line"><?= $client['document_value']; ?></td>
                                <td class="line"><?= $client['contact_name']; ?></td>
                                <td class="line"><?= $client['contact_email']; ?></td>
                                <td class="line"><?= $client['contact_phone']; ?></td>
                                
                                <td class="line"><button type="button" class="btn btn-secondary btn-sm" onclick="redirect('<?= $_SERVER['PHP_SELF']; ?>?action=edit&cod=<?= $client['id']; ?>')"><?= TRANS('BT_EDIT'); ?></button></td>
                                <td class="line"><button type="button" class="btn btn-danger btn-sm" onclick="confirmDeleteModal('<?= $client['id']; ?>')"><?= TRANS('REMOVE'); ?></button></td>
                            </tr>
                        <?php
                    }
                    ?>
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

                    <label for="client_name" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CLIENT_NAME'); ?></label>
                    <div class="form-group col-md-4">
                        <input type="text" class="form-control" id="client_name" name="client_name" required />
                    </div>

                    <label for="nickname" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('ALIAS'); ?></label>
                    <div class="form-group col-md-4 ">
                        <input type="text" class="form-control " id="nickname" name="nickname" />
                    </div>

                    <label for="doc_type" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('DOCUMENT_TYPE'); ?></label>
                    <div class="form-group col-md-4 ">
                        <select class="form-control" id="doc_type" name="doc_type">
                            <?php
                                foreach ($docTypes as $key => $value) {
                                    ?>
                                        <option value="<?= $key; ?>"><?= $value; ?></option>
                                    <?php
                                }
                            ?>
                        </select>
                    </div>

                    <label for="document_number" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('DOCUMENT_VALUE'); ?></label>
                    <div class="form-group col-md-4 ">
                        <input type="text" class="form-control " id="document_number" name="document_number" />
                    </div>

                    <label for="contact_name" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CLIENT_CONTACT_NAME_1'); ?></label>
                    <div class="form-group col-md-4 ">
                        <input type="text" class="form-control " id="contact_name" name="contact_name" />
                    </div>

                    <label for="contact_email" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CLIENT_EMAIL_1'); ?></label>
                    <div class="form-group col-md-4 ">
                        <input type="email" class="form-control " id="contact_email" name="contact_email" />
                    </div>

                    <label for="contact_phone" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CLIENT_PHONE_1'); ?></label>
                    <div class="form-group col-md-4 ">
                        <input type="tel" class="form-control " id="contact_phone" name="contact_phone" />
                    </div>

                    <label for="contact_name_2" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CLIENT_CONTACT_NAME_2'); ?></label>
                    <div class="form-group col-md-4 ">
                        <input type="text" class="form-control " id="contact_name_2" name="contact_name_2" />
                    </div>

                    <label for="contact_email_2" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CLIENT_EMAIL_2'); ?></label>
                    <div class="form-group col-md-4 ">
                        <input type="email" class="form-control " id="contact_email_2" name="contact_email_2" />
                    </div>

                    <label for="contact_phone_2" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CLIENT_PHONE_2'); ?></label>
                    <div class="form-group col-md-4 ">
                        <input type="tel" class="form-control " id="contact_phone_2" name="contact_phone_2" />
                    </div>

                    <div class="w-100"></div>
                    <label for="client_address" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CLIENT_ADDRESS'); ?></label>
                    <div class="form-group col-md-10 ">
                        <textarea class="form-control " id="client_address" name="client_address"></textarea>
                    </div>

                    <label for="client_type" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CLIENT_TYPE'); ?></label>
                    <div class="form-group col-md-4 ">
                        <div class="input-group">
                            <select class="form-control bs-select" id="client_type" name="client_type">
                                <option value=""><?= TRANS('SEL_SELECT'); ?></option>
                                <?php
                                    foreach ($clientTypes as $type) {
                                        ?>
                                            <option value="<?= $type['id']; ?>"><?= $type['type_name']; ?></option>
                                        <?php
                                    }
                                ?>
                            </select>
                            <div class="input-group-append">
                                <div class="input-group-text manage_popups" data-location="client_types" data-params="action=new" title="<?= TRANS('NEW'); ?>" data-placeholder="<?= TRANS('NEW'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                    <i class="fas fa-plus"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- <label for="requester_area" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('REQUESTER_AREA'); ?></label>
                    <div class="form-group col-md-4 ">
                        <select class="form-control" id="requester_area" name="requester_area">
                            <option value=""><?= TRANS('SEL_SELECT'); ?></option>
                            <?php
                                foreach ($requesterAreas as $area) {
                                    ?>
                                        <option value="<?= $area['sis_id']; ?>"><?= $area['sistema']; ?></option>
                                    <?php
                                }
                            ?>
                        </select>
                    </div> -->

                    <label for="client_status" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_STATUS'); ?></label>
                    <div class="form-group col-md-4 ">
                        <div class="input-group">
                            <select class="form-control  bs-select" id="client_status" name="client_status">
                                <option value=""><?= TRANS('SEL_SELECT'); ?></option>
                                <?php
                                    foreach ($clientStatus as $status) {
                                        ?>
                                            <option value="<?= $status['id']; ?>"><?= $status['status_name']; ?></option>
                                        <?php
                                    }
                                ?>
                            </select>
                            <div class="input-group-append">
                                <div class="input-group-text manage_popups" data-location="client_status" data-params="action=new" title="<?= TRANS('NEW'); ?>" data-placeholder="<?= TRANS('NEW'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                    <i class="fas fa-plus"></i>
                                </div>
                            </div>
                        </div>
                    </div>



                    <label class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_INACTIVE_CLIENT'); ?>"><?= firstLetterUp(TRANS('ACTIVE_O')); ?></label>
					<div class="form-group col-md-4 ">
						<div class="switch-field">
							<?php
							$yesChecked = "checked";
							$noChecked = "";
							?>
							<input type="radio" id="client_active" name="client_active" value="yes" <?= $yesChecked; ?> />
							<label for="client_active"><?= TRANS('YES'); ?></label>
							<input type="radio" id="client_active_no" name="client_active" value="no" <?= $noChecked; ?> />
							<label for="client_active_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

                </div>


                <div class="form-group row my-4">
                    <?php

                    /* Campos personalizados - customizados */
					$fields_id = [];
                    $custom_fields = getCustomFields($conn, null, 'clients');
					if (!empty($custom_fields)) {

						$labelColSize = 2;
						$fieldColSize = 4;
						$fieldRowSize = 10;
					    ?>
						<h6 class="w-100 mt-5 ml-5 border-top p-4"><i class="fas fa-pencil-ruler text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('CUSTOM_FIELDS')); ?></h6>
						<?php
						foreach ($custom_fields as $row) {


                            $inlineAttributes = keyPairsToHtmlAttrs($row['field_attributes']);
                            $maskType = ($row['field_mask_regex'] ? 'regex' : 'mask');
                            $fieldMask = "data-inputmask-" . $maskType . "=\"" . $row['field_mask'] . "\"";
                        ?>

                            <?= ($row['field_type'] == 'textarea' ? '<div class="w-100"></div>'  : ''); ?>
                            <label for="<?= $row['field_name']; ?>" class="col-sm-<?= $labelColSize; ?> col-md-<?= $labelColSize; ?> col-form-label col-form-label-sm text-md-right " title="<?= $row['field_title']; ?>" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= $row['field_description']; ?>"><?= $row['field_label']; ?></label>
                            <div class="form-group col-md-<?= ($row['field_type'] == 'textarea' ? $fieldRowSize  : $fieldColSize); ?>">
                                <?php
                                if ($row['field_type'] == 'select') {
                                ?>
                                    <select class="form-control custom_field_select" name="<?= $row['field_name']; ?>" id="<?= $row['field_name']; ?>" <?= $inlineAttributes; ?>>
                                        <?php

                                        $options = [];
                                        $options = getCustomFieldOptionValues($conn, $row['id']);
                                        ?>
                                        <option value=""><?= TRANS('SEL_SELECT'); ?></option>
                                        <?php
                                        foreach ($options as $rowValues) {
                                        ?>
                                            <option value="<?= $rowValues['id']; ?>" <?= ($row['field_default_value'] == $rowValues['option_value'] ? " selected" : ""); ?>><?= $rowValues['option_value']; ?></option>
                                        <?php
                                        }
                                        ?>
                                    </select>
                                <?php
                                } elseif ($row['field_type'] == 'select_multi') {
                                ?>
                                    <select class="form-control custom_field_select_multi" name="<?= $row['field_name']; ?>[]" id="<?= $row['field_name']; ?>" multiple="multiple" placeholder="<?= $row['field_placeholder']; ?>" <?= $inlineAttributes; ?>>
                                        <?php
                                        $defaultSelections = explode(',', $row['field_default_value']);
                                        $options = [];
                                        $options = getCustomFieldOptionValues($conn, $row['id']);
                                        ?>
                                        <?php
                                        foreach ($options as $rowValues) {
                                        ?>
                                            <option value="<?= $rowValues['id']; ?>" <?= (in_array($rowValues['option_value'], $defaultSelections) ? ' selected' : ''); ?>><?= $rowValues['option_value']; ?></option>
                                        <?php
                                        }
                                        ?>
                                    </select>
                                <?php
                                } elseif ($row['field_type'] == 'number') {
                                ?>
                                    <input class="form-control custom_field_number" type="number" name="<?= $row['field_name']; ?>" id="<?= $row['field_name']; ?>" value="<?= $row['field_default_value'] ?? ''; ?>" placeholder="<?= $row['field_placeholder']; ?>" <?= $inlineAttributes; ?>>
                                <?php
                                } elseif ($row['field_type'] == 'checkbox') {
                                    $checked_checkbox = ($row['field_default_value'] ? " checked" : "");
                                ?>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input custom_field_checkbox" type="checkbox" name="<?= $row['field_name']; ?>" id="<?= $row['field_name']; ?>" <?= $checked_checkbox ?> <?= $inlineAttributes; ?>>
                                        <legend class="col-form-label col-form-label-sm"><?= $row['field_placeholder']; ?></legend>
                                    </div>
                                <?php
                                } elseif ($row['field_type'] == 'textarea') {
                                ?>
                                    <textarea class="form-control custom_field_textarea" name="<?= $row['field_name']; ?>" id="<?= $row['field_name']; ?>" placeholder="<?= $row['field_placeholder']; ?>" <?= $inlineAttributes; ?>><?= $row['field_default_value'] ?? ''; ?></textarea>
                                <?php
                                } elseif ($row['field_type'] == 'date') {
                                ?>
                                    <input class="form-control custom_field_date" type="text" name="<?= $row['field_name']; ?>" id="<?= $row['field_name']; ?>" value="<?= $row['field_default_value'] ?? ''; ?>" placeholder="<?= $row['field_placeholder']; ?>" <?= $inlineAttributes; ?> autocomplete="off">
                                <?php
                                } elseif ($row['field_type'] == 'time') {
                                ?>
                                    <input class="form-control custom_field_time" type="text" name="<?= $row['field_name']; ?>" id="<?= $row['field_name']; ?>" value="<?= $row['field_default_value'] ?? ''; ?>" placeholder="<?= $row['field_placeholder']; ?>" <?= $inlineAttributes; ?> autocomplete="off">
                                <?php
                                } elseif ($row['field_type'] == 'datetime') {
                                ?>
                                    <input class="form-control custom_field_datetime" type="text" name="<?= $row['field_name']; ?>" id="<?= $row['field_name']; ?>" value="<?= $row['field_default_value'] ?? ''; ?>" placeholder="<?= $row['field_placeholder']; ?>" <?= $inlineAttributes; ?> autocomplete="off">
                                <?php
                                } else {
                                ?>
                                    <input class="form-control custom_field_text" type="text" name="<?= $row['field_name']; ?>" id="<?= $row['field_name']; ?>" value="<?= $row['field_default_value'] ?? ''; ?>" placeholder="<?= $row['field_placeholder']; ?>" <?= $fieldMask; ?> <?= $inlineAttributes; ?> autocomplete="off">
                                <?php
                                }
                                ?>
                            </div>
					<?php
						} /* foreach */
					}
                    ?>

                </div>



                <div class="form-group row my-4">
                    <div class="row w-100"></div>
                    <div class="form-group col-md-8 d-none d-md-block">
                    </div>
                    <div class="form-group col-12 col-md-2 ">

                        <input type="hidden" name="client_type_selected" value="" id="client_type_selected" />
                        <input type="hidden" name="client_status_selected" value="" id="client_status_selected" />
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
        
            $hasCustomFields = hasCustomFields($conn, $clients['id'], 'clients_x_cfields');
        
        ?>
            <h6><?= TRANS('BT_EDIT'); ?></h6>
            <form method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form">
                <?= csrf_input(); ?>
                <div class="form-group row my-4">

                <label for="client_name" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CLIENT_NAME'); ?></label>
                    <div class="form-group col-md-4">
                        <input type="text" class="form-control" id="client_name" name="client_name" value="<?= $clients['fullname']; ?>" required />
                    </div>

                    <label for="nickname" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('ALIAS'); ?></label>
                    <div class="form-group col-md-4 ">
                        <input type="text" class="form-control " id="nickname" name="nickname" value="<?= $clients['nickname']; ?>"/>
                    </div>

                    <label for="doc_type" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('DOCUMENT_TYPE'); ?></label>
                    <div class="form-group col-md-4 ">
                        <select class="form-control" id="doc_type" name="doc_type">
                            <?php
                                foreach ($docTypes as $key => $value) {
                                    ?>
                                        <option value="<?= $key; ?>"
                                        <?= $key == $clients['document_type'] ? " selected" : ""; ?>
                                        ><?= $value; ?></option>
                                    <?php
                                }
                            ?>
                        </select>
                    </div>

                    <label for="document_number" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('DOCUMENT_VALUE'); ?></label>
                    <div class="form-group col-md-4 ">
                        <input type="text" class="form-control " id="document_number" name="document_number" value="<?= $clients['document_value']; ?>"/>
                    </div>

                    <label for="contact_name" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CLIENT_CONTACT_NAME_1'); ?></label>
                    <div class="form-group col-md-4 ">
                        <input type="text" class="form-control " id="contact_name" name="contact_name" value="<?= $clients['contact_name']; ?>"/>
                    </div>

                    <label for="contact_email" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CLIENT_EMAIL_1'); ?></label>
                    <div class="form-group col-md-4 ">
                        <input type="email" class="form-control " id="contact_email" name="contact_email" value="<?= $clients['contact_email']; ?>"/>
                    </div>

                    <label for="contact_phone" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CLIENT_PHONE_1'); ?></label>
                    <div class="form-group col-md-4 ">
                        <input type="tel" class="form-control " id="contact_phone" name="contact_phone" value="<?= $clients['contact_phone']; ?>"/>
                    </div>

                    <label for="contact_name_2" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CLIENT_CONTACT_NAME_2'); ?></label>
                    <div class="form-group col-md-4 ">
                        <input type="text" class="form-control " id="contact_name_2" name="contact_name_2" value="<?= $clients['contact_name_2']; ?>"/>
                    </div>

                    <label for="contact_email_2" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CLIENT_EMAIL_2'); ?></label>
                    <div class="form-group col-md-4 ">
                        <input type="email" class="form-control " id="contact_email_2" name="contact_email_2" value="<?= $clients['contact_email_2']; ?>"/>
                    </div>

                    <label for="contact_phone_2" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CLIENT_PHONE_2'); ?></label>
                    <div class="form-group col-md-4 ">
                        <input type="tel" class="form-control " id="contact_phone_2" name="contact_phone_2" value="<?= $clients['contact_phone_2']; ?>"/>
                    </div>

                    <div class="w-100"></div>
                    <label for="client_address" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CLIENT_ADDRESS'); ?></label>
                    <div class="form-group col-md-10 ">
                        <textarea class="form-control " id="client_address" name="client_address"><?= $clients['address'];?></textarea>
                    </div>

                    <label for="client_type" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CLIENT_TYPE'); ?></label>
                    <div class="form-group col-md-4 ">
                        <div class="input-group">
                            <select class="form-control bs-select" id="client_type" name="client_type">
                                <option value=""><?= TRANS('SEL_SELECT'); ?></option>
                                <?php
                                    foreach ($clientTypes as $type) {
                                        ?>
                                            <option value="<?= $type['id']; ?>"
                                            <?= ($type['id'] == $clients['type'] ? " selected" : ""); ?>
                                            ><?= $type['type_name']; ?></option>
                                        <?php
                                    }
                                ?>
                            </select>
                            <div class="input-group-append">
                                    <div class="input-group-text manage_popups" data-location="client_types" data-params="action=new" title="<?= TRANS('NEW'); ?>" data-placeholder="<?= TRANS('NEW'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                        <i class="fas fa-plus"></i>
                                    </div>
                            </div>
                        </div>
                    </div>

                    <!-- <label for="requester_area" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('REQUESTER_AREA'); ?></label>
                    <div class="form-group col-md-4 ">
                        <select class="form-control" id="requester_area" name="requester_area">
                            <option value=""><?= TRANS('SEL_SELECT'); ?></option>
                            <?php
                                foreach ($requesterAreas as $area) {
                                    ?>
                                        <option value="<?= $area['sis_id']; ?>"
                                        <?= ($area['sis_id'] == $clients['area'] ? " selected" : ""); ?>
                                        ><?= $area['sistema']; ?></option>
                                    <?php
                                }
                            ?>
                        </select>
                    </div> -->


                    <label for="client_status" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_STATUS'); ?></label>
                    <div class="form-group col-md-4 ">
                        <div class="input-group">
                            <select class="form-control bs-select" id="client_status" name="client_status">
                                <option value=""><?= TRANS('SEL_SELECT'); ?></option>
                                <?php
                                    foreach ($clientStatus as $status) {
                                        ?>
                                            <option value="<?= $status['id']; ?>"
                                            <?= ($status['id'] == $clients['status'] ? " selected" : ""); ?>
                                            ><?= $status['status_name']; ?></option>
                                        <?php
                                    }
                                ?>
                            </select>
                            <div class="input-group-append">
                                    <div class="input-group-text manage_popups" data-location="client_status" data-params="action=new" title="<?= TRANS('NEW'); ?>" data-placeholder="<?= TRANS('NEW'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                        <i class="fas fa-plus"></i>
                                    </div>
                            </div>
                        </div>
                    </div>

                    <label class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_INACTIVE_CLIENT'); ?>"><?= firstLetterUp(TRANS('ACTIVE_O')); ?></label>
					<div class="form-group col-md-4 ">
						<div class="switch-field">
							<?php
                            $editable = (in_array($clients['id'], $systemClient) ? " disabled" : "");
							$yesChecked = ($clients['is_active'] == 1 ? "checked" : "");
							$noChecked = (!($clients['is_active'] == 1) ? "checked" : "");
							?>
							<input type="radio" id="client_active" name="client_active" value="yes" <?= $yesChecked; ?> <?= $editable; ?>/>
							<label for="client_active"><?= TRANS('YES'); ?></label>
							<input type="radio" id="client_active_no" name="client_active" value="no" <?= $noChecked; ?> <?= $editable; ?>/>
							<label for="client_active_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

                </div>

                
                <?php

                $custom_fields = getCustomFields($conn, null, 'clients');

                if (!empty($custom_fields)) {
                        /* Campos personalizados */
                        $labelColSize = 2;
                        $fieldColSize = 4;
                        $fieldRowSize = 10;
                        
                        ?>
                            <div class="form-group row my-4">
                        
                            <div class="w-100"></div>
                            <h6 class="w-100 mt-5 ml-5 border-top p-4"><i class="fas fa-pencil-ruler text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('CUSTOM_FIELDS')); ?></h6>
                            <?php

                        foreach ($custom_fields as $cfield) {
                            
                            $maskType = ($cfield['field_mask_regex'] ? 'regex' : 'mask');
                            $fieldMask = "data-inputmask-" . $maskType . "=\"" . $cfield['field_mask'] . "\"";
                            $inlineAttributes = keyPairsToHtmlAttrs($cfield['field_attributes']);
                            $field_value = getClientCustomFields($conn, $clients['id'], $cfield['id']);

                            /* Controle de acordo com a opção global conf_cfield_only_opened */
                            // if (!empty($field_value['field_id'])) {
                            ?>
                                <?= ($cfield['field_type'] == 'textarea' ? '<div class="w-100"></div>'  : ''); ?>
                                <label for="<?= $cfield['field_name']; ?>" class="col-sm-<?= $labelColSize; ?> col-md-<?= $labelColSize; ?> col-form-label col-form-label-sm text-md-right " title="<?= $cfield['field_title']; ?>" data-pop="popover" data-placement="top" data-trigger="hover" data-content="<?= $cfield['field_description']; ?>"><?= $cfield['field_label']; ?></label>
                                <div class="form-group col-md-<?= ($cfield['field_type'] == 'textarea' ? $fieldRowSize  : $fieldColSize); ?>">
                                    <?php
                                    if ($cfield['field_type'] == 'select') {
                                    ?>
                                        <select class="form-control custom_field_select" name="<?= $cfield['field_name']; ?>" id="<?= $cfield['field_name']; ?>" <?= $inlineAttributes; ?>>
                                            <?php

                                            $options = [];
                                            $options = getCustomFieldOptionValues($conn, $cfield['id']);
                                            ?>
                                            <option value=""><?= TRANS('SEL_SELECT'); ?></option>
                                            <?php
                                            foreach ($options as $cfieldValues) {
                                            ?>
                                                <option value="<?= $cfieldValues['id']; ?>" <?= ($cfieldValues['id'] == $field_value['field_value_idx'] ? " selected" : ""); ?>><?= $cfieldValues['option_value']; ?></option>
                                            <?php
                                            }
                                            ?>
                                        </select>
                                    <?php
                                    } elseif ($cfield['field_type'] == 'select_multi') {
                                    ?>
                                        <select class="form-control custom_field_select_multi" name="<?= $cfield['field_name']; ?>[]" id="<?= $cfield['field_name']; ?>" multiple="multiple" <?= $inlineAttributes; ?>>
                                            <?php

                                            $options = [];
                                            $options = getCustomFieldOptionValues($conn, $cfield['id']);
                                            $defaultSelections = explode(',', $field_value['field_value_idx']);

                                            ?>
                                            <option value=""><?= TRANS('SEL_SELECT'); ?></option>
                                            <?php
                                            foreach ($options as $cfieldValues) {
                                            ?>
                                                <option value="<?= $cfieldValues['id']; ?>" <?= (in_array($cfieldValues['id'], $defaultSelections) ? ' selected' : ''); ?>><?= $cfieldValues['option_value']; ?></option>
                                            <?php
                                            }
                                            ?>
                                        </select>
                                    <?php
                                    } elseif ($cfield['field_type'] == 'number') {
                                    ?>
                                        <input class="form-control custom_field_number" type="number" name="<?= $cfield['field_name']; ?>" id="<?= $cfield['field_name']; ?>" value="<?= $field_value['field_value']; ?>" placeholder="<?= $cfield['field_placeholder']; ?>" <?= $inlineAttributes; ?>>
                                    <?php
                                    } elseif ($cfield['field_type'] == 'checkbox') {
                                        $checked_checkbox = ($field_value['field_value'] == "on" ? " checked" : "");
                                    ?>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input custom_field_checkbox" type="checkbox" name="<?= $cfield['field_name']; ?>" id="<?= $cfield['field_name']; ?>" <?= $checked_checkbox; ?> placeholder="<?= $cfield['field_placeholder']; ?>" <?= $inlineAttributes; ?>>
                                            <legend class="col-form-label col-form-label-sm"><?= $cfield['field_placeholder']; ?></legend>
                                        </div>
                                    <?php
                                    } elseif ($cfield['field_type'] == 'textarea') {
                                    ?>
                                        <textarea class="form-control custom_field_textarea" name="<?= $cfield['field_name']; ?>" id="<?= $cfield['field_name']; ?>" placeholder="<?= $cfield['field_placeholder']; ?>" <?= $inlineAttributes; ?>><?= $field_value['field_value']; ?></textarea>
                                    <?php
                                    } elseif ($cfield['field_type'] == 'date') {
                                    ?>
                                        <input class="form-control custom_field_date" type="text" name="<?= $cfield['field_name']; ?>" id="<?= $cfield['field_name']; ?>" value="<?= dateScreen($field_value['field_value'], 1); ?>" placeholder="<?= $cfield['field_placeholder']; ?>" <?= $inlineAttributes; ?> autocomplete="off">
                                    <?php
                                    } elseif ($cfield['field_type'] == 'time') {
                                    ?>
                                        <input class="form-control custom_field_time" type="text" name="<?= $cfield['field_name']; ?>" id="<?= $cfield['field_name']; ?>" value="<?= $field_value['field_value']; ?>" placeholder="<?= $cfield['field_placeholder']; ?>" <?= $inlineAttributes; ?> autocomplete="off">
                                    <?php
                                    } elseif ($cfield['field_type'] == 'datetime') {
                                    ?>
                                        <input class="form-control custom_field_datetime" type="text" name="<?= $cfield['field_name']; ?>" id="<?= $cfield['field_name']; ?>" value="<?= dateScreen($field_value['field_value'], 0, 'd/m/Y H:i'); ?>" placeholder="<?= $cfield['field_placeholder']; ?>" <?= $inlineAttributes; ?> autocomplete="off">
                                    <?php
                                    } else {
                                    ?>
                                        <input class="form-control custom_field_text" type="text" name="<?= $cfield['field_name']; ?>" id="<?= $cfield['field_name']; ?>" value="<?= $field_value['field_value']; ?>" placeholder="<?= $cfield['field_placeholder']; ?>" <?= $fieldMask; ?> <?= $inlineAttributes; ?> autocomplete="off">
                                    <?php
                                    }
                                    ?>
                                </div>

                        <?php
                                /* Fim do controle de acordo com a configuração global */
                            // }
                        }
                        ?>
                        <div class="w-100"></div>

                    </div>
                        <?php
                        /* Fim dos campos personalizados */
                    }
                ?>
                
                
                
                <div class="form-group row my-4">

                    <input type="hidden" name="client_type_selected" value="<?= $clients['type']; ?>" id="client_type_selected" />
                    <input type="hidden" name="client_status_selected" value="<?= $clients['status']; ?>" id="client_status_selected" />
                    <input type="hidden" name="cod" id="cod" value="<?= $_GET['cod']; ?>">
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
    <script src="../../includes/components/bootstrap/js/bootstrap.bundle.js"></script>
    <script src="../../includes/components/bootstrap-select/dist/js/bootstrap-select.min.js"></script>
    <script type="text/javascript" charset="utf8" src="../../includes/components/datatables/datatables.js"></script>
    <script src="../../includes/components/Inputmask-5.x/dist/jquery.inputmask.min.js"></script>
	<script src="../../includes/components/Inputmask-5.x/dist/bindings/inputmask.binding.js"></script>

    <script type="text/javascript">
        $(function() {

            $(function() {
                $('[data-toggle="popover"]').popover({
                    html: true
                });
            });

            $('.popover-dismiss').popover({
                trigger: 'focus'
            });


            maskDynamically('doc_type');
            $('#doc_type').on('change', function() {
                maskDynamically('doc_type');
            });

            $.fn.selectpicker.Constructor.BootstrapVersion = '4';
            $('.bs-select').selectpicker({
				/* placeholder */
				title: "<?= TRANS('SEL_SELECT', '', 1); ?>",
				liveSearch: true,
				liveSearchNormalize: true,
				liveSearchPlaceholder: "<?= TRANS('BT_SEARCH', '', 1); ?>",
				noneResultsText: "<?= TRANS('NO_RECORDS_FOUND', '', 1); ?> {0}",
				style: "",
				styleBase: "form-control ",
			});


            $('#table_clients').DataTable({
				paging: true,
				deferRender: true,
				// order: [0, 'DESC'],
				columnDefs: [{
					searchable: false,
					orderable: false,
					targets: ['editar', 'remover']
				}],
				"language": {
					"url": "../../includes/components/datatables/datatables.pt-br.json"
				}
			});

            $('.manage_popups').css('cursor', 'pointer').on('click', function() {
				var params = $(this).attr('data-params');
				var location = $(this).attr('data-location');
				
				loadInPopup(location, params);
			});

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
                    url: './clients_process.php',
                    method: 'POST',
                    data: $('#form').serialize(),
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


        }); /* Final do Listener */



        function loadClientsTypes(selected_id = '') {
            $.ajax({
                url: './get_clients_types.php',
                method: 'POST',
                data: {
                    cat_type: 1
                },
                dataType: 'json',
            }).done(function(response) {
                $('#client_type').empty().append('<option value=""><?= TRANS('SEL_TYPE'); ?></option>');
                for (var i in response) {

                    var option = '<option value="' + response[i].id + '">' + response[i].type_name + '</option>';
                    $('#client_type').append(option);
                    $('#client_type').selectpicker('refresh');


                    if (selected_id !== '') {
                        $('#client_type').val(selected_id).change();
                    } else
                    if ($('#client_type_selected').val() != '') {
                        $('#client_type').val($('#client_type_selected').val()).change();
                    }
                }
            });
        }


        function loadClientsStatus(selected_id = '') {
            $.ajax({
                url: './get_clients_status.php',
                method: 'POST',
                data: {
                    cat_type: 1
                },
                dataType: 'json',
            }).done(function(response) {
                $('#client_status').empty().append('<option value=""><?= TRANS('SEL_TYPE'); ?></option>');
                for (var i in response) {

                    var option = '<option value="' + response[i].id + '">' + response[i].status_name + '</option>';
                    $('#client_status').append(option);
                    $('#client_status').selectpicker('refresh');


                    if (selected_id !== '') {
                        $('#client_status').val(selected_id).change();
                    } else
                    if ($('#client_status_selected').val() != '') {
                        $('#client_status').val($('#client_status_selected').val()).change();
                    }
                }
            });
        }



        function maskDynamically(el) {
            var doc_type = $('#'+el).val();
            if (doc_type == 'cpf') {
                $('#document_number').inputmask({
                    mask: ['999.999.999-99'],
                    keepStatic: true
                });
            } else if (doc_type == 'cnpj') {
                $('#document_number').inputmask({
                    mask: ['99.999.999/9999-99'],
                    keepStatic: true
                });
            } else {
                $('#document_number').inputmask({
                    mask: null,
                    keepStatic: true
                });
            }
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
                url: './clients_process.php',
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

        function loadInPopup(pageBase, params) {
			let url = pageBase + '.php?' + params;
			x = window.open(url, '', 'dependent=yes,width=800,scrollbars=yes,statusbar=no,resizable=yes');
			x.moveTo(window.parent.screenX + 100, window.parent.screenY + 100);
		}


    </script>
</body>

</html>