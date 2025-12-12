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

$post = $_POST;
// var_dump($post); exit;

$screenNotification = "";
$exception = "";
$data = [];
$data['success'] = true;
$data['message'] = "";
$data['cod'] = (isset($post['cod']) ? intval($post['cod']) : "");
$data['action'] = $post['action'];
$data['field_id'] = "";

$data['field_label'] = (isset($post['field_label']) ? noHtml($post['field_label']) : "");
$data['field_type'] = (isset($post['field_type']) ? noHtml($post['field_type']) : "");
$data['field_options'] = (isset($post['field_options']) ? noHtml($post['field_options']) : "");

$data['field_default_value'] = (isset($post['field_default_value']) ? $post['field_default_value'] : "");

if ($data['field_type'] == 'select_multi' && $data['field_default_value']) {
    $data['field_default_value'] = noHtml(implode(',', $data['field_default_value']));
} else {
    $data['field_default_value'] = noHtml($data['field_default_value']);
}

$data['field_table_to'] = (isset($post['field_table_to']) ? noHtml($post['field_table_to']) : "");
$data['field_title'] = (isset($post['field_title']) ? noHtml($post['field_title']) : "");
$data['field_placeholder'] = (isset($post['field_placeholder']) ? noHtml($post['field_placeholder']) : "");
$data['field_description'] = (isset($post['field_description']) ? noHtml($post['field_description']) : "");
$data['field_required'] = (isset($post['field_required']) ? ($post['field_required'] == "yes" ? 1 : 0) : 0);
$data['field_active'] = (isset($post['field_active']) ? ($post['field_active'] == "yes" ? 1 : 0) : 0);

$data['field_order'] = (isset($post['field_order']) ? noHtml($post['field_order']) : "");

$data['field_name'] = str_slug(noHtml($data['field_label']), 'cfield_'); /* prefixo para evitar que existam campos com o mesmo nome de campos já existentes */

$data['field_attributes'] = (isset($post['field_attributes']) ? noHtml($post['field_attributes']) : "");
$data['field_attributes'] = (!empty($data['field_attributes']) ? str_replace(' ', '', $data['field_attributes']): "");

$data['field_mask'] = (isset($post['field_mask']) ? noHtml(str_replace('\\', '\\\\', $post['field_mask'])) : "");
$data['field_mask_regex'] = (isset($post['field_mask_regex']) ? ($post['field_mask_regex'] == "yes" ? 1 : 0) : 0);





/* Validações */
if ($data['action'] == "new" || $data['action'] == "edit") {

    if (empty($data['field_label'])) {
        $data['success'] = false; 
        $data['field_id'] = 'field_label';
    } elseif (empty($data['field_type'])) {
        $data['success'] = false; 
        $data['field_id'] = 'field_type';
    } elseif ($data['field_type'] == 'select' && empty($data['field_options']) && $data['action'] == 'new') {
        $data['success'] = false; 
        $data['field_id'] = 'field_options';
    } elseif (empty($data['field_table_to'])) {
        $data['success'] = false; 
        $data['field_id'] = 'field_table_to';
    }


    if (!$data['success']) {
        $data['message'] = message('warning', 'Ooops!', TRANS('MSG_EMPTY_DATA'),'');
        echo json_encode($data);
        return false;
    }


    if ($data['field_type'] == 'number') {
        if ($data['field_default_value'] != "" && !filter_var($data['field_default_value'], FILTER_VALIDATE_INT)) {
            $data['success'] = false; 
            $data['field_id'] = "field_default_value";
        }
    } elseif ($data['field_type'] == 'date') {
        if ($data['field_default_value'] != "" && !isValidDate($data['field_default'], 'd/m/Y')) {
            $data['success'] = false; 
            $data['field_id'] = "field_default_value";
        }
    } elseif ($data['field_type'] == 'datetime') {
        if ($data['field_default_value'] != "" && !isValidDate($data['field_default'], 'd/m/Y H:i')) {
            $data['success'] = false; 
            $data['field_id'] = "field_default_value";
        }
    } elseif ($data['field_type'] == 'time') {
        if ($data['field_default_value'] != "" && !isValidDate($data['field_default'], 'H:i')) {
            $data['success'] = false; 
            $data['field_id'] = "field_default_value";
        }
    }


    if (!$data['success']) {
        $data['message'] = message('warning', 'Ooops!', TRANS('BAD_FIELD_FORMAT'),'');
        echo json_encode($data);
        return false;
    }
}


if ($data['action'] == 'new') {


    $sql = "SELECT id FROM custom_fields WHERE field_name = '" . $data['field_name'] . "' AND field_table_to = '" . $data['field_table_to'] ."' ";
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $data['success'] = false; 
        $data['field_id'] = $data['field_name'];
        $data['message'] = message('warning', '', TRANS('MSG_RECORD_EXISTS'), '');
        echo json_encode($data);
        return false;
    }


    if (!csrf_verify($post)) {
        $data['success'] = false; 
        $data['message'] = message('warning', 'Ooops!', TRANS('FORM_ALREADY_SENT'),'');
    
        echo json_encode($data);
        return false;
    }

    $sql = "INSERT INTO 
                custom_fields 
                (
                    field_name, 
                    field_type, 
                    field_default_value, 
                    field_required, 
                    field_table_to, 
                    field_label, 
                    field_order, 
                    field_title, 
                    field_placeholder, 
                    field_description, 
                    field_active, 
                    field_attributes, 
                    field_mask, 
                    field_mask_regex
                ) 
                VALUES 
                (
                    '" . $data['field_name'] . "', 
                    '" . $data['field_type'] . "', 
                    " . dbField($data['field_default_value'],'text') . ", 
                    '" . $data['field_required'] . "', 
                    '" . $data['field_table_to'] . "', 
                    '" . $data['field_label'] . "', 
                    " . dbField($data['field_order'],'text') . ",
                    " . dbField($data['field_title'],'text') . ", 
                    " . dbField($data['field_placeholder'],'text') . ", 
                    " . dbField($data['field_description'],'text') . ", 
                    1, 
                    " . dbField($data['field_attributes'],'text') . " ,
                    " . dbField($data['field_mask'],'text') . ",  
                    '" . $data['field_mask_regex'] . "' 
                )";


    try {
        $conn->exec($sql);
        $fieldId = $conn->lastInsertId();
        $data['success'] = true; 
        $data['message'] = TRANS('MSG_SUCCESS_INSERT');

        $options = [];
        if (!empty($data['field_options'])){
            $options = explode(',', $data['field_options']);
        }

        /* Remover duplicados */
        $options = array_unique($options, SORT_STRING);

        /* Opções de seleção */
        foreach ($options as $option) {
            $sql = "INSERT INTO custom_fields_option_values 
                (
                    custom_field_id, 
                    option_value
                )
                VALUES 
                (
                    {$fieldId},
                    '{$option}'
                )
                ";
            try {
                $conn->exec($sql);
            }
            catch (Exception $e) {
                $exception .= "<hr>" . $e->getMessage() . "<hr>" . $sql;
            }
        }


        $_SESSION['flash'] = message('success', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    } catch (Exception $e) {
        $exception .= "<hr>" . $e->getMessage() . "<hr>" . $sql;
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_SAVE_RECORD');
        $_SESSION['flash'] = message('danger', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    }

} elseif ($data['action'] == 'edit') {

    $requireOption = false;

    $sql = "SELECT id FROM custom_fields WHERE field_name = '" . $data['field_name'] . "' AND field_table_to = '" . $data['field_table_to'] ."' AND id <> '" . $data['cod'] . "' ";
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $data['success'] = false; 
        $data['field_id'] = $data['field_name'];
        $data['message'] = message('warning', '', TRANS('MSG_RECORD_EXISTS'), '');
        echo json_encode($data);
        return false;
    }


    $optionsBefore = "";
    $sql = "SELECT * FROM custom_fields_option_values WHERE custom_field_id = '" . $data['cod'] . "' ";
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        foreach($res->fetchAll() as $eachOption) {
            if (strlen((string)$optionsBefore)) $optionsBefore .= ",";
            $optionsBefore .= $eachOption['option_value'];
        }
    } elseif ($data['field_type'] == 'select') {
        $data['success'] = false; 
        $data['field_id'] = 'field_options';
        $data['message'] = message('warning', '', TRANS('MSG_EMPTY_DATA'), '');
        echo json_encode($data);
        return false;
    }
    

    if (!csrf_verify($post)) {
        $data['success'] = false; 
        $data['message'] = message('warning', 'Ooops!', TRANS('FORM_ALREADY_SENT'),'');
    
        echo json_encode($data);
        return false;
    }

    $sql = "UPDATE custom_fields SET 
                field_name = '" . $data['field_name'] . "', 
                field_type = '" . $data['field_type'] . "', 
                field_default_value = " . dbField($data['field_default_value'],'text') . ", 
                field_required = " . $data['field_required'] . ", 
                field_table_to = '" . $data['field_table_to'] . "', 
                field_label = '" . $data['field_label'] . "', 
                field_order =  " . dbField($data['field_order'],'text') . ", 
                field_title =  " . dbField($data['field_title'],'text') . ", 
                field_placeholder =  " . dbField($data['field_placeholder'],'text') . ", 
                field_description =  " . dbField($data['field_description'],'text') . ", 
                field_active = '" . $data['field_active'] . "', 
                field_attributes =  " . dbField($data['field_attributes'],'text') . ", 
                field_mask =  " . dbField($data['field_mask'],'text') . ", 
                field_mask_regex = " . $data['field_mask_regex'] . " 
                
            WHERE id = '" . $data['cod'] . "'";


    try {
        $conn->exec($sql);
        $data['success'] = true; 
        $data['message'] = TRANS('MSG_SUCCESS_EDIT');

        $options = [];
        if (!empty($data['field_options'])){
            $options = explode(',', $data['field_options']);
        }

        /* Pegar as opções já gravadas para comparar com as novas opções para não repetir nenhuma */
        $sql = "SELECT option_value FROM custom_fields_option_values WHERE custom_field_id = '" . $data['cod'] . "'";
        $res = $conn->query($sql);
        $optionsRecorded = [];
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $recorded) {
                $optionsRecorded[] = mb_strtolower($recorded['option_value']);
            }
        }

        // var_dump($optionsRecorded); exit;
        /* Opções de seleção */
        foreach ($options as $option) {
            if (!in_array(mb_strtolower($option), $optionsRecorded)) {
                $sql = "INSERT INTO custom_fields_option_values 
                (
                    custom_field_id, 
                    option_value
                )
                VALUES 
                (
                    '" . $data['cod'] . "',
                    '{$option}'
                )
                ";
                try {
                    $conn->exec($sql);
                } catch (Exception $e) {
                    $exception .= "<hr>" . $e->getMessage() . "<hr>" . $sql;
                }
            }
            
        }

        
    } catch (Exception $e) {
        $exception .= "<hr>" . $e->getMessage() . "<hr>" . $sql;
    }


    $_SESSION['flash'] = message('success', '', $data['message'] . $exception, '');
    echo json_encode($data);
    return false;
    

} elseif ($data['action'] == 'delete') {

    /* Ver que checagens serão necessárias para remover uma opção - Se a opção estiver sendo utilizada não permitir a remoção */
    /* Criar um array indicando que tabelas devem ser checadas a partir da field_table_to (como chave) ex: tickets_x_cfields */

    /* Tabela e campo de ID do campo personalizado */
    $tables_to_check = [
        'tickets_x_cfields' => 'cfield_id'
    ];

    $availableToDelete = true;

    foreach ($tables_to_check as $table => $field) {
        $sql = "SELECT ticket FROM {$table} WHERE {$field} = '" . $data['cod'] . "' ";
        try {
            $res = $conn->query($sql);
            if ($res->rowCount()) {
                $availableToDelete = false;
            }
        }
        catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage();
        }
    }


    if (!$availableToDelete) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_CANT_DEL');
        $_SESSION['flash'] = message('danger', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    }


    /* Caso não existam restrições para excluir o campo */
    $sql = "DELETE FROM custom_fields WHERE id = '" . $data['cod'] . "'";

    try {
        $conn->exec($sql);
        $data['success'] = true; 
        $data['message'] = TRANS('OK_DEL');

        /* Remove as opções associadas */
        $sql = "DELETE FROM custom_fields_option_values WHERE custom_field_id = '" . $data['cod'] . "'";
        try {
            $conn->exec($sql);
        }
        catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage() . "<hr>" . $sql;
        }

        $_SESSION['flash'] = message('success', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    } catch (Exception $e) {
        $exception .= "<hr>" . $e->getMessage() . "<hr>" . $sql;
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_DATA_REMOVE');
        $_SESSION['flash'] = message('danger', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    }
    
}

echo json_encode($data);