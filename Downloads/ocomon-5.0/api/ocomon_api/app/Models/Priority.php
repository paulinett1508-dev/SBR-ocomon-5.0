<?php

namespace OcomonApi\Models;

use CoffeeCode\DataLayer\DataLayer;

/**
 * OcoMon Api | Class xxx Active Record Pattern
 *
 * @author Flávio Ribeiro <flaviorib@gmail.com>
 * @package OcomonApi\Models
 */
class Priority extends DataLayer
{
    /**
     * User constructor.
     */
    public function __construct()
    {
        parent::__construct("prior_atend", ["pr_nivel", "pr_color"], "pr_cod", false);
    }

    public function default(): ?Priority
    {
        return (new Priority())->find("pr_default = :default", "default=1");
    }

    
}