<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

use \Bitrix\Main\Loader;
use \Bitrix\Main\Config\Option;
use \lib\HelperFunc;
use \Bitrix\Main\UserTable;

/**
* показ блоков подчиненности
* Class CUserSubordinate
*/
class CUserSubordinate extends CBitrixComponent
{
	/**
	* обработка параметров компонента
	* @param $arParams - параметры компонента
	* @return array
	*/
	public function onPrepareComponentParams($arParams)
	{
		global $USER;
		$employeeType = ['H', 'E', 'M', 'A'];

		$result = array(
			'USER_ID' => (intval($arParams['USER_ID'])) ? intval($arParams['USER_ID']) : $USER->GetID(),
			'DEPT_BLOCK' => ($arParams['DEPT_BLOCK'] != 'Y') ? 'N' : 'Y',
			'DEPT_MAX_CHILD_LEVEL' => (isset($arParams['DEPT_MAX_CHILD_LEVEL'])) ? intval($arParams['DEPT_MAX_CHILD_LEVEL']) : 1,
			'DEPT_MAX_SHOW_COUNT' => (intval($arParams['DEPT_MAX_SHOW_COUNT'])) ? intval($arParams['DEPT_MAX_SHOW_COUNT']) : 4,
			'DEPT_EXCLUDE_DEPTS' => (is_array($arParams['DEPT_EXCLUDE_DEPTS'])) ? $arParams['DEPT_EXCLUDE_DEPTS'] : [],
			'EMPLOYEE_BLOCK' => ($arParams['EMPLOYEE_BLOCK'] != 'Y') ? 'N' : 'Y',
			'EMPLOYEE_MAX_CHILD_LEVEL' => (isset($arParams['EMPLOYEE_MAX_CHILD_LEVEL'])) ? intval($arParams['EMPLOYEE_MAX_CHILD_LEVEL']) : 1,
			'EMPLOYEE_MAX_SHOW_COUNT' => (intval($arParams['EMPLOYEE_MAX_SHOW_COUNT'])) ? intval($arParams['EMPLOYEE_MAX_SHOW_COUNT']) : 4,
			'EMPLOYEE_TYPE' => (isset($arParams['EMPLOYEE_TYPE']) && in_array($arParams['EMPLOYEE_TYPE'], $employeeType)) ? $arParams['EMPLOYEE_TYPE'] : 'M',
			'EMPLOYEE_EXCLUDE_DEPTS' => (is_array($arParams['EMPLOYEE_EXCLUDE_DEPTS'])) ? $arParams['EMPLOYEE_EXCLUDE_DEPTS'] : [],
			'CACHE_TYPE' => isset($arParams['CACHE_TYPE']) ? $arParams['CACHE_TYPE'] : 'A',
			'CACHE_TIME' => isset($arParams['CACHE_TIME']) ? $arParams['CACHE_TIME'] : 36000,
			'HIDE_ICONS' => 'Y',
			'GROUP_ALL_ACCESS' => is_array($arParams['GROUP_ALL_ACCESS']) ? $arParams['GROUP_ALL_ACCESS'] : null,
		);

		if (!isset($arParams['UNIQ_COMPONENT_ID']))
		{
			$result['UNIQ_COMPONENT_ID'] = 'gpi_subordinate_' . $this->randString();
		}

		return $result;
	}

	/**
	* получение подразделений пользователя, где он является руководителем
	* @param $structBlock - идентификатор инфоблока подразделений
	* @param $userId - идентификатор пользователя
	* @return array|bool - список подразделений
	*/
	protected function getUserHeadDepts($structIBlockId, $userId)
	{
		global $USER;
		$userHeadDepts = [];

		if (intval($structIBlockId) && intval($userId))
		{
			$userHeadDepts = $this->getDepartments(
				['IBLOCK_ID' => $structIBlockId, 'UF_HEAD' => $userId],
				['ID', 'DEPTH_LEVEL', 'LEFT_MARGIN', 'RIGHT_MARGIN']
			);
		}

		return $userHeadDepts;
	}

	/**
	* показ блока подразделений вверх
	* @param $userHeadDepts - подразделения пользователя, где он является руководителем
	* @param $maxChildLevel - максимальный уровень подразделений
	* @param $excludeDepts - идентификаторы подразделений для исключения
	* @return array|bool - список подразделений
	* @return array|bool - список подразделений
	*/
	protected function getUserDepts($user, $userHeadDepts, $excludeDepts = [])
	{
		$result = [];

		if ($user) {
			$arrFilter = ["ID" => $user];
		}

		// Получим плоский массив из оргструктуры
		$orgFilter = [
			'IBLOCK_ID' => 3,
			'ACTIVE' => 'Y',
			'GLOBAL_ACTIVE' => 'Y',
			'UF_TESTORGGROUP' => 'N',
		];
		$dbRes = \CIBlockSection::GetList(['NAME' => 'asc'], $orgFilter, true);
		while ($el = $dbRes->fetch()) {
			$flat[$el['ID']] = $el;
		}

		// Соберём дерево разделов
		$dbTree = \CIBlockSection::GetTreeList(['IBLOCK_ID' => 3,'ACTIVE' => 'Y','GLOBAL_ACTIVE' => 'Y'],[]);
		while($section = $dbTree->GetNext()) {
			$tree[] = $section;
		}
		$tree = array_reverse($tree);
		$res = \Bitrix\Main\UserTable::getList(
			[
				"select" => ["ID", "NAME", "SECOND_NAME", "LAST_NAME", "WORK_POSITION", "EMAIL", "UF_DEPARTMENT", "UF_VIP_SUBHEAD"],
				'count_total' => true,
				"filter" => $arrFilter? $arrFilter : [],
			]
		);

		try {
			while ($user = $res->fetch()) {

				if ($user["LAST_NAME"] <> '') {
					if (count(explode(" ", $user["NAME"])) == 1)
						$user["FIO"] = $user["LAST_NAME"] . " " . $user["NAME"] . " " . $user["SECOND_NAME"];
					else
						$user["FIO"] = $user["LAST_NAME"] . " " . $user["NAME"];
				}

				if ($user['FIO'] == null || strpos($user['FIO'], 'Диспетчер') !== false || mb_strpos($user['FIO'], 'filial') !== false) continue;

				$elem['ID'] = $user['ID'];
				$elem['NAME'] = $user['FIO'];
				$elem['POSITION'] = $user['WORK_POSITION'];
				$elem['CATEGORY'] = HelperFunc::getUserCategory($user['WORK_POSITION']);
				if (strpos($user['CATEGORY'], 'VIP') !== false) {
					$elem['UF_VIP_SUBHEAD'] = '1';
				}
				else {
					$elem['UF_VIP_SUBHEAD'] = $user['UF_VIP_SUBHEAD'];
				}
				$elem['EMAIL'] = $user['EMAIL'];

				if (empty($userHeadDepts)){
					$DepID = $user['UF_DEPARTMENT'][0];
				}
				else {
					if (is_string($userHeadDepts))
						$DepID =  $userHeadDepts;
					else
					{
						$arDepID = current($userHeadDepts);
						$DepID =  $arDepID['ID'];
					}
				}
				// Получаем всю ветку оргструктуры пользователя вверх от подразделения пользователя
				$elem["DIVISION"] = [];

				if (is_numeric($DepID) && $DepID > 0) {
					// Найдем положение подразделения в оргструктуре
					$index = array_search($DepID, array_column($tree, 'ID'));
					// Откидываем все подразделения, встреченные раньше, потому что все предки будут позже
					$subtree = array_slice($tree, $index, count($tree));
					// Получим информацию по первому подразделению
					$first = array_shift($subtree);
					$elem["DIVISION"][] = $first['NAME'];
					// Ищем остальные вверх по оргуструктуре
					$dephLevel = $first['DEPTH_LEVEL'];
					foreach ($subtree as $el) {
						if ($el['DEPTH_LEVEL'] < $dephLevel) {
							array_unshift($elem["DIVISION"], $el['NAME']);
							$dephLevel = $el['DEPTH_LEVEL'];
							if ($dephLevel == 2) {
								break;
							}
						}
					}
				}

				$result[] = $elem;
			}
		} catch (\Throwable $th) {
			throw $th;
		}
		array_multisort( array_column($result, "NAME"), SORT_ASC, $result );

		return $result;

	}

	/**
	* показ блока подразделений
	* @param $userHeadDepts - подразделения пользователя, где он является руководителем
	* @param $maxChildLevel - максимальный уровень подчиненных подразделений
	* @param $excludeDepts - идентификаторы подразделений для исключения
	* @return array|bool - список подразделений
	* @return array|bool - список подразделений
	*/
	protected function getDeptsBlock($userHeadDepts, $maxChildLevel, $structIBlockId, $excludeDepts = [], $access = false)
	{
		$arSubordinateDepts = [];

		if (!$access) {
			if (is_array($userHeadDepts) && !empty($userHeadDepts))	{
				foreach ($userHeadDepts as $arHeadDept)	{
					$subordFilter = ['IBLOCK_ID' => $structIBlockId, 'LEFT_MARGIN' => $arHeadDept['LEFT_MARGIN'], 'RIGHT_MARGIN' => $arHeadDept['RIGHT_MARGIN']];
					if (intval($maxChildLevel) > 0)	{
						$maxDepth = $arHeadDept['DEPTH_LEVEL'] + $maxChildLevel;
						$subordFilter['<=DEPTH_LEVEL'] = $maxDepth;
					}
					elseif (intval($maxChildLevel) == 0) {
						$subordFilter['DEPTH_LEVEL'] = $arHeadDept['DEPTH_LEVEL'];
					}

					if (is_array($excludeDepts) && !empty($excludeDepts)) {
						$subordFilter['!ID'] = $excludeDepts;
					}

					$arCurSubordinateDepts = $this->getDepartments(
						$subordFilter,
						['ID', 'NAME', 'DEPTH_LEVEL', 'UF_HEAD']
					);
					$arSubordinateDepts = $arSubordinateDepts + $arCurSubordinateDepts;
				}
			}
		}
		else {
			$subordFilter = ['IBLOCK_ID' => $structIBlockId];
			$arCurSubordinateDepts = $this->getDepartments(
				$subordFilter,
				['ID', 'NAME', 'DEPTH_LEVEL', 'UF_HEAD']
			);
			$arSubordinateDepts = $arCurSubordinateDepts;
		}

		return $arSubordinateDepts;
	}

	/**
	* показ блока подчиненных
	* @param $userHeadDepts - подразделения пользователя, где он является руководителем
	* @param $maxChildLevel - максимальный уровень подчиненных подразделений
	* @param $type - режим выбора подчиненных
	* @param $structIBlockId - идентификатор инфоблока подразделений
	* @param $excludeDepts - идентификаторы подразделений для исключения
	* @return array|bool - список подчиненных
	*/
	protected function getEmployeeBlock($userHeadDepts, $maxChildLevel, $type, $structIBlockId, $excludeDepts, $access = false)
	{
		$arSubordinateEmployee = [];

		$subDepatments = $this->getDeptsBlock(
			$userHeadDepts,
			$maxChildLevel,
			$structIBlockId,
			$excludeDepts,
			$access,
		);

		$deptHeads = $this->getDeptHeads($subDepatments);

		$subMainFilter = ['ACTIVE' => 'Y'];
		switch ($type)
		{
			case 'H':
				$subAddFilter = ['ID' => $deptHeads, '!ID' => $this->arParams['USER_ID']];
				break;
			case 'E':
				$excludeHeadsId = array_merge($deptHeads, [$this->arParams['USER_ID']]);
				$subAddFilter = ['UF_DEPARTMENT' => array_keys($subDepatments), '!ID' => $excludeHeadsId];
				break;
			case 'M':
			case 'A':
				$subAddFilter = [
					[
						'LOGIC' => 'OR',
						['UF_DEPARTMENT' => array_keys($subDepatments)],
						['ID' => $deptHeads],
					],
					'!ID' => $this->arParams['USER_ID']
				];
				break;
		}
		$subFilter = array_merge($subMainFilter, $subAddFilter);

		$arSubordinateEmployee  = $this->getUsers(
			$subFilter,
			['ID', 'NAME', 'LAST_NAME', 'UF_DEPARTMENT'],
			'ID'
		);

		if ($type == 'M')
		{
			foreach ($arSubordinateEmployee as $uid => $arEmployee)
			{
				if (!in_array($uid, $deptHeads))
				{
					if (is_array($arEmployee['DIVISION']) && !empty($arEmployee['DIVISION']))
					{
						foreach ($arEmployee['DIVISION'] as $deptId)
						{
							if (isset($deptHeads[$deptId]) && intval($deptHeads[$deptId]) != $uid && intval($deptHeads[$deptId]) != $this->arParams['USER_ID'])
								unset($arSubordinateEmployee[$uid]);
						}
					}
				}
			}
		}
		$keys = array_keys($arSubordinateEmployee);
		$first = array_shift($this->arResult['USER']);
		$this->arResult['USER'] = $this->getUserDepts(
			$keys,
			null,
			$this->arParams['DEPT_EXCLUDE_DEPTS']
		);
		array_unshift($this->arResult['USER'], $first);
		///dump_t();
		//AddMessage2Log($arSubordinateEmployee);
		return $arSubordinateEmployee;
	}

	/**
	* получение идентификаторов подразделений пользователя, где он является руководителем
	* @param $filter - фильтр подразделений
	* @param $select - поля подразделений
	* @return array|bool - массив подразделений
	*/
	protected function getDepartments($filter, $select)
	{
		$arDeptIds = [];

		$dbDept = \CIBlockSection::GetList(array('left_margin' => 'asc', 'NAME' => 'asc'), $filter, false, $select, false);
		while ($arDept = $dbDept->GetNext())
		{
			$arDeptIds[$arDept['ID']] = $arDept;
		}

		return $arDeptIds;
	}

	protected function getDeptHeads($subDepartments)
	{
		$usersByDept = [];

		if (is_array($subDepartments) && !empty($subDepartments))
		{
			foreach ($subDepartments as $subDeptId => $arSubDept)
			{
				if ((int)$arSubDept['UF_HEAD'])
					$usersByDept[$subDeptId] = $arSubDept['UF_HEAD'];
			}

		}

		return $usersByDept;
	}

	protected function getUsers($filter, $select, $by)
	{
		$usersByDept = [];

		$defSelect = ['ID', 'LAST_NAME', 'NAME', 'SECOND_NAME', 'WORK_POSITION'];

		if (!in_array($by, $select, true))
			$select[] = $by;

		$select = array_merge($defSelect, $select);

		$rsUsers = UserTable::getList(array(
			'filter' => $filter,
			'select' => $select,
			'order' => ['LAST_NAME' => 'asc']
		));
		while($arUser = $rsUsers->Fetch()) {
			$arUser['NAME'] = $arUser['LAST_NAME'] . ' ' . $arUser['NAME'] . ' ' . $arUser['SECOND_NAME'];
			$usersByDept[$arUser[$by]] = $arUser;
		}

		return $usersByDept;
	}

	/**
	 * выполнение компонента
	 * @return bool|mixed
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function executeComponent()
	{
		global $USER;

		$this->arResult['ERROR'] = '';

		$this->arResult['STRUCT_IBLOCK_ID'] = Option::get('intranet', 'iblock_structure');
		if (!$this->arResult['STRUCT_IBLOCK_ID'])
			$this->arResult['ERROR'] = Loc::getMessage('IKIGAY_SUBORDINATE_STRUCT_IBLOCK_ID_ERROR');

		if (!$this->arResult['STRUCT_IBLOCK_ID'] || !Loader::includeModule('iblock'))
			$this->arResult['ERROR'] = Loc::getMessage('GPI_SUBORDINATE_BLOCKS_SHOW_ERROR');

		if (!empty($this->arResult['ERROR']))
		{
			global $APPLICATION;
			$APPLICATION->ThrowException($this->arResult['ERROR']);
			return false;
		}
		$this->ClearResultCache();
		if($this->startResultCache())
		{
			$this->arResult['USER_HEAD_DEPARMENTS'] = $this->getUserHeadDepts(
				$this->arResult['STRUCT_IBLOCK_ID'],
				$this->arParams['USER_ID']
			);

			/*
			if ($this->arParams['DEPT_BLOCK'] == 'Y') {
			$this->arResult['SUBORDINATE_DEPARTMENTS'] = $this->getDeptsBlock(
			$this->arResult['USER_HEAD_DEPARMENTS'],
			$this->arParams['DEPT_MAX_CHILD_LEVEL'],
			$this->arResult['STRUCT_IBLOCK_ID'],
			$this->arParams['DEPT_EXCLUDE_DEPTS']
			);
			}
			*/
			if ($this->arParams['DEPT_BLOCK'] == 'Y') {
				$this->arResult['USER'] = $this->getUserDepts(
					$this->arParams['USER_ID'],
					$this->arResult['USER_HEAD_DEPARMENTS'],
					$this->arParams['DEPT_EXCLUDE_DEPTS']
				);
			}

			//AddMessage2Log($this->arResult['USER']);
			//Пользователи переданной группы/групп могут выбрать любого пользователя в качестве заявителя
			$access = false;
			if (is_array($this->arParams['GROUP_ALL_ACCESS']) && count($this->arParams['GROUP_ALL_ACCESS']) > 0) {
				$userGroups = $USER->GetUserGroupArray();
				$select = ['NAME','ID','STRING_ID','C_SORT'];
				$filter = ['STRING_ID'=> $this->arParams['GROUP_ALL_ACCESS']];
				$groups = \Bitrix\Main\GroupTable::getList([
					'select'  => $select,
					'filter'  => $filter,
				]);
				while ($elem = $groups->fetch()) {
					if (in_array($elem['ID'], $userGroups)) {
						$access = true;
						break;
					}
				}
			}

			if ($access) {
				$this->arResult['SUBORDINATE_EMPOYEE'] = $this->getEmployeeBlock(
					[1],
					$this->arParams['EMPLOYEE_MAX_CHILD_LEVEL'],
					$this->arParams['EMPLOYEE_TYPE'],
					$this->arResult['STRUCT_IBLOCK_ID'],
					[],
					$access
				);
			}
			elseif ($this->arResult['USER_HEAD_DEPARMENTS']) {
				if ($this->arParams['EMPLOYEE_BLOCK'] == 'Y') {
					$this->arResult['SUBORDINATE_EMPOYEE'] = $this->getEmployeeBlock(
						$this->arResult['USER_HEAD_DEPARMENTS'],
						$this->arParams['EMPLOYEE_MAX_CHILD_LEVEL'],
						$this->arParams['EMPLOYEE_TYPE'],
						$this->arResult['STRUCT_IBLOCK_ID'],
						$this->arParams['EMPLOYEE_EXCLUDE_DEPTS'],
					);
				}
			}

			//echo json_encode($this->arResult['USER'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

			$this->includeComponentTemplate();
		}
	}
}
?>
