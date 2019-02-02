<?php

namespace Moduly;

/**
 * Class Szablon
 *
 * @package Moduly
 *
 * Klasa szablonu służy do pobierania danych odpowiedniego szablonu i zwracanie przetworzonego szablonu
 * Jest to potem obsługiwane w głównej akcji w index.php
 */
class Szablon
{
    private $uzytkownik = false;

    /**
     * Szablon constructor.
     *
     * Konstruktor klasy - inicjuje klasę użytkownika, k†órą zapisuje w zmiennej $uzytkownik
     */
    public function __construct()
    {
        $this->uzytkownik = new Uzytkownik();
    }

    /**
     * @param string $nazwa_szablonu
     * @param array|null $parametry
     * @return bool|mixed|null|string|string[]
     *
     * Na podstawie nazwy strony i parametrów zwraca przetworzony szablon z danymi.
     */
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