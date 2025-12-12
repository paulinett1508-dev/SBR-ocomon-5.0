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


?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" type="text/css" href="../../includes/css/estilos.css" />
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
		<h4 class="my-4"><i class="fas fa-file-signature text-secondary"></i>&nbsp;<?= TRANS('RESPONSIBILITY_STATEMENTS'); ?></h4>
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
        
        $query = "SELECT * FROM asset_statements";

		if (isset($_GET['cod'])) {
			$query .= " WHERE id = ".(int)$_GET['cod']."  ";
		}
        $query .= " ORDER BY name";
		$resultado = $conn->query($query);
		$registros = $resultado->rowCount();

		if ((!isset($_GET['action'])) && !isset($_POST['submit'])) {

			if ($registros == 0) {
				echo message('info', '', TRANS('NO_RECORDS_FOUND'), '', '', true);
			} else {

			?>
				<table id="table_lists" class="stripe hover order-column row-border" border="0" cellspacing="0" width="100%">

					<thead>
						<tr class="header">
							<td class="line issue_type"><?= TRANS('COL_NAME'); ?></td>
							<td class="line issue_type"><?= TRANS('TITLE'); ?></td>
							<td class="line editar" width="10%"><?= TRANS('BT_EDIT'); ?></td>
						</tr>
					</thead>
					<tbody>
						<?php

						foreach ($resultado->fetchall() as $row) {

						?>
							<tr>
								<td class="line"><?= $row['name']; ?></td>
								<td class="line"><?= $row['title']; ?></td>
								<td class="line"><button type="button" class="btn btn-secondary btn-sm" onclick="redirect('<?= $_SERVER['PHP_SELF']; ?>?action=edit&cod=<?= $row['id']; ?>')"><?= TRANS('BT_EDIT'); ?></button></td>
							</tr>

						<?php
						}
						?>
					</tbody>
				</table>
			<?php
			}
		} else

		if ((isset($_GET['action']) && $_GET['action'] == "edit") && empty($_POST['submit'])) {

			$row = $resultado->fetch();
		    ?>
			<h6><?= TRANS('BT_EDIT'); ?></h6>
			<form method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form">
				<?= csrf_input(); ?>
				<div class="form-group row my-4">

                    <label for="field_name" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_NAME'); ?></label>
					<div class="form-group col-md-10">
						<input type="text" class="form-control " id="field_name" name="field_name" value="<?= $row['name']; ?>" readonly />
						
                    </div>
					<label for="field_header" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('MAIN_HEADER'); ?></label>
					<div class="form-group col-md-10">
						<input type="text" class="form-control " id="field_header" name="field_header" value="<?= $row['header']; ?>" />
						
                    </div>
                    
                    <label for="field_title" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('TITLE'); ?></label>
					<div class="form-group col-md-10">
						<input type="text" class="form-control " id="field_title" name="field_title" value="<?= $row['title']; ?>" />
                    </div>

                    <label for="paragraph_1_bfr_list" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('P1_BFR_LIST'); ?></label>
					<div class="form-group col-md-10">
						<textarea class="form-control " id="paragraph_1_bfr_list" name="paragraph_1_bfr_list"><?= $row['p1_bfr_list']; ?></textarea>
                    </div>

                    <label for="paragraph_2_bfr_list" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('P2_BFR_LIST'); ?></label>
					<div class="form-group col-md-10">
						<textarea class="form-control " id="paragraph_2_bfr_list" name="paragraph_2_bfr_list"><?= $row['p2_bfr_list']; ?></textarea>
                    </div>

                    <label for="paragraph_3_bfr_list" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('P3_BFR_LIST'); ?></label>
					<div class="form-group col-md-10">
						<textarea class="form-control " id="paragraph_3_bfr_list" name="paragraph_3_bfr_list"><?= $row['p3_bfr_list']; ?></textarea>
                    </div>

                    <label for="paragraph_1_aft_list" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('P1_AFT_LIST'); ?></label>
					<div class="form-group col-md-10">
						<textarea class="form-control " id="paragraph_1_aft_list" name="paragraph_1_aft_list"><?= $row['p1_aft_list']; ?></textarea>
                    </div>

                    <label for="paragraph_2_aft_list" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('P2_AFT_LIST'); ?></label>
					<div class="form-group col-md-10">
						<textarea class="form-control " id="paragraph_2_aft_list" name="paragraph_2_aft_list"><?= $row['p2_aft_list']; ?></textarea>
                    </div>

                    <label for="paragraph_3_aft_list" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('P3_AFT_LIST'); ?></label>
					<div class="form-group col-md-10">
						<textarea class="form-control " id="paragraph_3_aft_list" name="paragraph_3_aft_list"><?= $row['p3_aft_list']; ?></textarea>
                    </div>


					<div class="row w-100"></div>
					<div class="form-group col-md-8 d-none d-md-block">
					</div>
					<div class="form-group col-12 col-md-2 ">
                        <input type="hidden" name="cod" value="<?= (int)$_GET['cod']; ?>">
                        <input type="hidden" name="action" id="action" value="edit">
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
	<!-- <script type="text/javascript" src="../../includes/components/jquery/jquery-ui-1.12.1/jquery-ui.js"></script> -->
	<script src="../../includes/components/bootstrap/js/bootstrap.min.js"></script>
	<script type="text/javascript" charset="utf8" src="../../includes/components/datatables/datatables.js"></script>
	<script type="text/javascript">
		$(function() {

			$('#table_lists').DataTable({
				paging: true,
				deferRender: true,
				columnDefs: [{
					searchable: false,
					orderable: false,
					targets: ['editar']
				}],
				"language": {
					"url": "../../includes/components/datatables/datatables.pt-br.json"
				}
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
					url: './responsibility_statements_process.php',
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

			$('#bt-cancel').on('click', function() {
				var url = '<?= $_SERVER['PHP_SELF'] ?>';
				$(location).prop('href', url);
			});
		});


	</script>
</body>

</html>