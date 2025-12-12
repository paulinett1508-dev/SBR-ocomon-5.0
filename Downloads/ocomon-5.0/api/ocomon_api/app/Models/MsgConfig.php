<?php

namespace OcomonApi\Models;

use CoffeeCode\DataLayer\DataLayer;

/**
 * OcoMon Api | Class MsgConfig - Active Record Pattern
 *
 * @author Flávio Ribeiro <flaviorib@gmail.com>
 * @package OcomonApi\Models
 */
class MsgConfig extends DataLayer
{
    /**
     * User constructor.
     */
    public function __construct()
    {
        parent::__construct(
            "msgconfig", 
            [
                "msg_event",
                "msg_fromname",
                "msg_replyto",
                "msg_subject",
                "msg_body",
            ],
            "msg_cod", 
            false
        );
    }

    /**
     * @param string $event
     * @param string $columns
     * @return null|MsgConfig
     */
    public function findByEvent(string $event, string $columns = "*"): ?MsgConfig
    {
        $find = $this->find("msg_event = :event", "event={$event}", $columns);
        return $find->fetch();
    }


}