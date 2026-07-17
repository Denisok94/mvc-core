<?php

namespace LiteMvc\Logger;

use Stringable;
use Psr\Log\LoggerInterface;

/**
 * https://www.php.net/manual/ru/network.constants.php
 * https://www.php-fig.org/psr/psr-3/
 */
class MvcLogger implements LoggerInterface
{
    /**
     * @param string $level
     * @param string|Stringable $message
     * @param array $context
     * @return void
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        if ($message instanceof \Throwable) {
            $message = sprintf("%s(%s:%s)", $message->getMessage(), $message->getFile(), $message->getLine());
            // $context[] = $massage->getTrace();
        }
        ErrorHandler::customErrorLog([
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ]);
    }

    /**
     * Логирование экстренных сообщений
     * @param string|Stringable $message
     * @param array $context
     * @return void
     */
    public function emergency($message, array $context = []): void
    {
        // 
        $this->log('emergency', $message, $context);
    }

    /**
     * Логирование критических оповещений
     * @param string|Stringable $message
     * @param array $context
     * @return void
     */
    public function alert($message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    /**
     * @param string|Stringable $message
     * @param array $context
     * @return void
     */
    public function critical($message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    /**
     * @param string|Stringable $message
     * @param array $context
     * @return void
     */
    public function error($message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    /**
     * @param string|Stringable $message
     * @param array $context
     * @return void
     */
    public function warning($message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    /**
     * @param string|Stringable $message
     * @param array $context
     * @return void
     */
    public function notice($message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    /**
     * @param string|Stringable $message
     * @param array $context
     * @return void
     */
    public function info($message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    /**
     * @param string|Stringable $message
     * @param array $context
     * @return void
     */
    public function debug($message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }
}
