<?php

namespace OcomonApi\Models;

use CoffeeCode\DataLayer\DataLayer;

/**
 * OcoMon Api | Class Config - Active Record Pattern
 *
 * @author Flavio Ribeiro <flaviorib@gmail.com>
 * @package OcomonApi\Models
 */
class Config extends DataLayer
{
    /**
     * User constructor.
     */
    public function __construct()
    {
        parent::__construct("config", [], "conf_cod", false);
    }

    

    
}