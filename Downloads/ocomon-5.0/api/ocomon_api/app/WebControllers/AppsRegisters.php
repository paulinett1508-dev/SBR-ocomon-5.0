<?php

namespace OcomonApi\WebControllers;

use OcomonApi\Core\OcomonWeb;
use OcomonApi\Models\AppsRegister;

class AppsRegisters extends OcomonWeb
{

    public function __construct()
    {
        parent::__construct();
        
    }


    public function list(): Object
    {
        $appsRegisters = (new AppsRegister())->find()->fetch(true);

        $return = [];

        /** @var AppsRegister $app */
        foreach ($appsRegisters as $app) {

            $return[] = $app->data();
        }

        $return = (Object)$return;
        return $return;
    }


    /**
     * save
     *
     * @param array $data
     * 
     * @return bool
     */
    public function save(array $data): bool
    {
        $data = filter_var_array($data, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        $id = (isset($data['id']) ? $data['id']: "");
        $app = (isset($data['app']) ? $data['app']: "");
        $controller = (isset($data['controller']) ? $data['controller'] : "");
        $methods = (isset($data['methods']) ? $data['methods']: "");

        if ((empty($app) || empty($controller) || empty ($methods)) && empty($id)) {
            echo "Dados incompletos";
            return false;
        }

        if (!empty($id)) {
            $find = (new AppsRegister())->findById($id);
        } else {
            $find = (new AppsRegister())->findByAppAndController($app, $controller);
        }

        /* Se o registro existe, entao atualiza */

        if ($find) {
            
            if (!empty($app)) {
                $find->app = $app;
            }
            $find->methods = $methods;
            if (!$find->save()) {
                echo "problemas em atualizar o registro";
                return false;
            }
            return true;
        }
            
        /* Se o registro nao existe, entao grava */
        $appRegister = new AppsRegister();
        
        $appRegister->app = $app;
        $appRegister->controller = $controller;
        $appRegister->methods = $methods;

        if (!$appRegister->save()) {
            echo "Problema na tentativa de gravar o registro";
            return false;
        }
        return true;
    }


    /**
     * delete
     *
     * @param int $id
     * 
     * @return bool
     */
    public function delete(int $id): bool
    {
        if ($id && filter_var($id, FILTER_VALIDATE_INT)) {

            $appRegister = (new AppsRegister())->findById($id);
            
            if (!$appRegister) {
                // $this->message("O registro não existe");
                echo "O registro não existe";
                return false;
            }

            if (!$appRegister->destroy()){
                echo "problemas em remover o registro";
                return false;
            }
            return true;
        }
        return false;
    }
}