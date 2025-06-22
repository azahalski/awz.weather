<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_js.php");

__IncludeLang($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/map.google.view/lang/'.LANGUAGE_ID.'/settings.php');

//if(!$USER->IsAdmin())
//	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

$obJSPopup = new CJSPopup('',
	array(
		'TITLE' => "Установите центр области погоды на карте",
		'SUFFIX' => 'google_map',
		'ARGS' => ''
	)
);

$arData = array();
if ($_REQUEST['MAP_DATA'])
{
	if (CheckSerializedData($_REQUEST['MAP_DATA']))
	{
		$arData = unserialize($_REQUEST['MAP_DATA'], ['allowed_classes' => false]);

		if (is_array($arData) && is_array($arData['PLACEMARKS']) && ($cnt = count($arData['PLACEMARKS'])))
		{
			for ($i = 0; $i < $cnt; $i++)
			{
				$arData['PLACEMARKS'][$i]['TEXT'] = str_replace('###RN###', "\r\n", $arData['PLACEMARKS'][$i]['TEXT']);
			}
		}
	}
}

$mapId = 'awz_weather_'.rand(0,100000);
?>
<script src="/bitrix/components/awz/weather.day/settings/settings_load.js?v=7"></script>
<script>
BX.loadCSS('/bitrix/components/bitrix/map.google.view/settings/settings.css');
var arPositionData = <?echo is_array($arData) && count($arData) > 0 ? CUtil::PhpToJsObject($arData) : '{}'?>;
window._global_BX_UTF = true;
BX.message({
	google_noname: '<?echo CUtil::JSEscape(GetMessage('MYMV_SET_NONAME'))?>',
	google_MAP_VIEW_ROADMAP: '<?echo CUtil::JSEscape(GetMessage('MYMS_PARAM_INIT_MAP_TYPE_MAP'))?>',
	google_MAP_VIEW_SATELLITE: '<?echo CUtil::JSEscape(GetMessage('MYMS_PARAM_INIT_MAP_TYPE_SATELLITE'))?>',
	google_MAP_VIEW_HYBRID: '<?echo CUtil::JSEscape(GetMessage('MYMS_PARAM_INIT_MAP_TYPE_HYBRID'))?>',
	google_MAP_VIEW_TERRAIN: '<?echo CUtil::JSEscape(GetMessage('MYMS_PARAM_INIT_MAP_TYPE_TERRAIN'))?>',
	google_current_view: '<?echo CUtil::JSEscape($_REQUEST['INIT_MAP_TYPE'])?>',
	google_nothing_found: '<?echo CUtil::JSEscape(GetMessage('MYMS_PARAM_INIT_MAP_NOTHING_FOUND'))?>'
});
</script>
<form name="bx_popup_form_google_map_<?=$mapId?>">
<?
$obJSPopup->ShowTitlebar();
?>
<?
$obJSPopup->StartContent();
?>
<div id="bx_google_map_control_<?=$mapId?>" style="position: absolute; 640px;border: solid 1px #B8C1DD;">
<?
$APPLICATION->IncludeComponent('bitrix:map.google.system', '', array(
	'INIT_MAP_TYPE' => 'NORMAL',
	'MAP_WIDTH' => 640,
	'MAP_HEIGHT' => 385,
	'INIT_MAP_LAT' => $arData['google_lat'],
	'INIT_MAP_LON' => $arData['google_lon'],
	'INIT_MAP_SCALE' => $arData['google_scale'],
	'MAP_ID' => 'system_view_edit_'.$mapId,
	'DEV_MODE' => 'Y',
	'API_KEY' => $arParams['API_KEY']
), false, array('HIDE_ICONS' => 'Y'));
?>
</div>
    <input type="submit" value="<?echo GetMessage('MYMV_SET_SUBMIT')?>" onclick="return jsGoogleCE.__saveChanges();" class="adm-btn-save"/>
<script>
if (null != window.jsGoogleCESearch)
	jsGoogleCESearch.clear();

if (window.google && window.google.maps && window.google.maps.Map)
{
	jsGoogleCE.init({
		mapId: '<?=$mapId?>'
	});
}
else
{
	(function BXWaitForMap(){
		if(null==window.GLOBAL_arMapObjects)
			return;

		if(window.GLOBAL_arMapObjects['system_view_edit'] && window.google && window.google.maps && window.google.maps.event)
		{
			jsGoogleCE.init({mapId: '<?=$mapId?>'});
		}
		else
		{
			setTimeout(BXWaitForMap,300);
		}
	})();
}
</script>
<?
$obJSPopup->StartButtons();
?>

<?
$obJSPopup->ShowStandardButtons(array('cancel'));
$obJSPopup->EndButtons();
?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_js.php");?>