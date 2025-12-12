<?php

namespace OcomonApi\Core;

use OcomonApi\Models\AuthWeb;
use OcomonApi\Core\Controller;

class OcomonWeb extends Controller
{
    
    protected $user;
    
    /**
     * __construct
     *
     * @return void
     */
    public function __construct()
    {
        $this->user = AuthWeb::user();

        if (!$this->user || $this->user->data()->nivel > 3) {
            // $this->message->error("Para acessar é preciso logar-se com nível de administração")->flash();
            // redirect("/admin/login");
            echo "Não autenticado!";
            exit;
        }
    }

}