<?php

namespace Nebo\CashRegister\Api;

use Nebo\CashRegister\Act as ActModule;
use \Bitrix\Highloadblock\HighloadBlockTable as HLBT;


/**
 * Класс-контроллер для взаимодействия  настройками приложения по REST-API
 * этот класс был создан потому, что нельзя взаимодействовать с HL-блоками по Rest-API
 */
class Settings extends \Bitrix\Main\Engine\Controller
{

    private function getHL_ID() {
        $hlblock = HLBT::getList([
            'filter' => ['=NAME' => 'AppFunnelForemanSettings']
        ])->fetch();
        return $hlblock['ID'];
    }

    private function GetEntityDataClass($HlBlockId)
    {
        if (empty($HlBlockId) || $HlBlockId < 1)
        {
            return false;
        }
        $hlblock = HLBT::getById($HlBlockId)->fetch();
        $entity = HLBT::compileEntity($hlblock);
        $entity_data_class = $entity->getDataClass();
        return $entity_data_class;
    }

    public function getAction(string $code = null): array
    {
        $entity_data_class = $this->GetEntityDataClass($this->getHL_ID());
        $filter = [];
        if ($code) {
            $filter = [
                'UF_SETTINGS' => $code,
            ];
        }
        $rsData = $entity_data_class::getList(array(
            'select' => ['*'],
            'filter' => $filter,
        ));
        $res = [];
        while($el = $rsData->fetch()){
            $res[$el['UF_SETTINGS']] = [
                'UF_SETTINGS_ELEMENTS' => [
                    'code' => $el['UF_SETTINGS'],
                    'elements' => $el['UF_SETTINGS_ELEMENTS'],
                ],
                'UF_ADD_SETTINGS' => json_decode($el['UF_ADD_SETTINGS'], true) ?? [],
            ];
        }
        return $res;
    }

    public function setAction($data): array
    {
        $code = $data['code'];
        $entity_data_class = $this->GetEntityDataClass($this->getHL_ID());
        $rsData = $entity_data_class::getList(array(
            'select' => ['ID'],
            'filter' => ['UF_SETTINGS' => $code],
        ));
        $elem = [];
        while($el = $rsData->fetch()){
            $elem = $el;
        }
        if ($elem['ID']) {
            $entity_data_class::update($elem['ID'], array(
                'UF_SETTINGS_ELEMENTS' =>  $data['elements']['chapters']['UF_SETTINGS_ELEMENTS']['elements'],
                'UF_ADD_SETTINGS' => json_encode($data['elements']['chapters']['UF_ADD_SETTINGS']['elements'], JSON_UNESCAPED_UNICODE),
            ));
            return static::getAction($code);
        }
        return [
            'status' => 'error',
            'message' => 'Элемент с таким кодом не найден!',
        ];
    }
}