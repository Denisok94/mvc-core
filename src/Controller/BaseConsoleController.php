<?php

namespace LiteMvc\Core\Controller;

use Exception;
use ReflectionMethod;
use LiteMvc\Core\View;
use LiteMvc\Core\Config;
use LiteMvc\Core\MvcException;
use denisok94\helper\other\Console;

/**
 * todo
 */
abstract class BaseConsoleController extends Console
{
    public $requiredOptions = [];
    public $requiredArguments = [];

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
