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
		<h4 class="my-4"><i class="fas fa-photo-video text-secondary"></i>&nbsp;<?= TRANS('ADM_SOFT'); ?></h4>
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

        $query = "SELECT 
                    s.*, l.*, c.*, f.*, fo.* 
                FROM 
                    licencas AS l, categorias AS c, fabricantes AS f, softwares AS s 
			    LEFT JOIN fornecedores AS fo on fo.forn_cod = s.soft_forn 
			    WHERE 
                    s.soft_tipo_lic = l.lic_cod AND s.soft_cat = c.cat_cod AND s.soft_fab = f.fab_cod 
        ";
        
		if (isset($_GET['cod'])) {
			$query .= " AND soft_cod = " . (int)$_GET['cod'] . "  ";
		}
		$query .= " ORDER BY soft_desc";
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
			<!-- <?= TRANS('MANAGE_RELATED_ITENS'); ?>:&nbsp;<button class="btn btn-sm btn-success manage" data-location="cat_prob1" name="probtp1"><?= $row_config['conf_prob_tipo_1']; ?></button>
			<button class="btn btn-sm btn-success manage" data-location="cat_prob2" name="probtp2"><?= $row_config['conf_prob_tipo_2']; ?></button>
			<button class="btn btn-sm btn-success manage" data-location="cat_prob3" name="probtp3"><?= $row_config['conf_prob_tipo_3']; ?></button>
			<br /><br /> -->
			<?php
			if ($registros == 0) {
				echo message('info', '', TRANS('NO_RECORDS_FOUND'), '', '', true);
			} else {

			    ?>
				<table id="table_lists" class="stripe hover order-column row-border" border="0" cellspacing="0" width="100%">

					<thead>
						<tr class="header">
							<td class="line issue_type"><?= TRANS('COL_SOFT'); ?></td>
							<td class="line description"><?= TRANS('CATEGORY'); ?></td>
							<td class="line area"><?= TRANS('COL_LICENSE'); ?></td>
							<td class="line sla"><?= TRANS('COL_NUMBER_OF_LICENSES'); ?></td>
							<td class="line sla"><?= TRANS('COL_AVAILABLE'); ?></td>
							<td class="line sla"><?= TRANS('COL_VENDOR'); ?></td>
							<td class="line sla"><?= TRANS('COL_NF'); ?></td>
							<td class="line editar"><?= TRANS('BT_EDIT'); ?></td>
							<td class="line remover"><?= TRANS('BT_REMOVE'); ?></td>
						</tr>
					</thead>
					<tbody>
						<?php

						foreach ($resultado->fetchall() as $row) {

                            $sqlAux = "SELECT COUNT(*) total FROM hw_sw WHERE hws_sw_cod = '" . $row['soft_cod'] . "' ";
                            $commitAux = $conn->query($sqlAux);
                            $rowAux = $commitAux->fetch();
                            $dispo = $row['soft_qtd_lic'] - $rowAux['total'];

						    ?>
							<tr>
								<td class="line"><?= $row['fab_nome']." ".$row['soft_desc']." ".$row['soft_versao']; ?></td>
								<td class="line"><?= $row['cat_desc']; ?></td>
								<td class="line"><?= $row['lic_desc']; ?></td>
								<td class="line"><?= $row['soft_qtd_lic']; ?></td>
								<td class="line"><?= $dispo; ?></td>
								<td class="line"><?= $row['forn_nome']; ?></td>
								<td class="line"><?= $row['soft_nf']; ?></td>
								
								<td class="line"><button type="button" class="btn btn-secondary btn-sm" onclick="redirect('<?= $_SERVER['PHP_SELF']; ?>?action=edit&cod=<?= $row['soft_cod']; ?>')"><?= TRANS('BT_EDIT'); ?></button></td>
								<td class="line"><button type="button" class="btn btn-danger btn-sm" onclick="confirmDeleteModal('<?= $row['soft_cod']; ?>')"><?= TRANS('REMOVE'); ?></button></td>
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
					
					<label for="client" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CLIENT'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control bs-select" id="client" name="client" required>
                            <?php
                                $clients = getClients($conn, null, null, $_SESSION['s_allowed_clients']);
                                foreach ($clients as $client) {
                                    ?>
                                        <option value="<?= $client['id']; ?>"
                                        ><?= $client['nickname']; ?></option>
                                    <?php
                                }
                            ?>
                            
                        </select>
                    </div>

                    <label for="asset_unit" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_UNIT'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control bs-select" id="asset_unit" name="asset_unit" required>
                            
                        </select>
                    </div>
				
				
					<label for="software_name" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_SOFT'); ?></label>
					<div class="form-group col-md-4">
						<input type="text" class="form-control " id="software_name" name="software_name" required />
                    </div>
                    
                    <label for="software_version" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_VERSION'); ?></label>
					<div class="form-group col-md-4">
						<input type="text" class="form-control " id="software_version" name="software_version" required />
					</div>


					<label for="manufacture" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_MANUFACTURER'); ?></label>
					<div class="form-group col-md-4">
						<select class="form-control sel2" name="manufacture" id="manufacture" required>
							<option value=""><?= TRANS('SEL_MANUFACTURER'); ?></option>
							<?php
							
                            $sql = "SELECT * FROM fabricantes WHERE fab_tipo IN (2,3) ORDER BY fab_tipo,fab_nome";
							$res = $conn->query($sql);
							foreach ($res->fetchall() as $rowSelect) {
							?>
								<option value='<?= $rowSelect['fab_cod']; ?>'><?= $rowSelect['fab_nome']; ?></option>
							<?php
							}
							?>
						</select>
                    </div>
                    
                    <label for="category" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CATEGORY'); ?></label>
					<div class="form-group col-md-4">
						<select class="form-control sel2" name="category" id="category" required>
							<option value=""><?= TRANS('SEL_CAT'); ?></option>
							<?php
							
                            $sql = "SELECT * FROM categorias ORDER BY cat_desc";
							$res = $conn->query($sql);
							foreach ($res->fetchall() as $rowSelect) {
							?>
								<option value='<?= $rowSelect['cat_cod']; ?>'><?= $rowSelect['cat_desc']; ?></option>
							<?php
							}
							?>
						</select>
                    </div>
                    
                    <label for="license_type" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_LICENSE'); ?></label>
					<div class="form-group col-md-4">
						<select class="form-control sel2" name="license_type" id="license_type" required>
							<option value=""><?= TRANS('SEL_LICENSE'); ?></option>
							<?php
							
                            $sql = "SELECT * FROM licencas ORDER BY lic_desc";
							$res = $conn->query($sql);
							foreach ($res->fetchall() as $rowSelect) {
							?>
								<option value='<?= $rowSelect['lic_cod']; ?>'><?= $rowSelect['lic_desc']; ?></option>
							<?php
							}
							?>
						</select>
					</div>

                    <label for="amount" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_QTD'); ?></label>
					<div class="form-group col-md-4">
						<input type="number" class="form-control " id="amount" name="amount"  />
					</div>

                    <label for="supplier" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_VENDOR'); ?></label>
					<div class="form-group col-md-4">
						<select class="form-control sel2" name="supplier" id="supplier" >
							<option value=""><?= TRANS('SEL_SUPLIER'); ?></option>
							<?php
							
                            $sql = "SELECT * FROM fornecedores ORDER BY forn_nome";
							$res = $conn->query($sql);
							foreach ($res->fetchall() as $rowSelect) {
							?>
								<option value='<?= $rowSelect['forn_cod']; ?>'><?= $rowSelect['forn_nome']; ?></option>
							<?php
							}
							?>
						</select>
					</div>

                    <label for="invoice_number" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_NF'); ?></label>
					<div class="form-group col-md-4">
						<input type="text" class="form-control " id="invoice_number" name="invoice_number"  />
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
                    
					<label for="client" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CLIENT'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control bs-select" id="client" name="client" required>
                            <?php
                                $clients = getClients($conn, null, null, $_SESSION['s_allowed_clients']);
                                foreach ($clients as $client) {
                                    ?>
                                        <option value="<?= $client['id']; ?>"
                                        ><?= $client['nickname']; ?></option>
                                    <?php
                                }
                            ?>
                            
                        </select>
                    </div>

                    <label for="asset_unit" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_UNIT'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control bs-select" id="asset_unit" name="asset_unit" required>
                            
                        </select>
                    </div>

                    <label for="software_name" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_SOFT'); ?></label>
					<div class="form-group col-md-4">
						<input type="text" class="form-control " id="software_name" name="software_name" value="<?= $row['soft_desc']; ?>" required />
                    </div>
                    
                    <label for="software_version" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_VERSION'); ?></label>
					<div class="form-group col-md-4">
						<input type="text" class="form-control " id="software_version" name="software_version" value="<?= $row['soft_versao']; ?>" required />
					</div>


					<label for="manufacture" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_MANUFACTURER'); ?></label>
					<div class="form-group col-md-4">
						<select class="form-control sel2" name="manufacture" id="manufacture" required>
							<option value=""><?= TRANS('SEL_MANUFACTURER'); ?></option>
							<?php
							
                            $sql = "SELECT * FROM fabricantes WHERE fab_tipo IN (2,3) ORDER BY fab_tipo,fab_nome";
							$res = $conn->query($sql);
							foreach ($res->fetchall() as $rowSelect) {
							?>
                                <option value='<?= $rowSelect['fab_cod']; ?>'
                                <?= ($rowSelect['fab_cod'] == $row['soft_fab'] ? ' selected' : ''); ?>
                                ><?= $rowSelect['fab_nome']; ?></option>
							<?php
							}
							?>
						</select>
                    </div>
                    
                    <label for="category" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CATEGORY'); ?></label>
					<div class="form-group col-md-4">
						<select class="form-control sel2" name="category" id="category" required>
							<option value=""><?= TRANS('SEL_CAT'); ?></option>
							<?php
							
                            $sql = "SELECT * FROM categorias ORDER BY cat_desc";
							$res = $conn->query($sql);
							foreach ($res->fetchall() as $rowSelect) {
							?>
                                <option value='<?= $rowSelect['cat_cod']; ?>'
                                <?= ($rowSelect['cat_cod'] == $row['soft_cat'] ? ' selected' : ''); ?>
                                ><?= $rowSelect['cat_desc']; ?></option>
							<?php
							}
							?>
						</select>
                    </div>
                    
                    <label for="license_type" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_LICENSE'); ?></label>
					<div class="form-group col-md-4">
						<select class="form-control sel2" name="license_type" id="license_type" required>
							<option value=""><?= TRANS('SEL_LICENSE'); ?></option>
							<?php
							
                            $sql = "SELECT * FROM licencas ORDER BY lic_desc";
							$res = $conn->query($sql);
							foreach ($res->fetchall() as $rowSelect) {
							?>
                                <option value='<?= $rowSelect['lic_cod']; ?>'
                                <?= ($rowSelect['lic_cod'] == $row['soft_tipo_lic'] ? ' selected' : ''); ?>
                                ><?= $rowSelect['lic_desc']; ?></option>
							<?php
							}
							?>
						</select>
					</div>

                    <label for="amount" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_QTD'); ?></label>
					<div class="form-group col-md-4">
						<input type="number" class="form-control " id="amount" name="amount" value="<?= $row['soft_qtd_lic']; ?>" />
					</div>

                    <label for="supplier" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_VENDOR'); ?></label>
					<div class="form-group col-md-4">
						<select class="form-control sel2" name="supplier" id="supplier" >
							<option value=""><?= TRANS('SEL_SUPLIER'); ?></option>
							<?php
							
                            $sql = "SELECT * FROM fornecedores ORDER BY forn_nome";
							$res = $conn->query($sql);
							foreach ($res->fetchall() as $rowSelect) {
							?>
                                <option value='<?= $rowSelect['forn_cod']; ?>'
                                <?= ($rowSelect['forn_cod'] == $row['soft_forn'] ? ' selected' : ''); ?>
                                ><?= $rowSelect['forn_nome']; ?></option>
							<?php
							}
							?>
						</select>
					</div>

                    <label for="invoice_number" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_NF'); ?></label>
					<div class="form-group col-md-4">
						<input type="text" class="form-control " id="invoice_number" name="invoice_number"  value="<?= $row['soft_nf']; ?>" />
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
	<script src="../../includes/components/bootstrap/js/bootstrap.bundle.js"></script>
    <script src="../../includes/components/bootstrap-select/dist/js/bootstrap-select.min.js"></script>
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
            

            $('.sel2').addClass('new-select2');
            $('.bs-select').addClass('new-select2');

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


			$("#client").on('change', function() {
				loadUnits();
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
					url: './sw_softwares_process.php',
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
				url: './sw_softwares_process.php',
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


		function loadUnits(targetId = 'asset_unit') {

            var loading = $(".loading");
            $(document).ajaxStart(function() {
                loading.show();
            });
            $(document).ajaxStop(function() {
                loading.hide();
            });

            $.ajax({
                url: '../../ocomon/geral/get_units_by_client.php',
                method: 'POST',
                dataType: 'json',
                data: {
                    client: $("#client").val()
                },
            }).done(function(data) {
                $('#' + targetId).empty();
                if (Object.keys(data).length > 1) {
                    $('#' + targetId).append('<option value=""><?= TRANS("SEL_SELECT"); ?></option>');
                }
                $.each(data, function(key, data) {
                    $('#' + targetId).append('<option value="' + data.inst_cod + '">' + data.inst_nome + '</option>');
                });

                $('#' + targetId).selectpicker('refresh');
                if ($('#parent_id').val() != '') {
                    $('#' + targetId).selectpicker('val', $('#parent_unit').val());
                    $('#' + targetId).selectpicker('refresh');
                }
            });
        }

		
	</script>
</body>

</html>