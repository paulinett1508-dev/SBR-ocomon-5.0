<?php

namespace OcomonApi\Models;

use CoffeeCode\DataLayer\DataLayer;

/**
 * OcoMon Api | Class WorktimeProfile Active Record Pattern
 *
 * @author Flavio Ribeiro <flaviorib@gmail.com>
 * @package OcomonApi\Models
 */
class WorktimeProfile extends DataLayer
{
    /**
     * User constructor.
     */
    public function __construct()
    {
        parent::__construct(
            "worktime_profiles", 
            [
                "name", 
                "week_ini_time_hour", 
                "week_ini_time_minute",
                "week_end_time_hour",
                "week_end_time_minute",
                "week_day_full_worktime",
                "sat_ini_time_hour", 
                "sat_ini_time_minute",
                "sat_end_time_hour",
                "sat_end_time_minute",
                "sat_day_full_worktime",
                "sun_ini_time_hour", 
                "sun_ini_time_minute",
                "sun_end_time_hour",
                "sun_end_time_minute",
                "sun_day_full_worktime",
                "off_ini_time_hour", 
                "off_ini_time_minute",
                "off_end_time_hour",
                "off_end_time_minute",
                "off_day_full_worktime"
            ], 
            "id", 
            false
        );
    }


    public function defaultProfileId(): int
    {
        $default = (new WorktimeProfile())->find("is_default = :one", "one=1")->fetch();

        return $default->id;
    }


    /**
     * List Worktime profile data to be used as parameter in Worktime class
     */
    public function arrayWorktime(): array
    {
        $defaultProfileId = $this->id;
        if (empty($this->id)) {
            $defaultProfileId = $this->defaultProfileId();
        }
        $worktimeProfile = $this->findById($defaultProfileId);

        if ($worktimeProfile) {

            $arrayWorktime = (array) $worktimeProfile->data();
            
            $worktime['247'] = ($arrayWorktime["247"] == 1 ? (bool)true : (bool)false);

            $worktime['week']['iniTimeHour'] = $arrayWorktime['week_ini_time_hour'];
            $worktime['week']['iniTimeMinute'] = $arrayWorktime['week_ini_time_minute'];
            $worktime['week']['endTimeHour'] = $arrayWorktime['week_end_time_hour'];
            $worktime['week']['endTimeMinute'] = $arrayWorktime['week_end_time_minute'];
            $worktime['week']['dayFullWorkTime'] = (int)$arrayWorktime['week_day_full_worktime'];
            $worktime['week']['dayFullWorkTimeInSecs'] = $arrayWorktime['week_day_full_worktime'] * 60;

            $worktime['sat']['iniTimeHour'] = $arrayWorktime['sat_ini_time_hour'];
            $worktime['sat']['iniTimeMinute'] = $arrayWorktime['sat_ini_time_minute'];
            $worktime['sat']['endTimeHour'] = $arrayWorktime['sat_end_time_hour'];
            $worktime['sat']['endTimeMinute'] = $arrayWorktime['sat_end_time_minute'];
            $worktime['sat']['dayFullWorkTime'] = (int)$arrayWorktime['sat_day_full_worktime'];
            $worktime['sat']['dayFullWorkTimeInSecs'] = $arrayWorktime['sat_day_full_worktime'] * 60;

            $worktime['sun']['iniTimeHour'] = $arrayWorktime['sun_ini_time_hour'];
            $worktime['sun']['iniTimeMinute'] = $arrayWorktime['sun_ini_time_minute'];
            $worktime['sun']['endTimeHour'] = $arrayWorktime['sun_end_time_hour'];
            $worktime['sun']['endTimeMinute'] = $arrayWorktime['sun_end_time_minute'];
            $worktime['sun']['dayFullWorkTime'] = (int)$arrayWorktime['sun_day_full_worktime'];
            $worktime['sun']['dayFullWorkTimeInSecs'] = $arrayWorktime['sun_day_full_worktime'] * 60;

            $worktime['off']['iniTimeHour'] = $arrayWorktime['off_ini_time_hour'];
            $worktime['off']['iniTimeMinute'] = $arrayWorktime['off_ini_time_minute'];
            $worktime['off']['endTimeHour'] = $arrayWorktime['off_end_time_hour'];
            $worktime['off']['endTimeMinute'] = $arrayWorktime['off_end_time_minute'];
            $worktime['off']['dayFullWorkTime'] = (int)$arrayWorktime['off_day_full_worktime'];
            $worktime['off']['dayFullWorkTimeInSecs'] = $arrayWorktime['off_day_full_worktime'] * 60;
        }
        return (array) $worktime;
    }

    
}