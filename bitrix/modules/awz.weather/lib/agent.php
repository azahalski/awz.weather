<?
namespace Awz\Weather;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\DateTime;

class Agent {

    public static function cleanHistory(){

        $maxDays = (string) Option::get(App::MODULE_ID, 'MAX_DAYS', '365', '');
        if($maxDays){
            $filter = [
                '<=DATE_DAY'=>DateTime::createFromTimestamp(strtotime('-'.$maxDays.'days'))
            ];
            HistoryTable::deleteByFilter($filter);
        }

        return "\\Awz\\Weather\\Agent::cleanHistory();";
    }

    public static function autoUpdate($lat, $lon, $provider=""){

        try{
            $app = new App($provider);
            $app->getCurrent((float)$lat, (float)$lon);
        }catch (\Exception $e){

        }

        return "\\Awz\\Weather\\Agent::autoUpdate(\"".$lat."\",\"".$lon."\",\"".$provider."\");";
    }

}