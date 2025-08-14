<?php

namespace LiteMvc\Core;

use LiteMvc\Core\Request;
use LiteMvc\Core\View;

/**
 *
 */
class Controller
{
    /**
     * @var View
     */
    public $view;

    public $defaultAction = 'index';
    public $layout = 'main';
    public array $config;

    /**
     * @var Request
     */
    public $request;

    public const CODE_OK = 200;
    public const CODE_CREATED = 201;
    public const CODE_NO_CONTENT = 204;
    public const CODE_BAD_REQUEST = 400;
    public const CODE_UNAUTHORIZED = 401;
    public const CODE_FORBIDDEN = 403;
    public const CODE_NOT_FOUND = 404;
    public const CODE_INTERNAL_SERVER_ERROR = 500;

    public function __construct($config)
    {
        $this->request = new Request();
        $this->config = $config;
        $this->view = new View($config, get_class($this));
    }

    /**
     *
     */
    public function actions()
    {
        return [];
    }

    /**
     *
     * @param string $action
     * @throws \Exception
     */
    public function runAction(string $action)
    {
        if ($action === '') {
            $action = $this->defaultAction;
        }

        $actionMap = $this->actions();
        if (isset($actionMap[$action])) {
            // return Yii::createObject($actionMap[$id], [$id, $this]);
        }

        if (preg_match('/^(?:[a-z0-9_]+-)*[a-z0-9_]+$/', $action)) {
            $methodName = 'action' . str_replace(' ', '', ucwords(str_replace('-', ' ', $action)));
            if (method_exists($this, $methodName)) {
                $method = new \ReflectionMethod($this, $methodName);
                if ($method->isPublic() && $method->getName() === $methodName) {
                    $this->view->title = $action;
                    if ($this->beforeAction($action)) {
                        $return = $this->$methodName();
                        $this->afterAction($action, $return);
                        return $return;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param string $action
     * @return bool
     */
    public function beforeAction($action)
    {
        return true;
    }

    /**
     * @param string $action
     */
    public function afterAction($action, &$result)
    {
    }

    /**
     * @param string $view the view name.
     * @param array $params the parameters
     * @throws \Exception
     */
    public function render(string $view, $params = [])
    {
        $content = $this->getView()->render($view, $params, $this);
        return $this->renderContent($content);
    }

    /**
     * @param string $content
     * @return string
     * @throws \Exception
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
