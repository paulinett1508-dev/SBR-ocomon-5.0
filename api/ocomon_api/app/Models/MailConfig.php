<?php

namespace OcomonApi\Models;

use CoffeeCode\DataLayer\DataLayer;

/**
 * OcoMon Api | Class MailConfig - Active Record Pattern
 *
 * @author Flávio Ribeiro <flaviorib@gmail.com>
 * @package OcomonApi\Models
 */
class MailConfig extends DataLayer
{
    /**
     * User constructor.
     */
    public function __construct()
    {
        parent::__construct(
            "mailconfig", 
            [
                "mail_issmtp",
                "mail_host",
                "mail_port",
                "mail_secure",
                "mail_isauth",
                "mail_user",
                "mail_from",
                "mail_from_name"
            ],
            "mail_cod", 
            false
        );
    }


}