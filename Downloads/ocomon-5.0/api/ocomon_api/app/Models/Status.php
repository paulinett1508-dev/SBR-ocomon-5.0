<?php

namespace OcomonApi\Models;

use CoffeeCode\DataLayer\DataLayer;

/**
 * OcoMon Api | Class xxx Active Record Pattern
 *
 * @author Flávio Ribeiro <flaviorib@gmail.com>
 * @package OcomonApi\Models
 */
class Status extends DataLayer
{
    /**
     * User constructor.
     */
    public function __construct()
    {
        parent::__construct("status", ["status"], "stat_id", false);
    }

    public function isStatusFreeze(): bool
    {
        if ($this->data()->stat_time_freeze == 1 || $this->data()->stat_time_freeze == 0)
            return $this->data()->stat_time_freeze;
        return 0;
    }

    
}