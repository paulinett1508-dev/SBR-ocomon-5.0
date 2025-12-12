<?php

namespace OcomonApi\Models;

use CoffeeCode\DataLayer\DataLayer;

/**
 * OcoMon Api | Class Channel - Active Record Pattern
 *
 * @author Flávio Ribeiro <flaviorib@gmail.com>
 * @package OcomonApi\Models
 */
class Channel extends DataLayer
{
    /**
     * User constructor.
     */
    public function __construct()
    {
        parent::__construct("channels", ["name"], "id", false);
    }

    public function default(): ?Channel
    {
        return (new Channel())->find("is_default = :default", "default=1");
    }


}