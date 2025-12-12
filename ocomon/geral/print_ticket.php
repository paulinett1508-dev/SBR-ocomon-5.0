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

$GLOBALACCESS = false;


require_once __DIR__ . "/" . "../../includes/include_geral_new.inc.php";
require_once __DIR__ . "/" . "../../includes/classes/ConnectPDO.php";

use includes\classes\ConnectPDO;

$conn = ConnectPDO::getInstance();


/* Pode ser acessado para imprimir tickets globais - sem autenticação */
if (!isset($_SESSION['s_logado']) || empty($_SESSION['s_logado']) || $_SESSION['s_logado'] == 0) {

	if (!isset($_GET['numero']) || !isset($_GET['id'])) {
		$_SESSION['session_expired'] = 1;
		echo "<script>top.window.location = '../../index.php'</script>";
		exit;
	} else {

		$numero = noHtml($_GET['numero']);
		$id = noHtml($_GET['id']);

		$id = str_replace(" ", "+", $id);
		if ($id == getGlobalTicketId($conn, $numero)) {
			$GLOBALACCESS = true;
		} else {
			echo "<script>top.window.location = '../../login.php'</script>";
			exit();
		}
	}
}

if (!$GLOBALACCESS) {
	$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 3, 1);
}


$isAdmin = (isset($_SESSION['s_nivel']) && $_SESSION['s_nivel'] == 1) ? true : false;
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" type="text/css" href="../../includes/css/invoice-print.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap/custom.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/components/fontawesome/css/all.min.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap-select/dist/css/bootstrap-select.min.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/css/my_bootstrap_select.css" />

	<style>
		body {
			font-size: 1.2rem !important;
		}

		.row {
			flex-wrap: nowrap !important;
		}
	</style>

	<title>OcoMon&nbsp;<?= VERSAO; ?></title>
</head>



<?php

$main_worker = getTicketWorkers($conn, (int)$_GET['numero'], 1);
$aux_worker = getTicketWorkers($conn, (int)$_GET['numero'], 2);

$aux_workers = "";
if (!empty($aux_worker)) {
	foreach ($aux_worker as $worker) {
		if (strlen((string)$aux_workers)) $aux_workers .= ', ';
		$aux_workers .= $worker['nome'];
	}
}

$customFields = [];
$customFields = getTicketCustomFields($conn, (int)$_GET['numero']);

/* Tratando os field_ids - aplicando casting para string */
if (!empty($customFields['field_id'])) {
	foreach ($customFields as $key => $value) {
		$customFields[$key]['field_id'] = (string)$value['field_id'];
	}
}


$sql = $QRY["ocorrencias_full_ini"] . " WHERE numero = '" . (int)$_GET['numero'] . "' ORDER BY numero";
try {
	$res = $conn->query($sql);
} catch (Exception $e) {
	echo $e->getMessage();
	exit();
}

$row = $res->fetch();

$isRequester = $row['aberto_por_cod'] == (isset($_SESSION['s_uid']) ? $_SESSION['s_uid'] : 0);
if ($GLOBALACCESS) {
	$isRequester = true;
}


$isAreaAdmin = false;
if (isset($_SESSION['s_uid'])) {
	$managebleAreas = getManagedAreasByUser($conn, $_SESSION['s_uid']);
	$managebleAreas = array_column($managebleAreas, 'sis_id');
	$isAreaAdmin = in_array($row['aberto_por_area'], $managebleAreas);
}

/* Controle para evitar acesso ao chamado por usuarios operadores que não fazem parte da area do chamado */
if (!$isAdmin && !$isRequester && !$isAreaAdmin) {
	$uareas = explode(',', $_SESSION['s_uareas']);
	if (!in_array($row['area_cod'], $uareas)) {
		?>
			<p class="p-3 m-4 text-center"></p>
		<?php
		echo message('danger', 'Ooops!', '<hr />'.TRANS('MSG_TICKET_NOT_ALLOWED_TO_BE_VIEWED'), '', '', true);
		exit;
	}
}

/* Tratamento para excluir os campos que não devem ser exibidos para o usuário final */
$hiddenCustomFields = [];
$profile_id = $row['profile_id'];
if ((isset($_SESSION['s_nivel']) && $_SESSION['s_nivel'] == 3) || $GLOBALACCESS) {
	/* Checagem se há campos que devem ser ocultos para usuários nível somente abertura */
	$hiddenCustomFields = ($profile_id ? explode(',',(string)getScreenInfo($conn, $profile_id)['cfields_user_hidden']) : []);
}

if (!empty($customFields['field_id'])) {
	foreach ($customFields as $key => $field) {
		if (!empty($hiddenCustomFields) && in_array($field['field_id'], $hiddenCustomFields)) {
			unset($customFields[$key]);
		}
	}
}



$sqlPriorityDesc = "SELECT * FROM prior_atend WHERE pr_cod = '" . $row['oco_prior'] . "'";
$resPriority = $conn->query($sqlPriorityDesc);
$rowPriority = $resPriority->fetch();
$rowCatProb = [];

$qryCatProb = "SELECT * FROM problemas as p " .
	"LEFT JOIN sla_solucao as sl on sl.slas_cod = p.prob_sla " .
	"LEFT JOIN prob_tipo_1 as pt1 on pt1.probt1_cod = p.prob_tipo_1 " .
	"LEFT JOIN prob_tipo_2 as pt2 on pt2.probt2_cod = p.prob_tipo_2 " .
	"LEFT JOIN prob_tipo_3 as pt3 on pt3.probt3_cod = p.prob_tipo_3 " .
	" WHERE p.prob_id = " . $row['prob_cod'] . " ";
try {
	$execCatprob = $conn->query($qryCatProb);

	if ($execCatprob->rowCount()) {
		$rowCatProb = $execCatprob->fetch();
	} else {
		$rowCatProb["probt1_desc"] = "";
		$rowCatProb["probt2_desc"] = "";
		$rowCatProb["probt3_desc"] = "";
	}
} catch (Exception $e) {
	echo "<hr>" . $e->getMessage();
}


$defaultFields = [];
$defaultFields[0]['field_id'] = '1';
$defaultFields[0]['field_label'] = TRANS('OPENED_BY');
$defaultFields[0]['field_value'] = $row['aberto_por'];
$defaultFields[1]['field_id'] = '2';
$defaultFields[1]['field_label'] = TRANS('CONTACT');
$defaultFields[1]['field_value'] = $row['contato'];
$defaultFields[2]['field_id'] = '3';
$defaultFields[2]['field_label'] = TRANS('COL_PHONE');
$defaultFields[2]['field_value'] = $row['telefone'];
$defaultFields[3]['field_id'] = '4';
$defaultFields[3]['field_label'] = TRANS('DEPARTMENT');
$defaultFields[3]['field_value'] = $row['setor'];
$defaultFields[4]['field_id'] = '5';
$defaultFields[4]['field_label'] = TRANS('OCO_PRIORITY');
$defaultFields[4]['field_value'] = $rowPriority['pr_desc'];
$defaultFields[5]['field_id'] = '6';
$defaultFields[5]['field_label'] = TRANS('OPENING_DATE');
$defaultFields[5]['field_value'] = dateScreen($row['data_abertura']);
$defaultFields[6]['field_id'] = '7';
$defaultFields[6]['field_label'] = TRANS('ISSUE_TYPE');
$defaultFields[6]['field_value'] = $row['problema'];
$defaultFields[7]['field_id'] = '8';
$defaultFields[7]['field_label'] = TRANS('COL_CAT_PROB');
$defaultFields[7]['field_value'] = $rowCatProb['probt1_desc'] . " | " . $rowCatProb['probt2_desc'] . " | " . $rowCatProb['probt3_desc'];
$defaultFields[8]['field_id'] = '9';
$defaultFields[8]['field_label'] = TRANS('COL_UNIT');
$defaultFields[8]['field_value'] = $row['unidade'];
$defaultFields[9]['field_id'] = '10';
$defaultFields[9]['field_label'] = TRANS('FIELD_TAG_EQUIP');
$defaultFields[9]['field_value'] = $row['etiqueta'];
$defaultFields[10]['field_id'] = '11';
$defaultFields[10]['field_label'] = TRANS('IDX_AGENDAMENTO');
// $defaultFields[10]['field_value'] = dateScreen($row['oco_scheduled_to']);
$defaultFields[10]['field_value'] = dateScreen(getLastScheduledDate($conn, (int)$_GET['numero']));

$sqlAssentamentos = "SELECT a.*, u.* 
						FROM assentamentos a, usuarios u 
						WHERE a.responsavel = u.user_id AND a.ocorrencia='" . (int)$_GET['numero'] . "' 
							AND a.asset_privated = 0 ORDER BY numero";
$resAssentamentos = $conn->query($sqlAssentamentos);
$countAssentamentos = $resAssentamentos->rowCount();

?>

<body>

	<div class="container-fluid" id="toPrint">
		<div class="row ">
			<div class="col-sm-6 mt-md "><img src="../../includes/logos/MAIN_LOGO.png" width="240" class="float-left" alt="logomarca"></div>
			<div class="col-sm-6 mt-md "></div>
		</div>
		<div class="w-100"></div>
		<h5 class="my-4"><i class="fas fa-print text-secondary"></i>&nbsp;<?= TRANS('PRINT_TO_TREATING'); ?></h5>
		<div class="modal" id="modal" tabindex="-1" style="z-index:9001!important">
			<div class="modal-dialog modal-xl">
				<div class="modal-content">
					<div id="divDetails">
					</div>
				</div>
			</div>
		</div>

		<div class="row mb-4 d-print-none">
			<div class="col-sm-6 mt-md ">
				<select class="form-control" id="selDefaultFields" name="selDefaultFields[]" multiple="multiple">
					<?php
					foreach ($defaultFields as $field) {
						echo "<option value='" . $field['field_id'] . "' selected>" . $field['field_label'] . "</option>";
					}
					?>
				</select>
				<small class="form-text text-muted"><?= TRANS('DEFINE_FROM_DEFAULT_FIELDS_TO_PRINT'); ?></small>
			</div>

			<div class="col-sm-6 mt-md ">
				<select class="form-control" id="selCustomFields" name="selCustomFields[]" multiple="multiple">
					<?php
					if (count($customFields) > 0) {
						if (!array_key_exists('field_id', $customFields)) {
							/* O array não está vazio */
							foreach ($customFields as $field) {
								echo "<option value='" . $field['field_id'] . "'>" . $field['field_label'] . "</option>";
							}
						}
					}
					?>
				</select>
				<small class="form-text text-muted"><?= TRANS('DEFINE_FROM_CUSTOM_FIELDS_TO_PRINT'); ?></small>
			</div>
		</div>


		<div class="invoice">
			<header class="clearfix">
				<div class="row">
					<div class="col-sm-6 mt-md">
						<h2 class="h2 mt-none mb-sm text-dark text-bold"><?= TRANS('TICKET_NUMBER'); ?></h2>
						<h4 class="h4 m-none text-dark text-bold"><?= $row['numero']; ?></h4>
						<h4 class="h6 m-none text-dark text-bold"><?= $row['nickname']; ?></h4>
					</div>
					<div class="col-sm-6 text-right mt-md mb-md">
						<!-- mt-md mb-md -->
						<address class="ib mr-xlg">
							<?= TRANS('RESPONSIBLE_AREA'); ?>:&nbsp;<span class="text-dark"><?= $row['area']; ?></span>
							<br />
							<?php
							if (!empty($main_worker)) {
								echo TRANS('MAIN_WORKER');
							?>
								:&nbsp;<span class="text-dark"><?= $main_worker['nome']; ?></span>
								<br />
								<?php
								if (!empty($aux_worker)) {
									echo TRANS('AUX_WORKERS');
								?>
									:&nbsp;<span class="text-dark"><?= $aux_workers; ?></span>
									<br />
								<?php
								}
							} else {
								echo TRANS('FIELD_LAST_OPERATOR');
								?>
								:&nbsp;<span class="text-dark"><?= $row['nome']; ?></span>
								<br />
							<?php
							}
							?>

							<?= TRANS('COL_STATUS'); ?>:&nbsp;<span class="text-dark"><?= $row['chamado_status']; ?></span>
							<br />
							<?= TRANS('PRINTING_DATE'); ?>:&nbsp;<span class="text-dark"><?= dateScreen(date("Y-m-d H:i:s")); ?></span>
						</address>
					</div>
				</div>
			</header>

			<div id="default_section">
				<!-- Conteúdo via js -->
			</div>

			<div class="bill-to ">
				<div class="row">
					<div class="col-md-12">
						<div class="bill-to">
							<!-- <p class="h5 mb-xs text-dark text-semibold">To:</p> -->
							<address>
								<?= TRANS('DESCRIPTION'); ?>:&nbsp;<span class="text-dark"><?= $row['descricao']; ?></span>
							</address>
						</div>
					</div>
				</div>
			</div>


			<!-- Nova seção para campos customizados -->
			<div id="custom_section">
				<!-- Conteúdo via js -->
			</div>



			<div class="table-responsive">
				<table class="table invoice-items">
					<thead>
						<tr class="h6 text-dark">
							<th id="cell-desc" class="text-semibold"><?= TRANS('TICKET_ENTRY'); ?></th>
							<th id="cell-type" class="text-semibold"><?= TRANS('COL_TYPE'); ?></th>
							<th id="cell-id" class="text-semibold"><?= TRANS('DATE'); ?></th>
							<th id="cell-author" class=" text-semibold"><?= TRANS('AUTHOR'); ?></th>
						</tr>
					</thead>
					<tbody>

						<?php
						foreach ($resAssentamentos->fetchall() as $rowEntries) {
						?>
							<tr>
								<td><?= $rowEntries['assentamento']; ?></td>
								<td><?= getEntryType($rowEntries['tipo_assentamento']); ?></td>
								<td><?= dateScreen($rowEntries['data']); ?></td>
								<td><?= $rowEntries['nome']; ?></td>
							</tr>
						<?php
						}
						?>
					</tbody>
				</table>
			</div>

			<div class="table-responsive mt-5">
				<table class="table invoice-items">
					<thead>
						<tr class="h6 text-dark">
							<th class="text-semibold"><?= TRANS('OBSERVATIONS'); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td></td>
						</tr>
						<tr>
							<td></td>
						</tr>
						<tr>
							<td></td>
						</tr>
						<tr>
							<td></td>
						</tr>
						<tr>
							<td></td>
						</tr>
						<tr>
							<td></td>
						</tr>
					</tbody>
				</table>
			</div>

			<div class="table-responsive mt-5">
				<table class="table ">
					<!-- invoice-items -->
					<thead>
						<tr class="h6 text-dark">
							<th class="text-semibold"><?= TRANS('REQUESTER_SIGNATURE'); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td></td>
						</tr>
					</tbody>
				</table>
			</div>
			<div class="table-responsive mt-0">
				<table class="table invoice-items">
					<thead>
						<tr class="h6 text-dark">
							<th class="text-semibold"><?= TRANS('TECHNICIAN_SIGNATURE'); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
		<div class="container-fluid d-print-none mb-4">
			<div class="row justify-content-end">
				<!-- <div class="col-2"><button type="button" id="toPdf" class="btn btn-danger btn-block"><?= TRANS('FIELD_PRINT_OCCO'); ?></button></div> -->
				<div class="col-2"><button type="reset" class="btn btn-primary btn-block" onClick="window.print();"><?= TRANS('FIELD_PRINT_OCCO'); ?></button></div>
				<div class="col-2"><button type="reset" class="btn btn-secondary btn-block" onClick="parent.history.back();"><?= TRANS('BT_RETURN'); ?></button></div>
			</div>
		</div>

	</div>

	<script src="../../includes/components/jquery/jquery.js"></script>
	<script src="../../includes/components/bootstrap/js/bootstrap.bundle.js"></script>
	<script src="../../includes/components/bootstrap-select/dist/js/bootstrap-select.min.js"></script>

	<script type="text/javascript">
		$(function() {


			$.fn.selectpicker.Constructor.BootstrapVersion = '4';
			$('#selDefaultFields').selectpicker({
				title: "<?= TRANS('DEFAULT_FIELDS', '', 1); ?>",
				actionsBox: true,
				deselectAllText: "<?= TRANS('DESELECT_ALL', '', 1); ?>",
				selectAllText: "<?= TRANS('SELECT_ALL', '', 1); ?>",
				liveSearch: true,
				liveSearchNormalize: true,
				liveSearchPlaceholder: "<?= TRANS('BT_SEARCH', '', 1); ?>",
				noneResultsText: "<?= TRANS('NO_RECORDS_FOUND', '', 1); ?> {0}",
				style: "",
				styleBase: "form-control input-select-multi",
			});
			$('#selCustomFields').selectpicker({
				title: "<?= TRANS('CUSTOM_FIELDS', '', 1); ?>",
				actionsBox: true,
				deselectAllText: "<?= TRANS('DESELECT_ALL', '', 1); ?>",
				selectAllText: "<?= TRANS('SELECT_ALL', '', 1); ?>",
				liveSearch: true,
				liveSearchNormalize: true,
				liveSearchPlaceholder: "<?= TRANS('BT_SEARCH', '', 1); ?>",
				noneResultsText: "<?= TRANS('NO_RECORDS_FOUND', '', 1); ?> {0}",
				style: "",
				styleBase: "form-control input-select-multi",
			});


			renderDefaultFields($('#selDefaultFields').val());
			// renderCustomFields([]);
			renderCustomFields($('#selCustomFields').val());

			$('#selCustomFields').on('change', function() {
				renderCustomFields($(this).val());
			});
			$('#selDefaultFields').on('change', function() {
				renderDefaultFields($(this).val());
			});

		});

		/*  Funções */
		function renderCustomFields(fieldIds) {

			var customFields = <?= json_encode($customFields); ?>;
			var hasCustomFields = false;

			if (customFields.length > 0) {

				let html = '<hr>';
				html += '<div class="bill-to ">';
				html += '<div class="row">';
				/* Coluna 1 */
				html += '<div class="col-md-6">';
				html += '<div class="bill-to">';
				html += '<address>';
				// for (var i in customFields) {
				// 	if (i % 2 == 0) {
				// 		if (fieldIds.indexOf(customFields[i]['field_id']) != -1) {
				// 			hasCustomFields = true;
				// 			html += customFields[i]['field_label'];
				// 			html += ':&nbsp;<span class="text-dark">' + (customFields[i]['field_value'] ?? "") + '</span><br />';
				// 		}
				// 	}
				// }
				for (var i in customFields) {
					if (i % 2 == 0) {

						let needle = customFields[i]['field_id'];
						needle = needle.toString();
						if (fieldIds.indexOf(needle) != -1) {
							hasCustomFields = true;
							html += customFields[i]['field_label'];
							html += ':&nbsp;<span class="text-dark">' + (customFields[i]['field_value'] ?? "") + '</span><br />';
						}
					}
				}
				html += '</address>';
				html += '</div>';
				html += '</div>';
				/* Encerramento da coluna 1 */

				/* Coluna 2 */
				html += '<div class="col-md-6">';
				html += '<div class="bill-to text-right text-muted">';
				html += '<p class="mb-none">';
				// for (var i in customFields) {
				// 	if (i % 2 != 0) {

				// 		if (fieldIds.indexOf(customFields[i]['field_id']) != -1) {
				// 			hasCustomFields = true;
				// 			html += customFields[i]['field_label'];
				// 			html += ':&nbsp;<span class="text-dark">' + (customFields[i]['field_value'] ?? "") + '</span><br />';
				// 		}
				// 	}
				// }
				for (var i in customFields) {
					if (i % 2 != 0) {

						let needle = customFields[i]['field_id'];
						needle = needle.toString();
						if (fieldIds.indexOf(needle) != -1) {
							hasCustomFields = true;
							html += customFields[i]['field_label'];
							html += ':&nbsp;<span class="text-dark">' + (customFields[i]['field_value'] ?? "") + '</span><br />';
						}
					}
				}
				html += '</p>';
				html += '</div></div>';
				/* Encerramento da coluna 2 */
				html += '</div></div>';

				if (hasCustomFields) {
					$('#custom_section').empty().append(html);
				} else {
					$('#custom_section').empty();
				}
			}
		}

		function renderDefaultFields(fieldIds) {

			var defaultFields = <?= json_encode($defaultFields); ?>;
			let hasDefaultFields = false;

			if (defaultFields.length > 0) {

				let html = '';
				html += '<div class="bill-to ">';
				html += '<div class="row">';
				/* Coluna 1 */
				html += '<div class="col-md-6">';
				html += '<div class="bill-to">';
				html += '<address>';
				for (var i in defaultFields) {
					if (i % 2 == 0) {
						if (fieldIds.indexOf(defaultFields[i]['field_id']) != -1) {
							hasDefaultFields = true;
							html += defaultFields[i]['field_label'];
							html += ':&nbsp;<span class="text-dark">' + (defaultFields[i]['field_value'] ?? "") + '</span><br />';
						}
					}
				}
				html += '</address>';
				html += '</div>';
				html += '</div>';
				/* Encerramento da coluna 1 */

				/* Coluna 2 */
				html += '<div class="col-md-6">';
				html += '<div class="bill-to text-right text-muted">';
				html += '<p class="mb-none">';
				for (var i in defaultFields) {
					if (i % 2 != 0) {

						if (fieldIds.indexOf(defaultFields[i]['field_id']) != -1) {
							hasDefaultFields = true;
							html += defaultFields[i]['field_label'];
							html += ':&nbsp;<span class="text-dark">' + (defaultFields[i]['field_value'] ?? "") + '</span><br />';
						}
					}
				}
				html += '</p>';
				html += '</div></div>';
				/* Encerramento da coluna 2 */
				html += '</div></div><hr>';

				if (hasDefaultFields) {
					$('#default_section').empty().append(html);
				} else {
					$('#default_section').empty();
				}
			}
		}

		// window.print();
	</script>
</body>

</html>