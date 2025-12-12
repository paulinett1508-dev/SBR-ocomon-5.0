<?php session_start();
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
*/

if (!isset($_SESSION['s_logado']) || $_SESSION['s_logado'] == 0) {
    $_SESSION['session_expired'] = 1;
    echo "<script>top.window.location = '../../index.php'</script>";
    exit;
}

require_once __DIR__ . "/" . "../../includes/include_basics_only.php";
require_once __DIR__ . "/" . "../../includes/classes/ConnectPDO.php";
require_once __DIR__ . "/" . "../../includes/classes/worktime/Worktime.php";
include_once __DIR__ . "/" . "../../includes/functions/getWorktimeProfile.php";

$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 2, 2);

use includes\classes\ConnectPDO;
$conn = ConnectPDO::getInstance();

$uareas = $_SESSION['s_uareas'];
$post = (isset($_POST) ? $_POST : '');

$imgsPath = "../../includes/imgs/";
$iconFrozen = "<span class='text-oc-teal' title='" . TRANS('HNT_TIMER_STOPPED') . "'><i class='fas fa-pause fa-lg'></i></span>";
$iconOutOfWorktime = "<span class='text-oc-teal' title='" . TRANS('HNT_TIMER_OUT_OF_WORKTIME') . "'><i class='fas fa-pause fa-lg'></i></i></span>";
$iconTicketClosed = "<span class='text-oc-teal' title='" . TRANS('HNT_TICKET_CLOSED') . "'><i class='fas fa-check fa-lg'></i></i></span>";
$config = getConfig($conn);
$percLimit = $config['conf_sla_tolerance']; 

$calc_slas = (isset($post['calc_slas']) && $post['calc_slas'] == 'on' ? true : false);

$options = [
    'client' => [
        'label' => TRANS('CLIENT'),
        'table' => 'clients',
        'field_id' => 'id',
        'field_name' => 'nickname',
        'table_reference' => 'instituicao',
        'table_reference_alias' => 'un',
        'field_reference' => 'inst_client',
        'sql_alias' => 'cl.id',
        'alias' => 'cl',
        'value' => ''
    ],
    'category' => [
        'label' => TRANS('ASSET_CATEGORY'),
        'table' => 'assets_categories',
        'field_id' => 'id',
        'field_name' => 'cat_name',
        'table_reference' => 'tipo_equip',
        'table_reference_alias' => 't',
        'field_reference' => 'tipo_categoria',
        'sql_alias' => 'equip.tipo_categoria',
        'alias' => 'cat',
        'value' => ''
    ],
    'asset_type' => [
        'label' => TRANS('ASSET_TYPE'),
        'table' => 'tipo_equip',
        'field_id' => 'tipo_cod',
        'field_name' => 'tipo_nome',
        'table_reference' => 'equipamentos',
        'table_reference_alias' => 'e',
        'field_reference' => 'comp_tipo_equip',
        'sql_alias' => 'equip.tipo_cod',
        'alias' => 't',
        'value' => ''
    ],
    'unit' => [
        'label' => TRANS('COL_UNIT'),
        'table' => 'instituicao',
        'field_id' => 'inst_cod',
        'field_name' => 'inst_nome',
        'table_reference' => 'equipamentos',
        'table_reference_alias' => 'e',
        'field_reference' => 'comp_inst',
        'sql_alias' => 'inst.inst_cod',
        'alias' => 'un',
        'value' => ''
    ],
    'department' => [
        'label' => TRANS('DEPARTMENT'),
        'table' => 'localizacao',
        'field_id' => 'loc_id',
        'field_name' => 'local',
        'table_reference' => 'equipamentos',
        'table_reference_alias' => 'e',
        'field_reference' => 'comp_local',
        'sql_alias' => 'loc.loc_id',
        'alias' => 'l',
        'value' => ''
    ],
    'model' => [
        'label' => TRANS('COL_MODEL'),
        'table' => 'marcas_comp',
        'field_id' => 'marc_cod',
        'field_name' => 'marc_nome',
        'table_reference' => 'equipamentos',
        'table_reference_alias' => 'e',
        'field_reference' => 'comp_marca',
        'sql_alias' => 'model.marc_cod',
        'alias' => 'm',
        'value' => ''
    ],
    'state' => [
        'label' => TRANS('STATE'),
        'table' => 'situacao',
        'field_id' => 'situac_cod',
        'field_name' => 'situac_nome',
        'table_reference' => 'equipamentos',
        'table_reference_alias' => 'e',
        'field_reference' => 'comp_situac',
        'sql_alias' => 'sit.situac_cod',
        'alias' => 's',
        'value' => ''
    ],
    'manufacturer' => [
        'label' => TRANS('COL_MANUFACTURER'),
        'table' => 'fabricantes',
        'field_id' => 'fab_cod',
        'field_name' => 'fab_nome',
        'table_reference' => 'equipamentos',
        'table_reference_alias' => 'e',
        'field_reference' => 'comp_fab',
        'sql_alias' => 'fab.fab_cod',
        'alias' => 'f',
        'value' => ''
    ]
];


if (!empty($post)) {

    // var_dump($post); exit;

    /* Níveis possíveis de agrupamento */
    $groups = [
        'group_1' => '',
        'group_2' => '',
        'group_3' => '',
        'group_4' => '',
        'group_5' => '',
    ];

    foreach ($groups as $key => $group) {
        if (!empty($post[$key])) {
            $groups[$key] = $post[$key];
        } else {
            unset($groups[$key]);
        }
    }

    $table_id = $post['params'];
    $params = [];
    if (isset($post['params']) && !empty($post['params'])) {

        /* Tratamento quando o último parâmetro for de valor nulo */
        if (substr($post['params'], -1) == '-') {
            $post['params'] .= '0';
        }
        $params = explode('--', str_replace('---','-0--', $post['params']));
    }


    $tmp = [];
    /** Adicionando o valor para pesquisa no array principal $options */
    foreach ($params as $param) {
        $tmp = explode('-' , $param);
        $options[$tmp[0]]['value'] = (array_key_exists(1, $tmp) ? $tmp[1] : '0');
    }


    /* Monta os termos de pesquisa para a consulta SQL que exibirá a tabela de chamados */
    $sql_terms = "";
    foreach ($options as $key => $value) {
        if ($value['value'] !== '') {

            $sql_terms .= ($value['value'] == 0 ? "AND {$value['sql_alias']} IS NULL " : "AND {$value['sql_alias']}={$value['value']} ");
        }
    }

    /* Controle para apenas unidades visíveis pela área primária do usuário */
    $terms = "";
    if (!empty($_SESSION['s_allowed_units'])) {
        $terms = " AND inst.inst_cod IN ({$_SESSION['s_allowed_units']}) ";
    }

    $sql = $QRY["full_detail_ini"];
    $sql .= $sql_terms;
    $sql .= $terms;
    $sql .= $QRY["full_detail_fim"];
    // $sql .= " ORDER BY instituicao, etiqueta";


    /* Só enviará dados se for o último nível do agrupamento selecionado */
    if (count($params) == count($groups)) {


        // var_dump([
        //     'params' => $params,
        //     'sql_terms' => $sql_terms,
        //     'terms' => $terms,
        // ]); return;


        try {
            $res = $conn->query($sql);

        ?>
        <div id="tables">
            <!-- Listagem dos chamados -->
            <table id="table<?= $table_id; ?>" class="lista_agrupamento stripe hover order-column row-border" border="0" cellspacing="0" width="100%">
                <thead>
                    <tr class="header">
                        <th class="line"><?= TRANS('ASSET_TAG'); ?></th>
                        <th class="line"><?= TRANS('CLIENT'); ?></th>
                        <th class="line"><?= TRANS('COL_UNIT'); ?><br /><?= TRANS('DEPARTMENT'); ?></th>
                        <th class="line"><?= TRANS('COL_TYPE'); ?><br /><?= TRANS('COL_MANUFACTURER'); ?><br /><?= TRANS('COL_MODEL'); ?></th>
                        <th class="line direct-attributes"><?= TRANS('DIRECT_ATTRIBUTES'); ?></th>
                        <th class="line aggregated-attributes"><?= TRANS('AGGREGATED_ATTRIBUTES'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php

                    foreach ($res->fetchall() as $rowDetail) { /* registros */
                    
                        $categorieInfo = getAssetCategoryInfo($conn, $rowDetail['comp_cod']);
                        $bgcolor = (!empty($categorieInfo['cat_bgcolor']) ? $categorieInfo['cat_bgcolor'] : 'red');
                        $textcolor = (!empty($categorieInfo['cat_textcolor']) ? $categorieInfo['cat_textcolor'] : 'white');
                        $categorieName = (!empty($categorieInfo['cat_name']) ? $categorieInfo['cat_name'] : TRANS('HAS_NOT_CATEGORY'));
                        $categorieBadge = '&nbsp;<span class="badge p-2" style="background-color:'.$bgcolor.'; color:'.$textcolor.'">' . $categorieName . '</span>';
                    

                        /* Atributos diretos */
                        $modelDetails = getModelSpecs($conn, $rowDetail['modelo_cod']);
                        $directAttributes = '';
                        foreach ($modelDetails as $detail) {
                            $directAttributes .= '<li class="list-attributes">' . $detail['mt_name'] . ': ' . $detail['spec_value'] . '' . $detail['unit_abbrev'] . '</li>';
                        }

                        /* Retona array com as especificações agregadas do ativo */
                        $aggregated_specs = getAssetSpecs($conn, $rowDetail['comp_cod']);
                        /* Atributos agregados */
                        $aggregatedAttributes = '';
                        if (!empty($aggregated_specs)) {
                            foreach ($aggregated_specs as $spec) {
                                $aggregatedAttributes .= '<li class="list-attributes">' . $spec['tipo_nome'] . ': ' . $spec['marc_nome'] . '</li>';
                            }
                        }



                    
                    ?>
                        <tr>
                            <td class="line" data-sort="<?= (int)$rowDetail['etiqueta']; ?>"><b><a onClick=openAssetInfo(<?= $rowDetail['comp_cod']; ?>)><?= $rowDetail['etiqueta']; ?></a></b></td>
                            <td class="line"><?= $rowDetail['nickname']; ?></td>
                            <td class="line"><b><?= $rowDetail['instituicao']; ?></b><br /><?= $rowDetail['local']; ?></td>
                            <td class="line"><b><?= $rowDetail['equipamento'] . $categorieBadge ?></b><br /><?= $rowDetail['fab_nome']; ?><br /><?= $rowDetail['modelo']; ?></td>
                            <td class="line"><?= $directAttributes; ?></td>
                            <td class="line"><?= $aggregatedAttributes; ?></td>
                            
                            
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <?php


        }
        catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage();
        }
    }
}



