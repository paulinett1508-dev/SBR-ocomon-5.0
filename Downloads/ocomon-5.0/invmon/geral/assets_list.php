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

if (!isset($_SESSION['s_logado']) || $_SESSION['s_logado'] == 0) {
    $_SESSION['session_expired'] = 1;
    echo "<script>top.window.location = '../../index.php'</script>";
    exit;
}

require_once __DIR__ . "/" . "../../includes/include_geral_new.inc.php";
require_once __DIR__ . "/" . "../../includes/classes/ConnectPDO.php";

use includes\classes\ConnectPDO;

$conn = ConnectPDO::getInstance();

$imgsPath = "../../includes/imgs/";
$logo = LOGO_PATH . '/MAIN_LOGO.png';

$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 3, 2);


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

    <div class="container-fluid">

        <div class="modal" id="modal" tabindex="-1" style="z-index:9001!important" id="modalSubs">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div id="divDetails">
                    </div>
                </div>
            </div>
        </div>

        <div class="modal" tabindex="-1" id="modalDefault">
			<div class="modal-dialog modal-xl">
				<div class="modal-content">
					<div id="divModalDetails" class="p-3"></div>
				</div>
			</div>
		</div>

<?php

if ($_SESSION['s_nivel'] == 1) {
    $administrador = true;
} else {
    $administrador = false;
}

?>
    <body>
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

        <h4 class="my-4"><i class="fas fa-qrcode text-secondary"></i>&nbsp;<?= TRANS('MNL_VIS_EQUIP'); ?></h4>
        <button class="btn btn-sm btn-primary" id="idBtIncluir" name="new" onClick="location.href='choose_asset_type_to_add.php'"><?= TRANS("ACT_NEW"); ?></button><br />
<?php

if (isset($_SESSION['flash']) && !empty($_SESSION['flash'])) {
    echo $_SESSION['flash'];
    $_SESSION['flash'] = '';
}

$comp_inst = "";
if (isset($_GET['comp_inst'])) {
    $comp_inst = $_GET['comp_inst'];
} else
if (isset($_POST['comp_inst'])) {
    $comp_inst = $_POST['comp_inst'];
}

if (!isset($_POST['saida']) && !empty($comp_inst)) {
    $saida = "";
    if (is_array($comp_inst)) {
        for ($i = 0; $i < count($comp_inst); $i++) {
            $saida .= "$comp_inst[$i],";
        }
    } else {
        $saida = $comp_inst;
    }

    if (strlen((string)$saida) > 0) {
        $saida = substr($saida, 0, -1);
    }
    $comp_inst = $saida;
}

$comp_inv = "";
if (isset($_GET['comp_inv'])) {
    $comp_inv = $_GET['comp_inv'];
} else
if (isset($_POST['comp_inv'])) {
    $comp_inv = $_POST['comp_inv'];
}


$msgInst = "";
$checked = "";
$comp_inv_flag = false;
$comp_sn_flag = false;
$comp_marca_flag = false;
$comp_mb_flag = false;
$comp_proc_flag = false;
$comp_memo_flag = false;
$comp_video_flag = false;
$comp_som_flag = false;
$comp_rede_flag = false;
$comp_modem_flag = false;
$comp_modelohd_flag = false;
$comp_cdrom_flag = false;
$comp_dvd_flag = false;
$comp_grav_flag = false;
$comp_local_flag = false;
$comp_reitoria_flag = false;
$comp_nome_flag = false;
$comp_fornecedor_flag = false;
$comp_nf_flag = false;
$comp_inst_flag = false;
$comp_tipo_equip_flag = false;
$comp_fab_flag = false;
$comp_tipo_imp_flag = false;
$comp_polegada_flag = false;
$comp_resolucao_flag = false;
$comp_ccusto_flag = false;
$comp_situac_flag = false;
$comp_data_flag = false;
$comp_data_compra_flag = false;
$garantia_flag = false;
$soft_flag = false;
$comp_assist_flag = false;
$comp_memo_notnull = false;
$comp_memo_null = false;
$tmpData = array();

if (isset($_GET['encadeado'])) {
    $checked = "checked";
}

$query = $QRY["full_detail_ini"]; 

if (isset($_REQUEST['negado'])) {
    $negado = $_REQUEST['negado'];
} else {
    $negado = false;
}

if (empty($logico)) {
    $logico = " and ";
}

if (empty($sinal)) {
    $sinal = "=";
    $neg = "";
}

if (!empty($comp_inv)) {
    $comp_inv_flag = true;
    $query .= "$logico (c.comp_inv in ('" . $comp_inv . "')) ";
}

if (isset($_REQUEST['comp_sn'])) {
    if ($_REQUEST['comp_sn'] != '') {
        $comp_sn_flag = true;
        $comp_sn = strtoupper($_REQUEST['comp_sn']);
        $query .= "$logico (UPPER(c.comp_sn) = '" . $comp_sn . "') ";
    }
} else {
    $comp_sn = "";
}

if (isset($_REQUEST['comp_marca'])) {
    if (($_REQUEST['comp_marca'] != -1) && ($_REQUEST['comp_marca'] != '')) {
        $comp_marca_flag = true;
        $query .= " " . $logico . " (c.comp_marca = " . $_REQUEST['comp_marca'] . ") ";
        $sinal_marca = "=";
    }
}

if (isset($_REQUEST['comp_mb'])) {
    if (($_REQUEST['comp_mb'] != -1) && ($_REQUEST['comp_mb'] != '')) {
        $comp_mb_flag = true;
        $query .= " " . $logico . " (c.comp_mb = " . $_REQUEST['comp_mb'] . ") ";
    }
}

if (isset($_REQUEST['comp_proc'])) {
    if (($_REQUEST['comp_proc'] != -1) && ($_REQUEST['comp_proc'] != '')) {
        $comp_proc_flag = true;
        $query .= " " . $logico . " (c.comp_proc = " . $_REQUEST['comp_proc'] . ") ";
    }
}

if (isset($_REQUEST['comp_memo'])) {
    if (($_REQUEST['comp_memo'] != -1) && ($_REQUEST['comp_memo'] != '')) {
        if ($_REQUEST['comp_memo'] == -2) {
            $comp_memo_notnull = true;
            $query .= " " . $logico . " (c.comp_memo is not null)";
        } else
        if ($_REQUEST['comp_memo'] == -3) {
            $comp_memo_null = true;
            $query .= " " . $logico . " (c.comp_memo is null)";
        } else {
            $comp_memo_flag = true;
            $query .= " " . $logico . " (c.comp_memo = " . $_REQUEST['comp_memo'] . ") ";
        }
    }
}

if (isset($_REQUEST['comp_video'])) {
    if (($_REQUEST['comp_video'] != -1) && ($_REQUEST['comp_video'] != '')) {
        $comp_video_flag = true;
        $query .= " " . $logico . " (c.comp_video = " . $_REQUEST['comp_video'] . ") ";
    }
}

if (isset($_REQUEST['comp_som'])) {
    if (($_REQUEST['comp_som'] != -1) && ($_REQUEST['comp_som'] != '')) {
        $comp_som_flag = true;
        $query .= " " . $logico . " (c.comp_som = " . $_REQUEST['comp_som'] . ") ";
    }
}

if (isset($_REQUEST['comp_rede'])) {
    if (($_REQUEST['comp_rede'] != -1) && ($_REQUEST['comp_rede'] != '')) {
        $comp_rede_flag = true;
        $query .= " " . $logico . " (c.comp_rede = " . $_REQUEST['comp_rede'] . ") ";
    }
}

if (isset($_REQUEST['comp_modem'])) {
    if (($_REQUEST['comp_modem'] != -1) && ($_REQUEST['comp_modem'] != '')) {
        $comp_modem_flag = true;
        if ($_REQUEST['comp_modem'] == -2) {$query .= "and (c.comp_modem is null or c.comp_modem = 0)";} else
        if ($_REQUEST['comp_modem'] == -3) {$query .= "and (c.comp_modem is not null and c.comp_modem != 0)";} else {
            $query .= " " . $logico . " (c.comp_modem = " . $_REQUEST['comp_modem'] . ") ";
        }

    }
}

if (isset($_REQUEST['comp_modelohd'])) {
    if (($_REQUEST['comp_modelohd'] != -1) && ($_REQUEST['comp_modelohd'] != '')) {
        $comp_modelohd_flag = true;
        $query .= " " . $logico . " (c.comp_modelohd = " . $_REQUEST['comp_modelohd'] . ") ";
    }
}

if (isset($_REQUEST['comp_cdrom'])) {
    if (($_REQUEST['comp_cdrom'] != -1) && ($_REQUEST['comp_cdrom'] != '')) {
        $comp_cdrom_flag = true;
        if ($_REQUEST['comp_cdrom'] == -2) {$query .= "and (c.comp_cdrom is null or c.comp_cdrom = 0)";} else
        if ($_REQUEST['comp_cdrom'] == -3) {$query .= "and (c.comp_cdrom is not null and c.comp_cdrom != 0)";} else {
            $query .= " " . $logico . " (c.comp_cdrom = " . $_REQUEST['comp_cdrom'] . ") ";
        }

    }
}

if (isset($_REQUEST['comp_dvd'])) {
    if (($_REQUEST['comp_dvd'] != -1) && ($_REQUEST['comp_dvd'] != '')) {
        $comp_dvd_flag = true;
        $query .= "$logico (c.comp_dvd = " . $_REQUEST['comp_dvd'] . ") ";
    }
}

if (isset($_REQUEST['comp_grav'])) {
    if (($_REQUEST['comp_grav'] != -1) && ($_REQUEST['comp_grav'] != '')) {
        $comp_grav_flag = true;
        if ($_REQUEST['comp_grav'] == -2) {$query .= "and (c.comp_grav is null or c.comp_grav = 0)";} else
        if ($_REQUEST['comp_grav'] == -3) {$query .= "and (c.comp_grav is not null and c.comp_grav != 0)";} else {
            $query .= " " . $logico . " (c.comp_grav = " . $_REQUEST['comp_grav'] . ") ";
        }

    }
}

if (isset($_REQUEST['comp_local'])) {
    if (($_REQUEST['comp_local'] != -1) && ($_REQUEST['comp_local'] != '')) {
        $comp_local_flag = true;
        if ($negado == "comp_local") {
            $query .= "$logico (c.comp_local <> " . $_REQUEST['comp_local'] . ") ";
        } else {
            $query .= "$logico (c.comp_local " . $sinal . " " . $_REQUEST['comp_local'] . ") ";
        }

    }
}

if (isset($_REQUEST['comp_reitoria'])) { // OBS: não existe o campo comp_reitoria, apenas usei esse nome para padronizar!
    if (($_REQUEST['comp_reitoria'] != -1) && ($_REQUEST['comp_reitoria'] != '')) {
        $comp_reitoria_flag = true;
        $query .= "$logico (c.comp_reitoria = " . $_REQUEST['comp_reitoria'] . ") ";
    }
}

if (isset($_REQUEST['comp_nome'])) {
    if (!empty($_REQUEST['comp_nome'])) {
        $comp_nome_flag = true;
        $query .= "$logico (c.comp_nome = " . $_REQUEST['comp_nome'] . ") ";
    }
}

if (isset($_REQUEST['comp_fornecedor'])) {
    if (($_REQUEST['comp_fornecedor'] != -1) && ($_REQUEST['comp_fornecedor'] != '')) {
        $comp_fornecedor_flag = true;
        $query .= "$logico (c.comp_fornecedor = " . $_REQUEST['comp_fornecedor'] . ") ";
    }
}

if (isset($_REQUEST['comp_nf'])) {
    if (!empty($_REQUEST['comp_nf'])) {
        $comp_nf_flag = true;
        $query .= "$logico (c.comp_nf = " . $_REQUEST['comp_nf'] . ") ";
    }
}

if (($comp_inst != -1) and ($comp_inst != '')) {
    $comp_inst_flag = true;
    if ($negado == "comp_inst") {
        $query .= "$logico (c.comp_inst not in (" . $comp_inst . "))";
    } else {
        $query .= "$logico (c.comp_inst in ('" . $comp_inst . "'))";
    }

    if ($comp_inst == 1) {$logo = LOGO_PATH . '/MAIN_LOGO.png';} else
    if ($comp_inst == 2) {$logo = LOGO_PATH . '/MAIN_LOGO.png';}
}

if (isset($_REQUEST['comp_tipo_equip'])) {
    if (($_REQUEST['comp_tipo_equip'] != -1) && ($_REQUEST['comp_tipo_equip'] != '')) {
        $comp_tipo_equip_flag = true;
        if ($negado == "comp_tipo_equip") {
            $query .= "$logico (c.comp_tipo_equip <> " . $_REQUEST['comp_tipo_equip'] . ") ";
        } else {
            $query .= "$logico (c.comp_tipo_equip " . $sinal . " " . $_REQUEST['comp_tipo_equip'] . ") ";
        }

    }
}

if (isset($_REQUEST['comp_fab'])) {
    if (($_REQUEST['comp_fab'] != -1) && ($_REQUEST['comp_fab'] != '')) {
        $comp_fab_flag = true;
        $query .= "$logico (c.comp_fab = " . $_REQUEST['comp_fab'] . ") ";
    }
}

if (isset($_REQUEST['comp_tipo_imp'])) {
    if (($_REQUEST['comp_tipo_imp'] != -1) && ($_REQUEST['comp_tipo_imp'] != '')) {
        $comp_tipo_imp_flag = true;
        $query .= "$logico (c.comp_tipo_imp = " . $_REQUEST['comp_tipo_imp'] . ") ";
    }
}

if (isset($_REQUEST['comp_polegada'])) {
    if (($_REQUEST['comp_polegada'] != -1) && ($_REQUEST['comp_polegada'] != '')) {
        $comp_polegada_flag = true;
        $query .= "$logico (c.comp_polegada = " . $_REQUEST['comp_polegada'] . ") ";
    }
}

if (isset($_REQUEST['comp_resolucao'])) {
    if (($_REQUEST['comp_resolucao'] != -1) && ($_REQUEST['comp_resolucao'] != '')) {
        $comp_resolucao_flag = true;
        $query .= "$logico (c.comp_resolucao = " . $_REQUEST['comp_resolucao'] . ") ";
    }
}
if (isset($_REQUEST['comp_ccusto'])) {
    if (($_REQUEST['comp_ccusto'] != -1) && ($_REQUEST['comp_ccusto'] != '')) {
        $comp_ccusto_flag = true;
        $query .= "$logico (c.comp_ccusto = " . $_REQUEST['comp_ccusto'] . ") ";
    }
}

if (isset($_REQUEST['comp_situac'])) {
    if (($_REQUEST['comp_situac'] != -1) && ($_REQUEST['comp_situac'] != '')) {
        $comp_situac_flag = true;

        if ($negado == "comp_situac") {
            $query .= "$logico (c.comp_situac <> " . $_REQUEST['comp_situac'] . ") ";
        } else {
            $query .= "$logico (c.comp_situac " . $sinal . " " . $_REQUEST['comp_situac'] . ") ";
        }

    }
}

if (isset($_REQUEST['comp_data'])) { //CADASTRO
    if (($_REQUEST['comp_data'] != '')) {
        $comp_data_flag = true;
        // $comp_data = FDate($_REQUEST['comp_data']);
        $comp_data = dateDB($_REQUEST['comp_data']);

        if (strpos($_REQUEST['comp_data'], " ")) {
            $tmpData = explode(" ", $_REQUEST['comp_data']);
            $comp_data = $tmpData[0];
        }

        if (isset($_REQUEST['fromDateRegister'])) {
            $query .= "$logico (c.comp_data >='" . $comp_data . "')";
        } else {
            $query .= "$logico (c.comp_data like ('" . $comp_data . "%'))";
        }
    }
} 

if (isset($_REQUEST['comp_data_compra'])) { //CADASTRO
    if (($_REQUEST['comp_data_compra'] != '')) {
        $comp_data_compra_flag = true;
        // $comp_data_compra = FDate($_REQUEST['comp_data_compra']);
        $comp_data_compra = dateDB($_REQUEST['comp_data_compra']);

        if (strpos($_REQUEST['comp_data_compra'], " ")) {
            $tmpData = explode(" ", $_REQUEST['comp_data_compra']);
            $comp_data_compra = $tmpData[0];
        }

        $query .= "$logico (c.comp_data_compra like ('" . $comp_data_compra . "%'))";
    }
}

if (isset($_REQUEST['garantia'])) {
    if (($_REQUEST['garantia'] == 1) && ($_REQUEST['garantia'] == 2)) {
        $garantia_flag = true;
        if ($_REQUEST['garantia'] == 1) {
            $consulta = TRANS('UNDER_WARRANTY');
            $query .= "and (date_add(c.comp_data_compra, interval tmp.tempo_meses month) >=now())";
        } else {
            $consulta = TRANS('TXT_GUARANT_OUTSIDE');
            $query .= "and (date_add(c.comp_data_compra, interval tmp.tempo_meses month) <now() or comp_garant_meses is null)";
        }
    }
}

if (isset($_REQUEST['software'])) {
    if (($_REQUEST['software'] != -1) && ($_REQUEST['software'] != '')) {
        $soft_flag = true;
        $query .= "$logico (soft.soft_cod = " . $_REQUEST['software'] . ") ";
    }
}

if (isset($_REQUEST['comp_assist'])) {
    if (($_REQUEST['comp_assist'] != -1) && ($_REQUEST['comp_assist'] != '')) {
        $comp_assist_flag = true;
        if ($_REQUEST['comp_assist'] == -2) {
            $query .= "and (c.comp_assist is null)";
        } else {
            $query .= "and (c.comp_assist " . $sinal . " " . $_REQUEST['comp_assist'] . ")";
        }

    }
}


if (isset($_REQUEST['VENCIMENTO'])) {
    $query .= " AND date_add(date_format(comp_data_compra, '%Y-%m-%d') , INTERVAL tempo_meses MONTH) = '" . $_REQUEST['VENCIMENTO'] . "'";
}


/* Controle para apenas unidades visíveis pela área primária do usuário */
if (!empty($_SESSION['s_allowed_units'])) {
    $query .= " AND inst.inst_cod IN ({$_SESSION['s_allowed_units']}) ";
}


$query .= $QRY["full_detail_fim"];
$query .= " ORDER BY etiqueta";

$traduzOrdena = "";

$qtdTotal = $query;
$resultadoTotal = $conn->query($qtdTotal);
$linhasTotal = $resultadoTotal->rowCount(); //Aqui armazedo a quantidade total de registros

$resultado = $conn->query($query);
$resultadoAux = $conn->query($query);
$linhas = $resultado->rowCount();

$row = $resultadoAux->fetch();

//Titulo da consulta que retorna o critério de pesquisa.
//$texto ="com: ";
$texto = "";
$tam = (strlen((string)$texto));
$param = "&";
$tamParam = (strlen((string)$param));

if ($comp_tipo_equip_flag) {
    if (strlen((string)$texto) > $tam) {
        $texto .= ", ";
    }

    $texto .= "[<b>" . TRANS('FIELD_TYPE_EQUIP') . "</b> = " . $row['equipamento'] . "]"; //Escreve o critério de pesquisa
    if (strlen((string)$param) > $tamParam) {
        $param .= "&";
    }

    $param .= "comp_tipo_equip=" . $_REQUEST['comp_tipo_equip'] . ""; //Monta a lista de parâmetros para a consulta
}
;
if ($comp_tipo_imp_flag) {
    if (strlen((string)$texto) > $tam) {
        $texto .= ", ";
    }

    $texto .= "[<b>" . TRANS('FIELD_TYPE_PRINTER') . "</b> = " . $row['impressora'] . "]";
    if (strlen((string)$param) > $tamParam) {
        $param .= "&";
    }

    $param .= "comp_tipo_imp=" . $_REQUEST['comp_tipo_imp'] . "";
}
;
if ($comp_polegada_flag) {
    if (strlen((string)$texto) > $tam) {
        $texto .= ", ";
    }

    $texto .= "[<b>" . TRANS('FIELD_MONITOR') . "</b> = " . $row['polegada_nome'] . "]";
    if (strlen((string)$param) > $tamParam) {
        $param .= "&";
    }

    $param .= "comp_polegada=" . $_REQUEST['comp_polegada'] . "";
}
;

if ($comp_resolucao_flag) {
    if (strlen((string)$texto) > $tam) {
        $texto .= ", ";
    }

    $texto .= "[<b>" . TRANS('FIELD_SCANNER') . "</b> = " . $row['resol_nome'] . "]";
    if (strlen((string)$param) > $tamParam) {
        $param .= "&";
    }

    $param .= "comp_resolucao=" . $_REQUEST['comp_resolucao'] . "";
}
;

if ($comp_inv_flag) {
    if (strlen((string)$texto) > $tam) {
        $texto .= ", ";
    }

    $texto .= "[<b>" . TRANS('ASSET_TAG') . "</b> = " . $comp_inv . "]";
    if (strlen((string)$param) > $tamParam) {
        $param .= "&";
    }

    $param .= "comp_inv=" . $comp_inv . "";
}
;

if ($comp_sn_flag) {
    if (strlen((string)$texto) > $tam) {
        $texto .= ", ";
    }

    $texto .= "[<b>" . TRANS('SERIAL_NUMBER') . "</b> = " . $row['serial'] . "]";
    if (strlen((string)$param) > $tamParam) {
        $param .= "&";
    }

    $param .= "comp_sn=" . $_REQUEST['comp_sn'] . "";
}
;

if ($comp_fab_flag) {
    if (strlen((string)$texto) > $tam) {
        $texto .= ", ";
    }

    $texto .= "[<b>" . TRANS('COL_MANUFACTURER') . "</b> = " . $row['fab_nome'] . "]";
    if (strlen((string)$param) > $tamParam) {
        $param .= "&";
    }

    $param .= "comp_fab=" . $_REQUEST['comp_fab'] . "";
}
;

if ($comp_marca_flag) {
    if (strlen((string)$texto) > $tam) {
        $texto .= ", ";
    }

    $texto .= "[<b>" . TRANS('COL_MODEL') . "</b> = " . $row['modelo'] . "]"; //$sinal
    if (strlen((string)$param) > $tamParam) {
        $param .= "&";
    }

    $param .= "comp_marca=" . $_REQUEST['comp_marca'] . "";
}
;

if ($comp_mb_flag) {
    if (strlen((string)$texto) > $tam) {
        $texto .= ", ";
    }

    $texto .= "[<b>" . TRANS('MOTHERBOARD') . "</b> = " . $row['fabricante_mb'] . " " . $row['mb'] . "]";
    if (strlen((string)$param) > $tamParam) {
        $param .= "&";
    }

    $param .= "comp_mb=" . $_REQUEST['comp_mb'] . "";
}
;
if ($comp_proc_flag) {
    if (strlen((string)$texto) > $tam) {
        $texto .= ", ";
    }

    $texto .= "[<b>" . TRANS('PROCESSOR') . "</b> = " . $row['processador'] . " " . $row['clock'] . " " . $row['proc_sufixo'] . "]";
    if (strlen((string)$param) > $tamParam) {
        $param .= "&";
    }

    $param .= "comp_proc=" . $_REQUEST['comp_proc'] . "";
}
;
if ($comp_memo_flag) {
    if (strlen((string)$texto) > $tam) {
        $texto .= ", ";
    }

    $texto .= "[<b>" . TRANS('CARD_MEMORY') . "</b> = " . $row['memoria'] . "" . $row['memo_sufixo'] . "]";
    if (strlen((string)$param) > $tamParam) {
        $param .= "&";
    }

    $param .= "comp_memo=" . $_REQUEST['comp_memo'] . "";
}
;
if ($comp_memo_notnull) {
    if (strlen((string)$texto) > $tam) {
        $texto .= ", ";
    }

    $texto .= "[<b>" . TRANS('CARD_MEMORY') . "</b> = " . TRANS('FIELD_NOT_NULL') . "]";
    if (strlen((string)$param) > $tamParam) {
        $param .= "&";
    }

    $param .= "comp_memo=" . $_REQUEST['comp_memo'] . "";
}
;
if ($comp_memo_null) {
    if (strlen((string)$texto) > $tam) {
        $texto .= ", ";
    }

    $texto .= "[<b>" . TRANS('CARD_MEMORY') . "</b> = " . TRANS('FIELD_NULL') . "]";
    if (strlen((string)$param) > $tamParam) {
        $param .= "&";
    }

    $param .= "comp_memo=" . $_REQUEST['comp_memo'] . "";
}
;

if ($comp_video_flag) {
    if (strlen((string)$texto) > $tam) {
        $texto .= ", ";
    }

    $texto .= "[<b>" . TRANS('CARD_VIDEO') . "</b> = " . $row['fabricante_video'] . " " . $row['video'] . "]";
    if (strlen((string)$param) > $tamParam) {
        $param .= "&";
    }

    $param .= "comp_video=" . $_REQUEST['comp_video'] . "";
}
;
if ($comp_som_flag) {
    if (strlen((string)$texto) > $tam) {
        $texto .= ", ";
    }

    $texto .= "[<b>" . TRANS('CARD_SOUND') . "</b> = " . $row['fabricante_som'] . " " . $row['som'] . "]";
    if (strlen((string)$param) > $tamParam) {
        $param .= "&";
    }

    $param .= "comp_som=" . $_REQUEST['comp_som'] . "";
}
;
if ($comp_cdrom_flag) {
    if (strlen((string)$texto) > $tam) {
        $texto .= ", ";
    }

    if ($_REQUEST['comp_cdrom'] == -2) {$texto .= "[<b>" . TRANS('FIELD_CDROM') . "</b> = " . TRANS('HAS_NONE') . "]";} else
    if ($_REQUEST['comp_cdrom'] == -3) {$texto .= "[<b>" . TRANS('FIELD_CDROM') . "</b> = " . TRANS('HAS_ANY_MODEL') . "]";} else {
        $texto .= "[<b>" . TRANS('FIELD_CDROM') . "</b> = " . $row['fabricante_cdrom'] . " " . $row['cdrom'] . "]";
    }

    if (strlen((string)$param) > $tamParam) {
        $param .= "&";
    }

    $param .= "comp_cdrom=" . $_REQUEST['comp_cdrom'] . "";
}
;

if ($comp_grav_flag) {
    if (strlen((string)$texto) > $tam) {
        $texto .= ", ";
    }

    if ($_REQUEST['comp_grav'] == -2) {$texto .= "[<b>" . TRANS('FIELD_RECORD_CD') . "</b> = " . TRANS('HAS_NONE') . "]";} else
    if ($_REQUEST['comp_grav'] == -3) {$texto .= "[<b>" . TRANS('FIELD_RECORD_CD') . "</b> = " . TRANS('HAS_ANY_MODEL') . "]";} else {
        $texto .= "[<b>" . TRANS('FIELD_RECORD_CD') . "</b> = " . $row['fabricante_gravador'] . " " . $row['gravador'] . "]";
    }

    if (strlen((string)$param) > $tamParam) {
        $param .= "&";
    }

    $param .= "comp_grav=" . $_REQUEST['comp_grav'] . "";
}
;

if ($comp_dvd_flag) {
    if (strlen((string)$texto) > $tam) {
        $texto .= ", ";
    }

    if ($_REQUEST['comp_dvd'] == -2) {$texto .= "[<b>" . TRANS('DVD') . "</b> = " . TRANS('HAS_NONE') . "]";} else
    if ($_REQUEST['comp_dvd'] == -3) {$texto .= "[<b>" . TRANS('DVD') . "</b> = " . TRANS('HAS_ANY_MODEL') . "]";} else {
        $texto .= "[<b>" . TRANS('DVD') . "</b> = " . $row['fabricante_dvd'] . " " . $row['dvd'] . "]";
    }

    if (strlen((string)$param) > $tamParam) {
        $param .= "&";
    }

    $param .= "comp_dvd=" . $_REQUEST['comp_dvd'] . "";
}
;

if ($comp_modem_flag) {
    if (strlen((string)$texto) > $tam) {
        $texto .= ", ";
    }

    if ($_REQUEST['comp_modem'] == -2) {$texto .= "[<b>" . TRANS('CARD_MODEN') . "</b> = " . TRANS('HAS_NONE') . "]";} else
    if ($_REQUEST['comp_modem'] == -3) {$texto .= "[<b>" . TRANS('CARD_MODEN') . "</b> = " . TRANS('HAS_ANY_MODEL') . "]";} else {
        $texto .= "[<b>" . TRANS('CARD_MODEN') . "</b> = " . $row['fabricante_modem'] . " " . $row['modem'] . "]";
    }

    if (strlen((string)$param) > $tamParam) {
        $param .= "&";
    }

    $param .= "comp_modem=" . $_REQUEST['comp_modem'] . "";
}
;

if ($comp_modelohd_flag) {
    if (strlen((string)$texto) > $tam) {
        $texto .= ", ";
    }

    $texto .= "[<b>" . TRANS('MNL_HD') . "</b> = " . $row['fabricante_hd'] . " " . $row['hd_capacidade'] . "" . $row['hd_sufixo'] . "]";
    if (strlen((string)$param) > $tamParam) {
        $param .= "&";
    }

    $param .= "comp_modelohd=" . $_REQUEST['comp_modelohd'] . "";
}
;
if ($comp_rede_flag) {
    if (strlen((string)$texto) > $tam) {
        $texto .= ", ";
    }

    $texto .= "[<b>" . TRANS('CARD_NETWORK') . "</b> = " . $row['rede_fabricante'] . " " . $row['rede'] . "]";
    if (strlen((string)$param) > $tamParam) {
        $param .= "&";
    }

    $param .= "comp_rede=" . $_REQUEST['comp_rede'] . "";
}
;
if ($comp_local_flag) {
    if (strlen((string)$texto) > $tam) {
        $texto .= ", ";
    }

    $texto .= "[<b>" . TRANS('LOCALIZATION') . "</b> = " . $row['local'] . "]";
    if (strlen((string)$param) > $tamParam) {
        $param .= "&";
    }

    $param .= "comp_local=" . $_REQUEST['comp_local'] . "";
}
;
if ($comp_reitoria_flag) {
    if (strlen((string)$texto) > $tam) {
        $texto .= ", ";
    }

    $texto .= "[<b>" . TRANS('COL_RECTORY') . "</b> = " . $row['reitoria'] . "]";
    if (strlen((string)$param) > $tamParam) {
        $param .= "&";
    }

    $param .= "comp_reitoria=" . $_REQUEST['comp_reitoria'] . "";
}
;

if ($comp_fornecedor_flag) {
    if (strlen((string)$texto) > $tam) {
        $texto .= ", ";
    }

    $texto .= "[<b>" . TRANS('COL_VENDOR') . "</b> = " . $row['fornecedor_nome'] . "]";
    if (strlen((string)$param) > $tamParam) {
        $param .= "&";
    }

    $param .= "comp_fornecedor=" . $_REQUEST['comp_fornecedor'] . "";
}
;
if ($comp_nf_flag) {
    if (strlen((string)$texto) > $tam) {
        $texto .= ", ";
    }

    $texto .= "[<b>" . TRANS('INVOICE_NUMBER') . "</b> = " . $row['nota'] . "]";
    if (strlen((string)$param) > $tamParam) {
        $param .= "&";
    }

    $param .= "comp_nf=" . $_REQUEST['comp_nf'] . "";
}

if (($comp_ccusto_flag) || ((isset($_REQUEST['visualiza']) && $_REQUEST['visualiza'] == 'termo'))) {
    if (strlen((string)$texto) > $tam) {
        $texto .= ", ";
    }

    $CC = $row['ccusto'];
    if ($CC == "") {
        $CC = -1;
    }

    $query2 = "select * from " . DB_CCUSTO . "." . TB_CCUSTO . " where " . CCUSTO_ID . "= $CC "; //
    $resultado2 = $conn->query($query2);
    $rowCC = $resultado2->fetch();
    $centroCusto = $rowCC[CCUSTO_DESC];
    $custoNum = $rowCC[CCUSTO_COD];
    $texto .= "[<b>" . TRANS('COST_CENTER') . "</b> = " . $centroCusto . "]";

    if (strlen((string)$param) > $tamParam) {
        $param .= "&";
    }

    $param .= "comp_ccusto = " . $_REQUEST['comp_ccusto'] . "";
}

if ($comp_inst_flag) {
    if (strlen((string)$texto) > $tam) {
        $texto .= ", ";
    }

    $sqlA = "select inst_nome as inst from instituicao where inst_cod in (" . $comp_inst . ")";
    $resultadoA = $conn->query($sqlA);
    
    foreach ($resultadoA->fetchall() as $rowA) {
        $msgInst .= $rowA['inst'] . ', ';
    }
    $msgInst = substr($msgInst, 0, -2);
    //}

    $texto .= "[<b>" . TRANS('COL_UNIT') . "</b> = " . $msgInst . "]";
    if (strlen((string)$param) > $tamParam) {
        $param .= "&";
    }

    $p_temp = explode(",", $comp_inst);

    for ($i = 0; $i < count($p_temp); $i++) {
        $param .= "comp_inst%5B%5D=" . $p_temp[$i] . "&"; //%5B%5D  Caracteres especiais do HTML para entender arrays!!
    }
    $param = substr($param, 0, -1);
    //$param.= "comp_inst in ($comp_inst)";
}

if ($comp_situac_flag) {
    if (strlen((string)$texto) > $tam) {
        $texto .= ", ";
    }

    if (strlen((string)$param) > $tamParam) {
        $param .= "&";
    }

    $texto .= "[<b>" . TRANS('STATE') . "</b> = " . $row['situac_nome'] . "]";
    $param .= "comp_situac=" . $_REQUEST['comp_situac'] . "";
}
;
if ($comp_data_flag) {
    if (strlen((string)$texto) > $tam) {
        $texto .= ", ";
    }

    if (isset($_REQUEST['fromDateRegister'])) {
        $texto .= "[<b>" . TRANS("COL_SUBSCRIBE_DATE") . "&nbsp;" . TRANS('INV_FROM_DATE_REGISTER') . "</b> = " . $comp_data . "]";
    } else {
        $texto .= "[<b>" . TRANS("COL_SUBSCRIBE_DATE") . "</b> = " . $comp_data . "]";
    }
    if (strlen((string)$param) > $tamParam) {
        $param .= "&";
    }

    $param .= "comp_data=" . $_REQUEST['comp_data'] . "";
}
;
if ($comp_data_compra_flag) {
    if (strlen((string)$texto) > $tam) {
        $texto .= ", ";
    }

    $texto .= "[<b>" . TRANS('PURCHASE_DATE') . "</b> = " . $comp_data_compra . "]";
    if (strlen((string)$param) > $tamParam) {
        $param .= "&";
    }

    $param .= "comp_data_compra=" . $_REQUEST['comp_data_compra'] . "";
}
;

if ($garantia_flag) {
    if (strlen((string)$texto) > $tam) {
        $texto .= ", ";
    }

    $texto .= "[<b>" . TRANS('UNDER_WARRANTY') . "</b> = " . $consulta . "]";
    if (strlen((string)$param) > $tamParam) {
        $param .= "&";
    }

    $param .= "garantia=" . $_REQUEST['garantia'] . "";
}
;

if ($soft_flag) {
    if (strlen((string)$texto) > $tam) {
        $texto .= ", ";
    }

    $texto .= "[<b>" . TRANS('COL_SOFT') . "</b> = " . $row['software'] . " " . $row['versao'] . "]";
    if (strlen((string)$param) > $tamParam) {
        $param .= "&";
    }

    $param .= "software=" . $_REQUEST['software'] . "";
}
;

if ($comp_assist_flag) {
    if (strlen((string)$texto) > $tam) {
        $texto .= ", ";
    }

    if ($comp_assist == -2) {$texto .= "[<b>" . TRANS('ASSISTENCE') . "</b> = " . TRANS('MSG_NOT_DEFINED') . "]";} else {
        $texto .= "[<b>" . TRANS('ASSISTENCE') . "</b> = " . $row['assistencia'] . "]";
    }

    if (strlen((string)$param) > $tamParam) {
        $param .= "&";
    }

    $param .= "comp_assist=" . $_REQUEST['comp_assist'] . "";
}
;

if (isset($_REQUEST['VENCIMENTO'])) {
    if (strlen((string)$texto) > $tam) {
        $texto .= ", ";
    }

    $texto .= "[<b>" . TRANS('WARRANTY_EXPIRE') . "</b> = " . $_REQUEST['VENCIMENTO'] . "]";
    $param .= "VENCIMENTO=" . $_REQUEST['VENCIMENTO'] . "";
}
;

if (strlen((string)$texto) == $tam) {$texto .= "[<b>" . TRANS('COL_TYPE') . "</b> = " . TRANS('ALL') . "]";}
; //Se nenhum campo foi selecionado para a consulta então todos os equipamentos são listados!!

$lim = (strlen((string)$texto) - 7);
$texto2 = (substr((string)$texto, 6, $lim));

// geraLog(LOG_PATH . 'invmon.txt', date("d-m-Y H:i:s"), $_SESSION['s_usuario'], $_SERVER['PHP_SELF'], $texto);

if ($linhas == 0) {

    echo message ('warning', 'Ooops!', TRANS('MSG_THIS_CONS_NOT_RESULT'), '', '', 1);
    return;
} else {
    
    print "<table border='0' cellspacing='1' width='100%' class='center'>";
    print "<tr><TD with='70%' align='left'><i>" . TRANS('FIELD_CRITE_EXIBIT') . ": " . $texto . ".</i></td>
                <td width='30%' align='left'>
                <form name='checagem'  method='post' action=''>
                    <input  type='checkbox' class='radio' name='encadeia' id='idEncadeia' value='ok' " . $checked . " onChange=\"checar();\"><a title='" . TRANS('HNT_PIPE') . "!'>" . TRANS('FIELD_CHAIN_NAV') . "</a>";
                   
    print "	</form></td></tr><br>";

    print "</table>";

}
print "</TD>";

    ?>
        <!-- <h4 class="my-4"><i class="fas fa-laptop text-secondary"></i>&nbsp;<?= TRANS('MNL_VIS_EQUIP'); ?></h4>
        <button class="btn btn-sm btn-primary" id="idBtIncluir" name="new"><?= TRANS("ACT_NEW"); ?></button><br /><br /> -->
    <?php


    ?>
    <table id="table_equipments_list" class="stripe hover order-column row-border" border="0" cellspacing="0" width="100%">
        <thead>
            <tr class="header">
            <?php
                print "<td class='line' valign='middle'><b><a onClick=\"redirect('" . $_SERVER['PHP_SELF'] . "?" . $param . "')\" >" . TRANS('ASSET_TAG') . "</a></td>" .
                "<td class='line'><b><a onClick=\"redirect('" . $_SERVER['PHP_SELF'] . "?" . $param . "')\" >" . TRANS('COL_UNIT') . "</a></td>" .
                "<td class='line'><b><a onClick=\"redirect('" . $_SERVER['PHP_SELF'] . "?" . $param . "')\" >" . TRANS('COL_TYPE') . "</a></td>" .
                "<td class='line'><b><a onClick=\"redirect('" . $_SERVER['PHP_SELF'] . "?" . $param . "')\" >" . TRANS('COL_MODEL') . "</a></td>" .
                "<td class='line'><b><a onClick=\"redirect('" . $_SERVER['PHP_SELF'] . "?" . $param . "')\" >" . TRANS('LOCALIZATION') . "</a></td>" .
                "<td class='line'><b><a onClick=\"redirect('" . $_SERVER['PHP_SELF'] . "?" . $param . "')\" >" . TRANS('STATE') . "</a></td>";
                if ($_SESSION['s_invmon'] == 1) {
                    print "<td class='line editar'><b>" . TRANS('BT_ALTER') . "</td>";
                }
                if ($administrador) {
                    print "<td class='line remover'><b>" . TRANS('BT_REMOVE') . "</td>";
                }
            ?>
            </tr>
        </thead>
    <?php
    
    
    $i = 0;
    $j = 2;
    $cont = 0;
    $alerta = "";
    $classHighlight = "";
    foreach ($resultado->fetchall() as $row) {
        $cont++;

        if (($row['situac_destaque'] == '1')) { //Situação com destaque
            // $alerta = ' class = "destaque" ';
            $classHighlight = "destaque";
        } else {
            // $alerta = "";
            $classHighlight = "";
        }
        $j++;
        
        print "<tr id='linhax" . $j . "'>";
        
        // print "<td class='line " . $classHighlight . "'><a class = '" . $classHighlight . "' onClick=\"getEquipmentDetails(" . $row['cod_inst'] . ", '" . $row['etiqueta'] . "' );\"  title='" . TRANS('HNT_SHOW_DETAIL_EQUIP_CAD') . "'>" . $row['etiqueta'] . "</a></TD>";
        print "<td class='line " . $classHighlight . "'><a class = '" . $classHighlight . "' onClick=\"loadInPopup(" . $row['comp_cod'] . ");\"  title='" . TRANS('HNT_SHOW_DETAIL_EQUIP_CAD') . "'>" . $row['etiqueta'] . "</a></TD>";

        print "<td class='line " . $classHighlight . "'><a class = '" . $classHighlight . "' title='" . TRANS('HNT_FILTER_EQUIP_UNIT') . " " . $row['instituicao'] . ".' href=\"javascript:monta_link('?comp_inst%5B%5D=" . $row['cod_inst'] . "','" . $param . "','comp_inst')\">" . $row['instituicao'] . "</a></td>";
        print "<td class='line " . $classHighlight . "'><a class = '" . $classHighlight . "' title='" . TRANS('HNT_FILTER_EQUIP_TYPE') . " " . $row['equipamento'] . ".' href=\"javascript:monta_link('?comp_tipo_equip=" . $row['tipo'] . "','" . $param . "','comp_tipo_equip')\">" . $row['equipamento'] . "</a></td>";
        print "<td class='line " . $classHighlight . "'><a class = '" . $classHighlight . "' title='" . TRANS('HNT_FILTER_EQUIP_MODEL') . " " . $row['fab_nome'] . " " . $row['modelo'] . ".' href=\"javascript:monta_link('?comp_marca=" . $row['modelo_cod'] . "','" . $param . "','comp_marca')\">" . $row['fab_nome'] . " " . $row['modelo'] . "</a></td>";
        print "<td class='line " . $classHighlight . "'><a class = '" . $classHighlight . "' title='" . TRANS('HNT_FILTER_EQUIP_LOCAL_SECTOR') . " " . $row['local'] . ".' href=\"javascript:monta_link('?comp_local=" . $row['tipo_local'] . "','" . $param . "','comp_local')\">" . $row['local'] . "</a></td>";
        print "<td class='line " . $classHighlight . "'><a class = '" . $classHighlight . "' title='" . TRANS('HNT_FILTER_EQUIP_SITUAC') . " " . $row['situac_nome'] . ".' href=\"javascript:monta_link('?comp_situac=" . $row['situac_cod'] . "','" . $param . "','NEG_SITUACAO')\">" . $row['situac_nome'] . "</a></td>";
        if ($_SESSION['s_invmon'] == 1) {

            ?>
            <td class="line"><button type="button" class="btn btn-secondary btn-sm" onclick="redirect('asset_edit.php?asset_id=<?= $row['comp_cod']; ?>')"><?= TRANS('BT_EDIT'); ?></button></td>
            <?php
        }

        if ($administrador) {

            ?>

            <td class="line"><button type="button" class="btn btn-danger btn-sm" onclick="confirmDeleteModal('<?= $row['comp_cod']; ?>')"><?= TRANS('REMOVE'); ?></button></td>

            <?php
        }
        print "</tr>";

        print "<input type='hidden' name='etiquetaAjax" . $j . "' id='idEtiqueta" . $j . "' value='" . $row['etiqueta'] . "'>";
        print "<input type='hidden' name='unidadeAjax" . $j . "' id='idUnidade" . $j . "' value='" . $row['cod_inst'] . "'>";
        print "<input type='hidden' name='INDIV' id='idINDIV' value='INDIV'>";

        $i++;
    }
    print "</table>";

?>

    <script src="../../includes/javascript/funcoes-3.0.js"></script>
    <script src="../../includes/components/jquery/jquery.js"></script>
    <script type="text/javascript" charset="utf8" src="../../includes/components/datatables/datatables.js"></script>
    <script src="../../includes/components/bootstrap/js/bootstrap.bundle.min.js"></script>
	<script>

        $(function() {
                
            $('#table_equipments_list').DataTable({
				paging: true,
				deferRender: true,
				// order: [0, 'DESC'],
				columnDefs: [{
					searchable: false,
					orderable: false,
					targets: ['editar', 'remover']
				}],
				"language": {
					"url": "../../includes/components/datatables/datatables.pt-br.json"
				}
            });
            
            $('#idBtIncluir').on("click", function() {
				$('#idLoad').css('display', 'block');
				var url = 'choose_asset_type_to_add.php';
				$(location).prop('href', url);
			});

            $('#modalDefault').on('hidden.bs.modal', function() {
                $("#divModalDetails").html('');
            });

            
        });


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
				url: './equipment_process.php',
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



		function desabilita(v){
			if (document.checagem.negada !=null)
				document.checagem.negada.disabled = v;
		}

		function checar() {
            
            if ($('#idEncadeia').length > 0) {
                if ($('#idEncadeia').is(':checked')) {
                    return true;
                }
            }
            return false;
            
		}

        function loadInPopup(params) {
            let pageBase = '../../invmon/geral/asset_show';
			let url = pageBase + '.php?asset_id=' + params;
			x = window.open(url, '', 'dependent=yes,width=800,scrollbars=yes,statusbar=no,resizable=yes');
			x.moveTo(window.parent.screenX + 100, window.parent.screenY + 100);
		}


		function monta_link(clicado,parametro,negaCampo){

			var encadeado = "encadeado=1";
			if (checar() == false){
				parametro = "";
				encadeado = "";
				negaCampo ="";
			}
            // montaPopup(clicado+"&"+parametro+"&"+encadeado);
			//FIM DO BLOCO ALTERADO
			window.location.href=clicado+"&"+parametro+"&"+encadeado;
        }
        
        function getEquipmentDetails(asset_id) {
		
            // $("#divModalDetails").load('../../invmon/geral/equipment_show.php?unit=' + unit + '&tag=' + tag);
            $("#divModalDetails").load('../../invmon/geral/asset_show.php?asset_id=' + asset_id);
            $('#modalDefault').modal();
		
            return false;
        }

		</script>
    </div>
    </body>
</html>

