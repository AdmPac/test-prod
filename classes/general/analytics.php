<?php
namespace Nebo\CashRegister;

use \Bitrix\Main\Localization\Loc as Loc; 
use Nebo\CashRegister\config as config;

class analytics
{

    /**
     * @param array $arr - массив данных, полученный через analitics::GetByID()
     * @param array $groupRules - массив с полями, которые нужно сгруппировать
     * Группирует существующий массив по одному полю:
     * 1. $groupRules = ['DATE'] - группировка по полю DATE
     * 2. $groupRules = ['ACT'=>'ФОТр'] - группировка по полю 'ACT' с определенным значением('ФОТр')
     * 3. $groupRules = ['ACT'=>['STATUS']] - группировка по полю 'ACT' с дальнейшей группировкой по полю 'STATUS'
     * @return array
     */
    public static function GroupByRules(array $arr, array $groupRules): array 
    {
        $na = $groupRules;
        $newArrs = [];
        
        $keys = [];
        $values = [];
        
        while(is_array($na)&&array_keys($na)[0]){//перечисляем все переданные ключи, чтобы сделать группы
            $key = array_keys($na)[0];

            if($key!='value'){
                $keys[] = $key;
                $values[] = $na[$key]['value'];
            }
            $na = end($na);
        }
        $map = array_combine($keys,$values);
        
                
        foreach($arr as $k=>$v){
            $keyV = array_keys($v);
            if(count(array_diff($keyV,$keys))+count($keys)!=count($v))//где-то указан несуществующий ключ
                continue;
            $str = '';
            $i=0;

            foreach($map as $key=>$value){
                $marker = true;//считаем, что все ок
                if(isset($v[$key])){
                    if($value){
                        if(is_array($value)){
                            if(!in_array($v[$key],$value)){
                                continue(2);
                            }
                        }else{
                            if($value!=$v[$key]){
                                continue(2);
                            }
                        }
                    }
                }
                $str.='[$v[$keys['.$i.']]]';
                $i++;
            }
            eval('$newArrs'.$str.'[] = $v;');
        }

        return $newArrs;
    }

    /**
     * @param int $idEntity - id сделки
     * @param array $select=null - возвращаемые поля
     * 
     * @return array - по id сделки возвращает данные по аналогии с getList битрикса
     */
    public static function GetByID(int $idEntity, array $select=null) : array
    {
        $selectIB = ['ID','PROPERTY_'.config::CASHREGISTER_ENTITY_FIELD];
        $arrDataActs = [];

        //берем id актов по id сущности
        $arr = \CIBlockElement::GetList([], [
            "IBLOCK_ID"=>lists::getBlockID(config::LIST_CASHREGISTER_MAIN_CODE),
            'PROPERTY_'.config::CASHREGISTER_ENTITY_FIELD=>$idEntity,
        ], false, false, $selectIB);

        $newArr = [];
        while($id = $arr->fetch()){

            $arrAct = \CIBlockElement::GetPropertyValues(
                lists::getBlockID(config::LIST_CASHREGISTER_MAIN_CODE),
                ['ID'=>$id['ID']],
                false,
                []
            );
    
            $dataAct = $arrAct->Fetch();
            $act = \CIBlockElement::GetByID($dataAct['1742'])->fetch();
            
            $arrDataActs[] = [
                'ACT'=>$act['NAME'],
                config::CASHREGISTER_STATUS_FIELD=>$dataAct['1743'],
                config::CASHREGISTER_SUM_FIELD=>$dataAct['1744'],
                'DATE'=>$dataAct['1745'],
                'APPROVED'=>$dataAct['1746'],
                'COMMENT'=>$dataAct['1747'],
                'FORMAT'=>$dataAct['1748'],
                'UID'=>$dataAct['1749'],
                'ID_ENTITY'=>$dataAct['1750'],
                'ID'=>$id['ID'],
            ];
            
            if(!$select){
                $newArr = $arrDataActs;
                continue;
            }

            $i = count($arrDataActs)-1;
            foreach($select as $field){
                if(array_key_exists($field,$arrDataActs[$i])){
                    $newArr[$i][$field] = $arrDataActs[$i][$field];
                }
            }
        }

        return $newArr;
    }

    /**
    * @param array $arr
    * 
    * @return int - максимальная вложенность(уровень) массива
    * Вычисляет максимальную вложенность массива
    */
    static private function cntLvlArray(array $arr) : int 
    {
        $na = $arr;
        $lvl = -1;
        while(is_array($na)) {
            $na = end($na);
            $lvl++;
        }
        return $lvl;
    }

    
    /**
    * @param array $arr
    * 
    * @return array - Возвращает предпоследние элементы массива
    * 
    */
    static private function prelast(array &$arr) : array
    {
        $na = $arr;
        $cntLvl = self::cntLvlArray($arr);
        $j = 1;
                
        while($j<$cntLvl-1||$j<2){//костыль для двумерного массива(цикл выполняется хотя бы раз)
            $newArr = [];
            for($i=0; $i<count($na); $i++) {
                $newArr = array_merge($newArr,array_values($na)[$i]);

                if($j+1>=$cntLvl-1){//если мы на предпоследнем элементе
                    foreach($arr[array_keys($arr)[$i]] as $k=>$v){
                        $arr[array_keys($arr)[$i]][$k] = [];
                    }
                }
            }
            
            $na = $newArr;

            $j++;
        }
        return $na;
    }

    /**
    * @param array $arr - массив для вычислений
    * @param string $field - по какому полю производить вычисление
    * 
    * @return array - массив с полем $field после вычисления
    * @todo - можно добавить(при масштабировании) выполнение любой операции(+,-,* и тд) по многим полям(опять же, если добавятся поля, которые нужно вычислять)
    */
    static public function sumField(array $arr, string $field = config::CASHREGISTER_SUM_FIELD) : array 
    {
        $na = self::prelast($arr);
        $status = [
            config::CASHREGISTER_DEF_ACCESS_STATUS,
            config::CASHREGISTER_ACCEPTED_ACCESS_STATUS,
            config::CASHREGISTER_REJECTED_ACCESS_STATUS,
            config::CASHREGISTER_FORMAT_COMING,
            config::CASHREGISTER_FORMAT_CONSUMPTION,
        ];

        $marker = false;
        foreach($na as $kPre=>$vPreArr) {//вычисляем сумму по полю
            if(is_int($kPre)&&!in_array($kPre,$status)){//костыль
                $newArr += $vPreArr[$field];
                $marker = true;
            }else{
                $newArr[$kPre] = 0;
                for($i=0; $i<count($vPreArr); $i++){
                    $newArr[$kPre] += $vPreArr[$i][$field];
                }
            }
        }

        foreach($arr as $kPre=>$vPre) {
            if($marker){
                $arr[$kPre] = $newArr;
                continue;
            }
            foreach($vPre as $kLast=>$vLast) {
                if(array_key_exists($kLast,$newArr)){
                    $arr[$kPre][$kLast] = $newArr[$kLast];
                }
            }
        }
        
        return $arr;
    }
    
}