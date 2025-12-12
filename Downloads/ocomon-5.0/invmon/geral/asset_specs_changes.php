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

	<title>OcoMon&nbsp;<?= VERSAO; ?></title>
</head>

<body>
	
	<div class="container">
		<div id="idLoad" class="loading" style="display:none"></div>
	</div>

	<div id="divResult"></div>


	<div class="container-fluid">
		<h4 class="my-4"><i class="fas fa-exchange-alt text-secondary"></i>&nbsp;<?= TRANS('ASSET_SPECS_CHANGES'); ?></h4>
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


        $asset_id = (isset($_GET['asset_id']) ? noHtml($_GET['asset_id']) : '');
        if (empty($asset_id)) {
            echo message('danger', 'Ooops!', TRANS('MSG_ERR_NOT_EXECUTE'), '', '', 1);
            return;
        }
        
		$sql_asset_info = $QRY["full_detail_ini"] . 'AND c.comp_cod = ' . $asset_id; 
		$res = $conn->query($sql_asset_info);
		if (!$res->rowCount()) {
			echo message('danger', 'Ooops!', TRANS('MSG_ERR_NOT_EXECUTE'), '', '', 1);
            return;
		}
		$row = $res->fetch();


		/* A partir da versão 5.0 */
        $changes = getAssetSpecsChanges($conn, $asset_id);
		$hasChanges = count($changes);

		
		/* Versões anteriores à 5.0 */
		$sqlOldVersion = "SELECT 
				u.nome, i.item_nome, m.*, h.hwa_data, ins.inst_nome 
			FROM 
				usuarios u, itens i, modelos_itens m, hw_alter h, instituicao ins 
			WHERE 
				i.item_cod = m.mdit_tipo  AND m.mdit_cod = h.hwa_item AND h.hwa_user = u.user_id AND hwa_inst = ins.inst_cod
			AND hwa_inv = '{$row['etiqueta']}' AND hwa_inst = '{$row['cod_inst']}'  
			ORDER BY h.hwa_data, i.item_nome
		";

		$resOldVersion = $conn->query($sqlOldVersion);
		$hasOldVersion = $resOldVersion->rowCount();


		if (!$hasChanges && !$hasOldVersion) {
			echo message('info', '', TRANS('NO_RECORDS_FOUND'), '', '', true);
			return;
		}



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
            <h6><?= TRANS('COL_TYPE'); ?>: <?= $row['equipamento']; ?></h6>
			<h6><?= TRANS('CLIENT'); ?>: <?= getUnits($conn, null, $row['cod_inst'])['nickname']; ?></h6>
			<h6><?= TRANS('COL_UNIT'); ?>: <?= $row['instituicao']; ?></h6>
			<h6><?= TRANS('DEPARTMENT'); ?>: <?= $row['local']; ?></h6>
			<h6><?= TRANS('ASSET_TAG'); ?>: <?= $row['etiqueta']; ?></h6><br/>

			<?php
			if ($hasChanges) {
				?>
				<table id="table_list_changes" class="stripe hover order-column row-border" border="0" cellspacing="0" width="100%">

					<thead>
						<tr class="header">
							<td class="line issue_type"><?= TRANS('ASSET_TYPE'); ?></td>
							<td class="line description"><?= TRANS('COL_MODEL'); ?></td>
							<td class="line author"><?= TRANS('COL_MODIF_FOR'); ?></td>
							<td class="line area"><?= TRANS('DATE'); ?></td>
						</tr>
					</thead>
					<tbody>
						<?php

						foreach ($changes as $change) {

                            $icon = ($change['action'] == 'add' ? '<span class="text-success"><i class="fas fa-plus"></i></span>' : '<span class="text-danger"><i class="fas fa-minus"></i></span>');
						    ?>
							<tr>
								<td class="line"><?= $icon . "&nbsp;" . $change['tipo_nome']; ?></td>
								<td class="line"><?= $change['fab_nome'] . '&nbsp;' . $change['marc_nome']; ?></td>
								<td class="line"><?= $change['nome']; ?></td>
								<td class="line" data-sort="<?= $change['updated_at']; ?>"><?= dateScreen($change['updated_at']); ?></td>
							</tr>

						    <?php
						}
						?>
					</tbody>
				</table>
			<?php
			}

			if ($hasOldVersion) {
				?>
					<h5 class="mt-5 mb-3 text-danger"><?= TRANS('LEGACY_INFO'); ?></h5>
					<table id="table_lists" class="stripe hover order-column row-border" border="0" cellspacing="0" width="100%">

					<thead>
						<tr class="header">
							<td class="line issue_type"><?= TRANS('ASSET_TYPE'); ?></td>
							<td class="line description"><?= TRANS('COL_MODEL'); ?></td>
							<td class="line author"><?= TRANS('COL_MODIF_FOR'); ?></td>
							<td class="line area"><?= TRANS('DATE'); ?></td>
						</tr>
					</thead>
					<tbody>
						<?php

						foreach ($resOldVersion->fetchall() as $rowOld) {

							?>
							<tr>
								<td class="line"><?= $rowOld['item_nome']; ?></td>
								<td class="line"><?= $rowOld['mdit_fabricante']."&nbsp;".$rowOld['mdit_desc']."&nbsp;".$rowOld['mdit_desc_capacidade']."&nbsp;".$rowOld['mdit_sufixo']; ?></td>
								<td class="line"><?= $rowOld['nome']; ?></td>
								<td class="line" data-sort="<?= $rowOld['hwa_data']; ?>"><?= dateScreen($rowOld['hwa_data']); ?></td>
							</tr>

							<?php
						}
						?>
					</tbody>
					</table>
				<?php
			}
		}

		?>
	</div>

	<script src="../../includes/javascript/funcoes-3.0.js"></script>
    <script src="../../includes/components/jquery/jquery.js"></script>
	<script src="../../includes/components/bootstrap/js/bootstrap.min.js"></script>
	<script type="text/javascript" charset="utf8" src="../../includes/components/datatables/datatables.js"></script>
	<script type="text/javascript">
		$(function() {

			$('#table_list_changes, #table_lists').DataTable({
				paging: true,
				deferRender: true,
				order: [3, 'desc'],
				columnDefs: [{
					searchable: false,
					orderable: false,
					targets: ['editar', 'remover']
				}],
				"language": {
					"url": "../../includes/components/datatables/datatables.pt-br.json"
				}
            });
            
            closeOrReturn ();



			$('#bt-cancel').on('click', function() {
				var url = '<?= $_SERVER['PHP_SELF'] ?>';
				$(location).prop('href', url);
			});
		});

        
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