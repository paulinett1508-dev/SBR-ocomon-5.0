<?php session_start();
/*      Copyright 2023 Flávio Ribeiro

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

use includes\classes\ConnectPDO;

$conn = ConnectPDO::getInstance();
$exception = "";
$criteria = [];
$criteriaText = "";
$data = [];
$data['success'] = true;
$data['message'] = "";
$data['total'] = 0;

$post = $_POST;

// var_dump($post); exit;


$client = (isset($post['client']) ? noHtml($post['client']) : "");

$asset_units = (isset($post['asset_unit']) && !empty(array_filter($post['asset_unit'], function($v) { return !empty($v); })) ? array_map('noHtml', $post['asset_unit']) : []);

$asset_categories = (isset($post['asset_category']) && !empty(array_filter($post['asset_category'], function($v) { return !empty($v); })) ? array_map('noHtml', $post['asset_category']) : []);

$asset_types = (isset($post['asset_type']) && !empty(array_filter($post['asset_type'], function($v) { return !empty($v); })) ? array_map('noHtml', $post['asset_type']) : []);


$termsTotal = "";
$terms = "";
$chart_title_sufix = '_chart_title';
$chart_1_prefix = 'chart_01';
$chart_2_prefix = 'chart_02';
$chart_3_prefix = 'chart_03';
$chart_4_prefix = 'chart_04';
$chart_5_prefix = 'chart_05';





/* Unidades com base no filtro de clientes */
if (!empty($client)) {

    $criteria['client'] = $client;
    
    if (empty($asset_units)) {

        $criteria['units'] = "";
        $units_filter = getUnits($conn, null, null, $client);

        $units_filter = (!empty($units_filter) ? $units_filter : [['inst_cod' => 0]]);

    } else {
        foreach ($asset_units as $unit) {
            $units_filter[] = getUnits($conn, null, $unit);
        }
        $criteria['units'] = array_column($units_filter, 'inst_nome');
    }
    

} elseif (!empty($asset_units)) {

    $criteria['client'] = "";
    foreach ($asset_units as $unit) {
        $units_filter[] = getUnits($conn, null, $unit);
    }
} else {
    $criteria['client'] = "";
    $criteria['units'] = "";
    /* Todas as unidades existentes - Não preciso do filtro */
    $units_filter = [];

}


/* Controle no limite de visualização para a área primária do usuário */
if (!empty($_SESSION['s_allowed_units'])) {
    $terms .= " AND e.comp_inst IN ({$_SESSION['s_allowed_units']}) ";

    $termsTotal .= (!empty($termsTotal) ? " AND " : " WHERE ");
    $termsTotal .= " e.comp_inst IN ({$_SESSION['s_allowed_units']}) ";
}


if (!empty($units_filter)) {
    $termsTotal .= (!empty($termsTotal) ? " AND " : " WHERE ");
    $units_filter = implode(",", array_column($units_filter, 'inst_cod'));
    $termsTotal .= " e.comp_inst IN ({$units_filter})";
    $terms .= " AND e.comp_inst IN ({$units_filter})";
}



/* Tipo de ativos com base no filtro de categoria */
if (!empty($asset_categories)) {
    
    $criteria['categories'] = $asset_categories;
    
    if (empty($asset_types)) {
        $types_filter = getAssetsTypes($conn, null, null, $asset_categories );
        $criteria['asset_type'] = "";
    } else {
        foreach ($asset_types as $asset_type) {
            $types_filter[] = getAssetsTypes($conn, $asset_type);
        }
        $criteria['asset_type'] = array_column($types_filter, 'tipo_nome');
    }
} elseif (!empty($asset_types)) {

    $criteria['categories'] = "";
    foreach ($asset_types as $asset_type) {
        $types_filter[] = getAssetsTypes($conn, $asset_type);
    }
    $criteria['asset_type'] = $types_filter;
} else {
    
    $criteria['categories'] = "";
    $criteria['asset_type'] = "";
    /* Todas os tipos existentes - Não preciso do filtro */
    $types_filter = [];
}


if (!empty($types_filter)) {
    $termsTotal .= (!empty($termsTotal) ? " AND " : " WHERE ");
    $types_filter = implode(",", array_column($types_filter, 'tipo_cod'));
    $termsTotal .= " e.comp_tipo_equip IN ({$types_filter})";
    $terms .= " AND e.comp_tipo_equip IN ({$types_filter})";
}



/* Montagem do texto dos critérios */
$criteriaClient = (empty($client) ? TRANS('CLIENT') . ':&nbsp;' . TRANS('ALL') : TRANS('CLIENT') . ':&nbsp;' . getClients($conn, $client)['nickname']);
$units_names = "";
$categories_names = "";
$types_names = "";


if (empty($criteria['units'])) {
    $criteriaUnits = TRANS('COL_UNIT') . ':&nbsp;' . TRANS('ALL');
} else {
    foreach ($criteria['units'] as $unit) {
        if (strlen((string)$units_names) > 0) $units_names .= ', ';
        $units_names .= $unit;
    }
    $criteriaUnits = TRANS('COL_UNIT') . ':&nbsp;' . $units_names;    
}

if (empty($criteria['categories'])) {
    $criteriaCategory = TRANS('CATEGORY') . ':&nbsp;' . TRANS('ALL');
} else {
    foreach ($criteria['categories'] as $categorie) {
        if (strlen((string)$categories_names) > 0) $categories_names .= ', ';
        $categories_names .= getAssetsCategories($conn, $categorie)['cat_name'];
    }
    $criteriaCategory = TRANS('CATEGORY') . ':&nbsp;' . $categories_names;
}

if (empty($criteria['asset_type'])) {
    $criteriaTypes = TRANS('COL_TYPE') . ':&nbsp;' . TRANS('ALL');
} else {
    foreach ($criteria['asset_type'] as $type) {
        if (strlen((string)$types_names) > 0) $types_names .= ', ';
        $types_names .= $type;
    }
    $criteriaTypes = TRANS('COL_TYPE') . ':&nbsp;' . $types_names;    
}

$criteriaText .= $criteriaClient . '<br />' . $criteriaUnits . '<br />' . $criteriaCategory . '<br />' . $criteriaTypes;


if (!empty($_SESSION['s_allowed_units'])) {
    $criteriaText .= " <br />(". TRANS('RESULT_LIMITED_BY_PERMISSIONS').")";
}

$data['criteria'] = $criteriaText;
/* Final da montagem dos critérios */


/* Totalização dos registros com base nos critérios */
$sqlTotal = "SELECT 
                count(*) as total 
            FROM equipamentos e 
            {$termsTotal}";
try {
    $res = $conn->prepare($sqlTotal);
    $res->execute();
    $result = $res->fetch(PDO::FETCH_ASSOC);
    $data['total'] = $result['total'];
} catch (Exception $e) {
    $exception .= "<hr>" . $e->getMessage();
    $data['exception'] = $exception;
    $data['sqlTotal'] = $sqlTotal;
}


if ($data['total'] == 0) {
    $data['success'] = false;
    $data['message'] = message('warning', 'Ooops!', TRANS('NO_RECORDS_FOUND'),'');
    echo json_encode($data);
    return false;
}


/* Tabela 1 */
$sql = "SELECT 
            count(*) as quantidade, 
            ROUND (count(*)*100/" . $data['total'] . ", 2) as percentual,
            t.tipo_nome AS asset_type,
            -- u.inst_nome AS asset_unit, 
            -- d.local AS department, 
            COALESCE (dom.dom_desc, 'N/A') AS domain
        FROM 
            equipamentos e, 
            tipo_equip t, 
            -- instituicao u, 
            localizacao d
                LEFT JOIN dominios dom ON dom.dom_cod = d.loc_dominio
        WHERE
            e.comp_tipo_equip = t.tipo_cod AND
            -- e.comp_inst = u.inst_cod AND 
            e.comp_local = d.loc_id 
            {$terms}

        GROUP BY
            dom.dom_desc,
            t.tipo_nome
            -- d.local, 
        ORDER BY
            dom.dom_desc,
            quantidade DESC,
            t.tipo_nome";

try {
    $res = $conn->prepare($sql);
    $res->execute();
    $result = $res->fetchAll(PDO::FETCH_ASSOC);
    $data['table'] = $result;

} catch (Exception $e) {
    $exception .= "<hr>" . $e->getMessage();
    $data['exception'] = $exception;
    $data['sql'] = $sql;
}



/* GRáfico 1 */
$sql = "SELECT 
            count(*) as quantidade, 
            ROUND (count(*)*100/" . $data['total'] . ", 2) as percentual,
            -- t.tipo_nome AS asset_type, 
            -- d.local AS department
            COALESCE (dom.dom_desc, 'N/A') AS domain
        FROM 
            equipamentos e, tipo_equip t, instituicao u, localizacao d
                LEFT JOIN dominios dom ON dom.dom_cod = d.loc_dominio
        WHERE
            e.comp_tipo_equip = t.tipo_cod AND
            e.comp_inst = u.inst_cod AND
            e.comp_local = d.loc_id
            {$terms}

        GROUP BY
            dom.dom_desc
        ORDER BY
            quantidade DESC,
            dom.dom_desc";

try {
    $res = $conn->prepare($sql);
    $res->execute();
    $result = $res->fetchAll(PDO::FETCH_ASSOC);
    $data[$chart_1_prefix] = $result;

    $data[$chart_1_prefix . $chart_title_sufix] = TRANS('GENERAL_DISTRIBUTION');

} catch (Exception $e) {
    $exception .= "<hr>" . $e->getMessage();
    $data['exception'] = $exception;
    $data['sql'] = $sql;
}



/* Gráfico 2 */
$sql = "SELECT 
            count(*) as quantidade, 
            ROUND (count(*)*100/" . $data['total'] . ", 2) as percentual,
            COALESCE(cat.cat_name, 'N/A') AS categorie
        FROM 
            equipamentos e, tipo_equip t
            LEFT JOIN assets_categories cat ON cat.id = t.tipo_categoria
        WHERE
            e.comp_tipo_equip = t.tipo_cod 
            {$terms}

        GROUP BY
            cat.cat_name
        ORDER BY
            quantidade DESC
            ";

try {
    $res = $conn->prepare($sql);
    $res->execute();
    $result = $res->fetchAll(PDO::FETCH_ASSOC);
    $data[$chart_2_prefix] = $result;

    $data[$chart_2_prefix . $chart_title_sufix] = TRANS('DISTRIBUTION_BY_CATEGORY');

} catch (Exception $e) {
    $exception .= "<hr>" . $e->getMessage();
    $data['exception'] = $exception;
    $data['sql'] = $sql;
}



/* Gráfico 3 */
$sql = "SELECT 
            count(*) as quantidade, 
            ROUND (count(*)*100/" . $data['total'] . ", 2) as percentual,
            COALESCE(c.nickname, 'N/A') AS client
        FROM 
            equipamentos e, tipo_equip t, instituicao u
            LEFT JOIN clients c ON c.id = u.inst_client
        WHERE
            e.comp_tipo_equip = t.tipo_cod AND
            e.comp_inst = u.inst_cod
            {$terms}

        GROUP BY
            c.nickname
        ORDER BY
            quantidade DESC
            ";

try {
    $res = $conn->prepare($sql);
    $res->execute();
    $result = $res->fetchAll(PDO::FETCH_ASSOC);
    $data[$chart_3_prefix] = $result;

    $data[$chart_3_prefix . $chart_title_sufix] = TRANS('DISTRIBUTION_BY_CLIENT');

} catch (Exception $e) {
    $exception .= "<hr>" . $e->getMessage();
    $data['exception'] = $exception;
    $data['sql'] = $sql;
}


/* Gráfico 4 */
$sql = "SELECT 
            count(*) as quantidade, 
            ROUND (count(*)*100/" . $data['total'] . ", 2) as percentual,
            -- t.tipo_nome AS asset_type,
            u.inst_nome AS asset_unit
        FROM 
            equipamentos e, tipo_equip t, instituicao u
        WHERE
            e.comp_tipo_equip = t.tipo_cod AND
            e.comp_inst = u.inst_cod
            {$terms}

        GROUP BY
            -- t.tipo_nome,
            u.inst_nome
        ORDER BY
            quantidade DESC,
            u.inst_nome
            -- t.tipo_nome 
            ";

try {
    $res = $conn->prepare($sql);
    $res->execute();
    $result = $res->fetchAll(PDO::FETCH_ASSOC);
    $data[$chart_4_prefix] = $result;

    $data[$chart_4_prefix . $chart_title_sufix] = TRANS('DISTRIBUTION_BY_UNIT');

} catch (Exception $e) {
    $exception .= "<hr>" . $e->getMessage();
    $data['exception'] = $exception;
    $data['sql'] = $sql;
}







echo json_encode($data);