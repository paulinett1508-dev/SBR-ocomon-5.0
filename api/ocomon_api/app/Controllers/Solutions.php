<?php

namespace OcomonApi\Controllers;

use OcomonApi\Core\OcomonApi;
use OcomonApi\Models\Solution;

class Solutions extends OcomonApi
{
    public function __construct()
    {
        parent::__construct();
    }

    
    public function read(array $data): void
    {

        var_dump($data);
        
        if (empty($data['numero'])) {
            $this->call(
                400,
                "invalid_data",
                "É necessário informar o número do ticket que deseja consultar"
            )->back();
            return;
        }
        
        // $solution = (new Solution())->findById($data['numero']);
        $solution = (new Solution())->findByNumber($data['numero']);

        if ($solution) {
            $response['ticket'] = $solution->numero;
            $response['problem'] = $solution->problema;
            $response['solution'] = $solution->solucao;
            $response['date'] = $solution->data()->date;
            $response['responsible'] = $solution->responsavel;

            $this->back($response);
            return;
        }

        $this->call(
            400,
            "not_found",
            "Solucao não encontrada"
        )->back();
        return;
    }

}