<?php

namespace OcomonApi\Controllers;

use OcomonApi\Models\File;
use OcomonApi\Core\OcomonApi;
use OcomonApi\Models\AppsRegister;

class Files extends OcomonApi
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * List files data by ticket
     */
    public function findByTicket(array $data): void
    {
        
        if (empty($data['ticket']) || !filter_var($data['ticket'], FILTER_VALIDATE_INT)) {
            $this->call(
                400,
                "invalid_data",
                "É necessário informar o número do chamado que deseja consultar"
            )->back();
            return;
        }

        
        $files = (new File())->findByTicket($data['ticket']);

        /** @var File $files */
        foreach ($files as $file) {
            $response[]['file'] = $file->data();
        }

        $this->back($response);
        return;
    }


    public function create(array $data): ?Files
    {
        
        /** Checagem para saber se o método pode ser acessado para o app informado na conexao */
        $app = (new AppsRegister())->methodAllowedByApp($this->headers["app"], get_class($this), __FUNCTION__);
        if (!$app) {
            return $this->call(
                401,
                "access_not_allowed",
                "Esse APP não está registrado para esse tipo de acesso"
            )->back();
            // return $this;
            // return;
        }
        
        
        if (empty($data['ticket']) || !filter_var($data['ticket'], FILTER_VALIDATE_INT)) {
            $this->call(
                400,
                "invalid_data",
                "É necessário informar o número do chamado para vincular o arquivo"
            )->back();
            return null;
        }

        $file = new File();
        $file->img_oco = $data['ticket'];
        $file->img_nome = $data['name'];
        $file->img_tipo = $data['type'];
        $file->img_bin = $data['bin'];
        $file->img_size = $data['size'];

        $file->save();

        $this->back($file->data());
        return $this;
    }
    



}