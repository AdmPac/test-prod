<?php

namespace Nebo\CashRegister\Api;

use Nebo\CashRegister\Accesses as AccessesModule;


/**
 * Класс-контроллер для взаимодействия с методами класса accesses по REST-API
 * Параметры методов полностью соответствуют аналогичному методу модуля
 */
class Accesses extends \Bitrix\Main\Engine\Controller
{
    public function getTypeAction($type, $blockID, $elementID): array
    {
        return AccessesModule::getType($type, $blockID, $elementID);
    }

    public function updateAction($id, $rights): array
    {
        return AccessesModule::update($id, $rights);
    }

    public function setAction($id, $data): array
    {
        return AccessesModule::set($id, $data);
    }
}
