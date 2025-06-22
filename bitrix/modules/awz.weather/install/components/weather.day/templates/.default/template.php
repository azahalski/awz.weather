<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
    die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Application;

Loc::loadLanguageFile(__DIR__.'/template.php');

/**
 * @var CBitrixComponentTemplate $this
 * @var string $componentPath
 * @var string $templateName
 * @var string $templateFolder
 * @var array $arParams
 * @var array $arResult
 */
$this->setFrameMode(true);
$frame = $this->createFrame()->begin();
$dayVidjet = $arResult['DAY'];
?>
<?if(!empty($dayVidjet)){?>
<div class="awz-weather__card-wrapper awz-weather__card">
    <div class="awz-weather__card-item-title">
        <div class="awz-weather__card-item-text-s">
            <?=$dayVidjet['lang']['min_day']?>
        </div>
        <div class="awz-weather__card-item-text-s">
            <?=date("d", $dayVidjet['timestamp'])?> <?=$dayVidjet['lang']['min_month']?>
        </div>
    </div>
    <div class="awz-weather__card-item-icon">
        <? if (isset($dayVidjet['weather_code']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $templateFolder . '/images/' . $dayVidjet['weather_code'] . '.png')) { ?>
            <img src="<?= $templateFolder ?>/images/<?= $dayVidjet['weather_code'] ?>.png" alt="" />
        <? } elseif(!isset($dayVidjet['weather_code'])){?>
            <img src="<?= $templateFolder ?>/images/-.png" alt="" />
        <?}elseif (isset($dayVidjet['icon'])) { ?>
            <img src="<?= $dayVidjet['icon'] ?>" alt="" />
        <? } ?>
    </div>
    <div class="awz-weather__card-item-temp">
        <?
        $temp = '';
        if (isset($dayVidjet['temp_avg'])) {
            $temp = $dayVidjet['temp_avg'];
        }
        if (isset($dayVidjet['temp_max'])) {
            $check = $dayVidjet['temp_max'] - $dayVidjet['temp_min'];
            if ($check > 1) {
                $temp = $dayVidjet['temp_min'] . ' - ' . $dayVidjet['temp_max'];
            }
        }
        ?>
        <?= $temp ? $temp.'°': '-' ?>
    </div>
</div>
<?}?>