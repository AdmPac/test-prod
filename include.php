<?

use Bitrix\Main\Localization\Loc;

CModule::IncludeModule("highloadblock");


// Основные константы
define('NEBO_CASHREGISTER_MODULE_ID', basename(__DIR__));

// Данные о версии модуля
require __DIR__ . '/install/version.php';

foreach ($arModuleVersion as $key => $value) {
    define('NEBO_CASHREGISTER_' . $key, $value);
}


CModule::AddAutoloadClasses(
    "nebo.cashregister",
    array(
        "\\Nebo\\CashRegister\\Lists" => "classes/general/lists.php",
        "\\Nebo\\CashRegister\\Presets" => "classes/general/presets.php",
        "\\Nebo\\CashRegister\\Config" => "lib/helpers/config.php",
        "\\Nebo\\CashRegister\\Act" => "classes/general/act.php",
        "\\Nebo\\CashRegister\\Access" => "classes/general/access.php",
        // REST API controllers
        "\\Nebo\\CashRegister\\Api\\Act" => "lib/api/general/act.php",
        "\\Nebo\\CashRegister\\Api\\Lists" => "lib/api/general/lists.php",
        "\\Nebo\\CashRegister\\Api\\Presets" => "lib/api/general/presets.php",
        "\\Nebo\\CashRegister\\Api\\Access" => "lib/api/general/access.php",
        "\\Nebo\\CashRegister\\Api\\Accesses" => "lib/api/general/accesses.php",
        "\\Nebo\\CashRegister\\Api\\Settings" => "lib/api/general/settings.php",
    )
);
