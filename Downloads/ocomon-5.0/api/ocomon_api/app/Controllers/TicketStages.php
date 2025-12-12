<?php

namespace OcomonApi\Controllers;

use OcomonApi\Models\Ticket;
use OcomonApi\Core\OcomonApi;
use OcomonApi\Models\TicketStage;

class TicketStages extends OcomonApi
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * List Stages data
     */
    public function index(): void
    {
        $stages = (new TicketStage())->find()->fetch(true);

        /** @var TicketStage $issue */
        foreach ($stages as $stage) {
            $response[]['stage'] = $stage->data();
        }
        $this->back($response);
        return;
    }


    public function read($data): void
    {
        if (empty($data['id']) || !filter_var($data['id'], FILTER_VALIDATE_INT)) {
            $this->call(
                400,
                "invalid_data",
                "É necessário informar o numero do chamado"
            )->back();
            return;
        }

        $lastStageId = (new TicketStage())->lastStageId($data['id']);

        $stages = (new TicketStage())->findByTicket($data['id'])->fetch(true);

        if ($stages) {

            $response['last_id'] = $lastStageId;
            /** @var TicketStage $stage */
            foreach ($stages as $stage) {
                $response[]['stage'] = $stage->data();
            }
            
            $this->back($response);
            return;
        }
        

        $this->call(
            400,
            "not_found",
            "Stage não encontrado"
        )->back();
        return;
    }


    public function last($data): void
    {
        if (empty($data['id']) || !filter_var($data['id'], FILTER_VALIDATE_INT)) {
            $this->call(
                400,
                "invalid_data",
                "É necessário informar o numero do chamado"
            )->back();
            return;
        }

        $stage = (new TicketStage())->lastStage($data['id']);

        $response['stage'] = $stage->data();

        $this->back($response);
        return;

    }


    /**
    * @param array $data
    */
    public function create(array $data): bool
    {
        // $request = $this->requestLimit("ticketStagesUpdate", 5, 60);
        // if (!$request) {
        //     return;
        // }

        $data = filter_var_array($data, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        $ticketNumber = $data['ticket'];
        $type = $data['type'];
        $ticketStatus = $data['status'];
        $date = date("Y-m-d H:i:s");

        $typeList = ["start", "stop"];
        if (!empty($data["type"]) && !in_array($data["type"], $typeList)) {
            $this->call(
                400,
                "invalid_data",
                "Tipos permitidos: start|stop"
            )->back();
            return false;
        }
        
        if (!empty($data["specific_date"])) {
            $check = \DateTime::createFromFormat("Y-m-d H:i:s", $data["specific_date"]);
            if (!$check || $check->format("Y-m-d H:i:s") != $data["specific_date"]) {
                $this->call(
                    400,
                    "invalid_data",
                    "Favor informe uma data específica válida"
                )->back();
                return false;
            }
            $date = $data['specific_date'];
        }


        $newStage = new TicketStage();
        $lastStage = (new TicketStage())->lastStage($data['ticket']);

        if (!$lastStage) {
            /* Nenhum registro encontrado para o chamado no stages */
            if ($data['type'] == 'start') {
                /* inserçao de novo registro de start */
                $newStage->ticket = (int) $ticketNumber;
                $newStage->date_start = $date;
                $newStage->status_id = $ticketStatus;

                if (!$newStage->save()) {
                    $this->call(
                        400,
                        "invalid_data",
                        // $newStage->message()->getText()
                        "Problemas na tentativa de gravar o registro"
                    )->back();
                    return false;
                }
        
                return true;
                // $this->back(["newStage" => $newStage->data()]);

            } else {
                /* Novo Registro de stop */
                $ticket = (new Ticket())->findById($data['ticket']);
                $openingDate = ($ticket->oco_real_open_date ? $ticket->oco_real_open_date : $ticket->data_abertura);

                $newStage->ticket = $ticketNumber;
                $newStage->date_start = $openingDate;
                $newStage->date_stop = $date;
                $newStage->status_id = $ticketStatus;

                if (!$newStage->save()) {
                    $this->call(
                        400,
                        "invalid_data",
                        // $newStage->message()->getText()
                        "Problemas na tentativa de gravar o registro"
                    )->back();
                    return false;
                }
                
                return true;
                // $this->back(["newStage" => $newStage->data()]);
            }
        } else {
            /* Se há registro no stages para o chamado */
            if (!empty($lastStage->date_stop)) {
                if ($type == "start") {
                    $newStage->ticket = $ticketNumber;
                    $newStage->date_start = $date;
                    $newStage->status_id = $ticketStatus;

                    if (!$newStage->save()) {
                        $this->call(
                            400,
                            "invalid_data",
                            // $newStage->message()->getText()
                            "Problemas na tentativa de gravar o registro"
                        )->back();
                        return false;
                    }
            
                    return true;
                    // $this->back(["newStage" => $newStage->data()]);
                } else {
                    $this->call(
                        400,
                        "invalid_data",
                        // $newStage->message()->getText()
                        "O tipo de stage deveria ser: start"
                    )->back();
                    return false;
                }
            } else {
                if ($type == "stop") {

                    $lastStage->ticket = $ticketNumber;
                    $lastStage->date_stop = $date;
                    $lastStage->status_id = $ticketStatus;

                    if (!$lastStage->save()) {
                        $this->call(
                            400,
                            "invalid_data",
                            // $lastStage->message()->getText()
                            "Problemas na tentativa de gravar o registro"
                        )->back();
                        return false;
                    }
            
                    $this->back(["lastStage" => $lastStage->data()]);
                } else {
                    $this->call(
                        400,
                        "invalid_data",
                        // $newStage->message()->getText()
                        "O tipo de stage deveria ser: stop"
                    )->back();
                    return false;
                }
            }
        }
    }
}