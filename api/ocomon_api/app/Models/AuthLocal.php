<?php

namespace OcomonApi\Models;

use OcomonApi\Models\User;
use OcomonApi\Support\Message;
use CoffeeCode\DataLayer\DataLayer;

/**
 * Class Auth
 * @package OcomonApi\Models
 */
class AuthLocal extends DataLayer
{
    /**
     * Auth constructor.
     */
    public function __construct()
    {
        parent::__construct("usuarios", ["login", "password"], "user_id", false);
    }

    /**
     * @return null|User
     */
    public static function user(string $login, string $password): ?User
    {
        /**
         * @var User $user
         */
        $user = (new User())->findByLogin($login)->fetch();

        if (!$user) {
            return null;
        }

        if ($user->nivel > 3) {
            return null;
        }

        // if (passwd_verify($password, $user->hash)) {
        //     return $user;
        // }
        
        return $user;
    }

   

    /**
     * @param string $login
     * @param string $password
     * @param int $level
     * @return \OcomonApi\Models\User|null
     */
    public function attempt(string $login, string $password, int $level = 1): ?User
    {
        $user = (new User())->findByLogin($login);
        $message = new Message();

        if (!$user) {
            $message->error("O login informado não está cadastrado");
            return null;
        }

        // if (!passwd_verify($password, $user->hash)) {
        //     $message->error("A senha informada não confere");
        //     return null;
        // }
        
        if ($user->data()->nivel > $level) {
            // $this->message->error("Desculpe, mas você não tem permissão para logar-se aqui");
            $message->error("Desculpe, mas você não tem permissão para logar-se aqui");
            return null;
        }

        return $user;
    }


}