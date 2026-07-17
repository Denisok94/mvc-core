<?php

namespace LiteMvc\Controller;

use Exception;
use LiteMvc\Core\View;
use LiteMvc\Core\Config;

/**
 * @property Config $config информация о конфигурации приложения
 * @property View $view шаблонизатор
 * 
 * @method bool beforeAction(string $action) обработать запрос до выполнения кода в action
 * @method void afterAction(string $action) обработать/доработать/дополнить ответ/результат action
 * @method string render(string $view, $params = []) рендер шаблона
 * 
 * @method array actions() ограничение доступа (в разработке)
 */
abstract class BaseController
{
    /**
     * @var View
     */
    public $view;

    public $defaultAction = 'index';
    public $layout = 'main';
    public Config $config;

    public const CODE_OK = 200;
    public const CODE_CREATED = 201;
    public const CODE_NO_CONTENT = 204;
    public const CODE_BAD_REQUEST = 400;
    public const CODE_UNAUTHORIZED = 401;
    public const CODE_FORBIDDEN = 403;
    public const CODE_NOT_FOUND = 404;
    public const CODE_INTERNAL_SERVER_ERROR = 500;

    public function init(Config $config)
    {
        $this->config = $config;
        $this->view = new View($config, get_class($this));
    }

    /**
     * todo
     */
    public function actions(): array
    {
        return [];
    }


    /**
     * @param string $action
     * @return bool
     */
    public function beforeAction(string $action): bool
    {
        return true;
    }

    /**
     * @param string $action
     */
    public function afterAction(string $action, &$result) {}

    /**
     * @param string $view the view name.
     * @param array $params the parameters
     * @throws Exception
     */
    public function render(string $view, $params = [])
    {
        $content = $this->getView()->render($view, $params, $this);
        return $this->renderContent($content);
    }

    /**
     * @param string $content
     * @return string
     * @throws Exception
     */
    public function renderContent($content)
    {
        $layoutFile = $this->findLayoutFile();
        if ($layoutFile !== false) {
            $this->view->theme = true;
            return $this->getView()->renderFile($layoutFile, ['content' => $content], $this);
        }

        return $content;
    }
    /**
     * @return string|bool 
     */
    public function findLayoutFile()
    {
        $layout = null;
        if (is_string($this->layout)) {
            $layout = $this->layout;
        }
        if ($layout === null) {
            return false;
        }

        $file = $this->getView()->getLayoutPath() .  $layout;

        if (pathinfo($file, PATHINFO_EXTENSION) !== '') {
            return $file;
        }

        $path = $file . '.php';
        return $path;
    }

    /**
     * @return View
     */
    public function getView()
    {
        return $this->view;
    }
}
