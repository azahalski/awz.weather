<?php
namespace Awz\Weather;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM;
use Bitrix\Main\Web\Json;

class HistoryTable extends ORM\Data\DataManager {

    use ORM\Data\Internal\DeleteByFilterTrait;

    public static function getFilePath()
    {
        return __FILE__;
    }

    public static function getTableName()
    {
        return 'b_awz_weather_history';
    }

    public static function getMap()
    {
        return [
            (new ORM\Fields\IntegerField('ID'))
                ->configureTitle(Loc::getMessage('AWZ_WEATHER_HISTORY_ENTITY_ID'))
                ->configureAutocomplete()->configurePrimary(true),
            (new ORM\Fields\StringField('LAT_LON_MD5'))->configureRequired(true)
                ->configureTitle(Loc::getMessage('AWZ_WEATHER_HISTORY_ENTITY_LAT_LON_MD5')),
            (new ORM\Fields\StringField('PROVIDER'))->configureRequired(true)
                ->configureTitle(Loc::getMessage('AWZ_WEATHER_HISTORY_ENTITY_PROVIDER')),
            (new ORM\Fields\StringField('PARAMS'))
                ->configureTitle(Loc::getMessage('AWZ_WEATHER_HISTORY_ENTITY_PARAMS'))
                ->addSaveDataModifier(function ($value){
                    return Json::encode($value);
                })->addFetchDataModifier(function ($str) {
                    $data = Json::decode($str);
                    if(isset($data['provider'])){
                        $className = "\\Awz\\Weather\\Providers\\".$data['provider'];
                        if(method_exists($className, "fetchDataModifier")){
                            $className::fetchDataModifier($data);
                        }
                    }
                    return $data;
                }),
            (new ORM\Fields\DatetimeField('DATE_DAY'))->configureRequired(true)
                ->configureTitle(Loc::getMessage('AWZ_WEATHER_HISTORY_ENTITY_DATE_DAY')),
            (new ORM\Fields\DatetimeField('DATE_UP'))->configureRequired(true)
                ->configureTitle(Loc::getMessage('AWZ_WEATHER_HISTORY_ENTITY_ATE_UP'))
        ];
    }

}