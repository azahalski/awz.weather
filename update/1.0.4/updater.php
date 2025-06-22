<?
$moduleId = "awz.weather";
if(IsModuleInstalled($moduleId)) {
	CopyDirFiles(
	$_SERVER['DOCUMENT_ROOT']."/bitrix/modules/".$moduleId."/install/components/weather.day/", 
	$_SERVER['DOCUMENT_ROOT']."/bitrix/components/awz/weather.day", true, true
	);
}