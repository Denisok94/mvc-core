<?php

namespace LiteMvc\Core;

use Throwable;
use LiteMvc\Core\Component\Session;
use LiteMvc\Core\Controller\BaseController;
use Wa72\Url\Url;
use denisok94\helper\other\MicroTimer;

class Core
{
    public array $config;
    public MicroTimer $queryTimer;

    /**
     * @var Url
     */
    public $url;
    /**
     * @var Session
     */
    public $session;

    public string $controllerNamespace = 'app\\controllers';
    public string $controller = 'site';

    /**
     *
     * @param array $config
     */
    public function __construct($config = [])
    {
        $this->queryTimer = new MicroTimer(); // start
        $this->config = $config;
        $this->initConfig();
        //
        $url = ((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $this->url = new Url($url);
    }

    /**
     *
     */
    public function initConfig()
    {
        if (isset($this->config['basePath'])) {
            $basePath = $this->config['basePath'];
        }
        $srcPath = $basePath . DIRECTORY_SEPARATOR . "src";
        $this->config['srcPath'] = $srcPath;
        $this->config['viewPath'] = $srcPath . DIRECTORY_SEPARATOR . "views";
        $this->config['webPath'] = $basePath . DIRECTORY_SEPARATOR . "web";
    }

    /**
     *
     */
    public function run()
    {
        $this->session = (new Session())->start();

        $alias = explode('/', $this->url->getPath());

        try {
            if (empty($alias[1])) {
                $alias[1] = $this->controller;
            }

            if (preg_match('/^(?:[a-z0-9_]+-)*[a-z0-9_]+$/', $alias[1])) {
                $class = str_replace(' ', '', ucwords(str_replace('-', ' ', $alias[1])));

                $class = $this->controllerNamespace . '\\' . $class . "Controller";

                /** @var BaseController $controller */
                $controller = new $class($this->config);
                echo $controller->runAction($alias[2] ?? '');
            }
        } catch (Throwable $th) {
            echo $th->getMessage();
            //throw $th;
        }

        // echo "<br>";
        // printf($this->queryTimer);
    }
}
