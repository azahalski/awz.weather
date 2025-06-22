<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;

$arComponentParameters = [
    "GROUPS" => [
        "DEF" => [
            "NAME" => Loc::getMessage('AWZ_WEATHER_SETT_PARAM_GROUP_DEF'),
        ],
    ],
    "PARAMETERS" => [
        'MAP_DATA' => [
            'NAME' => Loc::getMessage('AWZ_WEATHER_SETT_PARAM_MAP_DATA'),
            'TYPE' => 'CUSTOM',
            'JS_FILE' => '/bitrix/components/awz/weather.day/settings/settings.js?v=7',
            'JS_EVENT' => 'OnGoogleMapSettingsEdit',
            'JS_DATA' => 'ru||'.Loc::getMessage('AWZ_WEATHER_PARAM_DATA_DEFAULT_TITLE'),
            'DEFAULT' => serialize([
                'google_lat' => Loc::getMessage('AWZ_WEATHER_PARAM_DATA_DEFAULT_LAT'),
                'google_lon' => Loc::getMessage('AWZ_WEATHER_PARAM_DATA_DEFAULT_LON'),
                'google_scale' => 13
            ]),
            'PARENT' => 'DEF',
        ],
        'DATE' => [
            'NAME' => Loc::getMessage('AWZ_WEATHER_SETT_PARAM_DATE'),
            'TYPE' => 'STRING',
            'DEFAULT' => "+1day",
            'PARENT' => 'DEF',
        ],
        "CACHE_TIME" => ["DEFAULT"=>"43200"],
    ]
];