<?php

namespace OcomonApi\Models;



use OcomonApi\Models\Area;
use OcomonApi\Models\Unit;
use OcomonApi\Models\User;
use OcomonApi\Models\Entry;
use OcomonApi\Models\Issue;
use OcomonApi\Models\Client;
use OcomonApi\Models\Config;
use OcomonApi\Models\Status;
use OcomonApi\Models\Holiday;
use OcomonApi\Models\Priority;
use OcomonApi\Models\Solution;
use OcomonApi\Support\Worktime;
use OcomonApi\Models\Department;

use CoffeeCode\DataLayer\DataLayer;
use OcomonApi\Models\WorktimeProfile;

/**
 * OcoMon Api | Class Ticket Active Record Pattern
 *
 * @author Flavio Ribeiro <flaviorib@gmail.com>
 * @package OcomonApi\Models
 */
class Ticket extends DataLayer
{
    /**
     * Ticket constructor.
     */
    public function __construct()
    {
        parent::__construct("ocorrencias", ["descricao", "contato"], "numero", false);
    }

    /**
     * @param int $number
     * @param string $columns
     * @return null|Ticket
     */
    public function findByNumber(int $number, string $columns = "*"): ?Ticket
    {
        $find = $this->find("numero = :number", "number={$number}", $columns);
        return $find->fetch();
    }

    /** Checa se o campo tem valor para o ticket */
    public function has($field): bool
    {
        if ($this->data()->$field && $this->data()->$field != '-1')
            return true;
        return false;
    }
    
    
    
    public function area()
    {
        if ($this->data()->sistema)
            return (new Area())->findById($this->data()->sistema);
        return null;
    }

    public function client()
    {
        if ($this->data()->client)
            return (new Client())->findById($this->data()->client);
        return null;
    }

    public function worktimeProfile(int $area): ?WorktimeProfile
    {
        $area = (new Area())->findById($area);
        
        return (new WorktimeProfile())->findById($area->sis_wt_profile);
    }

    public function activeWorktimeProfile(): ?WorktimeProfile
    {
        $config = (new Config())->findById(1);
        if ($config->conf_wt_areas == 1) {

            /* considerar a área de origem do chamado */
            $areaId = $this->openedByArea();
        } else
            /* considerar a área destino do chamado */
            $areaId = $this->sistema;
        
        return $this->worktimeProfile($areaId);
        
    }



    public function department()
    {
        if ($this->data()->local)
            return (new Department())->findById($this->data()->local);
        return null;
    }

    public function operator()
    {
        if ($this->data()->operador)
            return (new User())->findById($this->data()->operador);
        return null;
    }

    public function openedBy()
    {
        return (new User())->findById($this->data()->aberto_por);
    }

    public function openedByArea(): int
    {
        $user = (new User())->findById($this->data()->aberto_por);

        return $user->data()->AREA;
    }

    public function status()
    {
        return (new Status())->findById($this->data()->status);
    }

    public function isTicketFrozen(): bool
    {
        /** @var Status $status */
        $status = (new Status())->findById($this->data()->status);
        return $status->isStatusFreeze();
    }

    public function unit()
    {
        if ($this->data()->instituicao)
            return (new Unit())->findById($this->data()->instituicao);
        return null;
    }

    public function issue()
    {
        if ($this->data()->problema)
            return (new Issue())->findById($this->data()->problema);
        return null;
    }

    public function slaSolution(): ?Sla
    {
        if ($this->data()->problema) {
            /** @var Issue $issue */
            $issue = (new Issue())->findById($this->data()->problema);
            if ($issue)
                return $issue->sla();
        }

        return null;
    }

    public function slaResponse(): ?Sla
    {
        if ($this->data()->local) {
            /** @var Department $department */
            $department = (new Department())->findById($this->data()->local);
            if ($department)
                return $department->sla();
        }

        return null;
    }

    public function priority()
    {
        if ($this->data()->oco_prior)
            return (new Priority())->findById($this->data()->oco_prior);
        return null;
    }

    public function entries()
    {
        return (new Entry())->find
        (
            "ocorrencia = :numero", 
            "numero={$this->data()->numero}",
            "numero, ocorrencia, assentamento, responsavel, data as date, tipo_assentamento"
        )->fetch(true);
    }

    public function lastEntry()
    {
        return (new Entry())->find
        (
            "ocorrencia = :numero", 
            "numero={$this->data()->numero}",
            "numero, ocorrencia, assentamento, responsavel, data as date, tipo_assentamento"
        )->limit(1)->offset(0)->order("numero DESC")->fetch();
    }

    public function channel()
    {
        if ($this->data()->oco_channel)
            return (new Channel())->findById($this->data()->oco_channel);
        return null;
    }

    public function solution()
    {
        if ($this->data()->data_fechamento)
            return (new Solution())->findByNumber($this->data()->numero);
        return null;
    }


    // public function files()
    // {
    //     return (new File())->find
    //     (
    //         "img_oco = :numero", 
    //         "numero={$this->data()->numero}",
    //         "*"
    //     )->fetch(true);
    // }

    /**
     * Retorna o tempo absoluto entre duas datas
     * Formato do retorno: x anos x meses x dias x horas x minutos x segundos
     * $startTime: data de início do período
     * $endTime: data de fim do feríodo
     */
    public function absoluteTime(string $startTime, string $endTime): array
    {
        $time1 = strtotime($startTime);
        $time2 = strtotime($endTime);
        $inSeconds = $time2 - $time1;

        $startTime = new \DateTime($startTime);
        $endTime = new \DateTime($endTime);

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


    public function lifetime(): array
    {
        $config = (new Config())->findById(1);
        $output = [];
        $now = date("Y-m-d H:i:s");
        
        $holidays = (new Holiday())->arrayHolidays();
        $worktimes = (new WorktimeProfile())->arrayWorktime($this->activeWorktimeProfile()->data()->id);

        /* Objeto para armazenar o tempo de solucao */
        $solutionLifetime = new WorkTime( $worktimes, $holidays );
        /* Objeto para armazenar o tempo de resposta */
        $responseLifetime = new WorkTime( $worktimes, $holidays );
        /* Objeto para checagem sobre o momento atual estar dentro da cobertura da jornada de trabalho associada */
        $currentLifetime = new WorkTime( $worktimes, $holidays );

        $opening_date = (!empty($this->data()->oco_real_open_date) ? $this->data()->oco_real_open_date : $this->data()->data_abertura);

        $response_date = (!empty($this->data()->data_atendimento) ? $this->data()->data_atendimento : "");
        $response_strtotime = (!empty($this->data()->data_atendimento) ? strtotime($this->data()->data_atendimento) : "");
        $closure_date = (!empty($this->data()->data_fechamento) ? $this->data()->data_fechamento : "");

        $stages = (new TicketStage())->findByTicket($this->data()->numero)->order("id")->fetch(true);

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
            $statusAtual = (new Status())->findById($this->data()->status);
            
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
        if ($this->has('local'))
            $definedSLAResponse = $this->slaResponse()->data()->slas_tempo ?? "";
        if ($this->has('problema'))
        $definedSLASolution = $this->slaSolution()->data()->slas_tempo ?? "";

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

        $nowTmp = (array) new \DateTime( $now );
        $nowTmp = explode(".", $nowTmp['date']); //nowTmp[0] = date part

        $before = new \DateTime( $now);
        $before = $before->modify( '-1 second' ); 
        $before = (array)$before;
        $before = explode(".", $before['date']);
        
        $later = new \DateTime( $now);
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
    
    
}