<?php

namespace Nebo\CashRegister\Api;

use Nebo\CashRegister\Act as ActModule;


/**
 * Класс-контроллер для взаимодействия с методами класса act по REST-API
 * Параметры методов полностью соответствуют аналогичному методу модуля
 */
class Act extends \Bitrix\Main\Engine\Controller
{
    public function addAction($object, $typeID, $data): array
    {
        $object = intval($object);
        if (is_string($data)) {
            $data = json_decode($data, true);
        }
        return ActModule::add($object, $typeID, $data);
    }
    
    public function acceptAction($actID)
    {
        $actID = intval($actID);
        return ActModule::accept($actID);
    }

    public function acceptArrayAction($actsID)
    {
        foreach($actsID as $k=>$id){
            $actsID[$k] = intval($id);
        }
        return ActModule::acceptArray($actsID);
    }


    public function rejectedArrayAction($actsID)
    {
        foreach($actsID as $k=>$id){
            $actsID[$k] = intval($id);
        }
        return ActModule::rejectedArray($actsID);
    }

    public function rejectedAction($actID)
    {
        $actID = intval($actID);
        return ActModule::rejected($actID);
    }
    
    public function calcActByDealIdAction($idEntity){
        $idEntity = intval($idEntity);
        return ActModule::calcActByDealId($idEntity);
    }

}
