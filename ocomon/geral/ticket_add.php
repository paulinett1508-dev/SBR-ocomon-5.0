<?php
ob_start(); 
session_start();
/*   
	Copyright 2023 Flávio Ribeiro

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

if (!isset($_SESSION['s_logado']) || $_SESSION['s_logado'] == 0) {
	$_SESSION['session_expired'] = 1;
	echo "<script>top.window.location = '../../index.php'</script>";
	exit();
}

require_once __DIR__ . "/" . "../../includes/include_geral_new.inc.php";
require_once __DIR__ . "/" . "../../includes/classes/ConnectPDO.php";

use includes\classes\ConnectPDO;

$conn = ConnectPDO::getInstance();
$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 3, 1);

$nextDay = new DateTime('+1 day');
$sysConfig = getConfig($conn);
$mailConfig = getMailConfig($conn);

$isDynamicMode = ($_SESSION['s_opening_mode'] == 2);
$issue_type = (isset($_GET['issue_type']) ? (int)($_GET['issue_type']) : "");
$issue_name = (!empty($issue_type) ? getIssueById($conn, $issue_type)['problema'] : "");

$params = "";


if ($isDynamicMode && empty($issue_type)) {
	/* Mode de abertura clássico - padrão */
	
	if (isset($_GET) && !empty($_GET)) {
		$params = "&" . http_build_query($_GET, "", "&");
	}
	
	header("Location: ./choose_ticket_type.php?" . $params);
	return;
}

$areaToOpen = [];
$possibleAreasToOpen = [];
if ($isDynamicMode) {
	$arrayAreaDynamic = getAreaInDynamicMode($conn, $issue_type, $_SESSION['s_area'], $_SESSION['s_uareas']);

	// var_dump([
	// 	'arrayAreaDynamic' => $arrayAreaDynamic
	// ]);

	$possibleAreasToOpen = $arrayAreaDynamic['common_areas_btw_issue_and_user'];
	$areaToOpen = $arrayAreaDynamic['area_receiver'];
	if ($arrayAreaDynamic['many_options']) {
		// $areaToOpen = $arrayAreaDynamic['common_areas_btw_issue_and_user'];
	}
}
// var_dump($arrayAreaDynamic);
// var_dump($areaToOpen);

if (isset($_GET['profile_id'])) {
	$profile_id = (int)$_GET['profile_id'];
} else {
	$profile_id = $_SESSION['s_screen'];
}

$screen = (!empty(getScreenInfo($conn, $profile_id)) ? getScreenInfo($conn, $profile_id) : getScreenInfo($conn, $_SESSION['s_screen']));

/* Para manter a compatibilidade com versões antigas */
$table = getTableCompat($conn);
$version4 = $sysConfig['conf_updated_issues'];

$onlyOpen = $_SESSION['s_nivel'] == 3;
$userInfo = getUserInfo($conn, $_SESSION['s_uid']);


if (!isset($_POST['submit']) || empty($_POST)) {
?>
	<!DOCTYPE html>
	<html lang="pt-BR">

	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title><?= TRANS('TICKET_OPENING'); ?></title>

		<link rel="stylesheet" type="text/css" href="../../includes/css/estilos.css" />
		<link rel="stylesheet" type="text/css" href="../../includes/components/jquery/datetimepicker/jquery.datetimepicker.css" />
		<link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap/custom.css" />
		<link rel="stylesheet" type="text/css" href="../../includes/components/fontawesome/css/all.min.css" />
		<link rel="stylesheet" type="text/css" href="../../includes/components/summernote/summernote-bs4.css" />
		<link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap-select/dist/css/bootstrap-select.min.css" />
		<link rel="stylesheet" type="text/css" href="../../includes/css/my_bootstrap_select.css" />

		<style>
			#iframeLoad {
				border: 1px solid lightgray !important;
				overflow: scroll !important;
			}
		</style>
	</head>

	<body>

		<?php

		/* Se a abertura de chamados não estiver habilitada para o perfil de tela */
		if ((!empty($screen) && !$screen['conf_user_opencall'])) {
			$msgDisable = TRANS('MSG_OPEN_TICKET_DISABLED');
			// echo mensagem($msgDisable);
			echo message('info', 'Ooops!', $msgDisable, '', '', true);
			exit;
		}

		/* Tratamento e checagem para ver o chamado pode receber chamados filhos */
		$father = (isset($_GET['pai']) && !empty($_GET['pai']) && filter_var($_GET['pai'], FILTER_VALIDATE_INT) ? $_GET['pai'] : "");

		$ticket = [];
		if (!empty($father) && isFatherOk($conn, $father)) {
			$ticket = getTicketData($conn, $father);
			$subCallMsg = message('info', '', TRANS('MSG_OCCO_SUBTICKET') . "&nbsp;" . $father, '', '', 1);
		} else $subCallMsg = "";

		?>



		<div class="container">
			<div id="idLoad" class="loading" style="display:none"></div>
			<!-- <div id="loading" class="loading" style="display:none"></div> -->
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

			<div class="modal" id="modalIframe" tabindex="-1" style="z-index:9001!important">
				<div class="modal-dialog modal-xl">
					<div class="modal-content">
						<div id="divDetails" style="position:relative">
							<iframe id="iframeLoad"  frameborder="0" style="position:absolute;top:0px;width:95%;height:100vh;"></iframe>
						</div>
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


				<div class="form-group row my-4">

					<?php
					if ($isDynamicMode) {
					?>
						<div class="form-group col-md-11 ml-4">
							<p class="text-secondary font-weight-bold"><i class="fas fa-ticket-alt"></i>&nbsp;<?= $issue_name; ?></p>
						</div>
						<div class="w-100"></div>
					<?php
					}


					/* CLIENTE */
					$selectedClient = (!empty($father) ? $ticket['client'] : $userInfo['user_client']);
					if ((isset($screen['conf_scr_client']) && $screen['conf_scr_client']) || empty($screen)) {
					?>
						<label for="client" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right "><?= TRANS('CLIENT'); ?></label>
						<div class="form-group col-md-4">
							<select class="form-control bts-select" id="client" name="client">
								<?php
								if ($onlyOpen) {
								?>
									<option value="<?= $userInfo['user_client']; ?>" selected><?= $userInfo['nickname']; ?></option>
								<?php
								} else {
									$clients = getClients($conn);
								?>
									<option value="" selected><?= TRANS('SEL_SELECT'); ?></option>
									<?php
									
									foreach ($clients as $client) {
									?>
										<option value="<?= $client['id']; ?>" 
										
										<?= ($client['id'] == $selectedClient ? " selected" : ""); ?>><?= $client['nickname']; ?></option>
								<?php
									}
								}
								?>
							</select>
						</div>
					<?php
					} else {
						/* Valores padrão */
						// $client = $userInfo['user_client'];
						$client = $selectedClient;
					?>
						<input type="hidden" name="client" value="<?= $client; ?>">
					<?php
					}



					/* Área de atendimento */
					if ((isset($screen['conf_scr_area']) && $screen['conf_scr_area']) || empty($screen)) {
					?>
						<label for="idArea" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('RESPONSIBLE_AREA'); ?></label>
						<div class="form-group col-md-4">
							<select class="form-control " id="idArea" name="sistema">

								<?php
								if (!$isDynamicMode || count($possibleAreasToOpen) != 1) {
								?>
									<option value="-1" selected><?= TRANS('SEL_AREA'); ?></option>
									<?php
								}

								/*** Areas que podem receber chamados do usuario logado */
								$areasTo = getAreasToOpen($conn, $_SESSION['s_uareas']);

								foreach ($areasTo as $areaTo) {
									if ($isDynamicMode && in_array($areaTo['sis_id'], $possibleAreasToOpen)) {
									?>
										<option value="<?= $areaTo['sis_id']; ?>"
										<?= ($areaTo['sis_id'] == $areaToOpen[0] ? ' selected' : ''); ?>
										><?= $areaTo['sistema']; ?></option>
									<?php
									} elseif (!$isDynamicMode) {
									?>
										<option value="<?= $areaTo['sis_id']; ?>"><?= $areaTo['sistema']; ?></option>
								<?php
									}
								}
								?>
							</select>
						</div>
					<?php

					} else {
						/* Se não existir a seleção de área e o modo dinâmico estiver habilitado */
						if ($isDynamicMode) {
							$sistema = $areaToOpen[0];
						} else {
							/* Buscará a informação definida no perfil de tela de abertura da área primária do usuário */
							$sistema = $screen['conf_opentoarea'] ?? "-1";
						}

					?>
						<input type="hidden" name="sistema" id="idArea" value="<?= $sistema; ?>">
					<?php
					}


					/* Tipo de problema */
					if (!$isDynamicMode && ((isset($screen['conf_scr_prob']) && $screen['conf_scr_prob']) || empty($screen))) {
					?>
						<label for="idProblema" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('ISSUE_TYPE'); ?></label>
						<div class="form-group col-md-4">
							<select class="form-control " id="idProblema" name="problema" <?= ($isDynamicMode ? " disabled" : ""); ?>>

								<option value=""><?= TRANS('ISSUE_TYPE'); ?></option>
								<?php

								if ($isDynamicMode) {
									$issues = getIssuesByArea4($conn, false, null, 0, $_SESSION['s_uareas'], null);
								} else {
									$issues = ($version4 ? getIssuesByArea4($conn, false, null, 1) : getIssuesByArea($conn));
								}

								foreach ($issues as $issue) {
								?>
									<option value="<?= $issue['prob_id']; ?>" <?= ($isDynamicMode && $issue['prob_id'] == $issue_type ? " selected" : ""); ?>><?= $issue['problema']; ?></option>

								<?php
								}
								?>
							</select>
						</div>
						<!-- Lista de tipos de problemas do mesmo tipo e categorias -->
						<div id="issueCategories"></div>
						<!-- Descrição do tipo de problema selecionado -->
						<div id="issueDescription"></div>
						<?php
						if ($isDynamicMode) {
						?>
							<input type="hidden" name="problema" value=<?= $issue_type; ?> />
						<?php
						}
					} else {
						/* Valores padrão */
						if ($isDynamicMode) {
						?>
							<input type="hidden" name="problema" value=<?= $issue_type; ?> />
						<?php
						} else {
						?>
							<input type="hidden" name="problema" value="">
						<?php
						}
					}


					/* Descrição do chamado */
					if ((isset($screen['conf_scr_desc']) && $screen['conf_scr_desc']) || empty($screen)) {
						?>
						<div class="w-100"></div>
						<label for="idDescricao" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('DESCRIPTION'); ?></label>

						<div class="form-group col-md-10">
							<textarea class="form-control" id="idDescricao" name="descricao" rows="4" required></textarea>
							<div class="invalid-feedback">
								<?= TRANS('MANDATORY_FIELD'); ?>
							</div>
							<small class="form-text text-muted">
								<?= TRANS('DESCRIPTION_HELPER'); ?>.
							</small>
						</div>
					<?php

					} else {
						/* Valores padrão */
					?>

						<input type="hidden" name="descricao" value="<?= TRANS('TICKET_EMPTY_DESCRIPTION'); ?>">
					<?php
					}


					/* Unidade */
					if ((isset($screen['conf_scr_unit']) && $screen['conf_scr_unit']) || empty($screen)) {
					?>
						<label for="idUnidade" class="col-sm-2 col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_UNIT'); ?></label>
						<div class="form-group col-md-4">
							<select class="form-control bts-select" id="idUnidade" name="instituicao">
								<option value="-1" selected><?= TRANS('LOADING'); ?></option>

							</select>
						</div>
					<?php
					} else {
						/* Valores padrão */
					?>
						<input type="hidden" name="instituicao" value="-1">
					<?php
					}



					/* Etiqueta do equipamento */
					if ((isset($screen['conf_scr_tag']) && $screen['conf_scr_tag']) || empty($screen)) {
					?>
						<label for="idEtiqueta" class="col-md-2 col-form-label col-form-label-sm text-md-right text-nowrap"><?= TRANS('ASSET_TAG'); ?></label>

						<div class="form-group col-md-4">
							<div class="input-group">
								<?php
								if ((isset($screen['conf_scr_chktag']) && $screen['conf_scr_chktag']) || empty($screen)) {
								?>
									<div class="input-group-prepend">
										<div class="input-group-text">
											<a href="javascript:void(0);" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('CONS_CONFIG_EQUIP'); ?>" onClick="checa_etiqueta()"><i class="fa fa-sliders-h"></i></a>
										</div>
									</div>
								<?php
								}
								?>
								<input type="text" class="form-control " id="idEtiqueta" name="equipamento" value="<?= (count($ticket) ? $ticket['equipamento'] : ""); ?>" placeholder="<?= TRANS('FIELD_TAG_EQUIP'); ?>" />
								<?php
								if ((isset($screen['conf_scr_chkhist']) && $screen['conf_scr_chkhist']) || empty($screen)) {
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
						/* Valores padrão */
					?>
						<input type="hidden" name="equipamento" value="">
					<?php
					}


					/* Contato */
					if ((isset($screen['conf_scr_contact']) && $screen['conf_scr_contact']) || empty($screen)) {
						$contact_name = "";
						if ($_SESSION['s_nivel'] == 3) {
							$contact_name = $_SESSION['s_usuario_nome'];
						}
					?>
						<label for="contato" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CONTACT'); ?></label>
						<div class="form-group col-md-4">
							<input type="text" class="form-control " id="contato" name="contato" list="contatos" value="<?= (count($ticket) ? $ticket['contato'] : $contact_name); ?>" autocomplete="off" placeholder="<?= TRANS('CONTACT_PLACEHOLDER'); ?>" />
						</div>
						<datalist id="contatos"></datalist>
					<?php
					} else {
						// valores padrão
					?>
						<input type="hidden" name="contato" value="<?= $_SESSION['s_usuario_nome']; ?>">
					<?php
					}


					/* E-mail de contato */
					$contact_email_disable = "";
					$contato_email = getUserInfo($conn, $_SESSION['s_uid'])['email'];
					if ($_SESSION['s_nivel'] == "3") {
						$contact_email_disable = " readonly ";
					}
					if ((isset($screen['conf_scr_contact_email']) && $screen['conf_scr_contact_email']) || empty($screen)) {


					?>
						<label for="contato_email" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('CONTACT_EMAIL'); ?></label>
						<div class="form-group col-md-4">
							<input type="email" class="form-control " id="contato_email" name="contato_email" <?= $contact_email_disable; ?> list="contatos_emails" value="<?= $contato_email; ?>" autocomplete="off" placeholder="<?= TRANS('CONTACT_EMAIL_PLACEHOLDER'); ?>" />
						</div>
						<datalist id="contatos_emails"></datalist>
					<?php
					} else {
						/* Valores padrão */
					?>
						<input type="hidden" name="contato_email" value="<?= $contato_email; ?>">
					<?php
					}


					/* Telefone */
					if ((isset($screen['conf_scr_fone']) && $screen['conf_scr_fone']) || empty($screen)) {
					?>
						<label for="idTelefone" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_PHONE'); ?></label>
						<div class="form-group col-md-4">
							<input type="tel" class="form-control " id="idTelefone" name="telefone" value="<?= (count($ticket) ? $ticket['telefone'] : ""); ?>" placeholder="<?= TRANS('PHONE_PLACEHOLDER'); ?>" />
						</div>
					<?php
					} else {
						/* Valores padrão */
					?>
						<input type="hidden" name="telefone" value="">
					<?php
					}


					/* Departamentos */
					if ((isset($screen['conf_scr_local']) && $screen['conf_scr_local']) || empty($screen)) {
					?>
						<label for="idLocal" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('DEPARTMENT'); ?></label>

						<div class="form-group col-md-4">
							<div class="input-group">
								<?php
								if ((isset($screen['conf_scr_btloadlocal']) && $screen['conf_scr_btloadlocal']) || empty($screen)) {
								?>
									<div class="input-group-prepend">
										<div class="input-group-text">
											<a href="javascript:void(0);" id="load_department" title="<?= TRANS('LOAD_DEPARTMENT_OF_THE_ASSET_TAG'); ?>"><i class="fa fa-sync-alt"></i></a>
										</div>
									</div>
								<?php
								}
								?>

								<select class="form-control " name="local" id="idLocal">
									<option value="-1"><?= TRANS('SEL_DEPARTMENT'); ?></option>


								</select>
								<?php
								if ((isset($screen['conf_scr_searchbylocal']) && $screen['conf_scr_searchbylocal']) || empty($screen)) {
								?>
									<div class="input-group-append">
										<div class="input-group-text">
											<a href="javascript:void(0);" title="<?= TRANS('CONS_EQUIP_LOCAL'); ?>" onClick="checa_por_local()"><i class="fa fa-search"></i></a>
										</div>
									</div>
								<?php
								}
								?>

							</div>
						</div>
					<?php
					} else {
						/* Valores padrão */
					?>
						<input type="hidden" name="local" value="">
					<?php
					}


					/* Operador */
					if ((isset($screen['conf_scr_operator']) && $screen['conf_scr_operator']) || empty($screen)) {
						$operatorLabel = ($_SESSION['s_nivel'] == 3 ? TRANS('REQUESTER') : TRANS('WORKER'));
					?>
						<label for="tecnico" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= $operatorLabel; ?></label>
						<div class="form-group col-md-4">
							<input type="text" class="form-control  " readonly id="tecnico" name="tecnico" value="<?= $_SESSION['s_usuario_nome']; ?>" />
						</div>
					<?php
					}


					/* Uoload de arquivos */
					if ((isset($screen['conf_scr_upload']) && $screen['conf_scr_upload']) || empty($screen)) {
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


					/* Prioridade */
					if ((isset($screen['conf_scr_prior']) && $screen['conf_scr_prior']) || empty($screen)) {
					?>
						<label for="idPrioridade" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('OCO_PRIORITY'); ?></label>
						<div class="form-group col-md-4">
							<select class="form-control " id="idPrioridade" name="prioridade">
								<?php
								$priorities = getPriorities($conn);
								foreach ($priorities as $priority) {
								?>
									<option value="<?= $priority['pr_cod']; ?>" <?= ($priority['pr_default'] ? " selected" : ""); ?>><?= $priority['pr_desc']; ?></option>
								<?php
								}
								?>
							</select>
						</div>
					<?php
					} else {
						/* Valores padrão */
					?>
						<input type="hidden" name="prioridade" value="<?= getDefaultPriority($conn)['pr_cod']; ?>">
					<?php
					}



					/* Encaminhar para operador */
					if (((isset($screen['conf_scr_foward']) && $screen['conf_scr_foward']) || empty($screen)) && $_SESSION['s_can_route']) {
					?>
						<label for="idFoward" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('FORWARD_TICKET_TO'); ?></label>
						<div class="form-group col-md-4">
							<select class="form-control " id="idFoward" name="foward">
								<option value=""><?= TRANS('OCO_SEL_OPERATOR'); ?></option>
								<?php
								// $users = getUsersByPrimaryArea($conn, null, [1, 2]);
								$users = getUsersByArea($conn, null, true, null, true);	
								foreach ($users as $user) {
									/* getOperatorTickets */
								?>
									<option value="<?= $user['user_id']; ?>"><?= $user['nome']; ?></option>
								<?php
								}
								?>
							</select>
						</div>
						<?php
					}


					/**
					 * Opções para envio de e-mail
					 * Só exibirá as opções de envio caso o envio de e-mails esteja habilitado
					 */
					if ($mailConfig['mail_send']) {
						if ((isset($screen['conf_scr_mail']) && $screen['conf_scr_mail']) || empty($screen)) {
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


					/* Data */
					if ((isset($screen['conf_scr_date']) && $screen['conf_scr_date']) || empty($screen)) {
						?>
						<label for="data_abertura" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('OPENING_DATE'); ?></label>
						<div class="form-group col-md-4">
							<input type="text" class="form-control  " readonly id="data_abertura" name="data_abertura" value="<?= date("d/m/Y H:i:s"); ?>" />
						</div>
					<?php
					}

					/* Status do chamado */
					if ((isset($screen['conf_scr_status']) && $screen['conf_scr_status']) || empty($screen)) {
					?>
						<label for="status" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('COL_STATUS'); ?></label>
						<div class="form-group col-md-4">
							<input type="text" class="form-control  " readonly id="status" name="status" value="<?= TRANS('STATUS_WAITING'); ?>" />
						</div>
					<?php
					}


					/* Agendamento do chamado */
					if ((isset($screen['conf_scr_schedule']) && $screen['conf_scr_schedule']) || empty($screen)) {
					?>
						<label for="idDate_schedule" class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= TRANS('TO_SCHEDULE'); ?></label>
						<div class="form-group col-md-4">
							<div class="input-group">
								<div class="input-group-prepend">
									<div class="input-group-text">
										<input type="checkbox" name="allowSchedule" id="allowSchedule" value="1">
									</div>
								</div>
								<input type="text" class="form-control " id="idDate_schedule" name="date_schedule" value="" placeholder="<?= TRANS('DATE_TO_SCHEDULE'); ?>" autocomplete="off" disabled /> <!--  -->
							</div>
						</div>
						<!-- <div class="form-group col-md-2">
							<input type="text" class="form-control " id="idTime_schedule" name="time_schedule" value="" placeholder="<?= TRANS('PLACEHOLDER_SCHEDULE_TIME'); ?>" autocomplete="off" disabled />
						</div> -->
					<?php
					}


					/* Campos personalizados - customizados*/
					$fields_id = [];
					$fields_only_edition_ids = [];
					if (isset($screen['conf_scr_custom_ids']) && !empty($screen['conf_scr_custom_ids'])) {
						$fields_id = explode(',', (string)$screen['conf_scr_custom_ids']);
						$fields_only_edition_ids = explode(',', (string)$screen['cfields_only_edition']);

						$labelColSize = 2;
						$fieldColSize = 4;
						$fieldRowSize = 10;
						$custom_fields = getCustomFields($conn, null, 'ocorrencias');
					?>
						<!-- <div class="w-100"></div> -->
						<?php
						foreach ($custom_fields as $row) {

							if (in_array($row['id'], $fields_id) && !in_array($row['id'], $fields_only_edition_ids)) {

								$inlineAttributes = keyPairsToHtmlAttrs($row['field_attributes']);
								$maskType = ($row['field_mask_regex'] ? 'regex' : 'mask');
								$fieldMask = "data-inputmask-" . $maskType . "=\"" . $row['field_mask'] . "\"";
						?>

								<?= ($row['field_type'] == 'textarea' ? '<div class="w-100"></div>'  : ''); ?>
								<label for="<?= $row['field_name']; ?>" class="col-sm-<?= $labelColSize; ?> col-md-<?= $labelColSize; ?> col-form-label col-form-label-sm text-md-right " title="<?= $row['field_title']; ?>" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= $row['field_description']; ?>"><?= $row['field_label']; ?></label>
								<div class="form-group col-md-<?= ($row['field_type'] == 'textarea' ? $fieldRowSize  : $fieldColSize); ?>">
									<?php
									if ($row['field_type'] == 'select') {
									?>
										<select class="form-control custom_field_select" name="<?= $row['field_name']; ?>" id="<?= $row['field_name']; ?>" <?= $inlineAttributes; ?>>
											<?php

											$options = [];
											$options = getCustomFieldOptionValues($conn, $row['id']);
											?>
											<option value=""><?= TRANS('SEL_SELECT'); ?></option>
											<?php
											foreach ($options as $rowValues) {
											?>
												<option value="<?= $rowValues['id']; ?>" <?= ($row['field_default_value'] == $rowValues['option_value'] ? " selected" : ""); ?>><?= $rowValues['option_value']; ?></option>
											<?php
											}
											?>
										</select>
									<?php
									} elseif ($row['field_type'] == 'select_multi') {
									?>
										<select class="form-control custom_field_select_multi" name="<?= $row['field_name']; ?>[]" id="<?= $row['field_name']; ?>" multiple="multiple" placeholder="<?= $row['field_placeholder']; ?>" <?= $inlineAttributes; ?>>
											<?php
											$defaultSelections = explode(',', (string)$row['field_default_value']);
											$options = [];
											$options = getCustomFieldOptionValues($conn, $row['id']);
											?>
											<?php
											foreach ($options as $rowValues) {
											?>
												<option value="<?= $rowValues['id']; ?>" <?= (in_array($rowValues['option_value'], $defaultSelections) ? ' selected' : ''); ?>><?= $rowValues['option_value']; ?></option>
											<?php
											}
											?>
										</select>
									<?php
									} elseif ($row['field_type'] == 'number') {
									?>
										<input class="form-control custom_field_number" type="number" name="<?= $row['field_name']; ?>" id="<?= $row['field_name']; ?>" value="<?= $row['field_default_value'] ?? ''; ?>" placeholder="<?= $row['field_placeholder']; ?>" <?= $inlineAttributes; ?>>
									<?php
									} elseif ($row['field_type'] == 'checkbox') {
										$checked_checkbox = ($row['field_default_value'] ? " checked" : "");
									?>
										<div class="form-check form-check-inline">
											<input class="form-check-input custom_field_checkbox" type="checkbox" name="<?= $row['field_name']; ?>" id="<?= $row['field_name']; ?>" <?= $checked_checkbox ?> <?= $inlineAttributes; ?>>
											<legend class="col-form-label col-form-label-sm"><?= $row['field_placeholder']; ?></legend>
										</div>
									<?php
									} elseif ($row['field_type'] == 'textarea') {
									?>
										<textarea class="form-control custom_field_textarea" name="<?= $row['field_name']; ?>" id="<?= $row['field_name']; ?>" placeholder="<?= $row['field_placeholder']; ?>" <?= $inlineAttributes; ?>><?= $row['field_default_value'] ?? ''; ?></textarea>
									<?php
									} elseif ($row['field_type'] == 'date') {
									?>
										<input class="form-control custom_field_date" type="text" name="<?= $row['field_name']; ?>" id="<?= $row['field_name']; ?>" value="<?= $row['field_default_value'] ?? ''; ?>" placeholder="<?= $row['field_placeholder']; ?>" <?= $inlineAttributes; ?> autocomplete="off">
									<?php
									} elseif ($row['field_type'] == 'time') {
									?>
										<input class="form-control custom_field_time" type="text" name="<?= $row['field_name']; ?>" id="<?= $row['field_name']; ?>" value="<?= $row['field_default_value'] ?? ''; ?>" placeholder="<?= $row['field_placeholder']; ?>" <?= $inlineAttributes; ?> autocomplete="off">
									<?php
									} elseif ($row['field_type'] == 'datetime') {
									?>
										<input class="form-control custom_field_datetime" type="text" name="<?= $row['field_name']; ?>" id="<?= $row['field_name']; ?>" value="<?= $row['field_default_value'] ?? ''; ?>" placeholder="<?= $row['field_placeholder']; ?>" <?= $inlineAttributes; ?> autocomplete="off">
									<?php
									} else {
									?>
										<input class="form-control custom_field_text" type="text" name="<?= $row['field_name']; ?>" id="<?= $row['field_name']; ?>" value="<?= $row['field_default_value'] ?? ''; ?>" placeholder="<?= $row['field_placeholder']; ?>" <?= $fieldMask; ?> <?= $inlineAttributes; ?> autocomplete="off">
									<?php
									}
									?>
								</div>


					<?php
							}
						} /* foreach */
					}
					?>
					<!-- <div class="w-100"></div> -->

					<?php
					/* Fim dos campos personalizados */








					/* Canal de atendimento */
					$defaultChannel = getDefaultChannel($conn);
					if ((isset($screen['conf_scr_channel']) && $screen['conf_scr_channel']) || empty($screen)) {
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

					/* Input se for chamado filho */
					if (!empty($father)) {
					?>
						<input type="hidden" name="pai" value="<?= $father; ?>">
					<?php
					}


					if ($version4) {
					?>
						<input type="hidden" name="url_process" id="url_process" value="get_issues_by_area4.php" />
					<?php
					} else {
					?>
						<input type="hidden" name="url_process" id="url_process" value="get_issues_by_area.php" />
					<?php
					}
					ob_end_flush();
					?>



					<input type="hidden" name="isDynamicMode" id="isDynamicMode" value="<?= $isDynamicMode; ?>" />
					<input type="hidden" name="profile_id" id="profile_id" value="<?= $profile_id; ?>" />
					<input type="hidden" name="issue_type" id="issue_type" value="<?= $issue_type; ?>" />
					<input type="hidden" name="action" value="open" />
					<input type="hidden" name="submit" value="submit" />

					<div class="w-100"></div>
					<div class="form-group col-md-8 d-none d-md-block">
					</div>
					<div class="form-group col-12 col-md-2 ">
						<button type="submit" id="idSubmit" class="btn btn-primary btn-block" onClick="LOAD=0;"><?= TRANS('BT_OK'); ?></button>
					</div>
					<div class="form-group col-12 col-md-2">
						<button type="reset" class="btn btn-secondary btn-block" onClick="parent.history.back();"><?= TRANS('BT_CANCEL'); ?></button>
					</div>

				</div>

			</form>
		</div>

	<?php
}
	?>


	<script src="../../includes/javascript/funcoes-3.0.js"></script>
	<script src="../../includes/components/jquery/jquery.js"></script>
	<script src="../../includes/components/jquery/jquery.initialize.min.js"></script>
	<script src="../../includes/components/jquery/datetimepicker/build/jquery.datetimepicker.full.min.js"></script>
	<script src="../../includes/components/bootstrap/js/bootstrap.bundle.js"></script>
	<script src="../../includes/components/bootstrap-select/dist/js/bootstrap-select.min.js"></script>
	<script src="../../includes/components/summernote/summernote-bs4.js"></script>
	<script src="../../includes/components/summernote/lang/summernote-pt-BR.min.js"></script>
	<script src="../../includes/components/Inputmask-5.x/dist/jquery.inputmask.min.js"></script>
	<script src="../../includes/components/Inputmask-5.x/dist/bindings/inputmask.binding.js"></script>

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

			if ($("#idArea").length > 0) {
				if ($('#idFoward').length > 0) {
					loadOperators();
				}
			}


			loadUnits();

			$("#client").on('change', function() {
				loadUnits();
			});

			$("#idUnidade").on('change', function() {
				loadDepartments();
			});


			/* Load issues type and operators */
			if ($("#idArea").length > 0) {
				$("#idArea").off().on("change", function() {
					if (!$("#isDynamicMode").val()) {
						showIssuesByArea($('#idProblema').val() ?? '');
					}
					if ($('#idFoward').length > 0) {
						loadOperators();
					}
					if ($("#idProblema").length > 0 && !$("#isDynamicMode").val()) {
						showSelectedIssue();
						showIssueDescription($("#idProblema").val());
					}
					if ($('#mailOP').length > 0)
						$('#mailOP').prop('disabled', true).prop('checked', false);
				});
			}

			/* Show selected issue */
			if ($("#idProblema").length > 0 && !$("#isDynamicMode").val()) {

				// $('#idProblema').selectpicker('refresh');

				$("#idProblema").off().on("change", function() {
					showSelectedIssue();
					showIssueDescription($("#idProblema").val());
				});
			}

			if ($("#idProblema").length > 0 && !$("#isDynamicMode").val()) {
				/* Adicionei o mutation observer em função dos elementos que são adicionados após o carregamento do DOM */
				var obsRadio = $.initialize(".radio_prob", function() {
					$(".radio_prob").off().on('click', function() {
						showIssueDescription($(this).val());
					});
				}, {
					target: document.getElementById('form')
				}); /* o target limita o scopo do observer */
			}

			if ($("#load_department").length > 0) {

				$("#load_department").on('click', function() {
					loadDepartment();
				});
			}

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

			var bar = '<?php print $_SESSION['s_formatBarOco']; ?>';
			if ($('#idDescricao').length > 0 && bar == 1) {
				$('#idDescricao').summernote({
					// placeholder: 'Hello Bootstrap 4',
					toolbar: [
						['style', ['style']],
						['font', ['bold', 'underline', 'clear']],
						['fontname', ['fontname']],
						['fontsize', ['fontsize']],
						['color', ['color']],
						['para', ['ul', 'ol', 'paragraph']],
						['table', ['table']],
						['insert', ['link']],
						['view', ['fullscreen']],
					],
					lang: 'pt-BR', // default: 'en-US'
					tabsize: 2,
					// height: 100,
					height: 100, // set editor height
					minHeight: null, // set minimum height of editor
					maxHeight: null, // set maximum height of editor
					// focus: true // set focus to editable area after initializing summernote
				});
			}

			controlCheckUserMailSend();
			if ($('#contato_email').length > 0) {
				$('#contato_email').on('blur', function() {
					controlCheckUserMailSend();
				});
			}

			if ($('#idFoward').length > 0) {
				$('#idFoward').on('change', function() {
					toogleMailOperator();
				});
			}


			$('input, select, textarea').on('blur', function() {
				if ($(this).val() != '') {
					$(this).removeClass('is-invalid');
				}
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
						// let scheduleProcess = getScheduleData(response.numero);



						/* Agendamento/encaminhamento */
						if ($('#mailAR').length > 0) {
							var sendEmailToArea = ($('#mailAR').is(':checked') ? true : false);
						} else {
							var sendEmailToArea = false;
						}

						if ($('#mailUS').length > 0) {
							var sendEmailToUser = ($('#mailUS').is(':checked') ? true : false);
						} else {
							var sendEmailToUser = false;
						}

						if ($('#mailOP').length > 0) {
							var sendEmailToWorkers = ($('#mailOP').is(':checked') ? true : false);
						} else {
							var sendEmailToWorkers = false;
						}

						var loading = $(".loading");
						$(document).ajaxStart(function() {
							loading.show();
						});
						$(document).ajaxStop(function() {
							loading.hide();
						});

						$.ajax({
							url: 'schedule_ticket.php',
							method: 'POST',
							dataType: 'json',
							data: {
								'numero': response.numero,
								'scheduleDate': $('#idDate_schedule').val(),
								'main_worker': $('#idFoward').val(),
								'action': 'open',
								'sendEmailToArea': sendEmailToArea,
								'sendEmailToUser': sendEmailToUser,
								'sendEmailToWorkers': sendEmailToWorkers
							},
						}).done(function(data) {
							if (!data.success) {
								return false;
							} else {
								$('#divResult').html('');
								$('input, select, textarea').removeClass('is-invalid');
								$("#idSubmit").prop("disabled", false);
								var url = 'ticket_show.php?numero=' + response.numero;
								$(location).prop('href', url);
								return false;
							}
						});

						// $('#divResult').html('');
						// $('input, select, textarea').removeClass('is-invalid');
						// $("#idSubmit").prop("disabled", false);
						// var url = 'ticket_show.php?numero=' + response.numero;
						// $(location).prop('href', url);
						// return false;
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
					$('#idDate_schedule').val('<?= $nextDay->format('d/m/Y H:i'); ?>');
				} else {
					$('#idDate_schedule').prop('disabled', true);
					$('#idDate_schedule').val('');
				}
			});


			$.fn.selectpicker.Constructor.BootstrapVersion = '4';
			$('.custom_field_select_multi').selectpicker({
				/* placeholder */
				title: "<?= TRANS('SEL_SELECT', '', 1); ?>",
				liveSearch: true,
				liveSearchNormalize: true,
				liveSearchPlaceholder: "<?= TRANS('BT_SEARCH', '', 1); ?>",
				noneResultsText: "<?= TRANS('NO_RECORDS_FOUND', '', 1); ?> {0}",
				style: "",
				styleBase: "form-control input-select-multi",
			});


			$('.bts-select').selectpicker({
				/* placeholder */
				title: "<?= TRANS('SEL_SELECT', '', 1); ?>",
				showSubtext: true,
				liveSearch: true,
				liveSearchNormalize: true,
				liveSearchPlaceholder: "<?= TRANS('BT_SEARCH', '', 1); ?>",
				noneResultsText: "<?= TRANS('NO_RECORDS_FOUND', '', 1); ?> {0}",
				style: "",
				styleBase: "form-control input-select-multi",
			});


			if ($('#idProblema').length > 0 && !$("#isDynamicMode").val()) {
				$('#idProblema').selectpicker({
					/* placeholder */
					title: "<?= TRANS('ISSUE_TYPE', '', 1); ?>",
					liveSearch: true,
					liveSearchNormalize: true,
					liveSearchPlaceholder: "<?= TRANS('BT_SEARCH', '', 1); ?>",
					noneResultsText: "<?= TRANS('NO_RECORDS_FOUND', '', 1); ?> {0}",
					style: "",
					styleBase: "form-control input-select-multi",
				});
			}

			if ($('#idLocal').length > 0) {
				$('#idLocal').selectpicker({
					/* placeholder */
					title: "<?= TRANS('DEPARTMENT', '', 1); ?>",
					showSubtext: true,
					liveSearch: true,
					liveSearchNormalize: true,
					liveSearchPlaceholder: "<?= TRANS('BT_SEARCH', '', 1); ?>",
					noneResultsText: "<?= TRANS('NO_RECORDS_FOUND', '', 1); ?> {0}",
					style: "",
					styleBase: "form-control input-select-multi",
				});
			}


			/* Idioma global para os calendários */
			$.datetimepicker.setLocale('pt-BR');


			$('#idDate_schedule').datetimepicker({
				timepicker: true,
				format: 'd/m/Y H:i',
				step: 30,
				minDate: 0,
				lazyInit: true
			});


			/* Para campos personalizados - bind pela classe*/
			$('.custom_field_datetime').datetimepicker({
				timepicker: true,
				format: 'd/m/Y H:i',
				step: 30,
				// minDate: 0,
				lazyInit: true
			});

			$('.custom_field_date').datetimepicker({
				timepicker: false,
				format: 'd/m/Y',
				lazyInit: true
			});

			$('.custom_field_time').datetimepicker({
				datepicker: false,
				format: 'H:i',
				step: 30,
				lazyInit: true
			});
		});


		/**
		 * Funções
		 */
		function showIssuesByArea(selected_id = '') {
			/* Exibir os tipos de problemas de acordo com a selecao da área de atendimento */
			if ($('#idProblema').length > 0) {

				var loading = $(".loading");
				$(document).ajaxStart(function() {
					loading.show();
				});
				$(document).ajaxStop(function() {
					loading.hide();
				});

				$.ajax({
					url: $('#url_process').val(),
					method: 'POST',
					dataType: 'json',
					data: {
						area: $('#idArea').val(),
						issue_selected: $('#issue_selected').val() ?? '',
					},
				}).done(function(response) {
					$('#idProblema').empty().append('<option value=""><?= TRANS('ISSUE_TYPE'); ?></option>');
					for (var i in response) {
						var option = '<option value="' + response[i].prob_id + '">' + response[i].problema + '</option>';
						$('#idProblema').append(option);

						if (selected_id !== '') {
							if ($("#idProblema").find('option[value="' + selected_id + '"]').length === 0) {
								$('#idProblema').val("").change();
							} else {
								$('#idProblema').val(selected_id).change();
							}
						} else
						if ($('#issue_selected').val() != '') {
							$('#idProblema').val($('#issue_selected').val()).change();
						}
					}
					$('#idProblema').selectpicker('refresh').selectpicker('val', selected_id);
				});
			}
		}

		function showSelectedIssue() {

			if ($('#idProblema').length > 0) {
				var loading = $(".loading");
				$(document).ajaxStart(function() {
					loading.show();
				});
				$(document).ajaxStop(function() {
					loading.hide();
				});

				$.ajax({
					url: './get_issue_detailed.php',
					method: 'POST',
					dataType: 'json',
					data: {
						area: $('#idArea').val() ?? '',
						issue_selected: $('#idProblema').val() ?? '',
					},
				}).done(function(response) {

					if (response.length > 0) {
						$('#issueCategories').addClass("form-group col-md-12");
						$('#issueCategories').empty();

						var html = '<table class="table table-striped table-hover">';
						html += '<thead bg-secondary">';
						html += '<tr class="header">';
						html += '<td><?= TRANS('ISSUE_TYPE'); ?></td>';
						html += '<td><?= TRANS('COL_SLA'); ?></td>';
						html += '<td><?= $sysConfig['conf_prob_tipo_1']; ?></td>';
						html += '<td><?= $sysConfig['conf_prob_tipo_2']; ?></td>';
						html += '<td><?= $sysConfig['conf_prob_tipo_3']; ?></td>';
						html += '</tr>';
						html += '</thead>';
						for (var i in response) {
							html += '<tr>';
							html += '<td>';
							html += '<input type="radio" class="radio_prob" id="idRadioProb' + response[i].prob_id + '" name="radio_prob" value="' + response[i].prob_id + '"';
							if (response[i].prob_id == $("#idProblema").val()) {
								html += ' checked';
							}
							html += '> ';
							html += response[i].problema;
							html += '</td>';
							html += '<td>' + (response[i].slas_desc ?? '') + '</td>';
							html += '<td>' + (response[i].probt1_desc ?? '') + '</td>';
							html += '<td>' + (response[i].probt2_desc ?? '') + '</td>';
							html += '<td>' + (response[i].probt3_desc ?? '') + '</td>';
							html += '</tr>';
						}
						html += '</table>';
						$('#issueCategories').append(html);
					} else {
						$('#issueCategories').removeClass("form-group col-md-12");
						$('#issueCategories').empty();
					}
				});
			}
		}


		function showIssueDescription(val) {

			var loading = $(".loading");
			$(document).ajaxStart(function() {
				loading.show();
			});
			$(document).ajaxStop(function() {
				loading.hide();
			});

			$.ajax({
				url: './get_issue_description.php',
				method: 'POST',
				dataType: 'json',
				data: {
					prob_id: val,
				},
			}).done(function(response) {
				if (response.description != '') {
					$("#issueDescription").addClass("form-group col-md-12");
				} else {
					$("#issueDescription").removeClass("form-group col-md-12");
					$("#issueDescription").empty();
				}
				$("#issueDescription").empty().html(response.description);
			});
		}


		function loadUnits() {

			if ($("#idUnidade").length > 0) {
				var loading = $(".loading");
				$(document).ajaxStart(function() {
					loading.show();
				});
				$(document).ajaxStop(function() {
					loading.hide();
				});

				$.ajax({
					url: './get_units_by_client.php',
					method: 'POST',
					dataType: 'json',
					data: {
						client: $("#client").val()
					},
				}).done(function(data) {
					$('#idUnidade').empty();
					$('#idUnidade').selectpicker('refresh');
					if (Object.keys(data).length > 1) {
						$('#idUnidade').append('<option value=""><?= TRANS("SEL_SELECT"); ?></option>');
					}
					$.each(data, function(key, data) {
						$('#idUnidade').append('<option value="' + data.inst_cod + '">' + data.inst_nome + '</option>');
					});
					$('#idUnidade').selectpicker('refresh');

					loadDepartments();
				});
			} else if ($("#idLocal").length > 0) {
				loadDepartments();
			}
		}

		function loadDepartments() {

			if ($("#idLocal").length > 0) {
				var loading = $(".loading");
				$(document).ajaxStart(function() {
					loading.show();
				});
				$(document).ajaxStop(function() {
					loading.hide();
				});

				$.ajax({
					url: './get_departments_by_client_unit.php',
					method: 'POST',
					dataType: 'json',
					data: {
						client: $("#client").val(),
						unit: $("#idUnidade").val()
					},
				}).done(function(data) {
					$('#idLocal').empty();
					if (Object.keys(data).length > 1) {
						$('#idLocal').append('<option value=""><?= TRANS("SEL_SELECT"); ?></option>');
					}
					$.each(data, function(key, data) {

						let unit = "";
						if (data.unidade != null) {
							unit = ' (' + data.unidade + ')';
						}
						$('#idLocal').append('<option data-subtext="' + unit + '" value="' + data.loc_id + '">' + data.local + '</option>');
					});
					$('#idLocal').selectpicker('refresh');
				});
			}
		}



		function loadDepartment() {
			var loading = $(".loading");
			$(document).ajaxStart(function() {
				loading.show();
			});
			$(document).ajaxStop(function() {
				loading.hide();
			});

			$.ajax({
				url: './get_department_by_unit_and_tag.php',
				method: 'POST',
				dataType: 'json',
				data: {
					unit: $("#idUnidade").val(),
					tag: $("#idEtiqueta").val()
				},
			}).done(function(response) {
				if (response.department != "") {
					// $('#idLocal').val(response.department).change();
					$('#idLocal').selectpicker('refresh');
					$('#idLocal').selectpicker('val', response.department);
				}
			});
		}


		function loadOperators() {
			var loading = $(".loading");
			$(document).ajaxStart(function() {
				loading.show();
			});
			$(document).ajaxStop(function() {
				loading.hide();
			});

			$.ajax({
				url: './get_operators_by_area.php',
				method: 'POST',
				dataType: 'json',
				data: {
					area: $("#idArea").val(),
				},
			}).done(function(response) {
				// console.log(response);
				$('#idFoward').empty().append('<option value=""><?= TRANS('OCO_SEL_OPERATOR'); ?></option>');
				for (var i in response) {
					var option = '<option value="' + response[i].user_id + '">' + response[i].nome + ': ' + response[i].total + '</option>';
					$('#idFoward').append(option);
				}
			});
		}


		function toogleMailOperator() {
			if ($('#idFoward').length > 0) {
				if ($("#idFoward").val() != '') {

					if ($('#mailOP').length > 0)
						$('#mailOP').prop('disabled', false);
				} else {
					if ($('#mailOP').length > 0)
						$('#mailOP').prop('disabled', true).prop('checked', false);
				}
			}
		}


		function getScheduleData(numero) {
            if ($('#mailAR').length > 0) {
                var sendEmailToArea = ($('#mailAR').is(':checked') ? true : false);
            } else {
                var sendEmailToArea = false;
            }

            if ($('#mailUS').length > 0) {
                var sendEmailToUser = ($('#mailUS').is(':checked') ? true : false);
            } else {
                var sendEmailToUser = false;
            }

            if ($('#mailOP').length > 0) {
                var sendEmailToWorkers = ($('#mailOP').is(':checked') ? true : false);
            } else {
                var sendEmailToWorkers = false;
            }

            var loading = $(".loading");
            $(document).ajaxStart(function() {
                loading.show();
            });
            $(document).ajaxStop(function() {
                loading.hide();
            });

            $.ajax({
                url: 'schedule_ticket.php',
                method: 'POST',
                dataType: 'json',
                data: {
                    'numero': numero,
                    'scheduleDate': $('#idDate_schedule').val(),
                    'main_worker': $('#idFoward').val(),
					'action': 'open',
                    'sendEmailToArea': sendEmailToArea,
                    'sendEmailToUser': sendEmailToUser,
                    'sendEmailToWorkers': sendEmailToWorkers
                },
            }).done(function(response) {
                if (!response.success) {
					return false;
                } /* else {
                    $('#modalSchedule').modal('hide');
                    location.reload();
                } */
            });
            return true;
        }



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
					
					$("#divModalDetails").html('<div class="modal-header bg-light"><h5 class="modal-title"><?php print TRANS('WARNING'); ?></h5><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button></div><div class="modal-body"><p><?php print TRANS('FILL_UNIT_TAG'); ?></p></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal"><?php print TRANS('LINK_CLOSE'); ?></button></div>');
					$('#modalDefault').modal();
				} else {
					getAssetInfoByUnitAndTag($('#idUnidade').val(), $('#idEtiqueta').val());
				}
					

			}
			return false;
		}


		function getAssetInfoByUnitAndTag(unit, tag) {
			$.ajax({
				url: '../../invmon/geral/get_asset_id_by_unit_and_tag.php',
				method: 'POST',
				dataType: 'json',
				data: {
					asset_unit: unit,
					asset_tag: tag,
				},
			}).done(function(response) {
				if (response.success == true) {
					
					if (response.asset_id != '') {
						popup_alerta_wide('../../invmon/geral/asset_show.php?asset_id=' + response.asset_id);
					} else {
						$('#divResult').html(response.message);
					}
					
				} else {
					$('#divResult').html(response.message);
				}
			});
		}
		


		function checa_chamados() {

			if ($('#idUnidade').length > 0 && $('#idEtiqueta').length > 0) {
				if ($('#idUnidade').val() == '-1' || $('#idEtiqueta').val() == '') {
					$("#divModalDetails").html('<div class="modal-header bg-light"><h5 class="modal-title"><?php print TRANS('WARNING'); ?></h5><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button></div><div class="modal-body"><p><?php print TRANS('FILL_UNIT_TAG'); ?></p></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal"><?php print TRANS('LINK_CLOSE'); ?></button></div>');
					$('#modalDefault').modal();
				} else {
					
					
					let location = './get_tickets_by_unit_and_tag.php?unit=' + $('#idUnidade').val() + '&tag=' + $('#idEtiqueta').val();
					$("#iframeLoad").attr('src',location)
					$('#modalIframe').modal();
					
					
					// popup_alerta_wide('./get_tickets_by_unit_and_tag.php?unit=' + $('#idUnidade').val() + '&tag=' + $('#idEtiqueta').val());
					
				}
					
			}
			return false;
		}

		function checa_por_local() {
			var local = document.getElementById('idLocal');
			if (local != null) {
				if (local.value == -1) {

					$("#divModalDetails").html('<div class="modal-header bg-light"><h5 class="modal-title"><?php print TRANS('WARNING'); ?></h5><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button></div><div class="modal-body"><p><?php print TRANS('FILL_LOCATION'); ?></p></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal"><?php print TRANS('LINK_CLOSE'); ?></button></div>');
					$('#modalDefault').modal();
				} else {
					
					let location = '../../invmon/geral/get_assets_filtered_list.php?departamento=' + local.value;
					$("#iframeLoad").attr('src',location)
					$('#modalIframe').modal();
					
					// popup_alerta_wide('../../invmon/geral/get_assets_filtered_list.php?departamento=' + local.value);
				}
			}
			return false;
		}


		function controlCheckUserMailSend(){
			if ($('#contato_email').val() != '') {
				$('#mailUS').prop('disabled', false);
			} else {
				$('#mailUS').prop('disabled', true).prop('checked', false);
			}
		}
	</script>
	</body>

	</html>