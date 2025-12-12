<?php session_start();
/*  Copyright 2023 Flávio Ribeiro

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
require_once __DIR__ . "/" . "../../includes/classes/worktime/Worktime.php";
include_once __DIR__ . "/" . "../../includes/functions/getWorktimeProfile.php";

use includes\classes\ConnectPDO;

$conn = ConnectPDO::getInstance();

$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 3, 1);

if (!isset($_POST['numero'])) {
    exit();
}

$numero = (int)$_POST['numero'];


/* $sqlStatusNow = "SELECT s.stat_id, s.status FROM ocorrencias o, status s WHERE o.numero = {$numero} AND o.status = s.stat_id ";
$resultStatusNow = $conn->query($sqlStatusNow);
$rowStatusNow = $resultStatusNow->fetch();
$statusNow = $rowStatusNow['status'];
$idStatusNow = $rowStatusNow['stat_id']; */


/* MÉTODOS PARA O CÁLCULO DE TEMPO VÁLIDO DE RESPOSTA E SOLUÇÃO */
$holidays = getHolidays($conn);
$profileCod = getProfileCod($conn, $_SESSION['s_wt_areas'], $numero);
$worktimeProfile = getWorktimeProfile($conn, $profileCod);



// $sql = $QRY["ocorrencias_full_ini"]. "WHERE o.numero = {$numero}";
// $sql = "SELECT ts.* , s.* FROM tickets_stages ts, status s WHERE ts.ticket = " . $numero . " AND ts.status_id = s.stat_id ORDER BY ts.id";
$sql = "SELECT * FROM tickets_stages WHERE ticket = " . $numero . " ORDER BY id";
try {
    $resultSQL = $conn->query($sql);
}
catch (Exception $e) {
    // echo 'Erro: ', $e->getMessage(), "<br/>";
    $erro = true;
    return false;
}

$data = array();
if ($resultSQL->rowCount()) {
    foreach ($resultSQL->fetchAll() as $row) {

        $filteredTime = TRANS('IS_PAUSE_STATUS');
        /* Objeto para cálculo do tempo válido */
        $objWT = new WorkTime( $worktimeProfile, $holidays );
        
        
        $status = 'Indeterminado';
        $freeze = 0;

        if ($row['status_id'] != 0) { /* Status Zero reservado para os casos de chamados existentes antes do ticket_stage */
            $sqlInner = "SELECT status, stat_time_freeze FROM status WHERE stat_id = " . $row['status_id'] . " ";
            $resultInner = $conn->query($sqlInner);
            $rowInner = $resultInner->fetch();
            $status = $rowInner['status'];
            $freeze = $rowInner['stat_time_freeze'];
        }

        /* Pegando as datas em cada stage */
        $date1 = $row['date_start'];
        $date2 = $row['date_stop'] ?? date('Y-m-d H:i:s');

        /* Se não for status de parada então é realizado o cálculo de tempo filtrado */
        if (!$freeze) {

            $objWT->startTimer($date1);
            $objWT->stopTimer($date2);

            $filteredTime = $objWT->getTime();
        }

        /* Tempo absoluto em cada stage */

        
        $loopData = array();
        $loopData['date_start'] = dateScreen($row['date_start']);
        $loopData['date_stop'] = dateScreen($row['date_stop']);
        $loopData['filtered_time'] = $filteredTime;
        $loopData['absolute_time'] = absoluteTime($date1, $date2)['inTime'];
        // $loopData['status'] = $row['status'];
        $loopData['status'] = $status;
        $loopData['freeze'] = transbool($freeze);

        $data[] = $loopData;
    }
} else {
    /* Nesse caso, o chamado é anterior a implementação do ticket_stages - não tenho as informações */
    $loopData['date_start'] = '';
    $loopData['date_stop'] = '';
    $loopData['status'] = 'Indisponível';
    $loopData['freeze'] = '';
    $data[] = $loopData;
}


echo json_encode($data);
