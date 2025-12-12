<?php

namespace ocomon_api_access\OcomonApi;

/**
 * Class Tickets
 * @package ocomon_api_access\OcomonApi
 */
class Tickets extends OcomonApi
{
    /**
     * Tickets constructor.
     * @param string $apiUrl
     * @param string $login
     * @param string $app
     * @param string $token
     */
    public function __construct(string $apiUrl, string $login, string $app, string $token)
    {
        parent::__construct($apiUrl, $login, $app, $token);
    }

    /**
     * @param array $fields
     * @return Tickets
     */
    public function create(array $fields): Tickets
    // public function create(array $fields)
    {
        $this->request(
            "POST",
            "/tickets",
            $fields
        );

        return $this;
    }
}