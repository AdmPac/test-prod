<?php

namespace Nebo\CashRegister;

class accesses
{
    /**
     * @param $type
     * @param $blockID
     * @param $elementID
     * @return array
     *
     * Получить доступы по определённому блоку
     */
    public static function getType($type, $blockID, $elementID): array
    {
        $query = [
            'iblock' => ['ENTITY_TYPE' => 'iblock', 'IBLOCK_ID' => $blockID],
            'element' => ['ENTITY_TYPE' => 'element', 'IBLOCK_ID' => $blockID, 'ENTITY_ID' => $elementID],
        ];
        if (!$query[$type]) return config::status(config::STATUS_ERROR, 'Тип не найден');
        $f = lists::getRights($query[$type]);
        $response = [];
        while ($i = $f->fetch()) {
            $response[] = $i;
        }
        return $response;
    }

    // Установить доступы умным образом по-умолчанию
    public static function update($id, $rights) {

        $el = new CIBlockElement;
        $el->Update($id, $rights);

    }

    // Установить доступы
    public static function set($id, $data) {

        $el = new CIBlockElement;
        $el->Update($id, $data);

    }

}
