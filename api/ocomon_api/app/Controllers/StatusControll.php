<?php

namespace OcomonApi\Controllers;

use OcomonApi\Core\OcomonApi;
use OcomonApi\Models\Status;

class StatusControll extends OcomonApi
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * List StatusControll data
     */
    public function index(): void
    {
        
        $statusList = (new Status())->find()->fetch(true);


        /** @var Status $status */
        foreach ($statusList as $status) {
            $response[]['status'] = $status->data();
        }


        $this->back($response);
        return;
    }


    

}