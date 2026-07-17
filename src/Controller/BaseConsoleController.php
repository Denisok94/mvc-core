<?php

namespace LiteMvc\Controller;

use Exception;
use LiteMvc\MvcException;
use LiteMvc\Core\Config;
use denisok94\helper\other\Console;

/**
 * @property Config $config информация о конфигурации приложения
 * @property array $requiredOptions перечень обязательных опций для команды
 * @property array $requiredArguments перечень обязательных параметров/аргументов для команды
 * 
 * @method void execute() тут выполняемый код
 * @method array getArguments()
 * @method bool hasArgument($name)
 * @method mixed getArgument($name, $default = null)
 * @method array getOptions()
 * @method bool hasOption($name)
 * @method mixed getOption($name, $default = null)
 */
abstract class BaseConsoleController extends Console
{
    public array $requiredOptions = [];
    public array $requiredArguments = [];

    public Config $config;
    public Console $console;

    public function __construct()
    {
        parent::__construct([
            'options' => $this->requiredOptions,
            'arguments' => $this->requiredArguments,
        ]);
    }

    public function init(Config $config)
    {
        $this->config = $config;
        try {
            $this->console = new Console();
        } catch (Exception $th) {
            die($th->getMessage());
        }
        return $this;
    }

    public function execute(): void
    {
        throw new MvcException("реализуйте свой код в методе `execute()`", 0);
    }
}
