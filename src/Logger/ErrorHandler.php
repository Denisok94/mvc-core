<?php

namespace LiteMvc\Logger;

use Throwable;
use LiteMvc\Logger\LodModel;
// Пример использования
// ErrorHandler::init();

// Для отключения логирования (например, в тестах)
// ErrorHandler::setEnabled(false);

/**
 * 
 */
final class ErrorHandler
{
    private static $logFile = 'application.log';
    private static $format = "[{datetime}] {level} - {message} {context} {extra}";
    private static $enabled = true;

    /**
     * Инициализация обработчиков ошибок
     */
    public static function init()
    {
        if (!self::$enabled) {
            return;
        }
        self::configureErrorHandling();
        set_error_handler([__CLASS__, 'handleError']);
        set_exception_handler([__CLASS__, 'handleException']);
        register_shutdown_function([__CLASS__, 'handleFatalError']);
    }

    /**
     * @param int $errno
     * @return string
     */
    private static function phpErrorLevelToPsrLevel(int $errno): string
    {
        if ($errno & E_RECOVERABLE_ERROR) {
            return 'critical';
        }
        if ($errno & (E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR)) {
            return 'error';
        }
        if ($errno & (E_WARNING | E_CORE_WARNING | E_COMPILE_WARNING | E_USER_WARNING)) {
            return 'warning';
        }
        if ($errno & (E_NOTICE | E_USER_NOTICE)) {
            return 'notice';
        }
        return 'debug';
    }

    /**
     * @param mixed|int $errorLevel
     * @return string
     */
    public static  function errorLevelToString($errorLevel)
    {
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
     * @param string $class
     * @return string
     */
    private static function psrLevelFromException(string $class): string
    {
        if (str_contains($class, 'NotFound')) {
            return 'notice';
        }

        if ($class === \RuntimeException::class) {
            return 'error';
        }
        if ($class === \InvalidArgumentException::class) {
            return 'warning';
        }
        if ($class === \LogicException::class) {
            return 'error';
        }

        // Ошибки движка
        if (is_subclass_of($class, \Error::class)) {
            return 'critical';
        }

        return 'error'; // fallback
    }

    /**
     * Обработка обычных ошибок
     */
    public static function handleError($errno, $errstr, $errfile, $errline)
    {
        if (!self::$enabled) {
            return false;
        }
        if (!$errno) {
            return false;
        }

        $error = (new LodModel())
            ->setLevel(self::phpErrorLevelToPsrLevel($errno))
            ->setMessage($errstr)
            ->setContext([
                'file' => $errfile,
                'line' => $errline,
            ])
            ->setExtra(self::getExtra());

        self::log($error);
        return true;
    }

    /**
     * Обработка исключений
     */
    public static function handleException(Throwable $exception)
    {
        if (!self::$enabled) {
            return;
        }

        $error = (new LodModel())
            ->setLevel(self::psrLevelFromException(get_class($exception)))
            ->setMessage($exception->getMessage())
            ->setContext([
                'type' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTrace(),
            ])
            ->setExtra(self::getExtra());

        self::log($error);
        return true;
    }

    /**
     * Обработка фатальных ошибок
     */
    public static function handleFatalError()
    {
        if (!self::$enabled) {
            return;
        }

        $error_last = error_get_last();
        if ($error_last) {
            $error = (new LodModel())->setMessage($error_last['message'])
                ->setLevel(self::phpErrorLevelToPsrLevel($error_last['type'] ?? E_ERROR))
                ->setContext([
                    'file' => $error_last['file'],
                    'line' => $error_last['line'],
                ])
                ->setExtra(self::getExtra());
            self::log($error);
            return true;
        }
    }

    /**
     * Пользовательская реализация error_log
     * @param string|array|Throwable $message
     * @param mixed $message_type
     * @param mixed $destination
     * @param mixed $extra_headers
     * @return bool
     */
    public static function customErrorLog($message, $message_type = 0, $destination = '', $extra_headers = '')
    {
        $error = new LodModel();
        if ($message instanceof Throwable) {
            $error->setLevel(self::psrLevelFromException(get_class($message)));
            $error->setMessage($message->getMessage());
            $error->setContext([
                'file' => $message->getFile(),
                'line' => $message->getLine(),
                'code' => $message->getCode(),
            ]);
            $error->setExtra($message->getTrace());
            //
        } else if (is_array($message)) {
            $error->setDatetime($message['datetime'] ?? $message['date_time'] ?? $message['date'] ?? $message['time'] ?? $message['dt'] ?? $error->getDatetime());
            $error->setLevel($message['level'] ?? $message['level_name'] ?? $message['lvl'] ?? $message['type'] ?? '');
            $error->setMessage($message['message'] ?? $message['msg'] ?? $message['text'] ?? '');
            $error->setContext($message['context'] ?? $message['cont'] ?? []);
            $error->setExtra($message['extra'] ?? $message['ext'] ?? []);
            //
        } else if (is_string($message) || method_exists($message, '__toString') || $message instanceof \Stringable) {
            $error->setMessage($message);
        } else {
            try {
                $error->setMessage($message);
            } catch (Throwable $th) {
                $error->setMessage('Не удаётся конвертировать сообщение лога в строку: ' . $th->getMessage());
            }
        }
        self::log($error);
        return true;
    }

    /**
     * Interpolates context values into the message placeholders.
     */
    private static function interpolate(string $format, array $context = []): string
    {
        // build a replacement array with braces around the context keys
        $replace = [];
        foreach ($context as $key => $val) {
            // check that the value can be cast to string
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            } else {
                $replace['{' . $key . '}'] = $val ? json_encode($val, JSON_UNESCAPED_UNICODE) : '';
            }
        }
        // interpolate replacement values into the message and return
        return strtr($format, $replace);
    }

    /**
     * Запись ошибки в лог
     * @param LodModel $logMessage
     * @return void
     */
    private static function log(LodModel $logMessage)
    {
        if (!self::$enabled) {
            return;
        }

        file_put_contents(
            self::$logFile,
            self::interpolate(self::$format, $logMessage->getArray()) . PHP_EOL,
            FILE_APPEND
        );
    }

    /**
     * Получение контекста выполнения
     */
    private static function getExtra()
    {
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
     * @param bool $enabled
     * @return void
     */
    public static function setEnabled(bool $enabled)
    {
        self::$enabled = $enabled;
    }

    public static function setFile(string $logFile)
    {
        if (!file_exists($logFile)) {
            file_put_contents($logFile, '');
        }
        self::$logFile = $logFile;
    }

    public static function getLogFile(): string
    {
        return self::$logFile;
    }

    //---

    /**
     * Функция для настройки уровня ошибок
     * @return void
     */
    public static function configureErrorHandling()
    {
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
    public static function getEnv()
    {
        return getenv('APP_ENV') ?? 'development';
    }


    // Разработка
    public static function setDevelopmentErrorLevel()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        ini_set('log_errors', 1);
        // ini_set('error_log', __DIR__ . '/error.log');
    }

    // Тестирование
    public static function setTestingErrorLevel()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 0);
        ini_set('log_errors', 1);
        // ini_set('error_log', __DIR__ . '/error.log');
    }

    // Продакшен
    public static function setProductionErrorLevel()
    {
        error_reporting(E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR);
        ini_set('display_errors', 0);
        ini_set('log_errors', 1);
        // ini_set('error_log', __DIR__ . '/error.log');
    }

    // Настройки по умолчанию
    public static function setDefaultErrorLevel()
    {
        error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
        ini_set('display_errors', 0);
        ini_set('log_errors', 1);
        // ini_set('error_log', __DIR__ . '/error.log');
    }
}
