<?php session_start();
/*                        Copyright 2023 Flávio Ribeiro

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
require_once __DIR__ . "/" . "../../includes/include_geral_new.inc.php";
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../../includes/css/estilos.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/bootstrap/custom.css" />
    <link rel="stylesheet" type="text/css" href="../../includes/components/fontawesome/css/all.min.css" />

    <title>OcoMon&nbsp;<?= VERSAO; ?></title>
</head>
<body>
    <div class="container-fluid">
        <div class='d-flex justify-content-center mt-5' style=' z-index:-1 !important;'>
            <div class='alert alert-warning fade show w-100' role='alert'>
                <i class='fas fa-exclamation-circle'></i>
                <strong>Ooops!</strong> <?= TRANS('MODULE_NOT_ALLOWED_MSG'); ?>
            </div>
        </div>
        <div class="w-100"></div>
        
        <div class="row justify-content-end">
            <div class=" col-12 col-md-2">
                <button type="reset" class="btn btn-secondary btn-block close-or-return"><?= TRANS('BT_CANCEL'); ?></button>
            </div>
        </div>
        
        
    </div>


    <script src="../../includes/javascript/funcoes-3.0.js"></script>
    <script src="../../includes/components/jquery/jquery.js"></script>
    <script src="../../includes/components/bootstrap/js/bootstrap.min.js"></script>
    <script>
    
        $(() => {
            closeOrReturn ();
        });

        function closeOrReturn (jumps = 1) {
			buttonValue ();
			$('.close-or-return').on('click', function(){
				if (isPopup()) {
					window.close();
				} else {
					window.history.back(jumps);
				}
			});
		}

		function buttonValue () {
			if (isPopup()) {
				$('.close-or-return').text('<?= TRANS('BT_CLOSE'); ?>');
			}
		}
    </script>
</body>
</html>