<?php

namespace LiteMvc\Core;

/**
 * 
 */
class Config
{
    public array $config = [
        'id' => 'basic',
        'name' => 'Lite Mvc',
        'language' => 'ru-RU',
        'basePath' => false,
        'baseNamespace' => 'app',
        'controllerNamespace' => 'app\\controllers',
        'controllerWebBese' => 'site',
        'modules' => [],
        'components' => [
            'db' => [
                // 'class' => 'yii\db\Connection',
                // 'dsn' => 'mysql:host=localhost;dbname=yii2basic',
                // 'username' => 'root',
                // 'password' => '',
                // 'charset' => 'utf8',
            ],
            'session' => [
                'class' => 'LiteMvc\Core\Component\Session',
            ],
            // todo:
            // 'errorHandler' => [
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

        $basePath = $this->basePath ?? false;
        if (!$basePath) {
            $webIndex = $this->getParentFile();
            if ($webIndex) {
                $basePath = dirname($webIndex, 1);
            }
        }
        if ($basePath) {
            $this->webPath = $basePath . DIRECTORY_SEPARATOR . "web";
            $srcPath = $basePath . DIRECTORY_SEPARATOR . "src";
            $this->srcPath = $srcPath;
            $this->viewPath = $srcPath . DIRECTORY_SEPARATOR . "views";
        } else {
            // error
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
}
