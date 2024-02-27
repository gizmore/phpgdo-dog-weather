<?php
namespace GDO\DogWeather;

use GDO\Core\GDO_Module;

final class Module_DogWeather extends GDO_Module
{

    public function onLoadLanguage(): void
    {
        $this->loadLanguage('lang/weather');
    }

    public function getDependencies(): array
    {
        return [
            'Dog',
        ];
    }
    public function getFriendencies(): array
    {
        return [
            'Address',
            'Country',
            'Maps',
        ];
    }

}
