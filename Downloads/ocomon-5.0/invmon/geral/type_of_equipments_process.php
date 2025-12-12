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

$exception = "";
$screenNotification = "";
$data = [];
$data['success'] = true;
$data['message'] = "";
$data['cod'] = (isset($post['cod']) ? intval($post['cod']) : "");
$data['action'] = $post['action'];
$data['field_id'] = "";
$data['csrf_session_key'] = (isset($post['csrf_session_key']) ? $post['csrf_session_key'] : "");

$data['type_name'] = (isset($post['type_name']) ? noHtml($post['type_name']) : "");
$data['tipo_categoria'] = (isset($post['tipo_categoria']) ? noHtml($post['tipo_categoria']) : "");

/* Gravar em assets_types_part_of */
$data['is_part_of'] = (isset($post['is_part_of']) ? $post['is_part_of'] : []);
$data['has_parts_of'] = (isset($post['has_parts_of']) ? $post['has_parts_of'] : []);

/* Gravar em profiles_x_assets_types */
$data['profile_id'] = (isset($post['profile_id']) ? noHtml($post['profile_id']) : "");


/* Validações */
if ($data['action'] == "new" || $data['action'] == "edit") {

    if (empty($data['type_name'])) {
        $data['success'] = false; 
        $data['field_id'] = "type_name";
    } elseif (empty($data['tipo_categoria'])) {
        $data['success'] = false; 
        $data['field_id'] = "tipo_categoria";
    }

    if ($data['success'] == false) {
        $data['message'] = message('warning', 'Ooops!', TRANS('MSG_EMPTY_DATA'),'');
        echo json_encode($data);
        return false;
    }
}

if ($data['action'] == 'new') {

    /* verifica se um registro com esse nome já existe */
    $sql = "SELECT tipo_cod FROM tipo_equip WHERE tipo_nome = '" . $data['type_name'] . "' ";
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $data['success'] = false; 
        $data['field_id'] = "type_name";
        $data['message'] = message('warning', '', TRANS('MSG_RECORD_EXISTS'), '');
        echo json_encode($data);
        return false;
    }


    if (!csrf_verify($post, $data['csrf_session_key'])) {
        $data['success'] = false; 
        $data['message'] = message('warning', 'Ooops!', TRANS('FORM_ALREADY_SENT'),'');
    
        echo json_encode($data);
        return false;
    }

    $sql = "INSERT INTO tipo_equip 
        (
            tipo_nome, 
            tipo_categoria
        ) 
        VALUES 
        (
            '" . $data['type_name'] . "',
            '" . $data['tipo_categoria'] . "'
        )";

    try {
        $conn->exec($sql);
        $data['success'] = true; 

        $data['cod'] = $conn->lastInsertId();

        if (!empty($data['profile_id'])) {
            
            $sql = "INSERT INTO profiles_x_assets_types 
                (
                    profile_id, 
                    asset_type_id
                ) 
                VALUES 
                (
                    " . $data['profile_id'] . ",
                    " . $data['cod'] . "
                )";

            try {
                $conn->exec($sql);
            }
            catch (Exception $e) {
                $exception .= "<hr>" . $e->getMessage();
            }
        }


        if (!empty($data['is_part_of'])) {
            
            foreach ($data['is_part_of'] as $parent) {
                $sql = "INSERT INTO assets_types_part_of 
                (
                    parent_id, 
                    child_id
                ) 
                VALUES 
                (
                    " . $parent . ", 
                    " . $data['cod'] . "
                )";

                try {
                    $conn->exec($sql);
                }
                catch (Exception $e) {
                    $exception .= "<hr>" . $e->getMessage();
                }
            }
        }
        
        if (!empty($data['has_parts_of'])) {
            
            foreach ($data['has_parts_of'] as $child) {
                $sql = "INSERT INTO assets_types_part_of 
                (
                    parent_id, 
                    child_id
                ) 
                VALUES 
                (
                    " . $data['cod'] . ",
                    " . $child . " 
                )";

                try {
                    $conn->exec($sql);
                }
                catch (Exception $e) {
                    $exception .= "<hr>" . $e->getMessage();
                }
            }
        }
        
        
        
        $data['message'] = TRANS('MSG_SUCCESS_INSERT');
        $_SESSION['flash'] = message('success', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    } catch (Exception $e) {
        $exception .= "<hr>" . $e->getMessage();
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_SAVE_RECORD') . $exception;
        $_SESSION['flash'] = message('danger', '', $data['message'], '');
        echo json_encode($data);
        return false;
    }

} elseif ($data['action'] == 'edit') {

    /* verifica se um registro com esse nome já existe para outro código */
    $sql = "SELECT tipo_cod FROM tipo_equip WHERE tipo_nome = '" . $data['type_name'] . "' AND tipo_cod <> '" . $data['cod'] . "'";
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $data['success'] = false; 
        $data['field_id'] = "type_name";
        $data['message'] = message('warning', '', TRANS('MSG_RECORD_EXISTS'), '');
        echo json_encode($data);
        return false;
    }

    if (!csrf_verify($post, $data['csrf_session_key'])) {
        $data['success'] = false; 
        $data['message'] = message('warning', 'Ooops!', TRANS('FORM_ALREADY_SENT'),'');
    
        echo json_encode($data);
        return false;
    }

    $sql = "UPDATE tipo_equip SET 
				tipo_nome = '" . $data['type_name'] . "',
				tipo_categoria = '" . $data['tipo_categoria'] . "'
            WHERE tipo_cod = '" . $data['cod'] . "'";

    try {
        $conn->exec($sql);
        $data['success'] = true; 
        
        
        if (!empty($data['profile_id'])) {

            /* Primeiro checo se já existe o registro */
            $sql = "SELECT id 
                    FROM profiles_x_assets_types 
                    WHERE 
                        asset_type_id = " . $data['cod'];
            $res = $conn->query($sql);
            if ($res->rowCount()) {
                /* Se existe faço o update */
                $sql = "UPDATE profiles_x_assets_types SET 
                            profile_id = " . $data['profile_id'] . "
                        WHERE 
                            asset_type_id = " . $data['cod'];
            } else {
                /* Se não existe realizo a inserção */
                $sql = "INSERT INTO profiles_x_assets_types 
                    (
                        profile_id, 
                        asset_type_id
                    ) 
                    VALUES 
                    (
                        " . $data['profile_id'] . ",
                        " . $data['cod'] . "
                    )";
            }

            try {
                $conn->exec($sql);
            }
            catch (Exception $e) {
                $exception .= "<hr>" . $e->getMessage();
            }
        } else {
            /* Deleto o registro caso exista */
            $sql = "DELETE FROM profiles_x_assets_types WHERE asset_type_id = " . $data['cod'];
            try {
                $conn->exec($sql);
            }
            catch (Exception $e) {
                $exception .= "<hr>" . $e->getMessage();
            }
        }



        /* Tratamento dos relacionamentos entre tipos de ativos */
        $sql = "DELETE FROM assets_types_part_of WHERE child_id = " . $data['cod'];
        try {
            $conn->exec($sql);

            /* Realizo as inserções novamente a partir do que foi informado */
            if (!empty($data['is_part_of'])) {
            
                foreach ($data['is_part_of'] as $parent) {
                    $sql = "INSERT INTO assets_types_part_of 
                    (
                        parent_id, 
                        child_id
                    ) 
                    VALUES 
                    (
                        " . $parent . ",
                        " . $data['cod'] . "
                    )";

                    try {
                        $conn->exec($sql);
                    }
                    catch (Exception $e) {
                        $exception .= "<hr>" . $e->getMessage();
                    }
                }
            }
        }
        catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage();
        }
        
        


        /* Tratamento dos relacionamentos entre tipos de ativos */
        $sql = "DELETE FROM assets_types_part_of WHERE parent_id = " . $data['cod'];
        try {
            $conn->exec($sql);
            
            /* Realizo as inserções novamente a partir do que foi informado */
            if (!empty($data['has_parts_of'])) {
            
                foreach ($data['has_parts_of'] as $child) {
                    $sql = "INSERT INTO assets_types_part_of 
                    (
                        parent_id, 
                        child_id
                    ) 
                    VALUES 
                    (
                        " . $data['cod'] . ",
                        " . $child . " 
                    )";
    
                    try {
                        $conn->exec($sql);
                    }
                    catch (Exception $e) {
                        $exception .= "<hr>" . $e->getMessage();
                    }
                }
            }
        }
        catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage();
        }


        




        $data['message'] = TRANS('MSG_SUCCESS_EDIT');
        $_SESSION['flash'] = message('success', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    } catch (Exception $e) {
        $exception .= "<hr>" . $e->getMessage();
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_DATA_UPDATE') . $exception;
        $_SESSION['flash'] = message('danger', '', $data['message'], '');
        echo json_encode($data);
        return false;
    }

} elseif ($data['action'] == 'delete') {

    $sqlFindPrevention = "SELECT E.*, T.* FROM equipamentos E, tipo_equip T 
                            WHERE E.comp_tipo_equip = T.tipo_cod and T.tipo_cod = ".$data['cod']."";
    $resFindPrevention = $conn->query($sqlFindPrevention);
    $foundPrevention = $resFindPrevention->rowCount();

    if ($foundPrevention) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_CANT_DEL');
        $_SESSION['flash'] = message('danger', '', $data['message'], '');
        echo json_encode($data);
        return false;
    }

    /* Verifica sobre relacionamentos entre tipos de ativos */
    $sqlFindPrevention = "SELECT id 
                            FROM assets_types_part_of 
                            WHERE 
                                parent_id = ".$data['cod']." OR child_id = ".$data['cod']."";
    $resFindPrevention = $conn->query($sqlFindPrevention);
    $foundPrevention = $resFindPrevention->rowCount();

    if ($foundPrevention) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_CANT_DEL');
        $_SESSION['flash'] = message('danger', '', $data['message'], '');
        echo json_encode($data);
        return false;
    }





    $sql = "DELETE FROM tipo_equip WHERE tipo_cod = '" . $data['cod'] . "'";

    try {
        $conn->exec($sql);
        $data['success'] = true; 

        $sql = "DELETE FROM profiles_x_assets_types WHERE asset_type_id = " . $data['cod'];
        try {
            $conn->exec($sql);
        }
        catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage();
        }


        $sql = "DELETE FROM assets_types_part_of WHERE child_id = " . $data['cod'];
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
        $exception .= "<hr>" . $e->getMessage();
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_DATA_REMOVE');
        $_SESSION['flash'] = message('danger', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    }
    
}

echo json_encode($data);