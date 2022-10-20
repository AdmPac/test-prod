<?php
return [
    // Указываем пути для обработки запросов API
    'controllers' => [
        'value' => [
            // По умолчанию поиск контроллеров API в таком пространстве имён
            'defaultNamespace' => '\\Nebo\\CashRegister\\Api',
            // Включаем интеграцию с API
            'restIntegration' => [
                'enabled' => true,
            ],
        ],
        'readonly' => false,
    ],
];
