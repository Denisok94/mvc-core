<?php

namespace LiteMvc\Core;

use LiteMvc\MvcException;

/**
 * @property array $config
 * @property string $basePath корневой каталог
 * @property string $webPath веб папка 
 * @property string $appPath папка приложения 
 * @property string $viewPath веб папка 
 * @property string $varPath var папка 
 * @method mixed get($property, $default = null) Получить значение свойство конфигурации
 */
class Config
{
    public string $basePath, $webPath, $appPath, $viewPath, $varPath;
    /**
     * теперь путь к папке приложения в переменной: `appPath`
     * @deprecated устарело с версии 0.1.3
     * @var string
     */
    public string $srcPath;

    public array $config = [
        'id' => 'basic',
        'name' => 'Lite Mvc',
        'language' => 'ru-RU',
        'basePath' => false,
        'baseNamespace' => 'app',
        'controllerNamespace' => 'app\\controllers',
        'consoleNamespace' => 'app\\commands',
        'controllerWebBase' => 'site',
        'modules' => [],
        'components' => [
            'db' => [
                // 'class' => 'yii\db\Connection',
                // 'dsn' => 'mysql:host=localhost;dbname=yii2basic',
                // 'username' => 'root',
                // 'password' => '',
                // 'charset' => 'utf8',
                // 'dsn' => 'sqlite:' . __DIR__ . "/../db/user.db",
            ],
            // 'session' => [
            //     'class' => 'LiteMvc\Core\Component\Session',
            // ],
            // todo:
            'log' => [
                'class' => 'LiteMvc\Core\Logger\MvcLogger',
                // 'format' => '',
                // 'levels' => ['error', 'warning'],
            ],
            // 'errorHandler' => [
            //     'class' => 'LiteMvc\Core\Logger\ErrorHandler',
            //     'errorAction' => 'site/error',
            // ],
            // 'cache' => [
            //     'class' => 'LiteMvc\Core\Component\FileCache',
            // ],
            // 'user' => [
            //     'class' => 'app\models\User',
            // ],
            // 'mailer' => [
            //     'class' => 'LiteMvc\Core\Component\Mailer',
            // ],
        ]
    ];

    /**
     * @param array $app_config
     */
    public function __construct($app_config = [])
    {
        $this->config = array_merge($this->config, $app_config);

        $basePath = $this->config['basePath'] ?? false;
        if (!$basePath) {
            $webIndex = $this->getParentFile();
            if ($webIndex) {
                $basePath = dirname($webIndex, 2);
            }
        } else {
            $basePath = $this->getAddDir($basePath);
        }
        if ($basePath) {
            $this->basePath = $basePath;
            $this->varPath = $basePath . DIRECTORY_SEPARATOR . "var";
            $this->webPath = $basePath . DIRECTORY_SEPARATOR . "web";
            $this->srcPath = $this->appPath = $basePath . DIRECTORY_SEPARATOR . "app";
            $this->viewPath = $this->appPath . DIRECTORY_SEPARATOR . "views";
        } else {
            throw new MvcException("в файле конфига не указан 'basePath'", 100);
        }
    }

    private function getParentFile()
    {
        $files = get_included_files();
        if (count($files) > 1) {
            return $files[0];
        }
        return false;
    }

    private function getAddDir(string $basePath): string
    {
        $appPath = $basePath . DIRECTORY_SEPARATOR . "app";
        $levels = 1;
        while (!is_dir($appPath)) {
            $basePath = dirname($basePath, $levels);
            $appPath = $basePath . DIRECTORY_SEPARATOR . "app";
            if ($levels > 5) {
                throw new MvcException("в файле конфига не указан 'basePath'", 100);
            }
            $levels++;
        }
        return $basePath;
    }

    /**
     * Добавить динамическое свойство
     * @param string $property
     * @param mixed $value
     */
    public function __set($property, $value)
    {
        $this->config[$property] = $value;
    }

    /**
     * Получить значение свойство
     * @param string $property
     */
    public function __get($property)
    {
        return $this->config[$property] ?? null;
    }

    /**
     * Получить значение свойство
     * @param mixed $property
     * @param mixed $default
     * @return mixed|null
     */
    public function get($property, $default = null)
    {
        return $this->config[$property] ?? $default;
    }
}
