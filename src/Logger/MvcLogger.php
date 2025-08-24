<?php

namespace LiteMvc\Core\Logger;

use LiteMvc\Core\Logger\LoggerInterface;

/**
 * https://www.php.net/manual/ru/network.constants.php
 * https://www.php-fig.org/psr/psr-3/
 */
class MvcLogger implements LoggerInterface {
    
    public function log(string $level, string $message, array $context = []) {

    }

    public function emergency(string $message, array $context = []) {
        // Логирование экстренных сообщений
    }

    public function alert(string $message, array $context = []) {
        // Логирование критических оповещений
    }

    public function critical(string $message, array $context = []) {}
    public function error(string $message, array $context = []) {}
    public function warning(string $message, array $context = []) {}
    public function notice(string $message, array $context = []) {}
    public function info(string $message, array $context = []) {}
    public function debug(string $message, array $context = []) {}
}