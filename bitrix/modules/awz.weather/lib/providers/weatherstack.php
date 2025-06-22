<?php
namespace Awz\Weather\Providers;

use Bitrix\Main\Result;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Error;
use Bitrix\Main\Type\DateTime;

use Awz\Weather\HistoryTable;

/**
 *
 */
class WeatherStack extends Base{

    /**
     *
     */
    const API_URL = 'http://api.weatherstack.com/';

    const MAX_TIMEOUT = 20;

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

    public function getCurrent(float $lat, float $lon, int $days=14): Result
    {
        $lat = round($lat, 3);
        $lon = round($lon, 3);
        $result = new Result();

        $httpClient = new HttpClient();
        $httpClient->disableSslVerification();
        $httpClient->setStreamTimeout(static::MAX_TIMEOUT);
        $httpClient->setTimeout(static::MAX_TIMEOUT);

        $res = $httpClient->get(
            static::API_URL.'forecast?'.
            http_build_query([
                'access_key'=>$this->getKey(),
                'query'=>$lat.','.$lon,
                'forecast_days'=>14,
                'hourly'=>1,
                'interval'=>3
            ])
        );

        try{
            $result->setData(['result'=>Json::decode($res)]);
            $this->checkHistory($result, $this->getMd5Hash($lat,$lon));
        }catch (\Exception $e){
            $result->setData(['result'=>$res]);
            $result->addError(new Error($e->getMessage()));
        }

        return $result;
    }


    private function checkHistory(Result $result, string $hash){
        if(!$this->isAllowedHistory())
            return;

        $data = $result->getData()['result'];
        $r = HistoryTable::getList([
            'select'=>['ID','DATE_DAY'],
            'filter'=>[
                '>=DATE_DAY'=>DateTime::createFromTimestamp(strtotime(date('d.m.Y'))),
                '=PROVIDER'=>$this->getProviderName(),
                'LAT_LON_MD5'=>$hash
            ]
        ]);
        $items = [];
        while($item = $r->fetch()){
            $items[date("d.m.Y", strtotime($item['DATE_DAY']->toString()))] = $item;
        }
        $groupDays = [];
        foreach($data['forecast'] as $day){
            $keyDay = date("d.m.Y", $day['date_epoch']);
            if(!isset($groupDays[$keyDay])){
                $groupDays[$keyDay] = [];
            }
            $groupDays[$keyDay][] = $day;
        }
        foreach($groupDays as $keyDay=>$day){
            $fields = [
                'PARAMS'=>['data'=>$day, 'provider'=>$this->getProviderName()],
                'DATE_UP'=>DateTime::createFromTimestamp(time()),
                'DATE_DAY'=>DateTime::createFromTimestamp(strtotime($keyDay)),
                'PROVIDER'=>$this->getProviderName(),
                'LAT_LON_MD5'=>$hash
            ];
            try{
                if(isset($items[$keyDay])){
                    HistoryTable::update(['ID'=>$items[$keyDay]['ID']], $fields);
                }else{
                    HistoryTable::add($fields);
                }
            }catch (\Exception $e){

            }
        }
    }

    private function getKey():string
    {
        return $this->key;
    }

    public static function fetchDataModifier(array &$data = [])
    {

    }

}