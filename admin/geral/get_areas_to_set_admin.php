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
$terms = "";

$cod = (isset($post['cod']) ? (int)$post['cod'] : "");

$action = (isset($post['action']) && !empty($post['action']) ? $post['action'] : "");

// $areaAdmin = 0;
// if (isset($_SESSION['s_area_admin']) && $_SESSION['s_area_admin'] == '1' && $_SESSION['s_nivel'] != '1') {
//     $areaAdmin = 1;
// }

$primary_area = (isset($post['primary_area']) ? $post['primary_area'] : "");

if (empty($primary_area) ) {
    return false;
}


if (isset($post['level']) && (($post['level'] != 3) || ($post['level'] == 5))) {
    return false;
}

$setAdmin = (isset($post['setAdmin']) && $post['setAdmin'] == "true" ? 1 : 0);

if (!$setAdmin) {
    return false;
}



$terms .= (!empty($primary_area) ? " AND sis_id <> {$primary_area} " : "");


$sql = "SELECT sis_id, sistema FROM sistemas WHERE sis_atende = 0 AND sis_status = 1 {$terms}";

$sql .= "ORDER BY sistema";

try {
    $res = $conn->query($sql);
}
catch (Exception $e) {
    return false;
}

$uareas = [];
?>
<div class="h6 w-100 my-4 border-top p-4"><?= TRANS('EXTRA_AREAS_TO_BE_MANAGED_BY_USER'); ?></div>
<?php
$noChecked = "checked";
$yesChecked = "";
foreach ($res->fetchall() as $row) {
    ?>
    <label class="col-md-2 col-form-label col-form-label-sm text-md-right"><?= $row['sistema']; ?></label>
        <div class="form-group col-md-4 switch-field ">
            <?php
            if (!empty($cod)) {
                $sqlUarea = "SELECT 
                                * 
                            FROM 
                                `users_x_area_admin` 
                            WHERE 
                                user_id = {$cod} AND 
                                area_id = {$row['sis_id']} ";
                $resArea = $conn->query($sqlUarea);
                $rowArea = $resArea->fetch();

                $yesChecked = ($rowArea && $rowArea['area_id'] == $row['sis_id'] ? "checked" : "");
                $noChecked = ($yesChecked == "" ? "checked" : "");
            }

            $disabled = (!empty($action) && ($action == 'profile' || $action == 'edit') && $_SESSION['s_nivel'] != 1 ? ' disabled' : '');

            ?>
            <input type="radio" id="manageble_area[<?= $row['sis_id']; ?>]" name="manageble_area[<?= $row['sis_id']; ?>]" value="yes" <?= $yesChecked; ?> <?= $disabled; ?>/>
            <label for="manageble_area[<?= $row['sis_id']; ?>]"><?= TRANS('YES'); ?></label>
            <input type="radio" id="manageble_area_no[<?= $row['sis_id']; ?>]" name="manageble_area[<?= $row['sis_id']; ?>]" value="no" <?= $noChecked; ?> <?= $disabled; ?> />
            <label for="manageble_area_no[<?= $row['sis_id']; ?>]"><?= TRANS('NOT'); ?></label>
        </div>
    <?php
}




// echo json_encode($data);