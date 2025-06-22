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
class OpenMeteo extends Base{

    /**
     *
     */
    const API_URL = 'https://api.open-meteo.com/v1/';

    const MAX_TIMEOUT = 20;

    /**
     * @var string
     */
    private $key;

    /**
     * @param string $apiKey
     */
    public function __construct(string $apiKey=""){
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
                'latitude'=>$lat,
                'longitude'=>$lon,
                'daily'=>'weather_code,temperature_2m_max,temperature_2m_min,apparent_temperature_max,apparent_temperature_min,sunrise,sunset,daylight_duration,sunshine_duration,uv_index_max,uv_index_clear_sky_max,rain_sum,showers_sum,snowfall_sum,precipitation_sum,precipitation_hours,precipitation_probability_max,et0_fao_evapotranspiration,shortwave_radiation_sum,wind_direction_10m_dominant,wind_gusts_10m_max,wind_speed_10m_max,temperature_2m_mean,apparent_temperature_mean,cloud_cover_mean,dew_point_2m_mean,relative_humidity_2m_mean,relative_humidity_2m_max,relative_humidity_2m_min,pressure_msl_mean,pressure_msl_max,pressure_msl_min,surface_pressure_mean,surface_pressure_max,surface_pressure_min,visibility_mean,visibility_min,visibility_max',
                'hourly'=>'temperature_2m,relative_humidity_2m,dew_point_2m,apparent_temperature,precipitation_probability,precipitation,rain,showers,snowfall,snow_depth,weather_code,pressure_msl,surface_pressure,cloud_cover,cloud_cover_low,cloud_cover_mid,cloud_cover_high,visibility,evapotranspiration,et0_fao_evapotranspiration,vapour_pressure_deficit',
                //'models'=>'icon_seamless',
                'timezone'=>'auto',
                'timeformat'=>'unixtime',
                'wind_speed_unit'=>'ms',
                'forecast_days'=>'14'
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
                '=LAT_LON_MD5'=>$hash
            ]
        ]);
        $items = [];
        while($item = $r->fetch()){
            $items[date("d.m.Y", strtotime($item['DATE_DAY']->toString()))] = $item;
        }
        $groupDays = [];
        foreach($data['daily']['time'] as $k=>$time){
            $keyDay = date("d.m.Y", $time);
            if(!isset($groupDays[$keyDay])){
                $groupDays[$keyDay] = ['dayly'=>[],'hourly'=>[]];
            }
            $day = [];
            foreach($data['daily'] as $key=>$v){
                $day[$key] = $v[$k];
            }
            $groupDays[$keyDay]['dayly'] = $day;
        }
        foreach($data['hourly']['time'] as $k=>$time){
            $keyDay = date("d.m.Y", $time);
            if(!isset($groupDays[$keyDay])){
                $groupDays[$keyDay] = ['dayly'=>[],'hourly'=>[]];
            }
            $dayh = [];
            foreach($data['hourly'] as $key=>$v){
                $dayh[$key] = $v[$k];
            }
            $groupDays[$keyDay]['hourly'][] = $dayh;
        }

        //echo'<pre>';print_r(array_keys($groupDays));echo'</pre>';
        //echo'<pre>';print_r($items);echo'</pre>';
        //die();

        foreach($groupDays as $keyDay=>$day){
            $fields = [
                'PARAMS'=>[
                    'data'=>$day,
                    'timezone'=>$data['timezone'],
                    'utc_offset_seconds'=>$data['utc_offset_seconds'],
                    'daily_units'=>$data['daily_units'],
                    'hourly_units'=>$data['hourly_units'],
                    'provider'=>$this->getProviderName()
                ],
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

        foreach($data['data']['hourly'] as $row){
            $row['timestamp_c'] = date("c", $row['time']);
            if(date("h", $row['time'])>=self::MIN_AVG_TIME && date("h", $row['time'])<=self::MAX_AVG_TIME){
                $temp[] = $row['temperature_2m'];
                $feels_like[] = $row['apparent_temperature'];
                if($row['rain']) $weather[] = 'rain';
                if($row['showers']) $weather[] = 'showers';
                if($row['snowfall']) $weather[] = 'snowfall';
                $cloud_pct[] = $row['cloud_cover'];
                $humidity[] = $row['relative_humidity_2m'];
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
            asort($temp);
            $data['day_vidjet']['temp_min'] = $temp[array_keys($temp)[0]];
            arsort($temp);
            $data['day_vidjet']['temp_max'] = $temp[array_keys($temp)[0]];
        }
        if(!empty($feels_like)){
            asort($feels_like);
            $data['day_vidjet']['feels_like_min'] = $feels_like[array_keys($feels_like)[0]];
            arsort($feels_like);
            $data['day_vidjet']['feels_like_max'] = $feels_like[array_keys($feels_like)[0]];
        }
        $data['day_vidjet']['weather_code'] = $data['data']['dayly']['weather_code'];

        //echo'<pre>';print_r($data);echo'</pre>';
        //echo'<pre>';print_r($temp);echo'</pre>';
        //die();
    }

}