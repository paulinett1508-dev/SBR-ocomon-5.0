<?php
session_start();
require_once (__DIR__ . "/" . "../../includes/include_basics_only.php");
require_once (__DIR__ . "/" . "../../includes/classes/ConnectPDO.php");
use includes\classes\ConnectPDO;

if ($_SESSION['s_logado'] != 1 || ($_SESSION['s_nivel'] > 3)) {
    return;
}

$conn = ConnectPDO::getInstance();

$post = $_POST;

$realIssueData = [];
$data = [];
$prob_area_data = [];
$terms = "";
$exception = "";

$post['area'] = (isset($post['area']) && $post['area'] != '-1' ? $post['area'] : null);

if (isset($post['real_issue_id']) && !empty($post['real_issue_id'])) {
    /* o código específico - que será do radio_prob */

    $realIssueData = getIssueById($conn, $post['real_issue_id']);

    if (count($realIssueData)) {
        
        /* Pego o nome do tipo de problema */
        $issue_name = $realIssueData['problema'];
        $areaHasIssueName = areaHasIssueName($conn, $post['area'], $post['real_issue_id']);
        $issueFreeFromArea = issueFreeFromArea($conn, $post['real_issue_id']);

        
        if ($areaHasIssueName || $issueFreeFromArea) {
            $data[] = [
                'prob_id' => $realIssueData['prob_id'], 
                'prob_area' => $realIssueData['prob_area'],
                'prob_descricao' => $realIssueData['prob_descricao'],
                'problema' => $realIssueData['problema']
            ];
        }
    }
}



// $issues = getIssuesByArea4($conn, false, $post['area'], 0, $_SESSION['s_uareas']);
$issues = getIssuesByArea4($conn, false, $post['area'], 0);

foreach ($issues as $issue) {

    if (!empty($realIssueData)) {
        if ($issue['problema'] != $issue_name)
            $data[] = $issue;
    } else {
        $data[] = $issue;
    }
}


// var_dump($data); exit;

/* Ordenação do array de acordo com a chave "problema" */
usort($data, function ($item1, $item2) {
    return $item1['problema'] <=> $item2['problema'];
});


echo json_encode($data);

?>
