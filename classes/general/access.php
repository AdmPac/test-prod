<?php

namespace Nebo\CashRegister;

/**
 * Класс взаимодействия с доступами
 */
class access
{
    /**
     * Константа связей ключей доступов и ID доступов.
     */
    private const LETTER_JOIN = [
        'D' => 61, // Нет доступа
        'R' => 62, // Чтение
        'E' => 63, // Добавление
        'S' => 64, // Просмотр в панели
        'T' => 65, // Добавление в панели
        'U' => 66, // Изменение с ограничениями
        'W' => 67, // Изменение
        'X' => 68, // Полный доступ
    ];

    /**
     * @param $element - ID элемента кассы
     * @return array
     *
     * Метод установки доступов на элемент, в зависимости от уже установленных глобальных
     * а так же зависит от статуса элемента.
     * @todo в идее обновление сразу массива элементов, через перебор, но смысл??
     */
    public static function setRules($element)
    {
        $data['RIGHTS'] = [];
        $rules[] = self::formatStatusRules($element);
        $rules[] = self::formatDefaultRules($element);
        $iz = 0;
        foreach (array_merge($rules[0], $rules[1]) as $k => $i) {
            $data['RIGHTS']['n'.$iz] = ["GROUP_CODE" => $k, "TASK_ID" => self::LETTER_JOIN[$i]];
            $iz++;
        }
        $el = new \CIBlockElement;
        return $el->Update($element, $data) ?? config::status(config::STATUS_ERROR, $el->LAST_ERROR);
    }

    /**
     * @param $iblock - CODE или ID информационного блока
     * @return array
     *
     * Получить доступы по умолчанию по всему списку. Исключая высокие доступы
     */
    public static function formatDefaultRules(int $elementID): array
    {
        $rules = [];
        $list = self::getList('iblock', lists::getBlockElementID($elementID));
        $exceptions = config::HIGH_ACCESS;
        foreach ($list as $i) {
            if (!in_array($i['GROUP_CODE'], $exceptions)) $rules[$i['GROUP_CODE']] = 'D';
        }
        return $rules;
    }

    /**
     * @param $element - элемент кассы (его ID)
     *
     * Получает группу правил проверки, и
     * @todo можно сделать куда гибче всё это, чтобы грузилось то - что используется в доступах, но пока так
     */
    public static function formatStatusRules($elementID): array
    {
        $data['list'] = \CIBlockElement::GetList([],
            ['IBLOCK_CODE' => config::LIST_CASHREGISTER_MAIN_CODE, 'ID' => $elementID], false, false,
            ['PROPERTY_STATUS', 'PROPERTY_ENTITY', 'PROPERTY_EXPENDITURE', 'ID'])->fetch();
        $data['rules'] = json_decode(self::getRulesExp((int) $data['list']['PROPERTY_EXPENDITURE_VALUE'])->fetch()['PROPERTY_ACCESS_VALUE'], true)[$data['list']['PROPERTY_STATUS_ENUM_ID'] ?? config::CASHREGISTER_DEF_ACCESS_STATUS];
        $response = [];
        foreach ($data['rules'] as $k => $i) {
            switch ($k){
                case 'object': {
                    $ro = self::_formatRules(
                        $k,
                        $i,
                        ['deal' => \CCrmDeal::GetList([],['ID' => $data['list']['PROPERTY_ENTITY_VALUE']], array_keys($i))->fetch()],
                    );
                    $response = array_merge($response, $ro);
                    break;
                }
            }
        }
        return $response;
    }

    /**
     * @param $type - тип полученого возврата (iblock - глобальные, element - только этот элемент)
     * @param $iblock - CODE или ID информационного блока
     * @param $elementID - ID элемента (не обязательный)
     * @return array - возвращает подготовленный массив с доступами
     *
     * Получить текущие доступы по определённому блоку/элементу.
     * Использует функцию нижних уровней lists::getRights
     */
    public static function getList(string $type, $iblock, int $elementID = null): array
    {
        $iblockID = gettype($iblock) === "string" ? lists::getBlockID($iblock) : $iblock;
        $query = [
            'iblock' => ['ENTITY_TYPE' => 'iblock', 'IBLOCK_ID' => $iblockID],
            'element' => ['ENTITY_TYPE' => 'element', 'IBLOCK_ID' => $iblockID, 'ENTITY_ID' => $elementID],
        ];
        if (!$query[$type]) return config::status(config::STATUS_ERROR, 'Тип не найден');
        $f = lists::getRights($query[$type]);
        $response = [];
        while ($i = $f->fetch()) {
            $response[] = $i;
        }
        return $response;
    }

    /**
     * @param $expID - ID/Код элемента, который необходимо выгрузить
     * @param string[] $select - массив полей
     * @return object - возвращает объект результат getlist (стандартный метод Битрикс)
     *
     * Метод получает правила по статье кассы (по её полю config::LIST_CASHREGISTER_EXPENDITURE_CODE)
     */
    public static function getRulesExp($expID, array $select = config::LIST_CASHREGISTER_EXPENDITURE_LIST): object
    {
        $filter = gettype($expID) === "string" ? ['CODE' => $expID] : ['ID' => $expID];
        $filter['IBLOCK_CODE'] = config::LIST_CASHREGISTER_EXPENDITURE_CODE;
        return \CIBlockElement::GetList([], $filter, false, false, $select);
    }

    /**
     * @param string $type - передаваемый тип (object - по объекту/*priority - приоритетные/*element - по элементу)
     * @param array $rules - правила, которые необходимо установить
     * @param array $data - некоторая полезная дата
     * @return array
     *
     * --- Типы ($types) ---
     * select_user - выбранный пользователь [U*]
     * parent_members - Родительский департамент Все сотрудники отдела [D*]
     * parent_members_sub - Родительский департамент Все сотрудники отдела с подотделами [DR*]
     * parent_manager - Руководитель родительского департамента [U*]
     *
     * Помощник форматирования правил, реализован для сокращения блока с основной проверкой.
     */
    private static function _formatRules(string $type, array $rules, array $data): array
    {
        $pref = config::PREFIX_GROUP_CODE; // Префиксы, групп-кодов
        $response = [];
        switch ($type) {
            case 'object': {
                foreach ($rules as $k => $i) {
                    foreach ($i as $i2) {
                        switch ($i2['type']) {
                            case 'select_user': {
                                $response[$pref['USER'].$data['deal'][$k]] = $i2['letter'];
                                break;
                            }
                            case 'sup_select_user': {
                                $response[$pref['SUP_USER'].$data['deal'][$k]] = $i2['letter'];
                                break;
                            }
                            case 'parent_members': {
                                $parentDepartment = array_column(config::getParentDepartment($data['deal'][$k], $i2['params']), 'ID');
                                foreach ($parentDepartment as $i3) {
                                    $response[$pref['DEPARTMENT'].$i3] = $i2['letter'];
                                }
                                break;
                            }
                            case 'parent_members_sub': {
                                $parentDepartment = array_column(config::getParentDepartment($data['deal'][$k], $i2['params']), 'ID');
                                foreach ($parentDepartment as $i4) {
                                    $response[$pref['DEPARTMENT_RIGHT'].$i4] = $i2['letter'];
                                }
                                break;
                            }
                            case 'parent_manager': {
                                $parentDepartment = array_column(config::getParentDepartment($data['deal'][$k], $i2['params']), 'UF_HEAD');
                                foreach ($parentDepartment as $i5) {
                                    $response[$pref['USER'].$i5] = $i2['letter'];
                                }
                                break;
                            }
                        }
                    }
                }
            }
       }
       return $response;
    }

}
