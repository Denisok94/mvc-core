<?php

namespace LiteMvc\Core\Logger;

// Пример использования
// ErrorLogger::init();

// Для отключения логирования (например, в тестах)
// ErrorLogger::setEnabled(false);

/**
 * 
 */
final class ErrorLogger
{
    private static $logFile = 'application.log';
    private static $enabled = true;

    /**
     * Инициализация обработчиков ошибок
     */
    public static function init() {
        if (!self::$enabled) {
            return;
        }
        self::configureErrorHandling();
        set_error_handler([__CLASS__, 'handleError']);
        set_exception_handler([__CLASS__, 'handleException']);
        register_shutdown_function([__CLASS__, 'handleFatalError']); 
        // Переопределяем стандартную функцию error_log
        if (function_exists('error_log')) {
            $GLOBALS['original_error_log'] = 'error_log';
            $GLOBALS['error_log'] = [__CLASS__, 'customErrorLog'];
        }
    }

    public static  function errorLevelToString($errorLevel) {
        $errorLevels = [
            E_ERROR => 'Ошибка выполнения',  // 1
            E_WARNING => 'Предупреждение', // 2
            E_PARSE => 'Синтаксическая ошибка', // 4
            E_NOTICE => 'Уведомление', // 8
            E_CORE_ERROR => 'Критическая ошибка ядра', // 16
            E_CORE_WARNING => "E_CORE_WARNING", // 32
            E_COMPILE_ERROR => 'Ошибка компиляции', // 64
            E_COMPILE_WARNING => "E_COMPILE_WARNING", // 128
            E_USER_ERROR => 'Пользовательская ошибка', // 256
            E_USER_WARNING => 'Пользовательское предупреждение', // 512
            E_USER_NOTICE => 'Пользовательское уведомление', // 1024
            E_STRICT => 'Строгий режим', // 2048
            E_RECOVERABLE_ERROR => 'Восстанавливаемая ошибка', // 4096
            E_DEPRECATED => 'Устаревшая функция', // 8192
            E_USER_DEPRECATED => 'Пользовательское устаревание', // 16384
            E_ALL => "E_ALL", // 32767
        ];
        
        // Возвращаем описание уровня или "Неизвестный уровень"
        return $errorLevels[$errorLevel] ?? 'Неизвестный уровень ошибки';
    }

    /**
     * Обработка обычных ошибок
     */
    public static function handleError($errno, $errstr, $errfile, $errline) {
        if (!self::$enabled) {
            return false;
        }
        if (!$errno) {
            return false;
        }

        $error = [
            'lavelName' => self::errorLevelToString($errno ),
            'type' => $errno,
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline,
            'time' => date('Y-m-d H:i:s'),
            'context' => self::getContext()
        ];

        self::log($error);
        return true;
    }

    /**
     * Обработка исключений
     */
    public static function handleException(Throwable $exception) {
        if (!self::$enabled) {
            return;
        }

        $error = [
            'lavelName' => '',
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTrace(),
            'time' => date('Y-m-d H:i:s'),
            'context' => self::getContext()
        ];

        self::log($error);
        return true;
    }

    /**
     * Обработка фатальных ошибок
     */
    public static function handleFatalError() {
        if (!self::$enabled) {
            return;
        }

        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $error['time'] = date('Y-m-d H:i:s');
            $error['context'] = self::getContext();
            self::log($error);
            return true;
        }
    }
    
    // Пользовательская реализация error_log
    public static function customErrorLog($message, $message_type = 0, $destination = '', $extra_headers = '') {
        // Добавляем префикс к сообщению
        $formattedMessage = "Пользовательский лог: $message\n";
        
        // Вызываем оригинальный error_log, если он существует
        if (isset($GLOBALS['original_error_log'])) {
            return call_user_func($GLOBALS['original_error_log'], $formattedMessage, $message_type, $destination, $extra_headers);
        }
        
        // Логируем в файл
        self::log($formattedMessage);
        return true;
    }

    /**
     * Запись ошибки в лог
     */
    private static function log($error) {
        if (!self::$enabled) {
            return;
        }

        echo print_r([
            'timestamp' => date('c'),
            'error' => $error
        ]) . "\n";

        // $logMessage = json_encode([
        //     'timestamp' => date('c'),
        //     'error' => $error
        // ]);
        // file_put_contents(
        //     self::$logFile,
        //     $logMessage . PHP_EOL,
        //     FILE_APPEND
        // );

    }

    /**
    * Получение контекста выполнения
    */
    private static function getContext() {
        $context = [
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? ''
        ];

        return $context;
    }

    /**
    * Включение/отключение логирования
    */
    public static function setEnabled($enabled) {
        self::$enabled = (bool)$enabled;
    }

    //---
    
    // Функция для настройки уровня ошибок
    public static function configureErrorHandling() {
        // Определяем среду
        $env = getEnv();
        
        switch ($env) {
            case 'dev':
            case 'development':
                self::setDevelopmentErrorLevel();
                break;
            case 'test':
            case 'testing':
                self::setTestingErrorLevel();
                break;
            case 'prod':
            case 'production':
                self::setProductionErrorLevel();
                break;
            default:
                self::setDefaultErrorLevel();
        }
    }
    // Определение среды
    public static function  getEnv() {
        return getenv('APP_ENV') ?? 'development';
    }


        // Разработка
    public static function  setDevelopmentErrorLevel() {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        ini_set('log_errors', 1);
        // ini_set('error_log', __DIR__ . '/error.log');
    }

    // Тестирование
    public static function  setTestingErrorLevel() {
        error_reporting(E_ALL);
        ini_set('display_errors', 0);
        ini_set('log_errors', 1);
        // ini_set('error_log', __DIR__ . '/error.log');
    }

    // Продакшен
    public static function  setProductionErrorLevel() {
        error_reporting(E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR);
        ini_set('display_errors', 0);
        ini_set('log_errors', 1);
        // ini_set('error_log', __DIR__ . '/error.log');
    }

    // Настройки по умолчанию
    public static function setDefaultErrorLevel() {
        error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
        ini_set('display_errors', 0);
        ini_set('log_errors', 1);
        // ini_set('error_log', __DIR__ . '/error.log');
    }
}