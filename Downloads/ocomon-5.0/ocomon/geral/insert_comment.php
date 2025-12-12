<?php /*                        Copyright 2023 Flávio Ribeiro

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
  */session_start();

if (!isset($_SESSION['s_logado']) || $_SESSION['s_logado'] == 0) {
	$_SESSION['session_expired'] = 1;
    echo "<script>top.window.location = '../../index.php'</script>";
	exit;
}
require_once __DIR__ . "/" . "../../includes/include_basics_only.php";
require_once __DIR__ . "/" . "../../includes/classes/ConnectPDO.php";

use OcomonApi\Support\Email;
use includes\classes\ConnectPDO;

$conn = ConnectPDO::getInstance();

$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 3, 1);


$config = getConfig($conn);
$rowconfmail = getMailConfig($conn);

$data['success'] = true;


if (isset($_POST['onlyOpen']) && $_POST['onlyOpen'] == 1) {

	// dump($_POST); exit();
	$exception = "";
	$mailNotification = "";
	$numero = noHtml($_POST['numero']);
	$data = [];
	$data['numero'] = $numero;
	$comment = (isset($_POST['add_comment']) ? noHtml($_POST['add_comment']) : "");

	
	if (empty($comment)) {
		
		$data['success'] = false; 
        $data['field_id'] = "add_comment";
        $data['message'] = message('warning', 'Ooops!', TRANS('MSG_EMPTY_DATA'), '');
        echo json_encode($data);
        return false;
		
		// $_SESSION['flash'] = message('warning', 'Ooops!', TRANS('MSG_EMPTY_DATA'), '');
		// return false;
	}

	
	$sql = "INSERT 
			INTO 
				assentamentos 
				(
					ocorrencia, 
					assentamento, 
					data, 
					responsavel, 
					asset_privated, 
					tipo_assentamento
				) 
					values
				(
					:numero,
					:comment,
					:date,
					:responsavel,
					0,
					8
				)";
	try {
		$res = $conn->prepare($sql);
		$now = date("Y-m-d H:i:s");
		$res->bindParam(':numero', $numero, PDO::PARAM_INT);
		$res->bindParam(':comment', $comment, PDO::PARAM_STR);
		$res->bindParam(':date', $now, PDO::PARAM_STR);
		$res->bindParam(':responsavel', $_SESSION['s_uid'], PDO::PARAM_INT);
		$res->execute();
		
		$data['message'] = TRANS('TICKET_ENTRY_SUCCESS_ADDED');
	}
	catch (Exception $e) {
		$data['success'] = false; 
		// $data['sql'] = $sql;
		$exception .= "<hr>" . $e->getMessage() . "<hr />" . $sql;
		$data['message'] = TRANS('MSG_SOMETHING_GOT_WRONG') . $exception;
		$_SESSION['flash'] = message('warning', 'Ooops!', $data['message'], '');
		echo json_encode($data);
		return false;
	}
			

	/* Checagens para upload de arquivos - vale para todos os actions */
	$totalFiles = ($_FILES ? count($_FILES['anexo']['name']) : 0);
	$filesClean = [];
	if ($totalFiles > $config['conf_qtd_max_anexos']) {

		$data['success'] = false; 
		$data['message'] .= '<hr>Too many files';
		echo json_encode($data);
		$_SESSION['flash'] = message('warning', 'Ooops!', $data['message'], '');
		return false;
	}

	$uploadMessage = "";
	$emptyFiles = 0;
	/* Testa os arquivos enviados para montar os índices do recordFile*/
	if ($totalFiles) {
		foreach ($_FILES as $anexo) {
			$file = array();
			for ($i = 0; $i < $totalFiles; $i++) {
				/* fazer o que precisar com cada arquivo */
				/* acessa:  $anexo['name'][$i] $anexo['type'][$i] $anexo['tmp_name'][$i] $anexo['size'][$i]*/
				if (!empty($anexo['name'][$i])) {
					$file['name'] =  $anexo['name'][$i];
					$file['type'] =  $anexo['type'][$i];
					$file['tmp_name'] =  $anexo['tmp_name'][$i];
					$file['error'] =  $anexo['error'][$i];
					$file['size'] =  $anexo['size'][$i];
	
					$upld = upload('anexo', $config, $config['conf_upld_file_types'], $file);
					if ($upld == "OK") {
						$recordFile[$i] = true;
						$filesClean[] = $file;
					} else {
						$recordFile[$i] = false;
						$uploadMessage .= $upld;
					}
				} else {
					$emptyFiles++;
				}
			}
		}
		$totalFiles -= $emptyFiles;
		
		if (strlen((string)$uploadMessage) > 0) {
			$data['success'] = false; 
			$data['field_id'] = "idInputFile";
			$data['message'] = message('warning', 'Ooops!', $uploadMessage, '');
			echo json_encode($data);
			$_SESSION['flash'] = $data['message'];
			return false;                
		}
	}

	/* Upload de arquivos - Todos os actions */
	foreach ($filesClean as $attach) {
		$fileinput = $attach['tmp_name'];
		$tamanho = getimagesize($fileinput);
		$tamanho2 = filesize($fileinput);

		if (!$tamanho) {
			/* Nâo é imagem */
			unset ($tamanho);
			$tamanho = [];
			$tamanho[0] = "";
			$tamanho[1] = "";
		}

		if (chop($fileinput) != "") {
			// $fileinput should point to a temp file on the server
			// which contains the uploaded file. so we will prepare
			// the file for upload with addslashes and form an sql
			// statement to do the load into the database.
			// $file = addslashes(fread(fopen($fileinput, "r"), 10000000));
			$file = addslashes(fread(fopen($fileinput, "r"), $config['conf_upld_size']));
			$sqlFile = "INSERT INTO imagens (img_nome, img_oco, img_tipo, img_bin, img_largura, img_altura, img_size) values " .
				"('" . noSpace($attach['name']) . "'," . $data['numero'] . ", '" . $attach['type'] . "', " .
				"'" . $file . "', " . dbField($tamanho[0]) . ", " . dbField($tamanho[1]) . ", " . dbField($tamanho2) . ")";
			// now we can delete the temp file
			unlink($fileinput);
		}
		try {
			$exec = $conn->exec($sqlFile);
		}
		catch (Exception $e) {
			$data['message'] = $data['message'] . "<hr>" . TRANS('MSG_ERR_NOT_ATTACH_FILE');
			$exception .= "<hr>" . $e->getMessage();
		}
	}
	/* Final do upload de arquivos */


	/* Variáveis de ambiente para envio de e-mail: todos os actions */
	$VARS = getEnvVarsValues($conn, $data['numero']);

	$mailSendMethod = 'send';
	if ($rowconfmail['mail_queue']) {
		$mailSendMethod = 'queue';
	}

	$event = 'edita-para-area';
	$eventTemplate = getEventMailConfig($conn, $event);

	// $mailSent = send_mail($event, $VARS['%area_email%'], $rowconfmail, $eventTemplate, $VARS);
	// if (!$mailSent) {
	// 	$mailNotification .= "<hr>" . TRANS('EMAIL_NOT_SENT');
	// }

	/* Disparo do e-mail (ou fila no banco) para a área de atendimento */
	$mail = (new Email())->bootstrap(
		transvars($eventTemplate['msg_subject'], $VARS),
		transvars($eventTemplate['msg_body'], $VARS),
		$VARS['%area_email%'],
		$eventTemplate['msg_fromname'],
		$data['numero']
	);

	// if (!$mail->queue()) {
	if (!$mail->{$mailSendMethod}()) {
		$mailNotification .= "<hr>" . TRANS('EMAIL_NOT_SENT') . "<hr>" . $mail->message()->getText();
	}
	
	$_SESSION['flash'] = message('success', '', TRANS('TICKET_ENTRY_SUCCESS_ADDED') . $mailNotification, '');

	return false;
	// echo TRANS('TICKET_ENTRY_SUCCESS_ADDED');
	// echo message('success', 'Pronto!', TRANS('TICKET_ENTRY_SUCCESS_ADDED'), '');

}
