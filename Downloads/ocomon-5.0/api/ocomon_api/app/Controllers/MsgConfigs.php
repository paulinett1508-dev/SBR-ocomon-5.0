<?php

namespace OcomonApi\Controllers;

use OcomonApi\Core\OcomonApi;
use OcomonApi\Models\MsgConfig;

class MsgConfigs extends OcomonApi
{
    public function __construct()
    {
        parent::__construct();
    }

    
    public function read(array $data): void
    {
        if (empty($data['event'])) {
            $this->call(
                400,
                "invalid_data",
                "É necessário informar o nome do evento que deseja consultar"
            )->back();
            return;
        }
        
        $msg = (new MsgConfig())->findByEvent($data['event']);

        if ($msg) {
            $response['event'] = $msg->msg_event;
            $response['from_name'] = $msg->msg_fromname;
            $response['reply_to'] = $msg->msg_replyto;
            $response['subject'] = $msg->msg_subject;
            $response['body'] = $msg->msg_body;

            $this->back($response);
            return;
        }

        $this->call(
            400,
            "not_found",
            "Evento não encontrado"
        )->back();
        return;
    }

}