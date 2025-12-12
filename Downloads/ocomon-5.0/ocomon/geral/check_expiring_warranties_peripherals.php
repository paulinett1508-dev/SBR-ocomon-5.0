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

use OcomonApi\Support\Email;
use includes\classes\ConnectPDO;

$conn = ConnectPDO::getInstance();

$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 2, 1);
$return = [];
$erro = false;
$mailNotification = "";

$config = getConfig($conn);
$rowconfmail = getMailConfig($conn);
/* Informações sobre a área destino */
$rowAreaTo = getAreaInfo($conn, $config['conf_wrty_area']);


if ($config['conf_days_bf'] != 0) {

    $qryWarranty = "SELECT e.estoq_cod, e.estoq_sn, e.estoq_partnumber, e.estoq_nf, 
        i.item_nome AS tipo, model.mdit_fabricante as fabricante, model.mdit_desc as modelo, 
        model.mdit_desc_capacidade as capacidade, model.mdit_sufixo as sufixo, 

        f.forn_nome as fornecedor, l.local as local,

        ew.ew_sent_first_alert as first_alert, ew.ew_sent_last_alert as last_alert,

        date_add(date_format(e.estoq_data_compra, '%Y-%m-%d') , INTERVAL t.tempo_meses MONTH) AS vencimento 

        FROM  
        estoque e  
        left join email_warranty ew on e.estoq_cod = ew.ew_piece_id 

        left join fornecedores f on f.forn_cod = e.estoq_vendor 

        left join localizacao l on l.loc_id = e.estoq_local,  

        tempo_garantia t, modelos_itens model, itens i 
        WHERE  

        date_add(date_format(e.estoq_data_compra, '%Y-%m-%d'), INTERVAL t.tempo_meses MONTH) >= 
        date_add(date_format(curdate(), '%Y-%m-%d'), INTERVAL 0 DAY) 

        AND 

        date_add(date_format(e.estoq_data_compra, '%Y-%m-%d'), INTERVAL t.tempo_meses MONTH) <= 
        date_add(date_format(curdate(), '%Y-%m-%d'), INTERVAL " . $config['conf_days_bf'] . " DAY) 

        AND e.estoq_warranty = t.tempo_cod AND e.estoq_tipo = i.item_cod 
        AND e.estoq_desc = model.mdit_cod 

        AND ((ew.ew_sent_first_alert is null OR ew.ew_sent_first_alert=0))

        ORDER BY vencimento, modelo";

    // dump($qryWarranty);
    
    try {
        $execWarranty = $conn->query($qryWarranty);
    }
    catch (Exception $e) {
        $erro = true;
    }


    $mailSendMethod = 'send';
    if ($rowconfmail['mail_queue']) {
        $mailSendMethod = 'queue';
    }


    $event = 'mail-about-warranty';
    // $qrymsg = "SELECT * FROM msgconfig WHERE msg_event like ('" . $event . "')";
    // $execmsg = $conn->query($qrymsg);
    // $rowmsg = $execmsg->fetch();

    $eventTemplate = getEventMailConfig($conn, $event);

    foreach ($execWarranty->fetchAll() as $rowWrt) {

        $VARS = array();
        $VARS['%serial%'] = $rowWrt['estoq_sn'];
        $VARS['%partnumber%'] = $rowWrt['estoq_partnumber'];
        $VARS['%tipo%'] = $rowWrt['tipo'];
        $VARS['%modelo%'] = $rowWrt['fabricante'] . "&nbsp;" . $rowWrt['modelo'] . "&nbsp;" . $rowWrt['capacidade'] . "&nbsp;" . $rowWrt['sufixo'];
        $VARS['%vencimento%'] = $rowWrt['vencimento'];
        $VARS['%notafiscal%'] = $rowWrt['estoq_nf'];
        $VARS['%fornecedor%'] = $rowWrt['fornecedor'];
        $VARS['%local%'] = $rowWrt['local'];

        // send_mail($event, $rowAreaTo['email'], $config, $rowmsg, $VARS);
        
        /* Disparo do e-mail (ou fila no banco) para a área de atendimento */
        $mail = (new Email())->bootstrap(
            transvars($eventTemplate['msg_subject'], $VARS),
            transvars($eventTemplate['msg_body'], $VARS),
            $rowAreaTo['email'],
            $eventTemplate['msg_fromname']
        );

        if (!$mail->{$mailSendMethod}()) {
            $mailNotification .= "<hr>" . TRANS('EMAIL_NOT_SENT') . "<hr>" . $mail->message()->getText();
        }


        $findMailSent = "SELECT * FROM email_warranty " .
            "\n\tWHERE ew_piece_id = '" . $rowWrt['estoq_cod'] . "' " .
            " ";
        $execFindMailSent = $conn->query($findMailSent);
        $found = $execFindMailSent->rowCount();

        if ($found) {
            $updMailSent = "UPDATE email_warranty SET " .
                "\n\tew_piece_id= '" . $rowWrt['estoq_cod'] . "', " .
                "\n\tew_sent_first_alert=1, " .
                "\n\tew_sent_last_alert=0" .
                " ";
            $execUpdMailSent = $conn->exec($updMailSent);
        } else {
            $insMailSent = "INSERT INTO email_warranty " .
                "\n\t(ew_piece_id,ew_sent_first_alert,ew_sent_last_alert) " .
                "\n\tvalues ('" . $rowWrt['estoq_cod'] . "',1,0 ) " .
                " ";
            $execInsMailSent = $conn->exec($insMailSent);

        }
    }
}



$return['msg'] = "Success!";
dump($return);
return true;

