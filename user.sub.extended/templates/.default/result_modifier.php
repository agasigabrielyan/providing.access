<?php if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/**
 * @var $arResult
 */
global $USER;
// определим данные текущего пользователя
foreach( $arResult['USER'] as $singleUser ) {
    if( $singleUser['ID'] == $USER->GetID() ) {
        $arResult['CURRENT_USER_DATA'] = $singleUser;
    }
}