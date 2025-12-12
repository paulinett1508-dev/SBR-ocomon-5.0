<?php

namespace OcomonApi\Models;

use OcomonApi\Models\User;
use OcomonApi\Core\Session;
use CoffeeCode\DataLayer\DataLayer;

/**
 * Class Auth
 * @package OcomonApi\Models
 */
class AuthWeb extends DataLayer
{
    /**
     * Auth constructor.
     */
    public function __construct()
    {
        parent::__construct("usuarios", ["login", "nome", "password", "email"], "user_id", false);
    }

    /**
     * @return null|User
     */
    public static function user(): ?User
    {
        $session = new Session();
        if (!$session->has("s_uid")) {
            return null;
        }

        return (new User())->findById($session->s_uid);
    }

    /**
     * log-out
     */
    public static function logout(): void
    {
        $session = new Session();
        $session->unset("s_uid");
    }

}