<?php

namespace OcomonApi\Controllers;

use OcomonApi\Core\OcomonApi;
use OcomonApi\Models\Holiday;

class Holidays extends OcomonApi
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * List Holidays data to be used as parameter in Worktime class
     */
    public function arrayHolidays(): ?array
    {
        
        $currentYear = (int)date("Y");
        $yearBase = $currentYear - 5; 
        $yearLimit = $currentYear + 5;

        $holidays = (new Holiday())->getFormattedHolidays()->fetch(true);
        $response = [];

        
        if ($holidays) {
            /** @var Holiday $issue */
            foreach ($holidays as $holiday) {
                if ($holiday->fixo) {
                    for ($i = $yearBase; $i <= $yearLimit; $i++) {
                        $response[] = $i . '-' . $holiday->mes . '-' . $holiday->dia;
                    }
                } else 
                    $response[] = $holiday->ano . '-' . $holiday->mes . '-' . $holiday->dia;
            }
        }

        return $response;
    }

}