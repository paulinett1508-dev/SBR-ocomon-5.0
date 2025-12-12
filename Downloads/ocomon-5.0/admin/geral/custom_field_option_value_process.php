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

$screenNotification = "";
$exception = "";
$data = [];
$data['success'] = true;
$data['message'] = "";
$data['cod'] = (isset($post['cod']) ? intval($post['cod']) : "");
$data['action'] = $post['action'];
$data['field_id'] = "";

$data['option_value_before'] = (isset($post['option_value_before']) ? noHtml($post['option_value_before']) : "");
$data['option_value'] = (isset($post['option_value']) ? noHtml($post['option_value']) : "");
$data['custom_field_id'] = (isset($post['custom_field_id']) ? noHtml($post['custom_field_id']) : "");



/* Validações */
if ($data['action'] == "edit" || $data['action'] == "delete") {

    if (empty($data['option_value'])) {
        $data['success'] = false; 
        $data['field_id'] = 'option_value';
    }

    if (!$data['success']) {
        $data['message'] = message('warning', 'Ooops!', TRANS('MSG_EMPTY_DATA'),'');
        echo json_encode($data);
        return false;
    }
}


if ($data['action'] == 'edit') {

    
    /* Pegar as opções já gravadas para comparar com as novas opções para evitar opções repetidas */
    $sql = "SELECT option_value FROM custom_fields_option_values 
            WHERE 
                custom_field_id = '" . $data['custom_field_id'] . "' AND
                option_value = '" . $data['option_value'] . "'";
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $data['success'] = false; 
        $data['message'] = TRANS('NO_CHANGES_DONE');     
        $data['message'] = message('warning', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    }

    $sql = "UPDATE custom_fields_option_values SET
                option_value = '" . $data['option_value'] . "' 
            WHERE 
            custom_field_id = '" . $data['custom_field_id'] . "' AND 
            option_value = '" . $data['option_value_before'] . "' ";
    try {
        $conn->exec($sql);   
        $data['success'] = true; 
        $data['message'] = TRANS('MSG_SUCCESS_EDIT');     

        $_SESSION['flash'] = message('success', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    }
    catch (Exception $e) {
        $exception .= "<hr>" . $e->getMessage();
    }

} elseif ($data['action'] == 'delete') {

    // var_dump($data); exit;

    /* Ver que checagens serão necessárias para remover uma opção - Se a opção estiver sendo utilizada não permitir a remoção */
    /* Criar um array indicando que tabelas devem ser checadas a partir da field_table_to (como chave) ex: tickets_x_cfields */

    /* Tabela e campo de ID do valor da opcao a ser removida */
    $tables_to_check = [
        'tickets_x_cfields' => 'cfield_value'
    ];

    $availableToDelete = true;
    $idToRemove = "";

    /* Buscando o ID da opção que se deseja excluir */
    $sql = "SELECT 
                id 
            FROM 
                custom_fields_option_values 
            WHERE 
                custom_field_id = '" . $data['custom_field_id'] . "' AND 
                option_value = '" . $data['option_value_before'] . "' ";
    try {
        $res = $conn->query($sql);
        if ($res->rowCount()) {
            $idToRemove = $res->fetch()['id'];
        } else {
            $exception .= "<hr>" . $e->getMessage() . "<hr>" . $sql;
            $data['success'] = false; 
            $data['message'] = TRANS('MSG_ERR_DATA_REMOVE');
            $_SESSION['flash'] = message('danger', '', $data['message'] . $exception, '');
            echo json_encode($data);
            return false;
        }
    }
    catch (Exception $e) {
        $exception .= "<hr>" . $e->getMessage();
    }

    /* Checando em cada tabela de expedição */
    foreach ($tables_to_check as $table => $field) {
        $sql = "SELECT ticket FROM {$table} WHERE {$field} = '" . $idToRemove . "' ";
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
        // $data['message'] = TRANS('MSG_CANT_DEL');

        $data['message'] = message('danger', 'Ooops!', TRANS('MSG_CANT_DEL'),'');
        echo json_encode($data);
        return false;

        // $_SESSION['flash'] = message('danger', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    }


    /* Sem restrições para excluir a opção */
    $sql = "DELETE FROM custom_fields_option_values 
            WHERE 
                custom_field_id = '" . $data['custom_field_id'] . "' AND 
                option_value = '" . $data['option_value_before'] . "' ";

    try {
        $conn->exec($sql);
        $data['success'] = true; 
        $data['message'] = TRANS('OK_DEL');

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