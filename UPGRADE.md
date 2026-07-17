Инструкции по обновлению для lite-mvc
=========================================

Upgrade from 0.1.3
-----------------------
- !!!Корневая папка приложения `src` переименована в `app`!!!
- Обновите пути Mvc::$app->config->srcPath → Mvc::$app->config->appPath


Upgrade from 0.1.2
-----------------------
- мини фиксы + обновлены зависимости
- обновлёна логика класса для консольных команд: BaseConsoleController 
- класс HttpClientProxy переименован HttpClientAuthorization, т.к. в нет ничего связанного с прокси
- обновлен ErrorHandler и MvcLogger

Upgrade from 0.1.1
-----------------------
- обновлены зависимости
- добавлен HttpClient class

Upgrade from 0.1.0
-----------------------
обновить классы файлы:
```php
// web\index.php:
(new \LiteMvc\Core\Core($config))->run();
→
(new \LiteMvc\Core\Application($config))->run();
// src\controllers:
use LiteMvc\Core\Controller\ApiController → BaseApiController
// src\console:
use LiteMvc\Core\Controller\ConsoleController → BaseConsoleController
```
config не массив а объект:
```php
$this->config['name'] → $this->config->name
```
получить данные запроса через глобальный класс Mvc
```php
$rawBody = $this->request->rawBody
→
use LiteMvc\Core\Mvc;
$rawBody = Mvc::$app->request->rawBody
```