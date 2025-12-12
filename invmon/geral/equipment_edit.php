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

$config = getConfig($conn);

/* Para manter a compatibilidade com versões antigas */
$table = "equipxpieces";
$clausule = $QRY['componentexequip_ini'];
$sqlTest = "SELECT * FROM {$table}";
try {
    $conn->query($sqlTest);
}
catch (Exception $e) {
    $table = "equipXpieces";
    $clausule = $QRY['componenteXequip_ini'];
}

$type = "";

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

$query = $QRY["full_detail_ini"];
$query.= " AND (c.comp_inv = '{$asset_tag}') AND (inst.inst_cod = '{$asset_unit}') ";
$query.= $QRY["full_detail_fim"];


try {
    $res = $conn->query($query);
    $row = $res->fetch();
}
catch (Exception $e) {
    $exception .= "<hr>" . $e->getMessage();
    echo message('danger', 'Ooops!', $exception, '', '', 1);
    return;
}


/* Anexos */
$sqlFiles = "SELECT  i.* FROM imagens i  WHERE 
                i.img_inv = '{$asset_tag}' AND i.img_inst = '{$asset_unit}' 
            ORDER BY i.img_cod ";
$resultFiles = $conn->query($sqlFiles);
$hasFiles = $resultFiles->rowCount();

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" type="text/css" href="../../includes/css/estilos.css" />
	<link rel="stylesheet" type="text/css" href="../../includes/css/switch_radio.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/jquery/datetimepicker/jquery.datetimepicker.css" />
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
		<h4 class="my-4"><i class="fas fa-hdd text-secondary"></i>&nbsp;<?= TRANS('MNL_EQUIPAMENTOS'); ?></h4>
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

		
        

		if ((!isset($_GET['action'])) && !isset($_POST['submit'])) {

			?>
			<h6><?= TRANS('BT_EDIT'); ?></h6>
            
            
			<form name="form" method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form" enctype="multipart/form-data">
				<?= csrf_input(); ?>
				<div class="form-group row my-4">
                    
                    <h6 class="w-100 mt-5 ml-5 border-top p-4"><i class="fas fa-info-circle text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('BASIC_INFORMATIONS')); ?></h6>

                    <label for="type" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_TYPE'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2" id="type" name="type" required>
                            <option value=""><?= TRANS('SEL_TYPE_EQUIP'); ?></option>
                            <?php
                            $sql = "SELECT * FROM tipo_equip ORDER BY tipo_nome";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                                ?>
								<option value="<?= $rowType['tipo_cod']; ?>"
                                <?= ($row['tipo'] == $rowType['tipo_cod'] ? ' selected' : ''); ?>
                                ><?= $rowType['tipo_nome']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
					</div>


                    <label for="manufacturer" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_MANUFACTURER'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2" id="manufacturer" name="manufacturer" required>
                            <option value=""><?= TRANS('SEL_MANUFACTURER'); ?></option>
                            <?php
                            $sql = "SELECT * FROM fabricantes ORDER BY fab_nome";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                                ?>
								<option value="<?= $rowType['fab_cod']; ?>"
                                <?= ($row['fab_cod'] == $rowType['fab_cod'] ? ' selected' : ''); ?>
                                ><?= $rowType['fab_nome']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
					</div>

                    <label for="model_full" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_MODEL'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2" id="model_full" name="model_full" required>
                            <option value=""><?= TRANS('SEL_MODEL'); ?></option>
                            
                        </select>
					</div>
					
                
                    <label for="serial_number" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('SERIAL_NUMBER'); ?></label>
					<div class="form-group col-md-4">
						<input type="text" class="form-control " id="serial_number" name="serial_number" value="<?= $row['serial']; ?>" />
                    </div>
					
                    <label for="department" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('DEPARTMENT'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2" id="department" name="department" required>
                            <option value=""><?= TRANS('SEL_DEPARTMENT'); ?></option>
                            <?php
                            $sql = "SELECT * FROM localizacao ORDER BY local";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                                ?>
								<option value="<?= $rowType['loc_id']; ?>"
                                <?= ($row['tipo_local'] == $rowType['loc_id'] ? ' selected' : ''); ?>
                                ><?= $rowType['local']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
					</div>

                    <label for="condition" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('STATE'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2" id="condition" name="condition" required>
                            <option value=""><?= TRANS('STATE'); ?></option>
                            <?php
                            $sql = "SELECT * FROM situacao ORDER BY situac_nome";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                                ?>
								<option value="<?= $rowType['situac_cod']; ?>"
                                <?= ($row['situac_cod'] == $rowType['situac_cod'] ? ' selected' : ''); ?>
                                ><?= $rowType['situac_nome']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
					</div>

                    <label for="net_name" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('NET_NAME'); ?></label>
					<div class="form-group col-md-4">
						<input type="text" class="form-control " id="net_name" name="net_name" value="<?= $row['nome']; ?>" />
					</div>


                    <label class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('ATTACH_FILE'); ?></label>
					<div class="form-group col-md-4">
						<div class="field_wrapper" id="field_wrapper">
							<div class="input-group">
								<div class="input-group-prepend">
									<div class="input-group-text">
										<a href="javascript:void(0);" class="add_button" title="<?= TRANS('TO_ATTACH_ANOTHER'); ?>"><i class="fa fa-plus"></i></a>
									</div>
								</div>
								<div class="custom-file">
									<input type="file" class="custom-file-input" name="anexo[]" id="idInputFile" aria-describedby="inputGroupFileAddon01" lang="br">
									<label class="custom-file-label text-truncate" for="inputGroupFile01"><?= TRANS('CHOOSE_FILE'); ?></label>
								</div>
							</div>
						</div>
					</div>

                    <h6 class="w-100 mt-5 ml-5 border-top p-4"><i class="fas fa-hdd text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('SUBTTL_INFO_CONFIG_EQUIP')); ?></h6>


                    <label for="motherboard" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('MOTHERBOARD'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2" id="motherboard" name="motherboard" >
                            <option value=""><?= TRANS('SEL_NONE'); ?></option>
                            <?php
                            $sql = "SELECT * FROM modelos_itens WHERE mdit_tipo = 10 ORDER BY mdit_fabricante, mdit_desc";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                                ?>
								<option value="<?= $rowType['mdit_cod']; ?>"
                                <?= ($row['cod_mb'] == $rowType['mdit_cod'] ? ' selected' : ''); ?>
                                ><?= $rowType['mdit_fabricante'] . " " . $rowType['mdit_desc'] . " " . $rowType['mdit_desc_capacidade'] . "" . $rowType['mdit_sufixo']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
					</div>

                    <label for="processor" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('PROCESSOR'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2" id="processor" name="processor" >
                            <option value=""><?= TRANS('SEL_NONE'); ?></option>
                            <?php
                            $sql = "SELECT * FROM modelos_itens WHERE mdit_tipo = 11 ORDER BY mdit_fabricante, mdit_desc";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                                ?>
								<option value="<?= $rowType['mdit_cod']; ?>"
                                <?= ($row['cod_processador'] == $rowType['mdit_cod'] ? ' selected' : ''); ?>
                                ><?= $rowType['mdit_fabricante'] . " " . $rowType['mdit_desc'] . " " . $rowType['mdit_desc_capacidade'] . "" . $rowType['mdit_sufixo']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
					</div>

                    <label for="memory" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CARD_MEMORY'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2" id="memory" name="memory" >
                            <option value=""><?= TRANS('SEL_NONE'); ?></option>
                            <?php
                            $sql = "SELECT * FROM modelos_itens WHERE mdit_tipo = 7 ORDER BY mdit_fabricante, mdit_desc";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                                ?>
								<option value="<?= $rowType['mdit_cod']; ?>"
                                <?= ($row['cod_memoria'] == $rowType['mdit_cod'] ? ' selected' : ''); ?>
                                ><?= $rowType['mdit_fabricante'] . " " . $rowType['mdit_desc'] . " " . $rowType['mdit_desc_capacidade'] . "" . $rowType['mdit_sufixo']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
					</div>

                    <label for="video" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CARD_VIDEO'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2" id="video" name="video" >
                            <option value=""><?= TRANS('SEL_NONE'); ?></option>
                            <?php
                            $sql = "SELECT * FROM modelos_itens WHERE mdit_tipo = 2 ORDER BY mdit_fabricante, mdit_desc";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                                ?>
								<option value="<?= $rowType['mdit_cod']; ?>"
                                <?= ($row['cod_video'] == $rowType['mdit_cod'] ? ' selected' : ''); ?>
                                ><?= $rowType['mdit_fabricante'] . " " . $rowType['mdit_desc'] . " " . $rowType['mdit_desc_capacidade'] . "" . $rowType['mdit_sufixo']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
					</div>

                    <label for="sound" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CARD_SOUND'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2" id="sound" name="sound" >
                            <option value=""><?= TRANS('SEL_NONE'); ?></option>
                            <?php
                            $sql = "SELECT * FROM modelos_itens WHERE mdit_tipo = 4 ORDER BY mdit_fabricante, mdit_desc";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                                ?>
								<option value="<?= $rowType['mdit_cod']; ?>"
                                <?= ($row['cod_som'] == $rowType['mdit_cod'] ? ' selected' : ''); ?>
                                ><?= $rowType['mdit_fabricante'] . " " . $rowType['mdit_desc'] . " " . $rowType['mdit_desc_capacidade'] . "" . $rowType['mdit_sufixo']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
					</div>


                    <label for="network" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CARD_NETWORK'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2" id="network" name="network" >
                            <option value=""><?= TRANS('SEL_NONE'); ?></option>
                            <?php
                            $sql = "SELECT * FROM modelos_itens WHERE mdit_tipo = 3 ORDER BY mdit_fabricante, mdit_desc";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                                ?>
								<option value="<?= $rowType['mdit_cod']; ?>"
                                <?= ($row['cod_rede'] == $rowType['mdit_cod'] ? ' selected' : ''); ?>
                                ><?= $rowType['mdit_fabricante'] . " " . $rowType['mdit_desc'] . " " . $rowType['mdit_desc_capacidade'] . "" . $rowType['mdit_sufixo']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
					</div>

                    <label for="modem" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CARD_MODEN'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2" id="modem" name="modem" >
                            <option value=""><?= TRANS('SEL_NONE'); ?></option>
                            <?php
                            $sql = "SELECT * FROM modelos_itens WHERE mdit_tipo = 6 ORDER BY mdit_fabricante, mdit_desc";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                                ?>
								<option value="<?= $rowType['mdit_cod']; ?>"
                                <?= ($row['cod_modem'] == $rowType['mdit_cod'] ? ' selected' : ''); ?>
                                ><?= $rowType['mdit_fabricante'] . " " . $rowType['mdit_desc'] . " " . $rowType['mdit_desc_capacidade'] . "" . $rowType['mdit_sufixo']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
					</div>

                    <label for="hdd" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('MNL_HD'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2" id="hdd" name="hdd" >
                            <option value=""><?= TRANS('SEL_NONE'); ?></option>
                            <?php
                            $sql = "SELECT * FROM modelos_itens WHERE mdit_tipo = 1 ORDER BY mdit_fabricante, mdit_desc";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                                ?>
								<option value="<?= $rowType['mdit_cod']; ?>"
                                <?= ($row['cod_hd'] == $rowType['mdit_cod'] ? ' selected' : ''); ?>
                                ><?= $rowType['mdit_fabricante'] . " " . $rowType['mdit_desc'] . " " . $rowType['mdit_desc_capacidade'] . "" . $rowType['mdit_sufixo']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
					</div>

                    <label for="recorder" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_RECORD_CD'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2" id="recorder" name="recorder" >
                            <option value=""><?= TRANS('SEL_NONE'); ?></option>
                            <?php
                            $sql = "SELECT * FROM modelos_itens WHERE mdit_tipo = 9 ORDER BY mdit_fabricante, mdit_desc";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                                ?>
								<option value="<?= $rowType['mdit_cod']; ?>"
                                <?= ($row['cod_gravador'] == $rowType['mdit_cod'] ? ' selected' : ''); ?>
                                ><?= $rowType['mdit_fabricante'] . " " . $rowType['mdit_desc'] . " " . $rowType['mdit_desc_capacidade'] . "" . $rowType['mdit_sufixo']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
					</div>

                    <label for="cdrom" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_CDROM'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2" id="cdrom" name="cdrom" >
                            <option value=""><?= TRANS('SEL_NONE'); ?></option>
                            <?php
                            $sql = "SELECT * FROM modelos_itens WHERE mdit_tipo = 5 ORDER BY mdit_fabricante, mdit_desc";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                                ?>
								<option value="<?= $rowType['mdit_cod']; ?>"
                                <?= ($row['cod_cdrom'] == $rowType['mdit_cod'] ? ' selected' : ''); ?>
                                ><?= $rowType['mdit_fabricante'] . " " . $rowType['mdit_desc'] . " " . $rowType['mdit_desc_capacidade'] . "" . $rowType['mdit_sufixo']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
					</div>

                    <label for="dvdrom" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('DVD'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2" id="dvdrom" name="dvdrom" >
                            <option value=""><?= TRANS('SEL_NONE'); ?></option>
                            <?php
                            $sql = "SELECT * FROM modelos_itens WHERE mdit_tipo = 8 ORDER BY mdit_fabricante, mdit_desc";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                                ?>
								<option value="<?= $rowType['mdit_cod']; ?>"
                                <?= ($row['cod_dvd'] == $rowType['mdit_cod'] ? ' selected' : ''); ?>
                                ><?= $rowType['mdit_fabricante'] . " " . $rowType['mdit_desc'] . " " . $rowType['mdit_desc_capacidade'] . "" . $rowType['mdit_sufixo']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
					</div>



                    <h6 class="w-100 mt-5 ml-5 border-top p-4"><i class="fas fa-print text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('SUBTTL_INFO_ITEM_ESPEC')); ?></h6>

                    <label for="printer_type" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_TYPE_PRINTER'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2" id="printer_type" name="printer_type" >
                            <option value=""><?= TRANS('SEL_TYPE_PRINTER'); ?></option>
                            <?php
                            $sql = "SELECT * FROM tipo_imp ORDER BY tipo_imp_nome";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                                ?>
								<option value="<?= $rowType['tipo_imp_cod']; ?>"
                                <?= ($row['tipo_imp'] == $rowType['tipo_imp_cod'] ? ' selected' : ''); ?>
                                ><?= $rowType['tipo_imp_nome']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
					</div>

                    <label for="monitor_size" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_MONITOR'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2" id="monitor_size" name="monitor_size" >
                            <option value=""><?= TRANS('SEL_SIZE_MONITOR'); ?></option>
                            <?php
                            $sql = "SELECT * FROM polegada ORDER BY pole_nome";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                                ?>
								<option value="<?= $rowType['pole_cod']; ?>"
                                <?= ($row['polegada_cod'] == $rowType['pole_cod'] ? ' selected' : ''); ?>
                                ><?= $rowType['pole_nome']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
					</div>

                    <label for="scanner_resolution" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_SCANNER'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2" id="scanner_resolution" name="scanner_resolution" >
                            <option value=""><?= TRANS('SEL_RESOLUT_SCANNER'); ?></option>
                            <?php
                            $sql = "SELECT * FROM resolucao ORDER BY resol_nome";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                                ?>
								<option value="<?= $rowType['resol_cod']; ?>"
                                <?= ($row['resolucao_cod'] == $rowType['resol_cod'] ? ' selected' : ''); ?>
                                ><?= $rowType['resol_nome']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
					</div>

                    <h6 class="w-100 mt-5 ml-5 border-top p-4"><i class="fas fa-file-invoice-dollar text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('SUBTTL_INFO_COUNT')); ?></h6>

					<label for="asset_unit" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_UNIT'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2" id="asset_unit" name="asset_unit" required>
                            <option value=""><?= TRANS('SEL_UNIT'); ?></option>
                            <?php
                            $sql = "SELECT * FROM instituicao ORDER BY inst_nome";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                                ?>
								<option value="<?= $rowType['inst_cod']; ?>"
                                <?= ($row['cod_inst'] == $rowType['inst_cod'] ? ' selected' : ''); ?>
                                ><?= $rowType['inst_nome']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
					</div>

                    <label for="asset_tag" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('ASSET_TAG'); ?></label>
					<div class="form-group col-md-4">
						<input type="text" class="form-control " id="asset_tag" name="asset_tag" value="<?= $row['etiqueta']; ?>" required />
                    </div>

                    <label for="invoice_number" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_NF'); ?></label>
					<div class="form-group col-md-4">
						<input type="text" class="form-control " id="invoice_number" name="invoice_number" value="<?= $row['nota']; ?>" />
					</div>


					<label for="cost_center" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COST_CENTER'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2" id="cost_center" name="cost_center" >
                            <option value=""><?= TRANS('COST_CENTER'); ?></option>
                            <?php
                            $sql = "SELECT * FROM `" . DB_CCUSTO . "`." . TB_CCUSTO . "  ORDER BY " . CCUSTO_DESC . "";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                                ?>
								<option value="<?= $rowType[CCUSTO_ID]; ?>"
                                <?= ($row['ccusto'] == $rowType[CCUSTO_ID] ? ' selected' : ''); ?>
                                ><?= $rowType[CCUSTO_DESC] . " - " . $rowType[CCUSTO_COD]; ?></option>
                                <?php
                            }
                            ?>
                        </select>
					</div>

                    <label for="price" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_VALUE'); ?></label>
					<div class="form-group col-md-4">
						<input type="text" class="form-control " id="price" name="price" value="<?= priceScreen($row['valor']); ?>" />
					</div>
					
                    <label for="purchase_date" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('PURCHASE_DATE'); ?></label>
					<div class="form-group col-md-4">
						<input type="text" class="form-control " id="purchase_date" name="purchase_date" autocomplete="off" value="<?= dateScreen($row['data_compra'],1); ?>" />
					</div>


                    <label for="supplier" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_VENDOR'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2" id="supplier" name="supplier" >
                            <option value=""><?= TRANS('OCO_SEL_VENDOR'); ?></option>
                            <?php
                            $sql = "SELECT * FROM fornecedores ORDER BY forn_nome";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                                ?>
								<option value="<?= $rowType['forn_cod']; ?>"
                                <?= ($row['fornecedor_cod'] == $rowType['forn_cod'] ? ' selected' : ''); ?>
                                ><?= $rowType['forn_nome']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
					</div>

                    <label for="assistance" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('ASSISTENCE'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2" id="assistance" name="assistance" >
                            <option value=""><?= TRANS('SEL_TYPE_ASSIST'); ?></option>
                            <?php
                            $sql = "SELECT * FROM assistencia ORDER BY assist_desc";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                                ?>
								<option value="<?= $rowType['assist_cod']; ?>"
                                <?= ($row['assistencia_cod'] == $rowType['assist_cod'] ? ' selected' : ''); ?>
                                ><?= $rowType['assist_desc']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
					</div>

					<label for="warranty_type" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_TYPE_WARRANTY'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2" id="warranty_type" name="warranty_type" >
                            <option value=""><?= TRANS('SEL_WARRANTY_TYPE'); ?></option>
                            <?php
                            $sql = "SELECT * FROM tipo_garantia ORDER BY tipo_garant_nome";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                                ?>
								<option value="<?= $rowType['tipo_garant_cod']; ?>"
                                <?= ($row['garantia_cod'] == $rowType['tipo_garant_cod'] ? ' selected' : ''); ?>
                                ><?= $rowType['tipo_garant_nome']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
					</div>
					
					<label for="time_of_warranty" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('OCO_SEL_WARRANTY'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2" id="time_of_warranty" name="time_of_warranty" >
                            <option value=""><?= TRANS('FIELD_TIME_MONTH'); ?></option>
                            <?php
                            $sql = "SELECT * FROM tempo_garantia ORDER BY tempo_meses";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                                ?>
								<option value="<?= $rowType['tempo_cod']; ?>"
                                <?= ($row['tempo_cod'] == $rowType['tempo_cod'] ? ' selected' : ''); ?>
                                ><?= $rowType['tempo_meses'] . ' ' . TRANS('MONTHS'); ?></option>
                                <?php
                            }
                            ?>
                        </select>
					</div>

                    <div class="w-100"></div>
					<label for="additional_info" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('ENTRY_TYPE_ADDITIONAL_INFO'); ?></label>
					<div class="form-group col-md-10">
						<textarea class="form-control " id="additional_info" name="additional_info"><?= $row['comentario']; ?></textarea>
					</div>



                    <?php
                        /* TRECHO PARA EXIBIÇÃO DA LISTAGEM DE ARQUIVOS ANEXOS */
                        $cont = 0;
                        if ($hasFiles) {

                            ?>
                            <div class="form-group col-md-12 my-2">

                                <div class="col-sm-12 border-bottom rounded p-0 bg-white " id="files">
                                    <!-- collapse -->
                                    <table class="table  table-hover table-striped rounded">
                                        <!-- table-responsive -->
                                        <!-- <thead class="bg-secondary text-white"> -->
                                        <thead class=" text-white" style="background-color: #48606b;">
                                            <tr>
                                                <th scope="col">#</th>
                                                <th scope="col"><?= TRANS('COL_TYPE'); ?></th>
                                                <!-- <th scope="col"><?= TRANS('SIZE'); ?></th> -->
                                                <th scope="col"><?= TRANS('FILE'); ?></th>
                                                <th scope="col"><?= TRANS('REMOVE'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $i = 1;
                                            // $cont = 0;
                                            foreach ($resultFiles->fetchAll() as $rowFiles) {
                                                $cont++;
                                                $size = round($rowFiles['img_size'] / 1024, 1);
                                                $rowFiles['img_tipo'] . "](" . $size . "k)";

                                                if (isImage($rowFiles["img_tipo"])) {

                                                    $viewImage = "&nbsp;<a onClick=\"javascript:popupWH('../../includes/functions/showImg.php?" .
                                                        "file=" . $rowFiles['img_cod'] . "&cod=" . $rowFiles['img_cod'] . "'," . $rowFiles['img_largura'] . "," . $rowFiles['img_altura'] . ")\" " .
                                                        "title='" . TRANS('VIEW') . "'><i class='fa fa-search'></i></a>";
                                                } else {
                                                    $viewImage = "";
                                                }
                                            ?>
                                                <tr>
                                                    <th scope="row"><?= $i; ?></th>
                                                    <td><?= $rowFiles['img_tipo']; ?></td>
                                                    <!-- <td><?= $size; ?></td> -->
                                                    <td><a onClick="redirect('../../includes/functions/download.php?file=<?= $rowFiles['img_cod']; ?>&cod=<?= $rowFiles['img_cod']; ?>')" title="Download the file"><?= $rowFiles['img_nome']; ?></a><?= $viewImage; ?></i></td>
                                                    <td><input type="checkbox" name="delImg[<?= $cont; ?>]" value="<?= $rowFiles['img_cod']; ?>">&nbsp;<span class="align-top"><i class="fas fa-trash-alt text-danger"></i></span></td>

                                                </tr>
                                            <?php
                                                $i++;
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <?php
                        }
                        /* FINAL DO TRECHO DE LISTAGEM DE ARQUIVOS ANEXOS*/
                    ?>



					
					

					<div class="row w-100"></div>
					<div class="form-group col-md-8 d-none d-md-block">
					</div>
					<div class="form-group col-12 col-md-2 ">

						<input type="hidden" name="action" id="action" value="edit">
                        <input type="hidden" name="cont" value="<?= $cont; ?>" />
						<input type="hidden" name="cod" id="cod" value="<?= $row['comp_cod']; ?>">
						<input type="hidden" name="old_department" id="old_department" value="<?= $row['tipo_local']; ?>">
						<input type="hidden" name="old_unit" id="old_unit" value="<?= $asset_unit; ?>">
						<input type="hidden" name="old_tag" id="old_tag" value="<?= $asset_tag; ?>">
                        <input type="hidden" name="model_selected" value="<?= $row['modelo_cod']; ?>" id="model_selected"/>
						<button type="submit" id="idSubmit" name="submit" class="btn btn-primary btn-block"><?= TRANS('BT_OK'); ?></button>
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
    <script src="../../includes/components/jquery/plentz-jquery-maskmoney/dist/jquery.maskMoney.min.js"></script>
    <script src="../../includes/components/jquery/jquery.initialize.min.js"></script>
    <script src="../../includes/components/jquery/datetimepicker/build/jquery.datetimepicker.full.min.js"></script>
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


			/* Carregamento dos modelos com base na seleção de tipo */
			showModelsByType($('#model_selected').val() ?? '');
			$('#type').on('change', function() {
				showModelsByType();
			});
            /* Final do carregamento dos modelos */
            

            /* Permitir a replicação do campo de input file */
            var maxField = <?= $config['conf_qtd_max_anexos']; ?>;
            var addButton = $('.add_button'); //Add button selector
            var wrapper = $('.field_wrapper'); //Input field wrapper

            var fieldHTML = '<div class="input-group my-1 d-block"><div class="input-group-prepend"><div class="input-group-text"><a href="javascript:void(0);" class="remove_button"><i class="fa fa-minus"></i></a></div><div class="custom-file"><input type="file" class="custom-file-input" name="anexo[]"  aria-describedby="inputGroupFileAddon01" lang="br"><label class="custom-file-label text-truncate" for="inputGroupFile01"><?= TRANS('CHOOSE_FILE', '', 1); ?></label></div></div></div></div>';

            var x = 1; //Initial field counter is 1

            //Once add button is clicked
            $(addButton).click(function() {
                //Check maximum number of input fields
                if (x < maxField) {
                    x++; //Increment field counter
                    $(wrapper).append(fieldHTML); //Add field html
                }
            });

            //Once remove button is clicked
            $(wrapper).on('click', '.remove_button', function(e) {
                e.preventDefault();
                $(this).parent('div').parent('div').parent('div').remove(); //Remove field html
                x--; //Decrement field counter
            });

            if ($('#idInputFile').length > 0) {
                /* Adicionei o mutation observer em função dos elementos que são adicionados após o carregamento do DOM */
                var obs = $.initialize(".custom-file-input", function() {
                    $('.custom-file-input').on('change', function() {
                        let fileName = $(this).val().split('\\').pop();
                        $(this).next('.custom-file-label').addClass("selected").html(fileName);
                    });

                }, {
                    target: document.getElementById('field_wrapper')
                }); /* o target limita o scopo do observer */

            }

            /* Idioma global para os calendários */
			$.datetimepicker.setLocale('pt-BR');
            /* Para campos personalizados - bind pela classe*/
            $('#purchase_date').datetimepicker({
                timepicker: false,
                format: 'd/m/Y',
                lazyInit: true
            });

			/* Trazer os parâmetros do banco a partir da opção que será criada para internacionaliação */
			$('#price').maskMoney({
                prefix:'R$ ',
                thousands:'.', 
                decimal:',', 
                allowZero: false, 
                affixesStay: false
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

                var form = $('form').get(0);
				$("#idSubmit").prop("disabled", true);
				$.ajax({
					url: './equipment_process.php',
					method: 'POST',
                    // data: $('#form').serialize(),
                    data: new FormData(form),
                    dataType: 'json',
                    
                    cache: false,
				    processData: false,
				    contentType: false,
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
						var url = 'equipment_show.php?tag=' + $('#asset_tag').val() + '&unit=' + $('#asset_unit').val();
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


		function showModelsByType (selected_id = '') {
			/* Popular os modelos de acordo com o tipo selecionado */
			if ($('#model_full').length > 0) {
				
				var loading = $(".loading");
				$(document).ajaxStart(function() {
					loading.show();
				});
				$(document).ajaxStop(function() {
					loading.hide();
				});
				
				$.ajax({
					url: './get_models_by_type_of_equipment.php',
					method: 'POST',
					dataType: 'json',
					data: {
						type: $('#type').val(),
						model_selected: $('#model_selected').val() ?? '',
					},
				}).done(function(response) {
					$('#model_full').empty().append('<option value=""><?= TRANS('SEL_MODEL'); ?></option>');
					for (var i in response) {
						var option = '<option value="' + response[i].marc_cod + '">' + response[i].marc_nome + '</option>';
						$('#model_full').append(option);

						if (selected_id !== '') {
							// $('#model_full').val(selected_id).change();
                            $('#model_full').selectpicker('refresh').selectpicker('val', selected_id);

						}
					}
                    $('#model_full').selectpicker('refresh');

				});
			}
		}


	</script>
</body>

</html>