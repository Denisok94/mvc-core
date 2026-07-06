Инструкции по обновлению для lite-mvc
=========================================

Upgrade from Helper 0.1.1
-----------------------
- обновлены зависимости
- добавлен HttpClient class

Upgrade from Helper 0.1.0
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