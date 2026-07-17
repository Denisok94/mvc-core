<?php

namespace LiteMvc\Component;

/**
 * @property string $rawBody оригинал запроса
 * 
 * @method string getMethod()
 * @method mixed get(string $name, $default = null) 
 * 
 * ```php
 * //запрос - localhost/user.php?id=Qashbs36e
 * echo Mvc::$app->request->id;
 * ```
 */
class Request
{
    /**
     * @var array
     */
    private $storage;

    /**
     * @var string
     */
    public $rawBody;

    /**
     * при создании объекта запроса мы пропускаем все данные
     * через фильтр-функцию для очистки параметров от нежелательных данных
     */
    public function __construct()
    {
        $this->storage = $this->cleanInput($_REQUEST);
        $this->rawBody = file_get_contents('php://input');
    }

    /**
     * магическая функция, которая позволяет обращаться к переменным
     * @param string $name
     */
    public function __get(string $name)
    {
        return $this->storage[$name] ?? null;
    }

    /**
     * Проверка существования переменной
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($this->storage[$name]);
    }

    /**
     * Получить значение переменной
     * @param string $name
     * @param mixed $default
     * @return mixed|null
     */
    public function get(string $name, $default = null)
    {
        return $this->storage[$name] ?? $default;
    }


    public function getMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * очистка данных от опасных символов
     * @param array $data
     */
    private function cleanInput($data)
    {
        if (is_array($data)) {
            $cleaned = [];
            foreach ($data as $key => $value) {
                $cleaned[$key] = $this->cleanInput($value);
            }
            return $cleaned;
        }
        return trim(htmlspecialchars($data, ENT_QUOTES));
    }

    /**
     * возвращаем содержимое хранилища
     * @return array
     */
    public function getRequestEntries(): array
    {
        return $this->storage;
    }
}
