<?php

namespace OcomonApi\Models;

use CoffeeCode\DataLayer\DataLayer;
use OcomonApi\Models\ResponseLevel;

/**
 * OcoMon Api | Class Department Active Record Pattern
 *
 * @author Flávio Ribeiro <flaviorib@gmail.com>
 * @package OcomonApi\Models
 */
class Department extends DataLayer
{
    /**
     * User constructor.
     */
    public function __construct()
    {
        parent::__construct("localizacao", ["local"], "loc_id", false);
    }

    public function sla(): ?Sla
    {
        if ($this->data()->loc_prior) {
            $responseLevel = (new ResponseLevel())->findById($this->data()->loc_prior);
            if ($responseLevel)
                return (new Sla())->findById($responseLevel->data()->prior_sla) ?? null;
        }
            
        return null;
    }

    
}