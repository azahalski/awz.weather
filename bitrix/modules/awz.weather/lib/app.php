<?
namespace Awz\Weather;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class App {

    const MODULE_ID = "awz.weather";
    const DEF_PROVIDER = "OpenMeteo";

    private ?Providers\Base $provider = null;

    public function __construct(string $provider="")
    {
        $className = "\\Awz\\Weather\\Providers\\".$provider;
        if(!$provider && !class_exists($className))
            $provider = static::DEF_PROVIDER;

        $className = "\\Awz\\Weather\\Providers\\".$provider;
        $key = Option::get(self::MODULE_ID, 'KEY_'.$provider, '-', '');
        if(class_exists($className)){
            $this->provider = new $className($key);
        }

        $this->provider?->allowHistory(true);
    }

    public function getCurrent(float $lat, float $lon): \Bitrix\Main\Result
    {
        if(!$this->getProvider()){
            $result = new \Bitrix\Main\Result();
            $result->addError(new \Bitrix\Main\Error("provider not initialized"));
        }else{
            $result = $this->getProvider()?->getCurrent($lat, $lon);
        }
        return $result;
    }

    public function getMinDay(float $lat, float $lon, int $timestamp = 0){
        if(!$timestamp) $timestamp = time();
        $strDate = date('d.m.Y', $timestamp);
        $timestamp = strtotime($strDate);
        $arRes = HistoryTable::getList(['select'=>['*'],'filter'=>[
            '=DATE_DAY'=>DateTime::createFromTimestamp($timestamp),
            '=LAT_LON_MD5'=>$this->getProvider()?->getMd5Hash($lat, $lon)
        ]])->fetchAll();
        $candidate = [
            'timestamp'=>$timestamp,
            'lat'=>$lat,
            'lon'=>$lon
        ];
        foreach($arRes as $weather){
            if(isset($weather['PARAMS']['day_vidjet'])){
                $candidate = $candidate + $weather['PARAMS']['day_vidjet'];
            }
        }
        $candidate['lang'] = [
            'day'=>self::getLangDay($timestamp),
            'min_day'=>self::getLangDayMin($timestamp),
            'month'=>self::getLangMonth($timestamp),
            'min_month'=>self::getLangMonthMin($timestamp)
        ];
        return $candidate;
    }

    public function getProvider(): ?Providers\Base
    {
        return $this->provider;
    }

    public static function getLangDay(int $timestamp = 0){
        if(!$timestamp) $timestamp = time();
        $key = date('w', $timestamp);
        return Loc::getMessage('AWZ_WEATHER_APP_DAYS_'.$key);
    }

    public static function getLangDayMin(int $timestamp = 0){
        if(!$timestamp) $timestamp = time();
        $key = date('w', $timestamp);
        return Loc::getMessage('AWZ_WEATHER_APP_DAYS_MIN_'.$key);
    }

    public static function getLangMonth(int $timestamp = 0){
        if(!$timestamp) $timestamp = time();
        $key = date('m', $timestamp);
        return Loc::getMessage('AWZ_WEATHER_APP_MONTH_'.$key);
    }

    public static function getLangMonthMin(int $timestamp = 0){
        if(!$timestamp) $timestamp = time();
        $key = date('m', $timestamp);
        return Loc::getMessage('AWZ_WEATHER_APP_MONTH_MIN_'.$key);
    }

}