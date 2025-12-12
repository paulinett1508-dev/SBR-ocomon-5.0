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

$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 3, 2);

$asset_id = (isset($_GET['asset_id']) ? (int)$_GET['asset_id'] : "");
if (empty($asset_id)) {
    echo message('warning', 'Ooops!', TRANS('MSG_ERR_NOT_EXECUTE'), '', '', 1);
    return;
}

$isAdmin = $_SESSION['s_nivel'] == 1;
$trashAction = '&nbsp;<span class="pointer text-danger" title="' . TRANS('DELETE_RECORD') . '" id="trashAction" data-cod="'. $asset_id .'"><i class="fas fa-trash"></i></span>';
$trashAction = ($isAdmin ? $trashAction : '');

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../../includes/css/estilos.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap/custom.css" /> <!-- custom bootstrap v4.5 -->
	<link rel="stylesheet" type="text/css" href="../../includes/css/switch_radio.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/fontawesome/css/all.min.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap-select/dist/css/bootstrap-select.min.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/css/my_bootstrap_select.css" />
    <title>OcoMon&nbsp;<?= VERSAO; ?></title>
    <style>
        .navbar-nav>.nav-link:hover {
            background-color: #3a4d56 !important;
        }

        .nav-pills>li>a.active {
            /* background-color: #6c757d !important; */
            background-color: #48606b !important;
        }

        .navbar-nav i {
            margin-right: 3px;
            font-size: 12px;
            width: 20px;
            height: 20px;
            line-height: 20px;
            text-align: center;
            -ms-flex-negative: 0;
            flex-shrink: 0;
            /* background-color: #3a4d56; */
            border-radius: 4px;
        }

        .oc-cursor {
            cursor: pointer;
        }
    </style>

</head>

<body class="bg-light">

    <?php

    // var_dump($_REQUEST);

    

    
    $query = $QRY["full_detail_ini"];
    $query .= " AND (c.comp_cod = '" . $asset_id . "')";
    
    /* Controle sobre as unidades que podem ser visualizadas pela área primária do operador */
    if (!empty($_SESSION['s_allowed_units'])) {
        $query .= " AND inst.inst_cod IN ({$_SESSION['s_allowed_units']}) ";
    }
    
    $query .= $QRY["full_detail_fim"];

    $resultado = $conn->query($query);
    $row = $resultado->fetch();

    if (!$row) {
        echo message ('warning','Ooops!', TRANS('NO_RECORDS_FOUND'), '', '', 1);
        return;
    }

    $asset_tag = $row['etiqueta'];
    $asset_unit = $row['cod_inst'];
    $client_name = (getUnits($conn, null, $asset_unit)['nickname'] ?? "");
    $department_info = getDepartments($conn, null, $row['tipo_local']);
    $department_unit = (!empty($department_info['unidade']) ? "&nbsp;(" . $department_info['unidade'] . ")" : "");
    $inconsistent_department = ($asset_unit != $department_info['loc_unit'] && !empty($department_info['loc_unit']));

    
    $model_id = $row['modelo_cod'];
    $modelDetails = getModelSpecs($conn,  $model_id);
    $subtext = '';
    foreach ($modelDetails as $detail) {
        $subtext .= '<span class="badge badge-info p-2 ml-2 mb-2">' . $detail['mt_name'] . ': ' . $detail['spec_value'] . '' . $detail['unit_abbrev'] . '</span>';
    }
    
    
    $specs = getAssetSpecs($conn, $asset_id, null, false);
    $specsDigital = getAssetSpecs($conn, $asset_id, null, true);

    $hasSpecsFields = (count($specs) > 0);
    $hasCustomFields = hasCustomFields($conn, $asset_id, 'assets_x_cfields');
    $parentInfo = getAssetParentId($conn, $asset_id);
    $hasParent = (!empty($parentInfo) ? true : false);

    $alertText = "<hr />" .TRANS('ASSET_UNIT') . ":&nbsp;" . $row['instituicao'] . "<br />";
    $alertText .= TRANS('DEPARTMENT_UNIT') . ":&nbsp;" . $department_info['unidade'];

    $inconsistent_alert = ($inconsistent_department ? message('danger', 'Ooops!', TRANS('INCONSISTENT_UNIT_X_DEPARTMENT') . $alertText, '', '', true) : "");


    /* Para manter a compatibilidade com versões antigas */
    $qryPieces = $QRY["componentexequip_ini"];
    $table = "equipxpieces";
    $sqlTest = "SELECT * FROM {$table}";
    try {
        $conn->query($sqlTest);
    }
    catch (Exception $e) {
        $table = "equipXpieces";
        $qryPieces = $QRY["componenteXequip_ini"];
    }



    /* Componentes avulsos */
    $qryPieces .= " and eqp.eqp_equip_inv in ('" . $asset_tag . "') and eqp.eqp_equip_inst=" . $asset_unit . "";
    $qryPieces .= $QRY["componenteXequip_fim"];
    $resultPieces = $conn->query($qryPieces);
    $pieces = $resultPieces->rowCount();


    /* Arquivos associados ao modelo*/
    $sqlFilesModel = "SELECT  i.* FROM imagens i  WHERE i.img_model ='" . $row['modelo_cod'] . "'  order by i.img_inv ";
    $resFilesModel = $conn->query($sqlFilesModel);
    $hasFilesFromModel = $resFilesModel->rowCount();


    /* Arquivos associados diretamente ao equipamento */
    $sqlFilesEquipment = "SELECT  i.* FROM imagens i  WHERE i.img_inst ='".$row['cod_inst']."' AND i.img_inv ='".$row['etiqueta']."'  ORDER BY i.img_inv ";
    $resFilesEquipment = $conn->query($sqlFilesEquipment);
    $hasFilesFromEquipment = $resFilesEquipment->rowCount();


    /* Arquivos nos chamados relacionados */
    $sqlFiles = "SELECT o.*, i.* FROM ocorrencias o , imagens i
				WHERE (i.img_oco = o.numero) AND (o.equipamento ='" . $asset_tag . "' AND o.instituicao ='" . $asset_unit . "')  ORDER BY o.numero ";
    $resultFiles = $conn->query($sqlFiles);
    $hasFilesFromTickets = $resultFiles->rowCount();

    /* Definições do grid */
    $colLabel = "col-sm-3 text-md-right font-weight-bold p-2";
    $colsDefault = "small text-break border-bottom rounded p-2 bg-white"; /* border-secondary */
    $colContent = $colsDefault . " col-sm-3 col-md-3";
    $colContentLine = $colsDefault . " col-sm-9";
    ?>

    <div class="container">
        <div id="idLoad" class="loading" style="display:none"></div>
    </div>

    <div id="divResult"></div>

    <!-- MENU DE OPÇÕES -->
    <nav class="navbar navbar-expand-md navbar-light  p-0 rounded" style="background-color: #48606b;">
        <!-- bg-secondary -->
        <!-- style="background-color: #dbdbdb; -->
        <div class="ml-2 font-weight-bold text-white"><?= $row['instituicao']; ?>:&nbsp;<i class="fas fa-tag"></i>&nbsp;<?= $row['etiqueta']; ?></div> <!-- navbar-brand --> 
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#idMenuOcorrencia" aria-controls="idMenuOcorrencia" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="idMenuOcorrencia">
            <div class="navbar-nav ml-2 mr-2">

                <?php
                    if ($_SESSION['s_invmon']) {
                        ?>
                        <a class="nav-link small text-white" href="../../invmon/geral/asset_edit.php?asset_id=<?= $asset_id ?>"><i class="fas fa-edit"></i><?= TRANS('BT_EDIT'); ?></a>

                        <a class="nav-link small text-white" onclick="getTickets('<?= $asset_unit;?>','<?= $asset_tag;?>')"><i class="fas fa-bars"></i><?= TRANS('TICKETS'); ?></a>

                        <a class="nav-link small text-white" onclick="popup_alerta('../../invmon/geral/equipment_softwares.php?popup=true&asset_tag=<?= $asset_tag;?>&asset_unit=<?= $asset_unit;?>')"><i class="fas fa-photo-video"></i><?= TRANS('MNL_SW'); ?></a>

                        <a class="nav-link small text-white" onclick="popup_alerta('../../invmon/geral/asset_specs_changes.php?asset_id=<?= $asset_id;?>')"><i class="fas fa-exchange-alt"></i><?= TRANS('HARDWARE_CHANGES'); ?></a>


                        <a class="nav-link small text-white" onclick="popup_alerta('../../invmon/geral/show_asset_location_history.php?popup=true&asset_id=<?= $asset_id;?>')"><i class="fas fa-door-closed"></i><?= TRANS('DEPARTMENTS'); ?></a>

                        <a class="nav-link small text-white" onclick="popup_alerta('../../invmon/geral/get_equipment_warranty_info.php?popup=true&asset_tag=<?= $asset_tag;?>&asset_unit=<?= $asset_unit;?>')"><i class="fas fa-business-time"></i><?= TRANS('LINK_GUARANT'); ?></a>

                        <a class="nav-link small text-white" onclick="popup_alerta('../../invmon/geral/documents.php?popup=true&model_id=<?= $row['modelo_cod'];?>')"><i class="fas fa-book"></i><?= TRANS('LINK_DOCUMENTS'); ?></a>

                        <a class="nav-link small text-white" href="../../invmon/geral/commitment_document.php?equipment_id=<?= $row['comp_cod'];?>"><i class="fas fa-file-signature"></i><?= TRANS('COMMITMENT_DOCUMENT_ABREV'); ?></a>

                        <a class="nav-link small text-white" href="../../invmon/geral/transit_document.php?equipment_id=<?= $row['comp_cod'];?>"><i class="fas fa-dolly-flatbed"></i><?= TRANS('TRANSIT_DOCUMENT_ABREV'); ?></a>

                        <?php
                            if ($isAdmin) {
                                ?>
                                    <a class="nav-link small text-white" href="#"><?= $trashAction; ?></a>
                                <?php
                            }
                        ?>
                        <?php
                    }
                ?>
                

            </div>
        </div>
    </nav>
    <!-- FINAL DO MENU DE OPÇÕES-->


    <div class="modal" tabindex="-1" style="z-index:9001!important" id="modalEquipment">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <!-- <div id="divModalEquipment" class="p-3"></div> -->
                <div id="divModalEquipment" style="position:relative">
                    <iframe id="ticketsInfo"  frameborder="0" style="position:absolute;top:0px;width:95%;height:100vh;"></iframe>
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



    <!-- Modal de exclusão de registro -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h5 class="modal-title text-white" id="deleteModalLabel"><i class="fas fa-exclamation-triangle text-white"></i>&nbsp;<?= TRANS('REMOVE'); ?></h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
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


    <!-- Modal para a vinculação de ativos filhos -->
    <div class="modal fade" id="modalLinkAsset" tabindex="-1" style="z-index:2001!important" role="dialog" aria-labelledby="myModalLinkAsset" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div id="divResultLinkAsset"></div>
                <div class="modal-header text-center bg-light">

                    <h4 class="modal-title w-100 font-weight-bold text-secondary"><i class="fas fa-link"></i>&nbsp;<?= TRANS('LINK_CHILD_ASSET_TO_THIS_ASSET'); ?></h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="row mx-2 mt-5">
                    <div id="child_info" class="form-group col-md-4 text-right"></div>
                    <div id="child_input" class="form-group col-md-8"></div>
                </div>

                <div id="buttons" class="modal-footer d-flex justify-content-end bg-light">
                    
                </div>
            </div>
        </div>
    </div>


        <!-- Modal para a desvinculação de ativos filhos -->
        <div class="modal fade" id="modalUnlinkAsset" tabindex="-1" style="z-index:2001!important" role="dialog" aria-labelledby="myModalUnlinkAsset" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div id="divResultUnlinkAsset"></div>
                <div class="modal-header text-center bg-light">

                    <h4 class="modal-title w-100 font-weight-bold text-secondary"><i class="fas fa-unlink"></i>&nbsp;<?= TRANS('UNLINK_CHILD_ASSET_FROM_THIS_ASSET'); ?></h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                
                <div class="row mx-2 mt-5">
                    <div id="info_child_to_unlink" class="form-group col-md-4 text-right"></div>
                    <div class="form-group col-md-8">
                        <?php
                            $departments = getDepartments($conn, null, null, $row['cod_inst']);
                        ?>
                        <select class="form-control bs-select" id="child_new_department" name="child_new_department">
                            <?php
                                foreach ($departments as $child_department) {
                                    ?>
                                        <option value="<?= $child_department['loc_id']; ?>"
                                        <?= ($child_department['loc_id'] == $row['tipo_local'] ? " selected" : ""); ?>
                                        ><?= $child_department['local']; ?></option>
                                    <?php
                                }
                            ?>
                        </select>
                        <small class="form-text text-muted"><?= TRANS('HELPER_SELECT_CHILD_NEW_DEPARTMENT'); ?></small>
                    </div>
                </div>
                <div class="row mx-2 mt-5">
                <label class="col-md-4 col-form-label text-md-right" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('REMOVE_SPEC_ALSO'); ?>"><?= firstLetterUp(TRANS('REMOVE_SPEC_ALSO')); ?></label>
					<div class="form-group col-md-8 ">
						<div class="switch-field">
							<?php
							$yesChecked = "";
							$noChecked = "checked";
							?>
							<input type="radio" id="remove_specification" name="remove_specification" value="yes" <?= $yesChecked; ?> />
							<label for="remove_specification"><?= TRANS('YES'); ?></label>
							<input type="radio" id="remove_specification_no" name="remove_specification" value="no" <?= $noChecked; ?> />
							<label for="remove_specification_no"><?= TRANS('NOT'); ?></label>
						</div>
					</div>
                </div>

                <div id="buttons_unlink" class="modal-footer d-flex justify-content-end bg-light">
                    <button id="confirm_unlink" class="btn btn-primary"><?= TRANS('BT_OK'); ?></button>
                    <button id="cancel_unlink" class="btn btn-secondary" data-dismiss="modal" aria-label="Close"><?= TRANS('BT_CANCEL'); ?></button>
                </div>
            </div>
        </div>
    </div>


    <div class="container-fluid bg-light">

        <?php

        /* MENSAGEM DE RETORNO PARA ABERTURA, EDIÇÃO E ENCERRAMENTO DO CHAMADO */
        if (isset($_SESSION['flash']) && !empty($_SESSION['flash'])) {
            echo $_SESSION['flash'];
            $_SESSION['flash'] = '';
        }

        echo $inconsistent_alert;
        ?>

        <div class="accordion"  id="accordionBasicInfo">

            <div class="card">
                <div class="card-header" id="cardBasicInfo">
                    <h2 class="mb-0">
                        <button class="btn btn-block text-left" type="button" data-toggle="collapse" data-target="#basicInfo" aria-expanded="true" aria-controls="basicInfo" onclick="this.blur();">
                            <h6 class="font-weight-bold"><i class="fas fa-info-circle text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('BASIC_INFORMATIONS')); ?></h6>
                        </button>
                    </h2>
                </div>
                
                <div id="basicInfo" class="collapse show" aria-labelledby="cardBasicInfo" data-parent="#accordionBasicInfo">
                    <div class="card-body">
                        <div class="row my-2">
                            <div class="<?= $colLabel; ?>"><?= TRANS('CLIENT'); ?></div>
                            <div class="<?= $colContent; ?>"><?= $client_name; ?></div>
                        </div>
                        <div class="w-100"></div>
                        <div class="row my-2">
                            <?php
                                $categorieInfo = getAssetCategoryInfo($conn, $row['comp_cod']);

                                $bgcolor = (!empty($categorieInfo['cat_bgcolor']) ? $categorieInfo['cat_bgcolor'] : 'red');
                                $textcolor = (!empty($categorieInfo['cat_textcolor']) ? $categorieInfo['cat_textcolor'] : 'white');
                                $categorieName = (!empty($categorieInfo['cat_name']) ? $categorieInfo['cat_name'] : TRANS('HAS_NOT_CATEGORY'));

                                $categorieBadge = '&nbsp;<span class="badge p-2" style="background-color:'.$bgcolor.'; color:'.$textcolor.'">' . $categorieName . '</span>';

                            ?>
                            <div class="<?= $colLabel; ?>"><?= TRANS('ASSET_TYPE'); ?></div>
                            <div class="<?= $colContent; ?>"><?= $row['equipamento'] . $categorieBadge ?></div>
                            <div class="<?= $colLabel; ?>"><?= TRANS('COL_MANUFACTURER'); ?></div>
                            <div class="<?= $colContent; ?>"><?= $row['fab_nome']; ?></div>
                        </div>

                        <div class="row my-2">
                            <div class="<?= $colLabel; ?>"><?= TRANS('COL_MODEL'); ?></div>
                            <div class="<?= $colContent; ?>"><?= $row['modelo'] . $subtext ?></div>
                            <div class="<?= $colLabel; ?>"><?= TRANS('SERIAL_NUMBER'); ?></div>
                            <div class="<?= $colContent; ?>"><?= $row['serial']; ?></div>
                        </div>

                        <div class="row my-2">
                            <div class="<?= $colLabel; ?>"><?= TRANS('DEPARTMENT'); ?></div>
                            <div class="<?= $colContent; ?>"><?= $row['local'] . $department_unit; ?></div>
                            <div class="<?= $colLabel; ?>"><?= TRANS('STATE'); ?></div>
                            <div class="<?= $colContent; ?>"><?= $row['situac_nome']; ?></div>
                        </div>

                        <?php
                            if ($row['nome'] != '') {
                                ?>
                                    <div class="row my-2">
                                        <div class="<?= $colLabel; ?>"><?= TRANS('NET_NAME'); ?></div>
                                        <div class="<?= $colContent; ?>"><?= $row['nome']; ?></div>
                                    </div>
                                <?php
                            }
                        
                            if ($hasParent) {

                                $badge_tag = '<span class="badge badge-info p-2 asset-tag" data-tag="'.$parentInfo['asset_id'].'" title="'.TRANS('ASSET_TAG').'">'.$parentInfo['comp_inv'].'</span>&nbsp<span class="unlink-child-tag text-danger" data-tag="'.$asset_id.'" title="'.TRANS('REMOVE_LINK').'"><i class="fas fa-unlink"></i></span>';
                                ?>
                                    <div class="row my-2">
                                        <div class="<?= $colLabel; ?>"><?= TRANS('LINKED_TO_PARENT_ASSET'); ?></div>
                                        <div class="<?= $colContent; ?>"><?= $badge_tag; ?></div>
                                    </div>
                                <?php
                            }
                        ?>

                        <div class="row my-2">
                            <div class="<?= $colLabel; ?>"><?= TRANS('ENTRY_TYPE_ADDITIONAL_INFO'); ?></div>
                            <div class="<?= $colContentLine; ?>"><?= $row['comentario']; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
            

        <div class="accordion"  id="accordionInventoryDetails">
        <?php
            if ($hasSpecsFields) {
                ?>
                    <div class="card">
                        <div class="card-header" id="cardSpecs">
                            <h2 class="mb-0">
                                <button class="btn btn-block text-left" type="button" data-toggle="collapse" data-target="#specification" aria-expanded="true" aria-controls="specification" onclick="this.blur();">
                                    <h6 class="font-weight-bold"><i class="fas fa-puzzle-piece text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('SUBTTL_DATA_COMPLE_CONFIG')); ?>&nbsp;<span class="badge badge-success"><?= TRANS('NEW'); ?></span></h6>
                                </button>
                            </h2>
                        </div>

                        <div id="specification" class="collapse " aria-labelledby="cardSpecs" data-parent="#accordionInventoryDetails">
                            <div class="card-body">
                        
                            <?php
                                if (!empty($specs)) {
                                    $i = 1;
                                    foreach ($specs as $spec) {
                                        if (isImpar($i)) {
                                            ?>
                                                <div class="row my-2">
                                            <?php
                                        }


                                        /* Exibirá a etiqueta caso o componente seja um ativo cadastrado */
                                        $tagged = ($spec['asset_spec_tagged_id'] ? '&nbsp;<span class="badge badge-info p-2 asset-tag" data-tag="'.$spec['asset_spec_tagged_id'].'" title="'.TRANS('ASSET_TAG').'"><i class="fas fa-tag"></i>&nbsp;'.$spec['comp_inv'].'</span>&nbsp;<span class="unlink-child-tag text-danger" data-tag="'.$spec['asset_spec_tagged_id'].'" title="'.TRANS('REMOVE_LINK').'"><i class="fas fa-unlink"></i></span>' : '');

                                        $spec_options = '';
                                        if (empty($tagged)) {
                                            $spec_options = '&nbsp;<span data-model="'.$spec['asset_spec_id'].'" class="fill-tag text-secondary" title="'.TRANS('FILL_TAG').'"><i class="fas fa-link"></i></span>';
                                        }

                                        $modelSpecs = getModelSpecs($conn, $spec['marc_cod']);
                                        $subtext = "";
                                        $specText = "";
                                        foreach ($modelSpecs as $mspec) {
                                            if (strlen((string)$subtext))
                                                $subtext .= " | ";
                                            $specText = $mspec['mt_name'] . ': ' . $mspec['spec_value'] . '' . $mspec['unit_abbrev'];
                                            $subtext .= '<span class="small">'.$specText.'</span>';
                                        }
                                        $subtext = (!empty($subtext) ? '&nbsp;(' . $subtext . ') ' : '');
                                        ?>
                                            <div class="<?= $colLabel; ?>"><?= $spec['tipo_nome'] ?></div>
                                            <div class="<?= $colContent; ?>"><?= $spec['marc_nome'] . $subtext . $tagged . $spec_options; ?></div>
                                        <?php
                                        if (isPar($i)) {
                                            ?>
                                                </div>
                                            <?php
                                        }
                                        $i++;
                                    }

                                    /* Fechamento final da div caso ainda não tenha sido fechada */
                                    if (!isImpar($i)) {
                                        ?>
                                            </div>
                                        <?php
                                    }
                                    ?>
                                        <div class="w-100"></div>
                                        <div class="row my-2 justify-content-end">
                                            <div class="form-group col-md-10 d-none d-md-block"></div>
                                            <div class="form-group col-12 col-md-2 justify-content-end" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_SAVE_SPECS_TO_MODEL'); ?>">
                                                <button id="save_model_specs" class="btn btn-primary btn-sm text-nowrap"><?= TRANS('SAVE_SPECS_TO_MODEL'); ?></button>
                                            </div>
                                        </div>
                                    <?php
                                }

                            ?>
                        </div> <!-- Final da div de especificação -->
                    </div> <!-- Final do Card -->
                <?php
            }
        ?>

        </div> <!-- Final do accordion -->   



        <div class="accordion"  id="accordionInventoryDetailsDigital">
        <?php
            if ($hasSpecsFields) {
                ?>
                    <div class="card">
                        <div class="card-header" id="cardSpecsDigital">
                            <h2 class="mb-0">
                                <button class="btn btn-block text-left" type="button" data-toggle="collapse" data-target="#specificationDigital" aria-expanded="true" aria-controls="specificationDigital" onclick="this.blur();">
                                    <h6 class="font-weight-bold"><i class="fas fa-photo-video text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('SUBTTL_DATA_COMPLE_CONFIG_DIGITAL')); ?>&nbsp;<span class="badge badge-success"><?= TRANS('NEW'); ?></span></h6>
                                </button>
                            </h2>
                        </div>

                        <div id="specificationDigital" class="collapse " aria-labelledby="cardSpecsDigital" data-parent="#accordionInventoryDetailsDigital">
                            <div class="card-body">
                        
                            <?php
                                if (!empty($specsDigital)) {
                                    $i = 1;
                                    foreach ($specsDigital as $spec) {
                                        if (isImpar($i)) {
                                            ?>
                                                <div class="row my-2">
                                            <?php
                                        }


                                        /* Exibirá a etiqueta caso o componente seja um ativo cadastrado */
                                        $tagged = ($spec['asset_spec_tagged_id'] ? '&nbsp;<span class="badge badge-info p-2 asset-tag" data-tag="'.$spec['asset_spec_tagged_id'].'" title="'.TRANS('ASSET_TAG').'"><i class="fas fa-tag"></i>&nbsp;'.$spec['comp_inv'].'</span>&nbsp;<span class="unlink-child-tag text-danger" data-tag="'.$spec['asset_spec_tagged_id'].'" title="'.TRANS('REMOVE_LINK').'"><i class="fas fa-unlink"></i></span>' : '');

                                        $spec_options = '';
                                        if (empty($tagged)) {
                                            $spec_options = '&nbsp;<span data-model="'.$spec['asset_spec_id'].'" class="fill-tag text-secondary" title="'.TRANS('FILL_TAG').'"><i class="fas fa-link"></i></span>';
                                        }

                                        $modelSpecs = getModelSpecs($conn, $spec['marc_cod']);
                                        $subtext = "";
                                        $specText = "";
                                        foreach ($modelSpecs as $mspec) {
                                            if (strlen((string)$subtext))
                                                $subtext .= " | ";
                                            $specText = $mspec['mt_name'] . ': ' . $mspec['spec_value'] . '' . $mspec['unit_abbrev'];
                                            $subtext .= '<span class="small">'.$specText.'</span>';
                                        }
                                        $subtext = (!empty($subtext) ? '&nbsp;(' . $subtext . ') ' : '');
                                        ?>
                                            <div class="<?= $colLabel; ?>"><?= $spec['tipo_nome'] ?></div>
                                            <div class="<?= $colContent; ?>"><?= $spec['marc_nome'] . $subtext . $tagged . $spec_options; ?></div>
                                        <?php
                                        if (isPar($i)) {
                                            ?>
                                                </div>
                                            <?php
                                        }
                                        $i++;
                                    }

                                    /* Fechamento final da div caso ainda não tenha sido fechada */
                                    if (!isImpar($i)) {
                                        ?>
                                            </div>
                                        <?php
                                    }
                                    ?>
                                        <div class="w-100"></div>
                                        <div class="row my-2 justify-content-end">
                                            <div class="form-group col-md-10 d-none d-md-block"></div>
                                            <div class="form-group col-12 col-md-2 justify-content-end" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="<?= TRANS('HELPER_SAVE_ALL_SPECS_TO_MODEL'); ?>">
                                                <button id="save_model_specs_digital" class="btn btn-primary btn-sm text-nowrap"><?= TRANS('SAVE_ALL_SPECS_TO_MODEL'); ?></button>
                                            </div>
                                        </div>
                                    <?php
                                }

                            ?>
                        </div> <!-- Final da div de especificação -->
                    </div> <!-- Final do Card -->
                <?php
            }
        ?>
        


        <div class="accordion"  id="accordionCustomFields">
            <?php
                if ($hasCustomFields) {
                    ?>
                        <div class="card">
                            <div class="card-header" id="cardCustomFields">
                                <h2 class="mb-0">
                                    <button class="btn btn-block text-left" type="button" data-toggle="collapse" data-target="#customFields" aria-expanded="true" aria-controls="customFields" onclick="this.blur();">
                                        <h6 class="font-weight-bold"><i class="fas fa-pencil-ruler text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('CUSTOM_FIELDS')); ?>&nbsp;<span class="badge badge-success"><?= TRANS('NEW'); ?></span></h6>
                                    </button>
                                </h2>
                            </div>

                            <div id="customFields" class="collapse " aria-labelledby="cardCustomFields" data-parent="#accordionCustomFields">
                                
                                <div class="card-body">
                                <?php
                                /* Exibição dos Campos personalizados */
                                    $custom_fields = getAssetCustomFields($conn, $asset_id);
                                    $number_of_collumns = 2;
                                    
                                    if (count($custom_fields) && !empty($custom_fields[0]['field_id'])) {
                                        ?>
                                        <div class="w-100"></div>
                                        <?php
                                        $col = 1;
                                        foreach ($custom_fields as $field) {
                                            $isTextArea = false;
                                            $value = "";
                                            $field_value = $field['field_value'] ?? '';
                                            
                                            if ($field['field_type'] == 'date' && !empty($field['field_value'])) {
                                                $field_value = dateScreen($field['field_value'],1);
                                            } elseif ($field['field_type'] == 'datetime' && !empty($field['field_value'])) {
                                                $field_value = dateScreen($field['field_value'], 0, 'd/m/Y H:i');
                                            } elseif ($field['field_type'] == 'checkbox' && !empty($field['field_value'])) {
                                                $field_value = '<span class="text-success"><i class="fas fa-check"></i></span>';
                                            } elseif ($field['field_type'] == 'textarea') {
                                                $isTextArea = true;
                                            }
                                            
                                            $col = ($col > $number_of_collumns ? 1 : $col);

                                            if ($col == 1) {
                                            ?>
                                                <div class="row my-2">
                                            <?php
                                            } elseif ($isTextArea) {
                                                ?>
                                                    </div>
                                                    <div class="w-100"></div>
                                                    <div class="row my-2">
                                                <?php
                                            }
                                            ?>
                                                <div class="<?= $colLabel; ?>"><?= $field['field_label']; ?></div>
                                                <div class="<?= ($field['field_type'] == 'textarea' ? $colContentLine : $colContent); ?>"><?= $field_value; ?></div>
                                            <?php
                                            if ($col == $number_of_collumns || $isTextArea) {
                                                $col = ($isTextArea ? 2 : $col);
                                            ?>
                                                </div>
                                                <div class="w-100"></div>
                                            <?php
                                            }
                                            $col ++;
                                        }

                                        if ($col == $number_of_collumns) {
                                        ?>
                                            </div>
                                            <div class="w-100"></div>
                                        <?php
                                        }
                                    }

                                ?>
                            </div> <!-- Final da div de especificação -->
                        </div> <!-- Final do Card -->
                    </div> <!-- Final do accordion -->
                    
                    <?php
                }


                /* Para checagem se o ativo possui configurações estáticas das versões anteriores' */
                $hasDeprecatedConfigs = false;
                $hasDeprecatedPrinterConfigs = false;
                
                $deprecatedConfigs = [
                    $row['fabricante_mb'],
                    $row['processador'],
                    $row['memoria'],
                    $row['fabricante_video'],
                    $row['fabricante_som'],
                    $row['rede_fabricante'],
                    $row['fabricante_modem'],
                    $row['fabricante_hd'],
                    $row['fabricante_cdrom'],
                    $row['fabricante_gravador'],
                    $row['fabricante_dvd']
                ];

                $deprecatedPrinterConfigs = [
                    $row['impressora'],
                    $row['polegada_nome'],
                    $row['resol_nome']
                ];

                
                foreach ($deprecatedConfigs as $key => $value) {
                    if ($value) {
                        $hasDeprecatedConfigs = true;
                        break;
                    }
                }

                foreach ($deprecatedPrinterConfigs as $key => $value) {
                    if ($value) {
                        $hasDeprecatedPrinterConfigs = true;
                        break;
                    }
                }
                
                
                if ($hasDeprecatedConfigs || $hasDeprecatedPrinterConfigs) {
                    ?>
                        <div class="accordion"  id="accordionDeprecated">
                    <?php
                }
                
                if ($hasDeprecatedConfigs) {
                ?>
                    <div class="card">
                    <div class="card-header" id="cardConfigurations">
                        <h2 class="mb-0">
                            <button class="btn btn-block text-left" type="button" data-toggle="collapse" data-target="#configuration" aria-expanded="true" aria-controls="configuration" onclick="this.blur();">
                                <h6 class="font-weight-bold"><i class="fas fa-hdd text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('SUBTTL_DATA_COMPLE_CONFIG')); ?>&nbsp;<span class="badge badge-warning"><?= TRANS('DEPRECATED'); ?></span></h6>
                            </button>
                        </h2>
                    </div>

                    <div id="configuration" class="collapse " aria-labelledby="cardConfigurations" data-parent="#accordionDeprecated">
                    <!-- <div class="card-body"> -->
                        <div class="row my-2">
                            <div class="<?= $colLabel; ?>"><?= TRANS('MOTHERBOARD'); ?></div>
                            <div class="<?= $colContent; ?>"><?= $row['fabricante_mb'] . " " . $row['mb']; ?></div>
                            <div class="<?= $colLabel; ?>"><?= TRANS('PROCESSOR'); ?></div>
                            <div class="<?= $colContent; ?>"><?= $row['processador'] . " " . $row['clock'] . " " . $row['proc_sufixo']; ?></div>
                        </div>

                        <div class="row my-2">
                            <div class="<?= $colLabel; ?>"><?= TRANS('CARD_MEMORY'); ?></div>
                            <div class="<?= $colContent; ?>"><?= $row['memoria'] . " " . $row['memo_sufixo']; ?></div>
                            <div class="<?= $colLabel; ?>"><?= TRANS('CARD_VIDEO'); ?></div>
                            <div class="<?= $colContent; ?>"><?= $row['fabricante_video'] . " " . $row['video']; ?></div>
                        </div>

                        <div class="row my-2">
                            <div class="<?= $colLabel; ?>"><?= TRANS('CARD_SOUND'); ?></div>
                            <div class="<?= $colContent; ?>"><?= $row['fabricante_som'] . " " . $row['som']; ?></div>
                            <div class="<?= $colLabel; ?>"><?= TRANS('CARD_NETWORK'); ?></div>
                            <div class="<?= $colContent; ?>"><?= $row['rede_fabricante'] . " " . $row['rede']; ?></div>
                        </div>

                        <div class="row my-2">
                            <div class="<?= $colLabel; ?>"><?= TRANS('CARD_MODEN'); ?></div>
                            <div class="<?= $colContent; ?>"><?= $row['fabricante_modem'] . " " . $row['modem']; ?></div>
                            <div class="<?= $colLabel; ?>"><?= TRANS('MNL_HD'); ?></div>
                            <div class="<?= $colContent; ?>"><?= $row['fabricante_hd'] . " " . $row['hd'] . " " . $row['hd_capacidade'] . " " . $row['hd_sufixo']; ?></div>
                        </div>

                        <div class="row my-2">
                            <div class="<?= $colLabel; ?>"><?= TRANS('FIELD_CDROM'); ?></div>
                            <div class="<?= $colContent; ?>"><?= $row['fabricante_cdrom'] . " " . $row['cdrom']; ?></div>
                            <div class="<?= $colLabel; ?>"><?= TRANS('FIELD_RECORD_CD'); ?></div>
                            <div class="<?= $colContent; ?>"><?= $row['fabricante_gravador'] . " " . $row['gravador']; ?></div>
                        </div>

                        <div class="row my-2">
                            <div class="<?= $colLabel; ?>"><?= TRANS('DVD'); ?></div>
                            <div class="<?= $colContent; ?>"><?= $row['fabricante_dvd'] . " " . $row['dvd']; ?></div>
                        </div>
                    </div>
                    <!-- </div> -->
                </div>
            
            <?php
            }
            
            if ($hasDeprecatedPrinterConfigs) {
                ?>
                    <div class="card">
                        <div class="card-header" id="cardConfigurationOthers">
                            <h2 class="mb-0">
                                <button class="btn btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#configurationOthers" aria-expanded="false" aria-controls="configurationOthers" onclick="this.blur();">
                                    <h6 class="font-weight-bold"><i class="fas fa-print text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('SUBTTL_DATA_COMP_OTHERS')); ?>&nbsp;<span class="badge badge-warning"><?= TRANS('DEPRECATED'); ?></span></h6>
                                </button>
                            </h2>
                        </div>
                        <div id="configurationOthers" class="collapse" aria-labelledby="cardConfigurationOthers" data-parent="#accordionDeprecated">
                            <div class="card-body">

                                <div class="row my-2">
                                    <div class="<?= $colLabel; ?>"><?= TRANS('FIELD_TYPE_PRINTER'); ?></div>
                                    <div class="<?= $colContent; ?>"><?= $row['impressora']; ?></div>
                                    <div class="<?= $colLabel; ?>"><?= TRANS('FIELD_MONITOR'); ?></div>
                                    <div class="<?= $colContent; ?>"><?= $row['polegada_nome']; ?></div>
                                </div>
                                <div class="row my-2">
                                    <div class="<?= $colLabel; ?>"><?= TRANS('FIELD_SCANNER'); ?></div>
                                    <div class="<?= $colContent; ?>"><?= $row['resol_nome']; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php
            }

            if ($hasDeprecatedConfigs || $hasDeprecatedPrinterConfigs) {
                ?>
                    </div> <!-- Final do accordion -->
                <?php
            }
            
            ?>


            <div class="accordion"  id="accordionExtraInfo">
            <div class="card">
                <div class="card-header" id="cardInvoice">
                    <h2 class="mb-0">
                        <button class="btn btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#configurationInvoice" aria-expanded="false" aria-controls="configurationInvoice" onclick="this.blur();">
                            <h6 class="font-weight-bold"><i class="fas fa-file-invoice-dollar text-secondary"></i>&nbsp;<?= firstLetterUp(TRANS('TXT_OBS_DATA_COMPLEM_2')); ?></h6>
                        </button>
                    </h2>
                </div>
                <div id="configurationInvoice" class="collapse" aria-labelledby="cardInvoice" data-parent="#accordionExtraInfo">
                    <div class="card-body">

                        <div class="row my-2">
                            <div class="<?= $colLabel; ?>"><?= TRANS('INVOICE_NUMBER'); ?></div>
                            <div class="<?= $colContent; ?>"><?= $row['nota']; ?></div>
                            <div class="<?= $colLabel; ?>"><?= TRANS('COST_CENTER'); ?></div>

                            <?php
                                $costcenter = (!empty($row['ccusto']) ? getCostCenters($conn, (int)$row['ccusto']) : '');
                                $showCostcenter = (!empty($costcenter) ? $costcenter['ccusto_name'] . "&nbsp" . $costcenter['ccusto_cod'] : '');

                            ?>
                            
                            <div class="<?= $colContent; ?>"><?= $showCostcenter; ?></div>
                        </div>

                        <div class="row my-2">
                            <div class="<?= $colLabel; ?>"><?= TRANS('FIELD_PRICE'); ?></div>
                            <div class="<?= $colContent; ?>"><?= priceScreen($row['valor']); ?></div>
                            <div class="<?= $colLabel; ?>"><?= TRANS('PURCHASE_DATE'); ?></div>
                            <div class="<?= $colContent; ?>"><?= dateScreen($row['data_compra'], 1); ?></div>
                        </div>

                        <div class="row my-2">
                            <div class="<?= $colLabel; ?>"><?= TRANS('COL_RECTORY'); ?></div>
                            <div class="<?= $colContent; ?>"><?= $row['reitoria']; ?></div>
                            <div class="<?= $colLabel; ?>"><?= TRANS('COL_SUBSCRIBE_DATE'); ?></div>
                            <div class="<?= $colContent; ?>"><?= dateScreen($row['data_cadastro'], 1); ?></div>
                        </div>
                        <div class="row my-2">
                            <div class="<?= $colLabel; ?>"><?= TRANS('COL_VENDOR'); ?></div>
                            <div class="<?= $colContent; ?>"><?= $row['fornecedor_nome']; ?></div>
                            <div class="<?= $colLabel; ?>"><?= TRANS('TECHNICAL_ASSISTANCE'); ?></div>
                            <div class="<?= $colContent; ?>"><?= $row['assistencia']; ?></div>
                        </div>

                    </div>
                </div>
            </div>
            </div><!-- Final do accordion -->
        </div>



        <?php
        /* ABAS */
        $classDisabledPieces = ($pieces > 0 ? '' : ' disabled');
        $ariaDisabledPieces = ($pieces > 0 ? '' : ' true');
        $classDisabledFilesFromEquipment = ($hasFilesFromEquipment > 0 ? '' : ' disabled');
        $ariaDisabledFilesFromEquipment = ($hasFilesFromEquipment > 0 ? '' : ' true');
        $classDisabledFilesFromModel = ($hasFilesFromModel > 0 ? '' : ' disabled');
        $ariaDisabledFilesFromModel = ($hasFilesFromModel > 0 ? '' : ' true');
        $classDisabledFilesFromTickets = ($hasFilesFromTickets > 0 ? '' : ' disabled');
        $ariaDisabledFilesFromTickets = ($hasFilesFromTickets > 0 ? '' : ' true');


        ?>
        <div class="row my-2">
            <div class="<?= $colLabel; ?>"></div>
            <div class="<?= $colContentLine; ?>">
                <ul class="nav nav-pills " id="pills-tab-inventory" role="tablist">
                    <li class="nav-item" role="pieces">
                        <a class="nav-link active <?= $classDisabledPieces; ?>" id="divPieces-tab" data-toggle="pill" href="#divPieces" role="tab" aria-controls="divPieces" aria-selected="true" aria-disabled="<?= $ariaDisabledPieces; ?>"><i class="fas fa-comment-alt"></i>&nbsp;<?= TRANS('DETACHED_COMPONENTS'); ?>&nbsp;<span class="badge badge-light p-1"><?= $pieces; ?></span></a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?= $classDisabledFilesFromEquipment; ?>" id="divFilesFromEquipment-tab" data-toggle="pill" href="#divFilesFromEquipment" role="tab" aria-controls="divFilesFromEquipment" aria-selected="true" aria-disabled="<?= $ariaDisabledFilesFromEquipment; ?>"><i class="fas fa-paperclip"></i>&nbsp;<?= TRANS('FILES_FROM_EQUIPMENT'); ?>&nbsp;<span class="badge badge-light pt-1"><?= $hasFilesFromEquipment; ?></span></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $classDisabledFilesFromModel; ?>" id="divFilesFromModel-tab" data-toggle="pill" href="#divFilesFromModel" role="tab" aria-controls="divFilesFromModel" aria-selected="true" aria-disabled="<?= $ariaDisabledFilesFromModel; ?>"><i class="fas fa-paperclip"></i>&nbsp;<?= TRANS('FILES_FROM_MODEL'); ?>&nbsp;<span class="badge badge-light pt-1"><?= $hasFilesFromModel; ?></span></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $classDisabledFilesFromTickets; ?>" id="divFilesFromTickets-tab" data-toggle="pill" href="#divFilesFromTickets" role="tab" aria-controls="divFilesFromTickets" aria-selected="true" aria-disabled="<?= $ariaDisabledFilesFromTickets; ?>"><i class="fas fa-paperclip"></i>&nbsp;<?= TRANS('FILES_FROM_RELATED_TICKETS'); ?>&nbsp;<span class="badge badge-light pt-1"><?= $hasFilesFromTickets; ?></span></a>
                    </li>

                </ul>
            </div>
        </div>
        <!-- FINAL DAS ABAS -->



        <!-- LISTAGEM DE COMPONENTES AVULSOS -->

        <div class="container tab-content" id="pills-tabInventoryContent">
            <?php
            if ($pieces) {
            ?>

                <div class="tab-pane fade show active" id="divPieces" role="tabpanel" aria-labelledby="divPieces-tab">

                    <div class="row my-2">

                        <div class="col-sm-12 border-bottom rounded p-0 bg-white " id="pieces">
                            <!-- collapse -->
                            <table class="table  table-hover table-striped rounded">
                                <!-- table-responsive -->
                                <thead class="text-white" style="background-color: #48606b;">
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col"><?= TRANS('SUBTTL_DATA_COMPLE_PIECES'); ?></th>
                                        <th scope="col"><?= TRANS('COMPONENT'); ?></th>
                                        <th scope="col"><?= TRANS('SERIAL_NUMBER'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $i = 1;
                                    foreach ($resultPieces->fetchAll() as $rowPiece) {

                                        $oldManufacturer = ($rowPiece['fabricante'] ? $rowPiece['fabricante'] . " " : "");
                                        $manufacturer = ($rowPiece['fab_nome'] ? $rowPiece['fab_nome'] . " " : "");
                                    ?>
                                        <tr>
                                            <td class="line"><a onclick="popupS('peripheral_show.php?&cod=<?= $rowPiece['estoq_cod']; ?>')"><?= $i; ?></a></td>
                                            <td><?= $rowPiece['item_nome']; ?></td>
                                            <td><?= $manufacturer . $oldManufacturer . $rowPiece['modelo'] . " " . $rowPiece['capacidade'] . " " . $rowPiece['sufixo']; ?></td>
                                            <td><?= $rowPiece['estoq_sn']; ?></td>
                                        </tr>
                                    <?php
                                        $i++;
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php
            }
            /* FINAL DA LISTAGEM DE COMPONENTES AVULSOS */


            /* TRECHO PARA EXIBIÇÃO DA LISTAGEM DE ARQUIVOS ANEXOS DO EQUIPAMENTO */
            if ($hasFilesFromEquipment) {
                ?>
                    <div class="tab-pane fade" id="divFilesFromEquipment" role="tabpanel" aria-labelledby="divFilesFromEquipment-tab">
                        <div class="row my-2">

                            <div class="col-sm-12 border-bottom rounded p-0 bg-white " id="files">
                                <!-- collapse -->
                                <table class="table  table-hover table-striped rounded">
                                    <!-- table-responsive -->
                                    <!-- <thead class="bg-secondary text-white"> -->
                                    <thead class=" text-white" style="background-color: #48606b;">
                                        <tr>
                                            <th scope="col">#</th>
                                            <th scope="col"><?= TRANS('COL_TYPE'); ?></th>
                                            <th scope="col"><?= TRANS('SIZE'); ?></th>
                                            <th scope="col"><?= TRANS('FILE'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $i = 1;
                                        foreach ($resFilesEquipment->fetchAll() as $rowFiles) {

                                            $size = round($rowFiles['img_size'] / 1024, 1);
                                            $rowFiles['img_tipo'] . "](" . $size . "k)";

                                            if (isImage($rowFiles["img_tipo"])) {
                                                $viewImage = "&nbsp;<a onClick=\"javascript:popupWH('../../includes/functions/showImg.php?" .
                                                    "file=" . $rowFiles['img_oco'] . "&cod=" . $rowFiles['img_cod'] . "'," . $rowFiles['img_largura'] . "," . $rowFiles['img_altura'] . ")\" " .
                                                    "title='view'><i class='fa fa-search'></i></a>";
                                            } else {
                                                $viewImage = "";
                                            }
                                        ?>
                                            <tr>
                                                <th scope="row"><?= $i; ?></th>
                                                <td><?= $rowFiles['img_tipo']; ?></td>
                                                <td><?= $size; ?>k</td>
                                                <td><a onClick="redirect('../../includes/functions/download.php?file=<?= $asset_tag; ?>&cod=<?= $rowFiles['img_cod']; ?>')" title="Download the file"><?= $rowFiles['img_nome']; ?></a><?= $viewImage; ?></i></td>
                                            </tr>
                                        <?php
                                            $i++;
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php
            }
            /* FINAL DO TRECHO DE LISTAGEM DE ARQUIVOS ANEXOS DO EQUIPAMENTO*/


             /* TRECHO PARA EXIBIÇÃO DA LISTAGEM DE ARQUIVOS ANEXOS DO EQUIPAMENTO */
             if ($hasFilesFromModel) {
                ?>
                    <div class="tab-pane fade" id="divFilesFromModel" role="tabpanel" aria-labelledby="divFilesFromModel-tab">
                        <div class="row my-2">

                            <div class="col-sm-12 border-bottom rounded p-0 bg-white " id="files">
                                <!-- collapse -->
                                <table class="table  table-hover table-striped rounded">
                                    <!-- table-responsive -->
                                    <!-- <thead class="bg-secondary text-white"> -->
                                    <thead class=" text-white" style="background-color: #48606b;">
                                        <tr>
                                            <th scope="col">#</th>
                                            <th scope="col"><?= TRANS('COL_TYPE'); ?></th>
                                            <th scope="col"><?= TRANS('SIZE'); ?></th>
                                            <th scope="col"><?= TRANS('FILE'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $i = 1;
                                        foreach ($resFilesModel->fetchAll() as $rowFiles) {

                                            $size = round($rowFiles['img_size'] / 1024, 1);
                                            $rowFiles['img_tipo'] . "](" . $size . "k)";

                                            if (isImage($rowFiles["img_tipo"])) {
                                                $viewImage = "&nbsp;<a onClick=\"javascript:popupWH('../../includes/functions/showImg.php?" .
                                                    "file=" . $rowFiles['img_cod'] . "&cod=" . $rowFiles['img_cod'] . "'," . $rowFiles['img_largura'] . "," . $rowFiles['img_altura'] . ")\" " .
                                                    "title='view'><i class='fa fa-search'></i></a>";
                                            } else {
                                                $viewImage = "";
                                            }
                                        ?>
                                            <tr>
                                                <th scope="row"><?= $i; ?></th>
                                                <td><?= $rowFiles['img_tipo']; ?></td>
                                                <td><?= $size; ?>k</td>
                                                <td><a onClick="redirect('../../includes/functions/download.php?file=<?= $asset_tag; ?>&cod=<?= $rowFiles['img_cod']; ?>')" title="Download the file"><?= $rowFiles['img_nome']; ?></a><?= $viewImage; ?></i></td>
                                            </tr>
                                        <?php
                                            $i++;
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php
            }
            /* FINAL DO TRECHO DE LISTAGEM DE ARQUIVOS ANEXOS DO EQUIPAMENTO*/


            /* TRECHO PARA EXIBIÇÃO DA LISTAGEM DE ARQUIVOS ANEXOS */
            if ($hasFilesFromTickets) {
            ?>
                <div class="tab-pane fade" id="divFilesFromTickets" role="tabpanel" aria-labelledby="divFilesFromTickets-tab">
                    <div class="row my-2">

                        <div class="col-sm-12 border-bottom rounded p-0 bg-white " id="files">
                            <!-- collapse -->
                            <table class="table  table-hover table-striped rounded">
                                <!-- table-responsive -->
                                <!-- <thead class="bg-secondary text-white"> -->
                                <thead class=" text-white" style="background-color: #48606b;">
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col"><?= TRANS('COL_TYPE'); ?></th>
                                        <th scope="col"><?= TRANS('SIZE'); ?></th>
                                        <th scope="col"><?= TRANS('FILE'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $i = 1;
                                    foreach ($resultFiles->fetchAll() as $rowFiles) {

                                        $size = round($rowFiles['img_size'] / 1024, 1);
                                        $rowFiles['img_tipo'] . "](" . $size . "k)";

                                        if (isImage($rowFiles["img_tipo"])) {
                                            $viewImage = "&nbsp;<a onClick=\"javascript:popupWH('../../includes/functions/showImg.php?" .
                                                "file=" . $rowFiles['img_cod'] . "&cod=" . $rowFiles['img_cod'] . "'," . $rowFiles['img_largura'] . "," . $rowFiles['img_altura'] . ")\" " .
                                                "title='view'><i class='fa fa-search'></i></a>";
                                        } else {
                                            $viewImage = "";
                                        }
                                    ?>
                                        <tr>
                                            <th scope="row"><?= $i; ?></th>
                                            <td><?= $rowFiles['img_tipo']; ?></td>
                                            <td><?= $size; ?>k</td>
                                            <td><a onClick="redirect('../../includes/functions/download.php?file=<?= $asset_tag; ?>&cod=<?= $rowFiles['img_cod']; ?>')" title="Download the file"><?= $rowFiles['img_nome']; ?></a><?= $viewImage; ?></i></td>
                                        </tr>
                                    <?php
                                        $i++;
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php
            }
            /* FINAL DO TRECHO DE LISTAGEM DE ARQUIVOS ANEXOS*/
            ?>

        </div> <!-- tab-content -->
        <input type="hidden" name="model_id" id="model_id" value="<?= $model_id; ?>">
        <input type="hidden" name="parent_id" id="parent_id" value="<?= $asset_id; ?>">
        <input type="hidden" name="child_model" id="child_model" value="">
        <input type="hidden" name="child_type_id" id="child_type_id" value="">
        <input type="hidden" name="child_manufacturer_id" id="child_manufacturer_id" value="">
        <input type="hidden" name="child_id" id="child_id" value="">
    </div>




    <script src="../../includes/javascript/funcoes-3.0.js"></script>
    <script src="../../includes/components/jquery/jquery.js"></script>
    <script src="../../includes/components/jquery/jquery.initialize.min.js"></script>
    <script src="../../includes/components/bootstrap/js/bootstrap.bundle.js"></script>
    <script src="../../includes/components/bootstrap-select/dist/js/bootstrap-select.min.js"></script>
    <script>
        $(function() {


            // $('body').on('hidden.bs.modal', '.modal', function () {
            //     $(this).removeData('bs.modal');
            // });

            $(function() {
                $('[data-toggle="popover"]').popover({
                    html: true
                });
            });

            $('.popover-dismiss').popover({
                trigger: 'focus'
            });

            $('.bs-select').selectpicker({
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
            
            $('.asset-tag').css('cursor', 'pointer').on('click', function() {
                let asset_id = $(this).attr('data-tag');
                let url = './asset_show';
                let params = 'asset_id=' + asset_id;
                loadInPopup(url, params);
            });

            $('.unlink-child-tag').css('cursor', 'pointer').on('click', function() {
                let asset_id = $(this).attr('data-tag');
                /* Primeiro abrir modal de confirmação solicitando o departamento (se for outro) para qual o ativo filho deve ir  */
                define_new_department(asset_id);
            });

            $('#confirm_unlink').on('click', function(e) {
                e.preventDefault();
                unlink_asset_child();
            })


            $('.fill-tag').css('cursor', 'pointer').on('click', function() {
                let child_asset_model = $(this).attr('data-model');
                checkLinkChildAsset(child_asset_model);
            });


            if ($('#trashAction').length > 0) {
                $('#trashAction').on('click', function(){
                    confirmDeleteModal($(this).attr('data-cod'));
                })
            }
            

            /* Adicionei o mutation observer em função dos elementos que são adicionados após o carregamento do DOM */
            var afterDom1 = $.initialize("#bt_confirm", function() {
                
                $('#bt_confirm').on('click', function(e) {
                    e.preventDefault();
                    linkChildAsset();
                });
            }, {
                target: document.getElementById('buttons')
            }); /* o target limita o scopo do observer */


            /* Adicionei o mutation observer em função dos elementos que são adicionados após o carregamento do DOM */
            var afterDom2 = $.initialize("#bt_register", function() {

                $('#bt_register').on('click', function(e) {
                    e.preventDefault();
                    
                    /**
                     * rodar funcao que recupera as informações necessárias para o cadastro:
                     * asset_type, asset_model, profile_id e um parametro extra que será 
                     * referente ao vínculo com o ativo pai: parent_id
                     */
                    register_asset();
                    return true;
                });
            }, {
                target: document.getElementById('buttons')
            }); /* o target limita o scopo do observer */



            $('#save_model_specs').on('click', function(e) {
                e.preventDefault();
                save_model_specs(0);
            });

            $('#save_model_specs_digital').on('click', function(e) {
                e.preventDefault();
                save_model_specs();
            });



        });


        function checkLinkChildAsset(modelId) {
            $.ajax({
                url: 'check_link_child_asset.php',
                method: 'POST',
                data: {
                    'child_model': modelId,
                    'parent_id': $('#parent_id').val()
                },
                dataType: 'json',

            }).done(function(data) {

                $('#child_info').html(data.asset_type + '&nbsp;' + data.manufacturer + '&nbsp;' + data.model);
                $('#child_type_id').val(data.cod_asset_type);
                $('#child_manufacturer_id').val(data.cod_manufacturer);
                // $('#child_model').val(data.child_model);

                let bt_register_success = '<button id="bt_register" class="btn btn-success"><?= TRANS('REGISTER_NEW'); ?></button>';
                let bt_register_primary = '<button id="bt_register" class="btn btn-primary"><?= TRANS('REGISTER_NEW'); ?></button>';
                let bt_confirm = '<button id="bt_confirm" class="btn btn-primary"><?= TRANS('LINK_TAG'); ?></button>';
                let bt_cancel = '<button id="cancelLink" class="btn btn-secondary" data-dismiss="modal" aria-label="Close"><?= TRANS('BT_CANCEL'); ?></button>';


                if (!data.free_to_link) {
                    $('#child_input').empty().html('<?= TRANS('MSG_ASSET_NOT_AVAILABLE_TO_LINK'); ?>').css('color','red');
                    
                    $('#buttons').empty().append(bt_register_primary);
                    $('#buttons').append(bt_cancel);
                    
                } else {
                    $('#child_input').empty().append('<input type="text" class="form-control " id="child_tag" name="child_tag" placeholder="<?= TRANS('HELPER_LINK_CHILD_ASSET_TO_THIS_ASSET'); ?>" value="" autocomplete="off" />');
                    
                    $('#buttons').empty().append(bt_confirm);
                    $('#buttons').append(bt_register_success);
                    $('#buttons').append(bt_cancel);
                }

                $('#child_model').val(modelId);
                $('#modalLinkAsset').modal();
            }).fail(function() {
                $('#divError').html('<p class="text-danger text-center"><?= TRANS('FETCH_ERROR'); ?></p>');
            });
            return false;
        }


        function linkChildAsset() {
            
            var loading = $(".loading");
            $(document).ajaxStart(function() {
                loading.show();
            });
            $(document).ajaxStop(function() {
                loading.hide();
            });
            $.ajax({
                url: 'link_child_asset_process.php',
                method: 'POST',
                data: {
                    'child_model': $('#child_model').val(),
                    'child_tag': $('#child_tag').val(),
                    'parent_id': $('#parent_id').val()
                },
                dataType: 'json',

            }).done(function(data) {

                if (!data.success) {
                    $('#divResultLinkAsset').html(data.message);

                    if (data.field_id != "") {
                        $('#' + data.field_id).focus().addClass('is-invalid');
                    }
                } else {

                    var url = '<?= $_SERVER['PHP_SELF'] ?>?<?= $_SERVER['QUERY_STRING'] ?>';
                    $(location).prop('href', url);
                    return false;
                }

            }).fail(function() {
                $('#divError').html('<p class="text-danger text-center"><?= TRANS('FETCH_ERROR'); ?></p>');
            });
            return false;
        }



        function save_model_specs(config_scope) {
            
            var loading = $(".loading");
            $(document).ajaxStart(function() {
                loading.show();
            });
            $(document).ajaxStop(function() {
                loading.hide();
            });
            $.ajax({
                url: 'save_model_specs_process.php',
                method: 'POST',
                data: {
                    'asset_id': $('#parent_id').val(),
                    'config_scope': config_scope
                },
                dataType: 'json',

            }).done(function(data) {

                $('#divResult').html(data.message);

            }).fail(function() {
                $('#divError').html('<p class="text-danger text-center"><?= TRANS('FETCH_ERROR'); ?></p>');
            });
            return false;
        }

        function register_asset() {
            
            var loading = $(".loading");
            $(document).ajaxStart(function() {
                loading.show();
            });
            $(document).ajaxStop(function() {
                loading.hide();
            });
            $.ajax({
                url: 'choose_asset_type_to_add_process.php',
                method: 'POST',
                data: {
                    'asset_type': $('#child_type_id').val(),
                    'asset_model': $('#child_model').val(),
                    'asset_manufacturer': $('#child_manufacturer_id').val(),
                    'parent_id': $('#parent_id').val()
                },
                dataType: 'json',

            }).done(function(data) {

                let params = 'asset_type=' + data.asset_type + '&asset_manufacturer=' + data.asset_manufacturer + '&asset_model=' + data.asset_model + '&profile_id=' + data.profile_id + '&parent_id=' + data.parent_id;
				let url = "./asset_add.php?" + params;
						
				$(location).prop('href', url);

            }).fail(function() {
                $('#divError').html('<p class="text-danger text-center"><?= TRANS('FETCH_ERROR'); ?></p>');
            });
            return false;
        }


        function unlink_asset_child() {
            
            var loading = $(".loading");
            $(document).ajaxStart(function() {
                loading.show();
            });
            $(document).ajaxStop(function() {
                loading.hide();
            });
            $.ajax({
                url: 'unlink_asset_child_process.php',
                method: 'POST',
                data: {
                    'parent_id': $('#parent_id').val(),
                    'child_id': $('#child_id').val(),
                    'child_new_department': $('#child_new_department').val(),
                    'remove_specification': ($('#remove_specification').is(':checked'))
                },
                dataType: 'json',

            }).done(function(data) {

                if (!data.success) {
                    $('#divResultUnlinkAsset').html(data.message);

                    if (data.field_id != "") {
                        $('#' + data.field_id).focus().addClass('is-invalid');
                    }
                } else {

                    var url = '<?= $_SERVER['PHP_SELF'] ?>?<?= $_SERVER['QUERY_STRING'] ?>';
                    $(location).prop('href', url);
                    return false;
                }

            }).fail(function() {
                $('#divError').html('<p class="text-danger text-center"><?= TRANS('FETCH_ERROR'); ?></p>');
            });
            return false;
        }



        /* Chama o modal para informar o novo departamento do ativo filho */
        function define_new_department(assetId) {
            $.ajax({
                url: 'child_new_department.php',
                method: 'POST',
                data: {
                    'child_id': assetId,
                },
                dataType: 'json',

            }).done(function(data) {

                $('#info_child_to_unlink').html(data.asset_type + '&nbsp;' + data.manufacturer + '&nbsp;' + data.model + '&nbsp;<span class="badge badge-info"><i class="fas fa-tag"></i>' + data.tag + '</span>');
                $('#child_id').val(assetId);
                $('#modalUnlinkAsset').modal();
            }).fail(function() {
                $('#divError').html('<p class="text-danger text-center"><?= TRANS('FETCH_ERROR'); ?></p>');
            });
            return false;
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
				url: './assets_process.php',
				method: 'POST',
				data: {
					cod: id,
					action: 'delete'
				},
				dataType: 'json',
			}).done(function(response) {
				
                if (!response.success) {
                    $('#deleteModal').modal('hide');
                    $('#divResult').html(response.message);
                } else {

                    /* Pegando as informações do iframe - caso seja */
                    let iframe = window.parent.document.getElementsByTagName("iframe")[0];

                    if (window.location !== window.parent.location && iframe.id != 'iframeMain') {
                        /* Iframe que não é o principal: a partir da janela parent, fechar o iframe e a modal e recarregar o parent location */
                        
                        if (typeof window.parent.closeIframeModal === 'function') {
                            window.parent.closeIframeModal();
                        }

                        if (typeof window.parent.agroup === 'function') {
                            window.parent.agroup();
                        }

                        return;
                    } else if (isPopup()) {

                        if (typeof window.opener.submitSearch === 'function') {
                            window.opener.submitSearch();
                        } else {
                            window.opener.location.reload();
                        }

                        window.close();
                        return;
                    } else {

                        /* Janela padrão dentro do iframe principal: iframeMain */
                        /* Volta para a tela anterior */
                        window.history.back();
                    }

                    return;
                }
                
				return false;
			});
			return false;
			// $('#deleteModal').modal('hide'); // now close modal
		}


        function loadPageInModal(page) {
            $("#divModalEquipment").load(page);
            $('#modalEquipment').modal();
        }

        function getTickets(unit, tag) {

            let location = '../../ocomon/geral/get_tickets_by_unit_and_tag.php?unit=' + unit + '&tag=' + tag;
		
            // $("#divModalEquipment").load('../../ocomon/geral/get_tickets_by_unit_and_tag.php?unit=' + unit + '&tag=' + tag);
            $("#ticketsInfo").attr('src',location)
            $('#modalEquipment').modal();
            return false;
        }

        function popup_alerta(pagina) { //Exibe uma janela popUP
            x = window.open(pagina, '_blank', 'dependent=yes,width=700,height=470,scrollbars=yes,statusbar=no,resizable=yes');
            x.moveTo(window.parent.screenX + 50, window.parent.screenY + 50);
            return false
        }

        function loadInPopup(pageBase, params) {
			let url = pageBase + '.php?' + params;
			x = window.open(url, '', 'dependent=yes,width=800,scrollbars=yes,statusbar=no,resizable=yes');
			x.moveTo(window.parent.screenX + 100, window.parent.screenY + 100);
		}
    </script>
</body>

</html>