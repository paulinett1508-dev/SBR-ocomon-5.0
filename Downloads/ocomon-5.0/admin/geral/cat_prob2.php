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

$areaAdmin = 0;
if (isset($_SESSION['s_area_admin']) && $_SESSION['s_area_admin'] == '1' && $_SESSION['s_nivel'] != '1') {
    $areaAdmin = 1;
}
$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 1);

$_SESSION['s_page_admin'] = $_SERVER['PHP_SELF'];

$qry_config = "SELECT * FROM config ";
$exec_config = $conn->query($qry_config);
$row_config = $exec_config->fetch();

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" type="text/css" href="../../includes/css/estilos.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/css/my_datatables.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap/custom.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/components/fontawesome/css/all.min.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/components/datatables/datatables.min.css" />

	<title>OcoMon&nbsp;<?= VERSAO; ?></title>
</head>

<body>
<?php
	// if ($areaAdmin) {
	// 	$auth->showHeader();
	// } else {
	// 	$auth->showHeader();
	// }
	?>
	
	<div class="container">
		<div id="idLoad" class="loading" style="display:none"></div>
	</div>

	<div id="divResult"></div>


	<div class="container-fluid">
		<h4 class="my-4"><i class="fas fa-tags text-secondary"></i>&nbsp;<?= TRANS('PROBLEM_TYPES_CATEGORIES'); ?>&nbsp;<span class="badge badge-secondary p-2" id="cat-label"><?= $row_config['conf_prob_tipo_2']; ?>&nbsp;<i class="fas fa-edit"></i></span></h4>
		
		<div class="modal" id="modal" tabindex="-1" style="z-index:9001!important">
			<div class="modal-dialog modal-xl">
				<div class="modal-content">
					<div id="divDetails">
					</div>
				</div>
			</div>
		</div>

		<div class="modal" id="modal-cat-label" tabindex="-1" style="z-index:9001!important">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
				<div id="divResultRename"></div>
                    <div class="modal-header bg-light">
                        <h5 class="modal-title" id="getit"><i class="fas fa-edit"></i>&nbsp;<?= TRANS('RENAME_ISSUE_CATEGORY'); ?></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <?= TRANS('RENAME'); ?>
                    </div>
                    <div class="row mx-2">
                        <div class="form-group col-md-12" id="divInputDetails">
							
                        </div>
                    </div>
                    <!-- Footer -->
                    <div class="modal-footer bg-light">
                        <button type="button" id="bt_rename" class="btn btn-primary" onclick="updateCatLabel()"><?= TRANS('RENAME'); ?></button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= TRANS('BT_CANCEL'); ?></button>
                    </div>
                </div>
            </div>
        </div>

		<?php
		if (isset($_SESSION['flash']) && !empty($_SESSION['flash'])) {
			echo $_SESSION['flash'];
			$_SESSION['flash'] = '';
        }
		
		$query = "SELECT * FROM prob_tipo_2 ";

		if (isset($_GET['cod'])) {
			$query .= " WHERE probt2_cod = ".(int)$_GET['cod']."  ";
		}
        $query .= " ORDER BY probt2_desc";
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

			<button class="btn btn-sm btn-primary" id="idBtIncluir" name="new"><?= TRANS("ACT_NEW"); ?></button>
			<button class="btn btn-sm btn-secondary " id="btReturnOrClose" name="btReturnOrClose"><?= TRANS("BT_RETURN"); ?></button><br /><br />
			<?php
			if ($registros == 0) {
				echo message('info', '', TRANS('NO_RECORDS_FOUND'), '', '', true);
			} else {

			?>
				<table id="table_lists" class="stripe hover order-column row-border" border="0" cellspacing="0" width="100%">

					<thead>
						<tr class="header">
							<td class="line issue_type"><?= TRANS('CATEGORY'); ?></td>
							<td class="line editar" width="10%"><?= TRANS('BT_EDIT'); ?></td>
							<td class="line remover" width="10%"><?= TRANS('BT_REMOVE'); ?></td>
						</tr>
					</thead>
					<tbody>
						<?php

						foreach ($resultado->fetchall() as $row) {

						?>
							<tr>
								<td class="line"><?= $row['probt2_desc']; ?></td>
								<td class="line"><button type="button" class="btn btn-secondary btn-sm" onclick="redirect('<?= $_SERVER['PHP_SELF']; ?>?action=edit&cod=<?= $row['probt2_cod']; ?>')"><?= TRANS('BT_EDIT'); ?></button></td>
								<td class="line"><button type="button" class="btn btn-danger btn-sm" onclick="confirmDeleteModal('<?= $row['probt2_cod']; ?>')"><?= TRANS('REMOVE'); ?></button></td>
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
				<?= csrf_input('csrf_cat_2'); ?>
				<div class="form-group row my-4">
					<label for="categoria" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CATEGORY'); ?></label>
					<div class="form-group col-md-10">
						<input type="text" class="form-control " id="categoria" name="categoria" required />
                    </div>
                    

					<div class="row w-100"></div>
					<div class="form-group col-md-8 d-none d-md-block">
					</div>
					<div class="form-group col-12 col-md-2 ">

						<input type="hidden" name="action" id="action" value="new">
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

			$row = $resultado->fetch();
		    ?>
			<h6><?= TRANS('BT_EDIT'); ?></h6>
			<form method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form">
				<?= csrf_input('csrf_cat_2'); ?>
				<div class="form-group row my-4">
					<label for="categoria" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CATEGORY'); ?></label>
					<div class="form-group col-md-10">
						<input type="text" class="form-control " id="categoria" name="categoria" value="<?= $row['probt2_desc']; ?>" required />
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
					targets: ['editar', 'remover']
				}],
				"language": {
					"url": "../../includes/components/datatables/datatables.pt-br.json"
				}
			});

			if ($('#categoria').length > 0) {
				$('#categoria').focus();
			}


			$("#cat-label").on("click", function(){
				openModalOption($(this).attr("id"));
			}).addClass("pointer");

			closeOrReturn();

			// $('#idBtReturn').on('click', function() {
			// 	let url = 'types_of_issues.php';
			// 	$(location).prop('href', url);
			// });

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
					url: './issues_cat_process.php',
					method: 'POST',
					data: $('#form').serialize() + '&table=prob_tipo_2&collumnID=probt2_cod&collumnName=probt2_desc',
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
						
						if (isPopup()) {
							window.opener.loadCat2();
						}
						
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
				url: './issues_cat_process.php',
				method: 'POST',
				data: {
					cod: id,
					table: 'prob_tipo_2',
					collumnID: 'probt2_cod',
					collumnName: 'probt2_desc',
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


        function openModalOption(id) {

            var loading = $(".loading");
			$(document).ajaxStart(function() {
				loading.show();
			});
			$(document).ajaxStop(function() {
				loading.hide();
			});

			$.ajax({
				url: './get_cat_label.php',
				method: 'POST',
				data: {
					cat_table: 'prob_tipo_2',
				},
				dataType: 'json',
			}).done(function(response) {
				let form = '<input class="form-control" type="text" name="cat_label" id="cat_label" value="' + response.current_label + '">';
				$('#divInputDetails').empty().html(form);
				$('#modal-cat-label').modal();
			});
        }

		
		function updateCatLabel() {

            var loading = $(".loading");
            $(document).ajaxStart(function() {
                loading.show();
            });
            $(document).ajaxStop(function() {
                loading.hide();
            });

            $.ajax({
                url: 'update_issues_cat_label.php',
                method: 'POST',
                dataType: 'json',
                data: {
                    'cat_table': 'prob_tipo_2',
                    'cat_label': $('#cat_label').val(),
                    'action': 'edit'
                },
            }).done(function(response) {
                if (!response.success) {
					if (response.field_id != "") {
                        $('#' + response.field_id).focus().addClass('is-invalid');
                    }
                    $('#divResultRename').html(response.message);
                } else {
                    $('#modal-cat-label').modal('hide');
					$('#cat-label').html(response.cat_label + ' <i class="fas fa-edit"></i>');
					$('#divResult').html(response.message);

					if (window.opener) {
						window.opener.loadCatLabel('prob_tipo_2');
					}
                }
            });
            return false;
        }


		function closeOrReturn (jumps = 1) {
			buttonValue ();
			$('.close-or-return').on('click', function(){
				if (isPopup()) {
					window.close();
				} else {
					window.history.back(jumps);
				}
			});

			$('#btReturnOrClose').on('click', function(){
				if (isPopup()) {
					window.close();
				} else {
					let url = 'types_of_issues_4.0.php';
					window.location.href = url;
				}
			});
		}

		function buttonValue () {
			if (isPopup()) {
				$('.close-or-return, #btReturnOrClose').text('<?= TRANS('BT_CLOSE'); ?>');
			}
		}
	</script>
</body>

</html>