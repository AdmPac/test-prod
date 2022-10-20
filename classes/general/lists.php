<?php
namespace Nebo\CashRegister;
/**
 * CList - Cash List
 * Класс отвечает за оперирование с листом актов, основной не глобальный класс
 */
class lists
{
    /**
     * @param $code - код блока CASHREGISTER_MAIN / 92
     * @return int - возврат кода
     *
     * Получить ID блока, по его коду
     */
    public static function getBlockID(string $code): int
    {
        return (int) \CIBlock::GetList([], ['CODE' => $code])->fetch()['ID'];
    }

    /**
     * Получить ID информационного блока по элементу.
     *
     * @param int $elementID - ID элемента
     * @return int - ID информационного блока к которому относится этот элемент
     */
    public static function getBlockElementID(int $elementID): int
    {
        return (int) \CIBlockElement::GetByID($elementID)->fetch()['IBLOCK_ID'];
    }

    /**
     * @param array $arFilter
     * @return object
     *
     * Получить доступы по элементу списка
     * $arFilter - [ENTITY_TYPE, IBLOCK_ID, ENTITY_ID]
     * ENTITY_TYPE = iblock/element/возможно что-то ещё...
     * IBLOCK_ID = ID инфоблока
     * ENTITY_ID = ID сущности
     *
     * response = [ITEM_ID, RIGHT_ID, GROUP_CODE, TASK_ID, LETTER]
     * LETTER:
     *   "D" - запрещён,
     *   "R" - чтение,
     *   "U" - изменение через документооборот,
     *   "W" - изменение,
     *   "X" - полный доступ (изменение + право изменять права
     */
    public static function getRights(array $arFilter): object
    {
        global $DB;
        $obQueryWhere = new \CSQLWhere;
        $obQueryWhere->SetFields(array(
            "IBLOCK_ID" => array(
                "TABLE_ALIAS" => "BR",
                "FIELD_NAME" => "BR.IBLOCK_ID",
                "MULTIPLE" => "N",
                "FIELD_TYPE" => "int",
                "JOIN" => false,
            ),
            "ENTITY_ID" => array(
                "TABLE_ALIAS" => "BR",
                "FIELD_NAME" => "BR.ENTITY_ID",
                "MULTIPLE" => "N",
                "FIELD_TYPE" => "int",
                "JOIN" => false,
            ),
            "ENTITY_TYPE" => array(
                "TABLE_ALIAS" => "BR",
                "FIELD_NAME" => "BR.ENTITY_TYPE",
                "MULTIPLE" => "N",
                "FIELD_TYPE" => "string",
                "JOIN" => false,
            ),
        ));
        $strWhere = $obQueryWhere->GetQuery($arFilter);

        return $DB->Query("
            SELECT
                BR.IBLOCK_ID
                ,BR.GROUP_CODE
                ,BR.ENTITY_TYPE
                ,BR.TASK_ID
				,BT.LETTER
            FROM
                b_iblock_right BR
            LEFT JOIN
                    b_task BT on BR.TASK_ID = BT.ID 
            ".($strWhere? "WHERE ".$strWhere: "")."
            ORDER BY
                BR.ID
        ");
    }
}
