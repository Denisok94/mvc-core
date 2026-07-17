<?php

namespace LiteMvc;

/**
 * @version 0.1.3
 * @property Application $app
 * 
 * @example Пример:
 * ```php
 * Mvc::$app->config; // получить конфигурацию приложения
 * // получить параметр конфигурацию
 * Mvc::$app->config->{params};
 * Mvc::$app->config->get('params');
 * Mvc::$app->config->config['params'];
 * // получить пуп до каталогов с/для данных
 * Mvc::$app->config->varPath; 
 * Mvc::$app->config->webPath;
 * Mvc::$app->config->appPath; 
 * Mvc::$app->config->basePath; // корневой каталог
 * Mvc::$app->request; // данные запроса
 * Mvc::$app->request->id; // получить параметр запроса
 * Mvc::$app->request->get('id');
 * Mvc::$app->request->rawBody; // получить оригинал запроса
 * Mvc::$app->log->info('init'); // отправить сообщение в лог
 * ```
 */
final class Mvc
{
    /**
     * @var Application
     */
    public static Application $app;
}
