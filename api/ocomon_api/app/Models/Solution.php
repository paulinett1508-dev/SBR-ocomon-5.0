<?php

namespace OcomonApi\Models;

use CoffeeCode\DataLayer\DataLayer;

/**
 * OcoMon Api | Class Solution Active Record Pattern
 *
 * @author Flavio Ribeiro <flaviorib@gmail.com>
 * @package OcomonApi\Models
 */
class Solution extends DataLayer
{
    /**
     * Solution constructor.
     */
    public function __construct()
    {
        parent::__construct("solucoes", ["problema", "solucao", "responsavel"], "numero", false);
    }

    public function findByNumber(int $number, string $columns = "numero, problema, solucao, data as date, responsavel"): ?Solution
    {
        $find = $this->find("numero = :number", "number={$number}", $columns);
        return $find->fetch();
    }

    
}