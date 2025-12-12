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

$config = getConfig($conn);
$exception = "";

$type = "";


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
		<h4 class="my-4"><i class="fas fa-clone text-secondary"></i>&nbsp;<?= TRANS('CONFIGURATION_EQUIPMENTS_MODELS'); ?></h4>
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

		$query = $QRY['configuration_models'];

        $COD = "";
		if (isset($_GET['cod']) && !empty($_GET['cod'])) {
            $COD = (int) $_GET['cod'];
            // $query .= " AND model.marc_cod = '{$COD}' ";
            $query .= " AND mold_cod = '{$COD}' ";
		}
		$query .= " ORDER BY equipamento, fab_nome, modelo";

        
        try {
            $resultado = $conn->query($query);
            $registros = $resultado->rowCount();
        }
        catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage();
            echo message('danger', 'Ooops!', $query . $exception, '', '', true);
            return;
        }


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
			
			<?php
			if ($registros == 0) {
				echo message('info', '', TRANS('NO_RECORDS_FOUND'), '', '', true);
			} else {

			?>
				<table id="table_lists" class="stripe hover order-column row-border" border="0" cellspacing="0" width="100%">

					<thead>
						<tr class="header">
							<td class="line col_sequence">#</td>
							<td class="line col_type"><?= TRANS('COL_TYPE'); ?></td>
							<td class="line col_type"><?= TRANS('COL_MANUFACTURER'); ?></td>
							<td class="line col_model"><?= TRANS('COL_MODEL'); ?></td>
							<td class="line editar" width="10%"><?= TRANS('BT_EDIT'); ?></td>
							<td class="line remover" width="10%"><?= TRANS('BT_REMOVE'); ?></td>
						</tr>
					</thead>
					<tbody>
						<?php
						$i = 1;
						foreach ($resultado->fetchAll() as $row) {

							?>
							<tr>
								<td class="line"><a onclick="redirect('<?= $_SERVER['PHP_SELF'] ?>?action=view&cod=<?= $row['cod']; ?>')"><?= $i; ?></a></td>
								<td class="line"><?= $row['equipamento']; ?></td>
								<td class="line"><?= $row['fab_nome']; ?></td>
								<td class="line"><?= $row['modelo']; ?></td>

								<td class="line"><button type="button" class="btn btn-secondary btn-sm" onclick="redirect('<?= $_SERVER['PHP_SELF']; ?>?action=edit&cod=<?= $row['cod']; ?>')"><?= TRANS('BT_EDIT'); ?></button></td>
								<td class="line"><button type="button" class="btn btn-danger btn-sm" onclick="confirmDeleteModal('<?= $row['cod']; ?>')"><?= TRANS('REMOVE'); ?></button></td>
							</tr>

							<?php
							$i++;
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
			<form name="form" method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form" >
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
                                <option value="<?= $rowType['tipo_cod']; ?>"><?= $rowType['tipo_nome']; ?></option>
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
                                <option value="<?= $rowType['fab_cod']; ?>"><?= $rowType['fab_nome']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>


					<label for="model_full" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_MODEL'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2" id="model_full" name="model_full" required>
                            <option value=""><?= TRANS('SEL_TYPE_ITEM'); ?></option>
                            
                        </select>
					</div>
                
                    <h6 class="w-100 mt-5 ml-5 border-top p-4"><i class="fas fa-hdd text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('SUBTTL_INFO_CONFIG_EQUIP')); ?></h6>


                    <label for="motherboard" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('MOTHERBOARD'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2" id="motherboard" name="motherboard">
                            <option value=""><?= TRANS('SEL_NONE'); ?></option>
                            <?php
                            $sql = "SELECT * FROM modelos_itens WHERE mdit_tipo = 10 ORDER BY mdit_fabricante, mdit_desc";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                            ?>
                                <option value="<?= $rowType['mdit_cod']; ?>"><?= $rowType['mdit_fabricante'] . " " . $rowType['mdit_desc'] . " " . $rowType['mdit_desc_capacidade'] . "" . $rowType['mdit_sufixo']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>

                    <label for="processor" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('PROCESSOR'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2" id="processor" name="processor">
                            <option value=""><?= TRANS('SEL_NONE'); ?></option>
                            <?php
                            $sql = "SELECT * FROM modelos_itens WHERE mdit_tipo = 11 ORDER BY mdit_fabricante, mdit_desc";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                            ?>
                                <option value="<?= $rowType['mdit_cod']; ?>"><?= $rowType['mdit_fabricante'] . " " . $rowType['mdit_desc'] . " " . $rowType['mdit_desc_capacidade'] . "" . $rowType['mdit_sufixo']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>

                    <label for="memory" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CARD_MEMORY'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2" id="memory" name="memory">
                            <option value=""><?= TRANS('SEL_NONE'); ?></option>
                            <?php
                            $sql = "SELECT * FROM modelos_itens WHERE mdit_tipo = 7 ORDER BY mdit_fabricante, mdit_desc";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                            ?>
                                <option value="<?= $rowType['mdit_cod']; ?>"><?= $rowType['mdit_fabricante'] . " " . $rowType['mdit_desc'] . " " . $rowType['mdit_desc_capacidade'] . "" . $rowType['mdit_sufixo']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>

                    <label for="video" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CARD_VIDEO'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2" id="video" name="video">
                            <option value=""><?= TRANS('SEL_NONE'); ?></option>
                            <?php
                            $sql = "SELECT * FROM modelos_itens WHERE mdit_tipo = 2 ORDER BY mdit_fabricante, mdit_desc";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                            ?>
                                <option value="<?= $rowType['mdit_cod']; ?>"><?= $rowType['mdit_fabricante'] . " " . $rowType['mdit_desc'] . " " . $rowType['mdit_desc_capacidade'] . "" . $rowType['mdit_sufixo']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>

                    <label for="sound" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CARD_SOUND'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2" id="sound" name="sound">
                            <option value=""><?= TRANS('SEL_NONE'); ?></option>
                            <?php
                            $sql = "SELECT * FROM modelos_itens WHERE mdit_tipo = 4 ORDER BY mdit_fabricante, mdit_desc";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                            ?>
                                <option value="<?= $rowType['mdit_cod']; ?>"><?= $rowType['mdit_fabricante'] . " " . $rowType['mdit_desc'] . " " . $rowType['mdit_desc_capacidade'] . "" . $rowType['mdit_sufixo']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>


                    <label for="network" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CARD_NETWORK'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2" id="network" name="network">
                            <option value=""><?= TRANS('SEL_NONE'); ?></option>
                            <?php
                            $sql = "SELECT * FROM modelos_itens WHERE mdit_tipo = 3 ORDER BY mdit_fabricante, mdit_desc";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                            ?>
                                <option value="<?= $rowType['mdit_cod']; ?>"><?= $rowType['mdit_fabricante'] . " " . $rowType['mdit_desc'] . " " . $rowType['mdit_desc_capacidade'] . "" . $rowType['mdit_sufixo']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>

                    <label for="modem" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CARD_MODEN'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2" id="modem" name="modem">
                            <option value=""><?= TRANS('SEL_NONE'); ?></option>
                            <?php
                            $sql = "SELECT * FROM modelos_itens WHERE mdit_tipo = 6 ORDER BY mdit_fabricante, mdit_desc";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                            ?>
                                <option value="<?= $rowType['mdit_cod']; ?>"><?= $rowType['mdit_fabricante'] . " " . $rowType['mdit_desc'] . " " . $rowType['mdit_desc_capacidade'] . "" . $rowType['mdit_sufixo']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>

                    <label for="hdd" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('MNL_HD'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2" id="hdd" name="hdd">
                            <option value=""><?= TRANS('SEL_NONE'); ?></option>
                            <?php
                            $sql = "SELECT * FROM modelos_itens WHERE mdit_tipo = 1 ORDER BY mdit_fabricante, mdit_desc";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                            ?>
                                <option value="<?= $rowType['mdit_cod']; ?>"><?= $rowType['mdit_fabricante'] . " " . $rowType['mdit_desc'] . " " . $rowType['mdit_desc_capacidade'] . "" . $rowType['mdit_sufixo']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>

                    <label for="recorder" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_RECORD_CD'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2" id="recorder" name="recorder">
                            <option value=""><?= TRANS('SEL_NONE'); ?></option>
                            <?php
                            $sql = "SELECT * FROM modelos_itens WHERE mdit_tipo = 9 ORDER BY mdit_fabricante, mdit_desc";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                            ?>
                                <option value="<?= $rowType['mdit_cod']; ?>"><?= $rowType['mdit_fabricante'] . " " . $rowType['mdit_desc'] . " " . $rowType['mdit_desc_capacidade'] . "" . $rowType['mdit_sufixo']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>

                    <label for="cdrom" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_CDROM'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2" id="cdrom" name="cdrom">
                            <option value=""><?= TRANS('SEL_NONE'); ?></option>
                            <?php
                            $sql = "SELECT * FROM modelos_itens WHERE mdit_tipo = 5 ORDER BY mdit_fabricante, mdit_desc";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                            ?>
                                <option value="<?= $rowType['mdit_cod']; ?>"><?= $rowType['mdit_fabricante'] . " " . $rowType['mdit_desc'] . " " . $rowType['mdit_desc_capacidade'] . "" . $rowType['mdit_sufixo']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>

                    <label for="dvdrom" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('DVD'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2" id="dvdrom" name="dvdrom">
                            <option value=""><?= TRANS('SEL_NONE'); ?></option>
                            <?php
                            $sql = "SELECT * FROM modelos_itens WHERE mdit_tipo = 8 ORDER BY mdit_fabricante, mdit_desc";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                            ?>
                                <option value="<?= $rowType['mdit_cod']; ?>"><?= $rowType['mdit_fabricante'] . " " . $rowType['mdit_desc'] . " " . $rowType['mdit_desc_capacidade'] . "" . $rowType['mdit_sufixo']; ?></option>
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

            $resultado = $conn->query($query);
			$row = $resultado->fetch();
		    ?>
			<h6><?= TRANS('BT_EDIT'); ?></h6>
			<form name="form" method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form" >
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
                                    <?= ($row['equipamento_cod'] == $rowType['tipo_cod'] ? ' selected' : ''); ?>
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
                            <option value=""><?= TRANS('SEL_TYPE_ITEM'); ?></option>
                            
                        </select>
                    </div>

                    <h6 class="w-100 mt-5 ml-5 border-top p-4"><i class="fas fa-hdd text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('SUBTTL_INFO_CONFIG_EQUIP')); ?></h6>


                    <label for="motherboard" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('MOTHERBOARD'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control sel2" id="motherboard" name="motherboard">
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
                        <select class="form-control sel2" id="processor" name="processor">
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
                        <select class="form-control sel2" id="memory" name="memory">
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
                        <select class="form-control sel2" id="video" name="video">
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
                        <select class="form-control sel2" id="sound" name="sound">
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
                        <select class="form-control sel2" id="network" name="network">
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
                        <select class="form-control sel2" id="modem" name="modem">
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
                        <select class="form-control sel2" id="hdd" name="hdd">
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
                        <select class="form-control sel2" id="recorder" name="recorder">
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
                        <select class="form-control sel2" id="cdrom" name="cdrom">
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
                        <select class="form-control sel2" id="dvdrom" name="dvdrom">
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
					
                
					<div class="row w-100"></div>
					<div class="form-group col-md-8 d-none d-md-block">
					</div>
					<div class="form-group col-12 col-md-2 ">

						<input type="hidden" name="model_selected" value="<?= $row['padrao']; ?>" id="model_selected"/>
                        <input type="hidden" name="cod" value="<?= $_GET['cod']; ?>">
                        <input type="hidden" name="action" id="action" value="edit">
						<button type="submit" id="idSubmit" name="submit" value="edit" class="btn btn-primary btn-block"><?= TRANS('BT_OK'); ?></button>
					</div>
					<div class="form-group col-12 col-md-2">
						<button type="reset" class="btn btn-secondary btn-block" onClick="parent.history.back();"><?= TRANS('BT_CANCEL'); ?></button>
					</div>

				</div>
			</form>
		<?php
		} else

		if ((isset($_GET['action']) && $_GET['action'] == "view") && empty($_POST['submit'])) {

			$row = $resultado->fetch();
			?>
			<button type="button" class="btn btn-secondary btn-sm" onclick="redirect('<?= $_SERVER['PHP_SELF']; ?>?action=edit&cod=<?= $row['cod']; ?>')"><?= TRANS('BT_EDIT'); ?></button>
			
			<form name="form_view" method="post" action="<?= $_SERVER['PHP_SELF']; ?>" id="form_view" >
				<?= csrf_input(); ?>
				<div class="form-group row my-4">
                    
					
                    <h6 class="w-100 mt-5 ml-5 border-top p-4"><i class="fas fa-info-circle text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('BASIC_INFORMATIONS')); ?></h6>

                    <label for="type_view" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_TYPE'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control " id="type_view" name="type_view" disabled>
                            <option value=""><?= TRANS('SEL_TYPE_EQUIP'); ?></option>
                            <?php
                            $sql = "SELECT * FROM tipo_equip ORDER BY tipo_nome";
                            $exec_sql = $conn->query($sql);
                            foreach ($exec_sql->fetchAll() as $rowType) {
                            ?>
                                <option value="<?= $rowType['tipo_cod']; ?>"
                                    <?= ($row['equipamento_cod'] == $rowType['tipo_cod'] ? ' selected' : ''); ?>
                                ><?= $rowType['tipo_nome']; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>

                    <label for="manufacturer_view" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_MANUFACTURER'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control " id="manufacturer_view" name="manufacturer_view" disabled>
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


                    <label for="model_full_view" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_MODEL'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control " id="model_full_view" name="model_full_view" disabled>
                            <option value=""><?= $row['modelo']; ?></option>
                            
                        </select>
                    </div>

                    <h6 class="w-100 mt-5 ml-5 border-top p-4"><i class="fas fa-hdd text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('SUBTTL_INFO_CONFIG_EQUIP')); ?></h6>


                    <label for="motherboard_view" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('MOTHERBOARD'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control " id="motherboard_view" name="motherboard_view" disabled>
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

                    <label for="processor_view" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('PROCESSOR'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control " id="processor_view" name="processor_view" disabled>
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

                    <label for="memory_view" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CARD_MEMORY'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control " id="memory_view" name="memory_view" disabled>
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

                    <label for="video_view" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CARD_VIDEO'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control " id="video_view" name="video_view" disabled>
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

                    <label for="sound_view" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CARD_SOUND'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control " id="sound_view" name="sound_view" disabled>
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


                    <label for="network_view" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CARD_NETWORK'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control " id="network_view" name="network_view" disabled>
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

                    <label for="modem_view" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CARD_MODEN'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control " id="modem_view" name="modem_view" disabled>
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

                    <label for="hdd_view" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('MNL_HD'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control " id="hdd_view" name="hdd_view" disabled>
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

                    <label for="recorder_view" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_RECORD_CD'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control " id="recorder_view" name="recorder_view" disabled>
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

                    <label for="cdrom_view" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('FIELD_CDROM'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control " id="cdrom_view" name="cdrom_view" disabled>
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

                    <label for="dvdrom_view" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('DVD'); ?></label>
                    <div class="form-group col-md-4">
                        <select class="form-control " id="dvdrom_view" name="dvdrom_view" disabled>
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



					

					<div class="row w-100"></div>
					<div class="form-group col-md-8 d-none d-md-block">
					</div>
					<div class="form-group col-12 col-md-2 ">
						
					</div>
					<div class="form-group col-12 col-md-2">
						<button type="reset" class="btn btn-secondary btn-block" onClick="parent.history.back();"><?= TRANS('BT_RETURN'); ?></button>
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


            if ($('#table_lists').length > 0) {
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
            }
			
			/* Carregamento dos modelos com base na seleção de tipo */
			showModelsByType($('#model_selected').val() ?? '');
			$('#type').on('change', function() {
				showModelsByType();
			});
			/* Final do carregamento dos modelos */



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

                // var form = $('form').get(0);
				$("#idSubmit").prop("disabled", true);
				$.ajax({
					url: './configuration_models_process.php',
					method: 'POST',
                    data: $('#form').serialize(),
                    // data: new FormData(form),
                    dataType: 'json',
                    
                    // cache: false,
				    // processData: false,
				    // contentType: false,
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


		function showModelsByType(selected_id = '') {
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
                        } else
                        if ($('#model_selected').val() != '') {
                            // $('#model_full').val($('#model_selected').val()).change();
                            $('#model_full').selectpicker('refresh').selectpicker('val', $('#model_selected').val());
                        }
                    }
                    $('#model_full').selectpicker('refresh');
                });
            }
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
				url: './configuration_models_process.php',
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
	</script>
</body>

</html>