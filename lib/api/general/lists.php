<?php

namespace Nebo\CashRegister\Api;

use Nebo\CashRegister\Lists as ListsModule;


/**
 * Класс-контроллер для взаимодействия с методами класса lists по REST-API
 * Параметры методов полностью соответствуют аналогичному методу модуля
 */
class Lists extends \Bitrix\Main\Engine\Controller
{
    public function getBlockIDAction($code): int
    {
        return ListsModule::getBlockID($code);
    }

    public function getRights($arFilter): object
    {
        $arFilter = json_decode($arFilter);
        return ListsModule::getRights($arFilter);
    }
}
