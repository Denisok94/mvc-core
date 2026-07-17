<?php

namespace LiteMvc\Core;

use Error;
use Exception;
use Throwable;
use LiteMvc\Core\Config;
use LiteMvc\Core\MvcException;
use LiteMvc\Core\Component\Session;
use LiteMvc\Core\Component\Request;
use LiteMvc\Core\Controller\BaseController;
use LiteMvc\Core\Controller\BaseConsoleController;
use LiteMvc\Core\Logger\MvcLogger;
use LiteMvc\Core\Logger\LoggerInterface;
use LiteMvc\Core\Logger\ErrorHandler;
use Wa72\Url\Url;
use denisok94\helper\other\MicroTimer;
use denisok94\helper\other\Console;

/**
 * @property Config $config информация о конфигурации приложения
 * @property MvcLogger|LoggerInterface $log отправить сообщение в лог
 * @property Request $request получить данные запроса
 * @property array $components компоненты приложения (в разработке)
 * @property array $params параметры (в разработке)
 * @property MicroTimer $queryTimer информация о времени выполнения кода
 * 
 * @method void run() запуск веб версии приложения
 * @method void runConsole() запуск консольной версии приложения
 */
class Application
{
    public Config $config;
    public MicroTimer $queryTimer;
    /**
     * @var MvcLogger|LoggerInterface
     */
    public $log;
    public Url $url;
    // /** @var Session|null */
    public $session = null;
    public Request $request;
    protected ?string $sessionClass = null;
    protected string $controllerNamespace = 'app\\controllers';
    protected string $consoleNamespace = 'app\\commands';
    protected string $controllerWebBase = 'site';
    public $components = [];
    public $params = [];

    /**
     *
     * @param array $config
     */
    public function __construct($config = [])
    {
        ErrorHandler::init();
        $this->queryTimer = new MicroTimer();
        $this->config = new Config($config);
        //
        $this->initConfig();
        $this->initEnv();
        $this->initComponents();
        //
        if (isset($_SERVER['HTTP_HOST']) && isset($_SERVER['REQUEST_URI'])) {
            $this->request = new Request();
            $this->url = new Url(
                (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
            );
        }
    }

    private function initEnv()
    {
        $dotenv = \Dotenv\Dotenv::createImmutable($this->config->basePath);
        $dotenv->safeLoad();
    }

    /**
     *
     */
    private function initConfig()
    {
        $logDir = $this->config->varPath . DIRECTORY_SEPARATOR . "log";
        $logFile = $logDir . DIRECTORY_SEPARATOR . date("Y-m-d") . "_application.log";
        if (!file_exists($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        ErrorHandler::setFile($logFile);
        //
        $this->controllerNamespace = $this->config->controllerNamespace ?? $this->controllerNamespace;
        $this->consoleNamespace = $this->config->consoleNamespace ?? $this->consoleNamespace;
        $this->controllerWebBase = $this->config->controllerWebBase ?? $this->controllerWebBase;
        $this->params = $this->config->params ?? [];
    }

    // Инициализация компонентов
    private function initComponents()
    {
        $this->components = $this->config->components ?? [];
        //
        $this->sessionClass = $this->components['session']['class'] ?? null;
        if ($this->sessionClass) {
            $class = $this->sessionClass;
            $this->session = (new $class())->start();
        }
        if (isset($this->components['log']['class'])) {
            $class = $this->components['log']['class'];
            $this->log = new $class();
        } else {
            $this->log = new MvcLogger();
        }

        // ErrorHandler::setEnabled(false);
    }

    // Получение компонента
    public function getComponent($id)
    {
        return $this->components[$id] ?? null;
    }

    /**
     *
     */
    public function run()
    {
        if (!isset(Mvc::$app)) {
            Mvc::$app = $this;
        }
        $alias = explode('/', $this->url->getPath());

        try {
            if (empty($alias[1])) {
                $alias[1] = $this->controllerWebBase;
            }

            if (preg_match('/^(?:[a-z0-9_]+-)*[a-z0-9_]+$/', $alias[1])) {
                $class = str_replace(' ', '', ucwords(str_replace('-', ' ', $alias[1])));
                echo $this->controllerInt($class, $alias[2] ?? '');
            }
        } catch (MvcException $ex) {
            http_response_code($ex->getCode());
            echo $ex->getMessage();
        } catch (Error | Throwable $th) {
            $class = $action = null;
            if (isset($this->components['errorHandler']['errorAction'])) {
                $errorAction = $this->components['errorHandler']['errorAction'];
                list($class, $action) = explode('/', $errorAction);
                echo $this->controllerInt($class, $action);
            } else {
                echo sprintf("%s(%s:%s)", $th->getMessage(), $th->getFile(), $th->getLine());
            }
        } finally {
        }

        // echo "<br>";
        // printf($this->queryTimer);
    }

    /**
     * @return void
     */
    public function runConsole()
    {
        if (!isset(Mvc::$app)) {
            Mvc::$app = $this;
        }
        try {
            $console = new Console();

            if ($alias = $console->getArgument(0)) {

                if (preg_match('/^(?:[a-z0-9_]+-)*[a-z0-9_]+$/', $alias)) {
                    $class = str_replace(' ', '', ucwords(str_replace('-', ' ', $alias)));

                    $class = $this->consoleNamespace . '\\' . $class . "Controller";
                    if (!class_exists($class)) {
                        throw new MvcException("console controller class '$class' not found.", 404);
                    }
                    /** @var BaseConsoleController $controller */
                    $controller = new $class();
                    $controller->init($this->config)
                        ->execute();
                }
            } else {
                throw new MvcException("не указано имя контроллера 'php LiteMvc.php {consoleController}'", 404);
            }
        } catch (MvcException $ex) {
            echo "\r" . $ex->getMessage();
        } catch (Error | Throwable $th) {
            echo sprintf("%s(%s:%s)", $th->getMessage(), $th->getFile(), $th->getLine());
        } finally {
        }
    }

    private function controllerInt(string $class, $action = '')
    {
        $class = $this->controllerNamespace . '\\' . $class . "Controller";
        if (!class_exists($class)) {
            throw new MvcException("Controller class '$class' not found.", 404);
        }
        /** @var BaseController $controller */
        $controller = new $class();
        $controller->init($this->config);
        return $controller->runAction($action);
    }

    // Запрещаем клонирование объекта
    public function __clone() {}

    // Запрещаем восстановление объекта
    public function __wakeup() {}
}
