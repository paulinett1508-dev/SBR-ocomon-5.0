<?php

namespace OcomonApi\Controllers;

use OcomonApi\Core\OcomonApi;

class LoggedUsers extends OcomonApi
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * List user data
     */
    public function index(): void
    {
        /* $response = $this->headers;
        $this->back($response); */
        $user = $this->user->data();

        unset($user->password);
        $response['user'] = $user;
        $response['user']->area_info = $this->user->area()->data();
        $response['user']->nivel_info = $this->user->nivel()->nivel_nome;

        $this->back($response);
        return;
    }

    public function update(array $data): void
    {
        /* $json["data"] = $data;
        $this->back($data); */

        $request = $this->requestLimit("UsersUpdate", 5, 60);
        if (!$request) {
            return;
        }

        // $data = filter_var_array($data, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $data = filter_var_array($data, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        $levelList = ["1", "2", "3", "5"];
        if (!empty($data["nivel"]) && !in_array($data['nivel'], $levelList)) {
            $this->call(
                400,
                "invalid_data",
                "Favor informe o nível como 1,2,3 ou 5"
            )->back();
            return;
        }

        if (!empty($data['data_inc'])) {
            $check = \DateTime::createFromFormat("Y-m-d", $data['data_inc']);
            if (!$check || $check->format('Y-m-d') != $data['data_inc']) {
                $this->call(
                    400,
                    "invalid_data",
                    "Favor informar uma data válida"
                )->back();
                return;
            }
        }

        $this->user->nome = (!empty($data['nome']) ? $data['nome'] : $this->user->nome);
        $this->user->email = (!empty($data['email']) ? $data['email'] : $this->user->email);
        $this->user->fone = (!empty($data['fone']) ? $data['fone'] : $this->user->fone);
        $this->user->data_inc = (!empty($data['data_inc']) ? $data['data_inc'] : $this->user->data_inc);
        $this->user->nivel = (!empty($data['nivel']) ? $data['nivel'] : $this->user->nivel);

        if (!$this->user->save()) {
            $this->call(
                400,
                "invalid_data",
                // $this->user->message()->getText();
                "Verifique seus dados"
            )->back();
            return;
        }

        $this->index();
    }
}