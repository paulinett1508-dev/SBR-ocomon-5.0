<?php

namespace OcomonApi\Models;

use CoffeeCode\DataLayer\DataLayer;

/**
 * OcoMon Api | Class User Active Record Pattern
 *
 * @author Flavio Ribeiro <flaviorib@gmail.com>
 * @package OcomonApi\Models
 */
class Sla extends DataLayer
{
    /**
     * Sla constructor.
     */
    public function __construct()
    {
        parent::__construct("sla_solucao", ["slas_tempo", "slas_desc"], "slas_cod", false);
    }

    
}