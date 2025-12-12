<?php

namespace OcomonApi\Support;

// use OcomonApi\Core\Session;

/**
 * OcoMon API | Class Message
 *
 * @author Flávio Ribeiro <flaviorib@gmail.com>
 * @package OcomonApi\Core
 */
class Message
{
    /** @var string */
    private $text;

    /** @var string */
    private $type;

    /** @var string */
    private $before;

    /** @var string */
    private $after;

    /**
     * @return string
     */
    public function getText(): ?string
    {
        return $this->before . $this->text . $this->after;
    }

    /**
     * @return string
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string $text
     * @return Message
     */
    public function before(string $text): Message
    {
        $this->before = $text;
        return $this;
    }

    /**
     * @param string $text
     * @return Message
     */
    public function after(string $text): Message
    {
        $this->after = $text;
        return $this;
    }

    /**
     * @param string $message
     * @return Message
     */
    public function setMessage(string $message): Message
    {
        $this->text = $this->filter($message);
        return $this;
    }

    /**
     * @param string $message
     * @return string
     */
    private function filter(string $message): string
    {
        return filter_var($message, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    }

    /**
     * @param string $message
     * @return Message
     */
    public function error(string $message): Message
    {
        $this->type = "error icon-warning";
        $this->text = $this->filter($message);
        return $this;
    }
}