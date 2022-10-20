<?php

namespace Nebo\CashRegister\Api;
use Nebo\CashRegister\Presets as PresetsModule;


/**
 * Класс-контроллер для взаимодействия с методами класса presets по REST-API
 * Параметры методов полностью соответствуют аналогичному методу модуля
 */
/**
 * Класс-контроллер для взаимодействия с методами класса presets по REST-API
 * Параметры методов полностью соответствуют аналогичному методу модуля
 */
class Presets extends \Bitrix\Main\Engine\Controller
{
    public function getListAction($filter = [], array $select = null): array
    {
        $res = [];
        $data = PresetsModule::getList($filter, $select);
        while ($el = $data->GetNext()) {
            $res[] = $el;
        }
        return $res;
    }

    public function getRulesAllAction()
    {
        return PresetsModule::getRulesAll();
    }
    
    public function getRulesAction($element)
    {
        if (is_numeric($element)) {
            $element = intval($element);
        }
        return PresetsModule::getRules($element);
    }

    public function checkRulesAction($id, $data): array
    {
        $data = json_decode($data, true);
        return PresetsModule::checkRules($id, $data);
    }

    public function checkRulesShortAction($checkRules): bool
    {
        $checkRules = json_decode($checkRules, true);
        return PresetsModule::checkRulesShort($checkRules);
    }
}
