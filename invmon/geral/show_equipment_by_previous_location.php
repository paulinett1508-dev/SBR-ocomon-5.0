<?php 
 /*                        Copyright 2023 Flávio Ribeiro

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
  */session_start();

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
	<link rel="stylesheet" type="text/css" href="../../includes/css/switch_radio.css" />
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
		<h4 class="my-4"><i class="fas fa-door-closed text-secondary"></i>&nbsp;<?= TRANS('PREVIOUS_LOCATIONS'); ?></h4>
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



// $request = (isset($_POST) && !empty($_POST) ? $_POST : (isset($_GET) && !empty($_GET) ? $_GET : ''));
$post = (isset($_POST) && !empty($_POST) ? $_POST : '');
if (empty($post)) {
    echo message('danger', 'Ooops!', TRANS('MSG_ERR_NOT_EXECUTE'), '', '', 1);
    return;
}
$terms = "";
$type = (isset($post['type']) ? noHtml($post['type']) : "");
$department = noHtml($post['department']);

if (!empty($type)) {
    $terms .= " AND t.tipo_cod = '{$type}' ";
}


if (!empty($_SESSION['s_allowed_units'])) {
    $hasLimitationByAllowedClients = true;
    $terms .= " AND c.comp_inst IN ({$_SESSION['s_allowed_units']}) ";
}

$query = "SELECT 
            c.comp_inv AS etiqueta, c.comp_inst AS instituicao, c.comp_local AS tipo_local, 
 			i.inst_nome AS instituicao_nome, c.comp_tipo_equip AS tipo, t.tipo_nome AS equipamento, 
			l.local AS locais, l.loc_id as local_cod, m.marc_nome as modelo, m.marc_cod as tipo_marca, 
            f.fab_nome as fabricante, f.fab_cod as tipo_fab, s.situac_cod as situac_cod, 
			h.hist_data AS DATA , 
            extract(DAY FROM hist_data ) AS DIA, 
			extract(MONTH FROM hist_data ) AS MES, 
            extract( year FROM hist_data ) AS ANO 
			
            FROM equipamentos AS c, instituicao AS i, localizacao AS l, historico AS h,
			tipo_equip AS t, marcas_comp as m, fabricantes as f, situacao as s

			WHERE
			c.comp_inv = h.hist_inv AND c.comp_inst = h.hist_inst AND c.comp_fab = f.fab_cod 
            AND h.hist_local = l.loc_id AND h.hist_inv = c.comp_inv  AND i.inst_cod = h.hist_inst 
            AND c.comp_tipo_equip = t.tipo_cod AND m.marc_cod = c.comp_marca AND c.comp_situac = s.situac_cod
            AND c.comp_local <> h.hist_local AND h.hist_local = '{$department}' 
            {$terms} 
            GROUP BY etiqueta, instituicao, tipo_local, instituicao_nome, tipo, 
            equipamento, locais, local_cod, modelo, tipo_marca, fabricante, tipo_fab, situac_cod, DATA, DIA, MES, ANO 
            ORDER BY equipamento, etiqueta
		";

$resultado = $conn->query($query);
$resultado2 = $conn->query($query);
$linhas = $resultado->rowCount();

if (!$linhas) {
    $_SESSION['flash'] = message('info', 'Ooops!', TRANS('MSG_NOT_FOUND_REG_EQUIP'), '', '');
    redirect('search_by_previous_location.php');
    return;
}
$row = $resultado->fetch();

?>
    <h6><?= TRANS('FIELD_TYPE_EQUIP'); ?>: <?= (!empty($type) ? $row['equipamento'] : TRANS('OCO_SEL_ANY')); ?></h6>
    <h6><?= TRANS('DEPARTMENT'); ?>: <?= $row['locais']; ?></h6>

    <table id="table_lists" class="stripe hover order-column row-border" border="0" cellspacing="0" width="100%">

        <thead>
            <tr class="header">
                <td class="line col_model"><?= TRANS('ASSET_TAG'); ?></td>
                <td class="line col_model"><?= TRANS('COL_UNIT'); ?></td>
                <td class="line col_model"><?= TRANS('COL_TYPE'); ?></td>
                <td class="line col_model"><?= TRANS('COL_MODEL'); ?></td>
                <td class="line col_model"><?= TRANS('COL_CURRENT_LOCAL'); ?></td>
                <td class="line col_button"><?= TRANS('MNL_CON_HIST'); ?></td>
            </tr>
        </thead>
        <tbody>
<?php

    $i = 1;
    foreach ($resultado2->fetchall() as $row) {
        $currentLocation = $row['tipo_local'];
		$queryB = "SELECT l.local AS loc_atual FROM localizacao AS l WHERE l.loc_id = '{$currentLocation}'";
		$resultadoB = $conn->query($queryB);
		$rowB = $resultadoB->fetch();
        ?>
        <tr>
            <td class="line"><?= $row['etiqueta']; ?></td>
            <td class="line"><?= $row['instituicao_nome']; ?></td>
            <td class="line"><?= $row['equipamento']; ?></td>
            <td class="line"><?= $row['fabricante'] ." ". $row['modelo']; ?></td>
            <td class="line"><?= $rowB['loc_atual']; ?></td>
            <td class="line"><button type="button" class="btn btn-info btn-sm" onclick="popupS('show_equipment_location_history.php?asset_unit=<?= $row['instituicao']; ?>&asset_tag=<?= $row['etiqueta']; ?>')"><?= TRANS('MNL_CON_HIST'); ?></button></td>
        </tr>
        <?php
        $i++;
    }
    ?>
    </tbody>
    </table>

    <div class="row w-100"></div><br/>
    <div class="row">
        <div class="col-md-10 d-none d-md-block"></div>
        <div class="col-12 col-md-2">
            <!-- <button type="reset" class="btn btn-secondary btn-block" onClick="parent.history.back();"><?= TRANS('BT_RETURN'); ?></button> -->
            <button type="reset" class="btn btn-secondary btn-block close-or-return"><?= TRANS('BT_RETURN'); ?></button>
        </div>
    </div>
    
    <?php




?>
    </div>

	<script src="../../includes/javascript/funcoes-3.0.js"></script>
    <script src="../../includes/components/jquery/jquery.js"></script>
    <script src="../../includes/components/jquery/plentz-jquery-maskmoney/dist/jquery.maskMoney.min.js"></script>
    <script src="../../includes/components/jquery/jquery.initialize.min.js"></script>
	<script src="../../includes/components/bootstrap/js/bootstrap.min.js"></script>
	<script type="text/javascript" charset="utf8" src="../../includes/components/datatables/datatables.js"></script>
    <script type="text/javascript">
    
    $(() => {
        
        if ($('#table_lists').length > 0) {
            $('#table_lists').DataTable({
                paging: true,
                deferRender: true,
                columnDefs: [{
                        searchable: false,
                        orderable: false,
                        targets: ['col_button']
                    }],
                
                "language": {
                    "url": "../../includes/components/datatables/datatables.pt-br.json"
                }
            });
        }
        
        closeOrReturn ();

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