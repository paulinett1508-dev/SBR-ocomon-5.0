<?php

namespace OcomonApi\Models;

use CoffeeCode\DataLayer\DataLayer;

/**
 * OcoMon Api | Class xxx Active Record Pattern
 *
 * @author Flávio Ribeiro <flaviorib@gmail.com>
 * @package OcomonApi\Models
 */
class Unit extends DataLayer
{
    /**
     * User constructor.
     */
    public function __construct()
    {
        parent::__construct("instituicao", ["inst_nome"], "inst_cod", false);
    }

    
}