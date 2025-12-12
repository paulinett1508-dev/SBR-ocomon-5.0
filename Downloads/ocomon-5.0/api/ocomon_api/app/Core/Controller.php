<?php

namespace OcomonApi\Core;

use OcomonApi\Support\Message;

/**
 * OcoMon API | Class Controller
 *
 * @author Flávio Ribeiro <flaviorib@gmail.com>
 * @package OcomonApi\Core
 */
class Controller
{
    /** @var $message */
    protected $message;

    /**
     * __construct
     *
     * @param string|null $pathToViews
     * 
     * @return void
     */
    public function __construct(?string $pathToViews = null)
    {
        $this->message = new Message();
    }
}