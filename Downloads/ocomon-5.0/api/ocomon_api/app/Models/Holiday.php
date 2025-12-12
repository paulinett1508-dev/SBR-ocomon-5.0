<?php

namespace OcomonApi\Models;

use CoffeeCode\DataLayer\DataLayer;

/**
 * OcoMon API | Class Holiday Active Record Pattern
 *
 * @author Flávio Ribeiro <flaviorib@gmail.com>
 * @package OcomonApi\Models
 */
class Holiday extends DataLayer
{
    /**
     * User constructor.
     */
    public function __construct()
    {
        parent::__construct("feriados", ["data_feriado", "desc_feriado"], "cod_feriado", false);
    }

    public function getFormattedHolidays(): ?Holiday
    {
        return (new Holiday())
            ->find("", "", "date_format(data_feriado, '%Y') ano, date_format(data_feriado, '%m' ) mes, date_format(data_feriado, '%d' ) dia, fixo_feriado fixo")->order("data_feriado");
    }

    /**
     * List Holidays data to be used as parameter in Worktime class
     */
    public function arrayHolidays(): ?array
    {
        
        $currentYear = (int)date("Y");
        $yearBase = $currentYear - 5; 
        $yearLimit = $currentYear + 5;

        $holidays = $this->getFormattedHolidays()->fetch(true);

        /** @var Holiday $issue */
        foreach ($holidays as $holiday) {
            if ($holiday->fixo) {
                for ($i = $yearBase; $i <= $yearLimit; $i++) {
                    $response[] = $i . '-' . $holiday->mes . '-' . $holiday->dia;
                }
            } else 
                $response[] = $holiday->ano . '-' . $holiday->mes . '-' . $holiday->dia;
        }

        return $response;
    }

}