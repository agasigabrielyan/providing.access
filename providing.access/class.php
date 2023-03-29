<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
use Bitrix\Highloadblock as HL;
use Bitrix\Main\Component\ParameterSigner;
use Bitrix\Main\Context;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\CurrentUser;
use Gazprom\EntitiesConstructor\Classes\IblockConstructor;
use Gazprom\EntitiesConstructor\Classes\IblockLoaderFacade;
use Gazprom\EntitiesConstructor\Classes\SmartConstructor;
use Gazprom\EntitiesConstructor\Classes\SmartWorkflow;
use Gazprom\EntitiesConstructor\Classes\Hpsm;
use \Bitrix\Main\Loader;
use \lib\HelperFunc;

class ProvidingAccess extends \CBitrixComponent implements Controllerable
{
    private $rsIblockDataClass;
    private $hlOrganizationDataClass;
    const SMARTS_ID = 161;

    public function configureActions()
    {

    }

    /**
     * Подготовка параметров компонента
     * @param $arParams
     * @return mixed
     */
    public function onPrepareComponentParams($arParams)
    {
        $object = new HelperFunc();
        $params = [
            'VISIBLE_STEPS' => [
                'Согласование непосредственным руководителем',
                'Согласование руководителем подразделения',
                'Согласование Департаментом 651',
            ],
            'SMART_CODE' => 'USERSDO',
            'NAUMEN' => 'Y', // Интеграция с Naumen / HPSM / other
            'STEPS_TITLE' => 'Этапы согласования',
            'COMMENTS_LOG' => 'Y'
        ];

        $arParams['CONSTRUCTOR'] = $object->prepareSmartParams("USERSDO", $params, "view");
        return $arParams;
    }

    protected function listKeysSignedParameters()
    {
        return [
            'IBLOCK_ID',
            'SMART_ID',
            'SMART_ITEM_ID',
            'HL_ORGANIZATION',
            'HL_USER_RIGHTS',
            'SERVICE_VIEW_URL',
            'SMART_NUMBER',
            'BIZPROC_TEMPLATE',
        ];
    }

    /**
     * Получить список существующих организаций по фильтру вводимому пользователем в input['data-organization']
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function organizationsAction() {
        $organizations = [];
        $request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getValues();
        $inputValue = $request['inputValue'];

        $higloadblockId = 37;
        $hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getById($higloadblockId)->fetch();
        $entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);
        $entity_data_class = $entity->getDataClass();

        $organizations = $entity_data_class::getList(array(
            'select' 	=> ["*"],
            'filter'    => ['UF_NAME' => "%" . $inputValue . "%"],
            'order'		=> ['UF_SHORT_NAME' => 'ASC']
        ))->fetchAll();

        return $organizations;
    }

    public function deleteAction($rowId)
    {
        // в переменной $rowId содержится идентификатор удаляемой строки, т.е. удаляемого пользователя

        (new IblockConstructor([], (int) $rowId))->delete();

        return $rowId;
    }

    public function saveAction($formDataFormated, $userId, $userChief, $userChief0)
    {
        // в переменной $formDataFormated содержатся данные формы, сохраняем данные и отправляем success во фронт
        $requestData = \Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getValues();

        $context = Context::getCurrent()->getRequest();
        $arParams = ParameterSigner::unsignParameters($this->getName(), $context->getPost('signedParameters'));

        $smartData = [
            'SMART_ID' => $arParams['SMART_ID'],
            'SMART_NUMBER' => $arParams['SMART_NUMBER'],
            'FIELDS' => [
                'TITLE' => CurrentUser::get()->getFullName(),
                'CREATED_BY' => $userId,
                sprintf('UF_CRM_%s_TARGET_USER', $arParams['SMART_NUMBER']) => $userId,
                sprintf('UF_CRM_%s_USER_CHIEF', $arParams['SMART_NUMBER']) => $userChief,
                sprintf('UF_CRM_%s_USER_CHIEF0', $arParams['SMART_NUMBER']) => $userChief0,
                sprintf('UF_CRM_%s_IS_DRAFT', $arParams['SMART_NUMBER']) => true,
            ],
        ];

        if ($arParams['SMART_ITEM_ID'] > 0) {
            $smartData = array_merge($smartData, ['ID' => $arParams['SMART_ITEM_ID']]);
        }

        $filter = [
            'IBLOCK_ID' => $arParams['IBLOCK_ID'],
        ];

        $rightsServiceJson = [];
        foreach( $requestData['formDataFormated']['RIGHTS'] as $rightId ) {
            $rightsServiceJson[] = [
                "JUSTIFICATION" => "",
                "URL" => "",
                "RIGHTS" => $rightId
            ];
        }

        $some = "";

        $formDataToBeSend[] = [
            "ID" => "",
            "IBLOCK_ID" => "131",
            "NAME" => $requestData['formDataFormated']['NAME'],
            "MODIFICATOR" => [
                "RIGHTS_SERVICE_JSON" => "JSON"
            ],
            "PROPERTY_VALUES" => [
                "EMAIL" => $requestData['formDataFormated']['EMAIL'],
                "PHONE" => $requestData['formDataFormated']['PHONE'],
                "ORGANIZATION" => explode(")",$requestData['formDataFormated']['ORGANIZATION'])[0],
                "POSITION" => $requestData['formDataFormated']['POSITION'],
                "LOGIN_LOC" => $requestData['formDataFormated']["LOGIN_LOC"],
                "DEPARTMENT" => $requestData['formDataFormated']['DEPARTMENT'],
                "RIGHTS_SERVICE_JSON" => $rightsServiceJson,
            ]
        ];

        $iblockLoader = new IblockLoaderFacade($smartData, $filter, $formDataToBeSend);
        $items = $iblockLoader->run();
        $smartItemId = $iblockLoader->getItemId();

        return [
            'items' => $items,
            'smart_item_id' => $smartItemId,
            'service_view_url' => $arParams['SERVICE_VIEW_URL']
        ];
    }

    public function sendAction($userId, $userChief, $userChief0)
    {
        // получаем данные, которые пришли к нам из формы
        $context = Context::getCurrent()->getRequest();
        $arParams = ParameterSigner::unsignParameters($this->getName(), $context->getPost('signedParameters'));

        // собираем данные для смарта
        $smartData = [
            'SMART_ID' => $arParams['SMART_ID'],
            'SMART_NUMBER' => $arParams['SMART_NUMBER'],
            'FIELDS' => [
                'TITLE' => CurrentUser::get()->getFullName(),
                'CREATED_BY' => $userId,
                sprintf('UF_CRM_%s_TARGET_USER', $arParams['SMART_NUMBER']) => $userId,
                sprintf('UF_CRM_%s_USER_CHIEF', $arParams['SMART_NUMBER']) => $userChief,
                sprintf('UF_CRM_%s_USER_CHIEF0', $arParams['SMART_NUMBER']) => $userChief0,
                sprintf('UF_CRM_%s_IS_DRAFT', $arParams['SMART_NUMBER']) => false,
            ],
        ];

        if ($arParams['SMART_ITEM_ID'] > 0) {
            $smartData = array_merge($smartData, ['ID' => $arParams['SMART_ITEM_ID']]);
        }

        // запускаем бизнес процессы
        $smartWorflow = new SmartWorkflow((int) $arParams['SMART_ID'], (int) $arParams['BIZPROC_TEMPLATE']);
        $workflowTemplateList = array_column(
            $smartWorflow->getInstanceWorkflows((int) $arParams['SMART_ITEM_ID']),
            'WORKFLOW_TEMPLATE_ID'
        );

        if (array_search($arParams['BIZPROC_TEMPLATE'], $workflowTemplateList) === false) {
            $smartWorflow->startProcess((int) $arParams['SMART_ITEM_ID']);
        }

        // возвращаем items для отображения, без возможности редактирования
        return [
            'items' => $this->arResult['ITEMS'],
            'params' => $arParams,
            'smart_item_id' => $arParams['SMART_ITEM_ID'],
            'service_view_url' => $arParams['SERVICE_VIEW_URL'],
        ];
    }

    public function executeComponent()
    {

        global $USER;

        if ((int) $this->arParams['SMART_ITEM_ID'] === 0) {
            //throw new Exception('Smart item ID not found');
        }

        global $arrExclDep;

        if (Loader::includeModule('gazprom.entitiesconstructor') && Loader::includeModule('crm')
            && Loader::includeModule('highloadblock')) {
            $hlOrgBlock = HL\HighloadblockTable::getById($this->arParams['HL_ORGANIZATION'])->fetch();
            $this->hlOrganizationDataClass = HL\HighloadblockTable::compileEntity($hlOrgBlock)->getDataClass();
            $rsOrgItems = $this->hlOrganizationDataClass::getList([
                'order' => [
                    'UF_NAME',
                ],
            ]);
            $this->arResult['ORGANIZATION'][] = '';
            while ($arOrgItem = $rsOrgItems->fetch()) {
                $this->arResult['ORGANIZATION'][$arOrgItem['UF_XML_ID']] = $arOrgItem;
                $this->arResult['ORGANIZATIONS_WITH_ID_AS_KEY'][$arOrgItem['ID']] = $arOrgItem;
            }

            $rsGroupItems = \Bitrix\Main\GroupTable::getList([
                'filter' => [
                    'ACTIVE' => 'Y',
                ],
            ]);
            $this->arResult['RIGHTS'][] = '';
            while ($arGroupItem = $rsGroupItems->fetch()) {
                $this->arResult['RIGHTS'][$arGroupItem['ID']] = $arGroupItem['NAME'];
            }

            $this->arResult['ITEMS'] = [];
            $this->arResult['IS_DRAFT'] = true;

            if ($this->arParams['SMART_ITEM_ID'] > 0) {
                $this->rsIblockDataClass = \Bitrix\Iblock\Iblock::wakeUp($this->arParams['IBLOCK_ID'])->getEntityDataClass();
                $rsItems = $this->rsIblockDataClass::getList([
                    'select' => [
                        'ID',
                        'NAME',
                        'DATE_CREATE',
                        'CREATED_BY',
                        'PROP_EMAIL' => 'EMAIL.VALUE',
                        'PROP_ORGANIZATION' => 'ORGANIZATION.VALUE',
                        'PROP_POSITION' => 'POSITION.VALUE',
                        'PROP_DEPARTMENT' => 'DEPARTMENT.VALUE',
                        'PROP_PHONE' => 'PHONE.VALUE',
                        'PROP_RIGHTS_SERVICE_JSON' => 'RIGHTS_SERVICE_JSON.VALUE',
                        'PROP_SMART_ITEM_ID' => 'SMART_ITEM_ID.VALUE',
                        'PROP_LOGIN_LOC' => 'LOGIN_LOC.VALUE',
                    ],
                    'filter' => [
                        'SMART_ITEM_ID.VALUE' => $this->arParams['SMART_ITEM_ID'],
                    ],
                ]);

                while ($arItem = $rsItems->fetch()) {
                    $arItem['DATE_CREATE'] = $arItem['DATE_CREATE']->format('d.m.Y H:i:s');
                    $arItem['PROP_RIGHTS_SERVICE_JSON'] = json_decode($arItem['PROP_RIGHTS_SERVICE_JSON'], true);
                    $this->arResult['ITEMS'][$arItem['ID']] = $arItem;
                }

                $smartWorflow = new SmartWorkflow((int) $this->arParams['SMART_ID'], (int) $this->arParams['BIZPROC_TEMPLATE']);
                $this->arResult['WORKFLOWS'] = $smartWorflow->getWorkflows((int) $this->arParams['SMART_ITEM_ID'], ['ID']);

                $this->arResult['WORKFLOW_TEMPLATES'] = [];
                foreach ($this->arResult['WORKFLOWS'] as $workflowId => $workflowData) {
                    $this->arResult['WORKFLOW_TEMPLATES'][$workflowId] = $workflowData['TEMPLATE_NAME'];

                    // Получение информации по текующей задаче БП
                    $workflowTasksFilter = [
                        "WORKFLOW_ID" => $workflowId,
                        "USER_ID" => $USER->getId(),
                        'USER_STATUS' => \CBPTaskUserStatus::Waiting,
                    ];

                    $dbTask = \CBPTaskService::GetList(
                        array(),
                        $workflowTasksFilter,
                        false,
                        false,
                        array("ID", "WORKFLOW_ID", "ACTIVITY", "ACTIVITY_NAME", "MODIFIED", "OVERDUE_DATE", "NAME", "DESCRIPTION", "PARAMETERS", 'IS_INLINE', 'STATUS', 'USER_STATUS', 'DOCUMENT_NAME', 'DELEGATION_TYPE')
                    );

                    if ($this->arResult["TASK"] = $dbTask->GetNext()) {
                        $this->arResult['TaskControls'] = \CBPDocument::getTaskControls($this->arResult["TASK"]);
                    }
                }

                $smartConstructor = new SmartConstructor();
                $smartConstructor->createProcessById($this->arParams['SMART_ID']);

                $this->arResult['SMART_ITEM'] = $smartConstructor->read([
                    'select' => [
                        'ID',
                        'TITLE',
                        'CREATED_BY',
                        'CREATED_TIME',
                        'UPDATED_TIME',
                        'STAGE_ID',
                        sprintf('UF_CRM_%s_RID_HPSM', $this->arParams['SMART_NUMBER']),
                        sprintf('UF_CRM_%s_RRESOLUTION', $this->arParams['SMART_NUMBER']),
                        sprintf('UF_CRM_%s_TARGET_USER', $this->arParams['SMART_NUMBER']),
                        sprintf('UF_CRM_%s_USER_CHIEF', $this->arParams['SMART_NUMBER']),
                        sprintf('UF_CRM_%s_USER_CHIEF0', $this->arParams['SMART_NUMBER']),
                        sprintf('UF_CRM_%s_IS_DRAFT', $this->arParams['SMART_NUMBER']),
                    ],
                    'filter' => [
                        'ID' => $this->arParams['SMART_ITEM_ID'],
                    ],
                ]);

                $this->arResult['IS_DRAFT'] = current($this->arResult['SMART_ITEM'])[sprintf('ufCrm%sIsDraft', $this->arParams['SMART_NUMBER'])];

                $this->arResult['CREATED_BY'] = $this->arResult['SMART_ITEM'][$this->arParams['SMART_ITEM_ID']]['createdBy'];
                $this->arResult['CURRENT_USER_ID'] = CurrentUser::get()->getId();
                $this->arResult['userAuthor'] = HelperFunc::SearchUser($this->arResult['CREATED_BY'], $arrExclDep);
                $userRequestId = $this->arResult['SMART_ITEM'][$this->arParams['SMART_ITEM_ID']][sprintf('ufCrm%sTargetUser', $this->arParams['SMART_NUMBER'])];
                $this->arResult['userRequest'] = ($userRequestId > 0) ? HelperFunc::SearchUser($userRequestId, $arrExclDep) : $this->arResult['userAuthor'];

                $this->arResult['STAGE_ID'] = $this->arResult['SMART_ITEM'][$this->arParams['SMART_ITEM_ID']]['stageId'];
                $this->arResult['STATUS'] = $this->arParams['CONSTRUCTOR']['ALL_STEPS'][$this->arResult['STAGE_ID']];
                $this->arResult['CREATED_TIME'] = $this->arResult['SMART_ITEM'][$this->arParams['SMART_ITEM_ID']]['createdTime'];
                $this->arResult['UPDATED_TIME'] = $this->arResult['SMART_ITEM'][$this->arParams['SMART_ITEM_ID']]['updatedTime'];

                $this->arResult['RID_HPSM'] = $this->arResult['SMART_ITEM'][$this->arParams['SMART_ITEM_ID']][sprintf('ufCrm%sRidHpsm', $this->arParams['SMART_NUMBER'])];
                $this->arResult['RRESOLUTION'] = $this->arResult['SMART_ITEM'][$this->arParams['SMART_ITEM_ID']][sprintf('ufCrm%sRresolution', $this->arParams['SMART_NUMBER'])];

                $this->arResult['USER_CHIEF'] = $this->arResult['SMART_ITEM'][$this->arParams['SMART_ITEM_ID']][sprintf('ufCrm%sUserChief', $this->arParams['SMART_NUMBER'])];
                $this->arResult['USER_CHIEF0'] = $this->arResult['SMART_ITEM'][$this->arParams['SMART_ITEM_ID']][sprintf('ufCrm%sUserChief0', $this->arParams['SMART_NUMBER'])];


                // Если выбрано логирование в комментарии смарт-процесса
                if (isset($this->arParams['CONSTRUCTOR']['COMMENTS_LOG']) && $this->arParams['CONSTRUCTOR']['COMMENTS_LOG'] == 'Y') {
                    // Получим комментарии смарт-процесса
                    $rs = \Bitrix\Crm\Timeline\Entity\TimelineTable::getList([
                        'filter' => [
                            'bind.ENTITY_ID' => $this->arParams['SMART_ITEM_ID'],
                            'bind.ENTITY_TYPE_ID' => $this->arParams['SMART_ID'],
                            'TYPE_ID' => '7' // 7 - это комментарии
                        ],
                        'select' => ['*', 'user.NAME', 'user.SECOND_NAME', 'user.LAST_NAME'],
                        'runtime' => [
                            'bind' => [
                                'data_type' => \Bitrix\Crm\Timeline\Entity\TimelineBindingTable::getEntity(),
                                'reference' => [
                                    '=this.ID' => 'ref.OWNER_ID',
                                ],
                                'join_type' => 'LEFT',
                            ],
                            'user' => [
                                'data_type' => \Bitrix\Main\UserTable::getEntity(),
                                'reference' => [
                                    '=this.AUTHOR_ID' => 'ref.ID',
                                ],
                                'join_type' => 'LEFT',
                            ],
                        ],
                        'order' => ['ID' => 'DESC', 'CREATED' => 'DESC']
                    ]);

                    $this->arResult['LOG'] = [];
                    $i = 0;
                    while ($ar = $rs->Fetch()) {
                        $ar_comment = explode(":", $ar['COMMENT']);
                        switch (count($ar_comment)) {
                            case 1:
                                if ($ar['CRM_TIMELINE_ENTITY_TIMELINE_user_LAST_NAME'] == '')
                                    $ar['AUTHOR'] = '';
                                else
                                    $ar['AUTHOR'] = $ar['CRM_TIMELINE_ENTITY_TIMELINE_user_LAST_NAME'] . ' ' . mb_substr($ar['CRM_TIMELINE_ENTITY_TIMELINE_user_NAME'], 0, 1) . '. ' . mb_substr($ar['CRM_TIMELINE_ENTITY_TIMELINE_user_SECOND_NAME'], 0, 1) . '. [' . $ar['AUTHOR_ID'] . ']';
                                $ar['TYPE'] = 'Комментарий';
                                $ar['COMMENT'] = $ar['COMMENT'];
                                break;
                            case 2:
                                if ($ar['CRM_TIMELINE_ENTITY_TIMELINE_user_LAST_NAME'] == '')
                                    $ar['AUTHOR'] = '';
                                else
                                    $ar['AUTHOR'] = $ar['CRM_TIMELINE_ENTITY_TIMELINE_user_LAST_NAME'] . ' ' . mb_substr($ar['CRM_TIMELINE_ENTITY_TIMELINE_user_NAME'], 0, 1) . '. ' . mb_substr($ar['CRM_TIMELINE_ENTITY_TIMELINE_user_SECOND_NAME'], 0, 1) . '. [' . $ar['AUTHOR_ID'] . ']';
                                $ar['TYPE'] = 'Изменение статуса';
                                $ar['COMMENT'] = $ar_comment[1];
                                break;

                            case 3:
                                $ar['AUTHOR'] = '';
                                $ar['TYPE'] = $ar_comment[0];
                                $ar['COMMENT'] = $ar_comment[1] . ': ' . $ar_comment[2];
                                break;
                        }
                        $ar['ID'] = ++$i;
                        $ar['DATE'] = $ar['CREATED']->format('d.m.Y h:i:s');
                        $this->arResult['WF_COMMENTS'][] = $ar;

                        // Формируем строки грида
                        $this->arResult['LOG'][] = [
                            'id' => $ar['ID'],
                            'data' => $ar,
                        ];
                    }
                }

                $request = Context::getCurrent()->getRequest();
                $post = $request->getPostList()->toArray();

                if (count($post) > 0) {
                    $test = ($this->arParams['CONSTRUCTOR']['DEV'] === 'Y') ? "test/" : "";
                    if (isset($post["addComment"]) || isset($post["U_APPLY"]) || isset($post["U_NONAPPLY"])) {
                        // Добавляем комментарий в БП и перезагружаем страницу чтобы его отобразить
                        if ($post["task_comment"] != '') {
                            $runtime = \CBPRuntime::GetRuntime();
                            $runtime->StartRuntime();
                            $taskService = $runtime->GetService("TaskService");
                            $dbres = $taskService->GetList(
                                ['ID' => 'DESC'],
                                ['WORKFLOW_ID' => $workflowId]
                            );
                            if ($task = $dbres->Fetch()) {
                                $trackingService = $runtime->GetService("TrackingService");
                                $trackingService->Write(
                                    $workflowId,
                                    \CBPTrackingType::Custom,
                                    $task['ACTIVITY_NAME'],
                                    1,
                                    0,
                                    $task['NAME'],
                                    $post["task_comment"],
                                    $USER->getId()
                                );
                            }

                            // Добавим комментарий в Смарт-процесс
                            if (isset($this->arParams['CONSTRUCTOR']['COMMENTS_LOG']) && $this->arParams['CONSTRUCTOR']['COMMENTS_LOG'] == 'Y') {
                                $resId = \Bitrix\Crm\Timeline\CommentEntry::create(
                                    array(
                                        'TEXT' => $post["task_comment"],
                                        'SETTINGS' => array(),
                                        'AUTHOR_ID' => $USER->getId(), //ID пользователя, от которого будет добавлен комментарий
                                        'BINDINGS' => array(array('ENTITY_TYPE_ID' => $this->arParams['SMART_ID'], 'ENTITY_ID' => $this->arParams['SMART_ITEM_ID']))
                                    )
                                );
                            }
                            if (isset($post["addComment"])) {
                                LocalRedirect($this->arParams['EXTRA_DATA']['UF_URL'] . "view.php?WFID=" . $this->arParams['SMART_ITEM_ID']); // Редирект, чтобы обновить отображение заявки
                            }
                        }

                        // Поменяем статус в смарт-процессе, если пользователь принял / отклонил
                        $stage_id = false;
                        if (isset($post["U_APPLY"])) {
                            $star = 5;
                            $stage_id = $this->arParams['CONSTRUCTOR']['U_APPLY'];
                        } elseif (isset($post["U_NONAPPLY"])) {
                            $star = 1;
                            $stage_id = $this->arParams['CONSTRUCTOR']['U_NONAPPLY'];
                        }

                        if ($stage_id) {
                            $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($this->arParams['SMART_ID']);
                            $item = $factory->getItem($this->arParams['SMART_ITEM_ID']);

                            if ($item) {
                                $item->setStageId($stage_id);
                            }

                            $operation = $factory->getUpdateOperation($item)
                                ->disableCheckAccess()
                                ->disableCheckFields();

                            $result = $operation->launch();
                            if (!$result->isSuccess()) {
                                $this->arParams["ERROR_MESSAGE"] = $result->getErrorMessages();
                            }
                        }

                        // Отправим в Naumen оценку и комментарий
                        Hpsm::sendRequest($this->arParams['SMART_ID'], $this->arParams['SMART_ITEM_ID'], $star, [$post["task_comment"]]);

                        LocalRedirect("/cpgp/services/" . $test . "myrequests.php");
                    } else {
                        $arErrorsTmp = [];
                        \CBPDocument::PostTaskForm(
                            $this->arResult["TASK"],
                            $USER->getId(),
                            $_REQUEST + $_FILES,
                            $arErrorsTmp,
                            $USER->GetFullName(false)
                        );

                        if (count($arErrorsTmp) > 0) {
                            $arError = [];
                            foreach ($arErrorsTmp as $e) {
                                $arError[] = [
                                    "id" => "bad_task",
                                    "text" => $e["message"],
                                ];
                            }
                            $e = new \CAdminException($arError);
                            $this->arParams["ERROR_MESSAGE"] = $e->GetString();
                        }

                        if (isset($post["approve"]) || isset($post["nonapprove"]))
                            $action = '/myaccepts.php';
                        elseif (isset($post["dotask"]))
                            $action = '/dotask.php';
                        else
                            $action = '/myrequests.php';

                        LocalRedirect("/cpgp/services/" . $test . $action);

                    }
                }
            }
        }

        $this->includeComponentTemplate();
    }
}
