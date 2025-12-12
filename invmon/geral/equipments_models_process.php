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

// var_dump($post);exit;


$config = getConfig($conn);
// $rowLogado = getUserInfo($conn, $_SESSION['s_uid']);

$recordFile = false;
$erro = false;
$exception = "";
$screenNotification = "";
$data = [];
$data['success'] = true;
$data['message'] = "";
$data['cod'] = (isset($post['cod']) ? intval($post['cod']) : "");
$data['numero'] = (isset($post['numero']) ? intval($post['numero']) : "");
$data['action'] = $post['action'];
$data['field_id'] = "";
$data['csrf_session_key'] = (isset($post['csrf_session_key']) ? $post['csrf_session_key'] : "");


$data['type'] = (isset($post['type']) ? noHtml($post['type']) : "");
$data['manufacturer'] = (isset($post['manufacturer']) ? noHtml($post['manufacturer']) : "");
$data['model_name'] = (isset($post['model_name']) ? noHtml($post['model_name']) : "");

$data['measure_type'] = (isset($post['measure_type']) && !empty(array_filter($post['measure_type'], function($v) { return !empty($v); })) ? array_map('noHtml', $post['measure_type']) : []);
$data['measure_unit'] = (isset($post['measure_unit']) && !empty(array_filter($post['measure_unit'], function($v) { return !empty($v); })) ? array_map('noHtml', $post['measure_unit']) : []);
$data['measure_value'] = (isset($post['measure_value']) && !empty(array_filter($post['measure_value'], function($v) { return !empty($v); })) ? array_map('noHtml', $post['measure_value']) : []);


$data['spec_id'] = (isset($post['spec_id']) ? $post['spec_id'] : []);
$data['measure_type_update'] = (isset($post['measure_type_update']) && !empty(array_filter($post['measure_type_update'], function($v) { return !empty($v); })) ? array_map('noHtml', $post['measure_type_update']) : []);
$data['measure_unit_update'] = (isset($post['measure_unit_update']) && !empty(array_filter($post['measure_unit_update'], function($v) { return !empty($v); })) ? array_map('noHtml', $post['measure_unit_update']) : []);
$data['measure_value_update'] = (isset($post['measure_value_update']) && !empty(array_filter($post['measure_value_update'], function($v) { return !empty($v); })) ? array_map('noHtml', $post['measure_value_update']) : []);
$data['deleteSpec'] = (isset($post['deleteSpec']) ? $post['deleteSpec'] : []);

$data['total_files_to_deal'] = (isset($post['cont']) ? noHtml($post['cont']) : 0);



/* Checagem de preenchimento dos campos obrigatórios*/
if ($data['action'] == "new" || $data['action'] == "edit") {

    if ($data['type'] == "") {
        $data['success'] = false; 
        $data['field_id'] = "type";
    } elseif ($data['manufacturer'] == "") {
        $data['success'] = false; 
        $data['field_id'] = "manufacturer";
    } elseif ($data['model_name'] == "") {
        $data['success'] = false; 
        $data['field_id'] = "model_name";
    } 

    if ($data['success'] == false) {
        $data['message'] = message('warning', '', TRANS('MSG_EMPTY_DATA'), '');
        echo json_encode($data);
        return false;
    }

    if (!empty($data['measure_type'])) {
        $i = 0;
        foreach ($data['measure_type'] as $attr) {
            if ($attr != "" && (empty($data['measure_unit'][$i]) || empty($data['measure_value'][$i]))) {
                $data['success'] = false; 
                break;
            } 
            $i++;
        }
    }


    /* action edit */
    if (!empty($data['spec_id'])) {
        $i = 0;
        foreach ($data['spec_id'] as $attr) {
            if ($attr != "" && (empty($data['measure_unit_update'][$i]) || empty($data['measure_value_update'][$i]))) {
                $data['success'] = false; 
                break;
            } 
            $i++;
        }
    }


    if ($data['success'] == false) {
        $data['field_id'] = "measure_type";
        $data['message'] = message('warning', '', TRANS('MSG_EMPTY_ATTRIBUTE'), '');
        echo json_encode($data);
        return false;
    }

    if (!empty($data['measure_value'])) {
        $i = 0;
        foreach ($data['measure_value'] as $attr) {
            if (!filter_var($attr, FILTER_VALIDATE_FLOAT)) {
                $data['success'] = false; 
                break;
            } 
            $i++;
        }
    }

    if (!empty($data['measure_value_update'])) {
        $i = 0;
        foreach ($data['measure_value_update'] as $attr) {
            if (!filter_var($attr, FILTER_VALIDATE_FLOAT)) {
                $data['success'] = false; 
                break;
            } 
            $i++;
        }
    }

    if ($data['success'] == false) {
        $data['field_id'] = "measure_type";
        $data['message'] = message('warning', '', TRANS('MSG_ERROR_WRONG_FORMATTED'), '');
        echo json_encode($data);
        return false;
    }

}


// var_dump($post); exit;

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

    $sql = "SELECT * FROM marcas_comp WHERE marc_manufacturer = '" . $data['manufacturer'] . "' AND marc_nome = '" . $data['model_name'] . "'";
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $data['success'] = false; 
        $data['field_id'] = "model_name";
        $data['message'] = message('warning', '', TRANS('MSG_RECORD_EXISTS'), '');
        echo json_encode($data);
        return false;
    }

    /* Verificação de CSRF */
    // if (!csrf_verify($post)) {
    if (!csrf_verify($post, $data['csrf_session_key'])) {
        $data['success'] = false; 
        $data['message'] = message('warning', 'Ooops!', TRANS('FORM_ALREADY_SENT'),'');
        echo json_encode($data);
        return false;
    }

	$sql = "INSERT INTO marcas_comp 
        (
            marc_nome, marc_tipo, marc_manufacturer
        )
		VALUES 
        (
            '" . $data['model_name'] . "', '" . $data['type'] . "', '" . $data['manufacturer'] . "'
        )";
		
    try {
        $conn->exec($sql);
        $data['success'] = true; 
        $data['cod'] = $conn->lastInsertId();


        /* Inserções das características do modelo */
        if (!empty($data['measure_type'])) {
            $i = 0;
            foreach ($data['measure_type'] as $attr) {
                
                if (!empty($attr)) {
                    $sql = "INSERT INTO model_x_specs
                        (
                            model_id, measure_unit_id, spec_value
                        )
                        VALUES
                        (
                            '" . $data['cod'] . "', '" . $data['measure_unit'][$i] . "', '" . $data['measure_value'][$i] . "'
                        )";
                    try {
                        $conn->exec($sql);
                    }
                    catch (Exception $e) {
                        $exception .= "<hr>" . $e->getMessage();
                    }
                }

                
                $i++;
            }
            /* Atualização do valor absoluto para cada atributo do modelo */
            setModelSpecsAbsValues($conn, $data['cod']);
        }

        $data['message'] = TRANS('MSG_SUCCESS_INSERT') . $uploadMessage . $exception;

    } catch (Exception $e) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_SAVE_RECORD') . "<hr />" . $sql;
        $_SESSION['flash'] = message('danger', '', $data['message'], '');
        echo json_encode($data);
        return false;
    }

} elseif ($data['action'] == 'edit') {

    $sql = "SELECT * FROM marcas_comp WHERE marc_manufacturer = '" . $data['manufacturer'] . "' AND marc_nome = '" . $data['model_name'] . "' AND marc_cod <> '" . $data['cod'] . "' ";
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $data['success'] = false; 
        $data['field_id'] = "model_name";
        $data['message'] = message('warning', '', TRANS('MSG_RECORD_EXISTS'), '');
        echo json_encode($data);
        return false;
    }

    // if (!csrf_verify($post)) {
    if (!csrf_verify($post, $data['csrf_session_key'])) {
        $data['success'] = false; 
        $data['message'] = message('warning', 'Ooops!', TRANS('FORM_ALREADY_SENT'),'');
    
        echo json_encode($data);
        return false;
    }

    $sql = "UPDATE marcas_comp SET 
    
                marc_nome = '" . $data['model_name'] . "', 
                marc_tipo = '" . $data['type'] . "',
                marc_manufacturer = '" . $data['manufacturer'] . "'
            WHERE 
                marc_cod = '" . $data['cod'] . "'";
            
    try {
        $conn->exec($sql);

        $data['success'] = true; 


        /* Atualização das características que já existiam */
        if (!empty($data['spec_id'])) {
            $i = 0;
            foreach ($data['spec_id'] as $attr) {
                $sql = "UPDATE model_x_specs SET 
                            measure_unit_id = '" . $data['measure_unit_update'][$i] . "', 
                            spec_value = '" . $data['measure_value_update'][$i] . "'
                        WHERE 
                            id = '" . $data['spec_id'][$i] . "'";
                try {
                    $conn->exec($sql);
                }
                catch (Exception $e) {
                    $exception .= "<hr>" . $e->getMessage();
                }
                $i++;
            }
        }


        /* Se alguma característica for removida */
        if (!empty($data['deleteSpec'])) {
            $i = 0;
            foreach ($data['deleteSpec'] as $attr) {
                $sql = "DELETE FROM model_x_specs WHERE id = '" . $data['deleteSpec'][$i] . "'";
                try {
                    $conn->exec($sql);
                }
                catch (Exception $e) {
                    $exception .= "<hr>" . $e->getMessage();
                }
                $i++;
            }
        }

        /* Inserções das novas características do modelo */
        if (!empty($data['measure_type'])) {
            $i = 0;
            foreach ($data['measure_type'] as $attr) {
                
                $sql = "INSERT INTO model_x_specs
                        (
                            model_id, measure_unit_id, spec_value
                        )
                        VALUES
                        (
                            '" . $data['cod'] . "', '" . $data['measure_unit'][$i] . "', '" . $data['measure_value'][$i] . "'
                        )";
                try {
                    $conn->exec($sql);
                }
                catch (Exception $e) {
                    $exception .= "<hr>" . $e->getMessage();
                }
                $i++;
            }
        }

        /* Atualização do valor absoluto para cada atributo do modelo */
        setModelSpecsAbsValues($conn, $data['cod']);


        $data['message'] = TRANS('MSG_SUCCESS_EDIT').$exception;

    } catch (Exception $e) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_DATA_UPDATE') . "<br />". $sql . "<br />" . $e->getMessage();
        $_SESSION['flash'] = message('danger', 'Ooops!', $data['message'], '');
        echo json_encode($data);
        return false;
    }
} elseif ($data['action'] == 'delete') {


    /* Confere se há impedimentos para excluir o registro */
    $sql = "SELECT * FROM equipamentos WHERE comp_marca = '" . $data['cod'] . "' ";
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_CANT_DEL');
        $_SESSION['flash'] = message('danger', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    }

    /* Características cadastradas para o modelo */
    $sql = "SELECT * FROM model_x_specs WHERE model_id = '" . $data['cod'] . "' ";
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_CANT_DEL');
        $_SESSION['flash'] = message('danger', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    }





    /* Sem restrições para excluir o registro */
    $sql = "DELETE FROM marcas_comp WHERE marc_cod = '" . $data['cod'] . "'";

    try {
        $conn->exec($sql);
        $data['success'] = true; 
        $data['message'] = TRANS('OK_DEL');

        /* Remove também os arquivos relacionados na tabela de imagens */
        $sql = "DELETE FROM imagens WHERE img_model = '" . $data['cod'] . "'";
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

        $sqlFile = "INSERT INTO imagens (img_nome, img_model, img_tipo, img_bin, img_largura, img_altura, img_size) values " .
            "('" . noSpace($attach['name']) . "'," . $data['cod'] . ", '" . $attach['type'] . "', " .
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