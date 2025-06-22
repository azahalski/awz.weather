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
class Ninjas extends Base{

    /**
     *
     */
    const API_URL = 'https://api.api-ninjas.com/v1/';

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
        $httpClient->setHeader('X-Api-Key', $this->getKey());

        $res = $httpClient->get(
            static::API_URL.'weatherforecast?'.
            http_build_query(['lat'=>$lat, 'lon'=>$lon])
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
        foreach($data as $day){
            $keyDay = date("d.m.Y", $day['timestamp']);
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
        $temp = [];
        $feels_like = [];
        $weather = [];
        $cloud_pct = [];
        $humidity = [];
        foreach($data['data'] as $row){
            $row['timestamp_c'] = date("c", $row['timestamp']);
            if(date("h", $row['timestamp'])>=12 && date("h", $row['timestamp'])<=21){
                $temp[] = $row['temp'];
                $feels_like[] = $row['feels_like'];
                $weather[] = $row['weather'];
                $cloud_pct[] = $row['cloud_pct'];
                $humidity[] = $row['humidity'];
            }
        }
        $humidity_avg = 0;
        foreach($humidity as $v){
            $humidity_avg+=$v;
        }
        $humidity_avg = round($humidity_avg/count($humidity));
        $cloud_pct_awg = 0;
        foreach($cloud_pct as $v){
            $cloud_pct_awg+=$v;
        }
        $cloud_pct_awg = round($cloud_pct_awg/count($cloud_pct));
        $weather = array_unique($weather);
        $temp_avg = 0;
        foreach($temp as $v){
            $temp_avg+=$v;
        }
        $temp_avg = round($temp_avg/count($temp));
        $feels_like_avg = 0;
        foreach($feels_like as $v){
            $feels_like_avg+=$v;
        }
        $feels_like_avg = round($feels_like_avg/count($feels_like));
        $data['day_vidjet'] = [
            'humidity_avg'=>$humidity_avg,
            'cloud_pct_awg'=>$cloud_pct_awg,
            'temp_avg'=>$temp_avg,
            'feels_like_avg'=>$feels_like_avg,
            'weather'=>$weather
        ];
        if(!empty($temp)){
            $data['day_vidjet']['temp_min'] = asort($temp)[0];
            $data['day_vidjet']['temp_max'] = arsort($temp)[0];
        }
        if(!empty($feels_like)){
            $data['day_vidjet']['feels_like_min'] = asort($feels_like)[0];
            $data['day_vidjet']['feels_like_max'] = arsort($feels_like)[0];
        }
    }

}