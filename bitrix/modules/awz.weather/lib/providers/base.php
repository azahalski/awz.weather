<?php
namespace Awz\Weather\Providers;

use Bitrix\Main\Result;

class Base {

    const MIN_AVG_TIME = 9;
    const MAX_AVG_TIME = 21;

    protected bool $allowedHistory = false;

    public function isAllowedHistory():bool
    {
        return $this->allowedHistory;
    }

    public function getCurrent(float $lat, float $lon): Result
    {
        $result = new Result();
        return $result;
    }

    public function allowHistory(bool $allow = false)
    {
        $this->allowedHistory = $allow;
    }

    public function getMd5Hash(float $lat, float $lon){
        $lat = round($lat, 2);
        $lon = round($lon, 2);
        return md5(serialize([$lat, $lon]));
    }

    protected function getProviderName(){
        return str_replace("Awz\\Weather\\Providers\\", "", get_class($this));
    }
}