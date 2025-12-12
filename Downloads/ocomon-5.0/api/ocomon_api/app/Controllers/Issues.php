<?php

namespace OcomonApi\Controllers;

use OcomonApi\Core\OcomonApi;
use OcomonApi\Models\Issue;

class Issues extends OcomonApi
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * List Issues data
     */
    public function index(): void
    {
        
        $issues = (new Issue())->find()->fetch(true);


        /** @var Issue $issue */
        foreach ($issues as $issue) {
            $response[]['issue'] = $issue->data();
        }


        $this->back($response);
        return;
    }

}