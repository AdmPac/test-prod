<?php
namespace Nebo\CashRegister;

use \Bitrix\Main\Localization\Loc as Loc; 

class config
{
    /**
     * Константы CASHREGISTER
     */
    public const LIST_CASHREGISTER_MAIN_CODE = 'CASHREGISTER_MAIN'; // Кол основного блока
    public const LIST_CASHREGISTER_EXPENDITURE_CODE = 'CASHREGISTER_EXPENDITURE'; // Блок со статьями
    public const LIST_CASHREGISTER_EXPENDITURE_LIST = ['ID', 'NAME', 'CODE', 'PROPERTY_ACCESS']; // Поля по умолчанию в списке со статьями
    public const LIST_CASHREGISTER_PRESETS_CODE = 'CASHREGISTER_PRESETS'; // ID листа с пресетами
    public const LIST_CASHREGISTER_PRESETS_LIST = ['ID', 'NAME', 'CODE']; // Поля по умолчанию в списке с пресетами
    public const CASHREGISTER_EXPENDITURE_FIELD = 'EXPENDITURE'; // Название поля, со статьями расходов
    public const CASHREGISTER_ADD_FIELD = 'ADDFIELD'; // Название поля, со статьями расходов
    public const CASHREGISTER_SUM_FIELD = 'SUM'; // Название поля, со статьями расходов
    public const CASHREGISTER_PAYMENT_FIELD = 'PAYMENT_FORMAT'; // Название поля, со статьями расходов
    public const CASHREGISTER_COMMENT_FIELD = 'COMMENT'; // Название поля, с комментарием
    public const CASHREGISTER_ENTITY_FIELD = 'ENTITY'; // Название поля, с объектом
    public const CASHREGISTER_UID_FIELD = 'UID'; // Название поля, с уникальным ID
    public const CASHREGISTER_STATUS_FIELD = 'STATUS'; // Название поля, со статусом
    public const CASHREGISTER_DATE_RECONCILIATION_FIELD = 'DATE_RECONCILIATION'; // Название поля, с датой утверждения акта
    public const CASHREGISTER_AGREED_EMPLOYEE_FIELD = 'AGREED_EMPLOYEE'; // Название поля, с сотрудником, утвердившим акт (начальник участка)
    public const CASHREGISTER_APPROVED_EMPLOYEE_FIELD = 'APPROVED_EMPLOYEE'; // Название поля, с юзером, который утвердил акт
    public const CASHREGISTER_CREATOR_EMPLOYEE_FIELD = 'CREATOR_EMPLOYEE'; // Название поля, с юзером, который утвердил акт
    public const CASHREGISTER_SECTION_FOREMEN = 'UF_CRM_1568623837'; // НУ
    public const CASHREGISTER_FOREMEN = 'UF_CRM_1568623890'; // Прораб
    public const CASHREGISTER_PLAN_MONEY_DEAL = 'UF_CRM_1568623717'; // План по доходу в сделке
    public const CASHREGISTER_DEF_ACCESS_STATUS = 1631; // Статус назначения доступов по-умолчанию
    public const CASHREGISTER_ACCEPTED_ACCESS_STATUS = 1632; // Статус назначения доступов ПРИНЯТОГО акта
    public const CASHREGISTER_REJECTED_ACCESS_STATUS = 1676; // Статус назначения доступов ОТКЛОНЁННОГО акта
    public const CASHREGISTER_FORMAT_COMING = 1620; // Статус статьи прихода
    public const CASHREGISTER_FORMAT_CONSUMPTION = 1621; // Статус статьи расхода
    public const CASHREGISTER_CHECK_LIST = [
        self::CASHREGISTER_EXPENDITURE_FIELD,
        self::CASHREGISTER_ADD_FIELD
    ]; // Список проверочных полей
    public const PREFIX_GROUP_CODE = [
        'USER' => 'U',
        'SUP_USER' => 'IU',
        'DEPARTMENT' => 'D',
        'DEPARTMENT_RIGHT' => 'DR',
    ];
    public const HIGH_ACCESS = ['G7', 'G27']; // Доступы высокого уровня, программа их не очищает
    public const TYPE_FIELDS_LINK = [
        'integer' => ['title' => 'Целое число', 'checkType' => 'php'],
        'string' => ['title' => 'Строка', 'checkType' => 'php'],
        'double' => ['title' => 'Число', 'checkType' => 'php'],
        'boolean' => ['title' => 'Да/Нет', 'checkType' => 'php'],
        'datetime' => ['title' => 'Дата/Время', 'checkType' => 'bx24'],
        'date' => ['title' => 'Дата', 'checkType' => 'bx24'],
        'money' => ['title' => 'Деньги', 'checkType' => 'bx24'],
        'url' => ['title' => 'Ссылка', 'checkType' => 'bx24'],
        'address' => ['title' => 'Адрес Google карты', 'checkType' => 'bx24'],
        'enumeration' => ['title' => 'Список', 'checkType' => 'bx24'],
        'file' => ['title' => 'Файл', 'checkType' => 'bx24'],
        'employee' => ['title' => 'Привязка к пользователю', 'checkType' => 'bx24'],
        'crm_status' => ['title' => 'Привязка к справочникам CRM', 'checkType' => 'bx24'],
        'iblock_section' => ['title' => 'Привязка к разделам инф. блоков', 'checkType' => 'bx24'],
        'iblock_element' => ['title' => 'Привязка к элементам инфоблоков', 'checkType' => 'bx24'],
        'crm' => ['title' => 'Привязка к элементам CRM'],
    ]; // типы связей поле-название-проверка

    /**
     * Технические константы
     */
    public const MAX_LENGTH_RAND_STR = 5; // Количество знаков в рандомайзере
    public const STATUS_SUCCESS = ['status' => 'ok']; // Обозначение статуса успеха
    public const STATUS_ERROR = ['status' => 'error']; // Обозначение ошибочного статуса


    /**
     * @param $status
     * @param $message
     * @param $data
     * @return array
     *
     * Метод объединения статуса и текста статуса
     */
    public static function status($status, $message, $data = null): array
    {
        return array_merge($status, ['text' => $message, 'data' => $data]);
    }

    /**
     * @param $element - проверяемый элемент
     * @param $rules - правила, которые необходимо проверить у текущего элемент
     * @return string[]|void
     * 
     * Проверка вводимого типа на правила.
     */
    public static function checkType($element, array $rules): array
    {
        if(!$element)//пропускаем пустой элемент, т.к. нельзя проверить его тип
           return self::STATUS_SUCCESS;
        
            foreach($rules['types'] as $typeName){//если затесался какой-то несуществующий тип
                if(!isset(self::TYPE_FIELDS_LINK[$typeName]))
                    return self::status(self::STATUS_ERROR, Loc::getMessage('NEBO_CASHREGISTER_SYSTEM_ERROR_CHECKTYPE') . json_encode([$element, $rules]));
        }

        $typeValue = array_map(function ($i) {return $i['value'];}, $rules["value"]);

        if(!(in_array(gettype($element),$rules['types'])||in_array($element,$typeValue)))//несоответствие данных и их типов(передали строку, а ожидалось целое число)
            return self::status(self::STATUS_ERROR, Loc::getMessage('NEBO_CASHREGISTER_TYPE_ERROR'). implode(", ", $rules['types']));

        return self::STATUS_SUCCESS;
    }

    /**
     * @return string
     *
     * Генератор рандомной строки
     * @todo Следует сделать проверку, на наличие такого уникального ID элемента списка в последующем
     */
    public static function generationUid(): string
    {
        return \randString(self::MAX_LENGTH_RAND_STR, array(
            "WERTYUIPASDFGHJJKLKZXCVBNNM",
            "123456789",
            "123456789",
            "123456789",
        ));
    }

    /**
     * @param array $arFilter - фильтр
     * @param array|string[] $arOrder - сортировка
     * @param array|string[] $arSelect - выбор
     * @return object
     *
     * Функция возвращает департаменты, по структуре CRM
     * @todo стоит перенести в основной модуль nebo.dev
     */
    public static function DepartmentStructure(array $arOrder, array $arFilter, array $arSelect = ['ID', 'NAME', 'DEPTH_LEVEL', 'UF_HEAD', 'IBLOCK_SECTION_ID']): object
    {
        $departmentIblockId = (int) \Bitrix\Main\Config\Option::get('intranet', 'iblock_structure', 0);
        return \CIBlockSection::GetList(
            $arOrder,
            array_merge(["ACTIVE"=>"Y", "IBLOCK_ID"=> $departmentIblockId], $arFilter),
            false,
            $arSelect
        );
    }

    /**
     * @param int $user - поиск по пользователю
     * @param int $lvl - уровень поиска (null - возвращает все, 0 - ищет в текущем, 1 - ищет на уровне выше и так далее...)
     * @return array - возвращает отсортированный массив (от конца к началу), всех департаментов в котором участвует пользователь
     *
     * Функция для получения пользователей в родительском департаменте пользователя.
     */
    public static function getParentDepartment(int $user, $lvl = null): array
    {
        $arrDep = [];
        $depID = \Bitrix\Main\UserTable::getList(['filter' => ['ID' => $user], 'select' => ['UF_DEPARTMENT']])->Fetch()['UF_DEPARTMENT'];
        
        foreach($depID as $v){//поддержка нескольких веток, lvl у всех общий
            $i=0;
            $k = $v;
            
            while(true){
                if($lvl>0&&$i>$lvl) break;//оптимизация - нет смысла идти дальше по ветке, если мы уже находимся на lvl
                
                $que = config::DepartmentStructure(['DEPTH_LEVEL' => 'desc'], ['ID'=>$v, 'DEPTH_LEVEL', 'IBLOCK_SECTION_ID', 'UF_HEAD']);//добавить возможность прохода по нескольким веткам
                $dataDep = $que->fetch();
                
                $arrDep[$k][] = $dataDep;
                
                $v = $dataDep['IBLOCK_SECTION_ID'];

                if(!$dataDep['IBLOCK_SECTION_ID']) break;
            }
        }
        return $arrDep;
    }

    
    /**
     * @param $children_id - ID дочернего отдела, кот которого будет проходить поиск
     * @param $data - дата, по которой ищем дерево
     * @param $field - по какому параметру искать родителя (IBLOCK_SECTION_ID)
     * @param array $parents - для рекурсии
     * @return array
     *
     * !!!УСТАРЕВШИЙ МЕТОД!!! - УЛУЧШЕННАЯ РЕАЛИЗАЦИЯ В МЕТОДЕ self::getParentDepartment
     * Получить древовидную последовательность
     * @todo функция перегружена, так как загружает все департаменты, есть смысл выгружать исключительно дерево
     */
    public static function getTree($children_id, array $data, string $field, array $parents = []): array
    {
        $parents[] = $data[$children_id];
        $parent_id = !!$children_id ? $data[$children_id][$field] : null;
        if(!!$parent_id) {
            $parents[] = $data[$parent_id];
            if($data[$parent_id][$field] !== null) {
                return self::getTree((string) $data[$parent_id][$field], $data, $field, $parents);
            }
        }
        return $parents;
    }

}
