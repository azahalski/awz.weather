<?php
namespace Awz\Weather\Providers;

use Bitrix\Main\Result;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Error;

/**
 * openweathermap.org
 * see https://openweathermap.org/api/one-call-3
 */
class OpenWeatherMap extends Base{

    /**
     *
     */
    const API_URL = 'https://api.openweathermap.org/data/3.0/';

    const MAX_TIMEOUT = 15;

    /**
     * @var string
     */
    private $key;

    /**
     * @param string $apiKey
     */
    public function __construct(string $apiKey){
        $this->key = $apiKey;
    }

    public function getCurrent(float $lat, float $lon): Result
    {
        $lat = round($lat, 2);
        $lon = round($lon, 2);
        $result = new Result();

        $httpClient = new HttpClient();
        $httpClient->disableSslVerification();
        $httpClient->setStreamTimeout(static::MAX_TIMEOUT);
        $httpClient->setTimeout(static::MAX_TIMEOUT);

        $res = $httpClient->get(
            static::API_URL.'onecall?'.
            http_build_query(['appid'=>$this->getKey(),'lat'=>$lat, 'lon'=>$lon])
        );

        try{
            $result->setData(['result'=>Json::decode($res)]);
        }catch (\Exception $e){
            $result->setData(['result'=>$res]);
            $result->addError(new Error($e->getMessage()));
        }

        return $result;
    }

    private function getKey():string
    {
        return $this->key;
    }

}