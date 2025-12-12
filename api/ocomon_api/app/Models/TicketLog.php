<?php

namespace OcomonApi\Models;

use CoffeeCode\DataLayer\DataLayer;

/**
 * OcoMon Api | Class xxx Active Record Pattern
 *
 * @author Flávio Ribeiro <flaviorib@gmail.com>
 * @package OcomonApi\Models
 */
class TicketLog extends DataLayer
{
    /**
     * User constructor.
     */
    public function __construct()
    {
        parent::__construct("ocorrencias_log", ["log_numero", "log_quem"], "log_id", false);
    }

    public function findByTicket(int $number, string $columns = "*"): ?TicketLog
    {
        $ticket_log = $this->find("log_numero = :number", "number={$number}", $columns)->order("log_id");
        if ($ticket_log)
            return $ticket_log;
        return null;
    }

    public function lastLogId(int $ticket): ?int
    {
        $log = (new TicketLog())->find("log_numero = :number", "number={$ticket}")
        ->order("log_id DESC")->fetch();
        
        if ($log) {
            return $log->log_id;
        }

        return null;
    }

    public function lastLog(int $ticket): ?TicketLog
    {
        $lastLogId = (new TicketLog())->lastLogId($ticket);

        if ($lastLogId)
            return (new TicketLog())->findById($lastLogId);
        return null;
    }

}