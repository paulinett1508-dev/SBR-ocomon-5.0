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


$erro = false;
$exception = "";
$mensagem = "";
$data = [];


$data['success'] = true;
$data['message'] = "";
$data['cod'] = (isset($post['cod']) ? intval($post['cod']) : "");
$data['action'] = $post['action'];
$data['field_id'] = "";
$data['field_custom_ids'] = "";
$data['field_specs_ids'] = "";

$data['profile_name'] = (isset($post['profile_name']) ? noHtml($post['profile_name']) : "");
$data['applied_to'] = (isset($post['applied_to']) && !empty(array_filter($post['applied_to'], function($v) { return !empty($v); })) ? array_map('noHtml', $post['applied_to']) : []);

// $data['applied_to_ids'] = (!empty($data['applied_to']) ? implode(",", $data['applied_to']) : "");

$data['field_serial_number'] = (isset($post['field_serial_number']) ? ($post['field_serial_number'] == "yes" ? 1 : 0) : 0);
$data['field_part_number'] = (isset($post['field_part_number']) ? ($post['field_part_number'] == "yes" ? 1 : 0) : 0);
$data['field_situation'] = (isset($post['field_situation']) ? ($post['field_situation'] == "yes" ? 1 : 0) : 0);
$data['field_net_name'] = (isset($post['field_net_name']) ? ($post['field_net_name'] == "yes" ? 1 : 0) : 0);
$data['field_invoice_number'] = (isset($post['field_invoice_number']) ? ($post['field_invoice_number'] == "yes" ? 1 : 0) : 0);
$data['field_cost_center'] = (isset($post['field_cost_center']) ? ($post['field_cost_center'] == "yes" ? 1 : 0) : 0);
$data['field_price'] = (isset($post['field_price']) ? ($post['field_price'] == "yes" ? 1 : 0) : 0);
$data['field_buy_date'] = (isset($post['field_buy_date']) ? ($post['field_buy_date'] == "yes" ? 1 : 0) : 0);
$data['field_supplier'] = (isset($post['field_supplier']) ? ($post['field_supplier'] == "yes" ? 1 : 0) : 0);
$data['field_assistance_type'] = (isset($post['field_assistance_type']) ? ($post['field_assistance_type'] == "yes" ? 1 : 0) : 0);
$data['field_warranty_type'] = (isset($post['field_warranty_type']) ? ($post['field_warranty_type'] == "yes" ? 1 : 0) : 0);
$data['field_warranty_time'] = (isset($post['field_warranty_time']) ? ($post['field_warranty_time'] == "yes" ? 1 : 0) : 0);
$data['field_extra_info'] = (isset($post['field_extra_info']) ? ($post['field_extra_info'] == "yes" ? 1 : 0) : 0);



/* Sobre a obrigatoriedade */
$data['field_serial_number_required'] = (isset($post['field_serial_number_required']) ? ($post['field_serial_number_required'] == "on" ? 1 : 0) : 0);
$data['field_part_number_required'] = (isset($post['field_part_number_required']) ? ($post['field_part_number_required'] == "on" ? 1 : 0) : 0);
$data['field_situation_required'] = (isset($post['field_situation_required']) ? ($post['field_situation_required'] == "on" ? 1 : 0) : 0);
$data['field_net_name_required'] = (isset($post['field_net_name_required']) ? ($post['field_net_name_required'] == "on" ? 1 : 0) : 0);
$data['field_invoice_number_required'] = (isset($post['field_invoice_number_required']) ? ($post['field_invoice_number_required'] == "on" ? 1 : 0) : 0);
$data['field_cost_center_required'] = (isset($post['field_cost_center_required']) ? ($post['field_cost_center_required'] == "on" ? 1 : 0) : 0);
$data['field_price_required'] = (isset($post['field_price_required']) ? ($post['field_price_required'] == "on" ? 1 : 0) : 0);
$data['field_buy_date_required'] = (isset($post['field_buy_date_required']) ? ($post['field_buy_date_required'] == "on" ? 1 : 0) : 0);
$data['field_supplier_required'] = (isset($post['field_supplier_required']) ? ($post['field_supplier_required'] == "on" ? 1 : 0) : 0);
$data['field_assistance_type_required'] = (isset($post['field_assistance_type_required']) ? ($post['field_assistance_type_required'] == "on" ? 1 : 0) : 0);
$data['field_warranty_type_required'] = (isset($post['field_warranty_type_required']) ? ($post['field_warranty_type_required'] == "on" ? 1 : 0) : 0);
$data['field_warranty_time_required'] = (isset($post['field_warranty_time_required']) ? ($post['field_warranty_time_required'] == "on" ? 1 : 0) : 0);
$data['field_extra_info_required'] = (isset($post['field_extra_info_required']) ? ($post['field_extra_info_required'] == "on" ? 1 : 0) : 0);



/* Para inserções sobre a obrigatoriedade dos campos */
$fields_required = [
	'asset_type' => 1,
	'manufacturer' => 1,
	'model' => 1,
	'asset_unit' => 1,
	'asset_tag' => 1,
	'department' => 1,
	'serial_number' => $data['field_serial_number_required'],
	'part_number' => $data['field_part_number_required'],
	'situation' => $data['field_situation_required'],
	'net_name' => $data['field_net_name_required'],
	'invoice_number' => $data['field_invoice_number_required'],
	'cost_center' => $data['field_cost_center_required'],
	'price' => $data['field_price_required'],
	'buy_date' => $data['field_buy_date_required'],
	'supplier' => $data['field_supplier_required'],
	'assistance_type' => $data['field_assistance_type_required'],
	'warranty_type' => $data['field_warranty_type_required'],
	'warranty_time' => $data['field_warranty_time_required'],
	'extra_info' => $data['field_extra_info_required']
];




/* Campos de especificação */
$dataSpecs = [];
$dataSpecs = getPossibleChildsFromManyAssetsTypes($conn, $data['applied_to']);

$specs_ids = [];
if (count($dataSpecs)) {
    foreach ($dataSpecs as $spec) {
        if (isset($post[str_slug($spec['tipo_nome'],"spec_")]) && $post[str_slug($spec['tipo_nome'],"spec_")] == "yes") {
            $specs_ids[] = $spec['tipo_cod'];
        }
    }
}
/* Composição dos IDs dos campos de especificação */
if (count($specs_ids)) {
    $data['field_specs_ids'] = implode(',', $specs_ids);
}



/* Campos personalizados - apenas ativos*/
$dataCustom = [];
$sql = "SELECT * FROM custom_fields WHERE field_table_to = 'equipamentos' AND field_active = 1";
try {
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        foreach ($res->fetchAll() as $cfield) {
            $dataCustom[] = $cfield;
        }
    }
}
catch (Exception $e) {
    $exception .= "<hr>" . $e->getMessage();
}

/* Composição dos IDs dos campos personalizados */
$fields_ids = [];
if (count($dataCustom)) {
    foreach ($dataCustom as $cfield) {
        if (isset($post[$cfield['field_name']]) && $post[$cfield['field_name']] == "yes") {
            $fields_ids[] = $cfield['id'];
        }
    }
}
if (count($fields_ids)) {
    $data['field_custom_ids'] = implode(',', $fields_ids);
}



/* Checar se pelo menos um campo personalizado foi selecionado */
$minCustomFields = false;
if (!empty($data['field_custom_ids'])) {
    $minCustomFields = true;
}

$screenNotification = "";


/* Validações */
if ($data['action'] == "new" || $data['action'] == "edit") {

    if (empty($data['profile_name']) || empty($data['applied_to'])) {
        $data['success'] = false; 
        $data['field_id'] = (empty($data['profile_name']) ? 'profile_name' : 'applied_to');
        $data['message'] = message('warning', 'Ooops!', TRANS('MSG_EMPTY_DATA'),'');
        echo json_encode($data);
        return false;
    }

    // if (!$minDefaultFields && !$minCustomFields) {
    //     $data['success'] = false; 
    //     $data['field_id'] = '';
    //     $data['message'] = message('warning', 'Ooops!', TRANS('AT_LEAST_FIELD'),'');
    //     echo json_encode($data);
    //     return false;
    // }
}


// var_dump($data); exit;

if ($data['action'] == 'edit') {

    $sql = "SELECT id FROM assets_fields_profiles WHERE profile_name = '" . $data['profile_name'] . "' AND 
    id <> '" . $data['cod'] . "' ";
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $data['success'] = false; 
        $data['field_id'] = "profile_name";
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

    $sql = "UPDATE assets_fields_profiles SET 
				profile_name = '" . $data['profile_name'] . "', 
				serial_number = '" . $data['field_serial_number'] . "', 
				part_number = '" . $data['field_part_number'] . "', 
				situation = '" . $data['field_situation'] . "', 
				net_name = '" . $data['field_net_name'] . "', 
				invoice_number = '" . $data['field_invoice_number'] . "', 
				cost_center = '" . $data['field_cost_center'] . "', 
				price = '" . $data['field_price'] . "', 
				buy_date = '" . $data['field_buy_date'] . "', 
				supplier = '" . $data['field_supplier'] . "', 
				assistance_type = '" . $data['field_assistance_type'] . "', 
				warranty_type = '" . $data['field_warranty_type'] . "', 
				warranty_time = '" . $data['field_warranty_time'] . "', 
				extra_info = '" . $data['field_extra_info'] . "', 
				field_specs_ids = " . dbField($data['field_specs_ids'],'text') . " , 
				field_custom_ids = " . dbField($data['field_custom_ids'],'text') . "  

				WHERE id = " . $data['cod'] . " ";
    try {
        $conn->exec($sql);
        $data['success'] = true; 
        $data['message'] = TRANS('MSG_SUCCESS_EDIT');



        /* Primeiro removo da tabela de relacionamentos entre perfis e tipos de ativos */
        $sql = "DELETE from profiles_x_assets_types WHERE profile_id =  ' " . $data['cod'] . "' ";
        try {
            $conn->exec($sql);

            /* Inserção na tabela de relacionamento entre perfis e tipos de ativos */
            foreach ($data['applied_to'] as $apliedTo) {
                $sql = "INSERT INTO profiles_x_assets_types 
                        (
                            profile_id, 
                            asset_type_id
                        )
                        VALUES
                        (
                            '" . $data['cod'] . "', 
                            '" . $apliedTo . "'
                        )
                        ";
                try {
                    $conn->exec($sql);
                }
                catch (Exception $e) {
                    $exception .= "<hr>" . $e->getMessage();
                }
            }
        }
        catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage();
        }



        /* Obrigatoriedade dos campos */
        $sql = "DELETE FROM assets_fields_required WHERE profile_id = '" . $data['cod'] . "' ";
        try {
            $conn->exec($sql);

            /* Foreach para inserir a obrigatoriedade dos campos da tela de abertura */
            foreach ($fields_required as $field_name => $required) {
                $sql = "INSERT INTO assets_fields_required 
                        (
                            profile_id, 
                            field_name,
                            field_required
                        )
                        VALUES
                        (
                            '" . $data['cod'] . "', 
                            '" . $field_name . "', 
                            '" . $required . "'
                        )
                        ";
                try {
                    $conn->exec($sql);
                }
                catch (Exception $e) {
                    $exception .= "<hr>" . $e->getMessage();
                }
            }
        }
        catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage();
        }
        
        

        $_SESSION['flash'] = message('success', '', $data['message'], '');
        echo json_encode($data);
        return false;
    } catch (Exception $e) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_DATA_UPDATE');
        $_SESSION['flash'] = message('danger', '', $data['message'], '');
        echo json_encode($data);
        return false;
    }

} elseif ($data['action'] == 'new') {

    $sql = "SELECT id FROM assets_fields_profiles WHERE profile_name = '" . $data['profile_name'] . "' ";
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $data['success'] = false; 
        $data['field_id'] = "profile_name";
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

    
    $sql = "INSERT INTO assets_fields_profiles 
            (
                profile_name, serial_number, 
                part_number, situation, net_name, 
                invoice_number, cost_center, price, 
                buy_date, supplier, assistance_type, 
                warranty_type, warranty_time, extra_info, 
                field_specs_ids, field_custom_ids
            ) 
            VALUES 
            (
                '" . $data['profile_name'] . "', " . $data['field_serial_number'] . ", 
                " . $data['field_part_number'] . ", " . $data['field_situation'] . ", " . $data['field_net_name'] . ", 
                " . $data['field_invoice_number'] . ", " . $data['field_cost_center'] . ", " . $data['field_price'] . ", 
                " . $data['field_buy_date'] . ", " . $data['field_supplier'] . ", " . $data['field_assistance_type'] . ", 
                " . $data['field_warranty_type'] . ", " . $data['field_warranty_time'] . ", " . $data['field_extra_info'] . ", 
                " . dbField($data['field_specs_ids'],'text') . ", " . dbField($data['field_custom_ids'],'text') . "
            )";


    try {
        $conn->exec($sql);
        $data['success'] = true; 
        $data['message'] = TRANS('MSG_SUCCESS_INSERT');
        
        $data['cod'] = $conn->lastInsertId();


        /* Inserção na tabela de relacionamento entre perfis e tipos de ativos */
        foreach ($data['applied_to'] as $apliedTo) {
            $sql = "INSERT INTO profiles_x_assets_types 
                    (
                        profile_id, 
                        asset_type_id
                    )
                    VALUES
                    (
                        '" . $data['cod'] . "', 
                        '" . $apliedTo . "'
                    )
                    ";
            try {
                $conn->exec($sql);
            }
            catch (Exception $e) {
                $exception .= "<hr>" . $e->getMessage();
            }
        }



        
        /* Foreach para inserir a obrigatoriedade dos campos da tela de abertura */
        foreach ($fields_required as $field_name => $required) {
            $sql = "INSERT INTO assets_fields_required 
                    (
                        profile_id, 
                        field_name,
                        field_required
                    )
                    VALUES
                    (
                        '" . $data['cod'] . "', 
                        '" . $field_name . "', 
                        '" . $required . "'
                    )
                    ";
            try {
                $conn->exec($sql);
            }
            catch (Exception $e) {
                $exception .= "<hr>" . $e->getMessage();
            }
        }

        
        
        $_SESSION['flash'] = message('success', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    } catch (Exception $e) {
        $exception .= "<hr>" . $e->getMessage();
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_SAVE_RECORD');
        $_SESSION['flash'] = message('danger', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    }

} elseif ($data['action'] == 'delete') {


    $sql = "SELECT id FROM assets_categories WHERE cat_default_profile = '" . $data['cod'] . "'";
    $res = $conn->query($sql);
    $achou = $res->rowCount();

    if ($achou) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_CANT_DEL');
        $_SESSION['flash'] = message('danger', '', $data['message'], '');
        echo json_encode($data);
        return false;
    }

    $sql =  "DELETE FROM assets_fields_profiles WHERE id = '".$data['cod']."'";

    try {
        $conn->exec($sql);
        $data['success'] = true; 
        $data['message'] = TRANS('OK_DEL');


        $sql = "DELETE FROM assets_fields_required WHERE profile_id = '" . $data['cod'] . "' ";
        try {
            $conn->exec($sql);
        }
        catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage();
        }


        $_SESSION['flash'] = message('success', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    } catch (Exception $e) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_DATA_REMOVE');
        $_SESSION['flash'] = message('danger', '', $data['message'], '');
        echo json_encode($data);
        return false;
    }
    
}

echo json_encode($data);