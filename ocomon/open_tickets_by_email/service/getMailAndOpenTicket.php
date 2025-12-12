<?php


if (version_compare(phpversion(), '8.1', '<' )) {
    require __DIR__ . "/../ddeboer_imap_older/vendor/autoload.php";
} else {
    require __DIR__ . "/../ddeboer_imap/vendor/autoload.php";
}


require __DIR__ . "/" . "../ocomon_api_access/src/OcomonApi.php";
require __DIR__ . "/" . "../ocomon_api_access/src/Tickets.php";
require __DIR__ . "/" . "../config/config.php";

use ocomon_api_access\OcomonApi\Tickets;

use Ddeboer\Imap\Server;
use Ddeboer\Imap\Message\Headers;
use Ddeboer\Imap\Search\Email\To;
use Ddeboer\Imap\Search\Text\Body;
use Ddeboer\Imap\SearchExpression;
use Ddeboer\Imap\Search\Date\Since;
use Ddeboer\Imap\Search\Flag\Unseen;
use Ddeboer\Imap\Search\Text\Subject;


if (ALLOW_OPEN_TICKET_BY_EMAIL != '1') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Settings: Opening tickets by email is not allowed'
    ]);
    return;
}

$exception = "";

$cert = (MAIL_GET_CERT == '0' ? '/novalidate-cert' : '');

/**
 * @var \Ddeboer\Imap\Server $server
 * Definir essas configurações
 */
$server = new Server(
    MAIL_GET_IMAP_ADDRESS,
    MAIL_GET_PORT,
    '/imap/ssl' . $cert
);

/**
 * Dados para a API - Tickets
 */
$tickets = new Tickets(
    API_OCOMON_ADDRESS,
    API_USERNAME,
    API_APP,
    API_TOKEN
);

/**
 * @var \Ddeboer\Imap\Connection $connection
 */

try {
    $connection = $server->authenticate(MAIL_GET_ADDRESS, MAIL_GET_PASSWORD);
}
catch (Exception $e) {
    echo $e->getMessage();
    return;
}

$hasMailbox = $connection->hasMailbox(MAIL_GET_MAILBOX);

if ($hasMailbox) {
    $mailbox = $connection->getMailbox(MAIL_GET_MAILBOX);
} else {
    echo "Mailbox " . MAIL_GET_MAILBOX . " not found";
    return;
}


$today = new DateTimeImmutable();
$daysAgo = $today->sub(new DateInterval('P' . MAIL_GET_DAYS_SINCE . 'D'));

$search = new SearchExpression();
// $search->addCondition(new To('teste@gmail.com'));

if (MAIL_GET_SUBJECT_CONTAINS)
    $search->addCondition(new Subject(MAIL_GET_SUBJECT_CONTAINS));
if (MAIL_GET_BODY_CONTAINS)
    $search->addCondition(new Body(MAIL_GET_BODY_CONTAINS));

// $search->addCondition(new Unseen());
$search->addCondition(new Since($daysAgo));

$messages = $mailbox->getMessages($search, \SORTDATE, false);


/** @var \Ddebo\Imap\Message $message*/
foreach ($messages as $message) {

    $headers = (array)$message->getHeaders();
    /* Só abrirá chamado se não for e-mail de resposta */
    if (!array_key_exists('in_reply_to', $headers) || empty($headers['in_reply_to'])) {
    
        $objFrom = $message->getFrom();
        
        // $dateObj = $message->getDate();
        // $dateObj->format('Y-m-d H:i:s');

        $description = "";
        // $description .= $message->getSubject() . "\n";
        $description .= $message->getSubject() . "\n";
        $description .= $message->getBodyText();
        // $description = nl2br($description);
        $description = toHtml($description);


        // var_dump([
        //     'description' => $description,
        //     'contact' => $objFrom->getName(),
        //     'contact_email' => $objFrom->getAddress(), 
        //     'channel' => API_TICKET_BY_MAIL_CHANNEL,
        //     'client' => API_TICKET_BY_MAIL_CLIENT, 
        //     'area' => API_TICKET_BY_MAIL_AREA, 
        //     'status' => API_TICKET_BY_MAIL_STATUS,
        //     'input_tag' => API_TICKET_BY_MAIL_TAG
        // ]); 
        // exit;
        
        /**
         * Abertura do chamado
         */
        $create = $tickets->create([
            'description' => $description,
            'contact' => $objFrom->getName(),
            'contact_email' => $objFrom->getAddress(), 
            'channel' => API_TICKET_BY_MAIL_CHANNEL,
            'client' => API_TICKET_BY_MAIL_CLIENT, 
            'area' => API_TICKET_BY_MAIL_AREA, 
            'status' => API_TICKET_BY_MAIL_STATUS,
            'input_tag' => API_TICKET_BY_MAIL_TAG
        ]);


        /* Se nao ocorrer erro, então movo a mensagem */
        if (!empty($create->response()->ticket)) {

            /* Movendo cada mensagem retornada para outra mailbox */
            if (MAIL_GET_MARK_SEEN && MAIL_GET_MARK_SEEN == '1') {
                $message->markAsSeen();
            }
            
            try {
                $newMailbox = $connection->getMailbox(MAIL_GET_MOVETO);
            }
            catch (Exception $e) {
                $exception .= "<hr>" . $e->getMessage();
                try {
                    $newMailbox = $connection->createMailbox(MAIL_GET_MOVETO);
                }
                catch (Exception $e) {
                    $exception .= "<hr>" . $e->getMessage();
                    echo $exception;
                    return;
                }
            }
            $message->move($newMailbox);
            echo json_encode(['ticket' => $create->response()->ticket]);
        } else {
            echo "No ticket created\n";
            var_dump($create->response());
        }
    }
}

/* Garante a remoção das mensagens marcadas para exclusão */
$connection->expunge();