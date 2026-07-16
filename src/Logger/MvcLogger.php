<?php

namespace LiteMvc\Core\Logger;

use LiteMvc\Core\Logger\LoggerInterface;

/**
 * https://www.php.net/manual/ru/network.constants.php
 * https://www.php-fig.org/psr/psr-3/
 */
class MvcLogger implements LoggerInterface
{
    /**
     * @param string $level
     * @param string $message
     * @param array $context
     * @return void
     */
    public function log(string $level, string $message, array $context = [])
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
     * @param string $message
     * @param array $context
     * @return void
     */
    public function emergency($message, array $context = [])
    {
        // 
        $this->log('emergency', $message, $context);
    }

    /**
     * Логирование критических оповещений
     * @param string $message
     * @param array $context
     * @return void
     */
    public function alert($message, array $context = [])
    {
        $this->log('alert', $message, $context);
    }

    public function critical($message, array $context = [])
    {
        $this->log('critical', $message, $context);
    }
    public function error($message, array $context = [])
    {
        $this->log('error', $message, $context);
    }
    public function warning($message, array $context = [])
    {
        $this->log('warning', $message, $context);
    }
    public function notice($message, array $context = [])
    {
        $this->log('notice', $message, $context);
    }
    public function info($message, array $context = [])
    {
        $this->log('info', $message, $context);
    }
    public function debug($message, array $context = [])
    {
        $this->log('debug', $message, $context);
    }
}
