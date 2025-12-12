<?php
session_start();
require_once (__DIR__ . "/" . "../../includes/include_basics_only.php");
require_once (__DIR__ . "/" . "../../includes/classes/ConnectPDO.php");
use includes\classes\ConnectPDO;

if ($_SESSION['s_logado'] != 1 || ($_SESSION['s_nivel'] != 1 && $_SESSION['s_nivel'] != 2)) {
    exit;
}

$conn = ConnectPDO::getInstance();

$sql = "SELECT tag_name FROM input_tags ORDER BY tag_name";
$sql = $conn->query($sql);

$data = array();
$result = "";
$suggestions = [];

foreach ($sql->fetchAll() as $row) {
	$suggestions[] = $row['tag_name'];
}
$data = [];

if($_SERVER['REQUEST_METHOD'] == 'POST')
{
	foreach($suggestions as $suggestion)
	{
		if(strpos(strtolower($suggestion), strtolower($_POST['term'])) !== false)
		{
			$data[] = $suggestion;	
		}
	}
}
else
{
	foreach($suggestions as $suggestion)
	{
		if(strpos(strtolower($suggestion), strtolower($_GET['term'])) !== false)
		{
			$data[] = $suggestion;	
		}
	}	
}

header('Content-Type: application/json');
echo json_encode(['suggestions' => $data]);
