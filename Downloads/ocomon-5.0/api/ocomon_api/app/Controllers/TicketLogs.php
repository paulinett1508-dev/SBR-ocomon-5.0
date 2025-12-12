<?php

namespace OcomonApi\Controllers;

use OcomonApi\Models\Ticket;
use OcomonApi\Core\OcomonApi;
use OcomonApi\Models\TicketLog;

class TicketLogs extends OcomonApi
{
    public function __construct()
    {
        parent::__construct();
    }


    public function createFirst(int $ticketNumber): bool
    {

        // $ticketNumber = $data['id'];
        // $type = $data['type'];
        // $autoRecord = $data['auto_record'];

        /** @var Ticket $ticket */
        $ticket = (new Ticket())->findById($ticketNumber);
        if (!$ticket) {
            $this->call(
                400,
                "invalid_data",
                // $newStage->message()->getText()
                "Número de chamado inexistente"
            )->back();
            return false;
        }

        /** @var TicketLog $ticketLog*/
        $log = (new TicketLog())->lastLog($ticketNumber);

        if (!$log) {
            /* Criar log */

            $log = new TicketLog();
            $log->log_numero = $ticket->numero;
            $log->log_quem = $this->user->data()->user_id;
            $log->log_data = $ticket->data_abertura;
            $log->log_descricao = $ticket->descricao;
            $log->log_prioridade = ($ticket->has("oco_prior") ? $ticket->oco_prior : null);
            $log->log_area = $ticket->sistema;
            $log->log_cliente = ($ticket->has("client") ? $ticket->data()->client : null);
            $log->log_problema = ($ticket->has("problema") ? $ticket->problema : null);
            $log->log_unidade = ($ticket->has("instituicao") ? $ticket->instituicao : null);
            $log->log_etiqueta = ($ticket->has("equipamento") ? $ticket->equipamento : null);
            $log->log_contato = ($ticket->has("contato") ? $ticket->contato : null);
            $log->log_contato_email = ($ticket->has("contato_email") ? $ticket->contato_email : null);
            $log->log_telefone = ($ticket->has("telefone") ? $ticket->telefone : null);
            $log->log_departamento = ($ticket->has("local") ? $ticket->local : null);
            $log->log_responsavel = ($ticket->has("operador") ? $ticket->operador : null);
            $log->log_data_agendamento = $ticket->oco_scheduled_to;
            $log->log_status = $ticket->data()->status;
            $log->log_tipo_edicao = 0;

            if (!$log->save()) {
                // var_dump($log);
                $this->call(
                    400,
                    "invalid_data",
                    // $newStage->message()->getText()
                    "Problemas na tentativa de gravar o registro de log"
                )->back();
                return false;
            }

            // $this->back(["ticketLog" => $log->data()]);
            return true;
        }
        // $this->back(["ticketLog" => "Já existe registro de log"]);
        return true;
    }


    public function createNew(): void
    {
        // $logNew = new TicketLog();

        // $logNew->log_numero = $ticketNumber;
        // $logNew->log_quem = $this->user->data()->user_id;
        // $logNew->log_prioridade = ($log->log_prioridade && $log->log_prioridade != $ticket->oco_prior ? $ticket->oco_prior : null);
        // $logNew->log_area = ($log->log_area && $log->log_area != $ticket->sistema ? $ticket->sistema : null);
        // $logNew->log_problema = ($log->log_problema && $log->log_problema != $ticket->problema ? $ticket->problema : null);
        // $logNew->log_unidade = ($log->log_unidade && $log->log_unidade != $ticket->instituicao ? $ticket->instituicao : null);
        // $logNew->log_etiqueta = ($log->log_etiqueta && $log->log_etiqueta != $ticket->equipamento ? $ticket->equipamento : null);
        // $logNew->log_contato = ($log->log_contato && $log->log_contato != $ticket->contato ? $ticket->contato : null);
        // $logNew->log_contato_email = ($log->log_contato_email && $log->log_contato_email != $ticket->contato_email ? $ticket->contato_email : null);
        // $logNew->log_telefone = ($log->log_telefone && $log->log_telefone != $ticket->telefone ? $ticket->telefone : null);
        // $logNew->log_departamento = ($log->log_departamento && $log->log_departamento != $ticket->local ? $ticket->local : null);
        // $logNew->log_status = ($log->log_status && $log->log_status != $ticket->data()->status ? $ticket->data()->status : null);

        // if (!$logNew->save()) {
        //     var_dump($logNew);
        //     $this->call(
        //         400,
        //         "invalid_data",
        //         "Problemas na tentativa de gravar o novo registro de log"
        //     )->back();
        //     return false;
        // }
        // $this->back(["ticketLog" => $logNew->data()]);
        // return true;
    }

}