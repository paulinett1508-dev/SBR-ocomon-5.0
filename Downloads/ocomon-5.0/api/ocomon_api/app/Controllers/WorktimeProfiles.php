<?php

namespace OcomonApi\Controllers;

use OcomonApi\Core\OcomonApi;
use OcomonApi\Models\WorktimeProfile;

class WorktimeProfiles extends OcomonApi
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * List WorktimeProfiles data
     */
    public function index(): void
    {
        
        $worktime_profiles = (new WorktimeProfile())->find()->fetch(true);


        /** @var WorktimeProfile $issue */
        foreach ($worktime_profiles as $worktime) {
            $response[]['worktime'] = $worktime->data();
        }


        $this->back($response);
        return;
    }

    /**
     * List Worktime profile data to be used as parameter in Worktime class
     */
    public function arrayWorktime($id): array
    {

        $defaultProfileId = $id;
        if (empty($id)) {
            $defaultProfileId = (new WorktimeProfile())->defaultProfileId();
        }

        $worktimeProfile = (new WorktimeProfile())->findById($defaultProfileId);
        

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

        // var_dump((array) $worktime);
        return (array) $worktime;

    }

}