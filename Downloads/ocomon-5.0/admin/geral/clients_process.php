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


$data['client_name'] = (isset($post['client_name']) ? noHtml($post['client_name']) : "");
$data['nickname'] = (isset($post['nickname']) ? noHtml($post['nickname']) : "");
$data['doc_type'] = (isset($post['doc_type']) ? noHtml($post['doc_type']) : "");
$data['document_number'] = (isset($post['document_number']) ? noHtml($post['document_number']) : "");
$data['contact_name'] = (isset($post['contact_name']) ? noHtml($post['contact_name']) : "");
$data['contact_email'] = (isset($post['contact_email']) ? noHtml($post['contact_email']) : "");
$data['contact_phone'] = (isset($post['contact_phone']) ? noHtml($post['contact_phone']) : "");
$data['contact_name_2'] = (isset($post['contact_name_2']) ? noHtml($post['contact_name_2']) : "");
$data['contact_email_2'] = (isset($post['contact_email_2']) ? noHtml($post['contact_email_2']) : "");
$data['contact_phone_2'] = (isset($post['contact_phone_2']) ? noHtml($post['contact_phone_2']) : "");
$data['client_address'] = (isset($post['client_address']) ? noHtml($post['client_address']) : "");
$data['client_type'] = (isset($post['client_type']) ? noHtml($post['client_type']) : "");
$data['requester_area'] = (isset($post['requester_area']) ? noHtml($post['requester_area']) : "");
$data['client_status'] = (isset($post['client_status']) ? noHtml($post['client_status']) : "");
$data['client_active'] = (isset($post['client_active']) ? ($post['client_active'] == "yes" ? 1 : 0) : 0);

/* Cliente padrão para operações sempre deve estar ativo */
if (isset($data['cod']) && $data['cod'] == 1) {
    $data['client_active'] = 1;
}

/* Chaves Definidas na tipagem no banco de dados */
$maskTypes = array(
    'cnpj' => '\d{2}\.\d{3}\.\d{3}\/\d{4}\-\d{2}',
    'cpf' => '\d{3}\-\d{2}',
    'outro' => ''
);


/* Validações */
if ($data['action'] == "new" || $data['action'] == "edit") {

    
    if (empty($data['client_name'])) {
        $data['success'] = false; 
        $data['field_id'] = "client_name";
    } elseif (empty($data['nickname'])) {
        $data['success'] = false; 
        $data['field_id'] = "nickname";
    } elseif (empty($data['contact_name'])) {
        $data['success'] = false; 
        $data['field_id'] = "contact_name";
    } elseif (empty($data['contact_email'])) {
        $data['success'] = false; 
        $data['field_id'] = "contact_email";
    } elseif (empty($data['contact_phone'])) {
        $data['success'] = false; 
        $data['field_id'] = "contact_phone";
    } /* elseif (empty($data['requester_area'])) {
        $data['success'] = false; 
        $data['field_id'] = "requester_area";
    } */
    
    if (!$data['success']) {
        $data['message'] = message('warning', 'Ooops!', TRANS('MSG_EMPTY_DATA'),'');
        echo json_encode($data);
        return false;
    }
    

    if (!empty($data['doc_type'])) {
        if (!preg_match('/' . $maskTypes[$data['doc_type']] . '/i', $data['document_number'])) {
            $data['success'] = false; 
            $data['field_id'] = 'document_number';
            $data['message'] = message('warning', '', TRANS('BAD_FIELD_FORMAT'), '');
            echo json_encode($data);
            return false;
        }
    }

    




    if (!filter_var($data['contact_email'], FILTER_VALIDATE_EMAIL)) {
        $data['success'] = false; 
        $data['field_id'] = "contact_email";
        $data['message'] = message('warning', '', TRANS('WRONG_FORMATTED_URL'), '');
        echo json_encode($data);
        return false;
    }


    if (!empty($data['contact_email_2']) && !filter_var($data['contact_email_2'], FILTER_VALIDATE_EMAIL)) {
        $data['success'] = false; 
        $data['field_id'] = "contact_email_2";
        $data['message'] = message('warning', '', TRANS('WRONG_FORMATTED_URL'), '');
        echo json_encode($data);
        return false;
    }




    /* Tratar e validar os campos personalizados - todos os actions */
    $dataCustom = [];
    $fields_ids = [];
        
    $cfields = getCustomFields($conn, null, 'clients');

    foreach ($cfields as $cfield) {
        
        /* Seleção multipla vazia */
        if (($cfield['field_type'] == 'select_multi') && !isset($post[$cfield['field_name']])) {
            $post[$cfield['field_name']] = '';
        }

        $dataCustom[] = $cfield; /* Guardado para a área de inserção/atualização */
        
        $field_value = [];
        $field_value['field_id'] = "";
        if ($data['action'] != 'new') {
            $field_value = getClientCustomFields($conn, $data['cod'], $cfield['id']);
        }
        

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



if ($data['action'] == 'new') {

    $sql = "SELECT id FROM clients WHERE fullname = '" . $data['client_name'] . "' ";
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $data['success'] = false; 
        $data['field_id'] = "client_name";
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
                clients 
                (
                    `type`,
                    fullname, 
                    nickname, 
                    document_type, 
                    document_value, 
                    contact_name, 
                    contact_email, 
                    contact_phone,
                    contact_name_2,
                    contact_email_2,
                    contact_phone_2,
                    `address`,
                    area,
                    `status`,
                    is_active
                ) 
                VALUES 
                (
                    " . dbField($data['client_type']) . ", 
                    '" . $data['client_name'] . "', 
                    '" . $data['nickname'] . "', 
                    '" . $data['doc_type'] . "', 
                    '" . $data['document_number'] . "', 
                    '" . $data['contact_name'] . "', 
                    '" . $data['contact_email'] . "', 
                    '" . $data['contact_phone'] . "', 
                    '" . $data['contact_name_2'] . "', 
                    '" . $data['contact_email_2'] . "', 
                    '" . $data['contact_phone_2'] . "', 
                    '" . $data['client_address'] . "',
                    null,
                    " . dbField($data['client_status']) . ",
                    '" . $data['client_active'] . "'
                )";


    try {
        $conn->exec($sql);
        $data['cod'] = $conn->lastInsertId();
        $data['success'] = true; 
        $data['message'] = TRANS('MSG_SUCCESS_INSERT');


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
                            clients_x_cfields (client_id, cfield_id, cfield_value, cfield_is_key) 
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


    $sql = "SELECT id FROM clients WHERE fullname = '" . $data['client_name'] . "' AND id <> '" . $data['cod'] . "' ";
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $data['success'] = false; 
        $data['field_id'] = "client_name";
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


    $sql = "UPDATE clients SET 
                `type` = " . dbField($data['client_type']) . ", 
                fullname = '" . $data['client_name'] . "', 
                nickname = '" . $data['nickname'] . "', 
                document_type = '" . $data['doc_type'] . "', 
                document_value = '" . $data['document_number'] . "', 
                contact_name = '" . $data['contact_name'] . "', 
                contact_email = '" . $data['contact_email'] . "', 
                contact_phone = '" . $data['contact_phone'] . "', 
                contact_name_2 = '" . $data['contact_name_2'] . "', 
                contact_email_2 = '" . $data['contact_email_2'] . "', 
                contact_phone_2 = '" . $data['contact_phone_2'] . "', 
                `address` = '" . $data['client_address'] . "', 
                `area` = null,
                `status` = " . dbField($data['client_status']) . ",
                is_active = '" . $data['client_active'] . "'
            WHERE id = '" . $data['cod'] . "'";


    try {
        $conn->exec($sql);
        $data['success'] = true; 
        $data['message'] = TRANS('MSG_SUCCESS_EDIT');


        /* Atualização ou inserção dos campos personalizados */
        if (count($dataCustom)) {
            foreach ($dataCustom as $cfield) {
                
                /* Para possibilitar o Controle de acordo com a opção global conf_cfield_only_opened */
                $field_value = [];
                $field_value = getClientCustomFields($conn, $data['cod'], $cfield['id']);
                


                /* Controle de acordo com a opção global conf_cfield_only_opened */
                // if (!empty($field_value['field_id'])) {


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
                    $sql = "SELECT id FROM clients_x_cfields 
                            WHERE client_id = '" . $data['cod'] . "' AND cfield_id = '" . $cfield['id'] . "' ";
                    try {
                        $res = $conn->query($sql);
                        if (!$res->rowCount()) {
                            
                            /* Nesse caso preciso inserir */
                            $sqlIns = "INSERT INTO 
                                clients_x_cfields (client_id, cfield_id, cfield_value, cfield_is_key) 
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
                                            clients_x_cfields 
                                        SET
                                            cfield_value =  " . dbField($data[$cfield['field_name']], 'text') . "
                                        WHERE
                                            client_id = '" . $data['cod'] . "' AND 
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
                // }
            }
        }


        $_SESSION['flash'] = message('success', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    } catch (Exception $e) {
        $exception .= "<hr>" . $e->getMessage() . "<hr>" . $sql;
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_ERR_DATA_UPDATE');
        $_SESSION['flash'] = message('danger', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    }

} elseif ($data['action'] == 'delete') {

    /* Cliente de operações - não deve ser excluído */
    if ($data['cod'] == 1) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_CANT_DEL');
        $_SESSION['flash'] = message('danger', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    }

    /* Confere se há usuários vinculados */
    $sql = "SELECT user_id FROM usuarios WHERE user_client = '" . $data['cod'] . "' ";
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $data['success'] = false; 
        $data['message'] = TRANS('MSG_CANT_DEL');
        $_SESSION['flash'] = message('danger', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return false;
    }


    /* Sem restrições para excluir o cliente */
    $sql = "DELETE FROM clients WHERE id = '" . $data['cod'] . "'";

    try {
        $conn->exec($sql);
        $data['success'] = true; 
        $data['message'] = TRANS('OK_DEL');


        $sql = "DELETE FROM clients_x_cfields WHERE client_id = {$data['cod']} ";
        try {
            $conn->exec($sql);
        }
        catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage();
        }



        $_SESSION['flash'] = message('success', '', $data['message'] . $exception, '');
        echo json_encode($data);
        return true;
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