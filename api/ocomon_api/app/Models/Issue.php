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
class Issue extends DataLayer
{

    /**
     * User constructor.
     */
    public function __construct()
    {
        parent::__construct("problemas", ["problema"], "prob_id", false);
    }

    public function sla(): ?Sla
    {
        if ($this->data()->prob_sla)
            return (new Sla())->findById($this->data()->prob_sla) ?? null;
        return null;
    }
}