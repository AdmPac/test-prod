<?php

namespace Nebo\CashRegister;

\Bitrix\Main\Loader::includeModule('iblock');

/**
 * class presets
 * Класс отвечает за работу с пресетами отправки, по-сути всё формируется через данный класс.
 * Невозможно добавить элемент в кассы в обход
 * этого класса, один из main classes
 */
class presets
{

    static $list = [];

    /**
     * @param array $filter - фильтр результатов.
     * @param array|null $select - селект полей.
     * @return object
     *
     * Основной метод запросов CIBlockElement::GetList
     * https://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/getlist.php
     *
     * Метод выполняет запрос на определённую таблицу по классу, и возвращает определённые поля ID/TITLE/CODE см. $select
     * Данные берутся по PROPERTY_<PROPERTY_CODE>, где PROPERTY_CODE - символьный код
     * наследует стандартные методы GetList (GetNextElement/GetNext/Fetch...)
     *
     * Использует данные по-умолчанию:
     * IBLOCK_CODE - config::LIST_CASHREGISTER_PRESETS_CODE
     * select - config::LIST_CASHREGISTER_PRESETS_LIST
     */
    public static function getList(array $filter = [], array $select = null): object
    {
        $filter['IBLOCK_CODE'] = config::LIST_CASHREGISTER_PRESETS_CODE;
        return \CIBlockElement::GetList([], $filter, false, false, $select ?? config::LIST_CASHREGISTER_PRESETS_LIST);
    }



    /**
     * @param $id - если строка идёт проверка с кодом пресета (CODE) если число - по ID
     *
     * Получить правила для определённого пресета, информация по заполняемым данным, полям.
     * @todo сделать красивую генерацию PROPERTY_*
     * @todo дополнительные поля в статьях сделать перебором
     */
    public static function getRules($id)
    {
        $return = [];
        $select = [
            // Информация о заполняемых полях
            'PROPERTY_FIELDS_FILL.ID',
            'PROPERTY_FIELDS_FILL.NAME',
            'PROPERTY_FIELDS_FILL.PROPERTY_FORMAT',
            'PROPERTY_FIELDS_FILL.PROPERTY_PARAMS',
            'PROPERTY_FIELDS_FILL.CODE',
            // Информация об обязательных полях
            'PROPERTY_REQUIRED_FIELDS',
            // Дополнительная информация из основного списка
            'ID',
            'NAME',
            'PROPERTY_ADDITIONAL_INFORMATION',
            'CODE'
        ];
        $filter = gettype($id) === "string" ? ['CODE' => $id] : ['ID' => $id];
        $query = self::getList($filter, $select);
        while ($i = $query->Fetch()) {
            print_r($i);
            if (!$return) {

                $add_data['query'] = json_decode($i['PROPERTY_ADDITIONAL_INFORMATION_VALUE'], true);
                $add_data['result'] = [];
                foreach ($add_data['query']['params'] as $i2) {
                    $add_data['result'][$i2['code']] = $i2;
                }

                $return = [
                    'ID' => $i['ID'],
                    'NAME' => $i['NAME'],
                    config::CASHREGISTER_ADD_FIELD => $add_data['result'],
                    'CODE' => $i['CODE'],
                ];
            }
            if ($i['PROPERTY_FIELDS_FILL_CODE']) $return[config::CASHREGISTER_EXPENDITURE_FIELD][$i['PROPERTY_FIELDS_FILL_CODE']] = [
                'required' => in_array($i['PROPERTY_REQUIRED_FIELDS'], $i['PROPERTY_FIELDS_FILL_ID']),
                'title' => $i['PROPERTY_FIELDS_FILL_NAME'],
                'code' => $i['PROPERTY_FIELDS_FILL_CODE'],
                'id' => $i['PROPERTY_FIELDS_FILL_ID'],
                'types' => json_decode($i['PROPERTY_FIELDS_FILL_PROPERTY_PARAMS_VALUE'], true)['types'],
                'format' => $i['PROPERTY_FIELDS_FILL_PROPERTY_FORMAT_ENUM_ID'],
            ];
        }
        self::$list[$id] = $return;
        return $return;
    }

    /**
     * @param $id - если строка идёт проверка с кодом пресета (CODE) если число - по ID
     * @param $data - ответы на задание
     * @return array
     *
     * Проверяет, на правильность заполнения, выполнения заполнения обязательных полей.
     */
    public static function checkRules($id, $data): array
    {
        $rules = self::getRules($id);
//        print_r($rules);
        // Необходим общий список проверяемых параметров
        $checkRules['params'] = [];
        foreach (config::CASHREGISTER_CHECK_LIST as $i){
            $checkRules['params'] = array_merge($checkRules['params'], $rules[$i]);
        }
        // Проверяем каждый из параметров
        $checkRules['response'] = [];
        foreach ($checkRules['params'] as $k => $v) {
            $checkRules['response'][$k] = config::checkType($data[$k], $v);
        }
        return $checkRules;
    }

    /**
     * @param $id
     * @param $data
     * @return bool
     *
     * Краткий ответ по верному заполнению данных.
     */
    public static function checkRulesShort($checkRules): bool
    {
        foreach ($checkRules as $i) {
            if ($i['status'] !== 'ok') return false;
        }
        return true;
    }



}
