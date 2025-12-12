<?php
session_start();
require_once (__DIR__ . "/" . "../../includes/include_basics_only.php");
require_once (__DIR__ . "/" . "../../includes/classes/ConnectPDO.php");
use includes\classes\ConnectPDO;

if ($_SESSION['s_logado'] != 1 || ($_SESSION['s_nivel'] != 1 && $_SESSION['s_nivel'] != 2)) {
    exit;
}

$conn = ConnectPDO::getInstance();


$isAdmin = $_SESSION['s_nivel'] == 1;
$aliasAreasFilter = ($_SESSION['requester_areas'] ? "ua.AREA" : "o.sistema");
$filtered_areas = $_SESSION['dash_filter_areas'];
$filtered_clients = $_SESSION['dash_filter_clients'];

// $qry_filter_areas = "";
// $areas_names = "";





/* Filtro de seleção a partir das áreas */
if (empty($filtered_areas)) {
    if ($isAdmin) {
        // $qry_filter_areas = "";
        $u_areas = null;
        // $areas_names .= TRANS('NONE_FILTER');
    } else {
        // $qry_filter_areas = " AND " . $aliasAreasFilter . " IN ({$_SESSION['s_uareas']}) OR " . $aliasAreasFilter . " = '-1')";
        $u_areas = $_SESSION['s_uareas'];
    }
} else {
    // $qry_filter_areas = " AND (" . $aliasAreasFilter . " IN ({$filtered_areas}))";
    
    $u_areas = $filtered_areas;
    // $array_areas_names = getUserAreasNames($conn, $filtered_areas);

    // foreach ($array_areas_names as $area_name) {
    //     if (strlen((string)$areas_names))
    //         $areas_names .= ", ";
    //     $areas_names .= $area_name;
    // }
}



$startDate = date("Y-m-01 00:00:00");
$endDate = date("Y-m-d H:i:s");


$data = array();
$none = true;
$data['message_empty'] = "";

foreach (getTagsList($conn) as $tag) {
    $tagCount = getTagCount($conn, $tag['tag_name'], $startDate, $endDate, $u_areas, $_SESSION['requester_areas'], $filtered_clients);
    if ($tagCount) {
        $none = false;
        $data[] = ['label' => $tag['tag_name'], 'weight' => $tagCount];
    }
}

if ($none) {
    $data['message_empty'] = message('info', '', TRANS('NO_RECORDS_FOUND'), '', '', true);
}
$data['title'] = TRANS('TAGGING_CLOUD_CURRENT_MONTH');
echo json_encode($data);

?>
