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


$config = getConfig($conn);
// $rowLogado = getUserInfo($conn, $_SESSION['s_uid']);

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
$data['action'] = $post['action'];
$data['field_id'] = "";


$data['type'] = (isset($post['type']) ? noHtml($post['type']) : "");    

$data['manufacturer'] = (isset($post['manufacturer']) ? noHtml($post['manufacturer']) : "");
$data['model_full'] = (isset($post['model_full']) ? noHtml($post['model_full']) : "");
$data['serial_number'] = (isset($post['serial_number']) ? noHtml($post['serial_number']) : "");
$data['department'] = (isset($post['department']) ? noHtml($post['department']) : "");
$data['old_department'] = (isset($post['old_department']) ? noHtml($post['old_department']) : "");
$data['old_unit'] = (isset($post['old_unit']) ? noHtml($post['old_unit']) : "");
$data['old_tag'] = (isset($post['old_tag']) ? noHtml($post['old_tag']) : "");


$data['condition'] = (isset($post['condition']) ? noHtml($post['condition']) : "");
$data['net_name'] = (isset($post['net_name']) && !empty($post['net_name']) ? noHtml($post['net_name']) : "");
$data['net_name'] = (!empty($post['net_name']) ? noSpace($post['net_name']) : $data['net_name']);

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

$data['asset_unit'] = (isset($post['asset_unit']) ? noHtml($post['asset_unit']) : "");
$data['asset_tag'] = (isset($post['asset_tag']) ? noHtml($post['asset_tag']) : "");


$data['invoice_number'] = (isset($post['invoice_number']) ? noHtml($post['invoice_number']) : "");
$data['cost_center'] = (isset($post['cost_center']) ? noHtml($post['cost_center']) : "");

$data['price'] = (isset($post['price']) && $post['price'] != "0,00" ? noHtml($post['price']) : "");
$data['price'] = (!empty($data['price']) ? priceDB($data['price']) : $data['price']);

$data['purchase_date'] = (isset($post['purchase_date']) ? noHtml($post['purchase_date']) : "");
$data['purchase_date'] = (!empty($data['purchase_date']) ? dateDB($data['purchase_date']) : "");

$data['supplier'] = (isset($post['supplier']) ? noHtml($post['supplier']) : "");
$data['assistance'] = (isset($post['assistance']) ? noHtml($post['assistance']) : "");
$data['warranty_type'] = (isset($post['warranty_type']) ? noHtml($post['warranty_type']) : "");
$data['time_of_warranty'] = (isset($post['time_of_warranty']) ? noHtml($post['time_of_warranty']) : "");
$data['additional_info'] = (isset($post['additional_info']) ? noHtml($post['additional_info']) : "");

$data['total_files_to_deal'] = (isset($post['cont']) ? noHtml($post['cont']) : 0);




/* Checagem de preenchimento dos campos obrigatórios*/
if ($data['action'] == "new" || $data['action'] == "edit") {

    if ($data['type'] == "") {
        $data['success'] = false; 
        $data['field_id'] = "type";
    } elseif ($data['manufacturer'] == "") {
        $data['success'] = false; 
        $data['field_id'] = "manufacturer";
    } elseif ($data['model_full'] == "") {
        $data['success'] = false; 
        $data['field_id'] = "model_full";
    } elseif ($data['department'] == "") {
        $data['success'] = false; 
        $data['field_id'] = "department";
    } elseif ($data['condition'] == "") {
        $data['success'] = false; 
        $data['field_id'] = "condition";
    } elseif ($data['asset_unit'] == "") {
        $data['success'] = false; 
        $data['field_id'] = "asset_unit";
    } elseif ($data['asset_tag'] == "") {
        $data['success'] = false; 
        $data['field_id'] = "asset_tag";
    }

    if ($data['success'] == false) {
        $data['message'] = message('warning', '', TRANS('MSG_EMPTY_DATA'), '');
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
        
        $sql = "SELECT comp_cod FROM equipamentos WHERE comp_marca = '" . $data['model_full'] . "' 
                AND comp_sn = '" . $data['serial_number'] . "' {$terms} ";
        $res = $conn->query($sql);
        if ($res->rowCount()) {
            $data['success'] = false; 
            $data['field_id'] = "serial_number";
            $data['message'] = message('warning', '', TRANS('MSG_SERIAL_NUMBER_CAD_IN_SYSTEM'), ''); /* . "<hr>" . $sql */
            echo json_encode($data);
            return false;
        }
    }


    if (!empty($data['printer_type']) && $data['type'] != 3) {
        $data['success'] = false; 
        $data['field_id'] = "printer_type";
        $data['message'] = message('warning', '', TRANS('INCONSISTENT_EQUIPMENT_TYPE'), '');
        echo json_encode($data);
        return false;
    }

    if (!empty($data['monitor_size']) && $data['type'] != 5) {
        $data['success'] = false; 
        $data['field_id'] = "monitor_size";
        $data['message'] = message('warning', '', TRANS('INCONSISTENT_EQUIPMENT_TYPE'), '');
        echo json_encode($data);
        return false;
    }

    if (!empty($data['scanner_resolution']) && $data['type'] != 4) {
        $data['success'] = false; 
        $data['field_id'] = "scanner_resolution";
        $data['message'] = message('warning', '', TRANS('INCONSISTENT_EQUIPMENT_TYPE'), '');
        echo json_encode($data);
        return false;
    }


    /* Checagem da etiqueta e unidade para equipamentos existentes*/
    $terms = ($data['action'] == "edit" ? " AND comp_cod <> '" . $data['cod'] . "' " : "");
    $sql = "SELECT comp_cod FROM equipamentos WHERE 
            comp_inv = '" . $data['asset_tag'] . "' AND 
            comp_inst = '" . $data['asset_unit'] . "' 
            {$terms}
    ";
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

    if ($data['price'] != "" && !filter_var($data['price'], FILTER_VALIDATE_FLOAT)) {
        $data['success'] = false; 
        $data['field_id'] = "price";
        $data['message'] = message('warning', '', TRANS('MSG_ERROR_WRONG_FORMATTED'), '');
        echo json_encode($data);
        return false;
    }

    if ($data['success'] == false) {
        $data['message'] = message('warning', '', TRANS('MSG_EMPTY_DATA'), '');
        echo json_encode($data);
        return false;
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


	$sql = "INSERT INTO equipamentos 
        (
            comp_inv, comp_sn, comp_marca, comp_mb, comp_proc, comp_memo, comp_video, comp_som, 
            comp_rede, comp_modelohd, comp_modem, comp_cdrom, comp_dvd, comp_grav, comp_nome, 
            comp_local, comp_fornecedor, comp_nf, comp_coment, comp_data, comp_valor, comp_data_compra, 
            comp_inst, comp_ccusto, comp_tipo_equip, comp_tipo_imp, comp_resolucao, comp_polegada, 
            comp_fab, comp_situac, comp_tipo_garant, comp_garant_meses, comp_assist
        )
		VALUES 
        (
            '" . $data['asset_tag'] . "', 
            " . dbField($data['serial_number'], 'text') . ", 
            '" . $data['model_full'] . "', 
            " . dbField($data['motherboard'], 'int') . ",
            " . dbField($data['processor'], 'int') . ",
            " . dbField($data['memory'], 'int') . ",
            " . dbField($data['video'], 'int') . ",
            " . dbField($data['sound'], 'int') . ",
            " . dbField($data['network'], 'int') . ",
            " . dbField($data['hdd'], 'int') . ",
            " . dbField($data['modem'], 'int') . ",
            " . dbField($data['cdrom'], 'int') . ",
            " . dbField($data['dvdrom'], 'int') . ",
            " . dbField($data['recorder'], 'int') . ",
            " . dbField($data['net_name'], 'text') . ",
            '" . $data['department'] . "', 
            " . dbField($data['supplier'], 'int') . ",  
            " . dbField($data['invoice_number'], 'text') . ",  
            " . dbField($data['additional_info'], 'text') . ",   
            '" . date('Y-m-d H:i:s') . "', 
            " . dbField($data['price'], 'float') . ",  
            " . dbField($data['purchase_date'], 'date') . ",  
            " . dbField($data['asset_unit'], 'text') . ",  
            " . dbField($data['cost_center'], 'int') . ", 
            '" . $data['type'] . "', 
            " . dbField($data['printer_type'], 'int') . ", 
            " . dbField($data['scanner_resolution'], 'int') . ", 
            " . dbField($data['monitor_size'], 'int') . ", 
            '" . $data['manufacturer'] . "', 
            " . dbField($data['condition'], 'int') . ",  
            " . dbField($data['warranty_type'], 'int') . ",  
            " . dbField($data['time_of_warranty'], 'int') . ",  
            " . dbField($data['assistance'], 'int') . "  
        )";
		
    try {
        $conn->exec($sql);
        $data['success'] = true; 
        // $data['equipment_id'] = $conn->lastInsertId();


        /* Historico de localizacao */
        $sql = "INSERT INTO historico 
                (
                    hist_inv, hist_inst, hist_local
                ) 
                VALUES 
                ('" . $data['asset_tag'] . "', '" . $data['asset_unit'] . "', '" . $data['department'] . "')";
        try {
            $conn->exec($sql);
        }
        catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage();
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

    // var_dump([
    //     'data' => $data,
    //     'oldData' => $oldData,
    // ]); exit;

    $sql = "UPDATE equipamentos SET 

                comp_inv = '" . $data['asset_tag'] . "', 
                comp_inst = '" . $data['asset_unit'] . "', 
                comp_marca = '" . $data['model_full'] . "', 
                comp_local = '" . $data['department'] . "', 
                comp_tipo_equip = '" . $data['type'] . "', 
                comp_fab = '" . $data['manufacturer'] . "', 
                comp_sn = " . dbField($data['serial_number'], 'text') . ", 
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
                comp_coment = " . dbField($data['additional_info'], 'text') . ", 
                comp_data_compra = " . dbField($data['purchase_date'], 'date') . ", 
                comp_valor = " . dbField($data['price'], 'float') . ", 
                comp_ccusto = " . dbField($data['cost_center'], 'int') . ", 
                comp_situac = " . dbField($data['condition'], 'int') . ", 
                comp_tipo_garant = " . dbField($data['warranty_type'], 'int') . ", 
                comp_garant_meses = " . dbField($data['time_of_warranty'], 'int') . ", 
                comp_assist = " . dbField($data['assistance'], 'int') . " 
                
            WHERE 
                comp_cod = '" . $data['cod'] . "'";
            
    try {
        $conn->exec($sql);

        $data['success'] = true; 


        if ($data['department'] != $data['old_department']) {

            //Se a Localização do equipamento for alterada é gravado na tabela de histórico!!!
            $sql = "INSERT INTO historico 
                (
                    hist_inv, hist_inst,  hist_local
                )
                VALUES 
                (
                    '" . $data['asset_tag'] . "', 
                    '" . $data['asset_unit'] . "', 
                    " . dbField($data['department'],'int') . "
                )";
            try {
                $conn->exec($sql);
            }
            catch (Exception $e) {
                $exception .= "<hr>" . $e->getMessage();
            }
        }

        /* Atualizar a localização dos componentes avulsos associados - a referência sao os
            dados de etiqueta anteriores (caso tenham sofrido alteração)
        */
        $sql = "SELECT * FROM {$table} 
                WHERE 
                    eqp_equip_inv = '" . $data['old_tag'] . "' AND  
                    eqp_equip_inst = '" . $data['old_unit'] . "'
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

        /**
         * Atualização das informações relacionadas diretamente à etiqueta/unidade
         * - Componentes avulsos
         * - Ocorrências
         * - Arquivos
         */
        if ($data['old_tag'] != $data['asset_tag'] || $data['old_unit'] != $data['asset_unit']) {
            /* indica que algum dos valores de etiqueta foi alterado */
            $sql = "UPDATE {$table} SET 
                        eqp_equip_inv = '" . $data['asset_tag'] . "', 
                        eqp_equip_inst = '" . $data['asset_unit'] . "' 
                    WHERE 
                        eqp_equip_inv = '" . $data['old_tag'] . "' AND 
                        eqp_equip_inst = '" . $data['old_unit'] . "'
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
                        equipamento = '" . $data['old_tag'] . "' AND 
                        instituicao = '" . $data['old_unit'] . "'
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
                        img_inv = '" . $data['old_tag'] . "' AND 
                        img_inst = '" . $data['old_unit'] . "'
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
        $data['message'] = TRANS('MSG_ERR_DATA_UPDATE') . "<br />". $sql . "<br />" . $e->getMessage();
        $_SESSION['flash'] = message('danger', 'Ooops!', $data['message'], '');
        echo json_encode($data);
        return false;
    }
} elseif ($data['action'] == 'delete') {

    /* Só permite exclusão se for admin */
    if ($_SESSION['s_nivel'] != 1) {
        $data['success'] = false; 
        $data['message'] = TRANS('ACTION_NOT_ALLOWED');
        $_SESSION['flash'] = message('danger', '', $data['message'] . $exception, '');
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
        $data['message'] = TRANS('MSG_CANT_DEL');
        $_SESSION['flash'] = message('danger', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    }

    /* Checa se há chamados associados associados */
    $sql = "SELECT * FROM ocorrencias WHERE equipamento = '{$tag}' AND instituicao = '{$unit}' ";
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_CANT_DEL');
        $_SESSION['flash'] = message('danger', '', $data['message'] . $exception, '');
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