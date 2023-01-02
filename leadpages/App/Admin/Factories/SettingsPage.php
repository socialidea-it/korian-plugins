<?php

namespace LeadpagesWP\Admin\Factories;

use TheLoop\Contracts\Factory;
use LeadpagesWP\Config\LpConfig;

class SettingsPage implements Factory
{
    public static function create($settingsPage)
    {
        $settingsPage = new $settingsPage();
        $settingsPage->registerPage();
    }
}
