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

if (!isset($_SESSION['s_logado']) || $_SESSION['s_logado'] == 0) {
    $_SESSION['session_expired'] = 1;
    echo "<script>top.window.location = '../../index.php'</script>";
    exit;
}

require_once __DIR__ . "/" . "../../includes/include_geral_new.inc.php";
require_once __DIR__ . "/" . "../../includes/classes/ConnectPDO.php";

use includes\classes\ConnectPDO;

$conn = ConnectPDO::getInstance();

$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 3, 2);

$terms = getStatementsInfo($conn, 'termo-compromisso');


?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../../includes/css/custom_input.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/css/invoice-print.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap/custom.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/fontawesome/css/all.min.css" />

    <style>

        p.indent {
            text-indent:1cm;
        }
        .input-line {
            border: 0;
            border-bottom: 1px solid;
            border-color: #CCC;
            width: 100%;
            margin-bottom: 4px;
            padding: 2px;
        }
    </style>


    <title>OcoMon&nbsp;<?= VERSAO; ?></title>
</head>

<body>

    <div class="container-fluid">
        <div class="row ">
            <div class="col-sm-6 mt-md "><img src="../../includes/logos/MAIN_LOGO.png" class="float-left" alt="logomarca"></div>
            <div class="col-sm-6 mt-md "></div>
        </div>
        <div class="w-100"></div>

        <?php
            if ($terms['header'] != '') {
                ?>
                <h4 class="my-4"><?= $terms['header']; ?></h4>
                <?php
            }
        
            if ($terms['title'] != '') {
                ?>
                <h4 class="my-5"><i class="fas fa-file-signature text-secondary"></i>&nbsp;<?= $terms['title']; ?></h4>
                <?php
            }
        ?>
        
        <div class="modal" id="modal" tabindex="-1" style="z-index:9001!important">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div id="divDetails">
                    </div>
                </div>
            </div>
        </div>

        <?php
        if (isset($_SESSION['flash']) && !empty($_SESSION['flash'])) {
            echo $_SESSION['flash'];
            $_SESSION['flash'] = '';
        }

        $equipment_id = (isset($_GET['equipment_id']) && !empty($_GET['equipment_id']) ? noHtml($_GET['equipment_id']) : '');

        if (empty($equipment_id)) {
            echo message('danger', 'Ooops!', TRANS('MSG_ERR_NOT_EXECUTE'), '', '', 1);
            return;
        }

        $sql = $QRY["full_detail_ini"] . " AND c.comp_cod IN ({$equipment_id}) ";
        $sql .= $QRY["full_detail_fim"];
        try {
            $res = $conn->query($sql);
        } catch (Exception $e) {
            // echo "<hr>" . $e->getMessage() . "<hr>" . $sql;
            echo message('danger', 'Ooops!', TRANS('MSG_ERR_GET_DATA'), '', 1);
            return;
        }

        ?>

        <div class="invoice">
            <!-- <header class="clearfix"> -->
                <div class="row">
                    <div class="col-sm-12 mt-md">
                        <?php
                            if ($terms['p1_bfr_list'] != '') {
                                ?>
                                <p class="indent"><?= $terms['p1_bfr_list']; ?></p>
                                <?php
                            }
                            if ($terms['p2_bfr_list'] != '') {
                                ?>
                                <p class="indent"><?= $terms['p2_bfr_list']; ?></p>
                                <?php
                            }
                            if ($terms['p3_bfr_list'] != '') {
                                ?>
                                <p class="indent"><?= $terms['p3_bfr_list']; ?></p>
                                <?php
                            }
                        ?>
                        
                    </div>
                </div>
            <!-- </header> -->


            <div class="table-responsive">
				<table class="table invoice-items">
					<thead>
						<tr class="h6 text-dark">
							<th id="cell-type"   class="text-semibold"><?= TRANS('COL_UNIT'); ?></th>
							<th id="cell-id"     class="text-semibold"><?= TRANS('ASSET_TAG'); ?></th>
							<th id="cell-id"   class="text-semibold"><?= TRANS('COL_TYPE'); ?></th>
							<th id="cell-author"  class=" text-semibold"><?= TRANS('COL_MANUFACTURER'); ?></th>
							<th id="cell-desc"  class=" text-semibold"><?= TRANS('COL_MODEL'); ?></th>
							<th id="cell-type"  class=" text-semibold"><?= TRANS('SERIAL_NUMBER'); ?></th>
							<th id="cell-author"  class=" text-semibold"><?= TRANS('INVOICE_NUMBER'); ?></th>
							<!-- <th id="cell-author"  class=" text-semibold"><?= TRANS('COST_CENTER'); ?></th> -->
							<th id="cell-author"  class=" text-semibold"><?= TRANS('STATE'); ?></th>
						</tr>
					</thead>
					<tbody>

                    <?php
                    foreach ($res->fetchall() as $rowList) {
                        $department = $rowList['local'];
                        ?>
                        <tr>
							<td><?= $rowList['instituicao']; ?></td>
							<td><?= $rowList['etiqueta']; ?></td>
							<td><?= $rowList['equipamento']; ?></td>
							<td><?= $rowList['fab_nome']; ?></td>
							<td><?= $rowList['modelo']; ?></td>
							<td><?= $rowList['serial']; ?></td>
							<td><?= $rowList['nota']; ?></td>
							<!-- <td><?= $rowList['ccusto']; ?></td> -->
							<td><?= $rowList['situac_nome']; ?></td>
						</tr>
                        <?php
                    }
                    ?>
					</tbody>
				</table>
			</div>

            <div class="bill-to my-4">
                <div class="row">
                    <div class="col-md-6">
                        <div class="bill-to">
                            <!-- <p class="h5 mb-xs text-dark text-semibold">To:</p> -->
                            <p class="h5 mb-xs text-dark text-semibold"><?= TRANS('TXT_INFO_COMPLEM'); ?></p>
                            <address>
                                <br />
                                <?= TRANS('DEPARTMENT'); ?>:&nbsp;<span class="text-dark"><input type="text" class="input-line" value="<?= $department; ?>" /></span>
                                <br />
                                <?= TRANS('FIELD_USER_RESP'); ?>:&nbsp;<span class="text-dark"><input type="text" class="input-line" /></span>
                                
                            </address>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <!-- <div class="bill-data text-right"> -->
                        <div class="bill-to text-muted text-right">
                            <p class="mb-none">
                                <span><?= TRANS('DOCUMENT_DATE'); ?>:</span>
                                <span class="value text-dark"><?= dateScreen(date("Y-m-d H:i:s")); ?></span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- <hr> -->

            <div class="row">
                <div class="col-sm-12 mt-md mb-4">
                    <h5><?= TRANS('TXT_IMPORTANT'); ?></h5>
                </div>
                <div class="col-sm-12 mt-md mb-4">
                    <?php
                        if ($terms['p1_aft_list'] != '') {
                            ?>
                            <p class="indent"><?= $terms['p1_aft_list']; ?></p>
                            <?php
                        }
                        if ($terms['p2_aft_list'] != '') {
                            ?>
                            <p class="indent"><?= $terms['p2_aft_list']; ?></p>
                            <?php
                        }
                        if ($terms['p3_aft_list'] != '') {
                            ?>
                            <p class="indent"><?= $terms['p3_aft_list']; ?></p>
                            <?php
                        }
                    ?>
                    
                    
                </div>
                <div class="col-sm-6 mt-md">
                    <p>
                        <?= TRANS('SIGNATURE'); ?>:&nbsp;<span class="text-dark"><input type="text" class="input-line" /></span>
                    </p>
                </div>
                <br />
                
                
            </div>
        </div>
        <div class="container-fluid d-print-none">
            <div class="row justify-content-end">
                <div class="col-2"><button type="reset" class="btn btn-primary btn-block" onClick="window.print();"><?= TRANS('FIELD_PRINT_OCCO'); ?></button></div>
                <div class="col-2"><button type="reset" class="btn btn-secondary btn-block" onClick="parent.history.back();"><?= TRANS('BT_RETURN'); ?></button></div>
            </div>
        </div>

    </div>

    <script src="../../includes/components/jquery/jquery.js"></script>
    <script src="../../includes/components/bootstrap/js/bootstrap.min.js"></script>
    <script type="text/javascript">
        $(function() {});
        // window.print();
    </script>
</body>

</html>