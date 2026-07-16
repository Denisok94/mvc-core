<?php

namespace LiteMvc\Core\Logger;

/**
 * Рекомендации по использованию
 * - Emergency(0) — Система неработоспособна, для критических сбоев системы
 * - Alert(1) — для ситуаций, требующих немедленного вмешательства
 * - Critical(2) — Критические ошибки, для серьезных ошибок, влияющих на работу
 * - Error(3) — Ошибки в системе, для обычных ошибок в работе
 * - Warning(4) — для предупреждений о возможных проблемах
 * - Notice(5) — для информационных сообщений
 * - Info(6) — для общих событий
 * - Debug(7) — для отладочной информации
 */
interface LoggerInterface
{
    /**
     * @param string $message
     * @param array $context
     * @return void
     */
    public function emergency($message, array $context = []);
    /**
     * @param string $message
     * @param array $context
     * @return void
     */
    public function alert($message, array $context = []);
    /**
     * @param string $message
     * @param array $context
     * @return void
     */
    public function critical($message, array $context = []);
    /**
     * @param string $message
     * @param array $context
     * @return void
     */
    public function error($message, array $context = []);
    /**
     * @param string $message
     * @param array $context
     * @return void
     */
    public function warning($message, array $context = []);
    /**
     * @param string $message
     * @param array $context
     * @return void
     */
    public function notice($message, array $context = []);
    /**
     * @param string $message
     * @param array $context
     * @return void
     */
    public function info($message, array $context = []);
    /**
     * @param string $message
     * @param array $context
     * @return void
     */
    public function debug($message, array $context = []);
}
