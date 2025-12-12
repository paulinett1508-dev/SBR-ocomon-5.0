<?php

namespace OcomonApi\Controllers;

use DateTime;
use Exception;
use OcomonApi\Models\Area;
use OcomonApi\Models\Unit;
use OcomonApi\Models\User;
use OcomonApi\Models\Issue;
use OcomonApi\Models\Client;
use OcomonApi\Models\Config;
use OcomonApi\Models\Status;
use OcomonApi\Models\Ticket;
use OcomonApi\Support\Email;
use OcomonApi\Core\OcomonApi;
use OcomonApi\Models\Channel;
use OcomonApi\Models\Holiday;
use OcomonApi\Models\Priority;
use OcomonApi\Models\MsgConfig;
use OcomonApi\Support\Worktime;
use OcomonApi\Models\Department;
use OcomonApi\Models\TicketStage;
use OcomonApi\Models\AppsRegister;
use CoffeeCode\Paginator\Paginator;
use OcomonApi\Controllers\Holidays;
use OcomonApi\Controllers\InputTags;

class Tickets extends OcomonApi
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * List tickets data
     */
    public function index(): void
    {
        $where = "";
        $params = "";
        $values = $this->headers;

        //by number
        if (!empty($values['number']) && $number = filter_var($values['number'], FILTER_VALIDATE_INT)) {
            $where .= " AND numero = :numero";
            $params .= "&numero={$number}";
        }

        //by area
        if (!empty($values['area']) && $area = filter_var($values['area'], FILTER_VALIDATE_INT)) {
            $where .= " AND sistema = :area";
            $params .= "&area={$area}";
        }

        //by status
        if (!empty($values['status']) && $status = filter_var($values['status'], FILTER_VALIDATE_INT)) {
            $where .= " AND status = :status";
            $params .= "&status={$status}";
        }

        //by department
        if (!empty($values['department']) && $department = filter_var($values['department'], FILTER_VALIDATE_INT)) {
            $where .= " AND local = :department";
            $params .= "&department={$department}";
        }
        
        try {
            
            $tickets = (new Ticket())->find("1 = :one{$where}", "one=1{$params}");

            if (!$tickets->count()) {
                $this->call(
                    400,
                    "not_found",
                    "Nenhum registro retornado de acordo com os termos pesquisados"
                )->back(['count' => 0]);
                return;
            }

            $page = (!empty($values['page']) ? $values['page'] : 1);
            $pager = new Paginator(url('/tickets/'));
            $pager->pager($tickets->count(), 10, $page);

            $response['results'] = $tickets->count();
            $response['page'] = $pager->page();
            $response['pages'] = $pager->pages();

            /** @var Ticket $ticket */
            foreach ($tickets->limit($pager->limit())->offset($pager->offset())->order('numero')->fetch(true) as $ticket) {
                $arrayResponse['number'] = $ticket->numero;
                $arrayResponse['opening_date'] = $ticket->data_abertura;
                $arrayResponse['priority'] = $ticket->priority()->pr_desc ?? "";
                $arrayResponse['area'] = $ticket->area()->sistema ?? "";
                $arrayResponse['issue'] = $ticket->issue()->problema ?? "";
                $arrayResponse['description'] = $ticket->descricao;
                $arrayResponse['asset_tag'] = $ticket->equipamento;
                $arrayResponse['asset_unit'] = $ticket->unit()->inst_nome ?? "";
                $arrayResponse['open_by'] = $ticket->openedBy()->nome;
                $arrayResponse['phone'] = $ticket->telefone;
                $arrayResponse['contact_name'] = $ticket->contato;
                $arrayResponse['contact_email'] = $ticket->contato_email;
                $arrayResponse['department'] = $ticket->department()->local ?? "";
                $arrayResponse['operator'] = $ticket->operator()->nome ?? "";
                $arrayResponse['status'] = $ticket->status()->status;
                $arrayResponse['channel'] = $ticket->channel()->name ?? "";
                $arrayResponse['treatment_date'] = $ticket->data_atendimento;
                $arrayResponse['closure_date'] = $ticket->data_fechamento;
                $arrayResponse['is_frozen'] = $ticket->isTicketFrozen();
                $arrayResponse['sla_response'] = "";
                if ($ticket->slaResponse())
                    $arrayResponse['sla_response'] = $ticket->slaResponse()->data()->slas_desc;
                $arrayResponse['sla_solution'] = "";
                if ($ticket->slaSolution())
                    $arrayResponse['sla_solution'] = $ticket->slaSolution()->data()->slas_desc;

                /* Vai depender da configuracao do sistema - area origem ou area destino */
                $arrayResponse['worktime_to_consider'] = $ticket->activeWorktimeProfile()->data()->name;
                $arrayResponse['lifetime'] = $this->lifetime($ticket->numero);

                if ($ticket->entries())
                    /** @var Entry $entry */
                    foreach ($ticket->entries() as $entry){
                        $responseEntry['entry'] = $entry->data()->assentamento;
                        $responseEntry['author'] = $entry->author()->data()->nome;
                        $responseEntry['date'] = $entry->data()->date;
                        $responseEntry['type'] = $entry->data()->tipo_assentamento;
                        $arrayResponse['entries'][] = $responseEntry;
                    }

                $response['tickets'][] = $arrayResponse;
            }

            $this->back($response);
            return;
        }
        catch (Exception $e) {
            $this->call(
                400,
                "invalid_data",
                "Ocorreu algum erro no método:findByNumber"
            )->back();
            return;
        }
    }

    public function read(array $data): void
    {
        /** Checagem para saber se o método pode ser acessado para o app informado na conexao */
        $app = (new AppsRegister())->methodAllowedByApp($this->headers["app"], get_class($this), __FUNCTION__);
        if (!$app) {
            $this->call(
                401,
                "access_not_allowed",
                "Esse APP não está registrado para esse tipo de acesso"
            )->back();
            return;
        }
        
        if (empty($data['id']) || !filter_var($data['id'], FILTER_VALIDATE_INT)) {
            $this->call(
                400,
                "invalid_data",
                "É necessário informar o número do chamado que deseja consultar"
            )->back();
            return;
        }

        $ticket = (new Ticket())->findByNumber($data['id']);

        if ($ticket) {
            $response['number'] = $ticket->numero;
            $response['opening_date'] = $ticket->data_abertura;
            $response['priority'] = $ticket->priority()->pr_desc ?? "";
            $response['client'] = $ticket->client()->fullname ?? "";
            $response['area'] = $ticket->area()->sistema ?? "";
            $response['issue'] = $ticket->issue()->problema ?? "";
            $response['description'] = $ticket->descricao;
            $response['asset_tag'] = $ticket->equipamento;
            $response['asset_unit'] = $ticket->unit()->inst_nome ?? "";
            $response['open_by'] = $ticket->openedBy()->nome;
            $response['phone'] = $ticket->telefone;
            $response['contact_name'] = $ticket->contato;
            $response['contact_email'] = $ticket->contato_email;
            $response['department'] = $ticket->department()->local ?? "";
            $response['operator'] = $ticket->operator()->nome ?? "";
            $response['status'] = $ticket->status()->status;
            $response['channel'] = $ticket->channel()->name ?? "";
            $response['treatment_date'] = $ticket->data_atendimento;
            $response['closure_date'] = $ticket->data_fechamento;
            $response['is_frozen'] = $ticket->isTicketFrozen();
            $response['sla_response'] = "";
            if ($ticket->slaResponse())
                $response['sla_response'] = $ticket->slaResponse()->data()->slas_desc;
            $response['sla_solution'] = "";
            if ($ticket->slaSolution())
                $response['sla_solution'] = $ticket->slaSolution()->data()->slas_desc;
            /* Vai depender da configuracao do sistema - area origem ou area destino */
            $response['worktime_to_consider'] = $ticket->activeWorktimeProfile()->data()->name;
            $response['lifetime'] = $this->lifetime($ticket->numero);

            if ($ticket->entries())
                /** @var Entry $entry */
                foreach ($ticket->entries() as $entry){
                    $responseEntry['entry'] = $entry->data()->assentamento;
                    $responseEntry['author'] = $entry->author()->data()->nome;
                    $responseEntry['date'] = $entry->data()->date;
                    $responseEntry['type'] = $entry->data()->tipo_assentamento;
                    $response['entries'][] = $responseEntry;
                }

            // $response['last_entry'] = $ticket->lastEntry()->data() ?? null;
            $response['last_entry'] = ($ticket->lastEntry() ? $ticket->lastEntry()->data() : null);

            $response['solution'] = ($ticket->solution() ? $ticket->solution()->data() : null);

            // $response['subject_to_email'] = $this->subjectToEmail($ticket->numero, 'abertura-para-usuario');
            // $response['body_to_email'] = $this->bodyToEmail($ticket->numero, 'abertura-para-usuario');


            // if ($ticket->files()) {
            //     /** @var File $file */
            //     foreach ($ticket->files() as $file) {
            //         $responseFile['name'] = $file->data()->img_nome;
            //         $responseFile['type'] = $file->data()->img_tipo;
            //         $responseFile['size'] = $file->data()->img_size;
            //         // $responseFile['bin'] = bin2hex($file->data()->img_bin);
            //         $response['files'][] = $responseFile;
            //     }
            // }



            $this->back($response);
            return;
        }

        $this->call(
            400,
            "not_found",
            "Chamado não encontrado"
        )->back();
        return;
    }


    /**
     * envVars
     * Retorna um array nominado onde os indices são valores públicos para configuração do template
     *  para envio de e-mails automáticos pelo sistema
     * @param int $ticket
     * @return array
     */
    public function envVars(int $ticket): array
    {
        // if (empty($data['id']) || !filter_var($data['id'], FILTER_VALIDATE_INT)) {
        //     $this->call(
        //         400,
        //         "invalid_data",
        //         "É necessário informar o número do chamado que deseja consultar"
        //     )->back();
        //     return;
        // }
        
        $ticket = (new Ticket())->findByNumber($ticket);
        $vars = [];

        if ($ticket) {

            $config = (new Config())->findById(1);

            $vars['%numero%'] = $ticket->numero;
            $vars['%usuario%'] = $ticket->contato;
            $vars['%contato%'] = $ticket->contato;
            $vars['%contato_email%'] = $ticket->contato_email;
            $vars['%descricao%'] = nl2br($ticket->descricao);
            // $vars['%descricao%'] = $ticket->descricao;
            $vars['%departamento%'] = $ticket->department()->local ?? "";
            $vars['%telefone%'] = $ticket->telefone;
            $vars['%site%'] = $config->data()->conf_ocomon_site;
            $vars['%area%'] = $ticket->area()->sistema ?? "";
            $vars['%area_email%'] = $ticket->area()->sis_email ?? "";
            $vars['%operador%'] = $ticket->operator()->nome ?? "";
            $vars['%editor%'] = $ticket->operator()->nome ?? "";
            $vars['%aberto_por%'] = $ticket->openedBy()->nome;
            $vars['%problema%'] = $ticket->issue()->problema ?? "";
            // $vars['%versao%'] = VERSAO;
            // $vars['%url%'] = getGlobalUri($conn, $ticket);
            // $vars['%linkglobal%'] = $vars['%url%'];

            $vars['%unidade%'] = $ticket->unit()->inst_nome ?? "";
            $vars['%etiqueta%'] = $ticket->equipamento;
            $vars['%patrimonio%'] = $vars['%unidade%'] . "&nbsp;" . $vars['%etiqueta%'];
            $vars['%data_abertura%'] = date_fmt($ticket->data_abertura);
            $vars['%status%'] = $ticket->status()->status;
            $vars['%data_agendamento%'] = ($ticket->oco_scheduled_to ? date_fmt($ticket->oco_scheduled_to) : "");
            $vars['%data_fechamento%'] = ($ticket->data_fechamento ? date_fmt($ticket->data_fechamento) : "");

            $vars['%dia_agendamento%'] = ($ticket->oco_scheduled_to ? explode(" ", date_fmt($ticket->oco_scheduled_to))[0] : "");
            $vars['%hora_agendamento%'] = ($ticket->oco_scheduled_to ? explode(" ", date_fmt($ticket->oco_scheduled_to))[1] : "");
        
            $vars['%descricao_tecnica%'] = ($ticket->solution() ? $ticket->solution()->data()->problema : "");
            $vars['%solucao%'] = ($ticket->solution() ? $ticket->solution()->data()->solucao : "");
            $vars['%assentamento%'] = ($ticket->lastEntry() ? $ticket->lastEntry()->data()->assentamento : "");

            

            // $this->back($vars);
            return $vars;
        }

        // $this->call(
        //     400,
        //     "not_found",
        //     "Chamado não encontrado"
        // )->back();
        return [];
    }


    /**
     * bodyToEmail
     * Retorna a mensagem/body que deverá ser enviada por e-mail de acordo com as 
     *  configurações relacionadas ao evento
     * @param int $ticketNumber
     * @param string $event
     * @return string
     */
    public function bodyToEmail(int $ticketNumber, string $event): string
    {
        // $ticket = (new Ticket())->findByNumber($ticketNumber);
        $event = (new MsgConfig())->findbyEvent($event);

        return transReplace($event->msg_body, $this->envVars($ticketNumber));
    }

    /**
     * subjectToEmail
     * Retorna o subject da mensagem a ser enviada por e-mail de acordo com as 
     *  configurações relacionadas ao evento
     * @param int $ticketNumber
     * @param string $event
     * @return string
     */
    public function subjectToEmail(int $ticketNumber, string $event): string
    {
        // $ticket = (new Ticket())->findByNumber($ticketNumber);
        $event = (new MsgConfig())->findbyEvent($event);

        return transReplace($event->msg_subject, $this->envVars($ticketNumber));
    }



    /**
     * Retorna o tempo absoluto entre duas datas
     * Formato do retorno: x anos x meses x dias x horas x minutos x segundos
     * @param string $startTime
     * @param string $endTime
     * @return array
     */
    public function absoluteTime(string $startTime, string $endTime): array
    {
        $time1 = strtotime($startTime);
        $time2 = strtotime($endTime);
        $inSeconds = $time2 - $time1;

        $startTime = new DateTime($startTime);
        $endTime = new DateTime($endTime);

        $diff = $startTime->diff($endTime);

        $years = ($diff->y ? $diff->y : '');
        $months = ($diff->m ? $diff->m : '');
        $days = ($diff->d ? $diff->d : '');
        $hours = ($diff->h ? $diff->h : '');
        $minutes = ($diff->i ? $diff->i : '');
        $seconds = ($diff->s ? $diff->s : '');

        $inTime = "";

        $inTime = (!empty($years) ? $years . "a " : '');
        $inTime .= (!empty($months) ? $months . "m " : '');
        $inTime .= (!empty($days) ? $days . "d " : '');
        $inTime .= (!empty($hours) ? $hours . "h " : '');
        $inTime .= (!empty($minutes) ? $minutes . "m " : '');
        $inTime .= (!empty($seconds) ? $seconds . "s " : '');

        $output = [];
        $output['inTime'] = trim($inTime);
        $output['inSeconds'] = $inSeconds;

        return $output;
    }

    /**
     * Retorna um array com as informações de tempo de vida do ticket
     * @return array
     */
    public function lifetime($ticketNumber): array
    {
        // $ticketNumber = $data['id'];
        $config = (new Config())->findById(1);
        $output = [];
        $now = date("Y-m-d H:i:s");
        
        /** @var Ticket $ticket */
        $ticket = (new Ticket())->findById($ticketNumber);

        $holidays = (new Holidays())->arrayHolidays();
        $worktimes = (new WorktimeProfiles())->arrayWorktime($ticket->activeWorktimeProfile()->data()->id);

        /* Objeto para armazenar o tempo de solucao */
        $solutionLifetime = new WorkTime( $worktimes, $holidays );
        /* Objeto para armazenar o tempo de resposta */
        $responseLifetime = new WorkTime( $worktimes, $holidays );
        /* Objeto para checagem sobre o momento atual estar dentro da cobertura da jornada de trabalho associada */
        $currentLifetime = new WorkTime( $worktimes, $holidays );

        $opening_date = (!empty($ticket->data()->oco_real_open_date) ? $ticket->data()->oco_real_open_date : $ticket->data()->data_abertura);

        $response_date = (!empty($ticket->data()->data_atendimento) ? $ticket->data()->data_atendimento : "");
        $response_strtotime = (!empty($ticket->data()->data_atendimento) ? strtotime($ticket->data()->data_atendimento) : "");
        $closure_date = (!empty($ticket->data()->data_fechamento) ? $ticket->data()->data_fechamento : "");

        $stages = (new TicketStage())->findByTicket($ticketNumber)->order("id")->fetch(true);

        /* Tempo de solucao */
        if ($stages) {
            $hasValidStage = false;
            foreach ($stages as $stage) {
                
                /* Se o código de status for 0, indica que o chamado é anterior à implementação do ticket stages */
                if ($stage->status_id != 0) {
                    /** @var Status $status */
                    $status = (new Status())->findById($stage->status_id);
                }
                /* Só considera os status que não param o relógio */
                if ($stage->status_id == 0 || !$status->isStatusFreeze()) {
                    $hasValidStage = true;

                    $solutionLifetime->startTimer($stage->date_start);
                    if (!empty($stage->date_stop)) {
                        $solutionLifetime->stopTimer($stage->date_stop);
                    } else {
                        $solutionLifetime->stopTimer($now);
                    }
                }
                
            }
            if (!$hasValidStage) {
                //Há registro no tickets_stages mas nenhum dispara a contagem de tempo
                $solutionLifetime->startTimer($now);
                $solutionLifetime->stopTimer($now);
            }
        } else {
            /* Se não encontrar nenhum registro em tickets_stages então considero apenas a data de abertura
                e a data de fechamento (caso exista), caso contrário, a data atual */
            
            /** @var Status $statusAtual */
            $statusAtual = (new Status())->findById($ticket->data()->status);
            
            $solutionLifetime->startTimer($opening_date);
            if ($closure_date != '') {
                $solutionLifetime->stopTimer($closure_date);
            } elseif ($statusAtual->isStatusFreeze()) {
                if (!empty($response_date))
                    $solutionLifetime->stopTimer($response_date);
                else
                    $solutionLifetime->stopTimer($opening_date);
            } else
                $solutionLifetime->stopTimer($now);
        }


        /* Tempo de resposta */
        if ($stages) {
            $hasValidStage = false;
            $foundResponse = false;

            foreach ($stages as $stage) {
                /* Só considera os status que não param o relógio */
                /* Faço a busca em cada estágio até encontrar a primeira resposta - 
                    caso não tenha, todos os estágios são considerados*/
                $dateStop = "";
                if (!$foundResponse) { //até encontrar a primeira resposta

                    /* Se o código de status for 0, indica que o chamado é anterior à implementação do ticket stages */
                    if ($stage->status_id != 0) {
                        /** @var Status $status */
                        $status = (new Status())->findById($stage->status_id);
                    }
                    
                    if ($stage->status_id == 0 || !$status->isStatusFreeze()) {
                        $hasValidStage = true;
                        $responseLifetime->startTimer($stage->date_start);
    
                        if (!empty($response_date)) {
    
                            if (!empty($stage->date_stop)) {
                                $dateStop = strtotime($stage->date_stop);
                            }
    
                            if (!empty($dateStop) && $dateStop <= $response_strtotime) {
                                $responseLifetime->stopTimer($stage->date_stop);
                                if ($dateStop == $response_strtotime)
                                    $foundResponse = true;
                            } else {
                                $responseLifetime->stopTimer($response_date);
                                $foundResponse = true;
                            }
    
                        } else {
    
                            if (!empty($stage->date_stop)) {
                                $responseLifetime->stopTimer($stage->date_stop);
                            } else 
                                $responseLifetime->stopTimer($now);
                        }
                    }
                }
            }
            if (!$hasValidStage) {
                //Há registro no tickets_stages mas nenhum dispara a contagem de tempo
                $responseLifetime->startTimer($now);
                $responseLifetime->stopTimer($now);
            }
        } else {
            /* Se não encontra nenhum registro em tickets_stages então considero apenas a data de abertura e a data atual */
            $responseLifetime->startTimer($opening_date);
            if (!empty($response_date)) {
                $responseLifetime->stopTimer($response_date);
            } else {
                $responseLifetime->stopTimer($now);
            }
        }

        $output['result_sla_response'] = 1; /* Não definido */
        $output['result_sla_solution'] = 1; /* Não definido */

        $ticketResponse = $responseLifetime->getSeconds();
        $ticketSolution = $solutionLifetime->getSeconds();
        $tolerance = $config->data()->conf_sla_tolerance;
        if ($ticket->has('local'))
            $definedSLAResponse = $ticket->slaResponse()->data()->slas_tempo ?? "";
        if ($ticket->has('problema'))
        $definedSLASolution = $ticket->slaSolution()->data()->slas_tempo ?? "";

        if (!empty($definedSLAResponse)) {
            if ($ticketResponse <= (($definedSLAResponse * 60))) {
                $output['result_sla_response'] = 2; /* Dentro do SLA */
            } elseif ($ticketResponse <= (($definedSLAResponse * 60) + (($definedSLAResponse * 60) * $tolerance / 100))) {
                $output['result_sla_response'] = 3; /* Dentro da tolerância excendente */
            } else 
                $output['result_sla_response'] = 4; /* Excedeu o SLA */
        }

        if (!empty($definedSLASolution)) {
            if ($ticketSolution <= (($definedSLASolution * 60))) {
                $output['result_sla_solution'] = 2; /* Dentro do SLA */
            } elseif ($ticketSolution <= (($definedSLASolution * 60) + (($definedSLASolution * 60) * $tolerance / 100))) {
                $output['result_sla_solution'] = 3; /* Dentro da tolerância excendente */
            } else 
                $output['result_sla_solution'] = 4; /* Excedeu o SLA */
        }


        /* Checar se o momento atual está dentro da cobertura da jornada de trabalho associada */
        $output['running'] = 0;

        $nowTmp = (array) new DateTime( $now );
        $nowTmp = explode(".", $nowTmp['date']); //nowTmp[0] = date part

        $before = new DateTime( $now);
        $before = $before->modify( '-1 second' ); 
        $before = (array)$before;
        $before = explode(".", $before['date']);
        
        $later = new DateTime( $now);
        $later = (array)$later->modify( '+1 second' ); 
        $later = explode(".", $later['date']);
        
        $currentLifetime->startTimer($nowTmp[0]);
        $currentLifetime->stopTimer($later[0]);

        if ($currentLifetime->getSeconds() > 0) {
            $output['running'] = $currentLifetime->getSeconds();
        } else {
            $currentLifetime->startTimer($before[0]);
            $currentLifetime->stopTimer($nowTmp[0]);

            if ($currentLifetime->getSeconds() > 0) {
                $output['running'] = $currentLifetime->getSeconds();
            }
        }
        
        $output['response']['time'] = $responseLifetime->getTime();
        $output['response']['seconds'] = $responseLifetime->getSeconds();
        $output['solution']['time'] = $solutionLifetime->getTime();
        $output['solution']['seconds'] = $solutionLifetime->getSeconds();
        
        $output['response']['absolute_time'] = $this->absoluteTime($opening_date, (!empty($response_date) ? $response_date : $now))['inTime'];
        $output['solution']['absolute_time'] = $this->absoluteTime($opening_date, (!empty($closure_date) ? $closure_date : $now))['inTime'];

        return $output;
    }


    /* Pendente de conclusão */
    public function update(array $data): void
    {
        $data = filter_var_array($data, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        if (empty($data["id"]) || !$ticket_number = filter_var($data["id"], FILTER_VALIDATE_INT)) {
            $this->call(
                400,
                "invalid_data",
                "Informe o número do chamado que deseja atualizar"
            )->back();
            return;
        }

        $ticket = (new Ticket())->findById($data['id']);

        if (!$ticket) {
            $this->call(
                400,
                "invalid_data",
                "Você está tentando atualizar um chamado que não existe"
            )->back();
            return;
        }

        if (!empty($data["priority_id"]) && $priority_id = filter_var($data["priority_id"], FILTER_VALIDATE_INT)) {
            $priority = (new Priority())->findById($priority_id);

            if (!$priority) {
                $this->call(
                    400,
                    "invalid_data",
                    "Você informou uma prioridade que não existe"
                )->back();
                return;
            }
        }

        $ticket->oco_prior = (!empty($data["priority_id"]) ? $data["priority_id"] : $ticket->oco_prior);

        if (!$ticket->save()) {
            $this->call(
                400,
                "invalid_data",
                // $ticket->message()->getText()
                "Houve algum problema durante a tentativa de gravar a atualização"
            )->back();
            return;
        }

        $this->back(["ticket" => $ticket->data()]);

    }


    /** Abertura de chamados - Processo completo (falta a inclusão de anexos)
     * @param array $data
     * @return Tickets
    */
    public function create(array $data): Tickets
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

        $data = filter_var_array($data, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $now = date("Y-m-d H:i:s");

        /* único campo obrigatório */
        if (empty($data["description"])) {
            return $this->call(
                400,
                "invalid_data",
                "A descrição precisa ser informada"
            )->back();
            // return $this;
            // return;
        }

        $config = (new Config())->findById(1);
        $ticket = new Ticket();

        /** Descrição do chamado */
        $ticket->descricao = $data['description'];

        /** Aberto por */
        $ticket->aberto_por = $this->user->data()->user_id;


        /** Tratamento para o cliente */
        if (!empty($data['client']) && filter_var($data['client'], FILTER_VALIDATE_INT)) {
            $client = (new Client())->findById($data['client']);
            if (!$client) {
                return $this->call(
                    400,
                    "invalid_data",
                    "Código de Cliente inválido"
                )->back();
                // return $this;
                // return;
            }
            $ticket->client = $data['client'];
        }



        /** Tratamento para a área */
        if (!empty($data['area']) && filter_var($data['area'], FILTER_VALIDATE_INT)) {
            $area = (new Area())->findById($data['area']);
            if (!$area) {
                return $this->call(
                    400,
                    "invalid_data",
                    "Código de Área inválido"
                )->back();
                // return $this;
                // return;
            }
            $ticket->sistema = $data['area'];
        } else {
            /* MODIFICAR PARA -> Buscar a área padrão definida no perfil de tela de abertura do usuário */
            $ticket->sistema = $this->user->data()->AREA;
        }

        /** Se o contato não for informado será assumido o usuário autenticado */
        if (empty($data['contact'])) {
            $ticket->contato = $this->user->data()->nome;
            $ticket->contato_email = $this->user->data()->email;
        } else {
            $ticket->contato = $data['contact'];
        }

        /** E-mail de contato */
        if (!empty($data['contact_email'])) {
            $contact_email = filter_var($data["contact_email"], FILTER_VALIDATE_EMAIL);
            if (!$contact_email) {
                return $this->call(
                    400,
                    "invalid_data",
                    "Endereço de email inválido"
                )->back();
                // return $this;
                // return;
            }
            $ticket->contato_email = $data['contact_email'];
        }

        /** Telefone */
        if (!empty($data['phone'])) {
            $ticket->telefone = $data['phone'];
        }

        /** Tipo de problema */
        if (!empty($data['issue'])) {

            if (filter_var($data['issue'], FILTER_VALIDATE_INT)) {
                $issue = (new Issue())->findById($data['issue']);
                if (!$issue) {
                    return $this->call(
                        400,
                        "invalid_data",
                        "Código de Tipo de problema inválido"
                    )->back();
                    // return $this;
                    // return;
                }
                $ticket->problema = $data['issue'];
            } else {
                return $this->call(
                    400,
                    "invalid_data",
                    "Formato de código de tipo de problema inválido"
                )->back();
                // return $this;
                // return;
            }
        }

        /** Unidade */
        if (!empty($data['asset_unit'])) {
            if (filter_var($data['asset_unit'], FILTER_VALIDATE_INT)) {
                $asset_unit = (new Unit())->findById($data['asset_unit']);
                if (!$asset_unit) {
                    return $this->call(
                        400,
                        "invalid_data",
                        "Código de Unidade inválido"
                    )->back();
                    // return $this;
                    // return;
                }
                $ticket->instituicao = $data['asset_unit'];
            } else {
                return $this->call(
                    400,
                    "invalid_data",
                    "Formato de código de Unidade inválido"
                )->back();
                // return $this;
                // return;
            }
        }
        
        /** Etiqueta */
        if (!empty($data['asset_tag'])) {
            $ticket->equipamento = $data['asset_tag'];
        }

        /** Departamento */
        if (!empty($data['department'])) {
            if (filter_var($data['department'], FILTER_VALIDATE_INT)) {
                $department = (new Department())->findById($data['department']);
                if (!$department) {
                    return $this->call(
                        400,
                        "invalid_data",
                        "Código de Departamento inválido"
                    )->back();
                    // return $this;
                    // return;
                }
                $ticket->local = $data['department'];
            } else {
                return $this->call(
                    400,
                    "invalid_data",
                    "Formato de código de Departamento inválido"
                )->back();
                // return $this;
                // return;
            }
        }

        /** Prioridade */
        if (!empty($data['priority'])) {
            if (filter_var($data['priority'], FILTER_VALIDATE_INT)) {
                $priority = (new Priority())->findById($data['priority']);
                if (!$priority) {
                    return $this->call(
                        400,
                        "invalid_data",
                        "Código de Prioridade inválido"
                    )->back();
                    // return $this;
                    // return;
                }
                $ticket->oco_prior = $data['priority'];
            } else {
                return $this->call(
                    400,
                    "invalid_data",
                    "Formato de código de Prioridade inválido"
                )->back();
                // return $this;
                // return;
            }
        } else {
            $priority = (new Priority())->default()->fetch();
            $ticket->oco_prior = $priority->pr_cod;
        }

        /** Channel */
        if (!empty($data['channel'])) {

            if (filter_var($data['channel'], FILTER_VALIDATE_INT)) {
                $channel = (new Channel())->findById((int)$data['channel']);
                if (!$channel) {
                    return $this->call(
                        400,
                        "invalid_data",
                        "Código de Canal de entrada inválido"
                    )->back();
                    // return $this;
                    // return;
                }
                $ticket->oco_channel = $data['channel'];
            } else {
                return $this->call(
                    400,
                    "invalid_data",
                    "Formato de código de Canal de entrada inválido"
                )->back();
                // return $this;
                // return;
            }
        } else {
            // $ticket->oco_channel = 1;
            $channel = (new Channel())->default()->fetch();
            $ticket->oco_channel = $channel->id;
        }

        /** Encaminhamento */
        if (!empty($data['operator'])) {
            if (filter_var($data['operator'], FILTER_VALIDATE_INT)) {
                $operator = (new User())->findById($data['operator']);
                if (!$operator) {
                    return $this->call(
                        400,
                        "invalid_data",
                        "Código de operador inválido"
                    )->back();
                    // return $this;
                    // return;
                }
                $ticket->operador = $data['operator'];
            } else {
                return $this->call(
                    400,
                    "invalid_data",
                    "Formato de código de Operador inválido"
                )->back();
                // return $this;
                // return;
            }
        } else {
            $ticket->operador = $ticket->aberto_por;
        }

        /* Status */
        if (!empty($data['status'])) {

            if (filter_var($data['status'], FILTER_VALIDATE_INT)) {
                $status = (new Status())->findById($data['status']);
                if (!$status) {
                    return $this->call(
                        400,
                        "invalid_data",
                        "Código de Status inválido"
                    )->back();
                    // return $this;
                    // return;
                }
                $ticket->status = $data['status'];
            } else {
                return $this->call(
                    400,
                    "invalid_data",
                    "Formato de código de Status inválido"
                )->back();
                // return $this;
                // return;
            }
        } elseif ($ticket->operador != $ticket->aberto_por) {
            $ticket->status = $config->data()->conf_foward_when_open;
        } else {
            $ticket->status = 1;
        }


        /** Tag/Tags */
        if (!empty($data['input_tag'])) {
            $ticket->oco_tag = filter_var($data['input_tag'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);

            $inputTags = explode(',', $data['input_tag']);
            /* Grava as tags informadas na tabela de referência */
            foreach ($inputTags as $tagName) {
                $objTag = (new InputTags())->create($tagName);
            }
        }


        /** Data de abertura */
        $ticket->data_abertura = $now;
        $ticket->oco_real_open_date = $now;

        /** Gravação do ticket */
        if ($ticket->save()) {

            /** Após tratar os dados de entrada - realizar demais procedimentos:
             * Inserir stage
             * Inserir no global_tickets
             * Inserir first log 
             * Colocar email na fila (em aberto)
             */

            $stage = (new TicketStages())->create(
                [
                    'ticket' => $ticket->data()->numero, 
                    'type' => 'start',
                    'status' => $ticket->data()->status
                ]
            );
            if (!$stage) {
                $this->call(
                    400,
                    "invalid_data",
                    // $ticket->message()->getText()
                    "O chamado foi registrado mas houve um problema no registro do Stage"
                )->back();
                // return;
            }

            /* Log de abertura */
            (new TicketLogs())->createFirst($ticket->data()->numero);

            
        } else {
            return $this->call(
                400,
                "invalid_data",
                // $ticket->message()->getText()
                "Houve algum problema durante a tentativa de registrar o chamado"
            )->back();
            // return $this;
            // return;
        }

        /* Disparo do e-mail (ou fila no banco) para o usuário */
        if ($ticket->data()->contato_email && $ticket->data()->contato) {
            $mail = (new Email())->bootstrap(
                $this->subjectToEmail($ticket->numero, 'abertura-para-usuario'),
                $this->bodyToEmail($ticket->numero, 'abertura-para-usuario'),
                $ticket->data()->contato_email,
                $ticket->data()->contato,
                $ticket->numero
            );

            if (!$mail->queue()) {
                var_dump($mail->message()->getText());
            }
        }

        /* Disparo do e-mail (ou fila no banco) para a área de atendimento */
        if ($ticket->area()->sistema) {
            $mail = (new Email())->bootstrap(
                $this->subjectToEmail($ticket->numero, 'abertura-para-area'),
                $this->bodyToEmail($ticket->numero, 'abertura-para-area'),
                $ticket->area()->sis_email,
                $ticket->area()->sistema,
                $ticket->numero
            );

            if (!$mail->queue()) {
                var_dump($mail->message()->getText());
            }
        }
        
        /** Retorno para a API */
        return $this->back(["ticket" => $ticket->data()]);
        // return $this;
    }
}