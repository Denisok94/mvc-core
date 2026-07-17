<?php

namespace LiteMvc\Core;

use Exception;
use RuntimeException;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionUnionType;
use ReflectionMethod;
use LiteMvc\Mvc;
use LiteMvc\MvcException;
use LiteMvc\Controller\BaseController;
use LiteMvc\Controller\BaseConsoleController;
//
use LiteMvc\Logger\MvcLogger;
use Psr\Log\LoggerInterface;

class Builder
{
    /**
     * Маппинг интерфейсов
     * @var array
     */
    private $interfaceMap = [
        LoggerInterface::class => MvcLogger::class,
        // AnotherInterface::class => AnotherService::class,
    ];

    /**
     * @param string $class
     * @param string $action
     * @throws MvcException
     */
    public function controllerInt(string $class, $action = '')
    {
        if (preg_match('/^(?:[a-z0-9_]+-)*[a-z0-9_]+$/', $class)) {
            $className = str_replace(' ', '', ucwords(str_replace('-', ' ', $class)));
        } else {
            throw new MvcException("Invalid controller name.", 404);
        }

        $className = Mvc::$app->controllerNamespace . '\\' . $className . "Controller";
        if (!class_exists($className)) {
            throw new MvcException("Controller class '$className' not found.", 404);
        }
        /** @var BaseController $controller */
        $controller = $this->resolveController($className);
        $controller->init(Mvc::$app->config);
        return $this->runAction($controller, $action);
    }

    /**
     * @param string $className
     * @throws RuntimeException
     * @return object|null
     */
    private function resolveController(string $className): object
    {
        $reflector = new ReflectionClass($className);
        $constructor = $reflector->getConstructor();

        if (!$constructor) {
            return new $className();
        }

        $deps = [];
        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();
            if (!$type instanceof ReflectionNamedType && !$type instanceof ReflectionUnionType) {
                // параметр контроллера не определён
                throw new RuntimeException("Constructor parameter without type: " . $param->getName());
            }
            $deps[] = $this->resolveDependency($type);
        }

        return $reflector->newInstanceArgs($deps);
    }

    /**
     * @param ReflectionNamedType $type
     * @throws RuntimeException
     * @return object|null
     */
    private function resolveDependency(ReflectionNamedType $type): object
    {
        $depClassOrTypeName = $type->getName();

        if (isset($this->interfaceMap[$depClassOrTypeName])) {
            $className = $this->interfaceMap[$depClassOrTypeName];
            return $this->resolveController($className);
        }

        // Если это класс, а не интерфейс — пробуем создать его напрямую
        if (class_exists($depClassOrTypeName)) {
            return $this->resolveController($depClassOrTypeName);
        }

        throw new RuntimeException("No binding found for type: $depClassOrTypeName");
    }

    //-----

    /**
     * @param BaseController $controller
     * @param string $action
     * @throws MvcException
     */
    public function runAction(BaseController $controller, string $action)
    {
        if ($action === '') {
            $action = $controller->defaultAction;
        }

        $actionMap = $controller->actions();
        if (isset($actionMap[$action])) {
            // todo: ограничения и роли
        }

        if (!preg_match('/^(?:[a-z0-9_]+-)*[a-z0-9_]+$/', $action)) {
            throw new MvcException("Invalid action name.", 400);
        }

        $methodName = 'action' . str_replace(' ', '', ucwords(str_replace('-', ' ', $action)));
        if (!method_exists($controller, $methodName)) {
            throw new MvcException("Action '$action' not found.", 404);
        }

        $method = new ReflectionMethod($controller, $methodName);
        if (!$method->isPublic()) {
            throw new MvcException("Action '$action' is not public.", 403);
        }

        // Собираем аргументы для вызова
        $args = [];

        $pathParts = explode('/', Mvc::$app->url->getPath());
        unset($pathParts[0], $pathParts[1]); // удаляем имя controller и action
        $pathParams = array_values($pathParts); // Пересчитываем индексы, чтобы были 0, 1, 2...

        foreach ($method->getParameters() as $index => $param) {
            $name = $param->getName();

            // Сначала пробуем взять из query/request (если ?id=5)
            if (Mvc::$app->request->has($name)) {
                $args[] = Mvc::$app->request->get($name);
                continue;
            }

            // берем из URL пути по порядку
            if (isset($pathParams[$index])) {
                // Тут можно добавить приведение типов, если нужно
                $args[] = $pathParams[$index];
                continue;
            }

            // Если есть значение по умолчанию — берем его
            if ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
                continue;
            }

            // Если ничего не подошло — ошибка
            throw new MvcException("Missing required parameter: $name (index: $index)", 400);
        }

        $controller->view->title = $controller->config->name . ($action != $controller->defaultAction ?  " / " . $action : '');
        if ($controller->beforeAction($action)) {
            $return = $method->invokeArgs($controller, $args);
            $controller->afterAction($action, $return);
            return $return;
        }

        return null;
    }


    //-----

    public function consoleControllerInt(string $class)
    {
        if (preg_match('/^(?:[a-z0-9_]+-)*[a-z0-9_]+$/', $class)) {
            $class = str_replace(' ', '', ucwords(str_replace('-', ' ', $class)));
        } else {
            throw new MvcException("Invalid controller name.", 404);
        }

        $class = Mvc::$app->consoleNamespace . '\\' . $class . "Controller";
        if (!class_exists($class)) {
            throw new MvcException("console controller class '$class' not found.", 404);
        }
        /** @var BaseConsoleController $controller */
        $controller = new $class();
        $controller->init(Mvc::$app->config)->execute();
    }
}
