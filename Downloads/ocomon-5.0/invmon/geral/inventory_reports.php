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

function ordenaPorColunas($array, $col)
{
    ksort($array);
    $arrayIndexado = array();
    $i = 0;
    foreach ($array as $nome => $link) {
        $arrayIndexado[$i][$nome] = $link;
        $i++;
    }
    $tamanho = count($array); //quantidade de elementos do array
    $partlen = floor($tamanho / $col); //quantidade de elementos por coluna (ajustar o valor do resto da divisao)
    $resto = $tamanho % $col;
    $arrayLinha = array();
    $i = 0;
    $elemento = 1;
    // $coluna_size = 0;
    $collumnIndex = 0;
    $cont = 0;
    $colunaX = array();

    for ($j = 0; $j < $col; $j++) {

        $colunaX[$j] = $partlen;

        if ($resto > 0) {
            $colunaX[$j] = $partlen + 1;
            $resto--;
        }
    }

    // foreach ($arrayIndexado as $indice => $array2) {
    foreach ($arrayIndexado as $array2) {
        foreach ($array2 as $nome => $link) {
            $arrayLinha[$i][$nome] = $link;
            if ($elemento < $colunaX[$cont]) {
                $elemento++;
            } else {
                $elemento = 1;
                $collumnIndex++;
                $cont++;
                $i = $collumnIndex;
            }
            if ($elemento != 1) {
                $i += $col;
            }
        }
    }
    ksort($arrayLinha);
    return $arrayLinha;
}

$colunas = 3;
$listItem = array();
$itemIcon = array();
$itenBadge = array();

/* Links */
$listItem[TRANS('TOP_TEN_MODELS_RECORD')] = "show_top_ten_equipments_models.php";
$listItem[TRANS('TTL_QTD_EQUIP_CAD_FOR_LOCAL')] = "report_assets_by_department.php";
$listItem[TRANS('TTL_COMP_X_MEMORY')] = "show_equipments_by_memory.php";
$listItem[TRANS('TTL_COMP_X_PROCESSOR')] = "show_equipments_by_processor.php";
$listItem[TRANS('TTL_COMP_X_HD')] = "show_equipments_by_hdd.php";
$listItem[TRANS('TTL_EQUIP_X_SITUAC')] = "report_assets_by_state.php";
$listItem[TRANS('TTL_ESTAT_CAD_EQUIP')] = "report_assets_general.php";
$listItem[TRANS('TTL_EQUIP_X_RECTORY')] = "report_assets_by_rectory.php";
$listItem[TRANS('TTL_EQUIP_X_DOMAIN')] = "report_assets_by_net_domain.php";
$listItem[TRANS('TTL_EXPIRAT_GUARANTEE')] = "show_expiring_warranties.php";
$listItem[TRANS('ASSETS_BY_ATTRIBUTE')] = "report_assets_by_attribute.php";
// $listItem[TRANS('ASSETS_BY_AGGREGATE')] = "report_assets_by_aggregate.php";


/* Icons */
$itemIcon[TRANS('TOP_TEN_MODELS_RECORD')] = "<i class='fas fa-clone text-secondary'></i>";
$itemIcon[TRANS('TTL_QTD_EQUIP_CAD_FOR_LOCAL')] = "<i class='fas fa-door-closed text-secondary'></i>";
$itemIcon[TRANS('TTL_COMP_X_MEMORY')] = "<i class='fas fa-memory text-secondary'></i>";
$itemIcon[TRANS('TTL_COMP_X_PROCESSOR')] = "<i class='fas fa-microchip text-secondary'></i>";
$itemIcon[TRANS('TTL_COMP_X_HD')] = "<i class='fas fa-hdd text-secondary'></i>";
$itemIcon[TRANS('TTL_EQUIP_X_SITUAC')] = "<i class='fas fa-hashtag text-secondary'></i>";
$itemIcon[TRANS('TTL_ESTAT_CAD_EQUIP')] = "<i class='fas fa-qrcode text-secondary'></i>";
$itemIcon[TRANS('TTL_EQUIP_X_RECTORY')] = "<i class='fas fa-university text-secondary'></i>";
$itemIcon[TRANS('TTL_EQUIP_X_DOMAIN')] = "<i class='fas fa-network-wired text-secondary'></i>";
$itemIcon[TRANS('TTL_EXPIRAT_GUARANTEE')] = "<i class='fas fa-business-time text-secondary'></i>";
$itemIcon[TRANS('ASSETS_BY_ATTRIBUTE')] = "<i class='fas fa-ruler-combined text-secondary'></i>";
$itemIcon[TRANS('ASSETS_BY_AGGREGATE')] = "<i class='fas fa-puzzle-piece text-secondary'></i>";

/* Badges */
$itemBadge[TRANS('TOP_TEN_MODELS_RECORD')] = '';
$itemBadge[TRANS('TTL_QTD_EQUIP_CAD_FOR_LOCAL')] = '';
$itemBadge[TRANS('TTL_COMP_X_MEMORY')] = '&nbsp;<span class="badge badge-warning text-small">' . TRANS('DEPRECATED') . '</span>';
$itemBadge[TRANS('TTL_COMP_X_PROCESSOR')] = '&nbsp;<span class="badge badge-warning text-small">' . TRANS('DEPRECATED') . '</span>';
$itemBadge[TRANS('TTL_COMP_X_HD')] = '&nbsp;<span class="badge badge-warning text-small">' . TRANS('DEPRECATED') . '</span>';
$itemBadge[TRANS('TTL_EQUIP_X_SITUAC')] = '';
$itemBadge[TRANS('TTL_ESTAT_CAD_EQUIP')] = '';
$itemBadge[TRANS('TTL_EQUIP_X_RECTORY')] = '';
$itemBadge[TRANS('TTL_EQUIP_X_DOMAIN')] = '';
$itemBadge[TRANS('TTL_EXPIRAT_GUARANTEE')] = '';
$itemBadge[TRANS('ASSETS_BY_ATTRIBUTE')] = '';
$itemBadge[TRANS('ASSETS_BY_AGGREGATE')] = '';


?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../../includes/css/estilos.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap/custom.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/fontawesome/css/all.min.css" />
    <title>OcoMon&nbsp;<?= VERSAO; ?></title>
    <style>
        #loadSmartSearch {
            cursor: pointer;
        }
    </style>
</head>

<body>
    
    <div class="container">
        <div id="idLoad" class="loading" style="display:none"></div>
    </div>


    <div class="container-fluid">
        <h5 class="my-4"><i class="fas fa-chart-bar text-secondary"></i>&nbsp;<?= TRANS('GENERAL_REPORTS'); ?></h5>
        <h6 class="my-4"><?= TRANS('TLT_REPORTS_SOON'); ?>&nbsp;<span class="badge badge-secondary p-2" id="loadSmartSearch"><?= TRANS('TLT_HERE'); ?></span>&nbsp;<?= TRANS('TLT_REPORTS_SOON_2'); ?>.</h6>
        <div class="modal" id="modal" tabindex="-1" style="z-index:9001!important">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div id="divDetails">
                    </div>
                </div>
            </div>
        </div>

        <table class="table">
            <tr class="header">
                <td class="line" colspan="<?= $colunas; ?>"><?= TRANS('GENERAL_REPORTS'); ?></td>
            </tr>
            <?php
            $ctdTD = 1;
            $indCol = 0;
            ?>
            <tr>
            <?php
            $checked = "";
            $i = 0;
            $j = 0;

            $REP2 = array();
            $REP2 = ordenaPorColunas($listItem, $colunas);

            //EXIBICAO COM ORDENACAO DINAMICA
            foreach ($REP2 as $indice) {
                foreach ($indice as $key => $value) {

                    $class = (isImpar($j) ? 'lin_par' : 'lin_impar');

                    if ($ctdTD == 1) {
                        ?>
                        <tr class="<?= $class; ?>">
                        <?php
                        $j++;
                    }
                    // print "<td class='line' colspan = '" . (($i + 1) == count($REP2) && ((count($REP2) - $j) != 0) ? count($REP2) - $j : '') . "'><a href='" . $value . "'>" . $key . "</a></TD>";
                    print "<td class='line' colspan = '" . (($i + 1) == count($REP2) && ((count($REP2) - $j) != 0) ? count($REP2) - $j : '') . "'><a href='" . $value . "'>" . $itemIcon[$key] . "&nbsp;" . $key . "". $itemBadge[$key] ."</a></TD>";

                    if ($ctdTD == $colunas) {
                        ?>
                        </tr>
                        <?php
                        $ctdTD = 1;
                    } else {
                        $ctdTD++;
                    }
                    $i++;
                }
            }
            ?>
            </tr>
        </table>
    </div>
    <script src="../../includes/javascript/funcoes-3.0.js"></script>
    <script src="../../includes/components/jquery/jquery.js"></script>
    <script src="../../includes/components/bootstrap/js/bootstrap.min.js"></script>
    <script type='text/javascript'>
        
        $(function() {

            $('#loadSmartSearch').on('click', function(){
                redirect('smart_search_inventory_to_report.php');
            });

        });
        
        function redirect(url) {
            window.location.href = url;
        }

        function checa_permissao(URL) {
            var admin = '<?php print $_SESSION['s_nivel']; ?>';
            var area_admin = '<?php print $_SESSION['s_area_admin'] ?>';
            if ((admin != 1) && (area_admin != 1)) {
                window.alert('Acesso Restrito!');
            } else
                redirect(URL);

            return false;
        }
    </script>
</body>
</html>