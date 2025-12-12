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

$config = getConfig($conn);
$hasFiles = 0;

$type = "";


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
		<h4 class="my-4"><i class="fas fa-book text-secondary"></i>&nbsp;<?= TRANS('TTL_ADMIN_DOC_CAD'); ?></h4>
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

        $query = "SELECT mat.* , marc.* \nFROM materiais AS mat LEFT JOIN marcas_comp as marc ON mat.mat_modelo_equip = marc.marc_cod WHERE 1 = 1 ";

        if (isset($_GET['model_id'])) {
            $query.= " AND mat.mat_modelo_equip = '".(int)$_GET['model_id']."' ";
		}

		if (isset($_GET['cod'])) {
            $query.= " AND mat.mat_cod = ".(int)$_GET['cod']." ";
		}
        $query .=" ORDER BY mat_nome";
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

			<button class="btn btn-sm btn-primary" id="idBtIncluir" name="new"><?= TRANS("ACT_NEW"); ?></button><br /><br />
			
			<?php
			if ($registros == 0) {
				echo message('info', '', TRANS('NO_RECORDS_FOUND'), '', '', true);
			} else {

			?>
				<table id="table_lists" class="stripe hover order-column row-border" border="0" cellspacing="0" width="100%">

					<thead>
						<tr class="header">
							<td class="line col_description"><?= TRANS('DESCRIPTION'); ?></td>
							<td class="line col_model"><?= TRANS('COL_MODEL_EQUIP'); ?></td>
							<td class="line col_model"><?= TRANS('COL_QTD'); ?></td>
							<td class="line col_model"><?= TRANS('BOX'); ?></td>
							<td class="line col_model"><?= TRANS('COL_OBS'); ?></td>
							<td class="line editar" width="10%"><?= TRANS('BT_EDIT'); ?></td>
							<td class="line remover" width="10%"><?= TRANS('BT_REMOVE'); ?></td>
						</tr>
					</thead>
					<tbody>
						<?php

						foreach ($resultado->fetchall() as $row) {

						?>
							<tr>
								<td class="line"><?= $row['mat_nome']; ?></td>
								<td class="line"><?= $row['marc_nome']; ?></td>
								<td class="line"><?= $row['mat_qtd']; ?></td>
								<td class="line"><?= $row['mat_caixa']; ?></td>
								<td class="line"><?= $row['mat_obs']; ?></td>
								<td class="line"><button type="button" class="btn btn-secondary btn-sm" onclick="redirect('<?= $_SERVER['PHP_SELF']; ?>?action=edit&cod=<?= $row['mat_cod']; ?>')"><?= TRANS('BT_EDIT'); ?></button></td>
								<td class="line"><button type="button" class="btn btn-danger btn-sm" onclick="confirmDeleteModal('<?= $row['mat_cod']; ?>')"><?= TRANS('REMOVE'); ?></button></td>
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
			<form name="form" method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form" >
				<?= csrf_input(); ?>
				<div class="form-group row my-4">
                    
                    
                    <label for="document_name" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('DOCUMENT'); ?></label>
					<div class="form-group col-md-10">
						<input type="text" class="form-control " id="document_name" name="document_name" required />
                    </div>

                    <label for="document_quantity" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_QTD'); ?></label>
					<div class="form-group col-md-10">
						<input type="number" class="form-control " id="document_quantity" name="document_quantity" required />
                    </div>

                    <label for="document_box" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('BOX'); ?></label>
					<div class="form-group col-md-10">
						<input type="text" class="form-control " id="document_box" name="document_box" />
                    </div>
                    
                    
                    <label for="related_model" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('RELATED_MODEL'); ?></label>
                    <div class="form-group col-md-10">
                        <select class="form-control sel2" id="related_model" name="related_model">
                            <option value=""><?= TRANS('RELATED_MODEL'); ?></option>
                            <?php
                            $sql = "SELECT * FROM marcas_comp ORDER BY marc_nome";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                                ?>
                                <option value="<?= $rowType['marc_cod']; ?>"
                                ><?= $rowType['marc_nome']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
                    </div>
                
                    <label for="aditional_info" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('ENTRY_TYPE_ADDITIONAL_INFO'); ?></label>
					<div class="form-group col-md-10">
						<textarea class="form-control " id="aditional_info" name="aditional_info" required></textarea>
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
			<form name="form" method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form" >
				<?= csrf_input(); ?>
				<div class="form-group row my-4">
                    
                    <label for="document_name" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('DOCUMENT'); ?></label>
					<div class="form-group col-md-10">
						<input type="text" class="form-control " id="document_name" name="document_name" value="<?= $row['mat_nome']; ?>" required />
                    </div>

                    <label for="document_quantity" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_QTD'); ?></label>
					<div class="form-group col-md-10">
						<input type="number" class="form-control " id="document_quantity" name="document_quantity" value="<?= $row['mat_qtd']; ?>" required />
                    </div>

                    <label for="document_box" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('BOX'); ?></label>
					<div class="form-group col-md-10">
						<input type="text" class="form-control " id="document_box" name="document_box" value="<?= $row['mat_caixa']; ?>"/>
                    </div>
                    
                    
                    <label for="related_model" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('RELATED_MODEL'); ?></label>
                    <div class="form-group col-md-10">
                        <select class="form-control sel2" id="related_model" name="related_model">
                            <option value=""><?= TRANS('RELATED_MODEL'); ?></option>
                            <?php
                            $sql = "SELECT * FROM marcas_comp ORDER BY marc_nome";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                                ?>
                                <option value="<?= $rowType['marc_cod']; ?>"
                                <?= ($rowType['marc_cod'] == $row['mat_modelo_equip'] ? ' selected' : ''); ?>
                                ><?= $rowType['marc_nome']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
                    </div>
                
                    <label for="aditional_info" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('ENTRY_TYPE_ADDITIONAL_INFO'); ?></label>
					<div class="form-group col-md-10">
						<textarea class="form-control " id="aditional_info" name="aditional_info" required><?= $row['mat_obs']; ?></textarea>
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
    <script src="../../includes/components/jquery/jquery.initialize.min.js"></script>
	<script src="../../includes/components/bootstrap/js/bootstrap.bundle.js"></script>
	<script src="../../includes/components/bootstrap-select/dist/js/bootstrap-select.min.js"></script>
	<script type="text/javascript" charset="utf8" src="../../includes/components/datatables/datatables.js"></script>
	<script type="text/javascript">
		$(function() {

            $('.sel2').addClass('new-select2');

            $('.new-select2').selectpicker({
				/* placeholder */
				title: "<?= TRANS('SEL_SELECT', '', 1); ?>",
				liveSearch: true,
				liveSearchNormalize: true,
				liveSearchPlaceholder: "<?= TRANS('BT_SEARCH', '', 1); ?>",
				noneResultsText: "<?= TRANS('NO_RECORDS_FOUND', '', 1); ?> {0}",
				
				style: "",
				styleBase: "form-control input-select-multi",
			});


            if ($('#table_lists').length > 0) {
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

                // var form = $('form').get(0);
				$("#idSubmit").prop("disabled", true);
				$.ajax({
					url: './documents_process.php',
					method: 'POST',
                    data: $('#form').serialize(),
                    // data: new FormData(form),
                    dataType: 'json',
                    
                    // cache: false,
				    // processData: false,
				    // contentType: false,
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
				url: './documents_process.php',
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
	</script>
</body>

</html>