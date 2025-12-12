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


$data['mt_name'] = (isset($post['mt_name']) ? noHtml($post['mt_name']) : "");
$data['mt_description'] = (isset($post['mt_description']) ? noHtml($post['mt_description']) : "");

/* array_filter com callback null: apenas no PHP 8 */
$data['unit_name'] = (isset($post['unit_name']) && !empty(array_filter($post['unit_name'], function($v) { return !empty($v); })) ? array_map('noHtml', $post['unit_name']) : []);
$data['unit_abbrev'] = (isset($post['unit_abbrev']) && !empty(array_filter($post['unit_abbrev'], function($v) { return !empty($v); })) ? array_map('noHtml', $post['unit_abbrev']) : []);
$data['equity_factor'] = (isset($post['equity_factor']) && !empty(array_filter($post['equity_factor'], function($v) { return !empty($v); })) ? array_map('noHtml', $post['equity_factor']) : []);
$data['operation'] = (isset($post['operation']) && !empty($post['operation']) ? $post['operation'] : []);


$data['unit_id'] = (isset($post['unit_id']) && !empty(array_filter($post['unit_id'], function($v) { return !empty($v); })) ? array_map('noHtml', $post['unit_id']) : []);
$data['unit_name_update'] = (isset($post['unit_name_update']) && !empty(array_filter($post['unit_name_update'], function($v) { return !empty($v); })) ? array_map('noHtml', $post['unit_name_update']) : []);
$data['unit_abbrev_update'] = (isset($post['unit_abbrev_update']) && !empty(array_filter($post['unit_abbrev_update'], function($v) { return !empty($v); })) ? array_map('noHtml', $post['unit_abbrev_update']) : []);
$data['equity_factor_update'] = (isset($post['equity_factor_update']) && !empty(array_filter($post['equity_factor_update'], function($v) { return !empty($v); })) ? array_map('noHtml', $post['equity_factor_update']) : []);
$data['operation_update'] = (isset($post['operation_update']) && !empty($post['operation_update']) ? $post['operation_update'] : []);

$data['delete_unit'] = (isset($post['delete_unit']) ? $post['delete_unit'] : []);


/* Validações */
if ($data['action'] == "new" || $data['action'] == "edit") {

    if (empty($data['mt_name'])) {
        $data['success'] = false; 
        $data['field_id'] = "mt_name";
    } elseif ($data['action'] == "new" && empty($data['unit_name'])) {
        $data['success'] = false; 
        $data['field_id'] = "unit_name";
    }


    if ($data['success'] == false) {
        $data['message'] = message('warning', 'Ooops!', TRANS('MSG_EMPTY_DATA'),'');
        echo json_encode($data);
        return false;
    }


    if (!empty($data['unit_name'])) {
        $i = 0;
        foreach ($data['unit_name'] as $unitRow) {

            if ($unitRow != "" && (empty($data['unit_abbrev'][$i]) || empty($data['equity_factor'][$i]))) {
                $data['success'] = false; 
                break;
            } 
            $i++;
        }
    }


    /* action edit */
    if (!empty($data['unit_id'])) {
        $i = 0;
        foreach ($data['unit_id'] as $unitRow) {
            if ($unitRow != "" && (empty($data['unit_name_update']) || empty($data['unit_abbrev_update'][$i]) || empty($data['equity_factor_update'][$i]))) {
                $data['success'] = false; 
                break;
            } 
            $i++;
        }
    }


    if ($data['success'] == false) {
        $data['field_id'] = "unit_name";
        $data['message'] = message('warning', '', TRANS('MSG_EMPTY_UNIT_INFO'), '');
        echo json_encode($data);
        return false;
    }

    if (!empty($data['equity_factor'])) {
        $i = 0;
        foreach ($data['equity_factor'] as $factor) {
            if (!filter_var($factor, FILTER_VALIDATE_FLOAT)) {
                $data['success'] = false; 
                break;
            } 
            $i++;
        }
    }

    if ($data['success'] == false) {
        $data['field_id'] = "";
        $data['message'] = message('warning', '', TRANS('MSG_ERROR_WRONG_FORMATTED'), '');
        echo json_encode($data);
        return false;
    }



    if ($data['action'] == "new") {
        if (count(array_unique($data['unit_name'])) != count($data['operation']) || count(array_unique($data['unit_abbrev'])) != count($data['operation'])) {
            $data['success'] = false; 
        }

        /* Checa se há valores == 1 no $data['equity_factor'] - Esse valor é aceito apenas uma vez como unidade de referência */
        $referenceUnit = array_filter($data['equity_factor'], function($v) { return $v == 1; });
        if (count($referenceUnit) > 1) {
            $data['success'] = false; 
        };


        /* Concateno o operation com o equity_factor para checar se há duplicações */
        $arrayCombo = [];
        $i = 0;
        foreach ($data['operation'] as $operation) {
            $arrayCombo[] = $operation . $data['equity_factor'][$i];
            $i++;
        }

        if (count(array_unique($arrayCombo)) != count($data['operation'])) {
            $data['success'] = false; 
        }
    }


    if ($data['action'] == "edit") {
        $countUnitNames = count(array_unique(array_merge($data['unit_name'], $data['unit_name_update'])));
        $countUnitAbbrevs = count(array_unique(array_merge($data['unit_abbrev'], $data['unit_abbrev_update'])));
        $countUnitOperation = count(array_merge($data['unit_abbrev'], $data['unit_abbrev_update']));

        if ($countUnitNames != $countUnitOperation || $countUnitAbbrevs != $countUnitOperation) {
            $data['success'] = false; 
        }

        /* Checa se há valores == 1 no $data['equity_factor'] - Esse valor é aceito apenas uma vez como unidade de referência */
        $mergedEquityFactor = array_merge($data['equity_factor'], $data['equity_factor_update']);
        $referenceUnit = array_filter($mergedEquityFactor, function($v) { return $v == 1; });
        if (count($referenceUnit) > 1) {
            $data['success'] = false; 
        };


        /* Concateno o operation com o equity_factor para checar se há duplicações */
        $arrayCombo = [];
        $mergedOperation = array_merge($data['operation'], $data['operation_update']);
        $i = 0;
        foreach ($mergedOperation as $operation) {
            $arrayCombo[] = $operation . $mergedEquityFactor[$i];
            $i++;
        }

        if (count(array_unique($arrayCombo)) != count($mergedOperation)) {
            $data['success'] = false; 
        }

    }

    

    if ($data['success'] == false) {
        $data['field_id'] = "";
        $data['message'] = message('warning', '', TRANS('MSG_INCONSISTENCY_FILLING'), '');
        echo json_encode($data);
        return false;
    }

}


if ($data['action'] == 'new') {

    /* verifica se um registro com esse nome já existe */
    $sql = "SELECT id FROM measure_types WHERE mt_name = '" . $data['mt_name'] . "' ";
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $data['success'] = false; 
        $data['field_id'] = "mt_name";
        $data['message'] = message('warning', '', TRANS('MSG_RECORD_EXISTS'), '');
        echo json_encode($data);
        return false;
    }


    /*  */


    if (!csrf_verify($post, $data['csrf_session_key'])) {
        $data['success'] = false; 
        $data['message'] = message('warning', 'Ooops!', TRANS('FORM_ALREADY_SENT'),'');
    
        echo json_encode($data);
        return false;
    }

    $sql = "INSERT INTO measure_types 
        (
            mt_name, 
            mt_description
        ) 
        VALUES 
        (
            '" . $data['mt_name'] . "', 
            " . dbField($data['mt_description'], 'text') . "  
        )";

    try {
        $conn->exec($sql);
        $data['success'] = true; 
        $data['message'] = TRANS('MSG_SUCCESS_INSERT');

        $data['cod'] = $conn->lastInsertId();


        if (!empty($data['unit_name'])) {
            $i = 0;
            foreach ($data['unit_name'] as $unitRow) {

                $sql = "INSERT INTO measure_units 
                (
                    type_id,
                    unit_name,
                    unit_abbrev,
                    equity_factor, 
                    operation
                )
                VALUES 
                (
                    " . $data['cod'] . ",
                    '" . $unitRow . "',
                    '" . $data['unit_abbrev'][$i] . "',
                    '" . $data['equity_factor'][$i] . "',
                    '" . $data['operation'][$i] . "'
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
    $sql = "SELECT id FROM measure_types WHERE mt_name = '" . $data['mt_name'] . "' AND id <> '" . $data['cod'] . "'";
    $res = $conn->query($sql);
    if ($res->rowCount()) {
        $data['success'] = false; 
        $data['field_id'] = "mt_name";
        $data['message'] = message('warning', '', TRANS('MSG_RECORD_EXISTS'), '');
        echo json_encode($data);
        return false;
    }


    // $sql = "SELECT id FROM measure_units 
    //         WHERE 
    //             type_id = '".$data['cod']."' AND 
    //             unit_abbrev = '".$data['base_unit']."' AND
    //             equity_factor <> 1  ";
    // $res = $conn->query($sql);
    // if ($res->rowCount()) {
    //     $data['success'] = false; 
    //     $data['field_id'] = "mt_name";
    //     $data['message'] = message('warning', '', TRANS('MEASURE_UNIT_EXISTS_WITH_ANOTHER_FACTOR'), '');
    //     echo json_encode($data);
    //     return false;
    // }


    if (!csrf_verify($post, $data['csrf_session_key'])) {
        $data['success'] = false; 
        $data['message'] = message('warning', 'Ooops!', TRANS('FORM_ALREADY_SENT'),'');
    
        echo json_encode($data);
        return false;
    }

    $sql = "UPDATE measure_types SET 
				mt_name = '" . $data['mt_name'] . "', 
				mt_description = " . dbField($data['mt_description'], 'text') . " 
            WHERE id = '" . $data['cod'] . "'";

    try {
        $conn->exec($sql);
        $data['success'] = true;
        
        
        /* Atualização das unidades de medida que já existiam */
        if (!empty($data['unit_id'])) {
            $i = 0;
            foreach ($data['unit_id'] as $unit) {
                $sql = "UPDATE measure_units SET 

                            unit_name = '" . $data['unit_name_update'][$i] . "',
                            unit_abbrev = '" . $data['unit_abbrev_update'][$i] . "',
                            equity_factor = '" . $data['equity_factor_update'][$i] . "',
                            operation = '" . $data['operation_update'][$i] . "'
                        WHERE 
                            id = '" . $unit . "'";
                try {
                    $conn->exec($sql);
                }
                catch (Exception $e) {
                    $exception .= "<hr>" . $e->getMessage();
                }

                /* Retorna os modelos que utilizam a unidade de medida */
                $modelsWithUnit = getModelsBySpecUnit($conn, $unit);
                foreach ($modelsWithUnit as $model) {
                    /* Atualização do valor absoluto para cada atributo dos modelos que possuirem a unidade de medida */
                    setModelSpecsAbsValues($conn, $model['model_id']);
                }
                
                $i++;
            }
        }


        if (!empty($data['unit_name'])) {
            $i = 0;
            foreach ($data['unit_name'] as $unitRow) {

                $sql = "INSERT INTO measure_units 
                (
                    type_id,
                    unit_name,
                    unit_abbrev,
                    equity_factor, 
                    operation
                )
                VALUES 
                (
                    " . $data['cod'] . ",
                    '" . $unitRow . "',
                    '" . $data['unit_abbrev'][$i] . "',
                    '" . $data['equity_factor'][$i] . "',
                    '" . $data['operation'][$i] . "'
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


        /* Se alguma unidade for removida */
        if (!empty($data['delete_unit'])) {
            $cantDelete = 0;
            $i = 0;
            foreach ($data['delete_unit'] as $unit) {
                
                /* Primeiro preciso checar se a unidade de medida está sendo utilizada em algum modelo de ativo */
                $sql = "SELECT id FROM model_x_specs WHERE measure_unit_id = '" . $unit . "'";
                $res = $conn->query($sql);
                if ($res->rowCount()) {
                    $cantDelete++;
                } else {
                    /* Não posso excluir unidades de medida que o equity_factor seja igual a 1 */
                    $sql = "DELETE FROM measure_units WHERE id = '" . $data['delete_unit'][$i] . "' AND equity_factor <> 1";
                    try {
                        $conn->exec($sql);
                    }
                    catch (Exception $e) {
                        $exception .= "<hr>" . $e->getMessage();
                    }
                }
                $i++;
            }

            if ($cantDelete > 0) {
                $exception .= "<hr />" . TRANS('MSG_RECORDS_CANT_BE_DELETED');
            }
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

    $canRemoveType = true;
    $unitsFromType = getMeasureUnits($conn, null, $data['cod']);

    if (count($unitsFromType)) {

        foreach ($unitsFromType as $unit) {
            $sql = "SELECT id FROM model_x_specs WHERE measure_unit_id = '" . $unit['id'] . "'";
            $res = $conn->query($sql);
            if ($res->rowCount()) {
                /* Nâo pode excluir */
                $canRemoveType = false;
            }
        }

        if ($canRemoveType) {
            $sql = "DELETE FROM measure_types WHERE id = '" . $data['cod'] . "'";
            try {
                $conn->exec($sql);
                $data['success'] = true; 

                /* Após excluir o tipo de medida serão excluídas todas as suas unidades de medida */
                $sql = "DELETE FROM measure_units WHERE type_id = '" . $data['cod'] . "'";
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

            }
            catch (Exception $e) {
                $exception .= "<hr>" . $e->getMessage();
            }
        } else {
            $data['success'] = false; 
            $data['message'] = TRANS('MSG_CANT_DEL');
            $_SESSION['flash'] = message('danger', '', $data['message'], '');
            echo json_encode($data);
            return false;
        }

    } else {
        /* Pode excluir - não possui unidades de medida */
        $sql = "DELETE FROM measure_types WHERE id = '" . $data['cod'] . "'";
        try {
            $conn->exec($sql);
            $data['success'] = true; 
            $data['message'] = TRANS('OK_DEL');
            $_SESSION['flash'] = message('success', '', $data['message'], '');
            echo json_encode($data);
            return false;
        }
        catch (Exception $e) {
            $exception .= "<hr>" . $e->getMessage();
        }
    }
}

echo json_encode($data);