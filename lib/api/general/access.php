<?php

namespace Nebo\CashRegister\Api;

use Nebo\CashRegister\Access as AccessModule;


/**
 * Класс-контроллер для взаимодействия с методами класса access по REST-API
 * Параметры методов полностью соответствуют аналогичному методу модуля
 */
class Access extends \Bitrix\Main\Engine\Controller
{
    public function setRulesAction($element)
    {
        return AccessModule::setRules($element);
    }

    public function formatDefaultRulesAction($iblock): array
    {
        return AccessModule::formatDefaultRules($iblock);
    }

    public function formatStatusRulesAction($elementID): array
    {
        return AccessModule::formatStatusRules($elementID);
    }

    public function getListAction($type, $iblock, $elementID = null): array
    {
        $elementID = intval($elementID);
        return AccessModule::getList($type, $iblock, $elementID);
    }

    public function getRulesExpAction($expID, $select): object
    {
        $select = json_decode($select, true);
        return AccessModule::getRulesExp($expID, $select);
    }
}
