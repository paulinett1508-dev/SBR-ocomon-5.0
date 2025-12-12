<?php
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
*/ session_start();


/**
 * Script para atualizar a base de fabricantes a partir dos registros da base de
 * modelos de componentes avulsos. Essa ação é necessária para atualizar o sistema
 * da versão 3.2 para a versão 3.3.
 */


if (!isset($_SESSION['s_logado']) || $_SESSION['s_logado'] == 0) {
    $_SESSION['session_expired'] = 1;
    echo "<script>top.window.location = '../../index.php'</script>";
    exit;
}

require_once __DIR__ . "/" . "../../includes/include_geral_new.inc.php";
require_once __DIR__ . "/" . "../../includes/classes/ConnectPDO.php";

use includes\classes\ConnectPDO;

$conn = ConnectPDO::getInstance();

$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 1, 1);

$config = getConfig($conn);

$post = $_POST;

$erro = false;
$exception = "";
$data = [];
$data['success'] = true;
$data['message'] = "";
$data['action'] = (isset($post['action']) ? $post['action'] : "");


if (empty($data['action']) || $data['action'] != 'update') {
    exit;
}


if ($config['conf_updated_issues']) {
    /* Sistema já atualizado */
    $data['success'] = false;
    $data['message'] = message('warning', '', TRANS('SYSTEM_UPDATED_ALREADY') . $exception, '');
    echo json_encode($data);
    return false;
}

/**
 * Atualização da arquitetura de relacionamento entre as tabelas de áreas de atendimento e
 * a tabela de tipos de problemas
 * Nova tabela criada: areas_x_issues
 * 
 * Etapas:
 * 1 - Iterar sobre todos os tipos de problemas existentes
 * 2 - Isolar a nomenclatura dos tipos de problemas para pegar o menor prob_id de cada tipo de problema com nomenclatura repetida
 * 3 - Para cada tipo de problema existente, gerar uma inserção na nova tabela NxN: areas_x_issues
 */


$sql = "SELECT problema FROM problemas GROUP BY problema ORDER BY prob_id";
try {
    $res1 = $conn->query($sql);
    if ($res1->rowCount()) {
        /* Para cada registro, faço novo sql buscando apenas pelo tipo específico de problema (descrição textual) */
        foreach ($res1->fetchAll() as $row1) {
            $sql2 = "SELECT prob_id, prob_area as area_id, problema FROM problemas WHERE problema like ('" . $row1['problema'] . "') ";
            try {
                $res2 = $conn->query($sql2);
                
                /**
                 * Pegando o menor ID do tipo de problema, essa será a chave para a descrição agrupada
                 */
                $sqlMinID = "SELECT min(prob_id) as prob_id FROM problemas WHERE problema like ('" . $row1['problema'] . "') ";
                $resMinID = $conn->query($sqlMinID);
                $min_prob_id = $resMinID->fetch()['prob_id'];

                /** 
                 * No campo prob_id, caso haja mais de um registro com o mesmo nome, só posso inserir o menor
                */
                foreach ($res2->fetchall() as $row2) {
                    
                    
                    /* Primeiro checo se já existe - Não serão aceitos registros duplicados no novo relacionamento*/
                    /* Antes era possível ter o mesmo tipo de problema (mesmo nome, porém com código diferente) 
                    mais de uma vez para uma mesma área com diferentes categorias e slas - agora não será mais possível */
                    $terms = "";
                    $terms = (!empty($row2['area_id']) && $row2['area_id'] != '-1' ? "area_id = " . $row2['area_id'] : "area_id IS NULL ");

                    $sql4 = "SELECT id FROM areas_x_issues 
                            WHERE 
                                {$terms} AND prob_id = '" . $min_prob_id . "' ";

                    try {
                        $res4 = $conn->query($sql4);

                        /** 
                         * Atualizar em todas as tabelas que fazem referência ao campo problema :
                         * ocorrencias
                         * ocorrencias_log
                         * prob_x_script
                         * Ver se há outras
                         * */
                        
                        if (!empty($row2['prob_id']) && $row2['prob_id'] != '-1' && $min_prob_id != $row2['prob_id']) {
                            $sqlUpdTickets = "UPDATE ocorrencias SET problema = '" . $min_prob_id . "' WHERE problema = '" . $row2['prob_id'] . "' ";
                            
                            try {
                                $conn->exec($sqlUpdTickets);
                            }
                            catch (Exception $e) {
                                $exception .= "<hr>" . $e->getMessage();
                            }


                            /* Roteiros de atendimento vinculados ao tipo de problema */
                            $sqlUpdScripts = "UPDATE prob_x_script SET prscpt_prob_id = " . dbField($min_prob_id) . " WHERE prscpt_prob_id = '" . $row2['prob_id'] . "' ";
                            try {
                                $conn->exec($sqlUpdScripts);
                            }
                            catch (Exception $e) {
                                $exception .= "<hr>" . $e->getMessage();
                            }
                        }

                        if ($min_prob_id != $row2['prob_id']) {
                            /* Log de modificações quanto ao tipo de problema */
                            $sqlUpdLog = "UPDATE ocorrencias_log SET log_problema = " . dbField($min_prob_id) . " WHERE log_problema = '" . $row2['prob_id'] . "' ";
                            try {
                                $conn->exec($sqlUpdLog);
                            }
                            catch (Exception $e) {
                                $exception .= "<hr>" . $e->getMessage();
                            }
                            
                        }

                        if (!$res4->rowCount()) {

                            /* Se for registro duplicado, será armazenado o old_prob_id */
                            $oldId = ($min_prob_id == $row2['prob_id'] ? "null" : $row2['prob_id']);
                            
                            $sql5 = "INSERT INTO areas_x_issues (area_id, prob_id, old_prob_id) 
                            VALUES 
                                (" . dbField($row2['area_id']) . ", " . $min_prob_id . ", " . $oldId . ")";
                            try {
                                $conn->exec($sql5);

                                $sqlDel = "DELETE FROM problemas WHERE problema like ('" . $row1['problema'] . "') AND prob_id > '" . $min_prob_id . "' ";

                                try {
                                    $conn->exec($sqlDel);
                                }
                                catch (Exception $e) {
                                    $exception .= "<hr>" . $e->getMessage();
                                }

                            } catch (Exception $e) {
                                $exception .= "<hr>" . $e->getMessage();
                            }
                        }


                    }
                    catch (Exception $e) {
                        $exception .= "<hr>" . $e->getMessage();
                    }
                }
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

if (strlen((string)$exception)) {
    $data['success'] = false;
    $data['message'] = message('warning', '', TRANS('ERROR_DURING_UPDATE') . $exception, '');
    echo json_encode($data);
    return false;
} else {
    $data['success'] = true;


    /* Atualização do flag para saber que o sistema já está atualizado */
    $sql = "UPDATE config SET conf_updated_issues = 1 ";
    try {
        $conn->exec($sql);
    }
    catch (Exception $e) {
        $exception .= "<hr>" . $e->getMessage();
    }


    $data['message'] = TRANS('UPDATE_SUCCESSFULLY');
    $_SESSION['flash'] = message('success', TRANS('GREAT') . "!", $data['message'] . $exception, '');
    echo json_encode($data);
    return false;
}
