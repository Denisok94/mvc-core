<?php

namespace LiteMvc\Core\Logger;

use  DateTime, DateTimeZone;

class LodModel
{
    public string $datetime;
    public string $level = 'debug';
    public string $message = '';
    public array $context = [];
    public array $extra = [];

    public function __construct()
    {
        $this->setDatetime((new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.u\Z'));
    }

    public function getArray(): array
    {
        return [
            'datetime' => $this->getDatetime(),
            'level' => $this->getLevel(),
            'message' => $this->getMessage(),
            'context' => $this->getContext(),
            'extra' => $this->getExtra()
        ];
    }

    /**
     * Get the value of datetime
     */
    public function getDatetime(): string
    {
        return $this->datetime;
    }

    /**
     * Set the value of datetime
     */
    public function setDatetime(string $datetime): self
    {
        $this->datetime = $datetime;

        return $this;
    }

    /**
     * Get the value of level
     */
    public function getLevel(): string
    {
        return $this->level;
    }

    /**
     * Set the value of level
     */
    public function setLevel(string $level): self
    {
        $this->level = $level;

        return $this;
    }

    /**
     * Get the value of message
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Set the value of message
     */
    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get the value of context
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Set the value of context
     * @param mixed $context
     * @return self
     */
    public function setContext($context): self
    {
        if (is_array($context)) {
            $this->context = $context;
        } else if (method_exists($context, '__toString')) {
            $this->context = $context;
        } else if (method_exists($context, 'getArray')) {
            $this->context = $context->getArray();
        } else if (is_object($context)) {
            $this->context = self::objectToArray($context);
        } else {
            $this->context = $context;
        }
        return $this;
    }

    /**
     * @param object $object
     * @return array
     */
    public static function objectToArray(object $object)
    {
        $a = array();
        foreach ($object as $k => $v) $a[$k] = (is_array($v) || is_object($v)) ? self::objectToArray($v) : $v;
        return $a;
    }

    /**
     * Get the value of extra
     */
    public function getExtra(): array
    {
        return $this->extra;
    }

    /**
     * Set the value of extra
     */
    public function setExtra(array $extra): self
    {
        $this->extra = $extra;

        return $this;
    }
}
