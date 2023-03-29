<?php if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
/**
 * @var $arResult
 */
global $USER;
?>
<div class="access__cell">
    <form class="access__form" id="sub-extended-form" data-currentuid="<?= $USER->GetID() ?>">
        <div class="access__input">
            <label>Заявитель</label>
            <select name="sub-extended-form-user">
                <?php foreach($arResult['USER'] as $singleUser): ?>
                    <option <?= $singleUser['ID'] === $USER->GetID() ? "selected='selected'" : "" ?> value="<?= $singleUser['ID'] ?>">
                        <?= $singleUser['NAME'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="access__input">
            <div class="user-department">
                <!-- заполняется js -->
            </div>
        </div>
        <div class="access__input">
            <label>Согласующие</label>
                <div class="label__small">
                    Непосредственный руководитель
                </div>
                <select name="sub-extended-form-approver">
                    <!-- заполняется js -->
                </select>
                <div class="label__small">
                    Ответственный от подразделения
                </div>
                <select name="sub-extended-form-responsible">
                    <!-- заполняется js -->
                </select>
        </div>
        <div class="access__input">
            <label>Этапы согласования</label>
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
    </form>
</div>
<script>
    BX.message({
        users:<?= json_encode($arResult['USER']); ?>
    });
</script>

