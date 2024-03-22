<?php
namespace GDO\DogWeather\Method;

use GDO\Address\GDO_Address;
use GDO\Core\Application;
use GDO\Core\GDO_ArgError;
use GDO\Core\GDT;
use GDO\Core\GDT_String;
use GDO\Country\GDO_Country;
use GDO\Dog\DOG_User;
use GDO\Dog\GDT_DogUser;
use GDO\Form\GDT_AntiCSRF;
use GDO\Form\GDT_Form;
use GDO\Form\GDT_Submit;
use GDO\Form\MethodForm;
use GDO\Language\Trans;
use GDO\Maps\GDT_Position;
use GDO\Maps\Position;
use GDO\Net\HTTP;
use GDO\UI\GDT_HTML;
use GDO\User\GDO_User;

final class Weather extends MethodForm
{

    public function isCLI(): bool
    {
        return true;
    }

    public function getCLITrigger(): string
    {
        return 'weather';
    }

    protected function createForm(GDT_Form $form): void
    {
        $form->addFields(
            GDT_DogUser::make('for'),
            GDT_String::make('in'),
        );
        if (module_enabled('Maps'))
        {
            $form->addField(GDT_Position::make('at'));
        }
        $form->addField(GDT_AntiCSRF::make());
        $form->actions()->addField(GDT_Submit::make());
    }

    /**
     * @throws GDO_ArgError
     */
    public function getDogUser(): ?Dog_User
    {
        return $this->gdoParameterValue('for');
    }

    /**
     * @throws GDO_ArgError
     */
    public function getPosition(): ?Position
    {
        if (module_enabled('Maps'))
        {
            return $this->gdoParameterValue('at');
        }
        return null;
    }

    /**
     * @throws GDO_ArgError
     */
    public function getLocation(): ?string
    {
        return $this->gdoParameterVar('in');
    }

    public function formValidated(GDT_Form $form): GDT
    {
        $have = 0;
        if ($pos = $this->getPosition())
        {
            $have++;
        }
        if ($loc = $this->getLocation())
        {
            $have++;
        }
        if ($user = $this->getDogUser())
        {
            $have++;
            $user = $user->getGDOUser();
        }
        if ($have === 0)
        {
            $user = GDO_User::current();
            $have++;
        }
        if ($have !== 1)
        {
            return $this->error('err_weather_info');
        }

        return $this->showWeather($user, $pos, $loc);
    }

    public function showWeather(?GDO_User $user, ?Position $pos, ?string $loc): GDT
    {
        $query = '';
        if ($user)
        {
            if (module_enabled('Address'))
            {
                /** @var GDO_Address $address */
                if ($address = $user->settingValue('Address', 'address'))
                {
                    $query = $address->getCity();
                    if ($country = $address->getCountry())
                    {
                        $query .= ",{$country->displayEnglishName()}";
                    }
                }
//                if ($city = $user->settingVar('Address', 'address_city'))
//                {
//                    $query = $city;
//                    if ($country = $user->settingValue('Address', 'address_country'))
//                    {
//                        /** @var GDO_Country $country **/
//
//                    }
//                }
            }
            if (module_enabled('Maps'))
            {
                if (!$query)
                {
                    if ($pos = $user->settingValue('Maps', 'position'))
                    {
                        /** @var Position $pos **/
                        $query = $pos->getLat() . ',' . $pos->getLng();
                    }
                }
            }
        }
        elseif ($pos)
        {
            $query = $pos->getLat() . ',' . $pos->getLng();
        }
        elseif ($loc)
        {
            $query = $loc;
        }

        if (!$query)
        {
            $query = '@wechall.net';
        }
        $query = urlencode($query);
        $lang = Trans::$ISO;
        $url = "https://wttr.in/{$query}?lang={$lang}";
        if (Application::instance()->isCLI())
        {
            $url .= '&format=4';
        }

        var_dump($url);

        $response = HTTP::getFromURL($url);
        return GDT_HTML::make()->var($response);
    }

}
