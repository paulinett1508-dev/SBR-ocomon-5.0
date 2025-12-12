<?php

namespace OcomonApi\WebControllers;

use OcomonApi\Core\OcomonWeb;
use OcomonApi\Models\User;

class Users extends OcomonWeb
{

    public function __construct()
    {
        parent::__construct();
        if ($this->user->data()->nivel != 1) {
            exit("User not allowed");
        }
        
    }


    public function list(): Object
    {
        $users = (new User())->find()->fetch(true);

        $return = [];

        /** @var User $app */
        foreach ($users as $user) {

            $return[] = $user->data();
        }

        $return = (Object)$return;
        return $return;
    }


    public function me(): Object
    {
        return $this->user->data();
    }


    public function create(array $data): bool
    {
        $data = filter_var_array($data, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $now = date("Y-m-d H:i:s");

        if (empty($data["fullname"])) {
            $this->message = "Nao informou o nome do usuário";
            return false;
        }

        if (empty($data["login"])) {
            $this->message = "Nao informou o nome de acesso do usuário";
            return false;
        }

        if (empty($data["email"])) {
            $this->message = "Nao informou o email do usuário";
            return false;
        }

        if (empty($data["password"])) {
            $this->message = "Nao informou a senha";
            return false;
        }

        if (empty($data["level"])) {
            $this->message = "Nao informou o nível";
            return false;
        }

        // if (empty($data["phone"])) {
        //     $this->message = "Nao informou o telefone";
        //     return false;
        // }

        if (empty($data["primary_area"])) {
            $this->message = "Nao informou a área primária";
            return false;
        }


        $user = new User();
        $user->login = $data['login'];
        $user->nome = $data['fullname'];
        $user->email = $data['email'];
        $user->password = pass_hash(md5($data['password']));
        $user->data_inc = $now;
        $user->fone = $data['phone'] ?? null;
        $user->nivel = $data['level'];
        $user->AREA = $data['primary_area'];

        var_dump($user);
        return false;


    }

}