<?php

include_once __DIR__ . '/' . "includes/config.inc.php";
require_once __DIR__ . '/' . 'includes/functions/functions.php';


$currentDate = TRANS(date("l")) . ", " . (dateScreen(date("Y/m/d H:i:s"),0,'d/m/Y H:i'));

$data[] = $currentDate;

echo json_encode($currentDate);