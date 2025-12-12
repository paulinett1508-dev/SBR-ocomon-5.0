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
    <link rel="stylesheet" type="text/css" href="../../includes/css/switch_radio.css" />
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
		<h4 class="my-4"><i class="fas fa-door-closed text-secondary"></i>&nbsp;<?= TRANS('DEPARTMENTS'); ?></h4>
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


		
		$departments = (isset($_GET['cod']) ? getDepartments($conn, null, (int)$_GET['cod']) : getDepartments($conn));

		$registros = count($departments);

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
			<?= TRANS('MANAGE_RELATED_ITENS'); ?>:&nbsp;<button class="btn btn-sm btn-secondary manage" data-location="buildings" name="buildings"><?= TRANS('MANAGE_BUILDINGS'); ?></button>
			<button class="btn btn-sm btn-secondary manage" data-location="rectories" name="probtp2"><?= TRANS('MANAGE_RECTORIES'); ?></button>
			<button class="btn btn-sm btn-secondary manage" data-location="domains" name="probtp3"><?= TRANS('MANAGE_DOMAINS'); ?></button>
			<br /><br />
			<?php
			if ($registros == 0) {
				echo message('info', '', TRANS('NO_RECORDS_FOUND'), '', '', true);
			} else {

			?>
				<table id="table_lists" class="stripe hover order-column row-border" border="0" cellspacing="0" width="100%">

					<thead>
						<tr class="header">
							<td class="line department"><?= TRANS('DEPARTMENT'); ?></td>
							<td class="line unit"><?= TRANS('CLIENT'); ?></td>
							<td class="line unit"><?= TRANS('COL_UNIT'); ?></td>
							<td class="line building"><?= TRANS('COL_BUILDING'); ?></td>
							<td class="line major"><?= TRANS('COL_RECTORY'); ?></td>
							<td class="line net_domain"><?= TRANS('NET_DOMAIN'); ?></td>
							<td class="line priority"><?= TRANS('RESPONSE_LEVEL'); ?></td>
							<td class="line status"><?= TRANS('ACTIVE_O'); ?></td>
							<td class="line editar"><?= TRANS('BT_EDIT'); ?></td>
							<td class="line remover"><?= TRANS('BT_REMOVE'); ?></td>
						</tr>
					</thead>
					<tbody>
						<?php

						foreach ($departments as $row) {
                            // $lstatus = TRANS('ACTIVE_O');
							// if ($row['loc_status'] == 0) $lstatus = TRANS('INACTIVE_O');

							$lstatus = ($row['loc_status'] == 1 ? '<span class="text-success"><i class="fas fa-check"></i></span>' : '');
							
							$badgeResponseTime = '<span class="badge badge-info p-2">' . $row['tempo_resposta'] . '</span>';

							$unit = (!empty($row['unidade']) ? '<span class="badge badge-info p-2">' . $row['unidade'] . '</span>' : '');
						    ?>
							<tr>
								<td class="line"><?= $row['local']; ?></td>
								<td class="line"><?= $row['nickname']; ?></td>
								<td class="line"><?= $row['unidade']; ?></td>
								<td class="line"><?= $row['pred_desc']; ?></td>
								<td class="line"><?= $row['reit_nome']; ?></td>
								<td class="line"><?= $row['dominio']; ?></td>
								<td class="line"><?= $row['prioridade'] . ' ' . $badgeResponseTime ?></td>
								<td class="line"><?= $lstatus; ?></td>
								<td class="line"><button type="button" class="btn btn-secondary btn-sm" onclick="redirect('<?= $_SERVER['PHP_SELF']; ?>?action=edit&cod=<?= $row['loc_id']; ?>')"><?= TRANS('BT_EDIT'); ?></button></td>
								<td class="line"><button type="button" class="btn btn-danger btn-sm" onclick="confirmDeleteModal('<?= $row['loc_id']; ?>')"><?= TRANS('REMOVE'); ?></button></td>
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
                    
                    <label for="department" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('DEPARTMENT'); ?></label>
					<div class="form-group col-md-10">
						<input type="text" class="form-control " id="department" name="department" required />
					</div>

					<label for="client" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('FILTER_BY_CLIENT'); ?></label>
                    <div class="form-group col-md-10 ">
                        <select class="form-control bs-select" id="client" name="client" required>
                            <option value=""><?= TRANS('SEL_SELECT'); ?></option>
							<?php
								$clients = getClients($conn);
								foreach ($clients as $client){
									?>
										<option value="<?= $client['id']; ?>"
										><?= $client['nickname']; ?></option>
									<?php
								}
							?>
                        </select>
                    </div>

					<label for="unit" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_UNIT'); ?></label>
					<div class="form-group col-md-10">
						<div class="input-group">
							<select class="form-control bs-select" name="unit" id="unit" required>
								<option value="-1" selected><?= TRANS('SEL_SELECT'); ?></option>
								<?php
									$units = getUnits($conn);
									foreach ($units as $unit) {
										?>
											<option value="<?= $unit['inst_cod']; ?>"><?= $unit['nickname'] . " - " .$unit['inst_nome']; ?></option>
										<?php
									}
								?>
								
							</select>
							<div class="input-group-append">
								<div class="input-group-text manage" data-location="units" title="<?= TRANS('MANAGE_UNITS'); ?>" data-placeholder="<?= TRANS('MANAGE_UNITS'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
									<i class="fas fa-city"></i>
								</div>
							</div>
						</div>
                    </div>
					<input type="hidden" name="unitDb" id="unitDb" value="">


					<label for="building" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_BUILDING'); ?></label>
					<div class="form-group col-md-10">
						<div class="input-group">
							<select class="form-control bs-select" name="building" id="building" required>
								<option value="-1" selected><?= TRANS('SEL_BUILDING'); ?></option>
								<?php
								$sql = "SELECT * from predios ORDER BY pred_desc";
								$resBuildings = $conn->query($sql);
								foreach ($resBuildings->fetchall() as $rowBuilding) {
								?>
									<option value='<?= $rowBuilding['pred_cod']; ?>'><?= $rowBuilding['pred_desc']; ?></option>
								<?php
								}
								?>
							</select>
							<div class="input-group-append">
								<div class="input-group-text manage" data-location="buildings" title="<?= TRANS('MANAGE_BUILDINGS'); ?>" data-placeholder="<?= TRANS('MANAGE_BUILDINGS'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
									<i class="fas fa-building"></i>
								</div>
							</div>
						</div>
                    </div>
					<input type="hidden" name="buildingDb" id="buildingDb" value="">
                    
					<label for="rectory" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_RECTORY'); ?></label>
					<div class="form-group col-md-10">
						<div class="input-group">
							<select class="form-control bs-select" name="rectory" id="rectory" required>
								<option value="-1" selected><?= TRANS('SEL_RECTORY'); ?></option>
								<?php
								$sql = "SELECT * FROM reitorias ORDER BY reit_nome";
								$resMajor = $conn->query($sql);
								foreach ($resMajor->fetchall() as $rowMajor) {
								?>
									<option value='<?= $rowMajor['reit_cod']; ?>'><?= $rowMajor['reit_nome']; ?></option>
								<?php
								}
								?>
							</select>
							<div class="input-group-append">
								<div class="input-group-text manage" data-location="rectories" title="<?= TRANS('MANAGE_RECTORIES'); ?>" data-placeholder="<?= TRANS('MANAGE_RECTORIES'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                    <i class="fas fa-university"></i>
								</div>
							</div>
						</div>
					</div>
					<input type="hidden" name="rectoryDb" id="rectoryDb" value="">
					
					<label for="net_domain" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('NET_DOMAIN'); ?></label>
					<div class="form-group col-md-10">
						<div class="input-group">
							<select class="form-control bs-select" name="net_domain" id="net_domain" required>
								<option value="-1" selected><?= TRANS('SEL_DOMAIN'); ?></option>
								<?php
								$sql = "SELECT * FROM dominios ORDER BY dom_desc";
								$resDomains = $conn->query($sql);
								foreach ($resDomains->fetchall() as $rowDomain) {
								?>
									<option value='<?= $rowDomain['dom_cod']; ?>'><?= $rowDomain['dom_desc']; ?></option>
								<?php
								}
								?>
							</select>
							<div class="input-group-append">
								<div class="input-group-text manage" data-location="domains" title="<?= TRANS('MANAGE_DOMAINS'); ?>" data-placeholder="<?= TRANS('MANAGE_DOMAINS'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
									<i class="fas fa-network-wired"></i>
								</div>
							</div>
						</div>
					</div>
					<input type="hidden" name="domainDb" id="domainDb" value="">

                    <label for="response_level" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('RESPONSE_LEVEL'); ?></label>
					<div class="form-group col-md-10">
						
							<select class="form-control bs-select" name="response_level" id="response_level" required>
								<option value="-1" selected><?= TRANS('SEL_RESPONSE_LEVEL'); ?></option>
								<?php
								$sql = "SELECT * FROM prioridades, sla_solucao WHERE prior_sla = slas_cod ORDER BY prior_nivel";
								$resPrior = $conn->query($sql);
								foreach ($resPrior->fetchall() as $rowPrior) {
								?>
									<option value='<?= $rowPrior['prior_cod']; ?>'><?= $rowPrior['prior_nivel'] . ' (' . $rowPrior['slas_desc'] . ')'; ?></option>
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

			$row = $departments;
		    ?>
			<h6><?= TRANS('BT_EDIT'); ?></h6>
			<form method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form">
				<?= csrf_input(); ?>
				<div class="form-group row my-4">
                    <label for="department" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('DEPARTMENT'); ?></label>
					<div class="form-group col-md-10">
						<input type="text" class="form-control " id="department" name="department" required value="<?= $row['local']; ?>"/>
                    </div>


					<label for="client" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('FILTER_BY_CLIENT'); ?></label>
                    <div class="form-group col-md-10 ">
                        <select class="form-control bs-select" id="client" name="client" required>
                            <option value=""><?= TRANS('SEL_SELECT'); ?></option>
							<?php
								$clients = getClients($conn);
								foreach ($clients as $client){
									?>
										<option value="<?= $client['id']; ?>"
										<?= ($client['id'] == $departments['client_id'] ? " selected" : ""); ?>
										><?= $client['nickname']; ?></option>
									<?php
								}
							?>
                        </select>
                    </div>
					

					<label for="unit" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_UNIT'); ?></label>
					<div class="form-group col-md-10">
						<div class="input-group">
							<select class="form-control bs-select" name="unit" id="unit" required>
								<option value="" selected><?= TRANS('SEL_SELECT'); ?></option>
								<?php
									$units = getUnits($conn);
									foreach ($units as $unit) {
										?>
											<option value="<?= $unit['inst_cod']; ?>"
											<?= ($unit['inst_cod'] == $departments['loc_unit'] ? " selected" : ""); ?>
											><?= $unit['inst_nome']; ?></option>
										<?php
									}
								?>
								
							</select>
							<div class="input-group-append">
								<div class="input-group-text manage" data-location="units" title="<?= TRANS('MANAGE_UNITS'); ?>" data-placeholder="<?= TRANS('MANAGE_UNITS'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
									<i class="fas fa-city"></i>
								</div>
							</div>
						</div>
                    </div>
					<input type="hidden" name="unitDb" id="unitDb" value="<?= $departments['loc_unit']; ?>">
                    

					<label for="building" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_BUILDING'); ?></label>
					<div class="form-group col-md-10">
						<div class="input-group">
							<select class="form-control bs-select" name="building" id="building" required>
								<option value="-1" selected><?= TRANS('SEL_BUILDING'); ?></option>
								<?php
								$sql = "SELECT * from predios ORDER BY pred_desc";
								$resBuildings = $conn->query($sql);
								foreach ($resBuildings->fetchall() as $rowBuilding) {
								?>
                                    <option value='<?= $rowBuilding['pred_cod']; ?>'
                                    <?= ($row['loc_predio'] == $rowBuilding['pred_cod'] ? ' selected' : ''); ?>
                                    ><?= $rowBuilding['pred_desc']; ?></option>
								<?php
								}
								?>
							</select>
							<div class="input-group-append">
								<div class="input-group-text manage" data-location="buildings" title="<?= TRANS('MANAGE_BUILDINGS'); ?>" data-placeholder="<?= TRANS('MANAGE_BUILDINGS'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
									<i class="fas fa-building"></i>
								</div>
							</div>
						</div>
                    </div>
					<input type="hidden" name="buildingDb" id="buildingDb" value="<?= $departments['loc_predio']; ?>">
                    
					<label for="rectory" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_RECTORY'); ?></label>
					<div class="form-group col-md-10">
						<div class="input-group">
							<select class="form-control bs-select" name="rectory" id="rectory" required>
								<option value="-1" selected><?= TRANS('SEL_RECTORY'); ?></option>
								<?php
								$sql = "SELECT * FROM reitorias ORDER BY reit_nome";
								$resMajor = $conn->query($sql);
								foreach ($resMajor->fetchall() as $rowMajor) {
								?>
                                    <option value='<?= $rowMajor['reit_cod']; ?>'
                                    <?= ($row['loc_reitoria'] == $rowMajor['reit_cod'] ? ' selected' : ''); ?>
                                    ><?= $rowMajor['reit_nome']; ?></option>
								<?php
								}
								?>
							</select>
							<div class="input-group-append">
								<div class="input-group-text manage" data-location="rectories" title="<?= TRANS('MANAGE_RECTORIES'); ?>" data-placeholder="<?= TRANS('MANAGE_RECTORIES'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
                                    <i class="fas fa-university"></i>
								</div>
							</div>
						</div>
					</div>
					<input type="hidden" name="rectoryDb" id="rectoryDb" value="<?= $departments['loc_reitoria']; ?>">
					
					<label for="net_domain" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('NET_DOMAIN'); ?></label>
					<div class="form-group col-md-10">
						<div class="input-group">
							<select class="form-control bs-select" name="net_domain" id="net_domain" required>
								<option value="-1" selected><?= TRANS('SEL_DOMAIN'); ?></option>
								<?php
								$sql = "SELECT * FROM dominios ORDER BY dom_desc";
								$resDomains = $conn->query($sql);
								foreach ($resDomains->fetchall() as $rowDomain) {
								    ?>
                                    <option value='<?= $rowDomain['dom_cod']; ?>'
                                        <?= ($row['loc_dominio'] == $rowDomain['dom_cod'] ? ' selected' : ''); ?>
                                    ><?= $rowDomain['dom_desc']; ?></option>
								    <?php
								}
								?>
							</select>
							<div class="input-group-append">
								<div class="input-group-text manage" data-location="domains" title="<?= TRANS('MANAGE_DOMAINS'); ?>" data-placeholder="<?= TRANS('MANAGE_DOMAINS'); ?>" data-toggle="popover" data-placement="top" data-trigger="hover">
									<i class="fas fa-network-wired"></i>
								</div>
							</div>
						</div>
					</div>
					<input type="hidden" name="domainDb" id="domainDb" value="<?= $departments['loc_dominio']; ?>">

                    <label for="response_level" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('RESPONSE_LEVEL'); ?></label>
					<div class="form-group col-md-10">
						
							<select class="form-control bs-select" name="response_level" id="response_level" required>
								<option value="-1" selected><?= TRANS('SEL_RESPONSE_LEVEL'); ?></option>
								<?php
								$sql = "SELECT * FROM prioridades, sla_solucao WHERE prior_sla = slas_cod ORDER BY prior_nivel";
								$resPrior = $conn->query($sql);
								foreach ($resPrior->fetchall() as $rowPrior) {
								    ?>
                                    <option value='<?= $rowPrior['prior_cod']; ?>'
                                        <?= ($row['loc_prior'] == $rowPrior['prior_cod'] ? ' selected' : ''); ?>
                                    ><?= $rowPrior['prior_nivel'] . ' (' . $rowPrior['slas_desc'] . ')'; ?></option>
								    <?php
								}
								?>
							</select>
                    </div>
                    
                    <label class="col-md-2 col-form-label col-form-label-sm text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('ACTIVE_O'); ?>"><?= firstLetterUp(TRANS('ACTIVE_O')); ?></label>
					<div class="form-group col-md-10 ">
						<div class="switch-field">
							<?php
							$yesChecked = ($row['loc_status'] == 1 ? "checked" : "");
							$noChecked = (!($row['loc_status'] == 1) ? "checked" : "");
							?>
							<input type="radio" id="department_status" name="department_status" value="yes" <?= $yesChecked; ?> />
							<label for="department_status"><?= TRANS('YES'); ?></label>
							<input type="radio" id="department_status_no" name="department_status" value="no" <?= $noChecked; ?> />
							<label for="department_status_no"><?= TRANS('NOT'); ?></label>
						</div>
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

			$.fn.selectpicker.Constructor.BootstrapVersion = '4';
            // $('#unit, #client').selectpicker({
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

			loadUnits();
			loadBuildings();
			loadRectories();
			loadDomains();

			$('#client').on('change', function(){
				loadUnits();
				loadBuildings();
				loadRectories();
				loadDomains();
			});

			$('#unit').on('change', function(){
				loadBuildings();
				loadRectories();
				loadDomains();
			});


			$('.manage').on('click', function() {
				loadInModal($(this).attr('data-location'));
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
					url: './departments_process.php',
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



		function loadUnits() {
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
				let unitDb = $('#unitDb').val();
				$('#unit').empty();
				if (Object.keys(data).length > 1) {
					$('#unit').append('<option value=""><?= TRANS("SEL_SELECT"); ?></option>');
				}
				$.each(data, function(key, data) {
					$('#unit').append('<option value="' + data.inst_cod + '">' + data.inst_nome + '</option>');
				});

				$('#unit').selectpicker('refresh');
				$('#unit').selectpicker('val', unitDb);
				
			});
		}


		function loadBuildings() {
			var loading = $(".loading");
			$(document).ajaxStart(function() {
				loading.show();
			});
			$(document).ajaxStop(function() {
				loading.hide();
			});

			$.ajax({
				url: '../../ocomon/geral/get_buildings_by_client.php',
				method: 'POST',
				dataType: 'json',
				data: {
					client: $("#client").val(),
					unit: $("#unit").val()
				},
			}).done(function(data) {
				let buildingDb = $('#buildingDb').val();
				$('#building').empty();
				if (Object.keys(data).length > 1) {
					$('#building').append('<option value=""><?= TRANS("SEL_SELECT"); ?></option>');
				}
				$.each(data, function(key, data) {
					$('#building').append('<option data-subtext="'+ (data.nickname ?? '') +'" value="' + data.pred_cod + '">' + data.pred_desc + '</option>');
				});

				$('#building').selectpicker('refresh');
				$('#building').selectpicker('val', buildingDb);
				
			});
		}


		function loadRectories() {
			var loading = $(".loading");
			$(document).ajaxStart(function() {
				loading.show();
			});
			$(document).ajaxStop(function() {
				loading.hide();
			});

			$.ajax({
				url: '../../ocomon/geral/get_rectories_by_client.php',
				method: 'POST',
				dataType: 'json',
				data: {
					client: $("#client").val(),
					unit: $("#unit").val()
				},
			}).done(function(data) {
				let rectoryDb = $('#rectoryDb').val();
				$('#rectory').empty();
				if (Object.keys(data).length > 1) {
					$('#rectory').append('<option value=""><?= TRANS("SEL_SELECT"); ?></option>');
				}
				$.each(data, function(key, data) {
					$('#rectory').append('<option data-subtext="'+ (data.nickname ?? '') +'" value="' + data.reit_cod + '">' + data.reit_nome + '</option>');
				});

				$('#rectory').selectpicker('refresh');
				$('#rectory').selectpicker('val', rectoryDb);
				
			});
		}


		function loadDomains() {
			var loading = $(".loading");
			$(document).ajaxStart(function() {
				loading.show();
			});
			$(document).ajaxStop(function() {
				loading.hide();
			});

			$.ajax({
				url: '../../ocomon/geral/get_domains_by_client.php',
				method: 'POST',
				dataType: 'json',
				data: {
					client: $("#client").val(),
					unit: $("#unit").val()
				},
			}).done(function(data) {
				let domainDb = $('#domainDb').val();
				$('#net_domain').empty();
				if (Object.keys(data).length > 1) {
					$('#net_domain').append('<option value=""><?= TRANS("SEL_SELECT"); ?></option>');
				}
				$.each(data, function(key, data) {
					$('#net_domain').append('<option data-subtext="'+ (data.nickname ?? '') +'" value="' + data.dom_cod + '">' + data.dom_desc + '</option>');
				});

				$('#net_domain').selectpicker('refresh');
				$('#net_domain').selectpicker('val', domainDb);
				
			});
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
				url: './departments_process.php',
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

		function loadInModal(pageBase) {
			let url = pageBase + '.php';
			$(location).prop('href', url);
			// $("#divDetails").load(url);
			// $('#modal').modal();
		}
	</script>
</body>

</html>