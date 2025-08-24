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
interface LoggerInterface {
    public function emergency(string $message, array $context = []);
    public function alert(string $message, array $context = []);
    public function critical(string $message, array $context = []);
    public function error(string $message, array $context = []);
    public function warning(string $message, array $context = []);
    public function notice(string $message, array $context = []);
    public function info(string $message, array $context = []);
    public function debug(string $message, array $context = []);
}