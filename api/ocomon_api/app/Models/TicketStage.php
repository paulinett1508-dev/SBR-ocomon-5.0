<?php

namespace OcomonApi\Models;

use CoffeeCode\DataLayer\DataLayer;

/**
 * OcoMon Api | Class User Active Record Pattern
 *
 * @author Flavio Ribeiro <flaviorib@gmail.com>
 * @package OcomonApi\Models
 */
class TicketStage extends DataLayer
{
    /**
     * TicketStage constructor.
     */
    public function __construct()
    {
        parent::__construct(
            "tickets_stages",
            ["ticket", "date_start", "status_id"],
            "id",
            false
        );
    }

    public function findByTicket(int $ticket): ?TicketStage
    {
        $stages = (new TicketStage())->find("ticket = :ticket", "ticket={$ticket}")
        ->order("id");
        
        if ($stages)
            return $stages;
        return null;
    }

    public function lastStageId(int $ticket): ?int
    {
        $stage = (new TicketStage())->find("ticket = :ticket", "ticket={$ticket}")
        ->order("id DESC")->fetch();
        
        if ($stage) {
            return $stage->id;
        }

        return null;
    }

    public function lastStage(int $ticket): ?TicketStage
    {
        $lastStageId = (new TicketStage())->lastStageId($ticket);

        if ($lastStageId)
            return (new TicketStage())->findById($lastStageId);
        return null;
    }

}