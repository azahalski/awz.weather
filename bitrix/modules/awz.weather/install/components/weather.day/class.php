<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
    die();
}

use Awz\AutForm\CodesTable;
use Awz\AutForm\Events;
use Awz\AutForm\Helper;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Context;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Errorable;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\Result;
use Bitrix\Main\Security;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Security\Random;
use Bitrix\Main\Service\GeoIp\Manager;
use Bitrix\Main\SystemException;
use Bitrix\Main\UserConsent\Agreement;
use Bitrix\Main\UserGroupTable;
use Bitrix\Main\UserTable;
use Bitrix\Main\Application;
use Bitrix\Sale\Internals\OrderPropsValueTable;
use Awz\CookiesSett\App as CookieApp;

Loc::loadMessages(__FILE__);

class AwzWeatherDayComponent extends CBitrixComponent implements Controllerable, Errorable
{
    /** @var ErrorCollection */
    protected $errorCollection;

    /** @var  Bitrix\Main\HttpRequest */
    protected $request;

    /** @var Context $context */
    protected $context;

    public $arParams = array();
    public $arResult = array();

    public $userGroups = array();

    /**
     * Ajax actions
     *
     * @return array[][]
     */
    public function configureActions(): array
    {
        return [];
    }

    /**
     * Signed params
     *
     * @return string[]
     */
    protected function listKeysSignedParameters(): array
    {
        return [];
    }

    /**
     * Create default component params
     *
     * @param array $arParams параметры
     * @return array
     */
    public function onPrepareComponentParams($arParams): array
    {
        $this->errorCollection = new ErrorCollection();
        $this->arParams = &$arParams;

        return $arParams;
    }

    /**
     * Show public component
     *
     * @throws LoaderException
     */
    public function executeComponent(): void
    {
        if(!$this->isRequiredModule())
        {
            ShowError(Loc::getMessage('AWZ_WEATHER_MODULE_NOT_INSTALL'));
            return;
        }

        $this->arResult = ['DAY'=>[],'CURRENT_WEEK'=>[]];

        try{
            if (\Bitrix\Main\Loader::includeModule('awz.weather')) {
                $mapData = unserialize($this->arParams['~MAP_DATA'], ['allowed_classes'=>false]);
                $app = new \Awz\Weather\App();
                $app->getCurrent((float)$mapData['google_lat'], (float)$mapData['google_lon']);
                $this->arResult['DAY'] = $app->getMinDay((float)$mapData['google_lat'], (float)$mapData['google_lon'], strtotime($this->arParams['DATE']));
                for($i=0;$i<7;$i++){
                    $this->arResult['CURRENT_WEEK'][] = $app->getMinDay((float)$mapData['google_lat'], (float)$mapData['google_lon'], time()+86400*$i);
                }
            }
        }catch (\Exception $e){
            if(\Bitrix\Main\Engine\CurrentUser::get()?->isAdmin()){
                ShowError($e->getMessage());
            }
        }

        $this->includeComponentTemplate('');
    }

    /**
     * Добавление ошибки
     *
     * @param string|Error $message
     * @param int|string $code
     */
    public function addError($message, $code=0)
    {
        if($message instanceof Error){
            $this->errorCollection[] = $message;
        }elseif(is_string($message)){
            $this->errorCollection[] = new Error($message, $code);
        }
    }

    /**
     * Массив ошибок
     *
     * Getting array of errors.
     * @return Error[]
     */
    public function getErrors(): array
    {
        return $this->errorCollection->toArray();
    }

    /**
     * Getting once error with the necessary code.
     *
     * @param string|int $code Code of error.
     * @return Error|null
     */
    public function getErrorByCode($code): ?Error
    {
        return $this->errorCollection->getErrorByCode($code);
    }

    /**
     * проверка установки обязательных модулей
     *
     * @return bool
     * @throws LoaderException
     */
    public function isRequiredModule(): bool
    {
        if(!Loader::includeModule('awz.weather')){
            $this->addError(Loc::getMessage('AWZ_WEATHER_MODULE_NOT_INSTALL'), 'system');
            return false;
        }
        return true;
    }

}
