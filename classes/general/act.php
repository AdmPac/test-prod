<?php

namespace Nebo\CashRegister;

use CModule;
use \Bitrix\Main\Localization\Loc as Loc;


if(!CModule::IncludeModule("bizproc")) die('Module `bizproc` not included');


/**
 * Класс взаимодействия с актами
 */
class act
{ 
    
    public static $arProperties;
    private static $workflowID = 911 ; // ID бп по созданию доков/сделок
    
    /**
     * @return string
     * Получает имя последнего вызывающего getMethodName метода
     */
    private static function getMethodName() : string
    {
        $ex = new \Exception();
        $trace = $ex->getTrace();
        $fName = $trace[count($trace)-1]['function'];
        
        $data = json_encode($data,JSON_UNESCAPED_UNICODE);
        
        if(in_array($fName, get_class_methods(get_called_class()))) return $fName;
        else return false;
    }

    /**
     * @param array $data - Данные для логирования
     * 
     * @return bool
     * Логирует данные в журнал событий битрикса
     */
    public static function log(array $data) : bool{
        global $USER;
        
        if(!$logName = self::getMethodName()) return false;

        \CEventLog::Add(array(
            "SEVERITY" => "INFO",
            "AUDIT_TYPE_ID" => $logName,
            "MODULE_ID" => "nebo.cashregister",
            "ITEM_ID" => $USER->GetID(),
            "DESCRIPTION" => $data,
        ));

        return true;
    }

    /**
     * @param int $object - id объекта(сделки и тд)
     * @param array $data - перечень уникальных правил при добавлении акта
     * 
     * @return array
     * Функция для объявления дефолтных полей при добавлении акта
     */
    private static function defaultArrayAct(int $object, array $data) : array{
        $uid = config::generationUid();
        global $USER;

        $created_by = \CCrmDeal::GetList([],['ID'=>$object],[config::CASHREGISTER_FOREMEN])->fetch()[config::CASHREGISTER_FOREMEN];
        
        $arrData = [
            config::CASHREGISTER_UID_FIELD => $uid,
            config::CASHREGISTER_ENTITY_FIELD => $object,
            config::CASHREGISTER_STATUS_FIELD => $data['status'] ?? config::CASHREGISTER_DEF_ACCESS_STATUS,
            'IBLOCK_ID' => lists::getBlockID(config::LIST_CASHREGISTER_MAIN_CODE),
            'CREATED_BY' => $created_by,
            'NAME' => $uid,
            config::CASHREGISTER_CREATOR_EMPLOYEE_FIELD => $USER->GetID(),
        ];

        return $arrData;
    }

    /**
     * @param array $dataElement - базовые поля акта
     * @param array $data - добавочные поля акта
     * 
     * @return [type]
     * Функция добавления записи в инфоблок с актами
     */
    private static function addIblockElement(array $dataElement, array $data){
        $el = new \CIBlockElement;

        $dataElement = array_merge($dataElement,$data);
        $dataElement["PROPERTY_VALUES"] = $dataElement;
        
        $element = $el->Add($dataElement);
        
        if ($element) access::setRules($element);

        $result = $element ?? config::status(config::STATUS_ERROR, $el->LAST_ERROR);
        
        $data['resultMethod'] = $result;

        self::log($data);

        return $result;
    }

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
        $checkRules = presets::checkRules($typeID, $data);//вынести ???
        if (!presets::checkRulesShort($checkRules['response'])){
            self::log([
                'response'=>$checkRules,
                'response'=>$checkRules,
            ]);
            return config::status(config::STATUS_ERROR, Loc::getMessage('NEBO_CASHREGISTER_ERROR_WRITE') . json_encode($checkRules, JSON_UNESCAPED_UNICODE));
        }
        
        $add = self::formatData($data, presets::$list[$typeID]);
        
        $arrData = self::defaultArrayAct($object,$data);

        $response = [];
        foreach ($add as $i) {
            $response[] = self::addIblockElement($i,$arrData);
        }
        
        $data = [
            'response'=>$response,
            'data'=>$arrData,
        ];
        
        self::log($data);
        return $response;
    }

    /**
     * @param int $object - объект (сделка по которой создаём)
     * @param $typeID - тип акта
     * @param 
     * $data - данные для добавления [
     *     reconciliation_date - дата принятия (необязательно)
     *     agreed_employee - кто принял (необязательно, ID пользователя)
     *     created_by - кем создана (необязательно, ID пользователя)
     *     status - создано(1631) / согласованно(1632), если не указано, то устанавливаем создано
     *]
     * @return array
     *
     * Добавление акта с множеством параметров для админов
     */
    public static function addAdmin(int $object, $typeID, $data): array
    {
        $checkRules = presets::checkRules($typeID, $data);
        if (!presets::checkRulesShort($checkRules['response'])) return config::status(config::STATUS_ERROR, Loc::getMessage('NEBO_CASHREGISTER_ERROR_WRITE') . json_encode($checkRules, JSON_UNESCAPED_UNICODE));
        
        $add = self::formatData($data, presets::$list[$typeID]);
        
        $arrData = [
            config::CASHREGISTER_DATE_RECONCILIATION_FIELD=>$data['reconciliation_date'],
            config::CASHREGISTER_AGREED_EMPLOYEE_FIELD=>$data['agreed_employee'],
            'DATE_CREATE'=>$data['date_create'],
        ];
        
        $arrData = array_merge($arrData,self::defaultArrayAct($object,$data));
        
        $response = [];
        foreach ($add as $i) {
            $response[] = self::addIblockElement($i,$arrData);
        }

        $data = [
            'response'=>$response,
            'data'=>$arrData,
        ];
        self::log($data);
        
        return $response;//смотреть на то, что было
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


    /**
     * @param array $filter
     * @param int $status
     * 
     * @return array
     * НЕИСПОЛЬЗУЕМАЯ ФУНКЦИЯ
     */
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
     * @param $data - данные для формирования
     * @param $params - данные о статьях
     * @return array - данные для создания акта
     *
     * Формирование данных для загрузки акта, из даты
     */
    private static function formatData(array &$data, $params): array
    {
        $createLead = false;
        $response = [];

        if(isset($data['act'])){//чтобы коммент падал всегда на акт при его присутствии
            $data = array_merge(['act'=>$data['act']],$data);
        }

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
     * @param array $selectIB
     * @param int $actID
     * @param array $saveData
     * @param mixed $listData
     * 
     * @return [type]
     */
    public static function getDataAccept(array $selectIB, int $actID, array $saveData, $listData){
        $marker = true;
        $statusErr = [];
        
        if($listData===0) $listData = \CIBlockElement::GetList([], ['ID' => $actID, 'IBLOCK_ID' => lists::getBlockID(config::LIST_CASHREGISTER_MAIN_CODE)], false, false, $selectIB)->fetch();
        
        if(!$listData){
            $marker = false;
            $statusErr[] = config::status(config::STATUS_ERROR, Loc::getMessage('NEBO_CASHREGISTER_ERROR_ACT_NOT_FOUND'));
        }

        $objectData = \CCrmDeal::GetListEx([], ['ID' => $listData['PROPERTY_'.config::CASHREGISTER_ENTITY_FIELD.'_VALUE']], false, false, [config::CASHREGISTER_SECTION_FOREMEN])->fetch();
        
        if (!$objectData){
            $marker = false;
            $statusErr[] = config::status(config::STATUS_ERROR, Loc::getMessage('NEBO_CASHREGISTER_OBJECT_NOT_FOUND'));//получать связанных лиц по запросу
        }
        
        if(!$marker) return $statusErr;
        else return $objectData;
        
    }

    /**
     * @param array $selectIB
     * @param int $actID
     * @param array $saveData
     * 
     * @return [type]
     * Функция-обобщение. Изменяет запись в инфоблоке с актами
     */
    public static function saveDataAccept(array $selectIB, int $actID, array $saveData, $listData=0){
        
        global $USER;

        if(!$objectData = self::getDataAccept($selectIB, $actID, $saveData, $listData)) return false;

        if(isset($saveData[config::CASHREGISTER_APPROVED_EMPLOYEE_FIELD])){//множественное утверждение
            $arrAct = \CIBlockElement::GetPropertyValues(
                lists::getBlockID(config::LIST_CASHREGISTER_MAIN_CODE),
                ['ID'=>512592]
            );
            $approveds = $arrAct->fetch()['1866'];
            
            foreach($approveds as $v){
                if(!in_array($v,$saveData[config::CASHREGISTER_APPROVED_EMPLOYEE_FIELD]))
                    $saveData[config::CASHREGISTER_APPROVED_EMPLOYEE_FIELD][] = $v;
            }
        }
        

        $defaultArray = [
            config::CASHREGISTER_STATUS_FIELD => config::CASHREGISTER_ACCEPTED_ACCESS_STATUS,
            config::CASHREGISTER_DATE_RECONCILIATION_FIELD => date('d.m.Y H:i:s', time()),
            config::CASHREGISTER_AGREED_EMPLOYEE_FIELD => $objectData[config::CASHREGISTER_SECTION_FOREMEN] ?? $USER->GetID(),
        ];
        
        $saveData = array_merge($saveData,$defaultArray);
        
        \CIBlockElement::SetPropertyValuesEx(
            $actID,
            lists::getBlockID(config::LIST_CASHREGISTER_MAIN_CODE),
            $saveData
        );

        return [
            'data'=>$saveData,
            'status'=>true,
        ];
    }

    
    /**
     * @param int $actID
     * @return array
     *
     * Функция утверждения поэтапки
     * v2 была добавлена так как в основном не было учёта внесения поэтапки другими сотрудниками.
     */
    public static function accept(int $actID)
    {   
        global $USER;
        
        $selectIB = ['ID', 'IBLOCK_ID', 'NAME', 'PROPERTY_' . config::CASHREGISTER_ENTITY_FIELD];

        $saveData = [
            config::CASHREGISTER_APPROVED_EMPLOYEE_FIELD => [$USER->GetID(),3,16],
        ];

        $res = self::saveDataAccept($selectIB,$actID,$saveData);

        if($res['status']!==true){
            self::log($res);
            return $res;//вывод ошибок
        } 

        $rules = access::setRules($actID);
        
        $marker = false;
        if ($rules === true){
            $marker = true;
            $arStatus = [config::STATUS_SUCCESS, Loc::getMessage('NEBO_CASHREGISTER_ACT_ACCEPT')];
        }else{
            $arStatus = [config::STATUS_SUCCESS, Loc::getMessage('NEBO_CASHREGISTER_ERROR_ACCEPT')];
        }
        
        $return = config::status($arStatus[0],$arStatus[1],[
            'runner'=>$USER->GetID(),
            'actID'=>$actID,
            'rules  '=>$rules,
        ]);
        
        self::log($return);

        if($marker) return $return;
        return $rules ?? [];    
    }

    /**
     * @param array $actsID - массив id актов
     * 
     * @return [type]
     * Функция для множественного утверждения актов
     */
    public static function acceptArray(array $actsID){
        foreach($actsID as $id){
            self::accept($id);
        }
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
        
        $selectIB = ['ID', 'IBLOCK_ID', 'NAME', 'PROPERTY_' . config::CASHREGISTER_AGREED_EMPLOYEE_FIELD, 'PROPERTY_' . config::CASHREGISTER_ENTITY_FIELD];

        $listData = \CIBlockElement::GetList([], ['ID' => $actID, 'IBLOCK_ID' => lists::getBlockID(config::LIST_CASHREGISTER_MAIN_CODE)], false, false, $selectIB)->fetch();
        $saveData = [config::CASHREGISTER_APPROVED_EMPLOYEE_FIELD => [$listData['PROPERTY_'.config::CASHREGISTER_AGREED_EMPLOYEE_FIELD.'_VALUE']]];
        
        $res = self::saveDataAccept($selectIB,$actID,$saveData);

        if($res['status']!==true){
            self::log($res);
            return $res;//вывод ошибок
        } 

        $rules = access::setRules($actID);
        $marker = false;
        if ($rules === true){
            $marker = true;
            $arStatus = [config::STATUS_SUCCESS, Loc::getMessage('NEBO_CASHREGISTER_ACT_ACCEPT')];
        }else{
            $arStatus = [config::STATUS_SUCCESS, Loc::getMessage('NEBO_CASHREGISTER_ERROR_ACCEPT')];
        }

        $return = config::status($arStatus[0],$arStatus[1],[
            'runner'=>$USER->GetID(),
            'actID'=>$actID,
            'rules  '=>$rules,
        ]);
        
        self::log($return);

        if($marker) return $return;
        return $rules ?? [];
    }

    /**
     * @param int $actID
     * 
     * @return [type]
     * Функция для отклонения поэтапок
     */
    public static function rejected(int $actID)
    {
        global $USER;
        $element = \CIBlockElement::GetByID($actID);
        if (!$ar_res = $element->GetNext()) return config::status(config::STATUS_ERROR, Loc::getMessage('NEBO_CASHREGISTER_ERROR_ACT_NOT_FOUND'));//Unusing $ar_res
        \CIBlockElement::SetPropertyValuesEx(
            $actID,
            lists::getBlockID(config::LIST_CASHREGISTER_MAIN_CODE),
            [
                config::CASHREGISTER_STATUS_FIELD => config::CASHREGISTER_REJECTED_ACCESS_STATUS,
            ],
        );
        $rules = access::setRules($actID);
        $marker = false;
        if ($rules === true){
            $marker = true;
            $arStatus = [config::STATUS_SUCCESS, Loc::getMessage('NEBO_CASHREGISTER_ACT_REJECTED')];
        }else{
            $arStatus = [config::STATUS_ERROR, Loc::getMessage('NEBO_CASHREGISTER_ERROR_REJECTED')];
        } 
        
        $return = config::status($arStatus[0],$arStatus[1],[
            'runner'=>$USER->GetID(),
            'actID'=>$actID,
            'rules  '=>$rules,
        ]);
        
        self::log($return);//логировать не только успех, а все
        
        if($marker) return $return;
        return $rules ?? [];
    }

    /**
     * @param array $actsID - массив id актов
     * 
     * @return [type]
     * Функция для множественного утверждения актов
     */
    public static function rejectedArray(array $actsID){
        foreach($actsID as $id){
            self::rejected($id);
        }
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