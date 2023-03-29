<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use \Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

$arComponentDescription = array(
	"NAME" => Loc::getMessage("GPI_SUBORDINATE_NAME"),
	"DESCRIPTION" => Loc::getMessage("GPI_SUBORDINATE_DESCRIPTION"),
    "ICON" => "images/icon.gif",
    "CACHE_PATH" => "Y",
    "SORT" => 10,
    "PATH" => array(
        "ID" => "GPI",
        "NAME" => "GPI",
    ),
);
?>