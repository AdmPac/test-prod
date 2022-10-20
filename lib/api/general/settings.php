<?php

namespace Nebo\CashRegister\Api;

use Nebo\CashRegister\Settings as SettingsModule;



/**
 * Класс-контроллер для взаимодействия  настройками приложения по REST-API
 * этот класс был создан потому, что нельзя взаимодействовать с HL-блоками по Rest-API
 */
class Settings extends \Bitrix\Main\Engine\Controller
{
    public function getAction(string $code = null): array
    {
        return SettingsModule::get($code);
    }
    
    public function setAction($data): array
    {
        return SettingsModule::set($data);
    }
}