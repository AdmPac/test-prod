<?php

namespace Nebo\CashRegister;

use Nebo\CashRegister\Access as AccessModule;
use \Bitrix\Highloadblock\HighloadBlockTable as HLBT; 
use \Bitrix\Main\Localization\Loc as Loc;
use Bitrix\Main\Config\Option;

/**
 * Класс-контроллер для взаимодействия  настройками приложения по REST-API
 * этот класс был создан потому, что нельзя взаимодействовать с HL-блоками по Rest-API
 */
class settings
{

    public static function getArticle(){
        $article = \CIBlockElement::GetList(
            [],
            ["IBLOCK_ID"=>lists::getBlockID(config::LIST_CASHREGISTER_EXPENDITURE_CODE)],
            false,
            false,
            ['NAME','ID']
        );

        while($arc = $article->fetch()){
            $arrArc[$arc['NAME']] = $arc['ID'];
        }
        
        return $arrArc;
    }

    /**
     * @return array
     * Метод возвращает статьи в виде массива:
     * [fotr] => Array
     *      (
     *      [0] => Акт
     *      [1] => ФОТр 
     * ) и тд
     */
    public static function getSettingsArticle(): array 
    {
        $settings = self::get();
        $articleSettings = [];
        $arr = [];
        
        foreach($settings as $name=>$arrSetting){
            $articleSettings[$name] = $arrSetting['UF_SETTINGS_ELEMENTS']['elements'];
        }
        
        foreach($articleSettings as $name=>$idsArticle){
            $idsArticle = (array)$idsArticle;
            foreach($idsArticle as $i=>$id){
                $article = \CIBlockElement::GetList([], [
                    "IBLOCK_ID"=>lists::getBlockID(config::LIST_CASHREGISTER_EXPENDITURE_CODE),
                    'ID'=>$id
                ], false, false, ['NAME'])->fetch();
                
                $articleSettings[$name][$i] = $article['NAME'];
            }
        }
        return $articleSettings;
    }

    private static function getHL_ID() : int
    {
        $hlblock = HLBT::getList([
            'filter' => ['=NAME' => 'AppFunnelForemanSettings']
        ])->fetch();
        return $hlblock['ID'];
    }

    private static function GetEntityDataClass($HlBlockId)
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

    public static function get(string $code = null): array
    {
        $settings = Option::getForModule('nebo.cashregister');

        $data = [];
        foreach($settings as $code=>$vArr){
            $vArr = (array)json_decode($vArr);
            $vArr['UF_SETTINGS_ELEMENTS'] = array_values((array)($vArr['UF_SETTINGS_ELEMENTS']));

            $data[$code] = [
                'UF_SETTINGS_ELEMENTS' => [
                    'code' => $code,
                    'elements' => (array)$vArr['UF_SETTINGS_ELEMENTS'],
                ],
                'UF_ADD_SETTINGS'=>$vArr['UF_ADD_SETTINGS']!='null' ? $vArr['UF_ADD_SETTINGS'] : [],
            ];
        }
        return $data;
    }

    public static function addPattern($data)
    {
        $dataSet = [];

        $code = array_keys($data)[0];

        $arcs = self::getArticle();
        
        $code = array_keys($data)[0];
        foreach($data[$code] as $k=>$v){
            $data[$code][$k] = $arcs[$v];
        }

        if(!$code||!array_keys($data)[0]) return false;
        $dataSet = [
            'code' => array_keys($data)[0]
        ];
        
        $dataSet['elements']['chapters']['UF_SETTINGS_ELEMENTS']['elements'] = $data[array_keys($data)[0]];

        if(!in_array($code,array_keys(self::get()))){
            $entity_data_class = self::GetEntityDataClass(self::getHL_ID());
            
            $setData = [
                'UF_SETTINGS' =>  $code,
                'UF_SETTINGS_ELEMENTS' => $dataSet['elements']['chapters']['UF_SETTINGS_ELEMENTS']['elements'],
            ];
            
            $entity_data_class::add($setData);
            Option::set('nebo.cashregister',$code,json_encode($setData));
        }

    }


    public static function set($data): array
    {
        global $USER;
        
        if(!$USER->IsAdmin())
            return [
                'status' => 'error',
                'message' => Loc::getMessage('NEBO_CASHREGISTER_ERROR_EDIT_SETTINGS'),
            ];

        $code = $data['code'];
        $entity_data_class = self::GetEntityDataClass(self::getHL_ID());
        $rsData = $entity_data_class::getList(array(
            'select' => ['ID'],
            'filter' => ['UF_SETTINGS' => $code],
        ));

        $elem = [];
        
        while($el = $rsData->fetch()){
            $elem = $el;
        }

        if ($elem['ID']) {
            $setData = [
                'UF_SETTINGS_ELEMENTS' =>  $data['elements']['chapters']['UF_SETTINGS_ELEMENTS']['elements'],
                'UF_ADD_SETTINGS' => json_encode($data['elements']['chapters']['UF_ADD_SETTINGS']['elements'], JSON_UNESCAPED_UNICODE),
            ];

            $entity_data_class::update($elem['ID'], $setData);
            Option::set('nebo.cashregister',$code,json_encode($setData));

            return static::get($code);
        }
        return [
            'status' => 'error',
            'message' => Loc::getMessage('NEBO_CASHREGISTER_CODE_ELEMENT_NOT_FOUND'),
        ];
    }
}