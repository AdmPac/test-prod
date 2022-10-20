<?php

namespace Nebo\CashRegister;

use CModule;

if(!CModule::IncludeModule("bizproc")) die('Module `bizproc` not included');


/**
 * Класс взаимодействия с актами
 */
class act
{

    private static $workflowID = 911 ; // ID бп по созданию доков/сделок

    /**
     * @param int $object - объект (сделка по которой создаём)
     * @param $typeID - тип акта
     * @param $data - данные для добавления [type => resp...]
     * @return array
     *
     * Добавление акта со всеми необходимыми проверками.
     * Так же после добавления автоматом прокидываются доступы на новосозданные элементы
     */
    public static function add(int $object, $typeID, $data): array
    {
        $checkRules = presets::checkRules($typeID, $data);
        if (!presets::checkRulesShort($checkRules['response'])) return config::status(config::STATUS_ERROR, "Ошибка в заполнении \n" . json_encode($checkRules, JSON_UNESCAPED_UNICODE));
        $add = self::formatData($data, presets::$list[$typeID]);
        $response = [];
        $uid = config::generationUid();
        foreach ($add as $i) {
            $el = new \CIBlockElement;
            $i[config::CASHREGISTER_UID_FIELD] = $uid;
            $i[config::CASHREGISTER_ENTITY_FIELD] = $object;
            $i[config::CASHREGISTER_STATUS_FIELD] = $data['status'] ?? config::CASHREGISTER_DEF_ACCESS_STATUS;
            $i['IBLOCK_ID'] = lists::getBlockID(config::LIST_CASHREGISTER_MAIN_CODE);
            $i['NAME'] = $uid;
            $i["PROPERTY_VALUES"] = $i;
            if ($data['created_by']) $i['CREATED_BY'] = $data['created_by'];
            $element = $el->Add($i);
            if ($element) access::setRules($element);
            $response[] = $element
                ?? config::status(config::STATUS_ERROR, $el->LAST_ERROR);
        }
        return $response;
    }

    public static function addTest(int $object, $typeID, $data): array
    {
        $checkRules = presets::checkRules($typeID, $data);
        if (!presets::checkRulesShort($checkRules['response'])) return config::status(config::STATUS_ERROR, "Ошибка в заполнении \n" . json_encode($checkRules, JSON_UNESCAPED_UNICODE));
        $add = self::formatData($data, presets::$list[$typeID]);
        $response = [];
        $uid = config::generationUid();
        return [];
        /*foreach ($add as $i) {
            $el = new \CIBlockElement;
            $i[config::CASHREGISTER_UID_FIELD] = $uid;
            $i[config::CASHREGISTER_ENTITY_FIELD] = $object;
            $i[config::CASHREGISTER_STATUS_FIELD] = $data['status'] ?? config::CASHREGISTER_DEF_ACCESS_STATUS;
            $i['IBLOCK_ID'] = lists::getBlockID(config::LIST_CASHREGISTER_MAIN_CODE);
            $i['NAME'] = $uid;
            $i["PROPERTY_VALUES"] = $i;
            $element = $el->Add($i);
            if ($element) {
              access::setRules($element);
              self::startWorkflow($element,'afterAdd');
            }
            $response[] = $element
                ?? config::status(config::STATUS_ERROR, $el->LAST_ERROR);
        }
        return $response;*/
    }

    /**
     * @param int $object - объект (сделка по которой создаём)
     * @param $typeID - тип акта
     * @param $data - данные для добавления [
     *     reconciliation_date - дата принятия (необязательно)
     *     agreed_employee - кто принял (необязательно, ID пользователя)
     *     created_by - кем создана (необязательно, ID пользователя)
     *     status - создано(1631) / согласованно(1632), если не указано, то устанавливаем создано
     *      ]
     * @return array
     *
     * Добавление акта с множеством параметров для админов
     */
    public static function addAdmin(int $object, $typeID, $data): array
    {
        $checkRules = presets::checkRules($typeID, $data);
        if (!presets::checkRulesShort($checkRules['response'])) return config::status(config::STATUS_ERROR, "Ошибка в заполнении \n" . json_encode($checkRules, JSON_UNESCAPED_UNICODE));
        $add = self::formatData($data, presets::$list[$typeID]);
        $response = [];
        $uid = config::generationUid();
        foreach ($add as $i) {
            $el = new \CIBlockElement;
            $i[config::CASHREGISTER_UID_FIELD] = $uid;
            $i[config::CASHREGISTER_ENTITY_FIELD] = $object;
            $i[config::CASHREGISTER_STATUS_FIELD] = $data['status'] ?? config::CASHREGISTER_DEF_ACCESS_STATUS;
            if($data['reconciliation_date'])$i[config::CASHREGISTER_DATE_RECONCILIATION_FIELD] = $data['reconciliation_date'];
            if($data['agreed_employee'])$i[config::CASHREGISTER_AGREED_EMPLOYEE_FIELD] = $data['agreed_employee'];
            if($data['date_create'])$i['DATE_CREATE'] = $data['date_create'];
            $i['IBLOCK_ID'] = lists::getBlockID(config::LIST_CASHREGISTER_MAIN_CODE);
            $i['NAME'] = $uid;
            if($data['created_by'])$i['CREATED_BY'] = $data['created_by'];
            $i["PROPERTY_VALUES"] = $i;
            $element = $el->Add($i);
            if ($element) access::setRules($element);
            $response[] = $element
                ?? config::status(config::STATUS_ERROR, $el->LAST_ERROR);
        }
        return $response;
    }

    /**
     * @param int $actID
     * @param array $data
     * @return array|bool
     *
     * Метод редактирования элемента
     * @todo Необходима реализация метода edit
     * Функция должна изменять только те параметры, которые есть в изменяемом акте внесены, если акт был добавлен с определёнными типами -
     * то изменить можно только эти типы, добавить новые - нельзя.
     * Зависим от доступов
     */
    public static function edit(int $actID, array $data): array
    {
      return true;
    }

    /**
     * @param int $actID
     * @return array|bool
     *
     * Метод удаления элемента
     * @todo Необходима реализация метода delete
     * Метод "удаления поэтапок" на самом деле метод убирает связь с объектом.
     * Элементы без связки с объектом - не котируются
     */
    public static function delete(int $actID): array
    {
        return true;
    }


    public static function setStatus(array $filter, int $status = config::CASHREGISTER_ACCEPTED_ACCESS_STATUS): array
    {
        $data['return'] = [];
        $iblockID = lists::getBlockID(config::LIST_CASHREGISTER_MAIN_CODE);
        $data['list'] = \CIBlockElement::GetList([], $filter, false, false, ['ID']);
        while ($el = $data['list']->fetch()) {

            if ($elReturn = \CIBlockElement::SetPropertyValuesEx($el['ID'], $iblockID, ['STATUS' => $status]))
                $data['return'][$elReturn] = access::setRules($el['ID']);
        }
        return $data['return'];
    }

    /**
     * @param $data
     * @param $params
     * @return array
     *
     * Формирование данных для загрузки, из даты
     */
    private static function formatData($data, $params): array
    {
        // return $params;
        $createLead = false;
        $response = [];
        foreach ($data as $k => $i) {
            if ($params[config::CASHREGISTER_EXPENDITURE_FIELD][$k]['format'] == config::CASHREGISTER_FORMAT_CONSUMPTION && $i > 0) $i *= -1;
            if ($params[config::CASHREGISTER_EXPENDITURE_FIELD][$k]) {
                $response[] = [
                    config::CASHREGISTER_EXPENDITURE_FIELD => $params[config::CASHREGISTER_EXPENDITURE_FIELD][$k]['id'],
                    config::CASHREGISTER_SUM_FIELD => $i,
                    config::CASHREGISTER_PAYMENT_FIELD => $data['pType'],
                    config::CASHREGISTER_COMMENT_FIELD => ($createLead) ? null : $data['comment'],
                ];
                $createLead = true;
            }
        }
        return $response;
    }


    /**
     * @param $data
     *
     * Формирование названия
     * проектируемая функция
     */
    public static function formatName($data) {

    }

    /**
     * @param int $actID
     * @return array
     *
     * Функция утверждения поэтапки
     */
    public static function accept(int $actID)
    {
        global $USER;
        $listData = \CIBlockElement::GetList([], ['ID' => $actID, 'IBLOCK_ID' => lists::getBlockID(config::LIST_CASHREGISTER_MAIN_CODE)], false, false, ['ID', 'IBLOCK_ID', 'NAME', 'PROPERTY_' . config::CASHREGISTER_ENTITY_FIELD])->fetch();
        if (!$listData) return config::status(config::STATUS_ERROR, "Акт с таким идентификатором не найден!");
        $objectData = \CCrmDeal::GetListEx([], ['ID' => $listData['PROPERTY_'.config::CASHREGISTER_ENTITY_FIELD.'_VALUE']], false, false, ['UF_CRM_1568623837'])->fetch();
        if (!$objectData) return config::status(config::STATUS_ERROR, "По данному акту не найден объект!");
        \CIBlockElement::SetPropertyValuesEx(
            $actID,
            lists::getBlockID(config::LIST_CASHREGISTER_MAIN_CODE),
            [
                config::CASHREGISTER_STATUS_FIELD => config::CASHREGISTER_ACCEPTED_ACCESS_STATUS,
                config::CASHREGISTER_DATE_RECONCILIATION_FIELD => date('d.m.Y H:i:s', time()),
                config::CASHREGISTER_AGREED_EMPLOYEE_FIELD => $objectData['UF_CRM_1568623837'] ?? $USER->GetID(),
                config::CASHREGISTER_APPROVED_EMPLOYEE_FIELD => [$USER->GetID()],
            ],
        );
        $rules = access::setRules($actID);
        if ($rules === true){
            self::startWorkflow($actID, 'afterAccept');
            return config::status(config::STATUS_SUCCESS, "Акт успешно принят!");
        }
        return $rules ?? [];
    }

    /**
     * @param int $actID
     * @return array
     *
     * Функция утверждения поэтапки
     * v2 была добавлена так как в основном не было учёта внесения поэтапки другими сотрудниками.
     */
    public static function acceptAdmins(int $actID)
    {
        global $USER;
        $listData = \CIBlockElement::GetList([], ['ID' => $actID, 'IBLOCK_ID' => lists::getBlockID(config::LIST_CASHREGISTER_MAIN_CODE)], false, false, ['ID', 'IBLOCK_ID', 'NAME', 'PROPERTY_' . config::CASHREGISTER_AGREED_EMPLOYEE_FIELD, 'PROPERTY_' . config::CASHREGISTER_ENTITY_FIELD])->fetch();
        if (!$listData) return config::status(config::STATUS_ERROR, "Акт с таким идентификатором не найден!");
        $objectData = \CCrmDeal::GetListEx([], ['ID' => $listData['PROPERTY_'.config::CASHREGISTER_ENTITY_FIELD.'_VALUE']], false, false, ['UF_CRM_1568623837'])->fetch();
        if (!$objectData) return config::status(config::STATUS_ERROR, "По данному акту не найден объект!");
        \CIBlockElement::SetPropertyValuesEx(
            $actID,
            lists::getBlockID(config::LIST_CASHREGISTER_MAIN_CODE),
            [
                config::CASHREGISTER_STATUS_FIELD => config::CASHREGISTER_ACCEPTED_ACCESS_STATUS,
                //config::CASHREGISTER_DATE_RECONCILIATION_FIELD => date('d.m.Y H:i:s', time()),
                config::CASHREGISTER_AGREED_EMPLOYEE_FIELD => $objectData['UF_CRM_1568623837'] ?? $USER->GetID(),
                config::CASHREGISTER_APPROVED_EMPLOYEE_FIELD => [$listData['PROPERTY_'.config::CASHREGISTER_AGREED_EMPLOYEE_FIELD.'_VALUE']],
            ],
        );
        $rules = access::setRules($actID);
        if ($rules === true){
            //self::startWorkflow($actID, 'afterAccept');
            return config::status(config::STATUS_SUCCESS, "Акт успешно принят!");
        }
        return $rules ?? [];
    }

    public static function rejected(int $actID)
    {
        global $USER;
        $element = \CIBlockElement::GetByID($actID);
        if (!$ar_res = $element->GetNext()) return config::status(config::STATUS_ERROR, "Акт с таким идентификатором не найден!");
        \CIBlockElement::SetPropertyValuesEx(
            $actID,
            lists::getBlockID(config::LIST_CASHREGISTER_MAIN_CODE),
            [
                config::CASHREGISTER_STATUS_FIELD => config::CASHREGISTER_REJECTED_ACCESS_STATUS,
            ],
        );
        $rules = access::setRules($actID);
        if ($rules === true) return config::status(config::STATUS_SUCCESS, "Акт успешно отклонён!");
        return $rules ?? [];
    }

    /**
     * Метод запуска бп создания документов и сделок ОКК и чистовых
     * в случае если поэтапка первая
     *
     * В бп два статуса
     *  - afterAdd (создание документов)
     *  - afterAccept (создание сделок)
     *
     * Какой статус выполнится зависит от $action
     *
     */
    public static function startWorkflow(int $actID, string $action)
    {
        \CBPDocument::StartWorkflow(
            self:: $workflowID,
            ['lists', 'Bitrix\Lists\BizprocDocumentLists', $actID],
            ['action'=> $action],
            $GLOBALS['error']
          );
    }
}
