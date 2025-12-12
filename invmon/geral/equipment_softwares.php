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
		<h4 class="my-4"><i class="fas fa-photo-video text-secondary"></i>&nbsp;<?= TRANS('EQUIPMENT_SOFTWARES'); ?></h4>
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
        
        $request = (isset($_POST) && !empty($_POST) ? $_POST : (isset($_GET) && !empty($_GET) ? $_GET : ''));
        if (empty($request)) {
            echo message('danger', 'Ooops!', TRANS('MSG_ERR_NOT_EXECUTE'), '', '', 1);
            return;
        }

        $asset_tag = (isset($request['asset_tag']) && !empty($request['asset_tag']) ? $request['asset_tag'] : '');
        $asset_unit = (isset($request['asset_unit']) && !empty($request['asset_unit']) ? $request['asset_unit'] : '');
        if (empty($asset_tag) || empty($asset_unit)) {
            echo message('danger', 'Ooops!', TRANS('MSG_ERR_NOT_EXECUTE'), '', '', 1);
            return;
        }

        $query = "SELECT 
                    s.*, l.*, c.*, f.*, h.* 
                FROM 
                    softwares AS s, licencas AS l, categorias AS c, 
				    fabricantes AS f, hw_sw AS h
				WHERE 
                    s.soft_tipo_lic = l.lic_cod AND s.soft_cat = c.cat_cod AND s.soft_fab = f.fab_cod
				    AND h.hws_sw_cod = s.soft_cod 
                    AND h.hws_hw_cod = '{$asset_tag}' 
                    AND h.hws_hw_inst ='{$asset_unit}' 
                ORDER BY f.fab_nome, s.soft_desc
        ";
        
		$resultado = $conn->query($query);
		$registros = $resultado->rowCount();

		if ((!isset($_GET['action'])) && !isset($_POST['submit'])) {

		?>
			<!-- Modal delete -->
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
            
            <!-- Modal load -->
			<div class="modal fade" id="loadModal" tabindex="-1" role="dialog" aria-labelledby="loadModalLabel" aria-hidden="true">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header bg-light">
							<h5 class="modal-title" id="loadModalLabel"><i class="fas fa-exclamation-triangle text-secondary"></i>&nbsp;<?= TRANS('LOAD_DEFAULT_SOFTWARES'); ?></h5>
							<button type="button" class="close" data-dismiss="modal" aria-label="Close">
								<span aria-hidden="true">&times;</span>
							</button>
						</div>
						<div class="modal-body">
							<?= TRANS('MSG_CONFIRM_LOAD_CONFIG'); ?> <span class="j_param_id"></span>?
						</div>
						<div class="modal-footer bg-light">
							<button type="button" class="btn btn-secondary" data-dismiss="modal"><?= TRANS('BT_CANCEL'); ?></button>
							<button type="button" id="loadButton" class="btn"><?= TRANS('BT_OK'); ?></button>
						</div>
					</div>
				</div>
            </div>
            
            <!-- Modal reset -->
			<div class="modal fade" id="resetModal" tabindex="-1" role="dialog" aria-labelledby="resetModalLabel" aria-hidden="true">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header bg-light">
							<h5 class="modal-title" id="resetModalLabel"><i class="fas fa-exclamation-triangle text-secondary"></i>&nbsp;<?= TRANS('REMOVE'); ?></h5>
							<button type="button" class="close" data-dismiss="modal" aria-label="Close">
								<span aria-hidden="true">&times;</span>
							</button>
						</div>
						<div class="modal-body">
							<?= TRANS('MSG_CONFIRM_DEL_CONFIG'); ?> <span class="j_param_id"></span>?
						</div>
						<div class="modal-footer bg-light">
							<button type="button" class="btn btn-secondary" data-dismiss="modal"><?= TRANS('BT_CANCEL'); ?></button>
							<button type="button" id="resetButton" class="btn"><?= TRANS('BT_OK'); ?></button>
						</div>
					</div>
				</div>
			</div>

			<button class="btn btn-sm btn-primary" id="idBtIncluir" name="new"><?= TRANS("ACT_NEW"); ?></button>
            <button class="btn btn-sm btn-success" name="sw_load" id="sw_load" ><?= TRANS('BT_LOAD'); ?></button>
            <button class="btn btn-sm btn-danger" name="sw_reset" id="sw_reset"><?= TRANS('BT_RESET'); ?></button>
			
			<br /><br />
			<?php
			if ($registros == 0) {
				echo message('info', '', TRANS('NO_RECORDS_FOUND'), '', '', true);
			} else {

				?>
				
				<h6><?= TRANS('COL_UNIT'); ?>: <?= getUnits($conn, 1, $asset_unit)['inst_nome']; ?></h6>
				<h6><?= TRANS('ASSET_TAG'); ?>: <?= $asset_tag; ?></h6><br/>
				<table id="table_lists" class="stripe hover order-column row-border" border="0" cellspacing="0" width="100%">

					<thead>
						<tr class="header">
							<td class="line issue_type"><?= TRANS('COL_SOFT'); ?></td>
							<td class="line description"><?= TRANS('CATEGORY'); ?></td>
							<td class="line area"><?= TRANS('COL_LICENSE'); ?></td>
							<td class="line remover"><?= TRANS('BT_REMOVE'); ?></td>
						</tr>
					</thead>
					<tbody>
						<?php

						foreach ($resultado->fetchall() as $row) {

						    ?>
							<tr>
								<td class="line"><?= $row['fab_nome']." ".$row['soft_desc']." ".$row['soft_versao']; ?></td>
								<td class="line"><?= $row['cat_desc']; ?></td>
								<td class="line"><?= $row['lic_desc']; ?></td>
								<td class="line"><button type="button" class="btn btn-danger btn-sm" onclick="confirmDeleteModal('<?= $row['hws_cod']; ?>')"><?= TRANS('REMOVE'); ?></button></td>
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
                    
                    <?php
						$terms = "";
                        $softs = "";

                        $sql = "SELECT * FROM hw_sw WHERE hws_hw_cod = '{$asset_tag}' AND hws_hw_inst = '{$asset_unit}' ";
                        $commit = $conn->query($sql);
                        foreach ($commit->fetchall() as $rowA) {
                            if (strlen((string)$softs)) $softs .= ",";
                            $softs .= $rowA["hws_sw_cod"];
						}
						
						if (!empty($softs)) {
							$terms .= " AND s.soft_cod NOT IN ({$softs}) ";
						}
						
                    ?>

					<label for="software" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('AVAILABLE_SOFTWARE'); ?></label>
					<div class="form-group col-md-10">
						<select class="form-control sel2" name="software" id="software" required>
							<option value=""><?= TRANS('SEL_SOFT'); ?></option>
							<?php
                            
                            $sql = "SELECT s.*, f.* FROM softwares s, fabricantes f WHERE f.fab_cod = s.soft_fab
                                {$terms} ORDER BY fab_nome, soft_desc";
                                
							$res = $conn->query($sql);
							foreach ($res->fetchall() as $rowSelect) {
							?>
								<option value='<?= $rowSelect['soft_cod']; ?>'><?= $rowSelect['fab_nome']." ".$rowSelect['soft_desc']." ".$rowSelect["soft_versao"]; ?></option>
							<?php
							}
							?>
						</select>
                    </div>
                    

					<div class="row w-100"></div>
					<div class="form-group col-md-8 d-none d-md-block">
					</div>
					<div class="form-group col-12 col-md-2 ">

						<input type="hidden" name="action" id="action" value="new">
						<input type="hidden" name="asset_tag" id="asset_tag" value="<?= $asset_tag; ?>"/>
						<input type="hidden" name="asset_unit" id="asset_unit" value="<?= $asset_unit; ?>"/>
						<button type="submit" id="idSubmit" name="submit" class="btn btn-primary btn-block"><?= TRANS('BT_OK'); ?></button>
					</div>
					<div class="form-group col-12 col-md-2">
						<button type="reset" class="btn btn-secondary btn-block close-or-return"><?= TRANS('BT_CANCEL'); ?></button>
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

            closeOrReturn ();

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
					url: './equipment_softwares_process.php',
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
						var url = '<?= $_SERVER['PHP_SELF'] ?>?asset_tag=<?= $asset_tag; ?>&asset_unit=<?= $asset_unit; ?>';
						$(location).prop('href', url);
						return false;
					}
				});
				return false;
			});

			$('#idBtIncluir').on("click", function() {
				$('#idLoad').css('display', 'block');
				var url = '<?= $_SERVER['PHP_SELF'] ?>?action=new&asset_tag=<?= $asset_tag; ?>&asset_unit=<?= $asset_unit; ?>';
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
				url: './equipment_softwares_process.php',
				method: 'POST',
				data: {
					cod: id,
					action: 'delete'
				},
				dataType: 'json',
			}).done(function(response) {
				var url = '<?= $_SERVER['PHP_SELF'] ?>?asset_tag=<?= $asset_tag; ?>&asset_unit=<?= $asset_unit; ?>';
				$(location).prop('href', url);
				return false;
			});
			return false;
			// $('#deleteModal').modal('hide'); // now close modal
        }

        $('#sw_load').on('click', function(){
			confirmLoadModal();
        });

        function confirmLoadModal() {
			$('#loadModal').modal();
			$('#loadButton').html('<a class="btn btn-primary" onclick="loadData()"><?= TRANS('LOAD'); ?></a>');
		}

		function loadData() {

			var loading = $(".loading");
			$(document).ajaxStart(function() {
				loading.show();
			});
			$(document).ajaxStop(function() {
				loading.hide();
			});

			$.ajax({
				url: './equipment_softwares_process.php',
				method: 'POST',
				data: {
					asset_tag: '<?= $asset_tag; ?>',
					asset_unit: '<?= $asset_unit; ?>',
					action: 'load'
				},
				dataType: 'json',
			}).done(function(response) {
				var url = '<?= $_SERVER['PHP_SELF'] ?>?asset_tag=<?= $asset_tag; ?>&asset_unit=<?= $asset_unit; ?>';
				$(location).prop('href', url);
				return false;
			});
			return false;
			// $('#deleteModal').modal('hide'); // now close modal
        }


        $('#sw_reset').on('click', function(){
            confirmResetModal();
        });

        function confirmResetModal() {
			$('#resetModal').modal();
			$('#resetButton').html('<a class="btn btn-danger" onclick="resetData()"><?= TRANS('BT_RESET'); ?></a>');
		}

		function resetData() {

			var loading = $(".loading");
			$(document).ajaxStart(function() {
				loading.show();
			});
			$(document).ajaxStop(function() {
				loading.hide();
			});

			$.ajax({
				url: './equipment_softwares_process.php',
				method: 'POST',
				data: {
					asset_tag: '<?= $asset_tag; ?>',
					asset_unit: '<?= $asset_unit; ?>',
					action: 'reset'
				},
				dataType: 'json',
			}).done(function(response) {
				var url = '<?= $_SERVER['PHP_SELF'] ?>?asset_tag=<?= $asset_tag; ?>&asset_unit=<?= $asset_unit; ?>';
				$(location).prop('href', url);
				return false;
			});
			return false;
			// $('#deleteModal').modal('hide'); // now close modal
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
		}

		function buttonValue () {
			if (isPopup()) {
				$('.close-or-return').text('<?= TRANS('BT_CLOSE'); ?>');
			}
		}

		
	</script>
</body>

</html>