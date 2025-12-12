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

$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 2, 2);

use includes\classes\ConnectPDO;
$conn = ConnectPDO::getInstance();


$post = $_POST;

$terms = "";
$criteria = array();
$criterText = "";
$badgeClass = "badge badge-info p-2 mb-1";
$badgeClassDirectAttributes = "badge badge-success p-2 mb-1";
$badgeClassAggregatedAttributes = "badge badge-oc-olive p-2 mb-1";
$badgeClassCustomFields = "badge badge-oc-teal p-2 mb-1";
$badgeClassEmptySearch = "badge badge-danger p-2 mb-1";


$imgsPath = "../../includes/imgs/";
$config = getConfig($conn);

$render_custom_fields = (isset($post['render_custom_fields']) ? $post['render_custom_fields']: 1);



$hasLimitationByAllowedClients = false;
$hasLimitationByAllowedUnits = false;

/* Cliente */
$field_label = TRANS('CLIENT');
$post_field_sufix = "client";
$field_table = "clients";
$field_id = "id";
$field_name = "nickname";

if (isset($post[$post_field_sufix]) && !empty($post[$post_field_sufix])) {
    $fieldIn = "";
    $unitIn = "";
    foreach ($post[$post_field_sufix] as $field) {
        if (strlen((string)$fieldIn)) $fieldIn .= ",";
        $fieldIn .= $field;
    }

    $sql = "SELECT inst_cod FROM instituicao WHERE inst_client IN ({$fieldIn}) ";
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        foreach ($res->fetchAll() as $rowUnit) {
            if (strlen((string)$unitIn)) $unitIn .= ",";
            $unitIn .= $rowUnit['inst_cod'];
        }

        $terms .= " AND inst.inst_cod IN ({$unitIn}) ";
    }

    $criterText = "";
    $sqlCriter = "SELECT {$field_name} FROM {$field_table} WHERE {$field_id} in ({$fieldIn}) ORDER BY {$field_name}";
    $resCriter = $conn->query($sqlCriter);
    foreach ($resCriter->fetchAll() as $rowCriter) {
        if (strlen((string)$criterText)) $criterText .= ", ";
        $criterText .= $rowCriter[$field_name];
    }
    $criterText = $field_label . ": " . $criterText ."<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
} else {
    /* Não houve seleção de cliente - é necessário checar se há limite de visualização para a área primária do usuário */
    if (!empty($_SESSION['s_allowed_units'])) {
        
        $hasLimitationByAllowedClients = true;
        
        $terms .= " AND inst.inst_cod IN ({$_SESSION['s_allowed_units']}) ";
    }
}



/* Unidade */
$field_label = TRANS('COL_UNIT');
$post_field_sufix = "unidade";
$sql_column = "inst.inst_cod";
$field_table = "instituicao";
$field_id = "inst_cod";
$field_name = "inst_nome";

if (isset($post['no_empty_' . $post_field_sufix]) && $post['no_empty_' . $post_field_sufix] == 1) {
    $terms .= " AND ( {$sql_column} != '-1' AND {$sql_column} != '0' AND {$sql_column} IS NOT NULL AND {$sql_column} != '') ";
    $criterText = $field_label . ": " . TRANS('SMART_NOT_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post['no_' . $post_field_sufix]) && $post['no_' . $post_field_sufix] == 1) {
    $terms .= " AND ( {$sql_column} = '-1' OR {$sql_column} = '0' OR {$sql_column} IS NULL OR {$sql_column} = '' ) ";
    $criterText = $field_label . ": " . TRANS('SMART_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post[$post_field_sufix]) && !empty($post[$post_field_sufix])) {
    $fieldIn = "";
    foreach ($post[$post_field_sufix] as $field) {
        if (strlen((string)$fieldIn)) $fieldIn .= ",";
        $fieldIn .= $field;
    }
    $terms .= " AND {$sql_column} IN ({$fieldIn}) ";

    $criterText = "";
    $sqlCriter = "SELECT {$field_name} FROM {$field_table} WHERE {$field_id} in ({$fieldIn}) ORDER BY {$field_name}";
    $resCriter = $conn->query($sqlCriter);
    foreach ($resCriter->fetchAll() as $rowCriter) {
        if (strlen((string)$criterText)) $criterText .= ", ";
        $criterText .= $rowCriter[$field_name];
    }
    $criterText = $field_label . ": " . $criterText ."<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
} else {
    /* Não houve seleção de unidade - é necessário checar se há limite de visualização para a área primária do usuário */
    if (!empty($_SESSION['s_allowed_units'])) {
        
        $hasLimitationByAllowedUnits = true;
        
        $terms .= " AND inst.inst_cod IN ({$_SESSION['s_allowed_units']}) ";
    }
}


/* Etiqueta */
$field_label = TRANS('ASSET_TAG');
$post_field_sufix = "etiqueta";
$sql_column = "c.comp_inv";

if (isset($post['no_empty_' . $post_field_sufix]) && $post['no_empty_' . $post_field_sufix] == 1) {
    $terms .= " AND ( {$sql_column} != '-1' AND {$sql_column} != '0' AND {$sql_column} IS NOT NULL AND {$sql_column} != '' ) ";
    $criterText = $field_label . ": " . TRANS('SMART_NOT_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post['no_' . $post_field_sufix]) && $post['no_' . $post_field_sufix] == 1) {
    $terms .= " AND ( {$sql_column} = '-1' OR {$sql_column} = '0' OR {$sql_column} IS NULL OR {$sql_column} = '' ) ";
    $criterText = $field_label . ": " . TRANS('SMART_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post[$post_field_sufix]) && !empty($post[$post_field_sufix])) {
    
    $tmp = explode(',', $post[$post_field_sufix]);
    // $treatValues = array_map('intval', $tmp);
    $treatValues = array_map('noHtml', $tmp);
    $tagIN = "";
    foreach ($treatValues as $tag) {
        if (strlen((string)$tagIN)) $tagIN .= ", ";
        $tag = trim($tag);
        $tagIN .= "'{$tag}'";
    }
    $terms .= " AND {$sql_column} IN ({$tagIN}) ";
    
    $criterText = $field_label . ": {$tagIN}<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
}


/* Tipo de ativo */
$field_label = TRANS('COL_TYPE');
$post_field_sufix = "equip_type";
$sql_column = "c.comp_tipo_equip";
$field_table = "tipo_equip";
$field_id = "tipo_cod";
$field_name = "tipo_nome";

if (isset($post['no_empty_' . $post_field_sufix]) && $post['no_empty_' . $post_field_sufix] == 1) {
    $terms .= " AND ( {$sql_column} != '-1' AND {$sql_column} != '0' AND {$sql_column} IS NOT NULL AND {$sql_column} != '') ";
    $criterText = $field_label . ": " . TRANS('SMART_NOT_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post['no_' . $post_field_sufix]) && $post['no_' . $post_field_sufix] == 1) {
    $terms .= " AND ( {$sql_column} = '-1' OR {$sql_column} = '0' OR {$sql_column} IS NULL OR {$sql_column} = '' ) ";
    $criterText = $field_label . ": " . TRANS('SMART_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post[$post_field_sufix]) && !empty($post[$post_field_sufix])) {
    $fieldIn = "";
    
    if (is_array($post[$post_field_sufix])) {
        foreach ($post[$post_field_sufix] as $field) {
            if (strlen((string)$fieldIn)) $fieldIn .= ",";
            $fieldIn .= $field;
        }
    } else {
        /* Se não for array, indica que a requisição não está vindo do filtro avancado */
        $fieldIn = $post[$post_field_sufix];
    }
    
    $terms .= " AND {$sql_column} IN ({$fieldIn}) ";

    $criterText = "";
    $sqlCriter = "SELECT {$field_name} FROM {$field_table} WHERE {$field_id} in ({$fieldIn}) ORDER BY {$field_name}";
    $resCriter = $conn->query($sqlCriter);
    foreach ($resCriter->fetchAll() as $rowCriter) {
        if (strlen((string)$criterText)) $criterText .= ", ";
        $criterText .= $rowCriter[$field_name];
    }
    $criterText = $field_label . ": " . $criterText ."<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
}





/* Categoria do tipo de ativo */
$field_label = TRANS('ASSET_CATEGORY');
$post_field_sufix = "asset_category";
$sql_column = "equip.tipo_categoria";
$field_table = "assets_categories";
$field_id = "id";
$field_name = "cat_name";


if (isset($post['no_empty_' . $post_field_sufix]) && $post['no_empty_' . $post_field_sufix] == 1) {
    $terms .= " AND ( {$sql_column} != '-1' AND {$sql_column} != '0' AND {$sql_column} IS NOT NULL AND {$sql_column} != '') ";
    $criterText = $field_label . ": " . TRANS('SMART_NOT_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post['no_' . $post_field_sufix]) && $post['no_' . $post_field_sufix] == 1) {
    $terms .= " AND ( {$sql_column} = '-1' OR {$sql_column} = '0' OR {$sql_column} IS NULL OR {$sql_column} = '' ) ";
    $criterText = $field_label . ": " . TRANS('SMART_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post[$post_field_sufix]) && !empty($post[$post_field_sufix])) {
    $fieldIn = "";
    
    
    if (is_array($post[$post_field_sufix])) {
        foreach ($post[$post_field_sufix] as $field) {
            if (strlen((string)$fieldIn)) $fieldIn .= ",";
            $fieldIn .= $field;
        }
    } else {
        /* Se não for array, indica que a requisição não está vindo do filtro avancado */
        $fieldIn = $post[$post_field_sufix];
    }
    
    
    $terms .= " AND {$sql_column} IN ({$fieldIn}) ";

    $criterText = "";
    $sqlCriter = "SELECT {$field_name} FROM {$field_table} WHERE {$field_id} in ({$fieldIn}) ORDER BY {$field_name}";
    $resCriter = $conn->query($sqlCriter);
    foreach ($resCriter->fetchAll() as $rowCriter) {
        if (strlen((string)$criterText)) $criterText .= ", ";
        $criterText .= $rowCriter[$field_name];
    }
    $criterText = $field_label . ": " . $criterText ."<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
}




/* Fabricante */
$field_label = TRANS('COL_MANUFACTURER');
$post_field_sufix = "manufacturer";
$sql_column = "fab.fab_cod";
$field_table = "fabricantes";
$field_id = "fab_cod";
$field_name = "fab_nome";

if (isset($post['no_empty_' . $post_field_sufix]) && $post['no_empty_' . $post_field_sufix] == 1) {
    $terms .= " AND ( {$sql_column} != '-1' AND {$sql_column} != '0' AND {$sql_column} IS NOT NULL AND {$sql_column} != '') ";
    $criterText = $field_label . ": " . TRANS('SMART_NOT_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post['no_' . $post_field_sufix]) && $post['no_' . $post_field_sufix] == 1) {
    $terms .= " AND ( {$sql_column} = '-1' OR {$sql_column} = '0' OR {$sql_column} IS NULL OR {$sql_column} = '' ) ";
    $criterText = $field_label . ": " . TRANS('SMART_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post[$post_field_sufix]) && !empty($post[$post_field_sufix])) {
    $fieldIn = "";
    foreach ($post[$post_field_sufix] as $field) {
        if (strlen((string)$fieldIn)) $fieldIn .= ",";
        $fieldIn .= $field;
    }
    $terms .= " AND {$sql_column} IN ({$fieldIn}) ";

    $criterText = "";
    $sqlCriter = "SELECT {$field_name} FROM {$field_table} WHERE {$field_id} in ({$fieldIn}) ORDER BY {$field_name}";
    $resCriter = $conn->query($sqlCriter);
    foreach ($resCriter->fetchAll() as $rowCriter) {
        if (strlen((string)$criterText)) $criterText .= ", ";
        $criterText .= $rowCriter[$field_name];
    }
    $criterText = $field_label . ": " . $criterText ."<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
}


/* Modelo */
$field_label = TRANS('COL_MODEL');
$post_field_sufix = "model";
$sql_column = "model.marc_cod";
$field_table = "marcas_comp";
$field_id = "marc_cod";
$field_name = "marc_nome";

if (isset($post['no_empty_' . $post_field_sufix]) && $post['no_empty_' . $post_field_sufix] == 1) {
    $terms .= " AND ( {$sql_column} != '-1' AND {$sql_column} != '0' AND {$sql_column} IS NOT NULL AND {$sql_column} != '') ";
    $criterText = $field_label . ": " . TRANS('SMART_NOT_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post['no_' . $post_field_sufix]) && $post['no_' . $post_field_sufix] == 1) {
    $terms .= " AND ( {$sql_column} = '-1' OR {$sql_column} = '0' OR {$sql_column} IS NULL OR {$sql_column} = '' ) ";
    $criterText = $field_label . ": " . TRANS('SMART_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post[$post_field_sufix]) && !empty($post[$post_field_sufix])) {
    $fieldIn = "";
    
    if (is_array($post[$post_field_sufix])) {
        foreach ($post[$post_field_sufix] as $field) {
            if (strlen((string)$fieldIn)) $fieldIn .= ",";
            $fieldIn .= $field;
        }
    } else {
        $fieldIn = $post[$post_field_sufix];
    }
    
    $terms .= " AND {$sql_column} IN ({$fieldIn}) ";

    $criterText = "";
    $sqlCriter = "SELECT {$field_name} FROM {$field_table} WHERE {$field_id} in ({$fieldIn}) ORDER BY {$field_name}";
    $resCriter = $conn->query($sqlCriter);
    foreach ($resCriter->fetchAll() as $rowCriter) {
        if (strlen((string)$criterText)) $criterText .= ", ";
        $criterText .= $rowCriter[$field_name];
    }
    $criterText = $field_label . ": " . $criterText ."<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
}



/* Serial number */
$field_label = TRANS('SERIAL_NUMBER');
$post_field_sufix = "serial_number";
$sql_column = "c.comp_sn";

if (isset($post['no_empty_' . $post_field_sufix]) && $post['no_empty_' . $post_field_sufix] == 1) {
    $terms .= " AND ( {$sql_column} != '-1' AND {$sql_column} != '0' AND {$sql_column} IS NOT NULL AND {$sql_column} != '' ) ";
    $criterText = $field_label . ": " . TRANS('SMART_NOT_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post['no_' . $post_field_sufix]) && $post['no_' . $post_field_sufix] == 1) {
    $terms .= " AND ( {$sql_column} = '-1' OR {$sql_column} = '0' OR {$sql_column} IS NULL OR {$sql_column} = '' ) ";
    $criterText = $field_label . ": " . TRANS('SMART_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post[$post_field_sufix]) && !empty($post[$post_field_sufix])) {
    
    $tmp = explode(',', $post[$post_field_sufix]);
    // $treatValues = array_map('intval', $tmp);
    $treatValues = array_map('noHtml', $tmp);
    $tagIN = "";
    foreach ($treatValues as $tag) {
        if (strlen((string)$tagIN)) $tagIN .= ", ";
        $tag = trim($tag);
        $tagIN .= "'{$tag}'";
    }
    $terms .= " AND {$sql_column} IN ({$tagIN}) ";
    
    $criterText = $field_label . ": {$tagIN}<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
}


/* Part-number */
$field_label = TRANS('COL_PARTNUMBER');
$post_field_sufix = "part_number";
$sql_column = "c.comp_part_number";

if (isset($post['no_empty_' . $post_field_sufix]) && $post['no_empty_' . $post_field_sufix] == 1) {
    $terms .= " AND ( {$sql_column} != '-1' AND {$sql_column} != '0' AND {$sql_column} IS NOT NULL AND {$sql_column} != '' ) ";
    $criterText = $field_label . ": " . TRANS('SMART_NOT_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post['no_' . $post_field_sufix]) && $post['no_' . $post_field_sufix] == 1) {
    $terms .= " AND ( {$sql_column} = '-1' OR {$sql_column} = '0' OR {$sql_column} IS NULL OR {$sql_column} = '' ) ";
    $criterText = $field_label . ": " . TRANS('SMART_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post[$post_field_sufix]) && !empty($post[$post_field_sufix])) {
    
    $tmp = explode(',', $post[$post_field_sufix]);
    // $treatValues = array_map('intval', $tmp);
    $treatValues = array_map('noHtml', $tmp);
    $tagIN = "";
    foreach ($treatValues as $tag) {
        if (strlen((string)$tagIN)) $tagIN .= ", ";
        $tag = trim($tag);
        $tagIN .= "'{$tag}'";
    }
    $terms .= " AND {$sql_column} IN ({$tagIN}) ";
    
    $criterText = $field_label . ": {$tagIN}<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
}




/* Departamento */
$field_label = TRANS('DEPARTMENT');
$post_field_sufix = "departamento";
$sql_column = "c.comp_local";
$field_table = "localizacao";
$field_id = "loc_id";
$field_name = "local";

if (isset($post['no_empty_' . $post_field_sufix]) && $post['no_empty_' . $post_field_sufix] == 1) {
    $terms .= " AND ( {$sql_column} != '-1' AND {$sql_column} != '0' AND {$sql_column} IS NOT NULL AND {$sql_column} != '') ";
    $criterText = $field_label . ": " . TRANS('SMART_NOT_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post['no_' . $post_field_sufix]) && $post['no_' . $post_field_sufix] == 1) {
    $terms .= " AND ( {$sql_column} = '-1' OR {$sql_column} = '0' OR {$sql_column} IS NULL OR {$sql_column} = '' ) ";
    $criterText = $field_label . ": " . TRANS('SMART_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post[$post_field_sufix]) && !empty($post[$post_field_sufix])) {
    $fieldIn = "";
    
    
    if (is_array($post[$post_field_sufix])) {
        foreach ($post[$post_field_sufix] as $field) {
            if (strlen((string)$fieldIn)) $fieldIn .= ",";
            $fieldIn .= $field;
        }
    } else {
        /* Se não for array, indica que a requisição não está vindo do filtro avancado */
        $fieldIn = $post[$post_field_sufix];
    }
    $terms .= " AND {$sql_column} IN ({$fieldIn}) ";

    $criterText = "";
    $sqlCriter = "SELECT {$field_name} FROM {$field_table} WHERE {$field_id} in ({$fieldIn}) ORDER BY {$field_name}";
    $resCriter = $conn->query($sqlCriter);
    foreach ($resCriter->fetchAll() as $rowCriter) {
        if (strlen((string)$criterText)) $criterText .= ", ";
        $criterText .= $rowCriter[$field_name];
    }
    $criterText = $field_label . ": " . $criterText ."<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
}




/* Situação */
$field_label = TRANS('STATE');
$post_field_sufix = "condition";
$sql_column = "c.comp_situac";
$field_table = "situacao";
$field_id = "situac_cod";
$field_name = "situac_nome";

if (isset($post['no_empty_' . $post_field_sufix]) && $post['no_empty_' . $post_field_sufix] == 1) {
    $terms .= " AND ( {$sql_column} != '-1' AND {$sql_column} != '0' AND {$sql_column} IS NOT NULL AND {$sql_column} != '') ";
    $criterText = $field_label . ": " . TRANS('SMART_NOT_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post['no_' . $post_field_sufix]) && $post['no_' . $post_field_sufix] == 1) {
    $terms .= " AND ( {$sql_column} = '-1' OR {$sql_column} = '0' OR {$sql_column} IS NULL OR {$sql_column} = '' ) ";
    $criterText = $field_label . ": " . TRANS('SMART_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post[$post_field_sufix]) && !empty($post[$post_field_sufix])) {
    $fieldIn = "";
    foreach ($post[$post_field_sufix] as $field) {
        if (strlen((string)$fieldIn)) $fieldIn .= ",";
        $fieldIn .= $field;
    }
    $terms .= " AND {$sql_column} IN ({$fieldIn}) ";

    $criterText = "";
    $sqlCriter = "SELECT {$field_name} FROM {$field_table} WHERE {$field_id} in ({$fieldIn}) ORDER BY {$field_name}";
    $resCriter = $conn->query($sqlCriter);
    foreach ($resCriter->fetchAll() as $rowCriter) {
        if (strlen((string)$criterText)) $criterText .= ", ";
        $criterText .= $rowCriter[$field_name];
    }
    $criterText = $field_label . ": " . $criterText ."<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
}

/* Fornecedores */
$field_label = TRANS('COL_VENDOR');
$post_field_sufix = "supplier";
$sql_column = "c.comp_fornecedor";
$field_table = "fornecedores";
$field_id = "forn_cod";
$field_name = "forn_nome";

if (isset($post['no_empty_' . $post_field_sufix]) && $post['no_empty_' . $post_field_sufix] == 1) {
    $terms .= " AND ( {$sql_column} != '-1' AND {$sql_column} != '0' AND {$sql_column} IS NOT NULL AND {$sql_column} != '') ";
    $criterText = $field_label . ": " . TRANS('SMART_NOT_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post['no_' . $post_field_sufix]) && $post['no_' . $post_field_sufix] == 1) {
    $terms .= " AND ( {$sql_column} = '-1' OR {$sql_column} = '0' OR {$sql_column} IS NULL OR {$sql_column} = '' ) ";
    $criterText = $field_label . ": " . TRANS('SMART_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post[$post_field_sufix]) && !empty($post[$post_field_sufix])) {
    $fieldIn = "";
    foreach ($post[$post_field_sufix] as $field) {
        if (strlen((string)$fieldIn)) $fieldIn .= ",";
        $fieldIn .= $field;
    }
    $terms .= " AND {$sql_column} IN ({$fieldIn}) ";

    $criterText = "";
    $sqlCriter = "SELECT {$field_name} FROM {$field_table} WHERE {$field_id} in ({$fieldIn}) ORDER BY {$field_name}";
    $resCriter = $conn->query($sqlCriter);
    foreach ($resCriter->fetchAll() as $rowCriter) {
        if (strlen((string)$criterText)) $criterText .= ", ";
        $criterText .= $rowCriter[$field_name];
    }
    $criterText = $field_label . ": " . $criterText ."<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
}

/* Centro de Custo */
$field_label = TRANS('COST_CENTER');
$post_field_sufix = "cost_center";
$sql_column = "c.comp_ccusto";
$db_name = DB_CCUSTO;
$field_table = TB_CCUSTO;
$field_id = CCUSTO_ID;
$field_name = CCUSTO_DESC;

if (isset($post['no_empty_' . $post_field_sufix]) && $post['no_empty_' . $post_field_sufix] == 1) {
    $terms .= " AND ( {$sql_column} != '-1' AND {$sql_column} != '0' AND {$sql_column} IS NOT NULL AND {$sql_column} != '') ";
    $criterText = $field_label . ": " . TRANS('SMART_NOT_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post['no_' . $post_field_sufix]) && $post['no_' . $post_field_sufix] == 1) {
    $terms .= " AND ( {$sql_column} = '-1' OR {$sql_column} = '0' OR {$sql_column} IS NULL OR {$sql_column} = '' ) ";
    $criterText = $field_label . ": " . TRANS('SMART_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post[$post_field_sufix]) && !empty($post[$post_field_sufix])) {
    $fieldIn = "";
    foreach ($post[$post_field_sufix] as $field) {
        if (strlen((string)$fieldIn)) $fieldIn .= ",";
        $fieldIn .= $field;
    }
    $terms .= " AND {$sql_column} IN ({$fieldIn}) ";

    $criterText = "";
    $sqlCriter = "SELECT {$field_name} FROM {$db_name}.{$field_table} WHERE {$field_id} in ({$fieldIn}) ORDER BY {$field_name}";
    $resCriter = $conn->query($sqlCriter);
    foreach ($resCriter->fetchAll() as $rowCriter) {
        if (strlen((string)$criterText)) $criterText .= ", ";
        $criterText .= $rowCriter[$field_name];
    }
    $criterText = $field_label . ": " . $criterText ."<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
}

/* Nota Fiscal */
$field_label = TRANS('INVOICE_NUMBER');
$post_field_sufix = "invoice_number";
$sql_column = "c.comp_nf";

if (isset($post['no_empty_' . $post_field_sufix]) && $post['no_empty_' . $post_field_sufix] == 1) {
    $terms .= " AND ( {$sql_column} != '-1' AND {$sql_column} != '0' AND {$sql_column} IS NOT NULL AND {$sql_column} != '' ) ";
    $criterText = $field_label . ": " . TRANS('SMART_NOT_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post['no_' . $post_field_sufix]) && $post['no_' . $post_field_sufix] == 1) {
    $terms .= " AND ( {$sql_column} = '-1' OR {$sql_column} = '0' OR {$sql_column} IS NULL OR {$sql_column} = '' ) ";
    $criterText = $field_label . ": " . TRANS('SMART_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post[$post_field_sufix]) && !empty($post[$post_field_sufix])) {
    
    $tmp = explode(',', $post[$post_field_sufix]);
    // $treatValues = array_map('intval', $tmp);
    $treatValues = array_map('noHtml', $tmp);
    $tagIN = "";
    foreach ($treatValues as $tag) {
        if (strlen((string)$tagIN)) $tagIN .= ", ";
        $tag = trim($tag);
        $tagIN .= "'{$tag}'";
    }
    $terms .= " AND {$sql_column} IN ({$tagIN}) ";
    
    $criterText = $field_label . ": {$tagIN}<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
}


/* Assistência */
$field_label = TRANS('ASSISTENCE');
$post_field_sufix = "assistance";
$sql_column = "c.comp_assist";
$field_table = "assistencia";
$field_id = "assist_cod";
$field_name = "assist_desc";

if (isset($post['no_empty_' . $post_field_sufix]) && $post['no_empty_' . $post_field_sufix] == 1) {
    $terms .= " AND ( {$sql_column} != '-1' AND {$sql_column} != '0' AND {$sql_column} IS NOT NULL AND {$sql_column} != '') ";
    $criterText = $field_label . ": " . TRANS('SMART_NOT_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post['no_' . $post_field_sufix]) && $post['no_' . $post_field_sufix] == 1) {
    $terms .= " AND ( {$sql_column} = '-1' OR {$sql_column} = '0' OR {$sql_column} IS NULL OR {$sql_column} = '' ) ";
    $criterText = $field_label . ": " . TRANS('SMART_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post[$post_field_sufix]) && !empty($post[$post_field_sufix])) {
    $fieldIn = "";
    foreach ($post[$post_field_sufix] as $field) {
        if (strlen((string)$fieldIn)) $fieldIn .= ",";
        $fieldIn .= $field;
    }
    $terms .= " AND {$sql_column} IN ({$fieldIn}) ";

    $criterText = "";
    $sqlCriter = "SELECT {$field_name} FROM {$field_table} WHERE {$field_id} in ({$fieldIn}) ORDER BY {$field_name}";
    $resCriter = $conn->query($sqlCriter);
    foreach ($resCriter->fetchAll() as $rowCriter) {
        if (strlen((string)$criterText)) $criterText .= ", ";
        $criterText .= $rowCriter[$field_name];
    }
    $criterText = $field_label . ": " . $criterText ."<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
}


/* Tipo de garantia */
$field_label = TRANS('FIELD_TYPE_WARRANTY');
$post_field_sufix = "warranty_type";
$sql_column = "c.comp_tipo_garant";
$field_table = "tipo_garantia";
$field_id = "tipo_garant_cod";
$field_name = "tipo_garant_nome";

if (isset($post['no_empty_' . $post_field_sufix]) && $post['no_empty_' . $post_field_sufix] == 1) {
    $terms .= " AND ( {$sql_column} != '-1' AND {$sql_column} != '0' AND {$sql_column} IS NOT NULL AND {$sql_column} != '') ";
    $criterText = $field_label . ": " . TRANS('SMART_NOT_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post['no_' . $post_field_sufix]) && $post['no_' . $post_field_sufix] == 1) {
    $terms .= " AND ( {$sql_column} = '-1' OR {$sql_column} = '0' OR {$sql_column} IS NULL OR {$sql_column} = '' ) ";
    $criterText = $field_label . ": " . TRANS('SMART_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post[$post_field_sufix]) && !empty($post[$post_field_sufix])) {
    $fieldIn = "";
    foreach ($post[$post_field_sufix] as $field) {
        if (strlen((string)$fieldIn)) $fieldIn .= ",";
        $fieldIn .= $field;
    }
    $terms .= " AND {$sql_column} IN ({$fieldIn}) ";

    $criterText = "";
    $sqlCriter = "SELECT {$field_name} FROM {$field_table} WHERE {$field_id} in ({$fieldIn}) ORDER BY {$field_name}";
    $resCriter = $conn->query($sqlCriter);
    foreach ($resCriter->fetchAll() as $rowCriter) {
        if (strlen((string)$criterText)) $criterText .= ", ";
        $criterText .= $rowCriter[$field_name];
    }
    $criterText = $field_label . ": " . $criterText ."<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
}

/* Garantia */
$field_label = TRANS('WARRANTY_STATUS');
$post_field_sufix = "warranty_status";


if (isset($post['no_empty_' . $post_field_sufix]) && $post['no_empty_' . $post_field_sufix] == 1) {
    $terms .= " AND (c.comp_data_compra IS NOT NULL AND c.comp_garant_meses IS NOT NULL) ";
    $criterText = $field_label . ": " . TRANS('SMART_NOT_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post['no_' . $post_field_sufix]) && $post['no_' . $post_field_sufix] == 1) {
    $terms .= " AND (c.comp_data_compra IS NULL OR c.comp_garant_meses IS NULL) ";
    $criterText = $field_label . ": " . TRANS('SMART_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post[$post_field_sufix]) && !empty($post[$post_field_sufix])) {
    
    $criterText = "";

    if ($post[$post_field_sufix] == 1) {
        $terms .= " AND (date_add(c.comp_data_compra, INTERVAL tmp.tempo_meses month) >= now()) ";
        $criterText .= TRANS('UNDER_WARRANTY');
    } elseif ($post[$post_field_sufix] == 2) {
        $terms .= " AND (date_add(c.comp_data_compra, INTERVAL tmp.tempo_meses month) < now()) ";
        $criterText .= TRANS('SEL_GUARANTEE_EXPIRED');
    }
    
    $criterText = $field_label . ": " . $criterText ."<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
}


/* Data mínima de aquisição */
$field_label = TRANS('SMART_MIN_PURCHASE_DATE');
$post_field_sufix = "purchase_date_from";
$sql_column = "c.comp_data_compra";

if (isset($post['no_empty_' . $post_field_sufix]) && $post['no_empty_' . $post_field_sufix] == 1) {
    $terms .= " AND ( {$sql_column} IS NOT NULL ) ";
    $criterText = $field_label . ": " . TRANS('SMART_NOT_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post['no_' . $post_field_sufix]) && $post['no_' . $post_field_sufix] == 1) {
    $terms .= " AND ( {$sql_column} IS NULL ) ";
    $criterText = $field_label . ": " . TRANS('SMART_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post[$post_field_sufix]) && !empty($post[$post_field_sufix])) {
    
    $date_from = "";
    $date_from = $post[$post_field_sufix] . " 00:00:00";
    $date_from = dateDB($date_from);
    
    $terms .= "  AND ( {$sql_column} >= '{$date_from}' ) ";
    
    $criterText = $field_label . ": " . $post[$post_field_sufix] . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
}


/* Data máxima de aquisição */
$field_label = TRANS('SMART_MAX_PURCHASE_DATE');
$post_field_sufix = "purchase_date_to";
$sql_column = "c.comp_data_compra";

if (isset($post['no_empty_' . $post_field_sufix]) && $post['no_empty_' . $post_field_sufix] == 1) {
    $terms .= " AND ( {$sql_column} IS NOT NULL ) ";
    $criterText = $field_label . ": " . TRANS('SMART_NOT_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post['no_' . $post_field_sufix]) && $post['no_' . $post_field_sufix] == 1) {
    $terms .= " AND ( {$sql_column} IS NULL ) ";
    $criterText = $field_label . ": " . TRANS('SMART_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post[$post_field_sufix]) && !empty($post[$post_field_sufix])) {
    
    $date_from = "";
    $date_from = $post[$post_field_sufix] . " 23:59:59";
    $date_from = dateDB($date_from);
    
    $terms .= "  AND ( {$sql_column} <= '{$date_from}' ) ";
    
    $criterText = $field_label . ": " . $post[$post_field_sufix] . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
}





// if (empty($terms)) {
//     $criterText = TRANS('SMART_WITHOUT_SEARCH_CRITERIA') . "<br />";
//     $criteria[] = "<span class='{$badgeClassEmptySearch}'>{$criterText}</span>";
// }

// echo $terms;

// $sql = $QRY["ocorrencias_full_ini"] . " WHERE 1 = 1 {$terms} ORDER BY numero";
$sql = $QRY["full_detail_ini"];
$sql .= $terms;
$sql .= $QRY["full_detail_fim"];
$sql .= " ORDER BY instituicao, etiqueta";

// dump($sql);

$sqlResult = $conn->query($sql);
$totalFiltered = $sqlResult->rowCount();






/* Exibição dos critérios sobre os atributos diretos do ativo pesquisado */
if (!empty($post['measure_type'])) {
    $i = 0;
    foreach ($post['measure_type'] as $measure) {
        if (!empty($post['measure_value'][$i]) && !empty($post['operation'][$i]) && !empty($post['measure_unit'][$i])) {
            
            $measure_type_name = getMeasureTypes($conn, $post['measure_type'][$i])['mt_name'];
            $measure_unit_name = getMeasureUnits($conn, $post['measure_unit'][$i])['unit_abbrev'];

            $criterText = $measure_type_name . '&nbsp;' . $post['operation'][$i] . '&nbsp;' . $post['measure_value'][$i] . $measure_unit_name . "<br />";
            $criteria[] = "<span class='{$badgeClassDirectAttributes}'>{$criterText}</span>";
        }
        $i++;
    }
}



/* Exibição dos critérios sobre os atributos agregados ao ativo pesquisado */
if (!empty($post['asset_type_aggregated'])) {
    $i = 0;
    foreach ($post['asset_type_aggregated'] as $type_aggregated) {
        
        if (!empty($post['asset_type_aggregated']) && !empty($post['measure_type_aggregated']) && !empty($post['measure_value_aggregated'][$i]) && !empty($post['measure_unit_aggregated'][$i])) {
            
            $asset_type_name = getAssetsTypes($conn, $post['asset_type_aggregated'][$i])['tipo_nome'];
            $measure_type_name = getMeasureTypes($conn, $post['measure_type_aggregated'][$i])['mt_name'];
            $measure_unit_name = getMeasureUnits($conn, $post['measure_unit_aggregated'][$i])['unit_abbrev'];

            $criterText = $asset_type_name . '&nbsp;' . TRANS('WITH') . '&nbsp;' . $measure_type_name . '&nbsp;' . $post['operation_aggregated'][$i] . '&nbsp;' . $post['measure_value_aggregated'][$i] . $measure_unit_name . "<br />";
             
            $criteria[] = "<span class='{$badgeClassAggregatedAttributes}'>{$criterText}</span>";
            
        } elseif (isset($post['no_empty_']) && count($post['no_empty_']) && array_key_exists($post['asset_type_aggregated'][$i], $post['no_empty_'])) {
            
            $asset_type_name = getAssetsTypes($conn, $post['asset_type_aggregated'][$i])['tipo_nome'];
            $criterText = $asset_type_name . ':&nbsp;' . TRANS('OCO_SEL_ANY'). "<br />";
            $criteria[] = "<span class='{$badgeClassAggregatedAttributes}'>{$criterText}</span>";
            

        } elseif (isset($post['no_']) && count($post['no_']) && array_key_exists($post['asset_type_aggregated'][$i], $post['no_'])) {
            $asset_type_name = getAssetsTypes($conn, $post['asset_type_aggregated'][$i])['tipo_nome'];
            $criterText = $asset_type_name . ':&nbsp;' . TRANS('SEL_NONE');
            $criteria[] = "<span class='{$badgeClassAggregatedAttributes}'>{$criterText}</span>";
        }

        
        $i++;
    }
}









/**
 * Campos personalizados
 * Tipos de campos possíveis:
 * ["date", "datetime", "select", "select_multi", "number", "text", "textarea", "checkbox"]
 * 
 * Até o momento esses são os campos permitidos e tratados:
 * ["date", "datetime", "select", "select_multi", "number", "text", "textarea", "checkbox"]
 */

$custom_fields = [];
$custom_fields_full = [];
if ($render_custom_fields) {
    $types = ["date", "datetime", "select", "select_multi", "number", "text", "textarea", "checkbox"];
    $custom_fields = getCustomFields($conn, null, 'equipamentos', $types); /* Apenas campos customizados ativos e que podem ser pesquisados */
    $custom_fields_full = getCustomFields($conn, null, 'equipamentos'); /* Para montar a tabela de exibição, todos os campos ativos sao utilizados */
}

/* Montagem dos Critérios dos campos personalizados preenchidos */
$emptyPrefix = "no_";
$notEmptyPrefix = "no_empty_";
$minDatePrefix = "min_";
$maxDatePrefix = "max_";
$minNumberPrefix = "minNum_";
$maxNumberPrefix = "maxNum_";
$noRenderPrefix = "norender_";
$dontRender = [];

/** Armazenarei aqui os valores a serem checados por cada chamado */
$customTerms = [];
foreach ($custom_fields as $cfield) {
    $criterText = "";

    /* Ver os campos que não devem ser renderizados */
    if (isset($post[$noRenderPrefix . $cfield['field_name']]) && $post[$noRenderPrefix . $cfield['field_name']] == 1) {
        $dontRender[] = $cfield['id'];
    }
    
    if (isset($post[$notEmptyPrefix . $cfield['field_name']]) && $post[$notEmptyPrefix . $cfield['field_name']] == 1) {
        /* Qualquer valor não vazio */
        $criterText = $cfield['field_label'] . ": " . TRANS('SMART_NOT_EMPTY') . "<br />";
        $criteria[] = "<span class='{$badgeClassCustomFields}'>{$criterText}</span>";

        /* id - operador - valor de comparacao */
        $customTerms[$cfield['id']]['!='][] = '';

    } elseif (isset($post[$emptyPrefix . $cfield['field_name']]) && $post[$emptyPrefix . $cfield['field_name']] == 1) {
        /* Valor obrigatiamente vazio */
        $criterText = $cfield['field_label'] . ": " . TRANS('SMART_EMPTY') . "<br />";
        $criteria[] = "<span class='{$badgeClassCustomFields}'>{$criterText}</span>";

        /* id - operador - valor de comparacao */
        $customTerms[$cfield['id']]['=='][] = '';
        
    } elseif (isset($post[$cfield['field_name']]) && !empty($post[$cfield['field_name']])) {
        /* Valor informado */
        
        if ($cfield['field_type'] == 'select' || $cfield['field_type'] == 'select_multi') {
            $fieldIN = [];
            foreach ($post[$cfield['field_name']] as $fieldValue) {
                $fieldIN[] = getCustomFieldValue($conn, $fieldValue);
            
                /* id - operador - valor de comparacao */
                $customTerms[$cfield['id']]['IN'][] = $fieldValue;
            }

        } else {
            /* Ver tratamento para cada tipo de campo - Datas não entram nesse laço */
            $fieldIN = $post[$cfield['field_name']];

            /* id - operador - valor de comparacao */
            // $customTerms[$cfield['id']]['=='][] = $fieldIN;

            /* Operador de comparação direta '===' */
            $customTerms[$cfield['id']]['==='][] = $fieldIN;
        }

        $criterText = (is_array($fieldIN) ? implode(", ", $fieldIN) : $fieldIN);

        $criterText = $cfield['field_label'] . ": " . $criterText ."<br />";
        $criteria[] = "<span class='{$badgeClassCustomFields}'>{$criterText}</span>";

    } elseif (isset($post[$minDatePrefix . $cfield['field_name']]) && !empty($post[$minDatePrefix . $cfield['field_name']])) {
        /* Se tiver data mínima selecionada - Campos do tipo date ou datetime' */
        $criterText = $post[$minDatePrefix . $cfield['field_name']];
        $criterText = $cfield['field_label'] . " (" . TRANS('MIN_DATE') . "): " . $criterText ."<br />";
        $criterText2 = "";


        /* id - operador - valor de comparacao */
        $customTerms[$cfield['id']]['<='][] = $post[$minDatePrefix . $cfield['field_name']];
        
        
        /* Tem data final? */
        if (isset($post[$maxDatePrefix . $cfield['field_name']]) && !empty($post[$maxDatePrefix . $cfield['field_name']])) {
            $criterText2 = $post[$maxDatePrefix . $cfield['field_name']];

            $criterText2 = $cfield['field_label'] . " (" . TRANS('MAX_DATE') . "): " . $criterText2 ."<br />";

            /* id - operador - valor de comparacao */
            $customTerms[$cfield['id']]['>='][] = $post[$maxDatePrefix . $cfield['field_name']];
        }

        $criteria[] = "<span class='{$badgeClassCustomFields}'>{$criterText}</span>";
        $criteria[] = "<span class='{$badgeClassCustomFields}'>{$criterText2}</span>";
    } elseif (isset($post[$maxDatePrefix . $cfield['field_name']]) && !empty($post[$maxDatePrefix . $cfield['field_name']])) {
        /* Se tiver data máxima selecionada mas não tiver data mínima  */
        $criterText = $post[$maxDatePrefix . $cfield['field_name']];
        $criterText = $cfield['field_label'] . " (" . TRANS('MAX_DATE') . "): " . $criterText ."<br />";
        $criterText2 = "";
        
        $criteria[] = "<span class='{$badgeClassCustomFields}'>{$criterText}</span>";

        /* id - operador - valor de comparacao */
        $customTerms[$cfield['id']]['>='][] = $post[$maxDatePrefix . $cfield['field_name']];
    
    
    }  elseif (isset($post[$minNumberPrefix . $cfield['field_name']]) && !empty($post[$minNumberPrefix . $cfield['field_name']])) {
        /* Se tiver data mínima selecionada - Campos do tipo date ou datetime' */
        $criterText = $post[$minNumberPrefix . $cfield['field_name']];
        $criterText = $cfield['field_label'] . " (" . TRANS('MIN_VALUE') . "): " . $criterText ."<br />";
        $criterText2 = "";


        /* id - operador - valor de comparacao */
        $customTerms[$cfield['id']]['<='][] = $post[$minNumberPrefix . $cfield['field_name']];
        
        
        /* Tem limite final? */
        if (isset($post[$maxNumberPrefix . $cfield['field_name']]) && !empty($post[$maxNumberPrefix . $cfield['field_name']])) {
            $criterText2 = $post[$maxNumberPrefix . $cfield['field_name']];

            $criterText2 = $cfield['field_label'] . " (" . TRANS('MAX_VALUE') . "): " . $criterText2 ."<br />";

            /* id - operador - valor de comparacao */
            $customTerms[$cfield['id']]['>='][] = $post[$maxNumberPrefix . $cfield['field_name']];
        }

        $criteria[] = "<span class='{$badgeClassCustomFields}'>{$criterText}</span>";
        $criteria[] = "<span class='{$badgeClassCustomFields}'>{$criterText2}</span>";

    } elseif (isset($post[$maxNumberPrefix . $cfield['field_name']]) && !empty($post[$maxNumberPrefix . $cfield['field_name']])) {
        /* Se tiver data máxima selecionada mas não tiver data mínima  */
        $criterText = $post[$maxNumberPrefix . $cfield['field_name']];
        $criterText = $cfield['field_label'] . " (" . TRANS('MAX_VALUE') . "): " . $criterText ."<br />";
        $criterText2 = "";
        
        $criteria[] = "<span class='{$badgeClassCustomFields}'>{$criterText}</span>";

        /* id - operador - valor de comparacao */
        $customTerms[$cfield['id']]['>='][] = $post[$maxNumberPrefix . $cfield['field_name']];
    } 
}
/* Final da montagem dos critérios sobre os campos personalizados preenchidos */




if (empty($criteria)) {
    $criterText = TRANS('SMART_WITHOUT_SEARCH_CRITERIA') . "<br />";
    $criteria[] = "<span class='{$badgeClassEmptySearch}'>{$criterText}</span>";
}


$criterios = "";

?>
    
    <div id="table_info"></div>
    <div id="div_criterios" class="row p-4">
        <div class="col-10">
            <?php
            foreach ($criteria as $badge) {
                $criterios .= $badge . "&nbsp;";
            }
            ?> 
        </div>
        
    </div>
    <div class="display-buttons"></div>

    <div class="double-scroll">
        <table id="table_tickets_queue" class="stripe hover order-column row-border" border="0" cellspacing="0" width="100%">
            <thead>
                <tr class="header">
                    <td class='line etiqueta'><?= TRANS('ASSET_TAG'); ?></td>
                    <td class='line cliente'><?= TRANS('CLIENT'); ?></td>
                    <td class='line unidade'><?= TRANS('COL_UNIT'); ?></td>
                    <td class='line asset_category'><?= TRANS('ASSET_CATEGORY'); ?></td>
                    <td class='line type_of'><?= TRANS('COL_TYPE'); ?></td>
                    <td class='line manufacturer'><?= TRANS('COL_MANUFACTURER'); ?></td>
                    <td class='line model'><?= TRANS('COL_MODEL'); ?></td>
                    <td class='line serial_number'><?= TRANS('SERIAL_NUMBER'); ?></td>
                    <td class='line part_number'><?= TRANS('COL_PARTNUMBER'); ?></td>
                    <td class='line department'><?= TRANS('DEPARTMENT'); ?></td>
                    <td class='line state'><?= TRANS('STATE'); ?></td>
                    <td class='line supplier'><?= TRANS('COL_VENDOR'); ?></td>
                    <td class='line cost_center'><?= TRANS('COST_CENTER'); ?></td>
                    <td class='line value'><?= TRANS('FIELD_PRICE'); ?></td>
                    <td class='line invoice_number'><?= TRANS('INVOICE_NUMBER'); ?></td>
                    <td class='line assistance'><?= TRANS('ASSISTENCE'); ?></td>
                    <td class='line waranty_type'><?= TRANS('FIELD_TYPE_WARRANTY'); ?></td>
                    <td class='line waranty_expire'><?= TRANS('WARRANTY_EXPIRE'); ?></td>
                    <td class='line purchase_date'><?= TRANS('PURCHASE_DATE'); ?></td>

                    <td class='line direct_attributes'><?= TRANS('DIRECT_ATTRIBUTES'); ?></td>
                    <td class='line aggregated_attributes'><?= TRANS('AGGREGATED_ATTRIBUTES'); ?></td>
                    <td class='line soft_aggregated_attributes'><?= TRANS('AGGREGATED_SOFTWARES'); ?></td>



                    <?php
                        /* Campos customizados */
                        foreach ($custom_fields_full as $cfield) {
                            if (!in_array($cfield['id'], $dontRender)) {
                            ?>
                                <td class="line custom_field <?= $cfield['field_name']; ?>"><?= $cfield['field_label']; ?></td>
                            <?php
                            }
                        }
                    ?>

                    <td class='line deprecated_attributes'><?= TRANS('DEPRECATED_ATTRIBUTES'); ?></td>

                </tr>
            </thead>
       
<?php



foreach ($sqlResult->fetchAll() as $row){
    $nestedData = array(); 
    $showRecord = true;
    

    /* Retona array com as especificações físicas agregadas ao ativo */
    $aggregated_specs = getAssetSpecs($conn, $row['comp_cod']);
    
    /* Retona array com as especificações físicas agregadas ao ativo */
    $hard_aggregated_specs = getAssetSpecs($conn, $row['comp_cod'], null, false);

    /* Retona array com as especificações de software agregadas ao ativo */
    $soft_aggregated_specs = getAssetSpecs($conn, $row['comp_cod'], null, true);

    /* Atributos diretos do modelo do ativo */
    if ($showRecord) {
        if (!empty($post['measure_type'])) {
            $i = 0;
            foreach ($post['measure_type'] as $measure) {
                if (!empty($post['measure_value'][$i]) && !empty($post['operation'][$i]) && !empty($post['measure_unit'][$i])) {
                    if (!modelHasAttribute($conn, $row['modelo_cod'], $post['measure_unit'][$i], $post['operation'][$i], $post['measure_value'][$i])) {
                        $showRecord = false;
                        break;
                    }
                }
                $i++;
            }
        }
    }



    /* Atributos agregados ao ativo */
    if ($showRecord) {
        if (!empty($post['asset_type_aggregated'])) {

            $matchesSpecs = [];
            $matchesNotEmpty = [];
            $matchesEmpty = [];

            /* Inicializo false, se atender a algum critério volta a ser true */
            $showRecord = false;
            
            $countCriteria = 0;
            $i = 0;
            foreach ($post['asset_type_aggregated'] as $type_aggregated) {
                
                if (!empty($post['asset_type_aggregated'][$i]) && !empty($post['measure_type_aggregated'][$i]) && !empty($post['measure_value_aggregated'][$i]) && !empty($post['measure_unit_aggregated'][$i])) {
                    
                    $countCriteria++;

                    foreach ($aggregated_specs as $asset_spec) {
                        if ($post['asset_type_aggregated'][$i] == $asset_spec['marc_tipo'] && modelHasAttribute($conn, $asset_spec['marc_cod'], $post['measure_unit_aggregated'][$i], $post['operation_aggregated'][$i], $post['measure_value_aggregated'][$i])) {
                            
                            // $matchesSpecs[$asset_spec['marc_tipo'].'-'.$post['measure_type_aggregated'][$i].'-'.$post['measure_unit_aggregated'][$i].'-'.$post['measure_value_aggregated'][$i]] = $post['measure_unit_aggregated'][$i];
                            $matchesSpecs[] = $post['measure_unit_aggregated'][$i];
                            break;
                        }
                    }
                    
                } elseif (isset($post['no_empty_']) && count($post['no_empty_']) && array_key_exists($post['asset_type_aggregated'][$i], $post['no_empty_'])) {
                    $countCriteria++;
                    /* Pesquisa por 'qualquer' */
                    foreach ($aggregated_specs as $asset_spec) {
                        if ($post['asset_type_aggregated'][$i] == $asset_spec['marc_tipo']) {
                            
                            $matchesNotEmpty[] = $asset_spec['marc_tipo'];
                            break;
                        }
                    }

                } elseif (isset($post['no_']) && count($post['no_']) && array_key_exists($post['asset_type_aggregated'][$i], $post['no_'])) {

                    /* Pesquisa por 'nenhum' */
                    $countCriteria++;

                    $showRecord = true;
                    
                    foreach ($aggregated_specs as $asset_spec) {
                            foreach ($post['no_'] as $none => $value) {
                                
                                if ($none == $asset_spec['marc_tipo'] && $none == $post['asset_type_aggregated'][$i]) {
                                    $showRecord = false;
                                    break;
                                }
                            }

                            if (!$showRecord) {
                                break;
                            }
                    }
                    if ($showRecord) {
                        $matchesEmpty[] = $post['asset_type_aggregated'][$i];
                    }
                }
                $i++;
            }

            if ((count($matchesSpecs) + count($matchesNotEmpty) + count($matchesEmpty) == $countCriteria) ) {
                $showRecord = true;
            } else {
                $showRecord = false;
            }
            // var_dump([
            //     'Etiqueta do Ativo' => $row['etiqueta'],
            //     'countCriteria' => $countCriteria,
            //     'matchesSpecs' => $matchesSpecs,
            //     'matchesNotEmpty' => $matchesNotEmpty,
            //     'matchesEmpty' => $matchesEmpty
            // ]);
        }
    }

    









    /** 
     * Processamento para consulta sobre os campos personalizados
    */
    if ($showRecord && count($customTerms)) {
        foreach ($customTerms as $id => $op) {

            $isNumber = false;
            $isDate = false;
            $assetFieldValues = getAssetCustomFields($conn, $row['comp_cod'], $id);
            if ($assetFieldValues['field_type'] == 'date') {
                /* campo de data */
                $isDate = true;
            } elseif ($assetFieldValues['field_type'] == 'number') {
                /* campo numérico */
                $isNumber = true;
            }
            $ticketFieldValue = $assetFieldValues['field_value_idx'];

            
            foreach ($op as $operation => $values) {

                if ($showRecord) {

                    $foundOne = false;
                    foreach ($values as $value) {

                        if ($operation == "!=" && $showRecord) {
                            /* não vazio */
                            $showRecord = (!empty($ticketFieldValue));

                        } elseif ($operation == "==" && $showRecord) {
                            /* vazio */
                            $showRecord = (empty($ticketFieldValue));

                        } elseif ($operation == "===" && $showRecord) {
                            /* Campos de comparação direta do valor - Tipo texto*/
                            $showRecord = ($ticketFieldValue == $value);

                        } elseif ($operation == "IN") {
                            /* valor do post */

                            $expMultiValues = (!empty($ticketFieldValue) ? explode(',', $ticketFieldValue) : []);
                            foreach ($expMultiValues as $SepValue) {
                                if ($SepValue == $value) {
                                    $foundOne = true;
                                }
                            }

                            $showRecord = $foundOne;
                            
                        } elseif ($operation == "<=" && $showRecord) {
                            /* A data pesquisada tem que ser menor ou igual à data gravada */

                            if ($isNumber) {
                                if (!empty($ticketFieldValue)) {
                                    $baseValue = "";

                                    if (filter_var($value, FILTER_VALIDATE_INT)) {
                                        $baseValue = $value;
                                    } else {
                                        $showRecord = false;
                                    }

                                    if (!($baseValue <= $ticketFieldValue)) {
                                        $showRecord = false;
                                    }
                                } else {
                                    $showRecord = false;
                                }
                            } elseif ($isDate) {
                                if (!empty($ticketFieldValue)) {
                                    $baseDate = "";
                                    if (isValidDate($value, "d/m/Y")) {
                                        $baseDate = dateDB($value);
                                    } else {
                                        $showRecord = false;
                                    }

                                    if (!(strtotime($baseDate) <= strtotime($ticketFieldValue))) {
                                        $showRecord = false;
                                    }
                                } else {
                                    $showRecord = false;
                                }
                            } else {
                                /* datetime */
                                if (!empty($ticketFieldValue)) {
                                    $baseDate = "";
                                    if (isValidDate($value, "d/m/Y H:i")) {
                                        $baseDate = dateDB($value);
                                    } else {
                                        $showRecord = false;
                                    }

                                    if (!(strtotime($baseDate) <= strtotime($ticketFieldValue))) {
                                        $showRecord = false;
                                    }
                                } else {
                                    $showRecord = false;
                                }
                            }
                            
                            
                            
                        } elseif ($operation == ">=" && $showRecord) {
                            
                            if ($isNumber) {
                                if (!empty($ticketFieldValue)) {
                                    $baseValue = "";

                                    if (filter_var($value, FILTER_VALIDATE_INT)) {
                                        $baseValue = $value;
                                    } else {
                                        $showRecord = false;
                                    }

                                    if (!($baseValue >= $ticketFieldValue)) {
                                        $showRecord = false;
                                    }
                                } else {
                                    $showRecord = false;
                                }
                            }
                            
                            
                            /* A data pesquisada tem que ser maior ou igual à data gravada */
                            elseif ($isDate) {
                                if (!empty($ticketFieldValue)) {
                                    $baseDate = "";
                                    if (isValidDate($value, "d/m/Y")) {
                                        $baseDate = dateDB($value . " 23:59:59");
                                    } else {
                                        $showRecord = false;
                                    }

                                    if (!(strtotime($baseDate) >= strtotime($ticketFieldValue))) {
                                        $showRecord = false;
                                    }
                                } else {
                                    $showRecord = false;
                                }
                            } else {
                                /* Datetime */
                                if (!empty($ticketFieldValue)) {
                                    $baseDate = "";
                                    if (isValidDate($value, "d/m/Y H:i")) {
                                        $baseDate = dateDB($value);
                                    } else {
                                        $showRecord = false;
                                    }

                                    if (!(strtotime($baseDate) >= strtotime($ticketFieldValue))) {
                                        $showRecord = false;
                                    }
                                } else {
                                    $showRecord = false;
                                }
                            }
                            
                        }
                    }
                }
            }
        }
    }
    /** Final do processamento sobre consulta por campos personalizados */





    if ($showRecord) {

        $cost_center = "";
        if (!empty($row['ccusto'])) {
            $ccusto_array = getCostCenters($conn, $row['ccusto']);
            $cost_center = (!empty($ccusto_array) ? $ccusto_array['ccusto_name'] . " - " . $ccusto_array['ccusto_cod'] : "");
        }

        
        /* Atributos diretos */
        $modelDetails = getModelSpecs($conn, $row['modelo_cod']);
        $directAttributes = '';
        foreach ($modelDetails as $detail) {
            $directAttributes .= '<li class="list-attributes">' . $detail['mt_name'] . ': ' . $detail['spec_value'] . '' . $detail['unit_abbrev'] . '</li>';
        }

        /* Atributos agregados */
        $aggregatedAttributes = '';
        if (!empty($hard_aggregated_specs)) {
            foreach ($hard_aggregated_specs as $spec) {
                $aggregatedAttributes .= '<li class="list-attributes">' . $spec['tipo_nome'] . ': ' . $spec['marc_nome'] . '</li>';
            }
        }

        /* Atributos de software agregados */
        $softAggregatedAttributes = '';
        if (!empty($soft_aggregated_specs)) {
            foreach ($soft_aggregated_specs as $spec) {
                $softAggregatedAttributes .= '<li class="list-attributes">' . $spec['tipo_nome'] . ': ' . $spec['marc_nome'] . '</li>';
            }
        }


        $mbArray = getPeripheralInfo($conn, $row['tipo_mb']);
        $procArray = getPeripheralInfo($conn, $row['tipo_proc']);
        $memoArray = getPeripheralInfo($conn, $row['tipo_memo']);
        $hddArray = getPeripheralInfo($conn, $row['tipo_hd']);
        $networkArray = getPeripheralInfo($conn, $row['tipo_rede']);
        $modemArray = getPeripheralInfo($conn, $row['tipo_modem']);
        $videoArray = getPeripheralInfo($conn, $row['tipo_video']);
        $soundArray = getPeripheralInfo($conn, $row['tipo_som']);
        $cdromArray = getPeripheralInfo($conn, $row['tipo_cdrom']);
        $dvdromArray = getPeripheralInfo($conn, $row['tipo_dvd']);
        $recorderArray = getPeripheralInfo($conn, $row['tipo_grav']);
        
        $motherboard = $mbArray['mdit_fabricante']. " " . $mbArray['mdit_desc'] . " " . $mbArray['mdit_desc_capacidade'] . " " . $mbArray['mdit_sufixo'];
        $processor = $procArray['mdit_fabricante']. " " . $procArray['mdit_desc'] . " " . $procArray['mdit_desc_capacidade'] . " " . $procArray['mdit_sufixo'];
        $memory = $memoArray['mdit_fabricante']. " " . $memoArray['mdit_desc'] . " " . $memoArray['mdit_desc_capacidade'] . " " . $memoArray['mdit_sufixo'];
        $hdd = $hddArray['mdit_fabricante']. " " . $hddArray['mdit_desc'] . " " . $hddArray['mdit_desc_capacidade'] . " " . $hddArray['mdit_sufixo'];
        $network = $networkArray['mdit_fabricante']. " " . $networkArray['mdit_desc'] . " " . $networkArray['mdit_desc_capacidade'] . " " . $networkArray['mdit_sufixo'];
        $modem = $modemArray['mdit_fabricante']. " " . $modemArray['mdit_desc'] . " " . $modemArray['mdit_desc_capacidade'] . " " . $modemArray['mdit_sufixo'];
        $video = $videoArray['mdit_fabricante']. " " . $videoArray['mdit_desc'] . " " . $videoArray['mdit_desc_capacidade'] . " " . $videoArray['mdit_sufixo'];
        $sound = $soundArray['mdit_fabricante']. " " . $soundArray['mdit_desc'] . " " . $soundArray['mdit_desc_capacidade'] . " " . $soundArray['mdit_sufixo'];
        $cdrom = $cdromArray['mdit_fabricante']. " " . $cdromArray['mdit_desc'] . " " . $cdromArray['mdit_desc_capacidade'] . " " . $cdromArray['mdit_sufixo'];
        $recorder = $recorderArray['mdit_fabricante']. " " . $recorderArray['mdit_desc'] . " " . $recorderArray['mdit_desc_capacidade'] . " " . $recorderArray['mdit_sufixo'];
        $dvdrom = $dvdromArray['mdit_fabricante']. " " . $dvdromArray['mdit_desc'] . " " . $dvdromArray['mdit_desc_capacidade'] . " " . $dvdromArray['mdit_sufixo'];


        $deprecatedAttributes = '';

        if (!empty(trim($motherboard))) {
            $deprecatedAttributes .= '<li class="list-attributes">' . TRANS('MOTHERBOARD') . ' ' . $mbArray['mdit_fabricante'] . ' ' . $mbArray['mdit_desc'] . ' ' . $mbArray['mdit_desc_capacidade'] . ' ' . $mbArray['mdit_sufixo'] . '</li>';
        }
        if (!empty(trim($processor))) {
            $deprecatedAttributes .= '<li class="list-attributes">' . TRANS('PROCESSOR') . ' ' . $procArray['mdit_fabricante'] . ' ' . $procArray['mdit_desc'] . ' ' . $procArray['mdit_desc_capacidade'] . ' ' . $procArray['mdit_sufixo'] . '</li>';
        }
        if (!empty(trim($memory))) {
            $deprecatedAttributes .= '<li class="list-attributes">' . TRANS('CARD_MEMORY') . ' ' . $memoArray['mdit_fabricante'] . ' ' . $memoArray['mdit_desc'] . ' ' . $memoArray['mdit_desc_capacidade'] . ' ' . $memoArray['mdit_sufixo'] . '</li>';
        }
        if (!empty(trim($hdd))) {
            $deprecatedAttributes .= '<li class="list-attributes">' . TRANS('MNL_HD') . ' ' . $hddArray['mdit_fabricante'] . ' ' . $hddArray['mdit_desc'] . ' ' . $hddArray['mdit_desc_capacidade'] . ' ' . $hddArray['mdit_sufixo'] . '</li>';
        }
        if (!empty(trim($network))) {
            $deprecatedAttributes .= '<li class="list-attributes">' . TRANS('CARD_NETWORK') . ' ' . $networkArray['mdit_fabricante'] . ' ' . $networkArray['mdit_desc'] . ' ' . $networkArray['mdit_desc_capacidade'] . ' ' . $networkArray['mdit_sufixo'] . '</li>';
        }
        if (!empty(trim($modem))) {
            $deprecatedAttributes .= '<li class="list-attributes">' . TRANS('CARD_MODEN') . ' ' . $modemArray['mdit_fabricante'] . ' ' . $modemArray['mdit_desc'] . ' ' . $modemArray['mdit_desc_capacidade'] . ' ' . $modemArray['mdit_sufixo'] . '</li>';
        }
        if (!empty(trim($video))) {
            $deprecatedAttributes .= '<li class="list-attributes">' . TRANS('CARD_VIDEO') . ' ' . $videoArray['mdit_fabricante'] . ' ' . $videoArray['mdit_desc'] . ' ' . $videoArray['mdit_desc_capacidade'] . ' ' . $videoArray['mdit_sufixo'] . '</li>';
        }
        if (!empty(trim($sound))) {
            $deprecatedAttributes .= '<li class="list-attributes">' . TRANS('CARD_SOUND') . ' ' . $soundArray['mdit_fabricante'] . ' ' . $soundArray['mdit_desc'] . ' ' . $soundArray['mdit_desc_capacidade'] . ' ' . $soundArray['mdit_sufixo'] . '</li>';
        }
        if (!empty(trim($cdrom))) {
            $deprecatedAttributes .= '<li class="list-attributes">' . TRANS('FIELD_CDROM') . ' ' . $cdromArray['mdit_fabricante'] . ' ' . $cdromArray['mdit_desc'] . ' ' . $cdromArray['mdit_desc_capacidade'] . ' ' . $cdromArray['mdit_sufixo'] . '</li>';
        }
        if (!empty(trim($recorder))) {
            $deprecatedAttributes .= '<li class="list-attributes">' . TRANS('MNL_GRAV') . ' ' . $recorderArray['mdit_fabricante'] . ' ' . $recorderArray['mdit_desc'] . ' ' . $recorderArray['mdit_desc_capacidade'] . ' ' . $recorderArray['mdit_sufixo'] . '</li>';
        }
        if (!empty(trim($dvdrom))) {
            $deprecatedAttributes .= '<li class="list-attributes">' . TRANS('DVD') . ' ' . $dvdromArray['mdit_fabricante'] . ' ' . $dvdromArray['mdit_desc'] . ' ' . $dvdromArray['mdit_desc_capacidade'] . ' ' . $dvdromArray['mdit_sufixo'] . '</li>';
        }


        $category = (isset($row['categoria_cod']) && !empty($row['categoria_cod']) ? getAssetsCategories($conn, (int)$row['categoria_cod'])['cat_name'] : '');

        /* Se for uma situação operacional marcada para destaque */
        if ($row['situac_destaque'] == '1') {
            $classHighlight = 'destaque';
        } else {
            $classHighlight = '';
        }

        ?>
        <tr>
            <td class="line <?= $classHighlight; ?>" data-sort="<?= (int)$row['etiqueta']; ?>"><span class="pointer" onClick="openAssetInfo('<?= $row['comp_cod']; ?>')"><?= "<b>" . $row['etiqueta'] . "</b>"; ?></span></td>
            <td class="line <?= $classHighlight; ?>"><?= $row['nickname'] ; ?></td>
            <td class="line <?= $classHighlight; ?>"><?= $row['instituicao'] ; ?></td>
            <td class="line <?= $classHighlight; ?>"><?= $category; ?></td>
            <td class="line <?= $classHighlight; ?>"><?= $row['equipamento'] ; ?></td>
            <td class="line <?= $classHighlight; ?>"><?= $row['fab_nome'] ; ?></td>
            <td class="line <?= $classHighlight; ?>"><?= $row['modelo'] ; ?></td>
            <td class="line <?= $classHighlight; ?>"><?= $row['serial'] ; ?></td>
            <td class="line <?= $classHighlight; ?>"><?= $row['part_number'] ; ?></td>
            <td class="line <?= $classHighlight; ?>"><?= $row['local'] ; ?></td>
            <td class="line <?= $classHighlight; ?>"><?= $row['situac_nome'] ; ?></td>
            <td class="line <?= $classHighlight; ?>"><?= $row['fornecedor_nome'] ; ?></td>
            <td class="line <?= $classHighlight; ?>"><?= $cost_center ; ?></td>
            <td class="line <?= $classHighlight; ?>"><?= priceScreen($row['valor']) ; ?></td>
            <td class="line <?= $classHighlight; ?>"><?= $row['nota'] ; ?></td>
            <td class="line <?= $classHighlight; ?>"><?= $row['assistencia'] ; ?></td>
            <td class="line <?= $classHighlight; ?>"><?= $row['tipo_garantia'] ; ?></td>
            <td class="line <?= $classHighlight; ?>" data-sort="<?= $row['vencimento']; ?>"><?= dateScreen($row['vencimento'], 1) ; ?></td>
            <td class="line <?= $classHighlight; ?>" data-sort="<?= $row['data_compra']; ?>"><?= dateScreen($row['data_compra'], 1) ; ?></td>
            
            <td class="line <?= $classHighlight; ?>"><?= $directAttributes ; ?></td>
            <td class="line <?= $classHighlight; ?>"><?= $aggregatedAttributes ; ?></td>
            <td class="line <?= $classHighlight; ?>"><?= $softAggregatedAttributes ; ?></td>

            <?php
                /* Valores do Campos customizados */
                foreach ($custom_fields_full as $cfield) {
                    
                    if (!in_array($cfield['id'], $dontRender)) {
                        $cfield_values = getAssetCustomFields($conn, $row['comp_cod'], $cfield['id']);

                        $showField = $cfield_values['field_value'];

                        if ($cfield['field_type'] == 'date') {
                            $showField = dateScreen($cfield_values['field_value'], 1);
                        } elseif ($cfield['field_type'] == 'datetime') {
                            $showField = dateScreen($cfield_values['field_value'], 0, "d/m/Y H:i");
                        }
                        ?>
                            <td class="line custom_field">
                                <?= $showField; ?>
                            </td>
                        <?php
                    }
                }
            ?>


            <!-- Itens legados - descontinuados -->
            <td class="line <?= $classHighlight; ?>"><?= $deprecatedAttributes ; ?></td>

        </tr>
        <?php
    } else {
        $totalFiltered--;
    }
}

$contextText = "";
if ($hasLimitationByAllowedClients && $hasLimitationByAllowedUnits) {
    $contextText = " <br />(". TRANS('RESULT_LIMITED_BY_PERMISSIONS').")";
}
?>
        </table>
        <?php
            if (isset($_SESSION['flash']) && !empty($_SESSION['flash'])) {
                echo $_SESSION['flash'];
                $_SESSION['flash'] = '';
            }
        ?>
        <div class="d-none" id="table_info_hidden">
            <div class="row"> <!-- d-none -->
                <div class="col-12"><?= TRANS('WERE_FOUND'); ?> <span class="bold"><?= $totalFiltered; ?></span> <?= TRANS('POSSIBLE_RECORDS_ACORDING_TO_FOLLOW'); ?> <span class="bold"><?= TRANS('SMART_SEARCH_CRITERIA'); ?>:</span><span class="small"><?= $contextText; ?></span></div>
            </div>
            <div class="row p-2 mt-2" id="divCriterios">
                <div class="col-10">
                    <?= $criterios; ?>
                </div>
            </div>

        </div>

    </div>
