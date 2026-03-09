<?php
// test_conn.php — APAGAR APÓS TESTE, nunca commitar
require_once 'includes/config.inc.php';
require_once 'includes/classes/ConnectPDO.php';
use includes\classes\ConnectPDO;

$conn = ConnectPDO::getInstance();
if ($conn) {
    $res = $conn->query("SELECT COUNT(*) as total FROM config");
    $row = $res->fetch();
    echo "✅ Conexão OK — config rows: " . $row['total'];
} else {
    echo "❌ Falhou";
}
