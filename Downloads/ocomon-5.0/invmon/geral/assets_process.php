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

$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 2, 2);

$post = $_POST;

// var_dump($post); exit;


$config = getConfig($conn);

/* Para manter a compatibilidade com versões antigas */
$table = "equipxpieces";
$sqlTest = "SELECT * FROM {$table}";
try {
    $conn->query($sqlTest);
}
catch (Exception $e) {
    $table = "equipXpieces";
}


$exception = "";
$screenNotification = "";
$data = [];
$data['success'] = true;
$data['message'] = "";
$data['cod'] = (isset($post['cod']) ? intval($post['cod']) : "");
$data['action'] = (isset($post['action']) ? noHtml($post['action']) : "");
$data['field_id'] = "";
$data['profile_id'] = (isset($post['profile_id']) ? noHtml($post['profile_id']) : "");


/* Campos comuns a todos os tipos de ativos */
$data['asset_type'] = (isset($post['asset_type']) ? noHtml($post['asset_type']) : "");    
$data['manufacturer'] = (isset($post['manufacturer']) ? noHtml($post['manufacturer']) : "");
$data['model'] = (isset($post['model']) ? noHtml($post['model']) : "");
$data['client'] = (isset($post['client']) ? noHtml($post['client']) : "");
$data['asset_unit'] = (isset($post['asset_unit']) ? noHtml($post['asset_unit']) : "");
$data['asset_tag'] = (isset($post['asset_tag']) ? noHtml($post['asset_tag']) : "");
$data['department'] = (isset($post['department']) ? noHtml($post['department']) : "");
$data['serial_number'] = (isset($post['serial_number']) ? noHtml($post['serial_number']) : "");
$data['part_number'] = (isset($post['part_number']) ? noHtml($post['part_number']) : "");
$data['net_name'] = (isset($post['net_name']) && !empty($post['net_name']) ? str_slug($post['net_name']) : "");
$data['invoice_number'] = (isset($post['invoice_number']) ? noHtml($post['invoice_number']) : "");
$data['cost_center'] = (isset($post['cost_center']) ? noHtml($post['cost_center']) : "");
$data['situation'] = (isset($post['situation']) ? noHtml($post['situation']) : "");
$data['price'] = (isset($post['price']) && !empty($post['price']) ? (float)priceDB(noHtml($post['price'])) : "");
$data['buy_date'] = (isset($post['buy_date']) && !empty($post['buy_date']) ? dateDB(noHtml($post['buy_date'])) : "");
$data['supplier'] = (isset($post['supplier']) ? noHtml($post['supplier']) : "");
$data['assistance_type'] = (isset($post['assistance_type']) ? noHtml($post['assistance_type']) : "");
$data['warranty_type'] = (isset($post['warranty_type']) ? noHtml($post['warranty_type']) : "");
$data['warranty_time'] = (isset($post['warranty_time']) ? noHtml($post['warranty_time']) : "");
$data['extra_info'] = (isset($post['extra_info']) ? noHtml($post['extra_info']) : "");
/* Apenas para exclusão de arquivos vinculados ao ativo */
$data['total_files_to_deal'] = (isset($post['cont']) ? noHtml($post['cont']) : 0);
/* Final dos campos comuns a todos os tipos de ativos */

$data['has_parent'] = (isset($post['has_parent']) ? ($post['has_parent'] == "yes" ? 1 : 0) : 0);
$data['parent_asset_tag'] = (isset($post['parent_asset_tag']) ? noHtml($post['parent_asset_tag']) : "");



/* Campos legados - Descontinuados */
$data['motherboard'] = (isset($post['motherboard']) ? noHtml($post['motherboard']) : "");
$data['processor'] = (isset($post['processor']) ? noHtml($post['processor']) : "");
$data['memory'] = (isset($post['memory']) ? noHtml($post['memory']) : "");
$data['video'] = (isset($post['video']) ? noHtml($post['video']) : "");
$data['sound'] = (isset($post['sound']) ? noHtml($post['sound']) : "");
$data['network'] = (isset($post['network']) ? noHtml($post['network']) : "");
$data['modem'] = (isset($post['modem']) ? noHtml($post['modem']) : "");
$data['hdd'] = (isset($post['hdd']) ? noHtml($post['hdd']) : "");
$data['recorder'] = (isset($post['recorder']) ? noHtml($post['recorder']) : "");
$data['cdrom'] = (isset($post['cdrom']) ? noHtml($post['cdrom']) : "");
$data['dvdrom'] = (isset($post['dvdrom']) ? noHtml($post['dvdrom']) : "");
$data['printer_type'] = (isset($post['printer_type']) ? noHtml($post['printer_type']) : "");
$data['monitor_size'] = (isset($post['monitor_size']) ? noHtml($post['monitor_size']) : "");
$data['scanner_resolution'] = (isset($post['scanner_resolution']) ? noHtml($post['scanner_resolution']) : "");
/* Final dos campos legados */




/* Campos adicionais de especificação - do mesmo tipo dos permitidos no perfil */
$data['spec_extra'] = (isset($post['spec_extra']) && !empty(array_filter($post['spec_extra'], function($v) { return !empty($v); })) ? array_map('noHtml', $post['spec_extra']) : []);
$data['spec_extra_model'] = (isset($post['spec_extra_model']) && !empty(array_filter($post['spec_extra_model'], function($v) { return !empty($v); })) ? array_map('noHtml', $post['spec_extra_model']) : []);



if ($data['action'] == 'edit') {
    /* Para definir os campos de obrigatoriedade baseados no perfil */
    $asset_type_info = getAssetsTypes($conn, $data['asset_type']);
    $data['profile_id'] = $asset_type_info['profile_id'];
    $data['category_id'] = $asset_type_info['id'];

    if (empty($data['profile_id'])) {
        /* Se o tipo de ativo não tiver perfil de cadastro associado então utilizo o perfil de cadastro da categoria do ativo - se tiver perfil */
        $categorie = getAssetsCategories($conn, $data['category_id']);

        if (!empty($categorie)) {
            // $data['profile_id'] = $categorie['cat_default_profile'];
            $data['profile_id'] = (array_key_exists('cat_default_profile', $categorie) ? $categorie['cat_default_profile'] : "");
        }
    }

    // if (empty($data['profile_id'])) {
    //     /* Não tem perfil associado ao tipo de ativo e nem perfil associado à categoria do ativo */
    //     $data['profile_id'] = 0;
    // }
}





/* Campos disponíveis de acordo com o perfil informado ou então o perfil básico comum a todos os tipos de ativos */
$profile_fields = (!empty($data['profile_id']) ? getAssetsProfiles($conn, $data['profile_id']) : []);
$profile_fields = (!empty($profile_fields) ? $profile_fields : setBasicProfile());



/* Validar preenchimento dos campos de acordo com a obrigatoriedade definida para o perfil ou para perfil padrão*/
if ($data['action'] == "new" || $data['action'] == "edit") {
    /* Recebe os valores de obrigatorieda para cada campo onde se aplica */
	$required_fields = (!empty($data['profile_id']) ? getFormRequiredInfo($conn, $data['profile_id'], 'assets_fields_required') : '');
    if (empty($required_fields)) {
        $required_fields = setBasicRequired();
    }

    /* Validação dos campos obrigatórios */
    $fields_names = [];
    $fields_names['asset_unit'] = TRANS('COL_UNIT');
    $fields_names['asset_tag'] = TRANS('ASSET_TAG');
    $fields_names['department'] = TRANS('DEPARTMENT');
    foreach ($required_fields as $field => $value) {
        if ($value == 1 && empty($data[$field])) {
            $data['success'] = false;

            $field_name = (array_key_exists($field, $fields_names) ? $fields_names[$field] : "");

            $data['message'] = message('warning', '', TRANS('MSG_EMPTY_DATA') . '<hr />' . $field_name, '');
            $data['field_id'] = $field;
            break;
        }
    }

    if ($data['success'] == false) {
        echo json_encode($data);
        return false;
    }


    if (strpos($data['asset_tag'], " ")) {
        $data['success'] = false; 
        $data['field_id'] = "asset_tag";

        $data['message'] = message('warning', '', TRANS('MSG_ERROR_WRONG_FORMATTED'), '');
        echo json_encode($data);
        return false;
    }


    /* Checagem do número de série */
    if (!empty($data['serial_number'])) {
            
        $terms = ($data['action'] == "edit" ? " AND comp_cod <> '" . $data['cod'] . "' " : "");
        
        $sql = "SELECT comp_cod FROM equipamentos WHERE comp_marca = '" . $data['model'] . "' 
                AND comp_sn = '" . $data['serial_number'] . "' {$terms} ";
        $res = $conn->query($sql);
        if ($res->rowCount()) {
            $data['success'] = false; 
            $data['field_id'] = "serial_number";
            $data['message'] = message('warning', '', TRANS('MSG_SERIAL_NUMBER_CAD_IN_SYSTEM'), '');
            echo json_encode($data);
            return false;
        }
    }

    /* Checagem da etiqueta e unidade para equipamentos existentes - VER PARA CONSIDERAR O CLIENTE */
    $terms = ($data['action'] == "edit" ? " AND comp_cod <> '" . $data['cod'] . "' " : "");
    $sql = "SELECT comp_cod FROM equipamentos WHERE 
            comp_inv = '" . $data['asset_tag'] . "' AND 
            comp_inst = '" . $data['asset_unit'] . "' 
            {$terms}";
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $data['success'] = false; 
        $data['field_id'] = "asset_tag";
        $data['message'] = message('warning', '', TRANS('MSG_RECORD_EXISTS_WITH_THIS_TAG'), '');
        echo json_encode($data);
        return false;
    }

    /* Checagem da etiqueta e unidade para componentes avulsos existentes*/
    $sql = "SELECT estoq_cod FROM estoque WHERE estoq_tag_inv = '".$data['asset_tag']."' AND 
                estoq_tag_inst = '".$data['asset_unit']."' ";
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $data['success'] = false; 
        $data['field_id'] = "asset_tag";
        $data['message'] = message('warning', '', TRANS('MSG_RECORD_EXISTS_WITH_THIS_TAG'), '');
        echo json_encode($data);
        return false;
    }


    if ($data['price'] != "" && $data['price'] != 0 && !filter_var($data['price'], FILTER_VALIDATE_FLOAT)) {
        $data['success'] = false; 
        $data['field_id'] = "price";
        $data['message'] = message('warning', '', TRANS('MSG_ERROR_WRONG_FORMATTED'), '');
        echo json_encode($data);
        return false;
    }


    if ($data['has_parent'] && empty($data['parent_asset_tag'])) {
        $data['success'] = false;
        $data['message'] = message('warning', '', TRANS('MSG_EMPTY_DATA'), '');
        $data['field_id'] = 'parent_asset_tag';
        echo json_encode($data);
        return false;
    }

}

/* Os campos de especificação não sao obrigatórios - Portanto não precisam de validação */


/* Tratar e validar os campos personalizados - todos os actions */
$dataCustom = [];
$fields_ids = [];
/* No caso de cadastro, restringe aos campos extras existentes no perfil de cadastro */
if ($profile_fields['field_custom_ids'] || $data['action'] != 'new') {
    
    $fields_ids = ($profile_fields['field_custom_ids'] ? explode(',', $profile_fields['field_custom_ids']) : []);
    
    $cfields = getCustomFields($conn, null, 'equipamentos');
    
    foreach ($cfields as $cfield) {
        
        if ($data['action'] != 'new' || in_array($cfield['id'], $fields_ids) ) {
            

            /* Seleção multipla vazia */
            if (($cfield['field_type'] == 'select_multi') && !isset($post[$cfield['field_name']])) {
                $post[$cfield['field_name']] = '';
            }

            
            $dataCustom[] = $cfield; /* Guardado para a área de inserção/atualização */
            
            /* Para possibilitar o Controle de acordo com a opção global conf_cfield_only_opened */
            $field_value = [];
            $field_value['field_id'] = "";
            if ($data['action'] != 'new') {
                $field_value = getAssetCustomFields($conn, $data['cod'], $cfield['id']);
            }
            
            /* Controle de acordo com a opção global conf_cfield_only_opened */
            if (($data['action'] == 'new' || !empty($field_value['field_id'])) && $data['action'] != 'delete') {

                if (empty($post[$cfield['field_name']]) && $cfield['field_required']) {
                    $data['success'] = false;
                    $data['field_id'] = $cfield['field_name'];
                    $data['message'] = message('warning', '', TRANS('MSG_EMPTY_DATA'), '');
                    echo json_encode($data);
                    return false;
                }

                if ($cfield['field_type'] == 'number') {
                    if ($post[$cfield['field_name']] != "" && !filter_var($post[$cfield['field_name']], FILTER_VALIDATE_INT)) {
                        $data['success'] = false; 
                        $data['field_id'] = $cfield['field_name'];
                    }
                } elseif ($cfield['field_type'] == 'date') {
                    if ($post[$cfield['field_name']] != "" && !isValidDate($post[$cfield['field_name']], 'd/m/Y')) {
                        $data['success'] = false; 
                        $data['field_id'] = $cfield['field_name'];
                    }
                } elseif ($cfield['field_type'] == 'datetime') {
                    if ($post[$cfield['field_name']] != "" && !isValidDate($post[$cfield['field_name']], 'd/m/Y H:i')) {
                        $data['success'] = false; 
                        $data['field_id'] = $cfield['field_name'];
                    }
                } elseif ($cfield['field_type'] == 'time') {
                    if ($post[$cfield['field_name']] != "" && !isValidDate($post[$cfield['field_name']], 'H:i')) {
                        $data['success'] = false; 
                        $data['field_id'] = $cfield['field_name'];
                    }
                } elseif ($cfield['field_type'] == 'checkbox') {
                    /* Ver se precisa desenvover */
                } elseif ($post[$cfield['field_name']] != "" && $cfield['field_type'] == 'text' && !empty($cfield['field_mask'] && $cfield['field_mask_regex'])) {
                    /* Validar a expressão regular */
                    if (!preg_match('/' . $cfield['field_mask'] . '/i', $post[$cfield['field_name']])) {
                        $data['success'] = false; 
                        $data['field_id'] = $cfield['field_name'];
                    }
                }
                
                if (!$data['success']) {
                    $data['message'] = message('warning', 'Ooops!', TRANS('BAD_FIELD_FORMAT'),'');
                    echo json_encode($data);
                    return false;
                }
            }
        }
    }
}


/* Checagens para upload de arquivos - vale para todos os actions */
$totalFiles = ($_FILES ? count($_FILES['anexo']['name']) : 0);
$filesClean = [];
if ($totalFiles > $config['conf_qtd_max_anexos']) {

    $data['success'] = false; 
    $data['message'] = message('warning', 'Ooops!', 'Too many files','');
    echo json_encode($data);
    return false;
}

$uploadMessage = "";
$emptyFiles = 0;
/* Testa os arquivos enviados para montar os índices do recordFile*/
if ($totalFiles) {
    foreach ($_FILES as $anexo) {
        $file = array();
        for ($i = 0; $i < $totalFiles; $i++) {
            /* fazer o que precisar com cada arquivo */
            /* acessa:  $anexo['name'][$i] $anexo['type'][$i] $anexo['tmp_name'][$i] $anexo['size'][$i]*/
            if (!empty($anexo['name'][$i])) {
                $file['name'] =  $anexo['name'][$i];
                $file['type'] =  $anexo['type'][$i];
                $file['tmp_name'] =  $anexo['tmp_name'][$i];
                $file['error'] =  $anexo['error'][$i];
                $file['size'] =  $anexo['size'][$i];

                $upld = upload('anexo', $config, $config['conf_upld_file_types'], $file);
                if ($upld == "OK") {
                    $recordFile[$i] = true;
                    $filesClean[] = $file;
                } else {
                    $recordFile[$i] = false;
                    $uploadMessage .= $upld;
                }
            } else {
                $emptyFiles++;
            }
        }
    }
    $totalFiles -= $emptyFiles;
    
    if (strlen((string)$uploadMessage) > 0) {
        $data['success'] = false; 
        $data['field_id'] = "idInputFile";
        $data['message'] = message('warning', 'Ooops!', $uploadMessage, '');
        echo json_encode($data);
        return false;                
    }
}



/* Processamento */
if ($data['action'] == "new") {

    /* Verificação de CSRF */
    if (!csrf_verify($post)) {
        $data['success'] = false; 
        $data['message'] = message('warning', 'Ooops!', TRANS('FORM_ALREADY_SENT'),'');
        echo json_encode($data);
        return false;
    }


    $beforeValues = [];

	$sql = "INSERT INTO equipamentos 
        (
            comp_tipo_equip, comp_fab, comp_marca, 
            comp_inst, comp_local, comp_inv, 
            comp_nome, 
            comp_sn, comp_part_number, 
            comp_nf, comp_fornecedor,
            comp_valor, comp_data_compra, 
            comp_ccusto, comp_situac, 
            comp_tipo_garant, comp_garant_meses, 
            comp_assist, comp_coment, 
            comp_data
        )
		VALUES 
        (
            {$data['asset_type']}, {$data['manufacturer']}, {$data['model']}, 
            {$data['asset_unit']}, {$data['department']}, '{$data['asset_tag']}', 
            " . dbField($data['net_name'], 'text') . ", 
            " . dbField($data['serial_number'], 'text') . ", " . dbField($data['part_number'], 'text') . ", 
            " . dbField($data['invoice_number'], 'text') . ", " . dbField($data['supplier']) . ", 
            " . dbField($data['price'], 'float') . ", " . dbField($data['buy_date'], 'date') . ", 
            " . dbField($data['cost_center']) . ", " . dbField($data['situation']) . ",  
            " . dbField($data['warranty_type']) . ", " . dbField($data['warranty_time']) . ",   
            " . dbField($data['assistance_type']) . ", " . dbField($data['extra_info'], 'text') . ", 
            NOW()
        )";
		
    try {
        $conn->exec($sql);
        $data['success'] = true; 
        $data['cod'] = $conn->lastInsertId();




        /* Inserção dos campos de especificação - Gravar em assets_x_specs */
        $spec_fields_ids = [];
        
        if ($profile_fields['field_specs_ids']) {
            $spec_fields_ids = ($profile_fields['field_specs_ids'] ? explode(',', $profile_fields['field_specs_ids']) : []);
        }

        if ($spec_fields_ids) {
            foreach ($spec_fields_ids as $spec_field_id) {
                
                $type_name = getAssetsTypes($conn, $spec_field_id)['tipo_nome'];
                $spec_field_name = str_slug($type_name, 'spec_');
                
                if (isset($post[$spec_field_name]) && !empty($post[$spec_field_name])) {
        
                    foreach ($post[$spec_field_name] as $field) {
                        
                        if (!empty($field)) {
                            $sql = "INSERT INTO assets_x_specs 
                            (
                                asset_id, asset_spec_id
                            )
                            VALUES 
                            (
                                {$data['cod']}, {$field}
                            )";
                        
                            try {
                                $conn->exec($sql);
                            } catch (Exception $e) {
                                $exception .= '<hr />' . $e->getMessage();
                            }
                        }
                    }
                }
            }
        }



        /**
         * Inserção dos campos extras de especificação
         */

        if (!empty($data['spec_extra_model'])) {
            foreach ($data['spec_extra_model'] as $extra) {
                
                if (!empty($extra)) {
                    $sql = "INSERT INTO assets_x_specs 
                    (
                        asset_id, asset_spec_id
                    )
                    VALUES 
                    (
                        {$data['cod']}, {$extra}
                    )";
                
                    try {
                        $conn->exec($sql);


                    } catch (Exception $e) {
                        $exception .= '<hr />' . $e->getMessage();
                    }
                }
            }
        }


        /**
         * Bloco responsável por registrar as mudanças nas especificações do ativo sendo cadastrado
         */
        $afterValues = getSpecsIdsFromAsset($conn, $data['cod']);
        $valuesRemoved = valuesRemoved($beforeValues, $afterValues);
        $valuesAdded = valuesAdded($beforeValues, $afterValues);

        foreach ($valuesRemoved as $removed) {
            insertNewAssetSpecChange($conn, $data['cod'], $removed, 'remove', $_SESSION['s_uid']);
        }

        foreach ($valuesAdded as $added) {
            insertNewAssetSpecChange($conn, $data['cod'], $added, 'add', $_SESSION['s_uid']);
        }
        /* Final do bloco responsável pelo registro das modificações */





        /* Inserção dos campos personalizados */
        if (count($dataCustom)) {
            foreach ($dataCustom as $cfield) {
                
                if ($cfield['field_type'] == 'checkbox' && !isset($post[$cfield['field_name']])) {
                    $data[$cfield['field_name']] = '';
                } else {
                    $data[$cfield['field_name']] = (is_array($post[$cfield['field_name']]) ? noHtml(implode(',', $post[$cfield['field_name']])) :  noHtml($post[$cfield['field_name']]) );
                }
                
                $isFieldKey = ($cfield['field_type'] == 'select' || $cfield['field_type'] == 'select_multi' ? 1 : 'null') ;

                /* Tratar data */
                if ($cfield['field_type'] == 'date' && !empty($data[$cfield['field_name']])) {
                    $data[$cfield['field_name']] = dateDB($data[$cfield['field_name']]);
                } elseif ($cfield['field_type'] == 'datetime' && !empty($data[$cfield['field_name']])) {
                    $data[$cfield['field_name']] = dateDB($data[$cfield['field_name']]);
                }
                
                $sqlIns = "INSERT INTO 
                            assets_x_cfields (asset_id, cfield_id, cfield_value, cfield_is_key) 
                            VALUES 
                            ('" . $data['cod'] . "', '" . $cfield['id'] . "', " . dbField($data[$cfield['field_name']],'text') . ", " . $isFieldKey . ")
                            ";
                try {
                    $resIns = $conn->exec($sqlIns);
                }
                catch (Exception $e) {
                    $exception .= "<hr />" . $e->getMessage() . "<hr />" . $sqlIns;
                }
            }
        }


        /* Quando o ativo é cadastrado já como filho de outro ativo */
        if ($data['has_parent']) {
            /* Grava o registro de especificação para o ativo pai  */
            $parent_info = getEquipmentInfo($conn, $data['asset_unit'], $data['parent_asset_tag']);
            if (!empty($parent_info)) {

                $sql = "INSERT INTO assets_x_specs
                (
                    asset_id, asset_spec_id, asset_spec_tagged_id
                ) 
                VALUES 
                (
                    {$parent_info['comp_cod']}, {$data['model']}, {$data['cod']}
                )";

                try {
                    $conn->exec($sql);

                    /* Nesse caso ocorre apenas a adição do novo ativo no ativo pai */
                    insertNewAssetSpecChange($conn, $parent_info['comp_cod'], $data['cod'], 'add', $_SESSION['s_uid']);

                }
                catch (Exception $e) {
                    $exception .= "<hr>" . $e->getMessage();
                }
            }
        }

        $newDepartmentInHistory = insertNewDepartmentInHistory($conn, $data['cod'], $data['department'], $_SESSION['s_uid']);
        if (!$newDepartmentInHistory) {
            $exception = '<hr />' .TRANS('MSG_ERROR_IN_LOGGING_NEW_DEPARTMENT');
        }

        $data['message'] = TRANS('MSG_SUCCESS_INSERT') . $exception;
        
    } catch (Exception $e) {
        $exception .= $e->getMessage();
        
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_SAVE_RECORD') . $exception;
        $_SESSION['flash'] = message('danger', '', $data['message'], '');
        echo json_encode($data);
        return false;
    }

} elseif ($data['action'] == 'edit') {


    if (!csrf_verify($post)) {
        $data['success'] = false; 
        $data['message'] = message('warning', 'Ooops!', TRANS('FORM_ALREADY_SENT'),'');
    
        echo json_encode($data);
        return false;
    }

    /* Para comparar os registros e checar alterações de componentes a serem gravadas no histórico */
    $sql = "SELECT * FROM equipamentos WHERE comp_cod = '". $data['cod'] ."' ";
    $res = $conn->query($sql);
    $oldData = $res->fetch();

    /* Se o ativo for filho - não posso deixar alterar a unidade e o departmento */
    if (assetHasParent($conn, $data['cod'])) {
        $data['asset_unit'] = $oldData['comp_inst'];
        $data['department'] = $oldData['comp_local'];
    }


    $beforeValues = getSpecsIdsFromAsset($conn, $data['cod']);

    $sql = "UPDATE equipamentos SET 

                comp_inv = '" . $data['asset_tag'] . "', 
                comp_inst = '" . $data['asset_unit'] . "', 
                comp_marca = '" . $data['model'] . "', 
                comp_local = '" . $data['department'] . "', 
                comp_tipo_equip = '" . $data['asset_type'] . "', 
                comp_fab = '" . $data['manufacturer'] . "', 
                comp_sn = " . dbField($data['serial_number'], 'text') . ", 
                comp_part_number = " . dbField($data['part_number'], 'text') . ", 
                comp_nome = " . dbField($data['net_name'], 'text') . ", 
                comp_mb = " . dbField($data['motherboard'], 'int') . ", 
                comp_proc = " . dbField($data['processor'], 'int') . ", 
                comp_memo = " . dbField($data['memory'], 'int') . ", 
                comp_video = " . dbField($data['video'], 'int') . ", 
                comp_som = " . dbField($data['sound'], 'int') . ", 
                comp_rede = " . dbField($data['network'], 'int') . ", 
                comp_modelohd = " . dbField($data['hdd'], 'int') . ", 
                comp_modem = " . dbField($data['modem'], 'int') . ", 
                comp_cdrom = " . dbField($data['cdrom'], 'int') . ", 
                comp_dvd = " . dbField($data['dvdrom'], 'int') . ", 
                comp_grav = " . dbField($data['recorder'], 'int') . ", 
                comp_tipo_imp = " . dbField($data['printer_type'], 'int') . ", 
                comp_resolucao = " . dbField($data['scanner_resolution'], 'int') . ", 
                comp_polegada = " . dbField($data['monitor_size'], 'int') . ", 
                comp_fornecedor = " . dbField($data['supplier'], 'int') . ", 
                comp_nf = " . dbField($data['invoice_number'], 'text') . ", 
                comp_coment = " . dbField($data['extra_info'], 'text') . ", 
                comp_data_compra = " . dbField($data['buy_date'], 'date') . ", 
                comp_valor = " . dbField($data['price'], 'float') . ", 
                comp_ccusto = " . dbField($data['cost_center'], 'int') . ", 
                comp_situac = " . dbField($data['situation'], 'int') . ", 
                comp_tipo_garant = " . dbField($data['warranty_type'], 'int') . ", 
                comp_garant_meses = " . dbField($data['warranty_time'], 'int') . ", 
                comp_assist = " . dbField($data['assistance_type'], 'int') . " 
                
            WHERE 
                comp_cod = '" . $data['cod'] . "'";
            
    try {
        $conn->exec($sql);

        $data['success'] = true; 


        /* Exclusão das especificaçoes anteriores à essa edição - apenas as que não têm etiqueta associada*/
        $sqlDel = "DELETE FROM assets_x_specs WHERE asset_id = '" . $data['cod'] . "' AND asset_spec_tagged_id IS NULL";
        try {
            $conn->exec($sqlDel);
        }
        catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage();
        }

        /* Novas Especificações */
        $possibleChilds = getAssetsTypesPossibleChilds($conn, $data['asset_type']);
        if (count($possibleChilds)) {
            foreach ($possibleChilds as $child) {
                $fieldSpec = (isset($post[str_slug($child['tipo_nome'], 'spec_')]) && !empty($post[str_slug($child['tipo_nome'], 'spec_')]) ? $post[str_slug($child['tipo_nome'], 'spec_')] : []);
            
                
                if (count($fieldSpec)) {
                    foreach ($fieldSpec as $spec) {
                        if (!empty($spec)) {
                            $sql = "INSERT INTO assets_x_specs (asset_id, asset_spec_id) VALUES ('" . $data['cod'] . "', '" . $spec . "')";
                            try {
                                $conn->exec($sql);

                            }
                            catch (Exception $e) {
                                $exception .= "<hr>" . $e->getMessage();
                            }
                        }
                    }
                }
            }
        }


        /**
         * Inserção dos campos extras de especificação
         */
        if (!empty($data['spec_extra_model'])) {
            foreach ($data['spec_extra_model'] as $extra) {
                
                if (!empty($extra)) {
                    $sql = "INSERT INTO assets_x_specs 
                    (
                        asset_id, asset_spec_id
                    )
                    VALUES 
                    (
                        {$data['cod']}, {$extra}
                    )";
                
                    try {
                        $conn->exec($sql);

                    } catch (Exception $e) {
                        $exception .= '<hr />' . $e->getMessage();
                    }
                }
            }
        }


        /**
         * Bloco responsável por registrar as mudanças nas especificações do ativo sendo cadastrado
         */
        $afterValues = getSpecsIdsFromAsset($conn, $data['cod']);
        $valuesRemoved = valuesRemoved($beforeValues, $afterValues);
        $valuesAdded = valuesAdded($beforeValues, $afterValues);

        foreach ($valuesRemoved as $removed) {
            insertNewAssetSpecChange($conn, $data['cod'], $removed, 'remove', $_SESSION['s_uid']);
        }

        foreach ($valuesAdded as $added) {
            insertNewAssetSpecChange($conn, $data['cod'], $added, 'add', $_SESSION['s_uid']);
        }
        /* Final do bloco responsável pelo registro das modificações */


       /* Atualização ou inserção dos campos personalizados */
       if (count($dataCustom)) {
            foreach ($dataCustom as $cfield) {
            
            
                /* Para possibilitar o Controle de acordo com a opção global conf_cfield_only_opened */
                $field_value = [];
                $field_value = getAssetCustomFields($conn, $data['cod'], $cfield['id']);
                

                /* Controle de acordo com a opção global conf_cfield_only_opened */
                if (!empty($field_value['field_id'])) {


                    if ($cfield['field_type'] == 'checkbox' && !isset($post[$cfield['field_name']])) {
                        $data[$cfield['field_name']] = '';
                    } else {
                        $data[$cfield['field_name']] = (is_array($post[$cfield['field_name']]) ? noHtml(implode(',', $post[$cfield['field_name']])) :  noHtml($post[$cfield['field_name']]) );
                    }

                    $isFieldKey = ($cfield['field_type'] == 'select' || $cfield['field_type'] == 'select_multi' ? 1 : 'null') ;

                    /* Tratar data */
                    if ($cfield['field_type'] == 'date' && !empty($data[$cfield['field_name']])) {
                        $data[$cfield['field_name']] = dateDB($data[$cfield['field_name']]);
                    } elseif ($cfield['field_type'] == 'datetime' && !empty($data[$cfield['field_name']])) {
                        $data[$cfield['field_name']] = dateDB($data[$cfield['field_name']]);
                    }
                    

                    /* Preciso identificar se o campo já existe para o ativo - caso contrário, é inserção */
                    $sql = "SELECT id FROM assets_x_cfields 
                            WHERE asset_id = '" . $data['cod'] . "' AND cfield_id = '" . $cfield['id'] . "' ";
                    try {
                        $res = $conn->query($sql);
                        if (!$res->rowCount()) {
                            
                            /* Nesse caso preciso inserir */
                            $sqlIns = "INSERT INTO 
                                assets_x_cfields (asset_id, cfield_id, cfield_value, cfield_is_key) 
                                VALUES 
                                ('" . $data['cod'] . "', '" . $cfield['id'] . "', " . dbField($data[$cfield['field_name']],'text') . ", " . $isFieldKey . ")
                                ";
                            try {
                                $resIns = $conn->exec($sqlIns);
                            }
                            catch (Exception $e) {
                                $exception .= "<hr>" . $e->getMessage() . "<hr>" . $sqlIns;
                            }

                        } else {
                            
                            /* Nesse caso preciso Atualizar */
                            $sqlUpd = "UPDATE
                                            assets_x_cfields 
                                        SET
                                            cfield_value =  " . dbField($data[$cfield['field_name']], 'text') . "
                                        WHERE
                                            asset_id = '" . $data['cod'] . "' AND 
                                            cfield_id = '" . $cfield['id'] . "'
                                        ";
                            try {
                                $resIns = $conn->exec($sqlUpd);
                            } catch (Exception $e) {
                                $exception .= "<hr>" . $e->getMessage() . "<hr>" . $sqlUpd;
                            }
                        }
                    }
                    catch (Exception $e) {
                        $exception .= "<hr>" . $e->getMessage();
                    }
                }
            }
        }



        if ($data['department'] != $oldData['comp_local']) {

            /**
             * Inserção na tabela de histórico caso a localização seja alterada
             * Até a versão 4, a referencia era feita pelos dados da etiqueta e unidade
             * A partir da versão 5, a referencia é feita pelo código do ativo
             */


            $newDepartmentInHistory = insertNewDepartmentInHistory($conn, $data['cod'], $data['department'], $_SESSION['s_uid']);
            if (!$newDepartmentInHistory) {
                $exception .= '<hr />' .TRANS('MSG_ERROR_IN_LOGGING_NEW_DEPARTMENT');
            }


            /* Fazer a atualização da localização também para os ativos filhos e também gravar a modificação no histórico*/
            $children = getAssetDescendants($conn, $data['cod']);
            foreach ($children as $child) {
                updateAssetDepartment($conn, $child['asset_spec_tagged_id'], $data['department']);
                $newDepartmentInHistory = insertNewDepartmentInHistory($conn, $child['asset_spec_tagged_id'], $data['department'], $_SESSION['s_uid']);
                if (!$newDepartmentInHistory) {
                    $exception .= '<hr />' .TRANS('MSG_ERROR_IN_LOGGING_NEW_DEPARTMENT');
                }
            }
        
        
            /* Atualizar a localização dos componentes avulsos (descontinuados a partir da versão 5) associados - 
            a referência sao os dados de etiqueta anteriores (caso tenham sofrido alteração)
            */
            $sql = "SELECT * FROM {$table} 
                    WHERE 
                        eqp_equip_inv = '" . $oldData['comp_inv'] . "' AND  
                        eqp_equip_inst = '" . $oldData['comp_inst'] . "'
                        ";

            try {
                $res = $conn->query($sql);
                $piecesIds = "";
                if ($res->rowCount()) {
                    foreach ($res->fetchall() as $rowPieces) {
                        if (strlen((string)$piecesIds > 0))
                            $piecesIds .= ",";
                        $piecesIds .= $rowPieces['eqp_piece_id'];
                    }
                }

                if (!empty($piecesIds)) {
                    $sql = "UPDATE estoque SET 
                                estoq_local = '" . $data['department'] . "' 
                        WHERE 
                            estoq_cod IN ({$piecesIds})
                    ";
                    try {
                        $conn->exec($sql);
                    }
                    catch (Exception $e) {
                        $exception .= "<hr>" . $e->getMessage(). "<hr>" . $sql;
                    }
                }
            }
            catch (Exception $e) {
                $exception .= "<hr>" . $e->getMessage() . "<hr>" . $sql;
            }
        }



        /**
         * Atualização das informações relacionadas diretamente à etiqueta/unidade
         * - Componentes avulsos
         * - Ocorrências
         * - Arquivos
         */
        if ($oldData['comp_inv'] != $data['asset_tag'] || $oldData['comp_inst'] != $data['asset_unit']) {
            /* indica que algum dos valores de etiqueta foi alterado */
            $sql = "UPDATE {$table} SET 
                        eqp_equip_inv = '" . $data['asset_tag'] . "', 
                        eqp_equip_inst = '" . $data['asset_unit'] . "' 
                    WHERE 
                        eqp_equip_inv = '" . $oldData['comp_inv'] . "' AND 
                        eqp_equip_inst = '" . $oldData['comp_inst'] . "'
            ";

            try {
                $conn->exec($sql);
            }
            catch (Exception $e) {
                 $exception .= "<hr>" . $e->getMessage() . "<hr>" . $sql;
            }


            /* ATualização da referência para ocorrências relacionadas */
            $sql = "UPDATE ocorrencias SET 
                        equipamento = '" . $data['asset_tag'] . "',
                        instituicao = '" . $data['asset_unit'] . "' 
                    WHERE 
                        equipamento = '" . $oldData['comp_inv'] . "' AND 
                        instituicao = '" . $oldData['comp_inst'] . "'
                    ";

            try {
                $conn->exec($sql);
            }
            catch (Exception $e) {
                $exception .= "<hr>" . $e->getMessage() . "</hr>" . $sql;
            }

            /* Atualizacao dos arquivos diretamente relacionados */
            $sql = "UPDATE imagens SET 
                        img_inv = '" . $data['asset_tag'] . "',
                        img_inst = '" . $data['asset_unit'] . "' 
                    WHERE 
                        img_inv = '" . $oldData['comp_inv'] . "' AND 
                        img_inst = '" . $oldData['comp_inst'] . "'
                    ";
            try {
                $conn->exec($sql);
            }
            catch (Exception $e) {
                $exception .= "<hr>" . $e->getMessage() . "</hr>" . $sql;
            }
        }
        /* Final da atualização dos registros relacionados à etiqueta e unidade */


        if ($data['processor'] != $oldData['comp_proc']) {
            $sql = "INSERT INTO hw_alter (hwa_inst, hwa_inv, hwa_item, hwa_user, hwa_data) 
                    VALUES (
                        '" . $data['asset_unit'] . "', '" . $data['asset_tag'] . "', 
                        " . dbField($oldData['comp_proc']) . ", '" . $_SESSION['s_uid'] . "',  
                        '" . date('Y-m-d H:i:s') . "'
                    )
            ";
            try {
                $conn->exec($sql);
            }
            catch (Exception $e) {
                 $exception .= "<hr>" . $e->getMessage();
            }
        }

        if ($data['motherboard'] != $oldData['comp_mb']) {
            $sql = "INSERT INTO hw_alter (hwa_inst, hwa_inv, hwa_item, hwa_user, hwa_data) 
                    VALUES (
                        '" . $data['asset_unit'] . "', '" . $data['asset_tag'] . "', 
                        " . dbField($oldData['comp_mb']) . ", '" . $_SESSION['s_uid'] . "',  
                        '" . date('Y-m-d H:i:s') . "'
                    )
            ";
            try {
                $conn->exec($sql);
            }
            catch (Exception $e) {
                $exception .= "<hr>" . $e->getMessage();
            }
        }

        if ($data['memory'] != $oldData['comp_memo']) {
            $sql = "INSERT INTO hw_alter (hwa_inst, hwa_inv, hwa_item, hwa_user, hwa_data) 
                    VALUES (
                        '" . $data['asset_unit'] . "', '" . $data['asset_tag'] . "', 
                        " . dbField($oldData['comp_memo']) . ", '" . $_SESSION['s_uid'] . "',  
                        '" . date('Y-m-d H:i:s') . "'
                    )
            ";
            try {
                $conn->exec($sql);
            }
            catch (Exception $e) {
                $exception .= "<hr>" . $sql. "<hr>" . $e->getMessage();
            }
        }

        if ($data['video'] != $oldData['comp_video']) {
            $sql = "INSERT INTO hw_alter (hwa_inst, hwa_inv, hwa_item, hwa_user, hwa_data) 
                    VALUES (
                        '" . $data['asset_unit'] . "', '" . $data['asset_tag'] . "', 
                        " . dbField($oldData['comp_video']) . ", '" . $_SESSION['s_uid'] . "',  
                        '" . date('Y-m-d H:i:s') . "'
                    )
            ";
            try {
                $conn->exec($sql);
            }
            catch (Exception $e) {
                $exception .= "<hr>" . $e->getMessage();
            }
        }

        if ($data['sound'] != $oldData['comp_som']) {
            $sql = "INSERT INTO hw_alter (hwa_inst, hwa_inv, hwa_item, hwa_user, hwa_data) 
                    VALUES (
                        '" . $data['asset_unit'] . "', '" . $data['asset_tag'] . "', 
                        " . dbField($oldData['comp_som']) . ", '" . $_SESSION['s_uid'] . "',  
                        '" . date('Y-m-d H:i:s') . "'
                    )
            ";
            try {
                $conn->exec($sql);
            }
            catch (Exception $e) {
                $exception .= "<hr>" . $e->getMessage();
            }
        }

        if ($data['network'] != $oldData['comp_rede']) {
            $sql = "INSERT INTO hw_alter (hwa_inst, hwa_inv, hwa_item, hwa_user, hwa_data) 
                    VALUES (
                        '" . $data['asset_unit'] . "', '" . $data['asset_tag'] . "', 
                        " . dbField($oldData['comp_rede']) . ", '" . $_SESSION['s_uid'] . "',  
                        '" . date('Y-m-d H:i:s') . "'
                    )
            ";
            try {
                $conn->exec($sql);
            } catch (Exception $e) {
                $exception .= "<hr>" . $e->getMessage();
            }
        }

        if ($data['hdd'] != $oldData['comp_modelohd']) {
            $sql = "INSERT INTO hw_alter (hwa_inst, hwa_inv, hwa_item, hwa_user, hwa_data) 
                    VALUES (
                        '" . $data['asset_unit'] . "', '" . $data['asset_tag'] . "', 
                        " . dbField($oldData['comp_modelohd']) . ", '" . $_SESSION['s_uid'] . "',  
                        '" . date('Y-m-d H:i:s') . "'
                    )
            ";
            try {
                $conn->exec($sql);
            }
            catch (Exception $e) {
                 $exception .= "<hr>" . $e->getMessage();
            }
        }

        if ($data['modem'] != $oldData['comp_modem']) {
            $sql = "INSERT INTO hw_alter (hwa_inst, hwa_inv, hwa_item, hwa_user, hwa_data) 
                    VALUES (
                        '" . $data['asset_unit'] . "', '" . $data['asset_tag'] . "', 
                        " . dbField($oldData['comp_modem']) . ", '" . $_SESSION['s_uid'] . "',  
                        '" . date('Y-m-d H:i:s') . "'
                    )
            ";
            try {
                $conn->exec($sql);
            }
            catch (Exception $e) {
                 $exception .= "<hr>" . $e->getMessage();
            }
        }

        if ($data['cdrom'] != $oldData['comp_cdrom']) {
            $sql = "INSERT INTO hw_alter (hwa_inst, hwa_inv, hwa_item, hwa_user, hwa_data) 
                    VALUES (
                        '" . $data['asset_unit'] . "', '" . $data['asset_tag'] . "', 
                        " . dbField($oldData['comp_cdrom']) . ", '" . $_SESSION['s_uid'] . "',  
                        '" . date('Y-m-d H:i:s') . "'
                    )
            ";
            try {
                $conn->exec($sql);
            }
            catch (Exception $e) {
                 $exception .= "<hr>" . $e->getMessage();
            }
        }

        if ($data['dvdrom'] != $oldData['comp_dvd']) {
            $sql = "INSERT INTO hw_alter (hwa_inst, hwa_inv, hwa_item, hwa_user, hwa_data) 
                    VALUES (
                        '" . $data['asset_unit'] . "', '" . $data['asset_tag'] . "', 
                        " . dbField($oldData['comp_dvd']) . ", '" . $_SESSION['s_uid'] . "',  
                        '" . date('Y-m-d H:i:s') . "'
                    )
            ";
            try {
                $conn->exec($sql);
            }
            catch (Exception $e) {
                 $exception .= "<hr>" . $e->getMessage();
            }
        }

        if ($data['recorder'] != $oldData['comp_grav']) {
            $sql = "INSERT INTO hw_alter (hwa_inst, hwa_inv, hwa_item, hwa_user, hwa_data) 
                    VALUES (
                        '" . $data['asset_unit'] . "', '" . $data['asset_tag'] . "', 
                        " . dbField($oldData['comp_grav']) . ", '" . $_SESSION['s_uid'] . "',  
                        '" . date('Y-m-d H:i:s') . "'
                    )
            ";
            try {
                $conn->exec($sql);
            }
            catch (Exception $e) {
                 $exception .= "<hr>" . $e->getMessage();
            }
        }



        $data['message'] = TRANS('MSG_SUCCESS_EDIT') . $exception;

    } catch (Exception $e) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_DATA_UPDATE') . "<hr />". $sql . "<hr />" . $e->getMessage();
        $_SESSION['flash'] = message('danger', 'Ooops!', $data['message'], '');
        echo json_encode($data);
        return false;
    }
} elseif ($data['action'] == 'delete') {

    /* Só permite exclusão se for admin */
    if ($_SESSION['s_nivel'] != 1) {
        $data['success'] = false; 
        // $data['message'] = TRANS('ACTION_NOT_ALLOWED');
        // $_SESSION['flash'] = message('danger', '', $data['message'] . $exception, '');
        $data['message'] = message('danger', '', TRANS('ACTION_NOT_ALLOWED') . $exception, '');
        echo json_encode($data);
        return false;
    }


    /* Busco os dados do equipamento */
    $equipmentInfo = getEquipmentInfo($conn, null, null, $data['cod']);
    $tag = $equipmentInfo['comp_inv'];
    $unit = $equipmentInfo['comp_inst'];


    /* Checa se há componentes avulsos associados */
    $sql = "SELECT * FROM {$table} WHERE eqp_equip_inv = '{$tag}' AND eqp_equip_inst = '{$unit}' ";
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $data['success'] = false; 
        $data['message'] = message('danger', '', TRANS('MSG_CANT_DEL') . $exception, '');
        echo json_encode($data);
        return false;
    }

    /* Checa se há chamados associados */
    $sql = "SELECT * FROM ocorrencias WHERE equipamento = '{$tag}' AND instituicao = '{$unit}' ";
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $data['success'] = false; 
        $data['message'] = message('danger', '', TRANS('MSG_CANT_DEL') . $exception, '');
        echo json_encode($data);
        return false;
    }


    /* Checa se há ativos agregados */
    $hasAggregated = getAssetSpecs($conn, $data['cod'], true);
    if (!empty($hasAggregated)) {
        $data['success'] = false; 
        $data['message'] = message('danger', '', TRANS('CANT_DELETE_DUE_AGGREGATION') . $exception, '');
        echo json_encode($data);
        return false;
    }

    /* Checa se o ativo possui um ativo pai */
    $hasParent = getAssetParentId($conn, $data['cod']);
    if (!empty($hasParent)) {
        $data['success'] = false; 
        $data['message'] = message('danger', '', TRANS('CANT_DELETE_DUE_AGGREGATION') . $exception, '');
        echo json_encode($data);
        return false;
    }


    /* Sem restrições para excluir o registro */
    $sql = "DELETE FROM equipamentos WHERE comp_cod = '" . $data['cod'] . "'";

    try {
        $conn->exec($sql);
        $data['success'] = true; 
        
        /* Remover do historico de localizacao (hist_inv, hist_inst) */
        $sql = "DELETE FROM historico WHERE hist_inv = '{$tag}' AND hist_inst = '{$unit}' ";
        try {
            $conn->exec($sql);
        }
        catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage();
        }
        
        /* Remover do historico de alteração de hardware (hwa_inst, hwa_inv) */
        $sql = "DELETE FROM hw_alter WHERE hwa_inv = '{$tag}' AND hwa_inst = '{$unit}' ";
        try {
            $conn->exec($sql);
        }
        catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage();
        }
        
        /* Remover do hw_sw (hws_hw_inst, hws_hw_cod) também */
        $sql = "DELETE FROM hw_sw WHERE hws_hw_cod = '{$tag}' AND hws_hw_inst = '{$unit}' ";
        try {
            $conn->exec($sql);
        }
        catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage();
        }


        /* Remover campos customizados relacionados ao ativo */
        $sql = "DELETE FROM assets_x_cfields WHERE asset_id = '{$data['cod']}' ";
        try {
            $conn->exec($sql);
        }
        catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage();
        }
        

        $data['message'] = TRANS('OK_DEL');
        $_SESSION['flash'] = message('success', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    } catch (Exception $e) {
        $exception .= "<hr>" . $e->getMessage() . "<hr>";
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_DATA_REMOVE');
        $_SESSION['flash'] = message('danger', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    }
}


/* Upload de arquivos - Todos os actions */
foreach ($filesClean as $attach) {
    $fileinput = $attach['tmp_name'];
    $tamanho = getimagesize($fileinput);
    $tamanho2 = filesize($fileinput);

    if (!$tamanho) {
        /* Nâo é imagem */
        unset ($tamanho);
        $tamanho = [];
        $tamanho[0] = "";
        $tamanho[1] = "";
    }

    if (chop($fileinput) != "") {
        // $fileinput should point to a temp file on the server
        // which contains the uploaded file. so we will prepare
        // the file for upload with addslashes and form an sql
        // statement to do the load into the database.
        // $file = addslashes(fread(fopen($fileinput, "r"), 10000000));
        $file = addslashes(fread(fopen($fileinput, "r"), $config['conf_upld_size']));
        $sqlFile = "INSERT INTO imagens (img_nome, img_inst, img_inv, img_tipo, img_bin, img_largura, img_altura, img_size) values " .
        "('" . noSpace($attach['name']) . "', '" . $data['asset_unit'] . "', '" . $data['asset_tag'] . "','" . $attach['type'] . "', " .
        "'" . $file . "', " . dbField($tamanho[0]) . ", " . dbField($tamanho[1]) . ", " . dbField($tamanho2) . ")";
        // now we can delete the temp file
        unlink($fileinput);
    }
    try {
        $exec = $conn->exec($sqlFile);
    }
    catch (Exception $e) {
        $data['message'] = $data['message'] . "<hr>" . TRANS('MSG_ERR_NOT_ATTACH_FILE');
        $exception .= "<hr>" . $e->getMessage();
    }
}
/* Final do upload de arquivos */



//Exclui os anexos marcados - Action edit || close
if ( $data['total_files_to_deal'] > 0 ) {
    for ($j = 1; $j <= $data['total_files_to_deal']; $j++) {
        if (isset($post['delImg'][$j])) {
            $qryDel = "DELETE FROM imagens WHERE img_cod = " . $post['delImg'][$j] . "";

            try {
                $conn->exec($qryDel);
            } catch (Exception $e) {
                $exception .= "<hr>" . $e->getMessage();
            }
        }
    }
}




$_SESSION['flash'] = message('success', '', $data['message'] . $exception, '');
echo json_encode($data);
return false;