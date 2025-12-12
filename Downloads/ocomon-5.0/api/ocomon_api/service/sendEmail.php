<?php

require __DIR__ . "/" . "../vendor/autoload.php";

use OcomonApi\Support\Email;


/**
 * SEND QUEUE
 */
$emailQueue = new Email();
$emailQueue->sendQueue();