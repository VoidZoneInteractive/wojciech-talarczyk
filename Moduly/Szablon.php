<?php

namespace Moduly;

class Szablon
{
    private $uzytkownik = false;

    public function __construct()
    {
        $this->uzytkownik = new Uzytkownik();
    }

    public function zwrocSzablon(string $nazwa_szablonu, ?array $parametry = null)
    {
        $szablon = file_get_contents(dirname(__FILE__) . '/../Szablony/' . $nazwa_szablonu . '.html');

        if (!is_null($parametry)) {
            $szablon = str_replace(array_keys($parametry), array_values($parametry), $szablon);
        }

        // wyczysc niedopasowane zmienne w szablonie
        $szablon = preg_replace('/%\{.+\}/U', '', $szablon);

        return $szablon;
    }
}