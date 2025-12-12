<?php

namespace OcomonApi\Models;

use OcomonApi\Models\Sla;
use CoffeeCode\DataLayer\DataLayer;

/**
 * OcoMon Api | Class User Active Record Pattern
 *
 * @author Flavio Ribeiro <flaviorib@gmail.com>
 * @package OcomonApi\Models
 */
class ResponseLevel extends DataLayer
{
    /**
     * User constructor.
     */
    public function __construct()
    {
        parent::__construct("prioridades", ["prior_nivel", "prior_sla"], "prior_cod", false);
    }

    public function sla(): ?Sla
    {
        if ($this->data()->prior_sla)
            return (new Sla())->findById($this->data()->prior_sla) ?? null;
        return null;
    }
}