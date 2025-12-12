<?php

namespace OcomonApi\Models;

use CoffeeCode\DataLayer\DataLayer;

/**
 * OcoMon Api | Class xxx Active Record Pattern
 *
 * @author Flávio Ribeiro <flaviorib@gmail.com>
 * @package OcomonApi\Models
 */
class Nivel extends DataLayer
{
    /**
     * User constructor.
     */
    public function __construct()
    {
        parent::__construct("nivel", ["nivel_nome"], "nivel_cod", false);
    }

    public function users()
    {
        return (new User())->find("nivel = :nivel", "nivel={$this->nivel_cod}")->fetch(true);
    }

    
}