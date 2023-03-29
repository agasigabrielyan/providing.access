<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use \Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

global $USER;

Loader::includeModule("intranet");

$arDeptExclude = CIntranetUtils::GetStructure();
$arDeptExclude = $arDeptExclude["DATA"];

foreach ($arDeptExclude as $arDept)
{
    $deptExclude[$arDept["ID"]] = str_repeat(". ", $arDept["DEPTH_LEVEL"] - 1) . $arDept["NAME"];
    $employeeExclude[$arDept["ID"]] = str_repeat(". ", $arDept["DEPTH_LEVEL"] - 1) . $arDept["NAME"];
}
$arComponentParameters = array(
	"GROUPS" => array(
		"DEPTS_SETTINGS" => array(
			"NAME" => Loc::getMessage("GPI_SUBORDINATE_DEPTS_GROUP_PARAMS"),
			"SORT" => 200
		),
		"EMPLOYEE_SETTINGS" => array(
			"NAME" => Loc::getMessage("GPI_SUBORDINATE_EMPLOYEE_PARAMS"),
			"SORT" => 210
		),
	),
	"PARAMETERS" => array(
		"USER_ID" => array(
			"PARENT" => "BASE",
			"NAME" => Loc::getMessage("GPI_SUBORDINATE_PARAM_TITLE_USER_ID"),
			"TYPE" => "STRING",
			"DEFAULT" => "={$USER->GetID()}",
		),
        "DEPT_BLOCK" => array(
            "PARENT" => "DEPTS_SETTINGS",
            "NAME" => Loc::getMessage("GPI_SUBORDINATE_PARAM_TITLE_DEPT_BLOCK"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y"
        ),
        "DEPT_MAX_CHILD_LEVEL" => array(
            "PARENT" => "DEPTS_SETTINGS",
            "NAME" => Loc::getMessage("GPI_SUBORDINATE_PARAM_TITLE_DEPT_MAX_CHILD_LEVEL"),
            "TYPE" => "STRING",
            "DEFAULT" => "1",
        ),
        "DEPT_MAX_SHOW_COUNT" => array(
            "PARENT" => "DEPTS_SETTINGS",
            "NAME" => Loc::getMessage("GPI_SUBORDINATE_PARAM_TITLE_DEPT_MAX_SHOW_COUNT"),
            "TYPE" => "STRING",
            "DEFAULT" => "4",
        ),
        "DEPT_EXCLUDE_DEPTS" => array(
            "PARENT" => "EMPLOYEE_SETTINGS",
            "NAME" => Loc::getMessage("GPI_SUBORDINATE_PARAM_TITLE_DEPT_EXCLUDE_DEPTS"),
            "TYPE" => "LIST",
            "MULTIPLE" => "Y",
            "DEFAULT" => "",
            "VALUES" => $deptExclude
        ),
        "EMPLOYEE_BLOCK" => array(
            "PARENT" => "EMPLOYEE_SETTINGS",
            "NAME" => Loc::getMessage("GPI_SUBORDINATE_PARAM_TITLE_EMPLOYEE_BLOCK"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y"
        ),
        "EMPLOYEE_MAX_CHILD_LEVEL" => array(
            "PARENT" => "EMPLOYEE_SETTINGS",
            "NAME" => Loc::getMessage("GPI_SUBORDINATE_PARAM_TITLE_EMPLOYEE_MAX_CHILD_LEVEL"),
            "TYPE" => "STRING",
            "DEFAULT" => "1",
        ),
        "EMPLOYEE_MAX_SHOW_COUNT" => array(
            "PARENT" => "EMPLOYEE_SETTINGS",
            "NAME" => Loc::getMessage("GPI_SUBORDINATE_PARAM_TITLE_EMPLOYEE_MAX_SHOW_COUNT"),
            "TYPE" => "STRING",
            "DEFAULT" => "4",
        ),
		"EMPLOYEE_TYPE" => array(
			"PARENT" => "EMPLOYEE_SETTINGS",
			"NAME" => Loc::getMessage("GPI_SUBORDINATE_PARAM_TITLE_EMPLOYEE_TYPE"),
			"TYPE" => "LIST",
			"MULTIPLE" => "N",
			"DEFAULT" => "M",
			"VALUES" => array(
			    "M" => Loc::getMessage("GPI_SUBORDINATE_PARAM_TITLE_EMPLOYEE_TYPE_M"),
			    "H" => Loc::getMessage("GPI_SUBORDINATE_PARAM_TITLE_EMPLOYEE_TYPE_H"),
			    "E" => Loc::getMessage("GPI_SUBORDINATE_PARAM_TITLE_EMPLOYEE_TYPE_E"),
			    "A" => Loc::getMessage("GPI_SUBORDINATE_PARAM_TITLE_EMPLOYEE_TYPE_A")
            )
		),
		"EMPLOYEE_EXCLUDE_DEPTS" => array(
			"PARENT" => "EMPLOYEE_SETTINGS",
			"NAME" => Loc::getMessage("GPI_SUBORDINATE_PARAM_TITLE_EMPLOYEE_EXCLUDE_DEPTS"),
			"TYPE" => "LIST",
			"MULTIPLE" => "Y",
			"DEFAULT" => "",
			"VALUES" => $employeeExclude
		),
        "GROUP_ALL_ACCESS" => array(
			"PARENT" => "EMPLOYEE_SETTINGS",
			"NAME" => Loc::getMessage("GPI_SUBORDINATE_PARAM_GROUP_ALL_ACCESS"),
			"TYPE" => "STRING",
			"DEFAULT" => "",
        ),
	),
);