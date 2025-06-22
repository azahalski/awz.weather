# AWZ: Права доступа (awz.weather)

<!-- desc-start -->

Модуль содержит API для получения погоды с сервисов:

* open-meteo.com (бесплатный, по умолчанию)
* api-ninjas.com
* openweathermap.org
* weatherapi.com
* weatherstack.com

**Поддерживаемые редакции CMS Битрикс:**<br>
«Первый сайт», «Старт», «Стандарт», «Малый бизнес», «Эксперт», «Бизнес», «Корпоративный портал», «Энтерпрайз», «Интернет-магазин + CRM»

<!-- desc-end -->

## Документация для разработчиков

<!-- dev-start -->

### Конструктор \Awz\Weather\App

На вход принимает 1 параметр `string` `$provider`

| Провайдер      | Описание                    |
|----------------|-----------------------------|
| OpenMeteo      | По умолчанию open-meteo.com |
| Ninjas         | api-ninjas.com              |
| OpenWeatherMap | openweathermap.org          |
| WeatherApi     | weatherapi.com              |
| WeatherStack   | weatherstack.com            |

### \Awz\Weather\App->getCurrent

Получение погоды с внешнего сервиса и запись/обновление в базу

| Параметр      | Описание |
|---------------|----------|
| lat `float`   | Широта   |
| lon `float`   | Долгота  |

```php
use Bitrix\Main\Loader;

/*
 * пример получения и записи погоды на 7-14 дней в базу данных
 * (период отличается в зависимости от сервиса и тарифного плана на сервисе)
*/

$lat = '43.357812';
$lon = '132.084237';

if(Loader::includeModule('awz.weather')){
    $app = new \Awz\Weather\App();
    $app->getCurrent((float)$lat, (float)$lon);
}

```

Рекомендуется выносить логику обновления погоды на агент:

модуль: awz.weather
функция: \\Awz\\Weather\\Agent::autoUpdate('43.357812', '132.084237', 'OpenMeteo');

### \Awz\Weather\App->getMinDay

Получение структуированных данных по погоде из базы на указанную дату. 

**Внимание!** метод не делает запрос на внешний сервис, 
у вас уже должна быть реализована логика запроса погоды через метод: \Awz\Weather\App->getCurrent

| Параметр        | Описание        |
|-----------------|-----------------|
| lat `float`     | Широта          |
| lon `float`     | Долгота         |
| timestamp `int` | Дата в unixtime |

```php
use Bitrix\Main\Loader;

$lat = '43.357812';
$lon = '132.084237';

if(Loader::includeModule('awz.weather')){
    $app = new \Awz\Weather\App();
    $dayVidjet = $app->getMinDay($lat, $lon, '+1day');
}

```

<!-- dev-end -->

<!-- cl-start -->
## История версий

https://github.com/azahalski/awz.weather/blob/master/CHANGELOG.md

<!-- cl-end -->