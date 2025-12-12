<?php session_start();
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
  */

exit; 

if (!isset($_SESSION['s_logado']) || $_SESSION['s_logado'] == 0) {
	$_SESSION['session_expired'] = 1;
	echo "<script>top.window.location = '../../index.php'</script>";
	exit();
}

require_once __DIR__ . "/" . "../../includes/include_geral_new.inc.php";
require_once __DIR__ . "/" . "../../includes/classes/ConnectPDO.php";

use includes\classes\ConnectPDO;

$conn = ConnectPDO::getInstance();

$imgsPath = "../../includes/imgs/";

$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 3, 1);

$_SESSION['s_page_ocomon'] = $_SERVER['PHP_SELF'];

$nextDay = new DateTime('+1 day');
// $nextDay = $nextDay->format('d/m/Y');

$sysConfig = getConfig($conn);
$mailConfig = getMailConfig($conn);

$screen = getScreenInfo($conn, $_SESSION['s_screen']);

/* Para manter a compatibilidade com versões antigas */
$table = getTableCompat($conn);

if (!isset($_POST['submit']) || empty($_POST)) {

?>
	<!DOCTYPE html>
	<html lang="pt-BR">

	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title><?= TRANS('TICKET_OPENING'); ?></title>

		<link rel="stylesheet" type="text/css" href="../../includes/css/estilos.css" />
		<link rel="stylesheet" type="text/css" href="../../includes/components/datatables/datatables.min.css" />
		<link rel="stylesheet" type="text/css" href="../../includes/css/my_datatables.css" />
		<link rel="stylesheet" href="../../includes/components/jquery/jquery-ui-1.12.1/jquery-ui.css" />
		<link rel="stylesheet" href="../../includes/components/jquery/timePicker/jquery.timepicker.min.css" />
		<link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap/custom.css" />
		<link rel="stylesheet" type="text/css" href="../../includes/components/fontawesome/css/all.min.css" />

		<style>
			/* Style the CKEditor element to look like a textfield */
			.cke_textarea_inline {
				width: 100%;
				padding: 10px;
				height: 100px;
				overflow: auto;
				border: 1px solid #ced4da;
				border-radius: 4px;
				-webkit-appearance: textfield;
			}
		</style>
	</head>

	<?php
	print "<body onLoad=\"";

	if ((!empty($screen) && $screen['conf_scr_prob']) || empty($screen)) {
		print "ajaxFunction('Problema', 'showSelProbs.php', 'idLoad', 'prob=idProblema', 'area_cod=idArea','radio_prob=idRadioProb', 'area_habilitada=idAreaHabilitada', 'area_destino=idAreaDestino');";
		print "ajaxFunction('divProblema', 'showProbs.php', 'idLoad', 'prob=idProblema', 'area_cod=idArea', 'radio_prob=idRadioProb'); ";
		print "ajaxFunction('divInformacaoProblema', 'showInformacaoProb.php', 'idLoad', 'prob=idProblema', 'area_cod=idArea'); ";
	}

	if ((!empty($screen) && $screen['conf_scr_local']) || empty($screen)) {
		if (((!empty($screen) && $screen['conf_scr_unit']) || empty($screen))  && ((!empty($screen) && $screen['conf_scr_tag']) || empty($screen))) {
			print "ajaxFunction('idDivSelLocal', 'showSelLocais.php', 'idLoad', 'unidade=idUnidade', 'etiqueta=idEtiqueta'); ";
		} else
			print "ajaxFunction('idDivSelLocal', 'showSelLocais.php', 'idLoad'); ";
	}
	if ((!empty($screen) && $screen['conf_scr_foward']) || empty($screen)) {
		print "ajaxFunction('divOperator', 'showOperators.php', 'idLoad');";
	}
	print "\">";

	if ((!empty($screen) && !$screen['conf_user_opencall'])) {
		$msgDisable = TRANS('MSG_OPEN_TICKET_DISABLED');
		// echo mensagem($msgDisable);
		echo message('info', 'Ooops!', $msgDisable, '', '', true);
		exit;
	}

	if (isset($_REQUEST['pai']) && isFatherOk($conn, $_REQUEST['pai'])) {
		$subCallMsg = message('info', '', TRANS('MSG_OCCO_SUBTICKET') . "&nbsp;" . $_REQUEST['pai'], '', '', 1);
	} else $subCallMsg = "";

	?>

	

	<div class="container">
		<div id="idLoad" class="loading" style="display:none"></div>
		<div id="loading" class="loading" style="display:none"></div>
	</div>
	<div id="divResult"></div>
	<div class="container-fluid">


		<div class="modal" tabindex="-1" id="modalDefault">
			<div class="modal-dialog modal-xl">
				<div class="modal-content">
					<div id="divModalDetails" class="p-3"></div>
				</div>
			</div>
		</div>
		<?php
		if (isset($_SESSION['flash']) && !empty($_SESSION['flash'])) {
			echo $_SESSION['flash'];
			$_SESSION['flash'] = '';
		}
		?>

		<h5 class="my-4"><i class="fas fa-plus-square text-secondary"></i>&nbsp;<?= TRANS('TICKET_OPENING') . ":" . $subCallMsg; ?></h5>
		<form name="form" id="form" method="post" action="<?= $_SERVER['PHP_SELF']; ?>" enctype="multipart/form-data">
			<?= csrf_input(); ?>
			<input type="hidden" name="MAX_FILE_SIZE" value="<?= $sysConfig['conf_upld_size']; ?>" />

			<?php

			$invTag = "";
			$invInst = "";
			$invLoc = "";
			$contato = "";
			$contato_email = "";
			$telefone = "";
			if (isset($_POST['problema'])) {
				$radio_prob = $_POST['problema'];
				$problema = $_POST['problema'];
			} else {
				$radio_prob = -1;
				$problema = -1;
			}
			if (isset($_POST['foward'])) {
				$foward = $_POST['foward'];
			} else {
				$foward = -1;
			}


			?>
			<div class="form-group row my-4">
				<?php
				
				
				/* Área de atendimento */
				if ((!empty($screen) && $screen['conf_scr_area']) || empty($screen)) {
				?>
					<label for="idArea" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('RESPONSIBLE_AREA'); ?></label>
					<div class="form-group col-md-4">
						<select class="form-control " id="idArea" name="sistema" required onChange="
							<?php
							if ((!empty($screen) && $screen['conf_scr_prob']) || empty($screen)) {
								print "ajaxFunction('Problema', 'showSelProbs.php', 'idLoad', 'prob=idProblema', 'area_cod=idArea', 'area_habilitada=idAreaHabilitada');";
								print "ajaxFunction('divProblema', 'showProbs.php', 'idLoad', 'prob=idProblema', 'area_cod=idArea');";
							}
							print "ajaxFunction('divInformacaoProblema', 'showInformacaoProb.php', 'idLoad', 'prob=idProblema', 'area_cod=idArea'); ";
							if ((!empty($screen) && $screen['conf_scr_foward']) || empty($screen)) {
								print "ajaxFunction('divOperator', 'showOperators.php', 'idLoad', 'area_cod=idArea');";
							}
							?>
						">
							<?php
							$query = "SELECT s.sis_id, s.sistema from sistemas s, {$table} a WHERE s.sis_status NOT IN (0) AND s.sis_atende = 1 AND s.sis_id = a.area AND a.area_abrechamado IN (" . $_SESSION['s_uareas'] . ") GROUP BY sis_id, sistema ORDER BY sistema";
							$resultado = $conn->query($query);

							if (isset($_POST['sistema'])) {
								$sistema = $_POST['sistema'];
							} else
								$sistema = "-1";
							?>

							<option value="-1" selected><?= TRANS('SEL_AREA'); ?></option>
							<?php
							foreach ($resultado->fetchAll() as $rowArea) {
								print "<option value='" . $rowArea['sis_id'] . "'";
								if ($rowArea['sis_id'] == $sistema) print " selected";
								print ">" . $rowArea['sistema'] . "</option>";
							}
							?>

						</select>
						<input type="hidden" name="areaHabilitada" id="idAreaHabilitada" value="sim">
						<input type="hidden" name="areaDestino" id="idAreaDestino" value="<?= $screen['conf_opentoarea'] ?? null; ?>">

					</div>
				<?php

				} else {
					$sistema = $screen['conf_opentoarea'];
					print "<input type='hidden' name='sistema' id='idArea' value='" . $sistema . "'>";
					print "<input type='hidden' name='areaHabilitada' id='idAreaHabilitada' value='nao'>";
					print "<input type='hidden' name='areaDestino' id='idAreaDestino' value='" . $screen['conf_opentoarea'] . "'>";
				}

				if ((!empty($screen) && $screen['conf_scr_prob']) || empty($screen)) {

				?>
					<label for="idProblema" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('ISSUE_TYPE'); ?></label>
					<div class="form-group col-md-4">
						<div id="Problema">
							<select class="form-control " id="idProblema" name="problema" required>
								<option value="-1"><?= TRANS('ISSUE_TYPE'); ?></option>
							</select>
							<input type="hidden" name="problema" id="idProblema" value="<?= $problema; ?>">
						</div>
					</div>
				<?php

				} else {
					$problema = -1;
				}
				?>

				<!-- <div class="form-group col-md-12 "> -->
				<div class=" col-md-12 ">
					<div id="divProblema">
						<input type="hidden" name="radio_prob" id="idRadioProb" value="<?= $radio_prob; ?>" />
					</div>
				</div>
				<!-- <div class="form-group col-md-12"> -->
				<div class=" col-md-12">
					<div id="divInformacaoProblema"></div>
				</div>



				<?php
				if ((!empty($screen) && $screen['conf_scr_desc']) || empty($screen)) {

					if (isset($_POST['descricao'])) {
						$descricao = $_POST['descricao'];
					} else
						$descricao = "";
				?>
					<label for="idDescricao" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('DESCRIPTION'); ?></label>

					<div class="form-group col-md-10">
						<textarea class="form-control " id="idDescricao" name="descricao" rows="4" required><?= noHtml($descricao); ?></textarea>
						<div class="invalid-feedback">
							<?= TRANS('MANDATORY_FIELD'); ?>
						</div>
						<small class="form-text text-muted">
							<?= TRANS('DESCRIPTION_HELPER'); ?>.
						</small>

					</div>
					
				<?php

				} else {
					$descricao = TRANS('OCO_NO_DESC');
					print "<input type='hidden' name='descricao' value='" . $descricao . "'>";
				}


				if (isset($_GET['invInst'])) {
					$invInst = $_GET['invInst'];
				} else
				if (isset($_POST['instituicao'])) {
					$invInst = $_POST['instituicao'];
				}
				if ((!empty($screen) && $screen['conf_scr_unit']) || empty($screen)) {

					$query2 = "SELECT * from instituicao WHERE inst_status not in (0) order by inst_cod";
					$resultado2 = $conn->query($query2);
					$linhas = $resultado2->rowCount();
				?>
					<label for="idUnidade" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_UNIT'); ?></label>
					<div class="form-group col-md-4">
						<select class="form-control " id="idUnidade" name="instituicao">
							<option value="-1" selected><?= TRANS('SEL_UNIT'); ?></option>
							<?php
							foreach ($resultado2->fetchAll() as $rowInst) {

								print "<option value=" . $rowInst['inst_cod'] . "";
								if ($rowInst['inst_cod'] == $invInst) print " selected";
								print ">" . $rowInst['inst_nome'] . "</option>";
							}
							?>
						</select>
					</div>
				<?php

				} else {
					$instituicao = -1;
					print "<input type='hidden' name='instituicao' value='-1'>";
				}


				if (isset($_GET['invTag'])) {
					$invTag = $_GET['invTag'];
				} else
			if (isset($_POST['equipamento'])) {
					$invTag = $_POST['equipamento'];
				}
				if ((!empty($screen) && $screen['conf_scr_tag']) || empty($screen)) {

				?>
					<label for="idEtiqueta" class="col-md-2 col-form-label col-form-label-sm text-md-right text-nowrap"><?= TRANS('ASSET_TAG'); ?></label>


					<div class="form-group col-md-4">
						<div class="input-group">

							<?php
							if ((!empty($screen) && $screen['conf_scr_chktag']) || empty($screen)) {
							?>
								<div class="input-group-prepend">
									<div class="input-group-text">
										<a href="javascript:void(0);" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('CONS_CONFIG_EQUIP'); ?>" onClick="checa_etiqueta()"><i class="fa fa-sliders-h"></i></a>
									</div>
								</div>
							<?php
							}
							?>
							<input type="text" class="form-control " id="idEtiqueta" name="equipamento" value="<?= $invTag; ?>" placeholder="<?= TRANS('FIELD_TAG_EQUIP'); ?>" />
							<?php
							if ((!empty($screen) && $screen['conf_scr_chkhist']) || empty($screen)) {
							?>
								<div class="input-group-append">
									<div class="input-group-text">
										<a href="javascript:void(0);" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('CONS_CALL_EQUIP'); ?>" onClick="checa_chamados()"><i class="fa fa-history"></i></a>
									</div>
								</div>
							<?php
							}
							?>

						</div>
					</div>
				<?php
				} else {
					$equipamento = null;
					print "<input type='hidden' name='equipamento' value=" . NULL . ">";
				}


				if (isset($_GET['contato'])) {
					$contato = $_GET['contato'];
				} else
				if (isset($_POST['contato'])) {
					$contato = $_POST['contato'];
				}

				if ($_SESSION['s_nivel'] == 3) {
					$contato = $_SESSION['s_usuario_nome'];
				}

				if ((!empty($screen) && $screen['conf_scr_contact']) || empty($screen)) {
				?>
					<label for="contato" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CONTACT'); ?></label>
					<div class="form-group col-md-4">
						<input type="text" class="form-control " id="contato" name="contato" list="contatos" value="<?= $contato; ?>" autocomplete="off" placeholder="<?= TRANS('CONTACT_PLACEHOLDER'); ?>" />
					</div>
					<datalist id="contatos"></datalist>
				<?php
				} else {
					$contato = $_SESSION['s_usuario_nome'];
					print "<input type='hidden' name='contato' value='" . $contato . "'>";
				}


				$contact_email_disable = "";
				if ($_SESSION['s_nivel'] == "3") {
					$contato_email = getUserInfo($conn, $_SESSION['s_uid'])['email'];
					$contact_email_disable = " readonly ";
				} else
				if (isset($_GET['contato_email'])) {
					$contato_email = $_GET['contato_email'];
				} else
				if (isset($_POST['contato_email'])) {
					$contato_email = $_POST['contato_email'];
				}
				if ((!empty($screen) && $screen['conf_scr_contact_email']) || empty($screen)) {

				?>
					<label for="contato_email" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CONTACT_EMAIL'); ?></label>
					<div class="form-group col-md-4">
						<input type="email" class="form-control " id="contato_email" name="contato_email" list="contatos_emails" value="<?= $contato_email; ?>" <?= $contact_email_disable; ?> autocomplete="off" placeholder="<?= TRANS('CONTACT_EMAIL_PLACEHOLDER'); ?>" />
					</div>
					<datalist id="contatos_emails"></datalist>

				<?php
				} else {
					$qry = "select email from usuarios where user_id = " . $_SESSION['s_uid'] . "";
					$exec = $conn->query($qry);
					$r_user = $exec->fetch();
					$contato_email = $r_user['email'];
					print "<input type='hidden' name='contato_email' value='" . $contato_email . "'>";
				}


				if (isset($_GET['telefone'])) {
					$telefone = $_GET['telefone'];
				} else
				if (isset($_POST['telefone'])) {
					$telefone = $_POST['telefone'];
				}
				if ((!empty($screen) && $screen['conf_scr_fone']) || empty($screen)) {
				?>
					<label for="idTelefone" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_PHONE'); ?></label>
					<div class="form-group col-md-4">
						<input type="tel" class="form-control " id="idTelefone" name="telefone" value="<?= $telefone; ?>" placeholder="<?= TRANS('PHONE_PLACEHOLDER'); ?>" />
					</div>
				<?php
				} else {
					$telefone = null;
					print "<input type='hidden' name='telefone' value=" . NULL . ">";
				}



				if (isset($_GET['invLoc'])) {
					$invLoc = $_GET['invLoc'];
				} else
				if (!isset($_POST['carrega'])) {
					if (isset($_POST['local'])) {
						$invLoc = $_POST['local'];
					}
				}
				if ((!empty($screen) && $screen['conf_scr_local']) || empty($screen)) {
				?>
					<label for="idLocal" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('DEPARTMENT'); ?></label>

					<div class="form-group col-md-4">
						<div id="idDivSelLocal">
							<select class="form-control " name="local" id="idLocal">
								<option value="-1"><?= TRANS('SEL_DEPARTMENT'); ?></option>
							</select>
						</div>
					</div>
				<?php
				} else {
					$local = -1;
					print "<input type='hidden' name='local' value='-1'>";
				}

				if ((!empty($screen) && $screen['conf_scr_operator']) || empty($screen)) {
				?>
					<label for="tecnico" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('TECHNICIAN'); ?></label>
					<div class="form-group col-md-4">
						<input type="text" class="form-control  " readonly id="tecnico" name="tecnico" value="<?= $_SESSION['s_usuario']; ?>" />
					</div>
				<?php

				} else {
					$operador = $_SESSION['s_usuario'];
					print "<input type='hidden' name='operador' value='" . $operador . "'>";
				}



				// if ((!empty($screen) && $screen['conf_scr_replicate']) || empty($screen)) {
				?>
				<!-- <label for="idReplicar" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('OCO_FIELD_REPLICATE'); ?></label>
					<div class="form-group col-md-4">
						<input type="number" min="0" max="5" class="form-control " id="idReplicar" name="replicar" value="0" />
						<small id="passwordHelpBlock" class="form-text text-muted">
							<?= TRANS('REPLICATION_HELPER'); ?>.
						</small>
					</div> -->
				<?php
				// } else $replicar = 0;


				if ((!empty($screen) && $screen['conf_scr_upload']) || empty($screen)) {
				?>
					<label class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('ATTACH_FILE'); ?></label>

					<div class="form-group col-md-4">
						<div class="field_wrapper" id="field_wrapper">
							<div class="input-group">
								<div class="input-group-prepend">
									<div class="input-group-text">
										<a href="javascript:void(0);" class="add_button" title="<?= TRANS('TO_ATTACH_ANOTHER'); ?>"><i class="fa fa-plus"></i></a>
									</div>
								</div>
								<!-- <input type="file" class="form-control  " name="anexo[]" /> -->
								<div class="custom-file">
									<input type="file" class="custom-file-input" name="anexo[]" id="idInputFile" aria-describedby="inputGroupFileAddon01" lang="br">
									<label class="custom-file-label text-truncate" for="inputGroupFile01"><?= TRANS('CHOOSE_FILE'); ?></label>
								</div>
							</div>
						</div>
					</div>

				<?php
				}

				if ((!empty($screen) && $screen['conf_scr_prior']) || empty($screen)) {

					$sql = "select * from prior_atend where pr_default = 1 ";
					$commit1 = $conn->query($sql);
					$rowR = $commit1->fetch();
					?>
					<label for="idPrioridade" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('OCO_PRIORITY'); ?></label>
					<div class="form-group col-md-4">
						<select class="form-control " id="idPrioridade" name="prioridade">
							<!-- <option value="-1"><?= TRANS('OCO_PRIORITY'); ?></option> -->
							<?php
							$sql2 = "select * from prior_atend order by pr_nivel";
							$commit2 = $conn->query($sql2);
							foreach ($commit2->fetchAll() as $rowB) {
								print "<option value=" . $rowB["pr_cod"] . "";
								if ($rowB['pr_cod'] == $rowR['pr_cod']) {
									print " selected";
								}
								print ">" . $rowB["pr_desc"] . "</option>";
							}
							?>
						</select>
					</div>
				<?php
				} else {
					$sql = "select * from prior_atend where pr_default = 1 ";
					$commit1 = $conn->query($sql);
					$rowR = $commit1->fetch();
					print "<input type='hidden' name='prioridade' value='" . $rowR['pr_cod'] . "'>";
				}



				if ((!empty($screen) && $screen['conf_scr_foward']) || empty($screen)) {
				?>
					<label for="idFoward" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('FORWARD_TICKET_TO'); ?></label>
					<div class="form-group col-md-4">
						<div id="divOperator">
							<input type="hidden" name="foward" id="idFoward" value="<?= $foward; ?>">
							<!-- <select class="form-control " id="encaminhar" name="encaminhar">
								<option value=""><?= TRANS('OCO_SEL_OPERATOR'); ?></option>
							</select> -->
						</div>
					</div>

				<?php
				} else {
				?>
					<!-- Importante para nao dar problema no mutate observer -->
					<div id="divOperator"></div>
					<?php
				}




				/* Só exibirá as opções de envio caso o envio de e-mails esteja habilitado */
				if ($mailConfig['mail_send']) {
					if ((!empty($screen) && $screen['conf_scr_mail']) || empty($screen)) {
					?>
						<label class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('OCO_FIELD_SEND_MAIL_TO'); ?></label>
						<div class="form-group col-md-4">
							<div class="form-check form-check-inline">
								<input class="form-check-input " type="checkbox" name="mailAR" value="ok" id="defaultCheck1" checked>
								<legend class="col-form-label col-form-label-sm"><?= TRANS('RESPONSIBLE_AREA'); ?></legend>
							</div>
							<div class="form-check form-check-inline">
								<input class="form-check-input " type="checkbox" name="mailOP" value="ok" id="mailOP" disabled>
								<legend class="col-form-label col-form-label-sm"><?= TRANS('TECHNICIAN'); ?></legend>
							</div>
							<div class="form-check form-check-inline">
								<input class="form-check-input " type="checkbox" name="mailUS" value="ok" disabled id="mailUS">
								<legend class="col-form-label col-form-label-sm"><?= TRANS('CONTACT'); ?></legend>
							</div>
						</div>
					<?php
					}
				}


				if ((!empty($screen) && $screen['conf_scr_date']) || empty($screen)) {
					?>
					<label for="data_abertura" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('OPENING_DATE'); ?></label>
					<div class="form-group col-md-4">
						<input type="text" class="form-control  " readonly id="data_abertura" name="data_abertura" value="<?= date("d/m/Y H:i:s"); ?>" />
					</div>
				<?php
				}
				if ((!empty($screen) && $screen['conf_scr_status']) || empty($screen)) {
				?>
					<label for="status" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_STATUS'); ?></label>
					<div class="form-group col-md-4">
						<input type="text" class="form-control  " readonly id="status" name="status" value="<?= TRANS('STATUS_WAITING'); ?>" />
					</div>
				<?php
				}

				if ((!empty($screen) && $screen['conf_scr_schedule']) || empty($screen)) {
				?>
					<label for="idDate_schedule" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('TO_SCHEDULE'); ?></label>
					<div class="form-group col-md-2">
						<div class="input-group">

							<div class="input-group-prepend">
								<div class="input-group-text">
									<input type="checkbox" name="allowSchedule" id="allowSchedule" value="1">
								</div>
							</div>

							<input type="text" class="form-control " id="idDate_schedule" name="date_schedule" value="" placeholder="<?= TRANS('DATE_TO_SCHEDULE'); ?>" autocomplete="off" disabled /> <!--  -->


						</div>
					</div>

					<div class="form-group col-md-2">
						<input type="text" class="form-control " id="idTime_schedule" name="time_schedule" value="" placeholder="<?= TRANS('PLACEHOLDER_SCHEDULE_TIME'); ?>" autocomplete="off" disabled /> <!--  -->
					</div>
				<?php
				}


				/* Canal de atendimento */
				$defaultChannel = getDefaultChannel($conn);
				if ((!empty($screen) && $screen['conf_scr_channel']) || empty($screen)) {
					$channels = getChannels($conn, null, 'open');
					?>
					<label for="channel" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('OPENING_CHANNEL'); ?></label>
					<div class="form-group col-md-4">
						<select class="form-control " id="channel" name="channel">
							<?php
								foreach ($channels as $channel) {
									print "<option value=" . $channel["id"] . "";
									if ($channel['id'] == $defaultChannel['id']) {
										print " selected";
									}
									print ">" . $channel["name"] . "</option>";
								}
							?>
						</select>
					</div>
				<?php
				} else {
					print "<input type='hidden' name='channel' value='" . $defaultChannel['id'] . "'>";
				}



				if (isset($_REQUEST['pai'])) {
					print "<input type='hidden' name='pai' value='" . $_REQUEST['pai'] . "'>";
				}
				print "<input type='hidden' name='data_gravada' value='" . date("Y-m-d H:i:s") . "'>";

				?>

				<input type="hidden" name="action" value="open" />
				<input type="hidden" name="_sistema" value="<?= $sistema; ?>" />
				<input type="hidden" name="submit" value="submit" />

				<!-- <div class="row w-100"> -->
				<div class="w-100"></div>
				<div class="form-group col-md-8 d-none d-md-block">
				</div>
				<div class="form-group col-12 col-md-2 ">
					<button type="submit" id="idSubmit" class="btn btn-primary btn-block" onClick="LOAD=0;"><?= TRANS('BT_OK'); ?></button>
				</div>
				<div class="form-group col-12 col-md-2">
					<button type="reset" class="btn btn-secondary btn-block" onClick="parent.history.back();"><?= TRANS('BT_CANCEL'); ?></button>
				</div>
				<!-- </div> -->

			</div>

		</form>
	</div>






<?php
}


$qrylogado = "SELECT sis_atende FROM sistemas where sis_id = " . $_SESSION['s_area'] . "";
$execlogado = $conn->query($qrylogado);
$rowlogado = $execlogado->fetch();
?>
<script src="../../includes/javascript/funcoes-3.0.js"></script>
<script src="../../includes/components/jquery/jquery.js"></script>
<script src="../../includes/components/jquery/jquery.initialize.min.js"></script>
<script type="text/javascript" src="../../includes/components/jquery/jquery-ui-1.12.1/jquery-ui.js"></script>
<script type="text/javascript" src="../../includes/components/jquery/timePicker/jquery.timepicker.min.js"></script>
<script src="../../includes/components/bootstrap/js/bootstrap.bundle.js"></script>
<script type="text/javascript" charset="utf8" src="../../includes/components/ckeditor/ckeditor.js"></script>

<script>
	$(function() {

		/* Permitir a replicação do campo de input file */
		var maxField = <?= $sysConfig['conf_qtd_max_anexos']; ?>;
		var addButton = $('.add_button'); //Add button selector
		var wrapper = $('.field_wrapper'); //Input field wrapper

		var fieldHTML = '<div class="input-group d-block my-1"><div class="input-group-prepend"><div class="input-group-text"><a href="javascript:void(0);" class="remove_button"><i class="fa fa-minus"></i></a></div><div class="custom-file"><input type="file" class="custom-file-input" name="anexo[]"  aria-describedby="inputGroupFileAddon01" lang="br"><label class="custom-file-label text-truncate" for="inputGroupFile01"><?= TRANS('CHOOSE_FILE', '', 1); ?></label></div></div></div></div>';

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


		/* Autocompletar os nomes dos contatos */
		if ($('#contatos').length > 0) {
			$.ajax({
				url: './get_contacts_names.php',
				method: 'POST',
				dataType: 'json',
			}).done(function(response) {
				for (var i in response) {
					var option = '<option value="' + response[i].contato + '"/>';
					$('#contatos').append(option);
				}
			});
		}

		/* Autocompletar os emails dos contatos */
		if ($('#contatos_emails').length > 0) {
			$.ajax({
				url: './get_contacts_emails.php',
				method: 'POST',
				dataType: 'json',
			}).done(function(response) {
				for (var i in response) {
					var option = '<option value="' + response[i].contato_email + '"/>';
					$('#contatos_emails').append(option);
				}
			});
		}


		$('.modal').on('hidden.bs.modal', function(e) {
			console.log('modal fechado');
			$('.modal').modal('dispose');
		})




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



		/* Outro mutation observer  */
		if ($('#idFoward').length > 0){
			var oper = $.initialize("#idFoward", function() {
				$('#idFoward').on('change', function() {
					if ($(this).val() != '-1') {

						if ($('#mailOP').length > 0)
							$('#mailOP').prop('disabled', false);
					} else {
						if ($('#mailOP').length > 0)
							$('#mailOP').prop('disabled', true).prop('checked', false);
					}
				});

			}, {
				target: document.getElementById('divOperator')
			}); /* o target limita o scopo do observer */
		}

		var bar = '<?php print $_SESSION['s_formatBarOco']; ?>';

		if ((typeof($('#idDescricao').val()) !== 'undefined') && bar == 1) {
			var formatBar = CKEDITOR.inline('idDescricao', {
				uiColor: '#CCCCCC',
				language: 'pt-br'
			});

			formatBar.on('change', function() {
				formatBar.updateElement();
			});
		}


		if ($('#contato_email').length > 0) {
			$('#contato_email').on('blur', function() {
				if ($('#contato_email').val() != '') {
					$('#mailUS').prop('disabled', false);
				} else {
					$('#mailUS').prop('disabled', true).prop('checked', false);
				}
			});
		}




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

			// for (instance in CKEDITOR.instances) {
			// 	CKEDITOR.instances[instance].updateElement();
			// }

			var form = $('form').get(0);
			// disabled the submit button
			$("#idSubmit").prop("disabled", true);

			$.ajax({
				url: './tickets_process.php',
				method: 'POST',

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
					var url = 'ticket_show.php?numero=' + response.numero;
					$(location).prop('href', url);
					return false;
				}
			});
			return false;
		});



		$(function() {
			$('[data-toggle="popover"]').popover()
		});

		$('.popover-dismiss').popover({
			trigger: 'focus'
		});


		$('#allowSchedule').on('click', function() {

			if ($(this).is(':checked')) {
				$('#idDate_schedule').prop('disabled', false);
				$('#idTime_schedule').prop('disabled', false);
				$('#idDate_schedule').val('<?= $nextDay->format('d/m/Y'); ?>');
				// $('#idTime_schedule').val('<?= date('H:i') ?>');
				$('#idTime_schedule').val(getTime(Date.now()));
			} else {
				$('#idDate_schedule').prop('disabled', true);
				$('#idDate_schedule').val('');

				$('#idTime_schedule').prop('disabled', true);
				$('#idTime_schedule').val('');
			}
		});

		$("#idDate_schedule").datepicker({
			dateFormat: 'dd/mm/yy',
			changeMonth: true,
			dayNames: ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado', 'Domingo'],
			dayNamesMin: ['D', 'S', 'T', 'Q', 'Q', 'S', 'S', 'D'],
			dayNamesShort: ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'],
			monthNames: ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro', 'Janeiro'],
			monthNamesShort: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez', 'Jan'],
			minDate: '+1d'
		});

		$('#idTime_schedule').timepicker({
			timeFormat: 'H:mm',
			interval: 30,
			minTime: '08',
			maxTime: '11:30pm',
			// defaultTime: '11',
			startTime: '8:00',
			dynamic: false,
			dropdown: true,
			scrollbar: false
		});


	});

	/* Também funciona - mas no momento estou optando pela versão jQuery*/
	/* document.querySelectorAll('input[type=file]').forEach(input => {
		input.addEventListener('change', e => {
			e.target.nextElementSibling.innerText = input.files[0].name;
		});
	}); */


	/* Prevencao para mais de um submit */
	// var form = document.querySelector('form');
	// form.addEventListener('submit', function(e) {
	// 	var submittedClass = 'form-submitted';
	// 	if (this.classList.contains(submittedClass)) {
	// 		e.preventDefault();
	// 	} else {
	// 		this.classList.add(submittedClass);
	// 	}
	// }, false);


	function dateToBR_old(date) {
		var date = new Date(date);

		var year = date.getFullYear().toString();
		var month = (date.getMonth() + 101).toString().substring(1);
		var day = (date.getDate() + 100).toString().substring(1);

		return day + '/' + month + '/' + year;
	}

	function dateToBR(date) {

		let d = date.split('-')[2];
		let m = date.split('-')[1];
		let y = date.split('-')[0];

		var date = new Date();
		date.setDate(d);
		date.setMonth(m);
		date.setFullYear(y);

		var year = date.getFullYear().toString();
		var month = (date.getMonth() + 101).toString().substring(1);
		var day = (date.getDate() + 100).toString().substring(1);

		return day + '/' + month + '/' + year;
	}

	function getTime(date) {
		var date = new Date(date);

		var hour = ('0' + date.getHours()).slice(-2);
		var minute = ('0' + date.getMinutes()).slice(-2);
		var second = ('0' + date.getSeconds()).slice(-2);

		return hour + ':' + minute;
	}


	function popup_alerta(pagina) { //Exibe uma janela popUP
		x = window.open(pagina, 'Alerta', 'dependent=yes,width=700,height=470,scrollbars=yes,statusbar=no,resizable=yes');
		//x.moveTo(100,100);
		x.moveTo(window.parent.screenX + 50, window.parent.screenY + 50);
		return false
	}

	function checa_etiqueta() {
		// var inst = document.getElementById('idUnidade');
		// var inv = document.getElementById('idEtiqueta');

		if ($('#idUnidade').length > 0 && $('#idEtiqueta').length > 0) {
			if ($('#idUnidade').val() == '-1' || $('#idEtiqueta').val() == '') {
				/* var msg = '<?php print TRANS('MSG_UNIT_TAG'); ?>!'
				window.alert(msg); */
				$("#divModalDetails").html('<div class="modal-header bg-light"><h5 class="modal-title"><?php print TRANS('WARNING'); ?></h5><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button></div><div class="modal-body"><p><?php print TRANS('FILL_UNIT_TAG'); ?></p></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal"><?php print TRANS('LINK_CLOSE'); ?></button></div>');
				$('#modalDefault').modal();
			} else
				$("#divModalDetails").load('../../invmon/geral/equipment_show.php?unit=' + $('#idUnidade').val() + '&tag=' + $('#idEtiqueta').val());
			$('#modalDefault').modal();

			// CreativaPopup.create('', '', '', { 
			// 	content: '../../invmon/geral/equipment_show.php?unit=' + inst.value + '&tag=' + inv.value, 
			// 	isPage: true, 
			// 	width: '80%', 
			// 	borderRadius: '4px', 
			// });
		}
		return false;
	}


	function checa_chamados() {

		if ($('#idUnidade').length > 0 && $('#idEtiqueta').length > 0) {
			if ($('#idUnidade').val() == '-1' || $('#idEtiqueta').val() == '') {
				$("#divModalDetails").html('<div class="modal-header bg-light"><h5 class="modal-title"><?php print TRANS('WARNING'); ?></h5><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button></div><div class="modal-body"><p><?php print TRANS('FILL_UNIT_TAG'); ?></p></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal"><?php print TRANS('LINK_CLOSE'); ?></button></div>');
				$('#modalDefault').modal();
			} else
				// popup_alerta('../../invmon/geral/ocorrencias.php?comp_inst=' + inst.value + '&comp_inv=' + inv.value + '&popup=' + true);
				popup_alerta('./get_tickets_by_unit_and_tag.php?unit=' + $('#idUnidade').val() + '&tag=' + $('#idEtiqueta').val());
			// $("#divModalDetails").load('./get_tickets_by_unit_and_tag.php?unit=' + $('#idUnidade').val() + '&tag=' + $('#idEtiqueta').val());
			// $('#modal').modal();
		}
		return false;
	}

	function checa_por_local() {
		//var local = document.form.local.value;
		var local = document.getElementById('idLocal');
		if (local != null) {
			if (local.value == -1) {

				$("#divModalDetails").html('<div class="modal-header bg-light"><h5 class="modal-title"><?php print TRANS('WARNING'); ?></h5><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button></div><div class="modal-body"><p><?php print TRANS('FILL_LOCATION'); ?></p></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal"><?php print TRANS('LINK_CLOSE'); ?></button></div>');
				$('#modalDefault').modal();
			} else {
				// $("#divModalDetails").load('../../invmon/geral/equipments_list.php?comp_local=' + local.value + '&popup=' + true);
				// $('#modalDefault').modal();
				popup_alerta('../../invmon/geral/equipments_list.php?comp_local=' + local.value + '&popup=' + true);
			}

		}
		return false;
	}
</script>
</body>

</html>