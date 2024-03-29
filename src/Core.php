<?php

namespace LiteMvc\Core;

use LiteMvc\Core\Controller;
use LiteMvc\Core\Session;
use Wa72\Url\Url;
use denisok94\helper\other\MicroTimer;

class Core
{
    public $config;
    public $queryTimer;

    /**
     * @var Url
     */
    public $url;
    /**
     * @var Session
     */
    public $session;

    public $controllerNamespace = 'app\\controllers';
    public $controller = 'site';

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

                /** @var Controller $controller */
                $controller = new $class($this->config);
                echo $controller->runAction($alias[2] ?? '');
            }
        } catch (\Throwable $th) {
            echo $th->getMessage();
            //throw $th;
        }

        // echo "<br>";
        // printf($this->queryTimer);
    }
}
