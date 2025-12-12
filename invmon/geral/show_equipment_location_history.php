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
		<h4 class="my-4"><i class="fas fa-door-closed text-secondary"></i>&nbsp;<?= TRANS('HNT_HISTORY_LOCAL_EQUIP'); ?></h4>
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

if (isset($_POST['from_menu'])){
    $BT_TEXT = "Voltar";
    $GETOUT = "javascript:history.back()";
} else {
    $BT_TEXT = TRANS('LINK_CLOSE');
    $GETOUT = "javascript:self.close()";
}

$request = (isset($_POST) && !empty($_POST) ? $_POST : (isset($_GET) && !empty($_GET) ? $_GET : ''));
if (empty($request)) {
    echo message('danger', 'Ooops!', TRANS('MSG_ERR_NOT_EXECUTE'), '', '', 1);
    return;
}
$asset_tag = (isset($request['asset_tag']) && !empty($request['asset_tag']) ? noHtml($request['asset_tag']) : '');
$asset_unit = (isset($request['asset_unit']) && !empty($request['asset_unit']) ? noHtml($request['asset_unit']) : '');

if (empty($asset_tag) || empty($asset_unit)) {
    echo message('danger', 'Ooops!', TRANS('MSG_ERR_NOT_EXECUTE'), '', '', 1);
    return;
}

$queryTotal = "SELECT * FROM equipamentos";
$resultadoTotal = $conn->query($queryTotal);
$linhasTotal = $resultadoTotal->rowCount();

$query = "SELECT 
        c.comp_inv AS etiqueta, c.comp_inst AS instituicao, c.comp_local AS tipo_local, 
        i.inst_nome AS instituicao_nome, c.comp_tipo_equip AS tipo, 
        t.tipo_nome AS equipamento, s.situac_cod AS situac_cod, 
        l.local AS locais, l.loc_id AS local_cod, h.hist_data AS data, 

        extract(day FROM hist_data)AS DIA, 
        extract(month FROM hist_data)AS MES, 
        extract(year FROM hist_data)AS ANO 

        FROM equipamentos AS c, instituicao AS i, 
        localizacao AS l, historico AS h, tipo_equip AS t, situacao AS s 

        WHERE 
        c.comp_inv = h.hist_inv AND c.comp_inst = h.hist_inst AND h.hist_local = l.loc_id AND h.hist_inv = '{$asset_tag}' 
        AND h.hist_inst = '{$asset_unit}' AND i.inst_cod = h.hist_inst AND c.comp_tipo_equip = t.tipo_cod 
        AND c.comp_situac = s.situac_cod 
        ORDER BY data DESC";

$resultado = $conn->query($query);
$resultado2 = $conn->query($query);
$linhas = $resultado->rowCount();

if (!$linhas) {
    echo message('info', 'Ooops!', TRANS('TXT_NOT_FOUND_EQUIP_CAD_SYSTEM'), '', '', 1);
    return;
}
$row = $resultado->fetch();

?>
    <h6><?= TRANS('COL_UNIT'); ?>: <?= $row['instituicao_nome']; ?></h6>
    <h6><?= TRANS('ASSET_TAG'); ?>: <?= $row['etiqueta']; ?></h6>
    <h6><?= TRANS('FIELD_TYPE_EQUIP'); ?>: <?= $row['equipamento']; ?></h6>

    <table id="table_lists" class="stripe hover order-column row-border" border="0" cellspacing="0" width="100%">

        <thead>
            <tr class="header">
                <td class="line col_sequence">#</td>
                <td class="line col_model"><?= TRANS('DEPARTMENT'); ?></td>
                <td class="line col_model"><?= TRANS('DATE'); ?></td>
            </tr>
        </thead>
        <tbody>
<?php

    $i = 1;
    foreach ($resultado2->fetchall() as $row) {
        $currentLocation = '';
        if ($i == 1) {
            $currentLocation = '<span class="badge badge-info p-2">'. TRANS('CURRENT_DEPARTMENT') .'</span>';
        }
        ?>
        <tr>
            <td class="line"><?= $i; ?></td>
            <td class="line"><?= $row['locais'] . " " . $currentLocation ?></td>
            <td class="line" data-sort="<?= $row['ANO']."-".$row['MES']."-".$row['DIA']?>"><?= $row['DIA']."/".$row['MES']."/".$row['ANO']; ?></td>
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