<?php if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED != true) die();
/**
 * @var $arResult
 * @var $templateFolder
 * @var $APPLICATION
 * @var $component
 */
use Bitrix\Main\UI\Extension;
\Bitrix\Main\Page\Asset::getInstance();
?>
<!-- BEGIN: форма добавления прав сотруднику ДО -->
<?php if(!($arResult['STATUS'])): ?>
    <!-- BEGIN: если смарт процесс в статусе черновика отображаем форму -->
        <div class="access">
            <div class="access__cell access__cell_left">
                <form class="access__form" id="provide-access" data-signed="<?= $this->getComponent()->getSignedParameters() ?>">
                    <div class="access__input">
                        <label>Пользователь</label>
                        <input autocomplete="off" data-required  type="text" name="NAME" value="" placeholder="ФИО" />
                    </div>
                    <div class="access__input">
                        <label>E-mail</label>
                        <input autocomplete="off" data-required data-email  type="text" name="EMAIL" value="" placeholder="___@___.ru" />
                    </div>
                    <div class="access__input">
                        <label>Телефон</label>
                        <input autocomplete="off" data-required type="text" name="PHONE" value="" onkeydown="javascript: return ['Backspace','Delete','ArrowLeft','ArrowRight'].includes(event.code) ? true : !isNaN(Number(event.key)) && event.code!=='Space'" placeholder="00 00 000" />
                    </div>
                    <div class="access__input access__input_for-org">
                        <label>Организация</label>
                        <input
                                autocomplete="off"
                                data-required data-organization
                                type="text"
                                name="ORGANIZATION"
                                value=""
                                placeholder="Начните вводить название" />
                    </div>
                    <div class="access__input">
                        <label>Должность</label>
                        <input autocomplete="off" data-required type="text" name="POSITION" value="" placeholder="Должность" />
                    </div>
                    <div class="access__input">
                        <label>Логин LOC</label>
                        <input autocomplete="off" data-required  type="text" name="LOGIN_LOC" value="" placeholder="gfASP01" />
                    </div>
                    <div class="access__input">
                        <label>Подразделение</label>
                        <input autocomplete="off" data-required type="text" name="DEPARTMENT" value="" />
                    </div>
                    <div class="access__input">
                        <label>Права</label>
                        <div class="access__checkbox-wrapper">
                            <?php foreach ( $arResult['ALLOWED_DO_GROUPS'] as $key => $right ): ?>
                                <div title="<?= $right['NAME']; ?>" class="access__checkbox">
                                    <span></span>
                                    <?= $right['NAME']; ?>
                                    <input type="checkbox" name="RIGHTS[]" value="<?= $right['ID']; ?>" />
                                </div>
                            <?php endforeach; ?>
                            <div class="checkbox__closer"></div>
                        </div>
                    </div>
                    <div class="access__input">
                        <input type="submit" value="Добавить в список" />
                    </div>
                </form>
            </div>
            <div class="access__cell_right">
                <?php
                    $APPLICATION -> IncludeComponent(
                        "gpi:user.sub.extended",
                        "",
                        Array(
                            "DEPT_BLOCK" => "Y",
                            "DEPT_EXCLUDE_DEPTS" => array("1"),
                            "DEPT_MAX_CHILD_LEVEL" => "6",
                            "DEPT_MAX_SHOW_COUNT" => "4",
                            "EMPLOYEE_BLOCK" => "Y",
                            "EMPLOYEE_EXCLUDE_DEPTS" => array("1"),
                            "EMPLOYEE_MAX_CHILD_LEVEL" => "6",
                            "EMPLOYEE_MAX_SHOW_COUNT" => "4",
                            "EMPLOYEE_TYPE" => "M",
                            "USER_ID" => "",
                        )
                    );
                ?>
            </div>
        </div>
    <!-- end: если смарт процесс в статусе черновика отображаем форму -->
<?php endif; ?>
<?php if(count($arResult['ITEMS'])>0): ?>
    <?php if( count($arResult['LOG'])>0 ): ?>
        <table class="access__result table">
            <thead>
                <tr>
                    <th><h2>Заявитель</h2></th>
                    <th><h2>Автор</h2></th>
                    <th><h2>Этапы согласования</h2></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div>
                            <?php if( $arResult['userRequest'] ): ?>
                                <ul>
                                    <li>
                                        <?php $userFormat = '<a href="/company/personal/user/%s">%s %s %s</a>'; echo sprintf( $userFormat, $arResult['userRequest']['ID'], $arResult['userRequest']['LAST_NAME'], $arResult['userRequest']['NAME'], $arResult['userRequest']['SECOND_NAME'] ); ?>
                                    </li>
                                </ul>
                                <?php if( count($arResult['userRequest']['DEPARTMENTS'])>0 ): ?>
                                    <ul>
                                        <?php foreach( $arResult['userRequest']['DEPARTMENTS'] as $key => $dep ): ?>
                                            <li><?= $dep; ?> <?= $key < count($arResult['userRequest']['DEPARTMENTS']) ? "<br/>" : "" ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                                <?= ( strlen($arResult['userRequest']['UF_PHONE_SPR'][0])>0 ? "<img class='access__phone' src='/local/components/gazprom/providing.access/templates/.default/images/phone.svg' />" . str_replace(" ","-", $arResult['userRequest']['UF_PHONE_SPR'][0]) : "" ); ?>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <?php if( $arResult['userAuthor'] ): ?>
                            <ul>
                                <li>
                                    <?php $userFormat = '<a href="/company/personal/user/%s">%s %s %s</a>'; echo sprintf( $userFormat, $arResult['userAuthor']['ID'], $arResult['userAuthor']['LAST_NAME'], $arResult['userAuthor']['NAME'], $arResult['userAuthor']['SECOND_NAME'] ); ?>
                                </li>
                            </ul>
                            <?php if( count($arResult['userAuthor']['DEPARTMENTS'])>0 ): ?>
                                <ul>
                                    <?php foreach( $arResult['userAuthor']['DEPARTMENTS'] as $key => $dep ): ?>
                                        <li><?= $dep; ?> <?= $key < count($arResult['userAuthor']['DEPARTMENTS']) ? "<br/>" : "" ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                            <?= ( strlen($arResult['userAuthor']['UF_PHONE_SPR'][0])>0 ? "<img class='access__phone' src='/local/components/gazprom/providing.access/templates/.default/images/phone.svg' />" . str_replace(" ","-", $arResult['userRequest']['UF_PHONE_SPR'][0]) : "" ); ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="access__input">
                            <div class="access__stages">
                                <div class="access__stage">
                                    <span class="access__digit">1</span> Непосредственный руководитель
                                </div>
                                <div class="access__stage">
                                    <span class="access__digit">2</span> Руководитель подразделения
                                </div>
                                <div class="access__stage">
                                    <span class="access__digit">3</span> Департамент 651
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    <?php endif; ?>
    <div class="cpgp-svc__block">
        <?php if (isset($arParams['SMART_ITEM_ID'])):?>
            <div class="cpgp-svc__status">
                <span id="STATUS"><b><?= $arResult['STATUS'] ?></b></span> &nbsp;&nbsp;&nbsp;
                <span>Создано: <?= $arResult['CREATED_TIME'] ?></span> &nbsp;&nbsp;&nbsp;
                <span>Обновлено: <?= $arResult['UPDATED_TIME'] ?></span> &nbsp;&nbsp;&nbsp;
                <span>№ обращения: <?= $arParams['CONSTRUCTOR']['SMART_CODE'] . '_' . $arParams['SMART_ITEM_ID'] ?></span> &nbsp;&nbsp;&nbsp;
                <br/>
                <?php if($arResult['RID_HPSM'] != ""):?>
                    <span class="w-100">Код обращения в Naumen: <?=$arResult['RID_HPSM']?></span>
                    <?php if($arResult['RRESOLUTION'] != ""):?>
                        <span class="w-100">Сообщение из Naumen: <?=html_entity_decode($arResult['RRESOLUTION'])?></span>
                    <?php endif?>
                <?php else:?>
                    <?php if($arResult['RRESOLUTION'] != ""):?>
                        <span class="w-100">Сообщение исполнителя: <?=html_entity_decode($arResult['RRESOLUTION'])?></span>
                    <?php endif?>
                <?php endif?>
            </div>
        <?php endif?>
    </div>
    <div class="access">
        <!-- BEGIN: таблица созданных запросов -->
        <div class="access__cell">
            <form class="access__form" id="provide-access-table">
                <?php if( count($arResult['LOG'])<= 0 ): ?>
                    <div class="access__heading">Список пользователей</div>
                <?php endif; ?>
                <table class="<?= $arResult['STATUS'] ? 'in_action' : '' ?>">
                    <thead>
                        <tr>
                            <th>ФИО</th>
                            <th>Организация</th>
                            <th>Должность</th>
                            <th>Подразделение</th>
                            <th>Права доступа</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach( $arResult['ITEMS'] as $key => $arItem ): ?>
                            <tr data-userdoid="<?= $arItem['ID'] ?>">
                                <td><?= $arItem['NAME'] ?></td>
                                <td><?= $arResult['ORGANIZATIONS_WITH_ID_AS_KEY'][$arItem['PROP_ORGANIZATION']]['UF_NAME'] ?></td>
                                <td><?= $arItem['PROP_POSITION'] ?></td>
                                <td><?= $arItem['PROP_DEPARTMENT'] ?></td>
                                <td>
                                    <?php foreach( $arItem['PROP_RIGHTS_SERVICE_JSON'] as $key => $right ): ?>
                                        <?= $arResult['ALLOWED_DO_GROUPS'][$right['RIGHTS']]['NAME']; ?><?= $key<(count($arItem['PROP_RIGHTS_SERVICE_JSON'])-1) ? ",<br/>" : ""; ?>
                                    <?php endforeach; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if(!($arResult['STATUS'])): ?>
                    <div class="access__input">
                        <input type="submit" value="Отправить на согласование" />
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
<?php endif; ?>

<!-- BEGIN: log -->
<?php if( count($arResult['LOG'])>0 ): ?>
    <?php
        $APPLICATION->IncludeComponent(
            "bitrix:main.ui.grid",
            "",
            array(
                "GRID_ID"=>"LOG",
                "HEADERS"=>	array(
                    array("id"=>"ID", "name"=>"ID", "default"=>false),
                    array("id"=>"DATE", "name"=>"Дата/время", "default"=>true),
                    array("id"=>"TYPE", "name"=>"Действие", "default"=>true),
                    array("id"=>"AUTHOR", "name"=>"Автор", "default"=>true),
                    array("id"=>"COMMENT", "name"=>"Примечание", "default"=>true),
                ),
                "SORT"=>array("sort"=>array("ID" => "desc")),
                "ROWS"=>$arResult["LOG"],
                "SHOW_CHECK_ALL_CHECKBOXES" => false,
                "SHOW_ROW_CHECKBOXES" => false,
                "SHOW_SELECTED_COUNTER" => false,
                "TOTAL_ROWS_COUNT" => count($arResult["LOG"]),
                'AJAX_ID' => '',
                'AJAX_MODE' => "Y",
                "AJAX_OPTION_JUMP" => "N",
                "AJAX_OPTION_STYLE" => "N",
                "AJAX_OPTION_HISTORY" => "N",
                "FILTER"=> [],
            ),
            $component
        );
    ?>
<?php endif; ?>
<!-- end: log -->
<script>
    BX.message({
        applicant:      <?= json_encode($arResult['CREATED_BY']) ?>,
        approver:       <?= json_encode($arResult['USER_CHIEF']) ?>,
        responsible:    <?= json_encode($arResult['USER_CHIEF0']) ?>,
    });
</script>