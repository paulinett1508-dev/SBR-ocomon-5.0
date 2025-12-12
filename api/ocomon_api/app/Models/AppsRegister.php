<?php

namespace OcomonApi\Models;

use CoffeeCode\DataLayer\DataLayer;

/**
 * OcoMon Api | Class AppsRegister Active Record Pattern
 *
 * @author Flavio Ribeiro <flaviorib@gmail.com>
 * @package OcomonApi\Models
 */
class AppsRegister extends DataLayer
{
    /**
     * AppsRegister constructor.
     */
    public function __construct()
    {
        parent::__construct("apps_register", ["app", "controller", "methods"], "id", false);
    }

    /**
     * @param string $app
     * @param string $columns
     * @return null|AppsRegister
     */
    public function findByApp(string $app, string $columns = "*"): ?AppsRegister
    {
        $find = $this->find("app = :app", "app={$app}", $columns);
        return $find->fetch();
    }

    /**
     * @param string $app
     * @param string $controller
     * @param string $columns
     * @return null|AppsRegister
     */
    public function findByAppAndController(string $app, string $controller, string $columns = "*"): ?AppsRegister
    {
        $find = $this->find("app = :app AND controller = :controller", "app={$app}&controller={$controller}", $columns);
        return $find->fetch();
    }


    /**
     * hasRegisteredMethod
     *
     * @param string $method
     * @param string $methods
     * 
     * @return bool
     */
    private function hasRegisteredMethod(string $method, string $methods): bool
    {
        $array = explode(",", $methods);

        if (in_array($method, $array))
            return true;
        
        return false;
    }

    /**
     * methodAllowed
     *
     * @param string $app
     * @param string $controller
     * @param string $method
     * 
     * @return bool
     */
    public function methodAllowedByApp(string $app, string $controller, string $method): bool
    {
        if ($this->findByAppAndController($app, $controller)) {

            if ($this->hasRegisteredMethod($method, $this->findByAppAndController($app, $controller)->methods))
                return true;
        }

        return false;
    }

}
