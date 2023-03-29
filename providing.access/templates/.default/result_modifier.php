<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use \Bitrix\Main\Loader;
Loader::includeModule('highloadblock');

use \Bitrix\Highloadblock as HL;
use \Bitrix\Main\Enitity;

$hlbl = 68; // укажем id нашего highload блока
$hlblock = HL\HighloadBlockTable::getById($hlbl)->fetch();

$entity = HL\HighloadBlockTable::compileEntity($hlblock);
$entityDataClass = $entity->getDataClass();

$doGroups = $entityDataClass::getList([
 'select' => ['*','NAME' => 'grouptable.NAME','GROUP_ID' => 'grouptable.ID'],
 'order' => ['NAME' => 'ASC'],
 'runtime' => [
	'grouptable' => [
		'data_type' => \Bitrix\Main\GroupTable::getEntity(),
        'reference' => [
			'=this.UF_DOGROUP_ID' => 'ref.ID'
        ] 
    ]
 ] 
])->fetchAll();

$doGroupsWithIdAsKey = [];
foreach( $doGroups as $key => $doGroup ) {
    $doGroupsWithIdAsKey[$doGroup['ID']] = $doGroup;
}

$arResult['ALLOWED_DO_GROUPS'] = $doGroupsWithIdAsKey;
