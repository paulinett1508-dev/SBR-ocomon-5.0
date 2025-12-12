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

$terms = "";
$criteria = array();
$criterText = "";
$badgeClass = "badge badge-info p-2 mb-1";
$badgeClassEmptySearch = "badge badge-danger p-2 mb-1";


$imgsPath = "../../includes/imgs/";
$config = getConfig($conn);



/* Unidade */
$field_label = TRANS('COL_UNIT');
$post_field_sufix = "unidade";
$sql_column = "inst.inst_cod";
$field_table = "instituicao";
$field_id = "inst_cod";
$field_name = "inst_nome";

/* Controle para o caso da área ter limitação para visualização de apenas algumas unidades */
$hasLimitationByAllowedUnits = false;
if (!empty($_SESSION['s_allowed_units'])) {
    $hasLimitationByAllowedUnits = true;
    $terms .= " AND inst.inst_cod IN ({$_SESSION['s_allowed_units']}) ";
}

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


/* Etiqueta */
$field_label = TRANS('ASSET_TAG');
$post_field_sufix = "etiqueta";
$sql_column = "e.estoq_tag_inv";

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


/* Tipo de componente */
$field_label = TRANS('COL_TYPE');
$post_field_sufix = "equip_type";
$sql_column = "e.estoq_tipo";
$field_table = "itens";
$field_id = "item_cod";
$field_name = "item_nome";

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
$sql_column = "e.estoq_desc";
$field_table = "modelos_itens";
$field_id = "mdit_cod";
$field_name = "mdit_desc"; /* mdit_desc_capacidade, mdit_sufixo */
$field_name2 = "mdit_desc_capacidade"; /* mdit_desc_capacidade, mdit_sufixo */
$field_name3 = "mdit_sufixo"; /* mdit_desc_capacidade, mdit_sufixo */

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
    $sqlCriter = "SELECT {$field_name}, {$field_name2}, {$field_name3} FROM {$field_table} WHERE {$field_id} in ({$fieldIn}) ORDER BY {$field_name}";
    $resCriter = $conn->query($sqlCriter);
    foreach ($resCriter->fetchAll() as $rowCriter) {
        if (strlen((string)$criterText)) $criterText .= ", ";
        $criterText .= $rowCriter[$field_name] . " " . $rowCriter[$field_name2] . " " . $rowCriter[$field_name3];
    }
    $criterText = $field_label . ": " . $criterText ."<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
}



/* Serial number */
$field_label = TRANS('SERIAL_NUMBER');
$post_field_sufix = "serial_number";
$sql_column = "e.estoq_sn";

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
$sql_column = "e.estoq_partnumber";

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
$sql_column = "e.estoq_local";
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




/* Situação */
$field_label = TRANS('STATE');
$post_field_sufix = "condition";
$sql_column = "e.estoq_situac";
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
$sql_column = "e.estoq_vendor";
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
$sql_column = "e.estoq_ccusto";
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
$sql_column = "e.estoq_nf";

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


/* Assistência  - Criar Campo*/  
$field_label = TRANS('ASSISTENCE');
$post_field_sufix = "assistance";
$sql_column = "e.estoq_assist";
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


/* Tipo de garantia - adicionar campo */
$field_label = TRANS('FIELD_TYPE_WARRANTY');
$post_field_sufix = "warranty_type";
$sql_column = "e.estoq_warranty_type";
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
    $terms .= " AND (e.estoq_data_compra IS NOT NULL AND e.estoq_data_compra IS NOT NULL) ";
    $criterText = $field_label . ": " . TRANS('SMART_NOT_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post['no_' . $post_field_sufix]) && $post['no_' . $post_field_sufix] == 1) {
    $terms .= " AND (e.estoq_data_compra IS NULL OR e.estoq_data_compra IS NULL) ";
    $criterText = $field_label . ": " . TRANS('SMART_EMPTY') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";

} elseif (isset($post[$post_field_sufix]) && !empty($post[$post_field_sufix])) {
    
    $criterText = "";

    if ($post[$post_field_sufix] == 1) {
        $terms .= " AND (date_add(e.estoq_data_compra, INTERVAL t.tempo_meses month) >= now()) ";
        $criterText .= TRANS('UNDER_WARRANTY');
    } elseif ($post[$post_field_sufix] == 2) {
        $terms .= " AND (date_add(e.estoq_data_compra, INTERVAL t.tempo_meses month) < now()) ";
        $criterText .= TRANS('SEL_GUARANTEE_EXPIRED');
    }
    
    $criterText = $field_label . ": " . $criterText ."<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
}



if (isset($post['only_relatives']) && !empty($post['only_relatives'])) {

    $criterText = TRANS('SMART_ONLY_IN_EQUIPMENT') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
    $terms .= " ";
} elseif (isset($post['no_relatives']) && !empty($post['no_relatives'])) {

    $criterText = TRANS('SMART_ONLY_OUT_OF_EQUIPMENT') . "<br />";
    $criteria[] = "<span class='{$badgeClass}'>{$criterText}</span>";
    $terms .= " ";
} 



/* Data mínima de aquisição */
$field_label = TRANS('SMART_MIN_PURCHASE_DATE');
$post_field_sufix = "purchase_date_from";
$sql_column = "e.estoq_data_compra";

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
$sql_column = "e.estoq_data_compra";

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





if (empty($terms)) {
    $criterText = TRANS('SMART_WITHOUT_SEARCH_CRITERIA') . "<br />";
    $criteria[] = "<span class='{$badgeClassEmptySearch}'>{$criterText}</span>";
}

// echo $terms;

// $sql = $QRY["ocorrencias_full_ini"] . " WHERE 1 = 1 {$terms} ORDER BY numero";
// $sql = $QRY["full_detail_ini"];
// $sql = $QRY["componentexequip_ini"];
$sql = $clausule;
$sql .= $terms;
// $sql .= $QRY["full_detail_fim"];
$sql .= " ORDER BY i.item_nome, e.estoq_desc";

$sqlResult = $conn->query($sql);
$totalFiltered = $sqlResult->rowCount();

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
                    <td class='line sequence_id'>#</td>
                    <td class='line type_of'><?= TRANS('COL_TYPE'); ?></td>
                    <td class='line manufacturer'><?= TRANS('COL_MANUFACTURER'); ?></td>
                    <td class='line model'><?= TRANS('COL_MODEL'); ?></td>
                    <td class='line etiqueta'><?= TRANS('ASSET_TAG'); ?></td>
                    <td class='line unidade'><?= TRANS('COL_UNIT'); ?></td>
                    <td class='line serial_number'><?= TRANS('SERIAL_NUMBER'); ?></td>
                    <td class='line part_number'><?= TRANS('COL_PARTNUMBER'); ?></td>
                    <td class='line equipment'><?= TRANS('COL_EQUIP'); ?></td>
                    <td class='line department'><?= TRANS('DEPARTMENT'); ?></td>
                    <td class='line state'><?= TRANS('STATE'); ?></td>
                    <td class='line supplier'><?= TRANS('COL_VENDOR'); ?></td>
                    <td class='line cost_center'><?= TRANS('COST_CENTER'); ?></td>
                    <td class='line invoice_number'><?= TRANS('INVOICE_NUMBER'); ?></td>
                    <td class='line assistance'><?= TRANS('ASSISTENCE'); ?></td>
                    <td class='line warranty_type'><?= TRANS('FIELD_TYPE_WARRANTY'); ?></td>
                    <!-- <td class='line waranty_expire'><?= TRANS('WARRANTY_EXPIRE'); ?></td> -->
                    <td class='line purchase_date'><?= TRANS('PURCHASE_DATE'); ?></td>

                    
                </tr>
            </thead>
       
<?php


$i = 0;
foreach ($sqlResult->fetchAll() as $row){
    $nestedData = array(); 
    $showRecord = true;

    /* Equipamentos associados */
    if (isset($post['only_relatives']) && !empty($post['only_relatives'])) {
        if (empty($row['eqp_equip_inv'])) {
            $showRecord = false;
        }
    }

    /* Sem equipamentos associados */
    if (isset($post['no_relatives']) && !empty($post['no_relatives'])) {
        if (!empty($row['eqp_equip_inv'])) {
            $showRecord = false;
        }
    }

    if ($showRecord) {

        $i++;

        // var_dump($row['ccusto']);
        // $ccusto_array = getCostCenters($conn, $row['ccusto']);
        // $cost_center = (!empty($ccusto_array) ? $ccusto_array['ccusto_name'] . " - " . $ccusto_array['ccusto_cod'] : "");
        $cost_center = $row['ccusto'] . " - " . $row['codigo'];

        /* Se for uma situação operacional marcada para destaque */
        if ($row['situac_destaque'] == '1') {
            $classHighlight = 'destaque';
        } else {
            $classHighlight = '';
        }

        // $equipUnit = getUnitInfo($conn, $row['eqp_equip_inst']);
        $equipUnit = "";
        if (!empty($row['eqp_equip_inst']) && $row['eqp_equip_inst'] != '-1'){
            $equipUnit = getUnits($conn, 1, $row['eqp_equip_inst']);
        }
        $equipUnit = (!empty($equipUnit) && $equipUnit['inst_nome'] ? $equipUnit['inst_nome'] . " " : "");
        $equipTag = (!empty($row['eqp_equip_inv']) ? '#' . $row['eqp_equip_inv'] : "");

        ?>
        <tr>
            <td class="line <?= $classHighlight; ?>"><span class="pointer" onClick="openPeripheralInfo('<?= $row['estoq_cod']; ?>')"><?= "<b>" . $i . "</b>"; ?></span></td>
            <td class="line <?= $classHighlight; ?>"><?= $row['item_nome'] ; ?></td>
            <td class="line <?= $classHighlight; ?>"><?= $row['fab_nome'] ; ?></td>
            <td class="line <?= $classHighlight; ?>"><?= $row['modelo'] ; ?></td>
            <td class="line <?= $classHighlight; ?>" data-sort="<?= $row['estoq_tag_inv']; ?>"><?= $row['estoq_tag_inv']; ?></td>
            <td class="line <?= $classHighlight; ?>"><?= $row['inst_nome'] ; ?></td>
            <td class="line <?= $classHighlight; ?>"><?= $row['estoq_sn'] ; ?></td>
            <td class="line <?= $classHighlight; ?>"><?= $row['estoq_partnumber'] ; ?></td>
            <td class="line <?= $classHighlight; ?>" data-sort="<?= $equipTag; ?>"><span class="pointer" onClick="openEquipmentInfo('<?= $row['eqp_equip_inv']; ?>', <?= $row['eqp_equip_inst']; ?>)"><?= "<b>" . $equipUnit . $equipTag . "</b>"; ?></span></td>
            <!-- <td class="line <?= $classHighlight; ?>"><?= $equipUnit . $equipTag ; ?></td> -->
            <td class="line <?= $classHighlight; ?>"><?= $row['local'] ; ?></td>
            <td class="line <?= $classHighlight; ?>"><?= $row['situac_nome'] ; ?></td>
            <td class="line <?= $classHighlight; ?>"><?= $row['forn_nome'] ; ?></td>
            <td class="line <?= $classHighlight; ?>"><?= $cost_center ; ?></td>
            <td class="line <?= $classHighlight; ?>"><?= $row['estoq_nf'] ; ?></td>
            <td class="line <?= $classHighlight; ?>"><?= $row['assistencia'] ; ?></td>
            <td class="line <?= $classHighlight; ?>"><?= $row['tipo_garantia'] ; ?></td>
            <!-- <td class="line <?= $classHighlight; ?>" data-sort="<?= $row['vencimento']; ?>"><?= dateScreen($row['vencimento'], 1) ; ?></td> -->
            <td class="line <?= $classHighlight; ?>" data-sort="<?= $row['estoq_data_compra']; ?>"><?= dateScreen($row['estoq_data_compra'], 1) ; ?></td>
            
            
        </tr>
        <?php
    } else {
        $totalFiltered--;
    }
}

$contextText = "";
if ($hasLimitationByAllowedUnits) {
    $contextText = " <br />(". TRANS('RESULT_LIMITED_BY_PERMISSIONS').")";
}
?>
        </table>
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
