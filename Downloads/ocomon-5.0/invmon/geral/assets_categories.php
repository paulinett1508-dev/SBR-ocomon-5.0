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

$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 2, 2);

$_SESSION['s_page_invmon'] = $_SERVER['PHP_SELF'];

$window = (isset($_GET['window']) && !empty($_GET['window']) ? $_GET['window'] : "");
$prefix = "_" . $_SERVER['PHP_SELF'];


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
	<link rel="stylesheet" type="text/css" href="../../includes/components/datatables/datatables.min.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/css/my_datatables.css" />
	
	<title>OcoMon&nbsp;<?= VERSAO; ?></title>
</head>

<body>
    
	<div class="container">
		<div id="idLoad" class="loading" style="display:none"></div>
	</div>

	<div id="divResult"></div>


	<div class="container-fluid">
		<h4 class="my-4"><i class="fas fa-tag text-secondary"></i>&nbsp;<?= TRANS('ASSETS_CATEGORIES'); ?></h4>
		<div class="modal" id="modal" tabindex="-1" style="z-index:9001!important">
			<div class="modal-dialog modal-xl">
				<div class="modal-content-assets-categories">
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
        
        
		$categories = (isset($_GET['cod']) ? getAssetsCategories($conn, $_GET['cod']) : getAssetsCategories($conn));
		$registros = count($categories);

		$profiles = getAssetsProfiles($conn);

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

				<input type="hidden" name="trans_remove" id="trans_remove" value="<?= TRANS('REMOVE'); ?>">
				<input type="hidden" name="trans_bt_close" id="trans_bt_close" value="<?= TRANS('BT_CLOSE'); ?>">
				<table id="table_lists" class="stripe hover order-column row-border" border="0" cellspacing="0" width="100%">

					<thead>
						<tr class="header">
							<td class="line issue_type"><?= TRANS('CATEGORY'); ?></td>
							<td class="line issue_type"><?= TRANS('DESCRIPTION'); ?></td>
							<td class="line category_digital"><?= TRANS('CATEGORY_DIGITAL'); ?></td>
							<td class="line category_product"><?= TRANS('CATEGORY_PRODUCT'); ?></td>
							<td class="line bgcolor"><?= TRANS('COL_BG_COLOR'); ?></td>
							<td class="line textcolor"><?= TRANS('FONT_COLOR'); ?></td>
							<td class="line issue_type"><?= TRANS('FIELD_PROFILE'); ?></td>
							<td class="line editar" width="10%"><?= TRANS('BT_EDIT'); ?></td>
							<td class="line remover" width="10%"><?= TRANS('BT_REMOVE'); ?></td>
						</tr>
					</thead>
					<tbody>
						<?php

						foreach ($categories as $row) {

							$is_digital = ($row['cat_is_digital'] ? '<span class="text-success"><i class="fas fa-check"></i></span>' : '');
							$is_product = ($row['cat_is_product'] ? '<span class="text-success"><i class="fas fa-check"></i></span>' : '');

							$profile = '';
							if (!empty($row['cat_default_profile'])) {
								$profile = getAssetsProfiles($conn, $row['cat_default_profile']);
							}
						    ?>
							<tr>
								<td class="line"><?= $row['cat_name']; ?></td>
								<td class="line"><?= $row['cat_description']; ?></td>
								<td class="line"><?= $is_digital; ?></td>
								<td class="line"><?= $is_product; ?></td>
								<td class="line"><span class="badge" style="border: 1px solid gray; background: <?= $row['cat_bgcolor']; ?>">&nbsp;&nbsp;&nbsp;</span></td>
								<td class="line"><span class="badge" style="border: 1px solid gray; background: <?= $row['cat_textcolor']; ?>">&nbsp;&nbsp;&nbsp;</span></td>
								<td class="line"><?= $profile['profile_name'] ?? ''; ?></td>
								<td class="line"><button type="button" class="btn btn-secondary btn-sm" onclick="redirect('<?= $_SERVER['PHP_SELF']; ?>?action=edit&cod=<?= $row['id']; ?>')"><?= TRANS('BT_EDIT'); ?></button></td>
								<td class="line"><button type="button" class="btn btn-danger btn-sm" onclick="confirmDeleteModal('<?= $row['id']; ?>')"><?= TRANS('REMOVE'); ?></button></td>
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
			<form method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="formAssetsCategories">
				<?= csrf_input('csrf_assets_categories'); ?>
				
				<div class="form-group row my-4">
					
					<!-- Digital -->
					<label class="col-md-2 col-form-label text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_CATEGORY_DIGITAL'); ?>"><?= firstLetterUp(TRANS('CATEGORY_DIGITAL')); ?></label>
					<div class="form-group col-md-2 ">
						<div class="switch-field">
							<?php
							$yesChecked = "";
							$noChecked = "checked";
							?>
							<input type="radio" id="is_digital" name="is_digital" value="yes" <?= $yesChecked; ?> />
							<label for="is_digital"><?= TRANS('YES'); ?></label>
							<input type="radio" id="is_digital_no" name="is_digital" value="no" <?= $noChecked; ?> />
							<label for="is_digital_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

					<!-- Produto -->
					<label class="col-md-2 col-form-label text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_CATEGORY_PRODUCT'); ?>"><?= firstLetterUp(TRANS('CATEGORY_PRODUCT')); ?></label>
					<div class="form-group col-md-2 ">
						<div class="switch-field">
							<?php
							$yesChecked = "";
							$noChecked = "checked";
							?>
							<input type="radio" id="is_product" name="is_product" value="yes" <?= $yesChecked; ?> />
							<label for="is_product"><?= TRANS('YES'); ?></label>
							<input type="radio" id="is_product_no" name="is_product" value="no" <?= $noChecked; ?> />
							<label for="is_product_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

					<div class="w-100"></div>
					<!-- Nome da categoria -->
					<label for="cat_name" class="col-md-2 col-form-label  text-md-right"><?= TRANS('CATEGORY'); ?></label>
					<div class="form-group col-md-10">
						<input type="text" class="form-control " id="cat_name" name="cat_name" required />
                    </div>
					
                    <label for="cat_description" class="col-md-2 col-form-label  text-md-right"><?= TRANS('DESCRIPTION'); ?></label>
					<div class="form-group col-md-10">
						<textarea class="form-control" id="cat_description" name="cat_description" rows="3"></textarea>
                    </div>

                    <label for="cat_default_profile" class="col-md-2 col-form-label  text-md-right"><?= TRANS('FIELD_PROFILE'); ?></label>
					<div class="form-group col-md-10">
						<select class="form-control " id="cat_default_profile" name="cat_default_profile">
							<option value=""><?= TRANS('SEL_SELECT'); ?></option>
							<?php
								// $profiles = getAssetsProfiles($conn);
								foreach ($profiles as $profile) {
									?>
									<option value="<?= $profile['id']; ?>"><?= $profile['profile_name']; ?></option>
									<?php
								}
							?>
						</select>
					</div>

					<label for="bgcolor" class="col-md-2 col-form-label text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_CATEGORY_BGCOLOR'); ?>"><?= TRANS('COL_BG_COLOR'); ?></label>
					<div class="form-group col-md-10">
						<input type="color" class="form-control " id="bgcolor" name="bgcolor" value="#17A2B8" required />
                    </div>

                    <label for="textcolor" class="col-md-2 col-form-label text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_CATEGORY_TEXTCOLOR'); ?>"><?= TRANS('FONT_COLOR'); ?></label>
					<div class="form-group col-md-10">
						<input type="color" class="form-control " id="textcolor" name="textcolor" value="#FFFFFF" required />
                    </div>
					


					<div class="row w-100"></div>
					<div class="form-group col-md-8 d-none d-md-block">
					</div>
					<div class="form-group col-12 col-md-2 ">

						<input type="hidden" name="action" id="action" value="new">
						
						<!-- Inputs que passarão valores para o javascript -->
						<input type="hidden" name="window" id="window" value="<?= $window ?>">
						<input type="hidden" name="prefix" id="prefix" value="<?= $prefix ?>">
						<input type="hidden" name="php_self" id="php_self" value="<?= $_SERVER['PHP_SELF'] ?>">
						<input type="hidden" name="trans_remove" id="trans_remove" value="<?= TRANS('REMOVE'); ?>">
						<input type="hidden" name="trans_bt_close" id="trans_bt_close" value="<?= TRANS('BT_CLOSE'); ?>">
						
						<button type="submit" id="idSubmit" name="submit" class="btn btn-primary btn-block"><?= TRANS('BT_OK'); ?></button>
					</div>
					<div class="form-group col-12 col-md-2">
						<button type="reset" class="btn btn-secondary btn-block close-or-return"><?= TRANS('BT_CANCEL'); ?></button>
					</div>


				</div>
			</form>
		<?php
		} else

		if ((isset($_GET['action']) && $_GET['action'] == "edit") && empty($_POST['submit'])) {

			$row = $categories;
		    ?>
			<h6><?= TRANS('EDITION'); ?></h6>
			<form method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="formAssetsCategories">
			<?= csrf_input('csrf_assets_categories'); ?>
				<div class="form-group row my-4">

					<!-- Digital -->
					<label class="col-md-2 col-form-label text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_CATEGORY_DIGITAL'); ?>"><?= firstLetterUp(TRANS('CATEGORY_DIGITAL')); ?></label>
					<div class="form-group col-md-2 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($row['cat_is_digital'] == 1 ? "checked" : "");
							$noChecked = (!($row['cat_is_digital'] == 1) ? "checked" : "");
							?>
							<input type="radio" id="is_digital" name="is_digital" value="yes" <?= $yesChecked; ?> />
							<label for="is_digital"><?= TRANS('YES'); ?></label>
							<input type="radio" id="is_digital_no" name="is_digital" value="no" <?= $noChecked; ?> />
							<label for="is_digital_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

					<!-- Produto -->
					<label class="col-md-2 col-form-label text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_CATEGORY_PRODUCT'); ?>"><?= firstLetterUp(TRANS('CATEGORY_PRODUCT')); ?></label>
					<div class="form-group col-md-2 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($row['cat_is_product'] == 1 ? "checked" : "");
							$noChecked = (!($row['cat_is_product'] == 1) ? "checked" : "");
							?>
							<input type="radio" id="is_product" name="is_product" value="yes" <?= $yesChecked; ?> />
							<label for="is_product"><?= TRANS('YES'); ?></label>
							<input type="radio" id="is_product_no" name="is_product" value="no" <?= $noChecked; ?> />
							<label for="is_product_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>

					<div class="w-100"></div>

					<!-- Nome da categoria -->
					<label for="cat_name" class="col-md-2 col-form-label  text-md-right"><?= TRANS('CATEGORY'); ?></label>
					<div class="form-group col-md-10">
						<input type="text" class="form-control " id="cat_name" name="cat_name" value="<?= $row['cat_name']; ?>" required />
                    </div>
                    
                    <label for="cat_description" class="col-md-2 col-form-label  text-md-right"><?= TRANS('DESCRIPTION'); ?></label>
					<div class="form-group col-md-10">
						<textarea class="form-control" id="cat_description" name="cat_description" rows="3"><?= $row['cat_description']; ?></textarea>
                    </div>

					<!-- Formulário/perfil de cadastro -->
					<label for="cat_default_profile" class="col-md-2 col-form-label  text-md-right"><?= TRANS('FIELD_PROFILE'); ?></label>
					<div class="form-group col-md-10">
						<select class="form-control " id="cat_default_profile" name="cat_default_profile">
							<option value=""><?= TRANS('SEL_SELECT'); ?></option>
							<?php
								// $profiles = getAssetsProfiles($conn);
								foreach ($profiles as $profile) {
									?>
									<option value="<?= $profile['id']; ?>"
									<?= ($profile['id'] == $row['cat_default_profile'] ? " selected" : ""); ?>
									><?= $profile['profile_name']; ?></option>
									<?php
								}
							?>
						</select>
					</div>

					<label for="bgcolor" class="col-md-2 col-form-label text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_CATEGORY_BGCOLOR'); ?>"><?= TRANS('COL_BG_COLOR'); ?></label>
					<div class="form-group col-md-10">
						<input type="color" class="form-control " id="bgcolor" name="bgcolor" value="<?= $row['cat_bgcolor']; ?>" required />
                    </div>

                    <label for="textcolor" class="col-md-2 col-form-label text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_CATEGORY_TEXTCOLOR'); ?>"><?= TRANS('FONT_COLOR'); ?></label>
					<div class="form-group col-md-10">
						<input type="color" class="form-control " id="textcolor" name="textcolor" value="<?= $row['cat_textcolor']; ?>" required />
                    </div>

					

					<div class="row w-100"></div>
					<div class="form-group col-md-8 d-none d-md-block">
					</div>
					<div class="form-group col-12 col-md-2 ">
                        <input type="hidden" name="cod" value="<?= $_GET['cod']; ?>">
                        <input type="hidden" name="action" id="action" value="edit">

						<!-- Inputs que passarão valores para o javascript -->
						<input type="hidden" name="window" id="window" value="<?= $window ?>">
						<input type="hidden" name="prefix" id="prefix" value="<?= $prefix ?>">
						<input type="hidden" name="php_self" id="php_self" value="<?= $_SERVER['PHP_SELF'] ?>">
						<input type="hidden" name="trans_remove" id="trans_remove" value="<?= TRANS('REMOVE'); ?>">
						<input type="hidden" name="trans_bt_close" id="trans_bt_close" value="<?= TRANS('BT_CLOSE'); ?>">
						
						<button type="submit" id="idSubmit" name="submit" value="edit" class="btn btn-primary btn-block"><?= TRANS('BT_OK'); ?></button>
					</div>
					<div class="form-group col-12 col-md-2">
						<button type="reset" class="btn btn-secondary btn-block close-or-return" ><?= TRANS('BT_CANCEL'); ?></button>
					</div>
				</div>
			</form>
		<?php
		}
		?>
	</div>


	<script src="../../includes/javascript/funcoes-3.0.js"></script>
	<script src="../../includes/components/jquery/jquery.js"></script>
	<script src="../../includes/components/bootstrap/js/bootstrap.bundle.min.js"></script>
	<script type="text/javascript" charset="utf8" src="../../includes/components/datatables/datatables.js"></script>
	<script type="text/javascript" charset="utf8" src="./js/assets_categories.js"></script>

</body>

</html>