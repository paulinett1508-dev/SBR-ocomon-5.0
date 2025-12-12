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

$auth = new AuthNew($_SESSION['s_logado'], $_SESSION['s_nivel'], 2);
$return = [];
$erro = false;
$exception = "";
$mailNotification = "";

$config = getConfig($conn);
$rowconfmail = getMailConfig($conn);
/* Informações sobre a área destino */
$rowAreaTo = getAreaInfo($conn, $config['conf_wrty_area']);

$days = $config['conf_days_bf']; /* Quantidade de dias de antecedência para o envio do e-mail */


if ($days != 0) {

    $qryWarranty = 
    "SELECT 
        e.comp_cod, u.inst_nome AS unit, e.comp_inv AS tag, 
        l.local AS department, e.comp_sn AS serial_number, e.comp_nf AS invoice_number, 
        t.tipo_nome AS equipment_type, f.fab_nome as manufacturer, m.marc_nome as model, 
        fo.forn_nome AS supplier,  
        date_add(date_format(e.comp_data_compra, '%Y-%m-%d'), INTERVAL warranty_time.tempo_meses MONTH) AS expiring_date  

    FROM  
        equipamentos e  
    LEFT JOIN fabricantes f on f.fab_cod = e.comp_fab 
    LEFT JOIN fornecedores fo on fo.forn_cod = e.comp_fornecedor 
    LEFT JOIN email_warranty_equipment email_sent on email_sent.equipment_id = e.comp_cod, 
        tipo_equip t, marcas_comp m, instituicao u, localizacao l, 
        tempo_garantia warranty_time  
    WHERE  

        date_sub(date_add(date_format(e.comp_data_compra, '%Y-%m-%d'), INTERVAL warranty_time.tempo_meses MONTH), INTERVAL {$days} DAY) >=
        date_sub(date_format(curdate(), '%Y-%m-%d'), INTERVAL {$days} DAY) AND

        date_sub(date_add(date_format(e.comp_data_compra, '%Y-%m-%d'), INTERVAL warranty_time.tempo_meses MONTH), INTERVAL {$days} DAY) <= 
        date_add(date_format(curdate(), '%Y-%m-%d'), INTERVAL 0 DAY) AND

        e.comp_inst = u.inst_cod AND 
        e.comp_local = l.loc_id AND 
        e.comp_garant_meses = warranty_time.tempo_cod AND 
        e.comp_tipo_equip = t.tipo_cod AND 
        e.comp_marca = m.marc_cod AND 
        email_sent.sent_date IS NULL

    ORDER BY expiring_date, model
    LIMIT 10
    "; /* Limitado em 10 registros por consulta para evitar gargalo no envio de e-mails */

    try {
        $execWarranty = $conn->query($qryWarranty);
    }
    catch (Exception $e) {
        return;
    }


    $mailSendMethod = 'send';
    if ($rowconfmail['mail_queue']) {
        $mailSendMethod = 'queue';
    }

    $event = "mail-about-warranty";
    $eventTemplate = getEventMailConfig($conn, $event);

    foreach ($execWarranty->fetchAll() as $rowWrt) {

        $VARS = array();
        $VARS['%serial%'] = $rowWrt['serial_number'];
        $VARS['%tipo%'] = $rowWrt['equipment_type'];
        $VARS['%modelo%'] = $rowWrt['model'];
        $VARS['%vencimento%'] = dateScreen($rowWrt['expiring_date'],1);
        $VARS['%notafiscal%'] = $rowWrt['invoice_number'];
        $VARS['%fornecedor%'] = $rowWrt['supplier'];
        $VARS['%local%'] = $rowWrt['department'];
        $VARS['%departamento%'] = $rowWrt['department'];
        $VARS['%etiqueta%'] = $rowWrt['tag'];
        $VARS['%unidade%'] = $rowWrt['unit'];

        // $mailSent = send_mail($event, $rowAreaTo['email'], $rowconfmail, $eventTemplate, $VARS);

        /* Disparo do e-mail (ou fila no banco) para a área de atendimento */
        $mail = (new Email())->bootstrap(
            transvars($eventTemplate['msg_subject'], $VARS),
            transvars($eventTemplate['msg_body'], $VARS),
            $rowAreaTo['email'],
            $eventTemplate['msg_fromname']
        );

        if (!$mail->{$mailSendMethod}()) {
            $mailNotification .= "<hr>" . TRANS('EMAIL_NOT_SENT') . "<hr>" . $mail->message()->getText();
        } else {
            $sql = "INSERT INTO email_warranty_equipment 
            (
                equipment_id
            )
            VALUES 
            (
                '" . $rowWrt['comp_cod'] . "'
            )
            ";

            try {
                $conn->exec($sql);
            }
            catch (Exception $e) {
                $exception .= "<hr>" . $e->getMessage();
            }
        }


        // if ($mailSent) {
        //     $sql = "INSERT INTO email_warranty_equipment 
        //     (
        //         equipment_id
        //     )
        //     VALUES 
        //     (
        //         '" . $rowWrt['comp_cod'] . "'
        //     )
        //     ";

        //     try {
        //         $conn->exec($sql);
        //     }
        //     catch (Exception $e) {
        //         $exception .= "<hr>" . $e->getMessage();
        //     }
        // } 
    }
}



$return['msg'] = "Success!";
// dump($return);
return true;

