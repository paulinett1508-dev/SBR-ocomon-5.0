<?php

namespace OcomonApi\Models;

use OcomonApi\Models\User;
use OcomonApi\Support\Message;
use OcomonApi\Models\AccessToken;
use CoffeeCode\DataLayer\DataLayer;

/**
 * Class Auth
 * @package OcomonApi\Models
 */
class Auth extends DataLayer
{
    /**
     * Auth constructor.
     */
    public function __construct()
    {
        parent::__construct("access_tokens", ["token"], "id", true);
    }

    /**
     * @return null|User
     */
    public static function user(string $login, string $app, string $token): ?User
    {
        /**
         * @var User $user
         */
        $user = (new User())->findByLogin($login)->fetch();

        if (!$user) {
            return null;
        }

        if ($user->data()->nivel > 3) {
            return null;
        }

        /* if (passwd_verify($password, $user->hash)) {
            return $user;
        } */

        $accessToken = new AccessToken();

        if ($accessToken->is_allowed($login, $app, $token)) {
            return $user;
        }
        return null;
    }


    /**
     * @param string $login
     * @param string $app
     * @param string $token
     * @return \OcomonApi\Models\User|null
     */
    // public function attempt(string $login, string $password, int $level = 1): ?User
    public function attempt(string $login, string $app, string $token): ?User
    {
        $user = (new User())->findByLogin($login);
        $message = new Message();

        if (!$user) {
            $message->error("O login informado não está cadastrado");
            var_dump($message);
            return null;
        }

        if ($user->data()->nivel > 3) {
            $message->error("O usuário não está habilitado para acessar esse recurso");
            var_dump($message);
            return null;
        }

        $accessToken = (new AccessToken())->findByUserAndApp($user->data->user_id, $app);

        if (!$accessToken) {
            $message->error("Token não encontrado");
            var_dump($message);
            return null;
        }

        if (!$accessToken->is_allowed($login, $app, $token)) {
            $message->error("Acesso não permitido");
            var_dump($message);
            return null;
        }

        // if ($user->data()->nivel > $level) {
        //     $message->error("Desculpe, mas você não tem permissão para logar-se aqui");
        //     var_dump($message);
        //     return null;
        // }

        
        return $user;
    }




}