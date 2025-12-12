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
		<h4 class="my-4"><i class="fas fa-exchange-alt text-secondary"></i>&nbsp;<?= TRANS('EQUIPMENT_HW_CHANGES'); ?></h4>
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
                    u.nome, i.item_nome, m.*, h.hwa_data, ins.inst_nome 
                FROM 
                    usuarios u, itens i, modelos_itens m, hw_alter h, instituicao ins 
				WHERE 
                    i.item_cod = m.mdit_tipo  AND m.mdit_cod = h.hwa_item AND h.hwa_user = u.user_id AND hwa_inst = ins.inst_cod
				AND hwa_inv = '{$asset_tag}' AND hwa_inst = '{$asset_unit}'  
                ORDER BY h.hwa_data, i.item_nome
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
							<td class="line issue_type"><?= TRANS('COMPONENT'); ?></td>
							<td class="line description"><?= TRANS('DESCRIPTION'); ?></td>
							<td class="line area"><?= TRANS('COL_MODIF_IN'); ?></td>
							<td class="line author"><?= TRANS('COL_MODIF_FOR'); ?></td>
						</tr>
					</thead>
					<tbody>
						<?php

						foreach ($resultado->fetchall() as $row) {

						    ?>
							<tr>
								<td class="line"><?= $row['item_nome']; ?></td>
								<td class="line"><?= $row['mdit_fabricante']."&nbsp;".$row['mdit_desc']."&nbsp;".$row['mdit_desc_capacidade']."&nbsp;".$row['mdit_sufixo']; ?></td>
								<td class="line" data-sort="<?= $row['hwa_data']; ?>"><?= dateScreen($row['hwa_data']); ?></td>
								<td class="line"><?= $row['nome']; ?></td>
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

			$('#table_lists').DataTable({
				paging: true,
				deferRender: true,
				order: [2, 'desc'],
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