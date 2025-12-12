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

$exception = "";
$needReview = 1;
$config = getConfig($conn);

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
		<h4 class="my-4"><i class="fas fa-tools text-secondary"></i>&nbsp;<?= TRANS('UPDATE_RELATION_AREAS_ISSUES'); ?></h4>

		<div class="modal fade" id="updateModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header bg-light">
						<h5 class="modal-title" id="exampleModalLabel"><i class="fas fa-tools text-secondary"></i>&nbsp;<?= TRANS('COMPAT_UPDATE'); ?></h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
					<div class="modal-body">
						<?= TRANS('CONFIRM_UPDATE'); ?></span>?
					</div>
					<div class="modal-footer bg-light">
						<button type="button" class="btn btn-secondary" data-dismiss="modal"><?= TRANS('BT_CANCEL'); ?></button>
						<button type="button" id="updateButton" class="btn"><?= TRANS('BT_UPDATE'); ?></button>
					</div>
				</div>
			</div>
		</div>

		<?php

		if (isset($_SESSION['flash']) && !empty($_SESSION['flash'])) {
			echo $_SESSION['flash'];
			$_SESSION['flash'] = '';
		}
		
		if ($config['conf_updated_issues']) {
			echo message('success', TRANS('GREAT'), "<hr>" . TRANS('SYSTEM_UPDATED_ALREADY'), '', '', true);
		} else {

			echo message('danger', TRANS('TXT_IMPORTANT'), "<hr>" . TRANS('MSG_UPDT_TYPES_OF_ISSUES'), '', '', true);

			$data = array();
			$data['total_issues'] = 0;
			$data['total_unique_issues'] = 0;
			$data['total_tickets_to_update'] = 0;
			$data['total_tickets_log_to_update'] = 0;
			$data['total_ids_to_remove'] = 0;
			$data['issues_to_rename_or_delete'] = [];
			$data['ids_to_update'] = [];

			/* Total de tipos de problemas cadastrados */
			$sql = "SELECT count(*) total FROM problemas";
			$res = $conn->query($sql);
			if ($res->rowCount()) {
				$data['total_issues'] = $res->fetch()['total'];
			}

			/* Total de registros repetidos */
			$sql = "SELECT count(*) as total, problema FROM problemas GROUP BY problema having total > 1";
			$res = $conn->query($sql);

			/* Linhas repetidas - Quantidade de tipos de problemas com mais de um registro */
			$data['each_issue_repeated'] = $res->rowCount();
			foreach ($res->fetchAll() as $issue) {
				$data['issues_to_rename_or_delete'][] = $issue['problema'];
			}

			/**
			 * Checagem das alterações necessárias
			 */
			$sql = "SELECT problema FROM problemas GROUP BY problema ORDER BY problema";
			try {
				$res1 = $conn->query($sql);
				$data['total_unique_issues'] = $res1->rowCount();

				if ($res1->rowCount()) {
					/* Para cada registro, faço novo sql buscando apenas pelo tipo específico de problema (descrição textual) */
					foreach ($res1->fetchAll() as $row1) {
						$sql2 = "SELECT prob_id, prob_area as area_id, problema FROM problemas WHERE problema like ('" . $row1['problema'] . "') ";
						try {
							$res2 = $conn->query($sql2);

							/**
							 * Pegando o menor ID do tipo de problema, essa será a chave para a descrição agrupada
							 */
							$sqlMinID = "SELECT min(prob_id) as prob_id FROM problemas WHERE problema like ('" . $row1['problema'] . "') ";
							$resMinID = $conn->query($sqlMinID);
							$min_prob_id = $resMinID->fetch()['prob_id'];

							/** 
							 * No campo prob_id, caso haja mais de um registro com o mesmo nome, só posso inserir o menor
							 */
							foreach ($res2->fetchall() as $row2) {

								/** 
								 * Checar os IDs que deverão ser atualizados nas tabelas que fazem referência ao campo problema :
								 * ocorrencias
								 * ocorrencias_log
								 * Ver se há outras
								 * */
								if (!empty($row2['prob_id']) && $row2['prob_id'] != '-1' && $min_prob_id != $row2['prob_id']) {
									/* Listagem de IDs que serão substituidos */
									$data['ids_to_update'][] = $row2['prob_id'];
								}
							}
						} catch (Exception $e) {
							$exception .= "<hr>" . $e->getMessage();
						}
					}
				}
			} catch (Exception $e) {
				$exception .= "<hr>" . $e->getMessage();
			}

			$data['total_ids_to_remove'] = count($data['ids_to_update']); 

			foreach ($data['ids_to_update'] as $key => $id) {

				if (!empty($id)) {
					$sql = "SELECT COUNT(*) as total FROM ocorrencias WHERE problema = '{$id}' ";
					try {
						$res = $conn->query($sql);
						$data['total_tickets_to_update'] += $res->fetch()['total'];
					} catch (Exception $e) {
						$exception .= "<hr>" . $e->getMessage();
					}

					$sql = "SELECT COUNT(*) as total FROM ocorrencias_log WHERE log_problema = '{$id}'";
					try {
						$res = $conn->query($sql);
						$data['total_tickets_log_to_update'] += $res->fetch()['total'];
					} catch (Exception $e) {
						$exception .= "<hr>" . $e->getMessage();
					}
				}
			}

			// var_dump([
			// 	'Dados' => $data,
			// 	'Erros' => $exception
			// ]);

			if (strlen((string)$exception)) {
				echo message('danger', 'Ooops', TRANS('SOME_ERROR_DONT_PROCEED') . $exception, '', '', true);
				return;
			}


			?>
			<h5 class="ml-4"><?= TRANS('TOTAL_OF_TYPES_OF_ISSUES'); ?>:</h5>
			<ul>
				<li><?= $data['total_issues']; ?></li>
			</ul>
			<?php

			/* Informação sobre a existência ou não de tipos de problemas repetidos */
			if (!count($data['issues_to_rename_or_delete'])) {
				echo message("info", TRANS('GREAT'), TRANS('THERES_NO_TYPES_OF_ISSUES_TO_UPDATE'), '', '', true);
				$needReview = 0;
			} else {

				$needReview = 1;
			?>
				<h5 class="ml-4"><?= TRANS('TYPES_OF_ISSUES_REPEATED'); ?>:</h5>
				<ul>
					<?php
					foreach ($data['issues_to_rename_or_delete'] as $key => $issue) {
					?>
						<li><?= $issue; ?></li>
					<?php
					}
					?>
				</ul>
				<h5 class="ml-4"><?= TRANS('NUMBER_OF_TICKETS_TO_UPDATE'); ?></h5>
				<ul>
					<li><?= $data['total_tickets_to_update']; ?></li>
				</ul>
				<h5 class="ml-4"><?= TRANS('NUMBER_OF_TICKETS_LOG_TO_UPDATE'); ?></h5>
				<ul>
					<li><?= $data['total_tickets_log_to_update']; ?></li>
				</ul>
			<?php
				echo message("info", '', TRANS('THERES_TYPES_OF_ISSUES_TO_UPDATE'), '', '', true);
			}
			?>


			<input type="hidden" name="action" id="action" value="update">
			<input type="hidden" name="need_review" id="need_review" value="<?= $needReview; ?>">
			<div class="form-group row my-4">
				<div class="row w-100"></div>
				<div class="form-group col-md-8 d-none d-md-block">
				</div>
				<div class="form-group col-12 col-md-2 ">
					<button type="button" id="review" name="review" class="btn btn-primary btn-block"><?= TRANS('REVIEW_TYPES_OF_ISSUES'); ?></button>
				</div>
				<div class="form-group col-12 col-md-2 ">
					<button type="button" id="submit" name="submit" class="btn btn-danger btn-block"><?= TRANS('BT_UPDATE_WITHOUT_REVIEW'); ?></button>
				</div>

			</div>
			<?php
		}
	?>

	</div>

	<script src="../../includes/javascript/funcoes-3.0.js"></script>
	<script src="../../includes/components/jquery/jquery.js"></script>
	<script src="../../includes/components/bootstrap/js/bootstrap.min.js"></script>
	<script type="text/javascript">
		$(function() {


			if ($("#need_review").val() == 0) {
				$('#review')
					.removeClass('btn-primary')
					.addClass('btn-secondary')
					.prop('disabled', true);
				$('#submit')
					.text('<?= TRANS('BT_UPDATE'); ?>')
					.removeClass('btn-danger')
					.addClass('btn-primary');
			}


			$('#review').on('click', function() {
				let url = './types_of_issues.php';
				$(location).prop('href', url);
			});

			
			
			$('#submit').on('click', function() {
				confirmUpdate();
			});
			
			
			$('input, select, textarea').on('change', function() {
				$(this).removeClass('is-invalid');
			});

		});


		function confirmUpdate() {
			$('#updateModal').modal();
			$('#updateButton').html('<a class="btn btn-primary" onclick="updateData()"><?= TRANS('BT_UPDATE'); ?></a>');
		}

		function updateData() {

			// e.preventDefault();
			var loading = $(".loading");
			$(document).ajaxStart(function() {
				loading.show();
			});
			$(document).ajaxStop(function() {
				loading.hide();
			});

			$("#submit").prop("disabled", true);
			$.ajax({
				url: './update_issues_areas_process.php',
				method: 'POST',
				data: {
					action: 'update'
				},
				dataType: 'json',
			}).done(function(response) {

				if (!response.success) {
						$('#divResult').html(response.message);
						$("#submit").prop("disabled", false);
						$('#updateModal').modal('hide');
				} else {
					$('#divResult').html('');
					// $("#submit").prop("disabled", false);
					
					var url = 'main_settings.php';
					$(location).prop('href', url);
					return false;
				}
			});
			return false;
		}
	</script>
</body>

</html>