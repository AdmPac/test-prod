# module cashregister
nebo.dev -> crm-nebo-cashregister

## Введение

Основной модуль работы с элементами кассы, добавление/изменение/регистрация/работа с доступами.
Модуль в первую очередь разработан именно для взаимодействия кассы, корректного заполнения элементов, 
утверждения элементов кассы и работы с доступами.

## Содержание
1. Дерево

## Tree
* [classes]()
    * [general]()
      * [Access]()
        * [public method setRules()]()
        * [public method formatDefaultRules()]()
        * [public method formatStatusRules()]()
        * [public method getList()]()
        * [public method getRulesExp()]()
        * [private method _formatRules()]()
      * [Act]()
          * [public method add()]()
          * [private method formatData()]()
      * [Lists]()
          * [public method getBlockID()]()
          * [public method getRights()]()
      * [Presets]()
          * [public method getList()]()
          * [public method getRules()]()
          * [public method checkRules()]()
          * [public method checkRulesShort()]()
* [install]()
* [lang]()
* [lib]()
  * [helpers]()
      * [config]()
        * [public method status()]()
        * [public method generationUid()]()
        * [public method checkType()]()
        * [public method checkRulesShort()]()
        * [public method DepartmentStructure()]()
        * [public method getParentDepartment()]()
        * [public method getTree()]()
      * [options]()
* [include]()


### Classes
Основной модуль обработки классов
