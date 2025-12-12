<?php

namespace OcomonApi\Models;

use OcomonApi\Models\Area;
use OcomonApi\Models\Nivel;
use CoffeeCode\DataLayer\DataLayer;

/**
 * OcoMon Api | Class User Active Record Pattern
 *
 * @author Flávio Ribeiro <flaviorib@gmail.com>
 * @package OcomonApi\Models
 */
class User extends DataLayer
{
    /**
     * User constructor.
     */
    public function __construct()
    {
        parent::__construct("usuarios", ["login", "nome", "password", "AREA"], "user_id", false);
    }

    /**
     * @param string $login
     * @param string $columns
     * @return null|User
     */
    public function findByLogin(string $login, string $columns = "*"): ?User
    {
        $find = $this->find("login = :login", "login={$login}", $columns);
        return $find->fetch();
    }


    public function area(): ?Area
    {
        return (new Area())->findById($this->data()->AREA);
    }


    public function nivel(): ?Nivel
    {
        return (new Nivel())->findById($this->data()->nivel);
    }


}