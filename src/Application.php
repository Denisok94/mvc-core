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
use LiteMvc\Core\Logger\MvcLogger;
use LiteMvc\Core\Logger\ErrorLogger;
use Wa72\Url\Url;
use denisok94\helper\other\MicroTimer;

class Application
{
    public Config $config;
    public MicroTimer $queryTimer;
    public MvcLogger $log;

    /**
     * @var Url
     */
    public $url;
    /**
     * @var Session|null
     */
    public $session = null;

    /**
     * @var Request
     */
    public $request;

    protected ?string $sessionClass = null;
    protected string $controllerNamespace = 'app\\controllers';
    protected string $controllerWebBese = 'site';
    protected $components = [];
    protected $params = [];

    /**
     *
     * @param array $config
     */
    public function __construct($config = [])
    {
        $this->queryTimer = new MicroTimer();
        $this->log = new MvcLogger();
        $this->config = new Config($config);
        $this->request = new Request();
        $this->initConfig();
        $this->initComponents();
        //
        $url = ((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $this->url = new Url($url);
    }

    /**
     *
     */
    public function initConfig()
    {
        // env init
        $srcPath = $this->config->srcPath;
        $dotenv = Dotenv\Dotenv::createImmutable($srcPath);
        $dotenv->safeLoad();
        //
        $this->controllerNamespace = $this->config->controllerNamespace ?? $this->controllerNamespace;
        $this->controllerWebBese = $this->config->controllerWebBese ?? $this->controllerWebBese;
        $this->params = $this->config->params ?? [];
    }

    // Инициализация компонентов
    private function initComponents() {
        $this->components = $this->config->components ?? [];
        //
        $this->sessionClass = $this->components['session']['class'] ?? null;
        if ($this->sessionClass) {
            $s = $this->sessionClass;
            $this->session = (new $s())->start();
        }
    }

    // Получение компонента
    public function getComponent($id) {
        return $this->components[$id] ?? null;
    }

    /**
     *
     */
    public function run()
    {
        ErrorLogger::init();
        Mvc::$app = $this;
        $alias = explode('/', $this->url->getPath());

        try {
            if (empty($alias[1])) {
                $alias[1] = $this->controllerWebBese;
            }

            if (preg_match('/^(?:[a-z0-9_]+-)*[a-z0-9_]+$/', $alias[1])) {
                $class = str_replace(' ', '', ucwords(str_replace('-', ' ', $alias[1])));
                echo $this->controllerInt($class, $alias[2] ?? '');
            }
        } catch (MvcException $ex) {
        } catch (Error| Throwable $th) {
            $class = $action = null;
            if (isset($this->components['errorHandler']['errorAction'])) {
                $errorAction = $this->components['errorHandler']['errorAction'];
                liset($class, $action) = explode('/', $errorAction);
                echo $this->controllerInt($class, $action);
            } else {
                echo $th->getMessage();
            }

            //throw $th;
        } finally {}

        // echo "<br>";
        // printf($this->queryTimer);
    }

    private function controllerInt($class, $action) {
        $class = $this->controllerNamespace . '\\' . $class . "Controller";
        /** @var BaseController $controller */
        $controller = new $class();
        $controller->init($this->config);
        return $controller->runAction($alias[2] ?? '');
    }
    
    // Запрещаем клонирование объекта
    private function __clone() {}

    // Запрещаем восстановление объекта
    private function __wakeup() {}
}
