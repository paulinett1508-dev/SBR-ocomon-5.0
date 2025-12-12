<?php

namespace OcomonApi\Models;

use OcomonApi\Models\User;
use CoffeeCode\DataLayer\DataLayer;

/**
 * OcoMon Api | Class User Active Record Pattern
 *
 * @author Flavio Ribeiro <flaviorib@gmail.com>
 * @package OcomonApi\Models
 */
class Entry extends DataLayer
{
    /**
     * Entry constructor.
     */
    public function __construct()
    {
        parent::__construct(
            "assentamentos",
            ["ocorrencia", "assentamento", "responsavel", "tipo_assentamento"],
            "numero",
            false
        );
    }

    public function author(): ?User
    {
        return (new User())->findById($this->data()->responsavel);
    }

    
}